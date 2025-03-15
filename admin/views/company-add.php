<div class="wrap oms-admin-wrapper">
    <div class="oms-admin-header">
        <h1 class="oms-admin-title"><?php echo esc_html__('Add New Company', 'org-management'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=oms-companies'); ?>" class="page-title-action">
            <i class="fas fa-arrow-left"></i> <?php echo esc_html__('Back to Companies', 'org-management'); ?>
        </a>
    </div>
    
    <div class="row mt-4">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><?php echo esc_html__('Company Information', 'org-management'); ?></h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger d-none" id="errorAlert"></div>
                    <div class="alert alert-success d-none" id="successAlert"></div>
                    
                    <form id="addCompanyForm">
                        <div class="row mb-3">
                            <label for="companyName" class="col-sm-3 col-form-label"><?php echo esc_html__('Company Name', 'org-management'); ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="companyName" name="company_name" required>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="username" class="col-sm-3 col-form-label"><?php echo esc_html__('Username', 'org-management'); ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="username" name="username" required>
                                <div class="form-text"><?php echo esc_html__('The company will use this username to log in.', 'org-management'); ?></div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="password" class="col-sm-3 col-form-label"><?php echo esc_html__('Password', 'org-management'); ?> <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button" data-target="password">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                                <div class="form-text"><?php echo esc_html__('Minimum 8 characters recommended.', 'org-management'); ?></div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="profileImage" class="col-sm-3 col-form-label"><?php echo esc_html__('Profile Image', 'org-management'); ?></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="text" class="form-control" id="profileImage" name="profile_image">
                                    <button class="btn btn-outline-secondary select-image" type="button">
                                        <i class="fas fa-image"></i> <?php echo esc_html__('Select Image', 'org-management'); ?>
                                    </button>
                                </div>
                                <div class="form-text"><?php echo esc_html__('Enter a URL or select an image.', 'org-management'); ?></div>
                                <div class="image-preview mt-2 d-none">
                                    <img src="" id="imagePreview" class="img-thumbnail" style="max-height: 150px;">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary" id="submitBtn">
                                    <i class="fas fa-save"></i> <?php echo esc_html__('Create Company', 'org-management'); ?>
                                </button>
                                <a href="<?php echo admin_url('admin.php?page=oms-companies'); ?>" class="btn btn-secondary">
                                    <i class="fas fa-times"></i> <?php echo esc_html__('Cancel', 'org-management'); ?>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><?php echo esc_html__('Information', 'org-management'); ?></h5>
                </div>
                <div class="card-body">
                    <p><i class="fas fa-info-circle text-info"></i> <?php echo esc_html__('Creating a company will automatically generate a Main Branch for it.', 'org-management'); ?></p>
                    <p><i class="fas fa-info-circle text-info"></i> <?php echo esc_html__('Company managers can create additional branches and employees.', 'org-management'); ?></p>
                    <p><i class="fas fa-info-circle text-info"></i> <?php echo esc_html__('Password is securely stored using WordPress password hashing.', 'org-management'); ?></p>
                    <p><i class="fas fa-exclamation-triangle text-warning"></i> <?php echo esc_html__('Make sure to securely share login credentials with the company.', 'org-management'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Handle form submission
    $('#addCompanyForm').on('submit', function(e) {
        e.preventDefault();
        
        // Reset alerts
        $('#errorAlert, #successAlert').addClass('d-none');
        
        // Validate
        const companyName = $('#companyName').val().trim();
        const username = $('#username').val().trim();
        const password = $('#password').val().trim();
        
        if (!companyName || !username || !password) {
            $('#errorAlert').removeClass('d-none').text('Please fill in all required fields.');
            return;
        }
        
        // Submit form
        const formData = $(this).serialize();
        
        $('#submitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Creating...');
        
        $.ajax({
            url: oms_admin.ajax_url,
            type: 'POST',
            data: {
                action: 'oms_create_company',
                nonce: oms_admin.nonce,
                company_name: companyName,
                username: username,
                password: password,
                profile_image: $('#profileImage').val()
            },
            success: function(response) {
                if (response.success) {
                    $('#successAlert').removeClass('d-none').text('Company created successfully!');
                    
                    // Reset form
                    $('#addCompanyForm')[0].reset();
                    $('.image-preview').addClass('d-none');
                    
                    // Redirect after delay
                    setTimeout(function() {
                        window.location.href = '<?php echo admin_url('admin.php?page=oms-companies&action=view&id='); ?>' + response.data.company_id;
                    }, 1500);
                } else {
                    $('#errorAlert').removeClass('d-none').text(response.data);
                    $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Create Company');
                }
            },
            error: function() {
                $('#errorAlert').removeClass('d-none').text('An error occurred. Please try again.');
                $('#submitBtn').prop('disabled', false).html('<i class="fas fa-save"></i> Create Company');
            }
        });
    });
    
    // Toggle password visibility
    $('.toggle-password').on('click', function() {
        const targetId = $(this).data('target');
        const passwordInput = $('#' + targetId);
        const icon = $(this).find('i');
        
        if (passwordInput.attr('type') === 'password') {
            passwordInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            passwordInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Select image using WordPress media library
    $('.select-image').on('click', function() {
        const frame = wp.media({
            title: 'Select or Upload Profile Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            $('#profileImage').val(attachment.url);
            $('#imagePreview').attr('src', attachment.url);
            $('.image-preview').removeClass('d-none');
        });
        
        frame.open();
    });
    
    // Preview image when URL is entered manually
    $('#profileImage').on('change input', function() {
        const imageUrl = $(this).val().trim();
        
        if (imageUrl) {
            $('#imagePreview').attr('src', imageUrl);
            $('.image-preview').removeClass('d-none');
        } else {
            $('.image-preview').addClass('d-none');
        }
    });
});
</script>
