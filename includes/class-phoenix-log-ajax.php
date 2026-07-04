<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Log_Ajax
{
    public function init(): void
    {
        add_action('wp_ajax_bso_phoenix_create_log', array($this, 'create_log'));
        add_action('wp_ajax_bso_phoenix_add_log_photos', array($this, 'add_log_photos'));
        add_action('wp_ajax_bso_phoenix_update_log_photo', array($this, 'update_log_photo'));
        add_action('wp_ajax_bso_phoenix_delete_log', array($this, 'delete_log'));
        add_action('wp_ajax_bso_phoenix_get_logs', array($this, 'get_logs'));
    }

    public function create_log(): void
    {
		$this->guard_request('bso_phoenix_log', BSO_PHOENIX_CAP_WRITE);

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

        $photo_names = array();
        if (isset($_FILES['log_photos']['name']) && is_array($_FILES['log_photos']['name'])) {
            $photo_names = array_map(
                static function ($name): string {
                    return sanitize_file_name((string) $name);
                },
                $_FILES['log_photos']['name']
            );
        }

        if (BSO_Phoenix_Hardening::is_duplicate_submission('ajax_create_log', array(
            'boat_id' => $boat_id,
            'trip_id' => $trip_id ?: 0,
            'entry_text' => sanitize_text_field((string) wp_strip_all_tags($entry_text)),
            'log_date' => $log_date,
            'log_time' => $log_time ?: '',
            'photo_names' => implode('|', $photo_names),
        ), 20)) {
            wp_send_json_error(array('message' => 'Dubbele logboekaanvraag gedetecteerd. Controleer of het item al is opgeslagen.'), 409);
        }

        $service = new BSO_Phoenix_Log_Service();

        $recent_duplicate_id = $service->find_recent_duplicate_log_id($boat_id, $trip_id, $entry_text, $log_date, 30);
        if ($recent_duplicate_id > 0) {
            wp_send_json_error(array('message' => 'Dubbele logboekaanvraag gedetecteerd. Controleer of het item al is opgeslagen.'), 409);
        }

        $log_id = $service->create_log($boat_id, $entry_text, $trip_id, $log_date, $log_time);

        if ($log_id <= 0) {
            wp_send_json_error(array('message' => 'Kon logboek niet opslaan.'), 500);
        }

        // Extra race-condition guard: if an identical row already exists, keep the first and drop this duplicate.
        $post_insert_duplicate_id = $service->find_recent_duplicate_log_id($boat_id, $trip_id, $entry_text, $log_date, 30, $log_id);
        if ($post_insert_duplicate_id > 0) {
            $service->delete_log($log_id);
            wp_send_json_error(array('message' => 'Dubbele logboekaanvraag gedetecteerd. Controleer of het item al is opgeslagen.'), 409);
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

    public function add_log_photos(): void
    {
		$this->guard_request('bso_phoenix_log', BSO_PHOENIX_CAP_WRITE);

        $log_id = isset($_POST['log_id']) ? (int) $_POST['log_id'] : 0;
        if ($log_id <= 0) {
            wp_send_json_error(array('message' => 'Ongeldige log_id.'), 400);
        }

        if (! isset($_FILES['log_photos'])) {
            wp_send_json_error(array('message' => 'Geen foto\'s aangeleverd.'), 400);
        }

        $photo_captions = array();
        if (isset($_POST['log_photo_captions']) && is_array($_POST['log_photo_captions'])) {
            $photo_captions = array_map(
                static function ($value): string {
                    return sanitize_text_field((string) $value);
                },
                wp_unslash($_POST['log_photo_captions'])
            );
        }

        $service = new BSO_Phoenix_Log_Service();
        $attachment_ids = $service->store_uploaded_photos($log_id, $_FILES['log_photos'], $photo_captions);

        if (empty($attachment_ids)) {
            wp_send_json_error(array('message' => 'Kon logfoto\'s niet opslaan.'), 500);
        }

        wp_send_json_success(
            array(
                'log_id' => $log_id,
                'attachment_ids' => $attachment_ids,
                'photos' => $service->get_log_photos($log_id),
            )
        );
    }

    public function update_log_photo(): void
    {
		$this->guard_request('bso_phoenix_log', BSO_PHOENIX_CAP_WRITE);

        $photo_id = isset($_POST['photo_id']) ? (int) $_POST['photo_id'] : 0;
        if ($photo_id <= 0) {
            wp_send_json_error(array('message' => 'Ongeldige photo_id.'), 400);
        }

        $caption = isset($_POST['caption']) ? sanitize_text_field((string) $_POST['caption']) : '';
        $sort_order = isset($_POST['sort_order']) && (int) $_POST['sort_order'] > 0 ? (int) $_POST['sort_order'] : null;

        $service = new BSO_Phoenix_Log_Service();
        $updated = $service->update_photo_details($photo_id, $caption, $sort_order);
        if (! $updated) {
            wp_send_json_error(array('message' => 'Kon logfoto niet bijwerken.'), 500);
        }

        $photo = $service->get_photo_by_id($photo_id);
        if (! is_array($photo) || empty($photo['log_id'])) {
            wp_send_json_success(array('updated' => true));
        }

        wp_send_json_success(
            array(
                'updated' => true,
                'photo_id' => $photo_id,
                'log_id' => (int) $photo['log_id'],
                'photos' => $service->get_log_photos((int) $photo['log_id']),
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
