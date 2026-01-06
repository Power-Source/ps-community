<?php

/* Group Shortcodes - Frontend functionality via shortcodes */

/* **** */ /* INIT */ /* **** */

function cpc_groups_init() {
    // JS and CSS
	wp_enqueue_script('cpc-groups-js', plugins_url('cpc_groups.js', __FILE__), array('jquery'));	
    wp_localize_script( 'cpc-groups-js', 'cpc_groups_ajax', array( 
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce('cpc_groups_nonce'),
        'is_admin' => current_user_can('manage_options'),
        'user_id' => get_current_user_id(),
    ) );		
    wp_enqueue_style('cpc-groups-css', plugins_url('cpc_groups.css', __FILE__), array(), '1.0');

    // Settings JS (for admin settings form in groups page)
	wp_enqueue_script('cpc-groups-settings-js', plugins_url('cpc_groups_settings.js', __FILE__), array('jquery'), '1.0');
	wp_localize_script( 'cpc-groups-settings-js', 'cpc_groups_settings', array( 
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
		'nonce' => wp_create_nonce('cpc_groups_nonce'),
	) );

    // Anything else?
    do_action('cpc_groups_init_hook');
}

// Register (not enqueue) scripts/styles so they're available but not loaded unless needed
function cpc_groups_register_assets() {
    if ( ! wp_script_is('cpc-groups-js', 'registered') ) {
        wp_register_script('cpc-groups-js', plugins_url('cpc_groups.js', __FILE__), array('jquery'));
    }
	if ( ! wp_script_is('cpc-groups-settings-js', 'registered') ) {
		wp_register_script('cpc-groups-settings-js', plugins_url('cpc_groups_settings.js', __FILE__), array('jquery'), '1.0');
	}
    if ( ! wp_style_is('cpc-groups-css', 'registered') ) {
        wp_register_style('cpc-groups-css', plugins_url('cpc_groups.css', __FILE__), array(), '1.0');
    }
}
add_action('wp_enqueue_scripts', 'cpc_groups_register_assets', 0);

/* ********** */ /* SHORTCODES */ /* ********** */

/**
 * [cpc-groups] - Display list of groups
 */
function cpc_groups_list($atts) {
	// Debug-Ausgabe
	$debug = '<div style="border:2px solid blue;padding:10px;margin:10px 0;background:#f0f0f0;">';
	$debug .= '<strong>DEBUG INFO:</strong><br>';
	$debug .= 'Shortcode wird ausgeführt!<br>';
	$debug .= 'CPC_CORE_PLUGINS: ' . (defined('CPC_CORE_PLUGINS') ? CPC_CORE_PLUGINS : 'NICHT DEFINIERT') . '<br>';
	$debug .= 'Blog ID: ' . (is_multisite() ? get_current_blog_id() : 'Kein Multisite') . '<br>';
	$debug .= 'cpc_groups_init exists: ' . (function_exists('cpc_groups_init') ? 'JA' : 'NEIN') . '<br>';
	$debug .= '</div>';
	
	// Debug: Check if function is called
	if (!function_exists('cpc_groups_init')) {
		return $debug . '<div style="border:2px solid red;padding:10px;margin:10px 0;">ERROR: cpc_groups_init function nicht gefunden! Gruppen-Modul nicht geladen.</div>';
	}
	
	// Init
	cpc_groups_init();

	global $current_user;
	
	// Shortcode parameters
	$values = cpc_get_shortcode_options('cpc_groups_list');
	extract( shortcode_atts( array(
		'type' => cpc_get_shortcode_value($values, 'cpc_groups_list-type', 'all'), // all|public|private|hidden
		'orderby' => cpc_get_shortcode_value($values, 'cpc_groups_list-orderby', 'title'),
		'order' => cpc_get_shortcode_value($values, 'cpc_groups_list-order', 'ASC'),
		'limit' => cpc_get_shortcode_value($values, 'cpc_groups_list-limit', -1),
		'show_avatar' => cpc_get_shortcode_value($values, 'cpc_groups_list-show_avatar', true),
		'avatar_size' => cpc_get_shortcode_value($values, 'cpc_groups_list-avatar_size', 50),
		'show_description' => cpc_get_shortcode_value($values, 'cpc_groups_list-show_description', true),
		'description_length' => cpc_get_shortcode_value($values, 'cpc_groups_list-description_length', 150),
		'show_member_count' => cpc_get_shortcode_value($values, 'cpc_groups_list-show_member_count', true),
		'show_join_button' => cpc_get_shortcode_value($values, 'cpc_groups_list-show_join_button', true),
		'columns' => cpc_get_shortcode_value($values, 'cpc_groups_list-columns', 2),
		'search' => cpc_get_shortcode_value($values, 'cpc_groups_list-search', true),
		'styles' => true,
	), $atts, 'cpc_groups_list' ) );

	$html = '';
	$html .= '<div class="cpc-groups-list">';

	// Placeholder image for missing avatars (embedded SVG, URL-encoded via rawurlencode)
	$placeholder_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect fill="#dbeafe" width="120" height="120"/><text x="50%" y="55%" font-size="28" text-anchor="middle" fill="#4b5563" font-family="sans-serif">G</text></svg>';
	$placeholder_avatar = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($placeholder_svg);

	// Groups Toolbar (Create + Search)
	$html .= '<div class="cpc-groups-toolbar">';
	
	// Create group button
	if (is_user_logged_in() && get_option('cpc_groups_allow_creation')):
		$create_page = get_option('cpccom_group_create_page');
		if ($create_page):
			$html .= '<a href="'.get_permalink($create_page).'" class="cpc-btn-create-group">'.__('+ Neue Gruppe erstellen', CPC2_TEXT_DOMAIN).'</a>';
		endif;
	endif;

	// Search form
	if ($search && ($search == true || $search == '1' || $search == 'true' || $search == 'on')):
		$search_term = isset($_GET['group_search']) ? sanitize_text_field($_GET['group_search']) : '';
		$html .= '<form method="get" action="" class="cpc-groups-search-form">';
		$html .= '<input type="text" name="group_search" placeholder="'.__('Gruppen durchsuchen...', CPC2_TEXT_DOMAIN).'" value="'.esc_attr($search_term).'" />';
		$html .= '<input type="submit" value="'.__('Suchen', CPC2_TEXT_DOMAIN).'" />';
		$html .= '</form>';
	endif;

	$html .= '</div>'; // .cpc-groups-toolbar

	// Get current blog ID for multisite support
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;

	// Get groups
	if (isset($_GET['group_search']) && $_GET['group_search'] != ''):
		$groups = cpc_search_groups($_GET['group_search'], $type == 'all' ? '' : $type, $current_blog_id);
	else:
		$groups = cpc_get_groups_by_type($type == 'all' ? '' : $type, $limit, $current_blog_id);
	endif;

	// Filter hidden groups if user is not logged in
	if (!is_user_logged_in()):
		$groups = array_filter($groups, function($group) {
			$group_type = get_post_meta($group->ID, 'cpc_group_type', true);
			return $group_type != 'hidden';
		});
	else:
		// Filter hidden groups user is not member of
		$user_id = get_current_user_id();
		$groups = array_filter($groups, function($group) use ($user_id) {
			return cpc_can_view_group($user_id, $group->ID);
		});
	endif;

	if ($groups):
		$html .= '<div class="cpc-groups-grid cpc-groups-cols-'.$columns.'">';
		foreach ($groups as $group):
			$group_type = get_post_meta($group->ID, 'cpc_group_type', true);
			if (!$group_type) $group_type = 'public';
			$member_count = get_post_meta($group->ID, 'cpc_group_member_count', true);
			if (!$member_count) $member_count = cpc_update_group_member_count($group->ID);
			
			$html .= '<div class="cpc-group-card cpc-group-type-'.$group_type.'">';
			
			if ($show_avatar):
				$html .= '<div class="cpc-group-avatar">';
			$html .= '<a href="'.cpc_get_group_link($group->ID).'">';
				if (has_post_thumbnail($group->ID)):
					$html .= get_the_post_thumbnail($group->ID, array($avatar_size, $avatar_size));
				else:
					$html .= '<img src="'.$placeholder_avatar.'" width="'.$avatar_size.'" height="'.$avatar_size.'" alt="" />';
				endif;
				$html .= '</a>';
				$html .= '</div>';
			endif;

			$html .= '<div class="cpc-group-info">';
			// Generate group link
			$group_single_page = get_option('cpccom_group_single_page');
			if ($group_single_page):
				$page_link = get_page_link($group_single_page);
				if (cpc_using_permalinks()):
					$group_link = $page_link . $group->post_name . '/';
				else:
					$group_link = $page_link . (strpos($page_link, '?') ? '&' : '?') . 'group_name=' . $group->post_name;
				endif;
			else:
				$group_link = get_permalink($group->ID);
			endif;
			$html .= '<h3 class="cpc-group-title"><a href="'.$group_link.'">'.$group->post_title.'</a></h3>';
			$html .= '<div class="cpc-group-meta">';
			$html .= '<span class="cpc-group-type-badge">'.cpc_get_group_type_label($group_type).'</span>';
			if ($show_member_count):
				$html .= '<span class="cpc-group-members">'.$member_count.' '._n('Mitglied', 'Mitglieder', $member_count, CPC2_TEXT_DOMAIN).'</span>';
			endif;
			$html .= '</div>';

			if ($show_description && $group->post_content):
				$description = wp_trim_words($group->post_content, $description_length);
				$html .= '<div class="cpc-group-description">'.$description.'</div>';
			endif;

			if ($show_join_button && is_user_logged_in()):
				$is_member = cpc_is_group_member(get_current_user_id(), $group->ID, $current_blog_id);
				if ($is_member):
					$html .= '<a href="#" class="cpc-group-leave-btn" data-group-id="'.$group->ID.'">'.__('Gruppe verlassen', CPC2_TEXT_DOMAIN).'</a>';
				else:
					$html .= '<a href="#" class="cpc-group-join-btn" data-group-id="'.$group->ID.'">'.__('Gruppe beitreten', CPC2_TEXT_DOMAIN).'</a>';
				endif;
			endif;

			$html .= '</div>'; // .cpc-group-info
			$html .= '</div>'; // .cpc-group-card
		endforeach;
		$html .= '</div>'; // .cpc-groups-grid
	else:
		$html .= '<p class="cpc-no-groups">'.__('Keine Gruppen gefunden.', CPC2_TEXT_DOMAIN).'</p>';
	endif;

	$html .= '</div>'; // .cpc-groups-list

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_groups_list', '', '', $styles, $values);
	return $html;
}
add_shortcode('cpc-groups', 'cpc_groups_list');

/**
 * [cpc-group-single] - Display single group details
 */
function cpc_group_single($atts) {
	cpc_groups_init();

	// Prevent WP from treating group + topic/tab URLs as 404 so the shortcode still renders
	global $wp_query;
	if (isset($_GET['group_name']) || isset($_GET['tab']) || isset($_GET['topic'])) {
		$wp_query->is_404 = false;
		$wp_query->is_page = true;
	}

	global $current_user, $post;
	
	$values = cpc_get_shortcode_options('cpc_group_single');
	extract( shortcode_atts( array(
		'group_id' => cpc_get_shortcode_value($values, 'cpc_group_single-group_id', ''),
		'show_avatar' => cpc_get_shortcode_value($values, 'cpc_group_single-show_avatar', true),
		'avatar_size' => cpc_get_shortcode_value($values, 'cpc_group_single-avatar_size', 100),
		'show_description' => cpc_get_shortcode_value($values, 'cpc_group_single-show_description', true),
		'show_members' => cpc_get_shortcode_value($values, 'cpc_group_single-show_members', true),
		'show_actions' => cpc_get_shortcode_value($values, 'cpc_group_single-show_actions', true),
		'styles' => true,
	), $atts, 'cpc_group_single' ) );

	// Get current blog ID for multisite support
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;

	// Try to get group ID from current post or URL
	if (!$group_id && is_singular('cpc_group')):
		$group_id = get_the_ID();
	endif;

	// Try to get group from URL parameter (group_name)
	if (!$group_id && isset($_GET['group_name'])):
		$group_name = sanitize_text_field($_GET['group_name']);
		$group = get_page_by_path($group_name, OBJECT, 'cpc_group');
		if ($group):
			$group_id = $group->ID;
		endif;
	endif;
	if (!$group_id):
		return '<p>'.__('Gruppe nicht gefunden.', CPC2_TEXT_DOMAIN).'</p>';
	endif;

	$placeholder_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect fill="#dbeafe" width="120" height="120"/><text x="50%" y="55%" font-size="28" text-anchor="middle" fill="#4b5563" font-family="sans-serif">G</text></svg>';
	$placeholder_avatar = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($placeholder_svg);

	$group = get_post($group_id);
	if (!$group || $group->post_type != 'cpc_group'):
		return '<p>'.__('Gruppe nicht gefunden.', CPC2_TEXT_DOMAIN).'</p>';
	endif;

	// Check if user can view this group
	if (!cpc_can_view_group(get_current_user_id(), $group_id)):
		return '<p>'.__('Du hast keine Berechtigung, diese Gruppe zu sehen.', CPC2_TEXT_DOMAIN).'</p>';
	endif;

	$html = '';
	$group_type = get_post_meta($group_id, 'cpc_group_type', true);
	if (!$group_type) $group_type = 'public';
	
	$html .= '<div class="cpc-group-single cpc-group-type-'.$group_type.'">';
	
	$html .= '<div class="cpc-group-header">';
	if ($show_avatar):
		$html .= '<div class="cpc-group-avatar-large">';
		if (has_post_thumbnail($group_id)):
			$html .= get_the_post_thumbnail($group_id, array($avatar_size, $avatar_size));
		else:
			$html .= '<img src="'.$placeholder_avatar.'" width="'.$avatar_size.'" height="'.$avatar_size.'" alt="" />';
		endif;
		$html .= '</div>';
	endif;

	$html .= '<div class="cpc-group-header-info">';
	$html .= '<h2 class="cpc-group-title">'.$group->post_title.'</h2>';
	$html .= '<div class="cpc-group-meta">';
	$html .= '<span class="cpc-group-type-badge">'.cpc_get_group_type_label($group_type).'</span>';
	$member_count = get_post_meta($group_id, 'cpc_group_member_count', true);
	if (!$member_count) $member_count = cpc_update_group_member_count($group_id);
	$html .= '<span class="cpc-group-members">'.$member_count.' '._n('Mitglied', 'Mitglieder', $member_count, CPC2_TEXT_DOMAIN).'</span>';
	$html .= '</div>';

	if (is_user_logged_in()):
		$is_member = cpc_is_group_member(get_current_user_id(), $group_id, $current_blog_id);
		
		$html .= '<div class="cpc-group-actions">';
		if ($is_member):
			// Invite button only if friendships module enabled and user has invite permission
			if (defined('CPC_CORE_PLUGINS') && strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false && function_exists('cpc_can_invite_members') && cpc_can_invite_members(get_current_user_id(), $group_id)) {
				$html .= '<a href="#" class="cpc-group-invite-btn" data-group-id="'.$group_id.'">'.__('Freunde einladen', CPC2_TEXT_DOMAIN).'</a>';
			}
			$html .= '<a href="#" class="cpc-group-leave-btn" data-group-id="'.$group_id.'">'.__('Gruppe verlassen', CPC2_TEXT_DOMAIN).'</a>';
		else:
			if ($group_type != 'hidden'):
				$html .= '<a href="#" class="cpc-group-join-btn" data-group-id="'.$group_id.'">'.__('Gruppe beitreten', CPC2_TEXT_DOMAIN).'</a>';
			endif;
		endif;
		$html .= '</div>';
	endif;

	$html .= '</div>'; // .cpc-group-header-info
	$html .= '</div>'; // .cpc-group-header

	// Determine available tabs dynamically
	$tabs = cpc_get_group_tabs($group_id);
	$valid_tabs = array_keys($tabs);
	
	// Derive requested tab
	$requested_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'overview';
	
	// If a topic is requested, force forum tab when available
	if (isset($_GET['topic']) && in_array('forum', $valid_tabs, true)) {
		$requested_tab = 'forum';
	}

	// Validate requested tab, fallback to overview or first available
	if (!in_array($requested_tab, $valid_tabs, true)) {
		$requested_tab = in_array('overview', $valid_tabs, true) ? 'overview' : reset($valid_tabs);
	}

	$active_tab = $requested_tab;
	
	// Render tabs navigation
	$html .= cpc_render_group_tabs($group_id, $active_tab);
	
	// Render tab content
	$html .= '<div class="cpc-group-tabs-content" data-group-id="'.$group_id.'">';
	$html .= cpc_render_group_tab_content($group_id, $active_tab, $atts);
	$html .= '</div>'; // .cpc-group-tabs-content

	$html .= '</div>'; // .cpc-group-single

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_group_single', '', '', $styles, $values);
	return $html;
}
add_shortcode('cpc-group-single', 'cpc_group_single');

/**
 * [cpc-group-members] - Display group members
 */
function cpc_group_members($atts) {
	cpc_groups_init();

	$values = cpc_get_shortcode_options('cpc_group_members');
	extract( shortcode_atts( array(
		'group_id' => cpc_get_shortcode_value($values, 'cpc_group_members-group_id', ''),
		'role' => cpc_get_shortcode_value($values, 'cpc_group_members-role', ''), // admin|moderator|member
		'show_avatar' => cpc_get_shortcode_value($values, 'cpc_group_members-show_avatar', true),
		'avatar_size' => cpc_get_shortcode_value($values, 'cpc_group_members-avatar_size', 50),
		'show_role' => cpc_get_shortcode_value($values, 'cpc_group_members-show_role', true),
		'limit' => cpc_get_shortcode_value($values, 'cpc_group_members-limit', -1),
		'columns' => cpc_get_shortcode_value($values, 'cpc_group_members-columns', 4),
		'styles' => true,
	), $atts, 'cpc_group_members' ) );

	if (!$group_id && is_singular('cpc_group')):
		$group_id = get_the_ID();
	endif;

	if (!$group_id):
		return '<p>'.__('Gruppe nicht angegeben.', CPC2_TEXT_DOMAIN).'</p>';
	endif;

	// Get current blog ID for multisite support
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;

	$html = '';
	$members = cpc_get_group_members($group_id, 'active', $role, $current_blog_id);

	if ($limit > 0):
		$members = array_slice($members, 0, $limit);
	endif;

	if ($members):
		$html .= '<div class="cpc-group-members">';
		$html .= '<h3>'.__('Mitglieder', CPC2_TEXT_DOMAIN).'</h3>';
		$html .= '<div class="cpc-members-grid cpc-members-cols-'.$columns.'">';
		
		foreach ($members as $member):
			$html .= '<div class="cpc-member-card">';
			
			if ($show_avatar):
				$html .= '<div class="cpc-member-avatar">';
				// Use CPC avatar function if available, otherwise fallback to WordPress avatar
				if (function_exists('user_avatar_get_avatar')):
					$html .= user_avatar_get_avatar($member->ID, $avatar_size);
				else:
					$html .= get_avatar($member->ID, $avatar_size);
				endif;
				$html .= '</div>';
			endif;

			$html .= '<div class="cpc-member-info">';
			$profile_link = cpc_comfile_link($member->ID);
			if ($profile_link):
				$html .= '<div class="cpc-member-name"><a href="'.$profile_link.'">'.$member->display_name.'</a></div>';
			else:
				$html .= '<div class="cpc-member-name">'.$member->display_name.'</div>';
			endif;
			
			if ($show_role && $member->member_role):
				$role_labels = array(
					'admin' => __('Admin', CPC2_TEXT_DOMAIN),
					'moderator' => __('Moderator', CPC2_TEXT_DOMAIN),
					'member' => __('Mitglied', CPC2_TEXT_DOMAIN),
				);
				$role_label = isset($role_labels[$member->member_role]) ? $role_labels[$member->member_role] : $member->member_role;
				
				// Check if this user is the group creator/owner
				$group = get_post($group_id);
				if ($group && $member->ID == $group->post_author && $member->member_role === 'admin'):
					$role_label = __('Gruppenbesitzer', CPC2_TEXT_DOMAIN);
				endif;
				
				$html .= '<div class="cpc-member-role">'.$role_label.'</div>';
			endif;

			$html .= '</div>'; // .cpc-member-info
			$html .= '</div>'; // .cpc-member-card
		endforeach;

		$html .= '</div>'; // .cpc-members-grid
		$html .= '</div>'; // .cpc-group-members
	else:
		$html .= '<p class="cpc-no-members">'.__('Keine Mitglieder gefunden.', CPC2_TEXT_DOMAIN).'</p>';
	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_group_members', '', '', $styles, $values);
	return $html;
}
add_shortcode('cpc-group-members', 'cpc_group_members');

/**
 * [cpc-my-groups] - Display current user's groups
 */
function cpc_my_groups($atts) {
	cpc_groups_init();

	if (!is_user_logged_in()):
		return '<p>'.__('Du musst angemeldet sein, um deine Gruppen zu sehen.', CPC2_TEXT_DOMAIN).'</p>';
	endif;

	$values = cpc_get_shortcode_options('cpc_my_groups');
	extract( shortcode_atts( array(
		'show_avatar' => cpc_get_shortcode_value($values, 'cpc_my_groups-show_avatar', true),
		'avatar_size' => cpc_get_shortcode_value($values, 'cpc_my_groups-avatar_size', 50),
		'show_role' => cpc_get_shortcode_value($values, 'cpc_my_groups-show_role', true),
		'columns' => cpc_get_shortcode_value($values, 'cpc_my_groups-columns', 3),
		'styles' => true,
	), $atts, 'cpc_my_groups' ) );

	$placeholder_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="120" height="120" viewBox="0 0 120 120"><rect fill="#dbeafe" width="120" height="120"/><text x="50%" y="55%" font-size="28" text-anchor="middle" fill="#4b5563" font-family="sans-serif">G</text></svg>';
	$placeholder_avatar = 'data:image/svg+xml;charset=UTF-8,' . rawurlencode($placeholder_svg);

	// Get current blog ID for multisite support
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;

	$html = '';
	$user_id = get_current_user_id();
	$groups = cpc_get_user_groups($user_id, 'active', $current_blog_id);

	if ($groups):
		$html .= '<div class="cpc-my-groups">';
		$html .= '<div class="cpc-groups-grid cpc-groups-cols-'.$columns.'">';

		foreach ($groups as $group):
			$group_type = get_post_meta($group->ID, 'cpc_group_type', true);
			if (!$group_type) $group_type = 'public';
			$user_role = cpc_get_group_member_role($user_id, $group->ID, $current_blog_id);

			$html .= '<div class="cpc-group-card cpc-group-type-'.$group_type.'">';
			
			if ($show_avatar):
				$html .= '<div class="cpc-group-avatar">';
		$html .= '<a href="'.cpc_get_group_link($group->ID).'">';
				if (has_post_thumbnail($group->ID)):
					$html .= get_the_post_thumbnail($group->ID, array($avatar_size, $avatar_size));
				else:
					$html .= '<img src="'.$placeholder_avatar.'" width="'.$avatar_size.'" height="'.$avatar_size.'" alt="" />';
				endif;
				$html .= '</a>';
				$html .= '</div>';
			endif;

			$html .= '<div class="cpc-group-info">';
		$html .= '<h3 class="cpc-group-title"><a href="'.cpc_get_group_link($group->ID).'">'.$group->post_title.'</a></h3>';
			if ($show_role && $user_role):
				$role_labels = array(
					'admin' => __('Admin', CPC2_TEXT_DOMAIN),
					'moderator' => __('Moderator', CPC2_TEXT_DOMAIN),
					'member' => __('Mitglied', CPC2_TEXT_DOMAIN),
				);
				$role_label = isset($role_labels[$user_role]) ? $role_labels[$user_role] : $user_role;
				$html .= '<div class="cpc-group-my-role">'.__('Deine Rolle:', CPC2_TEXT_DOMAIN).' '.$role_label.'</div>';
			endif;

			$html .= '</div>'; // .cpc-group-info
			$html .= '</div>'; // .cpc-group-card
		endforeach;

		$html .= '</div>'; // .cpc-groups-grid
		$html .= '</div>'; // .cpc-my-groups
	else:
		$html .= '<p class="cpc-no-groups">'.__('Du bist noch in keiner Gruppe Mitglied.', CPC2_TEXT_DOMAIN).'</p>';
	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_my_groups', '', '', $styles, $values);
	return $html;
}
add_shortcode('cpc-my-groups', 'cpc_my_groups');

/**
 * [cpc-group-create] - Group creation form
 */
function cpc_group_create($atts) {
	cpc_groups_init();

	if (!is_user_logged_in()):
		return '<p>'.__('Du musst angemeldet sein, um eine Gruppe zu erstellen.', CPC2_TEXT_DOMAIN).'</p>';
	endif;

	$values = cpc_get_shortcode_options('cpc_group_create');
	extract( shortcode_atts( array(
		'redirect' => cpc_get_shortcode_value($values, 'cpc_group_create-redirect', ''),
		'styles' => true,
	), $atts, 'cpc_group_create' ) );

	$html = '';
	$html .= '<div class="cpc-group-create-form">';
	$html .= '<form id="cpc-create-group-form" method="post" enctype="multipart/form-data">';
	
	$html .= '<div class="cpc-form-field">';
	$html .= '<label for="group_name">'.__('Gruppenname', CPC2_TEXT_DOMAIN).' *</label>';
	$html .= '<input type="text" name="group_name" id="group_name" required />';
	$html .= '</div>';

	$html .= '<div class="cpc-form-field">';
	$html .= '<label for="group_description">'.__('Beschreibung', CPC2_TEXT_DOMAIN).'</label>';
	$html .= '<textarea name="group_description" id="group_description" rows="5"></textarea>';
	$html .= '</div>';

	$html .= '<div class="cpc-form-field">';
	$html .= '<label for="group_type">'.__('Gruppentyp', CPC2_TEXT_DOMAIN).' *</label>';
	$html .= '<select name="group_type" id="group_type" required>';
	$html .= '<option value="public">'.__('Öffentlich - Jeder kann sehen und beitreten', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '<option value="private">'.__('Privat - Jeder kann sehen, Beitritt auf Anfrage', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '<option value="hidden">'.__('Versteckt - Nur Mitglieder können sehen', CPC2_TEXT_DOMAIN).'</option>';
	$html .= '</select>';
	$html .= '</div>';

	$html .= '<div class="cpc-form-field">';
	$html .= '<label for="group_avatar">'.__('Gruppenbild (optional)', CPC2_TEXT_DOMAIN).'</label>';
	$html .= '<input type="file" name="group_avatar" id="group_avatar" accept="image/*" />';
	$html .= '</div>';

	$html .= '<input type="hidden" name="cpc_create_group_nonce" value="'.wp_create_nonce('cpc_create_group').'" />';
	$html .= '<input type="hidden" name="redirect_to" value="'.$redirect.'" />';
	
	$html .= '<div class="cpc-form-submit">';
	$html .= '<button type="submit" class="cpc-group-create-submit">'.__('Gruppe erstellen', CPC2_TEXT_DOMAIN).'</button>';
	$html .= '</div>';

	$html .= '</form>';
	$html .= '<div class="cpc-group-create-message"></div>';
	$html .= '</div>'; // .cpc-group-create-form

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_group_create', '', '', $styles, $values);
	return $html;
}
add_shortcode('cpc-group-create', 'cpc_group_create');

/**
 * [cpc-group-join-button] - Simple join button
 */
function cpc_group_join_button($atts) {
	cpc_groups_init();

	if (!is_user_logged_in()):
		return '';
	endif;

	$values = cpc_get_shortcode_options('cpc_group_join_button');
	extract( shortcode_atts( array(
		'group_id' => cpc_get_shortcode_value($values, 'cpc_group_join_button-group_id', ''),
		'join_text' => cpc_get_shortcode_value($values, 'cpc_group_join_button-join_text', __('Beitreten', CPC2_TEXT_DOMAIN)),
		'leave_text' => cpc_get_shortcode_value($values, 'cpc_group_join_button-leave_text', __('Verlassen', CPC2_TEXT_DOMAIN)),
		'styles' => true,
	), $atts, 'cpc_group_join_button' ) );

	if (!$group_id && is_singular('cpc_group')):
		$group_id = get_the_ID();
	endif;

	if (!$group_id):
		return '';
	endif;

	// Get current blog ID for multisite support
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;

	$is_member = cpc_is_group_member(get_current_user_id(), $group_id, $current_blog_id);
	$group_type = get_post_meta($group_id, 'cpc_group_type', true);

	$html = '';
	if ($is_member):
		$html .= '<a href="#" class="cpc-group-leave-btn" data-group-id="'.$group_id.'">'.$leave_text.'</a>';
	else:
		if ($group_type != 'hidden'):
			$html .= '<a href="#" class="cpc-group-join-btn" data-group-id="'.$group_id.'">'.$join_text.'</a>';
		endif;
	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_group_join_button', '', '', $styles, $values);
	return $html;
}
add_shortcode('cpc-group-join-button', 'cpc_group_join_button');

/**
 * [cpc-group-leave-button] - Simple leave button
 */
function cpc_group_leave_button($atts) {
	cpc_groups_init();

	if (!is_user_logged_in()):
		return '';
	endif;

	$values = cpc_get_shortcode_options('cpc_group_leave_button');
	extract( shortcode_atts( array(
		'group_id' => cpc_get_shortcode_value($values, 'cpc_group_leave_button-group_id', ''),
		'text' => cpc_get_shortcode_value($values, 'cpc_group_leave_button-text', __('Gruppe verlassen', CPC2_TEXT_DOMAIN)),
		'styles' => true,
	), $atts, 'cpc_group_leave_button' ) );

	if (!$group_id && is_singular('cpc_group')):
		$group_id = get_the_ID();
	endif;

	if (!$group_id):
		return '';
	endif;

	// Get current blog ID for multisite support
	$current_blog_id = is_multisite() ? get_current_blog_id() : null;

	$is_member = cpc_is_group_member(get_current_user_id(), $group_id, $current_blog_id);

	$html = '';
	if ($is_member):
		$html .= '<a href="#" class="cpc-group-leave-btn" data-group-id="'.$group_id.'">'.$text.'</a>';
	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_group_leave_button', '', '', $styles, $values);
	return $html;
}
add_shortcode('cpc-group-leave-button', 'cpc_group_leave_button');
?>
