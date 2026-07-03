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
}
