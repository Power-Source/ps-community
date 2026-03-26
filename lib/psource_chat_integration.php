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
 * Check if PS-Chat profile status integration can be rendered.
 *
 * @return bool
 */
function cpc_pschat_profile_status_enabled() {
	$pschat = cpc_pschat_is_available();

	return !empty($pschat['active'])
		&& function_exists('psource_chat_get_user_status')
		&& function_exists('psource_chat_update_user_status');
}

/**
 * Check whether chat reachability setting should be available on edit-profile.
 *
 * @return bool
 */
function cpc_pschat_reachability_setting_enabled() {
	if (!cpc_pschat_profile_status_enabled()) {
		return false;
	}

	if (function_exists('cpc_are_friends')) {
		return true;
	}

	if (defined('CPC_CORE_PLUGINS') && strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false) {
		return true;
	}

	if (function_exists('cpc_is_active')) {
		return cpc_is_active('friends') || cpc_is_active('friendships') || cpc_is_active('core-friendships');
	}

	return false;
}

/**
 * Add PS-Chat reachability field to PS Community edit-profile form.
 */
add_filter('cpc_usermeta_change_filter', 'cpc_pschat_add_reachability_field', 20, 3);
function cpc_pschat_add_reachability_field($tabs, $atts, $user_id) {
	if (!cpc_pschat_reachability_setting_enabled()) {
		return $tabs;
	}

	$user_id = (int) $user_id;
	if (!$user_id || !is_user_logged_in() || get_current_user_id() !== $user_id) {
		return $tabs;
	}

	$user_meta = get_user_meta($user_id, 'psource-chat-user', true);
	if (!is_array($user_meta)) {
		$user_meta = array();
	}

	$reachability = isset($user_meta['chat_reachability']) ? sanitize_key($user_meta['chat_reachability']) : 'public';
	if ($reachability !== 'friends') {
		$reachability = 'public';
	}

	$form_html  = '<div class="cpc_usermeta_change_item">';
	$form_html .= '<div class="cpc_usermeta_change_label">' . esc_html__('Private Chat-Erreichbarkeit', 'psource-chat') . '</div>';
	$form_html .= '<select name="cpc_pschat_chat_reachability" id="cpc_pschat_chat_reachability">';
	$form_html .= '<option value="public"' . selected($reachability, 'public', false) . '>' . esc_html__('Öffentlich (alle Nutzer)', 'psource-chat') . '</option>';
	$form_html .= '<option value="friends"' . selected($reachability, 'friends', false) . '>' . esc_html__('Nur Freunde', 'psource-chat') . '</option>';
	$form_html .= '</select>';
	$form_html .= '<div class="cpc_note" style="margin-top:6px;">' . esc_html__('Steuert, wer dich per privatem Chat direkt kontaktieren darf.', 'psource-chat') . '</div>';
	$form_html .= '</div>';

	$tabs_array = get_option('cpc_comfile_tabs');
	$default_tab = isset($tabs_array['cpc_comfile_tab_default_tab']) ? (int) $tabs_array['cpc_comfile_tab_default_tab'] : 1;
	if ($default_tab < 1) {
		$default_tab = 1;
	}
	$tabs[] = array(
		'tab' => $default_tab,
		'html' => $form_html,
		'mandatory' => false,
	);

	return $tabs;
}

/**
 * Save PS-Chat reachability setting from PS Community edit-profile form.
 */
add_action('cpc_usermeta_change_hook', 'cpc_pschat_save_reachability_field', 20, 4);
function cpc_pschat_save_reachability_field($user_id, $atts, $the_post, $the_files) {
	if (!cpc_pschat_reachability_setting_enabled()) {
		return;
	}

	$user_id = (int) $user_id;
	if (!$user_id || !is_user_logged_in() || get_current_user_id() !== $user_id) {
		return;
	}

	if (!isset($the_post['cpc_pschat_chat_reachability'])) {
		return;
	}

	$reachability = sanitize_key($the_post['cpc_pschat_chat_reachability']);
	if ($reachability !== 'friends') {
		$reachability = 'public';
	}

	$user_meta = get_user_meta($user_id, 'psource-chat-user', true);
	if (!is_array($user_meta)) {
		$user_meta = array();
	}

	$user_meta['chat_reachability'] = $reachability;
	update_user_meta($user_id, 'psource-chat-user', $user_meta);
}

/**
 * Get available PS-Chat user statuses.
 *
 * @return array
 */
function cpc_pschat_get_status_options() {
	global $psource_chat;

	if (!isset($psource_chat) || !is_object($psource_chat)) {
		return array();
	}

	if (!isset($psource_chat->_chat_options['user-statuses']) || !is_array($psource_chat->_chat_options['user-statuses'])) {
		return array();
	}

	return $psource_chat->_chat_options['user-statuses'];
}

/**
 * Add PS-Chat status control to the profile header right slot.
 */
add_filter('cpc_profile_slot_content_filter', 'cpc_pschat_profile_slot_status', 10, 5);
function cpc_pschat_profile_slot_status($content, $slot, $user_id, $viewer_id, $atts) {
	if ($slot !== 'profile_header_right') {
		return $content;
	}

	if (!cpc_pschat_profile_status_enabled()) {
		return $content;
	}

	$user_id = (int) $user_id;
	if (!$user_id) {
		return $content;
	}

	$status_options = cpc_pschat_get_status_options();
	if (empty($status_options)) {
		return $content;
	}

	$current_status = psource_chat_get_user_status($user_id);
	if (!$current_status || !isset($status_options[$current_status])) {
		$current_status = key($status_options);
	}

	if (!$current_status || !isset($status_options[$current_status])) {
		return $content;
	}

	$current_label = $status_options[$current_status];
	$current_viewer_id = get_current_user_id();
	$can_edit = $current_viewer_id && ($current_viewer_id === $user_id);

	$status_html = '<div class="cpc-pschat-profile-status cpc-pschat-status-' . esc_attr($current_status) . '">';
		$status_html .= '<span class="cpc-pschat-profile-status-dot" aria-hidden="true"></span>';
		$status_html .= '<div class="cpc-pschat-profile-status-body">';

			if ($can_edit) {
				$status_html .= '<span class="cpc-pschat-profile-status-prefix">' . esc_html__('Chat-Status:', 'psource-chat') . '</span>';
				$status_html .= '<select id="cpc-pschat-status-' . $user_id . '" class="psource-chat-status-widget cpc-pschat-profile-status-select" aria-label="' . esc_attr__('Chat-Status', 'psource-chat') . '">';

				foreach ($status_options as $status_key => $status_label) {
					$selected = selected($status_key, $current_status, false);
					$status_html .= '<option value="' . esc_attr($status_key) . '"' . $selected . '>' . esc_html($status_label) . '</option>';
				}

				$status_html .= '</select>';
			} else {
				$status_html .= '<span class="cpc-pschat-profile-status-prefix">' . esc_html__('Chat-Status:', 'psource-chat') . '</span>';
				$status_html .= '<span class="cpc-pschat-profile-status-value">' . esc_html($current_label) . '</span>';
			}
		$status_html .= '</div>';
	$status_html .= '</div>';

	return $content . $status_html;
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
	if (
		isset($_POST['cpc_events_integrations_submit'])
		&& isset($_POST['cpc_events_integrations_nonce'])
		&& wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cpc_events_integrations_nonce'])), 'cpc_events_integrations_save')
	) {
		$provider_mode = isset($_POST['cpc_events_provider_mode'])
			? sanitize_text_field(wp_unslash($_POST['cpc_events_provider_mode']))
			: 'auto';
		if (!in_array($provider_mode, array('auto', 'internal', 'external'), true)) {
			$provider_mode = 'auto';
		}

		$external_shortcode = isset($_POST['cpc_events_external_shortcode'])
			? sanitize_key(wp_unslash($_POST['cpc_events_external_shortcode']))
			: 'auto';
		$allowed_shortcodes = array('auto', 'eab_archive', 'eab_calendar', 'eab_single', 'eab_expired', 'eab_events_map', 'eab_my_events');
		if (!in_array($external_shortcode, $allowed_shortcodes, true)) {
			$external_shortcode = 'auto';
		}

		update_option('cpc_events_provider_mode', $provider_mode);
		update_option('cpc_events_external_shortcode', $external_shortcode);

		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Events-Integrations-Einstellungen gespeichert.', CPC2_TEXT_DOMAIN) . '</p></div>';
	}

	$events_provider_mode = get_option('cpc_events_provider_mode', 'auto');
	if (!in_array($events_provider_mode, array('auto', 'internal', 'external'), true)) {
		$events_provider_mode = 'auto';
	}

	$events_external_shortcode = get_option('cpc_events_external_shortcode', 'auto');
	$events_shortcode_options = array('auto', 'eab_archive', 'eab_calendar', 'eab_single', 'eab_expired', 'eab_events_map', 'eab_my_events');
	if (!in_array($events_external_shortcode, $events_shortcode_options, true)) {
		$events_external_shortcode = 'auto';
	}

	$events_external_shortcode_disabled = ($events_provider_mode === 'internal') ? ' disabled="disabled"' : '';
	$events_external_shortcode_wrap_style = ($events_provider_mode === 'internal')
		? 'margin-top:12px;opacity:.55;'
		: 'margin-top:12px;';
	
	$pschat_status = cpc_pschat_is_available();
	?>
	
	<div class="wrap">
		<h1><?php _e('Integrationen', CPC2_TEXT_DOMAIN); ?></h1>
		
		<p><?php _e('Verwalte die Integration von PSOURCE Plugins mit PS Community.', CPC2_TEXT_DOMAIN); ?></p>

		<!-- Events Integration Section -->
		<div class="cpc-integration-box" style="border: 1px solid #ddd; padding: 20px; margin-top: 20px; background-color: #f9f9f9; border-radius: 5px;">
			<h2><?php _e('Events Integration (PS Events / events-and-bookings)', CPC2_TEXT_DOMAIN); ?></h2>

			<p><?php _e('Hier steuerst du, wie der Shortcode [cpc-events] rendert: internes Modul oder externer PS-Events-Provider.', CPC2_TEXT_DOMAIN); ?></p>

			<form method="post" action="">
				<?php wp_nonce_field('cpc_events_integrations_save', 'cpc_events_integrations_nonce'); ?>

				<p>
					<label for="cpc_events_provider_mode"><strong><?php _e('Events-Provider', CPC2_TEXT_DOMAIN); ?></strong></label><br />
					<select id="cpc_events_provider_mode" name="cpc_events_provider_mode" style="min-width:320px;">
						<option value="auto"<?php echo selected($events_provider_mode, 'auto', false); ?>><?php _e('Automatisch (extern wenn vorhanden)', CPC2_TEXT_DOMAIN); ?></option>
						<option value="internal"<?php echo selected($events_provider_mode, 'internal', false); ?>><?php _e('Nur internes PS Community Events-Modul', CPC2_TEXT_DOMAIN); ?></option>
						<option value="external"<?php echo selected($events_provider_mode, 'external', false); ?>><?php _e('Nur externer Provider (PS Events)', CPC2_TEXT_DOMAIN); ?></option>
					</select>
				</p>

				<div id="cpc_events_external_shortcode_wrap" style="<?php echo esc_attr($events_external_shortcode_wrap_style); ?>">
					<label for="cpc_events_external_shortcode"><strong><?php _e('Bevorzugter PS-Events-Shortcode', CPC2_TEXT_DOMAIN); ?></strong></label><br />
					<select id="cpc_events_external_shortcode" name="cpc_events_external_shortcode" style="min-width:320px;"<?php echo $events_external_shortcode_disabled; ?>>
						<option value="auto"<?php echo selected($events_external_shortcode, 'auto', false); ?>><?php _e('Automatisch wählen', CPC2_TEXT_DOMAIN); ?></option>
						<option value="eab_archive"<?php echo selected($events_external_shortcode, 'eab_archive', false); ?>>eab_archive</option>
						<option value="eab_calendar"<?php echo selected($events_external_shortcode, 'eab_calendar', false); ?>>eab_calendar</option>
						<option value="eab_single"<?php echo selected($events_external_shortcode, 'eab_single', false); ?>>eab_single</option>
						<option value="eab_expired"<?php echo selected($events_external_shortcode, 'eab_expired', false); ?>>eab_expired</option>
						<option value="eab_events_map"<?php echo selected($events_external_shortcode, 'eab_events_map', false); ?>>eab_events_map</option>
						<option value="eab_my_events"<?php echo selected($events_external_shortcode, 'eab_my_events', false); ?>>eab_my_events</option>
					</select>
					<p class="description" style="margin-top:6px;"><?php _e('Nur relevant bei externem Provider. Bei "Automatisch" wird der erste verfügbare EAB-Shortcode verwendet.', CPC2_TEXT_DOMAIN); ?></p>
				</div>

				<p style="margin-top:16px;">
					<button type="submit" name="cpc_events_integrations_submit" value="1" class="button button-primary"><?php _e('Events-Integration speichern', CPC2_TEXT_DOMAIN); ?></button>
				</p>
			</form>

			<script>
			(function(){
				var mode=document.getElementById('cpc_events_provider_mode');
				var ext=document.getElementById('cpc_events_external_shortcode');
				var wrap=document.getElementById('cpc_events_external_shortcode_wrap');
				if(!mode||!ext||!wrap){return;}
				var sync=function(){
					var internal=(mode.value==='internal');
					ext.disabled=internal;
					wrap.style.opacity=internal?'0.55':'1';
				};
				mode.addEventListener('change',sync);
				sync();
			})();
			</script>
		</div>
		
		<!-- PS-Chat Integration Section -->
		<div class="cpc-integration-box" style="border: 1px solid #ddd; padding: 20px; margin-top: 20px; background-color: #f9f9f9; border-radius: 5px;">
			<h2><?php _e('PS-Chat Integration', CPC2_TEXT_DOMAIN); ?></h2>
			
			<?php if ($pschat_status['installed'] && $pschat_status['active']): ?>
				<div style="margin:0 0 20px 0; padding:10px 12px; border:1px solid #b7dfc3; border-left:4px solid #46b450; background:#fff;">
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
				<div style="margin:0 0 20px 0; padding:10px 12px; border:1px solid #f2d7b0; border-left:4px solid #ffb900; background:#fff;">
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
				<div style="margin:0 0 20px 0; padding:10px 12px; border:1px solid #b8daff; border-left:4px solid #00a0d2; background:#fff;">
					<p>
						<strong><?php _e('Status:', CPC2_TEXT_DOMAIN); ?></strong>
						<span style="color: #0c5460;">ℹ <?php _e('Nicht installiert', CPC2_TEXT_DOMAIN); ?></span>
					</p>
				</div>
				
				<p>
					<?php _e('PS-Chat ist nicht installiert. Um Gruppen-Chats zu aktivieren, musst du zuerst das PS-Chat Plugin installieren.', CPC2_TEXT_DOMAIN); ?>
				</p>
				
				<p>
					<a href="https://psource.eimen.net//ps-chat/releases/latest" target="_blank" class="button">
						<?php _e('PS-Chat herunterladen', CPC2_TEXT_DOMAIN); ?>
					</a>
				</p>
			<?php endif; ?>
		</div>
		
		<!-- Additional integrations via action hook -->
		<?php do_action('cpc_integrations_settings'); ?>
		
	</div>
	
	<?php
}
