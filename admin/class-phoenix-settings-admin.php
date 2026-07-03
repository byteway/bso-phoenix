<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Settings_Admin
{
    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_submenu'));
        add_action('admin_post_bso_phoenix_save_settings', array($this, 'handle_save_settings'));
    }

    public function register_submenu(): void
    {
        add_submenu_page(
            'bso-phoenix',
            __('Instellingen', 'bso-phoenix'),
            __('Instellingen', 'bso-phoenix'),
            BSO_PHOENIX_CAP_MANAGE,
            'bso-phoenix-settings',
            array($this, 'render_page')
        );
    }

    public function render_page(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_MANAGE)) {
            wp_die(esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'bso-phoenix'));
        }

        $service = new BSO_Phoenix_Settings_Service();
        $settings = $service->get_all();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Instellingen', 'bso-phoenix') . '</h1>';

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Instellingen opgeslagen.', 'bso-phoenix') . '</p></div>';
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="bso_phoenix_save_settings" />';
        wp_nonce_field('bso_phoenix_save_settings', 'bso_phoenix_settings_nonce');

        echo '<table class="form-table" role="presentation">';

        // GPS interval
        echo '<tr>';
        echo '<th scope="row"><label for="gps_interval_seconds">' . esc_html__('GPS-interval (seconden)', 'bso-phoenix') . '</label></th>';
        echo '<td>';
        echo '<input type="number" id="gps_interval_seconds" name="gps_interval_seconds" min="1" max="60" class="small-text" value="' . esc_attr((string) $settings['gps_interval_seconds']) . '" />';
        echo '<p class="description">' . esc_html__('Hoe vaak een GPS-trackpoint wordt vastgelegd tijdens een actieve route.', 'bso-phoenix') . '</p>';
        echo '</td>';
        echo '</tr>';

        // Fuel use
        echo '<tr>';
        echo '<th scope="row"><label for="fuel_use_lph">' . esc_html__('Gemiddeld brandstofverbruik (l/u)', 'bso-phoenix') . '</label></th>';
        echo '<td>';
        echo '<input type="number" id="fuel_use_lph" name="fuel_use_lph" min="0" step="0.1" class="small-text" value="' . esc_attr((string) $settings['fuel_use_lph']) . '" />';
        echo '<p class="description">' . esc_html__('Wordt gebruikt voor de brandstofschatting na elke tocht.', 'bso-phoenix') . '</p>';
        echo '</td>';
        echo '</tr>';

        // Currency
        echo '<tr>';
        echo '<th scope="row"><label for="currency">' . esc_html__('Valuta', 'bso-phoenix') . '</label></th>';
        echo '<td>';
        echo '<select id="currency" name="currency">';
        foreach (array('EUR' => 'Euro (€)', 'USD' => 'US Dollar ($)', 'GBP' => 'Pond (£)') as $code => $label) {
            echo '<option value="' . esc_attr($code) . '"' . selected((string) $settings['currency'], $code, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '</td>';
        echo '</tr>';

        // Distance unit
        echo '<tr>';
        echo '<th scope="row"><label for="distance_unit">' . esc_html__('Afstandseenheid', 'bso-phoenix') . '</label></th>';
        echo '<td>';
        echo '<select id="distance_unit" name="distance_unit">';
        foreach (array('km' => 'Kilometer (km)', 'nm' => 'Nautische mijl (nm)') as $code => $label) {
            echo '<option value="' . esc_attr($code) . '"' . selected((string) $settings['distance_unit'], $code, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html__('Eenheid voor weergave van afstanden in het overzicht.', 'bso-phoenix') . '</p>';
        echo '</td>';
        echo '</tr>';

        // Delete data on uninstall
        echo '<tr>';
        echo '<th scope="row">' . esc_html__('Gegevens verwijderen bij deïnstallatie', 'bso-phoenix') . '</th>';
        echo '<td>';
        echo '<label><input type="checkbox" name="delete_data_on_uninstall" value="1"' . checked((string) $settings['delete_data_on_uninstall'], '1', false) . ' /> ';
        echo esc_html__('Alle plugin-data verwijderen bij het verwijderen van de plugin', 'bso-phoenix') . '</label>';
        echo '<p class="description" style="color:#d63638;">' . esc_html__('Let op: dit verwijdert ook alle tochten, logboekitems, taken en kosten.', 'bso-phoenix') . '</p>';
        echo '</td>';
        echo '</tr>';

        echo '</table>';

        submit_button(__('Opslaan', 'bso-phoenix'));
        echo '</form>';
        echo '</div>';
    }

    public function handle_save_settings(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_MANAGE)) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_save_settings', 'bso_phoenix_settings_nonce');

        $gps_interval = max(1, min(60, (int) ($_POST['gps_interval_seconds'] ?? 10)));
        $fuel_use = max(0.0, (float) str_replace(',', '.', (string) ($_POST['fuel_use_lph'] ?? '5')));
        $currency = in_array($_POST['currency'] ?? '', array('EUR', 'USD', 'GBP'), true) ? (string) $_POST['currency'] : 'EUR';
        $distance_unit = in_array($_POST['distance_unit'] ?? '', array('km', 'nm'), true) ? (string) $_POST['distance_unit'] : 'km';
        $delete_data = isset($_POST['delete_data_on_uninstall']) ? '1' : '0';

        $service = new BSO_Phoenix_Settings_Service();
        $service->save_all(array(
            'gps_interval_seconds'     => (string) $gps_interval,
            'fuel_use_lph'             => (string) $fuel_use,
            'currency'                 => $currency,
            'distance_unit'            => $distance_unit,
            'delete_data_on_uninstall' => $delete_data,
        ));

        wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-settings&saved=1'));
        exit;
    }
}
