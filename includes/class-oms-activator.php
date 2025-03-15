<?php
/**
 * Fired during plugin activation
 */
class OMS_Activator {

    /**
     * Create necessary database tables and roles during plugin activation
     */
    public static function activate() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        // Companies table
        $table_companies = $wpdb->prefix . 'oms_companies';
        $sql_companies = "CREATE TABLE $table_companies (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            company_name varchar(100) NOT NULL,
            username varchar(50) NOT NULL UNIQUE,
            password varchar(255) NOT NULL,
            profile_image varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Branches table
        $table_branches = $wpdb->prefix . 'oms_branches';
        $sql_branches = "CREATE TABLE $table_branches (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            company_id mediumint(9) NOT NULL,
            branch_name varchar(100) NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            FOREIGN KEY  (company_id) REFERENCES $table_companies(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Employees table
        $table_employees = $wpdb->prefix . 'oms_employees';
        $sql_employees = "CREATE TABLE $table_employees (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            branch_id mediumint(9) NOT NULL,
            company_id mediumint(9) NOT NULL,
            employee_name varchar(100) NOT NULL,
            username varchar(50) NOT NULL UNIQUE,
            password varchar(255) NOT NULL,
            profile_image varchar(255) DEFAULT '',
            role varchar(50) NOT NULL,
            status varchar(20) DEFAULT 'active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            FOREIGN KEY  (branch_id) REFERENCES $table_branches(id) ON DELETE CASCADE,
            FOREIGN KEY  (company_id) REFERENCES $table_companies(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Tasks table
        $table_tasks = $wpdb->prefix . 'oms_tasks';
        $sql_tasks = "CREATE TABLE $table_tasks (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            description text,
            assigned_by mediumint(9) NOT NULL,
            assigned_to_type varchar(20) NOT NULL,
            assigned_to_id mediumint(9) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        // Task completions table
        $table_task_completions = $wpdb->prefix . 'oms_task_completions';
        $sql_task_completions = "CREATE TABLE $table_task_completions (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            task_id mediumint(9) NOT NULL,
            employee_id mediumint(9) NOT NULL,
            status varchar(20) DEFAULT 'pending',
            completed_at datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            FOREIGN KEY  (task_id) REFERENCES $table_tasks(id) ON DELETE CASCADE,
            FOREIGN KEY  (employee_id) REFERENCES $table_employees(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Reports table
        $table_reports = $wpdb->prefix . 'oms_reports';
        $sql_reports = "CREATE TABLE $table_reports (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            employee_id mediumint(9) NOT NULL,
            report_date date NOT NULL,
            report_content text NOT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            FOREIGN KEY  (employee_id) REFERENCES $table_employees(id) ON DELETE CASCADE
        ) $charset_collate;";

        // Messages table
        $table_messages = $wpdb->prefix . 'oms_messages';
        $sql_messages = "CREATE TABLE $table_messages (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            sender_type varchar(20) NOT NULL,
            sender_id mediumint(9) NOT NULL,
            receiver_type varchar(20) NOT NULL,
            receiver_id mediumint(9) NOT NULL,
            message text NOT NULL,
            attachment varchar(255) DEFAULT '',
            status varchar(20) DEFAULT 'unread',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_companies);
        dbDelta($sql_branches);
        dbDelta($sql_employees);
        dbDelta($sql_tasks);
        dbDelta($sql_task_completions);
        dbDelta($sql_reports);
        dbDelta($sql_messages);

        // Create a default main branch for each existing company
        $companies = $wpdb->get_results("SELECT id FROM $table_companies");
        if (!empty($companies)) {
            foreach ($companies as $company) {
                $wpdb->insert(
                    $table_branches,
                    array(
                        'company_id' => $company->id,
                        'branch_name' => 'Main Branch'
                    )
                );
            }
        }

        // Add rewrite rules for custom pages
        flush_rewrite_rules();
    }
}
