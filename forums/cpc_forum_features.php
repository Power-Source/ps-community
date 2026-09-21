<?php

function cpc_forum_get_setting($key, $default = '') {
    $value = get_option('cpc_forum_'.$key, null);
    return $value === null ? $default : $value;
}

function cpc_forum_get_term_setting($term_id, $key, $default = '') {
    $value = cpc_get_term_meta((int)$term_id, 'cpc_forum_'.$key, true);
    return $value === '' ? cpc_forum_get_setting($key, $default) : $value;
}

function cpc_forum_get_post_term($post_id) {
    $terms = get_the_terms((int)$post_id, 'cpc_forum');
    return is_array($terms) && !empty($terms) ? reset($terms) : false;
}

function cpc_forum_get_moderator_ids($term_id) {
    $moderators = (string)cpc_get_term_meta((int)$term_id, 'cpc_forum_moderators', true);
    return array_filter(array_map('absint', preg_split('/[\s,]+/', $moderators)));
}

function cpc_forum_user_can_moderate($user_id, $term_id) {
    if (user_can((int)$user_id, 'manage_options')) {
        return true;
    }

    return in_array((int)$user_id, cpc_forum_get_moderator_ids($term_id), true);
}

function cpc_forum_is_qa_mode($term_id) {
    return cpc_forum_get_term_setting($term_id, 'mode', 'discussion') === 'qa';
}

function cpc_forum_should_moderate($term_id, $content_type, $user_id) {
    if (cpc_forum_user_can_moderate($user_id, $term_id)) {
        return false;
    }

    if (cpc_forum_get_term_setting($term_id, 'moderate_'.$content_type, 0)) {
        return true;
    }

    if (cpc_forum_get_setting('moderate_first_posts', 0)) {
        $count = count_user_posts((int)$user_id, 'cpc_forum_post', true);
        return $content_type === 'topic' ? $count === 0 : $count <= 1;
    }

    return false;
}

function cpc_forum_submission_error($term_id, $user_id, $content) {
    $content = trim(wp_strip_all_tags((string)$content));
    $minimum_length = absint(cpc_forum_get_term_setting($term_id, 'minimum_length', 0));
    if ($minimum_length && strlen($content) < $minimum_length) {
        return sprintf(__('Dein Beitrag muss mindestens %d Zeichen enthalten.', 'cp-community'), $minimum_length);
    }

    $interval = absint(cpc_forum_get_term_setting($term_id, 'flood_interval', 0));
    if ($interval && !cpc_forum_user_can_moderate($user_id, $term_id)) {
        $last_submission = absint(get_user_meta((int)$user_id, 'cpc_forum_last_submission', true));
        if ($last_submission && (time() - $last_submission) < $interval) {
            return sprintf(__('Bitte warte %d Sekunden, bevor Du erneut schreibst.', 'cp-community'), $interval - (time() - $last_submission));
        }
    }

    return '';
}

function cpc_forum_record_submission($user_id) {
    update_user_meta((int)$user_id, 'cpc_forum_last_submission', time());
}

function cpc_forum_user_notification_frequency($user_id) {
    $frequency = get_user_meta((int)$user_id, 'cpc_forum_notification_frequency', true);
    return in_array($frequency, array('instant', 'daily', 'off'), true) ? $frequency : 'instant';
}

function cpc_forum_send_notification($user_id, $subject, $message) {
    if (!cpc_forum_get_setting('notifications_enabled', 1)) {
        return;
    }

    if (cpc_forum_user_notification_frequency($user_id) !== 'instant') {
        return;
    }

    $user = get_userdata((int)$user_id);
    if ($user && is_email($user->user_email)) {
        wp_mail($user->user_email, $subject, $message);
    }
}

function cpc_forum_notify_moderators($term_id, $subject, $message, $exclude_user_id = 0) {
    foreach (cpc_forum_get_moderator_ids($term_id) as $user_id) {
        if ((int)$user_id !== (int)$exclude_user_id) {
            cpc_forum_send_notification($user_id, $subject, $message);
        }
    }
}

function cpc_forum_notify_topic_author($post_id, $subject, $message, $exclude_user_id = 0) {
    $post = get_post((int)$post_id);
    if ($post && (int)$post->post_author !== (int)$exclude_user_id) {
        cpc_forum_send_notification($post->post_author, $subject, $message);
    }
}