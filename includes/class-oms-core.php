<?php
/**
 * The core plugin class.
 */
class OMS_Core {

    /**
     * The loader that's responsible for maintaining and registering all hooks.
     */
    protected $loader;

    /**
     * Define the core functionality of the plugin.
     */
    public function __construct() {
        $this->load_dependencies();
        $this->define_admin_hooks();
        $this->define_company_hooks();
        $this->define_employee_hooks();
        $this->define_public_hooks();
    }

    /**
     * Load the required dependencies for this plugin.
     */
    private function load_dependencies() {
        // Core files
        require_once OMS_PLUGIN_DIR . 'includes/class-oms-loader.php';
        require_once OMS_PLUGIN_DIR . 'includes/class-oms-auth.php';
        
        // Admin area
        require_once OMS_PLUGIN_DIR . 'admin/class-oms-admin.php';
        
        // Company area
        require_once OMS_PLUGIN_DIR . 'company/class-oms-company.php';
        
        // Employee area
        require_once OMS_PLUGIN_DIR . 'employee/class-oms-employee.php';
        
        // Public area
        require_once OMS_PLUGIN_DIR . 'public/class-oms-public.php';
        
        // REST API
        require_once OMS_PLUGIN_DIR . 'includes/class-oms-api.php';
        
        $this->loader = new OMS_Loader();
    }

    /**
     * Register all of the hooks related to the admin area.
     */
    private function define_admin_hooks() {
        $admin = new OMS_Admin();
        
        // Admin menus and settings
        $this->loader->add_action('admin_menu', $admin, 'register_admin_menu');
        $this->loader->add_action('admin_init', $admin, 'register_settings');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_styles');
        $this->loader->add_action('admin_enqueue_scripts', $admin, 'enqueue_scripts');
        
        // AJAX actions for admin
        $this->loader->add_action('wp_ajax_oms_create_company', $admin, 'create_company');
        $this->loader->add_action('wp_ajax_oms_get_companies', $admin, 'get_companies');
        $this->loader->add_action('wp_ajax_oms_get_branches', $admin, 'get_branches');
        $this->loader->add_action('wp_ajax_oms_toggle_company_status', $admin, 'toggle_company_status');
        $this->loader->add_action('wp_ajax_oms_send_message_to_company', $admin, 'send_message_to_company');
        $this->loader->add_action('wp_ajax_oms_delete_message', $admin, 'delete_message');
        $this->loader->add_action('wp_ajax_oms_update_admin_profile', $admin, 'update_admin_profile');
    }

    /**
     * Register all of the hooks related to the company area.
     */
    private function define_company_hooks() {
        $company = new OMS_Company();
        
        // Company-related actions
        $this->loader->add_action('wp_ajax_oms_company_create_branch', $company, 'create_branch');
        $this->loader->add_action('wp_ajax_oms_company_create_employee', $company, 'create_employee');
        $this->loader->add_action('wp_ajax_oms_company_get_branches', $company, 'get_branches');
        $this->loader->add_action('wp_ajax_oms_company_get_employees', $company, 'get_employees');
        $this->loader->add_action('wp_ajax_oms_company_update_employee_role', $company, 'update_employee_role');
        $this->loader->add_action('wp_ajax_oms_company_assign_task', $company, 'assign_task');
        $this->loader->add_action('wp_ajax_oms_company_get_reports', $company, 'get_reports');
        $this->loader->add_action('wp_ajax_oms_company_toggle_branch_status', $company, 'toggle_branch_status');
        $this->loader->add_action('wp_ajax_oms_company_toggle_employee_status', $company, 'toggle_employee_status');
        $this->loader->add_action('wp_ajax_oms_company_switch_employee_branch', $company, 'switch_employee_branch');
        $this->loader->add_action('wp_ajax_oms_company_update_profile', $company, 'update_profile');
        $this->loader->add_action('wp_ajax_oms_company_send_message', $company, 'send_message');
        $this->loader->add_action('wp_ajax_oms_company_reply_to_admin', $company, 'reply_to_admin');
        $this->loader->add_action('wp_ajax_oms_company_download_report', $company, 'download_report');
    }

    /**
     * Register all of the hooks related to the employee area.
     */
    private function define_employee_hooks() {
        $employee = new OMS_Employee();
        
        // Employee-related actions
        $this->loader->add_action('wp_ajax_oms_employee_create_subordinate', $employee, 'create_subordinate');
        $this->loader->add_action('wp_ajax_oms_employee_assign_task', $employee, 'assign_task');
        $this->loader->add_action('wp_ajax_oms_employee_get_reports', $employee, 'get_reports');
        $this->loader->add_action('wp_ajax_oms_employee_toggle_subordinate_status', $employee, 'toggle_subordinate_status');
        $this->loader->add_action('wp_ajax_oms_employee_update_profile', $employee, 'update_profile');
        $this->loader->add_action('wp_ajax_oms_employee_submit_report', $employee, 'submit_report');
        $this->loader->add_action('wp_ajax_oms_employee_update_task_status', $employee, 'update_task_status');
        $this->loader->add_action('wp_ajax_oms_employee_send_message', $employee, 'send_message');
        $this->loader->add_action('wp_ajax_oms_employee_reply_to_message', $employee, 'reply_to_message');
        $this->loader->add_action('wp_ajax_oms_employee_download_report', $employee, 'download_report');
    }

    /**
     * Register all of the hooks related to the public-facing functionality.
     */
    private function define_public_hooks() {
        $public = new OMS_Public();
        
        // Register shortcodes
        $this->loader->add_action('init', $public, 'register_shortcodes');
        
        // Enqueue styles and scripts
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_styles');
        $this->loader->add_action('wp_enqueue_scripts', $public, 'enqueue_scripts');
        
        // Authentication hooks
        $this->loader->add_action('wp_ajax_nopriv_oms_login', $public, 'process_login');
        $this->loader->add_action('wp_ajax_oms_logout', $public, 'process_logout');
    }

    /**
     * Run the loader to execute all of the hooks.
     */
    public function run() {
        $this->loader->run();
    }
}
