<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Cost_Admin
{
    private const TYPE_LABELS = array(
        'fuel' => 'Brandstof',
        'maintenance' => 'Onderhoud',
        'mooring' => 'Ligplaats',
        'insurance' => 'Verzekering',
        'parts' => 'Onderdelen',
        'other' => 'Overig',
    );

    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_submenu'));
        add_action('admin_post_bso_phoenix_save_cost', array($this, 'handle_save_cost'));
        add_action('admin_post_bso_phoenix_update_cost', array($this, 'handle_update_cost'));
        add_action('admin_post_bso_phoenix_delete_cost', array($this, 'handle_delete_cost'));
        add_action('admin_post_bso_phoenix_export_costs_csv', array($this, 'handle_export_costs_csv'));
    }

    public function register_submenu(): void
    {
        add_submenu_page(
            'bso-phoenix',
            __('Kostenbeheer', 'bso-phoenix'),
            __('Kosten', 'bso-phoenix'),
            'manage_options',
            'bso-phoenix-costs',
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
        $filter_type = isset($_GET['cost_type']) ? sanitize_key((string) $_GET['cost_type']) : '';
        if (! array_key_exists($filter_type, self::TYPE_LABELS) && $filter_type !== '') {
            $filter_type = '';
        }

        $edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
        $service = new BSO_Phoenix_Cost_Service();
        $costs = $service->get_costs($date_from, $date_to, $filter_type, 100);
        $summary = $service->get_summary($date_from, $date_to);
        $edit_cost = $edit_id > 0 ? $service->get_cost_by_id($edit_id) : null;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Kostenbeheer', 'bso-phoenix') . '</h1>';

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Kostenpost opgeslagen.', 'bso-phoenix') . '</p></div>';
        }
        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Kostenpost verwijderd.', 'bso-phoenix') . '</p></div>';
        }

        // Summary cards
        echo '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:12px;margin:16px 0 20px;">';
        $this->render_stat_card(__('Totaal', 'bso-phoenix'), '€ ' . number_format_i18n((float) $summary['grand_total'], 2));
        foreach ($summary['by_type'] as $row) {
            $type_label = self::TYPE_LABELS[$row['cost_type']] ?? $row['cost_type'];
            $this->render_stat_card($type_label, '€ ' . number_format_i18n((float) $row['total'], 2));
        }
        echo '</div>';

        // Form
        $form_action = is_array($edit_cost) ? 'bso_phoenix_update_cost' : 'bso_phoenix_save_cost';
        $form_nonce = $form_action . (is_array($edit_cost) ? '_' . $edit_id : '');

        echo '<h2>' . esc_html(is_array($edit_cost) ? __('Kostenpost bewerken', 'bso-phoenix') : __('Nieuwe kostenpost', 'bso-phoenix')) . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="' . esc_attr($form_action) . '" />';
        if (is_array($edit_cost)) {
            echo '<input type="hidden" name="cost_id" value="' . esc_attr((string) $edit_cost['id']) . '" />';
        }
        wp_nonce_field($form_nonce, 'bso_phoenix_cost_nonce');

        echo '<table class="form-table" role="presentation">';
        echo '<tr><th scope="row"><label for="cost_date">' . esc_html__('Datum', 'bso-phoenix') . '</label></th>';
        echo '<td><input type="date" id="cost_date" name="cost_date" required value="' . esc_attr(is_array($edit_cost) ? (string) $edit_cost['cost_date'] : current_time('Y-m-d')) . '" /></td></tr>';

        echo '<tr><th scope="row"><label for="cost_type">' . esc_html__('Type', 'bso-phoenix') . '</label></th>';
        echo '<td><select id="cost_type" name="cost_type">';
        foreach (self::TYPE_LABELS as $val => $label) {
            $sel = is_array($edit_cost) ? selected((string) $edit_cost['cost_type'], $val, false) : '';
            echo '<option value="' . esc_attr($val) . '"' . $sel . '>' . esc_html($label) . '</option>';
        }
        echo '</select></td></tr>';

        echo '<tr><th scope="row"><label for="cost_amount">' . esc_html__('Bedrag (€)', 'bso-phoenix') . '</label></th>';
        echo '<td><input type="number" id="cost_amount" name="amount" min="0.01" step="0.01" required value="' . esc_attr(is_array($edit_cost) ? (string) $edit_cost['amount'] : '') . '" /></td></tr>';

        echo '<tr><th scope="row"><label for="cost_supplier">' . esc_html__('Leverancier', 'bso-phoenix') . '</label></th>';
        echo '<td><input type="text" id="cost_supplier" name="supplier" class="regular-text" value="' . esc_attr(is_array($edit_cost) ? (string) $edit_cost['supplier'] : '') . '" /></td></tr>';

        echo '<tr><th scope="row"><label for="cost_notes">' . esc_html__('Notities', 'bso-phoenix') . '</label></th>';
        echo '<td><textarea id="cost_notes" name="notes" rows="3" class="large-text">' . esc_textarea(is_array($edit_cost) ? (string) $edit_cost['notes'] : '') . '</textarea></td></tr>';
        echo '</table>';

        submit_button(is_array($edit_cost) ? __('Bijwerken', 'bso-phoenix') : __('Opslaan', 'bso-phoenix'));
        if (is_array($edit_cost)) {
            echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=bso-phoenix-costs')) . '">' . esc_html__('Annuleren', 'bso-phoenix') . '</a>';
        }
        echo '</form>';

        // Filters
        echo '<h2>' . esc_html__('Overzicht', 'bso-phoenix') . '</h2>';
        echo '<form method="get" action="" style="display:flex;gap:8px;align-items:end;margin:8px 0 12px;flex-wrap:wrap;">';
        echo '<input type="hidden" name="page" value="bso-phoenix-costs" />';
        foreach (array('date_from' => __('Vanaf', 'bso-phoenix'), 'date_to' => __('Tot en met', 'bso-phoenix')) as $field => $field_label) {
            echo '<label><span style="display:block;font-size:12px;color:#50575e;">' . esc_html($field_label) . '</span>';
            echo '<input type="date" name="' . esc_attr($field) . '" value="' . esc_attr($$field) . '" /></label>';
        }
        echo '<label><span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Type', 'bso-phoenix') . '</span>';
        echo '<select name="cost_type"><option value="">' . esc_html__('Alle', 'bso-phoenix') . '</option>';
        foreach (self::TYPE_LABELS as $val => $label) {
            echo '<option value="' . esc_attr($val) . '"' . selected($filter_type, $val, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label>';
        submit_button(__('Filter', 'bso-phoenix'), 'secondary', 'submit', false);
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=bso-phoenix-costs')) . '">' . esc_html__('Reset', 'bso-phoenix') . '</a>';
        echo '</form>';

        // Export
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="margin-bottom:14px;">';
        echo '<input type="hidden" name="action" value="bso_phoenix_export_costs_csv" />';
        echo '<input type="hidden" name="date_from" value="' . esc_attr($date_from) . '" />';
        echo '<input type="hidden" name="date_to" value="' . esc_attr($date_to) . '" />';
        echo '<input type="hidden" name="cost_type" value="' . esc_attr($filter_type) . '" />';
        wp_nonce_field('bso_phoenix_export_costs_csv', 'bso_phoenix_costs_export_nonce');
        submit_button(__('Exporteer naar CSV', 'bso-phoenix'), 'secondary', 'submit', false);
        echo '</form>';

        if (empty($costs)) {
            echo '<p>' . esc_html__('Geen kostenposten gevonden.', 'bso-phoenix') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Datum', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Type', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Bedrag', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Leverancier', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Notities', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Acties', 'bso-phoenix') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($costs as $cost) {
            $edit_url = admin_url('admin.php?page=bso-phoenix-costs&edit=' . (int) $cost['id']);
            $delete_url = wp_nonce_url(
                admin_url('admin-post.php?action=bso_phoenix_delete_cost&cost_id=' . (int) $cost['id']),
                'bso_phoenix_delete_cost_' . (int) $cost['id']
            );
            echo '<tr>';
            echo '<td>' . esc_html((string) $cost['cost_date']) . '</td>';
            echo '<td>' . esc_html(self::TYPE_LABELS[$cost['cost_type']] ?? (string) $cost['cost_type']) . '</td>';
            echo '<td>€ ' . esc_html(number_format_i18n((float) $cost['amount'], 2)) . '</td>';
            echo '<td>' . esc_html((string) $cost['supplier']) . '</td>';
            echo '<td>' . esc_html(wp_trim_words((string) $cost['notes'], 10)) . '</td>';
            echo '<td>';
            echo '<a class="button button-small" href="' . esc_url($edit_url) . '">' . esc_html__('Bewerken', 'bso-phoenix') . '</a> ';
            echo '<a class="button button-small button-link-delete" href="' . esc_url($delete_url) . '" onclick="return confirm(\'' . esc_js(__('Kostenpost verwijderen?', 'bso-phoenix')) . '\')">' . esc_html__('Verwijder', 'bso-phoenix') . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    public function handle_save_cost(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_save_cost', 'bso_phoenix_cost_nonce');

        $cost_type = sanitize_key((string) ($_POST['cost_type'] ?? 'other'));
        $amount = (float) str_replace(',', '.', (string) ($_POST['amount'] ?? '0'));
        $cost_date = $this->normalize_date(sanitize_text_field((string) ($_POST['cost_date'] ?? '')));
        $supplier = sanitize_text_field((string) ($_POST['supplier'] ?? ''));
        $notes = sanitize_textarea_field((string) ($_POST['notes'] ?? ''));

        if ($amount <= 0 || $cost_date === '') {
            wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-costs&error=invalid'));
            exit;
        }

        $service = new BSO_Phoenix_Cost_Service();
        $service->create_cost(1, $cost_type, $amount, $cost_date, 'EUR', $supplier, $notes, null);

        wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-costs&saved=1'));
        exit;
    }

    public function handle_update_cost(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        $cost_id = isset($_POST['cost_id']) ? (int) $_POST['cost_id'] : 0;
        if ($cost_id <= 0) {
            wp_die(esc_html__('Ongeldige cost_id.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_update_cost_' . $cost_id, 'bso_phoenix_cost_nonce');

        $cost_type = sanitize_key((string) ($_POST['cost_type'] ?? 'other'));
        $amount = (float) str_replace(',', '.', (string) ($_POST['amount'] ?? '0'));
        $cost_date = $this->normalize_date(sanitize_text_field((string) ($_POST['cost_date'] ?? '')));
        $supplier = sanitize_text_field((string) ($_POST['supplier'] ?? ''));
        $notes = sanitize_textarea_field((string) ($_POST['notes'] ?? ''));

        if ($amount <= 0 || $cost_date === '') {
            wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-costs&edit=' . $cost_id . '&error=invalid'));
            exit;
        }

        $service = new BSO_Phoenix_Cost_Service();
        $service->update_cost($cost_id, $cost_type, $amount, $cost_date, 'EUR', $supplier, $notes);

        wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-costs&saved=1'));
        exit;
    }

    public function handle_delete_cost(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        $cost_id = isset($_GET['cost_id']) ? (int) $_GET['cost_id'] : 0;
        if ($cost_id <= 0) {
            wp_die(esc_html__('Ongeldige cost_id.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_delete_cost_' . $cost_id);

        $service = new BSO_Phoenix_Cost_Service();
        $service->delete_cost($cost_id);

        wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-costs&deleted=1'));
        exit;
    }

    public function handle_export_costs_csv(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_export_costs_csv', 'bso_phoenix_costs_export_nonce');

        $date_from = $this->normalize_date(sanitize_text_field((string) ($_POST['date_from'] ?? '')));
        $date_to = $this->normalize_date(sanitize_text_field((string) ($_POST['date_to'] ?? '')));
        $cost_type = sanitize_key((string) ($_POST['cost_type'] ?? ''));

        $service = new BSO_Phoenix_Cost_Service();
        $costs = $service->get_costs($date_from, $date_to, $cost_type, 10000);

        $filename = 'phoenix-costs-' . gmdate('Ymd-His') . '.csv';

        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=' . $filename);

        $output = fopen('php://output', 'w');
        if ($output === false) {
            wp_die(esc_html__('Kon CSV-output niet openen.', 'bso-phoenix'));
        }

        fputcsv($output, array('id', 'cost_date', 'cost_type', 'amount', 'currency', 'supplier', 'notes'));

        foreach ($costs as $cost) {
            fputcsv($output, array(
                (string) $cost['id'],
                (string) $cost['cost_date'],
                (string) $cost['cost_type'],
                (string) $cost['amount'],
                (string) $cost['currency'],
                (string) $cost['supplier'],
                (string) $cost['notes'],
            ));
        }

        fclose($output);
        exit;
    }

    private function render_stat_card(string $label, string $value): void
    {
        echo '<div style="background:#fff;border:1px solid #dcdcde;border-radius:8px;padding:12px;">';
        echo '<div style="font-size:12px;color:#50575e;">' . esc_html($label) . '</div>';
        echo '<div style="font-size:20px;font-weight:600;line-height:1.3;">' . esc_html($value) . '</div>';
        echo '</div>';
    }

    private function normalize_date(string $value): string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        return $value;
    }
}
