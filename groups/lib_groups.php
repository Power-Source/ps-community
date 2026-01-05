<?php

/* Library of group helper functions */

/**
 * Get group member count
 */
function cpc_get_group_member_count($group_id) {
	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => 'cpc_member_group_id',
				'value' => $group_id,
			),
			array(
				'key' => 'cpc_member_status',
				'value' => 'active',
			),
		),
	);
	$members = get_posts($args);
	return count($members);
}

/**
 * Update group member count (cached in post meta)
 */
function cpc_update_group_member_count($group_id) {
	$count = cpc_get_group_member_count($group_id);
	update_post_meta($group_id, 'cpc_group_member_count', $count);
	return $count;
}

/**
 * Check if user is member of group
 */
function cpc_is_group_member($user_id, $group_id) {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return false;

	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => 1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => 'cpc_member_user_id',
				'value' => $user_id,
			),
			array(
				'key' => 'cpc_member_group_id',
				'value' => $group_id,
			),
			array(
				'key' => 'cpc_member_status',
				'value' => 'active',
			),
		),
	);
	$membership = get_posts($args);
	return !empty($membership);
}

/**
 * Get user's role in group
 */
function cpc_get_group_member_role($user_id, $group_id) {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return false;

	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => 1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => 'cpc_member_user_id',
				'value' => $user_id,
			),
			array(
				'key' => 'cpc_member_group_id',
				'value' => $group_id,
			),
		),
	);
	$membership = get_posts($args);
	if (!empty($membership)) {
		return get_post_meta($membership[0]->ID, 'cpc_member_role', true);
	}
	
	// Fallback: Check if user is group creator/author
	$group = get_post($group_id);
	if ($group && $group->post_author == $user_id) {
		return 'admin';
	}
	
	return false;
}

/**
 * Check if user is group admin
 */
function cpc_is_group_admin($user_id, $group_id) {
	if (!$user_id) $user_id = get_current_user_id();
	$role = cpc_get_group_member_role($user_id, $group_id);
	return $role === 'admin';
}

/**
 * Check if user is group moderator or admin
 */
function cpc_is_group_moderator($user_id, $group_id) {
	if (!$user_id) $user_id = get_current_user_id();
	$role = cpc_get_group_member_role($user_id, $group_id);
	return in_array($role, array('admin', 'moderator'));
}

/**
 * Check if user can view group
 */
function cpc_can_view_group($user_id, $group_id) {
	if (!$user_id) $user_id = get_current_user_id();
	
	$group_type = get_post_meta($group_id, 'cpc_group_type', true);
	if (!$group_type) $group_type = 'public';

	// Public groups can be viewed by anyone
	if ($group_type == 'public') return true;

	// Private groups can be viewed by anyone (but not joined)
	if ($group_type == 'private') return true;

	// Hidden groups can only be viewed by members
	if ($group_type == 'hidden') {
		return cpc_is_group_member($user_id, $group_id);
	}

	return true;
}

/**
 * Add user to group
 */
function cpc_add_group_member($user_id, $group_id, $role = 'member', $status = 'active') {
	// Check if already member
	if (cpc_is_group_member($user_id, $group_id)) {
		return false;
	}

	$user = get_user_by('id', $user_id);
	$group = get_post($group_id);

	if (!$user || !$group) return false;

	// Create membership post
	$membership_id = wp_insert_post(array(
		'post_title' => $user->display_name . ' - ' . $group->post_title,
		'post_type' => 'cpc_group_members',
		'post_status' => 'publish',
		'post_author' => $user_id,
	));

	if ($membership_id) {
		update_post_meta($membership_id, 'cpc_member_user_id', $user_id);
		update_post_meta($membership_id, 'cpc_member_group_id', $group_id);
		update_post_meta($membership_id, 'cpc_member_role', $role);
		update_post_meta($membership_id, 'cpc_member_status', $status);
		update_post_meta($membership_id, 'cpc_member_joined', current_time('timestamp'));

		// Update group member count
		cpc_update_group_member_count($group_id);

		// Update group activity
		update_post_meta($group_id, 'cpc_group_updated', current_time('timestamp'));

		do_action('cpc_user_joined_group', $user_id, $group_id, $membership_id);

		return $membership_id;
	}

	return false;
}

/**
 * Remove user from group
 */
function cpc_remove_group_member($user_id, $group_id) {
	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => 1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => 'cpc_member_user_id',
				'value' => $user_id,
			),
			array(
				'key' => 'cpc_member_group_id',
				'value' => $group_id,
			),
		),
	);
	$membership = get_posts($args);

	if (!empty($membership)) {
		wp_delete_post($membership[0]->ID, true);
		
		// Update group member count
		cpc_update_group_member_count($group_id);

		// Update group activity
		update_post_meta($group_id, 'cpc_group_updated', current_time('timestamp'));

		do_action('cpc_user_left_group', $user_id, $group_id);

		return true;
	}

	return false;
}

/**
 * Get user's groups
 */
function cpc_get_user_groups($user_id, $status = 'active') {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return array();

	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => 'cpc_member_user_id',
				'value' => $user_id,
			),
		),
	);

	if ($status) {
		$args['meta_query'][] = array(
			'key' => 'cpc_member_status',
			'value' => $status,
		);
	}

	$memberships = get_posts($args);
	$groups = array();

	foreach ($memberships as $membership) {
		$group_id = get_post_meta($membership->ID, 'cpc_member_group_id', true);
		if ($group_id) {
			$group = get_post($group_id);
			if ($group && $group->post_status == 'publish') {
				$groups[] = $group;
			}
		}
	}

	return $groups;
}

/**
 * Get group members
 */
function cpc_get_group_members($group_id, $status = 'active', $role = '') {
	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => 'cpc_member_group_id',
				'value' => $group_id,
			),
		),
	);

	if ($status) {
		$args['meta_query'][] = array(
			'key' => 'cpc_member_status',
			'value' => $status,
		);
	}

	if ($role) {
		$args['meta_query'][] = array(
			'key' => 'cpc_member_role',
			'value' => $role,
		);
	}

	$memberships = get_posts($args);
	$members = array();

	foreach ($memberships as $membership) {
		$user_id = get_post_meta($membership->ID, 'cpc_member_user_id', true);
		if ($user_id) {
			$user = get_user_by('id', $user_id);
			if ($user) {
				$user->membership_id = $membership->ID;
				$user->member_role = get_post_meta($membership->ID, 'cpc_member_role', true);
				$user->member_joined = get_post_meta($membership->ID, 'cpc_member_joined', true);
				$members[] = $user;
			}
		}
	}
	
	// Fallback: If no members found, add the group creator as admin
	if (empty($members)) {
		$group = get_post($group_id);
		if ($group && $group->post_author) {
			$creator = get_user_by('id', $group->post_author);
			if ($creator) {
				$creator->membership_id = 0;
				$creator->member_role = 'admin';
				$creator->member_joined = strtotime($group->post_date);
				$members[] = $creator;
			}
		}
	}

	return $members;
}

/**
 * Get group admins
 */
function cpc_get_group_admins($group_id) {
	return cpc_get_group_members($group_id, 'active', 'admin');
}

/**
 * Update group activity timestamp
 */
function cpc_update_group_activity($group_id) {
	update_post_meta($group_id, 'cpc_group_updated', current_time('timestamp'));
}

/**
 * Get group type label
 */
function cpc_get_group_type_label($type) {
	$labels = array(
		'public' => __('Öffentlich', CPC2_TEXT_DOMAIN),
		'private' => __('Privat', CPC2_TEXT_DOMAIN),
		'hidden' => __('Versteckt', CPC2_TEXT_DOMAIN),
	);
	return isset($labels[$type]) ? $labels[$type] : $type;
}

/**
 * Get groups by type
 */
function cpc_get_groups_by_type($type = 'public', $limit = -1) {
	$args = array(
		'post_type' => 'cpc_group',
		'posts_per_page' => $limit,
		'post_status' => 'publish',
		'orderby' => 'title',
		'order' => 'ASC',
	);

	if ($type && $type != 'all') {
		$args['meta_query'] = array(
			array(
				'key' => 'cpc_group_type',
				'value' => $type,
			),
		);
	}

	return get_posts($args);
}

/**
 * Search groups
 */
function cpc_search_groups($search_term, $type = '') {
	$args = array(
		'post_type' => 'cpc_group',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		's' => $search_term,
	);

	if ($type) {
		$args['meta_query'] = array(
			array(
				'key' => 'cpc_group_type',
				'value' => $type,
			),
		);
	}

	return get_posts($args);
}

/**
 * Get pending membership requests for a group
 */
function cpc_get_pending_membership_requests($group_id) {
	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'meta_query' => array(
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
	
	return get_posts($args);
}

/**
 * Reject membership request
 */
function cpc_reject_membership_request($request_id) {
	wp_delete_post($request_id, true);
}

/**
 * Get membership request by user and group
 */
function cpc_get_membership_request($group_id, $user_id) {
	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => 1,
		'post_status' => 'publish',
		'meta_query' => array(
			array(
				'key' => 'cpc_member_user_id',
				'value' => $user_id,
			),
			array(
				'key' => 'cpc_member_group_id',
				'value' => $group_id,
			),
		),
	);
	
	$results = get_posts($args);
	return !empty($results) ? $results[0] : null;
}

/**
 * Get link to group single page
 */
function cpc_get_group_link($group_id) {
	$group_page_id = get_option('cpccom_group_single_page');
	
	if (!$group_page_id) {
		// Fallback to CPT permalink if no page is configured
		return get_permalink($group_id);
	}
	
	$group = get_post($group_id);
	if (!$group) return '';
	
	$group_page = get_post($group_page_id);
	if (!$group_page) return get_permalink($group_id);
	
	// Always use the configured page as base and pass group_name as query arg.
	// Avoid pretty path segments because no rewrite rule maps them to the group page.
	$page_url = get_permalink($group_page_id);
	return add_query_arg('group_name', $group->post_name, $page_url);
}

/**
 * Create forum for group
 */
function cpc_create_group_forum($group_id) {
	// Check if taxonomy exists
	if (!taxonomy_exists('cpc_forum')) {
		return new WP_Error('forum_not_available', __('Forum-Funktion ist nicht verfügbar', CPC2_TEXT_DOMAIN));
	}
	
	$group = get_post($group_id);
	if (!$group || $group->post_type !== 'cpc_group') {
		return new WP_Error('invalid_group', __('Ungültige Gruppe', CPC2_TEXT_DOMAIN));
	}
	
	// Generate forum slug from group name
	$forum_slug = 'group-' . $group->post_name;
	
	// Check if forum already exists
	$existing_term = get_term_by('slug', $forum_slug, 'cpc_forum');
	if ($existing_term) {
		// Update meta to link to group
		cpc_update_term_meta($existing_term->term_id, 'cpc_forum_group_id', $group_id);
		update_post_meta($group_id, 'cpc_group_forum_slug', $forum_slug);
		return $forum_slug;
	}
	
	// Create new forum
	$forum_name = $group->post_title . ' Forum';
	$result = wp_insert_term(
		$forum_name,
		'cpc_forum',
		array(
			'slug' => $forum_slug,
			'description' => sprintf(__('Diskussionsforum für die Gruppe %s', CPC2_TEXT_DOMAIN), $group->post_title),
		)
	);
	
	if (is_wp_error($result)) {
		return $result;
	}
	
	$term_id = $result['term_id'];
	
	// Set forum meta
	$forum_visibility = get_post_meta($group_id, 'cpc_group_forum_visibility', true);
	if (!$forum_visibility) $forum_visibility = 'group_only';
	
	cpc_update_term_meta($term_id, 'cpc_forum_public', $forum_visibility === 'public');
	cpc_update_term_meta($term_id, 'cpc_forum_group_id', $group_id);
	cpc_update_term_meta($term_id, 'cpc_forum_order', 999); // Low priority in forum list
	
	// Save forum slug to group
	update_post_meta($group_id, 'cpc_group_forum_slug', $forum_slug);
	
	return $forum_slug;
}

/**
 * Check if user can moderate activity (edit/delete posts)
 */
function cpc_can_moderate_activity($user_id, $group_id, $action = 'edit') {
	if (!$user_id) return false;
	
	// Get user's role in group
	$role = cpc_get_group_member_role($user_id, $group_id);
	if (!$role) return false;
	
	// Get group permissions
	$permissions = get_post_meta($group_id, 'cpc_group_permissions', true);
	if (!is_array($permissions)) {
		$permissions = array(
			'activity_edit_all' => 'moderator',
			'activity_delete_all' => 'moderator',
		);
	}
	
	$permission_key = 'activity_' . $action . '_all';
	$required_role = isset($permissions[$permission_key]) ? $permissions[$permission_key] : 'moderator';
	
	// Check if user's role meets requirement
	if ($required_role === 'admin') {
		return $role === 'admin';
	} elseif ($required_role === 'moderator') {
		return in_array($role, array('admin', 'moderator'));
	}
	
	return false;
}

/**
 * Check if user can invite members to group
 */
function cpc_can_invite_members($user_id, $group_id) {
	if (!$user_id) return false;
	$role = cpc_get_group_member_role($user_id, $group_id);
	if (!$role) return false;

	$permissions = get_post_meta($group_id, 'cpc_group_permissions', true);
	if (!is_array($permissions)) {
		$permissions = array('invite_members' => 'member');
	}
	$required_role = isset($permissions['invite_members']) ? $permissions['invite_members'] : 'member';

	if ($required_role === 'admin') {
		return $role === 'admin';
	} elseif ($required_role === 'moderator') {
		return in_array($role, array('admin', 'moderator'));
	}
	return in_array($role, array('admin', 'moderator', 'member'));
}
?>
