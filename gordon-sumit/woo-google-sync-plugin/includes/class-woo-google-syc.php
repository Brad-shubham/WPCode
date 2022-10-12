<?php

/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       #
 * @since      1.0.0
 *
 * @package    Woo_Google_Syc
 * @subpackage Woo_Google_Syc/includes
 */

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Woo_Google_Syc
 * @subpackage Woo_Google_Syc/includes
 * @author     Gordon Sumit <gordon.sumit@ithands.com>
 */
class Woo_Google_Syc
{

    /**
     * The loader that's responsible for maintaining and registering all hooks that power
     * the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      Woo_Google_Syc_Loader $loader Maintains and registers all hooks for the plugin.
     */
    protected $loader;

    /**
     * The unique identifier of this plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $plugin_name The string used to uniquely identify this plugin.
     */
    protected $plugin_name;

    /**
     * The current version of the plugin.
     *
     * @since    1.0.0
     * @access   protected
     * @var      string $version The current version of the plugin.
     */
    protected $version;

    /**
     * Define the core functionality of the plugin.
     *
     * Set the plugin name and the plugin version that can be used throughout the plugin.
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
     *
     * @since    1.0.0
     */
    public function __construct()
    {
        if (defined('WOO_GOOGLE_SYC_VERSION')) {
            $this->version = WOO_GOOGLE_SYC_VERSION;
        } else {
            $this->version = '1.0.0';
        }
        $this->plugin_name = 'woo-google-syc';

        $this->load_dependencies();
        $this->set_locale();
        $this->define_admin_hooks();

    }

    /**
     * Load the required dependencies for this plugin.
     *
     * Include the following files that make up the plugin:
     *
     * - Woo_Google_Syc_Loader. Orchestrates the hooks of the plugin.
     * - Woo_Google_Syc_i18n. Defines internationalization functionality.
     * - Woo_Google_Syc_Admin. Defines all hooks for the admin area.
     * - Woo_Google_Syc_Public. Defines all hooks for the public side of the site.
     *
     * Create an instance of the loader which will be used to register the hooks
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function load_dependencies()
    {

        /**
         * The class responsible for orchestrating the actions and filters of the
         * core plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-google-syc-loader.php';

        /**
         * The class responsible for defining internationalization functionality
         * of the plugin.
         */
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-woo-google-syc-i18n.php';

      /**
       * The class responsible for defining all actions that occur in the admin area.
       */
      require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-woo-google-syc-admin.php';

      /**
       * The file responsible for defining all helper function that occur in the admin area.
       */
      require_once plugin_dir_path(dirname(__FILE__)) . '/includes/helper-function.php';


        $this->loader = new Woo_Google_Syc_Loader();

    }

    /**
     * Define the locale for this plugin for internationalization.
     *
     * Uses the Woo_Google_Syc_i18n class in order to set the domain and to register the hook
     * with WordPress.
     *
     * @since    1.0.0
     * @access   private
     */
    private function set_locale()
    {

        $plugin_i18n = new Woo_Google_Syc_i18n();

        $this->loader->add_action('plugins_loaded', $plugin_i18n, 'load_plugin_textdomain');

    }

    /**
     * Register all of the hooks related to the admin area functionality
     * of the plugin.
     *
     * @since    1.0.0
     * @access   private
     */
    private function define_admin_hooks()
    {

        $plugin_admin = new Woo_Google_Syc_Admin($this->get_plugin_name(), $this->get_version());

        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts');

        $this->loader->add_filter('woocommerce_settings_tabs_array', $plugin_admin, 'add_settings_tab', 50);

        $this->loader->add_action('woocommerce_settings_tabs_woo_google_sync', $plugin_admin, 'settings_tab');
        $this->loader->add_action('woocommerce_update_options_woo_google_sync', $plugin_admin, 'update_settings');

        $this->loader->add_action('rest_api_init', $plugin_admin, 'wooGSRest');
        $this->loader->add_action('wp_ajax_syncAction', $plugin_admin, 'syncAction');
        $this->loader->add_action('wp_ajax_wooChunkAction', $plugin_admin, 'wooChunkAction');
        $this->loader->add_action('wp_ajax_nopriv_wooChunkAction', $plugin_admin, 'myMustLogin');
        $this->loader->add_action('wp_ajax_nopriv_syncAction', $plugin_admin, 'myMustLogin');

    }

    /**
     * Run the loader to execute all of the hooks with WordPress.
     *
     * @since    1.0.0
     */
    public function run()
    {
        $this->loader->run();
    }

    /**
     * The name of the plugin used to uniquely identify it within the context of
     * WordPress and to define internationalization functionality.
     *
     * @return    string    The name of the plugin.
     * @since     1.0.0
     */
    public function get_plugin_name()
    {
        return $this->plugin_name;
    }

    /**
     * The reference to the class that orchestrates the hooks with the plugin.
     *
     * @return    Woo_Google_Syc_Loader    Orchestrates the hooks of the plugin.
     * @since     1.0.0
     */
    public function get_loader()
    {
        return $this->loader;
    }

    /**
     * Retrieve the version number of the plugin.
     *
     * @return    string    The version number of the plugin.
     * @since     1.0.0
     */
    public function get_version()
    {
        return $this->version;
    }

}
