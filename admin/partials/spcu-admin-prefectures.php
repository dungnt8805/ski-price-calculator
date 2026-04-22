<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix.'spcu_prefectures';
$area_error = ''; // using same var name for simplicity

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

$edit_area = null;
if(isset($_GET['edit'])){
    $edit_area = $wpdb->get_row("SELECT * FROM {$table} WHERE id=".intval($_GET['edit']));
}

$table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
if(!$table_exists && class_exists('SPCU_Database')){
    SPCU_Database::create_tables();
    $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
}

$schema_columns = [
    'short_description' => 'VARCHAR(200) NULL',
    'description' => 'TEXT NULL',
    'featured_image' => 'INT NULL',
    'images' => 'TEXT NULL',
    'is_featured' => 'TINYINT(1) NOT NULL DEFAULT 0',
];

if($table_exists){
    foreach($schema_columns as $column => $definition){
        $exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$table} LIKE %s", $column));
        if(!$exists){
            $wpdb->query("ALTER TABLE {$table} ADD COLUMN {$column} {$definition}");
        }
    }
}

$rows = $wpdb->get_results("SELECT * FROM $table");

wp_enqueue_media();
?>

<div class='wrap'>
    <?php spcu_admin_breadcrumb([
        ['label' => 'Ski Engine', 'url' => admin_url('admin.php?page=spcu-dashboard')],
        ['label' => 'Prefectures']
    ]); ?>


    <div class="spcu-header-row">
        <h1>Prefectures</h1>
        <a href='?page=spcu-prefecture-form' class='button button-primary'>Add Prefecture</a>
    </div>

    <?php if($area_error): ?>
        <div class="notice notice-error"><p><?= esc_html($area_error) ?></p></div>
        <div class="spcu-toast-source" data-type="error" data-message="<?= esc_attr($area_error) ?>"></div>
    <?php endif; ?>

    <div class='spcu-table'>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <tr><th>ID</th><th>Name</th><th>Featured</th><th>Featured Image</th><th>Action</th></tr>
            <?php foreach($rows as $r): ?>
            <tr>
                <td><?= esc_html($r->id) ?></td>
                <td>
                    <strong><?= esc_html($r->name) ?></strong>
                    <?php if(!empty($r->name_ja)): ?><br><span style="color:#6f7f8f;font-size:0.9em;"><?= esc_html($r->name_ja) ?></span><?php endif; ?>
                </td>
                <td>
                    <?php if(!empty($r->is_featured)): ?>
                        <span class="dashicons dashicons-star-filled" style="color:#f59e0b;" title="Featured"></span>
                    <?php else: ?>
                        <span class="dashicons dashicons-star-empty" style="color:#cbd5e1;" title="Not Featured"></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if(!empty($r->featured_image)):
                        $thumb = wp_get_attachment_image_url(intval($r->featured_image), 'thumbnail');
                        if($thumb): ?>
                            <img src="<?= esc_url($thumb) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:4px;">
                        <?php endif;
                    endif; ?>
                </td>
                <td><a href='?page=spcu-prefecture-form&edit=<?= esc_html($r->id) ?>' class='button button-small'>Edit</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
