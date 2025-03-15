jQuery(document).ready(function($) {
    // Login form submission
    $('.oms-login-form').on('submit', function(e) {
        e.preventDefault();
        
        const form = $(this);
        const formId = form.attr('id');
        const errorDiv = formId === 'companyLoginForm' ? $('#companyLoginError') : $('#employeeLoginError');
        
        errorDiv.addClass('d-none');
        
        $.ajax({
            url: oms_public.ajax_url,
            type: 'POST',
            data: form.serialize(),
            beforeSend: function() {
                form.find('button[type="submit"]').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Logging in...');
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                } else {
                    errorDiv.removeClass('d-none').text(response.data);
                    form.find('button[type="submit"]').prop('disabled', false).text(formId === 'companyLoginForm' ? 'Login as Company' : 'Login as Employee');
                }
            },
            error: function() {
                errorDiv.removeClass('d-none').text('An error occurred. Please try again.');
                form.find('button[type="submit"]').prop('disabled', false).text(formId === 'companyLoginForm' ? 'Login as Company' : 'Login as Employee');
            }
        });
    });
    
    // Logout functionality
    $('#companyLogout, #companyLogoutSidebar, #employeeLogout, #employeeLogoutSidebar').on('click', function(e) {
        e.preventDefault();
        
        $.ajax({
            url: oms_public.ajax_url,
            type: 'POST',
            data: {
                action: 'oms_logout',
                nonce: oms_public.login_nonce
            },
            success: function(response) {
                if (response.success) {
                    window.location.href = response.data.redirect;
                }
            }
        });
    });
    
    // Company Dashboard functionality
    if ($('.oms-company-dashboard').length) {
        // Load branches
        function loadBranches() {
            $.ajax({
                url: oms_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'oms_company_get_branches',
                    nonce: oms_public.company_nonce
                },
                success: function(response) {
                    if (response.success) {
                        let html = '';
                        
                        if (response.data.branches.length === 0) {
                            html = '<tr><td colspan="4" class="text-center">No branches found</td></tr>';
                        } else {
                            $.each(response.data.branches, function(i, branch) {
                                html += `
                                    <tr>
                                        <td>${branch.branch_name}</td>
                                        <td>
                                            <span class="badge bg-${branch.status === 'active' ? 'success' : 'danger'}">
                                                ${branch.status}
                                            </span>
                                        </td>
                                        <td id="branch-employee-count-${branch.id}">Loading...</td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary view-branch" data-id="${branch.id}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-${branch.status === 'active' ? 'warning' : 'success'} toggle-branch-status" 
                                                    data-id="${branch.id}" 
                                                    data-status="${branch.status === 'active' ? 'inactive' : 'active'}">
                                                    <i class="fas fa-${branch.status === 'active' ? 'ban' : 'check'}"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-info assign-task-to-branch" data-id="${branch.id}">
                                                    <i class="fas fa-tasks"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                                
                                // Get employee count for this branch
                                getEmployeeCountForBranch(branch.id);
                            });
                        }
                        
                        $('#branchesTable').html(html);
                    }
                }
            });
        }
        
        // Get employee count for a branch
        function getEmployeeCountForBranch(branchId) {
            $.ajax({
                url: oms_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'oms_company_get_employees',
                    nonce: oms_public.company_nonce,
                    branch_id: branchId
                },
                success: function(response) {
                    if (response.success) {
                        $(`#branch-employee-count-${branchId}`).text(response.data.employees.length);
                    }
                }
            });
        }
        
        // Load employees
        function loadEmployees(branchId = 0, role = '', status = '') {
            $.ajax({
                url: oms_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'oms_company_get_employees',
                    nonce: oms_public.company_nonce,
                    branch_id: branchId
                },
                success: function(response) {
                    if (response.success) {
                        let html = '';
                        let filteredEmployees = response.data.employees;
                        
                        // Apply filters
                        if (role) {
                            filteredEmployees = filteredEmployees.filter(employee => employee.role === role);
                        }
                        
                        if (status) {
                            filteredEmployees = filteredEmployees.filter(employee => employee.status === status);
                        }
                        
                        if (filteredEmployees.length === 0) {
                            html = '<tr><td colspan="7" class="text-center">No employees found</td></tr>';
                        } else {
                            $.each(filteredEmployees, function(i, employee) {
                                const profileImage = employee.profile_image ? 
                                    `<img src="${employee.profile_image}" alt="${employee.employee_name}" class="employee-thumbnail">` : 
                                    `<div class="employee-initials">${employee.employee_name.charAt(0)}</div>`;
                                
                                html += `
                                    <tr>
                                        <td>${profileImage}</td>
                                        <td>${employee.employee_name}</td>
                                        <td>${employee.username}</td>
                                        <td>${employee.branch_name}</td>
                                        <td>
                                            <span class="badge bg-${
                                                employee.role === 'Manager' ? 'danger' : 
                                                employee.role === 'Asst. Manager' ? 'warning' : 'info'
                                            }">
                                                ${employee.role}
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-${employee.status === 'active' ? 'success' : 'danger'}">
                                                ${employee.status}
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-primary view-employee" data-id="${employee.id}">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-info edit-employee" data-id="${employee.id}">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-${employee.status === 'active' ? 'warning' : 'success'} toggle-employee-status" 
                                                    data-id="${employee.id}" 
                                                    data-status="${employee.status === 'active' ? 'inactive' : 'active'}">
                                                    <i class="fas fa-${employee.status === 'active' ? 'ban' : 'check'}"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-primary assign-task-to-employee" data-id="${employee.id}">
                                                    <i class="fas fa-tasks"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                            });
                        }
                        
                        $('#employeesTable').html(html);
                    }
                }
            });
        }
        
        // Add branch
        $('#saveBranchBtn').on('click', function() {
            const form = $('#addBranchForm');
            const errorDiv = $('#addBranchError');
            
            errorDiv.addClass('d-none');
            
            $.ajax({
                url: oms_public.ajax_url,
                type: 'POST',
                data: form.serialize(),
                beforeSend: function() {
                    $('#saveBranchBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        $('#addBranchModal').modal('hide');
                        form.trigger('reset');
                        loadBranches();
                    } else {
                        errorDiv.removeClass('d-none').text(response.data);
                    }
                    $('#saveBranchBtn').prop('disabled', false).text('Save Branch');
                },
                error: function() {
                    errorDiv.removeClass('d-none').text('An error occurred. Please try again.');
                    $('#saveBranchBtn').prop('disabled', false).text('Save Branch');
                }
            });
        });
        
        // Add employee
        $('#saveEmployeeBtn').on('click', function() {
            const form = $('#addEmployeeForm');
            const errorDiv = $('#addEmployeeError');
            
            errorDiv.addClass('d-none');
            
            $.ajax({
                url: oms_public.ajax_url,
                type: 'POST',
                data: form.serialize(),
                beforeSend: function() {
                    $('#saveEmployeeBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        $('#addEmployeeModal').modal('hide');
                        form.trigger('reset');
                        loadEmployees();
                    } else {
                        errorDiv.removeClass('d-none').text(response.data);
                    }
                    $('#saveEmployeeBtn').prop('disabled', false).text('Save Employee');
                },
                error: function() {
                    errorDiv.removeClass('d-none').text('An error occurred. Please try again.');
                    $('#saveEmployeeBtn').prop('disabled', false).text('Save Employee');
                }
            });
        });
        
        // Toggle branch status
        $(document).on('click', '.toggle-branch-status', function() {
            const branchId = $(this).data('id');
            const newStatus = $(this).data('status');
            
            $.ajax({
                url: oms_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'oms_company_toggle_branch_status',
                    nonce: oms_public.company_nonce,
                    branch_id: branchId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        loadBranches();
                    } else {
                        alert(response.data);
                    }
                }
            });
        });
        
        // Toggle employee status
        $(document).on('click', '.toggle-employee-status', function() {
            const employeeId = $(this).data('id');
            const newStatus = $(this).data('status');
            
            $.ajax({
                url: oms_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'oms_company_toggle_employee_status',
                    nonce: oms_public.company_nonce,
                    employee_id: employeeId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        loadEmployees($('#filterBranch').val(), $('#filterRole').val(), $('#filterStatus').val());
                    } else {
                        alert(response.data);
                    }
                }
            });
        });
        
        // Employee filters
        $('#filterBranch, #filterRole, #filterStatus').on('change', function() {
            loadEmployees($('#filterBranch').val(), $('#filterRole').val(), $('#filterStatus').val());
        });
        
        // Initialize
        loadBranches();
        loadEmployees();
    }
    
    // Employee Dashboard functionality
    if ($('.oms-employee-dashboard').length) {
        // Load tasks
        function loadEmployeeTasks() {
            $.ajax({
                url: oms_public.ajax_url,
                type: 'POST',
                data: {
                    action: 'oms_employee_get_tasks',
                    nonce: oms_public.employee_nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Render tasks UI
                    }
                }
            });
        }
        
        // Submit report
        $('#submitReportBtn').on('click', function() {
            const form = $('#submitReportForm');
            const errorDiv = $('#submitReportError');
            
            errorDiv.addClass('d-none');
            
            $.ajax({
                url: oms_public.ajax_url,
                type: 'POST',
                data: form.serialize(),
                beforeSend: function() {
                    $('#submitReportBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Submitting...');
                },
                success: function(response) {
                    if (response.success) {
                        $('#submitReportForm').trigger('reset');
                        $('#submitReportSuccess').removeClass('d-none').text(response.data.message);
                        setTimeout(function() {
                            $('#submitReportSuccess').addClass('d-none');
                        }, 3000);
                    } else {
                        errorDiv.removeClass('d-none').text(response.data);
                    }
                    $('#submitReportBtn').prop('disabled', false).text('Submit Report');
                },
                error: function() {
                    errorDiv.removeClass('d-none').text('An error occurred. Please try again.');
                    $('#submitReportBtn').prop('disabled', false).text('Submit Report');
                }
            });
        });
    }
});
