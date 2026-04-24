<?php
class SPCU_Activator {
    public static function activate() {
        require_once SPCU_PATH.'includes/class-spcu-database.php';
        SPCU_Database::create_tables();
        self::create_inquiry_page();
    }

    public static function create_inquiry_page() {
        // Check if inquiry page already exists
        $existing_page = get_option('spcu_inquiry_page_id');
        if ($existing_page && get_post($existing_page)) {
            return; // Page already exists
        }

        // Check if there's already a page titled "Inquiry"
        $inquiry_pages = get_posts([
            'post_type' => 'page',
            'post_title' => 'Inquiry',
            'post_status' => 'publish',
            'numberposts' => 1,
        ]);

        if ($inquiry_pages) {
            update_option('spcu_inquiry_page_id', $inquiry_pages[0]->ID);
            update_option('spcu_inquiry_page_url', get_permalink($inquiry_pages[0]));
            return;
        }

        // Create new inquiry page
        $page_id = wp_insert_post([
            'post_title' => 'Inquiry',
            'post_content' => '[spcu_inquiry_form]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'post_name' => 'inquiry',
        ]);

        if ($page_id && !is_wp_error($page_id)) {
            update_option('spcu_inquiry_page_id', $page_id);
            update_option('spcu_inquiry_page_url', get_permalink($page_id));
        }
    }
}