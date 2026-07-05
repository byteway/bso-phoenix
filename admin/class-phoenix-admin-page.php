<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Admin_Page
{
    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_menu'));
        add_action('admin_post_bso_phoenix_export_trips_csv', array($this, 'handle_export_trips_csv'));
        add_action('admin_post_bso_phoenix_export_trip_trackpoints', array($this, 'handle_export_trip_trackpoints'));
    }

    public function register_menu(): void
    {
        add_menu_page(
            __('Phoenix Logboek', 'bso-phoenix'),
            __('Phoenix', 'bso-phoenix'),
            BSO_PHOENIX_CAP_READ,
            'bso-phoenix',
            array($this, 'render_page'),
            'dashicons-location-alt',
            56
        );
    }

    public function render_page(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_READ)) {
            wp_die(esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'bso-phoenix'));
        }

        $date_from = isset($_GET['date_from']) ? sanitize_text_field((string) $_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field((string) $_GET['date_to']) : '';
        $status = isset($_GET['status']) ? sanitize_text_field((string) $_GET['status']) : '';
        $date_from = $this->normalize_date_input($date_from);
        $date_to = $this->normalize_date_input($date_to);
        $status = $this->normalize_status_input($status);

        $service = new BSO_Phoenix_Trip_Service();
        $settings_service = new BSO_Phoenix_Settings_Service();
        $summary = $service->get_dashboard_summary();
        $recent_trips = $service->get_trips_by_date_range($date_from, $date_to, $status, 50);
        $distance_unit = $settings_service->get_distance_unit();
        $speed_unit = $settings_service->get_speed_unit();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Phoenix Logboek', 'bso-phoenix') . '</h1>';

        echo '<p>' . esc_html__('Overzicht van route-activiteit op basis van de huidige GPS-loggegevens.', 'bso-phoenix') . '</p>';

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:16px 0 20px;">';
        $this->render_stat_card(__('Totaal tochten', 'bso-phoenix'), (string) $summary['total_trips']);
        $this->render_stat_card(__('Actieve tochten', 'bso-phoenix'), (string) $summary['active_trips']);
        $this->render_stat_card(sprintf(__('Afstand totaal (%s)', 'bso-phoenix'), $distance_unit), $settings_service->format_distance((float) $summary['total_distance_km'], 2));
        $this->render_stat_card(__('Duur totaal (uur)', 'bso-phoenix'), number_format_i18n(((float) $summary['total_duration_minutes']) / 60, 2));
        $this->render_stat_card(sprintf(__('Gem. snelheid (%s)', 'bso-phoenix'), $speed_unit), $settings_service->format_speed((float) $summary['average_speed_kmh'], 2));
        echo '</div>';

        echo '<h2>' . esc_html__('Recente tochten', 'bso-phoenix') . '</h2>';

        echo '<form method="get" action="" style="display:flex;gap:8px;align-items:end;margin:8px 0 12px;flex-wrap:wrap;">';
        echo '<input type="hidden" name="page" value="bso-phoenix" />';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Vanaf', 'bso-phoenix') . '</span>';
        echo '<input type="date" name="date_from" value="' . esc_attr($date_from) . '" />';
        echo '</label>';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Tot en met', 'bso-phoenix') . '</span>';
        echo '<input type="date" name="date_to" value="' . esc_attr($date_to) . '" />';
        echo '</label>';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Status', 'bso-phoenix') . '</span>';
        echo '<select name="status">';
        echo '<option value=""' . selected($status, '', false) . '>' . esc_html__('Alle', 'bso-phoenix') . '</option>';
        echo '<option value="active"' . selected($status, 'active', false) . '>' . esc_html__('Actief', 'bso-phoenix') . '</option>';
        echo '<option value="completed"' . selected($status, 'completed', false) . '>' . esc_html__('Afgerond', 'bso-phoenix') . '</option>';
        echo '</select>';
        echo '</label>';
        submit_button(__('Filter', 'bso-phoenix'), 'secondary', 'submit', false);
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=bso-phoenix')) . '">' . esc_html__('Reset', 'bso-phoenix') . '</a>';
        echo '</form>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:8px 0 14px;">';
        echo '<input type="hidden" name="action" value="bso_phoenix_export_trips_csv" />';
        echo '<input type="hidden" name="date_from" value="' . esc_attr($date_from) . '" />';
        echo '<input type="hidden" name="date_to" value="' . esc_attr($date_to) . '" />';
        echo '<input type="hidden" name="status" value="' . esc_attr($status) . '" />';
        wp_nonce_field('bso_phoenix_export_trips_csv', 'bso_phoenix_export_nonce');
        submit_button(__('Exporteer trips naar CSV', 'bso-phoenix'), 'secondary', 'submit', false);
        echo '</form>';

        if (empty($recent_trips)) {
            echo '<p>' . esc_html__('Nog geen tochten geregistreerd.', 'bso-phoenix') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Trip', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Start', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Einde', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Status', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html(sprintf(__('Afstand (%s)', 'bso-phoenix'), $distance_unit)) . '</th>';
        echo '<th>' . esc_html__('Duur (min)', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html(sprintf(__('Gem. snelheid (%s)', 'bso-phoenix'), $speed_unit)) . '</th>';
        echo '<th>' . esc_html__('Trackpoints', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Export', 'bso-phoenix') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($recent_trips as $trip) {
            echo '<tr>';
            echo '<td>#' . esc_html((string) $trip['id']) . '</td>';
            echo '<td>' . esc_html($this->format_datetime((string) $trip['started_at'])) . '</td>';
            echo '<td>' . esc_html($this->format_datetime((string) $trip['ended_at'])) . '</td>';
            echo '<td>' . esc_html((string) $trip['status']) . '</td>';
            echo '<td>' . esc_html($settings_service->format_distance((float) $trip['distance_km'], 2)) . '</td>';
            echo '<td>' . esc_html(number_format_i18n((float) $trip['duration_minutes'], 1)) . '</td>';
            echo '<td>' . esc_html($settings_service->format_speed((float) $trip['average_speed_kmh'], 2)) . '</td>';
            echo '<td>' . (current_user_can(BSO_PHOENIX_CAP_MANAGE) ? $this->render_trip_trackpoints_link((int) $trip['id']) : esc_html__('Geen toegang', 'bso-phoenix')) . '</td>';
            echo '<td>' . $this->render_trip_export_links((int) $trip['id']) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    private function render_stat_card(string $label, string $value): void
    {
        echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px;">';
        echo '<div style="font-size:12px;color:#50575e;">' . esc_html($label) . '</div>';
        echo '<div style="font-size:22px;font-weight:600;line-height:1.3;">' . esc_html($value) . '</div>';
        echo '</div>';
    }

    private function format_datetime(string $value): string
    {
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return '-';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '-';
        }

        return wp_date('d-m-Y H:i', $timestamp);
    }

    public function handle_export_trips_csv(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_READ)) {
            wp_die(esc_html__('Je hebt geen rechten om deze actie uit te voeren.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_export_trips_csv', 'bso_phoenix_export_nonce');

        $date_from = isset($_POST['date_from']) ? sanitize_text_field((string) $_POST['date_from']) : '';
        $date_to = isset($_POST['date_to']) ? sanitize_text_field((string) $_POST['date_to']) : '';
        $status = isset($_POST['status']) ? sanitize_text_field((string) $_POST['status']) : '';
        $date_from = $this->normalize_date_input($date_from);
        $date_to = $this->normalize_date_input($date_to);
        $status = $this->normalize_status_input($status);

        $service = new BSO_Phoenix_Trip_Service();
        $settings_service = new BSO_Phoenix_Settings_Service();
        $trips = $service->get_trips_by_date_range($date_from, $date_to, $status, 1000);
        $distance_unit = $settings_service->get_distance_unit();
        $speed_unit = $settings_service->get_speed_unit();

        $range_suffix = '';
        if ($date_from !== '' || $date_to !== '' || $status !== '') {
            $range_suffix = '-' . ($date_from !== '' ? $date_from : 'start') . '-to-' . ($date_to !== '' ? $date_to : 'end');
            if ($status !== '') {
                $range_suffix .= '-' . $status;
            }
        }
        $filename = 'phoenix-trips' . $range_suffix . '-' . gmdate('Ymd-His') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('Kon CSV-output niet openen.', 'bso-phoenix'));
        }

        fputcsv($output, array('trip_id', 'started_at', 'ended_at', 'status', 'distance_' . $distance_unit, 'duration_minutes', 'average_speed_' . $speed_unit));

        foreach ($trips as $trip) {
            fputcsv(
                $output,
                array(
                    (string) $trip['id'],
                    (string) $trip['started_at'],
                    (string) $trip['ended_at'],
                    (string) $trip['status'],
                    (string) $settings_service->convert_distance_from_km((float) $trip['distance_km']),
                    (string) $trip['duration_minutes'],
                    (string) $settings_service->convert_speed_from_kmh((float) $trip['average_speed_kmh']),
                )
            );
        }

        fclose($output);
        exit;
    }

    private function normalize_date_input(string $value): string
    {
        if ($value === '') {
            return '';
        }

		return BSO_Phoenix_Hardening::normalize_date($value);
    }

    private function normalize_status_input(string $value): string
    {
        if (! in_array($value, array('', 'active', 'completed'), true)) {
            return '';
        }

        return $value;
    }

    public function handle_export_trip_trackpoints(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_READ)) {
            wp_die(esc_html__('Je hebt geen rechten om deze actie uit te voeren.', 'bso-phoenix'));
        }

        $trip_id = isset($_GET['trip_id']) ? (int) $_GET['trip_id'] : 0;
        $format = isset($_GET['format']) ? sanitize_key((string) $_GET['format']) : 'csv';

        if ($trip_id <= 0) {
            wp_die(esc_html__('Ongeldige trip-id.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_export_trip_' . $trip_id);

        if (! in_array($format, array('csv', 'gpx'), true)) {
            wp_die(esc_html__('Ongeldig exportformaat.', 'bso-phoenix'));
        }

        $service = new BSO_Phoenix_Trip_Service();
        $trip = $service->get_trip_by_id($trip_id);
        if (! is_array($trip)) {
            wp_die(esc_html__('Trip niet gevonden.', 'bso-phoenix'));
        }

        $points = $service->get_trackpoints_for_trip($trip_id);

        if ($format === 'gpx') {
            $this->download_trackpoints_gpx($trip_id, $trip, $points);
            return;
        }

        $this->download_trackpoints_csv($trip_id, $points);
    }

    private function render_trip_export_links(int $trip_id): string
    {
        $csv_url = wp_nonce_url(
            admin_url('admin-post.php?action=bso_phoenix_export_trip_trackpoints&format=csv&trip_id=' . $trip_id),
            'bso_phoenix_export_trip_' . $trip_id
        );

        $gpx_url = wp_nonce_url(
            admin_url('admin-post.php?action=bso_phoenix_export_trip_trackpoints&format=gpx&trip_id=' . $trip_id),
            'bso_phoenix_export_trip_' . $trip_id
        );

        return '<a class="button button-small" href="' . esc_url($csv_url) . '">CSV</a> '
            . '<a class="button button-small" href="' . esc_url($gpx_url) . '">GPX</a>';
    }

    private function render_trip_trackpoints_link(int $trip_id): string
    {
        $url = admin_url('admin.php?page=bso-phoenix-trackpoints&trip_id=' . $trip_id);

        return '<a class="button button-small" href="' . esc_url($url) . '">' . esc_html__('Beheer', 'bso-phoenix') . '</a>';
    }

    private function download_trackpoints_csv(int $trip_id, array $points): void
    {
        $filename = 'phoenix-trip-' . $trip_id . '-trackpoints-' . gmdate('Ymd-His') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('Kon CSV-output niet openen.', 'bso-phoenix'));
        }

        fputcsv($output, array('trip_id', 'latitude', 'longitude', 'altitude_m', 'speed_kmh', 'accuracy_m', 'recorded_at'));

        foreach ($points as $point) {
            fputcsv(
                $output,
                array(
                    (string) $point['trip_id'],
                    (string) $point['latitude'],
                    (string) $point['longitude'],
                    (string) $point['altitude_m'],
                    (string) $point['speed_kmh'],
                    (string) $point['accuracy_m'],
                    (string) $point['recorded_at'],
                )
            );
        }

        fclose($output);
        exit;
    }

    private function download_trackpoints_gpx(int $trip_id, array $trip, array $points): void
    {
        $filename = 'phoenix-trip-' . $trip_id . '-' . gmdate('Ymd-His') . '.gpx';

        nocache_headers();
        header('Content-Type: application/gpx+xml; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        echo '<?xml version="1.0" encoding="UTF-8"?>';
        echo '<gpx version="1.1" creator="BSO Phoenix" xmlns="http://www.topografix.com/GPX/1/1">';
        echo '<metadata><name>' . esc_html('Phoenix Trip #' . $trip_id) . '</name></metadata>';
        echo '<trk><name>' . esc_html('Trip #' . $trip_id) . '</name><trkseg>';

        foreach ($points as $point) {
            $lat = (string) $point['latitude'];
            $lon = (string) $point['longitude'];
            $ele = isset($point['altitude_m']) ? (string) $point['altitude_m'] : '';
            $recorded_at = (string) $point['recorded_at'];
            $timestamp = strtotime($recorded_at);

            echo '<trkpt lat="' . esc_attr($lat) . '" lon="' . esc_attr($lon) . '">';
            if ($ele !== '') {
                echo '<ele>' . esc_html($ele) . '</ele>';
            }
            if ($timestamp !== false) {
                echo '<time>' . esc_html(gmdate('c', $timestamp)) . '</time>';
            }
            echo '</trkpt>';
        }

        echo '</trkseg></trk></gpx>';
        exit;
    }
}
