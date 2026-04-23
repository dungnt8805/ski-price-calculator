<?php
if (!defined('ABSPATH')) exit;

global $wpdb;
$table = $wpdb->prefix . 'spcu_inquiries';

// Auto-create table if missing
if(!(bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table))){
    if(class_exists('SPCU_Database')) SPCU_Database::create_tables();
}

if(!function_exists('spcu_admin_breadcrumb')){
    function spcu_admin_breadcrumb($items){
        echo '<nav class="spcu-breadcrumb" aria-label="Breadcrumb">';
        $last = count($items) - 1;
        foreach($items as $i => $item){
            if($i > 0) echo '<span class="spcu-breadcrumb-sep">/</span>';
            if(!empty($item['url']) && $i !== $last)
                echo '<a href="'.esc_url($item['url']).'">'.esc_html($item['label']).'</a>';
            else
                echo '<span class="current">'.esc_html($item['label']).'</span>';
        }
        echo '</nav>';
    }
}

// ── Handle status change / delete ───────────────────────────────
$action_msg = '';
if(!empty($_GET['inq_action']) && !empty($_GET['inq_id']) && !empty($_GET['_wpnonce'])){
    $inq_id    = intval($_GET['inq_id']);
    $inq_act   = sanitize_key($_GET['inq_action']);
    $nonce_key = 'spcu_inq_' . $inq_act . '_' . $inq_id;

    if(wp_verify_nonce($_GET['_wpnonce'], $nonce_key)){
        if($inq_act === 'delete'){
            $wpdb->delete($table, ['id' => $inq_id]);
            $action_msg = 'Inquiry deleted.';
        } elseif(in_array($inq_act, ['read','replied','new'], true)){
            $wpdb->update($table, ['status' => $inq_act], ['id' => $inq_id]);
            $action_msg = 'Status updated to "' . $inq_act . '".';
        }
    }
    // Redirect to clean URL
    $redirect_url = add_query_arg(
        ['page' => 'spcu-inquiries', 'spcu_msg' => rawurlencode($action_msg)],
        admin_url('admin.php')
    );
    wp_safe_redirect($redirect_url);
    exit;
}

if(!empty($_GET['spcu_msg'])){
    $action_msg = sanitize_text_field(urldecode($_GET['spcu_msg']));
}

// ── Detail view ───────────────────────────────────────────────────
$view_id = !empty($_GET['view']) ? intval($_GET['view']) : 0;
$inq     = null;
if($view_id > 0){
    $inq = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $view_id));
    // Auto-mark as read
    if($inq && $inq->status === 'new'){
        $wpdb->update($table, ['status' => 'read'], ['id' => $view_id]);
        $inq->status = 'read';
    }
}

// ── Filter / list ────────────────────────────────────────────────
$filter_status = sanitize_key($_GET['status'] ?? '');
$where_sql     = $filter_status ? $wpdb->prepare(" WHERE status = %s", $filter_status) : '';
$rows          = $wpdb->get_results("SELECT * FROM {$table}{$where_sql} ORDER BY created_at DESC");
$counts        = $wpdb->get_results("SELECT status, COUNT(*) as cnt FROM {$table} GROUP BY status");
$count_map     = ['new' => 0, 'read' => 0, 'replied' => 0];
foreach($counts as $c){ $count_map[$c->status] = (int)$c->cnt; }
$total = array_sum($count_map);

$status_labels = ['new' => 'New', 'read' => 'Read', 'replied' => 'Replied'];
$status_colors = [
    'new'     => '#2271b1;color:#fff',
    'read'    => '#64748b;color:#fff',
    'replied' => '#059669;color:#fff',
];

function spcu_inq_status_badge($status){
    global $status_colors;
    $color = $status_colors[$status] ?? '#64748b;color:#fff';
    $label = ucfirst($status);
    return "<span style='display:inline-block;padding:2px 10px;border-radius:20px;font-size:11px;font-weight:700;background:{$color};'>{$label}</span>";
}

function spcu_inq_action_url($action, $id){
    return wp_nonce_url(
        add_query_arg(['page' => 'spcu-inquiries', 'inq_action' => $action, 'inq_id' => $id], admin_url('admin.php')),
        'spcu_inq_' . $action . '_' . $id
    );
}
?>

<div class="wrap">
<?php spcu_admin_breadcrumb([
    ['label' => 'Ski Engine', 'url' => admin_url('admin.php?page=spcu-dashboard')],
    $view_id ? ['label' => 'Inquiries', 'url' => admin_url('admin.php?page=spcu-inquiries')] : ['label' => 'Inquiries'],
    ...($view_id && $inq ? [['label' => '#'.$inq->id.' — '.$inq->first_name.' '.$inq->last_name]] : []),
]); ?>

<div class="spcu-header-row">
    <h1>Enquiries</h1>
</div>

<?php if($action_msg): ?>
    <div class="notice notice-success is-dismissible"><p><?= esc_html($action_msg) ?></p></div>
<?php endif; ?>

<?php if($inq): ?>
    <!-- ── DETAIL VIEW ──────────────────────────────────── -->
    <style>
    .spcu-inq-detail{background:#fff;border:1px solid #ddd;border-radius:8px;padding:24px;max-width:700px;margin-top:16px;}
    .spcu-inq-detail table{width:100%;border-collapse:collapse;}
    .spcu-inq-detail table th{width:180px;text-align:left;padding:8px 12px;font-weight:600;color:#1d2327;font-size:13px;vertical-align:top;}
    .spcu-inq-detail table td{padding:8px 12px;color:#1d2327;font-size:13px;vertical-align:top;}
    .spcu-inq-detail table tr{border-bottom:1px solid #f0f0f0;}
    .spcu-inq-detail table tr:last-child{border-bottom:none;}
    .spcu-inq-detail .inq-message{white-space:pre-wrap;background:#f9f9f9;padding:10px 14px;border-radius:6px;border:1px solid #e0e0e0;font-size:13px;}
    .spcu-inq-actions{margin-top:16px;display:flex;gap:8px;flex-wrap:wrap;}
    </style>
    <div class="spcu-inq-detail">
        <table>
            <tr><th>Status</th><td><?= spcu_inq_status_badge($inq->status) ?></td></tr>
            <tr><th>Name</th><td><?= esc_html($inq->first_name . ' ' . $inq->last_name) ?></td></tr>
            <tr><th>Email</th><td><a href="mailto:<?= esc_attr($inq->email) ?>"><?= esc_html($inq->email) ?></a></td></tr>
            <?php if($inq->phone): ?><tr><th>Phone</th><td><?= esc_html($inq->phone) ?></td></tr><?php endif; ?>
            <?php if($inq->country): ?><tr><th>Country</th><td><?= esc_html($inq->country) ?></td></tr><?php endif; ?>
            <?php if($inq->resort): ?><tr><th>Resort</th><td><?= esc_html($inq->resort) ?></td></tr><?php endif; ?>
            <?php if($inq->package_level): ?><tr><th>Package Level</th><td><?= esc_html(ucfirst($inq->package_level)) ?></td></tr><?php endif; ?>
            <?php if($inq->check_in): ?><tr><th>Check-in</th><td><?= esc_html($inq->check_in) ?></td></tr><?php endif; ?>
            <?php if($inq->check_out): ?><tr><th>Check-out</th><td><?= esc_html($inq->check_out) ?></td></tr><?php endif; ?>
            <?php if($inq->num_guests): ?><tr><th>Guests</th><td><?= esc_html($inq->num_guests) ?></td></tr><?php endif; ?>
            <?php if($inq->experience): ?><tr><th>Experience</th><td><?= esc_html(str_replace('_', ' ', ucfirst($inq->experience))) ?></td></tr><?php endif; ?>
            <tr><th>Submitted</th><td><?= esc_html($inq->created_at) ?></td></tr>
            <?php if($inq->message): ?>
            <tr>
                <th>Message</th>
                <td><div class="inq-message"><?= esc_html($inq->message) ?></div></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <div class="spcu-inq-actions">
        <?php if($inq->status !== 'replied'): ?>
            <a href="<?= esc_url(spcu_inq_action_url('replied', $inq->id)) ?>" class="button button-primary">Mark as Replied</a>
        <?php endif; ?>
        <?php if($inq->status !== 'read'): ?>
            <a href="<?= esc_url(spcu_inq_action_url('read', $inq->id)) ?>" class="button">Mark as Read</a>
        <?php endif; ?>
        <?php if($inq->status !== 'new'): ?>
            <a href="<?= esc_url(spcu_inq_action_url('new', $inq->id)) ?>" class="button">Mark as New</a>
        <?php endif; ?>
        <a href="mailto:<?= esc_attr($inq->email) ?>" class="button">Reply by Email</a>
        <a href="<?= esc_url(spcu_inq_action_url('delete', $inq->id)) ?>" class="button" style="color:#b32d2e;"
           onclick="return confirm('Delete this enquiry?')">Delete</a>
    </div>

<?php else: ?>
    <!-- ── LIST VIEW ────────────────────────────────────── -->
    <ul class="subsubsub" style="margin:8px 0 16px;">
        <li><a href="<?= esc_url(admin_url('admin.php?page=spcu-inquiries')) ?>" <?= !$filter_status ? 'class="current"' : '' ?>>All <span class="count">(<?= $total ?>)</span></a></li>
        <?php foreach($status_labels as $sk => $sl): ?>
        <li> | <a href="<?= esc_url(admin_url('admin.php?page=spcu-inquiries&status='.$sk)) ?>" <?= $filter_status === $sk ? 'class="current"' : '' ?>><?= $sl ?> <span class="count">(<?= $count_map[$sk] ?>)</span></a></li>
        <?php endforeach; ?>
    </ul>

    <div class="spcu-table">
        <table class="wp-list-table widefat fixed striped table-view-list">
            <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Resort</th>
                    <th>Dates</th>
                    <th style="width:60px;">Guests</th>
                    <th style="width:80px;">Status</th>
                    <th>Submitted</th>
                    <th style="width:120px;">Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if(empty($rows)): ?>
                <tr><td colspan="9" style="text-align:center;padding:24px;color:#777;">No enquiries yet.</td></tr>
            <?php endif; ?>
            <?php foreach($rows as $r): ?>
            <tr <?= $r->status === 'new' ? 'style="font-weight:700;"' : '' ?>>
                <td><?= esc_html($r->id) ?></td>
                <td><?= esc_html($r->first_name . ' ' . $r->last_name) ?></td>
                <td><a href="mailto:<?= esc_attr($r->email) ?>"><?= esc_html($r->email) ?></a></td>
                <td><?= esc_html($r->resort ?: '—') ?></td>
                <td>
                    <?php if($r->check_in || $r->check_out): ?>
                        <?= esc_html($r->check_in ?: '?') ?> → <?= esc_html($r->check_out ?: '?') ?>
                    <?php else: ?>—<?php endif; ?>
                </td>
                <td><?= esc_html($r->num_guests ?: '—') ?></td>
                <td><?= spcu_inq_status_badge($r->status) ?></td>
                <td style="font-size:12px;color:#666;"><?= esc_html(date('M j, Y', strtotime($r->created_at))) ?></td>
                <td>
                    <a class="button button-small" href="<?= esc_url(add_query_arg(['page' => 'spcu-inquiries', 'view' => $r->id], admin_url('admin.php'))) ?>">View</a>
                    <a href="<?= esc_url(spcu_inq_action_url('delete', $r->id)) ?>" style="margin-left:4px;color:#b32d2e;font-size:12px;"
                       onclick="return confirm('Delete?')">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
</div>
