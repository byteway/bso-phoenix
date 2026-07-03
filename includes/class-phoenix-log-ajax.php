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
        add_action('wp_ajax_bso_phoenix_get_logs', array($this, 'get_logs'));
    }

    public function create_log(): void
    {
		$this->guard_request('bso_phoenix_log', BSO_PHOENIX_CAP_WRITE);

        if (BSO_Phoenix_Hardening::is_duplicate_submission('ajax_create_log', array(
            'request_uid' => sanitize_text_field((string) ($_POST['request_uid'] ?? '')),
            'entry_text' => sanitize_text_field((string) ($_POST['entry_text'] ?? '')),
            'log_date' => sanitize_text_field((string) ($_POST['log_date'] ?? '')),
            'log_time' => sanitize_text_field((string) ($_POST['log_time'] ?? '')),
        ), 20)) {
            wp_send_json_error(array('message' => 'Dubbele logboekaanvraag gedetecteerd. Controleer of het item al is opgeslagen.'), 409);
        }

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

        if ($log_date === null) {
            wp_send_json_error(array('message' => 'Ongeldige logdatum. Gebruik een bestaande datum binnen de toegestane range.'), 400);
        }
        if ($log_time !== null && $log_time === '') {
            wp_send_json_error(array('message' => 'Ongeldige logtijd. Gebruik HH:MM of HH:MM:SS.'), 400);
        }

        $service = new BSO_Phoenix_Log_Service();
        $log_id = $service->create_log($boat_id, $entry_text, $trip_id, $log_date, $log_time);

        if ($log_id <= 0) {
            wp_send_json_error(array('message' => 'Kon logboek niet opslaan.'), 500);
        }

        $attachment_ids = array();
        $photo_captions = array();

        if (isset($_POST['log_photo_captions']) && is_array($_POST['log_photo_captions'])) {
            $photo_captions = array_map(
                static function ($value): string {
                    return sanitize_text_field((string) $value);
                },
                wp_unslash($_POST['log_photo_captions'])
            );
        }

        if (isset($_FILES['log_photos'])) {
            $attachment_ids = $service->store_uploaded_photos($log_id, $_FILES['log_photos'], $photo_captions);
        }

        wp_send_json_success(
            array(
                'log_id' => $log_id,
                'attachment_ids' => $attachment_ids,
                'photos' => $service->get_log_photos($log_id),
            )
        );
    }

    public function delete_log(): void
    {
		$this->guard_request('bso_phoenix_log', BSO_PHOENIX_CAP_WRITE);

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

    public function get_logs(): void
    {
		$this->guard_request('bso_phoenix_log', BSO_PHOENIX_CAP_READ);

        $limit = isset($_POST['limit']) ? (int) $_POST['limit'] : 20;
        $service = new BSO_Phoenix_Log_Service();
        $logs = $service->get_logs('', '', max(1, min(100, $limit)));

        $logs_with_photos = array_map(
            static function (array $log) use ($service): array {
                $log['id'] = isset($log['id']) ? (int) $log['id'] : 0;
                $log['photos'] = $log['id'] > 0 ? $service->get_log_photos($log['id']) : array();
                return $log;
            },
            $logs
        );

        wp_send_json_success(array('logs' => $logs_with_photos));
    }

	private function guard_request(string $nonce_action, string $required_cap): void
    {
        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Inloggen vereist.'), 401);
        }

		if (! current_user_can($required_cap)) {
			wp_send_json_error(array('message' => 'Onvoldoende rechten.'), 403);
		}

        $nonce = isset($_POST['nonce']) ? sanitize_text_field((string) $_POST['nonce']) : '';
        if (! wp_verify_nonce($nonce, $nonce_action)) {
            wp_send_json_error(array('message' => 'Ongeldige nonce.'), 403);
        }
    }

    private function normalize_date(string $value): ?string
    {
        $normalized = BSO_Phoenix_Hardening::normalize_date($value);
        return $normalized !== '' ? $normalized : null;
    }

    private function normalize_time(string $value): ?string
    {
        if ($value === '') {
            return null;
        }

        $normalized = BSO_Phoenix_Hardening::normalize_time($value);
        return $normalized !== null ? $normalized : '';
    }
}
