<?php
if (!defined('ABSPATH')) exit;

class SPCU_Admin {

    public function __construct(){
        add_action('admin_menu', [$this,'menu']);
        add_action('admin_enqueue_scripts', [$this,'enqueue_admin_css']);
    }

    /* Load admin CSS */
    public function enqueue_admin_css(){
        wp_enqueue_style(
            'spcu-admin',
            SPCU_URL . 'admin/admin.css',
            [],
            '1.0'
        );

        wp_enqueue_script(
            'spcu-admin',
            SPCU_URL . 'admin/admin.js',
            [],
            '1.0',
            true
        );
    }

    /* Create Admin Menu */
    public function menu(){
        add_menu_page(
            'Ski Calculator',
            'Ski Calculator',
            'manage_options',
            'spcu-dashboard',
            [$this,'dashboard'],
            'dashicons-chart-line',
            26
        );

        add_submenu_page('spcu-dashboard','Areas','Areas','manage_options','spcu-areas',[$this,'areas']);
        add_submenu_page('spcu-dashboard','Hotels','Hotels','manage_options','spcu-hotels',[$this,'hotels']);
        add_submenu_page(null,'Hotel Form','Hotel Form','manage_options','spcu-hotel-form',[$this,'hotel_form']);
        add_submenu_page(null,'Hotel Prices','Hotel Prices','manage_options','spcu-hotel-prices',[$this,'prices']);
        add_submenu_page('spcu-dashboard','Addon Prices','Addon Prices','manage_options','spcu-addon-prices',[$this,'prices']);
        add_submenu_page('spcu-dashboard','Import / Export','Import / Export','manage_options','spcu-io',[$this,'io']);
    }

    /* Dashboard */
    public function dashboard(){
        echo "<div class='wrap'><h1>Ski Calculator Ultimate</h1><p>Welcome to the dashboard. Manage your areas, hotels and prices from the menus.</p></div>";
    }

    public function areas(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-areas.php';
    }

    public function hotels(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-hotels.php';
    }

    public function hotel_form(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-hotel-form.php';
    }

    public function prices(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-prices.php';
    }

    public function io(){
        echo "<div class='wrap'><h1>Import / Export</h1>
        <p><a href='".rest_url('spc/v1/export')."' class='button button-primary'>Download CSV</a></p>
        </div>";
    }
}
