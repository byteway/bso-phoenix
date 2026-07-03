<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Frontend
{
    public function init(): void
    {
        add_action('wp_enqueue_scripts', array($this, 'enqueue_assets'));
        add_shortcode('phoenix_dashboard', array($this, 'render_dashboard_shortcode'));
    }

    public function enqueue_assets(): void
    {
        wp_register_style(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css',
            array(),
            '1.9.4'
        );

        wp_register_style(
            'bso-phoenix-frontend',
            BSO_PHOENIX_URL . 'assets/css/phoenix-frontend.css',
            array('leaflet'),
            BSO_PHOENIX_VERSION
        );

        wp_register_script(
            'leaflet',
            'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js',
            array(),
            '1.9.4',
            true
        );

        wp_register_script(
            'bso-phoenix-frontend',
            BSO_PHOENIX_URL . 'assets/js/phoenix-frontend.js',
            array('leaflet'),
            BSO_PHOENIX_VERSION,
            true
        );

        $trip_service = new BSO_Phoenix_Trip_Service();
        $latest_trip = $trip_service->get_recent_trips(1);
        $latest_trip_id = ! empty($latest_trip[0]['id']) ? (int) $latest_trip[0]['id'] : 0;
        $latest_completed_trip = $trip_service->get_latest_completed_trip();
        $active_trip = $trip_service->get_active_trip();
        $active_trip_id = is_array($active_trip) && ! empty($active_trip['id']) ? (int) $active_trip['id'] : 0;
        $settings_service = new BSO_Phoenix_Settings_Service();

        wp_localize_script(
            'bso-phoenix-frontend',
            'bsoPhoenix',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bso_phoenix_gps'),
                'logNonce' => wp_create_nonce('bso_phoenix_log'),
                'todoNonce' => wp_create_nonce('bso_phoenix_todo'),
                'costNonce' => wp_create_nonce('bso_phoenix_cost'),
                'defaultBoatId' => 1,
                'gpsIntervalMs' => (int) $settings_service->get('gps_interval_seconds') * 1000,
                'latestTripId' => $latest_trip_id,
                'latestCompletedTrip' => $latest_completed_trip,
                'activeTripId' => $active_trip_id,
                'activeTripStartedAt' => is_array($active_trip) && ! empty($active_trip['started_at']) ? (string) $active_trip['started_at'] : '',
                'distanceUnit' => $settings_service->get_distance_unit(),
                'fuelUseLph' => (float) $settings_service->get('fuel_use_lph'),
            )
        );
    }

    public function render_dashboard_shortcode(array $atts = array()): string
    {
        wp_enqueue_style('bso-phoenix-frontend');
        wp_enqueue_script('bso-phoenix-frontend');

        ob_start();
        include BSO_PHOENIX_DIR . 'templates/frontend-dashboard.php';
        return (string) ob_get_clean();
    }
}
