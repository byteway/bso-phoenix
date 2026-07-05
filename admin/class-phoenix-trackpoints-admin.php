<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Trackpoints_Admin
{
    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_submenu'));
        add_action('admin_post_bso_phoenix_manage_trackpoints', array($this, 'handle_manage_trackpoints'));
    }

    public function register_submenu(): void
    {
        add_submenu_page(
            'bso-phoenix',
            __('Trackpoints', 'bso-phoenix'),
            __('Trackpoints', 'bso-phoenix'),
            BSO_PHOENIX_CAP_MANAGE,
            'bso-phoenix-trackpoints',
            array($this, 'render_page')
        );
    }

    public function render_page(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_MANAGE)) {
            wp_die(esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'bso-phoenix'));
        }

        $trip_service = new BSO_Phoenix_Trip_Service();
        $trip_date_from = $this->sanitize_date_input(isset($_GET['trip_date_from']) ? sanitize_text_field(wp_unslash((string) $_GET['trip_date_from'])) : '');
        $trip_date_to = $this->sanitize_date_input(isset($_GET['trip_date_to']) ? sanitize_text_field(wp_unslash((string) $_GET['trip_date_to'])) : '');
        $trip_limit = $this->sanitize_limit(isset($_GET['trip_limit']) ? $_GET['trip_limit'] : 100, 100, 20, 500);

        $point_from_input = isset($_GET['point_from']) ? sanitize_text_field(wp_unslash((string) $_GET['point_from'])) : '';
        $point_to_input = isset($_GET['point_to']) ? sanitize_text_field(wp_unslash((string) $_GET['point_to'])) : '';
        $point_date_from = $this->parse_datetime_local($point_from_input);
        $point_date_to = $this->parse_datetime_local($point_to_input);
        $point_limit = $this->sanitize_allowed_limit(isset($_GET['point_limit']) ? $_GET['point_limit'] : 50, 50, array(25, 50, 100));
        $point_page = max(1, isset($_GET['point_page']) ? (int) $_GET['point_page'] : 1);

        $trips = $trip_service->get_trips_by_date_range(
            $trip_date_from !== '' ? $trip_date_from : null,
            $trip_date_to !== '' ? $trip_date_to : null,
            '',
            $trip_limit
        );
        $trip_id = isset($_GET['trip_id']) ? (int) $_GET['trip_id'] : 0;
        if ($trip_id <= 0 && ! empty($trips)) {
            $trip_id = (int) $trips[0]['id'];
        }

        $trip = $trip_id > 0 ? $trip_service->get_trip_by_id($trip_id) : null;
        $total_filtered_points = 0;
        $total_pages = 1;
        $points = array();

        if ($trip_id > 0) {
            $total_filtered_points = $trip_service->count_trackpoints_for_trip_filtered(
                $trip_id,
                $point_date_from !== '' ? $point_date_from : null,
                $point_date_to !== '' ? $point_date_to : null
            );
            $total_pages = max(1, (int) ceil($total_filtered_points / $point_limit));
            $point_page = min($point_page, $total_pages);

            $points = $trip_service->get_trackpoints_for_trip_filtered(
                $trip_id,
                $point_date_from !== '' ? $point_date_from : null,
                $point_date_to !== '' ? $point_date_to : null,
                $point_limit,
                ($point_page - 1) * $point_limit
            );
        }

        $filter_state = array(
            'trip_date_from' => $trip_date_from,
            'trip_date_to' => $trip_date_to,
            'trip_limit' => $trip_limit,
            'point_from' => $point_date_from !== '' ? $this->format_datetime_local_input($point_date_from) : '',
            'point_to' => $point_date_to !== '' ? $this->format_datetime_local_input($point_date_to) : '',
            'point_limit' => $point_limit,
            'point_page' => $point_page,
        );

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Trackpoints beheren', 'bso-phoenix') . '</h1>';
        echo '<p>' . esc_html__('Bewerk, verwijder en herstel trackpoints per tocht. Na opslaan of verwijderen wordt de trip zo nodig herberekend.', 'bso-phoenix') . '</p>';

        $this->render_notices();

        echo '<form method="get" action="" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin:0 0 16px;">';
        echo '<input type="hidden" name="page" value="bso-phoenix-trackpoints" />';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Tochten vanaf', 'bso-phoenix') . '</span>';
        echo '<input type="date" name="trip_date_from" value="' . esc_attr($trip_date_from) . '" />';
        echo '</label>';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Tochten t/m', 'bso-phoenix') . '</span>';
        echo '<input type="date" name="trip_date_to" value="' . esc_attr($trip_date_to) . '" />';
        echo '</label>';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Max tochten', 'bso-phoenix') . '</span>';
        echo '<select name="trip_limit">';
        foreach (array(50, 100, 200, 500) as $trip_limit_option) {
            echo '<option value="' . esc_attr((string) $trip_limit_option) . '"' . selected($trip_limit, $trip_limit_option, false) . '>' . esc_html((string) $trip_limit_option) . '</option>';
        }
        echo '</select>';
        echo '</label>';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Kies tocht', 'bso-phoenix') . '</span>';
        echo '<select name="trip_id">';
        if (empty($trips)) {
            echo '<option value="0">' . esc_html__('Geen trips gevonden', 'bso-phoenix') . '</option>';
        } else {
            foreach ($trips as $candidate_trip) {
                $candidate_trip_id = (int) $candidate_trip['id'];
                $label = sprintf(
                    '#%d - %s - %s',
                    $candidate_trip_id,
                    $this->format_datetime((string) $candidate_trip['started_at']),
                    (string) $candidate_trip['status']
                );
                echo '<option value="' . esc_attr((string) $candidate_trip_id) . '"' . selected($trip_id, $candidate_trip_id, false) . '>' . esc_html($label) . '</option>';
            }
        }
        echo '</select>';
        echo '</label>';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Trackpoints vanaf', 'bso-phoenix') . '</span>';
        echo '<input type="datetime-local" name="point_from" value="' . esc_attr((string) $filter_state['point_from']) . '" />';
        echo '</label>';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Trackpoints t/m', 'bso-phoenix') . '</span>';
        echo '<input type="datetime-local" name="point_to" value="' . esc_attr((string) $filter_state['point_to']) . '" />';
        echo '</label>';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Max trackpoints', 'bso-phoenix') . '</span>';
        echo '<select name="point_limit">';
        foreach (array(25, 50, 100) as $point_limit_option) {
            echo '<option value="' . esc_attr((string) $point_limit_option) . '"' . selected($point_limit, $point_limit_option, false) . '>' . esc_html((string) $point_limit_option) . '</option>';
        }
        echo '</select>';
        echo '</label>';
        echo '<input type="hidden" name="point_page" value="1" />';
        submit_button(__('Filter toepassen', 'bso-phoenix'), 'secondary', 'submit', false);
        echo '</form>';

        if (! is_array($trip)) {
            echo '<div class="notice notice-warning"><p>' . esc_html__('Selecteer een geldige tocht om de trackpoints te beheren.', 'bso-phoenix') . '</p></div>';
            echo '</div>';
            return;
        }

        $invalid_count = 0;
        foreach ($points as $point) {
            if (! BSO_Phoenix_Hardening::is_valid_coordinate((float) $point['latitude'], (float) $point['longitude'])) {
                $invalid_count++;
            }
        }

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:0 0 18px;">';
        $this->render_stat_card(__('Trip', 'bso-phoenix'), '#' . (string) $trip['id']);
        $this->render_stat_card(__('Status', 'bso-phoenix'), (string) $trip['status']);
        $this->render_stat_card(__('Trackpoints (geladen)', 'bso-phoenix'), (string) count($points));
        $this->render_stat_card(__('Trackpoints (filter totaal)', 'bso-phoenix'), (string) $total_filtered_points);
        $this->render_stat_card(__('Ongeldige punten', 'bso-phoenix'), (string) $invalid_count);
        echo '</div>';

        if ($total_filtered_points > 0) {
            echo '<div class="notice notice-info"><p>' . esc_html(sprintf(__('Pagina %1$d van %2$d, met %3$d totaal gefilterde trackpoints.', 'bso-phoenix'), $point_page, $total_pages, $total_filtered_points)) . '</p></div>';
        }

        $this->render_pagination($trip_id, $filter_state, $point_page, $total_pages);

        echo '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 14px;">';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:0;">';
        echo '<input type="hidden" name="action" value="bso_phoenix_manage_trackpoints" />';
        echo '<input type="hidden" name="trip_id" value="' . esc_attr((string) $trip_id) . '" />';
        echo '<input type="hidden" name="trackpoint_action" value="cleanup" />';
        $this->render_filter_hidden_inputs($filter_state);
        wp_nonce_field('bso_phoenix_manage_trackpoints_' . $trip_id, 'bso_phoenix_trackpoints_nonce');
        submit_button(__('Verwijder ongeldige punten', 'bso-phoenix'), 'secondary', 'submit', false);
        echo '</form>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:0;">';
        echo '<input type="hidden" name="action" value="bso_phoenix_manage_trackpoints" />';
        echo '<input type="hidden" name="trip_id" value="' . esc_attr((string) $trip_id) . '" />';
        echo '<input type="hidden" name="trackpoint_action" value="recalculate" />';
        $this->render_filter_hidden_inputs($filter_state);
        wp_nonce_field('bso_phoenix_manage_trackpoints_' . $trip_id, 'bso_phoenix_trackpoints_nonce');
        submit_button(__('Herbereken trip', 'bso-phoenix'), 'secondary', 'submit', false);
        echo '</form>';
        echo '</div>';

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="bso_phoenix_manage_trackpoints" />';
        echo '<input type="hidden" name="trip_id" value="' . esc_attr((string) $trip_id) . '" />';
        echo '<input type="hidden" name="trackpoint_action" value="save" />';
        $this->render_filter_hidden_inputs($filter_state);
        wp_nonce_field('bso_phoenix_manage_trackpoints_' . $trip_id, 'bso_phoenix_trackpoints_nonce');

        echo '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 10px;">';
        echo '<button type="button" class="button" id="bso-trackpoints-select-all">' . esc_html__('Alles selecteren', 'bso-phoenix') . '</button>';
        echo '<button type="button" class="button" id="bso-trackpoints-invert">' . esc_html__('Selectie omkeren', 'bso-phoenix') . '</button>';
        echo '</div>';

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Verwijderen', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('ID', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Recorded at', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Latitude', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Longitude', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Altitude (m)', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Speed (km/u)', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Accuracy (m)', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Status', 'bso-phoenix') . '</th>';
        echo '</tr></thead><tbody>';

        if (empty($points)) {
            echo '<tr><td colspan="9">' . esc_html__('Geen trackpoints gevonden voor deze tocht.', 'bso-phoenix') . '</td></tr>';
        } else {
            foreach ($points as $point) {
                $point_id = (int) $point['id'];
                $latitude = isset($point['latitude']) ? (string) $point['latitude'] : '';
                $longitude = isset($point['longitude']) ? (string) $point['longitude'] : '';
                $altitude = isset($point['altitude_m']) ? (string) $point['altitude_m'] : '';
                $speed = isset($point['speed_kmh']) ? (string) $point['speed_kmh'] : '';
                $accuracy = isset($point['accuracy_m']) ? (string) $point['accuracy_m'] : '';
                $recorded_at = isset($point['recorded_at']) ? (string) $point['recorded_at'] : '';
                $row_invalid = ! BSO_Phoenix_Hardening::is_valid_coordinate((float) $latitude, (float) $longitude);

                echo '<tr' . ($row_invalid ? ' style="background:#fff5f5;"' : '') . '>';
                echo '<td><label><input type="checkbox" class="bso-trackpoint-select" name="delete_ids[]" value="' . esc_attr((string) $point_id) . '" /> ' . esc_html__('Selecteer', 'bso-phoenix') . '</label></td>';
                echo '<td>#' . esc_html((string) $point_id) . '</td>';
                echo '<td><input type="datetime-local" name="trackpoints[' . esc_attr((string) $point_id) . '][recorded_at]" value="' . esc_attr($this->format_datetime_local_input($recorded_at)) . '" /></td>';
                echo '<td><input type="number" step="0.0000001" name="trackpoints[' . esc_attr((string) $point_id) . '][latitude]" value="' . esc_attr($latitude) . '" /></td>';
                echo '<td><input type="number" step="0.0000001" name="trackpoints[' . esc_attr((string) $point_id) . '][longitude]" value="' . esc_attr($longitude) . '" /></td>';
                echo '<td><input type="number" step="0.01" name="trackpoints[' . esc_attr((string) $point_id) . '][altitude_m]" value="' . esc_attr($altitude) . '" /></td>';
                echo '<td><input type="number" step="0.001" name="trackpoints[' . esc_attr((string) $point_id) . '][speed_kmh]" value="' . esc_attr($speed) . '" /></td>';
                echo '<td><input type="number" step="0.001" name="trackpoints[' . esc_attr((string) $point_id) . '][accuracy_m]" value="' . esc_attr($accuracy) . '" /></td>';
                echo '<td>' . ($row_invalid ? esc_html__('Ongeldig', 'bso-phoenix') : esc_html__('OK', 'bso-phoenix')) . '</td>';
                echo '</tr>';
            }
        }

        echo '</tbody></table>';

        echo '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin-top:14px;">';
        submit_button(__('Wijzigingen opslaan', 'bso-phoenix'), 'primary', 'submit', false);
        echo '<button type="submit" class="button button-secondary" name="trackpoint_action" value="delete" onclick="return confirm(\'' . esc_js(__('Geselecteerde trackpoints verwijderen?', 'bso-phoenix')) . '\')">' . esc_html__('Verwijder geselecteerde punten', 'bso-phoenix') . '</button>';
        echo '</div>';
        echo '</form>';

        if (! empty($points)) {
            echo '<script>';
            echo 'document.addEventListener("DOMContentLoaded", function () {';
            echo 'var selectAllButton = document.getElementById("bso-trackpoints-select-all");';
            echo 'var invertButton = document.getElementById("bso-trackpoints-invert");';
            echo 'var checkboxes = document.querySelectorAll(".bso-trackpoint-select");';
            echo 'if (selectAllButton) { selectAllButton.addEventListener("click", function () { checkboxes.forEach(function (checkbox) { checkbox.checked = true; }); }); }';
            echo 'if (invertButton) { invertButton.addEventListener("click", function () { checkboxes.forEach(function (checkbox) { checkbox.checked = !checkbox.checked; }); }); }';
            echo '});';
            echo '</script>';
        }

        $this->render_pagination($trip_id, $filter_state, $point_page, $total_pages);
        echo '</div>';
    }

    public function handle_manage_trackpoints(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_MANAGE)) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        $trip_id = isset($_POST['trip_id']) ? (int) $_POST['trip_id'] : 0;
        if ($trip_id <= 0) {
            wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-trackpoints&error=invalid_trip'));
            exit;
        }

        check_admin_referer('bso_phoenix_manage_trackpoints_' . $trip_id, 'bso_phoenix_trackpoints_nonce');

        $action = isset($_POST['trackpoint_action']) ? sanitize_key((string) $_POST['trackpoint_action']) : 'save';
        $service = new BSO_Phoenix_Trip_Service();
        $trip = $service->get_trip_by_id($trip_id);
        if (! is_array($trip)) {
            wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-trackpoints&error=trip_not_found'));
            exit;
        }

        $result = array(
            'saved' => 0,
            'deleted' => 0,
            'cleaned' => 0,
            'failed' => 0,
            'recalculated' => 0,
        );

        if ($action === 'save') {
            $rows = isset($_POST['trackpoints']) && is_array($_POST['trackpoints']) ? wp_unslash($_POST['trackpoints']) : array();
            foreach ($rows as $trackpoint_id_raw => $row) {
                $trackpoint_id = (int) $trackpoint_id_raw;
                if ($trackpoint_id <= 0 || ! is_array($row)) {
                    $result['failed']++;
                    continue;
                }

                $existing = $service->get_trackpoint_by_id($trackpoint_id);
                if (! is_array($existing) || (int) $existing['trip_id'] !== $trip_id) {
                    $result['failed']++;
                    continue;
                }

                $latitude = $this->parse_float_value($row['latitude'] ?? null);
                $longitude = $this->parse_float_value($row['longitude'] ?? null);
                $recorded_at = $this->parse_datetime_local((string) ($row['recorded_at'] ?? ''));
                if ($latitude === null || $longitude === null || $recorded_at === '') {
                    $result['failed']++;
                    continue;
                }

                if (! BSO_Phoenix_Hardening::is_valid_coordinate($latitude, $longitude)) {
                    $result['failed']++;
                    continue;
                }

                $updated = $service->update_trackpoint($trackpoint_id, array(
                    'latitude' => $latitude,
                    'longitude' => $longitude,
                    'altitude_m' => $this->parse_float_value($row['altitude_m'] ?? null),
                    'speed_kmh' => $this->parse_float_value($row['speed_kmh'] ?? null),
                    'accuracy_m' => $this->parse_float_value($row['accuracy_m'] ?? null),
                    'recorded_at' => $recorded_at,
                ));

                if ($updated) {
                    $result['saved']++;
                } else {
                    $result['failed']++;
                }
            }

            if ($service->recalculate_trip_metrics($trip_id)) {
                $result['recalculated'] = 1;
            }
        } elseif ($action === 'delete') {
            $delete_ids = isset($_POST['delete_ids']) && is_array($_POST['delete_ids']) ? array_map('intval', wp_unslash($_POST['delete_ids'])) : array();
            foreach ($delete_ids as $delete_id) {
                if ($delete_id <= 0) {
                    continue;
                }
                $point = $service->get_trackpoint_by_id($delete_id);
                if (! is_array($point) || (int) $point['trip_id'] !== $trip_id) {
                    $result['failed']++;
                    continue;
                }
                if ($service->delete_trackpoint($delete_id)) {
                    $result['deleted']++;
                } else {
                    $result['failed']++;
                }
            }

            if ($service->recalculate_trip_metrics($trip_id)) {
                $result['recalculated'] = 1;
            }
        } elseif ($action === 'cleanup') {
            $result['cleaned'] = $service->delete_invalid_trackpoints_for_trip($trip_id);
            if ($service->recalculate_trip_metrics($trip_id)) {
                $result['recalculated'] = 1;
            }
        } elseif ($action === 'recalculate') {
            if ($service->recalculate_trip_metrics($trip_id)) {
                $result['recalculated'] = 1;
            }
        } else {
            wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-trackpoints&trip_id=' . $trip_id . '&error=invalid_action'));
            exit;
        }

        $redirect_args = array(
            'page' => 'bso-phoenix-trackpoints',
            'trip_id' => $trip_id,
            'trip_limit' => $this->sanitize_limit(isset($_POST['trip_limit']) ? $_POST['trip_limit'] : 100, 100, 20, 500),
            'point_limit' => $this->sanitize_allowed_limit(isset($_POST['point_limit']) ? $_POST['point_limit'] : 50, 50, array(25, 50, 100)),
            'point_page' => max(1, isset($_POST['point_page']) ? (int) $_POST['point_page'] : 1),
        );

        $trip_date_from = $this->sanitize_date_input(isset($_POST['trip_date_from']) ? sanitize_text_field(wp_unslash((string) $_POST['trip_date_from'])) : '');
        $trip_date_to = $this->sanitize_date_input(isset($_POST['trip_date_to']) ? sanitize_text_field(wp_unslash((string) $_POST['trip_date_to'])) : '');
        $point_from = $this->parse_datetime_local(isset($_POST['point_from']) ? sanitize_text_field(wp_unslash((string) $_POST['point_from'])) : '');
        $point_to = $this->parse_datetime_local(isset($_POST['point_to']) ? sanitize_text_field(wp_unslash((string) $_POST['point_to'])) : '');

        if ($trip_date_from !== '') {
            $redirect_args['trip_date_from'] = $trip_date_from;
        }
        if ($trip_date_to !== '') {
            $redirect_args['trip_date_to'] = $trip_date_to;
        }
        if ($point_from !== '') {
            $redirect_args['point_from'] = $this->format_datetime_local_input($point_from);
        }
        if ($point_to !== '') {
            $redirect_args['point_to'] = $this->format_datetime_local_input($point_to);
        }

        if ($result['saved'] > 0) {
            $redirect_args['saved'] = $result['saved'];
        }
        if ($result['deleted'] > 0) {
            $redirect_args['deleted'] = $result['deleted'];
        }
        if ($result['cleaned'] > 0) {
            $redirect_args['cleaned'] = $result['cleaned'];
        }
        if ($result['failed'] > 0) {
            $redirect_args['failed'] = $result['failed'];
        }
        if ($result['recalculated'] > 0) {
            $redirect_args['recalculated'] = 1;
        }

        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }

    private function render_notices(): void
    {
        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf(__('Trackpoints opgeslagen: %d.', 'bso-phoenix'), max(0, (int) $_GET['saved']))) . '</p></div>';
        }
        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf(__('Trackpoints verwijderd: %d.', 'bso-phoenix'), max(0, (int) $_GET['deleted']))) . '</p></div>';
        }
        if (isset($_GET['cleaned'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html(sprintf(__('Ongeldige punten verwijderd: %d.', 'bso-phoenix'), max(0, (int) $_GET['cleaned']))) . '</p></div>';
        }
        if (isset($_GET['recalculated'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Trip opnieuw herberekend op basis van de huidige trackpoints.', 'bso-phoenix') . '</p></div>';
        }
        if (isset($_GET['failed']) && (int) $_GET['failed'] > 0) {
            echo '<div class="notice notice-warning is-dismissible"><p>' . esc_html(sprintf(__('Niet alle wijzigingen zijn doorgevoerd: %d fout(en).', 'bso-phoenix'), max(0, (int) $_GET['failed']))) . '</p></div>';
        }
        if (isset($_GET['error'])) {
            $error = sanitize_key((string) $_GET['error']);
            $messages = array(
                'invalid_trip' => __('Ongeldige trip geselecteerd.', 'bso-phoenix'),
                'trip_not_found' => __('Trip niet gevonden.', 'bso-phoenix'),
                'invalid_action' => __('Ongeldige actie.', 'bso-phoenix'),
            );
            if (isset($messages[$error])) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($messages[$error]) . '</p></div>';
            }
        }
    }

    private function render_stat_card(string $label, string $value): void
    {
        echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px;">';
        echo '<div style="font-size:12px;color:#50575e;">' . esc_html($label) . '</div>';
        echo '<div style="font-size:16px;font-weight:600;">' . esc_html($value) . '</div>';
        echo '</div>';
    }

    private function render_filter_hidden_inputs(array $filter_state): void
    {
        echo '<input type="hidden" name="trip_date_from" value="' . esc_attr((string) $filter_state['trip_date_from']) . '" />';
        echo '<input type="hidden" name="trip_date_to" value="' . esc_attr((string) $filter_state['trip_date_to']) . '" />';
        echo '<input type="hidden" name="trip_limit" value="' . esc_attr((string) $filter_state['trip_limit']) . '" />';
        echo '<input type="hidden" name="point_from" value="' . esc_attr((string) $filter_state['point_from']) . '" />';
        echo '<input type="hidden" name="point_to" value="' . esc_attr((string) $filter_state['point_to']) . '" />';
        echo '<input type="hidden" name="point_limit" value="' . esc_attr((string) $filter_state['point_limit']) . '" />';
        echo '<input type="hidden" name="point_page" value="' . esc_attr((string) $filter_state['point_page']) . '" />';
    }

    private function render_pagination(int $trip_id, array $filter_state, int $current_page, int $total_pages): void
    {
        if ($trip_id <= 0 || $total_pages <= 1) {
            return;
        }

        echo '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:12px 0;">';

        if ($current_page > 1) {
            $prev_url = $this->build_page_url($trip_id, $filter_state, $current_page - 1);
            echo '<a class="button" href="' . esc_url($prev_url) . '">' . esc_html__('Vorige', 'bso-phoenix') . '</a>';
        }

        echo '<span style="font-size:13px;color:#50575e;">' . esc_html(sprintf(__('Pagina %1$d van %2$d', 'bso-phoenix'), $current_page, $total_pages)) . '</span>';

        if ($current_page < $total_pages) {
            $next_url = $this->build_page_url($trip_id, $filter_state, $current_page + 1);
            echo '<a class="button" href="' . esc_url($next_url) . '">' . esc_html__('Volgende', 'bso-phoenix') . '</a>';
        }

        echo '</div>';
    }

    private function build_page_url(int $trip_id, array $filter_state, int $page): string
    {
        $args = array(
            'page' => 'bso-phoenix-trackpoints',
            'trip_id' => $trip_id,
            'trip_limit' => (int) $filter_state['trip_limit'],
            'point_limit' => (int) $filter_state['point_limit'],
            'point_page' => max(1, $page),
        );

        if ((string) $filter_state['trip_date_from'] !== '') {
            $args['trip_date_from'] = (string) $filter_state['trip_date_from'];
        }
        if ((string) $filter_state['trip_date_to'] !== '') {
            $args['trip_date_to'] = (string) $filter_state['trip_date_to'];
        }
        if ((string) $filter_state['point_from'] !== '') {
            $args['point_from'] = (string) $filter_state['point_from'];
        }
        if ((string) $filter_state['point_to'] !== '') {
            $args['point_to'] = (string) $filter_state['point_to'];
        }

        return add_query_arg($args, admin_url('admin.php'));
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

    private function format_datetime_local_input(string $value): string
    {
        if ($value === '' || $value === '0000-00-00 00:00:00') {
            return '';
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return '';
        }

        return wp_date('Y-m-d\TH:i', $timestamp);
    }

    private function parse_datetime_local(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $value = str_replace(' ', 'T', $value);
        $datetime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i', $value, wp_timezone());
        if (! $datetime instanceof DateTimeImmutable) {
            $datetime = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s', $value, wp_timezone());
        }

        return $datetime instanceof DateTimeImmutable ? $datetime->format('Y-m-d H:i:s') : '';
    }

    private function sanitize_date_input(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        $datetime = DateTimeImmutable::createFromFormat('Y-m-d', $value, wp_timezone());

        return $datetime instanceof DateTimeImmutable ? $datetime->format('Y-m-d') : '';
    }

    private function sanitize_limit($value, int $default, int $min, int $max): int
    {
        $limit = (int) $value;
        if ($limit <= 0) {
            return $default;
        }

        return max($min, min($max, $limit));
    }

    private function sanitize_allowed_limit($value, int $default, array $allowed): int
    {
        $limit = (int) $value;

        return in_array($limit, $allowed, true) ? $limit : $default;
    }

    private function parse_float_value($value): ?float
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);
        if ($value === '') {
            return null;
        }

        $value = str_replace(',', '.', $value);
        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
