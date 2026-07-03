<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Log_Admin
{
    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_submenu'));
        add_action('admin_post_bso_phoenix_save_log', array($this, 'handle_save_log'));
        add_action('admin_post_bso_phoenix_delete_log', array($this, 'handle_delete_log'));
    }

    public function register_submenu(): void
    {
        add_submenu_page(
            'bso-phoenix',
            __("Captain's log", 'bso-phoenix'),
            __("Captain's log", 'bso-phoenix'),
            'manage_options',
            'bso-phoenix-log',
            array($this, 'render_page')
        );
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'bso-phoenix'));
        }

        $date_from = isset($_GET['date_from']) ? sanitize_text_field((string) $_GET['date_from']) : '';
        $date_to = isset($_GET['date_to']) ? sanitize_text_field((string) $_GET['date_to']) : '';
        $date_from = $this->normalize_date($date_from);
        $date_to = $this->normalize_date($date_to);

        $service = new BSO_Phoenix_Log_Service();
        $logs = $service->get_logs($date_from, $date_to, 50);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__("Captain's log", 'bso-phoenix') . '</h1>';

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Logboekitem opgeslagen.', 'bso-phoenix') . '</p></div>';
        }

        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Logboekitem verwijderd.', 'bso-phoenix') . '</p></div>';
        }

        echo '<h2>' . esc_html__('Nieuw logboekitem', 'bso-phoenix') . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" enctype="multipart/form-data">';
        echo '<input type="hidden" name="action" value="bso_phoenix_save_log" />';
        wp_nonce_field('bso_phoenix_save_log', 'bso_phoenix_log_nonce');

        echo '<table class="form-table" role="presentation">';
        echo '<tr>';
        echo '<th scope="row"><label for="log_date">' . esc_html__('Datum', 'bso-phoenix') . '</label></th>';
        echo '<td><input type="date" id="log_date" name="log_date" value="' . esc_attr(current_time('Y-m-d')) . '" required /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="log_time">' . esc_html__('Tijd', 'bso-phoenix') . '</label></th>';
        echo '<td><input type="time" id="log_time" name="log_time" value="' . esc_attr(current_time('H:i')) . '" required /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="entry_text">' . esc_html__('Notitie', 'bso-phoenix') . '</label></th>';
        echo '<td><textarea id="entry_text" name="entry_text" rows="6" class="large-text" required placeholder="' . esc_attr__('Beschrijf de dag, weersomstandigheden, bijzonderheden...', 'bso-phoenix') . '"></textarea></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="log_photos">' . esc_html__('Foto\'s', 'bso-phoenix') . '</label></th>';
        echo '<td><input type="file" id="log_photos" name="log_photos[]" accept="image/*" multiple />';
        echo '<p class="description">' . esc_html__('Upload een of meerdere foto\'s bij dit logboekitem.', 'bso-phoenix') . '</p></td>';
        echo '</tr>';
        echo '</table>';

        submit_button(__('Opslaan', 'bso-phoenix'));
        echo '</form>';

        echo '<h2>' . esc_html__('Logboek', 'bso-phoenix') . '</h2>';

        echo '<form method="get" action="" style="display:flex;gap:8px;align-items:end;margin:8px 0 12px;flex-wrap:wrap;">';
        echo '<input type="hidden" name="page" value="bso-phoenix-log" />';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Vanaf', 'bso-phoenix') . '</span>';
        echo '<input type="date" name="date_from" value="' . esc_attr($date_from) . '" />';
        echo '</label>';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Tot en met', 'bso-phoenix') . '</span>';
        echo '<input type="date" name="date_to" value="' . esc_attr($date_to) . '" />';
        echo '</label>';
        submit_button(__('Filter', 'bso-phoenix'), 'secondary', 'submit', false);
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=bso-phoenix-log')) . '">' . esc_html__('Reset', 'bso-phoenix') . '</a>';
        echo '</form>';

        if (empty($logs)) {
            echo '<p>' . esc_html__('Geen logboekitems gevonden.', 'bso-phoenix') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Datum', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Tijd', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Notitie', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Trip', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Foto\'s', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Actie', 'bso-phoenix') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($logs as $log) {
            $photos = $service->get_log_photos((int) $log['id']);
            $delete_url = wp_nonce_url(
                admin_url('admin-post.php?action=bso_phoenix_delete_log&log_id=' . (int) $log['id']),
                'bso_phoenix_delete_log_' . (int) $log['id']
            );

            echo '<tr>';
            echo '<td>' . esc_html((string) $log['log_date']) . '</td>';
            echo '<td>' . esc_html(substr((string) $log['log_time'], 0, 5)) . '</td>';
            echo '<td>' . nl2br(esc_html(wp_trim_words((string) $log['entry_text'], 20))) . '</td>';
            echo '<td>' . (! empty($log['trip_id']) ? '#' . esc_html((string) $log['trip_id']) : '-') . '</td>';
            echo '<td>' . $this->render_photo_previews($photos) . '</td>';
            echo '<td><a class="button button-small button-link-delete" href="' . esc_url($delete_url) . '" onclick="return confirm(\'' . esc_js(__('Logboekitem verwijderen?', 'bso-phoenix')) . '\')">' . esc_html__('Verwijder', 'bso-phoenix') . '</a></td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    public function handle_save_log(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_save_log', 'bso_phoenix_log_nonce');

        $entry_text = isset($_POST['entry_text']) ? wp_kses_post((string) $_POST['entry_text']) : '';
        $log_date = isset($_POST['log_date']) ? sanitize_text_field((string) $_POST['log_date']) : '';
        $log_time = isset($_POST['log_time']) ? sanitize_text_field((string) $_POST['log_time']) : '';

        if (trim($entry_text) === '') {
            wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-log&error=empty'));
            exit;
        }

        $log_date = $this->normalize_date($log_date);
        $log_time = $log_time !== '' ? sanitize_text_field($log_time) : null;

        $service = new BSO_Phoenix_Log_Service();
        $log_id = $service->create_log(1, $entry_text, null, $log_date, $log_time);
        if ($log_id > 0 && isset($_FILES['log_photos'])) {
            $service->store_uploaded_photos($log_id, $_FILES['log_photos']);
        }

        wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-log&saved=1'));
        exit;
    }

    public function handle_delete_log(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        $log_id = isset($_GET['log_id']) ? (int) $_GET['log_id'] : 0;
        if ($log_id <= 0) {
            wp_die(esc_html__('Ongeldige log_id.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_delete_log_' . $log_id);

        $service = new BSO_Phoenix_Log_Service();
        $service->delete_log($log_id);

        wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-log&deleted=1'));
        exit;
    }

    private function normalize_date(string $value): string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return '';
        }

        return $value;
    }

    private function render_photo_previews(array $photos): string
    {
        if (empty($photos)) {
            return '-';
        }

        $html = '<div style="display:flex;gap:6px;flex-wrap:wrap;">';
        foreach ($photos as $photo) {
            $thumb = wp_get_attachment_image((int) $photo['attachment_id'], array(48, 48), false, array('style' => 'border-radius:6px;display:block;'));
            if ($thumb) {
                $html .= $thumb;
            }
        }
        $html .= '</div>';

        return $html;
    }
}
