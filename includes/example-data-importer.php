<?php
/**
 * Ski Price Calculator - Example Data Importer
 * 
 * Usage: Add this to your theme's functions.php or create a custom admin page
 * Then call: do_action('spcu_import_example_data');
 * 
 * Or run via WP-CLI:
 * wp eval 'do_action("spcu_import_example_data");'
 */

if (!defined('ABSPATH')) exit;

function spcu_import_example_data() {
    global $wpdb;
    
    // Define table names
    $areas_table = $wpdb->prefix . 'spcu_areas';
    $hotels_table = $wpdb->prefix . 'spcu_hotels';
    $prices_table = $wpdb->prefix . 'spcu_prices';
    $addon_prices_table = $wpdb->prefix . 'spcu_addon_prices';

    // ── AREAS ────────────────────────────────────────────────────────────

    $areas = [
        ['type' => 'Prefecture', 'name' => 'Hakuba Valley', 'name_ja' => '白馬バレー', 'short_description' => 'Japan\'s largest interconnected ski resort with 13 mountains'],
        ['type' => 'Town', 'name' => 'Niseko', 'name_ja' => 'ニセコ', 'short_description' => 'Powder paradise with consistent snow and world-class terrain'],
        ['type' => 'Town', 'name' => 'Yuzawa Snow Park', 'name_ja' => '湯沢スノーパーク', 'short_description' => 'Family-friendly resort near Tokyo with excellent facilities'],
        ['type' => 'Prefecture', 'name' => 'Nagano', 'name_ja' => '長野', 'short_description' => 'Olympic host with premium alpine skiing and spectacular views'],
    ];

    foreach ($areas as $area) {
        $wpdb->insert($areas_table, $area);
    }
    echo "✓ Inserted " . count($areas) . " areas\n";

    // ── HOTELS ──────────────────────────────────────────────────────────

    $hotels = [
        // Hakuba Valley
        ['area_id' => 1, 'name' => 'Hakuba Highland Hotel', 'name_ja' => '白馬ハイランドホテル', 'grade' => 'premium', 'short_description' => 'Ski-in/ski-out luxury resort with onsen', 'address' => '3581 Hakuba, Nagano', 'is_featured' => 1],
        ['area_id' => 1, 'name' => 'Hakuba Grandvaux', 'name_ja' => '白馬グランボー', 'grade' => 'exclusive', 'short_description' => 'Premier 5-star alpine lodge with Michelin dining', 'address' => '3593 Hakuba, Nagano', 'is_featured' => 1],
        ['area_id' => 1, 'name' => 'Alpine Valley Lodge', 'name_ja' => 'アルパインバレーロッジ', 'grade' => 'standard', 'short_description' => 'Cozy mountain lodge perfect for families', 'address' => '3500 Hakuba, Nagano', 'is_featured' => 0],
        ['area_id' => 1, 'name' => 'Ezo Powder House', 'name_ja' => 'エゾパウダーハウス', 'grade' => 'premium', 'short_description' => 'Modern lodge with shared kitchen facilities', 'address' => '3585 Hakuba, Nagano', 'is_featured' => 0],
        
        // Niseko
        ['area_id' => 2, 'name' => 'Niseko Grand Hotel', 'name_ja' => 'ニセコグランドホテル', 'grade' => 'exclusive', 'short_description' => 'Luxury resort overlooking Mount Yotei', 'address' => '204 Niseko, Hokkaido', 'is_featured' => 1],
        ['area_id' => 2, 'name' => 'Niseko Powder Lodge', 'name_ja' => 'ニセコパウダーロッジ', 'grade' => 'premium', 'short_description' => 'Boutique powder focused lodge', 'address' => '210 Niseko, Hokkaido', 'is_featured' => 0],
        ['area_id' => 2, 'name' => 'Niseko Base Camp', 'name_ja' => 'ニセコベースキャンプ', 'grade' => 'standard', 'short_description' => 'Budget-friendly accommodation near village', 'address' => '198 Niseko, Hokkaido', 'is_featured' => 0],
        
        // Yuzawa
        ['area_id' => 3, 'name' => 'Yuzawa Prince Hotel', 'name_ja' => '湯沢プリンスホテル', 'grade' => 'exclusive', 'short_description' => 'Premium resort with spa and multiple pools', 'address' => '1800 Yuzawa, Niigata', 'is_featured' => 1],
        ['area_id' => 3, 'name' => 'Yuzawa Family Resort', 'name_ja' => '湯沢ファミリーリゾート', 'grade' => 'standard', 'short_description' => 'Perfect for families with kids\' clubs', 'address' => '1750 Yuzawa, Niigata', 'is_featured' => 0],
        ['area_id' => 3, 'name' => 'Snow Ridge Inn', 'name_ja' => 'スノーリッジイン', 'grade' => 'premium', 'short_description' => 'Modern mid-range hotel with great views', 'address' => '1770 Yuzawa, Niigata', 'is_featured' => 0],
        
        // Nagano
        ['area_id' => 4, 'name' => 'Nagano Olympic Lodge', 'name_ja' => '長野オリンピックロッジ', 'grade' => 'exclusive', 'short_description' => 'Historic Olympic venue accommodation', 'address' => '3680 Nagano, Nagano', 'is_featured' => 1],
        ['area_id' => 4, 'name' => 'Nagano Summit Hotel', 'name_ja' => '長野サミットホテル', 'grade' => 'premium', 'short_description' => 'Alpine hotel with panoramic mountain views', 'address' => '3670 Nagano, Nagano', 'is_featured' => 0],
    ];

    foreach ($hotels as $hotel) {
        $wpdb->insert($hotels_table, $hotel);
    }
    echo "✓ Inserted " . count($hotels) . " hotels\n";

    // ── HOTEL PRICES (weekday / weekend / holiday dates) ───────────────

    $grade_base_jpy = [
        'standard'  => 58000,
        'premium'   => 88000,
        'exclusive' => 135000,
    ];

    $area_factor = [
        1 => 1.08, // Hakuba
        2 => 1.18, // Niseko
        3 => 0.86, // Yuzawa
        4 => 1.00, // Nagano
    ];

    $weekday_days = wp_json_encode(['monday', 'tuesday', 'wednesday', 'thursday', 'friday']);
    $holiday_dates = wp_json_encode([
        '2026-12-24', '2026-12-25', '2026-12-26', '2026-12-27',
        '2026-12-28', '2026-12-29', '2026-12-30', '2026-12-31',
        '2027-01-01', '2027-01-02', '2027-01-03', '2027-02-11',
    ]);

    $inserted_prices = 0;
    $hotel_rows = $wpdb->get_results("SELECT id, area_id, grade FROM {$hotels_table} ORDER BY id ASC");

    foreach ($hotel_rows as $hotel_row) {
        $base = $grade_base_jpy[$hotel_row->grade] ?? 70000;
        $factor = $area_factor[intval($hotel_row->area_id)] ?? 1.00;

        $weekday_jpy = round($base * $factor);
        $weekend_jpy = round($weekday_jpy * 1.18);
        $holiday_jpy = round($weekday_jpy * 1.35);

        $weekday_usd = round($weekday_jpy * 0.0073);
        $weekend_usd = round($weekend_jpy * 0.0073);
        $holiday_usd = round($holiday_jpy * 0.0073);

        $rules = [
            [
                'category' => 'hotel',
                'hotel_id' => intval($hotel_row->id),
                'days' => null,
                'price_type' => 'selected_days',
                'weekdays_json' => $weekday_days,
                'dates_json' => null,
                'date_from' => null,
                'date_to' => null,
                'currency' => 'BOTH',
                'price_jpy' => $weekday_jpy,
                'price_usd' => $weekday_usd,
            ],
            [
                'category' => 'hotel',
                'hotel_id' => intval($hotel_row->id),
                'days' => null,
                'price_type' => 'weekend',
                'weekdays_json' => null,
                'dates_json' => null,
                'date_from' => null,
                'date_to' => null,
                'currency' => 'BOTH',
                'price_jpy' => $weekend_jpy,
                'price_usd' => $weekend_usd,
            ],
            [
                'category' => 'hotel',
                'hotel_id' => intval($hotel_row->id),
                'days' => null,
                'price_type' => 'specific_dates',
                'weekdays_json' => null,
                'dates_json' => $holiday_dates,
                'date_from' => null,
                'date_to' => null,
                'currency' => 'BOTH',
                'price_jpy' => $holiday_jpy,
                'price_usd' => $holiday_usd,
            ],
        ];

        foreach ($rules as $rule) {
            $wpdb->insert($prices_table, $rule);
            $inserted_prices++;
        }
    }

    echo "✓ Inserted " . $inserted_prices . " hotel price rules (weekday/weekend/holiday)\n";

    // ── ADDON PRICES ─────────────────────────────────────────────────────

    $addon_prices = [
        // Hakuba Valley
        ['area_id' => 1, 'category' => 'lift', 'grade' => 'standard', 'days' => 5, 'price_jpy' => 31000, 'price_usd' => 225],
        ['area_id' => 1, 'category' => 'lift', 'grade' => 'premium', 'days' => 5, 'price_jpy' => 31000, 'price_usd' => 225],
        ['area_id' => 1, 'category' => 'lift', 'grade' => 'exclusive', 'days' => 5, 'price_jpy' => 31000, 'price_usd' => 225],
        ['area_id' => 1, 'category' => 'gear', 'grade' => 'standard', 'days' => 5, 'price_jpy' => 42000, 'price_usd' => 305],
        ['area_id' => 1, 'category' => 'gear', 'grade' => 'premium', 'days' => 5, 'price_jpy' => 52000, 'price_usd' => 378],
        ['area_id' => 1, 'category' => 'gear', 'grade' => 'exclusive', 'days' => 5, 'price_jpy' => 62000, 'price_usd' => 451],
        ['area_id' => 1, 'category' => 'transport', 'grade' => 'standard', 'days' => null, 'price_jpy' => 24000, 'price_usd' => 174],
        ['area_id' => 1, 'category' => 'transport', 'grade' => 'premium', 'days' => null, 'price_jpy' => 28000, 'price_usd' => 204],
        ['area_id' => 1, 'category' => 'transport', 'grade' => 'exclusive', 'days' => null, 'price_jpy' => 32000, 'price_usd' => 233],
        
        // Niseko
        ['area_id' => 2, 'category' => 'lift', 'grade' => 'standard', 'days' => 5, 'price_jpy' => 33000, 'price_usd' => 240],
        ['area_id' => 2, 'category' => 'lift', 'grade' => 'premium', 'days' => 5, 'price_jpy' => 33000, 'price_usd' => 240],
        ['area_id' => 2, 'category' => 'lift', 'grade' => 'exclusive', 'days' => 5, 'price_jpy' => 33000, 'price_usd' => 240],
        ['area_id' => 2, 'category' => 'gear', 'grade' => 'standard', 'days' => 5, 'price_jpy' => 45000, 'price_usd' => 327],
        ['area_id' => 2, 'category' => 'gear', 'grade' => 'premium', 'days' => 5, 'price_jpy' => 55000, 'price_usd' => 400],
        ['area_id' => 2, 'category' => 'gear', 'grade' => 'exclusive', 'days' => 5, 'price_jpy' => 65000, 'price_usd' => 473],
        ['area_id' => 2, 'category' => 'transport', 'grade' => 'standard', 'days' => null, 'price_jpy' => 26000, 'price_usd' => 189],
        ['area_id' => 2, 'category' => 'transport', 'grade' => 'premium', 'days' => null, 'price_jpy' => 30000, 'price_usd' => 218],
        ['area_id' => 2, 'category' => 'transport', 'grade' => 'exclusive', 'days' => null, 'price_jpy' => 34000, 'price_usd' => 247],
        
        // Yuzawa Snow Park
        ['area_id' => 3, 'category' => 'lift', 'grade' => 'standard', 'days' => 5, 'price_jpy' => 28000, 'price_usd' => 204],
        ['area_id' => 3, 'category' => 'lift', 'grade' => 'premium', 'days' => 5, 'price_jpy' => 28000, 'price_usd' => 204],
        ['area_id' => 3, 'category' => 'lift', 'grade' => 'exclusive', 'days' => 5, 'price_jpy' => 28000, 'price_usd' => 204],
        ['area_id' => 3, 'category' => 'gear', 'grade' => 'standard', 'days' => 5, 'price_jpy' => 38000, 'price_usd' => 276],
        ['area_id' => 3, 'category' => 'gear', 'grade' => 'premium', 'days' => 5, 'price_jpy' => 48000, 'price_usd' => 349],
        ['area_id' => 3, 'category' => 'gear', 'grade' => 'exclusive', 'days' => 5, 'price_jpy' => 58000, 'price_usd' => 422],
        ['area_id' => 3, 'category' => 'transport', 'grade' => 'standard', 'days' => null, 'price_jpy' => 18000, 'price_usd' => 131],
        ['area_id' => 3, 'category' => 'transport', 'grade' => 'premium', 'days' => null, 'price_jpy' => 22000, 'price_usd' => 160],
        ['area_id' => 3, 'category' => 'transport', 'grade' => 'exclusive', 'days' => null, 'price_jpy' => 26000, 'price_usd' => 189],
        
        // Nagano
        ['area_id' => 4, 'category' => 'lift', 'grade' => 'standard', 'days' => 5, 'price_jpy' => 32000, 'price_usd' => 233],
        ['area_id' => 4, 'category' => 'lift', 'grade' => 'premium', 'days' => 5, 'price_jpy' => 32000, 'price_usd' => 233],
        ['area_id' => 4, 'category' => 'lift', 'grade' => 'exclusive', 'days' => 5, 'price_jpy' => 32000, 'price_usd' => 233],
        ['area_id' => 4, 'category' => 'gear', 'grade' => 'standard', 'days' => 5, 'price_jpy' => 40000, 'price_usd' => 291],
        ['area_id' => 4, 'category' => 'gear', 'grade' => 'premium', 'days' => 5, 'price_jpy' => 50000, 'price_usd' => 364],
        ['area_id' => 4, 'category' => 'gear', 'grade' => 'exclusive', 'days' => 5, 'price_jpy' => 60000, 'price_usd' => 436],
        ['area_id' => 4, 'category' => 'transport', 'grade' => 'standard', 'days' => null, 'price_jpy' => 20000, 'price_usd' => 145],
        ['area_id' => 4, 'category' => 'transport', 'grade' => 'premium', 'days' => null, 'price_jpy' => 25000, 'price_usd' => 182],
        ['area_id' => 4, 'category' => 'transport', 'grade' => 'exclusive', 'days' => null, 'price_jpy' => 30000, 'price_usd' => 218],
    ];

    foreach ($addon_prices as $addon) {
        $wpdb->insert($addon_prices_table, $addon);
    }
    echo "✓ Inserted " . count($addon_prices) . " addon prices\n";

    echo "\n✓ Example data import complete!\n";
    echo "Data includes:\n";
    echo "  • 4 ski resort areas (Hakuba, Niseko, Yuzawa, Nagano)\n";
    echo "  • 12 hotels across 3 grades (standard, premium, exclusive)\n";
    echo "  • Hotel pricing rules: weekday, weekend, and holiday specific dates\n";
    echo "  • Addon pricing for lift, gear, and transport by area and grade\n";
}

// Register as action hook for easy calling
add_action('spcu_import_example_data', 'spcu_import_example_data');
