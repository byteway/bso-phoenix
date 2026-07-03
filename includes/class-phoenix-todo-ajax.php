<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Todo_Ajax
{
    public function init(): void
    {
        add_action('wp_ajax_bso_phoenix_create_todo', array($this, 'create_todo'));
        add_action('wp_ajax_bso_phoenix_update_todo_status', array($this, 'update_status'));
        add_action('wp_ajax_bso_phoenix_get_todos', array($this, 'get_todos'));
    }

    public function create_todo(): void
    {
        $this->guard_request();

        $title = isset($_POST['title']) ? sanitize_text_field((string) $_POST['title']) : '';
        $description = isset($_POST['description']) ? sanitize_textarea_field((string) $_POST['description']) : '';
        $priority = isset($_POST['priority']) ? sanitize_key((string) $_POST['priority']) : 'normal';
        $due_date = isset($_POST['due_date']) ? sanitize_text_field((string) $_POST['due_date']) : '';
        $boat_id = isset($_POST['boat_id']) ? (int) $_POST['boat_id'] : 1;

        if (trim($title) === '') {
            wp_send_json_error(array('message' => 'Titel is verplicht.'), 400);
        }

        $due_date = $this->normalize_date($due_date);

        $service = new BSO_Phoenix_Todo_Service();
        $todo_id = $service->create_todo($boat_id, $title, $description, $priority, $due_date);

        if ($todo_id <= 0) {
            wp_send_json_error(array('message' => 'Kon taak niet aanmaken.'), 500);
        }

        wp_send_json_success(array('todo_id' => $todo_id));
    }

    public function update_status(): void
    {
        $this->guard_request();

        $todo_id = isset($_POST['todo_id']) ? (int) $_POST['todo_id'] : 0;
        $status = isset($_POST['status']) ? sanitize_key((string) $_POST['status']) : '';

        if ($todo_id <= 0 || $status === '') {
            wp_send_json_error(array('message' => 'todo_id en status zijn verplicht.'), 400);
        }

        $service = new BSO_Phoenix_Todo_Service();
        $updated = $service->update_status($todo_id, $status);

        if (! $updated) {
            wp_send_json_error(array('message' => 'Kon status niet bijwerken.'), 500);
        }

        wp_send_json_success(array('updated' => true));
    }

    public function get_todos(): void
    {
        $this->guard_request();

        $status = isset($_POST['status']) ? sanitize_key((string) $_POST['status']) : '';
        $priority = isset($_POST['priority']) ? sanitize_key((string) $_POST['priority']) : '';

        $service = new BSO_Phoenix_Todo_Service();
        $todos = $service->get_todos($status, $priority, 100);

        wp_send_json_success(array('todos' => $todos));
    }

    private function guard_request(): void
    {
        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Inloggen vereist.'), 401);
        }

        $nonce = isset($_POST['nonce']) ? sanitize_text_field((string) $_POST['nonce']) : '';
        if (! wp_verify_nonce($nonce, 'bso_phoenix_todo')) {
            wp_send_json_error(array('message' => 'Ongeldige nonce.'), 403);
        }
    }

    private function normalize_date(string $value): ?string
    {
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        return $value;
    }
}
