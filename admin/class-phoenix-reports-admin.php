<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Reports_Admin
{
    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_submenu'));
    }

    public function register_submenu(): void
    {
        add_submenu_page(
            'bso-phoenix',
            __('Rapportages', 'bso-phoenix'),
            __('Rapportages', 'bso-phoenix'),
            'manage_options',
            'bso-phoenix-reports',
            array($this, 'render_page')
        );
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
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

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Rapportages', 'bso-phoenix') . '</h1>';
        echo '<p>' . esc_html__('Gecombineerd overzicht van tochten, kosten, logboek en taken binnen de geselecteerde periode.', 'bso-phoenix') . '</p>';

        echo '<form method="get" action="" style="display:flex;gap:8px;align-items:end;margin:12px 0 18px;flex-wrap:wrap;">';
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

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:12px;margin:0 0 24px;">';
        $this->render_stat_card(__('Tochten', 'bso-phoenix'), (string) $report['trip_count']);
        $this->render_stat_card(__('Afstand totaal', 'bso-phoenix'), $settings_service->format_distance($report['distance_km'], 2));
        $this->render_stat_card(__('Vaarduur totaal', 'bso-phoenix'), number_format_i18n($report['duration_hours'], 2) . ' uur');
        $this->render_stat_card(__('Kosten totaal', 'bso-phoenix'), $settings_service->format_money($report['cost_total']));
        $this->render_stat_card(__('Logboekitems', 'bso-phoenix'), (string) $report['log_count']);
        $this->render_stat_card(__('Open taken', 'bso-phoenix'), (string) $report['todo_open_count']);
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

        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:18px;align-items:start;">';

        echo '<section>';
        echo '<h2>' . esc_html__('Laatste tochten', 'bso-phoenix') . '</h2>';
        if (empty($trips)) {
            echo '<p>' . esc_html__('Geen tochten gevonden.', 'bso-phoenix') . '</p>';
        } else {
            echo '<table class="widefat striped">';
            echo '<thead><tr><th>' . esc_html__('Trip', 'bso-phoenix') . '</th><th>' . esc_html__('Start', 'bso-phoenix') . '</th><th>' . esc_html__('Afstand', 'bso-phoenix') . '</th></tr></thead><tbody>';
            foreach (array_slice($trips, 0, 8) as $trip) {
                echo '<tr>';
                echo '<td>#' . esc_html((string) $trip['id']) . '</td>';
                echo '<td>' . esc_html($this->format_datetime((string) $trip['started_at'])) . '</td>';
                echo '<td>' . esc_html($settings_service->format_distance((float) $trip['distance_km'], 2)) . '</td>';
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
            'cost_total' => $cost_total,
            'costs_by_type' => $costs_by_type,
            'log_count' => count($logs),
            'todo_open_count' => $todo_open_count,
            'todos_by_status' => $todos_by_status,
            'date_from' => $date_from,
            'date_to' => $date_to,
        );
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
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        return $value;
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
}
