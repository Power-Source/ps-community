<?php

// Add to Getting Started information
add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_friendships', 4);
function cpc_admin_getting_started_friendships() {

    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_friendships' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';    
  	echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" rel="cpc_admin_getting_started_friendships" id="cpc_admin_getting_started_friendships_div">'.__('Freundschaften', CPC2_TEXT_DOMAIN).'</div>';

  	$display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_friendships' ? 'block' : 'none';
  	echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_friendships" style="display:'.$display.'">';

		?>
		<table class="form-table">
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_friendships_all"><?php _e('Alle Freunde', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<input type="checkbox" style="width:10px" name="cpc_friendships_all" <?php if (get_option('cpc_friendships_all')) echo 'CHECKED'; ?> /> 
				<span class="description"><?php _e('Macht jeden Benutzer immer mit allen anderen befreundet. Gut für private soziale Netzwerke.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		<tr class="form-field">
        <td scope="row" valign="top">
            <label for="cpc_friends_layout"><?php _e('Freunde-Layout', CPC2_TEXT_DOMAIN); ?></label>
        </td>
        <td>
            <select name="cpc_friends_layout">
                <option value="list" <?php selected(get_option('cpc_friends_layout', 'list'), 'list'); ?>><?php _e('Liste', CPC2_TEXT_DOMAIN); ?></option>
                <option value="fluid" <?php selected(get_option('cpc_friends_layout', 'list'), 'fluid'); ?>><?php _e('Flüssig', CPC2_TEXT_DOMAIN); ?></option>
            </select>
        </td>
    	</tr> 
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_friendships_profile_visibility_default"><?php _e('Standard Profilsichtbarkeit', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<select name="cpc_friendships_profile_visibility_default" id="cpc_friendships_profile_visibility_default">
					<option value="private" <?php selected(get_option('cpc_friendships_profile_visibility_default', 'private'), 'private'); ?>><?php _e('Privat (nur Freunde)', CPC2_TEXT_DOMAIN); ?></option>
					<option value="public" <?php selected(get_option('cpc_friendships_profile_visibility_default', 'private'), 'public'); ?>><?php _e('Öffentlich', CPC2_TEXT_DOMAIN); ?></option>
				</select>
				<span class="description"><?php _e('Definiert die Profil- und Aktivitäts-Sichtbarkeit, wenn Freundschaften aktiv sind.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_friendships_allow_profile_privacy_choice"><?php _e('Privatsphärenauswahl erlauben', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<select name="cpc_friendships_allow_profile_privacy_choice" id="cpc_friendships_allow_profile_privacy_choice">
					<option value="0" <?php selected(get_option('cpc_friendships_allow_profile_privacy_choice', '0'), '0'); ?>><?php _e('Nein', CPC2_TEXT_DOMAIN); ?></option>
					<option value="1" <?php selected(get_option('cpc_friendships_allow_profile_privacy_choice', '0'), '1'); ?>><?php _e('Ja', CPC2_TEXT_DOMAIN); ?></option>
				</select>
				<span class="description"><?php _e('Wenn aktiv, können Benutzer ihre Sichtbarkeit auf der Seite Profil bearbeiten selbst festlegen.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_friendships_activity_privacy_enabled"><?php _e('Aktivitäts-Privatsphäre aktivieren', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<select name="cpc_friendships_activity_privacy_enabled" id="cpc_friendships_activity_privacy_enabled">
					<option value="0" <?php selected(get_option('cpc_friendships_activity_privacy_enabled', '0'), '0'); ?>><?php _e('Nein', CPC2_TEXT_DOMAIN); ?></option>
					<option value="1" <?php selected(get_option('cpc_friendships_activity_privacy_enabled', '0'), '1'); ?>><?php _e('Ja', CPC2_TEXT_DOMAIN); ?></option>
				</select>
				<span class="description"><?php _e('Aktiviert Sichtbarkeitsstufen pro Aktivitätsbeitrag im Stream.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_activity_visibility_default"><?php _e('Standard Aktivitäts-Sichtbarkeit', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<select name="cpc_activity_visibility_default" id="cpc_activity_visibility_default">
					<option value="public" <?php selected(get_option('cpc_activity_visibility_default', 'public'), 'public'); ?>><?php _e('Öffentlich', CPC2_TEXT_DOMAIN); ?></option>
					<option value="loggedin" <?php selected(get_option('cpc_activity_visibility_default', 'public'), 'loggedin'); ?>><?php _e('Nur angemeldete Benutzer', CPC2_TEXT_DOMAIN); ?></option>
					<?php if (function_exists('cpc_are_friends') || (defined('CPC_CORE_PLUGINS') && strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false)): ?>
					<option value="friends" <?php selected(get_option('cpc_activity_visibility_default', 'public'), 'friends'); ?>><?php _e('Nur Freunde', CPC2_TEXT_DOMAIN); ?></option>
					<?php endif; ?>
					<option value="onlyme" <?php selected(get_option('cpc_activity_visibility_default', 'public'), 'onlyme'); ?>><?php _e('Nur ich', CPC2_TEXT_DOMAIN); ?></option>
					<option value="adminsonly" <?php selected(get_option('cpc_activity_visibility_default', 'public'), 'adminsonly'); ?>><?php _e('Nur Administratoren', CPC2_TEXT_DOMAIN); ?></option>
				</select>
				<span class="description"><?php _e('Dieser Wert wird beim Erstellen neuer Aktivitätsbeiträge vorausgewählt.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label><?php _e('Erlaubte Sichtbarkeitsstufen', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<?php
				$activity_levels_base = cpc_friendships_get_activity_visibility_levels_base();
				$activity_enabled_levels = cpc_friendships_get_enabled_activity_visibility_levels();
				foreach ($activity_levels_base as $level_key => $level_label):
					$checked = isset($activity_enabled_levels[$level_key]);
				?>
				<label style="display:block;margin:2px 0;">
					<input type="checkbox" name="cpc_activity_visibility_enabled[<?php echo esc_attr($level_key); ?>]" value="1" <?php checked($checked); ?> />
					<?php echo esc_html($level_label); ?>
				</label>
				<?php endforeach; ?>
				<span class="description"><?php _e('Nur aktivierte Stufen werden in der Auswahl angezeigt und ausgewertet.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		</table>
        <?php
	echo '</div>';

}

/* AJAX */

add_action('cpc_admin_setup_form_get_hook', 'cpc_admin_friendships_save', 10, 2);
add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_friendships_save', 10, 2);

function cpc_admin_friendships_save($the_post) {

	if (isset($the_post['cpc_friendships_all'])):
		update_option('cpc_friendships_all', true);
	else:
		delete_option('cpc_friendships_all');
	endif;

	// Speichern der Layout-Option
    if (isset($the_post['cpc_friends_layout'])) {
        update_option('cpc_friends_layout', sanitize_text_field($the_post['cpc_friends_layout']));
    }

	if (isset($the_post['cpc_friendships_profile_visibility_default'])) {
		$profile_visibility_default = sanitize_key($the_post['cpc_friendships_profile_visibility_default']);
		if ($profile_visibility_default !== 'public') {
			$profile_visibility_default = 'private';
		}
		update_option('cpc_friendships_profile_visibility_default', $profile_visibility_default);
	}

	if (isset($the_post['cpc_friendships_allow_profile_privacy_choice'])) {
		$allow_privacy_choice = sanitize_text_field($the_post['cpc_friendships_allow_profile_privacy_choice']) === '1' ? '1' : '0';
		update_option('cpc_friendships_allow_profile_privacy_choice', $allow_privacy_choice);
	}

	if (isset($the_post['cpc_friendships_activity_privacy_enabled'])) {
		$activity_privacy_enabled = sanitize_text_field($the_post['cpc_friendships_activity_privacy_enabled']) === '1' ? '1' : '0';
		update_option('cpc_friendships_activity_privacy_enabled', $activity_privacy_enabled);
	}

	if (isset($the_post['cpc_activity_visibility_default'])) {
		$activity_default = sanitize_key($the_post['cpc_activity_visibility_default']);
		$allowed = cpc_friendships_get_activity_visibility_levels();
		if (!isset($allowed[$activity_default])) {
			$activity_default = 'public';
		}
		update_option('cpc_activity_visibility_default', $activity_default);
	}

	$enabled_levels = array();
	$base_levels = cpc_friendships_get_activity_visibility_levels_base();
	$submitted_levels = isset($the_post['cpc_activity_visibility_enabled']) && is_array($the_post['cpc_activity_visibility_enabled'])
		? $the_post['cpc_activity_visibility_enabled']
		: array();

	foreach ($base_levels as $level_key => $level_label) {
		if (isset($submitted_levels[$level_key])) {
			$enabled_levels[] = $level_key;
		}
	}

	if (empty($enabled_levels)) {
		$enabled_levels[] = 'public';
	}

	update_option('cpc_activity_visibility_enabled_levels', $enabled_levels);

}

function cpc_friendships_activity_privacy_enabled() {
	return get_option('cpc_friendships_activity_privacy_enabled', '0') === '1';
}

function cpc_friendships_get_activity_visibility_levels_base() {
	$levels = array(
		'public' => __('Öffentlich', CPC2_TEXT_DOMAIN),
		'loggedin' => __('Nur angemeldete Benutzer', CPC2_TEXT_DOMAIN),
		'onlyme' => __('Nur ich', CPC2_TEXT_DOMAIN),
		'adminsonly' => __('Nur Administratoren', CPC2_TEXT_DOMAIN),
	);

	if (function_exists('cpc_are_friends') || (defined('CPC_CORE_PLUGINS') && strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false)) {
		$levels = array_merge(
			array(
				'public' => __('Öffentlich', CPC2_TEXT_DOMAIN),
				'loggedin' => __('Nur angemeldete Benutzer', CPC2_TEXT_DOMAIN),
				'friends' => __('Nur Freunde', CPC2_TEXT_DOMAIN),
			),
			array(
				'onlyme' => __('Nur ich', CPC2_TEXT_DOMAIN),
				'adminsonly' => __('Nur Administratoren', CPC2_TEXT_DOMAIN),
			)
		);
	}

	return apply_filters('cpc_activity_visibility_levels_base_filter', $levels);
}

function cpc_friendships_get_enabled_activity_visibility_levels() {
	$base_levels = cpc_friendships_get_activity_visibility_levels_base();
	$stored = get_option('cpc_activity_visibility_enabled_levels', array_keys($base_levels));
	if (!is_array($stored)) {
		$stored = array_keys($base_levels);
	}

	$enabled = array();
	foreach ($stored as $level_key) {
		$level_key = sanitize_key($level_key);
		if (isset($base_levels[$level_key])) {
			$enabled[$level_key] = $base_levels[$level_key];
		}
	}

	if (empty($enabled) && isset($base_levels['public'])) {
		$enabled['public'] = $base_levels['public'];
	}

	return $enabled;
}

function cpc_friendships_get_activity_visibility_levels() {
	$levels = cpc_friendships_get_enabled_activity_visibility_levels();
	return apply_filters('cpc_activity_visibility_levels_filter', $levels);
}

function cpc_friendships_get_activity_visibility_for_post($activity_id) {
	$visibility = sanitize_key(get_post_meta((int) $activity_id, 'cpc_activity_visibility', true));
	$levels = cpc_friendships_get_activity_visibility_levels();
	if (!isset($levels[$visibility])) {
		$visibility = cpc_friendships_get_default_activity_visibility();
	}

	return $visibility;
}

function cpc_friendships_get_activity_visibility_label($visibility) {
	$levels = cpc_friendships_get_activity_visibility_levels();
	$visibility = sanitize_key($visibility);

	if (!isset($levels[$visibility])) {
		return '';
	}

	return $levels[$visibility];
}

function cpc_friendships_render_activity_visibility_badge($visibility, $with_prefix = true) {
	$visibility = sanitize_key($visibility);
	$label = cpc_friendships_get_activity_visibility_label($visibility);
	if ($label === '') {
		$visibility = 'public';
		$label = cpc_friendships_get_activity_visibility_label($visibility);
	}

	$html = '<span class="cpc_activity_visibility_label">';
	$html .= '<span class="cpc_activity_visibility_badge cpc_activity_visibility_badge-' . esc_attr($visibility) . '">' . esc_html($label) . '</span>';
	$html .= '</span>';

	return $html;
}

function cpc_friendships_get_default_activity_visibility() {
	$default = sanitize_key(get_option('cpc_activity_visibility_default', 'public'));
	$levels = cpc_friendships_get_activity_visibility_levels();

	if (!isset($levels[$default])) {
		$default = 'public';
	}

	return $default;
}

function cpc_friendships_can_view_activity($activity_id, $viewer_user_id = 0) {
	$activity_id = (int) $activity_id;
	$viewer_user_id = (int) $viewer_user_id;

	if (!$activity_id) {
		return false;
	}

	if (!cpc_friendships_activity_privacy_enabled()) {
		return true;
	}

	$author_id = (int) get_post_field('post_author', $activity_id);
	if (!$author_id) {
		return false;
	}

	if ($viewer_user_id > 0 && $viewer_user_id === $author_id) {
		return true;
	}

	if ($viewer_user_id > 0 && user_can($viewer_user_id, 'manage_options')) {
		return true;
	}

	$visibility = cpc_friendships_get_activity_visibility_for_post($activity_id);

	switch ($visibility) {
		case 'loggedin':
			return $viewer_user_id > 0;

		case 'friends':
			if ($viewer_user_id <= 0 || !function_exists('cpc_are_friends')) {
				return false;
			}
			$friends = cpc_are_friends($viewer_user_id, $author_id);
			return (is_array($friends) && isset($friends['status']) && $friends['status'] === 'publish');

		case 'onlyme':
			return ($viewer_user_id > 0 && $viewer_user_id === $author_id);

		case 'adminsonly':
			return ($viewer_user_id > 0 && user_can($viewer_user_id, 'manage_options'));

		case 'public':
		default:
			return true;
	}
}

add_filter('cpc_activity_post_post_form_filter', 'cpc_friendships_add_activity_privacy_field', 30, 4);
function cpc_friendships_add_activity_privacy_field($form_html, $atts, $user_id, $viewer_user_id) {
	if (!cpc_friendships_activity_privacy_enabled() || !is_user_logged_in()) {
		return $form_html;
	}

	$user_id = (int) $user_id;
	$viewer_user_id = (int) $viewer_user_id;
	if ($viewer_user_id <= 0 || $user_id <= 0 || !cpc_friendships_can_view_profile($user_id, $viewer_user_id)) {
		return $form_html;
	}

	$levels = cpc_friendships_get_activity_visibility_levels();
	$default = cpc_friendships_get_default_activity_visibility();

	$form_html .= '<div class="cpc_activity_visibility_field" style="margin:10px 0 6px;">';
	$form_html .= '<label for="cpc_activity_visibility" style="display:block;margin-bottom:4px;">' . esc_html__('Sichtbarkeit', CPC2_TEXT_DOMAIN) . '</label>';
	$form_html .= '<select name="cpc_activity_visibility" id="cpc_activity_visibility">';
	foreach ($levels as $level_key => $level_label) {
		$form_html .= '<option value="' . esc_attr($level_key) . '"' . selected($default, $level_key, false) . '>' . esc_html($level_label) . '</option>';
	}
	$form_html .= '</select>';
	$form_html .= '</div>';

	return $form_html;
}

add_filter('cpc_activity_item_meta_filter', 'cpc_friendships_render_activity_visibility_meta', 30, 5);
function cpc_friendships_render_activity_visibility_meta($item_html, $atts, $activity_id, $user_id, $this_user) {
	if (!cpc_friendships_activity_privacy_enabled()) {
		return $item_html;
	}

	$activity_id = (int) $activity_id;
	$this_user = (int) $this_user;
	if (!$activity_id) {
		return $item_html;
	}

	$current_visibility = cpc_friendships_get_activity_visibility_for_post($activity_id);

	$author_id = (int) get_post_field('post_author', $activity_id);
	$can_edit = ($this_user > 0 && ($this_user === $author_id || user_can($this_user, 'manage_options')));

	$item_html .= '<span class="cpc_activity_visibility_meta" style="margin-left:8px;">';
	$item_html .= cpc_friendships_render_activity_visibility_badge($current_visibility, true);

	if ($can_edit) {
		$levels = cpc_friendships_get_activity_visibility_levels();
		$item_html .= '<select class="cpc_activity_visibility_edit" data-post-id="' . (int) $activity_id . '" style="margin-left:8px;">';
		foreach ($levels as $level_key => $level_label) {
			$item_html .= '<option value="' . esc_attr($level_key) . '"' . selected($current_visibility, $level_key, false) . '>' . esc_html($level_label) . '</option>';
		}
		$item_html .= '</select>';
		$item_html .= '<span class="cpc_activity_visibility_feedback" aria-live="polite"></span>';
	}

	$item_html .= '</span>';

	return $item_html;
}

add_action('wp_ajax_cpc_activity_update_visibility', 'cpc_friendships_ajax_update_activity_visibility');
function cpc_friendships_ajax_update_activity_visibility() {
	check_ajax_referer('cpc-activity-nonce', 'security');

	if (!is_user_logged_in() || !cpc_friendships_activity_privacy_enabled()) {
		wp_send_json_error(array('message' => __('Nicht erlaubt.', CPC2_TEXT_DOMAIN)));
	}

	$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
	$visibility = isset($_POST['visibility']) ? sanitize_key($_POST['visibility']) : '';

	if (!$post_id || get_post_type($post_id) !== 'cpc_activity') {
		wp_send_json_error(array('message' => __('Ungültiger Aktivitätsbeitrag.', CPC2_TEXT_DOMAIN)));
	}

	$author_id = (int) get_post_field('post_author', $post_id);
	$current_user_id = get_current_user_id();
	if (!$author_id || ($current_user_id !== $author_id && !current_user_can('manage_options'))) {
		wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)));
	}

	$levels = cpc_friendships_get_activity_visibility_levels();
	if (!isset($levels[$visibility])) {
		wp_send_json_error(array('message' => __('Ungültige Sichtbarkeit.', CPC2_TEXT_DOMAIN)));
	}

	update_post_meta($post_id, 'cpc_activity_visibility', $visibility);

	wp_send_json_success(array(
		'post_id' => $post_id,
		'visibility' => $visibility,
		'label' => $levels[$visibility],
		'badge_html' => cpc_friendships_render_activity_visibility_badge($visibility, true),
	));
}

function cpc_friendships_filter_activity_items_by_visibility($activity, $atts, $user_id, $viewer_user_id) {
	if (!cpc_friendships_activity_privacy_enabled() || empty($activity) || !is_array($activity)) {
		return $activity;
	}

	$filtered = array();
	$viewer_user_id = (int) $viewer_user_id;

	foreach ($activity as $item) {
		$item_id = isset($item['ID']) ? (int) $item['ID'] : 0;
		if (!$item_id) {
			continue;
		}

		if (cpc_friendships_can_view_activity($item_id, $viewer_user_id)) {
			$filtered[] = $item;
		}
	}

	return $filtered;
}
add_filter('cpc_activity_items_filter', 'cpc_friendships_filter_activity_items_by_visibility', 20, 4);
add_filter('cpc_activity_single_item_filter', 'cpc_friendships_filter_activity_items_by_visibility', 20, 4);

function cpc_friendships_get_default_profile_visibility() {
	$default = get_option('cpc_friendships_profile_visibility_default', 'private');
	return ($default === 'public') ? 'public' : 'private';
}

function cpc_friendships_allow_profile_privacy_choice() {
	return get_option('cpc_friendships_allow_profile_privacy_choice', '0') === '1';
}

function cpc_friendships_get_effective_profile_visibility($user_id) {
	$visibility = cpc_friendships_get_default_profile_visibility();

	if (!cpc_friendships_allow_profile_privacy_choice()) {
		return $visibility;
	}

	$user_visibility = get_user_meta((int) $user_id, 'cpc_profile_visibility', true);
	if ($user_visibility === 'public' || $user_visibility === 'private') {
		return $user_visibility;
	}

	return $visibility;
}

function cpc_friendships_can_view_profile($profile_user_id, $viewer_user_id = 0) {
	$profile_user_id = (int) $profile_user_id;
	$viewer_user_id = (int) $viewer_user_id;

	if (!$profile_user_id) {
		return false;
	}

	if ($viewer_user_id > 0 && user_can($viewer_user_id, 'manage_options')) {
		return true;
	}

	if ($viewer_user_id > 0 && $viewer_user_id === $profile_user_id) {
		return true;
	}

	$visibility = cpc_friendships_get_effective_profile_visibility($profile_user_id);
	if ($visibility === 'public') {
		return true;
	}

	if ($viewer_user_id <= 0) {
		return false;
	}

	if (function_exists('cpc_are_friends')) {
		$friends = cpc_are_friends($viewer_user_id, $profile_user_id);
		return (is_array($friends) && isset($friends['status']) && $friends['status'] === 'publish');
	}

	return false;
}

function cpc_friendships_get_private_profile_notice($profile_user_id, $viewer_user_id = 0, $args = array()) {
	$profile_user_id = (int) $profile_user_id;
	$viewer_user_id = (int) $viewer_user_id;

	$defaults = array(
		'message' => __('Dieses Profil ist privat. Nur Freunde koennen Inhalte sehen.', CPC2_TEXT_DOMAIN),
		'include_friend_action' => true,
		'wrapper_id' => 'cpc_activity_post_private_msg',
		'wrapper_class' => 'cpc_activity_post_private_msg',
	);
	$args = wp_parse_args($args, $defaults);

	$html = '<div';
	if (!empty($args['wrapper_id'])) {
		$html .= ' id="' . esc_attr($args['wrapper_id']) . '"';
	}
	if (!empty($args['wrapper_class'])) {
		$html .= ' class="' . esc_attr($args['wrapper_class']) . '"';
	}
	$html .= '>' . esc_html($args['message']) . '</div>';

	$can_show_action = !empty($args['include_friend_action'])
		&& is_user_logged_in()
		&& $profile_user_id > 0
		&& $viewer_user_id > 0
		&& $profile_user_id !== $viewer_user_id
		&& function_exists('cpc_friends_add_button');

	if ($can_show_action) {
		$html .= '<div class="cpc_profile_private_friendship_action" style="margin-top:10px;">';
		$html .= cpc_friends_add_button(array('user_id' => $profile_user_id));
		$html .= '</div>';
	}

	return $html;
}

add_filter('cpc_check_profile_security_filter', 'cpc_friendships_apply_profile_visibility', 10, 3);
function cpc_friendships_apply_profile_visibility($can_see_profile, $profile_user_id, $viewer_user_id) {
	return cpc_friendships_can_view_profile($profile_user_id, $viewer_user_id);
}

add_filter('cpc_check_activity_security_filter', 'cpc_friendships_apply_activity_visibility', 10, 3);
function cpc_friendships_apply_activity_visibility($can_see_activity, $profile_user_id, $viewer_user_id) {
	return cpc_friendships_can_view_profile($profile_user_id, $viewer_user_id);
}

add_filter('cpc_usermeta_change_filter', 'cpc_friendships_add_profile_visibility_field', 20, 3);
function cpc_friendships_add_profile_visibility_field($tabs, $atts, $user_id) {
	if (!cpc_friendships_allow_profile_privacy_choice()) {
		return $tabs;
	}

	$user_id = (int) $user_id;
	if (!is_user_logged_in() || get_current_user_id() !== $user_id) {
		return $tabs;
	}

	$current_visibility = get_user_meta($user_id, 'cpc_profile_visibility', true);
	if ($current_visibility !== 'public' && $current_visibility !== 'private') {
		$current_visibility = cpc_friendships_get_default_profile_visibility();
	}

	$tabs_array = get_option('cpc_comfile_tabs');
	$default_tab = isset($tabs_array['cpc_comfile_tab_default_tab']) ? (int) $tabs_array['cpc_comfile_tab_default_tab'] : 1;
	if ($default_tab < 1) {
		$default_tab = 1;
	}

	$html  = '<div class="cpc_usermeta_change_item">';
	$html .= '<div class="cpc_usermeta_change_label">' . esc_html__('Profilsichtbarkeit', CPC2_TEXT_DOMAIN) . '</div>';
	$html .= '<select name="cpc_profile_visibility" id="cpc_profile_visibility">';
	$html .= '<option value="private"' . selected($current_visibility, 'private', false) . '>' . esc_html__('Privat (nur Freunde)', CPC2_TEXT_DOMAIN) . '</option>';
	$html .= '<option value="public"' . selected($current_visibility, 'public', false) . '>' . esc_html__('Öffentlich', CPC2_TEXT_DOMAIN) . '</option>';
	$html .= '</select>';
	$html .= '<div class="cpc_note" style="margin-top:6px;">' . esc_html__('Steuert, wer Dein Profil und Deine Aktivitaeten sehen darf.', CPC2_TEXT_DOMAIN) . '</div>';
	$html .= '</div>';

	$tabs[] = array(
		'tab' => $default_tab,
		'html' => $html,
		'mandatory' => false,
	);

	return $tabs;
}

add_action('cpc_usermeta_change_hook', 'cpc_friendships_save_profile_visibility_field', 20, 4);
function cpc_friendships_save_profile_visibility_field($user_id, $atts, $the_post, $the_files) {
	if (!cpc_friendships_allow_profile_privacy_choice()) {
		return;
	}

	$user_id = (int) $user_id;
	if (!is_user_logged_in() || get_current_user_id() !== $user_id) {
		return;
	}

	if (!isset($the_post['cpc_profile_visibility'])) {
		return;
	}

	$visibility = sanitize_key($the_post['cpc_profile_visibility']);
	if ($visibility !== 'public') {
		$visibility = 'private';
	}

	update_user_meta($user_id, 'cpc_profile_visibility', $visibility);
}

add_action( 'wp_ajax_cpc_add_favourite', 'cpc_add_favourite' ); 
add_action( 'wp_ajax_cpc_remove_favourite', 'cpc_remove_favourite' ); 

function cpc_add_favourite() {

	check_ajax_referer('cpc-friendship-nonce', 'security');
	if (!is_user_logged_in()) {
		wp_die('forbidden');
	}

	global $current_user;
	$user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
	if (!$user_id || $user_id === (int)$current_user->ID) {
		wp_die('invalid_user');
	}
	$the_user = get_user_by('id', $user_id);
	if (!$the_user) {
		wp_die('user_not_found');
	}

    $post = array(
    	'post_title'     => $current_user->user_login.' - '.$the_user->user_login,
		'post_name'	=> sanitize_title_with_dashes($current_user->user_login.' '.$the_user->user_login),      
		'post_status'    => 'publish',
		'post_type'      => 'cpc_favourite_friend',
		'post_author'    => $current_user->ID,
		'ping_status'    => 'closed',
		'comment_status' => 'closed',
    );  
    $new_id = wp_insert_post( $post );
    if ($new_id):
		update_post_meta( $new_id, 'cpc_favourite_member1', $current_user->ID );
		update_post_meta( $new_id, 'cpc_favourite_member2', $user_id );
		update_post_meta( $new_id, 'cpc_favourite_friendship_since', date('Y-m-d H:i:s') );
	endif;

	exit;

}

function cpc_remove_favourite() {

	check_ajax_referer('cpc-friendship-nonce', 'security');
	if (!is_user_logged_in()) {
		wp_die('forbidden');
	}

	global $current_user;
	$user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
	if (!$user_id) {
		wp_die('invalid_user');
	}

	$friendship = cpc_is_a_favourite_friend($current_user->ID, $user_id);
	if ($friendship):
		wp_delete_post($friendship['ID'], true);
	endif;

	exit;

}
?>