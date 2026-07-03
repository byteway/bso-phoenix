<?php
/**
 * Plugin Name: BSO Phoenix
 * Description: Logboek en route-app voor de motorboot Phoenix.
 * Version: 1.0.0
 * Author: Byteway Software Ontwikkeling
 * Text Domain: bso-phoenix
 * Domain Path: /languages
 */

if (! defined('ABSPATH')) {
    exit;
}

define('BSO_PHOENIX_VERSION', '1.0.0');
define('BSO_PHOENIX_FILE', __FILE__);
define('BSO_PHOENIX_DIR', plugin_dir_path(__FILE__));
define('BSO_PHOENIX_URL', plugin_dir_url(__FILE__));

require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-db.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-trip-service.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-log-service.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-todo-service.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-ajax.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-log-ajax.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-todo-ajax.php';
require_once BSO_PHOENIX_DIR . 'admin/class-phoenix-admin-page.php';
require_once BSO_PHOENIX_DIR . 'admin/class-phoenix-log-admin.php';
require_once BSO_PHOENIX_DIR . 'admin/class-phoenix-todo-admin.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-frontend.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-plugin.php';

register_activation_hook(BSO_PHOENIX_FILE, array('BSO_Phoenix_DB', 'activate'));

add_action('plugins_loaded', function () {
    load_plugin_textdomain('bso-phoenix', false, dirname(plugin_basename(BSO_PHOENIX_FILE)) . '/languages');

    $plugin = new BSO_Phoenix_Plugin();
    $plugin->init();
});
