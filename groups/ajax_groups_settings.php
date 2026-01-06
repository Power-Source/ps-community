<?php
// DEPRECATED: This file is no longer used.
// All AJAX handlers have been integrated into ajax_groups.php
// This file is kept for backwards compatibility only.
?>
function cpc_ajax_save_group_forum_settings() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$enable_forum = isset($_POST['enable_forum']) ? (bool) $_POST['enable_forum'] : false;
	$forum_visibility = isset($_POST['forum_visibility']) ? sanitize_text_field($_POST['forum_visibility']) : 'group_only';

	if (!$group_id) {
		wp_send_json_error(array('message' => __('Ungültige Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	// Check if user is group admin
	if (!cpc_is_group_admin(get_current_user_id(), $group_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)));
	}

	// Save settings
	update_post_meta($group_id, 'cpc_group_has_forum', $enable_forum);
	if ($enable_forum) {
		update_post_meta($group_id, 'cpc_group_forum_visibility', $forum_visibility);
	}

	wp_send_json_success(array('message' => __('Forum-Einstellungen gespeichert!', CPC2_TEXT_DOMAIN)));
}

/**
 * Save group chat settings via AJAX
 */
add_action('wp_ajax_cpc_save_group_chat_settings', 'cpc_ajax_save_group_chat_settings');
function cpc_ajax_save_group_chat_settings() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$enable_chat = isset($_POST['enable_chat']) ? (bool) $_POST['enable_chat'] : false;

	if (!$group_id) {
		wp_send_json_error(array('message' => __('Ungültige Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	// Check if user is group admin
	if (!cpc_is_group_admin(get_current_user_id(), $group_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)));
	}

	// Check if chats are enabled globally
	if (!cpc_group_chats_enabled()) {
		wp_send_json_error(array('message' => __('Gruppen-Chats sind nicht aktiviert.', CPC2_TEXT_DOMAIN)));
	}

	// Save enable/disable setting
	update_post_meta($group_id, 'cpc_group_has_chat', $enable_chat);
	
	// If chat is enabled, save configuration
	if ($enable_chat) {
		$config = array(
			'box_title' => isset($_POST['chat_box_title']) ? sanitize_text_field($_POST['chat_box_title']) : '',
			'emoticons' => isset($_POST['chat_emoticons']) ? sanitize_text_field($_POST['chat_emoticons']) : 'disabled',
			'row_time' => isset($_POST['chat_row_time']) ? sanitize_text_field($_POST['chat_row_time']) : 'disabled',
			'users_list_position' => isset($_POST['chat_users_list_position']) ? sanitize_text_field($_POST['chat_users_list_position']) : 'none',
			'sound' => isset($_POST['chat_sound']) ? sanitize_text_field($_POST['chat_sound']) : 'enabled',
			'file_uploads_enabled' => isset($_POST['chat_file_uploads_enabled']) ? sanitize_text_field($_POST['chat_file_uploads_enabled']) : 'disabled',
			'log_creation' => isset($_POST['chat_log_creation']) ? sanitize_text_field($_POST['chat_log_creation']) : 'disabled',
		);
		
		if (function_exists('cpc_save_group_chat_config')) {
			cpc_save_group_chat_config($group_id, $config);
		}
	}

	wp_send_json_success(array('message' => __('Chat-Einstellungen gespeichert!', CPC2_TEXT_DOMAIN)));
}

/**
 * Save group permissions via AJAX
 */
add_action('wp_ajax_cpc_save_group_permissions', 'cpc_ajax_save_group_permissions');
function cpc_ajax_save_group_permissions() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	
	if (!$group_id) {
		wp_send_json_error(array('message' => __('Ungültige Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	// Check if user is group admin
	if (!cpc_is_group_admin(get_current_user_id(), $group_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)));
	}

	// Build permissions array from POST data
	$permissions = array(
		'forum_post' => isset($_POST['forum_post']) ? sanitize_text_field($_POST['forum_post']) : 'member',
		'invite_members' => isset($_POST['invite_members']) ? sanitize_text_field($_POST['invite_members']) : 'member',
		'activity_edit_all' => isset($_POST['activity_edit_all']) ? sanitize_text_field($_POST['activity_edit_all']) : 'moderator',
		'activity_delete_all' => isset($_POST['activity_delete_all']) ? sanitize_text_field($_POST['activity_delete_all']) : 'moderator',
	);

	// Save settings
	update_post_meta($group_id, 'cpc_group_permissions', $permissions);

	wp_send_json_success(array('message' => __('Berechtigungen gespeichert!', CPC2_TEXT_DOMAIN)));
}

/**
 * Change member role via AJAX
 */
add_action('wp_ajax_cpc_change_member_role', 'cpc_ajax_change_member_role');
function cpc_ajax_change_member_role() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	$new_role = isset($_POST['new_role']) ? sanitize_text_field($_POST['new_role']) : 'member';

	if (!$group_id || !$user_id) {
		wp_send_json_error(array('message' => __('Ungültige Parameter.', CPC2_TEXT_DOMAIN)));
	}

	// Check if requester is group admin
	if (!cpc_is_group_admin(get_current_user_id(), $group_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)));
	}

	// Don't allow changing own role
	if ($user_id == get_current_user_id()) {
		wp_send_json_error(array('message' => __('Du kannst deine eigene Rolle nicht ändern.', CPC2_TEXT_DOMAIN)));
	}

	// Validate role
	$valid_roles = array('member', 'moderator', 'admin');
	if (!in_array($new_role, $valid_roles)) {
		wp_send_json_error(array('message' => __('Ungültige Rolle.', CPC2_TEXT_DOMAIN)));
	}

	// Find and update membership
	$membership = cpc_get_group_membership($user_id, $group_id);
	if (!$membership) {
		wp_send_json_error(array('message' => __('Mitgliedschaft nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}

	update_post_meta($membership->ID, 'cpc_member_role', $new_role);

	wp_send_json_success(array('message' => __('Rolle aktualisiert!', CPC2_TEXT_DOMAIN)));
}

/**
 * Delete group via AJAX
 */
add_action('wp_ajax_cpc_delete_group', 'cpc_ajax_delete_group');
function cpc_ajax_delete_group() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;

	if (!$group_id) {
		wp_send_json_error(array('message' => __('Ungültige Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	// Check if user is group admin
	if (!cpc_is_group_admin(get_current_user_id(), $group_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)));
	}

	// Delete the group
	wp_delete_post($group_id, true);

	wp_send_json_success(array('message' => __('Gruppe gelöscht!', CPC2_TEXT_DOMAIN), 'redirect' => home_url()));
}

/**
 * Approve membership request via AJAX
 */
add_action('wp_ajax_cpc_approve_membership', 'cpc_ajax_approve_membership');
function cpc_ajax_approve_membership() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
	$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;

	if (!$group_id || !$request_id || !$user_id) {
		wp_send_json_error(array('message' => __('Ungültige Parameter.', CPC2_TEXT_DOMAIN)));
	}

	// Check if user is group admin
	if (!cpc_is_group_admin(get_current_user_id(), $group_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)));
	}

	// Update membership status
	update_post_meta($request_id, 'cpc_member_status', 'active');

	// Trigger alert/notification if available
	if (function_exists('cpc_add_alert')) {
		$group = get_post($group_id);
		cpc_add_alert(
			$user_id,
			sprintf(__('Deine Beitrittsanfrage für die Gruppe <a href="%s">%s</a> wurde genehmigt!', CPC2_TEXT_DOMAIN), get_permalink($group_id), $group->post_title),
			'group_approved',
			$group_id
		);
	}

	wp_send_json_success(array('message' => __('Anfrage genehmigt!', CPC2_TEXT_DOMAIN)));
}

/**
 * Reject membership request via AJAX
 */
add_action('wp_ajax_cpc_reject_membership', 'cpc_ajax_reject_membership');
function cpc_ajax_reject_membership() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;

	if (!$group_id || !$request_id) {
		wp_send_json_error(array('message' => __('Ungültige Parameter.', CPC2_TEXT_DOMAIN)));
	}

	// Check if user is group admin
	if (!cpc_is_group_admin(get_current_user_id(), $group_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)));
	}

	// Delete the membership request
	wp_delete_post($request_id, true);

	wp_send_json_success(array('message' => __('Anfrage abgelehnt!', CPC2_TEXT_DOMAIN)));
}
