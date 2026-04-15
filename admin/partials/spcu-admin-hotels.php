<?php
if (!defined('ABSPATH')) exit;

global $wpdb;

$areas = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}spcu_areas");
$grade_options = SPCU_Grades::options();
$hotel_table = $wpdb->prefix.'spcu_hotels';

// Backward-compat: ensure hotels table schema supports Area + Grade fields.
$schema_error = '';
$table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $hotel_table));
if(!$table_exists && class_exists('SPCU_Database')){
    SPCU_Database::create_tables();
    $table_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $hotel_table));
}

$area_column_exists = false;
$grade_column_exists = false;

if($table_exists){
    $area_column_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", 'area_id'));
    $grade_column_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", 'grade'));

    if(!$area_column_exists){
        $wpdb->query("ALTER TABLE {$hotel_table} ADD COLUMN area_id INT NULL");
        $area_column_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", 'area_id'));
    }

    if(!$grade_column_exists){
        $wpdb->query("ALTER TABLE {$hotel_table} ADD COLUMN grade VARCHAR(50) NULL");
        $grade_column_exists = (bool) $wpdb->get_var($wpdb->prepare("SHOW COLUMNS FROM {$hotel_table} LIKE %s", 'grade'));
    }
}

if(!$table_exists || !$area_column_exists || !$grade_column_exists){
    $schema_error = 'Database schema is not up to date for hotels table.';
    if($wpdb->last_error){
        $schema_error .= ' ' . $wpdb->last_error;
    }
}

$hotel_form_error = '';

// Handle Add
if(isset($_POST['add_hotel'])){
    $images = isset($_POST['images']) ? sanitize_text_field($_POST['images']) : '';
    $area_id = intval($_POST['area_id'] ?? 0);
    $grade   = SPCU_Grades::normalize($_POST['grade'] ?? '');

    if($schema_error !== ''){
        $hotel_form_error = $schema_error;
    } elseif(empty($areas)){
        $hotel_form_error = 'Please create at least one Area before adding a hotel.';
    } elseif($area_id <= 0 || $grade === ''){
        $hotel_form_error = 'Please choose both Area and Grade before saving a hotel.';
    } else {
        $ok = $wpdb->insert($wpdb->prefix.'spcu_hotels',[
            'name'       => sanitize_text_field($_POST['name']),
            'name_ja'    => sanitize_text_field($_POST['name_ja']),
            'address'    => sanitize_textarea_field($_POST['address']),
            'address_ja' => sanitize_textarea_field($_POST['address_ja']),
            'images'     => $images,
            'area_id'    => $area_id,
            'grade'      => $grade
        ]);

        if($ok === false){
            $hotel_form_error = 'Could not save hotel. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
        }
    }
}

// Handle Edit
if(isset($_POST['edit_hotel'])){
    $images = isset($_POST['images']) ? sanitize_text_field($_POST['images']) : '';
    $area_id = intval($_POST['area_id'] ?? 0);
    $grade   = SPCU_Grades::normalize($_POST['grade'] ?? '');

    if($schema_error !== ''){
        $hotel_form_error = $schema_error;
    } elseif(empty($areas)){
        $hotel_form_error = 'Please create at least one Area before updating a hotel.';
    } elseif($area_id <= 0 || $grade === ''){
        $hotel_form_error = 'Please choose both Area and Grade before updating a hotel.';
    } else {
        $ok = $wpdb->update($wpdb->prefix.'spcu_hotels',[
            'name'       => sanitize_text_field($_POST['name']),
            'name_ja'    => sanitize_text_field($_POST['name_ja']),
            'address'    => sanitize_textarea_field($_POST['address']),
            'address_ja' => sanitize_textarea_field($_POST['address_ja']),
            'images'     => $images,
            'area_id'    => $area_id,
            'grade'      => $grade
        ], ['id' => intval($_POST['hotel_id'])]);

        if($ok === false){
            $hotel_form_error = 'Could not update hotel. ' . ($wpdb->last_error ? $wpdb->last_error : 'Please try again.');
        }
    }
}

// Handle Delete
if(isset($_GET['delete'])){
    $wpdb->delete($wpdb->prefix.'spcu_hotels',['id'=>intval($_GET['delete'])]);
}

// Load hotel for editing
$edit_hotel = null;
if(isset($_GET['edit'])){
    $edit_hotel = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}spcu_hotels WHERE id=".intval($_GET['edit']));
}

$rows = $wpdb->get_results("
SELECT h.*, a.name as area_name, h.grade as grade_name
FROM {$wpdb->prefix}spcu_hotels h
LEFT JOIN {$wpdb->prefix}spcu_areas a ON h.area_id = a.id
ORDER BY h.name ASC
");

// Enqueue WP Media
wp_enqueue_media();
?>

<div class='wrap'>
    <h1><?= $edit_hotel ? 'Edit Hotel' : 'Hotels' ?></h1>

    <?php if($hotel_form_error): ?>
        <div class="notice notice-error"><p><?= esc_html($hotel_form_error) ?></p></div>
    <?php endif; ?>

    <form method='post' id="hotel-form">
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
                <th scope="row"><label for="grade">Grade</label></th>
                <td>
                    <select name='grade' id='grade' required>
                        <option value=''>- Select Grade -</option>
                        <?php foreach($grade_options as $grade_key => $grade_label): ?>
                            <option value="<?= esc_attr($grade_key) ?>" <?= (($edit_hotel && $edit_hotel->grade == $grade_key) || (isset($_POST['grade']) && SPCU_Grades::normalize($_POST['grade']) === $grade_key)) ? 'selected' : '' ?>><?= esc_html($grade_label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label>Images</label></th>
                <td>
                    <!-- Hidden field stores comma-separated attachment IDs -->
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
            <?php endif; ?>
        </p>
    </form>

    <?php if(!$edit_hotel): ?>
    <hr>
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
                <td style="max-width:200px;font-size:12px;"><?= nl2br(esc_html($r->address ?: '-')) ?></td>
                <td><?= esc_html($r->area_name) ?></td>
                <td><?= esc_html(SPCU_Grades::label($r->grade_name) ?: '-') ?></td>
                <td><?= $thumbs ? implode('',$thumbs) : '-' ?></td>
                <td style="white-space:nowrap;">
                    <a href='?page=spcu-hotels&edit=<?= esc_html($r->id) ?>' class='button button-small'>Edit</a>
                    <a href='?page=spcu-hotel-prices&hotel=<?= esc_attr($r->id) ?>' class='button button-small'>Add hotel price here</a>
                    <a class='spcu-delete' href='?page=spcu-hotels&delete=<?= esc_html($r->id) ?>'>Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){

    // --- Media Library Image Picker ---
    var mediaFrame;
    var idsField  = document.getElementById('spcu_images_ids');
    var preview   = document.getElementById('spcu-image-preview');
    var addBtn    = document.getElementById('spcu-add-images');

    // Build IDs array from hidden field
    function getIds(){ return idsField.value ? idsField.value.split(',').filter(Boolean) : []; }
    function setIds(arr){ idsField.value = arr.join(','); }

    // Add a thumbnail to preview
    function addThumb(id, url){
        var wrap = document.createElement('div');
        wrap.className = 'spcu-img-wrap';
        wrap.dataset.id = id;
        wrap.style.cssText = 'position:relative;display:inline-block;';
        wrap.innerHTML = '<img src="'+url+'" style="width:80px;height:80px;object-fit:cover;border-radius:4px;border:2px solid #ccc;">'
                       + '<span class="spcu-img-remove" data-id="'+id+'" title="Remove" style="position:absolute;top:-6px;right:-6px;background:#c00;color:#fff;border-radius:50%;width:18px;height:18px;display:flex;align-items:center;justify-content:center;cursor:pointer;font-size:12px;line-height:1;">✕</span>';
        preview.appendChild(wrap);
    }

    // Open media library
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

    // Remove image on ✕ click (delegated)
    preview.addEventListener('click', function(e){
        var btn = e.target.closest('.spcu-img-remove');
        if(!btn) return;
        var id  = btn.dataset.id;
        var ids = getIds().filter(function(i){ return i !== id; });
        setIds(ids);
        btn.closest('.spcu-img-wrap').remove();
    });

});
</script>
