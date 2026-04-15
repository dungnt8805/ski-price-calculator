<?php
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

// Handle area update
if(isset($_POST['edit_area'])){
    $area_id = intval($_POST['area_id'] ?? 0);
    if($area_id > 0){
        $ok = $wpdb->update($table,[
            'type'=>sanitize_text_field($_POST['type']),
            'name'=>sanitize_text_field($_POST['name']),
            'name_ja'=>sanitize_text_field($_POST['name_ja'])
        ], ['id'=>$area_id]);

        if($ok !== false){
            wp_safe_redirect(add_query_arg([
                'page' => 'spcu-areas',
                'spcu_toast' => 'success',
                'spcu_msg' => rawurlencode('Area updated successfully.')
            ], admin_url('admin.php')));
            exit;
        }

        $area_error = 'Could not update area. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
    }
}

if(isset($_POST['add_area'])){
    $ok = $wpdb->insert($table,[
        'type'=>sanitize_text_field($_POST['type']),
        'name'=>sanitize_text_field($_POST['name']),
        'name_ja'=>sanitize_text_field($_POST['name_ja'])
    ]);

    if($ok !== false){
        wp_safe_redirect(add_query_arg([
            'page' => 'spcu-areas',
            'spcu_toast' => 'success',
            'spcu_msg' => rawurlencode('Area added successfully.')
        ], admin_url('admin.php')));
        exit;
    }

    $area_error = 'Could not add area. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
}

$edit_area = null;
if(isset($_GET['edit'])){
    $edit_area = $wpdb->get_row("SELECT * FROM {$table} WHERE id=".intval($_GET['edit']));
}

$rows = $wpdb->get_results("SELECT * FROM $table");
?>

<div class='wrap'>
    <?php spcu_admin_breadcrumb([
        ['label' => 'Ski Calculator', 'url' => admin_url('admin.php?page=spcu-dashboard')],
        ['label' => 'Areas']
    ]); ?>

    <h1><?= $edit_area ? 'Edit Area' : 'Areas' ?></h1>
    <?php if($area_error): ?>
        <div class="notice notice-error"><p><?= esc_html($area_error) ?></p></div>
        <div class="spcu-toast-source" data-type="error" data-message="<?= esc_attr($area_error) ?>"></div>
    <?php endif; ?>

    <div class="spcu-split spcu-split-areas">
        <div class="spcu-col spcu-col-form">
            <form method='post'>
                <?php if($edit_area): ?>
                    <input type='hidden' name='area_id' value='<?= esc_attr($edit_area->id) ?>'>
                <?php endif; ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="type">Area Type</label></th>
                        <td>
                            <select name='type' id='type' required>
                                <option value='City' <?= selected(($edit_area->type ?? ''), 'City', false) ?>>City</option>
                                <option value='Town' <?= selected(($edit_area->type ?? ''), 'Town', false) ?>>Town</option>
                                <option value='Village' <?= selected(($edit_area->type ?? ''), 'Village', false) ?>>Village</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="name">Area Name</label></th>
                        <td><input name='name' id='name' class="regular-text" placeholder='Hakuba' required value='<?= esc_attr($edit_area->name ?? '') ?>'></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="name_ja">Name (Japanese)</label></th>
                        <td><input name='name_ja' id='name_ja' class="regular-text" placeholder='白馬' value='<?= esc_attr($edit_area->name_ja ?? '') ?>'></td>
                    </tr>
                </table>
                <p class="submit">
                    <?php if($edit_area): ?>
                        <button name='edit_area' value='1' class="button button-primary">Update Area</button>
                        <a href='?page=spcu-areas' class='button'>Cancel</a>
                    <?php else: ?>
                        <button name='add_area' class="button button-primary">Add Area</button>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <div class="spcu-col spcu-col-list">
            <div class='spcu-table'>
                <table>
                    <tr><th>ID</th><th>Type</th><th>Name</th><th>Name (JA)</th><th>Action</th></tr>
                    <?php foreach($rows as $r): ?>
                    <tr>
                        <td><?= esc_html($r->id) ?></td>
                        <td><?= esc_html($r->type) ?></td>
                        <td><?= esc_html($r->name) ?></td>
                        <td><?= esc_html($r->name_ja) ?></td>
                        <td><a href='?page=spcu-areas&edit=<?= esc_html($r->id) ?>' class='button button-small'>Edit</a></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
</div>
