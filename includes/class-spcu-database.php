<?php
class SPCU_Database {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        require_once(ABSPATH.'wp-admin/includes/upgrade.php');

        $areas = "CREATE TABLE {$wpdb->prefix}spcu_areas(
            id INT AUTO_INCREMENT PRIMARY KEY,
            type VARCHAR(50) NOT NULL,
            name VARCHAR(200) NOT NULL,
            name_ja VARCHAR(200) NULL,
            short_description VARCHAR(255) NULL,
            description TEXT NULL,
            featured_image INT NULL,
            images TEXT NULL
        ) $charset;";

        $hotels = "CREATE TABLE {$wpdb->prefix}spcu_hotels(
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            name_ja VARCHAR(200) NULL,
            short_description VARCHAR(255) NULL,
            description TEXT NULL,
            facilities TEXT NULL,
            address VARCHAR(500) NULL,
            address_ja VARCHAR(500) NULL,
            featured_image INT NULL,
            images TEXT NULL,
            area_id INT NULL,
            grade VARCHAR(50) NULL
        ) $charset;";
        $prices = "CREATE TABLE {$wpdb->prefix}spcu_prices(
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50) NOT NULL,
            hotel_id INT NULL,
            area_id INT NULL,
            days INT NULL,
            price_type VARCHAR(20) NOT NULL DEFAULT 'selected_days',
            weekdays_json TEXT NULL,
            dates_json TEXT NULL,
            date_from DATE NULL,
            date_to DATE NULL,
            currency VARCHAR(3) NOT NULL DEFAULT 'JPY',
            price_jpy DECIMAL(10,2) NULL,
            price_usd DECIMAL(10,2) NULL,
            price_min_jpy DECIMAL(10,2) NULL,
            price_max_jpy DECIMAL(10,2) NULL,
            price_min_usd DECIMAL(10,2) NULL,
            price_max_usd DECIMAL(10,2) NULL,
            child_price_jpy DECIMAL(10,2) NULL,
            child_price_usd DECIMAL(10,2) NULL,
            infant_price_jpy DECIMAL(10,2) NULL,
            infant_price_usd DECIMAL(10,2) NULL
        ) $charset;";

        $addon_prices = "CREATE TABLE {$wpdb->prefix}spcu_addon_prices(
            id INT AUTO_INCREMENT PRIMARY KEY,
            category VARCHAR(50) NOT NULL,
            area_id INT NULL,
            grade VARCHAR(50) NULL,
            days INT NULL,
            price_type VARCHAR(20) NOT NULL DEFAULT 'selected_days',
            weekdays_json TEXT NULL,
            dates_json TEXT NULL,
            date_from DATE NULL,
            date_to DATE NULL,
            currency VARCHAR(3) NOT NULL DEFAULT 'JPY',
            price_jpy DECIMAL(10,2) NULL,
            price_usd DECIMAL(10,2) NULL
        ) $charset;";

        dbDelta($areas);
        dbDelta($hotels);
        dbDelta($prices);
        dbDelta($addon_prices);
    }
}