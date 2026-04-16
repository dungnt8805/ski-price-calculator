<?php
/**
 * Plugin Name: Ski Price Calculator Ultimate
 * Description: Manage ski resort areas, hotels (bilingual), grades and pricing with weekday/date/range schedules and JPY/USD support. Frontend calculator shortcode included.
 * Version:     2.0
 * Author:      Dungnt
 * License:     GPLv2 or later
 * Text Domain: ski-price-calculator
 */

if (!defined('ABSPATH')) exit;

define('SPCU_PATH', plugin_dir_path(__FILE__));
define('SPCU_URL', plugin_dir_url(__FILE__));

require_once SPCU_PATH.'includes/class-spcu-activator.php';
require_once SPCU_PATH.'includes/class-spcu-database.php';
require_once SPCU_PATH.'includes/class-spcu-grades.php';
require_once SPCU_PATH.'admin/class-spcu-admin.php';
require_once SPCU_PATH.'includes/class-spcu-shortcode.php';
require_once SPCU_PATH.'includes/class-spcu-api.php';
require_once SPCU_PATH.'includes/class-spcu-frontend.php';

require_once SPCU_PATH.'admin/partials/spcu-admin-areas-post.php';
add_action('admin_init', 'spcu_handle_areas_post');

require_once SPCU_PATH.'admin/partials/spcu-admin-prices-post.php';
add_action('admin_init', 'spcu_handle_prices_post');
add_action('admin_init', 'spcu_handle_prices_delete');
require_once SPCU_PATH.'admin/partials/spcu-admin-hotels-post.php';
add_action('admin_init', 'spcu_handle_hotels_post');
add_action('admin_init', 'spcu_handle_hotels_delete');

// Register hotel post type for taxonomy support
add_action('init', function(){
    register_post_type('spcu_hotel', [
        'public' => false,
        'publicly_queryable' => false,
        'show_ui' => false,
        'show_in_menu' => false,
        'show_in_rest' => true,
        'rest_base' => 'spcu_hotel',
        'supports' => ['taxonomies'],
        'capabilities' => ['delete' => 'do_not_allow'],
    ]);
    register_taxonomy('spcu_facility', 'spcu_hotel', [
        'label' => 'Hotel Facilities',
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rest_base' => 'spcu_facility',
        'hierarchical' => false,
        'rewrite' => false,
    ]);
});

// Enqueue facilities JavaScript in admin
add_action('admin_enqueue_scripts', function($hook){
    if(strpos($hook, 'spcu-hotel-form') !== false){
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'spcu-facilities',
            SPCU_URL . 'admin/admin-facilities.js',
            ['jquery'],
            '1.0',
            true
        );
        wp_localize_script('spcu-facilities', 'spcu_facilities_data', [
            'nonce' => wp_create_nonce('wp_rest'),
            'rest_url' => rest_url('wp/v2/'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }
});

register_activation_hook(__FILE__, ['SPCU_Activator','activate']);
register_activation_hook(__FILE__, 'flush_rewrite_rules');

new SPCU_Admin();
new SPCU_Shortcode();