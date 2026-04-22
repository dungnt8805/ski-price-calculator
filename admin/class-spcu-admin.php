<?php
if (!defined('ABSPATH')) exit;

class SPCU_Admin {

    public function __construct(){
        add_action('admin_menu', [$this,'menu']);
        add_action('admin_enqueue_scripts', [$this,'enqueue_admin_css']);
        add_action('admin_head', [$this, 'hide_internal_submenus']);
        add_filter('parent_file', [$this, 'set_active_admin_menu']);
        add_filter('submenu_file', [$this, 'set_active_admin_submenu']);
    }

    /* Load admin CSS */
    public function enqueue_admin_css(){
        wp_enqueue_style(
            'spcu-admin',
            SPCU_URL . 'admin/admin.css',
            [],
            '1.2'
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
            'Ski Engine',
            'Ski Engine',
            'manage_options',
            'spcu-dashboard',
            [$this,'dashboard'],
            'dashicons-chart-line',
            26
        );

        add_submenu_page('spcu-dashboard','Prefectures','Prefectures','manage_options','spcu-prefectures',[$this,'prefectures']);
        add_submenu_page('spcu-dashboard','Prefecture Form','Prefecture Form','manage_options','spcu-prefecture-form',[$this,'prefecture_form']);
        add_submenu_page('spcu-dashboard','Areas','Areas','manage_options','spcu-areas',[$this,'areas']);
        add_submenu_page('spcu-dashboard','Area Form','Area Form','manage_options','spcu-area-form',[$this,'area_form']);
        add_submenu_page('spcu-dashboard','Hotels','Hotels','manage_options','spcu-hotels',[$this,'hotels']);
        add_submenu_page('spcu-dashboard','Difficulties','Difficulties','manage_options','spcu-difficulties',[$this,'difficulties']);
        add_submenu_page('spcu-dashboard','Tags','Tags','manage_options','edit-tags.php?taxonomy=spcu_facility&post_type=spcu_hotel');
        add_submenu_page('spcu-dashboard','Hotel Form','Hotel Form','manage_options','spcu-hotel-form',[$this,'hotel_form']);
        add_submenu_page('spcu-dashboard','Hotel Prices','Hotel Prices','manage_options','spcu-hotel-prices',[$this,'prices']);
        add_submenu_page('spcu-dashboard','Addon Prices','Addon Prices','manage_options','spcu-addon-prices',[$this,'prices']);
        add_submenu_page('spcu-dashboard','Import / Export','Import / Export','manage_options','spcu-io',[$this,'io']);
    }

    /* Dashboard */
    public function dashboard(){
        global $wpdb;

        $areas_table = $wpdb->prefix . 'spcu_areas';
        $hotels_table = $wpdb->prefix . 'spcu_hotels';

        $areas_count = 0;
        $hotels_count = 0;

        $areas_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $areas_table));
        if($areas_exists){
            $areas_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$areas_table}");
        }

        $hotels_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $hotels_table));
        if($hotels_exists){
            $hotels_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$hotels_table}");
        }

        echo "<div class='wrap'>";
        echo "<h1>Ski Engine</h1>";
        echo "<p class='spcu-dashboard-intro'>Overview of your configured data.</p>";

        echo "<div class='spcu-dashboard-grid'>";
        echo "<div class='spcu-dashboard-card'>";
        echo "<div class='spcu-dashboard-label'>Areas</div>";
        echo "<div class='spcu-dashboard-value'>" . esc_html($areas_count) . "</div>";
        echo "<a class='button button-small' href='" . esc_url(admin_url('admin.php?page=spcu-areas')) . "'>Manage Areas</a>";
        echo "</div>";

        echo "<div class='spcu-dashboard-card'>";
        echo "<div class='spcu-dashboard-label'>Hotels</div>";
        echo "<div class='spcu-dashboard-value'>" . esc_html($hotels_count) . "</div>";
        echo "<a class='button button-small' href='" . esc_url(admin_url('admin.php?page=spcu-hotels')) . "'>Manage Hotels</a>";
        echo "</div>";
        echo "</div>";
        echo "</div>";
    }

    public function prefectures(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-prefectures.php';
    }

    public function prefecture_form(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-prefecture-form.php';
    }

    public function areas(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-areas.php';
    }

    public function area_form(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-area-form.php';
    }

    public function hotels(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-hotels.php';
    }

    public function difficulties(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-difficulties.php';
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

    public function hide_internal_submenus(){
        echo '<style>
            #toplevel_page_spcu-dashboard .wp-submenu a[href="admin.php?page=spcu-hotel-form"],
            #toplevel_page_spcu-dashboard .wp-submenu a[href="admin.php?page=spcu-area-form"],
            #toplevel_page_spcu-dashboard .wp-submenu a[href="admin.php?page=spcu-prefecture-form"],
            #toplevel_page_spcu-dashboard .wp-submenu a[href="admin.php?page=spcu-hotel-prices"] {
                display: none;
            }
        </style>';
    }

    public function set_active_admin_menu($parent_file){
        $page = sanitize_text_field($_GET['page'] ?? '');
        $taxonomy = sanitize_key($_GET['taxonomy'] ?? '');

        if(in_array($page, ['spcu-hotel-form', 'spcu-area-form', 'spcu-prefecture-form', 'spcu-hotel-prices'], true) || $taxonomy === 'spcu_facility'){
            return 'spcu-dashboard';
        }

        return $parent_file;
    }

    public function set_active_admin_submenu($submenu_file){
        $page = sanitize_text_field($_GET['page'] ?? '');
        $taxonomy = sanitize_key($_GET['taxonomy'] ?? '');

        if(in_array($page, ['spcu-hotel-form', 'spcu-hotel-prices'], true)){
            return 'spcu-hotels';
        }

        if($page === 'spcu-area-form'){
            return 'spcu-areas';
        }

        if($page === 'spcu-prefecture-form'){
            return 'spcu-prefectures';
        }

        if($taxonomy === 'spcu_facility'){
            return 'edit-tags.php?taxonomy=spcu_facility&post_type=spcu_hotel';
        }

        return $submenu_file;
    }
}
