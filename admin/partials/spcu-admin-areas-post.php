<?php
if (!defined('ABSPATH')) exit;

if(!function_exists('spcu_handle_areas_post')){
function spcu_handle_areas_post(){

    // chỉ chạy trong admin
    if (!is_admin()) return;

    // chỉ chạy khi POST
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;

    // chỉ chạy đúng page
    $page = $_GET['page'] ?? '';
    if (!in_array($page, ['spcu-areas', 'spcu-area-form'], true)) return;

    // nonce check
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'],'spcu_save_area')){
        return;
    }

    global $wpdb;
    $table = $wpdb->prefix.'spcu_areas';

    $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    if(!$table_exists && class_exists('SPCU_Database')){
        SPCU_Database::create_tables();
        $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
    }

    $schema_columns = [
        'prefecture_id' => 'INT NULL',
        'slug' => 'VARCHAR(200) NULL',
        'short_description' => 'VARCHAR(200) NULL',
        'description' => 'TEXT NULL',
        'featured_image' => 'INT NULL',
        'images' => 'TEXT NULL',
        'total_runs' => 'INT NULL',
        'max_vertical' => 'INT NULL',
        'total_resorts' => 'INT NULL',
        'season' => 'VARCHAR(100) NULL',
        'summit' => 'INT NULL',
        'distance' => 'VARCHAR(100) NULL',
        'difficulties_json' => 'TEXT NULL',
        'featured_badge' => 'VARCHAR(100) NULL',
        'area_tags' => 'TEXT NULL',
    ];

    if($table_exists){
        foreach($schema_columns as $column => $definition){
            $exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
            if(!$exists){
                $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
            }
        }
    }

    $slug = sanitize_title(wp_unslash($_POST['slug'] ?? ''));
    if(empty($slug)){
        $slug = sanitize_title($_POST['name'] ?? '');
    }

    $data = [
        'prefecture_id' => ($pref_id = intval($_POST['prefecture_id'] ?? 0)) > 0 ? $pref_id : null,
        'type' => sanitize_text_field($_POST['type'] ?? ''),
        'name' => sanitize_text_field($_POST['name'] ?? ''),
        'name_ja' => sanitize_text_field($_POST['name_ja'] ?? ''),
        'slug' => $slug,
        'short_description' => sanitize_textarea_field($_POST['short_description'] ?? ''),
        'description' => wp_kses_post($_POST['description'] ?? ''),
        'featured_image' => ($featured_image = absint($_POST['featured_image'] ?? 0)) > 0 ? $featured_image : null,
        'images' => sanitize_text_field($_POST['images'] ?? ''),
        'total_runs' => isset($_POST['total_runs']) && $_POST['total_runs'] !== '' ? intval($_POST['total_runs']) : null,
        'max_vertical' => isset($_POST['max_vertical']) && $_POST['max_vertical'] !== '' ? intval($_POST['max_vertical']) : null,
        'total_resorts' => isset($_POST['total_resorts']) && $_POST['total_resorts'] !== '' ? intval($_POST['total_resorts']) : null,
        'season' => sanitize_text_field($_POST['season'] ?? ''),
        'summit' => isset($_POST['summit']) && $_POST['summit'] !== '' ? intval($_POST['summit']) : null,
        'distance' => sanitize_text_field($_POST['distance'] ?? ''),
        'featured_badge' => sanitize_text_field($_POST['featured_badge'] ?? ''),
    ];

    // Handle area tags
    if(isset($_POST['area_tags'])){
        $tags_raw = sanitize_text_field($_POST['area_tags'] ?? '');
        if(!empty($tags_raw)){
            $tags_array = array_filter(array_map('trim', explode(",", $tags_raw)));
            $data['area_tags'] = !empty($tags_array) ? wp_json_encode(array_values($tags_array)) : null;
        } else {
            $data['area_tags'] = null;
        }
    }

    if(isset($_POST['difficulties']) && is_array($_POST['difficulties'])){
        $diffs = [];
        foreach($_POST['difficulties'] as $k => $v){
            if($v !== ''){
                $diffs[sanitize_key($k)] = intval($v);
            }
        }
        $data['difficulties_json'] = empty($diffs) ? null : wp_json_encode($diffs);
    } else {
        $data['difficulties_json'] = null;
    }

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