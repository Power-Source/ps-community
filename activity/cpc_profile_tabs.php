<?php

/* Profile Tabs Management */

/**
 * Get available tabs for a user profile
 * 
 * @param int $user_id User ID whose profile is being viewed
 * @param int $viewer_id Current user viewing the profile (0 = current user)
 * @return array Array of tabs with keys: label, icon, priority
 */
function cpc_get_profile_tabs($user_id, $viewer_id = 0) {
	if (!$viewer_id) $viewer_id = get_current_user_id();
	
	$tabs = array();
	
	// Activity tab - always visible (main profile content)
	$tabs['activity'] = array(
		'label' => __('Aktivität', CPC2_TEXT_DOMAIN),
		'icon' => 'format-chat',
		'priority' => 10,
	);
	
	// About/Info tab - for viewing user info
	// (kann später für erweiterte Profilinformationen genutzt werden)
	
	// Allow plugins to add tabs
	// PM Integration, Chat, etc. können sich hier einhaken
	$tabs = apply_filters('cpc_profile_tabs', $tabs, $user_id, $viewer_id);
	
	// Validate and sanitize tabs array - ensure all tabs are arrays with required keys
	foreach ($tabs as $tab_id => $tab_data) {
		// Remove invalid tabs (not arrays or missing required keys)
		if (!is_array($tab_data)) {
			unset($tabs[$tab_id]);
			continue;
		}
		
		// Ensure required keys exist with defaults
		if (!isset($tab_data['label'])) {
			$tab_data['label'] = ucfirst($tab_id);
		}
		if (!isset($tab_data['priority'])) {
			$tab_data['priority'] = 50;
		}
		
		// Update tab with sanitized data
		$tabs[$tab_id] = $tab_data;
	}
	
	// Sort by priority (keep string keys!)
	uasort($tabs, function($a, $b) {
		return $a['priority'] - $b['priority'];
	});
	
	return $tabs;
}

/**
 * Render profile tabs navigation
 * 
 * @param int $user_id User ID whose profile is being viewed
 * @param string $active_tab Currently active tab slug
 * @return string HTML for tab navigation
 */
function cpc_render_profile_tabs($user_id, $active_tab = 'activity') {
	$tabs = cpc_get_profile_tabs($user_id);
	
	// Build profile URL
	$profile_page_id = get_option('cpccom_profile_page');
	if (!$profile_page_id) return '';
	
	$profile_url = get_permalink($profile_page_id);
	if (!$profile_url) return '';
	
	// Add user_id to URL
	if (!get_option('cpccom_profile_permalinks')) {
		// Permalink mode: /profile/username
		$user = get_user_by('id', $user_id);
		if ($user) {
			$profile_url = trailingslashit($profile_url) . $user->user_login;
		}
	} else {
		// Query string mode: ?user_id=123
		$profile_url = add_query_arg('user_id', $user_id, $profile_url);
	}
	
	// Remove existing tab parameter
	$profile_url = remove_query_arg('tab', $profile_url);
		// Create nonce for AJAX tab loading
		$nonce = wp_create_nonce('cpc_profile_tab_nonce_' . $user_id);
	
	
	$html = '<div class="cpc-profile-tabs-nav">';
	$html .= '<ul class="cpc-profile-tabs-list" data-user-id="'.esc_attr($user_id).'" data-nonce="'.esc_attr($nonce).'">';
	
	foreach ($tabs as $tab_id => $tab_data):
		$class = 'cpc-profile-tab-item';
		if ($tab_id === $active_tab) $class .= ' active';
		
		$tab_url = add_query_arg('tab', $tab_id, $profile_url);
		
		$html .= '<li class="'.$class.'">';
		$html .= '<a href="'.esc_url($tab_url).'" class="cpc-profile-tab-link" data-tab="'.esc_attr($tab_id).'">';
		
		// Optional icon support (für später)
		if (!empty($tab_data['icon'])) {
			$html .= '<span class="dashicons dashicons-'.$tab_data['icon'].'"></span> ';
		}
		
		$html .= esc_html($tab_data['label']);
		$html .= '</a>';
		$html .= '</li>';
	endforeach;
	
	$html .= '</ul>';
	$html .= '</div>';
	
	return $html;
}

/**
 * Render profile tab content
 * 
 * @param int $user_id User ID whose profile is being viewed
 * @param string $active_tab Currently active tab slug
 * @param array $shortcode_atts Shortcode attributes from cpc_activity_page
 * @return string HTML for tab content
 */
function cpc_render_profile_tab_content($user_id, $active_tab = 'activity', $shortcode_atts = array()) {
	$html = '';
	
	// Ensure shortcode_atts is an array
	if (!is_array($shortcode_atts)) {
		$shortcode_atts = array();
	}
	
	// Initialize activity CSS/JS
	if (function_exists('cpc_activity_init')) {
		cpc_activity_init();
	}
	
	switch ($active_tab):
		case 'activity':
			// Standard activity feed (default profile content)
			$html .= cpc_render_profile_tab_activity($user_id, $shortcode_atts);
			break;
			
		default:
			// Allow plugins to render custom tab content
			$html = apply_filters('cpc_profile_tab_content', $html, $active_tab, $user_id, $shortcode_atts);
			
			// If no plugin handled it, show error
			if (empty($html)) {
				$html .= '<div class="cpc-error">';
				$html .= __('Dieser Tab konnte nicht geladen werden.', CPC2_TEXT_DOMAIN);
				$html .= '</div>';
			}
			break;
	endswitch;
	
	return $html;
}

/**
 * Render activity tab content (default profile view)
 * 
 * @param int $user_id User ID
 * @param array $atts Shortcode attributes
 * @return string HTML content
 */
function cpc_render_profile_tab_activity($user_id, $atts = array()) {
	$html = '';
	
	// Ensure atts is an array
	if (!is_array($atts)) {
		$atts = array();
	}
	
	// Post form
	$html .= cpc_activity_post(array_merge($atts, array('user_id' => $user_id)));
	
	// Activity feed
	$html .= cpc_activity(array_merge($atts, array('user_id' => $user_id)));
	
	return $html;
}

/**
 * Get current active tab from request
 * 
 * @param string $default Default tab if none specified
 * @return string Active tab slug
 */
function cpc_get_active_profile_tab($default = 'activity') {
	$tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : $default;
	
	// Validate tab exists
	// We'll check this in the rendering function
	
	return $tab;
}

/**
 * Check if a specific profile tab exists
 * 
 * @param string $tab_slug Tab slug to check
 * @param int $user_id User ID
 * @return bool
 */
function cpc_profile_tab_exists($tab_slug, $user_id) {
	$tabs = cpc_get_profile_tabs($user_id);
	return isset($tabs[$tab_slug]);
}
