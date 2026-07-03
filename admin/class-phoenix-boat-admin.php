<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Boat_Admin
{
    private const FIELDS = array(
        'name'               => array('label' => 'Naam boot',       'type' => 'text',   'required' => true),
        'boat_type'          => array('label' => 'Type',             'type' => 'text',   'required' => true),
        'length_m'           => array('label' => 'Lengte (m)',       'type' => 'number', 'required' => true),
        'width_m'            => array('label' => 'Breedte (m)',      'type' => 'number', 'required' => true),
        'draft_m'            => array('label' => 'Diepgang (m)',     'type' => 'number', 'required' => true),
        'height_m'           => array('label' => 'Hoogte (m)',       'type' => 'number', 'required' => true),
        'fuel_type'          => array('label' => 'Brandstoftype',    'type' => 'text',   'required' => false),
        'top_speed_kmh'      => array('label' => 'Topsnelheid (km/u)', 'type' => 'number', 'required' => false),
        'weight_kg'          => array('label' => 'Gewicht (kg)',     'type' => 'number', 'required' => false),
        'bridge_clearance_m' => array('label' => 'Max. brughoogte (m)', 'type' => 'number', 'required' => false),
        'notes'              => array('label' => 'Notities',         'type' => 'textarea', 'required' => false),
    );

    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_submenu'));
        add_action('admin_post_bso_phoenix_save_boat', array($this, 'handle_save_boat'));
    }

    public function register_submenu(): void
    {
        add_submenu_page(
            'bso-phoenix',
            __('Bootprofiel', 'bso-phoenix'),
            __('Bootprofiel', 'bso-phoenix'),
            'manage_options',
            'bso-phoenix-boat',
            array($this, 'render_page')
        );
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'bso-phoenix'));
        }

        $service = new BSO_Phoenix_Boat_Service();
        $boat = $service->get_boat();

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Bootprofiel', 'bso-phoenix') . '</h1>';

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Bootprofiel opgeslagen.', 'bso-phoenix') . '</p></div>';
        }

        if (! is_array($boat)) {
            echo '<div class="notice notice-error"><p>' . esc_html__('Geen bootrecord gevonden. Deactiveer en activeer de plugin opnieuw.', 'bso-phoenix') . '</p></div>';
            echo '</div>';
            return;
        }

        // Profile summary cards
        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin:16px 0 24px;">';
        $cards = array(
            __('Naam', 'bso-phoenix') => $boat['name'],
            __('Type', 'bso-phoenix') => $boat['boat_type'],
            __('Lengte', 'bso-phoenix') => $boat['length_m'] . ' m',
            __('Breedte', 'bso-phoenix') => $boat['width_m'] . ' m',
            __('Diepgang', 'bso-phoenix') => $boat['draft_m'] . ' m',
            __('Hoogte', 'bso-phoenix') => $boat['height_m'] . ' m',
            __('Brandstof', 'bso-phoenix') => $boat['fuel_type'],
            __('Topsnelheid', 'bso-phoenix') => $boat['top_speed_kmh'] . ' km/u',
            __('Gewicht', 'bso-phoenix') => $boat['weight_kg'] . ' kg',
            __('Max. brughoogte', 'bso-phoenix') => $boat['bridge_clearance_m'] . ' m',
        );
        foreach ($cards as $label => $value) {
            echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px;">';
            echo '<div style="font-size:12px;color:#50575e;">' . esc_html($label) . '</div>';
            echo '<div style="font-size:16px;font-weight:600;">' . esc_html((string) $value) . '</div>';
            echo '</div>';
        }
        echo '</div>';

        // Edit form
        echo '<h2>' . esc_html__('Gegevens bijwerken', 'bso-phoenix') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="bso_phoenix_save_boat" />';
        wp_nonce_field('bso_phoenix_save_boat', 'bso_phoenix_boat_nonce');

        echo '<table class="form-table" role="presentation">';
        foreach (self::FIELDS as $field => $meta) {
            $value = isset($boat[$field]) ? (string) $boat[$field] : '';
            $required = $meta['required'] ? ' required' : '';
            $field_id = 'boat_' . $field;

            echo '<tr>';
            echo '<th scope="row"><label for="' . esc_attr($field_id) . '">' . esc_html($meta['label']) . '</label></th>';
            echo '<td>';

            if ($meta['type'] === 'textarea') {
                echo '<textarea id="' . esc_attr($field_id) . '" name="' . esc_attr($field) . '" rows="4" class="large-text"' . $required . '>' . esc_textarea($value) . '</textarea>';
            } elseif ($meta['type'] === 'number') {
                echo '<input type="number" id="' . esc_attr($field_id) . '" name="' . esc_attr($field) . '" step="0.01" min="0" class="small-text" value="' . esc_attr($value) . '"' . $required . ' />';
            } else {
                echo '<input type="text" id="' . esc_attr($field_id) . '" name="' . esc_attr($field) . '" class="regular-text" value="' . esc_attr($value) . '"' . $required . ' />';
            }

            echo '</td>';
            echo '</tr>';
        }
        echo '</table>';

        submit_button(__('Opslaan', 'bso-phoenix'));
        echo '</form>';
        echo '</div>';
    }

    public function handle_save_boat(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_save_boat', 'bso_phoenix_boat_nonce');

        $data = array(
            'name'               => sanitize_text_field((string) ($_POST['name'] ?? '')),
            'boat_type'          => sanitize_text_field((string) ($_POST['boat_type'] ?? '')),
            'length_m'           => (float) str_replace(',', '.', (string) ($_POST['length_m'] ?? '0')),
            'width_m'            => (float) str_replace(',', '.', (string) ($_POST['width_m'] ?? '0')),
            'draft_m'            => (float) str_replace(',', '.', (string) ($_POST['draft_m'] ?? '0')),
            'height_m'           => (float) str_replace(',', '.', (string) ($_POST['height_m'] ?? '0')),
            'fuel_type'          => sanitize_text_field((string) ($_POST['fuel_type'] ?? '')),
            'top_speed_kmh'      => (float) str_replace(',', '.', (string) ($_POST['top_speed_kmh'] ?? '0')),
            'weight_kg'          => (float) str_replace(',', '.', (string) ($_POST['weight_kg'] ?? '0')),
            'bridge_clearance_m' => (float) str_replace(',', '.', (string) ($_POST['bridge_clearance_m'] ?? '0')),
            'notes'              => sanitize_textarea_field((string) ($_POST['notes'] ?? '')),
        );

        if (trim($data['name']) === '' || trim($data['boat_type']) === '') {
            wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-boat&error=invalid'));
            exit;
        }

        $service = new BSO_Phoenix_Boat_Service();
        $service->update_boat($data);

        wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-boat&saved=1'));
        exit;
    }
}
