<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpc_events_frontend_notice_message($notice) {
    $messages = array(
        'created' => __('Event erstellt.', CPC2_TEXT_DOMAIN),
        'updated' => __('Event aktualisiert.', CPC2_TEXT_DOMAIN),
        'deleted' => __('Event geloescht.', CPC2_TEXT_DOMAIN),
        'prefs_saved' => __('Benachrichtigungseinstellungen gespeichert.', CPC2_TEXT_DOMAIN),
        'pending' => __('Event eingereicht und wartet auf Freigabe.', CPC2_TEXT_DOMAIN),
        'denied' => __('Du hast keine Berechtigung fuer diese Aktion.', CPC2_TEXT_DOMAIN),
        'invalid' => __('Bitte pruefe die Eingaben (Titel/Startzeit).', CPC2_TEXT_DOMAIN),
        'nonce' => __('Sicherheitspruefung fehlgeschlagen.', CPC2_TEXT_DOMAIN),
        'missing' => __('Event nicht gefunden.', CPC2_TEXT_DOMAIN),
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

    $is_error = in_array($notice, array('denied', 'invalid', 'nonce', 'missing'), true);
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

function cpc_events_user_can_edit_event($event, $user_id = 0) {
    if (!$event || empty($event->ID)) {
        return false;
    }

    $user_id = $user_id ? (int)$user_id : get_current_user_id();
    if ($user_id <= 0) {
        return false;
    }

    if (user_can($user_id, 'manage_options') || user_can($user_id, 'edit_post', (int)$event->ID)) {
        return true;
    }

    $author_id = (int)$event->post_author;
    if ($author_id > 0 && $author_id === $user_id) {
        return true;
    }

    $group_id = (int)get_post_meta((int)$event->ID, 'cpc_event_group_id', true);
    if ($group_id > 0 && function_exists('cpc_is_group_admin') && cpc_is_group_admin($user_id, $group_id)) {
        return true;
    }

    return false;
}

function cpc_events_build_event_payload($post_data) {
    $title = isset($post_data['cpc_events_title']) ? sanitize_text_field(wp_unslash($post_data['cpc_events_title'])) : '';
    $content = isset($post_data['cpc_events_content']) ? wp_kses_post(wp_unslash($post_data['cpc_events_content'])) : '';
    $start = isset($post_data['cpc_events_start']) ? sanitize_text_field(wp_unslash($post_data['cpc_events_start'])) : '';
    $end = isset($post_data['cpc_events_end']) ? sanitize_text_field(wp_unslash($post_data['cpc_events_end'])) : '';
    $location = isset($post_data['cpc_events_location']) ? sanitize_text_field(wp_unslash($post_data['cpc_events_location'])) : '';

    $start_ts = $start ? strtotime($start) : 0;
    $end_ts = $end ? strtotime($end) : 0;

    if ($title === '' || !$start_ts) {
        return new WP_Error('invalid', 'invalid');
    }

    if ($end !== '' && $end_ts && $end_ts < $start_ts) {
        return new WP_Error('invalid', 'invalid');
    }

    if ($end === '') {
        $end_ts = $start_ts + HOUR_IN_SECONDS;
        $end = date('Y-m-d\\TH:i', $end_ts);
    }

    return array(
        'title' => $title,
        'content' => $content,
        'start' => $start,
        'end' => $end,
        'location' => $location,
        'start_ts' => (int)$start_ts,
        'end_ts' => (int)$end_ts,
    );
}

function cpc_events_handle_frontend_submission() {
    if (!is_user_logged_in()) {
        return;
    }
    if (empty($_POST['cpc_events_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash($_POST['cpc_events_action']));
    if (!in_array($action, array('create_event', 'update_event', 'delete_event', 'save_notify_pref'), true)) {
        return;
    }

    $nonce = isset($_POST['cpc_events_nonce']) ? sanitize_text_field(wp_unslash($_POST['cpc_events_nonce'])) : '';
    if (!$nonce || !wp_verify_nonce($nonce, 'cpc_events_frontend_action')) {
        cpc_events_redirect_with_notice('nonce');
    }

    $component = isset($_POST['cpc_events_component']) ? sanitize_key(wp_unslash($_POST['cpc_events_component'])) : '';
    $component_id = isset($_POST['cpc_events_component_id']) ? (int)$_POST['cpc_events_component_id'] : 0;
    $user_id = get_current_user_id();

    if ($action === 'save_notify_pref') {
        $enabled = !empty($_POST['cpc_events_notify_group_events']) ? 1 : 0;
        update_user_meta($user_id, 'cpc_events_notify_group_events', $enabled ? '1' : '0');
        cpc_events_redirect_with_notice('prefs_saved');
    }

    if ($action === 'create_event') {
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

        $payload = cpc_events_build_event_payload($_POST);
        if (is_wp_error($payload)) {
            cpc_events_redirect_with_notice('invalid');
        }

        $post_status = function_exists('cpc_events_user_matches_publish_scope') && cpc_events_user_matches_publish_scope($user_id) ? 'publish' : 'pending';
        $post_id = wp_insert_post(array(
            'post_type' => 'cpc_event',
            'post_status' => $post_status,
            'post_title' => $payload['title'],
            'post_content' => $payload['content'],
            'post_author' => $user_id,
        ), true);

        if (is_wp_error($post_id) || !$post_id) {
            cpc_events_redirect_with_notice('invalid');
        }

        update_post_meta($post_id, 'cpc_event_start', $payload['start']);
        update_post_meta($post_id, 'cpc_event_end', $payload['end']);
        update_post_meta($post_id, 'cpc_event_location', $payload['location']);
        update_post_meta($post_id, 'cpc_event_start_ts', $payload['start_ts']);
        update_post_meta($post_id, 'cpc_event_end_ts', $payload['end_ts']);

        if ($component === 'groups' && $component_id > 0) {
            update_post_meta($post_id, 'cpc_event_group_id', $component_id);
        }

        cpc_events_redirect_with_notice($post_status === 'publish' ? 'created' : 'pending');
    }

    $event_id = isset($_POST['cpc_events_event_id']) ? (int)$_POST['cpc_events_event_id'] : 0;
    if ($event_id <= 0) {
        cpc_events_redirect_with_notice('missing');
    }

    $event = get_post($event_id);
    if (!$event || $event->post_type !== 'cpc_event') {
        cpc_events_redirect_with_notice('missing');
    }

    if (!cpc_events_user_can_edit_event($event, $user_id)) {
        cpc_events_redirect_with_notice('denied');
    }

    if ($action === 'delete_event') {
        wp_trash_post($event_id);
        cpc_events_redirect_with_notice('deleted');
    }

    $payload = cpc_events_build_event_payload($_POST);

function cpc_events_render_notification_pref_form($component, $component_id) {
    if (!is_user_logged_in()) {
        return '';
    }

    $current = get_user_meta(get_current_user_id(), 'cpc_events_notify_group_events', true);
    $enabled = !in_array((string)$current, array('0', 'no', 'off', 'false'), true);

    $html = '<form method="post" class="cpc-events-notify-pref-form">';
    $html .= '<input type="hidden" name="cpc_events_action" value="save_notify_pref" />';
    $html .= '<input type="hidden" name="cpc_events_component" value="' . esc_attr($component) . '" />';
    $html .= '<input type="hidden" name="cpc_events_component_id" value="' . (int)$component_id . '" />';
    $html .= '<input type="hidden" name="cpc_events_nonce" value="' . esc_attr(wp_create_nonce('cpc_events_frontend_action')) . '" />';
    $html .= '<label>';
    $html .= '<input type="checkbox" name="cpc_events_notify_group_events" value="1" ' . checked($enabled, true, false) . ' /> ';
    $html .= esc_html__('E-Mail bei neuen Gruppen-Events erhalten', CPC2_TEXT_DOMAIN);
    $html .= '</label> ';
    $html .= '<button type="submit" class="cpc-btn cpc-btn-secondary">' . esc_html__('Speichern', CPC2_TEXT_DOMAIN) . '</button>';
    $html .= '</form>';

    return $html;
}
    if (is_wp_error($payload)) {
        cpc_events_redirect_with_notice('invalid');
    }

    $result = wp_update_post(array(
        'ID' => $event_id,
        'post_title' => $payload['title'],
        'post_content' => $payload['content'],
    ), true);

    if (is_wp_error($result)) {
        cpc_events_redirect_with_notice('invalid');
    }

    update_post_meta($event_id, 'cpc_event_start', $payload['start']);
    update_post_meta($event_id, 'cpc_event_end', $payload['end']);
    update_post_meta($event_id, 'cpc_event_location', $payload['location']);
    update_post_meta($event_id, 'cpc_event_start_ts', $payload['start_ts']);
    update_post_meta($event_id, 'cpc_event_end_ts', $payload['end_ts']);

    cpc_events_redirect_with_notice('updated');
}
add_action('init', 'cpc_events_handle_frontend_submission');

function cpc_events_get_context_statuses($component, $component_id) {
    $component = sanitize_key((string)$component);
    $component_id = (int)$component_id;

    if (current_user_can('manage_options')) {
        return array('publish', 'pending');
    }

    if ($component === 'members' && get_current_user_id() === $component_id) {
        return array('publish', 'pending');
    }

    if ($component === 'groups' && function_exists('cpc_is_group_admin') && cpc_is_group_admin(get_current_user_id(), $component_id)) {
        return array('publish', 'pending');
    }

    return array('publish');
}

function cpc_events_get_context_events($component, $component_id, $statuses, $month = '') {
    $component = sanitize_key((string)$component);
    $component_id = (int)$component_id;

    $args = array(
        'post_type' => 'cpc_event',
        'post_status' => $statuses,
        'posts_per_page' => 200,
        'orderby' => 'meta_value_num',
        'meta_key' => 'cpc_event_start_ts',
        'order' => 'ASC',
        'no_found_rows' => true,
    );

    if ($component === 'members') {
        $args['author'] = $component_id;
    }

    if ($component === 'groups') {
        $args['meta_query'] = array(
            array(
                'key' => 'cpc_event_group_id',
                'value' => $component_id,
                'type' => 'NUMERIC',
            ),
        );
    }

    $events = get_posts($args);
    if (!$month) {
        return $events;
    }

    $filtered = array();
    $month_start = strtotime($month . '-01 00:00:00');
    $month_end = strtotime(date('Y-m-t 23:59:59', $month_start));

    foreach ($events as $event) {
        $start_ts = (int)get_post_meta($event->ID, 'cpc_event_start_ts', true);
        if ($start_ts >= $month_start && $start_ts <= $month_end) {
            $filtered[] = $event;
        }
    }

    return $filtered;
}

function cpc_events_get_requested_month() {
    $raw = isset($_GET['cpc_events_month']) ? sanitize_text_field(wp_unslash($_GET['cpc_events_month'])) : '';
    if (preg_match('/^\\d{4}-\\d{2}$/', $raw)) {
        $ts = strtotime($raw . '-01');
        if ($ts) {
            return date('Y-m', $ts);
        }
    }

    return date('Y-m', current_time('timestamp'));
}

function cpc_events_month_nav_urls($month) {
    $base = remove_query_arg(array('cpc_events_month', 'cpc_events_notice'));
    $month_ts = strtotime($month . '-01');

    return array(
        'prev' => add_query_arg('cpc_events_month', date('Y-m', strtotime('-1 month', $month_ts)), $base),
        'next' => add_query_arg('cpc_events_month', date('Y-m', strtotime('+1 month', $month_ts)), $base),
        'today' => add_query_arg('cpc_events_month', date('Y-m', current_time('timestamp')), $base),
    );
}

function cpc_events_render_month_calendar($component, $component_id, $events, $month) {
    $month_ts = strtotime($month . '-01');
    $days_in_month = (int)date('t', $month_ts);
    $first_weekday = (int)date('N', $month_ts);
    $today = date('Y-m-d', current_time('timestamp'));

    $events_by_day = array();
    foreach ($events as $event) {
        $start_ts = (int)get_post_meta($event->ID, 'cpc_event_start_ts', true);
        if (!$start_ts) {
            continue;
        }
        $day_key = date('Y-m-d', $start_ts);
        if (!isset($events_by_day[$day_key])) {
            $events_by_day[$day_key] = array();
        }
        $events_by_day[$day_key][] = $event;
    }

    $labels = array(__('Mo', CPC2_TEXT_DOMAIN), __('Di', CPC2_TEXT_DOMAIN), __('Mi', CPC2_TEXT_DOMAIN), __('Do', CPC2_TEXT_DOMAIN), __('Fr', CPC2_TEXT_DOMAIN), __('Sa', CPC2_TEXT_DOMAIN), __('So', CPC2_TEXT_DOMAIN));

    $html = '<div class="cpc-events-calendar">';
    foreach ($labels as $label) {
        $html .= '<div class="cpc-events-calendar-head">' . esc_html($label) . '</div>';
    }

    for ($i = 1; $i < $first_weekday; $i++) {
        $html .= '<div class="cpc-events-day cpc-events-day-empty"></div>';
    }

    for ($day = 1; $day <= $days_in_month; $day++) {
        $day_date = date('Y-m-d', strtotime($month . '-' . str_pad((string)$day, 2, '0', STR_PAD_LEFT)));
        $is_today = ($day_date === $today);
        $has_events = !empty($events_by_day[$day_date]);

        $classes = 'cpc-events-day';
        if ($is_today) {
            $classes .= ' is-today';
        }
        if ($has_events) {
            $classes .= ' has-events';
        }

        $html .= '<div class="' . esc_attr($classes) . '">';
        $html .= '<div class="cpc-events-day-number">' . (int)$day . '</div>';

        if ($has_events) {
            $html .= '<div class="cpc-events-day-list">';
            $slice = array_slice($events_by_day[$day_date], 0, 2);
            foreach ($slice as $event) {
                $html .= '<a class="cpc-events-chip" href="#cpc-event-' . (int)$event->ID . '">' . esc_html(get_the_title($event->ID)) . '</a>';
            }
            $remaining = count($events_by_day[$day_date]) - count($slice);
            if ($remaining > 0) {
                $html .= '<span class="cpc-events-chip-more">+' . (int)$remaining . '</span>';
            }
            $html .= '</div>';
        }

        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
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
    $html .= '<summary class="cpc_button cpc-events-create-summary">' . esc_html__('Neues Event', CPC2_TEXT_DOMAIN) . '</summary>';
    $html .= '<form method="post" class="cpc-events-create-form">';
    $html .= '<input type="hidden" name="cpc_events_action" value="create_event" />';
    $html .= '<input type="hidden" name="cpc_events_component" value="' . esc_attr($component) . '" />';
    $html .= '<input type="hidden" name="cpc_events_component_id" value="' . (int)$component_id . '" />';
    $html .= '<input type="hidden" name="cpc_events_nonce" value="' . esc_attr(wp_create_nonce('cpc_events_frontend_action')) . '" />';

    $html .= '<div class="cpc-events-form-grid">';
    $html .= '<p><label><strong>' . esc_html__('Titel', CPC2_TEXT_DOMAIN) . '</strong></label><input type="text" name="cpc_events_title" required class="widefat" /></p>';
    $html .= '<p><label><strong>' . esc_html__('Ort', CPC2_TEXT_DOMAIN) . '</strong></label><input type="text" name="cpc_events_location" class="widefat" /></p>';
    $html .= '<p><label><strong>' . esc_html__('Start', CPC2_TEXT_DOMAIN) . '</strong></label><input type="datetime-local" name="cpc_events_start" required /></p>';
    $html .= '<p><label><strong>' . esc_html__('Ende', CPC2_TEXT_DOMAIN) . '</strong></label><input type="datetime-local" name="cpc_events_end" /></p>';
    $html .= '</div>';

    $html .= '<p><label><strong>' . esc_html__('Beschreibung', CPC2_TEXT_DOMAIN) . '</strong></label><textarea name="cpc_events_content" rows="4" class="widefat"></textarea></p>';
    $html .= '<p><button type="submit" class="cpc-btn cpc-btn-primary">' . esc_html__('Event speichern', CPC2_TEXT_DOMAIN) . '</button></p>';
    $html .= '</form>';
    $html .= '</details>';

    return $html;
}

function cpc_events_render_event_cards(array $events, $component, $component_id) {
    if (empty($events)) {
        return '<p class="cpc-events-empty">' . esc_html__('Keine Events vorhanden.', CPC2_TEXT_DOMAIN) . '</p>';
    }

    $component = sanitize_key((string)$component);
    $component_id = (int)$component_id;
    $user_id = get_current_user_id();

    $html = '<div class="cpc-events-list">';
    foreach ($events as $event) {
        $start_ts = (int)get_post_meta($event->ID, 'cpc_event_start_ts', true);
        $end_ts = (int)get_post_meta($event->ID, 'cpc_event_end_ts', true);
        $location = get_post_meta($event->ID, 'cpc_event_location', true);
        $can_edit = cpc_events_user_can_edit_event($event, $user_id);

        $html .= '<article id="cpc-event-' . (int)$event->ID . '" class="cpc-event-card">';
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

        $excerpt = wp_trim_words(wp_strip_all_tags((string)$event->post_content), 24);
        if ($excerpt) {
            $html .= '<div class="cpc-event-excerpt">' . esc_html($excerpt) . '</div>';
        }

        if ($can_edit) {
            $html .= '<details class="cpc-events-edit-toggle">';
            $html .= '<summary>' . esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN) . '</summary>';
            $html .= '<form method="post" class="cpc-events-edit-form">';
            $html .= '<input type="hidden" name="cpc_events_action" value="update_event" />';
            $html .= '<input type="hidden" name="cpc_events_event_id" value="' . (int)$event->ID . '" />';
            $html .= '<input type="hidden" name="cpc_events_component" value="' . esc_attr($component) . '" />';
            $html .= '<input type="hidden" name="cpc_events_component_id" value="' . (int)$component_id . '" />';
            $html .= '<input type="hidden" name="cpc_events_nonce" value="' . esc_attr(wp_create_nonce('cpc_events_frontend_action')) . '" />';

            $html .= '<div class="cpc-events-form-grid">';
            $html .= '<p><label><strong>' . esc_html__('Titel', CPC2_TEXT_DOMAIN) . '</strong></label><input type="text" name="cpc_events_title" required class="widefat" value="' . esc_attr(get_the_title($event->ID)) . '" /></p>';
            $html .= '<p><label><strong>' . esc_html__('Ort', CPC2_TEXT_DOMAIN) . '</strong></label><input type="text" name="cpc_events_location" class="widefat" value="' . esc_attr((string)$location) . '" /></p>';
            $html .= '<p><label><strong>' . esc_html__('Start', CPC2_TEXT_DOMAIN) . '</strong></label><input type="datetime-local" name="cpc_events_start" required value="' . esc_attr((string)get_post_meta($event->ID, 'cpc_event_start', true)) . '" /></p>';
            $html .= '<p><label><strong>' . esc_html__('Ende', CPC2_TEXT_DOMAIN) . '</strong></label><input type="datetime-local" name="cpc_events_end" value="' . esc_attr((string)get_post_meta($event->ID, 'cpc_event_end', true)) . '" /></p>';
            $html .= '</div>';

            $html .= '<p><label><strong>' . esc_html__('Beschreibung', CPC2_TEXT_DOMAIN) . '</strong></label><textarea name="cpc_events_content" rows="3" class="widefat">' . esc_textarea((string)$event->post_content) . '</textarea></p>';
            $html .= '<p><button type="submit" class="cpc-btn cpc-btn-primary">' . esc_html__('Aenderungen speichern', CPC2_TEXT_DOMAIN) . '</button></p>';
            $html .= '</form>';

            $html .= '<form method="post" class="cpc-events-delete-form" onsubmit="return confirm(\'' . esc_js(__('Event wirklich loeschen?', CPC2_TEXT_DOMAIN)) . '\');">';
            $html .= '<input type="hidden" name="cpc_events_action" value="delete_event" />';
            $html .= '<input type="hidden" name="cpc_events_event_id" value="' . (int)$event->ID . '" />';
            $html .= '<input type="hidden" name="cpc_events_component" value="' . esc_attr($component) . '" />';
            $html .= '<input type="hidden" name="cpc_events_component_id" value="' . (int)$component_id . '" />';
            $html .= '<input type="hidden" name="cpc_events_nonce" value="' . esc_attr(wp_create_nonce('cpc_events_frontend_action')) . '" />';
            $html .= '<p><button type="submit" class="cpc-btn cpc-btn-danger">' . esc_html__('Event loeschen', CPC2_TEXT_DOMAIN) . '</button></p>';
            $html .= '</form>';

            $html .= '</details>';
        }

        $html .= '</article>';
    }
    $html .= '</div>';

    return $html;
}

function cpc_events_render_context_layout($component, $component_id, $title) {
    $month = cpc_events_get_requested_month();
    $statuses = cpc_events_get_context_statuses($component, $component_id);
    $month_events = cpc_events_get_context_events($component, $component_id, $statuses, $month);
    $all_events = cpc_events_get_context_events($component, $component_id, $statuses, '');

    $nav = cpc_events_month_nav_urls($month);

    $html = '<div class="cpc-events-context">';
    $html .= '<div class="cpc-events-headline">';
    $html .= '<h3>' . esc_html($title) . '</h3>';
    $html .= '<div class="cpc-events-month-nav">';
    $html .= '<a href="' . esc_url($nav['prev']) . '">&larr; ' . esc_html__('Zurueck', CPC2_TEXT_DOMAIN) . '</a>';
    $html .= '<strong>' . esc_html(date_i18n('F Y', strtotime($month . '-01'))) . '</strong>';
    $html .= '<a href="' . esc_url($nav['next']) . '">' . esc_html__('Weiter', CPC2_TEXT_DOMAIN) . ' &rarr;</a>';
    $html .= '<a class="cpc-events-today-link" href="' . esc_url($nav['today']) . '">' . esc_html__('Heute', CPC2_TEXT_DOMAIN) . '</a>';
    $html .= '</div>';
    $html .= '</div>';

    $html .= cpc_events_render_frontend_notice();
    if ($component === 'groups') {
        $html .= cpc_events_render_notification_pref_form($component, $component_id);
    }
    $html .= cpc_events_render_create_form($component, $component_id);
    $html .= cpc_events_render_month_calendar($component, $component_id, $month_events, $month);

    $html .= '<h4 class="cpc-events-list-title">' . esc_html__('Event-Liste', CPC2_TEXT_DOMAIN) . '</h4>';
    $html .= cpc_events_render_event_cards($all_events, $component, $component_id);
    $html .= '</div>';

    return $html;
}

function cpc_events_add_profile_tab($tabs, $user_id, $viewer_id) {
    if (!cpc_events_is_core_enabled()) {
        return $tabs;
    }

    if (function_exists('cpc_events_allow_user_calendar') && !cpc_events_allow_user_calendar()) {
        return $tabs;
    }

    $count = (int)(new WP_Query(array(
        'post_type' => 'cpc_event',
        'post_status' => 'publish',
        'author' => (int)$user_id,
        'posts_per_page' => 1,
        'no_found_rows' => false,
        'fields' => 'ids',
    )))->found_posts;

    $label = __('Events', CPC2_TEXT_DOMAIN);
    if ($count > 0) {
        $label .= ' (' . $count . ')';
    }

    $tabs['events'] = array(
        'label' => $label,
        'icon' => 'calendar-alt',
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

    return cpc_events_render_context_layout('members', $user_id, __('Events', CPC2_TEXT_DOMAIN));
}
add_filter('cpc_profile_tab_content', 'cpc_events_render_profile_tab_content', 20, 4);

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
        'label' => __('Events', CPC2_TEXT_DOMAIN),
        'icon' => 'calendar-alt',
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

    return cpc_events_render_context_layout('groups', $group_id, __('Gruppen-Events', CPC2_TEXT_DOMAIN));
}
add_filter('cpc_group_tab_content_events', 'cpc_events_render_group_tab_content', 20, 3);

function cpc_events_on_save_post($post_id, $post) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if ($post->post_status !== 'publish') {
        return;
    }

    if (!get_post_meta($post_id, '_cpc_event_mail_sent', true)) {
        $group_id = (int)get_post_meta($post_id, 'cpc_event_group_id', true);
        if ($group_id > 0 && function_exists('cpc_events_send_group_event_notifications')) {
            cpc_events_send_group_event_notifications($post_id, $group_id, (int)$post->post_author);
        }
        update_post_meta($post_id, '_cpc_event_mail_sent', '1');
    }

    if (get_post_meta($post_id, '_cpc_event_activity_logged', true)) {
        return;
    }
    update_post_meta($post_id, '_cpc_event_activity_logged', '1');

    if (!function_exists('cpc_api_insert_activity_post') || !is_user_logged_in()) {
        return;
    }

    $author_id = (int)$post->post_author;
    $title = get_the_title($post_id);
    $url = get_permalink($post_id);

    $message = sprintf(
        __('Hat ein neues Event erstellt: <a href="%s">%s</a>', CPC2_TEXT_DOMAIN),
        esc_url($url),
        esc_html($title)
    );

    cpc_api_insert_activity_post($message, $author_id, $author_id);
}
add_action('save_post_cpc_event', 'cpc_events_on_save_post', 20, 2);

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
        'post_type' => 'cpc_group',
        'post_status' => 'publish',
        'posts_per_page' => 200,
        'orderby' => 'title',
        'order' => 'ASC',
        'no_found_rows' => true,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'cpc_group_has_events',
                'value' => '1',
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
