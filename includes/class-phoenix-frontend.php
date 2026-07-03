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
            'bso-phoenix-frontend',
            BSO_PHOENIX_URL . 'assets/css/phoenix-frontend.css',
            array(),
            BSO_PHOENIX_VERSION
        );

        wp_register_script(
            'bso-phoenix-frontend',
            BSO_PHOENIX_URL . 'assets/js/phoenix-frontend.js',
            array(),
            BSO_PHOENIX_VERSION,
            true
        );

        wp_localize_script(
            'bso-phoenix-frontend',
            'bsoPhoenix',
            array(
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('bso_phoenix_gps'),
                'logNonce' => wp_create_nonce('bso_phoenix_log'),
                'todoNonce' => wp_create_nonce('bso_phoenix_todo'),
                'defaultBoatId' => 1,
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
