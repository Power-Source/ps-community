<?php

function cpc_forum_profile_tab_settings() {
    return array(
        'enabled' => (bool)cpc_forum_get_setting('profile_tab_enabled', 1),
        'label' => (string)cpc_forum_get_setting('profile_tab_label', __('Forum', 'cp-community')),
        'priority' => max(1, absint(cpc_forum_get_setting('profile_tab_priority', 28))),
        'items' => min(20, max(3, absint(cpc_forum_get_setting('profile_tab_items', 6)))),
    );
}

function cpc_forum_add_profile_tab($tabs, $user_id, $viewer_id) {
    $settings = cpc_forum_profile_tab_settings();
    if (!$settings['enabled']) {
        return $tabs;
    }

    $tabs['forum'] = array(
        'label' => $settings['label'] !== '' ? $settings['label'] : __('Forum', 'cp-community'),
        'icon' => 'format-chat',
        'priority' => $settings['priority'],
    );

    return $tabs;
}
add_filter('cpc_profile_tabs', 'cpc_forum_add_profile_tab', 20, 3);

function cpc_forum_profile_can_view_comment($comment, $viewer_id) {
    if (!$comment || !empty(get_comment_meta($comment->comment_ID, 'cpc_private_post', true))) {
        if (!$comment) {
            return false;
        }
        $topic = get_post($comment->comment_post_ID);
        return $topic && ((int)$topic->post_author === (int)$viewer_id || (int)$comment->user_id === (int)$viewer_id || current_user_can('manage_options'));
    }

    return true;
}

function cpc_forum_profile_topic_url($post_id) {
    $url = get_permalink((int)$post_id);
    return $url ? $url : home_url('/');
}

function cpc_forum_profile_render_topic_item($topic) {
    $term = cpc_forum_get_post_term($topic->ID);
    $forum_name = $term ? $term->name : '';
    $reply_count = (int)get_comments(array(
        'post_id' => $topic->ID,
        'status' => 'approve',
        'type' => 'cpc_forum_comment',
        'parent' => 0,
        'count' => true,
    ));

    $html = '<li class="cpc_forum_profile_item cpc_forum_profile_topic">';
    $html .= '<a class="cpc_forum_profile_title" href="'.esc_url(cpc_forum_profile_topic_url($topic->ID)).'">'.esc_html($topic->post_title).'</a>';
    $html .= '<div class="cpc_forum_profile_meta">';
    if ($forum_name !== '') {
        $html .= '<span>'.esc_html($forum_name).'</span> ';
    }
    $html .= '<span>'.sprintf(esc_html(_n('%d Antwort', '%d Antworten', $reply_count, 'cp-community')), $reply_count).'</span>';
    $html .= '<span>'.sprintf(esc_html__('vor %s', 'cp-community'), esc_html(human_time_diff(strtotime($topic->post_date_gmt), current_time('timestamp', true)))).'</span>';
    $html .= '</div></li>';

    return $html;
}

function cpc_forum_profile_render_reply_item($reply) {
    $topic = get_post($reply->comment_post_ID);
    if (!$topic) {
        return '';
    }

    $term = cpc_forum_get_post_term($topic->ID);
    $forum_name = $term ? $term->name : '';
    $accepted = (bool)get_comment_meta($reply->comment_ID, 'cpc_forum_answer', true);

    $html = '<li class="cpc_forum_profile_item cpc_forum_profile_reply">';
    $html .= '<a class="cpc_forum_profile_title" href="'.esc_url(cpc_forum_profile_topic_url($topic->ID).'#comment-'.$reply->comment_ID).'">'.esc_html($topic->post_title).'</a>';
    $html .= '<div class="cpc_forum_profile_excerpt">'.esc_html(wp_trim_words(wp_strip_all_tags($reply->comment_content), 18)).'</div>';
    $html .= '<div class="cpc_forum_profile_meta">';
    if ($forum_name !== '') {
        $html .= '<span>'.esc_html($forum_name).'</span> ';
    }
    if ($accepted) {
        $html .= '<span class="cpc_forum_profile_accepted">'.esc_html__('Akzeptierte Antwort', 'cp-community').'</span>';
    }
    $html .= '<span>'.sprintf(esc_html__('vor %s', 'cp-community'), esc_html(human_time_diff(strtotime($reply->comment_date_gmt), current_time('timestamp', true)))).'</span>';
    $html .= '</div></li>';

    return $html;
}

function cpc_forum_profile_render_section($title, $items, $empty_message) {
    $html = '<section class="cpc_forum_profile_section">';
    $html .= '<h3>'.esc_html($title).'</h3>';
    if (!$items) {
        $html .= '<p class="cpc_forum_profile_empty">'.esc_html($empty_message).'</p>';
    } else {
        $html .= '<ul class="cpc_forum_profile_list">'.implode('', $items).'</ul>';
    }
    $html .= '</section>';

    return $html;
}

function cpc_forum_profile_render_stats($user_id) {
    global $wpdb;

    $topics = count_user_posts((int)$user_id, 'cpc_forum_post', true);
    $replies = (int)get_comments(array(
        'user_id' => (int)$user_id,
        'status' => 'approve',
        'type' => 'cpc_forum_comment',
        'count' => true,
    ));
    $accepted = (int)$wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM {$wpdb->comments} comments INNER JOIN {$wpdb->commentmeta} meta ON comments.comment_ID = meta.comment_id WHERE comments.user_id = %d AND comments.comment_approved = '1' AND comments.comment_type = 'cpc_forum_comment' AND meta.meta_key = 'cpc_forum_answer' AND meta.meta_value = '1'",
        (int)$user_id
    ));

    return '<div class="cpc_forum_profile_stats">'
        .'<span><strong>'.absint($topics).'</strong> '.esc_html__('Themen', 'cp-community').'</span>'
        .'<span><strong>'.absint($replies).'</strong> '.esc_html__('Antworten', 'cp-community').'</span>'
        .'<span><strong>'.absint($accepted).'</strong> '.esc_html__('Akzeptierte Antworten', 'cp-community').'</span>'
        .'</div>';
}

function cpc_forum_render_profile_tab_content($html, $active_tab, $user_id, $shortcode_atts) {
    if ($active_tab !== 'forum') {
        return $html;
    }

    $user_id = absint($user_id);
    if (!$user_id) {
        return '<p>'.esc_html__('Benutzer nicht gefunden.', 'cp-community').'</p>';
    }

    cpc_forum_init();
    $settings = cpc_forum_profile_tab_settings();
    $viewer_id = get_current_user_id();
    $limit = $settings['items'];

    $topics = array();
    $topic_query = new WP_Query(array(
        'post_type' => 'cpc_forum_post',
        'post_status' => 'publish',
        'author' => $user_id,
        'posts_per_page' => $limit * 4,
        'orderby' => 'date',
        'order' => 'DESC',
    ));
    foreach ($topic_query->posts as $topic) {
        if (user_can_see_post($viewer_id, $topic->ID)) {
            $topics[] = cpc_forum_profile_render_topic_item($topic);
            if (count($topics) >= $limit) {
                break;
            }
        }
    }
    wp_reset_postdata();

    $replies = array();
    $reply_query = get_comments(array(
        'user_id' => $user_id,
        'status' => 'approve',
        'type' => 'cpc_forum_comment',
        'number' => $limit * 6,
        'orderby' => 'comment_date_gmt',
        'order' => 'DESC',
    ));
    foreach ($reply_query as $reply) {
        $topic = get_post($reply->comment_post_ID);
        if (!$topic || $topic->post_type !== 'cpc_forum_post' || $topic->post_status !== 'publish') {
            continue;
        }
        if (!user_can_see_post($viewer_id, $topic->ID) || !cpc_forum_profile_can_view_comment($reply, $viewer_id)) {
            continue;
        }
        $replies[] = cpc_forum_profile_render_reply_item($reply);
        if (count($replies) >= $limit) {
            break;
        }
    }

    $html = '<div class="cpc_forum_profile_tab">';
    $html .= '<div class="cpc_forum_profile_header"><h2>'.esc_html($settings['label']).'</h2>';
    if ((int)$viewer_id === $user_id || current_user_can('manage_options')) {
        $html .= cpc_forum_profile_render_stats($user_id);
    }
    $html .= '</div>';
    $html .= cpc_forum_profile_render_section(__('Themen', 'cp-community'), $topics, __('Noch keine sichtbaren Themen vorhanden.', 'cp-community'));
    $html .= cpc_forum_profile_render_section(__('Letzte Antworten', 'cp-community'), $replies, __('Noch keine sichtbaren Antworten vorhanden.', 'cp-community'));
    $html .= '</div>';

    return $html;
}
add_filter('cpc_profile_tab_content', 'cpc_forum_render_profile_tab_content', 20, 4);
