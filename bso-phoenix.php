<?php
/**
 * Plugin Name: BSO Phoenix
 * Description: Logboek en route-app voor de motorboot Phoenix.
 * Version: 1.1.1
 * Author: Byteway Software Ontwikkeling
 * Text Domain: bso-phoenix
 * Domain Path: /languages
 */

if (! defined('ABSPATH')) {
    exit;
}

define('BSO_PHOENIX_VERSION', '1.1.1');
define('BSO_PHOENIX_FILE', __FILE__);
define('BSO_PHOENIX_DIR', plugin_dir_path(__FILE__));
define('BSO_PHOENIX_URL', plugin_dir_url(__FILE__));

require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-db.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-trip-service.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-log-service.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-todo-service.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-cost-service.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-boat-service.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-settings-service.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-access.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-ajax.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-log-ajax.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-todo-ajax.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-cost-ajax.php';
require_once BSO_PHOENIX_DIR . 'admin/class-phoenix-admin-page.php';
require_once BSO_PHOENIX_DIR . 'admin/class-phoenix-log-admin.php';
require_once BSO_PHOENIX_DIR . 'admin/class-phoenix-todo-admin.php';
require_once BSO_PHOENIX_DIR . 'admin/class-phoenix-cost-admin.php';
require_once BSO_PHOENIX_DIR . 'admin/class-phoenix-boat-admin.php';
require_once BSO_PHOENIX_DIR . 'admin/class-phoenix-settings-admin.php';
require_once BSO_PHOENIX_DIR . 'admin/class-phoenix-access-admin.php';
require_once BSO_PHOENIX_DIR . 'admin/class-phoenix-reports-admin.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-frontend.php';
require_once BSO_PHOENIX_DIR . 'includes/class-phoenix-plugin.php';

register_activation_hook(BSO_PHOENIX_FILE, array('BSO_Phoenix_DB', 'activate'));
register_activation_hook(BSO_PHOENIX_FILE, array('BSO_Phoenix_Access', 'activate'));

if (! defined('BSO_PHOENIX_CAP_READ')) {
    define('BSO_PHOENIX_CAP_READ', BSO_Phoenix_Access::CAP_READ);
}
if (! defined('BSO_PHOENIX_CAP_WRITE')) {
    define('BSO_PHOENIX_CAP_WRITE', BSO_Phoenix_Access::CAP_WRITE);
}
if (! defined('BSO_PHOENIX_CAP_MANAGE')) {
    define('BSO_PHOENIX_CAP_MANAGE', BSO_Phoenix_Access::CAP_MANAGE);
}

add_action('plugins_loaded', function () {
    load_plugin_textdomain('bso-phoenix', false, dirname(plugin_basename(BSO_PHOENIX_FILE)) . '/languages');

    BSO_Phoenix_Access::init();

    $plugin = new BSO_Phoenix_Plugin();
    $plugin->init();
});
