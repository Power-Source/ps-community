<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpc_events_get_post_type() {
    return (function_exists('cpc_events_external_plugin_active') && cpc_events_external_plugin_active())
        ? 'psource_event'
        : 'cpc_event';
}

function cpc_events_register_post_type() {
    $labels = array(
        'name' => __('Events', CPC2_TEXT_DOMAIN),
        'singular_name' => __('Event', CPC2_TEXT_DOMAIN),
        'add_new' => __('Event hinzufuegen', CPC2_TEXT_DOMAIN),
        'add_new_item' => __('Neues Event', CPC2_TEXT_DOMAIN),
        'edit_item' => __('Event bearbeiten', CPC2_TEXT_DOMAIN),
        'new_item' => __('Neues Event', CPC2_TEXT_DOMAIN),
        'view_item' => __('Event ansehen', CPC2_TEXT_DOMAIN),
        'search_items' => __('Events suchen', CPC2_TEXT_DOMAIN),
        'not_found' => __('Keine Events gefunden', CPC2_TEXT_DOMAIN),
    );

    register_post_type('cpc_event', array(
        'labels' => $labels,
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => array('title', 'editor', 'author'),
        'has_archive' => false,
        'rewrite' => array('slug' => 'cpc-event'),
    ));
}
if (!function_exists('cpc_events_external_plugin_active') || !cpc_events_external_plugin_active()) {
    add_action('init', 'cpc_events_register_post_type');
}

function cpc_events_add_meta_boxes() {
    add_meta_box('cpc_event_details', __('Event-Details', CPC2_TEXT_DOMAIN), 'cpc_events_meta_box_html', 'cpc_event', 'normal', 'high');
}
if (!function_exists('cpc_events_external_plugin_active') || !cpc_events_external_plugin_active()) {
    add_action('add_meta_boxes', 'cpc_events_add_meta_boxes');
}

function cpc_events_meta_box_html($post) {
    wp_nonce_field('cpc_event_save', 'cpc_event_nonce');

    $start = get_post_meta($post->ID, 'cpc_event_start', true);
    $end = get_post_meta($post->ID, 'cpc_event_end', true);
    $location = get_post_meta($post->ID, 'cpc_event_location', true);

    echo '<p><label for="cpc_event_start"><strong>' . esc_html__('Start', CPC2_TEXT_DOMAIN) . '</strong></label><br>';
    echo '<input type="datetime-local" id="cpc_event_start" name="cpc_event_start" value="' . esc_attr($start) . '" style="width:260px"></p>';

    echo '<p><label for="cpc_event_end"><strong>' . esc_html__('Ende', CPC2_TEXT_DOMAIN) . '</strong></label><br>';
    echo '<input type="datetime-local" id="cpc_event_end" name="cpc_event_end" value="' . esc_attr($end) . '" style="width:260px"></p>';

    echo '<p><label for="cpc_event_location"><strong>' . esc_html__('Ort', CPC2_TEXT_DOMAIN) . '</strong></label><br>';
    echo '<input type="text" id="cpc_event_location" name="cpc_event_location" value="' . esc_attr($location) . '" class="widefat"></p>';
}

function cpc_events_save_meta($post_id) {
    if (!isset($_POST['cpc_event_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['cpc_event_nonce'])), 'cpc_event_save')) {
        return;
    }
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (get_post_type($post_id) !== cpc_events_get_post_type()) {
        return;
    }
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    $start = isset($_POST['cpc_event_start']) ? sanitize_text_field(wp_unslash($_POST['cpc_event_start'])) : '';
    $end = isset($_POST['cpc_event_end']) ? sanitize_text_field(wp_unslash($_POST['cpc_event_end'])) : '';
    $location = isset($_POST['cpc_event_location']) ? sanitize_text_field(wp_unslash($_POST['cpc_event_location'])) : '';

    update_post_meta($post_id, 'cpc_event_start', $start);
    update_post_meta($post_id, 'cpc_event_end', $end);
    update_post_meta($post_id, 'cpc_event_location', $location);

    $start_ts = $start ? strtotime($start) : 0;
    $end_ts = $end ? strtotime($end) : 0;
    update_post_meta($post_id, 'cpc_event_start_ts', (int)$start_ts);
    update_post_meta($post_id, 'cpc_event_end_ts', (int)$end_ts);

    $group_id = isset($_POST['cpc_event_group_id']) ? max(0, (int)$_POST['cpc_event_group_id']) : 0;
    if ($group_id) {
        update_post_meta($post_id, 'cpc_event_group_id', $group_id);
    } else {
        delete_post_meta($post_id, 'cpc_event_group_id');
    }
}
if (!function_exists('cpc_events_external_plugin_active') || !cpc_events_external_plugin_active()) {
    add_action('save_post', 'cpc_events_save_meta');
}

function cpc_events_render_internal($atts) {
    if (function_exists('cpc_events_external_plugin_active') && cpc_events_external_plugin_active()) {
        $upcoming = isset($atts['upcoming']) ? (int)$atts['upcoming'] : 1;
        $limit = isset($atts['limit']) ? max(1, min(100, (int)$atts['limit'])) : 12;
        $date = $upcoming ? date('Y-m-d H:i:s', current_time('timestamp')) : '';
        $shortcode = '[eab_archive limit="' . (int)$limit . '"';
        if ($date !== '') {
            $shortcode .= ' date="' . esc_attr($date) . '"';
        }
        $shortcode .= ']';
        return do_shortcode($shortcode);
    }

    $upcoming = isset($atts['upcoming']) ? (int)$atts['upcoming'] : 1;
    $limit = isset($atts['limit']) ? max(1, min(100, (int)$atts['limit'])) : 12;

    $meta_query = array();
    if ($upcoming) {
        $meta_query[] = array(
            'key' => 'cpc_event_end_ts',
            'value' => current_time('timestamp'),
            'compare' => '>=',
            'type' => 'NUMERIC',
        );
    }

    $query = new WP_Query(array(
        'post_type' => 'cpc_event',
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => 'meta_value_num',
        'meta_key' => 'cpc_event_start_ts',
        'order' => 'ASC',
        'meta_query' => $meta_query,
        'no_found_rows' => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    ));

    if (empty($query->posts)) {
        return '<div class="cpc-events-empty">' . esc_html__('Keine Events gefunden.', CPC2_TEXT_DOMAIN) . '</div>';
    }

    $html = '<div class="cpc-events-list">';
    foreach ($query->posts as $event) {
        $start_ts = (int)get_post_meta($event->ID, 'cpc_event_start_ts', true);
        $end_ts = (int)get_post_meta($event->ID, 'cpc_event_end_ts', true);
        $location = get_post_meta($event->ID, 'cpc_event_location', true);

        $html .= '<article class="cpc-event-card">';
        $html .= '<h4 class="cpc-event-title"><a href="' . esc_url(get_permalink($event->ID)) . '">' . esc_html(get_the_title($event->ID)) . '</a></h4>';
        if ($start_ts) {
            $html .= '<div class="cpc-event-time">' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $start_ts));
            if ($end_ts) {
                $html .= ' - ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $end_ts));
            }
            $html .= '</div>';
        }
        if (!empty($location)) {
            $html .= '<div class="cpc-event-location">' . esc_html($location) . '</div>';
        }
        $html .= '<div class="cpc-event-excerpt">' . esc_html(wp_trim_words(wp_strip_all_tags((string)$event->post_content), 28)) . '</div>';
        $html .= '</article>';
    }
    $html .= '</div>';

    return $html;
}

function cpc_events_shortcode($atts) {
    $values = function_exists('cpc_get_shortcode_options') ? cpc_get_shortcode_options('cpc_events') : array();
    $atts = shortcode_atts(array(
        'limit' => cpc_get_shortcode_value($values, 'cpc_events-limit', 12),
        'upcoming' => cpc_get_shortcode_value($values, 'cpc_events-upcoming', 1),
        'styles' => true,
        'before' => '',
        'after' => '',
    ), $atts, 'cpc_events');

    $html = cpc_events_render_internal($atts);

    if ($html && function_exists('cpc_wrap_shortcode_styles')) {
        $html = apply_filters('cpc_wrap_shortcode_styles_filter', $html, 'cpc_events', $atts['before'], $atts['after'], $atts['styles'], $values);
    }

    return $html;
}

if (!is_admin()) {
    add_shortcode(CPC_PREFIX . '-events', 'cpc_events_shortcode');
}

function cpc_events_is_core_enabled() {
    $core = get_option('cpc_default_core', '');
    return is_string($core) && strpos($core, 'core-events') !== false;
}

function cpc_events_get_directory_page_id() {
    return max(0, (int)get_option('cpc_events_directory_page', 0));
}

function cpc_events_allow_user_calendar() {
    return (bool)get_option('cpc_events_allow_user_calendar', 1);
}

function cpc_events_allow_group_calendar() {
    return (bool)get_option('cpc_events_allow_group_calendar', 1);
}

function cpc_events_group_email_notifications_enabled() {
    return (bool)get_option('cpc_events_group_email_notifications', 1);
}

function cpc_events_group_mail_enabled_for_group($group_id) {
    $group_id = (int)$group_id;
    if ($group_id <= 0) {
        return false;
    }

    if (!cpc_events_group_email_notifications_enabled()) {
        return false;
    }

    $group_value = get_post_meta($group_id, 'cpc_group_events_email_notifications', true);
    if ($group_value === '' || $group_value === null) {
        return true;
    }

    return !in_array((string)$group_value, array('0', 'no', 'off', 'false'), true);
}

function cpc_events_user_wants_group_event_emails($user_id) {
    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return false;
    }

    $pref = get_user_meta($user_id, 'cpc_events_notify_group_events', true);
    if ($pref === '' || $pref === null) {
        return true;
    }

    return !in_array((string)$pref, array('0', 'no', 'off', 'false'), true);
}

function cpc_events_get_group_notification_recipients($group_id, $exclude_user_id = 0) {
    $group_id = (int)$group_id;
    $exclude_user_id = (int)$exclude_user_id;
    if ($group_id <= 0 || !function_exists('cpc_get_group_members')) {
        return array();
    }

    $members = cpc_get_group_members($group_id, 'active');
    if (empty($members)) {
        return array();
    }

    $recipients = array();
    foreach ($members as $member) {
        $uid = !empty($member->ID) ? (int)$member->ID : 0;
        if ($uid <= 0 || $uid === $exclude_user_id) {
            continue;
        }
        if (!cpc_events_user_wants_group_event_emails($uid)) {
            continue;
        }
        $recipients[] = $uid;
    }

    return array_values(array_unique($recipients));
}

function cpc_events_send_group_event_notifications($event_id, $group_id, $actor_id = 0) {
    $event_id = (int)$event_id;
    $group_id = (int)$group_id;
    $actor_id = (int)$actor_id;
    if ($event_id <= 0 || $group_id <= 0) {
        return;
    }

    if (!cpc_events_group_mail_enabled_for_group($group_id)) {
        return;
    }

    $event = get_post($event_id);
    if (!$event || $event->post_type !== cpc_events_get_post_type() || $event->post_status !== 'publish') {
        return;
    }

    $group = get_post($group_id);
    if (!$group || $group->post_type !== 'cpc_group') {
        return;
    }

    $recipients = cpc_events_get_group_notification_recipients($group_id, $actor_id);
    if (empty($recipients)) {
        return;
    }

    $event_url = get_permalink($event_id);
    $event_title = get_the_title($event_id);
    $start_ts = (int)get_post_meta($event_id, 'cpc_event_start_ts', true);
    $end_ts = (int)get_post_meta($event_id, 'cpc_event_end_ts', true);
    $location = (string)get_post_meta($event_id, 'cpc_event_location', true);
    $group_name = get_the_title($group_id);

    $actor_name = __('Ein Mitglied', CPC2_TEXT_DOMAIN);
    if ($actor_id > 0) {
        $actor = get_user_by('id', $actor_id);
        if ($actor && !empty($actor->display_name)) {
            $actor_name = $actor->display_name;
        }
    }

    $subject = sprintf(
        __('%1$s: Neues Gruppen-Event in %2$s', CPC2_TEXT_DOMAIN),
        wp_specialchars_decode(get_bloginfo('name'), ENT_QUOTES),
        $group_name
    );

    $time_line = '';
    if ($start_ts > 0) {
        $time_line = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $start_ts);
        if ($end_ts > 0) {
            $time_line .= ' - ' . date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $end_ts);
        }
    }

    $message = sprintf(
        __('%1$s hat ein neues Event in der Gruppe "%2$s" erstellt.', CPC2_TEXT_DOMAIN),
        $actor_name,
        $group_name
    ) . "\n\n";
    $message .= sprintf(__('Titel: %s', CPC2_TEXT_DOMAIN), $event_title) . "\n";
    if ($time_line !== '') {
        $message .= sprintf(__('Zeit: %s', CPC2_TEXT_DOMAIN), $time_line) . "\n";
    }
    if ($location !== '') {
        $message .= sprintf(__('Ort: %s', CPC2_TEXT_DOMAIN), $location) . "\n";
    }
    $message .= "\n" . sprintf(__('Event ansehen: %s', CPC2_TEXT_DOMAIN), $event_url) . "\n";

    foreach ($recipients as $recipient_id) {
        $user = get_userdata((int)$recipient_id);
        if (!$user || empty($user->user_email)) {
            continue;
        }
        wp_mail($user->user_email, $subject, $message);
    }
}

function cpc_events_role_permission_keys() {
    return array('publish', 'submit_profile', 'submit_group');
}

function cpc_events_get_default_role_permissions() {
    return array(
        'administrator' => array('publish' => 1, 'submit_profile' => 1, 'submit_group' => 1),
        'editor' => array('publish' => 1, 'submit_profile' => 1, 'submit_group' => 1),
        'author' => array('publish' => 0, 'submit_profile' => 1, 'submit_group' => 1),
        'contributor' => array('publish' => 0, 'submit_profile' => 1, 'submit_group' => 1),
        'subscriber' => array('publish' => 0, 'submit_profile' => 1, 'submit_group' => 1),
    );
}

function cpc_events_maybe_seed_role_permissions_defaults() {
    if (get_option('cpc_events_role_permissions', null) !== null) {
        return;
    }

    $defaults = cpc_events_get_default_role_permissions();
    $roles = function_exists('get_editable_roles') ? get_editable_roles() : array();
    $seed = array();

    foreach ($roles as $role_key => $role_info) {
        $seed[$role_key] = array();
        foreach (cpc_events_role_permission_keys() as $perm_key) {
            $seed[$role_key][$perm_key] = !empty($defaults[$role_key][$perm_key]) ? 1 : 0;
        }
    }

    update_option('cpc_events_role_permissions', $seed, false);
}
add_action('init', 'cpc_events_maybe_seed_role_permissions_defaults', 20);

function cpc_events_get_role_permissions() {
    $defaults = cpc_events_get_default_role_permissions();
    $saved = get_option('cpc_events_role_permissions', array());
    if (!is_array($saved)) {
        $saved = array();
    }

    $roles = function_exists('get_editable_roles') ? get_editable_roles() : array();
    $permissions = array();
    foreach ($roles as $role_key => $role_info) {
        $permissions[$role_key] = array();
        foreach (cpc_events_role_permission_keys() as $perm_key) {
            $default_value = isset($defaults[$role_key][$perm_key]) ? (int)$defaults[$role_key][$perm_key] : 0;
            $saved_value = isset($saved[$role_key][$perm_key]) ? (int)$saved[$role_key][$perm_key] : $default_value;
            $permissions[$role_key][$perm_key] = $saved_value ? 1 : 0;
        }
    }

    return $permissions;
}

function cpc_events_role_can($user_id, $permission) {
    $user_id = (int)$user_id;
    $permission = sanitize_key((string)$permission);
    if ($user_id <= 0 || !in_array($permission, cpc_events_role_permission_keys(), true)) {
        return false;
    }

    $user = get_userdata($user_id);
    if (!$user || empty($user->roles)) {
        return false;
    }

    $permissions = cpc_events_get_role_permissions();
    foreach ((array)$user->roles as $role_key) {
        if (!empty($permissions[$role_key][$permission])) {
            return true;
        }
    }

    return false;
}

function cpc_events_user_matches_publish_scope($user_id) {
    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return false;
    }

    return cpc_events_role_can($user_id, 'publish');
}

function cpc_events_enforce_publish_scope($data, $postarr) {
    if (!is_array($data) || empty($data['post_type']) || $data['post_type'] !== 'cpc_event') {
        return $data;
    }

    if (empty($data['post_status']) || $data['post_status'] !== 'publish') {
        return $data;
    }

    if (!is_user_logged_in()) {
        $data['post_status'] = 'pending';
        return $data;
    }

    if (!cpc_events_user_matches_publish_scope(get_current_user_id())) {
        $data['post_status'] = 'pending';
    }

    return $data;
}
add_filter('wp_insert_post_data', 'cpc_events_enforce_publish_scope', 10, 2);

if (is_admin()) {
    add_action('cpc_admin_getting_started_hook', 'cpc_admin_events_section', 20);
    add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_events_settings_save', 20, 1);
}

function cpc_admin_events_section() {
    if (!cpc_events_is_core_enabled()) {
        return;
    }

    $expand_id = 'cpc_admin_getting_started_events';
    $is_open = (isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === $expand_id)
        || (isset($_GET['cpc_expand']) && $_GET['cpc_expand'] === $expand_id);
    $css = $is_open ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';
    $display = $is_open ? 'block' : 'none';

    echo '<div class="' . $css . 'cpc_admin_getting_started_menu_item" id="' . $expand_id . '_div" rel="' . $expand_id . '">' . esc_html__('Events', CPC2_TEXT_DOMAIN) . '</div>';
    echo '<div class="cpc_admin_getting_started_content" id="' . $expand_id . '" style="display:' . $display . '">';
    echo '<table class="form-table">';

    echo '<tr class="form-field">';
    echo '<th scope="row" valign="top"><label>' . esc_html__('Events-Seite', CPC2_TEXT_DOMAIN) . '</label></th>';
    echo '<td>';
    echo wp_dropdown_pages(array(
        'name' => 'cpc_events_directory_page',
        'echo' => 0,
        'show_option_none' => esc_html__('Keine feste Seite', CPC2_TEXT_DOMAIN),
        'option_none_value' => '0',
        'selected' => cpc_events_get_directory_page_id(),
    ));
    echo '<br /><span class="description">' . esc_html__('Waehle eine bestehende Seite mit [cpc-events].', CPC2_TEXT_DOMAIN) . '</span>';
    echo '<br /><label><input type="checkbox" style="width:10px" name="cpc_events_create_page" /> ' . esc_html__('Events-Seite neu erstellen (fuegt [cpc-events] ein).', CPC2_TEXT_DOMAIN) . '</label>';
    echo '</td>';
    echo '</tr>';

    $role_permissions = cpc_events_get_role_permissions();
    $editable_roles = function_exists('get_editable_roles') ? get_editable_roles() : array();

    echo '<tr class="form-field">';
    echo '<th scope="row" valign="top"><label>' . esc_html__('Berechtigungsmaske (Rollen)', CPC2_TEXT_DOMAIN) . '</label></th>';
    echo '<td>';
    echo '<table class="widefat" style="max-width:760px">';
    echo '<thead><tr>';
    echo '<th style="width:28%">' . esc_html__('Rolle', CPC2_TEXT_DOMAIN) . '</th>';
    echo '<th>' . esc_html__('Direkt veroeffentlichen', CPC2_TEXT_DOMAIN) . '</th>';
    echo '<th>' . esc_html__('Im Profil erstellen', CPC2_TEXT_DOMAIN) . '</th>';
    echo '<th>' . esc_html__('In Gruppen erstellen', CPC2_TEXT_DOMAIN) . '</th>';
    echo '</tr></thead><tbody>';
    foreach ($editable_roles as $role_key => $role_info) {
        $role_label = isset($role_info['name']) ? translate_user_role($role_info['name']) : $role_key;
        echo '<tr>';
        echo '<td><strong>' . esc_html($role_label) . '</strong><br /><small>' . esc_html($role_key) . '</small></td>';
        echo '<td><label><input type="checkbox" style="width:10px" name="cpc_events_permissions[' . esc_attr($role_key) . '][publish]" value="1"' . checked(!empty($role_permissions[$role_key]['publish']), true, false) . ' /> ' . esc_html__('Ja', CPC2_TEXT_DOMAIN) . '</label></td>';
        echo '<td><label><input type="checkbox" style="width:10px" name="cpc_events_permissions[' . esc_attr($role_key) . '][submit_profile]" value="1"' . checked(!empty($role_permissions[$role_key]['submit_profile']), true, false) . ' /> ' . esc_html__('Ja', CPC2_TEXT_DOMAIN) . '</label></td>';
        echo '<td><label><input type="checkbox" style="width:10px" name="cpc_events_permissions[' . esc_attr($role_key) . '][submit_group]" value="1"' . checked(!empty($role_permissions[$role_key]['submit_group']), true, false) . ' /> ' . esc_html__('Ja', CPC2_TEXT_DOMAIN) . '</label></td>';
        echo '</tr>';
    }
    echo '</tbody></table>';
    echo '<span class="description">' . esc_html__('Wenn "Direkt veroeffentlichen" nicht gesetzt ist, wird das Event als "Ausstehend" gespeichert.', CPC2_TEXT_DOMAIN) . '</span>';
    echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<th scope="row" valign="top"><label>' . esc_html__('Kalender-Kontexte', CPC2_TEXT_DOMAIN) . '</label></th>';
    echo '<td>';
    echo '<label><input type="checkbox" style="width:10px" name="cpc_events_allow_user_calendar" value="1"' . checked(cpc_events_allow_user_calendar(), true, false) . ' /> ' . esc_html__('Benutzer-Kalender im Profil aktivieren', CPC2_TEXT_DOMAIN) . '</label>';
    echo '<br /><label><input type="checkbox" style="width:10px" name="cpc_events_allow_group_calendar" value="1"' . checked(cpc_events_allow_group_calendar(), true, false) . ' /> ' . esc_html__('Gruppen-Kalender aktivieren', CPC2_TEXT_DOMAIN) . '</label>';
    echo '<br /><label><input type="checkbox" style="width:10px" name="cpc_events_group_email_notifications" value="1"' . checked(cpc_events_group_email_notifications_enabled(), true, false) . ' /> ' . esc_html__('E-Mail-Benachrichtigungen bei neuen Gruppen-Events senden', CPC2_TEXT_DOMAIN) . '</label>';
    echo '<br /><span class="description">' . esc_html__('Gruppen-Kalender benoetigt zusaetzlich den Gruppen-Schalter im jeweiligen Gruppen-Settings-Tab.', CPC2_TEXT_DOMAIN) . '</span>';
    echo '</td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>';
}

function cpc_admin_events_settings_save($the_post) {
    if (isset($the_post['cpc_events_directory_page'])) {
        update_option('cpc_events_directory_page', max(0, (int)$the_post['cpc_events_directory_page']));
    }

    $editable_roles = function_exists('get_editable_roles') ? get_editable_roles() : array();
    $posted_permissions = isset($the_post['cpc_events_permissions']) && is_array($the_post['cpc_events_permissions'])
        ? $the_post['cpc_events_permissions']
        : array();
    $saved_permissions = array();
    foreach ($editable_roles as $role_key => $role_info) {
        $saved_permissions[$role_key] = array();
        foreach (cpc_events_role_permission_keys() as $perm_key) {
            $saved_permissions[$role_key][$perm_key] = !empty($posted_permissions[$role_key][$perm_key]) ? 1 : 0;
        }
    }
    update_option('cpc_events_role_permissions', $saved_permissions);

    update_option('cpc_events_allow_user_calendar', isset($the_post['cpc_events_allow_user_calendar']) ? 1 : 0);
    update_option('cpc_events_allow_group_calendar', isset($the_post['cpc_events_allow_group_calendar']) ? 1 : 0);
    update_option('cpc_events_group_email_notifications', isset($the_post['cpc_events_group_email_notifications']) ? 1 : 0);

    if (isset($the_post['cpc_events_create_page']) && function_exists('cpc_admin_create_standard_page')) {
        $new_id = cpc_admin_create_standard_page('cpc_events_directory_page', array(
            'post_name' => sanitize_title(__('events', CPC2_TEXT_DOMAIN)),
            'post_title' => __('Events', CPC2_TEXT_DOMAIN),
            'post_content' => '[cpc-events]',
            'post_status' => 'publish',
            'post_type' => 'page',
            'comment_status' => 'closed',
        ), 'cpc-events');
        if (!is_wp_error($new_id) && $new_id) {
            update_option('cpc_events_directory_page', (int)$new_id);
        }
    }
}
