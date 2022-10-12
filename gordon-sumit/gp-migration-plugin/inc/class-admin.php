<?php

namespace Genpak\Plugins\Migration;


class Admin
{
    /**
     * Unique plugin option page slug
     */
    const PARENT_SLUG = 'gpm_options';

    /**
     * Settings class instance
     * @var Settings
     */
    private $settings;

    /**
     * Admin constructor.
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_links_to_sidebar'));
        $this->settings = new Settings();
    }

    /**
     * Register a page to display plugin options
     */
    public function add_links_to_sidebar()
    {
        $parent_hook_suffix = add_menu_page(
            'Migration Settings', // page title
            'Migration',// menu title
            'manage_options',
            self::PARENT_SLUG,
            array($this->settings, 'load_page'),
            'dashicons-randomize',
            80
        );
    }
}
