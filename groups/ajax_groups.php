<?php

/* AJAX handlers for group functionality */

// Join group
add_action('wp_ajax_cpc_join_group', 'cpc_ajax_join_group');
function cpc_ajax_join_group() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$user_id = get_current_user_id();

	if (!$group_id) {
		wp_send_json_error(array('message' => __('Ungültige Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	$group = get_post($group_id);
	if (!$group || $group->post_type != 'cpc_group') {
		wp_send_json_error(array('message' => __('Gruppe nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}

	// Check if already member
	if (cpc_is_group_member($user_id, $group_id)) {
		wp_send_json_error(array('message' => __('Du bist bereits Mitglied dieser Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	$group_type = get_post_meta($group_id, 'cpc_group_type', true);
	if (!$group_type) $group_type = 'public';

	// Determine status based on group type
	$status = 'active';
	if ($group_type == 'private') {
		$status = 'pending'; // Requires approval for private groups
	}

	$membership_id = cpc_add_group_member($user_id, $group_id, 'member', $status);

	if ($membership_id) {
		if ($status == 'pending') {
			wp_send_json_success(array(
				'message' => __('Beitrittsanfrage gesendet. Warte auf Genehmigung.', CPC2_TEXT_DOMAIN),
				'status' => 'pending'
			));
		} else {
			wp_send_json_success(array(
				'message' => __('Du bist der Gruppe erfolgreich beigetreten!', CPC2_TEXT_DOMAIN),
				'status' => 'joined'
			));
		}
	} else {
		wp_send_json_error(array('message' => __('Fehler beim Beitritt zur Gruppe.', CPC2_TEXT_DOMAIN)));
	}
}

// Leave group
add_action('wp_ajax_cpc_leave_group', 'cpc_ajax_leave_group');
function cpc_ajax_leave_group() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$user_id = get_current_user_id();

	if (!$group_id) {
		wp_send_json_error(array('message' => __('Ungültige Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	// Check if user is the creator/only admin
	$admins = cpc_get_group_admins($group_id);
	if (count($admins) == 1 && $admins[0]->ID == $user_id) {
		wp_send_json_error(array('message' => __('Du kannst die Gruppe nicht verlassen, da du der einzige Admin bist. Ernenne zuerst einen anderen Admin.', CPC2_TEXT_DOMAIN)));
	}

	$success = cpc_remove_group_member($user_id, $group_id);

	if ($success) {
		wp_send_json_success(array('message' => __('Du hast die Gruppe verlassen.', CPC2_TEXT_DOMAIN)));
	} else {
		wp_send_json_error(array('message' => __('Fehler beim Verlassen der Gruppe.', CPC2_TEXT_DOMAIN)));
	}
}

// Create group
add_action('wp_ajax_cpc_create_group', 'cpc_ajax_create_group');
function cpc_ajax_create_group() {
	check_ajax_referer('cpc_create_group', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_name = isset($_POST['group_name']) ? sanitize_text_field($_POST['group_name']) : '';
	$group_description = isset($_POST['group_description']) ? wp_kses_post($_POST['group_description']) : '';
	$group_type = isset($_POST['group_type']) ? sanitize_text_field($_POST['group_type']) : 'public';

	if (!$group_name) {
		wp_send_json_error(array('message' => __('Gruppenname ist erforderlich.', CPC2_TEXT_DOMAIN)));
	}

	// Validate group type
	if (!in_array($group_type, array('public', 'private', 'hidden'))) {
		$group_type = 'public';
	}

	$user_id = get_current_user_id();

	// Create group post
	$group_id = wp_insert_post(array(
		'post_title' => $group_name,
		'post_content' => $group_description,
		'post_type' => 'cpc_group',
		'post_status' => 'publish',
		'post_author' => $user_id,
	));

	if (is_wp_error($group_id)) {
		wp_send_json_error(array('message' => __('Fehler beim Erstellen der Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	// Set group meta
	update_post_meta($group_id, 'cpc_group_type', $group_type);
	update_post_meta($group_id, 'cpc_group_creator', $user_id);
	update_post_meta($group_id, 'cpc_group_updated', current_time('timestamp'));

	// Add creator as admin member
	cpc_add_group_member($user_id, $group_id, 'admin', 'active');

	// Handle avatar upload if present
	if (!empty($_FILES['group_avatar']['name'])) {
		require_once(ABSPATH . 'wp-admin/includes/image.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/media.php');

		$attachment_id = media_handle_upload('group_avatar', $group_id);
		if (!is_wp_error($attachment_id)) {
			set_post_thumbnail($group_id, $attachment_id);
		}
	}

	do_action('cpc_group_created', $group_id, $user_id);

	wp_send_json_success(array(
		'message' => __('Gruppe erfolgreich erstellt!', CPC2_TEXT_DOMAIN),
		'group_id' => $group_id,
		'group_url' => get_permalink($group_id)
	));
}

// Update group member role (admin only)
add_action('wp_ajax_cpc_update_member_role', 'cpc_ajax_update_member_role');
function cpc_ajax_update_member_role() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;
	$new_role = isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '';

	$current_user_id = get_current_user_id();

	// Check if current user is admin
	if (!cpc_is_group_admin($current_user_id, $group_id)) {
		wp_send_json_error(array('message' => __('Du hast keine Berechtigung dazu.', CPC2_TEXT_DOMAIN)));
	}

	// Validate role
	if (!in_array($new_role, array('member', 'moderator', 'admin'))) {
		wp_send_json_error(array('message' => __('Ungültige Rolle.', CPC2_TEXT_DOMAIN)));
	}

	// Get membership
	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => 1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => 'cpc_member_user_id',
				'value' => $member_id,
			),
			array(
				'key' => 'cpc_member_group_id',
				'value' => $group_id,
			),
		),
	);
	$membership = get_posts($args);

	if (empty($membership)) {
		wp_send_json_error(array('message' => __('Mitgliedschaft nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}

	update_post_meta($membership[0]->ID, 'cpc_member_role', $new_role);

	wp_send_json_success(array('message' => __('Rolle erfolgreich aktualisiert.', CPC2_TEXT_DOMAIN)));
}

// Remove member from group (admin only)
add_action('wp_ajax_cpc_remove_member', 'cpc_ajax_remove_member');
function cpc_ajax_remove_member() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;

	$current_user_id = get_current_user_id();

	// Check if current user is admin or moderator
	if (!cpc_is_group_moderator($current_user_id, $group_id)) {
		wp_send_json_error(array('message' => __('Du hast keine Berechtigung dazu.', CPC2_TEXT_DOMAIN)));
	}

	// Can't remove self this way
	if ($member_id == $current_user_id) {
		wp_send_json_error(array('message' => __('Nutze die "Gruppe verlassen" Funktion.', CPC2_TEXT_DOMAIN)));
	}

	$success = cpc_remove_group_member($member_id, $group_id);

	if ($success) {
		wp_send_json_success(array('message' => __('Mitglied erfolgreich entfernt.', CPC2_TEXT_DOMAIN)));
	} else {
		wp_send_json_error(array('message' => __('Fehler beim Entfernen des Mitglieds.', CPC2_TEXT_DOMAIN)));
	}
}

// Approve pending member (admin only)
add_action('wp_ajax_cpc_approve_member', 'cpc_ajax_approve_member');
function cpc_ajax_approve_member() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$member_id = isset($_POST['member_id']) ? intval($_POST['member_id']) : 0;

	$current_user_id = get_current_user_id();

	// Check if current user is admin or moderator
	if (!cpc_is_group_moderator($current_user_id, $group_id)) {
		wp_send_json_error(array('message' => __('Du hast keine Berechtigung dazu.', CPC2_TEXT_DOMAIN)));
	}

	// Get membership
	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => 1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => 'cpc_member_user_id',
				'value' => $member_id,
			),
			array(
				'key' => 'cpc_member_group_id',
				'value' => $group_id,
			),
			array(
				'key' => 'cpc_member_status',
				'value' => 'pending',
			),
		),
	);
	$membership = get_posts($args);

	if (empty($membership)) {
		wp_send_json_error(array('message' => __('Ausstehende Mitgliedschaft nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}

	update_post_meta($membership[0]->ID, 'cpc_member_status', 'active');
	cpc_update_group_member_count($group_id);

	do_action('cpc_member_approved', $member_id, $group_id);

	wp_send_json_success(array('message' => __('Mitglied erfolgreich genehmigt.', CPC2_TEXT_DOMAIN)));
}
?>
