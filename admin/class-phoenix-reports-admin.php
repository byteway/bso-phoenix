<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Reports_Admin
{
    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_submenu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        add_action('admin_post_bso_phoenix_export_reports_csv', array($this, 'handle_export_reports_csv'));
        add_action('admin_post_bso_phoenix_export_reports_zip', array($this, 'handle_export_reports_zip'));
    }

    public function enqueue_assets(string $hook_suffix): void
    {
        if ($hook_suffix !== 'phoenix_page_bso-phoenix-reports') {
            return;
        }

        wp_enqueue_script(
            'chartjs',
            'https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js',
            array(),
            '4.4.3',
            true
        );
    }

    public function register_submenu(): void
    {
        add_submenu_page(
            'bso-phoenix',
            __('Rapportages', 'bso-phoenix'),
            __('Rapportages', 'bso-phoenix'),
            BSO_PHOENIX_CAP_READ,
            'bso-phoenix-reports',
            array($this, 'render_page')
        );
    }

    public function render_page(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_READ)) {
            wp_die(esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'bso-phoenix'));
        }

        $date_from = $this->normalize_date(isset($_GET['date_from']) ? sanitize_text_field((string) $_GET['date_from']) : '');
        $date_to = $this->normalize_date(isset($_GET['date_to']) ? sanitize_text_field((string) $_GET['date_to']) : '');

        $trip_service = new BSO_Phoenix_Trip_Service();
        $cost_service = new BSO_Phoenix_Cost_Service();
        $log_service = new BSO_Phoenix_Log_Service();
        $todo_service = new BSO_Phoenix_Todo_Service();
        $settings_service = new BSO_Phoenix_Settings_Service();

        $trips = $trip_service->get_trips_by_date_range($date_from, $date_to, '', 1000);
        $costs = $cost_service->get_costs($date_from, $date_to, '', 1000);
        $logs = $log_service->get_logs($date_from, $date_to, 1000);
        $todos = $todo_service->get_todos('', '', 1000);

        $report = $this->build_report($trips, $costs, $logs, $todos, $date_from, $date_to);
        $comparison = $this->build_period_comparison($trip_service, $cost_service, $log_service, $todo_service, $date_from, $date_to);
        $monthly_totals = $this->build_monthly_totals($trips, $costs);
        $top_costs = $this->build_top_costs($costs);
        $top_suppliers = $this->build_top_suppliers($costs);
        $monthly_cost_breakdown = $this->build_monthly_cost_breakdown($costs);
        $busiest_trip_days = $this->build_busiest_trip_days($trips);
        $chart_data = $this->build_chart_data($report, $comparison, $monthly_totals);
        $printed_at = wp_date('d-m-Y H:i');
        $period_label = $this->build_period_label($date_from, $date_to);

        echo '<div class="wrap">';
        $this->render_print_styles();
        echo '<h1>' . esc_html__('Rapportages', 'bso-phoenix') . '</h1>';
        echo '<p>' . esc_html__('Gecombineerd overzicht van tochten, kosten, logboek en taken binnen de geselecteerde periode.', 'bso-phoenix') . '</p>';
        if (isset($_GET['export_error'])) {
            $messages = array(
                'invalid_range' => __('Ongeldige periode: de einddatum ligt voor de startdatum.', 'bso-phoenix'),
                'zip_unavailable' => __('ZIP-export is niet beschikbaar op deze server (ZipArchive ontbreekt).', 'bso-phoenix'),
                'temp_file_failed' => __('Kon tijdelijk ZIP-bestand niet aanmaken.', 'bso-phoenix'),
                'zip_open_failed' => __('Kon ZIP-bestand niet opbouwen.', 'bso-phoenix'),
                'zip_write_failed' => __('Kon een of meer bestanden niet toevoegen aan het ZIP-pakket.', 'bso-phoenix'),
                'zip_close_failed' => __('Kon ZIP-bestand niet afronden.', 'bso-phoenix'),
                'zip_read_failed' => __('Kon ZIP-bestand niet uitlezen voor download.', 'bso-phoenix'),
            );
            $error_code = sanitize_key((string) $_GET['export_error']);
            if (isset($messages[$error_code])) {
                echo '<div class="notice notice-error is-dismissible"><p>' . esc_html($messages[$error_code]) . '</p></div>';
            }
        }

        echo '<div class="phoenix-report-toolbar no-print" style="display:flex;gap:8px;align-items:center;justify-content:space-between;flex-wrap:wrap;margin:12px 0 18px;">';
        echo '<form method="get" action="" style="display:flex;gap:8px;align-items:end;flex-wrap:wrap;margin:0;">';
        echo '<input type="hidden" name="page" value="bso-phoenix-reports" />';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Vanaf', 'bso-phoenix') . '</span>';
        echo '<input type="date" name="date_from" value="' . esc_attr($date_from) . '" />';
        echo '</label>';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Tot en met', 'bso-phoenix') . '</span>';
        echo '<input type="date" name="date_to" value="' . esc_attr($date_to) . '" />';
        echo '</label>';
        submit_button(__('Filter', 'bso-phoenix'), 'secondary', 'submit', false);
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=bso-phoenix-reports')) . '">' . esc_html__('Reset', 'bso-phoenix') . '</a>';
        echo '</form>';
        echo '<button type="button" class="button" onclick="window.print()">' . esc_html__('Printvriendelijke weergave', 'bso-phoenix') . '</button>';
        echo '</div>';

        echo '<form class="no-print" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:0 0 18px;">';
        echo '<input type="hidden" name="action" value="bso_phoenix_export_reports_csv" />';
        echo '<input type="hidden" name="date_from" value="' . esc_attr($date_from) . '" />';
        echo '<input type="hidden" name="date_to" value="' . esc_attr($date_to) . '" />';
        wp_nonce_field('bso_phoenix_export_reports_csv', 'bso_phoenix_reports_export_nonce');
        submit_button(__('Exporteer rapportage naar CSV', 'bso-phoenix'), 'secondary', 'submit', false);
        echo '</form>';

        echo '<form class="no-print" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin:0 0 18px;">';
        echo '<input type="hidden" name="action" value="bso_phoenix_export_reports_zip" />';
        echo '<input type="hidden" name="date_from" value="' . esc_attr($date_from) . '" />';
        echo '<input type="hidden" name="date_to" value="' . esc_attr($date_to) . '" />';
        wp_nonce_field('bso_phoenix_export_reports_zip', 'bso_phoenix_reports_export_zip_nonce');
        submit_button(__('Exporteer rapportagepakket (ZIP)', 'bso-phoenix'), 'primary', 'submit', false);
        echo '</form>';

        echo '<div class="phoenix-report-print">';
        echo '<div class="phoenix-report-print__header">';
        echo '<h1>' . esc_html__('Phoenix Rapportage', 'bso-phoenix') . '</h1>';
        echo '<p><strong>' . esc_html__('Periode', 'bso-phoenix') . ':</strong> ' . esc_html($period_label) . '</p>';
        echo '<p><strong>' . esc_html__('Gegenereerd op', 'bso-phoenix') . ':</strong> ' . esc_html($printed_at) . '</p>';
        echo '</div>';

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:0 0 24px;">';
        $this->render_stat_card(__('Tochten', 'bso-phoenix'), (string) $report['trip_count']);
        $this->render_stat_card(__('Afstand totaal', 'bso-phoenix'), $settings_service->format_distance($report['distance_km'], 2));
        $this->render_stat_card(__('Vaarduur totaal', 'bso-phoenix'), number_format_i18n($report['duration_hours'], 2) . ' uur');
        $this->render_stat_card(__('Gem. snelheid', 'bso-phoenix'), $settings_service->format_speed($report['average_speed_kmh'], 2));
        $this->render_stat_card(__('Kosten totaal', 'bso-phoenix'), $settings_service->format_money($report['cost_total']));
        $this->render_stat_card(__('Logboekitems', 'bso-phoenix'), (string) $report['log_count']);
        $this->render_stat_card(__('Open taken', 'bso-phoenix'), (string) $report['todo_open_count']);
        echo '</div>';

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;align-items:start;margin:0 0 24px;">';
        echo '<section style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px;">';
        echo '<h2 style="margin-top:0;">' . esc_html__('Maandtrends', 'bso-phoenix') . '</h2>';
        echo '<canvas id="phoenixMonthlyTrendChart" height="180"></canvas>';
        echo '</section>';
        echo '<section style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:16px;">';
        echo '<h2 style="margin-top:0;">' . esc_html__('Periodevergelijking', 'bso-phoenix') . '</h2>';
        echo '<canvas id="phoenixComparisonChart" height="180"></canvas>';
        echo '</section>';
        echo '</div>';

        echo '<h2>' . esc_html__('Periodevergelijking', 'bso-phoenix') . '</h2>';
        echo '<table class="widefat striped" style="max-width:880px;margin-bottom:24px;">';
        echo '<thead><tr><th>' . esc_html__('Metric', 'bso-phoenix') . '</th><th>' . esc_html__('Huidige periode', 'bso-phoenix') . '</th><th>' . esc_html__('Vorige periode', 'bso-phoenix') . '</th></tr></thead><tbody>';
        echo '<tr><td>' . esc_html__('Tochten', 'bso-phoenix') . '</td><td>' . esc_html((string) $comparison['current']['trip_count']) . '</td><td>' . esc_html((string) $comparison['previous']['trip_count']) . '</td></tr>';
        echo '<tr><td>' . esc_html__('Afstand', 'bso-phoenix') . '</td><td>' . esc_html($settings_service->format_distance($comparison['current']['distance_km'], 2)) . '</td><td>' . esc_html($settings_service->format_distance($comparison['previous']['distance_km'], 2)) . '</td></tr>';
        echo '<tr><td>' . esc_html__('Kosten', 'bso-phoenix') . '</td><td>' . esc_html($settings_service->format_money($comparison['current']['cost_total'])) . '</td><td>' . esc_html($settings_service->format_money($comparison['previous']['cost_total'])) . '</td></tr>';
        echo '<tr><td>' . esc_html__('Logboekitems', 'bso-phoenix') . '</td><td>' . esc_html((string) $comparison['current']['log_count']) . '</td><td>' . esc_html((string) $comparison['previous']['log_count']) . '</td></tr>';
        echo '</tbody></table>';

        echo '<h2>' . esc_html__('Maandtotalen', 'bso-phoenix') . '</h2>';
        if (empty($monthly_totals)) {
            echo '<p>' . esc_html__('Geen maandtotalen beschikbaar voor de huidige selectie.', 'bso-phoenix') . '</p>';
        } else {
            echo '<table class="widefat striped" style="max-width:880px;margin-bottom:24px;">';
            echo '<thead><tr><th>' . esc_html__('Maand', 'bso-phoenix') . '</th><th>' . esc_html__('Tochten', 'bso-phoenix') . '</th><th>' . esc_html__('Afstand', 'bso-phoenix') . '</th><th>' . esc_html__('Kosten', 'bso-phoenix') . '</th></tr></thead><tbody>';
            foreach ($monthly_totals as $month => $month_data) {
                echo '<tr>';
                echo '<td>' . esc_html($month) . '</td>';
                echo '<td>' . esc_html((string) $month_data['trip_count']) . '</td>';
                echo '<td>' . esc_html($settings_service->format_distance($month_data['distance_km'], 2)) . '</td>';
                echo '<td>' . esc_html($settings_service->format_money($month_data['cost_total'])) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;align-items:start;margin-bottom:24px;">';

        echo '<section>';
        echo '<h2>' . esc_html__('Maanddetail per kostensoort', 'bso-phoenix') . '</h2>';
        if (empty($monthly_cost_breakdown)) {
            echo '<p>' . esc_html__('Geen kostenverdeling per maand beschikbaar.', 'bso-phoenix') . '</p>';
        } else {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__('Maand', 'bso-phoenix') . '</th><th>' . esc_html__('Type', 'bso-phoenix') . '</th><th>' . esc_html__('Totaal', 'bso-phoenix') . '</th></tr></thead><tbody>';
            foreach ($monthly_cost_breakdown as $row) {
                echo '<tr>';
                echo '<td>' . esc_html($row['month']) . '</td>';
                echo '<td>' . esc_html($this->label_cost_type($row['cost_type'])) . '</td>';
                echo '<td>' . esc_html($settings_service->format_money((float) $row['total'])) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</section>';

        echo '<section>';
        echo '<h2>' . esc_html__('Drukste vaardagen', 'bso-phoenix') . '</h2>';
        if (empty($busiest_trip_days)) {
            echo '<p>' . esc_html__('Geen vaardagen beschikbaar in deze periode.', 'bso-phoenix') . '</p>';
        } else {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__('Datum', 'bso-phoenix') . '</th><th>' . esc_html__('Tochten', 'bso-phoenix') . '</th><th>' . esc_html__('Afstand', 'bso-phoenix') . '</th></tr></thead><tbody>';
            foreach ($busiest_trip_days as $row) {
                echo '<tr>';
                echo '<td>' . esc_html($row['date']) . '</td>';
                echo '<td>' . esc_html((string) $row['trip_count']) . '</td>';
                echo '<td>' . esc_html($settings_service->format_distance((float) $row['distance_km'], 2)) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</section>';

        echo '</div>';

        echo '<h2>' . esc_html__('Kostensoorten', 'bso-phoenix') . '</h2>';
        if (empty($report['costs_by_type'])) {
            echo '<p>' . esc_html__('Geen kosten gevonden in deze periode.', 'bso-phoenix') . '</p>';
        } else {
            echo '<table class="widefat striped" style="max-width:720px;margin-bottom:24px;">';
            echo '<thead><tr><th>' . esc_html__('Type', 'bso-phoenix') . '</th><th>' . esc_html__('Totaal', 'bso-phoenix') . '</th></tr></thead><tbody>';
            foreach ($report['costs_by_type'] as $type => $amount) {
                echo '<tr>';
                echo '<td>' . esc_html($this->label_cost_type($type)) . '</td>';
                echo '<td>' . esc_html($settings_service->format_money($amount)) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;align-items:start;margin-bottom:24px;">';

        echo '<section>';
        echo '<h2>' . esc_html__('Top kostenposten', 'bso-phoenix') . '</h2>';
        if (empty($top_costs)) {
            echo '<p>' . esc_html__('Geen kostenposten gevonden in deze periode.', 'bso-phoenix') . '</p>';
        } else {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__('Datum', 'bso-phoenix') . '</th><th>' . esc_html__('Type', 'bso-phoenix') . '</th><th>' . esc_html__('Leverancier', 'bso-phoenix') . '</th><th>' . esc_html__('Bedrag', 'bso-phoenix') . '</th></tr></thead><tbody>';
            foreach ($top_costs as $cost) {
                echo '<tr>';
                echo '<td>' . esc_html((string) $cost['cost_date']) . '</td>';
                echo '<td>' . esc_html($this->label_cost_type((string) $cost['cost_type'])) . '</td>';
                echo '<td>' . esc_html($cost['supplier'] !== '' ? (string) $cost['supplier'] : '-') . '</td>';
                echo '<td>' . esc_html($settings_service->format_money((float) $cost['amount'], (string) $cost['currency'])) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</section>';

        echo '<section>';
        echo '<h2>' . esc_html__('Grootste leveranciers', 'bso-phoenix') . '</h2>';
        if (empty($top_suppliers)) {
            echo '<p>' . esc_html__('Geen leveranciersdata gevonden in deze periode.', 'bso-phoenix') . '</p>';
        } else {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__('Leverancier', 'bso-phoenix') . '</th><th>' . esc_html__('Transacties', 'bso-phoenix') . '</th><th>' . esc_html__('Totaal', 'bso-phoenix') . '</th></tr></thead><tbody>';
            foreach ($top_suppliers as $supplier) {
                echo '<tr>';
                echo '<td>' . esc_html($supplier['supplier']) . '</td>';
                echo '<td>' . esc_html((string) $supplier['count']) . '</td>';
                echo '<td>' . esc_html($settings_service->format_money((float) $supplier['total'])) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</section>';

        echo '</div>';

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;align-items:start;">';

        echo '<section>';
        echo '<h2>' . esc_html__('Laatste tochten', 'bso-phoenix') . '</h2>';
        if (empty($trips)) {
            echo '<p>' . esc_html__('Geen tochten gevonden.', 'bso-phoenix') . '</p>';
        } else {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__('Trip', 'bso-phoenix') . '</th><th>' . esc_html__('Start', 'bso-phoenix') . '</th><th>' . esc_html__('Afstand', 'bso-phoenix') . '</th><th>' . esc_html__('Gem. snelheid', 'bso-phoenix') . '</th></tr></thead><tbody>';
            foreach (array_slice($trips, 0, 8) as $trip) {
                echo '<tr>';
                echo '<td>#' . esc_html((string) $trip['id']) . '</td>';
                echo '<td>' . esc_html($this->format_datetime((string) $trip['started_at'])) . '</td>';
                echo '<td>' . esc_html($settings_service->format_distance((float) $trip['distance_km'], 2)) . '</td>';
                echo '<td>' . esc_html($settings_service->format_speed((float) $trip['average_speed_kmh'], 2)) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</section>';

        echo '<section>';
        echo '<h2>' . esc_html__('Laatste logboekitems', 'bso-phoenix') . '</h2>';
        if (empty($logs)) {
            echo '<p>' . esc_html__('Geen logboekitems gevonden.', 'bso-phoenix') . '</p>';
        } else {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__('Datum', 'bso-phoenix') . '</th><th>' . esc_html__('Notitie', 'bso-phoenix') . '</th></tr></thead><tbody>';
            foreach (array_slice($logs, 0, 8) as $log) {
                echo '<tr>';
                echo '<td>' . esc_html((string) $log['log_date']) . '</td>';
                echo '<td>' . esc_html(wp_trim_words((string) $log['entry_text'], 12)) . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</section>';

        echo '<section>';
        echo '<h2>' . esc_html__('Taken per status', 'bso-phoenix') . '</h2>';
        echo '<table class="widefat striped">';
        echo '<thead><tr><th>' . esc_html__('Status', 'bso-phoenix') . '</th><th>' . esc_html__('Aantal', 'bso-phoenix') . '</th></tr></thead><tbody>';
        foreach ($report['todos_by_status'] as $status => $count) {
            echo '<tr>';
            echo '<td>' . esc_html($this->label_todo_status($status)) . '</td>';
            echo '<td>' . esc_html((string) $count) . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
        echo '</section>';

        echo '<section>';
        echo '<h2>' . esc_html__('Open taken met hoge prioriteit', 'bso-phoenix') . '</h2>';
        $important_todos = array_values(array_filter($todos, function ($todo) {
            return isset($todo['priority'], $todo['status']) && $todo['priority'] === 'high' && $todo['status'] !== 'done';
        }));
        if (empty($important_todos)) {
            echo '<p>' . esc_html__('Geen open taken met hoge prioriteit.', 'bso-phoenix') . '</p>';
        } else {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__('Titel', 'bso-phoenix') . '</th><th>' . esc_html__('Deadline', 'bso-phoenix') . '</th></tr></thead><tbody>';
            foreach (array_slice($important_todos, 0, 8) as $todo) {
                echo '<tr>';
                echo '<td>' . esc_html((string) $todo['title']) . '</td>';
                echo '<td>' . esc_html(! empty($todo['due_date']) ? (string) $todo['due_date'] : '-') . '</td>';
                echo '</tr>';
            }
            echo '</tbody></table>';
        }
        echo '</section>';

        echo '</div>';
        $this->render_chart_script($chart_data);
        echo '</div>';
        echo '</div>';
    }

    private function build_report(array $trips, array $costs, array $logs, array $todos, string $date_from, string $date_to): array
    {
        $distance_km = 0.0;
        $duration_minutes = 0.0;
        foreach ($trips as $trip) {
            $distance_km += isset($trip['distance_km']) ? (float) $trip['distance_km'] : 0.0;
            $duration_minutes += isset($trip['duration_minutes']) ? (float) $trip['duration_minutes'] : 0.0;
        }

        $cost_total = 0.0;
        $costs_by_type = array();
        foreach ($costs as $cost) {
            $type = isset($cost['cost_type']) ? (string) $cost['cost_type'] : 'other';
            $amount = isset($cost['amount']) ? (float) $cost['amount'] : 0.0;
            $cost_total += $amount;
            if (! isset($costs_by_type[$type])) {
                $costs_by_type[$type] = 0.0;
            }
            $costs_by_type[$type] += $amount;
        }

        $todo_open_count = 0;
        $todos_by_status = array(
            'open' => 0,
            'in_progress' => 0,
            'done' => 0,
        );
        foreach ($todos as $todo) {
            $status = isset($todo['status']) ? (string) $todo['status'] : 'open';
            if (isset($todos_by_status[$status])) {
                $todos_by_status[$status]++;
            }
            if ($status !== 'done') {
                $todo_open_count++;
            }
        }

        return array(
            'trip_count' => count($trips),
            'distance_km' => $distance_km,
            'duration_hours' => $duration_minutes / 60,
            'average_speed_kmh' => $duration_minutes > 0 ? ($distance_km / ($duration_minutes / 60)) : 0.0,
            'cost_total' => $cost_total,
            'costs_by_type' => $costs_by_type,
            'log_count' => count($logs),
            'todo_open_count' => $todo_open_count,
            'todos_by_status' => $todos_by_status,
            'date_from' => $date_from,
            'date_to' => $date_to,
        );
    }

    private function build_period_comparison(BSO_Phoenix_Trip_Service $trip_service, BSO_Phoenix_Cost_Service $cost_service, BSO_Phoenix_Log_Service $log_service, BSO_Phoenix_Todo_Service $todo_service, string $date_from, string $date_to): array
    {
        $current = $this->build_report(
            $trip_service->get_trips_by_date_range($date_from, $date_to, '', 1000),
            $cost_service->get_costs($date_from, $date_to, '', 1000),
            $log_service->get_logs($date_from, $date_to, 1000),
            $todo_service->get_todos('', '', 1000),
            $date_from,
            $date_to
        );

        if ($date_from === '' || $date_to === '') {
            return array(
                'current' => $current,
                'previous' => array(
                    'trip_count' => 0,
                    'distance_km' => 0.0,
                    'cost_total' => 0.0,
                    'log_count' => 0,
                ),
            );
        }

        $from_ts = strtotime($date_from . ' 00:00:00');
        $to_ts = strtotime($date_to . ' 00:00:00');
        if ($from_ts === false || $to_ts === false || $to_ts < $from_ts) {
            return array(
                'current' => $current,
                'previous' => array(
                    'trip_count' => 0,
                    'distance_km' => 0.0,
                    'cost_total' => 0.0,
                    'log_count' => 0,
                ),
            );
        }

        $period_days = (int) floor(($to_ts - $from_ts) / DAY_IN_SECONDS) + 1;
        $previous_to_ts = $from_ts - DAY_IN_SECONDS;
        $previous_from_ts = $previous_to_ts - (($period_days - 1) * DAY_IN_SECONDS);

        $previous_from = gmdate('Y-m-d', $previous_from_ts);
        $previous_to = gmdate('Y-m-d', $previous_to_ts);

        $previous = $this->build_report(
            $trip_service->get_trips_by_date_range($previous_from, $previous_to, '', 1000),
            $cost_service->get_costs($previous_from, $previous_to, '', 1000),
            $log_service->get_logs($previous_from, $previous_to, 1000),
            $todo_service->get_todos('', '', 1000),
            $previous_from,
            $previous_to
        );

        return array(
            'current' => $current,
            'previous' => $previous,
        );
    }

    private function build_monthly_totals(array $trips, array $costs): array
    {
        $months = array();

        foreach ($trips as $trip) {
            if (empty($trip['started_at'])) {
                continue;
            }

            $month = substr((string) $trip['started_at'], 0, 7);
            if (! isset($months[$month])) {
                $months[$month] = array(
                    'trip_count' => 0,
                    'distance_km' => 0.0,
                    'cost_total' => 0.0,
                );
            }

            $months[$month]['trip_count']++;
            $months[$month]['distance_km'] += isset($trip['distance_km']) ? (float) $trip['distance_km'] : 0.0;
        }

        foreach ($costs as $cost) {
            if (empty($cost['cost_date'])) {
                continue;
            }

            $month = substr((string) $cost['cost_date'], 0, 7);
            if (! isset($months[$month])) {
                $months[$month] = array(
                    'trip_count' => 0,
                    'distance_km' => 0.0,
                    'cost_total' => 0.0,
                );
            }

            $months[$month]['cost_total'] += isset($cost['amount']) ? (float) $cost['amount'] : 0.0;
        }

        krsort($months);

        return $months;
    }

    private function build_monthly_cost_breakdown(array $costs): array
    {
        $rows = array();

        foreach ($costs as $cost) {
            if (empty($cost['cost_date'])) {
                continue;
            }

            $month = substr((string) $cost['cost_date'], 0, 7);
            $type = isset($cost['cost_type']) ? (string) $cost['cost_type'] : 'other';
            $key = $month . '|' . $type;

            if (! isset($rows[$key])) {
                $rows[$key] = array(
                    'month' => $month,
                    'cost_type' => $type,
                    'total' => 0.0,
                );
            }

            $rows[$key]['total'] += isset($cost['amount']) ? (float) $cost['amount'] : 0.0;
        }

        usort($rows, function ($left, $right) {
            $monthCompare = strcmp((string) $right['month'], (string) $left['month']);
            if ($monthCompare !== 0) {
                return $monthCompare;
            }

            return ((float) $right['total']) <=> ((float) $left['total']);
        });

        return array_slice($rows, 0, 24);
    }

    private function build_busiest_trip_days(array $trips): array
    {
        $days = array();

        foreach ($trips as $trip) {
            if (empty($trip['started_at'])) {
                continue;
            }

            $date = substr((string) $trip['started_at'], 0, 10);
            if (! isset($days[$date])) {
                $days[$date] = array(
                    'date' => $date,
                    'trip_count' => 0,
                    'distance_km' => 0.0,
                );
            }

            $days[$date]['trip_count']++;
            $days[$date]['distance_km'] += isset($trip['distance_km']) ? (float) $trip['distance_km'] : 0.0;
        }

        usort($days, function ($left, $right) {
            $tripCompare = ((int) $right['trip_count']) <=> ((int) $left['trip_count']);
            if ($tripCompare !== 0) {
                return $tripCompare;
            }

            return ((float) $right['distance_km']) <=> ((float) $left['distance_km']);
        });

        return array_slice($days, 0, 10);
    }

    private function build_top_costs(array $costs): array
    {
        usort($costs, function ($left, $right) {
            return ((float) ($right['amount'] ?? 0)) <=> ((float) ($left['amount'] ?? 0));
        });

        return array_slice($costs, 0, 8);
    }

    private function build_top_suppliers(array $costs): array
    {
        $suppliers = array();

        foreach ($costs as $cost) {
            $supplier = trim((string) ($cost['supplier'] ?? ''));
            if ($supplier === '') {
                continue;
            }

            if (! isset($suppliers[$supplier])) {
                $suppliers[$supplier] = array(
                    'supplier' => $supplier,
                    'count' => 0,
                    'total' => 0.0,
                );
            }

            $suppliers[$supplier]['count']++;
            $suppliers[$supplier]['total'] += (float) ($cost['amount'] ?? 0);
        }

        usort($suppliers, function ($left, $right) {
            return ((float) ($right['total'] ?? 0)) <=> ((float) ($left['total'] ?? 0));
        });

        return array_slice($suppliers, 0, 8);
    }

    private function build_chart_data(array $report, array $comparison, array $monthly_totals): array
    {
        $months = array_reverse(array_keys($monthly_totals));
        $trip_counts = array();
        $distances = array();
        $costs = array();

        foreach ($months as $month) {
            $trip_counts[] = (int) $monthly_totals[$month]['trip_count'];
            $distances[] = (float) $monthly_totals[$month]['distance_km'];
            $costs[] = (float) $monthly_totals[$month]['cost_total'];
        }

        return array(
            'monthly' => array(
                'labels' => $months,
                'tripCounts' => $trip_counts,
                'distancesKm' => $distances,
                'costTotals' => $costs,
            ),
            'comparison' => array(
                'labels' => array('Tochten', 'Afstand', 'Kosten', 'Logboekitems'),
                'current' => array(
                    (float) $comparison['current']['trip_count'],
                    (float) $comparison['current']['distance_km'],
                    (float) $comparison['current']['cost_total'],
                    (float) $comparison['current']['log_count'],
                ),
                'previous' => array(
                    (float) $comparison['previous']['trip_count'],
                    (float) $comparison['previous']['distance_km'],
                    (float) $comparison['previous']['cost_total'],
                    (float) $comparison['previous']['log_count'],
                ),
            ),
        );
    }

    private function render_chart_script(array $chart_data): void
    {
        $json = wp_json_encode($chart_data);
        if (! is_string($json)) {
            return;
        }

        echo '<script>';
        echo 'document.addEventListener("DOMContentLoaded", function () {';
        echo 'if (typeof Chart === "undefined") { return; }';
        echo 'var chartData = ' . $json . ';';
        echo 'var monthlyCanvas = document.getElementById("phoenixMonthlyTrendChart");';
        echo 'if (monthlyCanvas) {';
        echo 'new Chart(monthlyCanvas, {';
        echo 'type: "line",';
        echo 'data: {';
        echo 'labels: chartData.monthly.labels,';
        echo 'datasets: [';
        echo '{ label: "Tochten", data: chartData.monthly.tripCounts, borderColor: "#0a6a4a", backgroundColor: "rgba(10,106,74,0.12)", yAxisID: "y", tension: 0.25 },';
        echo '{ label: "Afstand (km)", data: chartData.monthly.distancesKm, borderColor: "#1d4ed8", backgroundColor: "rgba(29,78,216,0.10)", yAxisID: "y1", tension: 0.25 },';
        echo '{ label: "Kosten", data: chartData.monthly.costTotals, borderColor: "#b45309", backgroundColor: "rgba(180,83,9,0.10)", yAxisID: "y2", tension: 0.25 }';
        echo ']';
        echo '},';
        echo 'options: {';
        echo 'responsive: true,';
        echo 'interaction: { mode: "index", intersect: false },';
        echo 'scales: {';
        echo 'y: { type: "linear", position: "left", beginAtZero: true },';
        echo 'y1: { type: "linear", position: "right", beginAtZero: true, grid: { drawOnChartArea: false } },';
        echo 'y2: { type: "linear", position: "right", beginAtZero: true, display: false }';
        echo '}';
        echo '}';
        echo '});';
        echo '}';
        echo 'var comparisonCanvas = document.getElementById("phoenixComparisonChart");';
        echo 'if (comparisonCanvas) {';
        echo 'new Chart(comparisonCanvas, {';
        echo 'type: "bar",';
        echo 'data: {';
        echo 'labels: chartData.comparison.labels,';
        echo 'datasets: [';
        echo '{ label: "Huidige periode", data: chartData.comparison.current, backgroundColor: "rgba(10,106,74,0.75)" },';
        echo '{ label: "Vorige periode", data: chartData.comparison.previous, backgroundColor: "rgba(107,114,128,0.65)" }';
        echo ']';
        echo '},';
        echo 'options: { responsive: true, scales: { y: { beginAtZero: true } } }';
        echo '});';
        echo '}';
        echo '});';
        echo '</script>';
    }

    private function render_stat_card(string $label, string $value): void
    {
        echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px;">';
        echo '<div style="font-size:12px;color:#50575e;">' . esc_html($label) . '</div>';
        echo '<div style="font-size:22px;font-weight:600;line-height:1.3;">' . esc_html($value) . '</div>';
        echo '</div>';
    }

    private function normalize_date(string $value): string
    {
		return BSO_Phoenix_Hardening::normalize_date($value);
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

    private function label_cost_type(string $type): string
    {
        $labels = array(
            'fuel' => __('Brandstof', 'bso-phoenix'),
            'maintenance' => __('Onderhoud', 'bso-phoenix'),
            'mooring' => __('Ligplaats', 'bso-phoenix'),
            'insurance' => __('Verzekering', 'bso-phoenix'),
            'parts' => __('Onderdelen', 'bso-phoenix'),
            'other' => __('Overig', 'bso-phoenix'),
        );

        return $labels[$type] ?? $type;
    }

    private function label_todo_status(string $status): string
    {
        $labels = array(
            'open' => __('Open', 'bso-phoenix'),
            'in_progress' => __('In behandeling', 'bso-phoenix'),
            'done' => __('Afgerond', 'bso-phoenix'),
        );

        return $labels[$status] ?? $status;
    }

    private function build_period_label(string $date_from, string $date_to): string
    {
        if ($date_from === '' && $date_to === '') {
            return __('Alle beschikbare data', 'bso-phoenix');
        }

        if ($date_from !== '' && $date_to !== '') {
            return $date_from . ' t/m ' . $date_to;
        }

        if ($date_from !== '') {
            return __('Vanaf', 'bso-phoenix') . ' ' . $date_from;
        }

        return __('Tot en met', 'bso-phoenix') . ' ' . $date_to;
    }

    private function render_print_styles(): void
    {
        echo '<style>';
        echo '.phoenix-report-print__header{display:none;margin:0 0 24px;}';
        echo '.phoenix-report-print__header h1{margin:0 0 8px;font-size:28px;}';
        echo '.phoenix-report-print__header p{margin:0 0 4px;font-size:14px;}';
        echo '@media print {';
        echo 'body.wp-admin{background:#fff !important;}';
        echo '#wpadminbar,#adminmenumain,#screen-meta,#screen-meta-links,.notice,.update-nag,.wrap > h1:first-of-type,.wrap > p:first-of-type,.no-print{display:none !important;}';
        echo '#wpcontent,#wpbody-content{margin:0 !important;padding:0 !important;}';
        echo '.wrap{margin:0 !important;padding:0 !important;}';
        echo '.phoenix-report-print__header{display:block;}';
        echo 'table{page-break-inside:avoid;width:100% !important;}';
        echo 'section,div{break-inside:avoid;}';
        echo 'canvas{max-width:100% !important;height:auto !important;}';
        echo '}';
        echo '</style>';
    }

    public function handle_export_reports_csv(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_READ)) {
            wp_die(esc_html__('Je hebt geen rechten om deze actie uit te voeren.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_export_reports_csv', 'bso_phoenix_reports_export_nonce');

        $date_from = $this->normalize_date(isset($_POST['date_from']) ? sanitize_text_field((string) $_POST['date_from']) : '');
        $date_to = $this->normalize_date(isset($_POST['date_to']) ? sanitize_text_field((string) $_POST['date_to']) : '');

        if (! BSO_Phoenix_Hardening::is_valid_date_range($date_from, $date_to)) {
            $this->redirect_export_error('invalid_range', $date_from, $date_to);
        }

        $trip_service = new BSO_Phoenix_Trip_Service();
        $cost_service = new BSO_Phoenix_Cost_Service();
        $log_service = new BSO_Phoenix_Log_Service();
        $todo_service = new BSO_Phoenix_Todo_Service();
        $settings_service = new BSO_Phoenix_Settings_Service();

        $trips = $trip_service->get_trips_by_date_range($date_from, $date_to, '', 1000);
        $costs = $cost_service->get_costs($date_from, $date_to, '', 1000);
        $logs = $log_service->get_logs($date_from, $date_to, 1000);
        $todos = $todo_service->get_todos('', '', 1000);
        $report = $this->build_report($trips, $costs, $logs, $todos, $date_from, $date_to);
        $comparison = $this->build_period_comparison($trip_service, $cost_service, $log_service, $todo_service, $date_from, $date_to);
        $monthly_totals = $this->build_monthly_totals($trips, $costs);
        $monthly_cost_breakdown = $this->build_monthly_cost_breakdown($costs);
        $busiest_trip_days = $this->build_busiest_trip_days($trips);
        $top_costs = $this->build_top_costs($costs);
        $top_suppliers = $this->build_top_suppliers($costs);

        $filename = sanitize_file_name('phoenix-report-' . gmdate('Ymd-His') . '.csv');

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('Kon CSV-output niet openen.', 'bso-phoenix'));
        }

        $this->write_csv_row($output, array('metric', 'value'));
        $this->write_csv_row($output, array('trip_count', (string) $report['trip_count']));
        $this->write_csv_row($output, array('distance', $settings_service->format_distance($report['distance_km'], 2)));
        $this->write_csv_row($output, array('duration_hours', number_format_i18n($report['duration_hours'], 2)));
        $this->write_csv_row($output, array('average_speed', $settings_service->format_speed($report['average_speed_kmh'], 2)));
        $this->write_csv_row($output, array('cost_total', $settings_service->format_money($report['cost_total'])));
        $this->write_csv_row($output, array('log_count', (string) $report['log_count']));
        $this->write_csv_row($output, array('todo_open_count', (string) $report['todo_open_count']));

        foreach ($report['costs_by_type'] as $type => $amount) {
            $this->write_csv_row($output, array('cost_type_' . $type, $settings_service->format_money($amount)));
        }

        foreach ($report['todos_by_status'] as $status => $count) {
            $this->write_csv_row($output, array('todo_status_' . $status, (string) $count));
        }

        $this->write_csv_row($output, array());
        $this->write_csv_row($output, array('comparison_metric', 'current_period', 'previous_period'));
        $this->write_csv_row($output, array('trip_count', (string) $comparison['current']['trip_count'], (string) $comparison['previous']['trip_count']));
        $this->write_csv_row($output, array('distance', $settings_service->format_distance($comparison['current']['distance_km'], 2), $settings_service->format_distance($comparison['previous']['distance_km'], 2)));
        $this->write_csv_row($output, array('cost_total', $settings_service->format_money($comparison['current']['cost_total']), $settings_service->format_money($comparison['previous']['cost_total'])));
        $this->write_csv_row($output, array('log_count', (string) $comparison['current']['log_count'], (string) $comparison['previous']['log_count']));

        $this->write_csv_row($output, array());
        $this->write_csv_row($output, array('monthly_total_month', 'trip_count', 'distance', 'cost_total'));
        foreach ($monthly_totals as $month => $month_data) {
            $this->write_csv_row($output, array(
                $month,
                (string) $month_data['trip_count'],
                $settings_service->format_distance($month_data['distance_km'], 2),
                $settings_service->format_money($month_data['cost_total']),
            ));
        }

        $this->write_csv_row($output, array());
        $this->write_csv_row($output, array('monthly_cost_breakdown_month', 'cost_type', 'total'));
        foreach ($monthly_cost_breakdown as $row) {
            $this->write_csv_row($output, array(
                (string) $row['month'],
                (string) $row['cost_type'],
                $settings_service->format_money((float) $row['total']),
            ));
        }

        $this->write_csv_row($output, array());
        $this->write_csv_row($output, array('top_cost_date', 'cost_type', 'supplier', 'amount'));
        foreach ($top_costs as $cost) {
            $this->write_csv_row($output, array(
                (string) $cost['cost_date'],
                (string) $cost['cost_type'],
                (string) $cost['supplier'],
                $settings_service->format_money((float) $cost['amount'], (string) $cost['currency']),
            ));
        }

        $this->write_csv_row($output, array());
        $this->write_csv_row($output, array('top_supplier', 'transactions', 'total'));
        foreach ($top_suppliers as $supplier) {
            $this->write_csv_row($output, array(
                (string) $supplier['supplier'],
                (string) $supplier['count'],
                $settings_service->format_money((float) $supplier['total']),
            ));
        }

        $this->write_csv_row($output, array());
        $this->write_csv_row($output, array('busiest_day', 'trip_count', 'distance'));
        foreach ($busiest_trip_days as $day) {
            $this->write_csv_row($output, array(
                (string) $day['date'],
                (string) $day['trip_count'],
                $settings_service->format_distance((float) $day['distance_km'], 2),
            ));
        }

        fclose($output);
        exit;
    }

    public function handle_export_reports_zip(): void
    {
        if (! current_user_can(BSO_PHOENIX_CAP_READ)) {
            wp_die(esc_html__('Je hebt geen rechten om deze actie uit te voeren.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_export_reports_zip', 'bso_phoenix_reports_export_zip_nonce');

        $date_from = $this->normalize_date(isset($_POST['date_from']) ? sanitize_text_field((string) $_POST['date_from']) : '');
        $date_to = $this->normalize_date(isset($_POST['date_to']) ? sanitize_text_field((string) $_POST['date_to']) : '');

        if (! BSO_Phoenix_Hardening::is_valid_date_range($date_from, $date_to)) {
            $this->redirect_export_error('invalid_range', $date_from, $date_to);
        }

        if (! class_exists('ZipArchive')) {
            $this->redirect_export_error('zip_unavailable', $date_from, $date_to);
        }

        $trip_service = new BSO_Phoenix_Trip_Service();
        $cost_service = new BSO_Phoenix_Cost_Service();
        $log_service = new BSO_Phoenix_Log_Service();
        $todo_service = new BSO_Phoenix_Todo_Service();

        $trips = $trip_service->get_trips_by_date_range($date_from, $date_to, '', 1000);
        $costs = $cost_service->get_costs($date_from, $date_to, '', 1000);
        $logs = $log_service->get_logs($date_from, $date_to, 1000);
        $todos = $todo_service->get_todos('', '', 1000);

        $zip_path = tempnam(sys_get_temp_dir(), 'phoenix-report-');
        if ($zip_path === false) {
            $this->redirect_export_error('temp_file_failed', $date_from, $date_to);
        }

        $zip = new ZipArchive();
        $opened = $zip->open($zip_path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ($opened !== true) {
            @unlink($zip_path);
            $this->redirect_export_error('zip_open_failed', $date_from, $date_to);
        }

        if (! $zip->addFromString('README.txt', $this->build_export_readme())) {
            $zip->close();
            @unlink($zip_path);
            $this->redirect_export_error('zip_write_failed', $date_from, $date_to);
        }
        if (! $zip->addFromString('summary.txt', $this->build_export_summary($date_from, $date_to, $trips, $costs, $logs, $todos))) {
            $zip->close();
            @unlink($zip_path);
            $this->redirect_export_error('zip_write_failed', $date_from, $date_to);
        }

        if (! $zip->addFromString('csv/trips.csv', $this->csv_to_string(
            array('trip_id', 'started_at', 'ended_at', 'status', 'distance_km', 'duration_minutes', 'average_speed_kmh', 'estimated_fuel_used_l'),
            array_map(function ($trip): array {
                return array(
                    (string) ($trip['id'] ?? ''),
                    (string) ($trip['started_at'] ?? ''),
                    (string) ($trip['ended_at'] ?? ''),
                    (string) ($trip['status'] ?? ''),
                    (string) ($trip['distance_km'] ?? ''),
                    (string) ($trip['duration_minutes'] ?? ''),
                    (string) ($trip['average_speed_kmh'] ?? ''),
                    (string) ($trip['estimated_fuel_used_l'] ?? ''),
                );
            }, $trips)
        ))) {
            $zip->close();
            @unlink($zip_path);
            $this->redirect_export_error('zip_write_failed', $date_from, $date_to);
        }

        if (! $zip->addFromString('csv/costs.csv', $this->csv_to_string(
            array('id', 'trip_id', 'cost_type', 'amount', 'currency', 'cost_date', 'supplier', 'notes'),
            array_map(function ($cost): array {
                return array(
                    (string) ($cost['id'] ?? ''),
                    (string) ($cost['trip_id'] ?? ''),
                    (string) ($cost['cost_type'] ?? ''),
                    (string) ($cost['amount'] ?? ''),
                    (string) ($cost['currency'] ?? ''),
                    (string) ($cost['cost_date'] ?? ''),
                    (string) ($cost['supplier'] ?? ''),
                    (string) ($cost['notes'] ?? ''),
                );
            }, $costs)
        ))) {
            $zip->close();
            @unlink($zip_path);
            $this->redirect_export_error('zip_write_failed', $date_from, $date_to);
        }

        if (! $zip->addFromString('csv/logs.csv', $this->csv_to_string(
            array('id', 'trip_id', 'log_date', 'log_time', 'entry_text', 'created_at'),
            array_map(function ($log): array {
                return array(
                    (string) ($log['id'] ?? ''),
                    (string) ($log['trip_id'] ?? ''),
                    (string) ($log['log_date'] ?? ''),
                    (string) ($log['log_time'] ?? ''),
                    (string) ($log['entry_text'] ?? ''),
                    (string) ($log['created_at'] ?? ''),
                );
            }, $logs)
        ))) {
            $zip->close();
            @unlink($zip_path);
            $this->redirect_export_error('zip_write_failed', $date_from, $date_to);
        }

        if (! $zip->addFromString('csv/todos.csv', $this->csv_to_string(
            array('id', 'title', 'status', 'priority', 'due_date', 'completed_at', 'created_at'),
            array_map(function ($todo): array {
                return array(
                    (string) ($todo['id'] ?? ''),
                    (string) ($todo['title'] ?? ''),
                    (string) ($todo['status'] ?? ''),
                    (string) ($todo['priority'] ?? ''),
                    (string) ($todo['due_date'] ?? ''),
                    (string) ($todo['completed_at'] ?? ''),
                    (string) ($todo['created_at'] ?? ''),
                );
            }, $todos)
        ))) {
            $zip->close();
            @unlink($zip_path);
            $this->redirect_export_error('zip_write_failed', $date_from, $date_to);
        }

        foreach ($trips as $trip) {
            $trip_id = isset($trip['id']) ? (int) $trip['id'] : 0;
            if ($trip_id <= 0) {
                continue;
            }

            $points = $trip_service->get_trackpoints_for_trip($trip_id);
            if (empty($points)) {
                continue;
            }

            if (! $zip->addFromString(
                'gpx/trip-' . $trip_id . '.gpx',
                $this->build_trip_gpx((array) $trip, $points)
            )) {
                $zip->close();
                @unlink($zip_path);
                $this->redirect_export_error('zip_write_failed', $date_from, $date_to);
            }
        }

        if (! $zip->close()) {
            @unlink($zip_path);
            $this->redirect_export_error('zip_close_failed', $date_from, $date_to);
        }

        $filesize = filesize($zip_path);
        if ($filesize === false || $filesize <= 0) {
            @unlink($zip_path);
            $this->redirect_export_error('zip_read_failed', $date_from, $date_to);
        }

        $filename = sanitize_file_name('phoenix-exportpakket-' . gmdate('Ymd-His') . '.zip');
        nocache_headers();
        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . (string) $filesize);

        $sent = readfile($zip_path);
        @unlink($zip_path);
        if ($sent === false) {
            wp_die(esc_html__('Kon ZIP-download niet uitleveren.', 'bso-phoenix'));
        }
        exit;
    }

    private function write_csv_row($output, array $row): void
    {
        if (fputcsv($output, $row) === false) {
            fclose($output);
            wp_die(esc_html__('Kon CSV-rij niet schrijven.', 'bso-phoenix'));
        }
    }

    private function redirect_export_error(string $error_code, string $date_from = '', string $date_to = ''): void
    {
        $redirect_url = add_query_arg(
            array(
                'page' => 'bso-phoenix-reports',
                'date_from' => $date_from,
                'date_to' => $date_to,
                'export_error' => sanitize_key($error_code),
            ),
            admin_url('admin.php')
        );

        wp_safe_redirect($redirect_url);
        exit;
    }

    private function csv_to_string(array $header, array $rows): string
    {
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            return '';
        }

        fputcsv($stream, $header);
        foreach ($rows as $row) {
            fputcsv($stream, $row);
        }

        rewind($stream);
        $content = stream_get_contents($stream);
        fclose($stream);

        return is_string($content) ? $content : '';
    }

    private function build_export_readme(): string
    {
        return implode("\n", array(
            'BSO Phoenix - Rapportage exportpakket',
            '',
            'Inhoud:',
            '- csv/trips.csv',
            '- csv/costs.csv',
            '- csv/logs.csv',
            '- csv/todos.csv',
            '- gpx/trip-<id>.gpx',
            '- summary.txt',
            '',
            'Dit pakket is gegenereerd vanuit de Rapportages pagina.',
        ));
    }

    private function build_export_summary(string $date_from, string $date_to, array $trips, array $costs, array $logs, array $todos): string
    {
        $lines = array(
            'BSO Phoenix - Export samenvatting',
            'Gegenereerd op: ' . gmdate('Y-m-d H:i:s') . ' UTC',
            'Periode vanaf: ' . ($date_from !== '' ? $date_from : '-'),
            'Periode tot en met: ' . ($date_to !== '' ? $date_to : '-'),
            '',
            'Aantallen',
            '- Trips: ' . count($trips),
            '- Costs: ' . count($costs),
            '- Logs: ' . count($logs),
            '- Todos: ' . count($todos),
        );

        return implode("\n", $lines);
    }

    private function build_trip_gpx(array $trip, array $points): string
    {
        $trip_id = isset($trip['id']) ? (int) $trip['id'] : 0;
        $name = 'Phoenix Trip #' . $trip_id;

        $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n";
        $xml .= '<gpx version="1.1" creator="BSO Phoenix" xmlns="http://www.topografix.com/GPX/1/1">';
        $xml .= '<metadata><name>' . esc_html($name) . '</name></metadata>';
        $xml .= '<trk><name>' . esc_html($name) . '</name><trkseg>';

        foreach ($points as $point) {
            $lat = isset($point['latitude']) ? (float) $point['latitude'] : 0.0;
            $lon = isset($point['longitude']) ? (float) $point['longitude'] : 0.0;
            $ele = isset($point['altitude_m']) ? (string) $point['altitude_m'] : '';
            $recorded_at = isset($point['recorded_at']) ? (string) $point['recorded_at'] : '';
            $timestamp = $recorded_at !== '' ? strtotime($recorded_at) : false;

            $xml .= '<trkpt lat="' . esc_attr((string) $lat) . '" lon="' . esc_attr((string) $lon) . '">';
            if ($ele !== '') {
                $xml .= '<ele>' . esc_html($ele) . '</ele>';
            }
            if ($timestamp !== false) {
                $xml .= '<time>' . gmdate('c', $timestamp) . '</time>';
            }
            $xml .= '</trkpt>';
        }

        $xml .= '</trkseg></trk></gpx>';

        return $xml;
    }
}
