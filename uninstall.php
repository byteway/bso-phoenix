<?php

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once __DIR__ . '/includes/class-phoenix-db.php';

$delete_data = get_option('bso_phoenix_delete_data_on_uninstall', '0');
if ($delete_data === '1') {
    BSO_Phoenix_DB::drop_tables();
    delete_option('bso_phoenix_delete_data_on_uninstall');
}
