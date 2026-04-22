<?php
class SPCU_Database {

    public static function create_tables() {
        global $wpdb;
        $charset = $wpdb->get_charset_collate();

        require_once(ABSPATH.'wp-admin/includes/upgrade.php');

        $prefectures = "CREATE TABLE {$wpdb->prefix}spcu_prefectures(
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            name_ja VARCHAR(200) NULL,
            short_description VARCHAR(200) NULL,
            description TEXT NULL,
            featured_image INT NULL,
            images TEXT NULL,
            is_featured TINYINT(1) NOT NULL DEFAULT 0
        ) $charset;";

        $areas = "CREATE TABLE {$wpdb->prefix}spcu_areas(
            id INT AUTO_INCREMENT PRIMARY KEY,
            prefecture_id INT NULL,
            type VARCHAR(50) NOT NULL,
            name VARCHAR(200) NOT NULL,
            name_ja VARCHAR(200) NULL,
            short_description VARCHAR(200) NULL,
            description TEXT NULL,
            featured_image INT NULL,
            images TEXT NULL,
            total_runs INT NULL,
            max_vertical INT NULL,
            total_resorts INT NULL,
            season VARCHAR(100) NULL,
            summit INT NULL,
            distance VARCHAR(100) NULL,
            difficulties_json TEXT NULL,
            featured_badge VARCHAR(100) NULL,
            area_tags TEXT NULL
        ) $charset;";

        $hotels = "CREATE TABLE {$wpdb->prefix}spcu_hotels(
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(200) NOT NULL,
            name_ja VARCHAR(200) NULL,
            slug VARCHAR(200) NULL,
            short_description VARCHAR(200) NULL,
            description TEXT NULL,
            facilities TEXT NULL,
            address VARCHAR(500) NULL,
            address_ja VARCHAR(500) NULL,
            featured_image INT NULL,
            images TEXT NULL,
            area_id INT NULL,
            grade VARCHAR(50) NULL,
            is_featured TINYINT(1) NOT NULL DEFAULT 0
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

        dbDelta($prefectures);
        dbDelta($areas);
        dbDelta($hotels);
        dbDelta($prices);
        dbDelta($addon_prices);
    }
}