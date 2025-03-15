jQuery(document).ready(function($) {
    // Common variables
    const adminNonce = oms_admin.nonce;
    
    // Modal functions
    function openModal(modalId) {
        $(`#${modalId}`).css('display', 'block');
    }
    
    function closeModal(modalId) {
        $(`#${modalId}`).css('display', 'none');
    }
    
    // Close modal when clicking the X or outside the modal
    $('.oms-modal-close').on('click', function() {
        $(this).closest('.oms-modal').css('display', 'none');
    });
    
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('oms-modal')) {
            $('.oms-modal').css('display', 'none');
        }
    });
    
    // Tab switching
    $('.oms-admin-tab').on('click', function() {
        const tabId = $(this).data('tab');
        $('.oms-admin-tab').removeClass('active');
        $(this).addClass('active');
        $('.oms-admin-tab-content').removeClass('active');
        $(`#${tabId}`).addClass('active');
    });
    
    // Media uploader
    $(document).on('click', '#uploadImageBtn, #changeProfileImageBtn', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const targetField = button.attr('id') === 'changeProfileImageBtn' ? '#admin_profile_image' : '#profile_image';
        
        const mediaUploader = wp.media({
            title: 'Select or Upload Profile Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $(targetField).val(attachment.url);
            
            // If it's the profile image, update the preview
            if (targetField === '#admin_profile_image') {
                if ($('#profileImagePreview').length) {
                    $('#profileImagePreview').attr('src', attachment.url);
                } else {
                    $('.oms-admin-profile-image-placeholder').html(`<img src="${attachment.url}" alt="" id="profileImagePreview">`);
                }
            }
        });
        
        mediaUploader.open();
    });
    
    $(document).on('click', '#uploadAttachmentBtn, #uploadReplyAttachmentBtn', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const targetField = button.attr('id') === 'uploadReplyAttachmentBtn' ? '#reply_attachment' : '#message_attachment';
        
        const mediaUploader = wp.media({
            title: 'Select or Upload Attachment',
            button: {
                text: 'Use this file'
            },
            multiple: false
        });
        
        mediaUploader.on('select', function() {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $(targetField).val(attachment.url);
        });
        
        mediaUploader.open();
    });
    
    // -----------------------------------------
    // Companies Page Functionality
    // -----------------------------------------
    if ($('#companiesTable').length) {
        // Load companies
        function loadCompanies() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'oms_get_companies',
                    nonce: adminNonce
                },
                success: function(response) {
                    if (response.success) {
                        const companies = response.data.companies;
                        let html = '';
                        
                        if (companies.length === 0) {
                            html = `<tr><td colspan="7">${oms_admin_i18n.no_companies}</td></tr>`;
                        } else {
                            $.each(companies, function(i, company) {
                                const profileImg = company.profile_image 
                                    ? `<img src="${company.profile_image}" alt="${company.company_name}" class="oms-admin-profile-image">` 
                                    : `<div class="oms-admin-profile-image-placeholder">C</div>`;
                                
                                html += `
                                    <tr>
                                        <td>${company.id}</td>
                                        <td>${profileImg}</td>
                                        <td>${company.company_name}</td>
                                        <td>${company.username}</td>
                                        <td><span class="oms-status-${company.status}">${company.status}</span></td>
                                        <td>${formatDate(company.created_at)}</td>
                                        <td>
                                            <button type="button" class="oms-admin-button view-branches-btn" data-id="${company.id}" data-name="${company.company_name}">
                                                <span class="dashicons dashicons-networking"></span>
                                            </button>
                                            <button type="button" class="oms-admin-button send-message-btn" data-id="${company.id}" data-name="${company.company_name}">
                                                <span class="dashicons dashicons-email"></span>
                                            </button>
                                            <button type="button" class="oms-admin-button toggle-status-btn" data-id="${company.id}" data-status="${company.status === 'active' ? 'inactive' : 'active'}">
                                                <span class="dashicons dashicons-${company.status === 'active' ? 'no' : 'yes'}"></span>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                        }
                        
                        $('#companiesTable tbody').html(html);
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while fetching companies.');
                }
            });
        }
        
        // Load company branches
        function loadBranches(companyId, companyName) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'oms_get_branches',
                    nonce: adminNonce,
                    company_id: companyId
                },
                success: function(response) {
                    if (response.success) {
                        const branches = response.data.branches;
                        let html = '';
                        
                        $('#branchesModalTitle').text(`${companyName} - Branches`);
                        
                        if (branches.length === 0) {
                            html = `<tr><td colspan="4">${oms_admin_i18n.no_branches}</td></tr>`;
                        } else {
                            $.each(branches, function(i, branch) {
                                html += `
                                    <tr>
                                        <td>${branch.id}</td>
                                        <td>${branch.branch_name}</td>
                                        <td><span class="oms-status-${branch.status}">${branch.status}</span></td>
                                        <td>${formatDate(branch.created_at)}</td>
                                    </tr>
                                `;
                            });
                        }
                        
                        $('#branchesTable tbody').html(html);
                        openModal('companyBranchesModal');
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while fetching branches.');
                }
            });
        }
        
        // Toggle company status
        function toggleCompanyStatus(companyId, newStatus) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'oms_toggle_company_status',
                    nonce: adminNonce,
                    company_id: companyId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        loadCompanies();
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while updating company status.');
                }
            });
        }
        
        // Create/Edit company
        $('#companyForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    $('#saveCompanyBtn').prop('disabled', true).text('Saving...');
                },
                success: function(response) {
                    if (response.success) {
                        closeModal('companyFormModal');
                        $('#companyForm')[0].reset();
                        loadCompanies();
                    } else {
                        alert(response.data);
                    }
                    $('#saveCompanyBtn').prop('disabled', false).text('Save Company');
                },
                error: function() {
                    alert('An error occurred while saving the company.');
                    $('#saveCompanyBtn').prop('disabled', false).text('Save Company');
                }
            });
        });
        
        // Send message to company
        $('#messageForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    $('#sendMessageBtn').prop('disabled', true).text('Sending...');
                },
                success: function(response) {
                    if (response.success) {
                        closeModal('messageModal');
                        $('#messageForm')[0].reset();
                        alert('Message sent successfully.');
                    } else {
                        alert(response.data);
                    }
                    $('#sendMessageBtn').prop('disabled', false).text('Send Message');
                },
                error: function() {
                    alert('An error occurred while sending the message.');
                    $('#sendMessageBtn').prop('disabled', false).text('Send Message');
                }
            });
        });
        
        // Event listeners
        $(document).on('click', '#addCompanyBtn', function() {
            $('#companyFormTitle').text('Add New Company');
            $('#company_id').val('0');
            $('#companyForm')[0].reset();
            $('#companyForm [name="action"]').val('oms_create_company');
            openModal('companyFormModal');
        });
        
        $(document).on('click', '.view-branches-btn', function() {
            const companyId = $(this).data('id');
            const companyName = $(this).data('name');
            loadBranches(companyId, companyName);
        });
        
        $(document).on('click', '.send-message-btn', function() {
            const companyId = $(this).data('id');
            const companyName = $(this).data('name');
            
            $('#messageModalTitle').text(`Send Message to ${companyName}`);
            $('#message_company_id').val(companyId);
            $('#messageForm')[0].reset();
            openModal('messageModal');
        });
        
        $(document).on('click', '.toggle-status-btn', function() {
            const companyId = $(this).data('id');
            const newStatus = $(this).data('status');
            
            if (confirm(`Are you sure you want to ${newStatus === 'active' ? 'activate' : 'deactivate'} this company?`)) {
                toggleCompanyStatus(companyId, newStatus);
            }
        });
        
        // Initialize
        loadCompanies();
    }
    
    // -----------------------------------------
    // Messages Page Functionality
    // -----------------------------------------
    if ($('#sentMessagesTable').length) {
        // Load sent messages
        function loadSentMessages(companyId = '') {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'oms_get_sent_messages',
                    nonce: adminNonce,
                    company_id: companyId
                },
                success: function(response) {
                    if (response.success) {
                        const messages = response.data.messages;
                        let html = '';
                        
                        if (messages.length === 0) {
                            html = `<tr><td colspan="7">${oms_admin_i18n.no_messages}</td></tr>`;
                        } else {
                            $.each(messages, function(i, message) {
                                const attachmentHtml = message.attachment 
                                    ? `<a href="${message.attachment}" target="_blank"><span class="dashicons dashicons-paperclip"></span></a>` 
                                    : '';
                                
                                html += `
                                    <tr>
                                        <td>${message.id}</td>
                                        <td>${message.company_name}</td>
                                        <td>${truncateText(message.message, 50)}</td>
                                        <td>${attachmentHtml}</td>
                                        <td><span class="oms-message-status-${message.status}">${message.status}</span></td>
                                        <td>${formatDate(message.created_at)}</td>
                                        <td>
                                            <button type="button" class="oms-admin-button view-message-btn" data-id="${message.id}">
                                                <span class="dashicons dashicons-visibility"></span>
                                            </button>
                                            <button type="button" class="oms-admin-button delete-message-btn" data-id="${message.id}">
                                                <span class="dashicons dashicons-trash"></span>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                        }
                        
                        $('#sentMessagesTable tbody').html(html);
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while fetching messages.');
                }
            });
        }
        
        // Load received messages
        function loadReceivedMessages(companyId = '') {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'oms_get_received_messages',
                    nonce: adminNonce,
                    company_id: companyId
                },
                success: function(response) {
                    if (response.success) {
                        const messages = response.data.messages;
                        let html = '';
                        
                        if (messages.length === 0) {
                            html = `<tr><td colspan="7">${oms_admin_i18n.no_messages}</td></tr>`;
                        } else {
                            $.each(messages, function(i, message) {
                                const attachmentHtml = message.attachment 
                                    ? `<a href="${message.attachment}" target="_blank"><span class="dashicons dashicons-paperclip"></span></a>` 
                                    : '';
                                
                                html += `
                                    <tr>
                                        <td>${message.id}</td>
                                        <td>${message.company_name}</td>
                                        <td>${truncateText(message.message, 50)}</td>
                                        <td>${attachmentHtml}</td>
                                        <td><span class="oms-message-status-${message.status}">${message.status}</span></td>
                                        <td>${formatDate(message.created_at)}</td>
                                        <td>
                                            <button type="button" class="oms-admin-button view-message-btn" data-id="${message.id}">
                                                <span class="dashicons dashicons-visibility"></span>
                                            </button>
                                            <button type="button" class="oms-admin-button reply-message-btn" data-id="${message.id}" data-company="${message.company_id}" data-name="${message.company_name}">
                                                <span class="dashicons dashicons-redo"></span>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                        }
                        
                        $('#receivedMessagesTable tbody').html(html);
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while fetching messages.');
                }
            });
        }
        
        // Get message details
        function getMessageDetails(messageId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'oms_get_message_details',
                    nonce: adminNonce,
                    message_id: messageId
                },
                success: function(response) {
                    if (response.success) {
                        const message = response.data.message;
                        
                        $('#messageDetailsTitle').text('Message Details');
                        $('#messageDetailsSender').text(message.sender_type === 'admin' ? 'You (Admin)' : message.company_name);
                        $('#messageDetailsRecipient').text(message.receiver_type === 'admin' ? 'You (Admin)' : message.company_name);
                        $('#messageDetailsDate').text(formatDate(message.created_at));
                        $('#messageDetailsStatus').text(message.status);
                        $('#messageDetailsBody').html(message.message.replace(/\n/g, '<br>'));
                        
                        if (message.attachment) {
                            $('#messageDetailsAttachment').show();
                            $('#messageDetailsAttachmentLink').attr('href', message.attachment).text(getFilenameFromUrl(message.attachment));
                        } else {
                            $('#messageDetailsAttachment').hide();
                        }
                        
                        // Show reply button only for received messages
                        if (message.sender_type === 'company') {
                            $('#replyMessageBtn').show().data('company', message.sender_id).data('name', message.company_name);
                        } else {
                            $('#replyMessageBtn').hide();
                        }
                        
                        openModal('messageDetailsModal');
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while fetching message details.');
                }
            });
        }
        
        // Delete message
        function deleteMessage(messageId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'oms_delete_message',
                    nonce: adminNonce,
                    message_id: messageId
                },
                success: function(response) {
                    if (response.success) {
                        loadSentMessages($('#companySentFilter').val());
                    } else {
                        alert(response.data);
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the message.');
                }
            });
        }
        
        // Event listeners
        $('#refreshSentBtn').on('click', function() {
            loadSentMessages($('#companySentFilter').val());
        });
        
        $('#refreshReceivedBtn').on('click', function() {
            loadReceivedMessages($('#companyReceivedFilter').val());
        });
        
        $('#companySentFilter').on('change', function() {
            loadSentMessages($(this).val());
        });
        
        $('#companyReceivedFilter').on('change', function() {
            loadReceivedMessages($(this).val());
        });
        
        $(document).on('click', '.view-message-btn', function() {
            const messageId = $(this).data('id');
            getMessageDetails(messageId);
        });
        
        $(document).on('click', '.delete-message-btn', function() {
            const messageId = $(this).data('id');
            
            if (confirm('Are you sure you want to delete this message?')) {
                deleteMessage(messageId);
            }
        });
        
        $(document).on('click', '.reply-message-btn, #replyMessageBtn', function() {
            const companyId = $(this).data('company');
            const companyName = $(this).data('name');
            
            $('#replyMessageTitle').text(`Reply to ${companyName}`);
            $('#reply_company_id').val(companyId);
            $('#replyMessageForm')[0].reset();
            
            closeModal('messageDetailsModal');
            openModal('replyMessageModal');
        });
        
        $('#replyMessageForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    $('#sendReplyBtn').prop('disabled', true).text('Sending...');
                },
                success: function(response) {
                    if (response.success) {
                        closeModal('replyMessageModal');
                        $('#replyMessageForm')[0].reset();
                        alert('Reply sent successfully.');
                    } else {
                        alert(response.data);
                    }
                    $('#sendReplyBtn').prop('disabled', false).text('Send Reply');
                },
                error: function() {
                    alert('An error occurred while sending the reply.');
                    $('#sendReplyBtn').prop('disabled', false).text('Send Reply');
                }
            });
        });
        
        // Initialize
        loadSentMessages();
        
        // Initial hiding of the received messages tab
        $('#receivedMessages').hide();
        
        // Show received messages when tab is clicked
        $('[data-tab="receivedMessages"]').on('click', function() {
            loadReceivedMessages();
        });
    }
    
    // -----------------------------------------
    // Profile Page Functionality
    // -----------------------------------------
    if ($('#adminProfileForm').length) {
        $('#adminProfileForm').on('submit', function(e) {
            e.preventDefault();
            
            const formData = $(this).serialize();
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: formData,
                beforeSend: function() {
                    $('#saveProfileBtn').prop('disabled', true).text('Saving...');
                    $('#profileUpdateSuccess, #profileUpdateError').hide();
                },
                success: function(response) {
                    if (response.success) {
                        $('#profileUpdateSuccess').fadeIn();
                        setTimeout(function() {
                            $('#profileUpdateSuccess').fadeOut();
                        }, 3000);
                    } else {
                        $('#profileUpdateErrorMessage').text(response.data);
                        $('#profileUpdateError').fadeIn();
                    }
                    $('#saveProfileBtn').prop('disabled', false).text('Save Profile');
                },
                error: function() {
                    $('#profileUpdateErrorMessage').text('An error occurred while saving your profile.');
                    $('#profileUpdateError').fadeIn();
                    $('#saveProfileBtn').prop('disabled', false).text('Save Profile');
                }
            });
        });
    }
    
    // -----------------------------------------
    // Helper functions
    // -----------------------------------------
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    }
    
    function truncateText(text, maxLength) {
        if (text.length <= maxLength) return text;
        return text.substr(0, maxLength) + '...';
    }
    
    function getFilenameFromUrl(url) {
        return url.split('/').pop();
    }
});
