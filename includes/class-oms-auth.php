<?php
/**
 * Authentication functionality
 */
class OMS_Auth {
    
    /**
     * Session keys
     */
    const ADMIN_SESSION_KEY = 'oms_admin_logged_in';
    const COMPANY_SESSION_KEY = 'oms_company_logged_in';
    const EMPLOYEE_SESSION_KEY = 'oms_employee_logged_in';
    
    /**
     * Start session if not already started
     */
    private function start_session() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
    
    /**
     * Process login for all user types
     */
    public function process_login($username, $password, $user_type) {
        global $wpdb;
        
        switch ($user_type) {
            case 'admin':
                return $this->login_admin($username, $password);
                break;
                
            case 'company':
                return $this->login_company($username, $password);
                break;
                
            case 'employee':
                return $this->login_employee($username, $password);
                break;
                
            default:
                return false;
        }
    }
    
    /**
     * Login admin
     */
    private function login_admin($username, $password) {
        // For admin, check against static credentials in settings
        $stored_username = get_option('oms_admin_username', 'admin');
        $stored_password = get_option('oms_admin_password', 'admin123'); // In a real implementation, this would be hashed
        
        if ($username === $stored_username && $password === $stored_password) {
            $this->start_session();
            $_SESSION[self::ADMIN_SESSION_KEY] = array(
                'username' => $username,
                'profile_name' => get_option('oms_admin_profile_name', 'Administrator'),
                'profile_image' => get_option('oms_admin_profile_image', ''),
                'time' => time()
            );
            return true;
        }
        
        return false;
    }
    
    /**
     * Login company
     */
    private function login_company($username, $password) {
        global $wpdb;
        $table_companies = $wpdb->prefix . 'oms_companies';
        
        $company = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table_companies WHERE username = %s AND status = 'active'",
            $username
        ));
        
        if (!$company) {
            return false;
        }
        
        // Verify password
        if (wp_check_password($password, $company->password)) {
            $this->start_session();
            $_SESSION[self::COMPANY_SESSION_KEY] = array(
                'id' => $company->id,
                'company_name' => $company->company_name,
                'username' => $company->username,
                'profile_image' => $company->profile_image,
                'time' => time()
            );
            return true;
        }
        
        return false;
    }
    
    /**
     * Login employee
     */
    private function login_employee($username, $password) {
        global $wpdb;
        $table_employees = $wpdb->prefix . 'oms_employees';
        $table_branches = $wpdb->prefix . 'oms_branches';
        $table_companies = $wpdb->prefix . 'oms_companies';
        
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT e.*, b.branch_name, c.company_name 
            FROM $table_employees e
            JOIN $table_branches b ON e.branch_id = b.id
            JOIN $table_companies c ON e.company_id = c.id
            WHERE e.username = %s AND e.status = 'active' AND b.status = 'active' AND c.status = 'active'",
            $username
        ));
        
        if (!$employee) {
            return false;
        }
        
        // Verify password
        if (wp_check_password($password, $employee->password)) {
            $this->start_session();
            $_SESSION[self::EMPLOYEE_SESSION_KEY] = array(
                'id' => $employee->id,
                'employee_name' => $employee->employee_name,
                'username' => $employee->username,
                'profile_image' => $employee->profile_image,
                'role' => $employee->role,
                'branch_id' => $employee->branch_id,
                'branch_name' => $employee->branch_name,
                'company_id' => $employee->company_id,
                'company_name' => $employee->company_name,
                'time' => time()
            );
            return true;
        }
        
        return false;
    }
    
    /**
     * Check if admin is logged in
     */
    public function is_admin_logged_in() {
        $this->start_session();
        return isset($_SESSION[self::ADMIN_SESSION_KEY]);
    }
    
    /**
     * Check if company is logged in
     */
    public function is_company_logged_in() {
        $this->start_session();
        return isset($_SESSION[self::COMPANY_SESSION_KEY]);
    }
    
    /**
     * Check if employee is logged in
     */
    public function is_employee_logged_in() {
        $this->start_session();
        return isset($_SESSION[self::EMPLOYEE_SESSION_KEY]);
    }
    
    /**
     * Get current admin
     */
    public function get_current_admin() {
        $this->start_session();
        if (isset($_SESSION[self::ADMIN_SESSION_KEY])) {
            return (object) $_SESSION[self::ADMIN_SESSION_KEY];
        }
        return null;
    }
    
    /**
     * Get current company
     */
    public function get_current_company() {
        $this->start_session();
        if (isset($_SESSION[self::COMPANY_SESSION_KEY])) {
            return (object) $_SESSION[self::COMPANY_SESSION_KEY];
        }
        return null;
    }
    
    /**
     * Get current employee
     */
    public function get_current_employee() {
        $this->start_session();
        if (isset($_SESSION[self::EMPLOYEE_SESSION_KEY])) {
            return (object) $_SESSION[self::EMPLOYEE_SESSION_KEY];
        }
        return null;
    }
    
    /**
     * Logout user
     */
    public function logout() {
        $this->start_session();
        
        if (isset($_SESSION[self::ADMIN_SESSION_KEY])) {
            unset($_SESSION[self::ADMIN_SESSION_KEY]);
        }
        
        if (isset($_SESSION[self::COMPANY_SESSION_KEY])) {
            unset($_SESSION[self::COMPANY_SESSION_KEY]);
        }
        
        if (isset($_SESSION[self::EMPLOYEE_SESSION_KEY])) {
            unset($_SESSION[self::EMPLOYEE_SESSION_KEY]);
        }
        
        // Optional: destroy session
        // session_destroy();
    }
}
