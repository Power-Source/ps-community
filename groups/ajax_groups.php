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
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;

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
	if (!cpc_is_group_member($user_id, $group_id, $current_blog_id)) {
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
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;

	if (!$group_id) {
		wp_send_json_error(array('message' => __('Ungültige Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	$group = get_post($group_id);
	if (!$group || $group->post_type != 'cpc_group') {
		wp_send_json_error(array('message' => __('Gruppe nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}

	// Check if already member
	if (cpc_is_group_member($user_id, $group_id, $current_blog_id)) {
		wp_send_json_error(array('message' => __('Du bist bereits Mitglied dieser Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	$group_type = get_post_meta($group_id, 'cpc_group_type', true);
	if (!$group_type) $group_type = 'public';

	// Determine status based on group type
	$status = 'active';
	if ($group_type == 'private') {
		$status = 'pending'; // Requires approval for private groups
	}

	$membership_id = cpc_add_group_member($user_id, $group_id, 'member', $status, $current_blog_id);

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
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;

	if (!$group_id) {
		wp_send_json_error(array('message' => __('Ungültige Gruppe.', CPC2_TEXT_DOMAIN)));
	}

	// Check if user is the creator/only admin
	$admins = cpc_get_group_admins($group_id);
	if (count($admins) == 1 && $admins[0]->ID == $user_id) {
		wp_send_json_error(array('message' => __('Du kannst die Gruppe nicht verlassen, da du der einzige Admin bist. Ernenne zuerst einen anderen Admin.', CPC2_TEXT_DOMAIN)));
	}

	$success = cpc_remove_group_member($user_id, $group_id, $current_blog_id);

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
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;

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
	cpc_add_group_member($user_id, $group_id, 'admin', 'active', $current_blog_id);

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
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;

	// Check if current user is admin or moderator
	if (!cpc_is_group_moderator($current_user_id, $group_id)) {
		wp_send_json_error(array('message' => __('Du hast keine Berechtigung dazu.', CPC2_TEXT_DOMAIN)));
	}

	// Can't remove self this way
	if ($member_id == $current_user_id) {
		wp_send_json_error(array('message' => __('Nutze die "Gruppe verlassen" Funktion.', CPC2_TEXT_DOMAIN)));
	}

	$success = cpc_remove_group_member($member_id, $group_id, $current_blog_id);

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

	// For chat we require full page load so PS-Chat assets/localization are present
	if ($tab === 'chat') {
		$redirect_url = add_query_arg('tab', 'chat', remove_query_arg('tab', cpc_get_group_link($group_id)));
		wp_send_json_success(array('redirect' => $redirect_url));
	}
	
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

// Post activity reply
add_action('wp_ajax_cpc_post_activity_reply', 'cpc_ajax_post_activity_reply');
function cpc_ajax_post_activity_reply() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}
	
	$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
	$reply_content = isset($_POST['reply_content']) ? wp_kses_post($_POST['reply_content']) : '';
	$user_id = get_current_user_id();
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;
	
	if (!$post_id) {
		wp_send_json_error(array('message' => __('Ungültiger Post.', CPC2_TEXT_DOMAIN)));
	}
	
	if (!$reply_content) {
		wp_send_json_error(array('message' => __('Antwort kann nicht leer sein.', CPC2_TEXT_DOMAIN)));
	}
	
	// Get the activity post
	$post = get_post($post_id);
	if (!$post || $post->post_type !== 'cpc_activity') {
		wp_send_json_error(array('message' => __('Post nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}
	
	// Get the group
	$group_id = get_post_meta($post_id, 'cpc_activity_group_id', true);
	if (!$group_id) {
		wp_send_json_error(array('message' => __('Gruppe nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}
	
	// Check if user is group member
	if (!cpc_is_group_member($user_id, $group_id, $current_blog_id)) {
		wp_send_json_error(array('message' => __('Du musst Mitglied der Gruppe sein um zu antworten.', CPC2_TEXT_DOMAIN)));
	}
	
	// Add comment as reply
	$comment_id = wp_insert_comment(array(
		'comment_post_ID' => $post_id,
		'comment_author' => '',
		'comment_author_email' => '',
		'comment_author_url' => '',
		'comment_content' => $reply_content,
		'user_id' => $user_id,
		'comment_approved' => 1,
	));
	
	if ($comment_id) {
		// Generate HTML for the new reply
		$comment = get_comment($comment_id);
		$reply_user = get_userdata($user_id);
		
		$reply_html = '<div class="cpc-activity-reply">';
		$reply_html .= '<span class="cpc-activity-reply-author">'.$reply_user->display_name.'</span>';
		$reply_html .= '<span class="cpc-activity-reply-time">'.__('gerade eben', CPC2_TEXT_DOMAIN).'</span>';
		$reply_html .= '<div>'.$reply_content.'</div>';
		$reply_html .= '</div>';
		
		wp_send_json_success(array(
			'message' => __('Antwort erfolgreich gepostet!', CPC2_TEXT_DOMAIN),
			'comment_id' => $comment_id,
			'reply_html' => $reply_html
		));
	} else {
		wp_send_json_error(array('message' => __('Fehler beim Posten der Antwort.', CPC2_TEXT_DOMAIN)));
	}
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
		
		if (!$forum_slug) {
			// Create forum for this group
			$forum_slug = cpc_create_group_forum($group_id);
			if (is_wp_error($forum_slug)) {
				wp_send_json_error(array('message' => $forum_slug->get_error_message()));
			}
		}
		
		update_post_meta($group_id, 'cpc_group_has_forum', true);
		update_post_meta($group_id, 'cpc_group_forum_visibility', $forum_visibility);
		
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
		
		wp_send_json_success(array(
			'message' => __('Forum deaktiviert', CPC2_TEXT_DOMAIN),
			'reload' => true
		));
	}
}

// Save group permissions
add_action('wp_ajax_cpc_save_group_permissions', 'cpc_ajax_save_group_permissions');
function cpc_ajax_save_group_permissions() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}
	
	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$current_user_id = get_current_user_id();
	
	// Debug
	$is_admin = cpc_is_group_admin($current_user_id, $group_id);
	$role = cpc_get_group_member_role($current_user_id, $group_id);
	error_log("DEBUG save_permissions: user_id=$current_user_id, group_id=$group_id, is_admin=$is_admin, role=$role");
	
	// Check if user is group admin (or WordPress admin as fallback)
	if (!cpc_is_group_admin($current_user_id, $group_id) && !current_user_can('manage_options')) {
		wp_send_json_error(array('message' => __('Du hast keine Berechtigung dazu.', CPC2_TEXT_DOMAIN)));
	}
	
	$permissions = array(
		'forum_post' => isset($_POST['forum_post']) ? sanitize_text_field($_POST['forum_post']) : 'member',
		'invite_members' => isset($_POST['invite_members']) ? sanitize_text_field($_POST['invite_members']) : 'member',
		'activity_edit_all' => isset($_POST['activity_edit_all']) ? sanitize_text_field($_POST['activity_edit_all']) : 'moderator',
		'activity_delete_all' => isset($_POST['activity_delete_all']) ? sanitize_text_field($_POST['activity_delete_all']) : 'moderator',
	);
	
	update_post_meta($group_id, 'cpc_group_permissions', $permissions);
	
	wp_send_json_success(array('message' => __('Berechtigungen erfolgreich gespeichert!', CPC2_TEXT_DOMAIN)));
}

// Load invite modal
add_action('wp_ajax_cpc_group_invite_modal', 'cpc_ajax_group_invite_modal');
function cpc_ajax_group_invite_modal() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}
	if (!defined('CPC_CORE_PLUGINS') || strpos(CPC_CORE_PLUGINS, 'core-friendships') === false) {
		wp_send_json_error(array('message' => __('Freundschaften sind nicht aktiviert.', CPC2_TEXT_DOMAIN)));
	}
	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	if (!$group_id) {
		wp_send_json_error(array('message' => __('Ungültige Gruppe.', CPC2_TEXT_DOMAIN)));
	}
	if (!function_exists('cpc_can_invite_members') || !cpc_can_invite_members(get_current_user_id(), $group_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung zum Einladen.', CPC2_TEXT_DOMAIN)));
	}

	$friends = function_exists('cpc_get_friends') ? cpc_get_friends(get_current_user_id(), true) : array();
	$html = '<style>.cpc-group-invite-modal-overlay{position:fixed;z-index:9999;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.45);display:flex;align-items:center;justify-content:center;padding:20px;}';
	$html .= '.cpc-group-invite-modal{background:#fff;border-radius:8px;max-width:500px;width:100%;padding:20px;position:relative;box-shadow:0 10px 40px rgba(0,0,0,0.2);}';
	$html .= '.cpc-group-invite-close{position:absolute;top:10px;right:12px;border:none;background:transparent;font-size:20px;cursor:pointer;}';
	$html .= '.cpc-group-invite-list{max-height:240px;overflow:auto;border:1px solid #e5e5e5;padding:10px;margin-bottom:12px;}';
	$html .= '.cpc-group-invite-row{display:block;margin:6px 0;font-weight:600;}';
	$html .= '.cpc-group-invite-form textarea{width:100%;min-height:80px;margin:6px 0 12px;}';
	$html .= '.cpc-group-invite-modal h3{margin-top:0;}';
	$html .= '</style>';
	$html .= '<div class="cpc-group-invite-modal-overlay"><div class="cpc-group-invite-modal"><button type="button" class="cpc-group-invite-close">×</button>';
	$html .= '<h3>'.__('Freunde einladen', CPC2_TEXT_DOMAIN).'</h3>';
	if (empty($friends)) {
		$html .= '<p>'.__('Du hast noch keine Freunde.', CPC2_TEXT_DOMAIN).'</p>';
	} else {
		$html .= '<form class="cpc-group-invite-form" data-group-id="'.$group_id.'">';
		$html .= '<div class="cpc-group-invite-list">';
		foreach ($friends as $friend) {
			$user = get_userdata($friend['ID']);
			if (!$user) continue;
			$html .= '<label class="cpc-group-invite-row">';
			$html .= '<input type="checkbox" name="friend_ids[]" value="'.$user->ID.'"> ';
			$html .= esc_html($user->display_name);
			$html .= '</label>';
		}
		$html .= '</div>';
		$default_msg = __('Hey, komm in unsere Gruppe – würde mich freuen, wenn du dabei bist!', CPC2_TEXT_DOMAIN);
		$html .= '<label>'.__('Nachricht (optional)', CPC2_TEXT_DOMAIN).'</label>';
		$html .= '<textarea name="invite_message" maxlength="300" placeholder="'.$default_msg.'"></textarea>';
		$html .= '<button type="submit" class="cpc-btn cpc-btn-primary">'.__('Einladungen senden', CPC2_TEXT_DOMAIN).'</button>';
		$html .= '</form>';
	}
	$html .= '</div></div>';

	wp_send_json_success(array('html' => $html));
}

// Send invites
add_action('wp_ajax_cpc_group_send_invites', 'cpc_ajax_group_send_invites');
function cpc_ajax_group_send_invites() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}
	if (!defined('CPC_CORE_PLUGINS') || strpos(CPC_CORE_PLUGINS, 'core-friendships') === false) {
		wp_send_json_error(array('message' => __('Freundschaften sind nicht aktiviert.', CPC2_TEXT_DOMAIN)));
	}
	$group_id = isset($_POST['group_id']) ? intval($_POST['group_id']) : 0;
	$friend_ids = isset($_POST['friend_ids']) ? (array) $_POST['friend_ids'] : array();
	$message = isset($_POST['invite_message']) ? wp_kses_post(wp_trim_words($_POST['invite_message'], 30, '...')) : '';
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;
	
	if (!$group_id || empty($friend_ids)) {
		wp_send_json_error(array('message' => __('Bitte wähle mindestens einen Freund aus.', CPC2_TEXT_DOMAIN)));
	}
	if (!function_exists('cpc_can_invite_members') || !cpc_can_invite_members(get_current_user_id(), $group_id)) {
		wp_send_json_error(array('message' => __('Keine Berechtigung zum Einladen.', CPC2_TEXT_DOMAIN)));
	}

	$sent = 0;
	$group = get_post($group_id);
	$group_link = function_exists('cpc_get_group_link') ? cpc_get_group_link($group_id) : get_permalink($group_id);
	$invite_link = add_query_arg(array('tab' => 'overview'), $group_link);
	$default_msg = __('Hey, komm in unsere Gruppe – würde mich freuen, wenn du dabei bist!', CPC2_TEXT_DOMAIN);
	if (!$message) $message = $default_msg;

	foreach ($friend_ids as $fid) {
		$fid = intval($fid);
		if (!$fid) continue;
		if (cpc_is_group_member($fid, $group_id, $current_blog_id)) continue;
		$existing = cpc_get_membership_request($group_id, $fid, $current_blog_id);
		if ($existing) continue;

		$membership_id = cpc_add_group_member($fid, $group_id, 'member', 'pending', $current_blog_id);
		if (!$membership_id) continue;
		update_post_meta($membership_id, 'cpc_member_invited_by', get_current_user_id());
		update_post_meta($membership_id, 'cpc_member_invite_message', $message);

		// Alert to invited user
		if (function_exists('cpc_com_insert_alert')) {
			$subject = sprintf(__('Einladung zur Gruppe %s', CPC2_TEXT_DOMAIN), $group ? $group->post_title : '');
			$subject = get_bloginfo('name').': '.$subject;
			$content = apply_filters('cpc_alert_before', '');
			$content .= '<p>'.esc_html($message).'</p>';
			$content .= '<p><a href="'.$invite_link.'">'.$invite_link.'</a></p>';
			$content = apply_filters('cpc_alert_after', $content);
			cpc_com_insert_alert('group_invite', $subject, $content, get_current_user_id(), $fid, '', $invite_link, $message, 'pending', '');
		}
		$sent++;
	}

	if ($sent === 0) {
		wp_send_json_error(array('message' => __('Keine Einladungen gesendet (bereits Mitglied oder Anfrage offen).', CPC2_TEXT_DOMAIN)));
	}

	wp_send_json_success(array('message' => sprintf(__('%d Einladung(en) gesendet.', CPC2_TEXT_DOMAIN), $sent)));
}

// Edit activity post
add_action('wp_ajax_cpc_edit_activity', 'cpc_ajax_edit_activity');
function cpc_ajax_edit_activity() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}
	
	$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
	$content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
	$current_user_id = get_current_user_id();
	
	if (!$post_id || !$content) {
		wp_send_json_error(array('message' => __('Ungültige Daten.', CPC2_TEXT_DOMAIN)));
	}
	
	$post = get_post($post_id);
	if (!$post || $post->post_type !== 'cpc_activity') {
		wp_send_json_error(array('message' => __('Beitrag nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}
	
	$group_id = get_post_meta($post_id, 'cpc_activity_group_id', true);
	
	// Check permission
	if ($post->post_author != $current_user_id && !cpc_can_moderate_activity($current_user_id, $group_id, 'edit')) {
		wp_send_json_error(array('message' => __('Keine Berechtigung zum Bearbeiten.', CPC2_TEXT_DOMAIN)));
	}
	
	wp_update_post(array(
		'ID' => $post_id,
		'post_content' => $content
	));
	
	wp_send_json_success(array(
		'message' => __('Beitrag erfolgreich bearbeitet!', CPC2_TEXT_DOMAIN),
		'content' => $content
	));
}

// Delete activity post
add_action('wp_ajax_cpc_delete_activity', 'cpc_ajax_delete_activity');
function cpc_ajax_delete_activity() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}
	
	$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
	$current_user_id = get_current_user_id();
	
	if (!$post_id) {
		wp_send_json_error(array('message' => __('Ungültige Daten.', CPC2_TEXT_DOMAIN)));
	}
	
	$post = get_post($post_id);
	if (!$post || $post->post_type !== 'cpc_activity') {
		wp_send_json_error(array('message' => __('Beitrag nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}
	
	$group_id = get_post_meta($post_id, 'cpc_activity_group_id', true);
	
	// Check permission
	if ($post->post_author != $current_user_id && !cpc_can_moderate_activity($current_user_id, $group_id, 'delete')) {
		wp_send_json_error(array('message' => __('Keine Berechtigung zum Löschen.', CPC2_TEXT_DOMAIN)));
	}
	
	wp_delete_post($post_id, true);
	
	wp_send_json_success(array('message' => __('Beitrag erfolgreich gelöscht!', CPC2_TEXT_DOMAIN)));
}

// Edit reply
add_action('wp_ajax_cpc_edit_reply', 'cpc_ajax_edit_reply');
function cpc_ajax_edit_reply() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}
	
	$comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
	$content = isset($_POST['content']) ? wp_kses_post($_POST['content']) : '';
	$current_user_id = get_current_user_id();
	
	if (!$comment_id || !$content) {
		wp_send_json_error(array('message' => __('Ungültige Daten.', CPC2_TEXT_DOMAIN)));
	}
	
	$comment = get_comment($comment_id);
	if (!$comment) {
		wp_send_json_error(array('message' => __('Antwort nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}
	
	$post_id = $comment->comment_post_ID;
	$group_id = get_post_meta($post_id, 'cpc_activity_group_id', true);
	
	// Check permission
	if ($comment->user_id != $current_user_id && !cpc_can_moderate_activity($current_user_id, $group_id, 'edit')) {
		wp_send_json_error(array('message' => __('Keine Berechtigung zum Bearbeiten.', CPC2_TEXT_DOMAIN)));
	}
	
	wp_update_comment(array(
		'comment_ID' => $comment_id,
		'comment_content' => $content
	));
	
	wp_send_json_success(array(
		'message' => __('Antwort erfolgreich bearbeitet!', CPC2_TEXT_DOMAIN),
		'content' => $content
	));
}

// Delete reply
add_action('wp_ajax_cpc_delete_reply', 'cpc_ajax_delete_reply');
function cpc_ajax_delete_reply() {
	check_ajax_referer('cpc_groups_nonce', 'nonce');
	
	if (!is_user_logged_in()) {
		wp_send_json_error(array('message' => __('Du musst angemeldet sein.', CPC2_TEXT_DOMAIN)));
	}
	
	$comment_id = isset($_POST['comment_id']) ? intval($_POST['comment_id']) : 0;
	$current_user_id = get_current_user_id();
	
	if (!$comment_id) {
		wp_send_json_error(array('message' => __('Ungültige Daten.', CPC2_TEXT_DOMAIN)));
	}
	
	$comment = get_comment($comment_id);
	if (!$comment) {
		wp_send_json_error(array('message' => __('Antwort nicht gefunden.', CPC2_TEXT_DOMAIN)));
	}
	
	$post_id = $comment->comment_post_ID;
	$group_id = get_post_meta($post_id, 'cpc_activity_group_id', true);
	
	// Check permission
	if ($comment->user_id != $current_user_id && !cpc_can_moderate_activity($current_user_id, $group_id, 'delete')) {
		wp_send_json_error(array('message' => __('Keine Berechtigung zum Löschen.', CPC2_TEXT_DOMAIN)));
	}
	
	wp_delete_comment($comment_id, true);
	
	wp_send_json_success(array('message' => __('Antwort erfolgreich gelöscht!', CPC2_TEXT_DOMAIN)));
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
?>
