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
	
	$current_url = add_query_arg(array());
	$current_url = remove_query_arg('tab', $current_url);
	
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
	$html = '';
	
	switch ($active_tab):
		case 'overview':
			$html .= cpc_render_group_tab_overview($group_id, $shortcode_atts);
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
	$html .= '<!-- Aktivitäts-Tab für Gruppe '.$group_id.' -->';
	
	// Get activity posts for this group
	$args = array(
		'post_type' => 'cpc_activity',
		'posts_per_page' => 20,
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
	else:
		$html .= '<p class="cpc-no-activity">'.__('Noch keine Aktivität in dieser Gruppe.', CPC2_TEXT_DOMAIN).'</p>';
	endif;
	
	// Post activity form if member
	if (is_user_logged_in() && cpc_is_group_member(get_current_user_id(), $group_id)):
		$html .= cpc_render_group_activity_form($group_id);
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
	
	$html .= '<hr>';
	$html .= '<h3>'.__('Mitglieder verwalten', CPC2_TEXT_DOMAIN).'</h3>';
	
	// Get pending membership requests
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
	$html .= '<small>'.human_time_diff($post->post_date_gmt, current_time('mysql', true)).' '.__('ago', CPC2_TEXT_DOMAIN).'</small>';
	$html .= '</div>';
	
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
