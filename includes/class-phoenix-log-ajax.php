<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Log_Ajax
{
    public function init(): void
    {
        add_action('wp_ajax_bso_phoenix_create_log', array($this, 'create_log'));
        add_action('wp_ajax_bso_phoenix_delete_log', array($this, 'delete_log'));
    }

    public function create_log(): void
    {
        $this->guard_request('bso_phoenix_log');

        $entry_text = isset($_POST['entry_text']) ? wp_kses_post((string) $_POST['entry_text']) : '';
        $boat_id = isset($_POST['boat_id']) ? (int) $_POST['boat_id'] : 1;
        $trip_id = isset($_POST['trip_id']) && (int) $_POST['trip_id'] > 0 ? (int) $_POST['trip_id'] : null;
        $log_date = isset($_POST['log_date']) ? sanitize_text_field((string) $_POST['log_date']) : '';
        $log_time = isset($_POST['log_time']) ? sanitize_text_field((string) $_POST['log_time']) : '';

        if (trim($entry_text) === '') {
            wp_send_json_error(array('message' => 'Notitietekst is verplicht.'), 400);
        }

        $log_date = $this->normalize_date($log_date);
        $log_time = $this->normalize_time($log_time);

        $service = new BSO_Phoenix_Log_Service();
        $log_id = $service->create_log($boat_id, $entry_text, $trip_id, $log_date, $log_time);

        if ($log_id <= 0) {
            wp_send_json_error(array('message' => 'Kon logboek niet opslaan.'), 500);
        }

        $attachment_ids = array();
        if (isset($_FILES['log_photos'])) {
            $attachment_ids = $service->store_uploaded_photos($log_id, $_FILES['log_photos']);
        }

        wp_send_json_success(array('log_id' => $log_id, 'attachment_ids' => $attachment_ids));
    }

    public function delete_log(): void
    {
        $this->guard_request('bso_phoenix_log');

        $log_id = isset($_POST['log_id']) ? (int) $_POST['log_id'] : 0;
        if ($log_id <= 0) {
            wp_send_json_error(array('message' => 'Ongeldige log_id.'), 400);
        }

        $service = new BSO_Phoenix_Log_Service();
        $deleted = $service->delete_log($log_id);

        if (! $deleted) {
            wp_send_json_error(array('message' => 'Kon logboekitem niet verwijderen.'), 500);
        }

        wp_send_json_success(array('deleted' => true));
    }

    private function guard_request(string $nonce_action): void
    {
        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Inloggen vereist.'), 401);
        }

        $nonce = isset($_POST['nonce']) ? sanitize_text_field((string) $_POST['nonce']) : '';
        if (! wp_verify_nonce($nonce, $nonce_action)) {
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

    private function normalize_time(string $value): ?string
    {
        if (! preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $value)) {
            return null;
        }

        return substr_count($value, ':') === 1 ? $value . ':00' : $value;
    }
}
