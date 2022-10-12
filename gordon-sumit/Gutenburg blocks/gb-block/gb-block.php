<?php
/**
 * Plugin Name: GB Block
 */

define('GTCB_BLOCKS_PATH', untrailingslashit(plugin_dir_path(__FILE__)));
define('GTCB_BLOCKS_URL', untrailingslashit(plugin_dir_url(__FILE__)));
function gutenberg_examples_01_register_block()
{
    wp_register_script(
        'gb-block-editor-js',
        GTCB_BLOCKS_URL . '/build/index.js',
        array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components', 'wp-i18n'),
        filemtime(GTCB_BLOCKS_PATH . '/build/index.js'),
        true
    );
    // Block front end styles.
    wp_register_style(
        'gb-block-front-end-styles',
        GTCB_BLOCKS_URL . '/src/style.css',
        [],
        filemtime(GTCB_BLOCKS_PATH . '/src/style.css')
    );

// Block editor styles.
    wp_register_style(
        'gb-block-editor-styles',
        GTCB_BLOCKS_URL . '/src/editor.css',
        ['wp-edit-blocks'],
        filemtime(GTCB_BLOCKS_PATH . '/src/editor.css')
    );

    register_block_type(__DIR__, array(
        'editor_script' => 'gb-block-editor-js',
        'style' => 'gb-block-front-end-styles',
        'editor_style' => 'gb-block-editor-styles',
    ));

}

add_action('init', 'gutenberg_examples_01_register_block');