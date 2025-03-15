<?php
// Get the company details
$company_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

global $wpdb;
$table_companies = $wpdb->prefix . 'oms_companies';
$table_branches = $wpdb->prefix . 'oms_branches';
$table_employees = $wpdb->prefix . 'oms_employees';

$company = $wpdb->get_row($wpdb->prepare(
    "SELECT * FROM $table_companies WHERE id = %d",
    $company_id
));

if (!$company) {
    wp_die('Company not found');
}

// Get branch count
$branch_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_branches WHERE company_id = %d",
    $company_id
));

// Get employee count
$employee_count = $wpdb->get_var($wpdb->prepare(
    "SELECT COUNT(*) FROM $table_employees WHERE company_id = %d",
    $company_id
));
?>

<div class="wrap oms-admin-wrapper">
    <div class="oms-admin-header">
        <h1 class="oms-admin-title"><?php echo esc_html__('Company Details', 'org-management'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=oms-companies'); ?>" class="page-title-action"><?php echo esc_html__('Back to Companies', 'org-management'); ?></a>
    </div>
    
    <div class="oms-admin-card">
        <div class="oms-admin-card-header">
            <?php echo esc_html($company->company_name); ?>
        </div>
        <div class="oms-admin-card-body">
            <div class="oms-admin-profile-container">
                <div class="oms-admin-profile-image-container">
                    <div class="oms-admin-profile-image-wrapper">
                        <?php if (!empty($company->profile_image)): ?>
                            <img src="<?php echo esc_url($company->profile_image); ?>" alt="<?php echo esc_attr($company->company_name); ?>">
                        <?php else: ?>
                            <div class="oms-admin-profile-image-placeholder">
                                <i class="dashicons dashicons-building"></i>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="oms-admin-profile-details">
                    <table class="oms-admin-table">
                        <tr>
                            <th><?php echo esc_html__('Company Name', 'org-management'); ?></th>
                            <td><?php echo esc_html($company->company_name); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Username', 'org-management'); ?></th>
                            <td><?php echo esc_html($company->username); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Status', 'org-management'); ?></th>
                            <td><span class="oms-status-<?php echo esc_attr($company->status); ?>"><?php echo esc_html($company->status); ?></span></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Created', 'org-management'); ?></th>
                            <td><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($company->created_at))); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Total Branches', 'org-management'); ?></th>
                            <td><?php echo esc_html($branch_count); ?></td>
                        </tr>
                        <tr>
                            <th><?php echo esc_html__('Total Employees', 'org-management'); ?></th>
                            <td><?php echo esc_html($employee_count); ?></td>
                        </tr>
                    </table>
                    
                    <div class="oms-admin-form-group" style="margin-top: 20px;">
                        <button type="button" class="oms-admin-button view-branches-btn" data-id="<?php echo esc_attr($company->id); ?>" data-name="<?php echo esc_attr($company->company_name); ?>">
                            <?php echo esc_html__('View Branches', 'org-management'); ?>
                        </button>
                        <button type="button" class="oms-admin-button send-message-btn" data-id="<?php echo esc_attr($company->id); ?>" data-name="<?php echo esc_attr($company->company_name); ?>">
                            <?php echo esc_html__('Send Message', 'org-management'); ?>
                        </button>
                        <button type="button" class="oms-admin-button toggle-status-btn" data-id="<?php echo esc_attr($company->id); ?>" data-status="<?php echo $company->status === 'active' ? 'inactive' : 'active'; ?>">
                            <?php echo $company->status === 'active' ? esc_html__('Deactivate', 'org-management') : esc_html__('Activate', 'org-management'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Recent Messages -->
    <div class="oms-admin-card" style="margin-top: 20px;">
        <div class="oms-admin-card-header">
            <?php echo esc_html__('Recent Messages', 'org-management'); ?>
        </div>
        <div class="oms-admin-card-body">
            <div id="companyMessagesContainer">
                <p><?php echo esc_html__('Loading messages...', 'org-management'); ?></p>
            </div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Load company messages
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
            action: 'oms_get_company_messages',
            nonce: oms_admin.nonce,
            company_id: <?php echo esc_js($company_id); ?>
        },
        success: function(response) {
            if (response.success) {
                let html = '';
                const messages = response.data.messages;
                
                if (messages.length === 0) {
                    html = '<p><?php echo esc_js(__('No messages found', 'org-management')); ?></p>';
                } else {
                    html = '<div class="oms-admin-message-list">';
                    $.each(messages, function(i, message) {
                        const isFromAdmin = (message.sender_type === 'admin');
                        const messageDirection = isFromAdmin ? 'sent to' : 'received from';
                        
                        html += `
                            <div class="oms-admin-message">
                                <div class="oms-admin-message-sender">
                                    ${isFromAdmin ? 'You' : '<?php echo esc_js($company->company_name); ?>'}
                                    ${messageDirection}
                                    ${isFromAdmin ? '<?php echo esc_js($company->company_name); ?>' : 'you'}
                                    <span class="oms-admin-message-time">
                                        ${formatDate(message.created_at)}
                                    </span>
                                </div>
                                <div class="oms-admin-message-content">
                                    ${message.message}
                                </div>
                            </div>
                        `;
                    });
                    html += '</div>';
                }
                
                $('#companyMessagesContainer').html(html);
            } else {
                $('#companyMessagesContainer').html('<p><?php echo esc_js(__('Error loading messages', 'org-management')); ?></p>');
            }
        },
        error: function() {
            $('#companyMessagesContainer').html('<p><?php echo esc_js(__('Error loading messages', 'org-management')); ?></p>');
        }
    });
    
    // Format date helper function
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
    
    // Set up event handlers for the buttons
    $('.view-branches-btn').on('click', function() {
        const companyId = $(this).data('id');
        const companyName = $(this).data('name');
        loadBranches(companyId, companyName);
    });
    
    $('.send-message-btn').on('click', function() {
        const companyId = $(this).data('id');
        const companyName = $(this).data('name');
        
        $('#messageModalTitle').text(`Send Message to ${companyName}`);
        $('#message_company_id').val(companyId);
        $('#messageForm')[0].reset();
        openModal('messageModal');
    });
    
    $('.toggle-status-btn').on('click', function() {
        const companyId = $(this).data('id');
        const newStatus = $(this).data('status');
        
        if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this company?`)) {
            toggleCompanyStatus(companyId, newStatus);
        }
    });
});
</script>
