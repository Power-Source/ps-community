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
        'eab_single',
        'eab_expired',
        'eab_events_map',
        'eab_my_events',
    ));

    $preferred = get_option('cpc_events_external_shortcode', 'auto');
    if (is_string($preferred) && $preferred !== 'auto' && in_array($preferred, $shortcodes, true)) {
        array_unshift($shortcodes, $preferred);
        $shortcodes = array_values(array_unique($shortcodes));
    }

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
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_icon' => 'dashicons-calendar-alt',
        'supports' => array('title', 'editor', 'author'),
        'has_archive' => false,
        'rewrite' => array('slug' => 'cpc-event'),
    ));
}
add_action('init', 'cpc_events_register_post_type');

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
