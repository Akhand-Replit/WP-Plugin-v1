<div class="wrap oms-admin-wrapper">
    <div class="oms-admin-header">
        <h1 class="oms-admin-title"><?php echo esc_html__('Organization Management Dashboard', 'org-management'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=oms-companies&action=new'); ?>" class="page-title-action"><?php echo esc_html__('Add New Company', 'org-management'); ?></a>
    </div>
    
    <?php
    // Get stats
    global $wpdb;
    $table_companies = $wpdb->prefix . 'oms_companies';
    $table_branches = $wpdb->prefix . 'oms_branches';
    $table_employees = $wpdb->prefix . 'oms_employees';
    $table_tasks = $wpdb->prefix . 'oms_tasks';
    
    $company_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_companies");
    $active_company_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_companies WHERE status = 'active'");
    
    $branch_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_branches");
    $active_branch_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_branches WHERE status = 'active'");
    
    $employee_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_employees");
    $active_employee_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_employees WHERE status = 'active'");
    
    $task_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_tasks");
    $pending_task_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_tasks WHERE status = 'pending'");
    ?>
    
    <div class="oms-admin-stats">
        <div class="oms-admin-stat-card">
            <div class="oms-admin-stat-label"><?php echo esc_html__('Companies', 'org-management'); ?></div>
            <div class="oms-admin-stat-number"><?php echo $company_count; ?></div>
            <div><?php echo sprintf(esc_html__('%d Active', 'org-management'), $active_company_count); ?></div>
        </div>
        
        <div class="oms-admin-stat-card">
            <div class="oms-admin-stat-label"><?php echo esc_html__('Branches', 'org-management'); ?></div>
            <div class="oms-admin-stat-number"><?php echo $branch_count; ?></div>
            <div><?php echo sprintf(esc_html__('%d Active', 'org-management'), $active_branch_count); ?></div>
        </div>
        
        <div class="oms-admin-stat-card">
            <div class="oms-admin-stat-label"><?php echo esc_html__('Employees', 'org-management'); ?></div>
            <div class="oms-admin-stat-number"><?php echo $employee_count; ?></div>
            <div><?php echo sprintf(esc_html__('%d Active', 'org-management'), $active_employee_count); ?></div>
        </div>
        
        <div class="oms-admin-stat-card">
            <div class="oms-admin-stat-label"><?php echo esc_html__('Tasks', 'org-management'); ?></div>
            <div class="oms-admin-stat-number"><?php echo $task_count; ?></div>
            <div><?php echo sprintf(esc_html__('%d Pending', 'org-management'), $pending_task_count); ?></div>
        </div>
    </div>
    
    <div class="oms-admin-card">
        <div class="oms-admin-card-header">
            <?php echo esc_html__('Recent Companies', 'org-management'); ?>
        </div>
        <div class="oms-admin-card-body">
            <table class="oms-admin-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('Company', 'org-management'); ?></th>
                        <th><?php echo esc_html__('Username', 'org-management'); ?></th>
                        <th><?php echo esc_html__('Status', 'org-management'); ?></th>
                        <th><?php echo esc_html__('Created', 'org-management'); ?></th>
                        <th><?php echo esc_html__('Actions', 'org-management'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $recent_companies = $wpdb->get_results(
                        "SELECT id, company_name, username, status, created_at 
                        FROM $table_companies 
                        ORDER BY id DESC 
                        LIMIT 5"
                    );
                    
                    if (!empty($recent_companies)) {
                        foreach ($recent_companies as $company) {
                            ?>
                            <tr>
                                <td><?php echo esc_html($company->company_name); ?></td>
                                <td><?php echo esc_html($company->username); ?></td>
                                <td>
                                    <span class="oms-status-<?php echo esc_attr($company->status); ?>">
                                        <?php echo esc_html($company->status); ?>
                                    </span>
                                </td>
                                <td><?php echo esc_html(date_i18n(get_option('date_format'), strtotime($company->created_at))); ?></td>
                                <td>
                                    <a href="<?php echo admin_url('admin.php?page=oms-companies&action=view&id=' . $company->id); ?>" class="button button-small">
                                        <?php echo esc_html__('View', 'org-management'); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        ?>
                        <tr>
                            <td colspan="5"><?php echo esc_html__('No companies found', 'org-management'); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="oms-admin-card-footer">
            <a href="<?php echo admin_url('admin.php?page=oms-companies'); ?>" class="button">
                <?php echo esc_html__('View All Companies', 'org-management'); ?>
            </a>
        </div>
    </div>
    
    <div class="oms-admin-card">
        <div class="oms-admin-card-header">
            <?php echo esc_html__('Recent Messages', 'org-management'); ?>
        </div>
        <div class="oms-admin-card-body">
            <?php
            $table_messages = $wpdb->prefix . 'oms_messages';
            
            $recent_messages = $wpdb->get_results(
                "SELECT m.*, c.company_name 
                FROM $table_messages m
                LEFT JOIN $table_companies c ON (m.sender_type = 'company' AND m.sender_id = c.id) OR (m.receiver_type = 'company' AND m.receiver_id = c.id)
                WHERE m.sender_type = 'company' OR m.receiver_type = 'company'
                ORDER BY m.created_at DESC 
                LIMIT 5"
            );
            
            if (!empty($recent_messages)) {
                foreach ($recent_messages as $message) {
                    $is_from_admin = ($message->sender_type === 'admin');
                    $message_direction = $is_from_admin ? 'sent to' : 'received from';
                    $company_name = isset($message->company_name) ? $message->company_name : 'Unknown Company';
                    ?>
                    <div class="oms-admin-message">
                        <div class="oms-admin-message-sender">
                            <?php echo esc_html(sprintf('%s %s %s', 
                                $is_from_admin ? 'You' : $company_name,
                                $message_direction,
                                $is_from_admin ? $company_name : 'you'
                            )); ?>
                            <span class="oms-admin-message-time">
                                <?php echo esc_html(human_time_diff(strtotime($message->created_at), current_time('timestamp'))); ?> ago
                            </span>
                        </div>
                        <div class="oms-admin-message-content">
                            <?php echo esc_html(wp_trim_words($message->message, 20)); ?>
                        </div>
                    </div>
                    <?php
                }
            } else {
                echo '<p>' . esc_html__('No messages found', 'org-management') . '</p>';
            }
            ?>
        </div>
        <div class="oms-admin-card-footer">
            <a href="<?php echo admin_url('admin.php?page=oms-messages'); ?>" class="button">
                <?php echo esc_html__('View All Messages', 'org-management'); ?>
            </a>
        </div>
    </div>
</div>
