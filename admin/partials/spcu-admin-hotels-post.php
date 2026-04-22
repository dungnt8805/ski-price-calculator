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

    $schema_columns = [
        'area_id' => 'INT NULL',
        'grade' => 'VARCHAR(50) NULL',
        'short_description' => 'VARCHAR(200) NULL',
        'description' => 'TEXT NULL',
        'facilities' => 'TEXT NULL',
        'featured_image' => 'INT NULL',
    ];
    $missing_columns = [];

    if($table_exists){
        foreach($schema_columns as $column => $definition){
            $exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", $column));
            if(!$exists){
                $wpdb->query("ALTER TABLE {$hotel_table} ADD COLUMN {$column} {$definition}");
                $exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", $column));
            }
            if(!$exists){
                $missing_columns[] = $column;
            }
        }
    }

    if(!$table_exists || !empty($missing_columns)){
        $msg = 'Database schema is not up to date for hotels table.';
        if(!empty($missing_columns)){
            $msg .= ' Missing: ' . implode(', ', $missing_columns) . '.';
        }
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
            ? 'Please choose both Area and Difficulty before saving a hotel.'
            : 'Please choose both Area and Difficulty before updating a hotel.';
        spcu_hotels_form_redirect('error', $msg, intval($_POST['hotel_id'] ?? 0));
    }

    $featured_image = absint($_POST['featured_image'] ?? 0);

    // Get facilities from form (array of term IDs)
    $facilities_terms = [];
    $facilities_raw = $_POST['spcu_facilities'] ?? '[]';
    try {
        $decoded = json_decode($facilities_raw, true);
        if(is_array($decoded)){
            $facilities_terms = array_filter(array_map('absint', $decoded));
        }
    } catch(Exception $e){}

    $data = [
        'name'              => sanitize_text_field($_POST['name'] ?? ''),
        'name_ja'           => sanitize_text_field($_POST['name_ja'] ?? ''),
        'short_description' => sanitize_textarea_field($_POST['short_description'] ?? ''),
        'description'       => wp_kses_post($_POST['description'] ?? ''),
        'address'           => sanitize_textarea_field($_POST['address'] ?? ''),
        'address_ja'        => sanitize_textarea_field($_POST['address_ja'] ?? ''),
        'featured_image'    => $featured_image > 0 ? $featured_image : null,
        'images'            => isset($_POST['images']) ? sanitize_text_field($_POST['images']) : '',
        'area_id'           => $area_id,
        'grade'             => $grade,
        'is_featured'       => isset($_POST['is_featured']) ? 1 : 0,
    ];

    if($is_add){
        $ok = $wpdb->insert($hotel_table, $data);
        if($ok === false){
            $msg = 'Could not save hotel. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
            spcu_hotels_form_redirect('error', $msg);
        }

        $hotel_id = intval($wpdb->insert_id);
        
        // Create shadow WordPress post for taxonomy support
        $post_id = wp_insert_post([
            'ID' => $hotel_id,
            'post_type' => 'spcu_hotel',
            'post_status' => 'publish',
            'post_title' => sanitize_text_field($_POST['name'] ?? ''),
        ], false);
        
        // Assign facilities to the post
        if($post_id && !empty($facilities_terms)){
            wp_set_object_terms($post_id, $facilities_terms, 'spcu_facility', false);
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

    // Update shadow WordPress post for taxonomy support
    wp_update_post([
        'ID' => $hotel_id,
        'post_title' => sanitize_text_field($_POST['name'] ?? ''),
    ]);
    
    // Update facilities assignment
    wp_set_object_terms($hotel_id, $facilities_terms, 'spcu_facility', false);

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
