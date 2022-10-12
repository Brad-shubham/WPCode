<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              #
 * @since             1.0.0
 * @package           Woo_Google_Syc
 *
 * @wordpress-plugin
 * Plugin Name:       Woo Google Sync
 * Plugin URI:        #
 * Description:       This is a short description of what the plugin does. It's displayed in the WordPress admin area.
 * Version:           1.0.0
 * Author:            Gordon Sumit
 * Author URI:        #
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       woo-google-syc
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define('WOO_GOOGLE_SYC_VERSION', '1.0.0');

define('WC_GOOGLE_SYC_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('WOO_GOOGLE_SYNC_AUTH_REDIRECT_URL', site_url() . '/wp-json/woogs/v1/auth');
define('WOO_GS_SHEET_RANGE', 'products');

define('WOOGS_SETTING_URL', admin_url('admin.php?page=wc-settings&tab=woo_google_sync'));
define('WOOGS_SETTING_PATH', '/wp-admin/admin.php?page=wc-settings&tab=woo_google_sync');

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-woo-google-syc-activator.php
 */
function activate_woo_google_syc()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-woo-google-syc-activator.php';
    Woo_Google_Syc_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-woo-google-syc-deactivator.php
 */
function deactivate_woo_google_syc()
{
    require_once plugin_dir_path(__FILE__) . 'includes/class-woo-google-syc-deactivator.php';
    Woo_Google_Syc_Deactivator::deactivate();
}

register_activation_hook(__FILE__, 'activate_woo_google_syc');
register_deactivation_hook(__FILE__, 'deactivate_woo_google_syc');

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path(__FILE__) . 'includes/class-woo-google-syc.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_woo_google_syc()
{

        $plugin = new Woo_Google_Syc();
        $plugin->run();

}

function isWooGsSettingPage()
{
    if ($_SERVER['REQUEST_URI'] == WOOGS_SETTING_PATH || $_POST) {
        return true;
    }
    if (!empty($_GET['code'])) {
        return true;
    }
}

run_woo_google_syc();
