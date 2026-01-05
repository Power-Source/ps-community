/* PS Community Groups JavaScript */

jQuery(document).ready(function($) {
    
    // Post Group Activity
    $(document).on('submit', '.cpc-group-post-activity-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var groupId = form.data('group-id');
        var content = form.find('textarea[name="activity_content"]').val();
        var submitBtn = form.find('button[type="submit"]');
        
        if (!groupId) {
            cpc_show_notification('Fehler: Gruppe nicht gefunden.', 'error');
            return;
        }
        
        if (!content || content.trim() === '') {
            cpc_show_notification('Bitte schreibe etwas, bevor du postest.', 'error');
            return;
        }
        
        submitBtn.prop('disabled', true).text('Wird gepostet...');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_post_group_activity',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId,
                activity_content: content
            },
            success: function(response) {
                if (response.success) {
                    cpc_show_notification(response.data.message, 'success');
                    form.find('textarea').val('');
                    submitBtn.prop('disabled', false).text('Posten');
                    
                    // Reload the activity tab to show new post
                    setTimeout(function() {
                        if (typeof cpc_load_group_tab === 'function') {
                            cpc_load_group_tab(groupId, 'overview');
                        } else {
                            location.reload();
                        }
                    }, 500);
                } else {
                    cpc_show_notification(response.data.message, 'error');
                    submitBtn.prop('disabled', false).text('Posten');
                }
            },
            error: function() {
                cpc_show_notification('Fehler beim Posten der Aktivität', 'error');
                submitBtn.prop('disabled', false).text('Posten');
            }
        });
    });
    
    // Show notification helper function
    function cpc_show_notification(message, type) {
        type = type || 'info';
        
        var notification = $('<div class="cpc-notification cpc-notification-' + type + '">' + message + '</div>');
        
        // Add to page if not exists
        if ($('.cpc-notifications-container').length === 0) {
            $('body').prepend('<div class="cpc-notifications-container"></div>');
        }
        
        $('.cpc-notifications-container').append(notification);
        
        notification.fadeIn().delay(3000).fadeOut(function() {
            $(this).remove();
        });
    }
    
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
        formData.append('nonce', form.find('input[name="cpc_create_group_nonce"]').val());
        
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

    // Group Tabs Navigation
    $(document).on('click', '.cpc-group-tab-link', function(e) {
        e.preventDefault();
        
        var link = $(this);
        var tab = link.data('tab');
        var tabs_container = link.closest('.cpc-group-tabs-nav').next('.cpc-group-tabs-content');
        
        // Update active tab styling
        link.closest('.cpc-group-tabs-list').find('.cpc-group-tab-item').removeClass('active');
        link.closest('.cpc-group-tab-item').addClass('active');
        
        // Update URL with tab parameter
        var url = new URL(window.location);
        url.searchParams.set('tab', tab);
        window.history.replaceState({}, '', url);
        
        // Load tab content via AJAX
        var groupId = tabs_container.data('group-id');
        if (groupId) {
            $.ajax({
                url: cpc_groups_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cpc_load_group_tab',
                    nonce: cpc_groups_ajax.nonce,
                    group_id: groupId,
                    tab: tab
                },
                success: function(response) {
                    if (response.success) {
                        tabs_container.html(response.data.html);
                    }
                },
                error: function() {
                    console.log('Error loading tab');
                }
            });
        }
    });
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

    // Approve membership request
    $(document).on('click', '.cpc-approve-membership', function(e) {
        e.preventDefault();
        
        var btn = $(this);
        var requestId = btn.data('request-id');
        var groupId = btn.data('group-id');
        
        if (!confirm('Möchtest du diese Beitrittanfrage wirklich annehmen?')) {
            return;
        }
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_approve_membership',
                nonce: cpc_groups_ajax.nonce,
                request_id: requestId,
                group_id: groupId
            },
            success: function(response) {
                if (response.success) {
                    btn.closest('.cpc-membership-request').fadeOut(function() {
                        $(this).remove();
                        // Check if there are any requests left
                        if ($('.cpc-membership-request').length === 0) {
                            $('.cpc-membership-requests').html('<p>Keine ausstehenden Beitrittanfragen.</p>');
                        }
                    });
                } else {
                    alert(response.data.message);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('Fehler beim Verarbeiten der Anfrage');
                btn.prop('disabled', false);
            }
        });
    });

    // Reject membership request
    $(document).on('click', '.cpc-reject-membership', function(e) {
        e.preventDefault();
        
        var btn = $(this);
        var requestId = btn.data('request-id');
        var groupId = btn.data('group-id');
        
        if (!confirm('Möchtest du diese Beitrittanfrage wirklich ablehnen?')) {
            return;
        }
        
        btn.prop('disabled', true);
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_reject_membership',
                nonce: cpc_groups_ajax.nonce,
                request_id: requestId,
                group_id: groupId
            },
            success: function(response) {
                if (response.success) {
                    btn.closest('.cpc-membership-request').fadeOut(function() {
                        $(this).remove();
                        // Check if there are any requests left
                        if ($('.cpc-membership-request').length === 0) {
                            $('.cpc-membership-requests').html('<p>Keine ausstehenden Beitrittanfragen.</p>');
                        }
                    });
                } else {
                    alert(response.data.message);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                alert('Fehler beim Verarbeiten der Anfrage');
                btn.prop('disabled', false);
            }
        });
    });

    // Toggle forum visibility field based on checkbox
    $(document).on('change', '#enable_forum', function() {
        if ($(this).is(':checked')) {
            $('.cpc-forum-visibility-field').slideDown();
        } else {
            $('.cpc-forum-visibility-field').slideUp();
        }
    });

    // Submit group forum settings
    $(document).on('submit', '.cpc-group-forum-settings-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var groupId = form.data('group-id');
        var enableForum = form.find('#enable_forum').is(':checked');
        var forumVisibility = form.find('input[name="forum_visibility"]:checked').val();
        var submitBtn = form.find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).text('Speichern...');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_toggle_group_forum',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId,
                enable_forum: enableForum,
                forum_visibility: forumVisibility
            },
            success: function(response) {
                submitBtn.prop('disabled', false).text('Forum-Einstellungen speichern');
                
                if (response.success) {
                    alert(response.data.message);
                    if (response.data.reload) {
                        location.reload();
                    }
                } else {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert('Fehler beim Speichern der Forum-Einstellungen');
                submitBtn.prop('disabled', false).text('Forum-Einstellungen speichern');
            }
        });
    });

    // Generate nonce for AJAX requests if not already set
    if (typeof cpc_groups_ajax.nonce === 'undefined') {
        // Try to get from other CPC ajax objects as fallback
        if (typeof cpc_ajax !== 'undefined' && cpc_ajax.nonce) {
            cpc_groups_ajax.nonce = cpc_ajax.nonce;
        }
    }

});
