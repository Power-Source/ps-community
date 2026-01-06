<?php

/* PS-Chat Integration for PS Community Groups */

/**
 * Check if PS-Chat plugin is installed and activated
 * 
 * @return array Status array with keys: installed, active, version
 */
function cpc_pschat_is_available() {
	// Ensure plugin functions are available in frontend contexts
	if (!function_exists('get_plugins')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugin_file = 'ps-chat/psource-chat.php';
	$all_plugins = get_plugins();
	$is_installed = isset($all_plugins[$plugin_file]);
	$is_active = is_plugin_active($plugin_file);
	
	$status = array(
		'installed' => $is_installed,
		'active' => $is_active,
		'version' => $is_installed ? $all_plugins[$plugin_file]['Version'] : null,
		'name' => $is_installed ? $all_plugins[$plugin_file]['Name'] : null,
	);
	
	return $status;
}

/**
 * Check if group chats are enabled globally
 * 
 * @return bool
 */
function cpc_group_chats_enabled() {
	$pschat = cpc_pschat_is_available();
	if (!$pschat['active']) {
		return false;
	}
	
	return (bool) get_option('cpc_enable_group_chats', false);
}

/**
 * Check if a specific group has chat enabled
 * 
 * @param int $group_id
 * @return bool
 */
function cpc_group_has_chat($group_id) {
	if (!cpc_group_chats_enabled()) {
		return false;
	}
	
	return (bool) get_post_meta($group_id, 'cpc_group_has_chat', true);
}

/**
 * Get chat configuration for a group with all PS-Chat shortcode attributes
 * 
 * @param int $group_id
 * @return array Chat configuration with all attributes
 */
function cpc_get_group_chat_config($group_id) {
	// Default PS-Chat shortcode attributes
	$defaults = array(
		'box_title' => '',
		'emoticons' => 'disabled',
		'row_time' => 'disabled',
		'users_list_position' => 'none',
		'sound' => 'enabled',
		'file_uploads_enabled' => 'disabled',
		'log_creation' => 'disabled',
	);
	
	// Get saved config or use defaults
	$saved_config = get_post_meta($group_id, 'cpc_group_chat_config', true);
	
	if (!is_array($saved_config)) {
		$saved_config = array();
	}
	
	// Merge with defaults
	$config = array_merge($defaults, $saved_config);
	
	return $config;
}

/**
 * Save group chat configuration with all PS-Chat attributes
 * 
 * @param int $group_id
 * @param array $config Configuration array
 */
function cpc_save_group_chat_config($group_id, $config) {
	if (!is_array($config)) {
		return false;
	}
	
	// Save the configuration
	update_post_meta($group_id, 'cpc_group_chat_config', $config);
	
	return true;
}

/**
 * Generate PS-Chat shortcode string from group config
 * 
 * @param int $group_id
 * @return string The [chat] shortcode with attributes
 */
function cpc_generate_group_chat_shortcode($group_id) {
	$group = get_post($group_id);
	if (!$group) {
		return '';
	}
	
	$config = cpc_get_group_chat_config($group_id);
	
	// Build shortcode attributes
	$atts = array();

	// Required: unique session ID for the group
	$atts['id'] = 'group-' . $group_id;

	// Use BuddyPress session type only when BuddyPress is present to avoid fatal BP calls inside PS-Chat
	$atts['session_type'] = function_exists('bp_get_group_permalink') ? 'bp-group' : 'page';
	
	// Chat title
	if (!empty($config['box_title'])) {
		$atts['box_title'] = esc_attr($config['box_title']);
	}
	
	// Emojis
	if ($config['emoticons'] === 'enabled') {
		$atts['box_emoticons'] = 'enabled';
	}
	
	// Timestamps
	if ($config['row_time'] === 'enabled') {
		$atts['row_time'] = 'enabled';
	}
	
	// User list position
	if ($config['users_list_position'] !== 'none') {
		$atts['users_list_position'] = $config['users_list_position'];
		$atts['users_list_show'] = 'both';
	}
	
	// Sound
	if ($config['sound'] === 'enabled') {
		$atts['box_sound'] = 'enabled';
	}
	
	// File uploads
	if ($config['file_uploads_enabled'] === 'enabled') {
		$atts['file_uploads_enabled'] = 'enabled';
	}
	
	// Logging
	if ($config['log_creation'] === 'enabled') {
		$atts['log_creation'] = 'enabled';
		$atts['log_display'] = 'enabled-link-below';
	}
	
	// Build shortcode string
	$shortcode = '[chat';
	foreach ($atts as $key => $value) {
		$shortcode .= ' ' . esc_attr($key) . '="' . esc_attr($value) . '"';
	}
	$shortcode .= ']';
	
	return $shortcode;
}

/**
 * Render PS-Chat integration admin page
 */
function cpc_integrations_page() {
	// Check permissions
	if (!current_user_can('manage_options')) {
		wp_die('Keine Berechtigung');
	}
	
	// Handle form submissions
	
	$pschat_status = cpc_pschat_is_available();
	?>
	
	<div class="wrap">
		<h1><?php _e('Integrationen', CPC2_TEXT_DOMAIN); ?></h1>
		
		<p><?php _e('Verwalte die Integration von PSOURCE Plugins mit PS Community.', CPC2_TEXT_DOMAIN); ?></p>
		
		<!-- PS-Chat Integration Section -->
		<div class="cpc-integration-box" style="border: 1px solid #ddd; padding: 20px; margin-top: 20px; background-color: #f9f9f9; border-radius: 5px;">
			<h2><?php _e('PS-Chat Integration', CPC2_TEXT_DOMAIN); ?></h2>
			
			<?php if ($pschat_status['installed'] && $pschat_status['active']): ?>
				<div class="notice notice-success" style="margin: 0 0 20px 0;">
					<p>
						<strong><?php _e('Status:', CPC2_TEXT_DOMAIN); ?></strong>
						<span style="color: #155724;">✓ <?php _e('Aktiviert', CPC2_TEXT_DOMAIN); ?></span>
					</p>
					<p style="margin: 5px 0 0 0;">
						<strong><?php _e('Plugin:', CPC2_TEXT_DOMAIN); ?></strong>
						<?php echo esc_html($pschat_status['name']); ?> v<?php echo esc_html($pschat_status['version']); ?>
					</p>
				</div>
				
				<p><?php _e('PS-Chat ist erfolgreich mit PS Community integriert.', CPC2_TEXT_DOMAIN); ?></p>
				
				<p style="background: #f0f0f0; padding: 10px; border-left: 4px solid #0073aa;">
					<strong><?php _e('Hinweis:', CPC2_TEXT_DOMAIN); ?></strong><br />
					<?php _e('Die Einstellungen für Gruppen-Chats findest du unter Einstellungen → Gruppen (Tab "Gruppen").', CPC2_TEXT_DOMAIN); ?>
				</p>
				
			<?php elseif ($pschat_status['installed'] && !$pschat_status['active']): ?>
				<div class="notice notice-warning" style="margin: 0 0 20px 0;">
					<p>
						<strong><?php _e('Status:', CPC2_TEXT_DOMAIN); ?></strong>
						<span style="color: #856404;">⚠ <?php _e('Installiert aber deaktiviert', CPC2_TEXT_DOMAIN); ?></span>
					</p>
				</div>
				
				<p>
					<?php _e('PS-Chat ist installiert, aber nicht aktiviert. Um Gruppen-Chats nutzen zu können, musst du das Plugin aktivieren.', CPC2_TEXT_DOMAIN); ?>
				</p>
				
				<p>
					<a href="<?php echo esc_url(wp_nonce_url(
						admin_url('plugins.php?action=activate&plugin=ps-chat/psource-chat.php'),
						'activate-plugin_ps-chat/psource-chat.php'
					)); ?>" class="button button-primary">
						<?php _e('PS-Chat aktivieren', CPC2_TEXT_DOMAIN); ?>
					</a>
				</p>
				
			<?php else: ?>
				<div class="notice notice-info" style="margin: 0 0 20px 0;">
					<p>
						<strong><?php _e('Status:', CPC2_TEXT_DOMAIN); ?></strong>
						<span style="color: #0c5460;">ℹ <?php _e('Nicht installiert', CPC2_TEXT_DOMAIN); ?></span>
					</p>
				</div>
				
				<p>
					<?php _e('PS-Chat ist nicht installiert. Um Gruppen-Chats zu aktivieren, musst du zuerst das PS-Chat Plugin installieren.', CPC2_TEXT_DOMAIN); ?>
				</p>
				
				<p>
					<a href="https://github.com/Power-Source/ps-chat/releases/latest" target="_blank" class="button">
						<?php _e('PS-Chat herunterladen', CPC2_TEXT_DOMAIN); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		
		<!-- Placeholder for future integrations -->
		<div class="cpc-integration-box" style="border: 1px solid #ddd; padding: 20px; margin-top: 20px; background-color: #f9f9f9; border-radius: 5px; opacity: 0.6;">
			<h2><?php _e('Weitere Integrationen folgen', CPC2_TEXT_DOMAIN); ?></h2>
			<p><?php _e('Platz für zusätzliche PSOURCE Plugins', CPC2_TEXT_DOMAIN); ?></p>
		</div>
	</div>
	
	<?php
}
