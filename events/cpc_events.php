<?php

if (!defined('ABSPATH')) {
    exit;
}

function cpc_events_provider_mode() {
    $mode = get_option('cpc_events_provider_mode', 'auto');
    if (!in_array($mode, array('auto', 'internal', 'external'), true)) {
        $mode = 'auto';
    }
    return $mode;
}

function cpc_events_external_is_available() {
    if (apply_filters('cpc_events_external_provider_active', false)) {
        return true;
    }

    if (class_exists('Eab_EventsHub') || post_type_exists('psource_event')) {
        return true;
    }

    return cpc_events_get_eab_shortcode_tag() !== '';
}

function cpc_events_get_eab_shortcode_tag() {
    $shortcodes = (array)apply_filters('cpc_events_external_shortcodes', array(
        'eab_archive',
        'eab_calendar',
        'eab_my_events',
        'eab_events_map',
    ));

    foreach ($shortcodes as $tag) {
        if (shortcode_exists($tag)) {
            return (string)$tag;
        }
    }

    return '';
}

function cpc_events_parse_eab_start_ts($value) {
    if (is_numeric($value)) {
        return (int)$value;
    }
    if (!is_string($value) || $value === '') {
        return 0;
    }

    $timestamp = strtotime($value);
    return $timestamp ? (int)$timestamp : 0;
}

function cpc_events_get_eab_event_times($event_id) {
    $starts = get_post_meta($event_id, 'psource_event_start');
    $valid_starts = array();
    foreach ((array)$starts as $start_value) {
        $start_ts = cpc_events_parse_eab_start_ts($start_value);
        if ($start_ts > 0) {
            $valid_starts[] = $start_ts;
        }
    }

    sort($valid_starts);
    $start_ts = !empty($valid_starts) ? (int)$valid_starts[0] : 0;

    $durations = get_post_meta($event_id, 'psource_event_duration');
    $duration_seconds = 0;
    if (!empty($durations)) {
        $duration_seconds = (int)max(array_map('intval', (array)$durations));
    }

    $end_ts = 0;
    if ($start_ts && $duration_seconds > 0) {
        $end_ts = $start_ts + $duration_seconds;
    }

    return array($start_ts, $end_ts);
}

function cpc_events_render_eab_cards($atts) {
    $upcoming = isset($atts['upcoming']) ? (int)$atts['upcoming'] : 1;
    $limit = isset($atts['limit']) ? max(1, min(100, (int)$atts['limit'])) : 12;
    $fetch_limit = max($limit * 4, 40);
    $now = current_time('timestamp');

    $query = new WP_Query(array(
        'post_type' => 'psource_event',
        'post_status' => 'publish',
        'posts_per_page' => $fetch_limit,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    ));

    if (empty($query->posts)) {
        return '<div class="cpc-events-empty">' . esc_html__('Keine Events gefunden.', CPC2_TEXT_DOMAIN) . '</div>';
    }

    $events = array();
    foreach ($query->posts as $event_post) {
        list($start_ts, $end_ts) = cpc_events_get_eab_event_times($event_post->ID);
        $effective_end = $end_ts ? $end_ts : $start_ts;

        if ($upcoming && $effective_end && $effective_end < $now) {
            continue;
        }

        $events[] = array(
            'post' => $event_post,
            'start_ts' => $start_ts,
            'end_ts' => $end_ts,
            'sort_ts' => $start_ts ? $start_ts : 0,
        );
    }

    if (empty($events)) {
        return '<div class="cpc-events-empty">' . esc_html__('Keine Events gefunden.', CPC2_TEXT_DOMAIN) . '</div>';
    }

    usort($events, function ($a, $b) use ($upcoming) {
        if ($a['sort_ts'] === $b['sort_ts']) {
            return 0;
        }
        if ($upcoming) {
            return ($a['sort_ts'] < $b['sort_ts']) ? -1 : 1;
        }
        return ($a['sort_ts'] > $b['sort_ts']) ? -1 : 1;
    });

    $events = array_slice($events, 0, $limit);
    $html = '<div class="cpc-events-list cpc-events-external-list">';

    foreach ($events as $event_data) {
        $event = $event_data['post'];
        $start_ts = (int)$event_data['start_ts'];
        $end_ts = (int)$event_data['end_ts'];
        $location = get_post_meta($event->ID, 'psource_event_venue', true);

        $html .= '<article class="cpc-event-card cpc-event-card-external">';
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

function cpc_events_should_use_external() {
    $mode = cpc_events_provider_mode();
    if ($mode === 'internal') {
        return false;
    }
    if ($mode === 'external') {
        return true;
    }
    return cpc_events_external_is_available();
}

function cpc_events_should_show_internal_admin_menu() {
    return !cpc_events_should_use_external();
}

function cpc_events_external_post_type() {
    return post_type_exists('psource_event') ? 'psource_event' : '';
}

function cpc_events_bridge_is_enabled() {
    if (cpc_events_provider_mode() === 'internal') {
        return false;
    }
    if (!cpc_events_external_is_available()) {
        return false;
    }

    return cpc_events_external_post_type() !== '';
}

function cpc_events_bridge_lock($set = null) {
    global $cpc_events_bridge_locked;
    if (!isset($cpc_events_bridge_locked)) {
        $cpc_events_bridge_locked = false;
    }

    if ($set !== null) {
        $cpc_events_bridge_locked = (bool)$set;
    }

    return (bool)$cpc_events_bridge_locked;
}

function cpc_events_sync_to_external($cpc_event_id) {
    $cpc_event_id = (int)$cpc_event_id;
    if ($cpc_event_id <= 0 || !cpc_events_bridge_is_enabled() || cpc_events_bridge_lock()) {
        return 0;
    }

    $event = get_post($cpc_event_id);
    if (!$event || $event->post_type !== 'cpc_event') {
        return 0;
    }

    $external_type = cpc_events_external_post_type();
    if ($external_type === '') {
        return 0;
    }

    $external_id = (int)get_post_meta($cpc_event_id, '_cpc_event_external_id', true);
    if ($external_id && get_post_type($external_id) !== $external_type) {
        $external_id = 0;
    }

    $payload = array(
        'post_type' => $external_type,
        'post_title' => $event->post_title,
        'post_content' => $event->post_content,
        'post_excerpt' => $event->post_excerpt,
        'post_author' => (int)$event->post_author,
        'post_status' => $event->post_status,
    );
    if ($external_id > 0) {
        $payload['ID'] = $external_id;
    }

    cpc_events_bridge_lock(true);
    $saved_external_id = wp_insert_post($payload, true);
    cpc_events_bridge_lock(false);

    if (is_wp_error($saved_external_id) || !$saved_external_id) {
        return 0;
    }

    $saved_external_id = (int)$saved_external_id;
    update_post_meta($cpc_event_id, '_cpc_event_external_id', $saved_external_id);
    update_post_meta($saved_external_id, '_cpc_source_event_id', $cpc_event_id);

    $start_ts = (int)get_post_meta($cpc_event_id, 'cpc_event_start_ts', true);
    if ($start_ts <= 0) {
        $start_raw = (string)get_post_meta($cpc_event_id, 'cpc_event_start', true);
        $start_ts = $start_raw ? (int)strtotime($start_raw) : 0;
    }

    $end_ts = (int)get_post_meta($cpc_event_id, 'cpc_event_end_ts', true);
    $duration = ($start_ts > 0 && $end_ts > $start_ts) ? (int)($end_ts - $start_ts) : 0;
    $location = (string)get_post_meta($cpc_event_id, 'cpc_event_location', true);
    $group_id = (int)get_post_meta($cpc_event_id, 'cpc_event_group_id', true);

    if ($start_ts > 0) {
        update_post_meta($saved_external_id, 'psource_event_start', $start_ts);
    }
    update_post_meta($saved_external_id, 'psource_event_duration', $duration);
    update_post_meta($saved_external_id, 'psource_event_venue', $location);
    if ($group_id > 0) {
        update_post_meta($saved_external_id, 'cpc_event_group_id', $group_id);
    } else {
        delete_post_meta($saved_external_id, 'cpc_event_group_id');
    }

    return $saved_external_id;
}

function cpc_events_resync_all_to_external($limit = 0) {
    $args = array(
        'post_type' => 'cpc_event',
        'post_status' => array('publish', 'pending', 'draft', 'future', 'private'),
        'posts_per_page' => $limit > 0 ? (int)$limit : -1,
        'fields' => 'ids',
        'no_found_rows' => true,
    );

    $ids = get_posts($args);
    $result = array(
        'total' => 0,
        'ok' => 0,
        'failed' => 0,
    );

    foreach ((array)$ids as $event_id) {
        $result['total']++;
        $external_id = cpc_events_sync_to_external((int)$event_id);
        if ($external_id > 0) {
            $result['ok']++;
        } else {
            $result['failed']++;
        }
    }

    return $result;
}

function cpc_events_bridge_sync_on_save($post_id, $post, $update) {
    if (cpc_events_bridge_lock()) {
        return;
    }
    if (wp_is_post_revision($post_id) || wp_is_post_autosave($post_id)) {
        return;
    }
    if (!$post || $post->post_type !== 'cpc_event') {
        return;
    }
    cpc_events_sync_to_external((int)$post_id);
}
add_action('save_post_cpc_event', 'cpc_events_bridge_sync_on_save', 30, 3);

function cpc_events_bridge_trash_external($post_id) {
    $post_id = (int)$post_id;
    if ($post_id <= 0 || cpc_events_bridge_lock() || !cpc_events_bridge_is_enabled()) {
        return;
    }
    if (get_post_type($post_id) !== 'cpc_event') {
        return;
    }

    $external_id = (int)get_post_meta($post_id, '_cpc_event_external_id', true);
    if ($external_id > 0 && get_post_type($external_id) === cpc_events_external_post_type()) {
        cpc_events_bridge_lock(true);
        wp_trash_post($external_id);
        cpc_events_bridge_lock(false);
    }
}
add_action('trash_post', 'cpc_events_bridge_trash_external', 20);

function cpc_events_bridge_untrash_external($post_id) {
    $post_id = (int)$post_id;
    if ($post_id <= 0 || cpc_events_bridge_lock() || !cpc_events_bridge_is_enabled()) {
        return;
    }
    if (get_post_type($post_id) !== 'cpc_event') {
        return;
    }

    $external_id = (int)get_post_meta($post_id, '_cpc_event_external_id', true);
    if ($external_id > 0 && get_post_type($external_id) === cpc_events_external_post_type()) {
        cpc_events_bridge_lock(true);
        wp_untrash_post($external_id);
        wp_update_post(array(
            'ID' => $external_id,
            'post_status' => get_post_status($post_id),
        ));
        cpc_events_bridge_lock(false);
    }
}
add_action('untrash_post', 'cpc_events_bridge_untrash_external', 20);

function cpc_events_bridge_delete_external($post_id) {
    $post_id = (int)$post_id;
    if ($post_id <= 0 || cpc_events_bridge_lock() || !cpc_events_bridge_is_enabled()) {
        return;
    }
    if (get_post_type($post_id) !== 'cpc_event') {
        return;
    }

    $external_id = (int)get_post_meta($post_id, '_cpc_event_external_id', true);
    if ($external_id > 0 && get_post_type($external_id) === cpc_events_external_post_type()) {
        cpc_events_bridge_lock(true);
        wp_delete_post($external_id, true);
        cpc_events_bridge_lock(false);
    }
}
add_action('before_delete_post', 'cpc_events_bridge_delete_external', 20);

function cpc_events_render_external($atts) {
    $tag = cpc_events_get_eab_shortcode_tag();
    if ($tag !== '') {
        $shortcode = '[' . $tag;
        if (isset($atts['limit'])) {
            $shortcode .= ' limit="' . (int)$atts['limit'] . '"';
        }
        if (isset($atts['upcoming']) && (int)$atts['upcoming'] === 0 && ($tag === 'eab_archive' || $tag === 'eab_calendar')) {
            $shortcode .= ' show_old="1"';
        }
        $shortcode .= ']';

        $rendered = do_shortcode($shortcode);
        if (!empty($rendered)) {
            return $rendered;
        }
    }

    if (post_type_exists('psource_event')) {
        return cpc_events_render_eab_cards($atts);
    }

    return '<div class="cpc-events-external-missing">' . esc_html__('PS Events ist aktiv, aber es wurde kein kompatibler Output gefunden.', CPC2_TEXT_DOMAIN) . '</div>';
}

function cpc_events_register_post_type() {
    $labels = array(
        'name' => __('Events', CPC2_TEXT_DOMAIN),
        'singular_name' => __('Event', CPC2_TEXT_DOMAIN),
        'add_new' => __('Event hinzufügen', CPC2_TEXT_DOMAIN),
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
        'show_ui' => cpc_events_should_show_internal_admin_menu(),
        'show_in_menu' => cpc_events_should_show_internal_admin_menu(),
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => array('title', 'editor', 'author'),
        'has_archive' => false,
        'rewrite' => array('slug' => 'cpc-event'),
    ));
}
add_action('init', 'cpc_events_register_post_type');

if (is_admin()) {
    add_action('admin_menu', 'cpc_events_hide_internal_menu_when_external', 999);
}

function cpc_events_hide_internal_menu_when_external() {
    if (cpc_events_should_show_internal_admin_menu()) {
        return;
    }
    remove_menu_page('edit.php?post_type=cpc_event');
}

function cpc_events_add_meta_boxes() {
    add_meta_box('cpc_event_details', __('Event-Details', CPC2_TEXT_DOMAIN), 'cpc_events_meta_box_html', 'cpc_event', 'normal', 'high');
}
add_action('add_meta_boxes', 'cpc_events_add_meta_boxes');

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
    if (get_post_type($post_id) !== 'cpc_event') {
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
add_action('save_post', 'cpc_events_save_meta');

function cpc_events_render_internal($atts) {
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
    // Usage: [cpc-events]
    // Main attrs: limit, upcoming
    $values = function_exists('cpc_get_shortcode_options') ? cpc_get_shortcode_options('cpc_events') : array();
    $atts = shortcode_atts(array(
        'limit' => cpc_get_shortcode_value($values, 'cpc_events-limit', 12),
        'upcoming' => cpc_get_shortcode_value($values, 'cpc_events-upcoming', 1),
        'styles' => true,
        'before' => '',
        'after' => '',
    ), $atts, 'cpc_events');

    $html = '';
    if (cpc_events_should_use_external()) {
        $html = cpc_events_render_external($atts);
    } else {
        $html = cpc_events_render_internal($atts);
    }

    if ($html && function_exists('cpc_wrap_shortcode_styles')) {
        $html = apply_filters('cpc_wrap_shortcode_styles_filter', $html, 'cpc_events', $atts['before'], $atts['after'], $atts['styles'], $values);
    }

    return $html;
}

if (!is_admin()) {
    add_shortcode(CPC_PREFIX . '-events', 'cpc_events_shortcode');
}

/* ── Helfer ───────────────────────────────────────────────────────────────── */

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

/* ── Admin-Integration: Einstellungen-Seite ─────────────────────────────── */

if (is_admin()) {
    add_action('cpc_admin_getting_started_hook', 'cpc_admin_events_section', 20);
    add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_events_settings_save', 20, 1);
}

function cpc_admin_events_section() {
    if (!cpc_events_is_core_enabled()) {
        return;
    }

    $expand_id = 'cpc_admin_getting_started_events';
    $is_open   = (isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === $expand_id)
              || (isset($_GET['cpc_expand'])  && $_GET['cpc_expand']  === $expand_id);
    $css     = $is_open ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';
    $display = $is_open ? 'block' : 'none';

    echo '<div class="' . $css . 'cpc_admin_getting_started_menu_item" id="' . $expand_id . '_div" rel="' . $expand_id . '">' . esc_html__('Events', CPC2_TEXT_DOMAIN) . '</div>';
    echo '<div class="cpc_admin_getting_started_content" id="' . $expand_id . '" style="display:' . $display . '">';
    echo '<table class="form-table">';

    echo '<tr class="form-field">';
    echo '<th scope="row" valign="top"><label>' . esc_html__('Events-Seite', CPC2_TEXT_DOMAIN) . '</label></th>';
    echo '<td>';
    echo wp_dropdown_pages(array(
        'name'              => 'cpc_events_directory_page',
        'echo'              => 0,
        'show_option_none'  => esc_html__('Keine feste Seite', CPC2_TEXT_DOMAIN),
        'option_none_value' => '0',
        'selected'          => cpc_events_get_directory_page_id(),
    ));
    echo '<br /><span class="description">' . esc_html__('Wähle eine bestehende Seite mit [cpc-events].', CPC2_TEXT_DOMAIN) . '</span>';
    echo '<br /><label><input type="checkbox" style="width:10px" name="cpc_events_create_page" /> ' . esc_html__('Events-Seite neu erstellen (fügt [cpc-events] ein).', CPC2_TEXT_DOMAIN) . '</label>';
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
    echo '<th>' . esc_html__('Direkt veröffentlichen', CPC2_TEXT_DOMAIN) . '</th>';
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
    echo '<span class="description">' . esc_html__('Wenn „Direkt veröffentlichen“ nicht gesetzt ist, wird das Event als „Ausstehend“ gespeichert.', CPC2_TEXT_DOMAIN) . '</span>';
    echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<th scope="row" valign="top"><label>' . esc_html__('Kalender-Kontexte', CPC2_TEXT_DOMAIN) . '</label></th>';
    echo '<td>';
    echo '<label><input type="checkbox" style="width:10px" name="cpc_events_allow_user_calendar" value="1"' . checked(cpc_events_allow_user_calendar(), true, false) . ' /> ' . esc_html__('Benutzer-Kalender im Profil aktivieren', CPC2_TEXT_DOMAIN) . '</label>';
    echo '<br /><label><input type="checkbox" style="width:10px" name="cpc_events_allow_group_calendar" value="1"' . checked(cpc_events_allow_group_calendar(), true, false) . ' /> ' . esc_html__('Gruppen-Kalender aktivieren', CPC2_TEXT_DOMAIN) . '</label>';
    echo '<br /><span class="description">' . esc_html__('Gruppen-Kalender benötigt zusätzlich den Gruppen-Schalter im jeweiligen Gruppen-Settings-Tab.', CPC2_TEXT_DOMAIN) . '</span>';
    echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<th scope="row" valign="top"><label>' . esc_html__('Synchronisierung', CPC2_TEXT_DOMAIN) . '</label></th>';
    echo '<td>';
    echo '<button type="submit" name="cpc_events_resync_now" value="1" class="button">' . esc_html__('Bestand jetzt synchronisieren', CPC2_TEXT_DOMAIN) . '</button>';
    echo '<br /><span class="description">' . esc_html__('Synchronisiert alle bestehenden Community-Events in den externen Event-Provider (psource_event).', CPC2_TEXT_DOMAIN) . '</span>';
    if (!empty($GLOBALS['cpc_events_resync_result']) && is_array($GLOBALS['cpc_events_resync_result'])) {
        $sync_result = $GLOBALS['cpc_events_resync_result'];
        echo '<div class="cpc_success" style="margin-top:8px;">'
            . sprintf(
                esc_html__('Sync abgeschlossen: %1$d gesamt, %2$d erfolgreich, %3$d fehlgeschlagen.', CPC2_TEXT_DOMAIN),
                (int)$sync_result['total'],
                (int)$sync_result['ok'],
                (int)$sync_result['failed']
            )
            . '</div>';
    }
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

    if (isset($the_post['cpc_events_allow_user_calendar'])) {
        update_option('cpc_events_allow_user_calendar', 1);
    } else {
        update_option('cpc_events_allow_user_calendar', 0);
    }

    if (isset($the_post['cpc_events_allow_group_calendar'])) {
        update_option('cpc_events_allow_group_calendar', 1);
    } else {
        update_option('cpc_events_allow_group_calendar', 0);
    }

    if (isset($the_post['cpc_events_create_page']) && function_exists('cpc_admin_create_standard_page')) {
        $new_id = cpc_admin_create_standard_page('cpc_events_directory_page', array(
            'post_name'      => sanitize_title(__('events', CPC2_TEXT_DOMAIN)),
            'post_title'     => __('Events', CPC2_TEXT_DOMAIN),
            'post_content'   => '[cpc-events]',
            'post_status'    => 'publish',
            'post_type'      => 'page',
            'comment_status' => 'closed',
        ), 'cpc-events');
        if (!is_wp_error($new_id) && $new_id) {
            update_option('cpc_events_directory_page', (int)$new_id);
        }
    }

    if (isset($the_post['cpc_events_resync_now']) && current_user_can('manage_options')) {
        if (function_exists('set_time_limit')) {
            @set_time_limit(300);
        }
        $GLOBALS['cpc_events_resync_result'] = cpc_events_resync_all_to_external();
    }
}
