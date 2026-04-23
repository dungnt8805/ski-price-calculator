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

$edit_area = null;
if(isset($_GET['edit'])){
    $edit_area = $wpdb->get_row("SELECT * FROM {$table} WHERE id=".intval($_GET['edit']));
}

$prefectures_table = $wpdb->prefix.'spcu_prefectures';
$prefectures = [];
if ((bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prefectures_table))) {
    $prefectures = $wpdb->get_results("SELECT id, name FROM $prefectures_table ORDER BY name ASC");
}

wp_enqueue_media();

$difficulties_list = class_exists('SPCU_Grades') ? SPCU_Grades::records() : [];
$area_difficulties = [];
if ($edit_area && !empty($edit_area->difficulties_json)) {
    $area_difficulties = json_decode($edit_area->difficulties_json, true) ?: [];
}
?>

<div class='wrap'>
    <?php spcu_admin_breadcrumb([
        ['label' => 'Ski Engine', 'url' => admin_url('admin.php?page=spcu-dashboard')],
        ['label' => 'Areas', 'url' => admin_url('admin.php?page=spcu-areas')],
        ['label' => $edit_area ? 'Edit Area' : 'Add Area']
    ]); ?>

    <?php if($edit_area): ?>
        <div style="background:#16a34a;color:#fff;padding:14px 18px;border-radius:8px;margin:16px 0 20px;font-size:18px;font-weight:600;box-shadow:0 6px 18px rgba(22,163,74,0.18);">
            You are editing <?= esc_html($edit_area->name) ?>
        </div>
    <?php endif; ?>

    <h1 class="wp-heading-inline"><?= $edit_area ? 'Edit Area' : 'Add New Area' ?></h1>
    <a href="?page=spcu-areas" class="page-title-action">Back to Areas</a>
    <hr class="wp-header-end">

    <?php if($area_error): ?>
        <div class="notice notice-error"><p><?= esc_html($area_error) ?></p></div>
        <div class="spcu-toast-source" data-type="error" data-message="<?= esc_attr($area_error) ?>"></div>
    <?php endif; ?>

    <div style="max-width: 800px; margin-top: 20px;">
        <div class="postbox" style="padding: 20px;">
            <form method='post' action='<?= esc_url(admin_url('admin.php?page=spcu-area-form')) ?>'>
                <?php wp_nonce_field('spcu_save_area'); ?>
                <?php if($edit_area): ?>
                    <input type='hidden' name='area_id' value='<?= esc_attr($edit_area->id) ?>'>
                <?php endif; ?>
                
                <table class="form-table spcu-form-vertical" role="presentation">
                    <tr>
                        <th scope="row"><label for="name">Area Name</label></th>
                        <td><input name='name' id='name' class="regular-text" placeholder='Hakuba' required value='<?= esc_attr($edit_area->name ?? '') ?>'></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="name_ja">Name (Japanese)</label></th>
                        <td><input name='name_ja' id='name_ja' class="regular-text" placeholder='白馬' value='<?= esc_attr($edit_area->name_ja ?? '') ?>'></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="slug">URL Slug</label></th>
                        <td>
                            <input name='slug' id='slug' class="regular-text" placeholder='hakuba' value='<?= esc_attr($edit_area->slug ?? '') ?>'>
                            <p class="description">Leave blank to auto-generate from Area Name.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="prefecture_id">Prefecture</label></th>
                        <td>
                            <select name='prefecture_id' id='prefecture_id' required>
                                <option value=''>-- Select Prefecture --</option>
                                <?php foreach($prefectures as $pref): ?>
                                    <option value='<?= esc_attr($pref->id) ?>' <?= selected(($edit_area->prefecture_id ?? ''), $pref->id, false) ?>><?= esc_html($pref->name) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
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
                        <th scope="row"><label for="short_description">Short Description</label></th>
                        <td>
                            <textarea name='short_description' id='short_description' class="large-text" rows="2" maxlength="255" placeholder='Compact overview for the area header'><?= esc_textarea($edit_area->short_description ?? '') ?></textarea>
                            <p class="description">255 character limit.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Difficulties (%)</label></th>
                        <td>
                            <div style="display:flex; flex-wrap:wrap; gap:15px;">
                                <?php foreach($difficulties_list as $diff): 
                                    $val = $area_difficulties[$diff['slug']] ?? '';
                                ?>
                                    <div style="display:flex; align-items:center; gap:5px;">
                                        <label for="diff_<?= esc_attr($diff['slug']) ?>" style="font-weight:600; color:<?= esc_attr($diff['color']) ?>;"><?= esc_html($diff['name']) ?></label>
                                        <input type="number" id="diff_<?= esc_attr($diff['slug']) ?>" name="difficulties[<?= esc_attr($diff['slug']) ?>]" value="<?= esc_attr($val) ?>" placeholder="%" style="width:70px;" min="0" max="100">
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="description">Enter the percentage for each difficulty level (e.g. 45).</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="total_runs">Total Runs</label></th>
                        <td><input name='total_runs' id='total_runs' class="regular-text" type="number" placeholder='e.g. 50' value='<?= esc_attr($edit_area->total_runs ?? '') ?>'></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="max_vertical">Max Vertical (m)</label></th>
                        <td><input name='max_vertical' id='max_vertical' class="regular-text" type="number" placeholder='e.g. 1071' value='<?= esc_attr($edit_area->max_vertical ?? '') ?>'></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="total_resorts">Total Resorts</label></th>
                        <td><input name='total_resorts' id='total_resorts' class="regular-text" type="number" placeholder='e.g. 9' value='<?= esc_attr($edit_area->total_resorts ?? '') ?>'></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="season">Season</label></th>
                        <td><input name='season' id='season' class="regular-text" type="text" placeholder='e.g. Dec to Apr' value='<?= esc_attr($edit_area->season ?? '') ?>'></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="summit">Summit (m)</label></th>
                        <td><input name='summit' id='summit' class="regular-text" type="number" placeholder='e.g. 1831' value='<?= esc_attr($edit_area->summit ?? '') ?>'></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="distance">Distance from Tokyo</label></th>
                        <td><input name='distance' id='distance' class="regular-text" type="text" placeholder='e.g. 4 hrs (Bullet Train + Bus)' value='<?= esc_attr($edit_area->distance ?? '') ?>'></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="featured_badge">Featured Badge</label></th>
                        <td>
                            <select name='featured_badge' id='featured_badge' class="regular-text">
                                <option value=''>-- None --</option>
                                <option value='MOST POPULAR' <?= selected(($edit_area->featured_badge ?? ''), 'MOST POPULAR', false) ?>>MOST POPULAR</option>
                                <option value='LARGEST IN JAPAN' <?= selected(($edit_area->featured_badge ?? ''), 'LARGEST IN JAPAN', false) ?>>LARGEST IN JAPAN</option>
                                <option value='HOT SPRINGS' <?= selected(($edit_area->featured_badge ?? ''), 'HOT SPRINGS', false) ?>>HOT SPRINGS</option>
                                <option value='SNOW MONKEYS' <?= selected(($edit_area->featured_badge ?? ''), 'SNOW MONKEYS', false) ?>>SNOW MONKEYS</option>
                                <option value='FAMILY FRIENDLY' <?= selected(($edit_area->featured_badge ?? ''), 'FAMILY FRIENDLY', false) ?>>FAMILY FRIENDLY</option>
                                <option value='BEGINNER PARADISE' <?= selected(($edit_area->featured_badge ?? ''), 'BEGINNER PARADISE', false) ?>>BEGINNER PARADISE</option>
                            </select>
                            <p class="description">Display a badge on the area card.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="area_tags">Area Tags (one per line)</label></th>
                        <td>
                            <textarea name='area_tags' id='area_tags' class="large-text" rows="6" placeholder='e.g.&#10;10 Resorts&#10;Olympic Heritage&#10;Deep Powder'><?= isset($edit_area->area_tags) ? esc_textarea(str_replace('","', "\n", str_replace('["', '', str_replace('"]', '', $edit_area->area_tags)))) : '' ?></textarea>
                            <p class="description">Enter one tag per line. These will display as pills on the area card.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="description">Description</label></th>
                        <td>
                            <?php wp_editor($edit_area->description ?? '', 'description', ['media_buttons' => true, 'teeny' => false, 'textarea_rows' => 10]); ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="spcu_area_featured_image_id">Featured Image</label></th>
                        <td>
                            <input type='hidden' name='featured_image' id='spcu_area_featured_image_id' value='<?= esc_attr(isset($edit_area->featured_image) ? intval($edit_area->featured_image) : 0) ?>'>
                            <div id="spcu-area-featured-image-preview" style="margin-bottom:10px;">
                                <?php
                                $featured_id = isset($edit_area->featured_image) ? intval($edit_area->featured_image) : 0;
                                if($featured_id){
                                    $featured_thumb = wp_get_attachment_image_url($featured_id, 'medium');
                                    if($featured_thumb){
                                        echo "<img src='".esc_url($featured_thumb)."' style='width:120px;height:120px;object-fit:cover;border-radius:6px;border:2px solid #ccc;'>";
                                    }
                                }
                                ?>
                            </div>
                            <button type='button' id='spcu-area-select-featured-image' class='button'>Select Featured Image</button>
                            <button type='button' id='spcu-area-remove-featured-image' class='button' <?= $featured_id ? '' : 'style="display:none;"' ?>>Remove</button>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label>Images</label></th>
                        <td>
                            <input type='hidden' name='images' id='spcu_area_images_ids' value='<?= esc_attr($edit_area->images ?? '') ?>'>
                            <div id="spcu-area-image-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                                <?php
                                if(!empty($edit_area->images)){
                                    foreach(explode(',', $edit_area->images) as $img_id){
                                        $img_id = intval($img_id);
                                        if($img_id){
                                            $thumb = wp_get_attachment_image_url($img_id,'thumbnail');
                                            if($thumb) echo "<div class='spcu-area-img-wrap' data-id='$img_id' style='position:relative;display:inline-block;'>
                                                <img src='".esc_url($thumb)."' style='width:80px;height:80px;object-fit:cover;border-radius:4px;border:2px solid #ccc;'>
                                                <span class='spcu-area-img-remove' data-id='$img_id' title='Remove' style='position:absolute;top:-6px;right:-6px;background:#c00;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;line-height:1;'>✕</span>
                                            </div>";
                                        }
                                    }
                                }
                                ?>
                            </div>
                            <button type='button' id='spcu-area-add-images' class='button'><?= $edit_area ? 'Manage Images' : 'Add Images' ?></button>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <?php if($edit_area): ?>
                        <button name='edit_area' value='1' class="button button-primary">Update Area</button>
                        <a href='?page=spcu-areas' class='button'>Cancel</a>
                    <?php else: ?>
                        <button name='add_area' class="button button-primary">Add New Area</button>
                    <?php endif; ?>
                </p>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    var mediaFrame;
    var featuredMediaFrame;
    var idsField = document.getElementById('spcu_area_images_ids');
    var preview = document.getElementById('spcu-area-image-preview');
    var addBtn = document.getElementById('spcu-area-add-images');
    var featuredIdField = document.getElementById('spcu_area_featured_image_id');
    var featuredPreview = document.getElementById('spcu-area-featured-image-preview');
    var featuredSelectBtn = document.getElementById('spcu-area-select-featured-image');
    var featuredRemoveBtn = document.getElementById('spcu-area-remove-featured-image');

    function getIds(){ return idsField && idsField.value ? idsField.value.split(',').filter(Boolean) : []; }
    function setIds(arr){ if(idsField) idsField.value = arr.join(','); }

    function setFeaturedImage(id, url){
        if(!featuredIdField || !featuredPreview) return;
        featuredIdField.value = id ? String(id) : '0';
        if(url){
            featuredPreview.innerHTML = '<img src="'+url+'" style="width:120px;height:120px;object-fit:cover;border-radius:6px;border:2px solid #ccc;">';
            if(featuredRemoveBtn) featuredRemoveBtn.style.display = '';
        } else {
            featuredPreview.innerHTML = '';
            if(featuredRemoveBtn) featuredRemoveBtn.style.display = 'none';
        }
    }

    function addThumb(id, url){
        var wrap = document.createElement('div');
        wrap.className = 'spcu-area-img-wrap';
        wrap.dataset.id = id;
        wrap.style.cssText = 'position:relative;display:inline-block;';
        wrap.innerHTML = '<img src="'+url+'" style="width:80px;height:80px;object-fit:cover;border-radius:4px;border:2px solid #ccc;">'
            + '<span class="spcu-area-img-remove" data-id="'+id+'" title="Remove" style="position:absolute;top:-6px;right:-6px;background:#c00;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;line-height:1;">✕</span>';
        preview.appendChild(wrap);
    }

    if(featuredSelectBtn){
        featuredSelectBtn.addEventListener('click', function(e){
            e.preventDefault();
            if(featuredMediaFrame){ featuredMediaFrame.open(); return; }
            featuredMediaFrame = wp.media({
                title: 'Select Featured Image',
                button: { text: 'Use as Featured Image' },
                multiple: false,
                library: { type: 'image' }
            });
            featuredMediaFrame.on('select', function(){
                var attachment = featuredMediaFrame.state().get('selection').first();
                if(!attachment) return;
                var id = attachment.attributes.id;
                var previewUrl = attachment.attributes.sizes && attachment.attributes.sizes.medium
                    ? attachment.attributes.sizes.medium.url
                    : attachment.attributes.url;
                setFeaturedImage(id, previewUrl);
            });
            featuredMediaFrame.open();
        });
    }

    if(featuredRemoveBtn){
        featuredRemoveBtn.addEventListener('click', function(e){
            e.preventDefault();
            setFeaturedImage(0, '');
        });
    }

    if(addBtn){
        addBtn.addEventListener('click', function(e){
            e.preventDefault();
            if(mediaFrame){ mediaFrame.open(); return; }
            mediaFrame = wp.media({
                title: 'Select Images',
                button: { text: 'Add Images' },
                multiple: true,
                library: { type: 'image' }
            });
            mediaFrame.on('select', function(){
                var selection = mediaFrame.state().get('selection');
                var ids = getIds();
                selection.each(function(attachment){
                    var id = String(attachment.attributes.id);
                    if(ids.indexOf(id) === -1){
                        ids.push(id);
                        var thumb = attachment.attributes.sizes && attachment.attributes.sizes.thumbnail
                            ? attachment.attributes.sizes.thumbnail.url
                            : attachment.attributes.url;
                        addThumb(id, thumb);
                    }
                });
                setIds(ids);
            });
            mediaFrame.open();
        });
    }

    if(preview){
        preview.addEventListener('click', function(e){
            var rm = e.target.closest('.spcu-area-img-remove');
            if(!rm) return;
            var id = rm.dataset.id;
            var ids = getIds().filter(function(v){ return v !== id; });
            setIds(ids);
            var wrap = rm.closest('.spcu-area-img-wrap');
            if(wrap) wrap.remove();
        });
    }
});
</script>
