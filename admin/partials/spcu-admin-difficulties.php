<?php
if (!defined('ABSPATH')) exit;

$difficulty_records = SPCU_Grades::records();
$edit_slug = sanitize_key($_GET['edit'] ?? '');
$edit_difficulty = SPCU_Grades::get($edit_slug);

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
?>

<div class="wrap">
    <?php spcu_admin_breadcrumb([
        ['label' => 'Ski Engine', 'url' => admin_url('admin.php?page=spcu-dashboard')],
        ['label' => 'Difficulties']
    ]); ?>

    <div class="spcu-header-row">
        <h1>Difficulties</h1>
    </div>

    <p>Manage difficulty names, slugs, and colors used by hotels and transport pricing.</p>

    <div style="display:grid;grid-template-columns:minmax(448px,608px) minmax(420px,1fr);gap:24px;align-items:start;max-width:1600px;">
        <div class="postbox" style="padding:24px;">
            <h2 style="margin-top:0;"><?= $edit_difficulty ? 'Edit Difficulty' : 'Add Difficulty' ?></h2>

            <form method="post">
                <?php wp_nonce_field('spcu_save_difficulty'); ?>
                <?php if($edit_difficulty): ?>
                    <input type="hidden" name="original_slug" value="<?= esc_attr($edit_difficulty['slug']) ?>">
                <?php endif; ?>
                <input type="hidden" name="page" value="spcu-difficulties">

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="spcu_difficulty_name">Name</label></th>
                        <td><input type="text" class="regular-text" style="width:100%;max-width:none;" id="spcu_difficulty_name" name="name" required value="<?= esc_attr($edit_difficulty['name'] ?? '') ?>" placeholder="Beginner"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="spcu_difficulty_slug">Slug</label></th>
                        <td>
                            <input type="text" class="regular-text" style="width:100%;max-width:none;" id="spcu_difficulty_slug" name="slug" required value="<?= esc_attr($edit_difficulty['slug'] ?? '') ?>" placeholder="beginner">
                            <p class="description">Used internally for hotel and price records.</p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="spcu_difficulty_description">Description</label></th>
                        <td>
                            <textarea id="spcu_difficulty_description" name="description" rows="5" class="large-text" style="width:100%;max-width:none;" placeholder="Optional description for this difficulty."><?= esc_textarea($edit_difficulty['description'] ?? '') ?></textarea>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="spcu_difficulty_color">Color</label></th>
                        <td><input type="color" id="spcu_difficulty_color" name="color" value="<?= esc_attr($edit_difficulty['color'] ?? '#111111') ?>"></td>
                    </tr>
                </table>

                <p>
                    <button type="submit" name="<?= $edit_difficulty ? 'edit_difficulty' : 'add_difficulty' ?>" class="button button-primary">
                        <?= $edit_difficulty ? 'Update Difficulty' : 'Add Difficulty' ?>
                    </button>
                    <?php if($edit_difficulty): ?>
                        <a href="<?= esc_url(admin_url('admin.php?page=spcu-difficulties')) ?>" class="button">Cancel</a>
                    <?php endif; ?>
                </p>
            </form>
        </div>

        <div class="spcu-table">
            <table>
                <tr>
                    <th>Name</th><th>Slug</th><th>Description</th><th>Action</th>
                </tr>
                <?php foreach($difficulty_records as $difficulty_record): ?>
                    <?php
                    $delete_url = wp_nonce_url(
                        add_query_arg([
                            'page' => 'spcu-difficulties',
                            'delete' => $difficulty_record['slug'],
                        ], admin_url('admin.php')),
                        'spcu_delete_difficulty_' . $difficulty_record['slug']
                    );
                    ?>
                    <tr>
                        <td>
                            <span style="display:inline-block;padding:4px 10px;border-radius:999px;background:<?= esc_attr($difficulty_record['color']) ?>;color:<?= esc_attr(SPCU_Grades::text_color($difficulty_record['slug'])) ?>;font-size:12px;font-weight:600;">
                                <?= esc_html($difficulty_record['name']) ?>
                            </span>
                        </td>
                        <td><?= esc_html($difficulty_record['slug']) ?></td>
                        <td style="min-width:160px;"><?= !empty($difficulty_record['description']) ? nl2br(esc_html($difficulty_record['description'])) : '<span style="color:#646970;">-</span>' ?></td>
                        <td style="white-space:nowrap;">
                            <a class="button button-small" href="<?= esc_url(add_query_arg(['page' => 'spcu-difficulties', 'edit' => $difficulty_record['slug']], admin_url('admin.php'))) ?>">Edit</a>
                            <?php if(count($difficulty_records) > 1): ?>
                                <a class="spcu-delete" href="<?= esc_url($delete_url) ?>">Delete</a>
                            <?php else: ?>
                                <span style="color:#646970;">Keep at least one</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
</div>