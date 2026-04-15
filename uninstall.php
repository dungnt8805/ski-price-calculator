<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
global $wpdb;

$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spcu_areas");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spcu_hotels");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spcu_grades");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spcu_prices");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}spcu_addon_prices");