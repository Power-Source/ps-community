/* PS Community Groups JavaScript */

jQuery(document).ready(function($) {
    
    // Join Group
    $(document).on('click', '.cpc-group-join-btn', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var groupId = button.data('group-id');
        
        if (!groupId) {
            alert('Gruppe nicht gefunden.');
            return;
        }

        button.prop('disabled', true).text('Wird verarbeitet...');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_join_group',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId
            },
            success: function(response) {
                if (response.success) {
                    if (response.data.status === 'pending') {
                        button.text('Ausstehend').removeClass('cpc-group-join-btn').addClass('cpc-group-pending-btn');
                        alert(response.data.message);
                    } else {
                        button.text('Gruppe verlassen').removeClass('cpc-group-join-btn').addClass('cpc-group-leave-btn');
                    }
                    
                    // Reload page to update member count
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data.message);
                    button.prop('disabled', false).text('Beitreten');
                }
            },
            error: function() {
                alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
                button.prop('disabled', false).text('Beitreten');
            }
        });
    });

    // Leave Group
    $(document).on('click', '.cpc-group-leave-btn', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var groupId = button.data('group-id');
        
        if (!groupId) {
            alert('Gruppe nicht gefunden.');
            return;
        }

        if (!confirm('Möchtest du diese Gruppe wirklich verlassen?')) {
            return;
        }

        button.prop('disabled', true).text('Wird verarbeitet...');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_leave_group',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId
            },
            success: function(response) {
                if (response.success) {
                    button.text('Beitreten').removeClass('cpc-group-leave-btn').addClass('cpc-group-join-btn');
                    
                    // Reload page to update member count
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data.message);
                    button.prop('disabled', false).text('Gruppe verlassen');
                }
            },
            error: function() {
                alert('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
                button.prop('disabled', false).text('Gruppe verlassen');
            }
        });
    });

    // Create Group Form
    $('#cpc-create-group-form').on('submit', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var formData = new FormData(this);
        formData.append('action', 'cpc_create_group');
        
        var submitBtn = form.find('.cpc-group-create-submit');
        var messageDiv = form.siblings('.cpc-group-create-message');
        
        submitBtn.prop('disabled', true).text('Wird erstellt...');
        messageDiv.removeClass('success error').empty();
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    messageDiv.addClass('success').text(response.data.message);
                    form[0].reset();
                    
                    // Redirect if specified
                    var redirectTo = form.find('input[name="redirect_to"]').val();
                    if (redirectTo) {
                        setTimeout(function() {
                            window.location.href = redirectTo;
                        }, 1500);
                    } else if (response.data.group_url) {
                        setTimeout(function() {
                            window.location.href = response.data.group_url;
                        }, 1500);
                    }
                    
                    submitBtn.prop('disabled', false).text('Gruppe erstellen');
                } else {
                    messageDiv.addClass('error').text(response.data.message);
                    submitBtn.prop('disabled', false).text('Gruppe erstellen');
                }
            },
            error: function() {
                messageDiv.addClass('error').text('Ein Fehler ist aufgetreten. Bitte versuche es erneut.');
                submitBtn.prop('disabled', false).text('Gruppe erstellen');
            }
        });
    });

    // Update Member Role (for admins)
    $(document).on('click', '.cpc-update-member-role', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var groupId = button.data('group-id');
        var memberId = button.data('member-id');
        var newRole = button.data('role');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_update_member_role',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId,
                member_id: memberId,
                role: newRole
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Ein Fehler ist aufgetreten.');
            }
        });
    });

    // Remove Member (for admins)
    $(document).on('click', '.cpc-remove-member', function(e) {
        e.preventDefault();
        
        if (!confirm('Möchtest du dieses Mitglied wirklich entfernen?')) {
            return;
        }
        
        var button = $(this);
        var groupId = button.data('group-id');
        var memberId = button.data('member-id');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_remove_member',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId,
                member_id: memberId
            },
            success: function(response) {
                if (response.success) {
                    button.closest('.cpc-member-card').fadeOut();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Ein Fehler ist aufgetreten.');
            }
        });
    });

    // Approve Pending Member (for admins)
    $(document).on('click', '.cpc-approve-member', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var groupId = button.data('group-id');
        var memberId = button.data('member-id');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_approve_member',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId,
                member_id: memberId
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Ein Fehler ist aufgetreten.');
            }
        });
    });

    // Image preview for group avatar upload
    $('#group_avatar').on('change', function(e) {
        var file = e.target.files[0];
        if (file) {
            var reader = new FileReader();
            reader.onload = function(e) {
                var preview = $('#avatar-preview');
                if (preview.length === 0) {
                    preview = $('<img id="avatar-preview" style="max-width: 200px; margin-top: 10px; border-radius: 8px;">');
                    $('#group_avatar').after(preview);
                }
                preview.attr('src', e.target.result);
            };
            reader.readAsDataURL(file);
        }
    });

    // Generate nonce for AJAX requests if not already set
    if (typeof cpc_groups_ajax.nonce === 'undefined') {
        // Try to get from other CPC ajax objects as fallback
        if (typeof cpc_ajax !== 'undefined' && cpc_ajax.nonce) {
            cpc_groups_ajax.nonce = cpc_ajax.nonce;
        }
    }

});
