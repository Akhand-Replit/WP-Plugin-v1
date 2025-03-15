
<?php
/**
 * Employee-specific functionality
 */
class OMS_Employee {

    /**
     * Create a subordinate employee (for Managers and Asst. Managers)
     */
    public function create_subordinate() {
        check_ajax_referer('oms_employee_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_employee();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        // Only managers and assistant managers can create subordinates
        if ($current_user->role !== 'Manager' && $current_user->role !== 'Asst. Manager') {
            wp_send_json_error('You do not have permission to create employees');
            return;
        }
        
        $employee_name = sanitize_text_field($_POST['employee_name']);
        $username = sanitize_user($_POST['username']);
        $password = $_POST['password']; // Will be hashed before storage
        $profile_image = esc_url_raw($_POST['profile_image']);
        
        // Validation
        if (empty($employee_name) || empty($username) || empty($password)) {
            wp_send_json_error('All fields are required');
            return;
        }
        
        global $wpdb;
        $table_employees = $wpdb->prefix . 'oms_employees';
        
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
        
        // Insert employee (always as General Employee)
        $result = $wpdb->insert(
            $table_employees,
            array(
                'branch_id' => $current_user->branch_id,
                'company_id' => $current_user->company_id,
                'employee_name' => $employee_name,
                'username' => $username,
                'password' => $hashed_password,
                'profile_image' => $profile_image,
                'role' => 'General Employee',
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
     * Assign a task to a subordinate employee
     */
    public function assign_task() {
        check_ajax_referer('oms_employee_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_employee();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        // Only managers and assistant managers can assign tasks
        if ($current_user->role !== 'Manager' && $current_user->role !== 'Asst. Manager') {
            wp_send_json_error('You do not have permission to assign tasks');
            return;
        }
        
        $title = sanitize_text_field($_POST['title']);
        $description = sanitize_textarea_field($_POST['description']);
        $employee_id = intval($_POST['employee_id']);
        
        if (empty($title)) {
            wp_send_json_error('Task title is required');
            return;
        }
        
        global $wpdb;
        $table_tasks = $wpdb->prefix . 'oms_tasks';
        $table_task_completions = $wpdb->prefix . 'oms_task_completions';
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        // If manager, can assign to any general employee or assistant manager in the branch
        if ($current_user->role === 'Manager') {
            $target_employee = $wpdb->get_row($wpdb->prepare(
                "SELECT id, role FROM $table_employees WHERE id = %d AND branch_id = %d AND company_id = %d AND status = 'active'",
                $employee_id, $current_user->branch_id, $current_user->company_id
            ));
            
            if (!$target_employee || $target_employee->role === 'Manager') {
                wp_send_json_error('Invalid employee selected or you cannot assign tasks to another manager');
                return;
            }
        } 
        // If assistant manager, can only assign to general employees
        else if ($current_user->role === 'Asst. Manager') {
            $target_employee = $wpdb->get_row($wpdb->prepare(
                "SELECT id, role FROM $table_employees WHERE id = %d AND branch_id = %d AND company_id = %d AND role = 'General Employee' AND status = 'active'",
                $employee_id, $current_user->branch_id, $current_user->company_id
            ));
            
            if (!$target_employee) {
                wp_send_json_error('Invalid employee selected or you cannot assign tasks to managers or other assistant managers');
                return;
            }
        }
        
        // Insert task
        $result = $wpdb->insert(
            $table_tasks,
            array(
                'title' => $title,
                'description' => $description,
                'assigned_by' => $current_user->id,
                'assigned_to_type' => 'employee',
                'assigned_to_id' => $employee_id,
                'status' => 'pending'
            )
        );
        
        if (!$result) {
            wp_send_json_error('Failed to create task');
            return;
        }
        
        $task_id = $wpdb->insert_id;
        
        // Create task completion record
        $wpdb->insert(
            $table_task_completions,
            array(
                'task_id' => $task_id,
                'employee_id' => $employee_id,
                'status' => 'pending'
            )
        );
        
        wp_send_json_success(array(
            'message' => 'Task assigned successfully',
            'task_id' => $task_id
        ));
    }

    /**
     * Get reports for subordinate employees or self
     */
    public function get_reports() {
        check_ajax_referer('oms_employee_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_employee();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $report_type = sanitize_text_field($_POST['report_type']);
        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : $current_user->id;
        $date_range = sanitize_text_field($_POST['date_range']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        global $wpdb;
        $table_reports = $wpdb->prefix . 'oms_reports';
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        // If not self, verify that the employee is a subordinate
        if ($employee_id !== $current_user->id) {
            // Managers can view reports of any employee in their branch
            if ($current_user->role === 'Manager') {
                $is_subordinate = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_employees WHERE id = %d AND branch_id = %d",
                    $employee_id, $current_user->branch_id
                ));
            }
            // Assistant managers can only view general employee reports
            else if ($current_user->role === 'Asst. Manager') {
                $is_subordinate = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_employees WHERE id = %d AND branch_id = %d AND role = 'General Employee'",
                    $employee_id, $current_user->branch_id
                ));
            }
            // General employees can only view their own reports
            else {
                $is_subordinate = false;
            }
            
            if (!$is_subordinate) {
                wp_send_json_error('You do not have permission to view this employee\'s reports');
                return;
            }
        }
        
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
        
        $reports = $wpdb->get_results($wpdb->prepare(
            "SELECT r.*, e.employee_name, e.role FROM $table_reports r 
            JOIN $table_employees e ON r.employee_id = e.id 
            WHERE r.employee_id = %d $date_condition 
            ORDER BY r.report_date DESC",
            $employee_id
        ));
        
        wp_send_json_success(array('reports' => $reports));
    }

    /**
     * Toggle subordinate employee status (activate/deactivate)
     */
    public function toggle_subordinate_status() {
        check_ajax_referer('oms_employee_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_employee();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        // Only managers and assistant managers can toggle employee status
        if ($current_user->role !== 'Manager' && $current_user->role !== 'Asst. Manager') {
            wp_send_json_error('You do not have permission to change employee status');
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
        
        // Verify the employee is in the same branch and is a subordinate
        if ($current_user->role === 'Manager') {
            $target_employee = $wpdb->get_row($wpdb->prepare(
                "SELECT id, role FROM $table_employees WHERE id = %d AND branch_id = %d AND id != %d",
                $employee_id, $current_user->branch_id, $current_user->id
            ));
        } else { // Asst. Manager
            $target_employee = $wpdb->get_row($wpdb->prepare(
                "SELECT id, role FROM $table_employees WHERE id = %d AND branch_id = %d AND role = 'General Employee'",
                $employee_id, $current_user->branch_id
            ));
        }
        
        if (!$target_employee) {
            wp_send_json_error('Invalid employee selected or you do not have permission to modify this employee');
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
     * Update employee profile
     */
    public function update_profile() {
        check_ajax_referer('oms_employee_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_employee();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $employee_name = sanitize_text_field($_POST['employee_name']);
        $profile_image = esc_url_raw($_POST['profile_image']);
        
        if (empty($employee_name)) {
            wp_send_json_error('Employee name is required');
            return;
        }
        
        global $wpdb;
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        $result = $wpdb->update(
            $table_employees,
            array(
                'employee_name' => $employee_name,
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
     * Submit a daily report
     */
    public function submit_report() {
        check_ajax_referer('oms_employee_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_employee();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $report_date = sanitize_text_field($_POST['report_date']);
        $report_content = sanitize_textarea_field($_POST['report_content']);
        
        if (empty($report_date) || empty($report_content)) {
            wp_send_json_error('Date and report content are required');
            return;
        }
        
        global $wpdb;
        $table_reports = $wpdb->prefix . 'oms_reports';
        
        // Check if a report for this date already exists
        $existing_report = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table_reports WHERE employee_id = %d AND report_date = %s",
            $current_user->id, $report_date
        ));
        
        if ($existing_report) {
            // Update existing report
            $result = $wpdb->update(
                $table_reports,
                array('report_content' => $report_content),
                array('id' => $existing_report)
            );
            
            $message = 'Report updated successfully';
        } else {
            // Insert new report
            $result = $wpdb->insert(
                $table_reports,
                array(
                    'employee_id' => $current_user->id,
                    'report_date' => $report_date,
                    'report_content' => $report_content
                )
            );
            
            $message = 'Report submitted successfully';
        }
        
        if (!$result) {
            wp_send_json_error('Failed to submit report');
            return;
        }
        
        wp_send_json_success(array(
            'message' => $message
        ));
    }

    /**
     * Update task status (mark as completed)
     */
    public function update_task_status() {
        check_ajax_referer('oms_employee_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_employee();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $task_id = intval($_POST['task_id']);
        $status = sanitize_text_field($_POST['status']);
        
        if (!in_array($status, array('completed', 'pending'))) {
            wp_send_json_error('Invalid status');
            return;
        }
        
        global $wpdb;
        $table_task_completions = $wpdb->prefix . 'oms_task_completions';
        $table_tasks = $wpdb->prefix . 'oms_tasks';
        
        // Verify the task is assigned to this employee
        $task_completion = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_task_completions WHERE task_id = %d AND employee_id = %d",
            $task_id, $current_user->id
        ));
        
        if (!$task_completion) {
            wp_send_json_error('Task not assigned to you');
            return;
        }
        
        // Update task completion status
        $result = $wpdb->update(
            $table_task_completions,
            array(
                'status' => $status,
                'completed_at' => $status === 'completed' ? current_time('mysql') : null
            ),
            array('id' => $task_completion->id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to update task status');
            return;
        }
        
        // If the task was assigned to a branch, check if all employees have completed it
        // If all have completed or if the employee is a manager/assistant manager, mark the main task as completed
        $task = $wpdb->get_row($wpdb->prepare(
            "SELECT assigned_to_type, assigned_to_id FROM $table_tasks WHERE id = %d",
            $task_id
        ));
        
        if ($task->assigned_to_type === 'branch') {
            // If manager or asst. manager, they can mark task as completed for the branch
            if ($current_user->role === 'Manager' || $current_user->role === 'Asst. Manager') {
                $wpdb->update(
                    $table_tasks,
                    array('status' => $status),
                    array('id' => $task_id)
                );
            }
            // Otherwise, check if all employees have completed
            else {
                $pending_count = $wpdb->get_var($wpdb->prepare(
                    "SELECT COUNT(*) FROM $table_task_completions WHERE task_id = %d AND status = 'pending'",
                    $task_id
                ));
                
                if ($pending_count == 0) {
                    $wpdb->update(
                        $table_tasks,
                        array('status' => 'completed'),
                        array('id' => $task_id)
                    );
                }
            }
        } else {
            // If task was assigned directly to this employee, update the main task status
            $wpdb->update(
                $table_tasks,
                array('status' => $status),
                array('id' => $task_id)
            );
        }
        
        wp_send_json_success(array(
            'message' => 'Task status updated successfully',
            'status' => $status
        ));
    }

    /**
     * Send message to branch manager, assistant manager, or other employees
     */
    public function send_message() {
        check_ajax_referer('oms_employee_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_employee();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $receiver_id = intval($_POST['receiver_id']);
        $message = sanitize_textarea_field($_POST['message']);
        $attachment = isset($_POST['attachment']) ? esc_url_raw($_POST['attachment']) : '';
        
        if (empty($message)) {
            wp_send_json_error('Message cannot be empty');
            return;
        }
        
        global $wpdb;
        $table_messages = $wpdb->prefix . 'oms_messages';
        $table_employees = $wpdb->prefix . 'oms_employees';
        
        // Verify the receiver is in the same branch
        $receiver = $wpdb->get_row($wpdb->prepare(
            "SELECT id FROM $table_employees WHERE id = %d AND branch_id = %d",
            $receiver_id, $current_user->branch_id
        ));
        
        if (!$receiver) {
            wp_send_json_error('Invalid receiver');
            return;
        }
        
        $result = $wpdb->insert(
            $table_messages,
            array(
                'sender_type' => 'employee',
                'sender_id' => $current_user->id,
                'receiver_type' => 'employee',
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
     * Reply to a message
     */
    public function reply_to_message() {
        check_ajax_referer('oms_employee_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_employee();
        
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
        
        // Verify the original message was sent to this employee
        $original_message = $wpdb->get_row($wpdb->prepare(
            "SELECT sender_type, sender_id FROM $table_messages 
            WHERE id = %d AND receiver_type = 'employee' AND receiver_id = %d",
            $original_message_id, $current_user->id
        ));
        
        if (!$original_message) {
            wp_send_json_error('Invalid message to reply to');
            return;
        }
        
        $result = $wpdb->insert(
            $table_messages,
            array(
                'sender_type' => 'employee',
                'sender_id' => $current_user->id,
                'receiver_type' => $original_message->sender_type,
                'receiver_id' => $original_message->sender_id,
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
        check_ajax_referer('oms_employee_nonce', 'nonce');
        
        // Verify authentication
        $auth = new OMS_Auth();
        $current_user = $auth->get_current_employee();
        
        if (!$current_user) {
            wp_send_json_error('Authentication failed');
            return;
        }
        
        $employee_id = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : $current_user->id;
        $date_range = sanitize_text_field($_POST['date_range']);
        $start_date = sanitize_text_field($_POST['start_date']);
        $end_date = sanitize_text_field($_POST['end_date']);
        
        // If not self, verify that the employee is a subordinate
        if ($employee_id !== $current_user->id) {
            global $wpdb;
            $table_employees = $wpdb->prefix . 'oms_employees';
            
            // Managers can view reports of any employee in their branch
            if ($current_user->role === 'Manager') {
                $is_subordinate = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_employees WHERE id = %d AND branch_id = %d",
                    $employee_id, $current_user->branch_id
                ));
            }
            // Assistant managers can only view general employee reports
            else if ($current_user->role === 'Asst. Manager') {
                $is_subordinate = $wpdb->get_var($wpdb->prepare(
                    "SELECT id FROM $table_employees WHERE id = %d AND branch_id = %d AND role = 'General Employee'",
                    $employee_id, $current_user->branch_id
                ));
            }
            // General employees can only view their own reports
            else {
                $is_subordinate = false;
            }
            
            if (!$is_subordinate) {
                wp_send_json_error('You do not have permission to view this employee\'s reports');
                return;
            }
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
