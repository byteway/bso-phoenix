<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Todo_Admin
{
    private const STATUSES = array(
        'open' => 'Open',
        'in_progress' => 'In behandeling',
        'done' => 'Afgerond',
    );

    private const PRIORITIES = array(
        'high' => 'Hoog',
        'normal' => 'Normaal',
        'low' => 'Laag',
    );

    public function init(): void
    {
        add_action('admin_menu', array($this, 'register_submenu'));
        add_action('admin_post_bso_phoenix_save_todo', array($this, 'handle_save_todo'));
        add_action('admin_post_bso_phoenix_update_todo', array($this, 'handle_update_todo'));
        add_action('admin_post_bso_phoenix_delete_todo', array($this, 'handle_delete_todo'));
    }

    public function register_submenu(): void
    {
        add_submenu_page(
            'bso-phoenix',
            __('TODO beheer', 'bso-phoenix'),
            __('TODO', 'bso-phoenix'),
            'manage_options',
            'bso-phoenix-todo',
            array($this, 'render_page')
        );
    }

    public function render_page(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Je hebt geen rechten om deze pagina te bekijken.', 'bso-phoenix'));
        }

        $filter_status = isset($_GET['status']) ? sanitize_key((string) $_GET['status']) : '';
        $filter_priority = isset($_GET['priority']) ? sanitize_key((string) $_GET['priority']) : '';
        if (! array_key_exists($filter_status, self::STATUSES)) {
            $filter_status = '';
        }
        if (! array_key_exists($filter_priority, self::PRIORITIES)) {
            $filter_priority = '';
        }

        $edit_id = isset($_GET['edit']) ? (int) $_GET['edit'] : 0;
        $service = new BSO_Phoenix_Todo_Service();
        $todos = $service->get_todos($filter_status, $filter_priority, 200);
        $edit_todo = $edit_id > 0 ? $service->get_todo_by_id($edit_id) : null;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('TODO beheer', 'bso-phoenix') . '</h1>';

        if (isset($_GET['saved'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Taak opgeslagen.', 'bso-phoenix') . '</p></div>';
        }
        if (isset($_GET['deleted'])) {
            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Taak verwijderd.', 'bso-phoenix') . '</p></div>';
        }

        $form_action = is_array($edit_todo) ? 'bso_phoenix_update_todo' : 'bso_phoenix_save_todo';
        $form_nonce = $form_action . (is_array($edit_todo) ? '_' . $edit_id : '');
        $form_title = is_array($edit_todo) ? __('Taak bewerken', 'bso-phoenix') : __('Nieuwe taak', 'bso-phoenix');

        echo '<h2>' . esc_html($form_title) . '</h2>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        echo '<input type="hidden" name="action" value="' . esc_attr($form_action) . '" />';
        if (is_array($edit_todo)) {
            echo '<input type="hidden" name="todo_id" value="' . esc_attr((string) $edit_todo['id']) . '" />';
        }
        wp_nonce_field($form_nonce, 'bso_phoenix_todo_nonce');

        echo '<table class="form-table" role="presentation">';
        echo '<tr>';
        echo '<th scope="row"><label for="todo_title">' . esc_html__('Titel', 'bso-phoenix') . '</label></th>';
        echo '<td><input type="text" id="todo_title" name="title" class="regular-text" required value="' . esc_attr(is_array($edit_todo) ? (string) $edit_todo['title'] : '') . '" /></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="todo_description">' . esc_html__('Omschrijving', 'bso-phoenix') . '</label></th>';
        echo '<td><textarea id="todo_description" name="description" rows="4" class="large-text">' . esc_textarea(is_array($edit_todo) ? (string) $edit_todo['description'] : '') . '</textarea></td>';
        echo '</tr>';
        echo '<tr>';
        echo '<th scope="row"><label for="todo_priority">' . esc_html__('Prioriteit', 'bso-phoenix') . '</label></th>';
        echo '<td><select id="todo_priority" name="priority">';
        foreach (self::PRIORITIES as $val => $label) {
            $sel = is_array($edit_todo) ? selected((string) $edit_todo['priority'], $val, false) : selected('normal', $val, false);
            echo '<option value="' . esc_attr($val) . '"' . $sel . '>' . esc_html($label) . '</option>';
        }
        echo '</select></td>';
        echo '</tr>';
        if (is_array($edit_todo)) {
            echo '<tr>';
            echo '<th scope="row"><label for="todo_status">' . esc_html__('Status', 'bso-phoenix') . '</label></th>';
            echo '<td><select id="todo_status" name="status">';
            foreach (self::STATUSES as $val => $label) {
                echo '<option value="' . esc_attr($val) . '"' . selected((string) $edit_todo['status'], $val, false) . '>' . esc_html($label) . '</option>';
            }
            echo '</select></td>';
            echo '</tr>';
        }
        echo '<tr>';
        echo '<th scope="row"><label for="todo_due_date">' . esc_html__('Deadline', 'bso-phoenix') . '</label></th>';
        echo '<td><input type="date" id="todo_due_date" name="due_date" value="' . esc_attr(is_array($edit_todo) ? (string) $edit_todo['due_date'] : '') . '" /></td>';
        echo '</tr>';
        echo '</table>';

        submit_button(is_array($edit_todo) ? __('Bijwerken', 'bso-phoenix') : __('Aanmaken', 'bso-phoenix'));
        if (is_array($edit_todo)) {
            echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=bso-phoenix-todo')) . '">' . esc_html__('Annuleren', 'bso-phoenix') . '</a>';
        }
        echo '</form>';

        echo '<h2>' . esc_html__('Taken', 'bso-phoenix') . '</h2>';
        echo '<form method="get" action="" style="display:flex;gap:8px;align-items:end;margin:8px 0 12px;flex-wrap:wrap;">';
        echo '<input type="hidden" name="page" value="bso-phoenix-todo" />';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Status', 'bso-phoenix') . '</span>';
        echo '<select name="status">';
        echo '<option value="">' . esc_html__('Alle', 'bso-phoenix') . '</option>';
        foreach (self::STATUSES as $val => $label) {
            echo '<option value="' . esc_attr($val) . '"' . selected($filter_status, $val, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label>';
        echo '<label>';
        echo '<span style="display:block;font-size:12px;color:#50575e;">' . esc_html__('Prioriteit', 'bso-phoenix') . '</span>';
        echo '<select name="priority">';
        echo '<option value="">' . esc_html__('Alle', 'bso-phoenix') . '</option>';
        foreach (self::PRIORITIES as $val => $label) {
            echo '<option value="' . esc_attr($val) . '"' . selected($filter_priority, $val, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label>';
        submit_button(__('Filter', 'bso-phoenix'), 'secondary', 'submit', false);
        echo '<a class="button" href="' . esc_url(admin_url('admin.php?page=bso-phoenix-todo')) . '">' . esc_html__('Reset', 'bso-phoenix') . '</a>';
        echo '</form>';

        if (empty($todos)) {
            echo '<p>' . esc_html__('Geen taken gevonden.', 'bso-phoenix') . '</p>';
            echo '</div>';
            return;
        }

        echo '<table class="widefat striped">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Prioriteit', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Titel', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Status', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Deadline', 'bso-phoenix') . '</th>';
        echo '<th>' . esc_html__('Acties', 'bso-phoenix') . '</th>';
        echo '</tr></thead><tbody>';

        foreach ($todos as $todo) {
            $edit_url = admin_url('admin.php?page=bso-phoenix-todo&edit=' . (int) $todo['id']);
            $delete_url = wp_nonce_url(
                admin_url('admin-post.php?action=bso_phoenix_delete_todo&todo_id=' . (int) $todo['id']),
                'bso_phoenix_delete_todo_' . (int) $todo['id']
            );
            $priority_label = self::PRIORITIES[$todo['priority']] ?? $todo['priority'];
            $status_label = self::STATUSES[$todo['status']] ?? $todo['status'];

            echo '<tr>';
            echo '<td>' . esc_html($priority_label) . '</td>';
            echo '<td>' . esc_html((string) $todo['title']) . '</td>';
            echo '<td>' . esc_html($status_label) . '</td>';
            echo '<td>' . esc_html(! empty($todo['due_date']) ? (string) $todo['due_date'] : '-') . '</td>';
            echo '<td>';
            echo '<a class="button button-small" href="' . esc_url($edit_url) . '">' . esc_html__('Bewerken', 'bso-phoenix') . '</a> ';
            echo '<a class="button button-small button-link-delete" href="' . esc_url($delete_url) . '" onclick="return confirm(\'' . esc_js(__('Taak verwijderen?', 'bso-phoenix')) . '\')">' . esc_html__('Verwijder', 'bso-phoenix') . '</a>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
        echo '</div>';
    }

    public function handle_save_todo(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_save_todo', 'bso_phoenix_todo_nonce');

        $title = isset($_POST['title']) ? sanitize_text_field((string) $_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field((string) $_POST['description']) : '';
        $priority = isset($_POST['priority']) ? sanitize_key((string) $_POST['priority']) : 'normal';
        $due_date = isset($_POST['due_date']) ? sanitize_text_field((string) $_POST['due_date']) : null;

        if (trim($title) === '') {
            wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-todo&error=empty'));
            exit;
        }

        $service = new BSO_Phoenix_Todo_Service();
        $service->create_todo(1, $title, $description, $priority, $due_date ?: null);

        wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-todo&saved=1'));
        exit;
    }

    public function handle_update_todo(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        $todo_id = isset($_POST['todo_id']) ? (int) $_POST['todo_id'] : 0;
        if ($todo_id <= 0) {
            wp_die(esc_html__('Ongeldige todo_id.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_update_todo_' . $todo_id, 'bso_phoenix_todo_nonce');

        $title = isset($_POST['title']) ? sanitize_text_field((string) $_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field((string) $_POST['description']) : '';
        $priority = isset($_POST['priority']) ? sanitize_key((string) $_POST['priority']) : 'normal';
        $status = isset($_POST['status']) ? sanitize_key((string) $_POST['status']) : 'open';
        $due_date = isset($_POST['due_date']) ? sanitize_text_field((string) $_POST['due_date']) : null;

        if (trim($title) === '') {
            wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-todo&edit=' . $todo_id . '&error=empty'));
            exit;
        }

        $service = new BSO_Phoenix_Todo_Service();
        $service->update_todo($todo_id, $title, $description, $priority, $status, $due_date ?: null);

        wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-todo&saved=1'));
        exit;
    }

    public function handle_delete_todo(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Geen rechten.', 'bso-phoenix'));
        }

        $todo_id = isset($_GET['todo_id']) ? (int) $_GET['todo_id'] : 0;
        if ($todo_id <= 0) {
            wp_die(esc_html__('Ongeldige todo_id.', 'bso-phoenix'));
        }

        check_admin_referer('bso_phoenix_delete_todo_' . $todo_id);

        $service = new BSO_Phoenix_Todo_Service();
        $service->delete_todo($todo_id);

        wp_safe_redirect(admin_url('admin.php?page=bso-phoenix-todo&deleted=1'));
        exit;
    }
}
