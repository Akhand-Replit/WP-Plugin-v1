/**
 * Organization Management System - Admin JavaScript
 */
jQuery(document).ready(function($) {
    
    /**
     * Toggle status for companies, branches, or employees
     */
    $(document).on('click', '.toggle-company-status', function() {
        const companyId = $(this).data('id');
        const newStatus = $(this).data('status');
        
        if (confirm('Are you sure you want to ' + (newStatus === 'active' ? 'activate' : 'deactivate') + ' this company? This will also ' + (newStatus === 'active' ? 'activate' : 'deactivate') + ' all associated branches and employees.')) {
            $.ajax({
                url: oms_admin.ajax_url,
                type: 'POST',
                data: {
                    action: 'oms_toggle_company_status',
                    nonce: oms_admin.nonce,
                    company_id: companyId,
                    status: newStatus
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data);
                    }
                }
            });
        }
    });
    
    /**
     * Media uploader for images
     */
    $('.select-image').on('click', function(e) {
        e.preventDefault();
        
        const button = $(this);
        const inputField = button.closest('.input-group').find('input[type="text"]');
        const previewContainer = button.closest('.row').find('.image-preview');
        const previewImage = previewContainer.find('img');
        
        const frame = wp.media({
            title: 'Select or Upload Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });
        
        frame.on('select', function() {
            const attachment = frame.state().get('selection').first().toJSON();
            inputField.val(attachment.url).trigger('change');
            
            if (previewImage.length) {
                previewImage.attr('src', attachment.url);
                previewContainer.removeClass('d-none');
            }
        });
        
        frame.open();
    });
    
    /**
     * Show/hide image preview when URL changes
     */
    $('input[name="profile_image"]').on('change input', function() {
        const input = $(this);
        const previewContainer = input.closest('.row').find('.image-preview');
        const previewImage = previewContainer.find('img');
        
        if (input.val().trim()) {
            previewImage.attr('src', input.val());
            previewContainer.removeClass('d-none');
        } else {
            previewContainer.addClass('d-none');
        }
    });
    
    /**
     * Toggle password visibility
     */
    $('.toggle-password').on('click', function() {
        const target = $(this).data('target');
        const input = $('#' + target);
        const icon = $(this).find('i');
        
        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            input.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    /**
     * Format date helper (for JavaScript)
     */
    window.formatDate = function(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        
        // Check if date is valid
        if (isNaN(date.getTime())) return dateString;
        
        return date.toLocaleDateString();
    };
    
    /**
     * Format date and time helper
     */
    window.formatDateTime = function(dateString) {
        if (!dateString) return '';
        
        const date = new Date(dateString);
        
        // Check if date is valid
        if (isNaN(date.getTime())) return dateString;
        
        return date.toLocaleDateString() + ' ' + date.toLocaleTimeString();
    };
    
});
