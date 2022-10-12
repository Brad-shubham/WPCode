<?php

namespace Genpak\Plugins\Migration;

/**
 * Plugin Name: Genpak Migration Plugin
 * Description: Plugin that defines commands to migrate data to Genpak WooCommerce.
 * Version: 1.0
 * Author: IT Hands
 **/

if (!defined('ABSPATH')) die;

define('GPM_PLUGIN_VERSION', '1.0.0');
define('GPM_BASE_FILE', __FILE__);

if (!file_exists(__DIR__ . '/vendor/autoload.php')) {
    wp_die("You need to run <code>composer install</code> inside - \n" . __DIR__);
}

require __DIR__ . '/vendor/autoload.php';

if (!file_exists(__DIR__ . '/config.php')) {
    wp_die("Create a config.php inside - \n" . __DIR__);
}

require __DIR__ . '/config.php';


if (defined('WP_CLI') && WP_CLI) {

    require __DIR__ . '/inc/base-class.php';
    require __DIR__ . '/inc/migrate-product-media.php';
    require __DIR__ . '/inc/migrate-categories.php';
    require __DIR__ . '/inc/migrate-products.php';
    require __DIR__ . '/inc/migrate-product-cross-sells.php';
    require __DIR__ . '/inc/link-product-media.php';
    require __DIR__ . '/inc/migrate-administrators.php';
    require __DIR__ . '/inc/migrate-customers.php';
    require __DIR__ . '/inc/migrate-users.php';
    require __DIR__ . '/inc/migrate-orders.php';

    require __DIR__ . '/inc/delete-categories.php';
    require __DIR__ . '/inc/delete-products.php';
    require __DIR__ . '/inc/delete-administrators.php';
    require __DIR__ . '/inc/delete-customers.php';
    require __DIR__ . '/inc/delete-orders.php';

    \WP_CLI::add_command('migrate:categories', __NAMESPACE__ . '\MigrateCategories');
    \WP_CLI::add_command('migrate:products', __NAMESPACE__ . '\MigrateProducts');
    \WP_CLI::add_command('migrate:product-cross-sells', __NAMESPACE__ . '\MigrateProductCrossSells');
    \WP_CLI::add_command('migrate:product-media', __NAMESPACE__ . '\MigrateProductMedia');
    \WP_CLI::add_command('link:product-media', __NAMESPACE__ . '\LinkProductMedia');
    \WP_CLI::add_command('migrate:administrators', __NAMESPACE__ . '\MigrateAdministrators');
    \WP_CLI::add_command('migrate:customers', __NAMESPACE__ . '\MigrateCustomers');
    \WP_CLI::add_command('migrate:users', __NAMESPACE__ . '\MigrateUsers');
    \WP_CLI::add_command('migrate:orders', __NAMESPACE__ . '\MigrateOrders');

    \WP_CLI::add_command('delete:products', __NAMESPACE__ . '\DeleteProducts');
    \WP_CLI::add_command('delete:customers', __NAMESPACE__ . '\DeleteCustomers');
    \WP_CLI::add_command('delete:administrators', __NAMESPACE__ . '\DeleteAdministrators');
    \WP_CLI::add_command('delete:categories', __NAMESPACE__ . '\DeleteCategories');
    \WP_CLI::add_command('delete:orders', __NAMESPACE__ . '\DeleteOrders');
}

require __DIR__ . '/inc/class-admin.php';
require __DIR__ . '/inc/traits/loads-view.php';
require __DIR__ . '/inc/class-settings.php';

new Admin();
