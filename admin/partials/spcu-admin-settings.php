<?php
if (!defined('ABSPATH')) exit;

// Determine active tab from page slug
$current_page = sanitize_key($_GET['page'] ?? 'spcu-settings-general');
$tab = match($current_page) {
    'spcu-settings-inquiry' => 'inquiry',
    'spcu-settings-email'   => 'email',
    default                 => 'general',
};

// ── Save ────────────────────────────────────────────────────────
if (isset($_POST['spcu_settings_submit']) && check_admin_referer('spcu_save_settings_' . $tab)) {
    if ($tab === 'general') {
        update_option('spcu_inquiry_footer_text', wp_kses_post($_POST['spcu_inquiry_footer_text'] ?? ''));
    } elseif ($tab === 'inquiry') {
        update_option('spcu_inquiry_page_overline',   sanitize_text_field($_POST['spcu_inquiry_page_overline']   ?? ''));
        update_option('spcu_inquiry_page_heading',    sanitize_text_field($_POST['spcu_inquiry_page_heading']    ?? ''));
        update_option('spcu_inquiry_page_subheading', sanitize_textarea_field($_POST['spcu_inquiry_page_subheading'] ?? ''));
    } elseif ($tab === 'email') {
        update_option('spcu_admin_email_to',          sanitize_text_field($_POST['spcu_admin_email_to']          ?? ''));
        update_option('spcu_admin_email_subject',     sanitize_text_field($_POST['spcu_admin_email_subject']     ?? ''));
        update_option('spcu_admin_email_body',        wp_kses_post($_POST['spcu_admin_email_body']               ?? ''));
        update_option('spcu_customer_email_subject',  sanitize_text_field($_POST['spcu_customer_email_subject']  ?? ''));
        update_option('spcu_customer_email_body',     wp_kses_post($_POST['spcu_customer_email_body']            ?? ''));
    }
    echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
}

// ── Tab nav definitions ─────────────────────────────────────────
$tabs = [
    'general' => ['label' => 'General',      'page' => 'spcu-settings-general'],
    'inquiry' => ['label' => 'Inquiry Page',  'page' => 'spcu-settings-inquiry'],
    'email'   => ['label' => 'Email',         'page' => 'spcu-settings-email'],
];
?>
<div class="wrap spcu-settings-wrap">
    <style>
        .spcu-settings-wrap{max-width:1120px;margin-top:16px;}
        .spcu-settings-head{display:flex;align-items:flex-end;justify-content:space-between;gap:12px;margin-bottom:14px;}
        .spcu-settings-kicker{font-size:11px;font-weight:700;letter-spacing:.08em;text-transform:uppercase;color:#64748b;margin-bottom:2px;}
        .spcu-settings-wrap .wp-heading-inline{font-size:24px;font-weight:700;color:#0f172a;line-height:1.25;}
        .spcu-settings-sub{margin:6px 0 0;color:#64748b;font-size:13px;}
        .spcu-settings-tabs{display:flex;gap:6px;padding:8px;border:1px solid #d0d7de;background:#fff;border-radius:12px;margin:0;}
        .spcu-settings-tabs .nav-tab{float:none;border:none;background:transparent;border-radius:9px;padding:9px 14px;margin:0;color:#475569;font-weight:600;transition:all .16s ease;}
        .spcu-settings-tabs .nav-tab:hover{background:#f1f5f9;color:#0f172a;}
        .spcu-settings-tabs .nav-tab.nav-tab-active{background:#0f1b2d;color:#fff;box-shadow:0 2px 10px rgba(15,27,45,.15);}
        .spcu-settings-card{background:#fff;border:1px solid #d0d7de;border-radius:14px;padding:20px 22px;box-shadow:0 1px 3px rgba(15,23,42,.05);margin-top:16px;}
        .spcu-settings-card h2{margin:0 0 14px;font-size:16px;font-weight:700;color:#0f172a;}
        .spcu-settings-card .description{color:#64748b;}
        .spcu-settings-wrap .form-table th{width:240px;padding-top:14px;padding-bottom:14px;color:#1e293b;}
        .spcu-settings-wrap .form-table td{padding-top:14px;padding-bottom:14px;}
        .spcu-settings-wrap input.regular-text,
        .spcu-settings-wrap input.large-text,
        .spcu-settings-wrap textarea.large-text,
        .spcu-settings-wrap select{border-radius:8px;border-color:#cbd5e1;}
        .spcu-settings-wrap input:focus,
        .spcu-settings-wrap textarea:focus,
        .spcu-settings-wrap select:focus{border-color:#2563eb;box-shadow:0 0 0 1px #2563eb;}
        .spcu-settings-wrap .submit{margin-top:16px;padding-top:0;}
        .spcu-settings-wrap .button.button-primary{background:#0f1b2d;border-color:#0f1b2d;border-radius:8px;padding:0 16px;}
        .spcu-settings-wrap .button.button-primary:hover{background:#1d3557;border-color:#1d3557;}
    </style>

    <div class="spcu-settings-head">
        <div>
            <div class="spcu-settings-kicker">Setup</div>
            <h1 class="wp-heading-inline">Ski Engine Settings</h1>
            <p class="spcu-settings-sub">Configure inquiry page content, email delivery, and response templates.</p>
        </div>
    </div>
    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper spcu-settings-tabs">
        <?php foreach ($tabs as $key => $t): ?>
        <a href="<?= esc_url(admin_url('admin.php?page=' . $t['page'])) ?>"
           class="nav-tab<?= $tab === $key ? ' nav-tab-active' : '' ?>">
            <?= esc_html($t['label']) ?>
        </a>
        <?php endforeach; ?>
    </nav>

    <form method="post" action="" style="margin-top:1.5rem;">
        <?php wp_nonce_field('spcu_save_settings_' . $tab); ?>

        <?php if ($tab === 'general'): ?>
        <!-- ── GENERAL ─────────────────────────────────────────── -->
        <div class="spcu-settings-card">
        <h2>General Setup</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="spcu_inquiry_footer_text">Inquiry Form Footer Text</label>
                </th>
                <td>
                    <textarea name="spcu_inquiry_footer_text" id="spcu_inquiry_footer_text"
                              class="large-text" rows="3"><?php
                        echo esc_textarea(get_option('spcu_inquiry_footer_text',
                            'We typically respond within 24 hours &middot; ski@ourjapanmoments.com'));
                    ?></textarea>
                    <p class="description">Displayed at the bottom of the inquiry form. Basic HTML allowed (e.g. <code>&lt;a&gt;</code>, <code>&lt;strong&gt;</code>).</p>
                </td>
            </tr>
            <tr>
                <th scope="row">Inquiry Page</th>
                <td>
                    <?php $inq_url = get_option('spcu_inquiry_page_url', ''); ?>
                    <?php if ($inq_url): ?>
                    <p><a href="<?= esc_url($inq_url) ?>" target="_blank"><?= esc_html($inq_url) ?></a></p>
                    <p class="description">Auto-created page. Edit the title/slug in <a href="<?= admin_url('edit.php?post_type=page') ?>">Pages</a> if needed.</p>
                    <?php else: ?>
                    <p class="description" style="color:#d63638;">Inquiry page not yet created. Visit any area page to auto-create it, or <a href="<?= admin_url('post-new.php?post_type=page&post_title=Inquiry') ?>">create manually</a> with the <code>[spcu_inquiry_form]</code> shortcode.</p>
                    <?php endif; ?>
                </td>
            </tr>
        </table>
        </div>

        <?php elseif ($tab === 'inquiry'): ?>
        <!-- ── INQUIRY PAGE ──────────────────────────────────────── -->
        <?php $inq_url = get_option('spcu_inquiry_page_url', ''); ?>
        <div class="spcu-settings-card">
        <h2>Inquiry Page Content</h2>
        <?php if ($inq_url): ?>
        <p class="description" style="margin-bottom:1rem;">
            These texts appear at the top of the <a href="<?= esc_url($inq_url) ?>" target="_blank">inquiry page ↗</a>.
        </p>
        <?php endif; ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="spcu_inquiry_page_overline">Overline</label>
                </th>
                <td>
                    <input type="text" name="spcu_inquiry_page_overline" id="spcu_inquiry_page_overline"
                           class="regular-text"
                           value="<?= esc_attr(get_option('spcu_inquiry_page_overline', 'Contact Us')) ?>">
                    <p class="description">Small uppercase eyebrow text above the heading (e.g. "Contact Us").</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spcu_inquiry_page_heading">Heading</label>
                </th>
                <td>
                    <input type="text" name="spcu_inquiry_page_heading" id="spcu_inquiry_page_heading"
                           class="large-text"
                           value="<?= esc_attr(get_option('spcu_inquiry_page_heading', 'Get Your Custom Quote')) ?>">
                    <p class="description">Main H1 heading on the inquiry page.</p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spcu_inquiry_page_subheading">Subheading</label>
                </th>
                <td>
                    <textarea name="spcu_inquiry_page_subheading" id="spcu_inquiry_page_subheading"
                              class="large-text" rows="3"><?php
                        echo esc_textarea(get_option('spcu_inquiry_page_subheading',
                            "Tell us about your trip and we'll send you a detailed, final quote within 24 hours."));
                    ?></textarea>
                    <p class="description">Descriptive paragraph shown below the heading.</p>
                </td>
            </tr>
        </table>
        </div>

        <?php elseif ($tab === 'email'): ?>
        <!-- ── EMAIL NOTIFICATIONS ──────────────────────────────── -->
        <div class="spcu-settings-card">
        <h2>Email Delivery & Templates</h2>
        <?php
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $spcu_wp_mail_smtp_active = function_exists('is_plugin_active')
            && (is_plugin_active('wp-mail-smtp/wp_mail_smtp.php')
            || (function_exists('is_plugin_active_for_network') && is_plugin_active_for_network('wp-mail-smtp/wp_mail_smtp.php')));
        ?>
        <?php if (!$spcu_wp_mail_smtp_active): ?>
        <div class="notice notice-error" style="margin:0 0 1rem;padding:.75rem 1rem;">
            <p><strong>WP Mail SMTP is required</strong> for inquiry email delivery. Please install and activate <em>WP Mail SMTP</em>.</p>
            <p><a class="button button-secondary" href="<?= esc_url(admin_url('plugin-install.php?s=WP%20Mail%20SMTP&tab=search&type=term')) ?>">Install WP Mail SMTP</a>
               <a class="button" href="<?= esc_url(admin_url('plugins.php')) ?>">Go to Plugins</a></p>
        </div>
        <?php endif; ?>
        <p class="description" style="margin-bottom:1.2rem;">
            Available placeholders:
            <code>{first_name}</code> <code>{last_name}</code> <code>{email}</code>
            <code>{country}</code> <code>{phone}</code> <code>{resort}</code>
            <code>{hotel}</code> <code>{transport}</code> <code>{nights}</code> <code>{price_total}</code>
            <code>{package_level}</code> <code>{check_in}</code> <code>{check_out}</code>
            <code>{num_guests}</code> <code>{experience}</code> <code>{message}</code>
        </p>
        </div>

        <div class="spcu-settings-card">
        <h2>Admin Notification</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="spcu_admin_email_to">Recipient Email</label>
                </th>
                <td>
                    <input type="text" name="spcu_admin_email_to" id="spcu_admin_email_to"
                           class="regular-text"
                           value="<?= esc_attr(get_option('spcu_admin_email_to', get_option('admin_email'))) ?>">
                    <p class="description">Defaults to site admin: <code><?= esc_html(get_option('admin_email')) ?></code></p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spcu_admin_email_subject">Subject</label>
                </th>
                <td>
                    <input type="text" name="spcu_admin_email_subject" id="spcu_admin_email_subject"
                           class="large-text"
                           value="<?= esc_attr(get_option('spcu_admin_email_subject', 'New Ski Enquiry: {first_name} {last_name} ({resort})')) ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spcu_admin_email_body">Email Body</label>
                </th>
                <td>
                    <textarea name="spcu_admin_email_body" id="spcu_admin_email_body"
                              class="large-text" rows="10"><?php
                        echo esc_textarea(get_option('spcu_admin_email_body',
                            "A new ski inquiry has been submitted.\n\n--- Contact ---\nName: {first_name} {last_name}\nEmail: {email}\nCountry: {country}\nPhone: {phone}\n\n--- Trip Details ---\nResort: {resort}\nHotel: {hotel}\nPackage Level: {package_level}\nTransport: {transport}\nCheck-in: {check_in}\nCheck-out: {check_out}\nNights: {nights}\nGuests: {num_guests}\nExperience: {experience}\nEstimated Group Total: {price_total}\n\n--- Customer Message ---\n{message}"));
                    ?></textarea>
                </td>
            </tr>
        </table>
        </div>

        <div class="spcu-settings-card">
        <h2>Customer Auto-Responder</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="spcu_customer_email_subject">Subject</label>
                </th>
                <td>
                    <input type="text" name="spcu_customer_email_subject" id="spcu_customer_email_subject"
                           class="large-text"
                           value="<?= esc_attr(get_option('spcu_customer_email_subject', 'We received your ski inquiry for {resort}')) ?>">
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="spcu_customer_email_body">Email Body</label>
                </th>
                <td>
                    <textarea name="spcu_customer_email_body" id="spcu_customer_email_body"
                              class="large-text" rows="10"><?php
                        echo esc_textarea(get_option('spcu_customer_email_body',
                            "Hi {first_name},\n\nThank you for your inquiry. We received your request and our team will send your detailed quote within 24 hours.\n\n--- Your submitted details ---\nResort: {resort}\nHotel: {hotel}\nPackage Level: {package_level}\nTransport: {transport}\nCheck-in: {check_in}\nCheck-out: {check_out}\nNights: {nights}\nGuests: {num_guests}\nExperience: {experience}\nEstimated Group Total: {price_total}\n\nIf anything needs updating, just reply to this email.\n\nBest regards,\nThe Skiverse Team"));
                    ?></textarea>
                    <p class="description">HTML is supported in email bodies.</p>
                </td>
            </tr>
        </table>
        </div>
        <?php endif; ?>

        <p class="submit">
            <button type="submit" name="spcu_settings_submit" class="button button-primary">Save Settings</button>
        </p>
    </form>
</div>
