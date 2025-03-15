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
                        <form id="companyLoginForm" class="oms-login-form">
                            <div class="alert alert-danger d-none" id="companyLoginError"></div>
                            
                            <div class="mb-3">
                                <label for="companyUsername" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="companyUsername" name="username" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="companyPassword" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="companyPassword" name="password" required>
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
                        <form id="employeeLoginForm" class="oms-login-form">
                            <div class="alert alert-danger d-none" id="employeeLoginError"></div>
                            
                            <div class="mb-3">
                                <label for="employeeUsername" class="form-label">Username</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" id="employeeUsername" name="username" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="employeePassword" class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" id="employeePassword" name="password" required>
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
