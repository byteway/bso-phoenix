<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

require_once __DIR__ . '/includes/class-phoenix-db.php';

$settings_table = $wpdb->prefix . 'phoenix_settings';
$delete_data = '0';

if ($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s', $settings_table)) === $settings_table) {
    $stored_value = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT option_value FROM {$settings_table} WHERE option_key = %s LIMIT 1",
            'delete_data_on_uninstall'
        )
    );

    if ($stored_value !== null) {
        $delete_data = (string) $stored_value;
    }
}

if ($delete_data !== '1') {
    $delete_data = get_option('bso_phoenix_delete_data_on_uninstall', '0');
}

if ($delete_data === '1') {
    BSO_Phoenix_DB::drop_tables();
    delete_option('bso_phoenix_delete_data_on_uninstall');
}
