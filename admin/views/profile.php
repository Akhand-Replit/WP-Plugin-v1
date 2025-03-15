<div class="wrap oms-admin-wrapper">
    <div class="oms-admin-header">
        <h1 class="oms-admin-title"><?php echo esc_html__('Admin Profile', 'org-management'); ?></h1>
    </div>
    
    <?php
    $profile_name = get_option('oms_admin_profile_name', 'Administrator');
    $username = get_option('oms_admin_username', 'admin');
    $profile_image = get_option('oms_admin_profile_image', '');
    ?>
    
    <div class="oms-admin-card">
        <div class="oms-admin-card-header">
            <?php echo esc_html__('Update Profile', 'org-management'); ?>
        </div>
        <div class="oms-admin-card-body">
            <div id="profileUpdateSuccess" class="oms-admin-message" style="display: none; border-left-color: #46b450;">
                <?php echo esc_html__('Profile updated successfully.', 'org-management'); ?>
            </div>
            
            <div id="profileUpdateError" class="oms-admin-message" style="display: none; border-left-color: #dc3232;">
                <span id="profileUpdateErrorMessage"></span>
            </div>
            
            <form id="adminProfileForm">
                <div class="oms-admin-profile-container">
                    <div class="oms-admin-profile-image-container">
                        <div class="oms-admin-profile-image-wrapper">
                            <?php if (!empty($profile_image)): ?>
                                <img src="<?php echo esc_url($profile_image); ?>" alt="<?php echo esc_attr($profile_name); ?>" id="profileImagePreview">
                            <?php else: ?>
                                <div class="oms-admin-profile-image-placeholder">
                                    <i class="dashicons dashicons-admin-users"></i>
                                </div>
                            <?php endif; ?>
                        </div>
                        <button type="button" class="oms-admin-button" id="changeProfileImageBtn"><?php echo esc_html__('Change Image', 'org-management'); ?></button>
                    </div>
                    
                    <div class="oms-admin-profile-details">
                        <div class="oms-admin-form-group">
                            <label for="profile_name" class="oms-admin-form-label"><?php echo esc_html__('Profile Name', 'org-management'); ?></label>
                            <input type="text" id="profile_name" name="profile_name" class="oms-admin-form-input" value="<?php echo esc_attr($profile_name); ?>" required>
                        </div>
                        
                        <div class="oms-admin-form-group">
                            <label for="admin_username" class="oms-admin-form-label"><?php echo esc_html__('Username', 'org-management'); ?></label>
                            <input type="text" id="admin_username" name="username" class="oms-admin-form-input" value="<?php echo esc_attr($username); ?>" required>
                        </div>
                        
                        <div class="oms-admin-form-group">
                            <label for="admin_profile_image" class="oms-admin-form-label"><?php echo esc_html__('Profile Image URL', 'org-management'); ?></label>
                            <input type="url" id="admin_profile_image" name="profile_image" class="oms-admin-form-input" value="<?php echo esc_attr($profile_image); ?>">
                        </div>
                        
                        <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oms_admin_nonce'); ?>">
                        <input type="hidden" name="action" value="oms_update_admin_profile">
                        
                        <div class="oms-admin-form-group">
                            <button type="submit" class="oms-admin-button" id="saveProfileBtn"><?php echo esc_html__('Save Profile', 'org-management'); ?></button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
