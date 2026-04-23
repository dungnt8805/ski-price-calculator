<?php
if (!defined('ABSPATH')) exit;

class SPCU_Inquiry {

    public function __construct(){
        add_shortcode('spcu_inquiry_form', [$this, 'render_form']);
        add_action('wp_ajax_spcu_submit_inquiry',        [$this, 'handle_submit']);
        add_action('wp_ajax_nopriv_spcu_submit_inquiry', [$this, 'handle_submit']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function enqueue_assets(){
        wp_localize_script('jquery', 'spcu_inquiry', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('spcu_inquiry_submit'),
        ]);
    }

    public function render_form($atts = []){
        global $wpdb;
        $atts = shortcode_atts(['resort' => ''], $atts);

        $areas = $wpdb->get_results("SELECT slug, name FROM {$wpdb->prefix}spcu_areas ORDER BY name ASC");

        // Auto-create table if needed
        $table = $wpdb->prefix . 'spcu_inquiries';
        if(!(bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table))){
            if(class_exists('SPCU_Database')) SPCU_Database::create_tables();
        }

        ob_start();
        ?>
        <div class="spcu-inquiry-wrap">
        <style>
        .spcu-inquiry-wrap{font-family:-apple-system,BlinkMacSystemFont,'Segoe UI',sans-serif;color:#1e293b;line-height:1.5;}
        .spcu-inquiry-form{max-width:720px;margin:30px auto;background:#fff;padding:2.5rem;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,.06);border:1px solid #e2e8f0;}
        .spcu-inquiry-form .form-row{display:grid;grid-template-columns:1fr 1fr;gap:1rem;margin-bottom:1rem;}
        .spcu-inquiry-form .form-field{margin-bottom:1rem;}
        .spcu-inquiry-form .form-field label{display:block;font-size:.72rem;font-weight:600;color:#0f1b2d;margin-bottom:.4rem;text-transform:uppercase;letter-spacing:.5px;}
        .spcu-inquiry-form .form-field input,
        .spcu-inquiry-form .form-field select,
        .spcu-inquiry-form .form-field textarea{width:100%;padding:.75rem 1rem;border:1px solid #e2e8f0;border-radius:10px;font-size:.9rem;font-family:inherit;color:#1e293b;box-sizing:border-box;background:#fff;}
        .spcu-inquiry-form .form-field input:focus,
        .spcu-inquiry-form .form-field select:focus,
        .spcu-inquiry-form .form-field textarea:focus{outline:none;border-color:#3b82f6;box-shadow:0 0 0 3px rgba(59,130,246,.1);}
        .spcu-inquiry-form .form-field textarea{min-height:100px;resize:vertical;}
        .spcu-inquiry-form .btn-submit{background:#0f1b2d;color:#fff;border:none;padding:.9rem;border-radius:10px;font-size:.95rem;font-weight:700;cursor:pointer;width:100%;transition:background .2s;font-family:inherit;}
        .spcu-inquiry-form .btn-submit:hover{background:#3b82f6;}
        .spcu-inquiry-form .btn-submit:disabled{opacity:.6;cursor:not-allowed;}
        .spcu-inquiry-success{display:none;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:1.5rem;text-align:center;margin-top:1rem;}
        .spcu-inquiry-success h3{color:#059669;margin-bottom:.5rem;}
        .spcu-inquiry-error{display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:1rem;font-size:.88rem;color:#dc2626;margin-bottom:1rem;}
        @media(max-width:640px){.spcu-inquiry-form .form-row{grid-template-columns:1fr;}}
        </style>

        <div class="spcu-inquiry-form">
            <div class="spcu-inquiry-error" id="spcu-inq-error"></div>
            <form id="spcu-inquiry-form" novalidate>
                <?php wp_nonce_field('spcu_inquiry_submit', 'spcu_inquiry_nonce'); ?>

                <div class="form-row">
                    <div class="form-field">
                        <label for="spcu_first_name">First Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="spcu_first_name" name="first_name" placeholder="John" required>
                    </div>
                    <div class="form-field">
                        <label for="spcu_last_name">Last Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" id="spcu_last_name" name="last_name" placeholder="Smith" required>
                    </div>
                </div>

                <div class="form-field">
                    <label for="spcu_email">Email <span style="color:#ef4444;">*</span></label>
                    <input type="email" id="spcu_email" name="email" placeholder="john@example.com" required>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="spcu_country">Country</label>
                        <select id="spcu_country" name="country">
                            <option value="">— Select —</option>
                            <option>Australia</option>
                            <option>United Kingdom</option>
                            <option>United States</option>
                            <option>Canada</option>
                            <option>New Zealand</option>
                            <option>Singapore</option>
                            <option>Japan</option>
                            <option>Other</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="spcu_phone">Phone (optional)</label>
                        <input type="tel" id="spcu_phone" name="phone" placeholder="+61 ...">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="spcu_resort">Preferred Resort</label>
                        <select id="spcu_resort" name="resort">
                            <option value="">Not decided yet</option>
                            <?php foreach($areas as $a): ?>
                                <option value="<?= esc_attr($a->slug ?: $a->name) ?>"<?= ($atts['resort'] && ($atts['resort'] === $a->slug || $atts['resort'] === $a->name)) ? ' selected' : '' ?>><?= esc_html($a->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="spcu_package_level">Package Level</label>
                        <select id="spcu_package_level" name="package_level">
                            <option value="standard">Standard</option>
                            <option value="premium" selected>Premium</option>
                            <option value="exclusive">Exclusive</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="spcu_check_in">Preferred Check-in</label>
                        <input type="date" id="spcu_check_in" name="check_in">
                    </div>
                    <div class="form-field">
                        <label for="spcu_check_out">Preferred Check-out</label>
                        <input type="date" id="spcu_check_out" name="check_out">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="spcu_num_guests">Number of Guests</label>
                        <select id="spcu_num_guests" name="num_guests">
                            <option value="1">1</option>
                            <option value="2" selected>2</option>
                            <option value="3">3</option>
                            <option value="4">4</option>
                            <option value="5">5</option>
                            <option value="6">6+</option>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="spcu_experience">Skiing Experience</label>
                        <select id="spcu_experience" name="experience">
                            <option value="first_timer">First timer</option>
                            <option value="beginner">Beginner</option>
                            <option value="intermediate" selected>Intermediate</option>
                            <option value="advanced">Advanced</option>
                            <option value="expert">Expert</option>
                        </select>
                    </div>
                </div>

                <div class="form-field">
                    <label for="spcu_message">Message / Special Requests</label>
                    <textarea id="spcu_message" name="message" placeholder="Tell us about your group, any special requirements, or questions..."></textarea>
                </div>

                <button type="submit" class="btn-submit" id="spcu-inq-submit">Send Enquiry</button>
                <?php 
                $footer_text = get_option('spcu_inquiry_footer_text', 'We typically respond within 24 hours &middot; ski@ourjapanmoments.com');
                if ($footer_text) : 
                ?>
                <div class="spcu-inquiry-footer" style="text-align: center; margin-top: 1rem; font-size: 0.85rem; color: #64748b;">
                    <?= wp_kses_post($footer_text) ?>
                </div>
                <?php endif; ?>
            </form>

            <div class="spcu-inquiry-success" id="spcu-inq-success">
                <h3>✓ Enquiry Sent!</h3>
                <p>Thank you! We'll get back to you within 24 hours.</p>
            </div>
        </div>
        </div>

        <script>
        (function(){
            var form = document.getElementById('spcu-inquiry-form');
            if(!form) return;

            form.addEventListener('submit', function(e){
                e.preventDefault();

                var errEl  = document.getElementById('spcu-inq-error');
                var okEl   = document.getElementById('spcu-inq-success');
                var btn    = document.getElementById('spcu-inq-submit');
                errEl.style.display = 'none';

                // Basic validation
                var firstName = form.querySelector('[name="first_name"]').value.trim();
                var lastName  = form.querySelector('[name="last_name"]').value.trim();
                var email     = form.querySelector('[name="email"]').value.trim();
                if(!firstName || !lastName){
                    errEl.textContent = 'Please enter your first and last name.';
                    errEl.style.display = 'block';
                    return;
                }
                if(!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)){
                    errEl.textContent = 'Please enter a valid email address.';
                    errEl.style.display = 'block';
                    return;
                }

                btn.disabled = true;
                btn.textContent = 'Sending...';

                var data = new FormData(form);
                data.append('action', 'spcu_submit_inquiry');
                data.append('_ajax_nonce', (window.spcu_inquiry || {}).nonce || form.querySelector('[name="spcu_inquiry_nonce"]').value);

                fetch((window.spcu_inquiry || {}).ajax_url || '<?= esc_js(admin_url('admin-ajax.php')) ?>', {
                    method: 'POST',
                    body: data,
                    credentials: 'same-origin',
                })
                .then(function(r){ return r.json(); })
                .then(function(res){
                    if(res.success){
                        form.style.display = 'none';
                        okEl.style.display = 'block';
                    } else {
                        errEl.textContent = res.data || 'An error occurred. Please try again.';
                        errEl.style.display = 'block';
                        btn.disabled = false;
                        btn.textContent = 'Send Enquiry';
                    }
                })
                .catch(function(){
                    errEl.textContent = 'Network error. Please try again.';
                    errEl.style.display = 'block';
                    btn.disabled = false;
                    btn.textContent = 'Send Enquiry';
                });
            });
        })();
        </script>
        <?php
        return ob_get_clean();
    }

    public function handle_submit(){
        if(!check_ajax_referer('spcu_inquiry_submit', '_ajax_nonce', false)){
            wp_send_json_error('Security check failed.');
        }

        $first_name    = sanitize_text_field($_POST['first_name'] ?? '');
        $last_name     = sanitize_text_field($_POST['last_name']  ?? '');
        $email         = sanitize_email($_POST['email']           ?? '');
        $country       = sanitize_text_field($_POST['country']    ?? '');
        $phone         = sanitize_text_field($_POST['phone']      ?? '');
        $resort        = sanitize_text_field($_POST['resort']     ?? '');
        $package_level = sanitize_text_field($_POST['package_level'] ?? '');
        $check_in      = sanitize_text_field($_POST['check_in']   ?? '');
        $check_out     = sanitize_text_field($_POST['check_out']  ?? '');
        $num_guests    = intval($_POST['num_guests'] ?? 0);
        $experience    = sanitize_text_field($_POST['experience'] ?? '');
        $message       = sanitize_textarea_field($_POST['message'] ?? '');

        if(!$first_name || !$last_name){
            wp_send_json_error('Please enter your name.');
        }
        if(!is_email($email)){
            wp_send_json_error('Please enter a valid email address.');
        }

        global $wpdb;
        $table = $wpdb->prefix . 'spcu_inquiries';

        // Ensure table exists
        if(!(bool) $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table))){
            if(class_exists('SPCU_Database')) SPCU_Database::create_tables();
        }

        $ok = $wpdb->insert($table, [
            'first_name'    => $first_name,
            'last_name'     => $last_name,
            'email'         => $email,
            'country'       => $country,
            'phone'         => $phone,
            'resort'        => $resort,
            'package_level' => $package_level,
            'check_in'      => $check_in  ?: null,
            'check_out'     => $check_out ?: null,
            'num_guests'    => $num_guests ?: null,
            'experience'    => $experience,
            'message'       => $message,
            'status'        => 'new',
        ]);

        if($ok === false){
            wp_send_json_error('Could not save enquiry. Please try again.');
        }

        // Prepare placeholders
        $placeholders = [
            '{first_name}'    => $first_name,
            '{last_name}'     => $last_name,
            '{email}'         => $email,
            '{country}'       => $country,
            '{phone}'         => $phone,
            '{resort}'        => $resort,
            '{package_level}' => $package_level,
            '{check_in}'      => $check_in,
            '{check_out}'     => $check_out,
            '{num_guests}'    => $num_guests,
            '{experience}'    => $experience,
            '{message}'       => $message,
        ];

        $replace_vars = function($string) use ($placeholders) {
            foreach ($placeholders as $key => $val) {
                $string = str_replace($key, esc_html($val), $string);
            }
            return $string;
        };

        // Send to Admin
        $admin_email_to = get_option('spcu_admin_email_to', get_option('admin_email'));
        if (empty($admin_email_to)) {
            $admin_email_to = get_option('admin_email');
        }
        $admin_subject  = get_option('spcu_admin_email_subject', 'New Ski Enquiry from {first_name} {last_name}');
        $admin_body     = get_option('spcu_admin_email_body', "New inquiry received!\n\nName: {first_name} {last_name}\nEmail: {email}\nCountry: {country}\nPhone: {phone}\nResort: {resort}\nPackage Level: {package_level}\nCheck-in: {check_in}\nCheck-out: {check_out}\nGuests: {num_guests}\nExperience: {experience}\n\nMessage:\n{message}");
        
        $admin_subject_parsed = $replace_vars($admin_subject);
        $admin_body_parsed    = nl2br($replace_vars($admin_body));

        $headers = ['Content-Type: text/html; charset=UTF-8'];
        wp_mail($admin_email_to, $admin_subject_parsed, $admin_body_parsed, $headers);

        // Send to Customer
        $customer_subject = get_option('spcu_customer_email_subject', 'Thank you for your enquiry, {first_name}');
        $customer_body    = get_option('spcu_customer_email_body', "Hi {first_name},\n\nThank you for reaching out to us! We have received your inquiry regarding {resort} and will get back to you within 24 hours.\n\nBest regards,\nThe Skiverse Team");
        
        $customer_subject_parsed = $replace_vars($customer_subject);
        $customer_body_parsed    = nl2br($replace_vars($customer_body));

        wp_mail($email, $customer_subject_parsed, $customer_body_parsed, $headers);

        wp_send_json_success('Enquiry submitted.');
    }
}

new SPCU_Inquiry();
