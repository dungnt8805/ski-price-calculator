<?php
if (!defined('ABSPATH')) exit;

if(!function_exists('spcu_difficulties_redirect')){
function spcu_difficulties_redirect($toast_type, $message, $edit_slug = ''){
    $args = [
        'page' => 'spcu-difficulties',
        'spcu_toast' => $toast_type,
        'spcu_msg' => rawurlencode($message),
    ];

    if($edit_slug !== ''){
        $args['edit'] = sanitize_key($edit_slug);
    }

    wp_safe_redirect(add_query_arg($args, admin_url('admin.php')));
    exit;
}
}

if(!function_exists('spcu_handle_difficulties_post')){
function spcu_handle_difficulties_post(){
    if (!is_admin()) return;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;

    $page = sanitize_text_field($_GET['page'] ?? $_POST['page'] ?? '');
    if ($page !== 'spcu-difficulties') return;

    $is_add = isset($_POST['add_difficulty']);
    $is_edit = isset($_POST['edit_difficulty']);
    if(!$is_add && !$is_edit) return;

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'spcu_save_difficulty')){
        return;
    }

    $name = sanitize_text_field($_POST['name'] ?? '');
    $slug = sanitize_title($_POST['slug'] ?? $name);
    $description = sanitize_textarea_field($_POST['description'] ?? '');
    $color = sanitize_text_field($_POST['color'] ?? '#111111');
    $original_slug = sanitize_key($_POST['original_slug'] ?? '');

    if($name === '' || $slug === ''){
        spcu_difficulties_redirect('error', 'Difficulty name and slug are required.', $original_slug);
    }

    if(!preg_match('/^#([0-9a-fA-F]{6})$/', $color)){
        spcu_difficulties_redirect('error', 'Please select a valid hex color.', $original_slug ?: $slug);
    }

    $records = SPCU_Difficulties::records();
    $updated_records = [];
    $slug_exists = false;
    $edited = false;

    foreach($records as $record){
        if($record['slug'] === $slug && $record['slug'] !== $original_slug){
            $slug_exists = true;
        }
    }

    if($slug_exists){
        spcu_difficulties_redirect('error', 'That difficulty slug already exists.', $original_slug ?: $slug);
    }

    foreach($records as $record){
        if($is_edit && $record['slug'] === $original_slug){
            $updated_records[] = [
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
                'color' => strtolower($color),
            ];
            $edited = true;
            continue;
        }

        $updated_records[] = $record;
    }

    if($is_add){
        $updated_records[] = [
            'name' => $name,
            'slug' => $slug,
            'description' => $description,
            'color' => strtolower($color),
        ];
    }

    if($is_edit && !$edited){
        spcu_difficulties_redirect('error', 'Difficulty not found.');
    }

    SPCU_Difficulties::save_records($updated_records);

    if($is_edit && $original_slug !== '' && $original_slug !== $slug){
        SPCU_Difficulties::rename_references($original_slug, $slug);
    }

    spcu_difficulties_redirect('success', $is_add ? 'Difficulty added successfully.' : 'Difficulty updated successfully.');
}
}

if(!function_exists('spcu_handle_difficulties_delete')){
function spcu_handle_difficulties_delete(){
    if (!is_admin()) return;

    $page = sanitize_text_field($_GET['page'] ?? '');
    if ($page !== 'spcu-difficulties') return;

    $delete_slug = sanitize_key($_GET['delete'] ?? '');
    if($delete_slug === '') return;

    $nonce_action = 'spcu_delete_difficulty_' . $delete_slug;
    if(!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], $nonce_action)){
        return;
    }

    $records = SPCU_Difficulties::records();
    if(count($records) <= 1){
        spcu_difficulties_redirect('error', 'At least one difficulty must remain.');
    }

    if(SPCU_Difficulties::usage_count($delete_slug) > 0){
        spcu_difficulties_redirect('error', 'This difficulty is currently used by one or more areas. Edit those area records first or rename the difficulty instead.');
    }

    $updated_records = [];
    foreach($records as $record){
        if($record['slug'] !== $delete_slug){
            $updated_records[] = $record;
        }
    }

    SPCU_Difficulties::save_records($updated_records);
    spcu_difficulties_redirect('success', 'Difficulty deleted successfully.');
}
}