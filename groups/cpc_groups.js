/* PS Community Groups JavaScript */

jQuery(document).ready(function($) {

    function cpcGroupActivityHasMedia(form) {
        var imageInput = form.find('input[name="cpc_activity_plus_images[]"]');
        var hasUploads = imageInput.length > 0 && imageInput[0].files && imageInput[0].files.length > 0;
        var remoteImages = $.trim(form.find('textarea[name="cpc_activity_plus_remote_images"]').val() || '');
        var linkUrl = $.trim(form.find('input[name="cpc_activity_plus_link_url"]').val() || '');
        var videoUrl = $.trim(form.find('input[name="cpc_activity_plus_video_url"]').val() || '');

        return hasUploads || remoteImages !== '' || linkUrl !== '' || videoUrl !== '';
    }
    
    // Post Group Activity
    $(document).on('submit', '.cpc-group-post-activity-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var groupId = form.data('group-id');
        var content = form.find('textarea[name="activity_content"]').val();
        var submitBtn = form.find('button[type="submit"]');
        
        if (!groupId) {
            cpc_show_notification(cpc_groups_ajax.i18n.groupNotFound, 'error');
            return;
        }
        
        content = content ? content.trim() : '';

        if (!content && !cpcGroupActivityHasMedia(form)) {
            cpc_show_notification(cpc_groups_ajax.i18n.emptyActivity, 'error');
            return;
        }
        
        submitBtn.prop('disabled', true).text(cpc_groups_ajax.i18n.posting);
        
        // Use FormData to support file uploads
        var formData = new FormData();
        formData.append('action', 'cpc_post_group_activity');
        formData.append('nonce', cpc_groups_ajax.nonce);
        formData.append('group_id', groupId);
        formData.append('activity_content', content);
        
        // Add Activity Plus nonce if exists
        var activityPlusNonce = form.find('input[name="cpc_activity_plus_nonce"]').val();
        if (activityPlusNonce) {
            formData.append('cpc_activity_plus_nonce', activityPlusNonce);
        }
        
        // Add uploaded images
        var imageFiles = form.find('input[name="cpc_activity_plus_images[]"]');
        if (imageFiles.length > 0 && imageFiles[0].files) {
            $.each(imageFiles[0].files, function(i, file) {
                formData.append('cpc_activity_plus_images[]', file);
            });
        }
        
        // Add remote images
        var remoteImages = form.find('textarea[name="cpc_activity_plus_remote_images"]').val();
        if (remoteImages) {
            formData.append('cpc_activity_plus_remote_images', remoteImages);
        }
        
        // Add link URL
        var linkUrl = form.find('input[name="cpc_activity_plus_link_url"]').val();
        if (linkUrl) {
            formData.append('cpc_activity_plus_link_url', linkUrl);
        }
        
        // Add video URL
        var videoUrl = form.find('input[name="cpc_activity_plus_video_url"]').val();
        if (videoUrl) {
            formData.append('cpc_activity_plus_video_url', videoUrl);
        }
        
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    cpc_show_notification(response.data.message, 'success');
                    form.find('textarea').val('');
                    form.find('input[type="file"]').val('');
                    form.find('input[type="url"]').val('');
                    form.find('.cpc_activity_plus_wrap').hide();
                    submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.post);

                    if (response.data && response.data.html) {
                        var newItem = $(response.data.html).hide();
                        var overviewTab = form.closest('.cpc-group-overview-tab');
                        var formWrap = form.closest('.cpc-group-activity-form');
                        overviewTab.find('.cpc-no-activity').remove();

                        if (formWrap.length) {
                            formWrap.after(newItem);
                        } else {
                            overviewTab.prepend(newItem);
                        }

                        newItem.slideDown(180);
                    } else {
                        location.reload();
                    }
                } else {
                    cpc_show_notification(response.data.message, 'error');
                    submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.post);
                }
            },
            error: function() {
                cpc_show_notification(cpc_groups_ajax.i18n.postActivityError, 'error');
                submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.post);
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
            toggle.text(cpc_groups_ajax.i18n.cancel);
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
            cpc_show_notification(cpc_groups_ajax.i18n.emptyReply, 'error');
            return;
        }
        
        btn.prop('disabled', true).text(cpc_groups_ajax.i18n.sending);
        
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
                    cpc_show_notification(cpc_groups_ajax.i18n.replyPosted, 'success');
                    textarea.val('');
                    btn.prop('disabled', false).text(cpc_groups_ajax.i18n.reply);
                    
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
                    repliesContainer.find('.cpc-reply-toggle').text(cpc_groups_ajax.i18n.addReply);
                } else {
                    cpc_show_notification(response.data.message, 'error');
                    btn.prop('disabled', false).text(cpc_groups_ajax.i18n.reply);
                }
            },
            error: function() {
                cpc_show_notification(cpc_groups_ajax.i18n.postReplyError, 'error');
                btn.prop('disabled', false).text(cpc_groups_ajax.i18n.reply);
            }
        });
    });
    
    // Save group permissions
    $(document).on('submit', '.cpc-group-permissions-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var submitBtn = form.find('button[type="submit"]');
        var groupId = form.find('input[name="group_id"]').val() || form.data('group-id');
        
        submitBtn.prop('disabled', true).text(cpc_groups_ajax.i18n.saving);
        
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
                submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.save);
            },
            error: function() {
                cpc_show_notification(cpc_groups_ajax.i18n.saveError, 'error');
                submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.save);
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
                '<button type="button" class="cpc-save-activity-edit" data-post-id="' + postId + '">' + cpc_groups_ajax.i18n.save + '</button>' +
                '<button type="button" class="cpc-cancel-activity-edit">' + cpc_groups_ajax.i18n.cancel + '</button>' +
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
            cpc_show_notification(cpc_groups_ajax.i18n.emptyContent, 'error');
            return;
        }
        
        btn.prop('disabled', true).text(cpc_groups_ajax.i18n.saving);
        
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
                    btn.prop('disabled', false).text(cpc_groups_ajax.i18n.save);
                }
            },
            error: function() {
                cpc_show_notification(cpc_groups_ajax.i18n.saveError, 'error');
                btn.prop('disabled', false).text(cpc_groups_ajax.i18n.save);
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
        
        if (!confirm(cpc_groups_ajax.i18n.confirmDeletePost)) {
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
                cpc_show_notification(cpc_groups_ajax.i18n.deleteError, 'error');
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
                '<button type="button" class="cpc-save-reply-edit" data-comment-id="' + commentId + '">' + cpc_groups_ajax.i18n.save + '</button>' +
                '<button type="button" class="cpc-cancel-reply-edit">' + cpc_groups_ajax.i18n.cancel + '</button>' +
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
            cpc_show_notification(cpc_groups_ajax.i18n.emptyContent, 'error');
            return;
        }
        
        btn.prop('disabled', true).text(cpc_groups_ajax.i18n.saving);
        
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
                    btn.prop('disabled', false).text(cpc_groups_ajax.i18n.save);
                }
            },
            error: function() {
                cpc_show_notification(cpc_groups_ajax.i18n.saveError, 'error');
                btn.prop('disabled', false).text(cpc_groups_ajax.i18n.save);
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
        
        if (!confirm(cpc_groups_ajax.i18n.confirmDeleteReply)) {
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
                cpc_show_notification(cpc_groups_ajax.i18n.deleteError, 'error');
            }
        });
    });
    
    // Join Group
    $(document).on('click', '.cpc-group-join-btn', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var groupId = button.data('group-id');
        
        if (!groupId) {
            alert(cpc_groups_ajax.i18n.groupNotFound);
            return;
        }

        button.prop('disabled', true).text(cpc_groups_ajax.i18n.processing);
        
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
                        button.text(cpc_groups_ajax.i18n.pending).removeClass('cpc-group-join-btn').addClass('cpc-group-pending-btn');
                        alert(response.data.message);
                    } else {
                        button.text(cpc_groups_ajax.i18n.leaveGroup).removeClass('cpc-group-join-btn').addClass('cpc-group-leave-btn');
                    }
                    
                    // Reload page to update member count
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data.message);
                    button.prop('disabled', false).text(cpc_groups_ajax.i18n.joinGroup);
                }
            },
            error: function() {
                alert(cpc_groups_ajax.i18n.genericRetryError);
                button.prop('disabled', false).text(cpc_groups_ajax.i18n.joinGroup);
            }
        });
    });

    // Leave Group
    $(document).on('click', '.cpc-group-leave-btn', function(e) {
        e.preventDefault();
        
        var button = $(this);
        var groupId = button.data('group-id');
        
        if (!groupId) {
            alert(cpc_groups_ajax.i18n.groupNotFound);
            return;
        }

        if (!confirm(cpc_groups_ajax.i18n.confirmLeaveGroup)) {
            return;
        }

        button.prop('disabled', true).text(cpc_groups_ajax.i18n.processing);
        
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
                    button.text(cpc_groups_ajax.i18n.joinGroup).removeClass('cpc-group-leave-btn').addClass('cpc-group-join-btn');
                    
                    // Reload page to update member count
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data.message);
                    button.prop('disabled', false).text(cpc_groups_ajax.i18n.leaveGroup);
                }
            },
            error: function() {
                alert(cpc_groups_ajax.i18n.genericRetryError);
                button.prop('disabled', false).text(cpc_groups_ajax.i18n.leaveGroup);
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
        
        submitBtn.prop('disabled', true).text(cpc_groups_ajax.i18n.creating);
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
                    
                    submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.createGroup);
                } else {
                    messageDiv.addClass('error').text(response.data.message);
                    submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.createGroup);
                }
            },
            error: function() {
                messageDiv.addClass('error').text(cpc_groups_ajax.i18n.genericRetryError);
                submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.createGroup);
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
                alert(cpc_groups_ajax.i18n.genericError);
            }
        });
    });

    // Remove Member (for admins)
    $(document).on('click', '.cpc-remove-member', function(e) {
        e.preventDefault();
        
        if (!confirm(cpc_groups_ajax.i18n.confirmRemoveMember)) {
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
                alert(cpc_groups_ajax.i18n.genericError);
            }
        });
    });

    // Group Tabs Navigation
    $(document).on('click', '.cpc-group-tab-link', function(e) {
        e.preventDefault();
        
        var link = $(this);
        var tab = link.data('tab');
        var href = link.attr('href');
        var tabs_container = link.closest('.cpc-group-single').find('.cpc-group-tabs-content').first();

        // Update active tab styling
        link.closest('.cpc-group-tabs-list').find('.cpc-group-tab-item').removeClass('active');
        link.closest('.cpc-group-tab-item').addClass('active');
        
        // Update URL with tab parameter
        try {
            var url = new URL(window.location.href);
            url.searchParams.set('tab', tab);
            window.history.replaceState({}, '', url);
        } catch (err) {
            // Ignore URL API errors in older/embedded browsers
        }
        
        // Load tab content via AJAX
        var groupId = tabs_container.data('group-id');
        if (groupId && typeof cpc_groups_ajax !== 'undefined' && cpc_groups_ajax.ajaxurl && cpc_groups_ajax.nonce) {
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
                        if (response.data && response.data.redirect) {
                            window.location.href = response.data.redirect;
                            return;
                        }
                        tabs_container.html(response.data.html);
                    } else if (href) {
                        window.location.href = href;
                    }
                },
                error: function() {
                    if (href) {
                        window.location.href = href;
                    }
                }
            });
        } else if (href) {
            window.location.href = href;
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
                alert(cpc_groups_ajax.i18n.genericError);
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
        
        if (!confirm(cpc_groups_ajax.i18n.confirmApproveRequest)) {
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
                            $('.cpc-membership-requests').html('<p>' + cpc_groups_ajax.i18n.noPendingRequests + '</p>');
                        }
                    });
                } else {
                    alert(response.data.message);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                alert(cpc_groups_ajax.i18n.processRequestError);
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
        
        if (!confirm(cpc_groups_ajax.i18n.confirmRejectRequest)) {
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
                            $('.cpc-membership-requests').html('<p>' + cpc_groups_ajax.i18n.noPendingRequests + '</p>');
                        }
                    });
                } else {
                    alert(response.data.message);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                alert(cpc_groups_ajax.i18n.processRequestError);
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

    // Save basic group settings
    $(document).on('submit', '.cpc-group-settings-form', function(e) {
        e.preventDefault();

        var form = $(this);
        var groupId = form.data('group-id');
        var groupType = form.find('select[name="group_type"]').val();
        var streamVisibility = form.find('select[name="group_stream_album_visibility"]').val() || '';
        var submitBtn = form.find('button[type="submit"]');

        submitBtn.prop('disabled', true).text(cpc_groups_ajax.i18n.saving);

        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_save_group_settings',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId,
                group_type: groupType,
                group_stream_album_visibility: streamVisibility
            },
            success: function(response) {
                submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.saveChanges);

                if (response.success) {
                    cpc_show_notification(response.data.message, 'success');
                } else {
                    cpc_show_notification(response.data.message, 'error');
                }
            },
            error: function() {
                cpc_show_notification(cpc_groups_ajax.i18n.saveSettingsError, 'error');
                submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.saveChanges);
            }
        });
    });

    // Submit group forum settings
    $(document).on('submit', '.cpc-group-forum-settings-form', function(e) {
        e.preventDefault();
        
        var form = $(this);
        var groupId = form.data('group-id');
        var enableForum = form.find('#enable_forum').is(':checked');
        var forumVisibility = form.find('input[name="forum_visibility"]:checked').val();
        var submitBtn = form.find('button[type="submit"]');
        
        submitBtn.prop('disabled', true).text(cpc_groups_ajax.i18n.saving);
        
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
                submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.saveForumSettings);
                
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
                alert(cpc_groups_ajax.i18n.saveForumSettingsError);
                submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.saveForumSettings);
            }
        });
    });

    // Save optional group module settings (media/docs/projects)
    $(document).on('submit', '.cpc-group-modules-form', function(e) {
        e.preventDefault();

        var form = $(this);
        var groupId = form.data('group-id');
        var submitBtn = form.find('button[type="submit"]');

        submitBtn.prop('disabled', true).text(cpc_groups_ajax.i18n.saving);

        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_save_group_module_settings',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId,
                enable_media: form.find('#enable_media').is(':checked') ? 1 : 0,
                enable_projects: form.find('#enable_projects').is(':checked') ? 1 : 0,
                enable_events: form.find('#enable_events').is(':checked') ? 1 : 0,
                enable_event_emails: form.find('#enable_event_emails').is(':checked') ? 1 : 0
            },
            success: function(response) {
                submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.saveModules);

                if (response.success) {
                    cpc_show_notification(response.data.message, 'success');
                    setTimeout(function() {
                        location.reload();
                    }, 250);
                } else {
                    cpc_show_notification(response.data.message, 'error');
                }
            },
            error: function() {
                cpc_show_notification(cpc_groups_ajax.i18n.saveModulesError, 'error');
                submitBtn.prop('disabled', false).text(cpc_groups_ajax.i18n.saveModules);
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
        
        if (confirm(cpc_groups_ajax.i18n.confirmRoleChange.replace('%s', newRole))) {
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
                    cpc_show_notification(cpc_groups_ajax.i18n.roleChangeError, 'error');
                    // Revert to old role
                    select.val(oldRole);
                }
            });
        } else {
            // Revert to old role
            select.val(oldRole);
        }
    });

    // Open invite modal
    $(document).on('click', '.cpc-group-invite-btn', function(e) {
        e.preventDefault();
        var groupId = $(this).data('group-id');
        if (!groupId) return;
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_group_invite_modal',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId
            },
            success: function(response) {
                if (response.success && response.data.html) {
                    $('body').append(response.data.html);
                } else if (response.data && response.data.message) {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(cpc_groups_ajax.i18n.loadInviteDialogError);
            }
        });
    });

    // Close invite modal
    $(document).on('click', '.cpc-group-invite-close, .cpc-group-invite-modal-overlay', function(e) {
        if ($(e.target).closest('.cpc-group-invite-modal').length && !$(e.target).hasClass('cpc-group-invite-close')) {
            return;
        }
        $('.cpc-group-invite-modal-overlay').remove();
    });

    // Submit invites
    $(document).on('submit', '.cpc-group-invite-form', function(e) {
        e.preventDefault();
        var form = $(this);
        var groupId = form.data('group-id');
        var selected = form.find('input[name="friend_ids[]"]:checked');
        if (selected.length === 0) {
            alert(cpc_groups_ajax.i18n.selectFriend);
            return;
        }
        var friendIds = [];
        selected.each(function(){ friendIds.push($(this).val()); });
        var message = form.find('textarea[name="invite_message"]').val();
        $.ajax({
            url: cpc_groups_ajax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cpc_group_send_invites',
                nonce: cpc_groups_ajax.nonce,
                group_id: groupId,
                friend_ids: friendIds,
                invite_message: message
            },
            success: function(response) {
                if (response.success) {
                    alert(response.data.message || cpc_groups_ajax.i18n.invitationsSent);
                    $('.cpc-group-invite-modal-overlay').remove();
                } else if (response.data && response.data.message) {
                    alert(response.data.message);
                }
            },
            error: function() {
                alert(cpc_groups_ajax.i18n.sendInvitationsError);
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
