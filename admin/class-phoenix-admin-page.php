<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Admin_Page
{
    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_menu'));
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

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Phoenix Logboek', 'bso-phoenix') . '</h1>';
        echo '<p>' . esc_html__('Deze basispagina wordt in volgende commits uitgebreid met bootprofiel, instellingen en beheerflows.', 'bso-phoenix') . '</p>';
        echo '<ul>';
        echo '<li>' . esc_html__('Bootprofiel beheren', 'bso-phoenix') . '</li>';
        echo '<li>' . esc_html__('Route- en GPS-instellingen', 'bso-phoenix') . '</li>';
        echo '<li>' . esc_html__('TODO en kostenbeheer', 'bso-phoenix') . '</li>';
        echo '</ul>';
        echo '</div>';
    }
}
