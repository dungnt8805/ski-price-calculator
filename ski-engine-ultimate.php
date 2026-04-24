<?php
/**
 * Plugin Name: Ski Engine
 * Description: Manage ski resort areas, hotels (bilingual), difficulties, tags and pricing with weekday/date/range schedules and JPY/USD support. Frontend calculator shortcode included.
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
require_once SPCU_PATH.'includes/class-spcu-difficulties.php';
require_once SPCU_PATH.'includes/class-spcu-grades.php';
require_once SPCU_PATH.'admin/class-spcu-admin.php';
require_once SPCU_PATH.'includes/class-spcu-shortcode.php';
require_once SPCU_PATH.'includes/class-spcu-api.php';
require_once SPCU_PATH.'includes/class-spcu-frontend.php';
require_once SPCU_PATH.'includes/class-spcu-elementor.php';
require_once SPCU_PATH.'includes/class-spcu-inquiry.php';

require_once SPCU_PATH.'admin/partials/spcu-admin-prefectures-post.php';
add_action('admin_init', 'spcu_handle_prefectures_post');
require_once SPCU_PATH.'admin/partials/spcu-admin-areas-post.php';
add_action('admin_init', 'spcu_handle_areas_post');
require_once SPCU_PATH.'admin/partials/spcu-admin-difficulties-post.php';
add_action('admin_init', 'spcu_handle_difficulties_post');
add_action('admin_init', 'spcu_handle_difficulties_delete');

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
        'labels' => [
            'name' => 'Tags',
            'singular_name' => 'Tag',
            'menu_name' => 'Tags',
            'search_items' => 'Search Tags',
            'popular_items' => 'Popular Tags',
            'all_items' => 'All Tags',
            'edit_item' => 'Edit Tag',
            'view_item' => 'View Tag',
            'update_item' => 'Update Tag',
            'add_new_item' => 'Add New Tag',
            'new_item_name' => 'New Tag Name',
            'separate_items_with_commas' => 'Separate tags with commas',
            'add_or_remove_items' => 'Add or remove tags',
            'choose_from_most_used' => 'Choose from the most used tags',
            'not_found' => 'No tags found',
            'back_to_items' => 'Back to Tags',
        ],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'show_in_rest' => true,
        'rest_base' => 'spcu_facility',
        'hierarchical' => false,
        'rewrite' => false,
    ]);
});

// Register taxonomy field for REST API
add_action('rest_api_init', function(){
    register_rest_field('spcu_hotel', 'spcu_facility', [
        'get_callback' => function($post) {
            $terms = wp_get_post_terms($post['id'], 'spcu_facility', ['fields' => 'ids']);
            return $terms ?: [];
        },
    ]);
});

// AJAX endpoint to load hotel facilities from database
add_action('wp_ajax_spcu_load_hotel_facilities', function(){
    $hotel_id = intval($_GET['hotel_id'] ?? 0);
    if($hotel_id <= 0){
        wp_send_json_error('Invalid hotel ID', 400);
    }

    global $wpdb;
    
    // First try to get from WordPress post
    $terms = wp_get_post_terms($hotel_id, 'spcu_facility', ['fields' => 'ids']);
    if(!empty($terms)){
        wp_send_json_success(['facilities' => $terms]);
    }
    
    // If no post, fallback is empty - user can add facilities
    wp_send_json_success(['facilities' => []]);
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
new SPCU_Inquiry();

// Admin notice: Check if inquiry page is set up
add_action('admin_notices', function(){
    if (!current_user_can('manage_options')) return;
    
    $inquiry_page_url = get_option('spcu_inquiry_page_url', '');
    if (!$inquiry_page_url) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>Ski Engine:</strong> The inquiry page is not configured. The "Request a Quote" button will not work. 
            <br>Please create a new page with:
            <ul style="margin-top:10px; margin-left:20px;">
                <li><strong>Title:</strong> "Inquiry"</li>
                <li><strong>Content:</strong> <code>[spcu_inquiry_form]</code></li>
                <li><strong>Status:</strong> Publish</li>
            </ul>
            Or click here to <a href="<?= admin_url('post-new.php?post_type=page&post_title=Inquiry') ?>" class="button">create the Inquiry page</a></p>
        </div>
        <?php
    }

    if (!function_exists('is_plugin_active')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    $smtp_active = function_exists('is_plugin_active')
        && (is_plugin_active('wp-mail-smtp/wp_mail_smtp.php')
        || (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('wp-mail-smtp/wp_mail_smtp.php')));

    if (!$smtp_active) {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><strong>Ski Engine:</strong> WP Mail SMTP is required for inquiry email delivery.
            <a href="<?= esc_url(admin_url('plugin-install.php?s=WP%20Mail%20SMTP&tab=search&type=term')) ?>">Install WP Mail SMTP</a> or
            <a href="<?= esc_url(admin_url('plugins.php')) ?>">activate it in Plugins</a>.</p>
        </div>
        <?php
    }
});