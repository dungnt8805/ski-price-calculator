<?php
if (!defined('ABSPATH')) exit;

if(!function_exists('spcu_handle_prefectures_post')){
function spcu_handle_prefectures_post(){

    // chỉ chạy trong admin
    if (!is_admin()) return;

    // chỉ chạy khi POST
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;

    // chỉ chạy đúng page
    if (!isset($_GET['page']) || $_GET['page'] !== 'spcu-prefectures') return;

    // nonce check
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'],'spcu_save_prefecture')){
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix.'spcu_prefectures';

    $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if(!$table_exists && class_exists('SPCU_Database')){
        SPCU_Database::create_tables();
        $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    }

    $schema_columns = [
        'short_description' => 'VARCHAR(200) NULL',
        'description' => 'TEXT NULL',
        'featured_image' => 'INT NULL',
        'images' => 'TEXT NULL',
    ];

    if($table_exists){
        foreach($schema_columns as $column => $definition){
            $exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
            if(!$exists){
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        }
    }

    $data = [
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'name_ja' => sanitize_text_field($_POST['name_ja'] ?? ''),
        'short_description' => sanitize_textarea_field($_POST['short_description'] ?? ''),
        'description' => wp_kses_post($_POST['description'] ?? ''),
        'featured_image' => ($featured_image = absint($_POST['featured_image'] ?? 0)) > 0 ? $featured_image : null,
        'images' => sanitize_text_field($_POST['images'] ?? ''),
    ];

    $prefecture_id = intval($_POST['prefecture_id'] ?? 0);

    if ($prefecture_id > 0){
        $ok  = $wpdb->update($table,$data,['id'=>$prefecture_id]);
        $msg = 'Prefecture updated successfully.';
    } else {
        $ok  = $wpdb->insert($table,$data);
        $msg = 'Prefecture added successfully.';
    }

    if ($ok !== false){
        wp_safe_redirect(add_query_arg([
            'page'=>'spcu-prefectures',
            'spcu_toast'=>'success',
            'spcu_msg'=>rawurlencode($msg)
        ], admin_url('admin.php')));
        exit;
    }

    wp_safe_redirect(add_query_arg([
        'page'=>'spcu-prefectures',
        'spcu_toast'=>'error',
        'spcu_msg'=>rawurlencode($wpdb->last_error ?: 'Database error')
    ], admin_url('admin.php')));
    exit;
}}