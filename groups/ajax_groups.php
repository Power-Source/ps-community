<?php

/* AJAX handlers for group functionality */

// Post group activity
add_action('wp_ajax_cpc_post_group_activity', 'cpc_ajax_post_group_activity');
function cpc_ajax_post_group_activity() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');

	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}

	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$content = isset($_POST['activity_content']) ? wp_kses_post($_POST['activity_content']) : '';
	$user_id = get_current_user_id();

	if (!$group_id) {
		wp_send_json_error(array('message' => __('Ungültige Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	if (!$content) {
		wp_send_json_error(array('message' => __('Aktivität kann nicht leer sein.', CPC2_TEXT_DOMAIN)));
	}

	$group = get_post($group_id);
	if (!$group || $group->post_type !== 'cpc_group') {
		wp_send_json_error(array('message' => __('Gruppe nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}

	// Check if user is group member
	if (!cpc_is_group_member($user_id, $group_id)) {
		wp_send_json_error(array('message' => __('Du musst Mitglied der Gruppe sein um zu posten.', CPC2_TEXT_DOMAIN)));
	}

	// Check if activity module is available and use it if present
	if (function_exists('cpc_add_activity')) {
		// Use activity module if available
		$activity_id = cpc_add_activity(
			$user_id,
			'group_activity',
			$content,
			$group_id
		);
	} else {
		// Fallback: create activity post directly
		$activity_id = wp_insert_post(array(
			'post_type' => 'cpc_activity',
			'post_content' => $content,
			'post_author' => $user_id,
			'post_status' => 'publish',
			'post_title' => substr($content, 0, 100)
		));

		if (!is_wp_error($activity_id)) {
			// Add meta to link to group
			update_post_meta($activity_id, 'cpc_activity_group_id', $group_id);
			update_post_meta($activity_id, 'cpc_activity_type', 'group_activity');
		}
	}

	if ($activity_id && !is_wp_error($activity_id)) {
		wp_send_json_success(array(
			'message' => __('Aktivität erfolgreich gepostet!', CPC2_TEXT_DOMAIN),
			'activity_id' => $activity_id
		));
	} else {
		wp_send_json_error(array('message' => __('Fehler beim Posten der Aktivität.', CPC2_TEXT_DOMAIN)));
	}
}

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

	wp_send_json_success(array('message' => __('Mitglied erfolgreich genehmigt.', CPC2_TEXT_DOMAIN)));}

// Load group tab content via AJAX
add_action('wp_ajax_cpc_load_group_tab', 'cpc_ajax_load_group_tab');
add_action('wp_ajax_nopriv_cpc_load_group_tab', 'cpc_ajax_load_group_tab');
function cpc_ajax_load_group_tab() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	
	if (!isset($_POST['group_id']) || !isset($_POST['tab'])) {
		wp_send_json_error(array('message' => 'Ungültige Parameter'));
	}
	
	$group_id = intval($_POST['group_id']);
	$tab = sanitize_key($_POST['tab']);
	
	// Get group
	$group = get_post($group_id);
	if (!$group || $group->post_type !== 'cpc_group') {
		wp_send_json_error(array('message' => 'Gruppe nicht gefunden'));
	}
	
	// Check if user can view group
	if (!cpc_can_view_group(get_current_user_id(), $group_id)) {
		wp_send_json_error(array('message' => 'Zugriff verweigert'));
	}
	
	// Render tab content - function returns HTML, doesn't echo it
	$content = cpc_render_group_tab_content($group_id, $tab, array());
	
	if (empty($content)) {
		$content = '<p>Kein Inhalt für diesen Tab verfügbar.</p>';
	}
	
	wp_send_json_success(array('html' => $content));
}

// Approve membership request
add_action('wp_ajax_cpc_approve_membership', 'cpc_ajax_approve_membership');
function cpc_ajax_approve_membership() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => 'Sie müssen angemeldet sein'));
	}
	
	if (!isset($_POST['request_id']) || !isset($_POST['group_id'])) {
		wp_send_json_error(array('message' => 'Ungültige Parameter'));
	}
	
	$request_id = intval($_POST['request_id']);
	$group_id = intval($_POST['group_id']);
	$current_user_id = get_current_user_id();
	
	// Check if user is group admin
	if (!cpc_is_group_admin($current_user_id, $group_id)) {
		wp_send_json_error(array('message' => 'Du hast keine Berechtigung, diese Anfrage zu genehmigen'));
	}
	
	// Get the request
	$request = get_post($request_id);
	if (!$request || $request->post_type !== 'cpc_group_members') {
		wp_send_json_error(array('message' => 'Anfrage nicht gefunden'));
	}
	
	// Approve the request
	update_post_meta($request_id, 'cpc_member_status', 'active');
	update_post_meta($request_id, 'cpc_member_joined', current_time('mysql'));
	
	// Update member count
	cpc_update_group_member_count($group_id);
	
	// Fire action for other plugins
	$user_id = get_post_meta($request_id, 'cpc_member_user_id', true);
	do_action('cpc_membership_approved', $user_id, $group_id);
	
	wp_send_json_success(array('message' => __('Mitgliedschaft genehmigt', CPC2_TEXT_DOMAIN)));
}

// Reject membership request
add_action('wp_ajax_cpc_reject_membership', 'cpc_ajax_reject_membership');
function cpc_ajax_reject_membership() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => 'Sie müssen angemeldet sein'));
	}
	
	if (!isset($_POST['request_id']) || !isset($_POST['group_id'])) {
		wp_send_json_error(array('message' => 'Ungültige Parameter'));
	}
	
	$request_id = intval($_POST['request_id']);
	$group_id = intval($_POST['group_id']);
	$current_user_id = get_current_user_id();
	
	// Check if user is group admin
	if (!cpc_is_group_admin($current_user_id, $group_id)) {
		wp_send_json_error(array('message' => 'Du hast keine Berechtigung, diese Anfrage abzulehnen'));
	}
	
	// Get the request
	$request = get_post($request_id);
	if (!$request || $request->post_type !== 'cpc_group_members') {
		wp_send_json_error(array('message' => 'Anfrage nicht gefunden'));
	}
	
	// Get user ID before deleting
	$user_id = get_post_meta($request_id, 'cpc_member_user_id', true);
	
	// Delete the request
	wp_delete_post($request_id, true);
	
	// Fire action for other plugins
	do_action('cpc_membership_rejected', $user_id, $group_id);
	
	wp_send_json_success(array('message' => __('Anfrage abgelehnt', CPC2_TEXT_DOMAIN)));
}

// Toggle group forum
add_action('wp_ajax_cpc_toggle_group_forum', 'cpc_ajax_toggle_group_forum');
function cpc_ajax_toggle_group_forum() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}
	
	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$enable_forum = isset($_POST['enable_forum']) && $_POST['enable_forum'] === 'true';
	$forum_visibility = isset($_POST['forum_visibility']) ? sanitize_text_field($_POST['forum_visibility']) : 'group_only';
	$current_user_id = get_current_user_id();
	
	error_log('DEBUG: cpc_toggle_group_forum - group_id='.$group_id.', enable='.$enable_forum.', visibility='.$forum_visibility);
	
	// Check if user is group admin
	if (!cpc_is_group_admin($current_user_id, $group_id)) {
		wp_send_json_error(array('message' => __('Du hast keine Berechtigung dazu.', CPC2_TEXT_DOMAIN)));
	}
	
	// Check if forum module is active
	if (!function_exists('cpc_forum_page')) {
		wp_send_json_error(array('message' => __('Forum-Modul ist nicht aktiviert.', CPC2_TEXT_DOMAIN)));
	}
	
	$group = get_post($group_id);
	if (!$group || $group->post_type !== 'cpc_group') {
		wp_send_json_error(array('message' => __('Gruppe nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}
	
	if ($enable_forum) {
		// Enable forum - create if doesn't exist
		$forum_slug = get_post_meta($group_id, 'cpc_group_forum_slug', true);
		error_log('DEBUG: Existing forum_slug='.$forum_slug);
		
		if (!$forum_slug) {
			// Create forum for this group
			$forum_slug = cpc_create_group_forum($group_id);
			error_log('DEBUG: Created forum_slug='.$forum_slug);
			if (is_wp_error($forum_slug)) {
				wp_send_json_error(array('message' => $forum_slug->get_error_message()));
			}
		}
		
		update_post_meta($group_id, 'cpc_group_has_forum', true);
		update_post_meta($group_id, 'cpc_group_forum_visibility', $forum_visibility);
		error_log('DEBUG: Updated meta - has_forum=true, visibility='.$forum_visibility);
		
		// Update forum visibility
		$term = get_term_by('slug', $forum_slug, 'cpc_forum');
		if ($term) {
			cpc_update_term_meta($term->term_id, 'cpc_forum_public', $forum_visibility === 'public');
		}
		
		wp_send_json_success(array(
			'message' => __('Forum erfolgreich aktiviert!', CPC2_TEXT_DOMAIN),
			'forum_slug' => $forum_slug,
			'reload' => true
		));
		
	} else {
		// Disable forum
		update_post_meta($group_id, 'cpc_group_has_forum', false);
		error_log('DEBUG: Disabled forum for group '.$group_id);
		
		wp_send_json_success(array(
			'message' => __('Forum deaktiviert', CPC2_TEXT_DOMAIN),
			'reload' => true
		));
	}
}
?>
