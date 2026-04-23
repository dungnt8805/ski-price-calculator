<?php
add_action('admin_enqueue_scripts', function($hook){

    // DEBUG: xem hook name nếu cần
    // error_log($hook);

    // load editor cho toàn bộ page của plugin SPCU
    if (strpos($hook, 'spcu') !== false) {

        // LOAD TINYMCE (QUAN TRỌNG)
        wp_enqueue_editor();

        // LOAD ICON
        wp_enqueue_style('dashicons');

        // LOAD CSS toolbar editor
        wp_enqueue_style('editor-buttons');
    }

    // đoạn cũ của bạn
    if(strpos($hook, 'spcu-hotel-form') !== false){
        wp_enqueue_script('jquery');
        wp_enqueue_script(
            'spcu-facilities',
            SPCU_URL . 'admin/admin-facilities.js',
            ['jquery'],
            '1.0',
            true
        );

        wp_localize_script('spcu-facilities', 'spcu_facilities_data', [
            'nonce' => wp_create_nonce('wp_rest'),
            'rest_url' => rest_url('wp/v2/'),
            'ajax_url' => admin_url('admin-ajax.php'),
        ]);
    }
});

if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix.'spcu_areas';
$area_error = '';

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

$rows = $wpdb->get_results("SELECT * FROM $table ORDER BY name ASC");

$prefectures_table = $wpdb->prefix.'spcu_prefectures';
$prefectures = [];
if ((bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefectures_table))) {
    $prefectures = $wpdb->get_results("SELECT id, name FROM $prefectures_table ORDER BY name ASC");
}

wp_enqueue_media();
?>

<div class='wrap'>
    <?php spcu_admin_breadcrumb([
        ['label' => 'Ski Engine', 'url' => admin_url('admin.php?page=spcu-dashboard')],
        ['label' => 'Areas']
    ]); ?>

    <div class="spcu-header-row">
        <h1>Areas</h1>
        <a href='?page=spcu-area-form' class='button button-primary'>Add Area</a>
    </div>

    <?php if($area_error): ?>
        <div class="notice notice-error"><p><?= esc_html($area_error) ?></p></div>
        <div class="spcu-toast-source" data-type="error" data-message="<?= esc_attr($area_error) ?>"></div>
    <?php endif; ?>

    <div class='spcu-table'>
        <table class="wp-list-table widefat fixed striped table-view-list">
            <tr><th>Prefecture</th><th>Name</th><th>Slug</th><th>Short Description</th><th>Featured Image</th><th>Action</th></tr>
            <?php foreach($rows as $r): 
                $pref_name = '';
                foreach($prefectures as $p) {
                    if($p->id == $r->prefecture_id) {
                        $pref_name = $p->name;
                        break;
                    }
                }
            ?>
            <tr>
                <td><?= esc_html($pref_name) ?></td>
                <td>
                    <strong><?= esc_html($r->name) ?></strong>
                    <?php if(!empty($r->name_ja)): ?><br>(<span style="color:#6f7f8f;font-size:0.9em;"><?= esc_html($r->name_ja) ?></span>)<?php endif; ?>
                </td>
                <td><?= esc_html($r->slug ?? '') ?></td>
                <td><?= esc_html($r->short_description ?? '') ?></td>
                <td>
                    <?php if(!empty($r->featured_image)):
                        $thumb = wp_get_attachment_image_url(intval($r->featured_image), 'thumbnail');
                        if($thumb): ?>
                            <img src="<?= esc_url($thumb) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:4px;">
                        <?php endif;
                    endif; ?>
                </td>
                <td><a href='?page=spcu-area-form&edit=<?= esc_html($r->id) ?>' class='button button-small'>Edit</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
