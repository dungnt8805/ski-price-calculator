<?php
if (!defined('ABSPATH')) exit;

if (isset($_POST['spcu_settings_submit']) && check_admin_referer('spcu_save_settings')) {
    // using wp_kses_post to allow simple HTML like <a>, <strong>, etc.
    update_option('spcu_inquiry_page_overline',    sanitize_text_field($_POST['spcu_inquiry_page_overline'] ?? ''));
    update_option('spcu_inquiry_page_heading',     sanitize_text_field($_POST['spcu_inquiry_page_heading'] ?? ''));
    update_option('spcu_inquiry_page_subheading',  sanitize_textarea_field($_POST['spcu_inquiry_page_subheading'] ?? ''));
    update_option('spcu_inquiry_footer_text', wp_kses_post($_POST['spcu_inquiry_footer_text'] ?? ''));
    
    update_option('spcu_admin_email_to', sanitize_text_field($_POST['spcu_admin_email_to'] ?? ''));
    update_option('spcu_admin_email_subject', sanitize_text_field($_POST['spcu_admin_email_subject'] ?? ''));
    update_option('spcu_admin_email_body', wp_kses_post($_POST['spcu_admin_email_body'] ?? ''));
    
    update_option('spcu_customer_email_subject', sanitize_text_field($_POST['spcu_customer_email_subject'] ?? ''));
    update_option('spcu_customer_email_body', wp_kses_post($_POST['spcu_customer_email_body'] ?? ''));

    echo '<div class="notice notice-success is-dismissible"><p>Settings saved.</p></div>';
}

$inquiry_footer_text = get_option('spcu_inquiry_footer_text', 'We typically respond within 24 hours &middot; ski@ourjapanmoments.com');
?>
<div class="wrap">
    <h1>Ski Engine Settings</h1>
    <form method="post" action="">
        <?php wp_nonce_field('spcu_save_settings'); ?>
        <h2>Inquiry Page Content</h2>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="spcu_inquiry_page_overline">Overline / Eyebrow Text</label></th>
                <td>
                    <input type="text" name="spcu_inquiry_page_overline" id="spcu_inquiry_page_overline" class="regular-text" value="<?php echo esc_attr(get_option('spcu_inquiry_page_overline', 'Contact Us')); ?>">
                    <p class="description">Small uppercase label shown above the main heading (e.g. "Contact Us").</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="spcu_inquiry_page_heading">Page Heading</label></th>
                <td>
                    <input type="text" name="spcu_inquiry_page_heading" id="spcu_inquiry_page_heading" class="large-text" value="<?php echo esc_attr(get_option('spcu_inquiry_page_heading', 'Get Your Custom Quote')); ?>">
                    <p class="description">Main H1 heading on the inquiry page.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="spcu_inquiry_page_subheading">Subheading / Description</label></th>
                <td>
                    <textarea name="spcu_inquiry_page_subheading" id="spcu_inquiry_page_subheading" class="large-text" rows="3"><?php echo esc_textarea(get_option('spcu_inquiry_page_subheading', "Tell us about your trip and we'll send you a detailed, final quote within 24 hours.")); ?></textarea>
                    <p class="description">Descriptive paragraph shown below the heading.</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="spcu_inquiry_footer_text">Inquiry Form Footer Text</label></th>
                <td>
                    <textarea name="spcu_inquiry_footer_text" id="spcu_inquiry_footer_text" class="large-text" rows="3"><?php echo esc_textarea($inquiry_footer_text); ?></textarea>
                    <p class="description">This text will be displayed at the bottom of the inquiry form. HTML tags are allowed.</p>
                </td>
            </tr>
        <h2>Inquiry Email Notifications</h2>
        <p>Available placeholders: <code>{first_name}</code>, <code>{last_name}</code>, <code>{email}</code>, <code>{country}</code>, <code>{phone}</code>, <code>{resort}</code>, <code>{package_level}</code>, <code>{check_in}</code>, <code>{check_out}</code>, <code>{num_guests}</code>, <code>{experience}</code>, <code>{message}</code></p>
        
        <h3>Admin Notification</h3>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="spcu_admin_email_to">Admin Email Recipient</label></th>
                <td>
                    <input type="text" name="spcu_admin_email_to" id="spcu_admin_email_to" class="regular-text" value="<?php echo esc_attr(get_option('spcu_admin_email_to', get_option('admin_email'))); ?>">
                    <p class="description">Defaults to the site admin email (<?php echo esc_html(get_option('admin_email')); ?>).</p>
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="spcu_admin_email_subject">Admin Email Subject</label></th>
                <td>
                    <input type="text" name="spcu_admin_email_subject" id="spcu_admin_email_subject" class="large-text" value="<?php echo esc_attr(get_option('spcu_admin_email_subject', 'New Ski Enquiry from {first_name} {last_name}')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="spcu_admin_email_body">Admin Email Body</label></th>
                <td>
                    <textarea name="spcu_admin_email_body" id="spcu_admin_email_body" class="large-text" rows="10"><?php 
                        echo esc_textarea(get_option('spcu_admin_email_body', "New inquiry received!\n\nName: {first_name} {last_name}\nEmail: {email}\nCountry: {country}\nPhone: {phone}\nResort: {resort}\nPackage Level: {package_level}\nCheck-in: {check_in}\nCheck-out: {check_out}\nGuests: {num_guests}\nExperience: {experience}\n\nMessage:\n{message}")); 
                    ?></textarea>
                </td>
            </tr>
        </table>

        <hr>
        
        <h3>Customer Auto-Responder</h3>
        <table class="form-table">
            <tr>
                <th scope="row"><label for="spcu_customer_email_subject">Customer Email Subject</label></th>
                <td>
                    <input type="text" name="spcu_customer_email_subject" id="spcu_customer_email_subject" class="large-text" value="<?php echo esc_attr(get_option('spcu_customer_email_subject', 'Thank you for your enquiry, {first_name}')); ?>">
                </td>
            </tr>
            <tr>
                <th scope="row"><label for="spcu_customer_email_body">Customer Email Body</label></th>
                <td>
                    <textarea name="spcu_customer_email_body" id="spcu_customer_email_body" class="large-text" rows="10"><?php 
                        echo esc_textarea(get_option('spcu_customer_email_body', "Hi {first_name},\n\nThank you for reaching out to us! We have received your inquiry regarding {resort} and will get back to you within 24 hours.\n\nBest regards,\nThe Skiverse Team")); 
                    ?></textarea>
                    <p class="description">HTML is supported in email bodies.</p>
                </td>
            </tr>
        </table>
        <p class="submit">
            <button type="submit" name="spcu_settings_submit" class="button button-primary">Save Settings</button>
        </p>
    </form>
</div>
