<?php

/* Group Hooks and Filters */

/**
 * Add group activity to activity stream when enabled
 */
if (strpos(CPC_CORE_PLUGINS, 'core-activity') !== false):

	// Group created
	add_action('cpc_group_created', 'cpc_groups_activity_created', 10, 2);
	function cpc_groups_activity_created($group_id, $user_id) {
		if (function_exists('cpc_add_activity')) {
			$group = get_post($group_id);
			cpc_add_activity(
				$user_id,
				'group_created',
				sprintf(__('hat die Gruppe <a href="%s">%s</a> erstellt', CPC2_TEXT_DOMAIN), get_permalink($group_id), $group->post_title),
				$group_id
			);
		}
	}

	// User joined group
	add_action('cpc_user_joined_group', 'cpc_groups_activity_joined', 10, 3);
	function cpc_groups_activity_joined($user_id, $group_id, $membership_id) {
		if (function_exists('cpc_add_activity')) {
			$group = get_post($group_id);
			cpc_add_activity(
				$user_id,
				'group_joined',
				sprintf(__('ist der Gruppe <a href="%s">%s</a> beigetreten', CPC2_TEXT_DOMAIN), get_permalink($group_id), $group->post_title),
				$group_id
			);
		}
	}

	// User left group
	add_action('cpc_user_left_group', 'cpc_groups_activity_left', 10, 2);
	function cpc_groups_activity_left($user_id, $group_id) {
		if (function_exists('cpc_add_activity')) {
			$group = get_post($group_id);
			cpc_add_activity(
				$user_id,
				'group_left',
				sprintf(__('hat die Gruppe <a href="%s">%s</a> verlassen', CPC2_TEXT_DOMAIN), get_permalink($group_id), $group->post_title),
				$group_id
			);
		}
	}

endif;

/**
 * Add group alerts when enabled
 */
if (strpos(CPC_CORE_PLUGINS, 'core-alerts') !== false):

	// Alert when member approved
	add_action('cpc_member_approved', 'cpc_groups_alert_member_approved', 10, 2);
	function cpc_groups_alert_member_approved($user_id, $group_id) {
		if (function_exists('cpc_add_alert')) {
			$group = get_post($group_id);
			cpc_add_alert(
				$user_id,
				sprintf(__('Deine Beitrittsanfrage für die Gruppe <a href="%s">%s</a> wurde genehmigt!', CPC2_TEXT_DOMAIN), get_permalink($group_id), $group->post_title),
				'group_approved',
				$group_id
			);
		}
	}

	// Alert group admins of new join request (private groups)
	add_action('cpc_user_joined_group', 'cpc_groups_alert_join_request', 10, 3);
	function cpc_groups_alert_join_request($user_id, $group_id, $membership_id) {
		$status = get_post_meta($membership_id, 'cpc_member_status', true);
		if ($status == 'pending' && function_exists('cpc_add_alert')) {
			$group = get_post($group_id);
			$user = get_user_by('id', $user_id);
			$admins = cpc_get_group_admins($group_id);
			
			foreach ($admins as $admin) {
				cpc_add_alert(
					$admin->ID,
					sprintf(__('%s möchte der Gruppe <a href="%s">%s</a> beitreten', CPC2_TEXT_DOMAIN), $user->display_name, get_permalink($group_id), $group->post_title),
					'group_join_request',
					$group_id
				);
			}
		}
	}

endif;

/**
 * Update group activity timestamp on various actions
 */
add_action('cpc_user_joined_group', 'cpc_groups_update_activity_on_join', 10, 2);
function cpc_groups_update_activity_on_join($user_id, $group_id) {
	cpc_update_group_activity($group_id);
}

add_action('save_post_cpc_group', 'cpc_groups_update_activity_on_save', 10, 1);
function cpc_groups_update_activity_on_save($post_id) {
	if (wp_is_post_revision($post_id)) return;
	cpc_update_group_activity($post_id);
}

/**
 * Clean up memberships when group is deleted
 */
add_action('before_delete_post', 'cpc_groups_cleanup_on_delete');
function cpc_groups_cleanup_on_delete($post_id) {
	$post = get_post($post_id);
	if ($post && $post->post_type == 'cpc_group') {
		// Delete all memberships
		$args = array(
			'post_type' => 'cpc_group_members',
			'posts_per_page' => -1,
			'post_status' => 'any',
			'meta_query' => array(
				array(
					'key' => 'cpc_member_group_id',
					'value' => $post_id,
				),
			),
		);
		$memberships = get_posts($args);
		foreach ($memberships as $membership) {
			wp_delete_post($membership->ID, true);
		}
	}
}

/**
 * Clean up when user is deleted
 */
add_action('delete_user', 'cpc_groups_cleanup_user_memberships');
function cpc_groups_cleanup_user_memberships($user_id) {
	// Delete user's memberships
	$args = array(
		'post_type' => 'cpc_group_members',
		'posts_per_page' => -1,
		'post_status' => 'any',
		'meta_query' => array(
			array(
				'key' => 'cpc_member_user_id',
				'value' => $user_id,
			),
		),
	);
	$memberships = get_posts($args);
	foreach ($memberships as $membership) {
		$group_id = get_post_meta($membership->ID, 'cpc_member_group_id', true);
		wp_delete_post($membership->ID, true);
		if ($group_id) {
			cpc_update_group_member_count($group_id);
		}
	}
}

/**
 * Add groups to user profile
 */
add_filter('cpc_profile_tabs', 'cpc_groups_add_profile_tab', 10, 2);
function cpc_groups_add_profile_tab($tabs, $user_id) {
	$tabs['groups'] = __('Gruppen', CPC2_TEXT_DOMAIN);
	return $tabs;
}

add_filter('cpc_profile_tab_content', 'cpc_groups_profile_tab_content', 10, 2);
function cpc_groups_profile_tab_content($content, $args) {
	if ($args['tab'] == 'groups') {
		$user_id = $args['user_id'];
		$groups = cpc_get_user_groups($user_id);
		
		if ($groups) {
			$content = '<div class="cpc-user-groups">';
			$content .= '<h3>'.__('Mitglied in diesen Gruppen', CPC2_TEXT_DOMAIN).'</h3>';
			$content .= '<ul class="cpc-groups-list">';
			foreach ($groups as $group) {
				$content .= '<li><a href="'.get_permalink($group->ID).'">'.$group->post_title.'</a></li>';
			}
			$content .= '</ul>';
			$content .= '</div>';
		} else {
			$content = '<p>'.__('Noch kein Mitglied in einer Gruppe.', CPC2_TEXT_DOMAIN).'</p>';
		}
	}
	return $content;
}

/**
 * Custom body classes for group pages
 */
add_filter('body_class', 'cpc_groups_body_class');
function cpc_groups_body_class($classes) {
	if (is_singular('cpc_group')) {
		$classes[] = 'cpc-group-single';
		$group_type = get_post_meta(get_the_ID(), 'cpc_group_type', true);
		if ($group_type) {
			$classes[] = 'cpc-group-type-' . $group_type;
		}
	}
	if (is_post_type_archive('cpc_group')) {
		$classes[] = 'cpc-groups-archive';
	}
	return $classes;
}

/**
 * Modify group query for archive pages
 */
add_action('pre_get_posts', 'cpc_groups_archive_query');
function cpc_groups_archive_query($query) {
	if (!is_admin() && $query->is_main_query() && is_post_type_archive('cpc_group')) {
		// Hide hidden groups from non-members
		if (!is_user_logged_in()) {
			$query->set('meta_query', array(
				array(
					'key' => 'cpc_group_type',
					'value' => 'hidden',
					'compare' => '!=',
				),
			));
		}
		// Order by member count or title
		$query->set('orderby', 'title');
		$query->set('order', 'ASC');
	}
}

/**
 * Add group avatar support
 */
if (strpos(CPC_CORE_PLUGINS, 'core-avatar') !== false):

	add_filter('cpc_avatar_object_types', 'cpc_groups_add_avatar_object');
	function cpc_groups_add_avatar_object($types) {
		$types[] = 'group';
		return $types;
	}

	add_filter('cpc_avatar_group', 'cpc_groups_get_avatar', 10, 4);
	function cpc_groups_get_avatar($avatar, $object_id, $size, $args) {
		$group = get_post($object_id);
		if ($group && $group->post_type == 'cpc_group') {
			if (has_post_thumbnail($object_id)) {
				$avatar = get_the_post_thumbnail($object_id, array($size, $size));
			} else {
				$avatar = '<img src="'.plugins_url('images/group-avatar-default.png', __FILE__).'" width="'.$size.'" height="'.$size.'" />';
			}
		}
		return $avatar;
	}

endif;
?>
