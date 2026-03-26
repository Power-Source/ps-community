<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpc_events_frontend_notice_message($notice) {
    $messages = array(
        'created' => __('Event erstellt.', CPC2_TEXT_DOMAIN),
        'pending' => __('Event eingereicht und wartet auf Freigabe.', CPC2_TEXT_DOMAIN),
        'denied' => __('Du hast keine Berechtigung für diese Aktion.', CPC2_TEXT_DOMAIN),
        'invalid' => __('Bitte prüfe die Eingaben (Titel/Startzeit).', CPC2_TEXT_DOMAIN),
        'nonce' => __('Sicherheitsprüfung fehlgeschlagen.', CPC2_TEXT_DOMAIN),
    );

    return isset($messages[$notice]) ? $messages[$notice] : '';
}

function cpc_events_render_frontend_notice() {
    if (empty($_GET['cpc_events_notice'])) {
        return '';
    }

    $notice = sanitize_key(wp_unslash($_GET['cpc_events_notice']));
    $message = cpc_events_frontend_notice_message($notice);
    if (!$message) {
        return '';
    }

    $is_error = in_array($notice, array('denied', 'invalid', 'nonce'), true);
    $class = $is_error ? 'cpc-events-notice cpc-events-notice-error' : 'cpc-events-notice cpc-events-notice-success';
    return '<div class="' . esc_attr($class) . '">' . esc_html($message) . '</div>';
}

function cpc_events_redirect_with_notice($notice) {
    $url = wp_get_referer();
    if (!$url) {
        $url = home_url('/');
    }
    $url = remove_query_arg('cpc_events_notice', $url);
    $url = add_query_arg('cpc_events_notice', sanitize_key($notice), $url);
    wp_safe_redirect($url);
    exit;
}

function cpc_events_user_can_submit_member_event($profile_user_id, $user_id = 0) {
    if (!function_exists('cpc_events_allow_user_calendar') || !cpc_events_allow_user_calendar()) {
        return false;
    }
    if (!function_exists('cpc_events_role_can')) {
        return false;
    }

    $profile_user_id = (int)$profile_user_id;
    $user_id = $user_id ? (int)$user_id : get_current_user_id();
    if ($user_id <= 0 || $profile_user_id <= 0) {
        return false;
    }

    if (!cpc_events_role_can($user_id, 'submit_profile')) {
        return false;
    }

    if ((int)$user_id === (int)$profile_user_id) {
        return true;
    }

    return user_can($user_id, 'manage_options');
}

function cpc_events_user_can_submit_group_event($group_id, $user_id = 0) {
    if (!function_exists('cpc_events_allow_group_calendar') || !cpc_events_allow_group_calendar()) {
        return false;
    }
    if (!function_exists('cpc_events_role_can')) {
        return false;
    }

    $group_id = (int)$group_id;
    $user_id = $user_id ? (int)$user_id : get_current_user_id();
    if ($group_id <= 0 || $user_id <= 0) {
        return false;
    }
    if (!cpc_events_role_can($user_id, 'submit_group')) {
        return false;
    }
    if (!get_post_meta($group_id, 'cpc_group_has_events', true)) {
        return false;
    }
    if (function_exists('cpc_can_view_group') && !cpc_can_view_group($user_id, $group_id)) {
        return false;
    }

    if (function_exists('cpc_is_group_admin') && cpc_is_group_admin($user_id, $group_id)) {
        return true;
    }

    if (function_exists('cpc_is_group_member') && cpc_is_group_member($user_id, $group_id)) {
        return true;
    }

    return false;
}

function cpc_events_render_create_form($component, $component_id) {
    $component = sanitize_key((string)$component);
    $component_id = (int)$component_id;
    $user_id = get_current_user_id();

    if (!is_user_logged_in()) {
        return '';
    }

    $allowed = false;
    if ($component === 'members') {
        $allowed = cpc_events_user_can_submit_member_event($component_id, $user_id);
    }
    if ($component === 'groups') {
        $allowed = cpc_events_user_can_submit_group_event($component_id, $user_id);
    }
    if (!$allowed) {
        return '';
    }

    $html  = '<details class="cpc-events-create-toggle">';
    $html .= '<summary class="cpc_button cpc-events-create-summary">' . esc_html__('Event erstellen', CPC2_TEXT_DOMAIN) . '</summary>';
    $html .= '<form method="post" class="cpc-events-create-form">';
    $html .= '<input type="hidden" name="cpc_events_action" value="create_event" />';
    $html .= '<input type="hidden" name="cpc_events_component" value="' . esc_attr($component) . '" />';
    $html .= '<input type="hidden" name="cpc_events_component_id" value="' . (int)$component_id . '" />';
    $html .= '<input type="hidden" name="cpc_events_nonce" value="' . esc_attr(wp_create_nonce('cpc_events_frontend_action')) . '" />';

    $html .= '<p><label><strong>' . esc_html__('Titel', CPC2_TEXT_DOMAIN) . '</strong></label><br />';
    $html .= '<input type="text" name="cpc_events_title" required class="widefat" /></p>';

    $html .= '<p><label><strong>' . esc_html__('Start', CPC2_TEXT_DOMAIN) . '</strong></label><br />';
    $html .= '<input type="datetime-local" name="cpc_events_start" required /></p>';

    $html .= '<p><label><strong>' . esc_html__('Ende', CPC2_TEXT_DOMAIN) . '</strong></label><br />';
    $html .= '<input type="datetime-local" name="cpc_events_end" /></p>';

    $html .= '<p><label><strong>' . esc_html__('Ort', CPC2_TEXT_DOMAIN) . '</strong></label><br />';
    $html .= '<input type="text" name="cpc_events_location" class="widefat" /></p>';

    $html .= '<p><label><strong>' . esc_html__('Beschreibung', CPC2_TEXT_DOMAIN) . '</strong></label><br />';
    $html .= '<textarea name="cpc_events_content" rows="5" class="widefat"></textarea></p>';

    $html .= '<p><button type="submit" class="cpc-btn cpc-btn-primary">' . esc_html__('Event speichern', CPC2_TEXT_DOMAIN) . '</button></p>';
    $html .= '</form>';
    $html .= '</details>';

    return $html;
}

function cpc_events_handle_frontend_submission() {
    if (!is_user_logged_in()) {
        return;
    }
    if (!isset($_POST['cpc_events_action']) || $_POST['cpc_events_action'] !== 'create_event') {
        return;
    }

    $nonce = isset($_POST['cpc_events_nonce']) ? sanitize_text_field(wp_unslash($_POST['cpc_events_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'cpc_events_frontend_action')) {
        cpc_events_redirect_with_notice('nonce');
    }

    $component = isset($_POST['cpc_events_component']) ? sanitize_key(wp_unslash($_POST['cpc_events_component'])) : '';
    $component_id = isset($_POST['cpc_events_component_id']) ? (int)$_POST['cpc_events_component_id'] : 0;
    $user_id = get_current_user_id();

    $allowed = false;
    if ($component === 'members') {
        $allowed = cpc_events_user_can_submit_member_event($component_id, $user_id);
    }
    if ($component === 'groups') {
        $allowed = cpc_events_user_can_submit_group_event($component_id, $user_id);
    }
    if (!$allowed) {
        cpc_events_redirect_with_notice('denied');
    }

    $title = isset($_POST['cpc_events_title']) ? sanitize_text_field(wp_unslash($_POST['cpc_events_title'])) : '';
    $content = isset($_POST['cpc_events_content']) ? wp_kses_post(wp_unslash($_POST['cpc_events_content'])) : '';
    $start = isset($_POST['cpc_events_start']) ? sanitize_text_field(wp_unslash($_POST['cpc_events_start'])) : '';
    $end = isset($_POST['cpc_events_end']) ? sanitize_text_field(wp_unslash($_POST['cpc_events_end'])) : '';
    $location = isset($_POST['cpc_events_location']) ? sanitize_text_field(wp_unslash($_POST['cpc_events_location'])) : '';

    $start_ts = $start ? strtotime($start) : 0;
    $end_ts = $end ? strtotime($end) : 0;
    if ($title === '' || !$start_ts) {
        cpc_events_redirect_with_notice('invalid');
    }
    if ($end && $end_ts && $end_ts < $start_ts) {
        cpc_events_redirect_with_notice('invalid');
    }

    $post_status = function_exists('cpc_events_user_matches_publish_scope') && cpc_events_user_matches_publish_scope($user_id) ? 'publish' : 'pending';
    $post_id = wp_insert_post(array(
        'post_type' => 'cpc_event',
        'post_status' => $post_status,
        'post_title' => $title,
        'post_content' => $content,
        'post_author' => $user_id,
    ), true);

    if (is_wp_error($post_id) || !$post_id) {
        cpc_events_redirect_with_notice('invalid');
    }

    update_post_meta($post_id, 'cpc_event_start', $start);
    update_post_meta($post_id, 'cpc_event_end', $end);
    update_post_meta($post_id, 'cpc_event_location', $location);
    update_post_meta($post_id, 'cpc_event_start_ts', (int)$start_ts);
    update_post_meta($post_id, 'cpc_event_end_ts', (int)$end_ts);

    if ($component === 'groups' && $component_id > 0) {
        update_post_meta($post_id, 'cpc_event_group_id', $component_id);
    }

    cpc_events_redirect_with_notice($post_status === 'publish' ? 'created' : 'pending');
}
add_action('init', 'cpc_events_handle_frontend_submission');

/* ── Hilfsfunktionen ─────────────────────────────────────────────────────── */

/**
 * Rendert eine Liste von Event-Posts als HTML-Cards.
 *
 * @param WP_Post[] $events
 * @return string
 */
function cpc_events_render_event_cards(array $events) {
    if (empty($events)) {
        return '<p class="cpc-events-empty">' . esc_html__('Keine Events vorhanden.', CPC2_TEXT_DOMAIN) . '</p>';
    }

    $html = '<div class="cpc-events-list">';
    foreach ($events as $event) {
        $start_ts = (int)get_post_meta($event->ID, 'cpc_event_start_ts', true);
        $end_ts   = (int)get_post_meta($event->ID, 'cpc_event_end_ts', true);
        $location = get_post_meta($event->ID, 'cpc_event_location', true);

        $html .= '<article class="cpc-event-card">';
        $html .= '<h4 class="cpc-event-title"><a href="' . esc_url(get_permalink($event->ID)) . '">' . esc_html(get_the_title($event->ID)) . '</a></h4>';

        if ($start_ts) {
            $html .= '<div class="cpc-event-time">'
                   . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $start_ts));
            if ($end_ts) {
                $html .= ' &ndash; ' . esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $end_ts));
            }
            $html .= '</div>';
        }

        if (!empty($location)) {
            $html .= '<div class="cpc-event-location">' . esc_html($location) . '</div>';
        }

        $excerpt = wp_trim_words(wp_strip_all_tags((string)$event->post_content), 24);
        if ($excerpt) {
            $html .= '<div class="cpc-event-excerpt">' . esc_html($excerpt) . '</div>';
        }

        $html .= '</article>';
    }
    $html .= '</div>';

    return $html;
}

/* ── Profil-Tab ──────────────────────────────────────────────────────────── */

function cpc_events_add_profile_tab($tabs, $user_id, $viewer_id) {
    if (!cpc_events_is_core_enabled()) {
        return $tabs;
    }

    if (function_exists('cpc_events_allow_user_calendar') && !cpc_events_allow_user_calendar()) {
        return $tabs;
    }

    $count = (int)(new WP_Query(array(
        'post_type'      => 'cpc_event',
        'post_status'    => 'publish',
        'author'         => (int)$user_id,
        'posts_per_page' => 1,
        'no_found_rows'  => false,
        'fields'         => 'ids',
    )))->found_posts;

    $label = __('Events', CPC2_TEXT_DOMAIN);
    if ($count > 0) {
        $label .= ' (' . $count . ')';
    }

    $tabs['events'] = array(
        'label'    => $label,
        'icon'     => 'calendar-alt',
        'priority' => 27,
    );

    return $tabs;
}
add_filter('cpc_profile_tabs', 'cpc_events_add_profile_tab', 20, 3);

function cpc_events_render_profile_tab_content($html, $active_tab, $user_id, $shortcode_atts) {
    if ($active_tab !== 'events') {
        return $html;
    }

    $user_id = (int)$user_id;
    if (!$user_id) {
        return '<p>' . esc_html__('Benutzer nicht gefunden.', CPC2_TEXT_DOMAIN) . '</p>';
    }

    $events = get_posts(array(
        'post_type'      => 'cpc_event',
        'post_status'    => ((int)get_current_user_id() === $user_id || current_user_can('manage_options')) ? array('publish', 'pending') : array('publish'),
        'author'         => $user_id,
        'posts_per_page' => 50,
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'cpc_event_start_ts',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    ));

    $html  = '<div class="cpc-events-profile-tab">';
    $html .= '<h3>' . esc_html__('Events', CPC2_TEXT_DOMAIN) . '</h3>';
    $html .= cpc_events_render_frontend_notice();
    $html .= cpc_events_render_create_form('members', $user_id);
    $html .= cpc_events_render_event_cards($events);
    $html .= '</div>';

    return $html;
}
add_filter('cpc_profile_tab_content', 'cpc_events_render_profile_tab_content', 20, 4);

/* ── Gruppen-Tab ─────────────────────────────────────────────────────────── */

function cpc_events_add_group_tab($tabs, $group_id, $user_id) {
    if (!cpc_events_is_core_enabled()) {
        return $tabs;
    }

    if (function_exists('cpc_events_allow_group_calendar') && !cpc_events_allow_group_calendar()) {
        return $tabs;
    }

    if (!get_post_meta((int)$group_id, 'cpc_group_has_events', true)) {
        return $tabs;
    }

    if (function_exists('cpc_can_view_group') && !cpc_can_view_group($user_id, $group_id)) {
        return $tabs;
    }

    $tabs['events'] = array(
        'label'    => __('Events', CPC2_TEXT_DOMAIN),
        'icon'     => 'calendar-alt',
        'priority' => 25,
    );

    return $tabs;
}
add_filter('cpc_group_tabs', 'cpc_events_add_group_tab', 20, 3);

function cpc_events_render_group_tab_content($html, $group_id, $shortcode_atts) {
    $group_id = (int)$group_id;
    if (!$group_id) {
        return '<p>' . esc_html__('Gruppe nicht gefunden.', CPC2_TEXT_DOMAIN) . '</p>';
    }

    if (function_exists('cpc_can_view_group') && !cpc_can_view_group(get_current_user_id(), $group_id)) {
        return '<p>' . esc_html__('Keine Berechtigung.', CPC2_TEXT_DOMAIN) . '</p>';
    }

    $group_post_status = array('publish');
    if (function_exists('cpc_is_group_admin') && cpc_is_group_admin(get_current_user_id(), $group_id)) {
        $group_post_status = array('publish', 'pending');
    }
    if (current_user_can('manage_options')) {
        $group_post_status = array('publish', 'pending');
    }

    $events = get_posts(array(
        'post_type'      => 'cpc_event',
        'post_status'    => $group_post_status,
        'posts_per_page' => 50,
        'orderby'        => 'meta_value_num',
        'meta_key'       => 'cpc_event_start_ts',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'meta_query'     => array(
            array(
                'key'   => 'cpc_event_group_id',
                'value' => $group_id,
                'type'  => 'NUMERIC',
            ),
        ),
    ));

    $html  = '<div class="cpc-events-group-tab">';
    $html .= '<h3>' . esc_html__('Gruppen-Events', CPC2_TEXT_DOMAIN) . '</h3>';
    $html .= cpc_events_render_frontend_notice();
    $html .= cpc_events_render_create_form('groups', $group_id);
    $html .= cpc_events_render_event_cards($events);
    $html .= '</div>';

    return $html;
}
add_filter('cpc_group_tab_content_events', 'cpc_events_render_group_tab_content', 20, 3);

/* ── Aktivitäts-Hook bei Event-Erstellung ────────────────────────────────── */

function cpc_events_on_save_post($post_id, $post) {
    // Nur neue, veröffentlichte Events (kein Update, kein Autosave)
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if ($post->post_status !== 'publish') {
        return;
    }
    // Nur beim ersten Veröffentlichen (kein zweites Mal)
    if (get_post_meta($post_id, '_cpc_event_activity_logged', true)) {
        return;
    }
    update_post_meta($post_id, '_cpc_event_activity_logged', '1');

    if (!function_exists('cpc_api_insert_activity_post') || !is_user_logged_in()) {
        return;
    }

    $author_id = (int)$post->post_author;
    $title     = get_the_title($post_id);
    $url       = get_permalink($post_id);

    /* translators: %s = Event-Titel als Link */
    $message = sprintf(
        __('Hat ein neues Event erstellt: <a href="%s">%s</a>', CPC2_TEXT_DOMAIN),
        esc_url($url),
        esc_html($title)
    );

    cpc_api_insert_activity_post($message, $author_id, $author_id);
}
add_action('save_post_cpc_event', 'cpc_events_on_save_post', 20, 2);

/* ── Gruppen-Selektor im Event-Meta-Box (Admin) ──────────────────────────── */

if (is_admin()) {
    add_action('add_meta_boxes_cpc_event', 'cpc_events_add_group_meta_box');
}

function cpc_events_add_group_meta_box() {
    add_meta_box(
        'cpc_event_group',
        __('Gruppe zuordnen', CPC2_TEXT_DOMAIN),
        'cpc_events_group_meta_box_html',
        'cpc_event',
        'side',
        'default'
    );
}

function cpc_events_group_meta_box_html($post) {
    if (function_exists('cpc_events_allow_group_calendar') && !cpc_events_allow_group_calendar()) {
        echo '<p class="description">' . esc_html__('Gruppen-Kalender sind global deaktiviert.', CPC2_TEXT_DOMAIN) . '</p>';
        return;
    }

    $current_group_id = (int)get_post_meta($post->ID, 'cpc_event_group_id', true);

    $groups = get_posts(array(
        'post_type'      => 'cpc_group',
        'post_status'    => 'publish',
        'posts_per_page' => 200,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
        'fields'         => 'ids',
        'meta_query'     => array(
            array(
                'key'     => 'cpc_group_has_events',
                'value'   => '1',
                'compare' => '=',
            ),
        ),
    ));

    echo '<p>';
    echo '<select name="cpc_event_group_id" style="width:100%;">';
    echo '<option value="0"' . selected($current_group_id, 0, false) . '>' . esc_html__('Keine Gruppe', CPC2_TEXT_DOMAIN) . '</option>';
    foreach ($groups as $gid) {
        echo '<option value="' . (int)$gid . '"' . selected($current_group_id, $gid, false) . '>' . esc_html(get_the_title($gid)) . '</option>';
    }
    echo '</select>';
    echo '</p>';
    echo '<p class="description">' . esc_html__('Nur Gruppen mit aktiviertem Events-Modul werden angezeigt.', CPC2_TEXT_DOMAIN) . '</p>';
}

/* ── CSS Einreihen ───────────────────────────────────────────────────────── */

function cpc_events_enqueue_styles() {
    if (!cpc_events_is_core_enabled()) {
        return;
    }
    $ver = get_option('cp_community_ver', '1.0.0');
    wp_enqueue_style(
        'cpc-events',
        plugins_url('cpc_events.css', __FILE__),
        array(),
        $ver
    );
}
add_action('wp_enqueue_scripts', 'cpc_events_enqueue_styles');
