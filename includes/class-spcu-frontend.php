<?php
class SPCU_Frontend {

    /**
     * Cached area object for the current request.
     *
     * @var object|null|false
     */
    private $current_area = false;

    public function __construct(){
        add_action('init', [$this, 'register_rewrite_rules']);
        add_filter('query_vars', [$this, 'add_query_vars']);
        add_filter('pre_get_document_title', [$this, 'filter_area_document_title']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('template_redirect', [$this, 'load_area_template']);
    }

    private function get_current_area(){
        if($this->current_area !== false){
            return $this->current_area;
        }

        $area_name = get_query_var('area_name') ?: get_query_var('area_slug');

        if(empty($area_name)){
            $this->current_area = null;
            return $this->current_area;
        }

        $this->current_area = self::get_area_by_slug($area_name);

        return $this->current_area;
    }

    public function filter_area_document_title($title){
        $area = $this->get_current_area();

        if(!$area || empty($area->name)){
            return $title;
        }

        return wp_strip_all_tags((string) $area->name);
    }

    public function enqueue_assets(){
        if(get_query_var('area_name') === ''){
            return;
        }

        wp_enqueue_style(
            'spcu-public',
            SPCU_URL . 'public/public.css',
            [],
            '2.3'
        );
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
        $vars[] = 'area_slug';  // Also support theme's area_slug query var
        return $vars;
    }

    public function load_area_template(){
        // Support both plugin's area_name query var and theme's area_slug query var
        $area_name = get_query_var('area_name') ?: get_query_var('area_slug');
        
        if(!empty($area_name)){
            global $wp_query;

            $area = $this->get_current_area();

            if($area && isset($wp_query)){
                $wp_query->is_404 = false;
                $wp_query->queried_object = null;
                $wp_query->queried_object_id = 0;
            }

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

        $raw_slug = sanitize_text_field(rawurldecode((string) $slug));
        $normalized_slug = sanitize_title($raw_slug);
        $search_name = sanitize_text_field(str_replace(['-', '_'], ' ', $raw_slug));

        $area = $wpdb->get_row($wpdb->prepare(
            "SELECT a.*, p.name AS prefecture_name
            FROM {$wpdb->prefix}spcu_areas a
            LEFT JOIN {$wpdb->prefix}spcu_prefectures p ON p.id = a.prefecture_id
            WHERE a.slug = %s OR a.slug = %s OR LOWER(a.name) = %s OR LOWER(a.name_ja) = %s
            LIMIT 1",
            $raw_slug,
            $normalized_slug,
            strtolower($search_name),
            strtolower($search_name)
        ));

        if($area){
            return $area;
        }

        $areas = $wpdb->get_results(
            "SELECT a.*, p.name AS prefecture_name
            FROM {$wpdb->prefix}spcu_areas a
            LEFT JOIN {$wpdb->prefix}spcu_prefectures p ON p.id = a.prefecture_id"
        );

        foreach($areas as $candidate_area){
            $candidate_slug = !empty($candidate_area->slug) ? (string) $candidate_area->slug : (string) $candidate_area->name;

            if(sanitize_title($candidate_slug) === $normalized_slug){
                return $candidate_area;
            }

            if(!empty($candidate_area->name_ja) && sanitize_title((string) $candidate_area->name_ja) === $normalized_slug){
                return $candidate_area;
            }
        }

        return null;
    }

    /**
     * Get hotels in area
     */
    public static function get_hotels_by_area($area_id){
        global $wpdb;
        
        return $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}spcu_hotels WHERE area_id = %d ORDER BY is_featured DESC, name ASC",
            $area_id
        ));
    }

    /**
     * Get minimum hotel prices keyed by hotel ID for an area.
     */
    public static function get_hotel_min_prices_by_area($area_id){
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT p.hotel_id,
                    MIN(NULLIF(p.price_jpy, 0)) AS min_price_jpy,
                    MIN(NULLIF(p.price_usd, 0)) AS min_price_usd
             FROM {$wpdb->prefix}spcu_prices p
             INNER JOIN {$wpdb->prefix}spcu_hotels h ON h.id = p.hotel_id
             WHERE h.area_id = %d AND p.category = 'hotel'
             GROUP BY p.hotel_id",
            $area_id
        ));

        $result = [];
        foreach($rows as $row){
            $hotel_id = (int) ($row->hotel_id ?? 0);
            if($hotel_id <= 0){
                continue;
            }

            $result[$hotel_id] = [
                'price_jpy' => isset($row->min_price_jpy) && $row->min_price_jpy !== null ? (int) round((float) $row->min_price_jpy) : 0,
                'price_usd' => isset($row->min_price_usd) && $row->min_price_usd !== null ? (int) round((float) $row->min_price_usd) : 0,
            ];
        }

        return $result;
    }

    /**
     * Get addon prices for area
     */
    public static function get_area_addon_prices($area_id){
        global $wpdb;

        $rows = $wpdb->get_results($wpdb->prepare(
            "SELECT DISTINCT category, grade FROM {$wpdb->prefix}spcu_addon_prices WHERE area_id = %d ORDER BY category ASC, grade ASC",
            $area_id
        ));

        usort($rows, function($left, $right){
            if($left->category !== $right->category){
                return strcmp($left->category, $right->category);
            }

            $order = array_flip(SPCU_Grades::ordered_keys());
            return ($order[SPCU_Grades::normalize($left->grade)] ?? PHP_INT_MAX) <=> ($order[SPCU_Grades::normalize($right->grade)] ?? PHP_INT_MAX);
        });

        return $rows;
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
