<div class="wrap oms-admin-wrapper">
    <div class="oms-admin-header">
        <h1 class="oms-admin-title"><?php echo esc_html__('Messages', 'org-management'); ?></h1>
    </div>
    
    <?php
    global $wpdb;
    $table_messages = $wpdb->prefix . 'oms_messages';
    $table_companies = $wpdb->prefix . 'oms_companies';
    ?>
    
    <div class="oms-admin-card">
        <div class="oms-admin-card-header">
            <div class="oms-admin-tabs" id="messageTabs">
                <button class="oms-admin-tab active" data-tab="sentMessages"><?php echo esc_html__('Sent Messages', 'org-management'); ?></button>
                <button class="oms-admin-tab" data-tab="receivedMessages"><?php echo esc_html__('Received Messages', 'org-management'); ?></button>
            </div>
        </div>
        <div class="oms-admin-card-body">
            <!-- Sent Messages Tab -->
            <div class="oms-admin-tab-content active" id="sentMessages">
                <div class="oms-admin-filter">
                    <select id="companySentFilter" class="oms-admin-form-select">
                        <option value=""><?php echo esc_html__('All Companies', 'org-management'); ?></option>
                        <?php
                        $companies = $wpdb->get_results("SELECT id, company_name FROM $table_companies ORDER BY company_name");
                        foreach ($companies as $company) {
                            echo '<option value="' . esc_attr($company->id) . '">' . esc_html($company->company_name) . '</option>';
                        }
                        ?>
                    </select>
                    <button id="refreshSentBtn" class="oms-admin-button"><?php echo esc_html__('Refresh', 'org-management'); ?></button>
                </div>
                
                <table class="oms-admin-table" id="sentMessagesTable">
                    <thead>
                        <tr>
                            <th width="50"><?php echo esc_html__('ID', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Recipient', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Message', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Attachment', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Status', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Sent Date', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Actions', 'org-management'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7"><?php echo esc_html__('Loading messages...', 'org-management'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <!-- Received Messages Tab -->
            <div class="oms-admin-tab-content" id="receivedMessages">
                <div class="oms-admin-filter">
                    <select id="companyReceivedFilter" class="oms-admin-form-select">
                        <option value=""><?php echo esc_html__('All Companies', 'org-management'); ?></option>
                        <?php
                        foreach ($companies as $company) {
                            echo '<option value="' . esc_attr($company->id) . '">' . esc_html($company->company_name) . '</option>';
                        }
                        ?>
                    </select>
                    <button id="refreshReceivedBtn" class="oms-admin-button"><?php echo esc_html__('Refresh', 'org-management'); ?></button>
                </div>
                
                <table class="oms-admin-table" id="receivedMessagesTable">
                    <thead>
                        <tr>
                            <th width="50"><?php echo esc_html__('ID', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Sender', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Message', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Attachment', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Status', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Received Date', 'org-management'); ?></th>
                            <th><?php echo esc_html__('Actions', 'org-management'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="7"><?php echo esc_html__('Loading messages...', 'org-management'); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Message Details Modal -->
    <div id="messageDetailsModal" class="oms-modal">
        <div class="oms-modal-content">
            <div class="oms-modal-header">
                <span class="oms-modal-close">&times;</span>
                <h2 id="messageDetailsTitle"><?php echo esc_html__('Message Details', 'org-management'); ?></h2>
            </div>
            <div class="oms-modal-body">
                <div id="messageDetailsContent">
                    <div class="oms-admin-message-details">
                        <p><strong><?php echo esc_html__('From:', 'org-management'); ?></strong> <span id="messageDetailsSender"></span></p>
                        <p><strong><?php echo esc_html__('To:', 'org-management'); ?></strong> <span id="messageDetailsRecipient"></span></p>
                        <p><strong><?php echo esc_html__('Date:', 'org-management'); ?></strong> <span id="messageDetailsDate"></span></p>
                        <p><strong><?php echo esc_html__('Status:', 'org-management'); ?></strong> <span id="messageDetailsStatus"></span></p>
                        <hr>
                        <div class="oms-admin-message-body" id="messageDetailsBody"></div>
                        
                        <div id="messageDetailsAttachment" style="margin-top: 15px;">
                            <p><strong><?php echo esc_html__('Attachment:', 'org-management'); ?></strong> <a href="#" id="messageDetailsAttachmentLink" target="_blank"></a></p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="oms-modal-footer">
                <button type="button" class="oms-admin-button oms-admin-button-secondary oms-modal-close"><?php echo esc_html__('Close', 'org-management'); ?></button>
                <button type="button" class="oms-admin-button" id="replyMessageBtn"><?php echo esc_html__('Reply', 'org-management'); ?></button>
            </div>
        </div>
    </div>
    
    <!-- Reply Message Modal -->
    <div id="replyMessageModal" class="oms-modal">
        <div class="oms-modal-content">
            <div class="oms-modal-header">
                <span class="oms-modal-close">&times;</span>
                <h2 id="replyMessageTitle"><?php echo esc_html__('Reply to Message', 'org-management'); ?></h2>
            </div>
            <div class="oms-modal-body">
                <form id="replyMessageForm">
                    <div class="oms-admin-form-group">
                        <label for="reply_message" class="oms-admin-form-label"><?php echo esc_html__('Reply Message', 'org-management'); ?></label>
                        <textarea id="reply_message" name="message" class="oms-admin-form-textarea" required></textarea>
                    </div>
                    
                    <div class="oms-admin-form-group">
                        <label for="reply_attachment" class="oms-admin-form-label"><?php echo esc_html__('Attachment URL', 'org-management'); ?></label>
                        <input type="url" id="reply_attachment" name="attachment" class="oms-admin-form-input">
                        <button type="button" class="oms-media-upload-button" id="uploadReplyAttachmentBtn"><?php echo esc_html__('Upload Attachment', 'org-management'); ?></button>
                    </div>
                    
                    <input type="hidden" id="reply_company_id" name="company_id" value="0">
                    <input type="hidden" name="nonce" value="<?php echo wp_create_nonce('oms_admin_nonce'); ?>">
                    <input type="hidden" name="action" value="oms_send_message_to_company">
                    
                    <div class="oms-admin-form-group" style="margin-top: 20px;">
                        <button type="submit" class="oms-admin-button" id="sendReplyBtn"><?php echo esc_html__('Send Reply', 'org-management'); ?></button>
                        <button type="button" class="oms-admin-button oms-admin-button-secondary oms-modal-close"><?php echo esc_html__('Cancel', 'org-management'); ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
