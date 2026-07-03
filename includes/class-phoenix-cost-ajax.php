<?php

if (! defined('ABSPATH')) {
    exit;
}

class BSO_Phoenix_Cost_Ajax
{
    public function init(): void
    {
        add_action('wp_ajax_bso_phoenix_create_cost', array($this, 'create_cost'));
    }

    public function create_cost(): void
    {
		$this->guard_request(BSO_PHOENIX_CAP_WRITE);

        if (BSO_Phoenix_Hardening::is_duplicate_submission('ajax_create_cost', array(
            'request_uid' => sanitize_text_field((string) ($_POST['request_uid'] ?? '')),
            'amount' => sanitize_text_field((string) ($_POST['amount'] ?? '')),
            'cost_date' => sanitize_text_field((string) ($_POST['cost_date'] ?? '')),
            'cost_type' => sanitize_key((string) ($_POST['cost_type'] ?? 'other')),
        ), 20)) {
            wp_send_json_error(array('message' => 'Dubbele kostenaanvraag gedetecteerd. Controleer of de post al is opgeslagen.'), 409);
        }

        $cost_type = isset($_POST['cost_type']) ? sanitize_key((string) $_POST['cost_type']) : 'other';
        $amount_raw = isset($_POST['amount']) ? (string) $_POST['amount'] : '';
        $cost_date = isset($_POST['cost_date']) ? sanitize_text_field((string) $_POST['cost_date']) : '';
        $supplier = isset($_POST['supplier']) ? sanitize_text_field((string) $_POST['supplier']) : '';
        $notes = isset($_POST['notes']) ? sanitize_textarea_field((string) $_POST['notes']) : '';
        $boat_id = isset($_POST['boat_id']) ? (int) $_POST['boat_id'] : 1;
        $trip_id = isset($_POST['trip_id']) && (int) $_POST['trip_id'] > 0 ? (int) $_POST['trip_id'] : null;

        $amount = (float) str_replace(',', '.', $amount_raw);
        if ($amount <= 0) {
            wp_send_json_error(array('message' => 'Bedrag moet groter dan 0 zijn.'), 400);
        }

        $cost_date = BSO_Phoenix_Hardening::normalize_date($cost_date);
        if ($cost_date === '') {
            wp_send_json_error(array('message' => 'Ongeldige datum. Gebruik een bestaande datum binnen de toegestane range.'), 400);
        }

        $service = new BSO_Phoenix_Cost_Service();
        $currency = (new BSO_Phoenix_Settings_Service())->get_currency_code();
        $cost_id = $service->create_cost($boat_id, $cost_type, $amount, $cost_date, $currency, $supplier, $notes, $trip_id);

        if ($cost_id <= 0) {
            wp_send_json_error(array('message' => 'Kon kostenpost niet opslaan.'), 500);
        }

        wp_send_json_success(array('cost_id' => $cost_id));
    }

	private function guard_request(string $required_cap): void
    {
        if (! is_user_logged_in()) {
            wp_send_json_error(array('message' => 'Inloggen vereist.'), 401);
        }

		if (! current_user_can($required_cap)) {
			wp_send_json_error(array('message' => 'Onvoldoende rechten.'), 403);
		}

        $nonce = isset($_POST['nonce']) ? sanitize_text_field((string) $_POST['nonce']) : '';
        if (! wp_verify_nonce($nonce, 'bso_phoenix_cost')) {
            wp_send_json_error(array('message' => 'Ongeldige nonce.'), 403);
        }
    }
}
