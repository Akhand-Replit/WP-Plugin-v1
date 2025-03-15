<?php
/**
 * Plugin Name: Organization Management System
 * Description: A plugin to manage organizations with hierarchical structure (Admin, Companies, Branches, Employees).
 * Version: 1.0.0
 * Author: Tareq Mohammad Rafiq Shuvo
 * Text Domain: org-management
 */

// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}

// Define plugin constants
define('OMS_VERSION', '1.0.0');
define('OMS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('OMS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('OMS_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once OMS_PLUGIN_DIR . 'includes/class-oms-activator.php';
require_once OMS_PLUGIN_DIR . 'includes/class-oms-deactivator.php';
require_once OMS_PLUGIN_DIR . 'includes/class-oms-core.php';

// Activation and deactivation hooks
register_activation_hook(__FILE__, array('OMS_Activator', 'activate'));
register_deactivation_hook(__FILE__, array('OMS_Deactivator', 'deactivate'));

/**
 * Begin execution of the plugin
 */
function run_organization_management_system() {
    $plugin = new OMS_Core();
    $plugin->run();
}

run_organization_management_system();
