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
            'Ski Engine', 'Ski Engine', 'manage_options',
            'spcu-dashboard', [$this,'dashboard'],
            'dashicons-chart-line', 26
        );

        // First submenu = same as parent (removes the duplicate "Ski Engine" auto-item)
        add_submenu_page('spcu-dashboard','Dashboard','Dashboard','manage_options','spcu-dashboard',[$this,'dashboard']);

        // ── RESORTS ─────────────────────────────────────────────
        add_submenu_page('spcu-dashboard','Prefectures','Prefectures','manage_options','spcu-prefectures',[$this,'prefectures']);
        add_submenu_page('spcu-dashboard','Areas','Areas','manage_options','spcu-areas',[$this,'areas']);
        add_submenu_page('spcu-dashboard','Difficulties','Difficulties','manage_options','spcu-difficulties',[$this,'difficulties']);

        // ── ACCOMMODATION ────────────────────────────────────────
        add_submenu_page('spcu-dashboard','Hotels','Hotels','manage_options','spcu-hotels',[$this,'hotels']);
        add_submenu_page('spcu-dashboard','Hotel Prices','Hotel Prices','manage_options','spcu-hotel-prices',[$this,'prices']);
        add_submenu_page('spcu-dashboard','Tags','Tags','manage_options','edit-tags.php?taxonomy=spcu_facility&post_type=spcu_hotel');

        // ── OPERATIONS ───────────────────────────────────────────
        global $wpdb;
        $inq_table = $wpdb->prefix . 'spcu_inquiries';
        $new_count = 0;
        if((bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $inq_table))){
            $new_count = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$inq_table} WHERE status = 'new'");
        }
        $inq_label = 'Inquiries';
        if($new_count > 0){
            $inq_label .= ' <span class="awaiting-mod count-'.intval($new_count).'" style="background:#e74c3c;">'.intval($new_count).'</span>';
        }
        add_submenu_page('spcu-dashboard','Inquiries',$inq_label,'manage_options','spcu-inquiries',[$this,'inquiries']);
        add_submenu_page('spcu-dashboard','Import / Export','Import / Export','manage_options','spcu-io',[$this,'io']);

        // ── SETTINGS (3 visible sub-pages) ───────────────────────
        add_submenu_page('spcu-dashboard','General','General','manage_options','spcu-settings-general',[$this,'settings']);
        add_submenu_page('spcu-dashboard','Inquiry Page','Inquiry Page','manage_options','spcu-settings-inquiry',[$this,'settings']);
        add_submenu_page('spcu-dashboard','Email','Email','manage_options','spcu-settings-email',[$this,'settings']);

        // ── HIDDEN PAGES (null parent = never in sidebar) ────────
        add_submenu_page(null,'Prefecture Form','Prefecture Form','manage_options','spcu-prefecture-form',[$this,'prefecture_form']);
        add_submenu_page(null,'Area Form','Area Form','manage_options','spcu-area-form',[$this,'area_form']);
        add_submenu_page(null,'Hotel Form','Hotel Form','manage_options','spcu-hotel-form',[$this,'hotel_form']);
        add_submenu_page(null,'Addon Prices','Addon Prices','manage_options','spcu-addon-prices',[$this,'prices']);
        // Legacy settings slug redirect
        add_submenu_page(null,'Settings','Settings','manage_options','spcu-settings',[$this,'settings_legacy']);
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

    public function inquiries(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-inquiries.php';
    }

    public function settings(){
        require_once plugin_dir_path(__FILE__) . 'partials/spcu-admin-settings.php';
    }

    public function settings_legacy(){
        // Redirect old ?page=spcu-settings to General tab
        wp_safe_redirect(admin_url('admin.php?page=spcu-settings-general'));
        exit;
    }

    public function hide_internal_submenus(){
        echo '<style>
        /* ── Section Labels in sidebar ───────────────────────────────── */
        #toplevel_page_spcu-dashboard .wp-submenu li:has(a[href$="spcu-prefectures"]),
        #toplevel_page_spcu-dashboard .wp-submenu li:has(a[href$="spcu-hotels"]),
        #toplevel_page_spcu-dashboard .wp-submenu li:has(a[href$="spcu-inquiries"]),
        #toplevel_page_spcu-dashboard .wp-submenu li:has(a[href$="spcu-settings-general"]) {
            margin-top: 4px;
            padding-top: 2px;
            border-top: 1px solid rgba(240,246,252,0.10);
        }
        #toplevel_page_spcu-dashboard .wp-submenu li:has(a[href$="spcu-prefectures"])::before { content: "RESORTS"; }
        #toplevel_page_spcu-dashboard .wp-submenu li:has(a[href$="spcu-hotels"])::before       { content: "ACCOMMODATION"; }
        #toplevel_page_spcu-dashboard .wp-submenu li:has(a[href$="spcu-inquiries"])::before    { content: "OPERATIONS"; }
        #toplevel_page_spcu-dashboard .wp-submenu li:has(a[href$="spcu-settings-general"])::before { content: "SETTINGS"; }
        #toplevel_page_spcu-dashboard .wp-submenu li::before {
            display: block;
            font-size: 0.58rem;
            font-weight: 800;
            letter-spacing: 0.1em;
            text-transform: uppercase;
            color: rgba(240,246,252,0.32);
            padding: 6px 12px 2px;
            pointer-events: none;
        }
        /* Only show ::before on the 4 targeted items, hide on all others */
        #toplevel_page_spcu-dashboard .wp-submenu li:not(:has(a[href$="spcu-prefectures"])):not(:has(a[href$="spcu-hotels"])):not(:has(a[href$="spcu-inquiries"])):not(:has(a[href$="spcu-settings-general"]))::before {
            display: none;
        }
        </style>';
    }

    public function set_active_admin_menu($parent_file){
        $page     = sanitize_text_field($_GET['page'] ?? '');
        $taxonomy = sanitize_key($_GET['taxonomy'] ?? '');

        $plugin_pages = [
            'spcu-hotel-form','spcu-area-form','spcu-prefecture-form',
            'spcu-hotel-prices','spcu-addon-prices',
            'spcu-settings','spcu-settings-general','spcu-settings-inquiry','spcu-settings-email',
        ];
        if(in_array($page, $plugin_pages, true) || $taxonomy === 'spcu_facility'){
            return 'spcu-dashboard';
        }
        return $parent_file;
    }

    public function set_active_admin_submenu($submenu_file){
        $page     = sanitize_text_field($_GET['page'] ?? '');
        $taxonomy = sanitize_key($_GET['taxonomy'] ?? '');

        if(in_array($page, ['spcu-hotel-form','spcu-hotel-prices'], true)) return 'spcu-hotels';
        if($page === 'spcu-area-form')   return 'spcu-areas';
        if($page === 'spcu-addon-prices') return 'spcu-areas';
        if($page === 'spcu-prefecture-form') return 'spcu-prefectures';
        if($taxonomy === 'spcu_facility') return 'edit-tags.php?taxonomy=spcu_facility&post_type=spcu_hotel';
        // Settings sub-pages keep their own submenu item active
        if(in_array($page, ['spcu-settings','spcu-settings-general'], true)) return 'spcu-settings-general';
        if($page === 'spcu-settings-inquiry') return 'spcu-settings-inquiry';
        if($page === 'spcu-settings-email')   return 'spcu-settings-email';

        return $submenu_file;
    }
}
