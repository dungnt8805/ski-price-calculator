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

$rows = [];
if($hotel_error === ''){
    $rows = $wpdb->get_results("
        SELECT h.*, a.name as area_name, h.grade as grade_name
        FROM {$wpdb->prefix}spcu_hotels h
        LEFT JOIN {$wpdb->prefix}spcu_areas a ON h.area_id = a.id
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

    <div class='spcu-table'>
        <table>
            <tr>
                <th>ID</th><th>Name</th><th>Name (JP)</th><th>Short Description</th><th>Address</th>
                <th>Area</th><th>Difficulty</th><th>Featured</th><th>Action</th>
            </tr>
            <?php foreach($rows as $r): ?>
            <?php
                $featured_thumb = !empty($r->featured_image)
                    ? wp_get_attachment_image_url(intval($r->featured_image), 'thumbnail')
                    : '';
                $delete_url = wp_nonce_url(
                    add_query_arg([
                        'page' => 'spcu-hotels',
                        'delete' => intval($r->id),
                    ], admin_url('admin.php')),
                    'spcu_delete_hotel_' . intval($r->id)
                );
            ?>
            <tr>
                <td><?= esc_html($r->id) ?></td>
                <td><strong><?= esc_html($r->name) ?></strong><?php if($r->name_ja) echo "<br><small>".esc_html($r->name_ja)."</small>"; ?></td>
                <td><?= esc_html($r->name_ja ?: '-') ?></td>
                <td style="max-width:220px;font-size:12px;"><?= esc_html($r->short_description ?: '-') ?></td>
                <td style="max-width:220px;font-size:12px;"><?= nl2br(esc_html($r->address ?: '-')) ?></td>
                <td><?= esc_html($r->area_name) ?></td>
                <td>
                    <?php $difficulty_label = SPCU_Grades::label($r->grade_name); ?>
                    <?php if($difficulty_label): ?>
                        <span style="display:inline-block;padding:4px 10px;border-radius:999px;background:<?= esc_attr(SPCU_Grades::color($r->grade_name)) ?>;color:<?= esc_attr(SPCU_Grades::text_color($r->grade_name)) ?>;font-size:12px;font-weight:600;">
                            <?= esc_html($difficulty_label) ?>
                        </span>
                    <?php else: ?>
                        -
                    <?php endif; ?>
                </td>
                <td style="display:flex;align-items:center;gap:8px;">
                    <?= $featured_thumb ? "<img src='".esc_url($featured_thumb)."' style='width:40px;height:40px;object-fit:cover;border-radius:3px;'>" : '' ?>
                    <?= ($r->is_featured ? '<span style="background:#16a34a;color:#fff;padding:3px 8px;border-radius:3px;font-size:11px;font-weight:600;white-space:nowrap;">★ Featured</span>' : '-') ?>
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
