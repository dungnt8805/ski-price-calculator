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

require_once SPCU_PATH.'admin/partials/spcu-admin-areas-post.php';
add_action('admin_init', 'spcu_handle_areas_post');

require_once SPCU_PATH.'admin/partials/spcu-admin-prices-post.php';
add_action('admin_init', 'spcu_handle_prices_post');
add_action('admin_init', 'spcu_handle_prices_delete');
require_once SPCU_PATH.'admin/partials/spcu-admin-hotels-post.php';
add_action('admin_init', 'spcu_handle_hotels_post');
add_action('admin_init', 'spcu_handle_hotels_delete');

register_activation_hook(__FILE__, ['SPCU_Activator','activate']);

new SPCU_Admin();
new SPCU_Shortcode();