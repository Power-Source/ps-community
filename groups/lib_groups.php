<?php

/* Library of group helper functions */

function cpc_groups_fast_query_args($args, $ids_only = false) {
	$defaults = array(
		'no_found_rows' => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'cache_results' => false,
		'suppress_filters' => true,
		'ignore_sticky_posts' => true,
	);
	if ($ids_only) {
		$defaults['fields'] = 'ids';
	}
	return wp_parse_args($args, $defaults);
}

/**
 * Get group member count
 */
function cpc_get_group_member_count($group_id, $blog_id = null) {
	// Multisite support
	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}
	
	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => 1000,  // FIXED: Reasonable limit
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
	$args = cpc_groups_fast_query_args($args, true);
	$members = get_posts($args);
	$count = count($members);
	
	if ($switched) {
		restore_current_blog();
	}
	
	return $count;
}

/**
 * Update group member count (cached in post meta)
 */
function cpc_update_group_member_count($group_id, $blog_id = null) {
	$count = cpc_get_group_member_count($group_id, $blog_id);
	update_post_meta($group_id, 'cpc_group_member_count', $count);
	return $count;
}

/**
 * Get all membership IDs for a user/group pair.
 */
function cpc_get_group_membership_ids($user_id, $group_id, $status = null, $blog_id = null) {
	$user_id = (int) $user_id;
	$group_id = (int) $group_id;
	if (!$user_id || !$group_id) return array();

	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}

	$meta_query = array(
		array(
			'key' => 'cpc_member_user_id',
			'value' => $user_id,
			'type' => 'NUMERIC',
		),
		array(
			'key' => 'cpc_member_group_id',
			'value' => $group_id,
			'type' => 'NUMERIC',
		),
	);

	if ($status !== null) {
		$meta_query[] = array(
			'key' => 'cpc_member_status',
			'value' => $status,
		);
	}

	$membership_ids = get_posts(cpc_groups_fast_query_args(array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => -1,
		'post_status' => 'publish',
		'orderby' => 'ID',
		'order' => 'DESC',
		'meta_query' => $meta_query,
	), true));

	if ($switched) {
		restore_current_blog();
	}

	return array_map('absint', $membership_ids);
}

/**
 * Choose the effective record when legacy data contains duplicate memberships.
 */
function cpc_get_preferred_group_membership_id($membership_ids) {
	$status_priority = array('pending' => 1, 'banned' => 2, 'active' => 3);
	$role_priority = array('member' => 1, 'moderator' => 2, 'admin' => 3);
	$preferred_id = 0;
	$preferred_score = -1;

	foreach ($membership_ids as $membership_id) {
		$membership_id = (int) $membership_id;
		$status = get_post_meta($membership_id, 'cpc_member_status', true);
		$role = get_post_meta($membership_id, 'cpc_member_role', true);
		$score = (isset($status_priority[$status]) ? $status_priority[$status] : 0) * 10;
		$score += isset($role_priority[$role]) ? $role_priority[$role] : 0;

		if ($score > $preferred_score || ($score === $preferred_score && $membership_id > $preferred_id)) {
			$preferred_id = $membership_id;
			$preferred_score = $score;
		}
	}

	return $preferred_id;
}

/**
 * Check whether a user is the group author or explicitly stored creator.
 */
function cpc_is_group_creator($user_id, $group_id, $blog_id = null) {
	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}

	$group = get_post($group_id);
	$creator_id = (int) get_post_meta($group_id, 'cpc_group_creator', true);
	$is_creator = $group && ((int) $group->post_author === (int) $user_id || $creator_id === (int) $user_id);

	if ($switched) {
		restore_current_blog();
	}

	return (bool) $is_creator;
}

/**
 * Check if user is member of group
 */
function cpc_is_group_member($user_id, $group_id, $blog_id = null) {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return false;

	return !empty(cpc_get_group_membership_ids($user_id, $group_id, 'active', $blog_id));
}

/**
 * Get user's role in group
 */
function cpc_get_group_member_role($user_id, $group_id, $blog_id = null) {
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return false;

	// Multisite support
	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}

	$role = cpc_is_group_creator($user_id, $group_id) ? 'admin' : false;

	if (!$role) {
		$role_priority = array('member' => 1, 'moderator' => 2, 'admin' => 3);
		foreach (cpc_get_group_membership_ids($user_id, $group_id, 'active') as $membership_id) {
			$membership_role = get_post_meta($membership_id, 'cpc_member_role', true);
			if (isset($role_priority[$membership_role]) && (!$role || $role_priority[$membership_role] > $role_priority[$role])) {
				$role = $membership_role;
			}
		}
	}
	
	if ($switched) {
		restore_current_blog();
	}
	
	return $role;
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
function cpc_add_group_member($user_id, $group_id, $role = 'member', $status = 'active', $blog_id = null) {
	// Multisite support
	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}
	
	$user = get_user_by('id', $user_id);
	$group = get_post($group_id);

	if (!$user || !$group) {
		if ($switched) restore_current_blog();
		return false;
	}

	$valid_roles = array('member', 'moderator', 'admin');
	$valid_statuses = array('active', 'pending', 'banned');
	$role = in_array($role, $valid_roles, true) ? $role : 'member';
	$status = in_array($status, $valid_statuses, true) ? $status : 'active';
	$membership_ids = cpc_get_group_membership_ids($user_id, $group_id);

	if (!empty($membership_ids)) {
		$membership_id = cpc_get_preferred_group_membership_id($membership_ids);
		$current_role = get_post_meta($membership_id, 'cpc_member_role', true);
		$current_status = get_post_meta($membership_id, 'cpc_member_status', true);
		$is_creator = cpc_is_group_creator($user_id, $group_id);

		if ($current_status === 'banned' && !$is_creator) {
			if ($switched) restore_current_blog();
			return false;
		}

		$role_priority = array('member' => 1, 'moderator' => 2, 'admin' => 3);
		if (isset($role_priority[$current_role]) && $role_priority[$current_role] > $role_priority[$role]) {
			$role = $current_role;
		}
		if ($current_status === 'active') {
			$status = 'active';
		}
		if ($is_creator) {
			$role = 'admin';
			$status = 'active';
		}

		update_post_meta($membership_id, 'cpc_member_role', $role);
		update_post_meta($membership_id, 'cpc_member_status', $status);
		foreach ($membership_ids as $duplicate_id) {
			if ((int) $duplicate_id !== $membership_id) {
				wp_delete_post((int) $duplicate_id, true);
			}
		}
		cpc_update_group_member_count($group_id);

		if ($switched) restore_current_blog();
		return $membership_id;
	}

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

		if ($switched) restore_current_blog();
		return $membership_id;
	}

	if ($switched) restore_current_blog();
	return false;
}

/**
 * Remove user from group
 */
function cpc_remove_group_member($user_id, $group_id, $blog_id = null) {
	// Multisite support
	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}
	
	$membership_ids = cpc_get_group_membership_ids($user_id, $group_id);

	if (!empty($membership_ids)) {
		foreach ($membership_ids as $membership_id) {
			wp_delete_post((int) $membership_id, true);
		}
		
		// Update group member count
		cpc_update_group_member_count($group_id);

		// Update group activity
		update_post_meta($group_id, 'cpc_group_updated', current_time('timestamp'));

		do_action('cpc_user_left_group', $user_id, $group_id);

		if ($switched) restore_current_blog();
		return true;
	}

	if ($switched) restore_current_blog();
	return false;
}

/**
 * Get user's groups - OPTIMIZED: Reduces N+1 queries
 */
function cpc_get_user_groups($user_id, $status = 'active', $blog_id = null) {
	global $wpdb;
	
	if (!$user_id) $user_id = get_current_user_id();
	if (!$user_id) return array();

	// Multisite support
	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}

	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => 500,  // FIXED: Limit instead of -1
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

	$args = cpc_groups_fast_query_args($args, true);

	$memberships = get_posts($args);
	
	if (empty($memberships)) {
		if ($switched) {
			restore_current_blog();
		}
		return array();
	}

	// OPTIMIZATION: Load all group_ids in batch (1 query instead of N)
	$membership_ids = array_map('absint', $memberships);
	
	$meta_results = $wpdb->get_results(
		"SELECT post_id, meta_value FROM {$wpdb->postmeta} 
		WHERE post_id IN (" . implode(',', $membership_ids) . ") 
		AND meta_key = 'cpc_member_group_id'"
	);
	
	$group_ids = array();
	foreach ($meta_results as $row) {
		$gid = absint($row->meta_value);
		if ($gid > 0) {
			$group_ids[] = $gid;
		}
	}

	$groups = array();
	if (!empty($group_ids)) {
		// OPTIMIZATION: Load all groups in ONE query instead of N get_post() calls
		$groups = get_posts(cpc_groups_fast_query_args(array(
			'post_type' => 'cpc_group',
			'post_status' => 'publish',
			'posts_per_page' => -1,
			'include' => array_unique($group_ids),
		), false));
	}

	if ($switched) {
		restore_current_blog();
	}

	return $groups;
}

/**
 * Get group members - OPTIMIZED: Reduces N+1 queries
 */
function cpc_get_group_members($group_id, $status = 'active', $role = '', $blog_id = null) {
	global $wpdb;
	
	// Multisite support
	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}
	
	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => 500,  // FIXED: Limit instead of -1
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

	$args = cpc_groups_fast_query_args($args, true);

	$memberships = get_posts($args);
	$members = array();

	if (empty($memberships)) {
		// Fallback: If no members found, add the group creator as admin
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
		
		if ($switched) {
			restore_current_blog();
		}
		return $members;
	}

	// OPTIMIZATION: Load all metadata in batch instead of per-member queries
	$membership_ids = array_map('absint', $memberships);
	
	// Get all meta at once (1 query instead of N*3 queries)
	$meta_results = $wpdb->get_results(
		"SELECT post_id, meta_key, meta_value FROM {$wpdb->postmeta} 
		WHERE post_id IN (" . implode(',', $membership_ids) . ")"
	);
	
	// Build lookup table for metadata
	$meta_map = array();
	foreach ($meta_results as $row) {
		if (!isset($meta_map[$row->post_id])) {
			$meta_map[$row->post_id] = array();
		}
		$meta_map[$row->post_id][$row->meta_key] = $row->meta_value;
	}

	// Collect all user IDs first
	$user_ids = array();
	foreach ($memberships as $membership_id) {
		$membership_id = (int) $membership_id;
		$user_id = isset($meta_map[$membership_id]['cpc_member_user_id']) 
			? absint($meta_map[$membership_id]['cpc_member_user_id']) 
			: 0;
		if ($user_id > 0) {
			$user_ids[] = $user_id;
		}
	}

	// OPTIMIZATION: Load all users in ONE query instead of N get_user_by() calls
	$users_map = array();
	if (!empty($user_ids)) {
		$users = get_users(array(
			'include' => array_unique($user_ids),
			'number' => -1,
		));
		foreach ($users as $user_obj) {
			if (is_object($user_obj) && isset($user_obj->ID)) {
				$users_map[(int) $user_obj->ID] = $user_obj;
			} elseif (is_array($user_obj) && isset($user_obj['ID'])) {
				$users_map[(int) $user_obj['ID']] = (object) $user_obj;
			}
		}
	}

	// Build final members array with cached data
	foreach ($memberships as $membership_id) {
		$membership_id = (int) $membership_id;
		$user_id = isset($meta_map[$membership_id]['cpc_member_user_id']) 
			? absint($meta_map[$membership_id]['cpc_member_user_id']) 
			: 0;
		
		if ($user_id > 0 && isset($users_map[$user_id])) {
			$user = $users_map[$user_id];
			if (is_array($user)) {
				$user = (object) $user;
			}
			if (!is_object($user)) {
				continue;
			}
			$member_user = clone $user;
			$member_user->membership_id = $membership_id;
			$member_user->member_role = $meta_map[$membership_id]['cpc_member_role'] ?? 'member';
			$member_user->member_joined = $meta_map[$membership_id]['cpc_member_joined'] ?? '';
			$members[] = $member_user;
		}
	}

	if ($switched) {
		restore_current_blog();
	}

	return $members;
}

/**
 * Get group admins
 */
function cpc_get_group_admins($group_id, $blog_id = null) {
	return cpc_get_group_members($group_id, 'active', 'admin', $blog_id);
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
		'public' => __('Öffentlich', 'cp-community'),
		'private' => __('Privat', 'cp-community'),
		'hidden' => __('Versteckt', 'cp-community'),
	);
	return isset($labels[$type]) ? $labels[$type] : $type;
}

/**
 * Get groups by type
 */
function cpc_get_groups_by_type($type = 'public', $limit = -1, $blog_id = null) {
	// Multisite support
	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}
	
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

	$args = cpc_groups_fast_query_args($args, false);

	$groups = get_posts($args);
	
	if ($switched) {
		restore_current_blog();
	}
	
	return $groups;
}

/**
 * Search groups
 */
function cpc_search_groups($search_term, $type = '', $blog_id = null) {
	// Multisite support
	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}
	
	$args = array(
		'post_type' => 'cpc_group',
		'posts_per_page' => 100,
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

	$args = cpc_groups_fast_query_args($args, false);

	$groups = get_posts($args);
	
	if ($switched) {
		restore_current_blog();
	}
	
	return $groups;
}

/**
 * Get pending membership requests for a group
 */
function cpc_get_pending_membership_requests($group_id, $blog_id = null) {
	// Multisite support
	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}
	
	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => 500,
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
	$args = cpc_groups_fast_query_args($args, false);
	
	$requests = get_posts($args);
	
	if ($switched) {
		restore_current_blog();
	}
	
	return $requests;
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
function cpc_get_membership_request($group_id, $user_id, $blog_id = null) {
	// Multisite support
	$switched = false;
	if (is_multisite() && $blog_id && $blog_id != get_current_blog_id()) {
		switch_to_blog($blog_id);
		$switched = true;
	}
	
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
	$args = cpc_groups_fast_query_args($args, false);
	
	$results = get_posts($args);
	$result = !empty($results) ? $results[0] : null;
	
	if ($switched) {
		restore_current_blog();
	}
	
	return $result;
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
		return new WP_Error('forum_not_available', __('Forum-Funktion ist nicht verfügbar', 'cp-community'));
	}
	
	$group = get_post($group_id);
	if (!$group || $group->post_type !== 'cpc_group') {
		return new WP_Error('invalid_group', __('Ungültige Gruppe', 'cp-community'));
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
			'description' => sprintf(__('Diskussionsforum für die Gruppe %s', 'cp-community'), $group->post_title),
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
