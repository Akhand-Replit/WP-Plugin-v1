<div class="wrap oms-admin-wrapper">
    <div class="oms-admin-header">
        <h1 class="oms-admin-title"><?php echo esc_html__('Manage Companies', 'org-management'); ?></h1>
        <button type="button" class="page-title-action" id="addCompanyBtn"><?php echo esc_html__('Add New Company', 'org-management'); ?></button>
    </div>
    
    <div class="oms-admin-card">
        <div class="oms-admin-card-header">
            <?php echo esc_html__('Companies List', 'org-management'); ?>
        </div>
        <div class="oms-admin-card-body">
            <table class="oms-admin-table" id="companiesTable">
                <thead>
                    <tr>
                        <th width="50"><?php echo esc_html__('ID', 'org-management'); ?></th>
                        <th width="60"><?php echo esc_html__('Image', 'org-management'); ?></th>
                        <th><?php echo esc_html__('Company Name', 'org-management'); ?></th>
                        <th><?php echo esc_html__('Username', 'org-management'); ?></th>
                        <th><?php echo esc_html__('Status', 'org-management'); ?></th>
                        <th><?php echo esc_html__('Created', 'org-management'); ?></th>
                        <th><?php echo esc_html__('Actions', 'org-management'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td colspan="7"><?php echo esc_html__('Loading companies...', 'org-management'); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    
    <!-- Company Branches Modal -->
    <div id="companyBranchesModal" class="oms-modal">
        <div class="oms-modal-content">
            <div class="oms-modal-header">
                <span class="oms-modal-close">&times;</span>
                <h2 id="branchesModalTitle"><?php echo esc_html__('Company Branches', 'org-management'); ?></h2>
            </div>
            <div class="oms-modal-body">
                <div id="branchesContainer">
                    <table class="oms-admin-table" id="branchesTable">
                        <thead>
                            <tr>
                                <th width="50"><?php echo esc_html__('ID', 'org-management'); ?></th>
                                <th><?php echo esc_html__('Branch Name', 'org-management'); ?></th>
                                <th><?php echo esc_html__('Status', 'org-management'); ?></th>
                                <th><?php echo esc_html__('Created', 'org-management'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td colspan="4"><?php echo esc_html__('Loading branches...', 'org-management'); ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Add/Edit Company Modal -->
    <div id="companyFormModal" class="oms-modal">
        <div class="oms-modal-content">
            <div class="oms-modal-header">
                <span class="oms-modal-close">&times;</span>
                <h2 id="companyFormTitle"><?php echo esc_html__('Add New Company', 'org-management'); ?></h2>
            </div>
            <div class="oms-modal-body">
                <form id="companyForm">
                    <div class="oms-admin-form-group">
                        <label for="company_name" class="oms-admin-form-label"><?php echo esc_html__('Company Name', 'org-management'); ?></label>
                        <input type="text" id="company_name" name="company_name" class="oms-admin-form-input" required>
                    </div>
                    
                    <div class="oms-admin-form-group">
                        <label for="username" class="oms-admin-form-label"><?php echo esc_html__('Username', 'org-management'); ?></label>
                        <input type="text" id="username" name="username" class="oms-admin-form-input" required>
                    </div>
                    
                    <div class="oms-admin-form-group">
                        <label for="password" class="oms-admin-form-label"><?php echo esc_html__('Password', 'org-management'); ?></label>
                        <input type="password" id="password" name="password" class="oms-admin-form-input" required>
                    </div>
                    
                    <div class="oms-admin-form-group">
                        <label for="profile_image" class="oms-admin-form-label"><?php echo esc_html__('Profile Image URL', 'org-management'); ?></label>
                        <input type="url" id="profile_image" name="profile_image" class="oms-admin-form-input">
                        <button type="button" class="oms-media-upload-button" id="uploadImageBtn"><?php echo esc_html__('Upload Image', 'org-management'); ?></button>
                    </div>
                    
                    <input type="hidden" id="company_id" name="company_id" value="0">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oms_admin_nonce'); ?>">
                    <input type="hidden" name="action" value="oms_create_company">
                    
                    <div class="oms-admin-form-group" style="margin-top: 20px;">
                        <button type="submit" class="oms-admin-button" id="saveCompanyBtn"><?php echo esc_html__('Save Company', 'org-management'); ?></button>
                        <button type="button" class="oms-admin-button oms-admin-button-secondary oms-modal-close"><?php echo esc_html__('Cancel', 'org-management'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Send Message Modal -->
    <div id="messageModal" class="oms-modal">
        <div class="oms-modal-content">
            <div class="oms-modal-header">
                <span class="oms-modal-close">&times;</span>
                <h2 id="messageModalTitle"><?php echo esc_html__('Send Message to Company', 'org-management'); ?></h2>
            </div>
            <div class="oms-modal-body">
                <form id="messageForm">
                    <div class="oms-admin-form-group">
                        <label for="message" class="oms-admin-form-label"><?php echo esc_html__('Message', 'org-management'); ?></label>
                        <textarea id="message" name="message" class="oms-admin-form-textarea" required></textarea>
                    </div>
                    
                    <div class="oms-admin-form-group">
                        <label for="message_attachment" class="oms-admin-form-label"><?php echo esc_html__('Attachment URL', 'org-management'); ?></label>
                        <input type="url" id="message_attachment" name="attachment" class="oms-admin-form-input">
                        <button type="button" class="oms-media-upload-button" id="uploadAttachmentBtn"><?php echo esc_html__('Upload Attachment', 'org-management'); ?></button>
                    </div>
                    
                    <input type="hidden" id="message_company_id" name="company_id" value="0">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oms_admin_nonce'); ?>">
                    <input type="hidden" name="action" value="oms_send_message_to_company">
                    
                    <div class="oms-admin-form-group" style="margin-top: 20px;">
                        <button type="submit" class="oms-admin-button" id="sendMessageBtn"><?php echo esc_html__('Send Message', 'org-management'); ?></button>
                        <button type="button" class="oms-admin-button oms-admin-button-secondary oms-modal-close"><?php echo esc_html__('Cancel', 'org-management'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
