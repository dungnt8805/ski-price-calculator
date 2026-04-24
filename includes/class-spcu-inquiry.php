<?php
if (!defined('ABSPATH')) exit;

class SPCU_Inquiry {

    public function __construct(){
        add_shortcode('spcu_inquiry_form', [$this, 'render_form']);
        add_action('wp_ajax_spcu_submit_inquiry',        [$this, 'handle_submit']);
        add_action('wp_ajax_nopriv_spcu_submit_inquiry', [$this, 'handle_submit']);
        add_action('wp_enqueue_scripts', [$this, 'enqueue_assets']);
        add_action('init', [$this, 'ensure_inquiry_page']);
        add_action('wp_loaded', [$this, 'ensure_inquiry_page']);
    }

    public function ensure_inquiry_page(){
        // Get or create inquiry page
        $inquiry_page_id = get_option('spcu_inquiry_page_id');
        $inquiry_page_url = '';
        
        if ($inquiry_page_id) {
            $page = get_post($inquiry_page_id);
            if ($page && $page->post_status === 'publish') {
                $inquiry_page_url = get_permalink($page);
            } else {
                // Page ID stored but page doesn't exist or not published
                delete_option('spcu_inquiry_page_id');
                $inquiry_page_id = null;
            }
        }
        
        // If no valid page found, try to find one by title
        if (!$inquiry_page_id) {
            $inquiry_pages = get_posts([
                'post_type' => 'page',
                'post_title' => 'Inquiry',
                'post_status' => 'publish',
                'numberposts' => 1,
            ]);
            
            if ($inquiry_pages) {
                $inquiry_page_id = $inquiry_pages[0]->ID;
                $inquiry_page_url = get_permalink($inquiry_pages[0]);
                update_option('spcu_inquiry_page_id', $inquiry_page_id);
            } else {
                // No page found, create one
                $page_id = wp_insert_post([
                    'post_title' => 'Inquiry',
                    'post_content' => '[spcu_inquiry_form]',
                    'post_status' => 'publish',
                    'post_type' => 'page',
                    'post_name' => 'inquiry',
                ]);
                
                if ($page_id && !is_wp_error($page_id)) {
                    $inquiry_page_url = get_permalink($page_id);
                    update_option('spcu_inquiry_page_id', $page_id);
                }
            }
        }
        
        // Store URL for frontend use
        if ($inquiry_page_url) {
            update_option('spcu_inquiry_page_url', $inquiry_page_url);
        }
    }

    public function enqueue_assets(){
        $this->ensure_inquiry_page();
        wp_enqueue_script('jquery');
        wp_localize_script('jquery', 'spcu_inquiry', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('spcu_inquiry_submit'),
            'inquiry_page_url' => get_option('spcu_inquiry_page_url', ''),
        ]);
    }

    public function render_form($atts = []){
        global $wpdb;
        $atts = shortcode_atts(['resort' => ''], $atts);

        // Read query parameters for pre-filling (when redirected from area page)
        $prefill = [
            'resort'        => sanitize_text_field($_GET['resort'] ?? $atts['resort'] ?? ''),
            'check_in'      => sanitize_text_field($_GET['check_in'] ?? ''),
            'check_out'     => sanitize_text_field($_GET['check_out'] ?? ''),
            'num_guests'    => intval($_GET['num_guests'] ?? 0),
            'package_level' => sanitize_text_field($_GET['level'] ?? ''),
            'hotel'         => sanitize_text_field($_GET['hotel'] ?? ''),
            'transport'     => sanitize_text_field($_GET['transport'] ?? ''),
            'price_total'   => sanitize_text_field($_GET['price_total'] ?? ''),
            'nights'        => intval($_GET['nights'] ?? 0),
        ];
        $has_sim_data = !empty($prefill['resort']) || !empty($prefill['check_in']) || !empty($prefill['hotel']);

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
        .spcu-inquiry-success{display:none;max-width:720px;margin:40px auto;background:#fff;border-radius:20px;box-shadow:0 4px 20px rgba(0,0,0,.06);border:1px solid #e2e8f0;padding:3rem 2.5rem;text-align:center;}
        .spcu-inquiry-success__icon{width:64px;height:64px;background:#ecfdf5;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:1.75rem;line-height:1;}
        .spcu-inquiry-success h3{color:#0f1b2d;font-size:1.5rem;font-weight:700;margin:0 0 .75rem;}
        .spcu-inquiry-success p{color:#64748b;font-size:.95rem;line-height:1.6;margin:0 0 .5rem;}
        .spcu-inquiry-success .btn-home{display:inline-block;margin-top:1.75rem;background:#0f1b2d;color:#fff;text-decoration:none;padding:.75rem 2rem;border-radius:10px;font-size:.9rem;font-weight:700;transition:background .2s;}
        .spcu-inquiry-success .btn-home:hover{background:#3b82f6;color:#fff;}
        .spcu-inquiry-error{display:none;background:#fef2f2;border:1px solid #fecaca;border-radius:8px;padding:1rem;font-size:.88rem;color:#dc2626;margin-bottom:1rem;}
        .spcu-inq-prefill{background:linear-gradient(135deg,#0f1b2d,#1a2d4a);color:#fff;border-radius:14px;padding:1.3rem 1.6rem;margin-bottom:1.75rem;}
        .spcu-inq-prefill__label{font-size:.68rem;font-weight:700;text-transform:uppercase;letter-spacing:.8px;opacity:.6;margin-bottom:.5rem;}
        .spcu-inq-prefill__area{font-size:1.05rem;font-weight:700;margin-bottom:.25rem;line-height:1.4;}
        .spcu-inq-prefill__grade{font-weight:400;opacity:.75;font-size:.9rem;}
        .spcu-inq-prefill__details{font-size:.85rem;opacity:.75;margin-bottom:.4rem;}
        .spcu-inq-prefill__price{font-size:1.25rem;font-weight:800;color:#f59e0b;margin-top:.5rem;font-family:'Playfair Display',Georgia,serif;}
        @media(max-width:640px){.spcu-inquiry-form .form-row{grid-template-columns:1fr;}}
        </style>

        <div class="spcu-inquiry-form">
            <?php if ($has_sim_data): ?>
            <div class="spcu-inq-prefill">
                <div class="spcu-inq-prefill__label">Your Simulator Selection</div>
                <?php if ($prefill['resort'] || $prefill['hotel']): ?>
                <div class="spcu-inq-prefill__area"><?php
                    $grade_labels = ['standard' => 'Standard', 'premium' => 'Premium', 'exclusive' => 'Exclusive'];
                    $grade_label  = $grade_labels[$prefill['package_level']] ?? ucfirst($prefill['package_level']);
                    $parts = array_filter([$prefill['resort'], $prefill['hotel']]);
                    echo esc_html(implode(' — ', $parts));
                    if ($grade_label) echo ' <span class="spcu-inq-prefill__grade">(' . esc_html($grade_label) . ')</span>';
                ?></div>
                <?php endif; ?>
                <?php if ($prefill['check_in'] || $prefill['num_guests'] || $prefill['transport']): ?>
                <div class="spcu-inq-prefill__details"><?php
                    $detail_parts = [];
                    if ($prefill['check_in'] && $prefill['check_out']) {
                        $nights_str = $prefill['nights'] ? ' (' . $prefill['nights'] . ' nights)' : '';
                        $detail_parts[] = esc_html($prefill['check_in'] . ' → ' . $prefill['check_out'] . $nights_str);
                    }
                    if ($prefill['num_guests']) $detail_parts[] = esc_html($prefill['num_guests'] . ' guest' . ($prefill['num_guests'] > 1 ? 's' : ''));
                    if ($prefill['transport']) $detail_parts[] = esc_html(ucfirst($prefill['transport'])) . ' transport';
                    echo implode(' · ', $detail_parts);
                ?></div>
                <?php endif; ?>
                <?php if ($prefill['price_total']): ?>
                <div class="spcu-inq-prefill__price">Est. Group Total: <?= esc_html($prefill['price_total']) ?></div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
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
                                <option value="<?= esc_attr($a->slug ?: $a->name) ?>"<?= ($prefill['resort'] && ($prefill['resort'] === $a->slug || $prefill['resort'] === $a->name)) ? ' selected' : '' ?>><?= esc_html($a->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-field">
                        <label for="spcu_package_level">Package Level</label>
                        <select id="spcu_package_level" name="package_level">
                            <option value="standard"<?= $prefill['package_level'] === 'standard' ? ' selected' : '' ?>>Standard</option>
                            <option value="premium"<?= $prefill['package_level'] === 'premium' || !$prefill['package_level'] ? ' selected' : '' ?>>Premium</option>
                            <option value="exclusive"<?= $prefill['package_level'] === 'exclusive' ? ' selected' : '' ?>>Exclusive</option>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="spcu_check_in">Preferred Check-in</label>
                        <input type="date" id="spcu_check_in" name="check_in" value="<?= esc_attr($prefill['check_in']) ?>">
                    </div>
                    <div class="form-field">
                        <label for="spcu_check_out">Preferred Check-out</label>
                        <input type="date" id="spcu_check_out" name="check_out" value="<?= esc_attr($prefill['check_out']) ?>">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="spcu_num_guests">Number of Guests</label>
                        <select id="spcu_num_guests" name="num_guests">
                            <option value="1"<?= $prefill['num_guests'] === 1 ? ' selected' : '' ?>>1</option>
                            <option value="2"<?= $prefill['num_guests'] === 2 || !$prefill['num_guests'] ? ' selected' : '' ?>>2</option>
                            <option value="3"<?= $prefill['num_guests'] === 3 ? ' selected' : '' ?>>3</option>
                            <option value="4"<?= $prefill['num_guests'] === 4 ? ' selected' : '' ?>>4</option>
                            <option value="5"<?= $prefill['num_guests'] === 5 ? ' selected' : '' ?>>5</option>
                            <option value="6"<?= $prefill['num_guests'] === 6 ? ' selected' : '' ?>>6+</option>
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
                <div class="spcu-inquiry-success__icon">✓</div>
                <h3>Enquiry Sent!</h3>
                <p>Thank you for reaching out. We've received your enquiry and will send you a personalised quote within <strong>24 hours</strong>.</p>
                <p style="font-size:.85rem;">A confirmation has been sent to your email address.</p>
                <a href="<?= esc_url(home_url('/')) ?>" class="btn-home">← Back to Home</a>
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
