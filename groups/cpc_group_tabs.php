<?php

/* Group Tabs Management */

/**
 * Get available tabs for a group
 */
function cpc_get_group_tabs($group_id, $user_id = 0) {
	if (!$user_id) $user_id = get_current_user_id();
	
	$tabs = array();
	
	// Overview tab - everyone
	$tabs['overview'] = array(
		'label' => __('Aktivität', CPC2_TEXT_DOMAIN),
		'icon' => 'comments',
		'priority' => 10,
	);
	
	// Forum tab - only if enabled for this group
	if (get_post_meta($group_id, 'cpc_group_has_forum', true)) {
		$tabs['forum'] = array(
			'label' => __('Forum', CPC2_TEXT_DOMAIN),
			'icon' => 'comments-alt',
			'priority' => 15,
		);
	}
	
	// Members tab - everyone
	$tabs['members'] = array(
		'label' => __('Mitglieder', CPC2_TEXT_DOMAIN),
		'icon' => 'users',
		'priority' => 20,
	);
	
	// Settings tab - only group admins
	if (cpc_is_group_admin($user_id, $group_id)):
		$tabs['settings'] = array(
			'label' => __('Einstellungen', CPC2_TEXT_DOMAIN),
			'icon' => 'cog',
			'priority' => 30,
		);
	endif;
	
	// Allow plugins to add tabs
	$tabs = apply_filters('cpc_group_tabs', $tabs, $group_id, $user_id);
	
	// Sort by priority (keep string keys!)
	uasort($tabs, function($a, $b) {
		return $a['priority'] - $b['priority'];
	});
	
	return $tabs;
}

/**
 * Render group tabs navigation
 */
function cpc_render_group_tabs($group_id, $active_tab = 'overview') {
	$tabs = cpc_get_group_tabs($group_id);

	// Always build from canonical group link so we keep group_name in query string
	$current_url = remove_query_arg('tab', cpc_get_group_link($group_id));
	
	$html = '<div class="cpc-group-tabs-nav">';
	$html .= '<ul class="cpc-group-tabs-list">';
	
	foreach ($tabs as $tab_id => $tab_data):
		$class = 'cpc-group-tab-item';
		if ($tab_id === $active_tab) $class .= ' active';
		
		$tab_url = add_query_arg('tab', $tab_id, $current_url);
		
		$html .= '<li class="'.$class.'">';
		$html .= '<a href="'.$tab_url.'" class="cpc-group-tab-link" data-tab="'.$tab_id.'">';
		$html .= $tab_data['label'];
		$html .= '</a>';
		$html .= '</li>';
	endforeach;
	
	$html .= '</ul>';
	$html .= '</div>';
	
	return $html;
}

/**
 * Render group tab content
 */
function cpc_render_group_tab_content($group_id, $active_tab = 'overview', $shortcode_atts = array()) {
	// Initialize groups JS/CSS for AJAX context
	if (function_exists('cpc_groups_init')) {
		cpc_groups_init();
	}
	
	$html = '';
	
	switch ($active_tab):
		case 'overview':
			$html .= cpc_render_group_tab_overview($group_id, $shortcode_atts);
			break;
		case 'forum':
			$html .= cpc_render_group_tab_forum($group_id, $shortcode_atts);
			break;
		case 'members':
			$html .= cpc_render_group_tab_members($group_id, $shortcode_atts);
			break;
		case 'settings':
			if (cpc_is_group_admin(get_current_user_id(), $group_id)):
				$html .= cpc_render_group_tab_settings($group_id, $shortcode_atts);
			else:
				$html .= '<p>Keine Berechtigung für Einstellungen</p>';
			endif;
			break;
		default:
			$html .= apply_filters('cpc_group_tab_content_'.$active_tab, '', $group_id, $shortcode_atts);
	endswitch;
	
	return $html;
}

/**
 * Render Overview Tab
 */
function cpc_render_group_tab_overview($group_id, $atts = array()) {
	global $wpdb;
	
	$html = '';
	$html .= '<div class="cpc-group-overview-tab">';
	
	// Post activity form first (top of tab) - only for members
	if (is_user_logged_in() && cpc_is_group_member(get_current_user_id(), $group_id)):
		$html .= cpc_render_group_activity_form($group_id);
	endif;
	
	// Get pagination
	$current_page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
	$posts_per_page = 20;
	$offset = ($current_page - 1) * $posts_per_page;
	
	// Get activity posts for this group
	$args = array(
		'post_type' => 'cpc_activity',
		'posts_per_page' => $posts_per_page,
		'paged' => $current_page,
		'post_status' => 'publish',
		'meta_key' => 'cpc_activity_group_id',
		'meta_value' => $group_id,
		'orderby' => 'date',
		'order' => 'DESC',
	);
	
	$activity_query = new WP_Query($args);
	$activity_posts = $activity_query->posts;
	
	if (!empty($activity_posts)):
		foreach ($activity_posts as $post):
			$html .= cpc_render_group_activity_post_full($post);
		endforeach;
		wp_reset_postdata();
		
		// Pagination
		if ($activity_query->max_num_pages > 1):
			$html .= '<div class="cpc-pagination">';
			
			// Previous page link
			if ($current_page > 1):
				$prev_url = add_query_arg('page', $current_page - 1);
				$html .= '<a href="'.$prev_url.'" class="cpc-pagination-prev">&laquo; '.__('Zurück', CPC2_TEXT_DOMAIN).'</a>';
			endif;
			
			// Page numbers
			for ($i = 1; $i <= $activity_query->max_num_pages; $i++):
				if ($i === $current_page):
					$html .= '<span class="cpc-pagination-current">'.$i.'</span>';
				else:
					$page_url = add_query_arg('page', $i);
					$html .= '<a href="'.$page_url.'" class="cpc-pagination-number">'.$i.'</a>';
				endif;
			endfor;
			
			// Next page link
			if ($current_page < $activity_query->max_num_pages):
				$next_url = add_query_arg('page', $current_page + 1);
				$html .= '<a href="'.$next_url.'" class="cpc-pagination-next">'.__('Weiter', CPC2_TEXT_DOMAIN).' &raquo;</a>';
			endif;
			
			$html .= '</div>';
		endif;
	else:
		$html .= '<p class="cpc-no-activity">'.__('Noch keine Aktivität in dieser Gruppe.', CPC2_TEXT_DOMAIN).'</p>';
	endif;
	
	$html .= '</div>'; // .cpc-group-overview-tab
	
	return $html;
}

/**
 * Render Members Tab
 */
function cpc_render_group_tab_members($group_id, $atts = array()) {
	return do_shortcode('[cpc-group-members group_id="'.$group_id.'"]');
}

/**
 * Render Forum Tab
 */
function cpc_render_group_tab_forum($group_id, $atts = array()) {
	// Check if group has forum enabled
	if (!get_post_meta($group_id, 'cpc_group_has_forum', true)) {
		return '<p>'.__('Forum ist für diese Gruppe nicht aktiviert.', CPC2_TEXT_DOMAIN).'</p>';
	}
	
	// Get forum slug for this group
	$forum_slug = get_post_meta($group_id, 'cpc_group_forum_slug', true);
	
	if (!$forum_slug) {
		return '<p>'.__('Forum-Fehler: Kein Forum-Slug gefunden.', CPC2_TEXT_DOMAIN).'</p>';
	}
	
	// Check if forum module is active
	if (!function_exists('cpc_forum_page')) {
		return '<p>'.__('Forum-Modul ist nicht aktiviert.', CPC2_TEXT_DOMAIN).'</p>';
	}
	
	// Initialize forum (load JS/CSS) - important for AJAX context
	if (function_exists('cpc_forum_init')) {
		cpc_forum_init();
	}

	// If ?topic=slug is present (common in tab context), resolve to topic_id for forum renderer
	if (isset($_GET['topic']) && !isset($_GET['topic_id'])) {
		$topic_slug = sanitize_title(wp_unslash($_GET['topic']));
		if ($topic_slug) {
			$topic_post = get_page_by_path($topic_slug, OBJECT, 'cpc_forum_post');
			if ($topic_post) {
				$_GET['topic_id'] = $topic_post->ID;
				// Also set query var so cpc_forum_page() detects single view logic
				global $wp_query;
				if (isset($wp_query) && is_object($wp_query)) {
					$wp_query->query_vars['topic'] = $topic_slug;
				}
			}
		}
	}

	// Ensure forum assets are loaded in AJAX context (wp_enqueue_script won't print here)
	$plugin_base = dirname(plugins_url('', __FILE__)); // .../plugins/ps-community
	$html_assets  = '<link rel="stylesheet" href="'.$plugin_base.'/forums/cpc_forum.css" />';
	$html_assets .= '<link rel="stylesheet" href="'.$plugin_base.'/js/select2.css" />';
	$html_assets .= '<script>window.cpc_forum_ajax = {ajaxurl: "'.admin_url('admin-ajax.php').'", is_admin: '.(current_user_can('manage_options') ? 'true' : 'false').'};</script>';
	$html_assets .= '<script src="'.$plugin_base.'/js/select2.js"></script>';
	$html_assets .= '<script src="'.$plugin_base.'/forums/cpc_forum.js"></script>';

	// Call forum function directly with show=true to display the form immediately
	$html = $html_assets;

	// Keep form hidden until the user explicitly opens it for better UX
	$html .= '<style>
		#cpc_forum_post_form{display:none;position:static;left:auto;top:auto;}
		.cpc-forum-post-open #cpc_forum_post_form{display:block;}
	</style>';

	$html .= cpc_forum_page(array('slug' => $forum_slug, 'show' => false));

	// Inject group_id into the add-topic form so redirects stay in group context
	$html = preg_replace(
		'/<form([^>]*)id="cpc_forum_post_theuploadform"([^>]*)>/',
		'<form$1id="cpc_forum_post_theuploadform"$2><input type="hidden" name="cpc_group_id" value="'.$group_id.'" />',
		$html,
		1
	);

	$html .= '<script>(function(){
		var form = document.getElementById("cpc_forum_post_form");
		var btn = document.getElementById("cpc_forum_post_button");
		if(!form || !btn){return;}
		var container = document.getElementById("cpc_forum_post_div");
		var opened = false;
		btn.addEventListener("click", function(ev){
			if(opened){return;}
			ev.preventDefault();
			opened = true;
			if(container){container.classList.add("cpc-forum-post-open");}
			var title = document.getElementById("cpc_forum_post_title");
			if(title){title.focus();}
			btn.textContent = btn.textContent.trim() || "Add Topic";
		});
	})();</script>';

	// Rewrite topic links to stay in group tab context
	$group_tab_url = add_query_arg('tab', 'forum', cpc_get_group_link($group_id));
	$html = preg_replace_callback(
		'/href="([^"]*?)(?:[?&])topic=([^"&#]+)/',
		function($m) use ($group_tab_url) {
			$target = add_query_arg('topic', $m[2], $group_tab_url);
			return 'href="'.$target.'"';
		},
		$html
	);

	// Rewrite generic back links (e.g., cpc_forum_backto) to the group forum tab
	$html = preg_replace(
		'/href="[^\"]*(?:[?&](?:page_id|cpc_forum_cat_page)=[^" ]*)"/',
		'href="'.$group_tab_url.'"',
		$html
	);
	
	return $html;
}

/**
 * Render Activity Tab
 */
function cpc_render_group_tab_activity($group_id, $atts = array()) {
	$html = '';
	$html .= '<div class="cpc-group-activity-tab">';
	
	// Get group activity posts
	$activity_posts = cpc_get_group_activity($group_id, 10);
	
	if ($activity_posts):
		foreach ($activity_posts as $activity):
			$html .= cpc_render_group_activity_post($activity);
		endforeach;
	else:
		$html .= '<p class="cpc-no-activity">'.__('Noch keine Aktivität in dieser Gruppe.', CPC2_TEXT_DOMAIN).'</p>';
	endif;
	
	// Post activity form if member
	if (is_user_logged_in() && cpc_is_group_member(get_current_user_id(), $group_id)):
		$html .= cpc_render_group_activity_form($group_id);
	endif;
	
	$html .= '</div>'; // .cpc-group-activity-tab
	
	return $html;
}

/**
 * Render Settings Tab
 */
function cpc_render_group_tab_settings($group_id, $atts = array()) {
	if (!cpc_is_group_admin(get_current_user_id(), $group_id)):
		return '<p>'.__('Du hast keine Berechtigung, diese Einstellungen zu ändern.', CPC2_TEXT_DOMAIN).'</p>';
	endif;
	
	$group = get_post($group_id);
	$group_type = get_post_meta($group_id, 'cpc_group_type', true);
	
	$html = '';
	$html .= '<div class="cpc-group-settings-tab">';
	
	$html .= '<h3>'.__('Grundeinstellungen', CPC2_TEXT_DOMAIN).'</h3>';
	$html .= '<form class="cpc-group-settings-form" data-group-id="'.$group_id.'">';
	
	$html .= '<div class="cpc-form-field">';
	$html .= '<label for="group_type_setting">'.__('Gruppentyp', CPC2_TEXT_DOMAIN).'</label>';
	$html .= '<select name="group_type" id="group_type_setting">';
	$html .= '<option value="public" '.selected($group_type, 'public', false).'>'.__('Öffentlich', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '<option value="private" '.selected($group_type, 'private', false).'>'.__('Privat', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '<option value="hidden" '.selected($group_type, 'hidden', false).'>'.__('Versteckt', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '</select>';
	$html .= '</div>';
	
	$html .= '<button type="submit" class="cpc-btn cpc-btn-primary">'.__('Änderungen speichern', CPC2_TEXT_DOMAIN).'</button>';
	$html .= '</form>';
	
	// Forum Settings (only if forum module is active)
	if (function_exists('cpc_forum_page')):
		$html .= '<hr>';
		$html .= '<h3>'.__('Forum-Einstellungen', CPC2_TEXT_DOMAIN).'</h3>';
		
		$has_forum = get_post_meta($group_id, 'cpc_group_has_forum', true);
		$forum_visibility = get_post_meta($group_id, 'cpc_group_forum_visibility', true);
		if (!$forum_visibility) $forum_visibility = 'group_only';
		
		$html .= '<form class="cpc-group-forum-settings-form" data-group-id="'.$group_id.'">' ;
		
		$html .= '<div class="cpc-form-field">';
		$html .= '<label>';
		$html .= '<input type="checkbox" name="enable_forum" id="enable_forum" '.checked($has_forum, true, false).'> ';
		$html .= __('Forum für diese Gruppe aktivieren', CPC2_TEXT_DOMAIN);
		$html .= '</label>';
		$html .= '<p class="description">'.__('Wenn aktiviert, können Gruppenmitglieder im Gruppen-Forum diskutieren.', CPC2_TEXT_DOMAIN).'</p>';
		$html .= '</div>';
		
		$html .= '<div class="cpc-form-field cpc-forum-visibility-field" '.(!$has_forum ? 'style="display:none;"' : '').'>';
		$html .= '<label>'.__('Forum-Sichtbarkeit', CPC2_TEXT_DOMAIN).'</label>';
		$html .= '<label style="display:block;margin:5px 0;">';
		$html .= '<input type="radio" name="forum_visibility" value="group_only" '.checked($forum_visibility, 'group_only', false).'> ';
		$html .= __('Nur für Gruppenmitglieder sichtbar', CPC2_TEXT_DOMAIN);
		$html .= '</label>';
		$html .= '<label style="display:block;margin:5px 0;">';
		$html .= '<input type="radio" name="forum_visibility" value="public" '.checked($forum_visibility, 'public', false).'> ';
		$html .= __('Auch in der öffentlichen Forenansicht sichtbar', CPC2_TEXT_DOMAIN);
		$html .= '</label>';
		$html .= '</div>';
		
		$html .= '<button type="submit" class="cpc-btn cpc-btn-primary">'.__('Forum-Einstellungen speichern', CPC2_TEXT_DOMAIN).'</button>';
		$html .= '</form>';
	endif;
	
	// Group Permissions
	$html .= '<hr>';
	$html .= '<h3>'.__('Gruppenberechtigungen', CPC2_TEXT_DOMAIN).'</h3>';
	
	$permissions = get_post_meta($group_id, 'cpc_group_permissions', true);
	if (!is_array($permissions)) {
		$permissions = array(
			'forum_post' => 'member', // Who can post in forum: member, moderator, admin
			'invite_members' => 'member', // Who can invite members: member, moderator, admin
			'activity_edit_all' => 'moderator', // Who can edit all activity posts: moderator, admin
			'activity_delete_all' => 'moderator', // Who can delete all activity posts: moderator, admin
		);
	} else {
		// Backfill missing key
		if (!isset($permissions['invite_members'])) {
			$permissions['invite_members'] = 'member';
		}
	}
	
	$html .= '<form class="cpc-group-permissions-form" data-group-id="'.$group_id.'">';
	$html .= '<input type="hidden" name="group_id" value="'.$group_id.'">';
	
	$html .= '<div class="cpc-form-field">';
	$html .= '<label>'.__('Wer darf im Forum posten?', CPC2_TEXT_DOMAIN).'</label>';
	$html .= '<select name="forum_post">';
	$html .= '<option value="member" '.selected($permissions['forum_post'], 'member', false).'>'.__('Alle Mitglieder', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '<option value="moderator" '.selected($permissions['forum_post'], 'moderator', false).'>'.__('Nur Moderatoren und Admins', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '<option value="admin" '.selected($permissions['forum_post'], 'admin', false).'>'.__('Nur Admins', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '</select>';
	$html .= '</div>';

	$html .= '<div class="cpc-form-field">';
	$html .= '<label>'.__('Wer darf Mitglieder einladen?', CPC2_TEXT_DOMAIN).'</label>';
	$html .= '<select name="invite_members">';
	$html .= '<option value="member" '.selected($permissions['invite_members'], 'member', false).'>'.__('Alle Mitglieder', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '<option value="moderator" '.selected($permissions['invite_members'], 'moderator', false).'>'.__('Nur Moderatoren und Admins', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '<option value="admin" '.selected($permissions['invite_members'], 'admin', false).'>'.__('Nur Admins', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '</select>';
	$html .= '</div>';

	$html .= '<div class="cpc-form-field">';
	$html .= '<label>'.__('Wer darf fremde Aktivitäts-Beiträge bearbeiten?', CPC2_TEXT_DOMAIN).'</label>';
	$html .= '<select name="activity_edit_all">';
	$html .= '<option value="moderator" '.selected($permissions['activity_edit_all'], 'moderator', false).'>'.__('Moderatoren und Admins', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '<option value="admin" '.selected($permissions['activity_edit_all'], 'admin', false).'>'.__('Nur Admins', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '</select>';
	$html .= '<p class="description">'.__('Eigene Beiträge kann jeder immer bearbeiten.', CPC2_TEXT_DOMAIN).'</p>';
	$html .= '</div>';
	
	$html .= '<div class="cpc-form-field">';
	$html .= '<label>'.__('Wer darf fremde Aktivitäts-Beiträge löschen?', CPC2_TEXT_DOMAIN).'</label>';
	$html .= '<select name="activity_delete_all">';
	$html .= '<option value="moderator" '.selected($permissions['activity_delete_all'], 'moderator', false).'>'.__('Moderatoren und Admins', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '<option value="admin" '.selected($permissions['activity_delete_all'], 'admin', false).'>'.__('Nur Admins', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '</select>';
	$html .= '<p class="description">'.__('Eigene Beiträge kann jeder immer löschen.', CPC2_TEXT_DOMAIN).'</p>';
	$html .= '</div>';
	
	$html .= '<button type="submit" class="cpc-btn cpc-btn-primary">'.__('Berechtigungen speichern', CPC2_TEXT_DOMAIN).'</button>';
	$html .= '</form>';
	
	$pending_requests = cpc_get_pending_membership_requests($group_id);
	
	if (!empty($pending_requests)):
		$html .= '<div class="cpc-membership-requests">';
		$html .= '<p><strong>'.sprintf(__('%d Ausstehende Anfragen', CPC2_TEXT_DOMAIN), count($pending_requests)).'</strong></p>';
		
		foreach ($pending_requests as $request):
			$user = get_userdata($request->post_author);
			if (!$user) continue;
			
			$html .= '<div class="cpc-membership-request">';
			$html .= '<div class="cpc-request-user">';
			if (function_exists('user_avatar_get_avatar')):
				$html .= user_avatar_get_avatar($user->ID, 32);
			else:
				$html .= get_avatar($user->ID, 32);
			endif;
			$html .= '<span class="cpc-request-name">'.$user->display_name.'</span>';
			$html .= '</div>';
			
			$html .= '<div class="cpc-request-actions">';
			$html .= '<button type="button" class="cpc-btn cpc-btn-primary cpc-approve-membership" data-request-id="'.$request->ID.'" data-group-id="'.$group_id.'" data-user-id="'.$user->ID.'">'.__('Annehmen', CPC2_TEXT_DOMAIN).'</button>';
			$html .= '<button type="button" class="cpc-btn cpc-btn-danger cpc-reject-membership" data-request-id="'.$request->ID.'" data-group-id="'.$group_id.'">'.__('Ablehnen', CPC2_TEXT_DOMAIN).'</button>';
			$html .= '</div>';
			$html .= '</div>';
		endforeach;
		
		$html .= '</div>';
	else:
		$html .= '<p>'.__('Keine ausstehenden Beitrittanfragen.', CPC2_TEXT_DOMAIN).'</p>';
	endif;
	
	// Moderators Management Section
	$html .= '<hr>';
	$html .= '<h3>'.__('Moderatoren verwalten', CPC2_TEXT_DOMAIN).'</h3>';
	
	// Get all group members
	$members = cpc_get_group_members($group_id);
	
	if (!empty($members)):
		$html .= '<table class="cpc-members-role-table">';
		$html .= '<thead><tr><th>'.__('Mitglied', CPC2_TEXT_DOMAIN).'</th><th>'.__('Aktuelle Rolle', CPC2_TEXT_DOMAIN).'</th><th>'.__('Aktion', CPC2_TEXT_DOMAIN).'</th></tr></thead><tbody>';
		
		foreach ($members as $member):
			$user = get_userdata($member->user_id);
			if (!$user) continue;
			
			$html .= '<tr>';
			$html .= '<td>'.$user->display_name.'</td>';
			$html .= '<td><strong>'.ucfirst($member->member_role).'</strong></td>';
			$html .= '<td>';
			
			// Don't allow changing own role
			if ($member->user_id != get_current_user_id()):
				$html .= '<select class="cpc-change-member-role" data-user-id="'.$member->user_id.'" data-group-id="'.$group_id.'">';
				$html .= '<option value="member"'.($member->member_role === 'member' ? ' selected' : '').'>'.__('Mitglied', CPC2_TEXT_DOMAIN).'</option>';
				$html .= '<option value="moderator"'.($member->member_role === 'moderator' ? ' selected' : '').'>'.__('Moderator', CPC2_TEXT_DOMAIN).'</option>';
				$html .= '<option value="admin"'.($member->member_role === 'admin' ? ' selected' : '').'>'.__('Admin', CPC2_TEXT_DOMAIN).'</option>';
				$html .= '</select>';
			else:
				$html .= '<em>'.__('(Du selbst)', CPC2_TEXT_DOMAIN).'</em>';
			endif;
			
			$html .= '</td>';
			$html .= '</tr>';
		endforeach;
		
		$html .= '</tbody></table>';
	else:
		$html .= '<p>'.__('Keine Mitglieder gefunden.', CPC2_TEXT_DOMAIN).'</p>';
	endif;
	
	$html .= '<hr>';
	$html .= '<h3>'.__('Gefährliche Zonen', CPC2_TEXT_DOMAIN).'</h3>';
	$html .= '<p>';
	$html .= '<button type="button" class="cpc-btn cpc-btn-danger cpc-delete-group-btn" data-group-id="'.$group_id.'">';
	$html .= __('Gruppe löschen', CPC2_TEXT_DOMAIN);
	$html .= '</button>';
	$html .= '</p>';
	
	$html .= '</div>'; // .cpc-group-settings-tab
	
	return $html;
}

/**
 * Get group activity posts
 */
function cpc_get_group_activity($group_id, $limit = 10) {
	$args = array(
		'post_type' => 'cpc_activity',
		'posts_per_page' => $limit,
		'meta_query' => array(
			array(
				'key' => 'cpc_activity_group_id',
				'value' => $group_id,
			),
		),
		'orderby' => 'date',
		'order' => 'DESC',
	);
	
	$query = new WP_Query($args);
	return $query->posts;
}

/**
 * Render single activity post - Full version with styling
 */
function cpc_render_group_activity_post_full($post) {
	$user = get_userdata($post->post_author);
	if (!$user) return '';
	
	$html = '';
	$html .= '<div class="cpc-group-activity-post cpc-activity-post">';
	
	// Avatar
	$html .= '<div class="cpc-activity-avatar">';
	if (function_exists('user_avatar_get_avatar')):
		$html .= user_avatar_get_avatar($post->post_author, 48);
	else:
		$html .= get_avatar($post->post_author, 48);
	endif;
	$html .= '</div>';
	
	// Content
	$html .= '<div class="cpc-activity-content">';
	$html .= '<div class="cpc-activity-author">';
	$profile_link = cpc_comfile_link($post->post_author);
	if ($profile_link):
		$html .= '<a href="'.$profile_link.'">'.$user->display_name.'</a>';
	else:
		$html .= $user->display_name;
	endif;
	$html .= '</div>';
	
	$html .= '<div class="cpc-activity-text">'.$post->post_content.'</div>';
	
	$html .= '<div class="cpc-activity-meta">';
	$html .= '<small>'.human_time_diff(strtotime($post->post_date_gmt), current_time('timestamp', true)).' '.__('ago', CPC2_TEXT_DOMAIN).'</small>';
	
	// Edit/Delete buttons if user has permission
	$group_id = get_post_meta($post->ID, 'cpc_activity_group_id', true);
	$current_user_id = get_current_user_id();
	
	if ($current_user_id) {
		$can_edit = ($post->post_author == $current_user_id) || cpc_can_moderate_activity($current_user_id, $group_id, 'edit');
		$can_delete = ($post->post_author == $current_user_id) || cpc_can_moderate_activity($current_user_id, $group_id, 'delete');
		
		if ($can_edit || $can_delete) {
			$html .= '<span class="cpc-activity-actions">';
			
			if ($can_edit) {
				$html .= ' | <a href="#" class="cpc-edit-activity" data-post-id="'.$post->ID.'">'.__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
			}
			
			if ($can_delete) {
				$html .= ' | <a href="#" class="cpc-delete-activity" data-post-id="'.$post->ID.'">'.__('Löschen', CPC2_TEXT_DOMAIN).'</a>';
			}
			
			$html .= '</span>';
		}
	}
	
	$html .= '</div>';
	
	// Replies section
	if (is_user_logged_in() && cpc_is_group_member(get_current_user_id(), get_post_meta($post->ID, 'cpc_activity_group_id', true))):
		$html .= '<div class="cpc-activity-replies">';
		
		// Get replies/comments
		$comments = get_comments(array(
			'post_id' => $post->ID,
			'status' => 'approve',
		));
		
		if (!empty($comments)):
			foreach ($comments as $comment):
				$reply_user = get_userdata($comment->user_id);
				if (!$reply_user) continue;
				
				$html .= '<div class="cpc-activity-reply" data-comment-id="'.$comment->comment_ID.'">';
				$html .= '<span class="cpc-activity-reply-author">'.$reply_user->display_name.'</span>';
				$html .= '<span class="cpc-activity-reply-time">'.human_time_diff(strtotime($comment->comment_date_gmt), current_time('timestamp', true)).' ago</span>';
				
				// Edit/Delete for replies
				$can_edit_reply = ($comment->user_id == get_current_user_id()) || cpc_can_moderate_activity(get_current_user_id(), get_post_meta($post->ID, 'cpc_activity_group_id', true), 'edit');
				$can_delete_reply = ($comment->user_id == get_current_user_id()) || cpc_can_moderate_activity(get_current_user_id(), get_post_meta($post->ID, 'cpc_activity_group_id', true), 'delete');
				
				if ($can_edit_reply || $can_delete_reply) {
					$html .= '<span class="cpc-reply-actions">';
					if ($can_edit_reply) {
						$html .= ' | <a href="#" class="cpc-edit-reply" data-comment-id="'.$comment->comment_ID.'">'.__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
					}
					if ($can_delete_reply) {
						$html .= ' | <a href="#" class="cpc-delete-reply" data-comment-id="'.$comment->comment_ID.'">'.__('Löschen', CPC2_TEXT_DOMAIN).'</a>';
					}
					$html .= '</span>';
				}
				
				$html .= '<div class="cpc-reply-text">'.$comment->comment_content.'</div>';
				$html .= '</div>';
			endforeach;
		endif;
		
		// Reply form (collapsible)
		$html .= '<div class="cpc-activity-reply-form" style="display:none;">';
		$html .= '<textarea placeholder="'.__('Schreibe eine Antwort...', CPC2_TEXT_DOMAIN).'" class="cpc-reply-content" data-post-id="'.$post->ID.'"></textarea>';
		$html .= '<button type="button" class="cpc-btn cpc-btn-small cpc-post-reply" data-post-id="'.$post->ID.'">'.__('Antworten', CPC2_TEXT_DOMAIN).'</button>';
		$html .= '</div>';
		
		$html .= '<span class="cpc-reply-toggle" data-post-id="'.$post->ID.'">'.__('Antwort hinzufügen', CPC2_TEXT_DOMAIN).'</span>';
		
		$html .= '</div>'; // .cpc-activity-replies
	endif;
	
	$html .= '</div>'; // .cpc-activity-content
	$html .= '</div>'; // .cpc-activity-post
	
	return $html;
}

/**
 * Render single activity post
 */
function cpc_render_group_activity_post($post) {
	// Use activity module's rendering if available
	if (function_exists('cpc_render_activity_post')):
		return cpc_render_activity_post($post);
	endif;
	
	$html = '';
	$html .= '<div class="cpc-group-activity-post">';
	$html .= '<strong>'.get_the_author_meta('display_name', $post->post_author).'</strong>';
	$html .= '<p>'.$post->post_content.'</p>';
	$html .= '<small>'.human_time_diff($post->post_date_gmt, current_time('mysql', true)).' '.__('Uhr', CPC2_TEXT_DOMAIN).'</small>';
	$html .= '</div>';
	
	return $html;
}

/**
 * Render activity post form for group
 */
function cpc_render_group_activity_form($group_id) {
	$html = '';
	$html .= '<div class="cpc-group-activity-form">';
	$html .= '<h4>'.__('Aktivität posten', CPC2_TEXT_DOMAIN).'</h4>';
	
	$html .= '<form class="cpc-group-post-activity-form" data-group-id="'.$group_id.'">';
	$html .= '<textarea name="activity_content" placeholder="'.__('Was machst du gerade?', CPC2_TEXT_DOMAIN).'" required></textarea>';
	$html .= '<button type="submit" class="cpc-btn cpc-btn-primary">'.__('Posten', CPC2_TEXT_DOMAIN).'</button>';
	$html .= '</form>';
	$html .= '</div>';
	
	return $html;
}

?>
