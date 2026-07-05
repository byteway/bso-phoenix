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
        add_action('admin_post_bso_phoenix_bulk_delete_trips', array($this, 'handle_bulk_delete_trips'));
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
        $can_delete_trips = current_user_can(BSO_PHOENIX_CAP_WRITE);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Phoenix Logboek', 'bso-phoenix') . '</h1>';

        echo '<p>' . esc_html__('Overzicht van route-activiteit op basis van de huidige GPS-loggegevens.', 'bso-phoenix') . '</p>';

        if (isset($_GET['bulk_deleted'])) {
            $deleted_count = max(0, (int) $_GET['bulk_deleted']);
            $failed_count = isset($_GET['bulk_failed']) ? max(0, (int) $_GET['bulk_failed']) : 0;
            if ($deleted_count > 0) {
                echo '<div class="notice notice-success is-dismissible"><p>'
                    . esc_html(sprintf(__('Bulkverwijdering voltooid: %d tocht(en) verwijderd.', 'bso-phoenix'), $deleted_count));
                if ($failed_count > 0) {
                    echo ' ' . esc_html(sprintf(__('Niet verwijderd: %d.', 'bso-phoenix'), $failed_count));
                }
                echo '</p></div>';
            } elseif ($failed_count > 0) {
                echo '<div class="notice notice-warning is-dismissible"><p>'
                    . esc_html(sprintf(__('Geen tochten verwijderd. Mislukt: %d.', 'bso-phoenix'), $failed_count))
                    . '</p></div>';
            }
        }
        if (isset($_GET['export_error'])) {
            $messages = array(
                'invalid_range' => __('Ongeldige periode: de einddatum ligt voor de startdatum.', 'bso-phoenix'),
                'invalid_trip' => __('Export mislukt: ongeldige trip-id.', 'bso-phoenix'),
                'invalid_format' => __('Export mislukt: ongeldig exportformaat.', 'bso-phoenix'),
                'trip_not_found' => __('Export mislukt: trip niet gevonden.', 'bso-phoenix'),
                'empty_trackpoints' => __('Export mislukt: geen trackpoints beschikbaar voor deze trip.', 'bso-phoenix'),
            );
            $error_code = sanitize_key((string) $_GET['export_error']);
            if (isset($messages[$error_code])) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($messages[$error_code]) . '</p></div>';
            }
        }

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

        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" data-phoenix-admin-bulk-form>';
        echo '<input type="hidden" name="action" value="bso_phoenix_bulk_delete_trips" />';
        echo '<input type="hidden" name="date_from" value="' . esc_attr($date_from) . '" />';
        echo '<input type="hidden" name="date_to" value="' . esc_attr($date_to) . '" />';
        echo '<input type="hidden" name="status" value="' . esc_attr($status) . '" />';
        wp_nonce_field('bso_phoenix_bulk_delete_trips', 'bso_phoenix_bulk_delete_nonce');

        echo '<div style="display:flex;gap:8px;align-items:center;flex-wrap:wrap;margin:0 0 10px;">';
        echo '<button type="button" class="button" data-phoenix-admin-select-all>' . esc_html__('Selecteer alles', 'bso-phoenix') . '</button>';
        echo '<button type="button" class="button" data-phoenix-admin-select-none>' . esc_html__('Deselecteer alles', 'bso-phoenix') . '</button>';
        echo '<button type="button" class="button" data-phoenix-admin-select-invert>' . esc_html__('Selectie omkeren', 'bso-phoenix') . '</button>';
        if ($can_delete_trips) {
            echo '<button type="submit" class="button button-secondary" data-phoenix-admin-delete-selected>' . esc_html__('Verwijder geselecteerde tochten', 'bso-phoenix') . '</button>';
        } else {
            echo '<span style="font-size:12px;color:#50575e;">' . esc_html__('Alleen-lezen rechten: bulk verwijderen is uitgeschakeld.', 'bso-phoenix') . '</span>';
        }
        echo '<span style="font-size:12px;color:#50575e;" data-phoenix-admin-selection-count>0 geselecteerd</span>';
        echo '</div>';

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th><input type="checkbox" data-phoenix-admin-select-toggle aria-label="' . esc_attr__('Selecteer alle zichtbare tochten', 'bso-phoenix') . '" /></th>';
        echo '<th>' . esc_html__('Trip', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Start', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Einde', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Status', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html(sprintf(__('Afstand (%s)', 'bso-phoenix'), $distance_unit)) . '</th>';
        echo '<th>' . esc_html__('Duur (min)', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html(sprintf(__('Gem. snelheid (%s)', 'bso-phoenix'), $speed_unit)) . '</th>';
        echo '<th>' . esc_html__('Export', 'bso-phoenix') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($recent_trips as $trip) {
            $trip_id = (int) $trip['id'];
            echo '<tr>';
            echo '<td><input type="checkbox" name="trip_ids[]" value="' . esc_attr((string) $trip_id) . '" data-phoenix-admin-trip-checkbox /></td>';
            echo '<td>#' . esc_html((string) $trip_id) . '</td>';
            echo '<td>' . esc_html($this->format_datetime((string) $trip['started_at'])) . '</td>';
            echo '<td>' . esc_html($this->format_datetime((string) $trip['ended_at'])) . '</td>';
            echo '<td>' . esc_html((string) $trip['status']) . '</td>';
            echo '<td>' . esc_html($settings_service->format_distance((float) $trip['distance_km'], 2)) . '</td>';
            echo '<td>' . esc_html(number_format_i18n((float) $trip['duration_minutes'], 1)) . '</td>';
            echo '<td>' . esc_html($settings_service->format_speed((float) $trip['average_speed_kmh'], 2)) . '</td>';
            echo '<td>' . $this->render_trip_export_links($trip_id) . '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</form>';

        echo '<script>';
        echo '(function(){';
        echo 'var form=document.querySelector("[data-phoenix-admin-bulk-form]");if(!form){return;}';
        echo 'var checkboxes=function(){return Array.prototype.slice.call(form.querySelectorAll("[data-phoenix-admin-trip-checkbox]"));};';
        echo 'var countNode=form.querySelector("[data-phoenix-admin-selection-count]");';
        echo 'var toggle=form.querySelector("[data-phoenix-admin-select-toggle]");';
        echo 'var update=function(){var selected=checkboxes().filter(function(cb){return cb.checked;}).length;if(countNode){countNode.textContent=selected+" geselecteerd";}if(toggle){var all=checkboxes();toggle.checked=all.length>0&&selected===all.length;toggle.indeterminate=selected>0&&selected<all.length;}};';
        echo 'form.addEventListener("change",function(event){var target=event.target;if(!target){return;}if(target.matches("[data-phoenix-admin-trip-checkbox]")||target.matches("[data-phoenix-admin-select-toggle]")){if(target.matches("[data-phoenix-admin-select-toggle]")){checkboxes().forEach(function(cb){cb.checked=target.checked;});}update();}});';
        echo 'var bind=function(selector,handler){var node=form.querySelector(selector);if(node){node.addEventListener("click",handler);}};';
        echo 'bind("[data-phoenix-admin-select-all]",function(){checkboxes().forEach(function(cb){cb.checked=true;});update();});';
        echo 'bind("[data-phoenix-admin-select-none]",function(){checkboxes().forEach(function(cb){cb.checked=false;});update();});';
        echo 'bind("[data-phoenix-admin-select-invert]",function(){checkboxes().forEach(function(cb){cb.checked=!cb.checked;});update();});';
        echo 'form.addEventListener("submit",function(event){var selected=checkboxes().filter(function(cb){return cb.checked;});if(!selected.length){event.preventDefault();window.alert("Selecteer minimaal 1 tocht om te verwijderen.");return;}if(!window.confirm("Weet je zeker dat je de geselecteerde tochten wilt verwijderen?")){event.preventDefault();}});';
        echo 'update();';
        echo '})();';
        echo '</script>';
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

        if (! BSO_Phoenix_Hardening::is_valid_date_range($date_from, $date_to)) {
            $this->redirect_export_error('invalid_range', $date_from, $date_to, $status);
        }

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
        $filename = sanitize_file_name('phoenix-trips' . $range_suffix . '-' . gmdate('Ymd-His') . '.csv');

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('Kon CSV-output niet openen.', 'bso-phoenix'));
        }

        if (fputcsv($output, array('trip_id', 'started_at', 'ended_at', 'status', 'distance_' . $distance_unit, 'duration_minutes', 'average_speed_' . $speed_unit)) === false) {
            fclose($output);
            wp_die(esc_html__('Kon CSV-header niet schrijven.', 'bso-phoenix'));
        }

        foreach ($trips as $trip) {
            $written = fputcsv(
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

            if ($written === false) {
                fclose($output);
                wp_die(esc_html__('Kon CSV-rij niet schrijven.', 'bso-phoenix'));
            }
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
            $this->redirect_export_error('invalid_trip');
        }

        check_admin_referer('bso_phoenix_export_trip_' . $trip_id);

        if (! in_array($format, array('csv', 'gpx'), true)) {
            $this->redirect_export_error('invalid_format');
        }

        $service = new BSO_Phoenix_Trip_Service();
        $trip = $service->get_trip_by_id($trip_id);
        if (! is_array($trip)) {
            $this->redirect_export_error('trip_not_found');
        }

        $points = $service->get_trackpoints_for_trip($trip_id);
        if (empty($points)) {
            $this->redirect_export_error('empty_trackpoints');
        }

        $valid_points = $this->filter_valid_trackpoints($points);
        if (empty($valid_points)) {
            $this->redirect_export_error('empty_trackpoints');
        }

        if ($format === 'gpx') {
            $this->download_trackpoints_gpx($trip_id, $trip, $valid_points);
            return;
        }

        $this->download_trackpoints_csv($trip_id, $valid_points);
    }

    public function handle_bulk_delete_trips(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_WRITE)) {
            wp_die(esc_html__('Je hebt geen rechten om deze actie uit te voeren.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_bulk_delete_trips', 'bso_phoenix_bulk_delete_nonce');

        $date_from = $this->normalize_date_input(isset($_POST['date_from']) ? sanitize_text_field((string) $_POST['date_from']) : '');
        $date_to = $this->normalize_date_input(isset($_POST['date_to']) ? sanitize_text_field((string) $_POST['date_to']) : '');
        $status = $this->normalize_status_input(isset($_POST['status']) ? sanitize_text_field((string) $_POST['status']) : '');

        $trip_ids = isset($_POST['trip_ids']) && is_array($_POST['trip_ids'])
            ? array_values(array_unique(array_filter(array_map('intval', wp_unslash($_POST['trip_ids'])), function ($id) {
                return $id > 0;
            })))
            : array();

        $deleted_count = 0;
        $failed_count = 0;

        if (! empty($trip_ids)) {
            $service = new BSO_Phoenix_Trip_Service();
            foreach ($trip_ids as $trip_id) {
                if ($service->delete_trip_with_related_data((int) $trip_id)) {
                    $deleted_count++;
                } else {
                    $failed_count++;
                }
            }
        }

        $redirect_url = add_query_arg(
            array(
                'page' => 'bso-phoenix',
                'date_from' => $date_from,
                'date_to' => $date_to,
                'status' => $status,
                'bulk_deleted' => $deleted_count,
                'bulk_failed' => $failed_count,
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
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
        $filename = sanitize_file_name('phoenix-trip-' . $trip_id . '-trackpoints-' . gmdate('Ymd-His') . '.csv');

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('Kon CSV-output niet openen.', 'bso-phoenix'));
        }

        if (fputcsv($output, array('trip_id', 'latitude', 'longitude', 'altitude_m', 'speed_kmh', 'accuracy_m', 'recorded_at')) === false) {
            fclose($output);
            wp_die(esc_html__('Kon CSV-header niet schrijven.', 'bso-phoenix'));
        }

        foreach ($points as $point) {
            $written = fputcsv(
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

            if ($written === false) {
                fclose($output);
                wp_die(esc_html__('Kon CSV-rij niet schrijven.', 'bso-phoenix'));
            }
        }

        fclose($output);
        exit;
    }

    private function download_trackpoints_gpx(int $trip_id, array $trip, array $points): void
    {
        $filename = sanitize_file_name('phoenix-trip-' . $trip_id . '-' . gmdate('Ymd-His') . '.gpx');

        nocache_headers();
        header('Content-Type: application/gpx+xml; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

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

    private function filter_valid_trackpoints(array $points): array
    {
        return array_values(array_filter($points, function (array $point): bool {
            $latitude = isset($point['latitude']) ? (float) $point['latitude'] : null;
            $longitude = isset($point['longitude']) ? (float) $point['longitude'] : null;

            if ($latitude === null || $longitude === null) {
                return false;
            }

            return $this->is_valid_coordinate($latitude, $longitude);
        }));
    }

    private function is_valid_coordinate(float $latitude, float $longitude): bool
    {
        return $latitude >= -90.0 && $latitude <= 90.0 && $longitude >= -180.0 && $longitude <= 180.0;
    }

    private function redirect_export_error(string $error_code, string $date_from = '', string $date_to = '', string $status = ''): void
    {
        $redirect_url = add_query_arg(
            array(
                'page' => 'bso-phoenix',
                'date_from' => $date_from,
                'date_to' => $date_to,
                'status' => $status,
                'export_error' => sanitize_key($error_code),
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }
}
