<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Ajax
{
    public function init(): void
    {
        add_action('wp_ajax_bso_phoenix_start_trip', array($this, 'start_trip'));
        add_action('wp_ajax_bso_phoenix_trackpoint', array($this, 'trackpoint'));
        add_action('wp_ajax_bso_phoenix_stop_trip', array($this, 'stop_trip'));
        add_action('wp_ajax_bso_phoenix_get_trip_trackpoints', array($this, 'get_trip_trackpoints'));
        add_action('wp_ajax_bso_phoenix_get_trip_summaries', array($this, 'get_trip_summaries'));
        add_action('wp_ajax_bso_phoenix_download_trip_gpx', array($this, 'download_trip_gpx'));
    }

    public function start_trip(): void
    {
		$this->guard_request(BSO_PHOENIX_CAP_WRITE);

        if (BSO_Phoenix_Hardening::is_duplicate_submission('ajax_start_trip', array(
            'request_uid' => sanitize_text_field((string) ($_POST['request_uid'] ?? '')),
            'boat_id' => (int) ($_POST['boat_id'] ?? 1),
        ), 12)) {
            wp_send_json_error(array('message' => 'Dubbele start-aanvraag gedetecteerd. Wacht even en vernieuw de status.'), 409);
        }

        $boat_id = isset($_POST['boat_id']) ? (int) $_POST['boat_id'] : 1;
        if ($boat_id <= 0) {
            wp_send_json_error(array('message' => 'Ongeldige boat_id.'), 400);
        }

        $service = new BSO_Phoenix_Trip_Service();
        $existing_active_trip = $service->get_active_trip();
        $trip_id = $service->start_trip($boat_id);
        $active_trip = $service->get_active_trip();

        if ($trip_id <= 0) {
            wp_send_json_error(array('message' => 'Kon route niet starten.'), 500);
        }

        wp_send_json_success(
            array(
                'trip_id' => $trip_id,
                'already_active' => is_array($existing_active_trip) && ! empty($existing_active_trip['id']),
                'started_at' => is_array($active_trip) && ! empty($active_trip['started_at']) ? (string) $active_trip['started_at'] : null,
            )
        );
    }

    public function trackpoint(): void
    {
		$this->guard_request(BSO_PHOENIX_CAP_WRITE);

        $trip_id = isset($_POST['trip_id']) ? (int) $_POST['trip_id'] : 0;
        $latitude = isset($_POST['latitude']) ? (float) $_POST['latitude'] : null;
        $longitude = isset($_POST['longitude']) ? (float) $_POST['longitude'] : null;

        if ($trip_id <= 0 || $latitude === null || $longitude === null) {
            wp_send_json_error(array('message' => 'trip_id, latitude en longitude zijn verplicht.'), 400);
        }

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            wp_send_json_error(array('message' => 'Ongeldige GPS-coordinaten.'), 400);
        }

        $altitude = isset($_POST['altitude']) ? (float) $_POST['altitude'] : null;
        $speed = isset($_POST['speed']) ? (float) $_POST['speed'] : null;
        $accuracy = isset($_POST['accuracy']) ? (float) $_POST['accuracy'] : null;
        $recorded_at = $this->parse_recorded_at(isset($_POST['recorded_at']) ? (string) $_POST['recorded_at'] : '');

        if (BSO_Phoenix_Hardening::is_duplicate_submission('ajax_trackpoint', array(
            'trip_id' => $trip_id,
            'latitude' => round((float) $latitude, 6),
            'longitude' => round((float) $longitude, 6),
            'recorded_at' => (string) ($recorded_at ?: ''),
        ), 15)) {
            wp_send_json_success(array('saved' => true, 'duplicate' => true));
        }

        $service = new BSO_Phoenix_Trip_Service();
        $saved = $service->add_trackpoint($trip_id, $latitude, $longitude, $altitude, $speed, $accuracy, $recorded_at);

        if (! $saved) {
			wp_send_json_error(array('message' => 'Trackpoint geweigerd: trip bestaat niet of is niet actief.'), 409);
        }

        wp_send_json_success(array('saved' => true));
    }

    public function stop_trip(): void
    {
		$this->guard_request(BSO_PHOENIX_CAP_WRITE);

        $service = new BSO_Phoenix_Trip_Service();

        if (BSO_Phoenix_Hardening::is_duplicate_submission('ajax_stop_trip', array(
            'request_uid' => sanitize_text_field((string) ($_POST['request_uid'] ?? '')),
            'trip_id' => (int) ($_POST['trip_id'] ?? 0),
        ), 12)) {
            wp_send_json_error(array('message' => 'Dubbele stop-aanvraag gedetecteerd. Controleer of de trip al is afgerond.'), 409);
        }

        $trip_id = isset($_POST['trip_id']) ? (int) $_POST['trip_id'] : 0;
        if ($trip_id <= 0) {
            $active_trip = $service->get_active_trip();
            if (! is_array($active_trip) || empty($active_trip['id'])) {
                wp_send_json_error(array('message' => 'Geen actieve route om te stoppen.'), 409);
            }

            $trip_id = (int) $active_trip['id'];
        }

        $stopped = $service->stop_trip($trip_id);

        if (! $stopped) {
            wp_send_json_error(array('message' => 'Kon route niet stoppen.'), 500);
        }

        wp_send_json_success(array('trip_id' => $trip_id, 'status' => 'completed'));
    }

    public function get_trip_trackpoints(): void
    {
		$this->guard_request(BSO_PHOENIX_CAP_READ);

        $trip_id = isset($_POST['trip_id']) ? (int) $_POST['trip_id'] : 0;
        if ($trip_id <= 0) {
            wp_send_json_error(array('message' => 'Ongeldige trip_id.'), 400);
        }

        $service = new BSO_Phoenix_Trip_Service();
        $trip = $service->get_trip_by_id($trip_id);
        if (! is_array($trip)) {
            wp_send_json_error(array('message' => 'Trip niet gevonden.'), 404);
        }

        $points = $service->get_trackpoints_for_trip($trip_id);

        wp_send_json_success(
            array(
                'trip' => $trip,
                'trackpoints' => $points,
            )
        );
    }

    public function get_trip_summaries(): void
    {
		$this->guard_request(BSO_PHOENIX_CAP_READ);

        $limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 10;
        $limit = max(1, min(50, $limit));

        $service = new BSO_Phoenix_Trip_Service();
        $trips = $service->get_trips_by_date_range(null, null, 'completed', $limit);

        $summaries = array_map(function (array $trip): array {
            $trip_id = isset($trip['id']) ? (int) $trip['id'] : 0;
            $duration_minutes = isset($trip['duration_minutes']) ? (float) $trip['duration_minutes'] : 0.0;
            $distance_km = isset($trip['distance_km']) ? (float) $trip['distance_km'] : 0.0;
            $average_speed_kmh = isset($trip['average_speed_kmh']) ? (float) $trip['average_speed_kmh'] : 0.0;
            $estimated_fuel_used_l = isset($trip['estimated_fuel_used_l']) ? (float) $trip['estimated_fuel_used_l'] : 0.0;
            $download_url = add_query_arg(
                array(
                    'action' => 'bso_phoenix_download_trip_gpx',
                    'trip_id' => $trip_id,
                    'nonce' => wp_create_nonce('bso_phoenix_gps'),
                ),
                admin_url('admin-ajax.php')
            );

            return array(
                'id' => $trip_id,
                'started_at' => isset($trip['started_at']) ? (string) $trip['started_at'] : '',
                'ended_at' => isset($trip['ended_at']) ? (string) $trip['ended_at'] : '',
                'duration_minutes' => $duration_minutes,
                'distance_km' => $distance_km,
                'average_speed_kmh' => $average_speed_kmh,
                'estimated_fuel_used_l' => $estimated_fuel_used_l,
                'download_url' => esc_url_raw($download_url),
            );
        }, $trips);

        wp_send_json_success(array('trips' => $summaries));
    }

    public function download_trip_gpx(): void
    {
        if (! is_user_logged_in()) {
            wp_die('Inloggen vereist.', 'Unauthorized', array('response' => 401));
        }

        if (! current_user_can(BSO_PHOENIX_CAP_READ)) {
            wp_die('Onvoldoende rechten.', 'Forbidden', array('response' => 403));
        }

        $nonce = isset($_GET['nonce']) ? sanitize_text_field((string) $_GET['nonce']) : '';
        if (! wp_verify_nonce($nonce, 'bso_phoenix_gps')) {
            wp_die('Ongeldige nonce.', 'Forbidden', array('response' => 403));
        }

        $trip_id = isset($_GET['trip_id']) ? (int) $_GET['trip_id'] : 0;
        if ($trip_id <= 0) {
            wp_die('Ongeldige trip_id.', 'Bad Request', array('response' => 400));
        }

        $service = new BSO_Phoenix_Trip_Service();
        $trip = $service->get_trip_by_id($trip_id);
        if (! is_array($trip)) {
            wp_die('Trip niet gevonden.', 'Not Found', array('response' => 404));
        }

        $points = $service->get_trackpoints_for_trip($trip_id);
        if (empty($points)) {
            wp_die('Geen trackpoints beschikbaar voor deze trip.', 'Not Found', array('response' => 404));
        }

        $valid_points = array_values(array_filter($points, function (array $point): bool {
            $lat = isset($point['latitude']) ? (float) $point['latitude'] : null;
            $lon = isset($point['longitude']) ? (float) $point['longitude'] : null;

            if ($lat === null || $lon === null) {
                return false;
            }

            return $this->is_valid_coordinate($lat, $lon);
        }));

        if (empty($valid_points)) {
            wp_die('Geen geldige GPS-trackpoints beschikbaar voor deze trip.', 'Unprocessable Entity', array('response' => 422));
        }

        $gpx = $this->build_gpx_xml($trip, $valid_points);
        $filename = sanitize_file_name('phoenix-trip-' . $trip_id . '.gpx');

        nocache_headers();
        header('Content-Type: application/gpx+xml; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        echo $gpx; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
        exit;
    }

    private function build_gpx_xml(array $trip, array $points): string
    {
        $trip_id = isset($trip['id']) ? (int) $trip['id'] : 0;
        $name = 'Phoenix Trip #' . $trip_id;
        $started_at = isset($trip['started_at']) ? (string) $trip['started_at'] : '';

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= '<gpx version="1.1" creator="BSO Phoenix" xmlns="http://www.topografix.com/GPX/1/1">';
        $xml .= '<metadata>';
        $xml .= '<name>' . esc_html($name) . '</name>';
        if ($started_at !== '') {
            $timestamp = strtotime($started_at);
            if ($timestamp !== false) {
                $xml .= '<time>' . gmdate('c', $timestamp) . '</time>';
            }
        }
        $xml .= '</metadata>';
        $xml .= '<trk><name>' . esc_html($name) . '</name><trkseg>';

        foreach ($points as $point) {
            $lat = isset($point['latitude']) ? (float) $point['latitude'] : 0.0;
            $lon = isset($point['longitude']) ? (float) $point['longitude'] : 0.0;
            $time = isset($point['recorded_at']) ? (string) $point['recorded_at'] : '';

            $xml .= '<trkpt lat="' . esc_attr((string) $lat) . '" lon="' . esc_attr((string) $lon) . '">';
            if ($time !== '') {
                $timestamp = strtotime($time);
                if ($timestamp !== false) {
                    $xml .= '<time>' . gmdate('c', $timestamp) . '</time>';
                }
            }
            $xml .= '</trkpt>';
        }

        $xml .= '</trkseg></trk></gpx>';

        return $xml;
    }

	private function guard_request(string $required_cap): void
    {
        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Inloggen vereist.'), 401);
        }

		if (! current_user_can($required_cap)) {
			wp_send_json_error(array('message' => 'Onvoldoende rechten.'), 403);
		}

        $nonce = isset($_POST['nonce']) ? sanitize_text_field((string) $_POST['nonce']) : '';
        if (! wp_verify_nonce($nonce, 'bso_phoenix_gps')) {
            wp_send_json_error(array('message' => 'Ongeldige nonce.'), 403);
        }
    }

    private function parse_recorded_at(string $raw): ?string
    {
        if ($raw === '') {
            return null;
        }

        if (ctype_digit($raw)) {
            $timestamp = (int) $raw;
            if ($timestamp > 9999999999) {
                $timestamp = (int) floor($timestamp / 1000);
            }
            return gmdate('Y-m-d H:i:s', $timestamp);
        }

        $timestamp = strtotime($raw);
        if ($timestamp === false) {
            return null;
        }

        return gmdate('Y-m-d H:i:s', $timestamp);
    }

    private function is_valid_coordinate(float $latitude, float $longitude): bool
    {
        return $latitude >= -90.0 && $latitude <= 90.0 && $longitude >= -180.0 && $longitude <= 180.0;
    }
}
