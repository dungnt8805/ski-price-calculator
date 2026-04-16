<?php
if (!defined('ABSPATH')) exit;

if(!function_exists('spcu_handle_areas_post')){
function spcu_handle_areas_post(){

    // chỉ chạy trong admin
    if (!is_admin()) return;

    // chỉ chạy khi POST
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;

    // chỉ chạy đúng page
    if (!isset($_GET['page']) || $_GET['page'] !== 'spcu-areas') return;

    // nonce check
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'],'spcu_save_area')){
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix.'spcu_areas';

    $data = [
        'type'    => sanitize_text_field($_POST['type']),
        'name'    => sanitize_text_field($_POST['name']),
        'name_ja' => sanitize_text_field($_POST['name_ja'])
    ];

    $area_id = intval($_POST['area_id'] ?? 0);

    if ($area_id > 0){
        $ok  = $wpdb->update($table,$data,['id'=>$area_id]);
        $msg = 'Area updated successfully.';
    } else {
        $ok  = $wpdb->insert($table,$data);
        $msg = 'Area added successfully.';
    }

    if ($ok !== false){
        wp_safe_redirect(add_query_arg([
            'page'=>'spcu-areas',
            'spcu_toast'=>'success',
            'spcu_msg'=>rawurlencode($msg)
        ], admin_url('admin.php')));
        exit;
    }

    wp_safe_redirect(add_query_arg([
        'page'=>'spcu-areas',
        'spcu_toast'=>'error',
        'spcu_msg'=>rawurlencode($wpdb->last_error ?: 'Database error')
    ], admin_url('admin.php')));
    exit;
}}