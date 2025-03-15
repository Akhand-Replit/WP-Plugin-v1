<?php
// Check if already logged in
$auth = new OMS_Auth();
if ($auth->is_company_logged_in()) {
    $redirect_url = get_permalink(get_page_by_path('company-dashboard'));
    echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
    exit;
} elseif ($auth->is_employee_logged_in()) {
    $redirect_url = get_permalink(get_page_by_path('employee-dashboard'));
    echo '<script>window.location.href = "' . esc_url($redirect_url) . '";</script>';
    exit;
}
?>
<div class="oms-container">
    <div class="oms-login-container mt-5">
        <div class="card shadow">
            <div class="card-header bg-primary text-white">
                <h3 class="text-center mb-0">Organization Management System</h3>
            </div>
            <div class="card-body">
                <ul class="nav nav-tabs" id="loginTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="company-tab" data-bs-toggle="tab" data-bs-target="#company-login" type="button" role="tab" aria-controls="company-login" aria-selected="true">Company Login</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="employee-tab" data-bs-toggle="tab" data-bs-target="#employee-login" type="button" role="tab" aria-controls="employee-login" aria-selected="false">Employee Login</button>
                    </li>
                </ul>
                
                <div class="tab-content mt-3" id="loginTabsContent">
                    <!-- Company Login Form -->
                    <div class="tab-pane fade show active" id="company-login" role="tabpanel" aria-labelledby="company-tab">
                        <form id="companyLoginForm" class="oms-login-form" onsubmit="return false;">
                            <div class="alert alert-danger d-none" id="companyLoginError"></div>
                            
                            <div class="mb-3">
                                <label for="companyUsername" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="companyUsername" name="username" required autocomplete="username">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="companyPassword" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="companyPassword" name="password" required autocomplete="current-password">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1" data-target="companyPassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <input type="hidden" name="user_type" value="company">
                            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oms_login_nonce'); ?>">
                            <input type="hidden" name="action" value="oms_login">
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">Login as Company</button>
                            </div>
                        </form>
                    </div>
                    
                    <!-- Employee Login Form -->
                    <div class="tab-pane fade" id="employee-login" role="tabpanel" aria-labelledby="employee-tab">
                        <form id="employeeLoginForm" class="oms-login-form" onsubmit="return false;">
                            <div class="alert alert-danger d-none" id="employeeLoginError"></div>
                            
                            <div class="mb-3">
                                <label for="employeeUsername" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="employeeUsername" name="username" required autocomplete="username">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="employeePassword" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="employeePassword" name="password" required autocomplete="current-password">
                                    <button class="btn btn-outline-secondary toggle-password" type="button" tabindex="-1" data-target="employeePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <input type="hidden" name="user_type" value="employee">
                            <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oms_login_nonce'); ?>">
                            <input type="hidden" name="action" value="oms_login">
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-success btn-lg">Login as Employee</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-footer text-center text-muted">
                <small>If you don't have login credentials, please contact your administrator.</small>
            </div>
        </div>
    </div>
</div>

<script>
    // Add toggle password functionality
    document.addEventListener('DOMContentLoaded', function() {
        const toggleButtons = document.querySelectorAll('.toggle-password');
        toggleButtons.forEach(function(button) {
            button.addEventListener('click', function() {
                const targetId = this.getAttribute('data-target');
                const passwordInput = document.getElementById(targetId);
                const icon = this.querySelector('i');
                
                if (passwordInput.type === 'password') {
                    passwordInput.type = 'text';
                    icon.classList.remove('fa-eye');
                    icon.classList.add('fa-eye-slash');
                } else {
                    passwordInput.type = 'password';
                    icon.classList.remove('fa-eye-slash');
                    icon.classList.add('fa-eye');
                }
            });
        });
    });
</script>
