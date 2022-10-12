<?php

namespace Genpak\Plugins\Migration;


class Settings
{
    use LoadsView;

    /**
     * Constants
     */
    const PLUGIN_OPTION_GROUP = 'gpm_options';

    /**
     * Settings constructor.
     */
    public function __construct()
    {
        // Register setting
        add_action('admin_init', array($this, 'register_plugin_settings'));

        // To save default options upon activation
        register_activation_hook(plugin_basename(GPM_BASE_FILE), array($this, 'do_upon_plugin_activation'));

    }

    /**
     * Returns default plugin db options
     * @return array
     */
    public function get_default_options()
    {
        return array(
            'plugin_ver' => GPM_PLUGIN_VERSION,
            'network_key' => '',
        );
    }

    /**
     * Save default settings upon plugin activation
     */
    public function do_upon_plugin_activation()
    {

        if (false == get_option('gpm_options')) {
            add_option('gpm_options', $this->get_default_options());
        }
    }

    /**
     * Register plugin settings, using WP settings API
     */
    public function register_plugin_settings()
    {
        register_setting(self::PLUGIN_OPTION_GROUP, 'gpm_options', array($this, 'validate_form_post'));
    }

    /**
     * Load plugin option page view
     */
    public function load_page()
    {
        $this->loadView('settings', [
            'db' => get_option('gpm_options'),
            'option_group' => self::PLUGIN_OPTION_GROUP,
        ]);
    }

    /**
     * Validate form $_POST data
     * @param $in array
     * @return array Validated array
     */
    public function validate_form_post($in)
    {
        $out = array();
        $errors = array();
        //always store plugin version to db
        $out['plugin_ver'] = esc_attr(GPM_PLUGIN_VERSION);;

        if (!empty($in['driver'])) {
            $out['driver'] = sanitize_text_field($in['driver']);
        } else {
            $errors[] = 'Driver name is required.';
            $out['driver'] = '';
        }

        if (!empty($in['host'])) {
            $out['host'] = sanitize_text_field($in['host']);
        } else {
            $errors[] = 'Host name is required.';
            $out['host'] = '';
        }

        if (!empty($in['port'])) {
            $out['port'] = sanitize_text_field($in['port']);
        } else {
            $errors[] = 'Port is required.';
            $out['port'] = '';
        }

        if (!empty($in['database'])) {
            $out['database'] = sanitize_text_field($in['database']);
        } else {
            $errors[] = 'Database name is required.';
            $out['database'] = '';
        }

        if (!empty($in['username'])) {
            $out['username'] = sanitize_text_field($in['username']);
        } else {
            $errors[] = 'Database username name is required.';
            $out['username'] = '';
        }

        $out['password'] = sanitize_text_field($in['password']);

        // Show all form errors in a single notice
        if (!empty($errors)) {
            add_settings_error('gpm_options', 'gpm_error', implode('<br>', $errors));
        } else {
            add_settings_error('gpm_options', 'gpm_updated', 'Settings saved.', 'updated');
        }

        return $out;
    }
}
