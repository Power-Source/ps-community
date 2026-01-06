<?php

/* PS Community Groups - Admin Settings */

/**
 * Add Groups settings section to main admin setup page
 */
add_action('cpc_admin_getting_started_options_hook', 'cpc_groups_settings_section', 10);
function cpc_groups_settings_section() {
	if (strpos(CPC_CORE_PLUGINS, 'core-groups') === false) {
		return; // Groups not enabled
	}
	
	// Handle form submission for groups settings
	if (isset($_POST['cpc_groups_settings_update']) && $_POST['cpc_groups_settings_update'] == 'yes') {
		check_admin_referer('cpc_groups_settings_nonce');
		
		// Update group chats option
		if (isset($_POST['cpc_enable_group_chats'])) {
			update_option('cpc_enable_group_chats', 1);
		} else {
			delete_option('cpc_enable_group_chats');
		}
		
		echo '<div class="notice notice-success"><p>';
		echo esc_html__('Gruppen-Einstellungen gespeichert!', CPC2_TEXT_DOMAIN);
		echo '</p></div>';
	}
	
	$group_chats_enabled = (bool) get_option('cpc_enable_group_chats', false);
	$pschat_status = cpc_pschat_is_available();
	?>
	
	<div class="cpc-groups-settings-section" style="margin-bottom: 20px;">
		<div class="cpc_admin_getting_started_menu_item">
			<?php _e('Gruppen-Einstellungen', CPC2_TEXT_DOMAIN); ?>
		</div>
		
		<div class="cpc_admin_getting_started_content" style="display: block;">
			<form method="post" action="">
				<?php wp_nonce_field('cpc_groups_settings_nonce'); ?>
				<input type="hidden" name="cpc_groups_settings_update" value="yes" />
				
				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="cpc_enable_group_chats">
								<?php _e('Gruppen-Chats', CPC2_TEXT_DOMAIN); ?>
							</label>
						</th>
						<td>
							<?php if (!$pschat_status['active']): ?>
								<p class="notice notice-warning" style="padding: 8px; margin: 0 0 10px 0;">
									<?php 
									if ($pschat_status['installed']) {
										_e('PS-Chat ist installiert, aber nicht aktiviert. Aktiviere es zuerst.', CPC2_TEXT_DOMAIN);
									} else {
										_e('PS-Chat ist nicht installiert. Installiere es, um Gruppen-Chats zu nutzen.', CPC2_TEXT_DOMAIN);
									}
									?>
								</p>
								<input type="checkbox" id="cpc_enable_group_chats" name="cpc_enable_group_chats" value="1" disabled />
								<label for="cpc_enable_group_chats" style="display: inline; color: #999;">
									<?php _e('Aktiviert wenn PS-Chat verfügbar ist', CPC2_TEXT_DOMAIN); ?>
								</label>
							<?php else: ?>
								<input type="checkbox" 
									   id="cpc_enable_group_chats" 
									   name="cpc_enable_group_chats" 
									   value="1" 
									   <?php checked($group_chats_enabled); ?> />
								<label for="cpc_enable_group_chats" style="display: inline;">
									<?php _e('Erlaube Gruppen-Admins, einen Chat für ihre Gruppe zu erstellen', CPC2_TEXT_DOMAIN); ?>
								</label>
								<p class="description">
									<?php _e('Wenn aktiviert, wird den Gruppen-Admins im Tab "Einstellungen" die Möglichkeit gegeben, einen Chat für ihre Gruppe einzurichten.', CPC2_TEXT_DOMAIN); ?>
								</p>
							<?php endif; ?>
						</td>
					</tr>
				</table>
				
				<?php submit_button(__('Änderungen speichern', CPC2_TEXT_DOMAIN)); ?>
			</form>
		</div>
	</div>
	
	<?php
}
