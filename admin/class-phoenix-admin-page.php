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
            'manage_options',
            'bso-phoenix',
            array($this, 'render_page'),
            'dashicons-location-alt',
            56
        );
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'bso-phoenix'));
        }

        $service = new BSO_Phoenix_Trip_Service();
        $summary = $service->get_dashboard_summary();
        $recent_trips = $service->get_recent_trips(12);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Phoenix Logboek', 'bso-phoenix') . '</h1>';

        echo '<p>' . esc_html__('Overzicht van route-activiteit op basis van de huidige GPS-loggegevens.', 'bso-phoenix') . '</p>';

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:16px 0 20px;">';
        $this->render_stat_card(__('Totaal tochten', 'bso-phoenix'), (string) $summary['total_trips']);
        $this->render_stat_card(__('Actieve tochten', 'bso-phoenix'), (string) $summary['active_trips']);
        $this->render_stat_card(__('Afstand totaal (km)', 'bso-phoenix'), number_format_i18n((float) $summary['total_distance_km'], 2));
        $this->render_stat_card(__('Duur totaal (uur)', 'bso-phoenix'), number_format_i18n(((float) $summary['total_duration_minutes']) / 60, 2));
        $this->render_stat_card(__('Gem. snelheid (km/u)', 'bso-phoenix'), number_format_i18n((float) $summary['average_speed_kmh'], 2));
        echo '</div>';

        echo '<h2>' . esc_html__('Recente tochten', 'bso-phoenix') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:8px 0 14px;">';
        echo '<input type="hidden" name="action" value="bso_phoenix_export_trips_csv" />';
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
        echo '<th>' . esc_html__('Afstand (km)', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Duur (min)', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Gem. snelheid (km/u)', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Export', 'bso-phoenix') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($recent_trips as $trip) {
            echo '<tr>';
            echo '<td>#' . esc_html((string) $trip['id']) . '</td>';
            echo '<td>' . esc_html($this->format_datetime((string) $trip['started_at'])) . '</td>';
            echo '<td>' . esc_html($this->format_datetime((string) $trip['ended_at'])) . '</td>';
            echo '<td>' . esc_html((string) $trip['status']) . '</td>';
            echo '<td>' . esc_html(number_format_i18n((float) $trip['distance_km'], 2)) . '</td>';
            echo '<td>' . esc_html(number_format_i18n((float) $trip['duration_minutes'], 1)) . '</td>';
            echo '<td>' . esc_html(number_format_i18n((float) $trip['average_speed_kmh'], 2)) . '</td>';
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
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Je hebt geen rechten om deze actie uit te voeren.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_export_trips_csv', 'bso_phoenix_export_nonce');

        $service = new BSO_Phoenix_Trip_Service();
        $trips = $service->get_recent_trips(1000);

        $filename = 'phoenix-trips-' . gmdate('Ymd-His') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('Kon CSV-output niet openen.', 'bso-phoenix'));
        }

        fputcsv($output, array('trip_id', 'started_at', 'ended_at', 'status', 'distance_km', 'duration_minutes', 'average_speed_kmh'));

        foreach ($trips as $trip) {
            fputcsv(
                $output,
                array(
                    (string) $trip['id'],
                    (string) $trip['started_at'],
                    (string) $trip['ended_at'],
                    (string) $trip['status'],
                    (string) $trip['distance_km'],
                    (string) $trip['duration_minutes'],
                    (string) $trip['average_speed_kmh'],
                )
            );
        }

        fclose($output);
        exit;
    }

    public function handle_export_trip_trackpoints(): void
    {
        if (! current_user_can('manage_options')) {
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
