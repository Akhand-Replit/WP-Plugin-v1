<?php
/**
 * Admin-specific functionality
 */
class OMS_Admin {

    /**
     * Register the admin menu
     */
    public function register_admin_menu() {
        add_menu_page(
            __('Organization Management', 'org-management'),
            __('Org Management', 'org-management'),
            'manage_options',
            'organization-management',
            array($this, 'display_admin_dashboard'),
            'dashicons-groups',
            26
        );
        
        add_submenu_page(
            'organization-management',
            __('Dashboard', 'org-management'),
            __('Dashboard', 'org-management'),
            'manage_options',
            'organization-management',
            array($this, 'display_admin_dashboard')
        );
        
        add_submenu_page(
            'organization-management',
            __('Companies', 'org-management'),
            __('Companies', 'org-management'),
            'manage_options',
            'oms-companies',
            array($this, 'display_companies_page')
        );
        
        add_submenu_page(
            'organization-management',
            __('Messages', 'org-management'),
            __('Messages', 'org-management'),
            'manage_options',
            'oms-messages',
            array($this, 'display_messages_page')
        );
        
        add_submenu_page(
            'organization-management',
            __('Settings', 'org-management'),
            __('Settings', 'org-management'),
            'manage_options',
            'oms-settings',
            array($this, 'display_settings_page')
        );
        
        add_submenu_page(
            'organization-management',
            __('Profile', 'org-management'),
            __('Profile', 'org-management'),
            'manage_options',
            'oms-profile',
            array($this, 'display_profile_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('oms_settings', 'oms_admin_username');
        register_setting('oms_settings', 'oms_admin_profile_name');
        register_setting('oms_settings', 'oms_admin_profile_image');
    }

    /**
     * Enqueue admin styles
     */
    public function enqueue_styles($hook) {
        if (strpos($hook, 'organization-management') !== false || strpos($hook, 'oms-') !== false) {
            wp_enqueue_style('oms-admin-style', OMS_PLUGIN_URL . 'admin/css/admin.css', array(), OMS_VERSION, 'all');
            wp_enqueue_style('oms-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3', 'all');
            wp_enqueue_style('oms-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0', 'all');
        }
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if (strpos($hook, 'organization-management') !== false || strpos($hook, 'oms-') !== false) {
            wp_enqueue_script('oms-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.1.3', true);
            wp_enqueue_script('oms-admin-script', OMS_PLUGIN_URL . 'admin/js/admin.js', array('jquery'), OMS_VERSION, true);
            wp_enqueue_media();
            
            wp_localize_script('oms-admin-script', 'oms_admin', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('oms_admin_nonce'),
            ));
        }
    }

    /**
     * Display admin dashboard
     */
    public function display_admin_dashboard() {
        include_once OMS_PLUGIN_DIR . 'admin/views/dashboard.php';
    }

    /**
     * Display companies page
     */
    public function display_companies_page() {
        include_once OMS_PLUGIN_DIR . 'admin/views/companies.php';
    }

    /**
     * Display messages page
     */
    public function display_messages_page() {
        include_once OMS_PLUGIN_DIR . 'admin/views/messages.php';
    }

    /**
     * Display settings page
     */
    public function display_settings_page() {
        include_once OMS_PLUGIN_DIR . 'admin/views/settings.php';
    }

    /**
     * Display profile page
     */
    public function display_profile_page() {
        include_once OMS_PLUGIN_DIR . 'admin/views/profile.php';
    }

    /**
     * Create a new company
     */
    public function create_company() {
        check_ajax_referer('oms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $company_name = sanitize_text_field($_POST['company_name']);
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password']; // Will be hashed before storage
        $profile_image = esc_url_raw($_POST['profile_image']);
        
        // Validation
        if (empty($company_name) || empty($username) || empty($password)) {
            wp_send_json_error('All fields are required');
            return;
        }
        
        global $wpdb;
        $table_companies = $wpdb->prefix . 'oms_companies';
        $table_branches = $wpdb->prefix . 'oms_branches';
        
        // Check if username already exists
        $existing_user = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_companies WHERE username = %s",
            $username
        ));
        
        if ($existing_user) {
            wp_send_json_error('Username already exists');
            return;
        }
        
        // Hash password
        $hashed_password = wp_hash_password($password);
        
        // Insert company
        $result = $wpdb->insert(
            $table_companies,
            array(
                'company_name' => $company_name,
                'username' => $username,
                'password' => $hashed_password,
                'profile_image' => $profile_image,
                'status' => 'active'
            )
        );
        
        if (!$result) {
            wp_send_json_error('Failed to create company');
            return;
        }
        
        $company_id = $wpdb->insert_id;
        
        // Create default main branch
        $wpdb->insert(
            $table_branches,
            array(
                'company_id' => $company_id,
                'branch_name' => 'Main Branch',
                'status' => 'active'
            )
        );
        
        wp_send_json_success(array(
            'message' => 'Company created successfully',
            'company_id' => $company_id
        ));
    }

    /**
     * Get all companies
     */
    public function get_companies() {
        check_ajax_referer('oms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        global $wpdb;
        $table_companies = $wpdb->prefix . 'oms_companies';
        
        $companies = $wpdb->get_results(
            "SELECT id, company_name, username, profile_image, status, created_at FROM $table_companies ORDER BY id DESC"
        );
        
        wp_send_json_success(array('companies' => $companies));
    }

    /**
     * Get branches for a company
     */
    public function get_branches() {
        check_ajax_referer('oms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $company_id = intval($_POST['company_id']);
        
        global $wpdb;
        $table_branches = $wpdb->prefix . 'oms_branches';
        
        $branches = $wpdb->get_results($wpdb->prepare(
            "SELECT id, branch_name, status, created_at FROM $table_branches WHERE company_id = %d ORDER BY id ASC",
            $company_id
        ));
        
        wp_send_json_success(array('branches' => $branches));
    }

    /**
     * Toggle company status (activate/deactivate)
     */
    public function toggle_company_status() {
        check_ajax_referer('oms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $company_id = intval($_POST['company_id']);
        $new_status = sanitize_text_field($_POST['status']);
        
        if (!in_array($new_status, array('active', 'inactive'))) {
            wp_send_json_error('Invalid status');
            return;
        }
        
        global $wpdb;
        $table_companies = $wpdb->prefix . 'oms_companies';
        $table_branches = $wpdb->prefix . 'oms_branches';
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        // Update company status
        $result = $wpdb->update(
            $table_companies,
            array('status' => $new_status),
            array('id' => $company_id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update company status');
            return;
        }
        
        // Update all branches of the company to the same status
        $wpdb->update(
            $table_branches,
            array('status' => $new_status),
            array('company_id' => $company_id)
        );
        
        // Update all employees of the company to the same status
        $wpdb->update(
            $table_employees,
            array('status' => $new_status),
            array('company_id' => $company_id)
        );
        
        wp_send_json_success(array(
            'message' => 'Company status updated successfully',
            'status' => $new_status
        ));
    }

    /**
     * Send message to a company
     */
    public function send_message_to_company() {
        check_ajax_referer('oms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $company_id = intval($_POST['company_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $attachment = isset($_POST['attachment']) ? esc_url_raw($_POST['attachment']) : '';
        
        if (empty($message)) {
            wp_send_json_error('Message cannot be empty');
            return;
        }
        
        global $wpdb;
        $table_messages = $wpdb->prefix . 'oms_messages';
        
        $result = $wpdb->insert(
            $table_messages,
            array(
                'sender_type' => 'admin',
                'sender_id' => 0,
                'receiver_type' => 'company',
                'receiver_id' => $company_id,
                'message' => $message,
                'attachment' => $attachment,
                'status' => 'unread'
            )
        );
        
        if (!$result) {
            wp_send_json_error('Failed to send message');
            return;
        }
        
        $message_id = $wpdb->insert_id;
        
        wp_send_json_success(array(
            'message' => 'Message sent successfully',
            'message_id' => $message_id
        ));
    }

    /**
     * Delete a message
     */
    public function delete_message() {
        check_ajax_referer('oms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $message_id = intval($_POST['message_id']);
        
        global $wpdb;
        $table_messages = $wpdb->prefix . 'oms_messages';
        
        $result = $wpdb->delete(
            $table_messages,
            array('id' => $message_id, 'sender_type' => 'admin'),
            array('%d', '%s')
        );
        
        if (!$result) {
            wp_send_json_error('Failed to delete message');
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Message deleted successfully'
        ));
    }

    /**
     * Update admin profile
     */
    public function update_admin_profile() {
        check_ajax_referer('oms_admin_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Permission denied');
            return;
        }
        
        $profile_name = sanitize_text_field($_POST['profile_name']);
        $username = sanitize_user($_POST['username']);
        $profile_image = esc_url_raw($_POST['profile_image']);
        
        if (empty($profile_name) || empty($username)) {
            wp_send_json_error('Profile name and username are required');
            return;
        }
        
        update_option('oms_admin_profile_name', $profile_name);
        update_option('oms_admin_username', $username);
        update_option('oms_admin_profile_image', $profile_image);
        
        wp_send_json_success(array(
            'message' => 'Profile updated successfully'
        ));
    }
}
