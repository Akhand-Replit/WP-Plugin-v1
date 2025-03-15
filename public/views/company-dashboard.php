<?php
// Get company details
$auth = new OMS_Auth();
$company = $auth->get_current_company();

// Get branches
global $wpdb;
$table_branches = $wpdb->prefix . 'oms_branches';
$branches = $wpdb->get_results($wpdb->prepare(
    "SELECT id, branch_name, status FROM $table_branches WHERE company_id = %d ORDER BY branch_name",
    $company->id
));

// Get counts
$table_employees = $wpdb->prefix . 'oms_employees';
$employee_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_employees WHERE company_id = %d",
    $company->id
));

$active_employee_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_employees WHERE company_id = %d AND status = 'active'",
    $company->id
));

$table_tasks = $wpdb->prefix . 'oms_tasks';
$pending_tasks = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_tasks WHERE assigned_by = %d AND status = 'pending'",
    $company->id
));
?>

<div class="oms-company-dashboard">
    <!-- Top Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">
                <?php if (!empty($company->profile_image)): ?>
                    <img src="<?php echo esc_url($company->profile_image); ?>" alt="<?php echo esc_attr($company->company_name); ?>" class="profile-image">
                <?php endif; ?>
                <?php echo esc_html($company->company_name); ?>
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
                        <a class="nav-link" href="#branches" data-bs-toggle="tab">Branches</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#employees" data-bs-toggle="tab">Employees</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#tasks" data-bs-toggle="tab">Tasks</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#reports" data-bs-toggle="tab">Reports</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#messages" data-bs-toggle="tab">Messages</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#profile" data-bs-toggle="tab">Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#" id="companyLogout">Logout</a>
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
                    <a href="#branches" data-bs-toggle="tab" class="list-group-item list-group-item-action">
                        <i class="fas fa-building"></i> Branches
                    </a>
                    <a href="#employees" data-bs-toggle="tab" class="list-group-item list-group-item-action">
                        <i class="fas fa-users"></i> Employees
                    </a>
                    <a href="#tasks" data-bs-toggle="tab" class="list-group-item list-group-item-action">
                        <i class="fas fa-tasks"></i> Tasks
                    </a>
                    <a href="#reports" data-bs-toggle="tab" class="list-group-item list-group-item-action">
                        <i class="fas fa-chart-bar"></i> Reports
                    </a>
                    <a href="#messages" data-bs-toggle="tab" class="list-group-item list-group-item-action">
                        <i class="fas fa-envelope"></i> Messages
                    </a>
                    <a href="#profile" data-bs-toggle="tab" class="list-group-item list-group-item-action">
                        <i class="fas fa-user-circle"></i> Profile
                    </a>
                    <a href="#" id="companyLogoutSidebar" class="list-group-item list-group-item-action text-danger">
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
                                <div class="card bg-primary text-white h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title">Total Branches</h5>
                                                <h2 class="display-4"><?php echo count($branches); ?></h2>
                                            </div>
                                            <i class="fas fa-building fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a href="#branches" data-bs-toggle="tab" class="text-white">View Details</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4 mb-4">
                                <div class="card bg-success text-white h-100">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <h5 class="card-title">Total Employees</h5>
                                                <h2 class="display-4"><?php echo $employee_count; ?></h2>
                                            </div>
                                            <i class="fas fa-users fa-3x"></i>
                                        </div>
                                    </div>
                                    <div class="card-footer d-flex align-items-center justify-content-between">
                                        <a href="#employees" data-bs-toggle="tab" class="text-white">View Details</a>
                                        <div class="small text-white">
                                            <span class="badge bg-light text-success"><?php echo $active_employee_count; ?> Active</span>
                                        </div>
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
                                        <a href="#tasks" data-bs-toggle="tab" class="text-white">View Details</a>
                                        <i class="fas fa-angle-right"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Recent Activity -->
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Recent Activity</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Activity</th>
                                                <th>Details</th>
                                            </tr>
                                        </thead>
                                        <tbody id="recentActivityTable">
                                            <!-- Will be populated by AJAX -->
                                            <tr><td colspan="3">Loading recent activity...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Branches Tab -->
                    <div class="tab-pane fade" id="branches">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Manage Branches</h2>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addBranchModal">
                                <i class="fas fa-plus"></i> Add Branch
                            </button>
                        </div>
                        
                        <div class="card">
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Branch Name</th>
                                                <th>Status</th>
                                                <th>Employees</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="branchesTable">
                                            <!-- Will be populated by AJAX -->
                                            <tr><td colspan="4">Loading branches...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Employees Tab -->
                    <div class="tab-pane fade" id="employees">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h2>Manage Employees</h2>
                            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                                <i class="fas fa-plus"></i> Add Employee
                            </button>
                        </div>
                        
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <div class="row">
                                    <div class="col-md-4">
                                        <select id="filterBranch" class="form-select">
                                            <option value="">All Branches</option>
                                            <?php foreach ($branches as $branch): ?>
                                                <option value="<?php echo esc_attr($branch->id); ?>"><?php echo esc_html($branch->branch_name); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select id="filterRole" class="form-select">
                                            <option value="">All Roles</option>
                                            <option value="Manager">Managers</option>
                                            <option value="Asst. Manager">Assistant Managers</option>
                                            <option value="General Employee">General Employees</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <select id="filterStatus" class="form-select">
                                            <option value="">All Status</option>
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>Photo</th>
                                                <th>Name</th>
                                                <th>Username</th>
                                                <th>Branch</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="employeesTable">
                                            <!-- Will be populated by AJAX -->
                                            <tr><td colspan="7">Loading employees...</td></tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Other tabs (Tasks, Reports, Messages, Profile) follow the same pattern -->
                    <div class="tab-pane fade" id="tasks">
                        <h2 class="mb-4">Manage Tasks</h2>
                        <!-- Task management content will be loaded here -->
                    </div>
                    
                    <div class="tab-pane fade" id="reports">
                        <h2 class="mb-4">Reports</h2>
                        <!-- Reports content will be loaded here -->
                    </div>
                    
                    <div class="tab-pane fade" id="messages">
                        <h2 class="mb-4">Messages</h2>
                        <!-- Messages content will be loaded here -->
                    </div>
                    
                    <div class="tab-pane fade" id="profile">
                        <h2 class="mb-4">Company Profile</h2>
                        <!-- Profile content will be loaded here -->
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Branch Modal -->
<div class="modal fade" id="addBranchModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Branch</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addBranchForm">
                    <div class="alert alert-danger d-none" id="addBranchError"></div>
                    
                    <div class="mb-3">
                        <label for="branchName" class="form-label">Branch Name</label>
                        <input type="text" class="form-control" id="branchName" name="branch_name" required>
                    </div>
                    
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oms_company_nonce'); ?>">
                    <input type="hidden" name="action" value="oms_company_create_branch">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveBranchBtn">Save Branch</button>
            </div>
        </div>
    </div>
</div>

<!-- Add Employee Modal -->
<div class="modal fade" id="addEmployeeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Employee</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addEmployeeForm">
                    <div class="alert alert-danger d-none" id="addEmployeeError"></div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employeeName" class="form-label">Employee Name</label>
                            <input type="text" class="form-control" id="employeeName" name="employee_name" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="employeeUsername" class="form-label">Username</label>
                            <input type="text" class="form-control" id="employeeUsername" name="username" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employeePassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="employeePassword" name="password" required>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="employeeRole" class="form-label">Role</label>
                            <select class="form-select" id="employeeRole" name="role" required>
                                <option value="">Select Role</option>
                                <option value="Manager">Manager</option>
                                <option value="Asst. Manager">Assistant Manager</option>
                                <option value="General Employee">General Employee</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employeeBranch" class="form-label">Branch</label>
                            <select class="form-select" id="employeeBranch" name="branch_id" required>
                                <option value="">Select Branch</option>
                                <?php foreach ($branches as $branch): ?>
                                    <?php if ($branch->status === 'active'): ?>
                                        <option value="<?php echo esc_attr($branch->id); ?>"><?php echo esc_html($branch->branch_name); ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="employeeImage" class="form-label">Profile Image URL</label>
                            <input type="url" class="form-control" id="employeeImage" name="profile_image">
                        </div>
                    </div>
                    
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oms_company_nonce'); ?>">
                    <input type="hidden" name="action" value="oms_company_create_employee">
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveEmployeeBtn">Save Employee</button>
            </div>
        </div>
    </div>
</div>

<!-- Other modals for editing employees, assigning tasks, etc. would follow -->
