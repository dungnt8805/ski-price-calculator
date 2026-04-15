<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$page_mode     = (isset($_GET['page']) && $_GET['page'] === 'spcu-addon-prices') ? 'addon' : 'hotel';
$page_title    = $page_mode === 'hotel' ? 'Hotel Prices' : 'Addon Prices (Lift, Gear, Transport)';
$category_sql  = $page_mode === 'hotel' ? "p.category = 'hotel'" : "p.category != 'hotel'";
$selected_hotel_id = ($page_mode === 'hotel' && isset($_GET['hotel'])) ? intval($_GET['hotel']) : 0;

if(!function_exists('spcu_admin_breadcrumb')){
    function spcu_admin_breadcrumb($items){
        echo '<nav class="spcu-breadcrumb" aria-label="Breadcrumb">';
        $last = count($items) - 1;
        foreach($items as $i => $item){
            if($i > 0) echo '<span class="spcu-breadcrumb-sep">/</span>';
            if(!empty($item['url']) && $i !== $last){
                echo '<a href="'.esc_url($item['url']).'">'.esc_html($item['label']).'</a>';
            } else {
                echo '<span class="current">'.esc_html($item['label']).'</span>';
            }
        }
        echo '</nav>';
    }
}

$areas  = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spcu_areas");
$hotels = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spcu_hotels ORDER BY name ASC");
$grade_options = SPCU_Grades::options();

$db_table = $page_mode === 'hotel' ? $wpdb->prefix.'spcu_prices' : $wpdb->prefix.'spcu_addon_prices';
$price_form_error = '';
$selected_hotel = null;

if($page_mode === 'hotel'){
    if($selected_hotel_id <= 0){
        $price_form_error = 'Please open Hotel Prices from a specific hotel in the Hotels list.';
    } else {
        $selected_hotel = $wpdb->get_row($wpdb->prepare("SELECT h.*, a.name as area_name FROM {$wpdb->prefix}spcu_hotels h LEFT JOIN {$wpdb->prefix}spcu_areas a ON a.id = h.area_id WHERE h.id = %d", $selected_hotel_id));
        if(!$selected_hotel){
            $price_form_error = 'Selected hotel was not found.';
        }
    }
}

// Backward-compat: ensure prices tables include required v2 columns.
$table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $db_table));
if(!$table_exists && class_exists('SPCU_Database')){
    SPCU_Database::create_tables();
    $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $db_table));
}

if(!$table_exists){
    $price_form_error = 'Database table is missing for this page.';
}

if($price_form_error === '' && $page_mode === 'addon'){
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
            $price_form_error = 'Database schema is not up to date for addon prices table.';
            if($wpdb->last_error){
                $price_form_error .= ' ' . $wpdb->last_error;
            }
            break;
        }
    }
}

$days_of_week = ['monday','tuesday','wednesday','thursday','friday','saturday','sunday'];
$day_labels   = ['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'];

/* ── Handle Add ──────────────────────────────────────────────────── */
if(isset($_POST['add_price'])){

    if($price_form_error !== ''){
        // Keep schema error visible; skip insert.
    } else {

    $post_category = sanitize_text_field($_POST['category'] ?? '');
    $hotel_id   = !empty($_POST['hotel']) ? intval($_POST['hotel']) : ($selected_hotel_id ?: null);
    $area_id    = !empty($_POST['area'])  ? intval($_POST['area'])  : null;
    $days       = !empty($_POST['days'])  ? intval($_POST['days'])  : null;
    $price_type = sanitize_text_field($_POST['price_type'] ?? 'selected_days');
    $addon_grade = SPCU_Grades::normalize($_POST['grade'] ?? '');

    if($page_mode === 'addon'){
        if(!$area_id || $area_id <= 0){
            $price_form_error = 'Please select Area for add-on prices.';
        } elseif(in_array($post_category, ['lift','gear'], true) && (!$days || $days <= 0)){
            $price_form_error = 'Lift and Gear prices require Days greater than 0.';
        } elseif($post_category === 'transport' && $addon_grade === ''){
            $price_form_error = 'Transport prices require Grade selection.';
        }
    }

    /* Days-of-week JSON */
    $weekdays_json = null;
    if($price_type === 'selected_days'){
        $sel = array_intersect($_POST['weekdays'] ?? [], $days_of_week);
        $weekdays_json = !empty($sel) ? wp_json_encode(array_values($sel)) : null;
    }

    /* Specific dates JSON */
    $dates_json = null;
    if($price_type === 'specific_dates'){
        $raw = array_filter(array_map('trim', explode(',', $_POST['specific_dates'] ?? '')));
        $clean = [];
        foreach($raw as $d){ $ts = strtotime($d); if($ts) $clean[] = date('Y-m-d',$ts); }
        $dates_json = !empty($clean) ? wp_json_encode(array_values($clean)) : null;
    }

    /* Date range */
    $date_from = ($price_type === 'date_range' && !empty($_POST['date_from'])) ? sanitize_text_field($_POST['date_from']) : null;
    $date_to   = ($price_type === 'date_range' && !empty($_POST['date_to']))   ? sanitize_text_field($_POST['date_to'])   : null;

    /* Currency */
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
        'price_jpy'     => ($_POST['price_jpy']     ?? '') !== '' ? floatval($_POST['price_jpy'])     : null,
        'price_usd'     => ($_POST['price_usd']     ?? '') !== '' ? floatval($_POST['price_usd'])     : null,
    ];

    if ($page_mode === 'hotel') {
        $data['hotel_id']      = $hotel_id;
    } else {
        $data['grade']         = $addon_grade;
    }

    if($price_form_error === ''){
        $ok = $wpdb->insert($db_table, $data);
        if($ok === false){
            $price_form_error = 'Could not save price rule. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
        } else {
            $redirect_args = [
                'page' => $page_mode === 'hotel' ? 'spcu-hotel-prices' : 'spcu-addon-prices',
                'spcu_toast' => 'success',
                'spcu_msg' => rawurlencode('Price rule saved successfully.')
            ];
            if($page_mode === 'hotel'){
                $redirect_args['hotel'] = $selected_hotel_id;
            }
            wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
            exit;
        }
    }
    }
}

/* ── Handle Delete ───────────────────────────────────────────────── */
if(isset($_GET['delete'])){
    $ok = $wpdb->delete($db_table, ['id'=>intval($_GET['delete'])]);
    if($ok !== false){
        $redirect_args = [
            'page' => $page_mode === 'hotel' ? 'spcu-hotel-prices' : 'spcu-addon-prices',
            'spcu_toast' => 'success',
            'spcu_msg' => rawurlencode('Price rule deleted successfully.')
        ];
        if($page_mode === 'hotel'){
            $redirect_args['hotel'] = $selected_hotel_id;
        }
        wp_safe_redirect(add_query_arg($redirect_args, admin_url('admin.php')));
        exit;
    }
    $price_form_error = 'Could not delete price rule. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
}

/* ── Load rows ───────────────────────────────────────────────────── */
if ($page_mode === 'hotel') {
    $hotel_filter_sql = $selected_hotel_id ? $wpdb->prepare(" AND p.hotel_id = %d", $selected_hotel_id) : " AND 1=0";
    $rows = $wpdb->get_results("
        SELECT p.*, h.name as hotel_name, a.name as area_name, NULL as grade_name
        FROM {$db_table} p
        LEFT JOIN {$wpdb->prefix}spcu_hotels h ON p.hotel_id = h.id
        LEFT JOIN {$wpdb->prefix}spcu_areas  a ON p.area_id  = a.id
        WHERE {$category_sql} {$hotel_filter_sql}
        ORDER BY p.category ASC, p.hotel_id ASC, p.id DESC
    ");
} else {
    $rows = $wpdb->get_results("
        SELECT p.*, NULL as hotel_name, a.name as area_name, p.grade as grade_name
        FROM {$db_table} p
        LEFT JOIN {$wpdb->prefix}spcu_areas  a ON p.area_id  = a.id
        WHERE {$category_sql}
        ORDER BY p.category ASC, p.area_id ASC, p.grade ASC, p.id DESC
    ");
}

function spcu_schedule_summary($r){
    switch($r->price_type){
        case 'selected_days':
            $arr = json_decode($r->weekdays_json ?? '[]', true);
            return $arr ? implode(', ', array_map('ucfirst', $arr)) : '—';
        case 'specific_dates':
            $arr = json_decode($r->dates_json ?? '[]', true);
            return $arr ? implode(', ', array_map('esc_html', $arr)) : '—';
        case 'date_range':
            return ($r->date_from && $r->date_to) ? esc_html($r->date_from).' → '.esc_html($r->date_to) : '—';
        case 'weekend': return 'Sat + Sun';
        default: return esc_html($r->price_type);
    }
}
?>

<style>
/* ── Prices Admin Styles ───────────────────────────── */
.spcu-cal-wrap{position:relative;display:inline-block;width:100%;}
.spcu-cal-trigger{
    display:flex;align-items:center;gap:8px;cursor:pointer;
    padding:6px 12px;border:1.5px solid #c3c4c7;border-radius:6px;
    background:#fff;font-size:13px;color:#1d2327;min-width:220px;
    transition:border-color .2s;
}
.spcu-cal-trigger:hover{border-color:#2271b1;}
.spcu-cal-trigger .dashicons{font-size:18px;color:#2271b1;}

.spcu-cal-popup{
    display:none;position:absolute;z-index:9999;top:calc(100% + 6px);left:0;
    background:#fff;border:1.5px solid #c3c4c7;border-radius:10px;
    box-shadow:0 8px 32px rgba(0,0,0,.15);padding:16px;min-width:300px;
    animation:spcu-pop .18s ease;
}
.spcu-cal-popup.open{display:block;}
@keyframes spcu-pop{from{opacity:0;transform:translateY(6px);}to{opacity:1;transform:translateY(0);}}

/* header row */
.spcu-cal-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:12px;}
.spcu-cal-header button{
    background:none;border:1.5px solid #c3c4c7;border-radius:6px;
    width:28px;height:28px;cursor:pointer;font-size:14px;line-height:1;
    display:flex;align-items:center;justify-content:center;
    transition:background .15s,border-color .15s;
}
.spcu-cal-header button:hover{background:#f0f6fc;border-color:#2271b1;}
.spcu-cal-title{font-weight:600;font-size:14px;color:#1a3a5c;}

/* grid */
.spcu-cal-grid{display:grid;grid-template-columns:repeat(7,1fr);gap:3px;}
.spcu-cal-dow{
    text-align:center;font-size:11px;font-weight:600;
    color:#78757a;padding:4px 0;text-transform:uppercase;
}
.spcu-cal-day{
    text-align:center;font-size:13px;padding:6px 2px;border-radius:5px;
    cursor:pointer;transition:background .15s,color .15s;user-select:none;
}
.spcu-cal-day:hover{background:#f0f6fc;}
.spcu-cal-day.empty{cursor:default;}
.spcu-cal-day.today{font-weight:700;color:#2271b1;}

/* specific-dates: toggled */
.spcu-cal-day.sel-date{
    background:#2271b1;color:#fff;font-weight:600;border-radius:5px;
}
/* date-range: start/end */
.spcu-cal-day.range-start,
.spcu-cal-day.range-end{background:#2271b1;color:#fff;font-weight:600;}
.spcu-cal-day.range-start{border-radius:5px 0 0 5px;}
.spcu-cal-day.range-end{border-radius:0 5px 5px 0;}
/* when start === end */
.spcu-cal-day.range-start.range-end{border-radius:5px;}
/* in-between */
.spcu-cal-day.range-mid{background:#dbeafe;color:#1e40af;border-radius:0;}

/* selected chips beneath trigger */
.spcu-sel-chips{display:flex;flex-wrap:wrap;gap:5px;margin-top:6px;}
.spcu-chip{
    display:inline-flex;align-items:center;gap:5px;
    background:#f0f6fc;border:1px solid #2271b1;border-radius:20px;
    padding:2px 10px;font-size:12px;color:#1d2327;
}
.spcu-chip .rm{cursor:pointer;color:#c00;font-weight:700;font-size:14px;line-height:1;}

.spcu-range-lbl{font-size:12px;color:#50575e;margin-top:4px;}
.spcu-range-lbl strong{color:#1a3a5c;}
</style>

<div class='wrap'>
<?php
if($page_mode === 'hotel'){
    spcu_admin_breadcrumb([
        ['label' => 'Ski Calculator', 'url' => admin_url('admin.php?page=spcu-dashboard')],
        ['label' => 'Hotels', 'url' => admin_url('admin.php?page=spcu-hotels')],
        ['label' => $selected_hotel ? $selected_hotel->name : 'Hotel Prices']
    ]);
} else {
    spcu_admin_breadcrumb([
        ['label' => 'Ski Calculator', 'url' => admin_url('admin.php?page=spcu-dashboard')],
        ['label' => 'Addon Prices']
    ]);
}
?>

<h1><?= esc_html($page_title) ?></h1>

<?php if($price_form_error): ?>
    <div class="notice notice-error"><p><?= esc_html($price_form_error) ?></p></div>
    <div class="spcu-toast-source" data-type="error" data-message="<?= esc_attr($price_form_error) ?>"></div>
<?php endif; ?>

<?php if($page_mode === 'hotel' && $selected_hotel): ?>
<div class="spcu-info-card">
    <h2><?= esc_html($selected_hotel->name) ?></h2>
    <p><strong>Area:</strong> <?= esc_html($selected_hotel->area_name ?: '-') ?></p>
    <p><strong>Grade:</strong> <?= esc_html(SPCU_Grades::label($selected_hotel->grade) ?: '-') ?></p>
    <?php if(!empty($selected_hotel->address)): ?><p><strong>Address:</strong> <?= esc_html($selected_hotel->address) ?></p><?php endif; ?>
</div>
<?php endif; ?>

<?php if($page_mode === 'hotel' && !$selected_hotel): ?>
    <div class="spcu-table">
        <table>
            <tr><th>Hotel</th><th>Action</th></tr>
            <?php foreach($hotels as $h): ?>
                <tr>
                    <td><?= esc_html($h->name) ?></td>
                    <td><a class="button button-small" href="?page=spcu-hotel-prices&hotel=<?= esc_attr($h->id) ?>">Open Hotel Prices</a></td>
                </tr>
            <?php endforeach; ?>
        </table>
    </div>
<?php else: ?>

<form method='post' id="price-form">
<table class="form-table" role="presentation">

    <!-- Category -->
    <?php if($page_mode === 'hotel'): ?>
        <input type="hidden" name="category" id="category" value="hotel">
    <?php else: ?>
        <tr>
            <th scope="row"><label for="category">Category</label></th>
            <td>
                <select name='category' id="category" required>
                    <option value='lift'>Lift</option>
                    <option value='gear'>Gear</option>
                    <option value='transport'>Transport</option>
                </select>
            </td>
        </tr>
    <?php endif; ?>

    <!-- Hotel -->
    <tr id="wrap_hotel">
        <th scope="row"><label for="hotel">Hotel</label></th>
        <td>
            <?php if($page_mode === 'hotel' && $selected_hotel): ?>
                <strong><?= esc_html($selected_hotel->name) ?></strong>
                <input type="hidden" name="hotel" id="hotel" value="<?= esc_attr($selected_hotel->id) ?>">
            <?php else: ?>
                <select name='hotel' id="hotel">
                    <option value=''>— Select Hotel —</option>
                    <?php foreach($hotels as $h) echo "<option value='".esc_attr($h->id)."'".selected($selected_hotel_id, (int)$h->id, false).">".esc_html($h->name)."</option>"; ?>
                </select>
            <?php endif; ?>
        </td>
    </tr>

    <!-- Area -->
    <tr id="wrap_area">
        <th scope="row"><label for="area">Area</label></th>
        <td>
            <select name='area' id="area">
                <option value=''>— Select Area —</option>
                <?php foreach($areas as $a) echo "<option value='".esc_attr($a->id)."'>".esc_html($a->name)."</option>"; ?>
            </select>
        </td>
    </tr>

    <!-- Grade (Transport only) -->
    <tr id="wrap_grade">
        <th scope="row"><label for="grade">Grade</label></th>
        <td>
            <select name='grade' id="grade">
                <option value=''>— Select Grade —</option>
                <?php foreach($grade_options as $grade_key => $grade_label) echo "<option value='".esc_attr($grade_key)."'>".esc_html($grade_label)."</option>"; ?>
            </select>
            <p class="description">Used for Transport prices.</p>
        </td>
    </tr>

    <!-- Days -->
    <tr id="wrap_days">
        <th scope="row"><label for="days">Number of Days</label></th>
        <td><input type='number' step='1' min='1' name='days' id="days" class="small-text"> days</td>
    </tr>

    <!-- Price Schedule (hotel only) -->
    <tr id="wrap_price_type">
        <th scope="row"><label for="price_type">Price Schedule</label></th>
        <td>
            <select name='price_type' id="price_type">
                <option value='selected_days'>Selected Days of Week</option>
                <option value='weekend'>Weekend (Sat &amp; Sun)</option>
                <option value='specific_dates'>Specific Dates</option>
                <option value='date_range'>Date Range</option>
            </select>
            <p class="description">When does this hotel price apply?</p>
        </td>
    </tr>

    <!-- Days-of-week checkboxes -->
    <tr id="wrap_weekdays">
        <th scope="row">Days of Week</th>
        <td>
            <div class="spcu-day-grid" style="display:flex;flex-wrap:wrap;gap:6px;">
                <?php foreach($days_of_week as $i => $d): ?>
                <label style="display:inline-flex;align-items:center;gap:5px;border:1px solid #c3c4c7;border-radius:4px;padding:4px 10px;cursor:pointer;font-size:13px;background:#fff;">
                    <input type='checkbox' name='weekdays[]' value='<?= $d ?>'>
                    <span><?= $day_labels[$i] ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <p class="description">Select one or more days this price applies each week.</p>
        </td>
    </tr>

    <!-- ── Specific Dates Calendar ── -->
    <tr id="wrap_specific_dates">
        <th scope="row">Specific Dates</th>
        <td>
            <input type='hidden' name='specific_dates' id='specific_dates_value'>
            <div class="spcu-cal-wrap" id="cal-multi-wrap">
                <div class="spcu-cal-trigger" id="cal-multi-trigger">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span id="cal-multi-label">Click to select dates</span>
                </div>
                <div class="spcu-cal-popup" id="cal-multi-popup">
                    <div class="spcu-cal-header">
                        <button type="button" id="cal-multi-prev">&#8592;</button>
                        <span class="spcu-cal-title" id="cal-multi-title"></span>
                        <button type="button" id="cal-multi-next">&#8594;</button>
                    </div>
                    <div class="spcu-cal-grid" id="cal-multi-grid"></div>
                </div>
            </div>
            <div class="spcu-sel-chips" id="cal-multi-chips"></div>
            <p class="description">Click days to select/deselect. Multiple dates allowed.</p>
        </td>
    </tr>

    <!-- ── Date Range Calendar ── -->
    <tr id="wrap_date_range">
        <th scope="row">Date Range</th>
        <td>
            <input type='hidden' name='date_from' id='cal-range-from-val'>
            <input type='hidden' name='date_to'   id='cal-range-to-val'>
            <div class="spcu-cal-wrap" id="cal-range-wrap">
                <div class="spcu-cal-trigger" id="cal-range-trigger">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <span id="cal-range-label">Click to select range</span>
                </div>
                <div class="spcu-cal-popup" id="cal-range-popup">
                    <div class="spcu-cal-header">
                        <button type="button" id="cal-range-prev">&#8592;</button>
                        <span class="spcu-cal-title" id="cal-range-title"></span>
                        <button type="button" id="cal-range-next">&#8594;</button>
                    </div>
                    <div class="spcu-cal-grid" id="cal-range-grid"></div>
                    <p class="description" style="margin:10px 0 0;">Click start date, then end date.</p>
                </div>
            </div>
            <div class="spcu-range-lbl" id="cal-range-display"></div>
        </td>
    </tr>

    <!-- Currency -->
    <tr>
        <th scope="row">Currency</th>
        <td>
            <label><input type='checkbox' id='currency_jpy' checked> JPY (¥)</label>
            &nbsp;&nbsp;
            <label><input type='checkbox' id='currency_usd'> USD ($)</label>
            <input type='hidden' name='currency_jpy' id='currency_jpy_hidden' value='1'>
            <input type='hidden' name='currency_usd' id='currency_usd_hidden' value=''>
        </td>
    </tr>

    <!-- Fixed JPY -->
    <tr id="wrap_price_jpy">
        <th scope="row"><label for="price_jpy">Fixed Price JPY (¥)</label></th>
        <td><input type='number' step='1' name='price_jpy' id="price_jpy" class="regular-text"></td>
    </tr>
    <!-- Fixed USD -->
    <tr id="wrap_price_usd">
        <th scope="row"><label for="price_usd">Fixed Price USD ($)</label></th>
        <td><input type='number' step='0.01' name='price_usd' id="price_usd" class="regular-text"></td>
    </tr>

</table>

<p class="submit">
    <button name='add_price' value='1' class="button button-primary">Add Price Rule</button>
</p>
</form>
<?php endif; ?>

<!-- ── Price List ───────────────────────────────────────────────── -->
<hr>
<div class='spcu-table'>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <?php if ($page_mode !== 'hotel'): ?><th>Category</th><th>Target (Hotel / Area / Grade)</th><?php endif; ?>
                <th>Days</th>
                <?php if ($page_mode === 'hotel'): ?><th>Schedule</th><?php endif; ?>
                <th>JPY</th><th>USD</th><th>Action</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($rows as $r): ?>
        <?php
            $subject   = $r->hotel_name ?: ($r->area_name ?: '—');
            if ($r->grade_name) {
                $subject .= ' (' . (SPCU_Grades::label($r->grade_name) ?: $r->grade_name) . ')';
            }
            $jpy_fixed = $r->price_jpy     ? '¥'.number_format($r->price_jpy) : '';
            $jpy_range = (($r->price_min_jpy ?? null) && ($r->price_max_jpy ?? null))
                ? '¥'.number_format($r->price_min_jpy).' – ¥'.number_format($r->price_max_jpy) : '';
            $usd_fixed = $r->price_usd     ? '$'.number_format($r->price_usd,2) : '';
            $usd_range = (($r->price_min_usd ?? null) && ($r->price_max_usd ?? null))
                ? '$'.number_format($r->price_min_usd,2).' – $'.number_format($r->price_max_usd,2) : '';
            if($page_mode === 'hotel'){
                $jpy_col = $jpy_fixed ?: '—';
                $usd_col = $usd_fixed ?: '—';
            } else {
                $jpy_col = $jpy_fixed ?: $jpy_range ?: '—';
                $usd_col = $usd_fixed ?: $usd_range ?: '—';
            }
        ?>
        <tr>
            <td><?= esc_html($r->id) ?></td>
            <?php if ($page_mode !== 'hotel'): ?>
            <td><?= esc_html($r->category) ?></td>
            <td><?= esc_html($subject) ?></td>
            <?php endif; ?>
            <td><?= esc_html($r->days ?: '—') ?></td>
            <?php if ($page_mode === 'hotel'): ?><td><?= spcu_schedule_summary($r) ?></td><?php endif; ?>
            <td><?= esc_html($jpy_col) ?></td>
            <td><?= esc_html($usd_col) ?></td>
            <td>
                <a class='spcu-delete' href='?page=<?= esc_html($_GET['page']) ?>&delete=<?= esc_html($r->id) ?><?= $page_mode === 'hotel' && $selected_hotel_id ? '&hotel='.esc_attr($selected_hotel_id) : '' ?>'>Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
</div>

<script>
/* ════════════════════════════════════════════════════
   SPCU ADMIN — CALENDAR + FORM TOGGLE
════════════════════════════════════════════════════ */
(function(){

/* ── Helpers ──────────────────────────────────────── */
function $(id){ return document.getElementById(id); }
function show(id){ var e=$(id); if(e) e.style.display=''; }
function hide(id){ var e=$(id); if(e) e.style.display='none'; }
function setRequired(id, required){
    var e = $(id);
    if(!e) return;
    if(required) e.setAttribute('required', 'required');
    else e.removeAttribute('required');
}

if(!$('price-form')){
    return;
}

var MONTHS = ['January','February','March','April','May','June',
              'July','August','September','October','November','December'];
var DOWS   = ['Su','Mo','Tu','We','Th','Fr','Sa'];

function pad(n){ return String(n).padStart(2,'0'); }
function ymd(y,m,d){ return y+'-'+pad(m+1)+'-'+pad(d); }
function today(){
    var t=new Date(); return ymd(t.getFullYear(), t.getMonth(), t.getDate());
}

/* ── Calendar builder ─────────────────────────────── */
function buildCalendar(grid, year, month, getCellClass, onDayClick, onDayHover){
    grid.innerHTML='';
    /* day-of-week headers */
    DOWS.forEach(function(d){
        var h=document.createElement('div');
        h.className='spcu-cal-dow'; h.textContent=d; grid.appendChild(h);
    });
    var first = new Date(year, month, 1).getDay(); // 0=Sun
    var daysInMonth = new Date(year, month+1, 0).getDate();
    /* empty prefix cells */
    for(var i=0;i<first;i++){
        var e=document.createElement('div'); e.className='spcu-cal-day empty'; grid.appendChild(e);
    }
    var todayStr = today();
    for(var d=1; d<=daysInMonth; d++){
        var dateStr = ymd(year, month, d);
        var cell = document.createElement('div');
        cell.className = 'spcu-cal-day';
        if(dateStr === todayStr) cell.classList.add('today');
        cell.textContent = d;
        cell.dataset.date = dateStr;
        (function(ds, c){
            c.className = 'spcu-cal-day' + (ds===todayStr?' today':'') + ' ' + getCellClass(ds);
            c.addEventListener('click', function(){ onDayClick(ds); });
            if(onDayHover) c.addEventListener('mouseenter', function(){ onDayHover(ds); });
        })(dateStr, cell);
        grid.appendChild(cell);
    }
}

/* ── Refresh all cell classes without full rebuild ── */
function refreshCells(grid, getCellClass){
    grid.querySelectorAll('.spcu-cal-day:not(.empty)').forEach(function(c){
        var base = c.classList.contains('today') ? 'spcu-cal-day today' : 'spcu-cal-day';
        c.className = base + ' ' + getCellClass(c.dataset.date);
    });
}

/* ════ MULTI-SELECT CALENDAR ════════════════════════ */
var multiState = { year: new Date().getFullYear(), month: new Date().getMonth(), sel: [] };

function multiGetClass(ds){
    return multiState.sel.indexOf(ds) !== -1 ? 'sel-date' : '';
}
function multiRender(){
    $('cal-multi-title').textContent = MONTHS[multiState.month]+' '+multiState.year;
    buildCalendar($('cal-multi-grid'), multiState.year, multiState.month,
        multiGetClass,
        function(ds){
            var idx = multiState.sel.indexOf(ds);
            if(idx===-1) multiState.sel.push(ds);
            else multiState.sel.splice(idx,1);
            multiState.sel.sort();
            multiSyncHidden();
            refreshCells($('cal-multi-grid'), multiGetClass);
            multiRenderChips();
        },
        null
    );
}
function multiSyncHidden(){
    $('specific_dates_value').value = multiState.sel.join(',');
    $('cal-multi-label').textContent = multiState.sel.length
        ? multiState.sel.length+' date(s) selected'
        : 'Click to select dates';
}
function multiRenderChips(){
    var wrap = $('cal-multi-chips');
    wrap.innerHTML='';
    multiState.sel.forEach(function(d){
        var chip=document.createElement('span');
        chip.className='spcu-chip';
        chip.innerHTML=d+' <span class="rm" data-date="'+d+'">&#215;</span>';
        wrap.appendChild(chip);
    });
    wrap.addEventListener('click', function(e){
        var rm=e.target.closest('.rm'); if(!rm) return;
        var d=rm.dataset.date;
        multiState.sel = multiState.sel.filter(function(x){return x!==d;});
        multiSyncHidden();
        refreshCells($('cal-multi-grid'), multiGetClass);
        multiRenderChips();
    });
}
$('cal-multi-prev').addEventListener('click', function(){
    if(multiState.month===0){multiState.month=11;multiState.year--;} else multiState.month--;
    multiRender();
});
$('cal-multi-next').addEventListener('click', function(){
    if(multiState.month===11){multiState.month=0;multiState.year++;} else multiState.month++;
    multiRender();
});

/* ════ DATE RANGE CALENDAR ══════════════════════════ */
var rangeState = { year: new Date().getFullYear(), month: new Date().getMonth(),
                   from: null, to: null, picking: 'from', hover: null };

function rangeGetClass(ds){
    var f = rangeState.from, t = rangeState.to, h = rangeState.hover;
    /* determine visual end for mid-range highlighting */
    var end = t || h;
    var lo = f && end ? (f<end?f:end) : f;
    var hi = f && end ? (f<end?end:f) : f;
    var cls = '';
    if(ds===f && ds===t) cls = 'range-start range-end';
    else if(ds===lo) cls = 'range-start';
    else if(ds===hi) cls = 'range-end';
    else if(lo && hi && ds>lo && ds<hi) cls = 'range-mid';
    return cls;
}
function rangeRender(){
    $('cal-range-title').textContent = MONTHS[rangeState.month]+' '+rangeState.year;
    buildCalendar($('cal-range-grid'), rangeState.year, rangeState.month,
        rangeGetClass,
        function(ds){
            if(!rangeState.from || rangeState.picking==='from'){
                rangeState.from = ds; rangeState.to = null; rangeState.picking='to';
            } else {
                /* ensure from <= to */
                if(ds < rangeState.from){
                    rangeState.to = rangeState.from; rangeState.from = ds;
                } else {
                    rangeState.to = ds;
                }
                rangeState.picking = 'from';
            }
            rangeSyncHidden();
            refreshCells($('cal-range-grid'), rangeGetClass);
            rangeUpdateDisplay();
        },
        function(ds){ /* hover */
            if(rangeState.picking==='to'){
                rangeState.hover = ds;
                refreshCells($('cal-range-grid'), rangeGetClass);
            }
        }
    );
    rangeUpdateDisplay();
}
function rangeSyncHidden(){
    $('cal-range-from-val').value = rangeState.from || '';
    $('cal-range-to-val').value   = rangeState.to   || '';
    var lbl = 'Click to select range';
    if(rangeState.from && rangeState.to) lbl = rangeState.from+' → '+rangeState.to;
    else if(rangeState.from) lbl = 'From: '+rangeState.from+' — pick end date';
    $('cal-range-label').textContent = lbl;
}
function rangeUpdateDisplay(){
    var d = $('cal-range-display');
    if(rangeState.from && rangeState.to)
        d.innerHTML='<strong>From:</strong> '+rangeState.from+' &nbsp; <strong>To:</strong> '+rangeState.to;
    else if(rangeState.from)
        d.innerHTML='<strong>From:</strong> '+rangeState.from+' &nbsp; — now pick end date';
    else
        d.innerHTML='';
}
$('cal-range-prev').addEventListener('click', function(){
    if(rangeState.month===0){rangeState.month=11;rangeState.year--;} else rangeState.month--;
    rangeRender();
});
$('cal-range-next').addEventListener('click', function(){
    if(rangeState.month===11){rangeState.month=0;rangeState.year++;} else rangeState.month++;
    rangeRender();
});

/* ════ POPUP OPEN / CLOSE ═══════════════════════════ */
function initPopup(triggerId, popupId, renderFn){
    var trigger=$(triggerId), popup=$(popupId);
    trigger.addEventListener('click', function(e){
        e.stopPropagation();
        var wasOpen = popup.classList.contains('open');
        /* close all */
        document.querySelectorAll('.spcu-cal-popup').forEach(function(p){ p.classList.remove('open'); });
        if(!wasOpen){ popup.classList.add('open'); renderFn(); }
    });
    popup.addEventListener('click', function(e){ e.stopPropagation(); });
}
document.addEventListener('click', function(){
    document.querySelectorAll('.spcu-cal-popup').forEach(function(p){ p.classList.remove('open'); });
});

initPopup('cal-multi-trigger', 'cal-multi-popup', multiRender);
initPopup('cal-range-trigger', 'cal-range-popup', rangeRender);

/* ════ FORM FIELD TOGGLE ════════════════════════════ */
var catSel  = $('category');
var typeSel = $('price_type');
var jpyChk  = $('currency_jpy');
var usdChk  = $('currency_usd');

if(!catSel || !typeSel || !jpyChk || !usdChk){
    return;
}

function toggle(){
    var cat  = catSel.value;
    var type = typeSel.value;
    var jpy  = jpyChk.checked;
    var usd  = usdChk.checked;

    $('currency_jpy_hidden').value = jpy ? '1' : '';
    $('currency_usd_hidden').value = usd ? '1' : '';

    /* ── Hotel ── */
    if(cat === 'hotel'){
        show('wrap_hotel'); hide('wrap_area'); hide('wrap_days'); hide('wrap_grade');
        show('wrap_price_type');

        setRequired('area', false);
        setRequired('days', false);
        setRequired('grade', false);

        /* price fields: fixed only */
        jpy ? show('wrap_price_jpy') : hide('wrap_price_jpy');
        usd ? show('wrap_price_usd') : hide('wrap_price_usd');

        /* schedule rows based on type */
        hide('wrap_weekdays'); hide('wrap_specific_dates'); hide('wrap_date_range');
        if(type==='selected_days')  show('wrap_weekdays');
        if(type==='specific_dates') show('wrap_specific_dates');
        if(type==='date_range')     show('wrap_date_range');

    /* ── Lift / Gear / Transport ── */
    } else {
        hide('wrap_hotel'); show('wrap_area');
        setRequired('area', true);
        
        hide('wrap_price_type');
        hide('wrap_weekdays'); hide('wrap_specific_dates'); hide('wrap_date_range');

        if (cat === 'transport') {
            hide('wrap_days');
            show('wrap_grade');
            setRequired('days', false);
            setRequired('grade', true);
        } else {
            show('wrap_days');
            hide('wrap_grade');
            setRequired('days', true);
            setRequired('grade', false);
        }

        /* price fields: fixed price only */
        jpy ? show('wrap_price_jpy') : hide('wrap_price_jpy');
        usd ? show('wrap_price_usd') : hide('wrap_price_usd');
    }
}

catSel.addEventListener('change',  toggle);
typeSel.addEventListener('change', toggle);
jpyChk.addEventListener('change',  toggle);
usdChk.addEventListener('change',  toggle);
toggle();

})();
</script>
