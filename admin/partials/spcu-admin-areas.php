<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix.'spcu_areas';

if(isset($_POST['add_area'])){
    $wpdb->insert($table,[
        'type'=>sanitize_text_field($_POST['type']),
        'name'=>sanitize_text_field($_POST['name']),
        'name_ja'=>sanitize_text_field($_POST['name_ja'])
    ]);
}

if(isset($_GET['delete'])){
    $wpdb->delete($table,['id'=>intval($_GET['delete'])]);
}

$rows = $wpdb->get_results("SELECT * FROM $table");
?>

<div class='wrap'>
    <h1>Areas</h1>
    <form method='post'>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="type">Area Type</label></th>
                <td>
                    <select name='type' id='type' required>
                        <option value='City'>City</option>
                        <option value='Town'>Town</option>
                        <option value='Village'>Village</option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="name">Area Name</label></th>
                <td><input name='name' id='name' class="regular-text" placeholder='Hakuba' required></td>
            </tr>
            <tr>
                <th scope="row"><label for="name_ja">Name (Japanese)</label></th>
                <td><input name='name_ja' id='name_ja' class="regular-text" placeholder='白馬'></td>
            </tr>
        </table>
        <p class="submit">
            <button name='add_area' class="button button-primary">Add Area</button>
        </p>
    </form>

    <div class='spcu-table'>
        <table>
            <tr><th>ID</th><th>Type</th><th>Name</th><th>Name (JA)</th><th>Action</th></tr>
            <?php foreach($rows as $r): ?>
            <tr>
                <td><?= esc_html($r->id) ?></td>
                <td><?= esc_html($r->type) ?></td>
                <td><?= esc_html($r->name) ?></td>
                <td><?= esc_html($r->name_ja) ?></td>
                <td><a class='spcu-delete' href='?page=spcu-areas&delete=<?= esc_html($r->id) ?>'>Delete</a></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
