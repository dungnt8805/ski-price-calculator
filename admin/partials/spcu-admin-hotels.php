<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$hotel_table = $wpdb->prefix.'spcu_hotels';
$hotel_error = '';

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

$table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $hotel_table));
if(!$table_exists && class_exists('SPCU_Database')){
    SPCU_Database::create_tables();
    $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $hotel_table));
}

if(!$table_exists){
    $hotel_error = 'Hotels table is missing.';
}

// Handle hotel search
$search_term = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$search_where = '';
if($search_term !== ''){
    $search_where = $wpdb->prepare(
        " WHERE h.name LIKE %s OR h.name_ja LIKE %s",
        '%' . $wpdb->esc_like($search_term) . '%',
        '%' . $wpdb->esc_like($search_term) . '%'
    );
}

$rows = [];
if($hotel_error === ''){
    $rows = $wpdb->get_results("
        SELECT h.*, a.name as area_name, h.grade as grade_name
        FROM {$wpdb->prefix}spcu_hotels h
        LEFT JOIN {$wpdb->prefix}spcu_areas a ON h.area_id = a.id
            {$search_where}
        ORDER BY h.name ASC
    ");
}
?>

<div class='wrap'>
    <?php spcu_admin_breadcrumb([
        ['label' => 'Ski Engine', 'url' => admin_url('admin.php?page=spcu-dashboard')],
        ['label' => 'Hotels']
    ]); ?>

    <div class="spcu-header-row">
        <h1>Hotels</h1>
        <a href='?page=spcu-hotel-form' class='button button-primary'>Add Hotel</a>
    </div>

    <?php if($hotel_error): ?>
        <div class="notice notice-error"><p><?= esc_html($hotel_error) ?></p></div>
        <div class="spcu-toast-source" data-type="error" data-message="<?= esc_attr($hotel_error) ?>"></div>
    <?php endif; ?>

    <div style="margin:15px 0;display:flex;gap:10px;align-items:center;">
        <form method="get" style="display:flex;gap:10px;align-items:center;">
            <input type="hidden" name="page" value="spcu-hotels">
            <input type="search" name="s" placeholder="Search by hotel name..." value="<?= esc_attr($search_term) ?>" style="padding:6px 12px;border:1px solid #ccc;border-radius:3px;">
            <button type="submit" class="button">Search</button>
            <?php if($search_term): ?>
                <a href="?page=spcu-hotels" class="button">Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class='spcu-table'>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <tr><th>Name</th>
                <th>Area</th><th>Grade</th><th>Featured</th><th>Images</th><th>Action</th>
            </tr>
            <?php foreach($rows as $r): ?>
            <?php
                $delete_url = wp_nonce_url(
                    add_query_arg([
                        'page' => 'spcu-hotels',
                        'delete' => intval($r->id),
                    ], admin_url('admin.php')),
                    'spcu_delete_hotel_' . intval($r->id)
                );
            ?>
            <tr>
                <td><strong><?= esc_html($r->name) ?></strong><?php if($r->name_ja) echo "<br><small>".esc_html($r->name_ja)."</small>"; ?></td>
                <td><?= esc_html($r->area_name) ?></td>
                <td><span style="display:inline-block;padding:3px 8px;border-radius:3px;background:#e2e8f0;color:#334155;font-size:12px;font-weight:600;text-transform:capitalize;"><?= esc_html($r->grade ?: '-') ?></span></td>
                <td>
                    <?= ($r->is_featured ? '<span style="background:#16a34a;color:#fff;padding:3px 8px;border-radius:3px;font-size:11px;font-weight:600;white-space:nowrap;">★ Featured</span>' : '<span style="color:#9ca3af;">-</span>') ?>
                </td>
                <td style="display:flex;align-items:center;gap:8px;flex-wrap:wrap;">
                    <?php
                        // Display featured image
                        if(!empty($r->featured_image)){
                            $featured_thumb = wp_get_attachment_image_url(intval($r->featured_image), 'thumbnail');
                            if($featured_thumb){
                                echo "<img src='".esc_url($featured_thumb)."' style='width:40px;height:40px;object-fit:cover;border-radius:3px;' title='Featured image'>";
                            }
                        }
                        // Display other images
                        if(!empty($r->images)){
                            $image_ids = array_filter(array_map('trim', explode(',', $r->images)));
                            foreach($image_ids as $img_id){
                                $img_url = wp_get_attachment_image_url(intval($img_id), 'thumbnail');
                                if($img_url){
                                    echo "<img src='".esc_url($img_url)."' style='width:40px;height:40px;object-fit:cover;border-radius:3px;'>";
                                }
                            }
                        }
                        if(empty($r->featured_image) && empty($r->images)){
                            echo '<span style="color:#9ca3af;">-</span>';
                        }
                    ?>
                </td>
                <td style="white-space:nowrap;">
                    <a href='?page=spcu-hotel-prices&hotel=<?= esc_attr($r->id) ?>' class='button button-small'>Details</a>
                    <a href='?page=spcu-hotel-form&edit=<?= esc_attr($r->id) ?>' class='button button-small'>Edit</a>
                    <a class='spcu-delete' href='<?= esc_url($delete_url) ?>'>Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>

    <div style="margin:15px 0;display:flex;gap:10px;align-items:center;">
        <form method="get" style="display:flex;gap:10px;align-items:center;">
            <input type="hidden" name="page" value="spcu-hotels">
            <input type="search" name="s" placeholder="Search by hotel name..." value="<?= esc_attr($search_term) ?>" style="padding:6px 12px;border:1px solid #ccc;border-radius:3px;">
            <button type="submit" class="button">Search</button>
            <?php if($search_term): ?>
                <a href="?page=spcu-hotels" class="button">Clear</a>
            <?php endif; ?>
        </div>
    </div>
