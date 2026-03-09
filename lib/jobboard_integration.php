<?php

/**
 * PS Jobboard Integration for PS Community
 * 
 * Integrates Jobboard into profile pages with:
 * - "Jobboard" tab in profile for inline jobs/experts view
 * - Admin settings for integration options
 * - Smooth AJAX-based navigation within profile context
 */

/* Check if Jobboard is available */

/**
 * Check if PS Jobboard plugin is installed and activated
 * 
 * @return array Status array with keys: installed, active, version
 */
function cpc_jobboard_is_available() {
	// Ensure plugin functions are available
	if (!function_exists('get_plugins')) {
		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}

	$plugin_file = 'ps-jobboard/jobs-experts.php';
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
 * Check if Jobboard integration is enabled in PS Community settings
 * 
 * @return bool
 */
function cpc_jobboard_integration_enabled() {
	$jb_status = cpc_jobboard_is_available();
	if (!$jb_status['active']) {
		return false;
	}
	
	return (bool) get_option('cpc_enable_jobboard_integration', false);
}

/* Profile Tab Integration */

/**
 * Add "Jobboard" tab to profile tabs
 * Hooks into: cpc_profile_tabs
 */
add_filter('cpc_profile_tabs', 'cpc_jobboard_add_profile_tab', 15, 3);
function cpc_jobboard_add_profile_tab($tabs, $user_id, $viewer_id) {
	// Only if Jobboard integration is enabled
	if (!cpc_jobboard_integration_enabled()) {
		return $tabs;
	}
	
	// Only show to the profile owner (viewing their own profile)
	if ($viewer_id != $user_id) {
		return $tabs;
	}
	
	// Add jobboard tab
	$tabs['jobboard'] = array(
		'label' => __('Jobboard', CPC2_TEXT_DOMAIN),
		'icon' => 'portfolio',
		'priority' => 20, // After activity and messages
	);
	
	return $tabs;
}

/**
 * Modify Jobboard button URLs to use je_section parameter in profile context
 * Hooks into: jbp_button_url
 */
add_filter('jbp_button_url', 'cpc_jobboard_filter_button_url', 10, 2);
function cpc_jobboard_filter_button_url($url, $button_type) {
	// Only if integration is enabled
	if (!cpc_jobboard_integration_enabled()) {
		return $url;
	}
	
	// Check if we're in profile context (profile page loaded)
	if (!is_page() || !isset($_GET['user_id']) || !isset($_GET['tab']) || $_GET['tab'] !== 'jobboard') {
		return $url;
	}
	
	// Get the profile page URL
	$profile_page_id = get_option('cpccom_profile_page');
	if (!$profile_page_id) {
		return $url;
	}
	
	$profile_url = get_permalink($profile_page_id);
	if (!$profile_url) {
		return $url;
	}
	
	// Map button types to je_section values
	$section_map = array(
		'landing_page' => 'landing',
		'add_new_job' => 'job-add',
		'add_new_expert' => 'expert-add',
		'job_list' => 'job-list',
		'expert_list' => 'expert-list',
		'my_jobs' => 'my-jobs',
		'my_profiles' => 'my-expert',
		'my_profile' => 'my-expert',
	);
	
	$section = isset($section_map[$button_type]) ? $section_map[$button_type] : 'landing';
	
	// Return profile URL with je_section parameter instead of original URL
	return add_query_arg(array(
		'user_id' => get_current_user_id(),
		'tab' => 'jobboard',
		'je_section' => $section
	), $profile_url);
}

/**
 * Render jobboard tab content
 * Hooks into: cpc_profile_tab_content
 */
add_filter('cpc_profile_tab_content', 'cpc_jobboard_render_tab_content', 10, 4);
function cpc_jobboard_render_tab_content($html, $active_tab, $user_id, $shortcode_atts) {
	// Only handle 'jobboard' tab
	if ($active_tab !== 'jobboard') {
		return $html;
	}
	
	// Only if Jobboard integration is enabled
	if (!cpc_jobboard_integration_enabled()) {
		return '<div class="cpc-error">'.__('Jobboard Integration ist nicht aktiviert.', CPC2_TEXT_DOMAIN).'</div>';
	}
	
	// Only if user is viewing their own profile
	if (get_current_user_id() != $user_id) {
		return '<div class="cpc-error">'.__('Du kannst nur deine eigene Jobboard sehen.', CPC2_TEXT_DOMAIN).'</div>';
	}

	// Render the Jobboard profile panel shortcode
	if (shortcode_exists('jbp-profile-panel')) {
		return do_shortcode('[jbp-profile-panel]');
	}

	return '<div class="cpc-error">'.__('Jobboard Shortcode nicht verfügbar (jbp-profile-panel).', CPC2_TEXT_DOMAIN).'</div>';
}

/* Admin Settings Integration */

/**
 * Add Jobboard integration settings to PS Community integrations page
 * Hooks into: cpc_integrations_settings
 */
add_action('cpc_integrations_settings', 'cpc_jobboard_integration_settings');
function cpc_jobboard_integration_settings() {
	// Handle form submission
	if (isset($_POST['cpc_jobboard_integration_save'])) {
		if (!current_user_can('manage_options')) {
			return;
		}
		
		check_admin_referer('cpc_jobboard_integration_settings');
		
		// Save settings
		update_option('cpc_enable_jobboard_integration', isset($_POST['cpc_enable_jobboard_integration']) ? 1 : 0);
		
		echo '<div class="notice notice-success is-dismissible"><p>';
		echo __('Jobboard-Integrations-Einstellungen gespeichert.', CPC2_TEXT_DOMAIN);
		echo '</p></div>';
	}
	
	$jb_status = cpc_jobboard_is_available();
	$integration_enabled = get_option('cpc_enable_jobboard_integration', false);
	
	?>
	<div class="cpc-integration-box" style="border: 1px solid #ddd; padding: 20px; margin-top: 20px; background-color: #f9f9f9; border-radius: 5px;">
		<h2><?php _e('PS Jobboard Integration', CPC2_TEXT_DOMAIN); ?></h2>
		
		<?php if ($jb_status['installed'] && $jb_status['active']): ?>
			<div class="notice notice-success inline" style="margin: 0 0 20px 0;">
				<p>
					<strong><?php _e('Status:', CPC2_TEXT_DOMAIN); ?></strong>
					<span style="color: #155724;">✓ <?php _e('Aktiviert', CPC2_TEXT_DOMAIN); ?></span>
				</p>
				<p style="margin: 5px 0 0 0;">
					<strong><?php _e('Plugin:', CPC2_TEXT_DOMAIN); ?></strong>
					<?php echo esc_html($jb_status['name']); ?> v<?php echo esc_html($jb_status['version']); ?>
				</p>
			</div>
			
			<form method="post" action="">
				<?php wp_nonce_field('cpc_jobboard_integration_settings'); ?>
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="cpc_enable_jobboard_integration">
								<?php _e('Integration aktivieren', CPC2_TEXT_DOMAIN); ?>
							</label>
						</th>
						<td>
							<label>
								<input type="checkbox" 
									   id="cpc_enable_jobboard_integration" 
									   name="cpc_enable_jobboard_integration" 
									   value="1" 
									   <?php checked($integration_enabled, 1); ?> />
								<?php _e('Jobboard in Profilseiten integrieren', CPC2_TEXT_DOMAIN); ?>
							</label>
							<p class="description">
								<?php _e('Fügt einen "Jobboard"-Tab zu Profilseiten hinzu mit Inline-Navigation für Jobs und Experten.', CPC2_TEXT_DOMAIN); ?>
							</p>
						</td>
					</tr>
				</table>
				
				<p class="submit">
					<button type="submit" name="cpc_jobboard_integration_save" class="button button-primary">
						<?php _e('Einstellungen speichern', CPC2_TEXT_DOMAIN); ?>
					</button>
				</p>
			</form>
			
			<?php if ($integration_enabled): ?>
				<div style="background: #e7f5fe; padding: 15px; border-left: 4px solid #0073aa; margin-top: 20px;">
					<h4 style="margin-top: 0;"><?php _e('Integration aktiv', CPC2_TEXT_DOMAIN); ?></h4>
					<ul style="margin: 0;">
						<li>✓ <?php _e('Jobboard-Tab wird in Profilseiten angezeigt', CPC2_TEXT_DOMAIN); ?></li>
						<li>✓ <?php _e('Smooth AJAX-Navigation innerhalb des Tabs', CPC2_TEXT_DOMAIN); ?></li>
					</ul>
				</div>
			<?php endif; ?>
		
		<?php elseif ($jb_status['installed'] && !$jb_status['active']): ?>
			<div class="notice notice-warning" style="margin: 0 0 20px 0;">
				<p>
					<strong><?php _e('Status:', CPC2_TEXT_DOMAIN); ?></strong>
					<span style="color: #856404;">⚠ <?php _e('Installiert aber deaktiviert', CPC2_TEXT_DOMAIN); ?></span>
				</p>
			</div>
			
			<p>
				<?php _e('PS Jobboard ist installiert, aber nicht aktiviert. Um die Jobboard-Integration nutzen zu können, musst du das Plugin aktivieren.', CPC2_TEXT_DOMAIN); ?>
			</p>
			
		<?php else: ?>
			<div class="notice notice-info" style="margin: 0 0 20px 0;">
				<p>
					<strong><?php _e('Status:', CPC2_TEXT_DOMAIN); ?></strong>
					<span style="color: #0c5460;">ℹ <?php _e('Nicht installiert', CPC2_TEXT_DOMAIN); ?></span>
				</p>
			</div>
			
			<p>
				<?php _e('PS Jobboard ist nicht installiert. Installiere zuerst das Jobboard-Plugin, um diese Integration nutzen zu können.', CPC2_TEXT_DOMAIN); ?>
			</p>
			
		<?php endif; ?>
	</div>
	<?php
}
