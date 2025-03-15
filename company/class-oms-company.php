
<?php
/**
 * Company-specific functionality
 */
class OMS_Company {

    /**
     * Create a new branch
     */
    public function create_branch() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $branch_name = sanitize_text_field($_POST['branch_name']);
        
        if (empty($branch_name)) {
            wp_send_json_error('Branch name is required');
            return;
        }
        
        global $wpdb;
        $table_branches = $wpdb->prefix . 'oms_branches';
        
        $result = $wpdb->insert(
            $table_branches,
            array(
                'company_id' => $current_user->id,
                'branch_name' => $branch_name,
                'status' => 'active'
            )
        );
        
        if (!$result) {
            wp_send_json_error('Failed to create branch');
            return;
        }
        
        $branch_id = $wpdb->insert_id;
        
        wp_send_json_success(array(
            'message' => 'Branch created successfully',
            'branch_id' => $branch_id
        ));
    }

    /**
     * Create a new employee
     */
    public function create_employee() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $employee_name = sanitize_text_field($_POST['employee_name']);
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password']; // Will be hashed before storage
        $profile_image = esc_url_raw($_POST['profile_image']);
        $role = sanitize_text_field($_POST['role']);
        $branch_id = intval($_POST['branch_id']);
        
        // Validation
        if (empty($employee_name) || empty($username) || empty($password) || empty($role)) {
            wp_send_json_error('All fields are required');
            return;
        }
        
        if (!in_array($role, array('Manager', 'Asst. Manager', 'General Employee'))) {
            wp_send_json_error('Invalid role');
            return;
        }
        
        global $wpdb;
        $table_employees = $wpdb->prefix . 'oms_employees';
        $table_branches = $wpdb->prefix . 'oms_branches';
        
        // Verify the branch belongs to this company
        $branch = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_branches WHERE id = %d AND company_id = %d",
            $branch_id, $current_user->id
        ));
        
        if (!$branch) {
            wp_send_json_error('Invalid branch selected');
            return;
        }
        
        // Check if username already exists
        $existing_user = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_employees WHERE username = %s",
            $username
        ));
        
        if ($existing_user) {
            wp_send_json_error('Username already exists');
            return;
        }
        
        // Hash password
        $hashed_password = wp_hash_password($password);
        
        // Insert employee
        $result = $wpdb->insert(
            $table_employees,
            array(
                'branch_id' => $branch_id,
                'company_id' => $current_user->id,
                'employee_name' => $employee_name,
                'username' => $username,
                'password' => $hashed_password,
                'profile_image' => $profile_image,
                'role' => $role,
                'status' => 'active'
            )
        );
        
        if (!$result) {
            wp_send_json_error('Failed to create employee');
            return;
        }
        
        $employee_id = $wpdb->insert_id;
        
        wp_send_json_success(array(
            'message' => 'Employee created successfully',
            'employee_id' => $employee_id
        ));
    }

    /**
     * Get all branches for the company
     */
    public function get_branches() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        global $wpdb;
        $table_branches = $wpdb->prefix . 'oms_branches';
        
        $branches = $wpdb->get_results($wpdb->prepare(
            "SELECT id, branch_name, status, created_at FROM $table_branches WHERE company_id = %d ORDER BY id ASC",
            $current_user->id
        ));
        
        wp_send_json_success(array('branches' => $branches));
    }

    /**
     * Get all employees for the company
     */
    public function get_employees() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $branch_id = isset($_POST['branch_id']) ? intval($_POST['branch_id']) : 0;
        
        global $wpdb;
        $table_employees = $wpdb->prefix . 'oms_employees';
        $table_branches = $wpdb->prefix . 'oms_branches';
        
        if ($branch_id > 0) {
            // Verify the branch belongs to this company
            $branch = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_branches WHERE id = %d AND company_id = %d",
                $branch_id, $current_user->id
            ));
            
            if (!$branch) {
                wp_send_json_error('Invalid branch selected');
                return;
            }
            
            $employees = $wpdb->get_results($wpdb->prepare(
                "SELECT e.*, b.branch_name FROM $table_employees e 
                JOIN $table_branches b ON e.branch_id = b.id 
                WHERE e.company_id = %d AND e.branch_id = %d 
                ORDER BY e.role, e.employee_name",
                $current_user->id, $branch_id
            ));
        } else {
            $employees = $wpdb->get_results($wpdb->prepare(
                "SELECT e.*, b.branch_name FROM $table_employees e 
                JOIN $table_branches b ON e.branch_id = b.id 
                WHERE e.company_id = %d 
                ORDER BY e.role, e.employee_name",
                $current_user->id
            ));
        }
        
        wp_send_json_success(array('employees' => $employees));
    }

    /**
     * Update employee role
     */
    public function update_employee_role() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $employee_id = intval($_POST['employee_id']);
        $new_role = sanitize_text_field($_POST['role']);
        
        if (!in_array($new_role, array('Manager', 'Asst. Manager', 'General Employee'))) {
            wp_send_json_error('Invalid role');
            return;
        }
        
        global $wpdb;
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        // Verify the employee belongs to this company
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_employees WHERE id = %d AND company_id = %d",
            $employee_id, $current_user->id
        ));
        
        if (!$employee) {
            wp_send_json_error('Invalid employee selected');
            return;
        }
        
        $result = $wpdb->update(
            $table_employees,
            array('role' => $new_role),
            array('id' => $employee_id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update employee role');
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Employee role updated successfully',
            'role' => $new_role
        ));
    }

    /**
     * Assign a task to a branch or employee
     */
    public function assign_task() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $assigned_to_type = sanitize_text_field($_POST['assigned_to_type']);
        $assigned_to_id = intval($_POST['assigned_to_id']);
        
        if (empty($title)) {
            wp_send_json_error('Task title is required');
            return;
        }
        
        if (!in_array($assigned_to_type, array('branch', 'employee'))) {
            wp_send_json_error('Invalid assignment type');
            return;
        }
        
        global $wpdb;
        $table_tasks = $wpdb->prefix . 'oms_tasks';
        $table_task_completions = $wpdb->prefix . 'oms_task_completions';
        $table_branches = $wpdb->prefix . 'oms_branches';
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        // Verify the branch or employee belongs to this company
        if ($assigned_to_type === 'branch') {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_branches WHERE id = %d AND company_id = %d",
                $assigned_to_id, $current_user->id
            ));
        } else {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_employees WHERE id = %d AND company_id = %d",
                $assigned_to_id, $current_user->id
            ));
        }
        
        if (!$exists) {
            wp_send_json_error('Invalid assignment target');
            return;
        }
        
        // Insert task
        $result = $wpdb->insert(
            $table_tasks,
            array(
                'title' => $title,
                'description' => $description,
                'assigned_by' => $current_user->id,
                'assigned_to_type' => $assigned_to_type,
                'assigned_to_id' => $assigned_to_id,
                'status' => 'pending'
            )
        );
        
        if (!$result) {
            wp_send_json_error('Failed to create task');
            return;
        }
        
        $task_id = $wpdb->insert_id;
        
        // If assigned to a branch, create task completions for all employees in that branch
        if ($assigned_to_type === 'branch') {
            $employees = $wpdb->get_results($wpdb->prepare(
                "SELECT id FROM $table_employees WHERE branch_id = %d AND status = 'active'",
                $assigned_to_id
            ));
            
            foreach ($employees as $employee) {
                $wpdb->insert(
                    $table_task_completions,
                    array(
                        'task_id' => $task_id,
                        'employee_id' => $employee->id,
                        'status' => 'pending'
                    )
                );
            }
        } else {
            // If assigned to an employee, create a single task completion
            $wpdb->insert(
                $table_task_completions,
                array(
                    'task_id' => $task_id,
                    'employee_id' => $assigned_to_id,
                    'status' => 'pending'
                )
            );
        }
        
        wp_send_json_success(array(
            'message' => 'Task assigned successfully',
            'task_id' => $task_id
        ));
    }

    /**
     * Get reports for branches or employees
     */
    public function get_reports() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $report_type = sanitize_text_field($_POST['report_type']);
        $target_id = intval($_POST['target_id']);
        $date_range = sanitize_text_field($_POST['date_range']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        if (!in_array($report_type, array('branch', 'employee'))) {
            wp_send_json_error('Invalid report type');
            return;
        }
        
        global $wpdb;
        $table_reports = $wpdb->prefix . 'oms_reports';
        $table_employees = $wpdb->prefix . 'oms_employees';
        $table_branches = $wpdb->prefix . 'oms_branches';
        
        // Set date conditions based on range
        $date_condition = "";
        switch ($date_range) {
            case 'daily':
                $date_condition = "AND DATE(r.report_date) = CURDATE()";
                break;
            case 'monthly':
                $date_condition = "AND MONTH(r.report_date) = MONTH(CURDATE()) AND YEAR(r.report_date) = YEAR(CURDATE())";
                break;
            case 'yearly':
                $date_condition = "AND YEAR(r.report_date) = YEAR(CURDATE())";
                break;
            case 'custom':
                if (!empty($start_date) && !empty($end_date)) {
                    $date_condition = $wpdb->prepare(
                        "AND r.report_date BETWEEN %s AND %s",
                        $start_date, $end_date
                    );
                }
                break;
        }
        
        // Get reports based on report type
        if ($report_type === 'branch') {
            // Verify the branch belongs to this company
            $branch = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_branches WHERE id = %d AND company_id = %d",
                $target_id, $current_user->id
            ));
            
            if (!$branch) {
                wp_send_json_error('Invalid branch selected');
                return;
            }
            
            $reports = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, e.employee_name, e.role FROM $table_reports r 
                JOIN $table_employees e ON r.employee_id = e.id 
                WHERE e.branch_id = %d AND e.company_id = %d $date_condition 
                ORDER BY r.report_date DESC, e.role, e.employee_name",
                $target_id, $current_user->id
            ));
        } else {
            // Verify the employee belongs to this company
            $employee = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM $table_employees WHERE id = %d AND company_id = %d",
                $target_id, $current_user->id
            ));
            
            if (!$employee) {
                wp_send_json_error('Invalid employee selected');
                return;
            }
            
            $reports = $wpdb->get_results($wpdb->prepare(
                "SELECT r.*, e.employee_name, e.role FROM $table_reports r 
                JOIN $table_employees e ON r.employee_id = e.id 
                WHERE r.employee_id = %d AND e.company_id = %d $date_condition 
                ORDER BY r.report_date DESC",
                $target_id, $current_user->id
            ));
        }
        
        wp_send_json_success(array('reports' => $reports));
    }

    /**
     * Toggle branch status (activate/deactivate)
     */
    public function toggle_branch_status() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $branch_id = intval($_POST['branch_id']);
        $new_status = sanitize_text_field($_POST['status']);
        
        if (!in_array($new_status, array('active', 'inactive'))) {
            wp_send_json_error('Invalid status');
            return;
        }
        
        global $wpdb;
        $table_branches = $wpdb->prefix . 'oms_branches';
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        // Verify the branch belongs to this company
        $branch = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_branches WHERE id = %d AND company_id = %d",
            $branch_id, $current_user->id
        ));
        
        if (!$branch) {
            wp_send_json_error('Invalid branch selected');
            return;
        }
        
        // Update branch status
        $result = $wpdb->update(
            $table_branches,
            array('status' => $new_status),
            array('id' => $branch_id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update branch status');
            return;
        }
        
        // Update all employees in this branch to the same status
        $wpdb->update(
            $table_employees,
            array('status' => $new_status),
            array('branch_id' => $branch_id)
        );
        
        wp_send_json_success(array(
            'message' => 'Branch status updated successfully',
            'status' => $new_status
        ));
    }

    /**
     * Toggle employee status (activate/deactivate)
     */
    public function toggle_employee_status() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $employee_id = intval($_POST['employee_id']);
        $new_status = sanitize_text_field($_POST['status']);
        
        if (!in_array($new_status, array('active', 'inactive'))) {
            wp_send_json_error('Invalid status');
            return;
        }
        
        global $wpdb;
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        // Verify the employee belongs to this company
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_employees WHERE id = %d AND company_id = %d",
            $employee_id, $current_user->id
        ));
        
        if (!$employee) {
            wp_send_json_error('Invalid employee selected');
            return;
        }
        
        $result = $wpdb->update(
            $table_employees,
            array('status' => $new_status),
            array('id' => $employee_id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update employee status');
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Employee status updated successfully',
            'status' => $new_status
        ));
    }

    /**
     * Switch employee branch
     */
    public function switch_employee_branch() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $employee_id = intval($_POST['employee_id']);
        $new_branch_id = intval($_POST['branch_id']);
        
        global $wpdb;
        $table_employees = $wpdb->prefix . 'oms_employees';
        $table_branches = $wpdb->prefix . 'oms_branches';
        
        // Verify the employee belongs to this company
        $employee = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_employees WHERE id = %d AND company_id = %d",
            $employee_id, $current_user->id
        ));
        
        if (!$employee) {
            wp_send_json_error('Invalid employee selected');
            return;
        }
        
        // Verify the branch belongs to this company
        $branch = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_branches WHERE id = %d AND company_id = %d",
            $new_branch_id, $current_user->id
        ));
        
        if (!$branch) {
            wp_send_json_error('Invalid branch selected');
            return;
        }
        
        $result = $wpdb->update(
            $table_employees,
            array('branch_id' => $new_branch_id),
            array('id' => $employee_id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to switch employee branch');
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Employee branch switched successfully',
            'branch_id' => $new_branch_id
        ));
    }

    /**
     * Update company profile
     */
    public function update_profile() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $company_name = sanitize_text_field($_POST['company_name']);
        $profile_image = esc_url_raw($_POST['profile_image']);
        
        if (empty($company_name)) {
            wp_send_json_error('Company name is required');
            return;
        }
        
        global $wpdb;
        $table_companies = $wpdb->prefix . 'oms_companies';
        
        $result = $wpdb->update(
            $table_companies,
            array(
                'company_name' => $company_name,
                'profile_image' => $profile_image
            ),
            array('id' => $current_user->id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update profile');
            return;
        }
        
        wp_send_json_success(array(
            'message' => 'Profile updated successfully'
        ));
    }

    /**
     * Send message to branches or employees
     */
    public function send_message() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $receiver_type = sanitize_text_field($_POST['receiver_type']);
        $receiver_id = intval($_POST['receiver_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $attachment = isset($_POST['attachment']) ? esc_url_raw($_POST['attachment']) : '';
        
        if (empty($message)) {
            wp_send_json_error('Message cannot be empty');
            return;
        }
        
        if (!in_array($receiver_type, array('branch', 'employee'))) {
            wp_send_json_error('Invalid receiver type');
            return;
        }
        
        global $wpdb;
        $table_messages = $wpdb->prefix . 'oms_messages';
        $table_branches = $wpdb->prefix . 'oms_branches';
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        // Verify the branch or employee belongs to this company
        if ($receiver_type === 'branch') {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_branches WHERE id = %d AND company_id = %d",
                $receiver_id, $current_user->id
            ));
        } else {
            $exists = $wpdb->get_var($wpdb->prepare(
                "SELECT id FROM $table_employees WHERE id = %d AND company_id = %d",
                $receiver_id, $current_user->id
            ));
        }
        
        if (!$exists) {
            wp_send_json_error('Invalid receiver');
            return;
        }
        
        $result = $wpdb->insert(
            $table_messages,
            array(
                'sender_type' => 'company',
                'sender_id' => $current_user->id,
                'receiver_type' => $receiver_type,
                'receiver_id' => $receiver_id,
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
     * Reply to admin message
     */
    public function reply_to_admin() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $original_message_id = intval($_POST['message_id']);
        $reply = sanitize_textarea_field($_POST['reply']);
        $attachment = isset($_POST['attachment']) ? esc_url_raw($_POST['attachment']) : '';
        
        if (empty($reply)) {
            wp_send_json_error('Reply cannot be empty');
            return;
        }
        
        global $wpdb;
        $table_messages = $wpdb->prefix . 'oms_messages';
        
        // Verify the original message was sent to this company
        $original_message = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_messages WHERE id = %d AND receiver_type = 'company' AND receiver_id = %d",
            $original_message_id, $current_user->id
        ));
        
        if (!$original_message) {
            wp_send_json_error('Invalid message to reply to');
            return;
        }
        
        $result = $wpdb->insert(
            $table_messages,
            array(
                'sender_type' => 'company',
                'sender_id' => $current_user->id,
                'receiver_type' => 'admin',
                'receiver_id' => 0,
                'message' => $reply,
                'attachment' => $attachment,
                'status' => 'unread'
            )
        );
        
        if (!$result) {
            wp_send_json_error('Failed to send reply');
            return;
        }
        
        $reply_id = $wpdb->insert_id;
        
        // Mark the original message as read
        $wpdb->update(
            $table_messages,
            array('status' => 'read'),
            array('id' => $original_message_id)
        );
        
        wp_send_json_success(array(
            'message' => 'Reply sent successfully',
            'reply_id' => $reply_id
        ));
    }

    /**
     * Generate and download a PDF report
     */
    public function download_report() {
        check_ajax_referer('oms_company_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_company();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $report_type = sanitize_text_field($_POST['report_type']);
        $target_id = intval($_POST['target_id']);
        $date_range = sanitize_text_field($_POST['date_range']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        if (!in_array($report_type, array('branch', 'employee'))) {
            wp_send_json_error('Invalid report type');
            return;
        }
        
        // Here we would normally generate a PDF report
        // For the sake of this example, we'll return success
        // In a real implementation, you'd use a library like TCPDF or DOMPDF
        
        wp_send_json_success(array(
            'message' => 'PDF report generated successfully',
            'download_url' => '#' // In a real implementation, this would be a URL to download the PDF
        ));
    }
}
