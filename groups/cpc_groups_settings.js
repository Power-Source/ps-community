/**
 * PS Community Groups - Settings (AJAX)
 */
(function($) {
	$(function() {
		// Inline notification helper (uses global if present)
		function showNotice(message, type) {
			type = type || 'info';
			if (typeof window.cpc_show_notification === 'function') {
				window.cpc_show_notification(message, type);
				return;
			}

			var $container = $('.cpc-notifications-container');
			if (!$container.length) {
				$container = $('<div class="cpc-notifications-container"></div>').prependTo('body');
			}

			var $note = $('<div class="cpc-notification cpc-notification-' + type + '"></div>').text(message);
			$container.append($note);
			$note.fadeIn().delay(3000).fadeOut(function() {
				$(this).remove();
			});
		}

		// Chat config visibility toggle
		function toggleChatConfig($form) {
			var enabled = $form.find('#enable_chat').is(':checked');
			$form.find('.cpc-chat-config-section').toggle(enabled);
		}

		// Initialize chat config visibility on load
		$('.cpc-group-chat-settings-form').each(function() {
			toggleChatConfig($(this));
		});

		// Toggle chat config when enable checkbox changes
		$(document).on('change', '#enable_chat', function() {
			toggleChatConfig($(this).closest('form'));
		});

		// Submit chat settings via AJAX
		$(document).on('submit', '.cpc-group-chat-settings-form', function(event) {
			event.preventDefault();

			var $form = $(this);
			var groupId = $form.data('group-id');
			var enableChat = $form.find('#enable_chat').is(':checked') ? 1 : 0;
			var data = {
				action: 'cpc_save_group_chat_settings',
				nonce: cpc_groups_settings.nonce,
				group_id: groupId,
				enable_chat: enableChat
			};

			if (enableChat) {
				data.chat_box_title = $form.find('input[name="chat_box_title"]').val();
				data.chat_emoticons = $form.find('#chat_emoticons').is(':checked') ? 'enabled' : 'disabled';
				data.chat_row_time = $form.find('#chat_row_time').is(':checked') ? 'enabled' : 'disabled';
				data.chat_users_list_position = $form.find('select[name="chat_users_list_position"]').val();
				data.chat_sound = $form.find('#chat_sound').is(':checked') ? 'enabled' : 'disabled';
				data.chat_file_uploads_enabled = $form.find('#chat_file_uploads_enabled').is(':checked') ? 'enabled' : 'disabled';
				data.chat_log_creation = $form.find('#chat_log_creation').is(':checked') ? 'enabled' : 'disabled';
			}

			var $submit = $form.find('button[type="submit"]');
			$submit.prop('disabled', true);

			$.post(cpc_groups_settings.ajaxurl, data)
				.done(function(response) {
					var message = (response && response.data && response.data.message) ? response.data.message : 'Chat settings could not be saved.';
					showNotice(message, response && response.success ? 'success' : 'error');
					if (response && response.success && enableChat) {
						window.location.reload();
					}
				})
				.fail(function() {
					showNotice('Ein Fehler ist aufgetreten.', 'error');
				})
				.always(function() {
					$submit.prop('disabled', false);
				});
		});

		// Forum visibility toggle
		$(document).on('change', '#enable_forum', function() {
			$('.cpc-forum-visibility-field').slideToggle();
		});

		// Save forum settings
		$(document).on('submit', '.cpc-group-forum-settings-form', function(event) {
			event.preventDefault();

			var $form = $(this);
			var groupId = $form.data('group-id');
			var enableForum = $form.find('#enable_forum').is(':checked') ? 1 : 0;
			var forumVisibility = $form.find('input[name="forum_visibility"]:checked').val();

			$.ajax({
				url: cpc_groups_settings.ajaxurl,
				type: 'POST',
				data: {
					action: 'cpc_save_group_forum_settings',
					nonce: cpc_groups_settings.nonce,
					group_id: groupId,
					enable_forum: enableForum,
					forum_visibility: forumVisibility
				}
			}).done(function(response) {
				if (response && response.success) {
					showNotice(response.data.message, 'success');
				} else {
					showNotice('Fehler: ' + (response && response.data ? response.data.message : 'Unbekannter Fehler'), 'error');
				}
			}).fail(function() {
				showNotice('Ein Fehler ist aufgetreten.', 'error');
			});
		});

		// Save permissions
		$(document).on('submit', '.cpc-group-permissions-form', function(event) {
			event.preventDefault();

			var $form = $(this);
			var groupId = $form.data('group-id');

			$.ajax({
				url: cpc_groups_settings.ajaxurl,
				type: 'POST',
				data: {
					action: 'cpc_save_group_permissions',
					nonce: cpc_groups_settings.nonce,
					group_id: groupId,
					forum_post: $form.find('select[name="forum_post"]').val(),
					invite_members: $form.find('select[name="invite_members"]').val(),
					activity_edit_all: $form.find('select[name="activity_edit_all"]').val(),
					activity_delete_all: $form.find('select[name="activity_delete_all"]').val()
				}
			}).done(function(response) {
				if (response && response.success) {
					showNotice(response.data.message, 'success');
				} else {
					showNotice('Fehler: ' + (response && response.data ? response.data.message : 'Unbekannter Fehler'), 'error');
				}
			}).fail(function() {
				showNotice('Ein Fehler ist aufgetreten.', 'error');
			});
		});

		// Change member role
		$(document).on('change', '.cpc-change-member-role', function() {
			var $select = $(this);
			var userId = $select.data('user-id');
			var groupId = $select.data('group-id');
			var newRole = $select.val();

			if (!confirm('Rolle für Mitglied wirklich ändern?')) {
				$select.val($select.data('previous-role'));
				return;
			}

			$.ajax({
				url: cpc_groups_settings.ajaxurl,
				type: 'POST',
				data: {
					action: 'cpc_change_member_role',
					nonce: cpc_groups_settings.nonce,
					group_id: groupId,
					user_id: userId,
					new_role: newRole
				}
			}).done(function(response) {
				if (response && response.success) {
					showNotice(response.data.message, 'success');
					$select.data('previous-role', newRole);
				} else {
					showNotice('Fehler: ' + (response && response.data ? response.data.message : 'Unbekannter Fehler'), 'error');
					window.location.reload();
				}
			}).fail(function() {
				showNotice('Ein Fehler ist aufgetreten.', 'error');
				window.location.reload();
			});
		});

		// Delete group
		$(document).on('click', '.cpc-delete-group-btn', function(event) {
			event.preventDefault();

			if (!confirm('Willst du diese Gruppe wirklich löschen? Dies kann nicht rückgängig gemacht werden.')) {
				return;
			}

			if (!confirm('Bist du dir wirklich sicher?')) {
				return;
			}

			var groupId = $(this).data('group-id');

			$.ajax({
				url: cpc_groups_settings.ajaxurl,
				type: 'POST',
				data: {
					action: 'cpc_delete_group',
					nonce: cpc_groups_settings.nonce,
					group_id: groupId
				}
			}).done(function(response) {
				if (response && response.success) {
					showNotice(response.data.message, 'success');
					window.location.href = response.data.redirect;
				} else {
					showNotice('Fehler: ' + (response && response.data ? response.data.message : 'Unbekannter Fehler'), 'error');
				}
			}).fail(function() {
				showNotice('Ein Fehler ist aufgetreten.', 'error');
			});
		});

		// Approve membership
		$(document).on('click', '.cpc-approve-membership', function(event) {
			event.preventDefault();

			var $btn = $(this);
			var requestId = $btn.data('request-id');
			var groupId = $btn.data('group-id');
			var userId = $btn.data('user-id');

			$.ajax({
				url: cpc_groups_settings.ajaxurl,
				type: 'POST',
				data: {
					action: 'cpc_approve_membership',
					nonce: cpc_groups_settings.nonce,
					group_id: groupId,
					request_id: requestId,
					user_id: userId
				}
			}).done(function(response) {
				if (response && response.success) {
					showNotice(response.data.message, 'success');
					$btn.closest('.cpc-membership-request').fadeOut();
				} else {
					showNotice('Fehler: ' + (response && response.data ? response.data.message : 'Unbekannter Fehler'), 'error');
				}
			}).fail(function() {
				showNotice('Ein Fehler ist aufgetreten.', 'error');
			});
		});

		// Reject membership
		$(document).on('click', '.cpc-reject-membership', function(event) {
			event.preventDefault();

			if (!confirm('Anfrage wirklich ablehnen?')) {
				return;
			}

			var $btn = $(this);
			var requestId = $btn.data('request-id');
			var groupId = $btn.data('group-id');

			$.ajax({
				url: cpc_groups_settings.ajaxurl,
				type: 'POST',
				data: {
					action: 'cpc_reject_membership',
					nonce: cpc_groups_settings.nonce,
					group_id: groupId,
					request_id: requestId
				}
			}).done(function(response) {
				if (response && response.success) {
					showNotice(response.data.message, 'success');
					$btn.closest('.cpc-membership-request').fadeOut();
				} else {
					showNotice('Fehler: ' + (response && response.data ? response.data.message : 'Unbekannter Fehler'), 'error');
				}
			}).fail(function() {
				showNotice('Ein Fehler ist aufgetreten.', 'error');
			});
		});
	});
})(jQuery);

