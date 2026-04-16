<?php
if (!defined('ABSPATH')) exit;

class SPCU_API {

    public function __construct(){
        add_action('rest_api_init', [$this,'routes']);
    }

    public function routes(){

        /* All prices (full detail) */
        register_rest_route('spc/v1', '/prices', [
            'methods'             => 'GET',
            'callback'            => [$this,'get_prices'],
            'permission_callback' => '__return_true',
        ]);

        /* Hotels list for dropdowns (area + grade embedded) */
        register_rest_route('spc/v1', '/hotels', [
            'methods'             => 'GET',
            'callback'            => [$this,'get_hotels'],
            'permission_callback' => '__return_true',
        ]);

        /* Prices for a specific hotel on a specific date (calculator) */
        register_rest_route('spc/v1', '/hotel-price', [
            'methods'             => 'GET',
            'callback'            => [$this,'get_hotel_price'],
            'permission_callback' => '__return_true',
            'args' => [
                'hotel_id' => ['required'=>true,  'sanitize_callback'=>'absint'],
                'date'     => ['required'=>false, 'sanitize_callback'=>'sanitize_text_field'],
            ],
        ]);

        /* CSV Export */
        register_rest_route('spc/v1', '/export', [
            'methods'             => 'GET',
            'callback'            => [$this,'export_csv'],
            'permission_callback' => '__return_true',
        ]);

        /* Aggregated data: areas + addon prices + hotels with prices */
        register_rest_route('spc/v1', '/catalog', [
            'methods'             => 'GET',
            'callback'            => [$this,'get_catalog'],
            'permission_callback' => '__return_true',
        ]);
    }

    /* ── Aggregated catalog payload ─────────────────────────────── */
    public function get_catalog(){
        global $wpdb;

        $areas = $wpdb->get_results("\n            SELECT id, name, name_ja, type\n            FROM {$wpdb->prefix}spcu_areas\n            ORDER BY name ASC\n        ");

        $addon_rows = $wpdb->get_results("\n            SELECT\n                p.*,\n                a.name as area,\n                a.name_ja as area_ja,\n                a.type as area_type\n            FROM {$wpdb->prefix}spcu_addon_prices p\n            LEFT JOIN {$wpdb->prefix}spcu_areas a ON a.id = p.area_id\n            ORDER BY p.category ASC, p.area_id ASC, p.days ASC, p.id ASC\n        ");

        foreach($addon_rows as $r){
            $r->weekdays = $r->weekdays_json ? json_decode($r->weekdays_json, true) : [];
            $r->dates    = $r->dates_json ? json_decode($r->dates_json, true) : [];
            $r->grade_key = $r->grade;
            $r->grade = SPCU_Grades::label($r->grade_key ?? '');
            unset($r->weekdays_json, $r->dates_json);
        }

        $hotels = $wpdb->get_results("\n            SELECT\n                h.id, h.name, h.name_ja, h.address, h.address_ja, h.images, h.grade as grade_key,\n                a.id as area_id, a.name as area, a.name_ja as area_ja, a.type as area_type\n            FROM {$wpdb->prefix}spcu_hotels h\n            LEFT JOIN {$wpdb->prefix}spcu_areas a ON a.id = h.area_id\n            ORDER BY h.name ASC\n        ");

        $hotel_price_rows = $wpdb->get_results("\n            SELECT *\n            FROM {$wpdb->prefix}spcu_prices\n            WHERE category = 'hotel'\n            ORDER BY hotel_id ASC, id ASC\n        ");

        $prices_by_hotel = [];
        foreach($hotel_price_rows as $rule){
            $hotel_id = intval($rule->hotel_id ?? 0);
            if($hotel_id <= 0) continue;
            if(!isset($prices_by_hotel[$hotel_id])){
                $prices_by_hotel[$hotel_id] = [];
            }
            $formatted = $this->format_price_rule($rule);
            $formatted['category'] = $rule->category;
            $formatted['days'] = $rule->days ? (int)$rule->days : null;
            $prices_by_hotel[$hotel_id][] = $formatted;
        }

        foreach($hotels as $h){
            $imgs = [];
            if(!empty($h->images)){
                foreach(explode(',', $h->images) as $id){
                    $url = wp_get_attachment_image_url(intval($id), 'medium');
                    if($url) $imgs[] = $url;
                }
            }
            $h->image_urls = $imgs;
            $h->grade = SPCU_Grades::label($h->grade_key ?? '');
            $h->prices = $prices_by_hotel[intval($h->id)] ?? [];
        }

        return rest_ensure_response([
            'areas' => $areas,
            'addon_prices' => $addon_rows,
            'hotels' => $hotels,
        ]);
    }

    /* ── All Prices ──────────────────────────────────────────────── */
    public function get_prices(){
        global $wpdb;

        $rows1 = $wpdb->get_results("
            SELECT
                p.*,
                h.name    as hotel,
                h.name_ja as hotel_ja,
                a.name    as area,
                a.name_ja as area_ja,
                a.type    as area_type,
                NULL      as grade_key
            FROM {$wpdb->prefix}spcu_prices p
            LEFT JOIN {$wpdb->prefix}spcu_hotels h ON h.id = p.hotel_id
            LEFT JOIN {$wpdb->prefix}spcu_areas  a ON a.id = COALESCE(p.area_id, h.area_id)
        ");

        $rows2 = $wpdb->get_results("
            SELECT
                p.*,
                NULL      as price_min_jpy,
                NULL      as price_max_jpy,
                NULL      as price_min_usd,
                NULL      as price_max_usd,
                NULL      as hotel_id,
                NULL      as hotel,
                NULL      as hotel_ja,
                a.name    as area,
                a.name_ja as area_ja,
                a.type    as area_type,
                p.grade   as grade_key
            FROM {$wpdb->prefix}spcu_addon_prices p
            LEFT JOIN {$wpdb->prefix}spcu_areas  a ON a.id = p.area_id
        ");

        $rows = array_merge($rows1, $rows2);

        // Decode JSON columns
        foreach($rows as $r){
            $r->weekdays = $r->weekdays_json ? json_decode($r->weekdays_json) : [];
            $r->dates    = $r->dates_json    ? json_decode($r->dates_json)    : [];
            $r->grade    = SPCU_Grades::label($r->grade_key ?? '');
            unset($r->weekdays_json, $r->dates_json);
        }

        return rest_ensure_response($rows);
    }

    /* ── Hotels list ─────────────────────────────────────────────── */
    public function get_hotels(){
        global $wpdb;

        $rows = $wpdb->get_results("
            SELECT
                h.id, h.name, h.name_ja, h.address, h.address_ja, h.images,
                a.id   as area_id,   a.name as area,     a.name_ja as area_ja,   a.type as area_type,
                h.grade as grade_key
            FROM {$wpdb->prefix}spcu_hotels h
            LEFT JOIN {$wpdb->prefix}spcu_areas  a ON a.id = h.area_id
            ORDER BY h.name ASC
        ");

        $hotel_price_rows = $wpdb->get_results("
            SELECT *
            FROM {$wpdb->prefix}spcu_prices
            WHERE category = 'hotel'
            ORDER BY hotel_id ASC, id ASC
        ");

        $prices_by_hotel = [];
        foreach($hotel_price_rows as $rule){
            $hotel_id = intval($rule->hotel_id ?? 0);
            if($hotel_id <= 0) continue;
            if(!isset($prices_by_hotel[$hotel_id])){
                $prices_by_hotel[$hotel_id] = [];
            }
            $formatted = $this->format_price_rule($rule);
            $formatted['category'] = $rule->category;
            $formatted['days'] = $rule->days ? (int)$rule->days : null;
            $prices_by_hotel[$hotel_id][] = $formatted;
        }

        // Resolve image URLs
        foreach($rows as $r){
            $imgs = [];
            if(!empty($r->images)){
                foreach(explode(',', $r->images) as $id){
                    $url = wp_get_attachment_image_url(intval($id), 'medium');
                    if($url) $imgs[] = $url;
                }
            }
            $r->image_urls = $imgs;
            $r->grade = SPCU_Grades::label($r->grade_key ?? '');
            $r->prices = $prices_by_hotel[intval($r->id)] ?? [];
        }

        return rest_ensure_response($rows);
    }

    /* ── Price lookup for a single hotel + date ──────────────────── */
    public function get_hotel_price(WP_REST_Request $req){
        global $wpdb;

        $hotel_id = $req->get_param('hotel_id');
        $date_str = $req->get_param('date'); // YYYY-MM-DD, optional

        // Fetch all price rules for this hotel
        $rules = $wpdb->get_results($wpdb->prepare("
            SELECT * FROM {$wpdb->prefix}spcu_prices
            WHERE category = 'hotel' AND hotel_id = %d
        ", $hotel_id));

        if(empty($rules)){
            return rest_ensure_response(['prices' => [], 'matched' => null]);
        }

        $matched = null;

        if($date_str){
            $ts       = strtotime($date_str);
            $dow      = strtolower(date('l', $ts)); // e.g. 'monday'
            $ymd      = date('Y-m-d', $ts);

            foreach($rules as $rule){
                switch($rule->price_type){
                    case 'specific_dates':
                        $dates = json_decode($rule->dates_json ?? '[]', true);
                        if(in_array($ymd, $dates, true)){ $matched = $rule; break 2; }
                        break;
                    case 'date_range':
                        if($rule->date_from && $rule->date_to
                           && $ymd >= $rule->date_from && $ymd <= $rule->date_to){
                            $matched = $rule; break 2;
                        }
                        break;
                    case 'weekend':
                        if(in_array($dow, ['saturday','sunday'], true)){ $matched = $rule; break 2; }
                        break;
                    case 'selected_days':
                        $days = json_decode($rule->weekdays_json ?? '[]', true);
                        if(in_array($dow, $days, true)){ $matched = $rule; break 2; }
                        break;
                }
            }

            // Fallback: first rule if no specific match
            if(!$matched) $matched = $rules[0];
        }

        // Format response
        $result = [
            'rules'   => array_map([$this,'format_price_rule'], $rules),
            'matched' => $matched ? $this->format_price_rule($matched) : null,
        ];

        return rest_ensure_response($result);
    }

    /* ── Format a single price rule for JSON output ──────────────── */
    private function format_price_rule($r){
        return [
            'id'           => (int)$r->id,
            'price_type'   => $r->price_type,
            'weekdays'     => json_decode($r->weekdays_json ?? '[]', true),
            'dates'        => json_decode($r->dates_json    ?? '[]', true),
            'date_from'    => $r->date_from,
            'date_to'      => $r->date_to,
            'currency'     => $r->currency,
            'price_jpy'    => $r->price_jpy    ? (float)$r->price_jpy    : null,
            'price_usd'    => $r->price_usd    ? (float)$r->price_usd    : null,
            'price_min_jpy'=> $r->price_min_jpy ? (float)$r->price_min_jpy : null,
            'price_max_jpy'=> $r->price_max_jpy ? (float)$r->price_max_jpy : null,
            'price_min_usd'=> $r->price_min_usd ? (float)$r->price_min_usd : null,
            'price_max_usd'=> $r->price_max_usd ? (float)$r->price_max_usd : null,
        ];
    }

    /* ── CSV Export ──────────────────────────────────────────────── */
    public function export_csv(){
        global $wpdb;

        $rows1 = $wpdb->get_results("
            SELECT
                p.id, p.category, p.price_type,
                p.weekdays_json, p.dates_json, p.date_from, p.date_to,
                p.currency, p.days,
                p.price_jpy, p.price_usd,
                p.price_min_jpy, p.price_max_jpy,
                p.price_min_usd, p.price_max_usd,
                h.name as hotel,  h.name_ja as hotel_ja,
                a.name as area,   a.name_ja as area_ja,
                NULL as grade
            FROM {$wpdb->prefix}spcu_prices p
            LEFT JOIN {$wpdb->prefix}spcu_hotels h ON h.id = p.hotel_id
            LEFT JOIN {$wpdb->prefix}spcu_areas  a ON a.id = COALESCE(p.area_id, h.area_id)
        ", ARRAY_A);

        $rows2 = $wpdb->get_results("
            SELECT
                p.id, p.category, p.price_type,
                p.weekdays_json, p.dates_json, p.date_from, p.date_to,
                p.currency, p.days,
                p.price_jpy, p.price_usd,
                NULL as price_min_jpy, NULL as price_max_jpy,
                NULL as price_min_usd, NULL as price_max_usd,
                NULL as hotel,  NULL as hotel_ja,
                a.name as area,   a.name_ja as area_ja,
                p.grade as grade
            FROM {$wpdb->prefix}spcu_addon_prices p
            LEFT JOIN {$wpdb->prefix}spcu_areas  a ON a.id = p.area_id
        ", ARRAY_A);

        $rows = array_merge($rows1, $rows2);

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename=ski-prices.csv');
        echo "\xEF\xBB\xBF"; // UTF-8 BOM for Excel

        $out = fopen('php://output', 'w');
        if(!empty($rows)){
            fputcsv($out, array_keys($rows[0]));
            foreach($rows as $row) fputcsv($out, $row);
        }
        fclose($out);
        exit;
    }
}

new SPCU_API();