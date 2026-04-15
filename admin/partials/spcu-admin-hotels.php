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

if(isset($_GET['delete'])){
    $ok = $wpdb->delete($hotel_table, ['id' => intval($_GET['delete'])]);
    if($ok !== false){
        wp_safe_redirect(add_query_arg([
            'page' => 'spcu-hotels',
            'spcu_toast' => 'success',
            'spcu_msg' => rawurlencode('Hotel deleted successfully.')
        ], admin_url('admin.php')));
        exit;
    }
    $hotel_error = 'Could not delete hotel. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
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
        ['label' => 'Ski Calculator', 'url' => admin_url('admin.php?page=spcu-dashboard')],
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
                <th>ID</th><th>Name</th><th>Name (JP)</th><th>Address</th>
                <th>Area</th><th>Grade</th><th>Images</th><th>Action</th>
            </tr>
            <?php foreach($rows as $r): ?>
            <?php
                $thumbs = [];
                if(!empty($r->images)){
                    foreach(explode(',',$r->images) as $img_id){
                        $t = wp_get_attachment_image_url(intval($img_id),'thumbnail');
                        if($t) $thumbs[] = "<img src='".esc_url($t)."' style='width:40px;height:40px;object-fit:cover;border-radius:3px;margin-right:3px;'>";
                    }
                }
            ?>
            <tr>
                <td><?= esc_html($r->id) ?></td>
                <td><strong><?= esc_html($r->name) ?></strong><?php if($r->name_ja) echo "<br><small>".esc_html($r->name_ja)."</small>"; ?></td>
                <td><?= esc_html($r->name_ja ?: '-') ?></td>
                <td style="max-width:220px;font-size:12px;"><?= nl2br(esc_html($r->address ?: '-')) ?></td>
                <td><?= esc_html($r->area_name) ?></td>
                <td><?= esc_html(SPCU_Grades::label($r->grade_name) ?: '-') ?></td>
                <td><?= $thumbs ? implode('',$thumbs) : '-' ?></td>
                <td style="white-space:nowrap;">
                    <a href='?page=spcu-hotel-prices&hotel=<?= esc_attr($r->id) ?>' class='button button-small'>Details</a>
                    <a href='?page=spcu-hotel-form&edit=<?= esc_attr($r->id) ?>' class='button button-small'>Edit</a>
                    <a class='spcu-delete' href='?page=spcu-hotels&delete=<?= esc_attr($r->id) ?>'>Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
