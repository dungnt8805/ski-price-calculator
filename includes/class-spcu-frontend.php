<?php
class SPCU_Frontend {

    public function __construct(){
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_action('template_redirect', [$this, 'load_area_template']);
    }

    public function register_rewrite_rules(){
        add_rewrite_rule(
            '^area/([^/]+)/?$',
            'index.php?area_name=$matches[1]',
            'top'
        );
    }

    public function add_query_vars($vars){
        $vars[] = 'area_name';
        return $vars;
    }

    public function load_area_template(){
        $area_name = get_query_var('area_name');
        
        if(!empty($area_name)){
            status_header(200);
            
            // Load the area template
            $template_path = SPCU_PATH.'templates/area-single.php';
            if(file_exists($template_path)){
                include $template_path;
                exit;
            }
        }
    }

    /**
     * Get area by URL-friendly name (slug)
     */
    public static function get_area_by_slug($slug){
        global $wpdb;
        
        // Sanitize slug - replace hyphens/underscores with spaces for matching
        $search_name = sanitize_text_field(str_replace(['-', '_'], ' ', $slug));
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spcu_areas WHERE LOWER(name) = %s OR LOWER(name_ja) = %s LIMIT 1",
            strtolower($search_name),
            strtolower($search_name)
        ));
    }

    /**
     * Get hotels in area
     */
    public static function get_hotels_by_area($area_id){
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spcu_hotels WHERE area_id = %d ORDER BY name ASC",
            $area_id
        ));
    }

    /**
     * Get addon prices for area
     */
    public static function get_area_addon_prices($area_id){
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT category, grade FROM {$wpdb->prefix}spcu_addon_prices WHERE area_id = %d ORDER BY category ASC, FIELD(grade, 'standard', 'premium', 'exclusive')",
            $area_id
        ));
    }

    /**
     * Get specific addon price
     */
    public static function get_addon_price($area_id, $category, $grade){
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT price_jpy, price_usd FROM {$wpdb->prefix}spcu_addon_prices WHERE area_id = %d AND category = %s AND grade = %s LIMIT 1",
            $area_id, $category, $grade
        ));
    }
}

new SPCU_Frontend();
