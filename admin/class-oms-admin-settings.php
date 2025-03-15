<?php
/**
 * Admin Settings Class
 * Handles the settings for the admin dashboard including static credentials
 */
class OMS_Admin_Settings {
    
    /**
     * Register settings fields
     */
    public static function register_settings() {
        register_setting('oms_admin_settings', 'oms_admin_username', array(
            'default' => 'admin',
            'sanitize_callback' => 'sanitize_user'
        ));
        
        register_setting('oms_admin_settings', 'oms_admin_password', array(
            'default' => '',
            'sanitize_callback' => array('OMS_Admin_Settings', 'hash_password')
        ));
        
        register_setting('oms_admin_settings', 'oms_admin_profile_name', array(
            'default' => 'Administrator',
            'sanitize_callback' => 'sanitize_text_field'
        ));
        
        register_setting('oms_admin_settings', 'oms_admin_profile_image', array(
            'default' => '',
            'sanitize_callback' => 'esc_url_raw'
        ));
    }
    
    /**
     * Hash password before storing
     */
    public static function hash_password($password) {
        // Only hash if there's a new password
        if (!empty($password)) {
            return wp_hash_password($password);
        }
        
        // If empty, keep the old password
        return get_option('oms_admin_password');
    }
    
    /**
     * Initialize settings with defaults if not set
     */
    public static function initialize_settings() {
        $username = get_option('oms_admin_username');
        $password = get_option('oms_admin_password');
        
        // Set default username if not already set
        if (empty($username)) {
            update_option('oms_admin_username', 'admin');
        }
        
        // Set default password if not already set
        if (empty($password)) {
            update_option('oms_admin_password', wp_hash_password('admin123'));
        }
    }
    
    /**
     * Get admin settings for use with Streamlit secrets
     */
    public static function get_streamlit_settings() {
        return array(
            'admin_username' => get_option('oms_admin_username'),
            'admin_password' => get_option('oms_admin_password'),
            'admin_profile_name' => get_option('oms_admin_profile_name'),
        );
    }
    
    /**
     * Verify admin credentials
     */
    public static function verify_credentials($username, $password) {
        $stored_username = get_option('oms_admin_username');
        $stored_password = get_option('oms_admin_password');
        
        if ($username === $stored_username && wp_check_password($password, $stored_password)) {
            return true;
        }
        
        return false;
    }
}
