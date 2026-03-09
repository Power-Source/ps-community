<?php

/**
 * PS PM-System Integration for PS Community
 * 
 * Integrates Private Messaging into profile pages with:
 * - Profile header badge showing unread message count
 * - "Nachrichten" tab in profile for inline inbox view
 * - Redirect from standalone inbox page to profile tab
 * - Admin settings for integration options
 */

/* Check if PM System is available */

/**
 * Check if PS PM-System plugin is installed and activated
 * 
 * @return array Status array with keys: installed, active, version
 */
function cpc_pm_is_available() {
	// Ensure plugin functions are available
	if (!function_exists('get_plugins')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugin_file = 'private-messaging/messaging.php';
	$all_plugins = get_plugins();
	$is_installed = isset($all_plugins[$plugin_file]);
	$is_active = is_plugin_active($plugin_file);
	
	$status = array(
		'installed' => $is_installed,
		'active' => $is_active,
		'version' => $is_installed ? $all_plugins[$plugin_file]['Version'] : null,
		'name' => $is_installed ? $all_plugins[$plugin_file]['Name'] : null,
		'file' => $plugin_file,
	);
	
	return $status;
}

/**
 * Check if PM integration is enabled in PS Community settings
 * 
 * @return bool
 */
function cpc_pm_integration_enabled() {
	$pm_status = cpc_pm_is_available();
	if (!$pm_status['active']) {
		return false;
	}
	
	return (bool) get_option('cpc_enable_pm_integration', false);
}

/**
 * Check if PM contacts should be restricted to friends only
 * 
 * @return bool
 */
function cpc_pm_friends_only() {
	if (!cpc_pm_integration_enabled()) {
		return false;
	}
	
	return (bool) get_option('cpc_pm_friends_only', false);
}

/* Profile Slot Integration (unread message badge) */

/**
 * Add unread messages badge to profile header slot
 * Hooks into: cpc_profile_slot_content_filter
 */
add_filter('cpc_profile_slot_content_filter', 'cpc_pm_profile_slot_badge', 10, 5);
function cpc_pm_profile_slot_badge($content, $slot, $user_id, $viewer_id, $atts) {
	// Only add to profile_header_right slot
	if ($slot !== 'profile_header_right') {
		return $content;
	}
	
	// Only if PM integration is enabled
	if (!cpc_pm_integration_enabled()) {
		return $content;
	}
	
	// Only show to the profile owner (viewing their own profile)
	if ($viewer_id != $user_id) {
		return $content;
	}
	
	// Only if user is logged in
	if (!is_user_logged_in()) {
		return $content;
	}
	
	// Get unread count (requires PM plugin functions)
	if (!class_exists('MM_Conversation_Model')) {
		return $content;
	}
	
	$unread_count = MM_Conversation_Model::count_unread(true);
	
	// Build profile tab URL for messages
	$profile_page_id = get_option('cpccom_profile_page');
	if ($profile_page_id) {
		$profile_url = get_permalink($profile_page_id);
		if ($profile_url) {
			$profile_url = add_query_arg(array(
				'user_id' => $user_id,
				'tab' => 'messages'
			), $profile_url);
		}
	} else {
		$profile_url = '#';
	}
	
	// Build badge HTML
	$badge_html = '<div class="cpc-pm-profile-badge">';
		$badge_html .= '<a href="'.esc_url($profile_url).'" class="cpc-pm-badge-link">';
			$badge_html .= '<span class="cpc-pm-icon">✉</span>';
			$badge_html .= '<span class="cpc-pm-label">'.__('Nachrichten', CPC2_TEXT_DOMAIN).'</span>';
			if ($unread_count > 0) {
				$badge_html .= '<span class="cpc-pm-unread-badge">'.$unread_count.'</span>';
			}
		$badge_html .= '</a>';
	$badge_html .= '</div>';
	
	return $content . $badge_html;
}

/* Profile Tab Integration */

/**
 * Add "Nachrichten" tab to profile tabs
 * Hooks into: cpc_profile_tabs
 */
add_filter('cpc_profile_tabs', 'cpc_pm_add_profile_tab', 10, 3);
function cpc_pm_add_profile_tab($tabs, $user_id, $viewer_id) {
	// Only if PM integration is enabled
	if (!cpc_pm_integration_enabled()) {
		return $tabs;
	}
	
	// Only show to the profile owner (viewing their own profile)
	if ($viewer_id != $user_id) {
		return $tabs;
	}
	
	// Add messages tab
	$tabs['messages'] = array(
		'label' => __('Nachrichten', CPC2_TEXT_DOMAIN),
		'icon' => 'email',
		'priority' => 15, // After activity (10), before other tabs
	);
	
	return $tabs;
}

/**
 * Render messages tab content (inbox shortcode)
 * Hooks into: cpc_profile_tab_content
 */
add_filter('cpc_profile_tab_content', 'cpc_pm_render_tab_content', 10, 4);
function cpc_pm_render_tab_content($html, $active_tab, $user_id, $shortcode_atts) {
	// Only handle 'messages' tab
	if ($active_tab !== 'messages') {
		return $html;
	}
	
	// Only if PM integration is enabled
	if (!cpc_pm_integration_enabled()) {
		return '<div class="cpc-error">'.__('Private Nachrichten Integration ist nicht aktiviert.', CPC2_TEXT_DOMAIN).'</div>';
	}
	
	// Only if user is viewing their own profile
	if (get_current_user_id() != $user_id) {
		return '<div class="cpc-error">'.__('Du kannst nur deine eigenen Nachrichten sehen.', CPC2_TEXT_DOMAIN).'</div>';
	}

	$inbox_html = '';

	// Ensure PM assets are registered in AJAX context before inbox rendering
	if (function_exists('mmg')) {
		$pm_instance = mmg();
		if (is_object($pm_instance) && method_exists($pm_instance, 'scripts')) {
			$pm_instance->scripts();
		}
	}

	// Preferred: render directly via PM controller (works more reliably in AJAX context)
	if (function_exists('mmg')) {
		$pm_instance = mmg();
		if (is_object($pm_instance) && isset($pm_instance->global['inbox_sc']) && is_object($pm_instance->global['inbox_sc']) && method_exists($pm_instance->global['inbox_sc'], 'inbox')) {
			$inbox_html = $pm_instance->global['inbox_sc']->inbox(array('nav_view' => 'both'));
		}
	}

	// Fallback: shortcode rendering
	if (empty(trim((string) $inbox_html)) && shortcode_exists('message_inbox')) {
		$inbox_html = do_shortcode('[message_inbox nav_view="both"]');
	}

	if (!empty(trim((string) $inbox_html))) {
		return $inbox_html;
	}

	return '<div class="cpc-error">'.__('Inbox konnte nicht gerendert werden (leere Ausgabe).', CPC2_TEXT_DOMAIN).'</div>';
}

/* Redirect standalone inbox page to profile tab */

/**
 * Redirect inbox page to profile messages tab if integration is enabled
 * Only redirects when viewing the page with [message_inbox] shortcode
 */
add_action('template_redirect', 'cpc_pm_redirect_inbox_to_profile', 5);
function cpc_pm_redirect_inbox_to_profile() {
	// Only if PM integration is enabled
	if (!cpc_pm_integration_enabled()) {
		return;
	}
	
	// Only if redirect option is enabled
	if (!get_option('cpc_pm_redirect_inbox', false)) {
		return;
	}
	
	// Only if user is logged in
	if (!is_user_logged_in()) {
		return;
	}
	
	global $post;
	
	// Check if current page has [message_inbox] shortcode
	if (!is_a($post, 'WP_Post')) {
		return;
	}
	
	if (!has_shortcode($post->post_content, 'message_inbox')) {
		return;
	}
	
	// Don't redirect if already on profile page (prevents loop)
	$profile_page_id = get_option('cpccom_profile_page');
	if ($post->ID == $profile_page_id) {
		return;
	}
	
	// Build profile URL with messages tab
	$profile_url = get_permalink($profile_page_id);
	if ($profile_url) {
		$redirect_args = array(
			'user_id' => get_current_user_id(),
			'tab' => 'messages'
		);

		// Preserve selected inbox box (inbox/unread/read/sent/archive) to avoid redirect loops
		if (isset($_GET['box'])) {
			$box = sanitize_key(wp_unslash($_GET['box']));
			if (!empty($box)) {
				$redirect_args['box'] = $box;
			}
		}

		$profile_url = add_query_arg($redirect_args, $profile_url);
		
		wp_redirect($profile_url);
		exit;
	}
}

/* Filter contacts (friends only mode) */

/**
 * Filter PM recipients to friends only if enabled
 * This would hook into PM system's user suggestion AJAX
 */
add_filter('mm_suggest_users_query', 'cpc_pm_filter_contacts_friends_only', 10, 2);
function cpc_pm_filter_contacts_friends_only($query_args, $search_term) {
	// Only if friends-only mode is enabled
	if (!cpc_pm_friends_only()) {
		return $query_args;
	}
	
	// Only if friendships module is active
	if (strpos(CPC_CORE_PLUGINS, 'core-friendships') === false) {
		return $query_args;
	}
	
	// Get current user's friends
	$current_user_id = get_current_user_id();
	$friends = cpc_pm_get_friend_ids($current_user_id);
	
	if (empty($friends)) {
		// No friends - return empty result
		$query_args['include'] = array(0); // Non-existent user
	} else {
		// Restrict to friends only
		$query_args['include'] = $friends;
	}
	
	return $query_args;
}

/* Admin Settings Integration */

/**
 * Add PM integration settings to PS Community integrations page
 * Hooks into: cpc_integrations_settings
 */
add_action('cpc_integrations_settings', 'cpc_pm_integration_settings');
function cpc_pm_integration_settings() {
	// Handle form submission
	if (isset($_POST['cpc_pm_integration_save'])) {
		if (!current_user_can('manage_options')) {
			return;
		}
		
		check_admin_referer('cpc_pm_integration_settings');
		
		// Save settings
		update_option('cpc_enable_pm_integration', isset($_POST['cpc_enable_pm_integration']) ? 1 : 0);
		update_option('cpc_pm_redirect_inbox', isset($_POST['cpc_pm_redirect_inbox']) ? 1 : 0);
		update_option('cpc_pm_friends_only', isset($_POST['cpc_pm_friends_only']) ? 1 : 0);
		
		echo '<div class="notice notice-success is-dismissible"><p>';
		echo __('PM-Integrations-Einstellungen gespeichert.', CPC2_TEXT_DOMAIN);
		echo '</p></div>';
	}
	
	$pm_status = cpc_pm_is_available();
	$integration_enabled = get_option('cpc_enable_pm_integration', false);
	$redirect_enabled = get_option('cpc_pm_redirect_inbox', false);
	$friends_only = get_option('cpc_pm_friends_only', false);
	$friendships_available = strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false;
	
	?>
	<div class="cpc-integration-box" style="border: 1px solid #ddd; padding: 20px; margin-top: 20px; background-color: #f9f9f9; border-radius: 5px;">
		<h2><?php _e('PS PM-System Integration', CPC2_TEXT_DOMAIN); ?></h2>
		
		<?php if ($pm_status['installed'] && $pm_status['active']): ?>
			<div class="notice notice-success inline" style="margin: 0 0 20px 0;">
				<p>
					<strong><?php _e('Status:', CPC2_TEXT_DOMAIN); ?></strong>
					<span style="color: #155724;">✓ <?php _e('Aktiviert', CPC2_TEXT_DOMAIN); ?></span>
				</p>
				<p style="margin: 5px 0 0 0;">
					<strong><?php _e('Plugin:', CPC2_TEXT_DOMAIN); ?></strong>
					<?php echo esc_html($pm_status['name']); ?> v<?php echo esc_html($pm_status['version']); ?>
				</p>
			</div>
			
			<form method="post" action="">
				<?php wp_nonce_field('cpc_pm_integration_settings'); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="cpc_enable_pm_integration">
								<?php _e('Integration aktivieren', CPC2_TEXT_DOMAIN); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
									   id="cpc_enable_pm_integration" 
									   name="cpc_enable_pm_integration" 
									   value="1" 
									   <?php checked($integration_enabled, 1); ?> />
								<?php _e('PM-System in Profilseiten integrieren', CPC2_TEXT_DOMAIN); ?>
							</label>
							<p class="description">
								<?php _e('Fügt einen "Nachrichten"-Tab zu Profilseiten hinzu und zeigt ungelesene Nachrichten im Profil-Header an.', CPC2_TEXT_DOMAIN); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="cpc_pm_redirect_inbox">
								<?php _e('Inbox-Seite umleiten', CPC2_TEXT_DOMAIN); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
									   id="cpc_pm_redirect_inbox" 
									   name="cpc_pm_redirect_inbox" 
									   value="1" 
									   <?php checked($redirect_enabled, 1); ?>
									   <?php disabled(!$integration_enabled); ?> />
								<?php _e('Eigenständige Inbox-Seite zum Profil-Tab umleiten', CPC2_TEXT_DOMAIN); ?>
							</label>
							<p class="description">
								<?php _e('Wenn aktiviert, wird die Seite mit [message_inbox] Shortcode automatisch zum Profil-Nachrichten-Tab umgeleitet.', CPC2_TEXT_DOMAIN); ?>
							</p>
						</td>
					</tr>
					
					<tr>
						<th scope="row">
							<label for="cpc_pm_friends_only">
								<?php _e('Nur Freunde kontaktieren', CPC2_TEXT_DOMAIN); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
									   id="cpc_pm_friends_only" 
									   name="cpc_pm_friends_only" 
									   value="1" 
									   <?php checked($friends_only, 1); ?>
									   <?php disabled(!$integration_enabled || !$friendships_available); ?> />
								<?php _e('Kontaktliste auf Freunde beschränken', CPC2_TEXT_DOMAIN); ?>
							</label>
							<p class="description">
								<?php 
								if ($friendships_available) {
									_e('Benutzer können nur Nachrichten an ihre Freunde senden (Friendships-Modul erforderlich).', CPC2_TEXT_DOMAIN);
								} else {
									echo '<strong style="color: #856404;">';
									_e('⚠ Friendships-Modul ist nicht aktiviert. Diese Option erfordert das Friendships-Modul.', CPC2_TEXT_DOMAIN);
									echo '</strong>';
								}
								?>
							</p>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<button type="submit" name="cpc_pm_integration_save" class="button button-primary">
						<?php _e('Einstellungen speichern', CPC2_TEXT_DOMAIN); ?>
					</button>
				</p>
			</form>
			
			<?php if ($integration_enabled): ?>
				<div style="background: #e7f5fe; padding: 15px; border-left: 4px solid #0073aa; margin-top: 20px;">
					<h4 style="margin-top: 0;"><?php _e('Integration aktiv', CPC2_TEXT_DOMAIN); ?></h4>
					<ul style="margin: 0;">
						<li>✓ <?php _e('Nachrichten-Tab wird in Profilseiten angezeigt', CPC2_TEXT_DOMAIN); ?></li>
						<li>✓ <?php _e('Ungelesene Nachrichten Badge im Profil-Header', CPC2_TEXT_DOMAIN); ?></li>
						<?php if ($redirect_enabled): ?>
							<li>✓ <?php _e('Inbox-Seite leitet zum Profil-Tab um', CPC2_TEXT_DOMAIN); ?></li>
						<?php endif; ?>
						<?php if ($friends_only && $friendships_available): ?>
							<li>✓ <?php _e('Kontakte auf Freunde beschränkt', CPC2_TEXT_DOMAIN); ?></li>
						<?php endif; ?>
					</ul>
				</div>
			<?php endif; ?>
			
		<?php elseif ($pm_status['installed'] && !$pm_status['active']): ?>
			<div class="notice notice-warning inline" style="margin: 0 0 20px 0;">
				<p>
					<strong><?php _e('Status:', CPC2_TEXT_DOMAIN); ?></strong>
					<span style="color: #856404;">⚠ <?php _e('Installiert aber deaktiviert', CPC2_TEXT_DOMAIN); ?></span>
				</p>
			</div>
			
			<p>
				<?php _e('PS PM-System ist installiert, aber nicht aktiviert. Um die Integration nutzen zu können, musst du das Plugin aktivieren.', CPC2_TEXT_DOMAIN); ?>
			</p>
			
			<p>
				<a href="<?php echo esc_url(wp_nonce_url(
					admin_url('plugins.php?action=activate&plugin=' . $pm_status['file']),
					'activate-plugin_' . $pm_status['file']
				)); ?>" class="button button-primary">
					<?php _e('PS PM-System aktivieren', CPC2_TEXT_DOMAIN); ?>
				</a>
			</p>
			
		<?php else: ?>
			<div class="notice notice-info inline" style="margin: 0 0 20px 0;">
				<p>
					<strong><?php _e('Status:', CPC2_TEXT_DOMAIN); ?></strong>
					<span style="color: #0c5460;">ℹ <?php _e('Nicht installiert', CPC2_TEXT_DOMAIN); ?></span>
				</p>
			</div>
			
			<p>
				<?php _e('PS PM-System ist nicht installiert. Um die Integration zu nutzen, musst du zuerst das Plugin installieren.', CPC2_TEXT_DOMAIN); ?>
			</p>
			
			<p>
				<a href="https://github.com/Power-Source/private-messaging/releases/latest" target="_blank" class="button">
					<?php _e('PS PM-System herunterladen', CPC2_TEXT_DOMAIN); ?>
				</a>
			</p>
		<?php endif; ?>
	</div>
	<?php
}

/* Helper function to get user's friend IDs as simple array (used by friends-only filter) */
function cpc_pm_get_friend_ids($user_id) {
	// Use existing PS Community friendships function
	if (!function_exists('cpc_get_friends')) {
		return array();
	}
	
	// Get friends using core friendships function (returns array of arrays with 'ID' key)
	$friends_data = cpc_get_friends($user_id, false);
	
	// Convert to simple array of IDs
	$friend_ids = array();
	foreach ($friends_data as $friend) {
		if (isset($friend['ID'])) {
			$friend_ids[] = $friend['ID'];
		}
	}
	
	return $friend_ids;
}

