<?php
if (!defined('ABSPATH')) exit;

if(!function_exists('spcu_hotels_form_redirect')){
function spcu_hotels_form_redirect($toast_type, $message, $edit_id = 0){
    $args = [
        'page' => 'spcu-hotel-form',
        'spcu_toast' => $toast_type,
        'spcu_msg' => rawurlencode($message),
    ];

    if($edit_id > 0){
        $args['edit'] = intval($edit_id);
    }

    wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
    exit;
}
}

if(!function_exists('spcu_handle_hotels_post')){
function spcu_handle_hotels_post(){

    if (!is_admin()) return;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;

    $page = sanitize_text_field($_GET['page'] ?? '');
    if ($page !== 'spcu-hotel-form') return;

    $is_add = isset($_POST['add_hotel']);
    $is_edit = isset($_POST['edit_hotel']);
    if(!$is_add && !$is_edit) return;

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'spcu_save_hotel')){
        return;
    }

    global $wpdb;
    $hotel_table = $wpdb->prefix.'spcu_hotels';

    $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $hotel_table));
    if(!$table_exists && class_exists('SPCU_Database')){
        SPCU_Database::create_tables();
        $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $hotel_table));
    }

    $area_column_exists = false;
    $grade_column_exists = false;

    if($table_exists){
        $area_column_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", 'area_id'));
        $grade_column_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", 'grade'));

        if(!$area_column_exists){
            $wpdb->query("ALTER TABLE {$hotel_table} ADD COLUMN area_id INT NULL");
            $area_column_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", 'area_id'));
        }

        if(!$grade_column_exists){
            $wpdb->query("ALTER TABLE {$hotel_table} ADD COLUMN grade VARCHAR(50) NULL");
            $grade_column_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", 'grade'));
        }
    }

    if(!$table_exists || !$area_column_exists || !$grade_column_exists){
        $msg = 'Database schema is not up to date for hotels table.';
        if($wpdb->last_error){
            $msg .= ' ' . $wpdb->last_error;
        }
        spcu_hotels_form_redirect('error', $msg, intval($_POST['hotel_id'] ?? 0));
    }

    $areas_count = intval($wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}spcu_areas"));
    if($areas_count <= 0){
        $msg = $is_add
            ? 'Please create at least one Area before adding a hotel.'
            : 'Please create at least one Area before updating a hotel.';
        spcu_hotels_form_redirect('error', $msg, intval($_POST['hotel_id'] ?? 0));
    }

    $area_id = intval($_POST['area_id'] ?? 0);
    $grade   = SPCU_Grades::normalize($_POST['grade'] ?? '');

    if($area_id <= 0 || $grade === ''){
        $msg = $is_add
            ? 'Please choose both Area and Grade before saving a hotel.'
            : 'Please choose both Area and Grade before updating a hotel.';
        spcu_hotels_form_redirect('error', $msg, intval($_POST['hotel_id'] ?? 0));
    }

    $data = [
        'name'       => sanitize_text_field($_POST['name'] ?? ''),
        'name_ja'    => sanitize_text_field($_POST['name_ja'] ?? ''),
        'address'    => sanitize_textarea_field($_POST['address'] ?? ''),
        'address_ja' => sanitize_textarea_field($_POST['address_ja'] ?? ''),
        'images'     => isset($_POST['images']) ? sanitize_text_field($_POST['images']) : '',
        'area_id'    => $area_id,
        'grade'      => $grade,
    ];

    if($is_add){
        $ok = $wpdb->insert($hotel_table, $data);
        if($ok === false){
            $msg = 'Could not save hotel. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
            spcu_hotels_form_redirect('error', $msg);
        }

        wp_safe_redirect(add_query_arg([
            'page' => 'spcu-hotels',
            'spcu_toast' => 'success',
            'spcu_msg' => rawurlencode('Hotel added successfully.')
        ], admin_url('admin.php')));
        exit;
    }

    $hotel_id = intval($_POST['hotel_id'] ?? 0);
    if($hotel_id <= 0){
        spcu_hotels_form_redirect('error', 'Invalid hotel ID for update.');
    }

    $ok = $wpdb->update($hotel_table, $data, ['id' => $hotel_id]);
    if($ok === false){
        $msg = 'Could not update hotel. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
        spcu_hotels_form_redirect('error', $msg, $hotel_id);
    }

    wp_safe_redirect(add_query_arg([
        'page' => 'spcu-hotels',
        'spcu_toast' => 'success',
        'spcu_msg' => rawurlencode('Hotel updated successfully.')
    ], admin_url('admin.php')));
    exit;
}
}

if(!function_exists('spcu_handle_hotels_delete')){
function spcu_handle_hotels_delete(){

    if (!is_admin()) return;

    $page = sanitize_text_field($_GET['page'] ?? '');
    if ($page !== 'spcu-hotels') return;

    $delete_id = intval($_GET['delete'] ?? 0);
    if($delete_id <= 0) return;

    $nonce_action = 'spcu_delete_hotel_' . $delete_id;
    if(!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], $nonce_action)){
        return;
    }

    global $wpdb;
    $hotel_table = $wpdb->prefix.'spcu_hotels';

    $ok = $wpdb->delete($hotel_table, ['id' => $delete_id]);
    if($ok === false){
        $msg = 'Could not delete hotel. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
        wp_safe_redirect(add_query_arg([
            'page' => 'spcu-hotels',
            'spcu_toast' => 'error',
            'spcu_msg' => rawurlencode($msg)
        ], admin_url('admin.php')));
        exit;
    }

    wp_safe_redirect(add_query_arg([
        'page' => 'spcu-hotels',
        'spcu_toast' => 'success',
        'spcu_msg' => rawurlencode('Hotel deleted successfully.')
    ], admin_url('admin.php')));
    exit;
}
}
