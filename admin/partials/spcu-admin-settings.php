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
    <h1 class="wp-heading-inline">Settings</h1>
    <hr class="wp-header-end">

    <nav class="nav-tab-wrapper" style="margin-bottom:0;padding-bottom:0;border-bottom:1px solid #c3c4c7;">
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

        <?php elseif ($tab === 'inquiry'): ?>
        <!-- ── INQUIRY PAGE ──────────────────────────────────────── -->
        <?php $inq_url = get_option('spcu_inquiry_page_url', ''); ?>
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

        <?php elseif ($tab === 'email'): ?>
        <!-- ── EMAIL NOTIFICATIONS ──────────────────────────────── -->
        <p class="description" style="margin-bottom:1.2rem;">
            Available placeholders:
            <code>{first_name}</code> <code>{last_name}</code> <code>{email}</code>
            <code>{country}</code> <code>{phone}</code> <code>{resort}</code>
            <code>{package_level}</code> <code>{check_in}</code> <code>{check_out}</code>
            <code>{num_guests}</code> <code>{experience}</code> <code>{message}</code>
        </p>

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
                           value="<?= esc_attr(get_option('spcu_admin_email_subject', 'New Ski Enquiry from {first_name} {last_name}')) ?>">
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
                            "New inquiry received!\n\nName: {first_name} {last_name}\nEmail: {email}\nCountry: {country}\nPhone: {phone}\nResort: {resort}\nPackage Level: {package_level}\nCheck-in: {check_in}\nCheck-out: {check_out}\nGuests: {num_guests}\nExperience: {experience}\n\nMessage:\n{message}"));
                    ?></textarea>
                </td>
            </tr>
        </table>

        <hr>

        <h2>Customer Auto-Responder</h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="spcu_customer_email_subject">Subject</label>
                </th>
                <td>
                    <input type="text" name="spcu_customer_email_subject" id="spcu_customer_email_subject"
                           class="large-text"
                           value="<?= esc_attr(get_option('spcu_customer_email_subject', 'Thank you for your enquiry, {first_name}')) ?>">
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
                            "Hi {first_name},\n\nThank you for reaching out to us! We have received your inquiry regarding {resort} and will get back to you within 24 hours.\n\nBest regards,\nThe Skiverse Team"));
                    ?></textarea>
                    <p class="description">HTML is supported in email bodies.</p>
                </td>
            </tr>
        </table>
        <?php endif; ?>

        <p class="submit">
            <button type="submit" name="spcu_settings_submit" class="button button-primary">Save Settings</button>
        </p>
    </form>
</div>
