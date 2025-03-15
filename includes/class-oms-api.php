<?php
/**
 * REST API functionality
 */
class OMS_API {
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        add_action('rest_api_init', array($this, 'register_api_endpoints'));
    }
    
    /**
     * Register API endpoints
     */
    public function register_api_endpoints() {
        register_rest_route('oms/v1', '/companies', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_companies'),
            'permission_callback' => array($this, 'admin_permissions_check')
        ));
        
        register_rest_route('oms/v1', '/companies/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_company'),
            'permission_callback' => array($this, 'admin_permissions_check')
        ));
        
        register_rest_route('oms/v1', '/companies/(?P<id>\d+)/branches', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_company_branches'),
            'permission_callback' => array($this, 'admin_or_company_permissions_check')
        ));
        
        register_rest_route('oms/v1', '/branches/(?P<id>\d+)/employees', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_branch_employees'),
            'permission_callback' => array($this, 'admin_or_company_permissions_check')
        ));
        
        register_rest_route('oms/v1', '/tasks', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_tasks'),
            'permission_callback' => array($this, 'any_authenticated_user')
        ));
        
        register_rest_route('oms/v1', '/reports', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_reports'),
            'permission_callback' => array($this, 'any_authenticated_user')
        ));
    }
    
    /**
     * Check if user is admin
     */
    public function admin_permissions_check($request) {
        $auth = new OMS_Auth();
        return $auth->is_admin_logged_in();
    }
    
    /**
     * Check if user is admin or company
     */
    public function admin_or_company_permissions_check($request) {
        $auth = new OMS_Auth();
        
        if ($auth->is_admin_logged_in()) {
            return true;
        }
        
        if ($auth->is_company_logged_in()) {
            // For company routes, check if the requested resource belongs to this company
            $company = $auth->get_current_company();
            $route = $request->get_route();
            
            // Extract company ID from route
            if (preg_match('/\/companies\/(\d+)/', $route, $matches)) {
                return $company->id == $matches[1];
            }
            
            // For branch routes, check if branch belongs to this company
            if (preg_match('/\/branches\/(\d+)/', $route, $matches)) {
                global $wpdb;
                $table_branches = $wpdb->prefix . 'oms_branches';
                
                $branch_company_id = $wpdb->get_var($wpdb->prepare(
                    "SELECT company_id FROM $table_branches WHERE id = %d",
                    $matches[1]
                ));
                
                return $company->id == $branch_company_id;
            }
        }
        
        return false;
    }
    
    /**
     * Check if any user is authenticated
     */
    public function any_authenticated_user($request) {
        $auth = new OMS_Auth();
        return $auth->is_admin_logged_in() || $auth->is_company_logged_in() || $auth->is_employee_logged_in();
    }
    
    /**
     * Get all companies
     */
    public function get_companies($request) {
        global $wpdb;
        $table_companies = $wpdb->prefix . 'oms_companies';
        
        $companies = $wpdb->get_results(
            "SELECT id, company_name, username, profile_image, status, created_at, updated_at 
            FROM $table_companies 
            ORDER BY company_name ASC"
        );
        
        return rest_ensure_response($companies);
    }
    
    /**
     * Get a specific company
     */
    public function get_company($request) {
        $company_id = $request['id'];
        
        global $wpdb;
        $table_companies = $wpdb->prefix . 'oms_companies';
        
        $company = $wpdb->get_row($wpdb->prepare(
            "SELECT id, company_name, username, profile_image, status, created_at, updated_at 
            FROM $table_companies 
            WHERE id = %d",
            $company_id
        ));
        
        if (!$company) {
            return new WP_Error('no_company', 'Company not found', array('status' => 404));
        }
        
        return rest_ensure_response($company);
    }
    
    /**
     * Get branches for a company
     */
    public function get_company_branches($request) {
        $company_id = $request['id'];
        
        global $wpdb;
        $table_branches = $wpdb->prefix . 'oms_branches';
        
        $branches = $wpdb->get_results($wpdb->prepare(
            "SELECT id, branch_name, status, created_at, updated_at 
            FROM $table_branches 
            WHERE company_id = %d 
            ORDER BY branch_name ASC",
            $company_id
        ));
        
        return rest_ensure_response($branches);
    }
    
    /**
     * Get employees for a branch
     */
    public function get_branch_employees($request) {
        $branch_id = $request['id'];
        
        global $wpdb;
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        $employees = $wpdb->get_results($wpdb->prepare(
            "SELECT id, employee_name, username, profile_image, role, status, created_at, updated_at 
            FROM $table_employees 
            WHERE branch_id = %d 
            ORDER BY role, employee_name ASC",
            $branch_id
        ));
        
        return rest_ensure_response($employees);
    }
    
    /**
     * Get tasks based on user type and permissions
     */
    public function get_tasks($request) {
        global $wpdb;
        $table_tasks = $wpdb->prefix . 'oms_tasks';
        $table_task_completions = $wpdb->prefix . 'oms_task_completions';
        
        $auth = new OMS_Auth();
        
        // Filter parameters
        $status = isset($request['status']) ? sanitize_text_field($request['status']) : '';
        $start_date = isset($request['start_date']) ? sanitize_text_field($request['start_date']) : '';
        $end_date = isset($request['end_date']) ? sanitize_text_field($request['end_date']) : '';
        
        // Build query conditions
        $where = "WHERE 1=1";
        $query_args = array();
        
        if (!empty($status) && in_array($status, array('pending', 'completed'))) {
            $where .= " AND t.status = %s";
            $query_args[] = $status;
        }
        
        if (!empty($start_date)) {
            $where .= " AND DATE(t.created_at) >= %s";
            $query_args[] = $start_date;
        }
        
        if (!empty($end_date)) {
            $where .= " AND DATE(t.created_at) <= %s";
            $query_args[] = $end_date;
        }
        
        // Different queries based on user type
        if ($auth->is_admin_logged_in()) {
            // Admin can see all tasks
            $query = "SELECT t.* FROM $table_tasks t $where ORDER BY t.created_at DESC";
            if (!empty($query_args)) {
                $query = $wpdb->prepare($query, $query_args);
            }
            $tasks = $wpdb->get_results($query);
        } 
        else if ($auth->is_company_logged_in()) {
            // Company can see tasks they assigned or that were assigned to their branches
            $company = $auth->get_current_company();
            $where .= " AND (t.assigned_by = %d OR (t.assigned_to_type = 'branch' AND t.assigned_to_id IN (SELECT id FROM {$wpdb->prefix}oms_branches WHERE company_id = %d)))";
            $query_args[] = $company->id;
            $query_args[] = $company->id;
            
            $query = "SELECT t.* FROM $table_tasks t $where ORDER BY t.created_at DESC";
            $tasks = $wpdb->get_results($wpdb->prepare($query, $query_args));
        } 
        else if ($auth->is_employee_logged_in()) {
            // Employee can see tasks assigned to them directly or to their branch
            $employee = $auth->get_current_employee();
            
            $query = "
                SELECT t.* 
                FROM $table_tasks t 
                LEFT JOIN $table_task_completions tc ON t.id = tc.task_id 
                $where
                AND (
                    (t.assigned_to_type = 'employee' AND t.assigned_to_id = %d) 
                    OR 
                    (t.assigned_to_type = 'branch' AND t.assigned_to_id = %d AND tc.employee_id = %d)
                )
                ORDER BY t.created_at DESC
            ";
            $query_args[] = $employee->id;
            $query_args[] = $employee->branch_id;
            $query_args[] = $employee->id;
            
            $tasks = $wpdb->get_results($wpdb->prepare($query, $query_args));
        }
        
        return rest_ensure_response($tasks);
    }
    
    /**
     * Get reports based on user type and permissions
     */
    public function get_reports($request) {
        global $wpdb;
        $table_reports = $wpdb->prefix . 'oms_reports';
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        $auth = new OMS_Auth();
        
        // Filter parameters
        $start_date = isset($request['start_date']) ? sanitize_text_field($request['start_date']) : '';
        $end_date = isset($request['end_date']) ? sanitize_text_field($request['end_date']) : '';
        $employee_id = isset($request['employee_id']) ? intval($request['employee_id']) : 0;
        $branch_id = isset($request['branch_id']) ? intval($request['branch_id']) : 0;
        
        // Build query conditions
        $where = "WHERE 1=1";
        $query_args = array();
        
        if (!empty($start_date)) {
            $where .= " AND r.report_date >= %s";
            $query_args[] = $start_date;
        }
        
        if (!empty($end_date)) {
            $where .= " AND r.report_date <= %s";
            $query_args[] = $end_date;
        }
        
        if ($employee_id > 0) {
            $where .= " AND r.employee_id = %d";
            $query_args[] = $employee_id;
        }
        
        // Different queries based on user type
        if ($auth->is_admin_logged_in()) {
            // Admin can see all reports, optionally filtered by branch
            if ($branch_id > 0) {
                $where .= " AND e.branch_id = %d";
                $query_args[] = $branch_id;
            }
            
            $query = "
                SELECT r.*, e.employee_name, e.role, e.branch_id 
                FROM $table_reports r 
                JOIN $table_employees e ON r.employee_id = e.id 
                $where 
                ORDER BY r.report_date DESC
            ";
            
            if (!empty($query_args)) {
                $query = $wpdb->prepare($query, $query_args);
            }
            
            $reports = $wpdb->get_results($query);
        } 
        else if ($auth->is_company_logged_in()) {
            // Company can see reports from their employees
            $company = $auth->get_current_company();
            
            if ($branch_id > 0) {
                // Verify the branch belongs to this company
                $branch_exists = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM {$wpdb->prefix}oms_branches WHERE id = %d AND company_id = %d",
                    $branch_id, $company->id
                ));
                
                if ($branch_exists) {
                    $where .= " AND e.branch_id = %d";
                    $query_args[] = $branch_id;
                }
            }
            
            $where .= " AND e.company_id = %d";
            $query_args[] = $company->id;
            
            $query = "
                SELECT r.*, e.employee_name, e.role, e.branch_id 
                FROM $table_reports r 
                JOIN $table_employees e ON r.employee_id = e.id 
                $where 
                ORDER BY r.report_date DESC
            ";
            
            $reports = $wpdb->get_results($wpdb->prepare($query, $query_args));
        } 
        else if ($auth->is_employee_logged_in()) {
            // Employee access based on role
            $employee = $auth->get_current_employee();
            
            // If manager, can see all reports in their branch
            if ($employee->role === 'Manager') {
                $where .= " AND e.branch_id = %d";
                $query_args[] = $employee->branch_id;
            }
            // If assistant manager, can see reports from general employees
            else if ($employee->role === 'Asst. Manager') {
                $where .= " AND e.branch_id = %d AND (e.role = 'General Employee' OR e.id = %d)";
                $query_args[] = $employee->branch_id;
                $query_args[] = $employee->id;
            }
            // If general employee, can only see own reports
            else {
                $where .= " AND r.employee_id = %d";
                $query_args[] = $employee->id;
            }
            
            $query = "
                SELECT r.*, e.employee_name, e.role, e.branch_id 
                FROM $table_reports r 
                JOIN $table_employees e ON r.employee_id = e.id 
                $where 
                ORDER BY r.report_date DESC
            ";
            
            $reports = $wpdb->get_results($wpdb->prepare($query, $query_args));
        }
        
        return rest_ensure_response($reports);
    }
}
