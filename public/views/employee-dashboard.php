<?php
// Get employee details
$auth = new OMS_Auth();
$employee = $auth->get_current_employee();

// Get role-specific permissions
$is_manager = ($employee->role === 'Manager');
$is_asst_manager = ($employee->role === 'Asst. Manager');
$can_create_employees = ($is_manager || $is_asst_manager);
$can_assign_tasks = ($is_manager || $is_asst_manager);

// Get tasks
global $wpdb;
$table_task_completions = $wpdb->prefix . 'oms_task_completions';
$table_tasks = $wpdb->prefix . 'oms_tasks';

$pending_tasks = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_task_completions tc
    JOIN $table_tasks t ON tc.task_id = t.id
    WHERE tc.employee_id = %d AND tc.status = 'pending'",
    $employee->id
));

// Get report count
$table_reports = $wpdb->prefix . 'oms_reports';
$report_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_reports WHERE employee_id = %d",
    $employee->id
));
?>

<div class="oms-employee-dashboard">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-success mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <?php if (!empty($employee->profile_image)): ?>
                    <img src="<?php echo esc_url($employee->profile_image); ?>" alt="<?php echo esc_attr($employee->employee_name); ?>" class="profile-image">
                <?php endif; ?>
                <?php echo esc_html($employee->employee_name); ?> 
                <span class="badge bg-light text-success"><?php echo esc_html($employee->role); ?></span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="#dashboard" data-bs-toggle="tab">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tasks" data-bs-toggle="tab">Tasks</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports" data-bs-toggle="tab">Reports</a>
                    </li>
                    <?php if ($can_create_employees): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#employees" data-bs-toggle="tab">Employees</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#messages" data-bs-toggle="tab">Messages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#profile" data-bs-toggle="tab">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="employeeLogout">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar on larger screens -->
            <div class="col-lg-2 d-none d-lg-block">
                <div class="list-group">
                    <a href="#dashboard" data-bs-toggle="tab" class="list-group-item list-group-item-action active">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                    <a href="#tasks" data-bs-toggle="tab" class="list-group-item list-group-item-action">
                        <i class="fas fa-tasks"></i> Tasks
                    </a>
                    <a href="#reports" data-bs-toggle="tab" class="list-group-item list-group-item-action">
                        <i class="fas fa-file-alt"></i> Reports
                    </a>
                    <?php if ($can_create_employees): ?>
                    <a href="#employees" data-bs-toggle="tab" class="list-group-item list-group-item-action">
                        <i class="fas fa-users"></i> Employees
                    </a>
                    <?php endif; ?>
                    <a href="#messages" data-bs-toggle="tab" class="list-group-item list-group-item-action">
                        <i class="fas fa-envelope"></i> Messages
                    </a>
                    <a href="#profile" data-bs-toggle="tab" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-circle"></i> Profile
                    </a>
                    <a href="#" id="employeeLogoutSidebar" class="list-group-item list-group-item-action text-danger">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-10">
                <div class="tab-content">
                    <!-- Dashboard Tab -->
                    <div class="tab-pane fade show active" id="dashboard">
                        <h2 class="mb-4">Dashboard</h2>
                        
                        <div class="row">
                            <div class="col-md-4 mb-4">
                                <div class="card bg-info text-white h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title">Company</h5>
                                                <p class="h4"><?php echo esc_html($employee->company_name); ?></p>
                                            </div>
                                            <i class="fas fa-building fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="card-footer">
                                        <small>Branch: <?php echo esc_html($employee->branch_name); ?></small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <div class="card bg-warning text-white h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title">Pending Tasks</h5>
                                                <h2 class="display-4"><?php echo $pending_tasks; ?></h2>
                                            </div>
                                            <i class="fas fa-tasks fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a href="#tasks" data-bs-toggle="tab" class="text-white">View Tasks</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <div class="card bg-success text-white h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title">Reports Submitted</h5>
                                                <h2 class="display-4"><?php echo $report_count; ?></h2>
                                            </div>
                                            <i class="fas fa-file-alt fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a href="#reports" data-bs-toggle="tab" class="text-white">Submit Report</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Today's Tasks -->
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Today's Tasks</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Task</th>
                                                <th>Assigned By</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="todayTasksTable">
                                            <!-- Will be populated by AJAX -->
                                            <tr><td colspan="4">Loading today's tasks...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Tasks Tab -->
                    <div class="tab-pane fade" id="tasks">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Manage Tasks</h2>
                            <?php if ($can_assign_tasks): ?>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#assignTaskModal">
                                <i class="fas fa-plus"></i> Assign Task
                            </button>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <div class="row">
                                    <div class="col-md-4">
                                        <select id="filterTaskStatus" class="form-select">
                                            <option value="">All Status</option>
                                            <option value="pending">Pending</option>
                                            <option value="completed">Completed</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <input type="date" id="filterTaskDate" class="form-control" placeholder="Filter by date">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Task</th>
                                                <th>Description</th>
                                                <th>Assigned By</th>
                                                <th>Created</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tasksTable">
                                            <!-- Will be populated by AJAX -->
                                            <tr><td colspan="6">Loading tasks...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Reports Tab -->
                    <div class="tab-pane fade" id="reports">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Daily Reports</h2>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#submitReportModal">
                                <i class="fas fa-plus"></i> Submit Report
                            </button>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <select id="filterReportDateRange" class="form-select">
                                                    <option value="daily">Today</option>
                                                    <option value="weekly">This Week</option>
                                                    <option value="monthly">This Month</option>
                                                    <option value="custom">Custom Range</option>
                                                </select>
                                            </div>
                                            <div class="col-md-4 custom-date-range d-none">
                                                <input type="date" id="filterReportStartDate" class="form-control" placeholder="Start Date">
                                            </div>
                                            <div class="col-md-4 custom-date-range d-none">
                                                <input type="date" id="filterReportEndDate" class="form-control" placeholder="End Date">
                                            </div>
                                        </div>
                                    </div>
                                    <div class="card-body">
                                        <div class="table-responsive">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Report</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody id="reportsTable">
                                                    <!-- Will be populated by AJAX -->
                                                    <tr><td colspan="3">Loading reports...</td></tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($can_create_employees): ?>
                    <!-- Employees Tab (only for Managers and Assistant Managers) -->
                    <div class="tab-pane fade" id="employees">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Manage Employees</h2>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSubordinateModal">
                                <i class="fas fa-plus"></i> Add Employee
                            </button>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Photo</th>
                                                <th>Name</th>
                                                <th>Username</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="subordinatesTable">
                                            <!-- Will be populated by AJAX -->
                                            <tr><td colspan="6">Loading employees...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Other tabs (Messages, Profile) follow the same pattern -->
                    <div class="tab-pane fade" id="messages">
                        <h2 class="mb-4">Messages</h2>
                        <!-- Messages content will be loaded here -->
                    </div>
                    
                    <div class="tab-pane fade" id="profile">
                        <h2 class="mb-4">Employee Profile</h2>
                        <!-- Profile content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Submit Report Modal -->
<div class="modal fade" id="submitReportModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Submit Daily Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="submitReportForm">
                    <div class="alert alert-danger d-none" id="submitReportError"></div>
                    <div class="alert alert-success d-none" id="submitReportSuccess"></div>
                    
                    <div class="mb-3">
                        <label for="reportDate" class="form-label">Date</label>
                        <input type="date" class="form-control" id="reportDate" name="report_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="reportContent" class="form-label">Report Content</label>
                        <textarea class="form-control" id="reportContent" name="report_content" rows="5" required></textarea>
                    </div>
                    
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oms_employee_nonce'); ?>">
                    <input type="hidden" name="action" value="oms_employee_submit_report">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="submitReportBtn">Submit Report</button>
            </div>
        </div>
    </div>
</div>

<?php if ($can_assign_tasks): ?>
<!-- Assign Task Modal -->
<div class="modal fade" id="assignTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Assign Task</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="assignTaskForm">
                    <div class="alert alert-danger d-none" id="assignTaskError"></div>
                    
                    <div class="mb-3">
                        <label for="taskTitle" class="form-label">Task Title</label>
                        <input type="text" class="form-control" id="taskTitle" name="title" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="taskDescription" class="form-label">Task Description</label>
                        <textarea class="form-control" id="taskDescription" name="description" rows="3"></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="assignTo" class="form-label">Assign To</label>
                        <select class="form-select" id="assignTo" name="employee_id" required>
                            <option value="">Select Employee</option>
                            <!-- Will be populated by AJAX -->
                        </select>
                    </div>
                    
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oms_employee_nonce'); ?>">
                    <input type="hidden" name="action" value="oms_employee_assign_task">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="assignTaskBtn">Assign Task</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if ($can_create_employees): ?>
<!-- Add Subordinate Modal -->
<div class="modal fade" id="addSubordinateModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addSubordinateForm">
                    <div class="alert alert-danger d-none" id="addSubordinateError"></div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="subordinateName" class="form-label">Employee Name</label>
                            <input type="text" class="form-control" id="subordinateName" name="employee_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="subordinateUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="subordinateUsername" name="username" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="subordinatePassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="subordinatePassword" name="password" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="subordinateImage" class="form-label">Profile Image URL</label>
                            <input type="url" class="form-control" id="subordinateImage" name="profile_image">
                        </div>
                    </div>
                    
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oms_employee_nonce'); ?>">
                    <input type="hidden" name="action" value="oms_employee_create_subordinate">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveSubordinateBtn">Save Employee</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
