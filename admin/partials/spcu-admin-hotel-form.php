<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$areas = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spcu_areas");
$grade_options = SPCU_Grades::options();
$hotel_table = $wpdb->prefix.'spcu_hotels';
$hotel_form_error = '';

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

// Ensure hotels table has required columns.
$schema_error = '';
$table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $hotel_table));
if(!$table_exists && class_exists('SPCU_Database')){
    SPCU_Database::create_tables();
    $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $hotel_table));
}

$schema_columns = [
    'area_id' => 'INT NULL',
    'grade' => 'VARCHAR(50) NULL',
    'short_description' => 'VARCHAR(200) NULL',
    'description' => 'TEXT NULL',
    'facilities' => 'TEXT NULL',
    'featured_image' => 'INT NULL',
    'is_featured' => 'TINYINT(1) DEFAULT 0',
];
$missing_columns = [];

if($table_exists){
    foreach($schema_columns as $column => $definition){
        $exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", $column));
        if(!$exists){
            $wpdb->query("ALTER TABLE {$hotel_table} ADD COLUMN {$column} {$definition}");
            $exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", $column));
        }
        if(!$exists){
            $missing_columns[] = $column;
        }
    }
}

if(!$table_exists || !empty($missing_columns)){
    $schema_error = 'Database schema is not up to date for hotels table.';
    if(!empty($missing_columns)){
        $schema_error .= ' Missing: ' . implode(', ', $missing_columns) . '.';
    }
    if($wpdb->last_error){
        $schema_error .= ' ' . $wpdb->last_error;
    }
}

$edit_hotel = null;
if(isset($_GET['edit'])){
    $edit_hotel = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}spcu_hotels WHERE id=".intval($_GET['edit']));
}

// Enqueue WP Media.
wp_enqueue_media();
?>

<div class='wrap'>
    <?php spcu_admin_breadcrumb([
        ['label' => 'Ski Engine', 'url' => admin_url('admin.php?page=spcu-dashboard')],
        ['label' => 'Hotels', 'url' => admin_url('admin.php?page=spcu-hotels')],
        ['label' => $edit_hotel ? 'Edit Hotel' : 'Add Hotel']
    ]); ?>

    <h1><?= $edit_hotel ? 'Edit Hotel' : 'Add Hotel' ?></h1>

    <?php if($schema_error): ?>
        <div class="notice notice-error"><p><?= esc_html($schema_error) ?></p></div>
        <div class="spcu-toast-source" data-type="error" data-message="<?= esc_attr($schema_error) ?>"></div>
    <?php endif; ?>

    <?php if($hotel_form_error): ?>
        <div class="notice notice-error"><p><?= esc_html($hotel_form_error) ?></p></div>
        <div class="spcu-toast-source" data-type="error" data-message="<?= esc_attr($hotel_form_error) ?>"></div>
    <?php endif; ?>

    <form method='post' id="hotel-form">
        <?php wp_nonce_field('spcu_save_hotel'); ?>
        <?php if($edit_hotel): ?>
            <input type='hidden' name='hotel_id' value='<?= esc_attr($edit_hotel->id) ?>'>
        <?php endif; ?>

        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="name">Hotel Name</label></th>
                <td><input name='name' id='name' class="regular-text" placeholder='Hakuba Grand Hotel' required value="<?= esc_attr($edit_hotel->name ?? '') ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="name_ja">Name (Japanese)</label></th>
                <td><input name='name_ja' id='name_ja' class="regular-text" placeholder='白馬グランドホテル' value="<?= esc_attr($edit_hotel->name_ja ?? '') ?>"></td>
            </tr>
            <tr>
                <th scope="row"><label for="short_description">Short Description</label></th>
                <td>
                    <textarea name='short_description' id='short_description' class="large-text" rows="2" maxlength="255" placeholder='Ski-in, ski-out hotel with mountain views'><?= esc_textarea($edit_hotel->short_description ?? '') ?></textarea>
                    <p class="description">255 character limit.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="description">Description</label></th>
                <td>
                    <?php wp_editor($edit_hotel->description ?? '', 'description', ['media_buttons' => true, 'teeny' => false, 'textarea_rows' => 10]); ?>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="spcu_facilities_input">Facilities</label></th>
                <td>
                    <input type="hidden" name="spcu_facilities" id="spcu_facilities_hidden" value="">
                    <div style="margin-bottom:10px;display:flex;flex-wrap:wrap;gap:6px;" id="spcu-facilities-tags"></div>
                    <div style="position:relative;">
                        <input type="text" id="spcu_facilities_input" placeholder="Search or add facility (e.g., Free Wi-Fi, Onsen, Gym)" style="width:100%;padding:6px 10px;border:1px solid #c3c4c7;border-radius:4px;" autocomplete="off">
                        <div id="spcu-facility-autocomplete" style="display:none;position:absolute;background:#fff;border:1px solid #999;border-radius:4px;margin-top:2px;z-index:1000;max-height:200px;overflow-y:auto;min-width:100%;box-shadow:0 2px 8px rgba(0,0,0,0.1);"></div>
                    </div>
                    <p style="margin:8px 0 0 0;font-size:12px;color:#666;">Start typing to search existing facilities or press Enter to create new</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="address">Address</label></th>
                <td><textarea name='address' id='address' class="regular-text" rows="2" placeholder="123 Hakuba Village, Nagano"><?= esc_textarea($edit_hotel->address ?? '') ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="address_ja">Address (Japanese)</label></th>
                <td><textarea name='address_ja' id='address_ja' class="regular-text" rows="2" placeholder="〒399-9301 長野県北安曇郡白馬村"><?= esc_textarea($edit_hotel->address_ja ?? '') ?></textarea></td>
            </tr>
            <tr>
                <th scope="row"><label for="area_id">Area</label></th>
                <td>
                    <select name='area_id' id='area_id' required>
                        <option value=''>- Select Area -</option>
                        <?php foreach($areas as $a): ?>
                            <option value="<?= esc_attr($a->id) ?>" <?= (($edit_hotel && $edit_hotel->area_id == $a->id) || (isset($_POST['area_id']) && intval($_POST['area_id']) === intval($a->id))) ? 'selected' : '' ?>><?= esc_html($a->name) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="grade">Difficulty</label></th>
                <td>
                    <select name='grade' id='grade' required>
                        <option value=''>- Select Difficulty -</option>
                        <?php foreach($grade_options as $grade_key => $grade_label): ?>
                            <option value="<?= esc_attr($grade_key) ?>" <?= (($edit_hotel && $edit_hotel->grade == $grade_key) || (isset($_POST['grade']) && SPCU_Grades::normalize($_POST['grade']) === $grade_key)) ? 'selected' : '' ?>><?= esc_html($grade_label) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">Manage difficulty names and colors in <a href="<?= esc_url(admin_url('admin.php?page=spcu-difficulties')) ?>">Difficulties</a>.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="is_featured">Featured</label></th>
                <td>
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type='checkbox' name='is_featured' id='is_featured' value='1' <?= ($edit_hotel && $edit_hotel->is_featured) ? 'checked' : '' ?>>
                        <span>Mark this hotel as featured</span>
                    </label>
                    <p class="description">Featured hotels are highlighted in the admin list and public interface.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="spcu_featured_image_id">Featured Image</label></th>
                <td>
                    <input type='hidden' name='featured_image' id='spcu_featured_image_id' value="<?= esc_attr(isset($edit_hotel->featured_image) ? intval($edit_hotel->featured_image) : 0) ?>">
                    <div id="spcu-featured-image-preview" style="margin-bottom:10px;">
                        <?php
                        $featured_id = isset($edit_hotel->featured_image) ? intval($edit_hotel->featured_image) : 0;
                        if($featured_id){
                            $featured_thumb = wp_get_attachment_image_url($featured_id, 'medium');
                            if($featured_thumb){
                                echo "<img src='".esc_url($featured_thumb)."' style='width:120px;height:120px;object-fit:cover;border-radius:6px;border:2px solid #ccc;'>";
                            }
                        }
                        ?>
                    </div>
                    <button type='button' id='spcu-select-featured-image' class='button'>Select Featured Image</button>
                    <button type='button' id='spcu-remove-featured-image' class='button' <?= $featured_id ? '' : 'style="display:none;"' ?>>Remove</button>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Images</label></th>
                <td>
                    <input type='hidden' name='images' id='spcu_images_ids' value="<?= esc_attr($edit_hotel->images ?? '') ?>">

                    <div id="spcu-image-preview" style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:10px;">
                        <?php
                        if(!empty($edit_hotel->images)){
                            foreach(explode(',',$edit_hotel->images) as $img_id){
                                $img_id = intval($img_id);
                                if($img_id){
                                    $thumb = wp_get_attachment_image_url($img_id,'thumbnail');
                                    if($thumb) echo "<div class='spcu-img-wrap' data-id='$img_id' style='position:relative;display:inline-block;'>
                                        <img src='".esc_url($thumb)."' style='width:80px;height:80px;object-fit:cover;border-radius:4px;border:2px solid #ccc;'>
                                        <span class='spcu-img-remove' data-id='$img_id' title='Remove' style='position:absolute;top:-6px;right:-6px;background:#c00;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;line-height:1;'>✕</span>
                                    </div>";
                                }
                            }
                        }
                        ?>
                    </div>

                    <button type='button' id='spcu-add-images' class='button'>
                        <?= $edit_hotel ? 'Manage Images' : 'Add Images' ?>
                    </button>
                </td>
            </tr>
        </table>

        <p class="submit">
            <?php if($edit_hotel): ?>
                <button name='edit_hotel' value='1' class="button button-primary">Update Hotel</button>
                <a href='?page=spcu-hotels' class='button'>Cancel</a>
            <?php else: ?>
                <button name='add_hotel' value='1' class="button button-primary">Add Hotel</button>
                <a href='?page=spcu-hotels' class='button'>Back to Hotels</a>
            <?php endif; ?>
        </p>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){

    var mediaFrame;
    var featuredMediaFrame;
    var idsField  = document.getElementById('spcu_images_ids');
    var preview   = document.getElementById('spcu-image-preview');
    var addBtn    = document.getElementById('spcu-add-images');
    var featuredIdField = document.getElementById('spcu_featured_image_id');
    var featuredPreview = document.getElementById('spcu-featured-image-preview');
    var featuredSelectBtn = document.getElementById('spcu-select-featured-image');
    var featuredRemoveBtn = document.getElementById('spcu-remove-featured-image');

    function getIds(){ return idsField.value ? idsField.value.split(',').filter(Boolean) : []; }
    function setIds(arr){ idsField.value = arr.join(','); }

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
        wrap.className = 'spcu-img-wrap';
        wrap.dataset.id = id;
        wrap.style.cssText = 'position:relative;display:inline-block;';
        wrap.innerHTML = '<img src="'+url+'" style="width:80px;height:80px;object-fit:cover;border-radius:4px;border:2px solid #ccc;">'
                       + '<span class="spcu-img-remove" data-id="'+id+'" title="Remove" style="position:absolute;top:-6px;right:-6px;background:#c00;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;line-height:1;">✕</span>';
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
                title: 'Select Hotel Images',
                button: { text: 'Add to Hotel' },
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

    preview.addEventListener('click', function(e){
        var btn = e.target.closest('.spcu-img-remove');
        if(!btn) return;
        var id  = btn.dataset.id;
        var ids = getIds().filter(function(i){ return i !== id; });
        setIds(ids);
        btn.closest('.spcu-img-wrap').remove();
    });

    // Initialize facilities loading in edit mode
    var hotelIdField = document.querySelector('input[name="hotel_id"]');
    if(hotelIdField && typeof window.loadHotelFacilities === 'function'){
        window.loadHotelFacilities();
    }

});
</script>
