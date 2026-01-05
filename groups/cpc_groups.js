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
    
    // Toggle activity reply form
    $(document).on('click', '.cpc-reply-toggle', function(e) {
        e.preventDefault();
        
        var toggle = $(this);
        var postId = toggle.data('post-id');
        var form = toggle.closest('.cpc-activity-replies').find('.cpc-activity-reply-form');
        
        if (form.is(':visible')) {
            form.slideUp();
            toggle.text($(this).data('original-text') || 'Antwort hinzufügen');
        } else {
            form.slideDown();
            toggle.text('Abbrechen');
            toggle.data('original-text', 'Antwort hinzufügen');
            form.find('textarea').focus();
        }
    });
    
    // Post activity reply
    $(document).on('click', '.cpc-post-reply', function(e) {
        e.preventDefault();
        
        var btn = $(this);
        var postId = btn.data('post-id');
        var textarea = btn.closest('.cpc-activity-reply-form').find('.cpc-reply-content');
        var content = textarea.val();
        var repliesContainer = btn.closest('.cpc-activity-replies');
        
        if (!content || content.trim() === '') {
            cpc_show_notification('Bitte schreibe etwas als Antwort', 'error');
            return;
        }
        
        btn.prop('disabled', true).text('Wird gesendet...');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_post_activity_reply',
                nonce: cpc_groups_ajax.nonce,
                post_id: postId,
                reply_content: content
            },
            success: function(response) {
                if (response.success) {
                    cpc_show_notification('Antwort erfolgreich gepostet!', 'success');
                    textarea.val('');
                    btn.prop('disabled', false).text('Antworten');
                    
                    // Add reply to DOM immediately
                    if (response.data.reply_html) {
                        // Find or create the replies container
                        var existingReplies = repliesContainer.find('.cpc-activity-reply');
                        if (existingReplies.length > 0) {
                            // Add after last reply
                            existingReplies.last().after(response.data.reply_html);
                        } else {
                            // Add before the form
                            repliesContainer.find('.cpc-activity-reply-form').before(response.data.reply_html);
                        }
                        
                        // Animate the new reply
                        repliesContainer.find('.cpc-activity-reply').last().hide().fadeIn();
                    }
                    
                    // Hide the form
                    repliesContainer.find('.cpc-activity-reply-form').slideUp();
                    repliesContainer.find('.cpc-reply-toggle').text('Antwort hinzufügen');
                } else {
                    cpc_show_notification(response.data.message, 'error');
                    btn.prop('disabled', false).text('Antworten');
                }
            },
            error: function() {
                cpc_show_notification('Fehler beim Posten der Antwort', 'error');
                btn.prop('disabled', false).text('Antworten');
            }
        });
    });
    
    // Save group permissions
    $(document).on('submit', '.cpc-group-permissions-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var groupId = form.find('input[name="group_id"]').val() || form.data('group-id');
        
        submitBtn.prop('disabled', true).text('Speichern...');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_save_group_permissions',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId,
                forum_post: form.find('select[name="forum_post"]').val(),
                activity_edit_all: form.find('select[name="activity_edit_all"]').val(),
                activity_delete_all: form.find('select[name="activity_delete_all"]').val()
            },
            success: function(response) {
                if (response.success) {
                    cpc_show_notification(response.data.message, 'success');
                } else {
                    cpc_show_notification(response.data.message, 'error');
                }
                submitBtn.prop('disabled', false).text('Speichern');
            },
            error: function() {
                cpc_show_notification('Fehler beim Speichern', 'error');
                submitBtn.prop('disabled', false).text('Speichern');
            }
        });
    });
    
    // Edit activity post
    $(document).on('click', '.cpc-edit-activity', function(e) {
        e.preventDefault();
        
        var link = $(this);
        var postId = link.data('post-id');
        var activityPost = link.closest('.cpc-group-activity-post');
        var textDiv = activityPost.find('.cpc-activity-text');
        var originalContent = textDiv.html().trim();
        
        // Create edit form
        var editForm = $('<div class="cpc-edit-activity-form">' +
            '<textarea class="cpc-edit-activity-textarea">' + $('<div>').html(originalContent).text() + '</textarea>' +
            '<div class="cpc-edit-actions">' +
                '<button type="button" class="cpc-save-activity-edit" data-post-id="' + postId + '">Speichern</button>' +
                '<button type="button" class="cpc-cancel-activity-edit">Abbrechen</button>' +
            '</div>' +
        '</div>');
        
        textDiv.hide().after(editForm);
        link.hide();
    });
    
    // Save activity edit
    $(document).on('click', '.cpc-save-activity-edit', function(e) {
        var btn = $(this);
        var postId = btn.data('post-id');
        var editForm = btn.closest('.cpc-edit-activity-form');
        var textarea = editForm.find('.cpc-edit-activity-textarea');
        var content = textarea.val();
        var activityPost = editForm.closest('.cpc-group-activity-post');
        var textDiv = activityPost.find('.cpc-activity-text');
        
        if (!content.trim()) {
            cpc_show_notification('Bitte gib einen Inhalt ein', 'error');
            return;
        }
        
        btn.prop('disabled', true).text('Speichern...');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_edit_activity',
                nonce: cpc_groups_ajax.nonce,
                post_id: postId,
                content: content
            },
            success: function(response) {
                if (response.success) {
                    cpc_show_notification(response.data.message, 'success');
                    textDiv.html(response.data.content).show();
                    editForm.remove();
                    activityPost.find('.cpc-edit-activity').show();
                } else {
                    cpc_show_notification(response.data.message, 'error');
                    btn.prop('disabled', false).text('Speichern');
                }
            },
            error: function() {
                cpc_show_notification('Fehler beim Speichern', 'error');
                btn.prop('disabled', false).text('Speichern');
            }
        });
    });
    
    // Cancel activity edit
    $(document).on('click', '.cpc-cancel-activity-edit', function(e) {
        var editForm = $(this).closest('.cpc-edit-activity-form');
        var activityPost = editForm.closest('.cpc-group-activity-post');
        
        activityPost.find('.cpc-activity-text').show();
        activityPost.find('.cpc-edit-activity').show();
        editForm.remove();
    });
    
    // Delete activity post
    $(document).on('click', '.cpc-delete-activity', function(e) {
        e.preventDefault();
        
        if (!confirm('Möchtest du diesen Beitrag wirklich löschen?')) {
            return;
        }
        
        var link = $(this);
        var postId = link.data('post-id');
        var activityPost = link.closest('.cpc-group-activity-post');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_delete_activity',
                nonce: cpc_groups_ajax.nonce,
                post_id: postId
            },
            success: function(response) {
                if (response.success) {
                    cpc_show_notification(response.data.message, 'success');
                    activityPost.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    cpc_show_notification(response.data.message, 'error');
                }
            },
            error: function() {
                cpc_show_notification('Fehler beim Löschen', 'error');
            }
        });
    });
    
    // Edit reply
    $(document).on('click', '.cpc-edit-reply', function(e) {
        e.preventDefault();
        
        var link = $(this);
        var commentId = link.data('comment-id');
        var reply = link.closest('.cpc-activity-reply');
        var textDiv = reply.find('.cpc-reply-text');
        var originalContent = textDiv.html().trim();
        
        // Create edit form
        var editForm = $('<div class="cpc-edit-reply-form">' +
            '<textarea class="cpc-edit-reply-textarea">' + $('<div>').html(originalContent).text() + '</textarea>' +
            '<div class="cpc-edit-actions">' +
                '<button type="button" class="cpc-save-reply-edit" data-comment-id="' + commentId + '">Speichern</button>' +
                '<button type="button" class="cpc-cancel-reply-edit">Abbrechen</button>' +
            '</div>' +
        '</div>');
        
        textDiv.hide().after(editForm);
        link.hide();
    });
    
    // Save reply edit
    $(document).on('click', '.cpc-save-reply-edit', function(e) {
        var btn = $(this);
        var commentId = btn.data('comment-id');
        var editForm = btn.closest('.cpc-edit-reply-form');
        var textarea = editForm.find('.cpc-edit-reply-textarea');
        var content = textarea.val();
        var reply = editForm.closest('.cpc-activity-reply');
        var textDiv = reply.find('.cpc-reply-text');
        
        if (!content.trim()) {
            cpc_show_notification('Bitte gib einen Inhalt ein', 'error');
            return;
        }
        
        btn.prop('disabled', true).text('Speichern...');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_edit_reply',
                nonce: cpc_groups_ajax.nonce,
                comment_id: commentId,
                content: content
            },
            success: function(response) {
                if (response.success) {
                    cpc_show_notification(response.data.message, 'success');
                    textDiv.html(response.data.content).show();
                    editForm.remove();
                    reply.find('.cpc-edit-reply').show();
                } else {
                    cpc_show_notification(response.data.message, 'error');
                    btn.prop('disabled', false).text('Speichern');
                }
            },
            error: function() {
                cpc_show_notification('Fehler beim Speichern', 'error');
                btn.prop('disabled', false).text('Speichern');
            }
        });
    });
    
    // Cancel reply edit
    $(document).on('click', '.cpc-cancel-reply-edit', function(e) {
        var editForm = $(this).closest('.cpc-edit-reply-form');
        var reply = editForm.closest('.cpc-activity-reply');
        
        reply.find('.cpc-reply-text').show();
        reply.find('.cpc-edit-reply').show();
        editForm.remove();
    });
    
    // Delete reply
    $(document).on('click', '.cpc-delete-reply', function(e) {
        e.preventDefault();
        
        if (!confirm('Möchtest du diese Antwort wirklich löschen?')) {
            return;
        }
        
        var link = $(this);
        var commentId = link.data('comment-id');
        var reply = link.closest('.cpc-activity-reply');
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_delete_reply',
                nonce: cpc_groups_ajax.nonce,
                comment_id: commentId
            },
            success: function(response) {
                if (response.success) {
                    cpc_show_notification(response.data.message, 'success');
                    reply.fadeOut(300, function() {
                        $(this).remove();
                    });
                } else {
                    cpc_show_notification(response.data.message, 'error');
                }
            },
            error: function() {
                cpc_show_notification('Fehler beim Löschen', 'error');
            }
        });
    });
    
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
    
    // Change member role
    $(document).on('change', '.cpc-change-member-role', function() {
        var select = $(this);
        var userId = select.data('user-id');
        var groupId = select.data('group-id');
        var newRole = select.val();
        var oldRole = select.find('option[selected]').val() || select.find('option:first').val();
        
        if (confirm('Rolle wirklich zu "' + newRole + '" ändern?')) {
            $.ajax({
                url: cpc_groups_ajax.ajaxurl,
                type: 'POST',
                data: {
                    action: 'cpc_change_member_role',
                    nonce: cpc_groups_ajax.nonce,
                    group_id: groupId,
                    member_id: userId,
                    new_role: newRole
                },
                success: function(response) {
                    if (response.success) {
                        cpc_show_notification(response.data.message, 'success');
                        // Update the selected attribute
                        select.find('option').removeAttr('selected');
                        select.find('option[value="' + newRole + '"]').attr('selected', 'selected');
                        // Update the display
                        select.closest('tr').find('td:eq(1)').html('<strong>' + newRole.charAt(0).toUpperCase() + newRole.slice(1) + '</strong>');
                    } else {
                        cpc_show_notification(response.data.message, 'error');
                        // Revert to old role
                        select.val(oldRole);
                    }
                },
                error: function() {
                    cpc_show_notification('Fehler beim Ändern der Rolle', 'error');
                    // Revert to old role
                    select.val(oldRole);
                }
            });
        } else {
            // Revert to old role
            select.val(oldRole);
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
