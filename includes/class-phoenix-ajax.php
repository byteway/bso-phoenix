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
    }

    public function start_trip(): void
    {
        $this->guard_request();

        $boat_id = isset($_POST['boat_id']) ? (int) $_POST['boat_id'] : 1;
        if ($boat_id <= 0) {
            wp_send_json_error(array('message' => 'Ongeldige boat_id.'), 400);
        }

        $service = new BSO_Phoenix_Trip_Service();
        $trip_id = $service->start_trip($boat_id);

        if ($trip_id <= 0) {
            wp_send_json_error(array('message' => 'Kon route niet starten.'), 500);
        }

        wp_send_json_success(array('trip_id' => $trip_id));
    }

    public function trackpoint(): void
    {
        $this->guard_request();

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

        $service = new BSO_Phoenix_Trip_Service();
        $saved = $service->add_trackpoint($trip_id, $latitude, $longitude, $altitude, $speed, $accuracy, $recorded_at);

        if (! $saved) {
            wp_send_json_error(array('message' => 'Kon trackpoint niet opslaan.'), 500);
        }

        wp_send_json_success(array('saved' => true));
    }

    public function stop_trip(): void
    {
        $this->guard_request();

        $trip_id = isset($_POST['trip_id']) ? (int) $_POST['trip_id'] : 0;
        if ($trip_id <= 0) {
            wp_send_json_error(array('message' => 'Ongeldige trip_id.'), 400);
        }

        $service = new BSO_Phoenix_Trip_Service();
        $stopped = $service->stop_trip($trip_id);

        if (! $stopped) {
            wp_send_json_error(array('message' => 'Kon route niet stoppen.'), 500);
        }

        wp_send_json_success(array('trip_id' => $trip_id, 'status' => 'completed'));
    }

    public function get_trip_trackpoints(): void
    {
        $this->guard_request();

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

    private function guard_request(): void
    {
        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Inloggen vereist.'), 401);
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
}
