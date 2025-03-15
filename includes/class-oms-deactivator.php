<?php
/**
 * Fired during plugin deactivation
 */
class OMS_Deactivator {

    /**
     * Clean up during plugin deactivation
     */
    public static function deactivate() {
        // Flush rewrite rules on deactivation
        flush_rewrite_rules();
        
        // Note: We are not removing tables on deactivation to preserve data
        // If you want to remove tables, uncomment the code below
        
        /*
        global $wpdb;
        $tables = array(
            $wpdb->prefix . 'oms_messages',
            $wpdb->prefix . 'oms_reports',
            $wpdb->prefix . 'oms_task_completions',
            $wpdb->prefix . 'oms_tasks',
            $wpdb->prefix . 'oms_employees',
            $wpdb->prefix . 'oms_branches',
            $wpdb->prefix . 'oms_companies'
        );
        
        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
        */
    }
}
