<?php
if (!defined('ABSPATH')) exit;

if(!function_exists('spcu_handle_prices_post')){
function spcu_handle_prices_post(){

    if (!is_admin()) return;
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') return;
    if (!isset($_POST['add_price'])) return;

    $page = sanitize_text_field($_GET['page'] ?? '');
    if (!in_array($page, ['spcu-hotel-prices', 'spcu-addon-prices'], true)) return;

    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'spcu_save_price_rule')){
        return;
    }

    global $wpdb;

    $page_mode = ($page === 'spcu-addon-prices') ? 'addon' : 'hotel';
    $db_table = $page_mode === 'hotel' ? $wpdb->prefix.'spcu_prices' : $wpdb->prefix.'spcu_addon_prices';
    $selected_hotel_id = intval($_POST['hotel'] ?? ($_GET['hotel'] ?? 0));

    $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $db_table));
    if(!$table_exists){
        if(class_exists('SPCU_Database')){
            SPCU_Database::create_tables();
            $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $db_table));
        }
    }

    if(!$table_exists){
        spcu_redirect_price_post_result($page_mode, $selected_hotel_id, 'error', 'Database table is missing for this page.');
    }

    $required_columns_all = [
        'child_price_jpy' => "DECIMAL(10,2) NULL",
        'child_price_usd' => "DECIMAL(10,2) NULL",
        'infant_price_jpy' => "DECIMAL(10,2) NULL",
        'infant_price_usd' => "DECIMAL(10,2) NULL",
    ];

    foreach($required_columns_all as $column => $definition){
        $exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$db_table} LIKE %s", $column));
        if(!$exists){
            $wpdb->query("ALTER TABLE {$db_table} ADD COLUMN {$column} {$definition}");
        }
    }

    if($page_mode === 'addon'){
        $required_columns = [
            'grade'         => "VARCHAR(50) NULL",
            'price_type'    => "VARCHAR(20) NOT NULL DEFAULT 'selected_days'",
            'weekdays_json' => "TEXT NULL",
            'dates_json'    => "TEXT NULL",
            'date_from'     => "DATE NULL",
            'date_to'       => "DATE NULL",
        ];

        foreach($required_columns as $column => $definition){
            $exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$db_table} LIKE %s", $column));
            if(!$exists){
                $wpdb->query("ALTER TABLE {$db_table} ADD COLUMN {$column} {$definition}");
                $exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$db_table} LIKE %s", $column));
            }

            if(!$exists){
                $msg = 'Database schema is not up to date for addon prices table.';
                if($wpdb->last_error){
                    $msg .= ' ' . $wpdb->last_error;
                }
                spcu_redirect_price_post_result($page_mode, $selected_hotel_id, 'error', $msg);
            }
        }
    }

    $post_category = sanitize_text_field($_POST['category'] ?? '');
    $hotel_id   = !empty($_POST['hotel']) ? intval($_POST['hotel']) : ($selected_hotel_id ?: null);
    $area_id    = !empty($_POST['area'])  ? intval($_POST['area'])  : null;
    $days       = !empty($_POST['days'])  ? intval($_POST['days'])  : null;
    $price_type = sanitize_text_field($_POST['price_type'] ?? 'selected_days');
    $addon_grade = SPCU_Grades::normalize($_POST['grade'] ?? '');

    if($page_mode === 'hotel'){
        if(!$hotel_id || $hotel_id <= 0){
            spcu_redirect_price_post_result($page_mode, $selected_hotel_id, 'error', 'Please open Hotel Prices from a specific hotel in the Hotels list.');
        }

        $hotel_exists = (bool) $wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->prefix}spcu_hotels WHERE id = %d", $hotel_id));
        if(!$hotel_exists){
            spcu_redirect_price_post_result($page_mode, $selected_hotel_id, 'error', 'Selected hotel was not found.');
        }
    }

    if($page_mode === 'addon'){
        if(!$area_id || $area_id <= 0){
            spcu_redirect_price_post_result($page_mode, $selected_hotel_id, 'error', 'Please select Area for add-on prices.');
        }
        if(in_array($post_category, ['lift','gear'], true) && (!$days || $days <= 0)){
            spcu_redirect_price_post_result($page_mode, $selected_hotel_id, 'error', 'Lift and Gear prices require Days greater than 0.');
        }
        if($post_category === 'transport' && $addon_grade === ''){
            spcu_redirect_price_post_result($page_mode, $selected_hotel_id, 'error', 'Transport prices require Grade selection.');
        }
    }

    $days_of_week = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];

    $weekdays_json = null;
    if($price_type === 'selected_days'){
        $sel = array_intersect($_POST['weekdays'] ?? [], $days_of_week);
        $weekdays_json = !empty($sel) ? wp_json_encode(array_values($sel)) : null;
    }

    $dates_json = null;
    if($price_type === 'specific_dates'){
        $raw = array_filter(array_map('trim', explode(',', $_POST['specific_dates'] ?? '')));
        $clean = [];
        foreach($raw as $d){
            $ts = strtotime($d);
            if($ts){
                $clean[] = date('Y-m-d', $ts);
            }
        }
        $dates_json = !empty($clean) ? wp_json_encode(array_values($clean)) : null;
    }

    $date_from = ($price_type === 'date_range' && !empty($_POST['date_from'])) ? sanitize_text_field($_POST['date_from']) : null;
    $date_to   = ($price_type === 'date_range' && !empty($_POST['date_to']))   ? sanitize_text_field($_POST['date_to'])   : null;

    $jpy = !empty($_POST['currency_jpy']);
    $usd = !empty($_POST['currency_usd']);
    $currency = ($jpy && $usd) ? 'BOTH' : ($usd ? 'USD' : 'JPY');

    $data = [
        'category'      => $post_category,
        'area_id'       => $area_id,
        'days'          => $days,
        'price_type'    => $price_type,
        'weekdays_json' => $weekdays_json,
        'dates_json'    => $dates_json,
        'date_from'     => $date_from,
        'date_to'       => $date_to,
        'currency'      => $currency,
        'price_jpy'     => ($_POST['price_jpy'] ?? '') !== '' ? floatval($_POST['price_jpy']) : null,
        'price_usd'     => ($_POST['price_usd'] ?? '') !== '' ? floatval($_POST['price_usd']) : null,
        'child_price_jpy' => ($_POST['child_price_jpy'] ?? '') !== '' ? floatval($_POST['child_price_jpy']) : null,
        'child_price_usd' => ($_POST['child_price_usd'] ?? '') !== '' ? floatval($_POST['child_price_usd']) : null,
        'infant_price_jpy' => ($_POST['infant_price_jpy'] ?? '') !== '' ? floatval($_POST['infant_price_jpy']) : null,
        'infant_price_usd' => ($_POST['infant_price_usd'] ?? '') !== '' ? floatval($_POST['infant_price_usd']) : null,
    ];

    if($page_mode === 'hotel'){
        $data['hotel_id'] = $hotel_id;
    } else {
        $data['grade'] = $addon_grade;
    }

    $ok = $wpdb->insert($db_table, $data);
    if($ok === false){
        $msg = 'Could not save price rule. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
        spcu_redirect_price_post_result($page_mode, $selected_hotel_id, 'error', $msg);
    }

    spcu_redirect_price_post_result($page_mode, $selected_hotel_id, 'success', 'Price rule saved successfully.');
}
}

if(!function_exists('spcu_handle_prices_delete')){
function spcu_handle_prices_delete(){

    if (!is_admin()) return;

    $page = sanitize_text_field($_GET['page'] ?? '');
    if (!in_array($page, ['spcu-hotel-prices', 'spcu-addon-prices'], true)) return;

    $delete_id = intval($_GET['delete'] ?? 0);
    if($delete_id <= 0) return;

    $nonce_action = 'spcu_delete_price_rule_' . $delete_id;
    if(!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], $nonce_action)){
        return;
    }

    global $wpdb;

    $page_mode = ($page === 'spcu-addon-prices') ? 'addon' : 'hotel';
    $db_table = $page_mode === 'hotel' ? $wpdb->prefix.'spcu_prices' : $wpdb->prefix.'spcu_addon_prices';
    $selected_hotel_id = intval($_GET['hotel'] ?? 0);

    $ok = $wpdb->delete($db_table, ['id' => $delete_id]);
    if($ok === false){
        $msg = 'Could not delete price rule. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
        spcu_redirect_price_post_result($page_mode, $selected_hotel_id, 'error', $msg);
    }

    spcu_redirect_price_post_result($page_mode, $selected_hotel_id, 'success', 'Price rule deleted successfully.');
}
}

if(!function_exists('spcu_redirect_price_post_result')){
function spcu_redirect_price_post_result($page_mode, $selected_hotel_id, $toast_type, $message){
    $redirect_args = [
        'page' => $page_mode === 'hotel' ? 'spcu-hotel-prices' : 'spcu-addon-prices',
        'spcu_toast' => $toast_type,
        'spcu_msg' => rawurlencode($message),
    ];

    if($page_mode === 'hotel' && $selected_hotel_id){
        $redirect_args['hotel'] = intval($selected_hotel_id);
    }

    wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
    exit;
}
}
