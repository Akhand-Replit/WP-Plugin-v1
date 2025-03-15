<?php
/**
 * Public-facing functionality
 */
class OMS_Public {

    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        add_shortcode('oms_login', array($this, 'login_shortcode'));
        add_shortcode('oms_company_dashboard', array($this, 'company_dashboard_shortcode'));
        add_shortcode('oms_employee_dashboard', array($this, 'employee_dashboard_shortcode'));
    }

    /**
     * Enqueue public styles
     */
    public function enqueue_styles() {
        // Only enqueue on pages with our shortcodes
        global $post;
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'oms_login') ||
            has_shortcode($post->post_content, 'oms_company_dashboard') ||
            has_shortcode($post->post_content, 'oms_employee_dashboard')
        )) {
            wp_enqueue_style('oms-public-style', OMS_PLUGIN_URL . 'public/css/public.css', array(), OMS_VERSION, 'all');
            wp_enqueue_style('oms-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css', array(), '5.1.3', 'all');
            wp_enqueue_style('oms-fontawesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css', array(), '6.0.0', 'all');
        }
    }

    /**
     * Enqueue public scripts
     */
    public function enqueue_scripts() {
        // Only enqueue on pages with our shortcodes
        global $post;
        if (is_a($post, 'WP_Post') && (
            has_shortcode($post->post_content, 'oms_login') ||
            has_shortcode($post->post_content, 'oms_company_dashboard') ||
            has_shortcode($post->post_content, 'oms_employee_dashboard')
        )) {
            wp_enqueue_script('oms-bootstrap', 'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js', array('jquery'), '5.1.3', true);
            wp_enqueue_script('oms-public-script', OMS_PLUGIN_URL . 'public/js/public.js', array('jquery'), OMS_VERSION, true);
            
            wp_localize_script('oms-public-script', 'oms_public', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'login_nonce' => wp_create_nonce('oms_login_nonce'),
                'company_nonce' => wp_create_nonce('oms_company_nonce'),
                'employee_nonce' => wp_create_nonce('oms_employee_nonce'),
            ));
        }
    }

    /**
     * Login page shortcode
     */
    public function login_shortcode($atts) {
        $auth = new OMS_Auth();
        
        // If already logged in, redirect to appropriate dashboard
        if ($auth->is_company_logged_in()) {
            return '<script>window.location.href = "' . esc_url(get_permalink(get_page_by_path('company-dashboard'))) . '";</script>';
        } else if ($auth->is_employee_logged_in()) {
            return '<script>window.location.href = "' . esc_url(get_permalink(get_page_by_path('employee-dashboard'))) . '";</script>';
        }
        
        ob_start();
        include_once OMS_PLUGIN_DIR . 'public/views/login.php';
        return ob_get_clean();
    }

    /**
     * Company dashboard shortcode
     */
    public function company_dashboard_shortcode($atts) {
        $auth = new OMS_Auth();
        
        // If not logged in as company, redirect to login
        if (!$auth->is_company_logged_in()) {
            return '<script>window.location.href = "' . esc_url(get_permalink(get_page_by_path('organization-login'))) . '";</script>';
        }
        
        $company = $auth->get_current_company();
        
        ob_start();
        include_once OMS_PLUGIN_DIR . 'public/views/company-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Employee dashboard shortcode
     */
    public function employee_dashboard_shortcode($atts) {
        $auth = new OMS_Auth();
        
        // If not logged in as employee, redirect to login
        if (!$auth->is_employee_logged_in()) {
            return '<script>window.location.href = "' . esc_url(get_permalink(get_page_by_path('organization-login'))) . '";</script>';
        }
        
        $employee = $auth->get_current_employee();
        
        ob_start();
        include_once OMS_PLUGIN_DIR . 'public/views/employee-dashboard.php';
        return ob_get_clean();
    }

    /**
     * Process login
     */
    public function process_login() {
        check_ajax_referer('oms_login_nonce', 'nonce');
        
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password'];
        $user_type = sanitize_text_field($_POST['user_type']);
        
        if (empty($username) || empty($password) || empty($user_type)) {
            wp_send_json_error('All fields are required');
            return;
        }
        
        if (!in_array($user_type, array('company', 'employee'))) {
            wp_send_json_error('Invalid user type');
            return;
        }
        
        $auth = new OMS_Auth();
        $success = $auth->process_login($username, $password, $user_type);
        
        if ($success) {
            // Determine which dashboard to redirect to
            $redirect = '';
            if ($user_type === 'company') {
                $redirect = get_permalink(get_page_by_path('company-dashboard'));
            } else if ($user_type === 'employee') {
                $redirect = get_permalink(get_page_by_path('employee-dashboard'));
            }
            
            wp_send_json_success(array(
                'message' => 'Login successful',
                'redirect' => $redirect
            ));
        } else {
            wp_send_json_error('Invalid credentials');
        }
    }

    /**
     * Process logout
     */
    public function process_logout() {
        $auth = new OMS_Auth();
        $auth->logout();
        
        wp_send_json_success(array(
            'message' => 'Logout successful',
            'redirect' => get_permalink(get_page_by_path('organization-login'))
        ));
    }
}
