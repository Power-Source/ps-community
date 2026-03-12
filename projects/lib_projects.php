<?php

function cpc_projects_is_enabled() {
    return (bool)get_option('cpc_projects_module_enabled', 1);
}

function cpc_projects_show_profile_create_form() {
    return (bool)get_option('cpc_projects_profile_allow_create', 0);
}

function cpc_projects_alerts_enabled() {
    return (bool)get_option('cpc_projects_alerts_enabled', 1);
}

function cpc_projects_activity_enabled() {
    return (bool)get_option('cpc_projects_activity_enabled', 1);
}

function cpc_projects_group_alert_scope() {
    $scope = sanitize_key((string)get_option('cpc_projects_group_alert_scope', 'moderators'));
    if (!in_array($scope, array('moderators', 'all_members'), true)) {
        $scope = 'moderators';
    }
    return $scope;
}

function cpc_projects_task_comment_attachments_enabled() {
    return (bool)get_option('cpc_projects_comment_attachments_enabled', 1);
}

function cpc_projects_task_comment_max_attachment_mb() {
    $size = (int)get_option('cpc_projects_comment_max_attachment_mb', 10);
    if ($size < 1) {
        $size = 1;
    }
    if ($size > 50) {
        $size = 50;
    }
    return $size;
}

function cpc_projects_comment_allowed_extensions() {
    $raw = (string)get_option('cpc_projects_comment_allowed_exts', 'jpg,jpeg,png,gif,webp,pdf,zip,txt,doc,docx,xls,xlsx,csv,mp3,mp4');
    $parts = array_map('trim', explode(',', strtolower($raw)));
    $parts = array_values(array_unique(array_filter($parts, function($ext) {
        return (bool)preg_match('/^[a-z0-9]+$/', $ext);
    })));

    if (empty($parts)) {
        $parts = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'zip', 'txt');
    }

    return $parts;
}

function cpc_projects_comment_attachment_type_limit_mb($type) {
    $type = sanitize_key((string)$type);
    $defaults = array(
        'image' => 10,
        'video' => 40,
        'audio' => 20,
        'document' => 15,
    );

    $option_key = 'cpc_projects_comment_max_attachment_mb_'.$type;
    $value = (int)get_option($option_key, isset($defaults[$type]) ? $defaults[$type] : 10);
    if ($value < 1) {
        $value = 1;
    }
    if ($value > 200) {
        $value = 200;
    }

    return $value;
}

function cpc_projects_get_attachment_bucket_for_mime($mime_type) {
    $mime_type = (string)$mime_type;
    if (strpos($mime_type, 'image/') === 0) {
        return 'image';
    }
    if (strpos($mime_type, 'video/') === 0) {
        return 'video';
    }
    if (strpos($mime_type, 'audio/') === 0) {
        return 'audio';
    }
    return 'document';
}

function cpc_projects_db_version() {
    return '1.1.0';
}

function cpc_projects_get_tasks_table_name() {
    global $wpdb;
    return $wpdb->prefix.'cpc_project_tasks';
}

function cpc_projects_maybe_install() {
    if (!cpc_projects_is_enabled()) {
        return;
    }

    if (get_option('cpc_projects_db_version') === cpc_projects_db_version()) {
        return;
    }

    cpc_projects_install_tables();
}

function cpc_projects_install_tables() {
    global $wpdb;

    $table = cpc_projects_get_tasks_table_name();
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        project_id BIGINT(20) UNSIGNED NOT NULL,
        user_id BIGINT(20) UNSIGNED NOT NULL,
        title VARCHAR(255) NOT NULL,
        description LONGTEXT NULL,
        status VARCHAR(20) NOT NULL DEFAULT 'open',
        priority TINYINT(2) NOT NULL DEFAULT 1,
        deadline DATETIME NULL,
        assigned_user_ids TEXT NULL,
        completed_by BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        date_added DATETIME NOT NULL,
        date_updated DATETIME NOT NULL,
        PRIMARY KEY  (id),
        KEY project_id (project_id),
        KEY user_id (user_id),
        KEY status (status)
    ) {$charset_collate};";

    require_once ABSPATH.'wp-admin/includes/upgrade.php';
    dbDelta($sql);

    update_option('cpc_projects_db_version', cpc_projects_db_version());
}

function cpc_projects_get_user_tab_name() {
    $label = trim((string)get_option('cpc_projects_user_tab_name', 'Projekte'));
    return $label !== '' ? $label : __('Projekte', CPC2_TEXT_DOMAIN);
}

function cpc_projects_get_group_tab_name() {
    $label = trim((string)get_option('cpc_projects_group_tab_name', 'Projekte'));
    return $label !== '' ? $label : __('Projekte', CPC2_TEXT_DOMAIN);
}

function cpc_projects_get_component($project_id) {
    $component = get_post_meta((int)$project_id, 'cpc_project_component', true);
    return $component ? $component : 'members';
}

function cpc_projects_get_component_id($project_id) {
    return (int)get_post_meta((int)$project_id, 'cpc_project_component_id', true);
}

function cpc_projects_get_status($project_id) {
    $status = get_post_meta((int)$project_id, 'cpc_project_status', true);
    return $status ? $status : 'public';
}

function cpc_projects_user_can_create_for_context($component, $component_id, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    if (current_user_can('manage_options')) {
        return true;
    }

    $component_id = (int)$component_id;

    if ($component === 'members') {
        return ((int)$user_id === $component_id);
    }

    if ($component === 'groups') {
        return function_exists('cpc_is_group_moderator') && cpc_is_group_moderator($user_id, $component_id);
    }

    return false;
}

function cpc_projects_user_can_view_project($project_id, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $project = get_post((int)$project_id);
    if (!$project || $project->post_type !== 'cpc_project') {
        return false;
    }

    if (current_user_can('manage_options')) {
        return true;
    }

    $status = cpc_projects_get_status($project_id);
    $component = cpc_projects_get_component($project_id);
    $component_id = cpc_projects_get_component_id($project_id);

    if ($component === 'groups' && function_exists('cpc_can_view_group')) {
        if (!cpc_can_view_group($user_id, $component_id)) {
            return false;
        }
    }

    if ($status === 'public') {
        return true;
    }

    if ($status === 'members') {
        return is_user_logged_in();
    }

    if ($status === 'private') {
        if ($project->post_author == $user_id) {
            return true;
        }

        if ($component === 'members') {
            return ((int)$user_id === $component_id);
        }

        if ($component === 'groups' && function_exists('cpc_is_group_moderator')) {
            return cpc_is_group_moderator($user_id, $component_id);
        }

        return false;
    }

    return true;
}

function cpc_projects_user_can_manage_project($project_id, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $project = get_post((int)$project_id);
    if (!$project || $project->post_type !== 'cpc_project') {
        return false;
    }

    if (current_user_can('manage_options')) {
        return true;
    }

    if ((int)$project->post_author === (int)$user_id) {
        return true;
    }

    $component = cpc_projects_get_component($project_id);
    $component_id = cpc_projects_get_component_id($project_id);

    if ($component === 'members') {
        return ((int)$component_id === (int)$user_id);
    }

    if ($component === 'groups' && function_exists('cpc_is_group_moderator')) {
        return cpc_is_group_moderator($user_id, $component_id);
    }

    return false;
}

function cpc_projects_get_projects($args = array()) {
    $defaults = array(
        'component' => '',
        'component_id' => 0,
        'posts_per_page' => 100,
        'orderby' => 'date',
        'order' => 'DESC',
    );
    $args = wp_parse_args($args, $defaults);

    $query_args = array(
        'post_type' => 'cpc_project',
        'post_status' => 'publish',
        'posts_per_page' => (int)$args['posts_per_page'],
        'orderby' => $args['orderby'],
        'order' => $args['order'],
        'no_found_rows' => true,
        'update_post_meta_cache' => false,
        'update_post_term_cache' => false,
    );

    $meta_query = array();
    if ($args['component'] !== '') {
        $meta_query[] = array(
            'key' => 'cpc_project_component',
            'value' => sanitize_key($args['component']),
        );
    }

    if (!empty($args['component_id'])) {
        $meta_query[] = array(
            'key' => 'cpc_project_component_id',
            'value' => (int)$args['component_id'],
            'type' => 'NUMERIC',
        );
    }

    if (!empty($meta_query)) {
        $query_args['meta_query'] = $meta_query;
    }

    $posts = get_posts($query_args);
    if (!$posts) {
        return array();
    }

    return array_values(array_filter($posts, function($project) {
        return cpc_projects_user_can_view_project($project->ID);
    }));
}

function cpc_projects_get_user_group_ids($user_id) {
    $user_id = (int)$user_id;
    if ($user_id <= 0 || !function_exists('cpc_get_user_groups')) {
        return array();
    }

    $groups = cpc_get_user_groups($user_id, 'active');
    if (empty($groups)) {
        return array();
    }

    $group_ids = array();
    foreach ($groups as $group) {
        if (!empty($group->ID)) {
            $group_ids[] = (int)$group->ID;
        }
    }

    return array_values(array_unique(array_filter($group_ids)));
}

function cpc_projects_get_user_participating_project_ids($user_id) {
    global $wpdb;

    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return array();
    }

    $project_ids = array();
    $tasks_table = cpc_projects_get_tasks_table_name();

    $task_project_ids = $wpdb->get_col($wpdb->prepare(
        "SELECT DISTINCT project_id FROM {$tasks_table}
         WHERE user_id = %d OR completed_by = %d OR FIND_IN_SET(%d, assigned_user_ids)",
        $user_id,
        $user_id,
        $user_id
    ));
    if (!empty($task_project_ids)) {
        $project_ids = array_merge($project_ids, array_map('intval', $task_project_ids));
    }

    $comment_ids = get_comments(array(
        'user_id' => $user_id,
        'type' => 'cpc_project_task',
        'status' => 'approve',
        'number' => 500,
        'fields' => 'ids',
    ));
    if (!empty($comment_ids)) {
        foreach ($comment_ids as $comment_id) {
            $comment = get_comment((int)$comment_id);
            if ($comment && !empty($comment->comment_post_ID)) {
                $project_ids[] = (int)$comment->comment_post_ID;
            }
        }
    }

    return array_values(array_unique(array_filter(array_map('intval', $project_ids))));
}

function cpc_projects_get_profile_projects($profile_user_id, $args = array()) {
    $profile_user_id = (int)$profile_user_id;
    if ($profile_user_id <= 0) {
        return array();
    }

    $defaults = array(
        'posts_per_page' => 120,
        'viewer_id' => get_current_user_id(),
    );
    $args = wp_parse_args($args, $defaults);

    $candidate_ids = array();

    // Projects authored by this profile user (member and group projects).
    $authored = get_posts(array(
        'post_type' => 'cpc_project',
        'post_status' => 'publish',
        'author' => $profile_user_id,
        'posts_per_page' => 300,
        'fields' => 'ids',
        'no_found_rows' => true,
    ));
    if (!empty($authored)) {
        $candidate_ids = array_merge($candidate_ids, array_map('intval', $authored));
    }

    // Personal projects bound to this profile user context.
    $member_projects = get_posts(array(
        'post_type' => 'cpc_project',
        'post_status' => 'publish',
        'posts_per_page' => 300,
        'fields' => 'ids',
        'meta_query' => array(
            array('key' => 'cpc_project_component', 'value' => 'members'),
            array('key' => 'cpc_project_component_id', 'value' => $profile_user_id, 'type' => 'NUMERIC'),
        ),
        'no_found_rows' => true,
    ));
    if (!empty($member_projects)) {
        $candidate_ids = array_merge($candidate_ids, array_map('intval', $member_projects));
    }

    // Group projects where this profile user is a group member.
    $group_ids = cpc_projects_get_user_group_ids($profile_user_id);
    if (!empty($group_ids)) {
        $group_projects = get_posts(array(
            'post_type' => 'cpc_project',
            'post_status' => 'publish',
            'posts_per_page' => 400,
            'fields' => 'ids',
            'meta_query' => array(
                array('key' => 'cpc_project_component', 'value' => 'groups'),
                array('key' => 'cpc_project_component_id', 'value' => $group_ids, 'compare' => 'IN', 'type' => 'NUMERIC'),
            ),
            'no_found_rows' => true,
        ));
        if (!empty($group_projects)) {
            $candidate_ids = array_merge($candidate_ids, array_map('intval', $group_projects));
        }
    }

    // Projects where this profile user participated through tasks/comments.
    $participant_projects = cpc_projects_get_user_participating_project_ids($profile_user_id);
    if (!empty($participant_projects)) {
        $candidate_ids = array_merge($candidate_ids, array_map('intval', $participant_projects));
    }

    $candidate_ids = array_values(array_unique(array_filter(array_map('intval', $candidate_ids))));
    if (empty($candidate_ids)) {
        return array();
    }

    $posts = get_posts(array(
        'post_type' => 'cpc_project',
        'post_status' => 'publish',
        'posts_per_page' => max(1, (int)$args['posts_per_page']),
        'post__in' => $candidate_ids,
        'orderby' => 'date',
        'order' => 'DESC',
        'no_found_rows' => true,
    ));

    if (empty($posts)) {
        return array();
    }

    $viewer_id = (int)$args['viewer_id'];
    return array_values(array_filter($posts, function($project) use ($viewer_id) {
        return cpc_projects_user_can_view_project($project->ID, $viewer_id);
    }));
}

function cpc_projects_get_notification_pref($user_id, $pref_key) {
    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return true; // default: opted in
    }
    $meta = get_user_meta($user_id, $pref_key, true);
    if ($meta === '') {
        return true; // not yet set = opted in
    }
    return (bool)(int)$meta;
}

function cpc_projects_notice_message($code) {
    $map = array(
        'created' => __('Projekt wurde erstellt.', CPC2_TEXT_DOMAIN),
        'task_created' => __('Task wurde erstellt.', CPC2_TEXT_DOMAIN),
        'task_updated' => __('Task wurde aktualisiert.', CPC2_TEXT_DOMAIN),
        'task_deleted' => __('Task wurde geloescht.', CPC2_TEXT_DOMAIN),
        'denied' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN),
        'invalid' => __('Ungueltige Anfrage.', CPC2_TEXT_DOMAIN),
        'failed' => __('Aktion fehlgeschlagen.', CPC2_TEXT_DOMAIN),
    );

    return isset($map[$code]) ? $map[$code] : '';
}

function cpc_projects_handle_frontend_requests() {
    if (!cpc_projects_is_enabled() || empty($_POST['cpc_projects_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash($_POST['cpc_projects_action']));
    $redirect = isset($_POST['cpc_projects_redirect']) ? esc_url_raw(wp_unslash($_POST['cpc_projects_redirect'])) : cpc_curPageURL();
    if (!$redirect) {
        $redirect = cpc_curPageURL();
    }

    $nonce = isset($_POST['cpc_projects_nonce']) ? sanitize_text_field(wp_unslash($_POST['cpc_projects_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'cpc_projects_frontend_action')) {
        wp_safe_redirect(add_query_arg('cpc_projects_notice', 'invalid', $redirect));
        exit;
    }

    if ($action !== 'create_project') {
        return;
    }

    $component = isset($_POST['cpc_projects_component']) ? sanitize_key(wp_unslash($_POST['cpc_projects_component'])) : '';
    $component_id = isset($_POST['cpc_projects_component_id']) ? (int)$_POST['cpc_projects_component_id'] : 0;

    if (!in_array($component, array('members', 'groups'), true) || !$component_id) {
        wp_safe_redirect(add_query_arg('cpc_projects_notice', 'invalid', $redirect));
        exit;
    }

    if (!cpc_projects_user_can_create_for_context($component, $component_id)) {
        wp_safe_redirect(add_query_arg('cpc_projects_notice', 'denied', $redirect));
        exit;
    }

    $title = isset($_POST['cpc_projects_title']) ? sanitize_text_field(wp_unslash($_POST['cpc_projects_title'])) : '';
    $description = isset($_POST['cpc_projects_description']) ? wp_kses_post(wp_unslash($_POST['cpc_projects_description'])) : '';
    $status = isset($_POST['cpc_projects_status']) ? sanitize_key(wp_unslash($_POST['cpc_projects_status'])) : 'public';

    if (!in_array($status, array('public', 'members', 'private'), true)) {
        $status = 'public';
    }

    if ($title === '') {
        wp_safe_redirect(add_query_arg('cpc_projects_notice', 'failed', $redirect));
        exit;
    }

    $project_id = wp_insert_post(array(
        'post_type' => 'cpc_project',
        'post_status' => 'publish',
        'post_title' => $title,
        'post_content' => $description,
        'post_author' => get_current_user_id(),
    ), true);

    if (is_wp_error($project_id) || !$project_id) {
        wp_safe_redirect(add_query_arg('cpc_projects_notice', 'failed', $redirect));
        exit;
    }

    update_post_meta($project_id, 'cpc_project_component', $component);
    update_post_meta($project_id, 'cpc_project_component_id', $component_id);
    update_post_meta($project_id, 'cpc_project_status', $status);

    wp_safe_redirect(add_query_arg('cpc_projects_notice', 'created', $redirect));
    exit;
}

function cpc_projects_get_tasks($project_id, $args = array()) {
    global $wpdb;

    $project_id = (int)$project_id;
    if ($project_id <= 0) {
        return array();
    }

    $defaults = array(
        'status' => '',
        'limit' => 300,
    );
    $args = wp_parse_args($args, $defaults);

    $table = cpc_projects_get_tasks_table_name();
    $limit = max(1, min(1000, (int)$args['limit']));

    if ($args['status'] !== '' && in_array($args['status'], array('open', 'done'), true)) {
        $query = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE project_id = %d AND status = %s ORDER BY status ASC, priority DESC, date_added DESC LIMIT %d",
            $project_id,
            $args['status'],
            $limit
        );
    } else {
        $query = $wpdb->prepare(
            "SELECT * FROM {$table} WHERE project_id = %d ORDER BY status ASC, priority DESC, date_added DESC LIMIT %d",
            $project_id,
            $limit
        );
    }

    return $wpdb->get_results($query);
}

function cpc_projects_get_task_progress($project_id) {
    global $wpdb;

    $project_id = (int)$project_id;
    if ($project_id <= 0) {
        return array(
            'total' => 0,
            'completed' => 0,
            'remaining' => 0,
            'progress' => 0,
        );
    }

    $table = cpc_projects_get_tasks_table_name();
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT COUNT(*) AS total, SUM(CASE WHEN status = 'done' THEN 1 ELSE 0 END) AS completed FROM {$table} WHERE project_id = %d",
        $project_id
    ));

    $total = !empty($row->total) ? (int)$row->total : 0;
    $completed = !empty($row->completed) ? (int)$row->completed : 0;
    $remaining = max(0, $total - $completed);
    $progress = $total > 0 ? (int)ceil(($completed / $total) * 100) : 0;

    return array(
        'total' => $total,
        'completed' => $completed,
        'remaining' => $remaining,
        'progress' => max(0, min(100, $progress)),
    );
}

function cpc_projects_get_user_recent_tasks($user_id = 0, $limit = 5) {
    global $wpdb;

    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        $user_id = get_current_user_id();
    }
    if ($user_id <= 0) {
        return array();
    }

    $limit = max(1, min(50, (int)$limit));
    $table = cpc_projects_get_tasks_table_name();

    $query = $wpdb->prepare(
        "SELECT t.*, p.post_title AS project_title
         FROM {$table} t
         INNER JOIN {$wpdb->posts} p ON p.ID = t.project_id
         WHERE p.post_type = 'cpc_project'
           AND p.post_status = 'publish'
           AND (t.user_id = %d OR t.completed_by = %d OR FIND_IN_SET(%d, t.assigned_user_ids))
         ORDER BY t.date_updated DESC
         LIMIT %d",
        $user_id,
        $user_id,
        $user_id,
        $limit
    );

    $rows = $wpdb->get_results($query);
    if (empty($rows)) {
        return array();
    }

    return array_values(array_filter($rows, function($row) use ($user_id) {
        return !empty($row->project_id) && cpc_projects_user_can_view_project((int)$row->project_id, $user_id);
    }));
}

function cpc_projects_normalize_assigned_user_ids($assigned_user_ids) {
    if (is_string($assigned_user_ids)) {
        $assigned_user_ids = explode(',', $assigned_user_ids);
    }
    if (!is_array($assigned_user_ids)) {
        return '';
    }

    $ids = array();
    foreach ($assigned_user_ids as $id) {
        $id = (int)$id;
        if ($id > 0) {
            $ids[] = $id;
        }
    }

    $ids = array_values(array_unique($ids));
    return implode(',', $ids);
}

function cpc_projects_get_task_assigned_user_ids($task) {
    if (!$task || empty($task->assigned_user_ids)) {
        return array();
    }

    $ids = explode(',', (string)$task->assigned_user_ids);
    $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
    return $ids;
}

function cpc_projects_get_assignable_users($project_id) {
    $project_id = (int)$project_id;
    if ($project_id <= 0) {
        return array();
    }

    $component = cpc_projects_get_component($project_id);
    $component_id = cpc_projects_get_component_id($project_id);
    $user_ids = array();

    if ($component === 'groups' && $component_id > 0 && function_exists('cpc_get_group_members')) {
        $members = cpc_get_group_members($component_id, 'active');
        foreach ($members as $member) {
            if (!empty($member->ID)) {
                $user_ids[] = (int)$member->ID;
            }
        }
    } elseif ($component === 'members' && $component_id > 0) {
        $user_ids[] = (int)$component_id;
    }

    $project = get_post($project_id);
    if ($project && !empty($project->post_author)) {
        $user_ids[] = (int)$project->post_author;
    }

    $user_ids = array_values(array_unique(array_filter($user_ids)));
    if (empty($user_ids)) {
        return array();
    }

    $users = get_users(array(
        'include' => $user_ids,
        'number' => -1,
        'orderby' => 'display_name',
        'order' => 'ASC',
    ));

    // Some membership providers can return duplicate identities across joins.
    // Keep only one user object per unique user ID.
    $unique_users = array();
    foreach ($users as $user) {
        if (empty($user->ID)) {
            continue;
        }
        $uid = (int)$user->ID;
        if (!isset($unique_users[$uid])) {
            $unique_users[$uid] = $user;
        }
    }

    return array_values($unique_users);
}

function cpc_projects_add_task($project_id, $user_id, $title, $description = '', $priority = 1, $deadline = '', $assigned_user_ids = '') {
    global $wpdb;

    $project_id = (int)$project_id;
    $user_id = (int)$user_id;
    $title = sanitize_text_field((string)$title);
    if ($project_id <= 0 || $user_id <= 0 || $title === '') {
        return 0;
    }

    $priority = max(1, min(3, (int)$priority));
    $assigned_user_ids = cpc_projects_normalize_assigned_user_ids($assigned_user_ids);
    $deadline = trim((string)$deadline);
    $deadline_value = null;
    if ($deadline !== '') {
        $timestamp = strtotime($deadline);
        if ($timestamp) {
            $deadline_value = gmdate('Y-m-d H:i:s', $timestamp);
        }
    }

    $now = current_time('mysql', 1);
    $table = cpc_projects_get_tasks_table_name();
    $ok = $wpdb->insert(
        $table,
        array(
            'project_id' => $project_id,
            'user_id' => $user_id,
            'title' => $title,
            'description' => wp_kses_post((string)$description),
            'status' => 'open',
            'priority' => $priority,
            'deadline' => $deadline_value,
            'assigned_user_ids' => $assigned_user_ids,
            'completed_by' => 0,
            'date_added' => $now,
            'date_updated' => $now,
        ),
        array('%d', '%d', '%s', '%s', '%s', '%d', '%s', '%s', '%d', '%s', '%s')
    );

    if (!$ok) {
        return 0;
    }

    return (int)$wpdb->insert_id;
}

function cpc_projects_get_task($task_id) {
    global $wpdb;

    $task_id = (int)$task_id;
    if ($task_id <= 0) {
        return null;
    }

    $table = cpc_projects_get_tasks_table_name();
    $query = $wpdb->prepare("SELECT * FROM {$table} WHERE id = %d", $task_id);
    return $wpdb->get_row($query);
}

function cpc_projects_update_task_status($task_id, $status = 'open', $completed_by = 0) {
    global $wpdb;

    $task_id = (int)$task_id;
    if ($task_id <= 0 || !in_array($status, array('open', 'done'), true)) {
        return false;
    }

    $table = cpc_projects_get_tasks_table_name();
    $data = array(
        'status' => $status,
        'completed_by' => ($status === 'done') ? (int)$completed_by : 0,
        'date_updated' => current_time('mysql', 1),
    );

    $updated = $wpdb->update($table, $data, array('id' => $task_id), array('%s', '%d', '%s'), array('%d'));
    return ($updated !== false);
}

function cpc_projects_update_task($task_id, $data = array()) {
    global $wpdb;

    $task_id = (int)$task_id;
    if ($task_id <= 0 || !is_array($data) || empty($data)) {
        return false;
    }

    $table = cpc_projects_get_tasks_table_name();
    $update_data = array();
    $formats = array();

    if (isset($data['title'])) {
        $title = sanitize_text_field((string)$data['title']);
        if ($title === '') {
            return false;
        }
        $update_data['title'] = $title;
        $formats[] = '%s';
    }

    if (isset($data['description'])) {
        $update_data['description'] = wp_kses_post((string)$data['description']);
        $formats[] = '%s';
    }

    if (isset($data['priority'])) {
        $update_data['priority'] = max(1, min(3, (int)$data['priority']));
        $formats[] = '%d';
    }

    if (array_key_exists('deadline', $data)) {
        $deadline = trim((string)$data['deadline']);
        $deadline_value = null;
        if ($deadline !== '') {
            $timestamp = strtotime($deadline);
            if ($timestamp) {
                $deadline_value = gmdate('Y-m-d H:i:s', $timestamp);
            }
        }
        $update_data['deadline'] = $deadline_value;
        $formats[] = '%s';
    }

    if (isset($data['assigned_user_ids'])) {
        $update_data['assigned_user_ids'] = cpc_projects_normalize_assigned_user_ids($data['assigned_user_ids']);
        $formats[] = '%s';
    }

    if (empty($update_data)) {
        return false;
    }

    $update_data['date_updated'] = current_time('mysql', 1);
    $formats[] = '%s';

    $updated = $wpdb->update($table, $update_data, array('id' => $task_id), $formats, array('%d'));
    return ($updated !== false);
}

function cpc_projects_delete_task($task_id) {
    global $wpdb;

    $task_id = (int)$task_id;
    if ($task_id <= 0) {
        return false;
    }

    $table = cpc_projects_get_tasks_table_name();
    $deleted = $wpdb->delete($table, array('id' => $task_id), array('%d'));
    return ($deleted !== false);
}

function cpc_projects_get_task_comments($project_id, $task_id, $args = array()) {
    $project_id = (int)$project_id;
    $task_id = (int)$task_id;
    if ($project_id <= 0 || $task_id <= 0) {
        return array();
    }

    $defaults = array(
        'limit' => 30,
    );
    $args = wp_parse_args($args, $defaults);
    $number = max(1, min(200, (int)$args['limit']));

    $query = array(
        'post_id' => $project_id,
        'status' => 'approve',
        'type' => 'cpc_project_task',
        'number' => $number,
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => 'cpc_project_task_id',
                'value' => $task_id,
                'type' => 'NUMERIC',
            ),
        ),
    );

    return get_comments($query);
}

function cpc_projects_add_task_comment($project_id, $task_id, $user_id, $content) {
    $project_id = (int)$project_id;
    $task_id = (int)$task_id;
    $user_id = (int)$user_id;
    $content = trim((string)$content);

    if ($project_id <= 0 || $task_id <= 0 || $user_id <= 0 || $content === '') {
        return 0;
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        return 0;
    }

    $comment_data = array(
        'comment_post_ID' => $project_id,
        'comment_author' => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_author_url' => '',
        'comment_content' => wp_kses_post($content),
        'comment_type' => 'cpc_project_task',
        'comment_parent' => 0,
        'user_id' => $user_id,
        'comment_approved' => 1,
    );

    $comment_id = wp_insert_comment($comment_data);
    if (!$comment_id) {
        return 0;
    }

    add_comment_meta($comment_id, 'cpc_project_task_id', $task_id, true);

    return (int)$comment_id;
}

function cpc_projects_get_task_url($project_id, $task_id = 0) {
    $project_id = (int)$project_id;
    if ($project_id <= 0) {
        return '';
    }

    $url = get_permalink($project_id);
    if (!$url) {
        return '';
    }

    $task_id = (int)$task_id;
    if ($task_id > 0) {
        $url .= '#cpc-project-task-'.$task_id;
    }

    return $url;
}

function cpc_projects_get_task_notification_recipients($project_id, $task = null, $exclude_user_id = 0) {
    $project_id = (int)$project_id;
    if ($project_id <= 0) {
        return array();
    }

    $exclude_user_id = (int)$exclude_user_id;
    $project = get_post($project_id);
    if (!$project || $project->post_type !== 'cpc_project') {
        return array();
    }

    $recipients = array((int)$project->post_author);
    $component = cpc_projects_get_component($project_id);
    $component_id = cpc_projects_get_component_id($project_id);
    if ($component === 'members' && $component_id > 0) {
        $recipients[] = (int)$component_id;
    }
    if ($component === 'groups' && $component_id > 0 && function_exists('cpc_get_group_members')) {
        $scope = cpc_projects_group_alert_scope();
        if ($scope === 'all_members') {
            $members = cpc_get_group_members($component_id, 'active');
            foreach ($members as $member) {
                if (!empty($member->ID)) {
                    $recipients[] = (int)$member->ID;
                }
            }
        } else {
            $admins = cpc_get_group_members($component_id, 'active', 'admin');
            $moderators = cpc_get_group_members($component_id, 'active', 'moderator');
            foreach (array_merge($admins, $moderators) as $member) {
                if (!empty($member->ID)) {
                    $recipients[] = (int)$member->ID;
                }
            }
        }
    }

    if ($task) {
        $recipients[] = (int)$task->user_id;
        if (!empty($task->completed_by)) {
            $recipients[] = (int)$task->completed_by;
        }

        if (!empty($task->assigned_user_ids)) {
            $assigned_ids = explode(',', (string)$task->assigned_user_ids);
            foreach ($assigned_ids as $assigned_id) {
                $assigned_id = (int)trim($assigned_id);
                if ($assigned_id > 0) {
                    $recipients[] = $assigned_id;
                }
            }
        }

        $task_comments = cpc_projects_get_task_comments($project_id, (int)$task->id, array('limit' => 200));
        foreach ($task_comments as $task_comment) {
            $recipients[] = (int)$task_comment->user_id;
        }
    }

    $recipients = array_values(array_unique(array_filter(array_map('intval', $recipients))));
    if ($exclude_user_id > 0) {
        $recipients = array_values(array_filter($recipients, function($id) use ($exclude_user_id) {
            return $id !== $exclude_user_id;
        }));
    }

    return $recipients;
}

function cpc_projects_insert_task_alert($recipient_id, $author_id, $subject, $message, $url, $project_id, $task_id, $event_type) {
    $recipient_id = (int)$recipient_id;
    $author_id = (int)$author_id;

    if ($recipient_id <= 0 || $author_id <= 0 || $subject === '' || $message === '' || $url === '') {
        return 0;
    }

    if (!function_exists('cpc_com_insert_alert') || !post_type_exists('cpc_alerts')) {
        return 0;
    }

    $content = '<p>'.esc_html($message).'</p>';
    $content .= '<p><a href="'.esc_url($url).'">'.esc_html($url).'</a></p>';

    $parameters = 'project_id='.(int)$project_id.'&task_id='.(int)$task_id.'&event='.rawurlencode((string)$event_type);

    return (int)cpc_com_insert_alert(
        'project_task',
        $subject,
        $content,
        $author_id,
        $recipient_id,
        $parameters,
        $url,
        $message,
        'pending',
        ''
    );
}

function cpc_projects_insert_task_activity($author_id, $project_id, $task_id, $text, $event_type = 'updated') {
    $author_id = (int)$author_id;
    $project_id = (int)$project_id;
    $task_id = (int)$task_id;
    $text = trim((string)$text);

    if ($author_id <= 0 || $project_id <= 0 || $text === '') {
        return 0;
    }

    if (!cpc_projects_activity_enabled()) {
        return 0;
    }

    if (function_exists('cpc_add_activity')) {
        return (int)cpc_add_activity($author_id, 'project_task_'.$event_type, $text, $project_id);
    }

    if (!post_type_exists('cpc_activity')) {
        return 0;
    }

    $activity_id = wp_insert_post(array(
        'post_type' => 'cpc_activity',
        'post_status' => 'publish',
        'post_title' => wp_strip_all_tags($text),
        'post_content' => wp_kses_post($text),
        'post_author' => $author_id,
        'comment_status' => 'open',
        'ping_status' => 'closed',
    ), true);

    if (is_wp_error($activity_id) || !$activity_id) {
        return 0;
    }

    update_post_meta($activity_id, 'cpc_target_type', 'project_task_'.$event_type);
    update_post_meta($activity_id, 'cpc_project_id', $project_id);
    update_post_meta($activity_id, 'cpc_project_task_id', $task_id);
    update_post_meta($activity_id, 'cpc_target', (int)get_post_field('post_author', $project_id));

    return (int)$activity_id;
}

function cpc_projects_notify_task_event($project_id, $task, $event_type, $actor_id, $comment_text = '') {
    $project_id = (int)$project_id;
    $actor_id = (int)$actor_id;
    $event_type = sanitize_key((string)$event_type);
    $comment_text = trim((string)$comment_text);

    if ($project_id <= 0 || !$task || $actor_id <= 0) {
        return;
    }

    $project = get_post($project_id);
    if (!$project || $project->post_type !== 'cpc_project') {
        return;
    }

    $task_id = (int)$task->id;
    $task_title = trim((string)$task->title);
    if ($task_title === '') {
        $task_title = __('Task', CPC2_TEXT_DOMAIN);
    }

    $actor = get_user_by('id', $actor_id);
    $actor_name = $actor ? $actor->display_name : __('Ein Mitglied', CPC2_TEXT_DOMAIN);

    $url = cpc_projects_get_task_url($project_id, $task_id);
    if ($url === '') {
        return;
    }

    $subject = get_bloginfo('name').': '.__('Projekt-Task Update', CPC2_TEXT_DOMAIN);
    $message = '';
    $activity_text = '';

    if ($event_type === 'created') {
        $message = sprintf(__('"%s" hat die Task "%s" erstellt.', CPC2_TEXT_DOMAIN), $actor_name, $task_title);
        $activity_text = sprintf(__('hat die Task <a href="%1$s">%2$s</a> im Projekt <a href="%3$s">%4$s</a> erstellt', CPC2_TEXT_DOMAIN), esc_url($url), esc_html($task_title), esc_url(get_permalink($project_id)), esc_html(get_the_title($project_id)));
    } elseif ($event_type === 'updated') {
        $message = sprintf(__('"%s" hat die Task "%s" aktualisiert.', CPC2_TEXT_DOMAIN), $actor_name, $task_title);
        $activity_text = sprintf(__('hat die Task <a href="%1$s">%2$s</a> aktualisiert', CPC2_TEXT_DOMAIN), esc_url($url), esc_html($task_title));
    } elseif ($event_type === 'completed') {
        $message = sprintf(__('"%s" hat die Task "%s" abgeschlossen.', CPC2_TEXT_DOMAIN), $actor_name, $task_title);
        $activity_text = sprintf(__('hat die Task <a href="%1$s">%2$s</a> als erledigt markiert', CPC2_TEXT_DOMAIN), esc_url($url), esc_html($task_title));
    } elseif ($event_type === 'reopened') {
        $message = sprintf(__('"%s" hat die Task "%s" wieder geoeffnet.', CPC2_TEXT_DOMAIN), $actor_name, $task_title);
        $activity_text = sprintf(__('hat die Task <a href="%1$s">%2$s</a> wieder geoeffnet', CPC2_TEXT_DOMAIN), esc_url($url), esc_html($task_title));
    } elseif ($event_type === 'commented') {
        $message = sprintf(__('"%s" hat die Task "%s" kommentiert: %s', CPC2_TEXT_DOMAIN), $actor_name, $task_title, wp_trim_words(wp_strip_all_tags($comment_text), 18));
        $activity_text = sprintf(__('hat die Task <a href="%1$s">%2$s</a> kommentiert', CPC2_TEXT_DOMAIN), esc_url($url), esc_html($task_title));
    } elseif ($event_type === 'deleted') {
        $message = sprintf(__('"%s" hat die Task "%s" geloescht.', CPC2_TEXT_DOMAIN), $actor_name, $task_title);
        $activity_text = sprintf(__('hat die Task "%s" im Projekt %s geloescht', CPC2_TEXT_DOMAIN), esc_html($task_title), esc_html(get_the_title($project_id)));
    }

    if ($message === '') {
        return;
    }

    $recipients = array();
    if (cpc_projects_alerts_enabled()) {
        $recipients = cpc_projects_get_task_notification_recipients($project_id, $task, $actor_id);
        foreach ($recipients as $recipient_id) {
            if ($event_type === 'created' && !cpc_projects_get_notification_pref($recipient_id, 'cpc_projects_notify_task')) {
                continue;
            }
            if ($event_type === 'commented' && !cpc_projects_get_notification_pref($recipient_id, 'cpc_projects_notify_comment')) {
                continue;
            }
            cpc_projects_insert_task_alert($recipient_id, $actor_id, $subject, $message, $url, $project_id, $task_id, $event_type);
        }
    }

    cpc_projects_insert_task_activity($actor_id, $project_id, $task_id, $activity_text, $event_type);
    cpc_projects_add_task_event($project_id, $task_id, $actor_id, $event_type, $message);

    do_action('cpc_projects_task_event', $event_type, $project_id, $task_id, $actor_id, $recipients, $message);
}

function cpc_projects_add_task_event($project_id, $task_id, $user_id, $event_type, $message = '') {
    $project_id = (int)$project_id;
    $task_id = (int)$task_id;
    $user_id = (int)$user_id;
    $event_type = sanitize_key((string)$event_type);
    $message = trim((string)$message);

    if ($project_id <= 0 || $task_id <= 0 || $user_id <= 0 || $event_type === '' || $message === '') {
        return 0;
    }

    $user = get_user_by('id', $user_id);
    if (!$user) {
        return 0;
    }

    $comment_id = wp_insert_comment(array(
        'comment_post_ID' => $project_id,
        'comment_author' => $user->display_name,
        'comment_author_email' => $user->user_email,
        'comment_author_url' => '',
        'comment_content' => wp_kses_post($message),
        'comment_type' => 'cpc_project_task_event',
        'comment_parent' => 0,
        'user_id' => $user_id,
        'comment_approved' => 1,
    ));

    if (!$comment_id) {
        return 0;
    }

    add_comment_meta($comment_id, 'cpc_project_task_id', $task_id, true);
    add_comment_meta($comment_id, 'cpc_project_task_event_type', $event_type, true);

    return (int)$comment_id;
}

function cpc_projects_get_task_events($project_id, $task_id, $limit = 25) {
    $project_id = (int)$project_id;
    $task_id = (int)$task_id;
    $limit = max(1, min(200, (int)$limit));

    if ($project_id <= 0 || $task_id <= 0) {
        return array();
    }

    return get_comments(array(
        'post_id' => $project_id,
        'status' => 'approve',
        'type' => 'cpc_project_task_event',
        'number' => $limit,
        'order' => 'DESC',
        'meta_query' => array(
            array(
                'key' => 'cpc_project_task_id',
                'value' => $task_id,
                'type' => 'NUMERIC',
            ),
        ),
    ));
}

function cpc_projects_get_comment_attachment_ids($comment_id) {
    $comment_id = (int)$comment_id;
    if ($comment_id <= 0) {
        return array();
    }

    $ids = get_comment_meta($comment_id, 'cpc_project_task_attachment_ids', true);
    if (!is_array($ids)) {
        return array();
    }

    return array_values(array_filter(array_map('intval', $ids)));
}

function cpc_projects_add_comment_attachments($project_id, $task_id, $comment_id, $files) {
    $project_id = (int)$project_id;
    $task_id = (int)$task_id;
    $comment_id = (int)$comment_id;

    if ($project_id <= 0 || $task_id <= 0 || $comment_id <= 0 || !cpc_projects_task_comment_attachments_enabled()) {
        return array();
    }

    if (empty($files['name'])) {
        return array();
    }

    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/image.php';

    $created_ids = array();
    $max_bytes = cpc_projects_task_comment_max_attachment_mb() * 1024 * 1024;
    $allowed_exts = cpc_projects_comment_allowed_extensions();
    $names = is_array($files['name']) ? $files['name'] : array($files['name']);

    foreach ($names as $index => $name) {
        $name = (string)$name;
        if ($name === '') {
            continue;
        }

        $single = array(
            'name' => isset($files['name'][$index]) ? $files['name'][$index] : '',
            'type' => isset($files['type'][$index]) ? $files['type'][$index] : '',
            'tmp_name' => isset($files['tmp_name'][$index]) ? $files['tmp_name'][$index] : '',
            'error' => isset($files['error'][$index]) ? (int)$files['error'][$index] : 0,
            'size' => isset($files['size'][$index]) ? (int)$files['size'][$index] : 0,
        );

        if ((int)$single['error'] !== 0 || empty($single['tmp_name'])) {
            continue;
        }

        if ($max_bytes > 0 && (int)$single['size'] > $max_bytes) {
            continue;
        }

        $file_type = wp_check_filetype((string)$single['name']);
        $ext = isset($file_type['ext']) ? strtolower((string)$file_type['ext']) : '';
        $mime_type = isset($file_type['type']) ? (string)$file_type['type'] : '';
        if ($ext === '' || !in_array($ext, $allowed_exts, true)) {
            continue;
        }

        $bucket = cpc_projects_get_attachment_bucket_for_mime($mime_type);
        $bucket_max = cpc_projects_comment_attachment_type_limit_mb($bucket) * 1024 * 1024;
        if ($bucket_max > 0 && (int)$single['size'] > $bucket_max) {
            continue;
        }

        $uploaded = wp_handle_upload($single, array('test_form' => false));
        if (!is_array($uploaded) || !empty($uploaded['error']) || empty($uploaded['file']) || empty($uploaded['url'])) {
            continue;
        }

        $attachment_id = wp_insert_attachment(array(
            'post_mime_type' => (string)$uploaded['type'],
            'post_title' => sanitize_text_field(pathinfo((string)$single['name'], PATHINFO_FILENAME)),
            'post_content' => '',
            'post_status' => 'inherit',
            'post_author' => get_current_user_id(),
            'post_parent' => $project_id,
        ), $uploaded['file'], $project_id);

        if (is_wp_error($attachment_id) || !$attachment_id) {
            continue;
        }

        $meta = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
        if (is_array($meta) && !empty($meta)) {
            wp_update_attachment_metadata($attachment_id, $meta);
        }

        update_post_meta($attachment_id, 'cpc_project_id', $project_id);
        update_post_meta($attachment_id, 'cpc_project_task_id', $task_id);
        update_post_meta($attachment_id, 'cpc_project_task_comment_id', $comment_id);

        $created_ids[] = (int)$attachment_id;
    }

    if (!empty($created_ids)) {
        update_comment_meta($comment_id, 'cpc_project_task_attachment_ids', $created_ids);
    }

    return $created_ids;
}

function cpc_projects_user_can_delete_comment_attachment($attachment_id, $user_id = 0) {
    $attachment_id = (int)$attachment_id;
    if ($attachment_id <= 0) {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return false;
    }

    if (current_user_can('manage_options')) {
        return true;
    }

    $project_id = (int)get_post_meta($attachment_id, 'cpc_project_id', true);
    $comment_id = (int)get_post_meta($attachment_id, 'cpc_project_task_comment_id', true);

    if ($project_id > 0 && cpc_projects_user_can_manage_project($project_id, $user_id)) {
        return true;
    }

    $attachment = get_post($attachment_id);
    if ($attachment && (int)$attachment->post_author === $user_id) {
        return true;
    }

    if ($comment_id > 0) {
        $comment = get_comment($comment_id);
        if ($comment && (int)$comment->user_id === $user_id) {
            return true;
        }
    }

    return false;
}

function cpc_projects_delete_comment_attachment($attachment_id, $user_id = 0) {
    $attachment_id = (int)$attachment_id;
    if ($attachment_id <= 0 || !cpc_projects_user_can_delete_comment_attachment($attachment_id, $user_id)) {
        return false;
    }

    $comment_id = (int)get_post_meta($attachment_id, 'cpc_project_task_comment_id', true);
    if ($comment_id > 0) {
        $ids = cpc_projects_get_comment_attachment_ids($comment_id);
        if (!empty($ids)) {
            $ids = array_values(array_filter($ids, function($id) use ($attachment_id) {
                return (int)$id !== $attachment_id;
            }));
            update_comment_meta($comment_id, 'cpc_project_task_attachment_ids', $ids);
        }
    }

    return (bool)wp_delete_attachment($attachment_id, true);
}

function cpc_projects_get_task_attachment_ids($task_id) {
    $task_id = (int)$task_id;
    if ($task_id <= 0) {
        return array();
    }
    $raw = get_option('cpc_projects_task_attachment_ids_'.$task_id, array());
    if (!is_array($raw)) {
        return array();
    }
    return array_values(array_filter(array_map('intval', $raw)));
}

function cpc_projects_add_task_attachments($project_id, $task_id, $files) {
    $project_id = (int)$project_id;
    $task_id    = (int)$task_id;

    if ($project_id <= 0 || $task_id <= 0 || !cpc_projects_task_comment_attachments_enabled()) {
        return array();
    }

    if (empty($files['name'])) {
        return array();
    }

    require_once ABSPATH.'wp-admin/includes/file.php';
    require_once ABSPATH.'wp-admin/includes/image.php';

    $created_ids  = array();
    $max_bytes    = cpc_projects_task_comment_max_attachment_mb() * 1024 * 1024;
    $allowed_exts = cpc_projects_comment_allowed_extensions();
    $names        = is_array($files['name']) ? $files['name'] : array($files['name']);

    foreach ($names as $index => $name) {
        $name = (string)$name;
        if ($name === '') {
            continue;
        }

        $single = array(
            'name'     => isset($files['name'][$index])     ? $files['name'][$index]     : '',
            'type'     => isset($files['type'][$index])     ? $files['type'][$index]     : '',
            'tmp_name' => isset($files['tmp_name'][$index]) ? $files['tmp_name'][$index] : '',
            'error'    => isset($files['error'][$index])    ? (int)$files['error'][$index] : 0,
            'size'     => isset($files['size'][$index])     ? (int)$files['size'][$index]  : 0,
        );

        if ((int)$single['error'] !== 0 || empty($single['tmp_name'])) {
            continue;
        }

        if ($max_bytes > 0 && (int)$single['size'] > $max_bytes) {
            continue;
        }

        $file_type = wp_check_filetype((string)$single['name']);
        $ext       = isset($file_type['ext'])  ? strtolower((string)$file_type['ext'])  : '';
        $mime_type = isset($file_type['type']) ? (string)$file_type['type']              : '';

        if ($ext === '' || !in_array($ext, $allowed_exts, true)) {
            continue;
        }

        $bucket     = cpc_projects_get_attachment_bucket_for_mime($mime_type);
        $bucket_max = cpc_projects_comment_attachment_type_limit_mb($bucket) * 1024 * 1024;
        if ($bucket_max > 0 && (int)$single['size'] > $bucket_max) {
            continue;
        }

        $uploaded = wp_handle_upload($single, array('test_form' => false));
        if (!is_array($uploaded) || !empty($uploaded['error']) || empty($uploaded['file']) || empty($uploaded['url'])) {
            continue;
        }

        $attachment_id = wp_insert_attachment(array(
            'post_mime_type' => (string)$uploaded['type'],
            'post_title'     => sanitize_text_field(pathinfo((string)$single['name'], PATHINFO_FILENAME)),
            'post_content'   => '',
            'post_status'    => 'inherit',
            'post_author'    => get_current_user_id(),
            'post_parent'    => $project_id,
        ), $uploaded['file'], $project_id);

        if (is_wp_error($attachment_id) || !$attachment_id) {
            continue;
        }

        $meta = wp_generate_attachment_metadata($attachment_id, $uploaded['file']);
        if (is_array($meta) && !empty($meta)) {
            wp_update_attachment_metadata($attachment_id, $meta);
        }

        update_post_meta($attachment_id, 'cpc_project_id',      $project_id);
        update_post_meta($attachment_id, 'cpc_project_task_id', $task_id);
        update_post_meta($attachment_id, 'cpc_project_task_direct_attachment', 1);

        $created_ids[] = (int)$attachment_id;
    }

    if (!empty($created_ids)) {
        $existing = cpc_projects_get_task_attachment_ids($task_id);
        update_option('cpc_projects_task_attachment_ids_'.$task_id, array_values(array_unique(array_merge($existing, $created_ids))), false);
    }

    return $created_ids;
}

function cpc_projects_delete_task_attachment($attachment_id, $user_id = 0) {
    $attachment_id = (int)$attachment_id;
    if ($attachment_id <= 0) {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $user_id = (int)$user_id;

    $project_id = (int)get_post_meta($attachment_id, 'cpc_project_id', true);
    $task_id    = (int)get_post_meta($attachment_id, 'cpc_project_task_id', true);

    $can_delete = current_user_can('manage_options')
        || ($project_id > 0 && cpc_projects_user_can_manage_project($project_id, $user_id));

    if (!$can_delete) {
        $attachment = get_post($attachment_id);
        $can_delete = $attachment && (int)$attachment->post_author === $user_id;
    }

    if (!$can_delete) {
        return false;
    }

    if ($task_id > 0) {
        $ids = cpc_projects_get_task_attachment_ids($task_id);
        $ids = array_values(array_filter($ids, function($id) use ($attachment_id) {
            return (int)$id !== $attachment_id;
        }));
        update_option('cpc_projects_task_attachment_ids_'.$task_id, $ids, false);
    }

    return (bool)wp_delete_attachment($attachment_id, true);
}

function cpc_projects_delete_task_comment($comment_id, $user_id = 0) {
    $comment_id = (int)$comment_id;
    if ($comment_id <= 0) {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return false;
    }

    $comment = get_comment($comment_id);
    if (!$comment || $comment->comment_type !== 'cpc_project_task_comment') {
        return false;
    }

    $project_id = (int)$comment->comment_post_ID;
    $can_delete = current_user_can('manage_options')
        || ($project_id > 0 && cpc_projects_user_can_manage_project($project_id, $user_id))
        || (int)$comment->user_id === $user_id;

    if (!$can_delete) {
        return false;
    }

    $attachment_ids = cpc_projects_get_comment_attachment_ids($comment_id);
    foreach ($attachment_ids as $aid) {
        wp_delete_attachment((int)$aid, true);
    }

    return (bool)wp_delete_comment($comment_id, true);
}

function cpc_projects_get_project_events($project_id, $limit = 80) {
    $project_id = (int)$project_id;
    $limit = max(1, min(300, (int)$limit));

    if ($project_id <= 0) {
        return array();
    }

    return get_comments(array(
        'post_id' => $project_id,
        'status' => 'approve',
        'type' => 'cpc_project_task_event',
        'number' => $limit,
        'order' => 'DESC',
    ));
}
