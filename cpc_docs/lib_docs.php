<?php

function cpc_docs_is_enabled() {
    return (bool)get_option('cpc_docs_module_enabled', 0);
}

function cpc_docs_get_directory_page_id() {
    return max(0, (int)get_option('cpc_docs_directory_page', 0));
}

function cpc_docs_get_slug() {
    $slug = sanitize_title((string)get_option('cpc_docs_slug', 'docs'));
    return $slug !== '' ? $slug : 'docs';
}

function cpc_docs_get_directory_title() {
    $title = trim((string)get_option('cpc_docs_directory_title', 'Dokumente-Verzeichnis'));
    return $title !== '' ? $title : __('Dokumente-Verzeichnis', CPC2_TEXT_DOMAIN);
}

function cpc_docs_get_user_tab_name() {
    $label = trim((string)get_option('cpc_docs_user_tab_name', 'Dokumente'));
    return $label !== '' ? $label : __('Dokumente', CPC2_TEXT_DOMAIN);
}

function cpc_docs_get_group_tab_name() {
    $label = trim((string)get_option('cpc_docs_group_tab_name', 'Dokumente'));
    return $label !== '' ? $label : __('Dokumente', CPC2_TEXT_DOMAIN);
}

function cpc_docs_get_directory_excerpt_length() {
    return max(0, min(120, (int)get_option('cpc_docs_directory_excerpt_length', 18)));
}

function cpc_docs_enable_attachments() {
    return (bool)get_option('cpc_docs_enable_attachments', 1);
}

function cpc_docs_get_directory_items_per_page() {
    return max(6, min(60, (int)get_option('cpc_docs_directory_items_per_page', 12)));
}

function cpc_docs_get_current_request_url() {
    $scheme = is_ssl() ? 'https://' : 'http://';
    $host = isset($_SERVER['HTTP_HOST']) ? sanitize_text_field(wp_unslash($_SERVER['HTTP_HOST'])) : '';
    $uri = isset($_SERVER['REQUEST_URI']) ? wp_unslash($_SERVER['REQUEST_URI']) : '';
    if ($host === '') {
        return home_url('/');
    }
    return esc_url_raw($scheme.$host.$uri);
}

function cpc_docs_get_component($doc_id) {
    $component = get_post_meta((int)$doc_id, 'cpc_doc_component', true);
    return $component ? $component : 'members';
}

function cpc_docs_get_component_id($doc_id) {
    return (int)get_post_meta((int)$doc_id, 'cpc_doc_component_id', true);
}

function cpc_docs_get_status($doc_id) {
    $status = get_post_meta((int)$doc_id, 'cpc_doc_status', true);
    return $status ? $status : 'public';
}

function cpc_docs_get_status_options($component = 'members') {
    if ($component === 'groups') {
        return array(
            'public' => __('Oeffentlich', CPC2_TEXT_DOMAIN),
            'members' => __('Nur Gruppenmitglieder', CPC2_TEXT_DOMAIN),
            'admins' => __('Nur Gruppen-Admins', CPC2_TEXT_DOMAIN),
            'private' => __('Privat', CPC2_TEXT_DOMAIN),
        );
    }

    return array(
        'public' => __('Oeffentlich', CPC2_TEXT_DOMAIN),
        'loggedin' => __('Angemeldet', CPC2_TEXT_DOMAIN),
        'private' => __('Privat', CPC2_TEXT_DOMAIN),
    );
}

function cpc_docs_normalize_status($status, $component = 'members') {
    $status = sanitize_key((string)$status);
    $options = cpc_docs_get_status_options($component);
    return isset($options[$status]) ? $status : 'public';
}

function cpc_docs_get_permission_defaults($component = 'members') {
    if ($component === 'groups') {
        return array(
            'edit' => 'group_adminmod',
            'manage' => 'group_adminmod',
            'read_comments' => 'group_members',
            'post_comments' => 'group_members',
            'view_history' => 'group_members',
        );
    }

    return array(
        'edit' => 'author',
        'manage' => 'author',
        'read_comments' => 'loggedin',
        'post_comments' => 'loggedin',
        'view_history' => 'loggedin',
    );
}

function cpc_docs_get_permission_options($component = 'members') {
    $base = array(
        'anyone' => __('Jeder', CPC2_TEXT_DOMAIN),
        'loggedin' => __('Angemeldete', CPC2_TEXT_DOMAIN),
        'author' => __('Nur Autor', CPC2_TEXT_DOMAIN),
    );

    if ($component === 'groups') {
        return $base + array(
            'group_members' => __('Gruppenmitglieder', CPC2_TEXT_DOMAIN),
            'group_adminmod' => __('Gruppen-Admins/Mods', CPC2_TEXT_DOMAIN),
        );
    }

    return $base;
}

function cpc_docs_normalize_permission($permission, $component = 'members', $field = 'edit') {
    $permission = sanitize_key((string)$permission);
    $options = cpc_docs_get_permission_options($component);
    $defaults = cpc_docs_get_permission_defaults($component);
    $fallback = isset($defaults[$field]) ? $defaults[$field] : (isset($defaults['edit']) ? $defaults['edit'] : 'author');
    return isset($options[$permission]) ? $permission : $fallback;
}

function cpc_docs_get_doc_permission($doc_id, $field, $component = '') {
    $doc_id = (int)$doc_id;
    $field = sanitize_key((string)$field);
    $allowed_fields = array('edit', 'manage', 'read_comments', 'post_comments', 'view_history');
    if ($doc_id <= 0 || !in_array($field, $allowed_fields, true)) {
        return '';
    }

    if ($component === '') {
        $component = cpc_docs_get_component($doc_id);
    }

    $stored = sanitize_key((string)get_post_meta($doc_id, 'cpc_doc_perm_'.$field, true));
    if ($stored !== '') {
        return cpc_docs_normalize_permission($stored, $component, $field);
    }

    $defaults = cpc_docs_get_permission_defaults($component);
    return isset($defaults[$field]) ? $defaults[$field] : '';
}

function cpc_docs_set_doc_permissions($doc_id, $component, $permissions = array()) {
    $doc_id = (int)$doc_id;
    if ($doc_id <= 0) {
        return;
    }

    $fields = array('edit', 'manage', 'read_comments', 'post_comments', 'view_history');
    $defaults = cpc_docs_get_permission_defaults($component);

    foreach ($fields as $field) {
        $value = isset($permissions[$field]) ? $permissions[$field] : (isset($defaults[$field]) ? $defaults[$field] : 'author');
        update_post_meta($doc_id, 'cpc_doc_perm_'.$field, cpc_docs_normalize_permission($value, $component, $field));
    }
}

function cpc_docs_user_matches_permission($doc_id, $permission, $user_id = 0) {
    $doc = get_post((int)$doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    $permission = sanitize_key((string)$permission);
    $component = cpc_docs_get_component($doc_id);
    $component_id = cpc_docs_get_component_id($doc_id);

    if ($permission === 'anyone') {
        return true;
    }

    if ($permission === 'loggedin') {
        return (bool)$user_id;
    }

    if ($permission === 'author') {
        return (int)$doc->post_author === (int)$user_id;
    }

    if ($component === 'groups' && $component_id > 0) {
        if ($permission === 'group_members') {
            return function_exists('cpc_is_group_member') && cpc_is_group_member($user_id, $component_id);
        }

        if ($permission === 'group_adminmod') {
            $is_admin = function_exists('cpc_is_group_admin') && cpc_is_group_admin($user_id, $component_id);
            $is_mod = function_exists('cpc_is_group_moderator') && cpc_is_group_moderator($user_id, $component_id);
            return $is_admin || $is_mod;
        }
    }

    return false;
}

function cpc_docs_user_can_create_for_context($component, $component_id, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id || !is_user_logged_in()) {
        return false;
    }

    if (current_user_can('manage_options')) {
        return true;
    }

    $component = sanitize_key((string)$component);
    $component_id = (int)$component_id;

    if ($component === 'members') {
        return $component_id > 0 && $component_id === (int)$user_id;
    }

    if ($component === 'groups' && $component_id > 0 && function_exists('cpc_is_group_member')) {
        return cpc_is_group_member($user_id, $component_id);
    }

    return false;
}

function cpc_docs_user_can_manage_doc($doc_id, $user_id = 0) {
    $doc = get_post((int)$doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    if (current_user_can('manage_options') || (int)$doc->post_author === (int)$user_id) {
        return true;
    }

    $manage_permission = cpc_docs_get_doc_permission($doc->ID, 'manage', cpc_docs_get_component($doc->ID));
    return cpc_docs_user_matches_permission($doc->ID, $manage_permission, $user_id);
}

function cpc_docs_user_can_edit_doc($doc_id, $user_id = 0) {
    $doc = get_post((int)$doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (current_user_can('manage_options') || (int)$doc->post_author === (int)$user_id) {
        return true;
    }

    $edit_permission = cpc_docs_get_doc_permission($doc->ID, 'edit', cpc_docs_get_component($doc->ID));
    return cpc_docs_user_matches_permission($doc->ID, $edit_permission, $user_id);
}

function cpc_docs_user_can_view_doc($doc_id, $user_id = 0) {
    $doc = get_post((int)$doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (cpc_docs_user_can_manage_doc($doc_id, $user_id)) {
        return true;
    }

    $component = cpc_docs_get_component($doc_id);
    $component_id = cpc_docs_get_component_id($doc_id);
    $status = cpc_docs_get_status($doc_id);

    if ($component === 'groups' && $component_id > 0) {
        if (function_exists('cpc_can_view_group') && !cpc_can_view_group($user_id, $component_id)) {
            return false;
        }

        if ($status === 'public') {
            return true;
        }

        if ($status === 'members' || $status === 'private') {
            return function_exists('cpc_is_group_member') && cpc_is_group_member($user_id, $component_id);
        }

        if ($status === 'admins') {
            return function_exists('cpc_is_group_admin') && cpc_is_group_admin($user_id, $component_id);
        }

        return false;
    }

    if ($status === 'public') {
        return true;
    }

    if ($status === 'loggedin') {
        return is_user_logged_in();
    }

    if ($status === 'private') {
        return (int)$doc->post_author === (int)$user_id;
    }

    return false;
}

function cpc_docs_user_can_view_history($doc_id, $user_id = 0) {
    if (!cpc_docs_user_can_view_doc($doc_id, $user_id)) {
        return false;
    }

    $doc = get_post((int)$doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (current_user_can('manage_options') || (int)$doc->post_author === (int)$user_id) {
        return true;
    }

    $perm = cpc_docs_get_doc_permission($doc_id, 'view_history', cpc_docs_get_component($doc_id));
    return cpc_docs_user_matches_permission($doc_id, $perm, $user_id);
}

function cpc_docs_user_can_read_comments($doc_id, $user_id = 0) {
    if (!cpc_docs_user_can_view_doc($doc_id, $user_id)) {
        return false;
    }

    $doc = get_post((int)$doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (current_user_can('manage_options') || (int)$doc->post_author === (int)$user_id) {
        return true;
    }

    $perm = cpc_docs_get_doc_permission($doc_id, 'read_comments', cpc_docs_get_component($doc_id));
    return cpc_docs_user_matches_permission($doc_id, $perm, $user_id);
}

function cpc_docs_user_can_post_comments($doc_id, $user_id = 0) {
    if (!cpc_docs_user_can_read_comments($doc_id, $user_id)) {
        return false;
    }

    $doc = get_post((int)$doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (current_user_can('manage_options') || (int)$doc->post_author === (int)$user_id) {
        return true;
    }

    $perm = cpc_docs_get_doc_permission($doc_id, 'post_comments', cpc_docs_get_component($doc_id));
    return cpc_docs_user_matches_permission($doc_id, $perm, $user_id);
}

function cpc_docs_get_docs($args = array()) {
    $defaults = array(
        'post_type' => 'cpc_doc',
        'post_status' => 'publish',
        'posts_per_page' => 20,
        'orderby' => 'modified',
        'order' => 'DESC',
    );

    $query_args = wp_parse_args($args, $defaults);
    $meta_query = array();

    if (!empty($query_args['component'])) {
        $meta_query[] = array(
            'key' => 'cpc_doc_component',
            'value' => sanitize_text_field($query_args['component']),
        );
        unset($query_args['component']);
    }

    if (!empty($query_args['component_id'])) {
        $meta_query[] = array(
            'key' => 'cpc_doc_component_id',
            'value' => (int)$query_args['component_id'],
        );
        unset($query_args['component_id']);
    }

    if (!empty($query_args['doc_status'])) {
        $meta_query[] = array(
            'key' => 'cpc_doc_status',
            'value' => sanitize_key($query_args['doc_status']),
        );
        unset($query_args['doc_status']);
    }

    if ($meta_query) {
        $query_args['meta_query'] = $meta_query;
    }

    return get_posts($query_args);
}

function cpc_docs_build_safe_redirect() {
    $redirect = isset($_POST['cpc_docs_redirect']) ? trim(wp_unslash($_POST['cpc_docs_redirect'])) : '';
    return cpc_normalize_frontend_redirect($redirect, cpc_curPageURL());
}

function cpc_docs_set_doc_meta($doc_id, $component, $component_id, $status, $permissions = array()) {
    update_post_meta($doc_id, 'cpc_doc_component', sanitize_key($component));
    update_post_meta($doc_id, 'cpc_doc_component_id', (int)$component_id);
    update_post_meta($doc_id, 'cpc_doc_status', cpc_docs_normalize_status($status, $component));
    cpc_docs_set_doc_permissions($doc_id, $component, is_array($permissions) ? $permissions : array());
}

function cpc_docs_get_doc_attachments($doc_id) {
    $attachments = get_children(array(
        'post_parent' => (int)$doc_id,
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'orderby' => 'menu_order ID',
        'order' => 'ASC',
    ));

    return $attachments ? array_values($attachments) : array();
}

function cpc_docs_get_edit_link($doc_id, $base_url = '') {
    $doc_id = (int)$doc_id;
    if ($doc_id <= 0) {
        return '';
    }

    $target = $base_url !== '' ? $base_url : get_permalink($doc_id);

    return add_query_arg(array(
        'cpc_docs_action' => 'edit',
        'cpc_docs_doc_id' => $doc_id,
    ), $target);
}

function cpc_docs_get_history_link($doc_id, $base_url = '') {
    $doc_id = (int)$doc_id;
    if ($doc_id <= 0) {
        return '';
    }

    $target = $base_url !== '' ? $base_url : get_permalink($doc_id);

    return add_query_arg(array(
        'cpc_docs_action' => 'history',
        'cpc_docs_doc_id' => $doc_id,
    ), $target);
}

function cpc_docs_get_history_compare_link($doc_id, $from_id, $to_id, $base_url = '') {
    $doc_id = (int)$doc_id;
    $from_id = (int)$from_id;
    $to_id = (int)$to_id;
    if ($doc_id <= 0 || $from_id <= 0 || $to_id <= 0) {
        return '';
    }

    $target = $base_url !== '' ? $base_url : get_permalink($doc_id);

    return add_query_arg(array(
        'cpc_docs_action' => 'history',
        'cpc_docs_doc_id' => $doc_id,
        'cpc_docs_compare_from' => $from_id,
        'cpc_docs_compare_to' => $to_id,
    ), $target);
}

function cpc_docs_get_lock_timeout() {
    return max(60, (int)apply_filters('cpc_docs_edit_lock_timeout', 30 * MINUTE_IN_SECONDS));
}

function cpc_docs_get_edit_lock($doc_id) {
    $lock = get_post_meta((int)$doc_id, 'cpc_doc_edit_lock', true);
    if (!is_array($lock) || empty($lock['user_id']) || empty($lock['time'])) {
        return array();
    }

    return array(
        'user_id' => (int)$lock['user_id'],
        'time' => (int)$lock['time'],
    );
}

function cpc_docs_is_doc_locked($doc_id, $user_id = 0) {
    $doc_id = (int)$doc_id;
    $user_id = (int)$user_id;
    if ($doc_id <= 0) {
        return false;
    }

    if ($user_id <= 0) {
        $user_id = get_current_user_id();
    }

    $lock = cpc_docs_get_edit_lock($doc_id);
    if (empty($lock)) {
        return false;
    }

    if ((time() - (int)$lock['time']) > cpc_docs_get_lock_timeout()) {
        delete_post_meta($doc_id, 'cpc_doc_edit_lock');
        return false;
    }

    return (int)$lock['user_id'] !== $user_id;
}

function cpc_docs_get_doc_locker_name($doc_id) {
    $lock = cpc_docs_get_edit_lock((int)$doc_id);
    if (empty($lock['user_id'])) {
        return '';
    }

    $user = get_user_by('id', (int)$lock['user_id']);
    return $user ? $user->display_name : '';
}

function cpc_docs_acquire_edit_lock($doc_id, $user_id = 0) {
    $doc_id = (int)$doc_id;
    if ($doc_id <= 0) {
        return false;
    }

    if ($user_id <= 0) {
        $user_id = get_current_user_id();
    }

    if ($user_id <= 0) {
        return false;
    }

    if (cpc_docs_is_doc_locked($doc_id, $user_id)) {
        return false;
    }

    update_post_meta($doc_id, 'cpc_doc_edit_lock', array(
        'user_id' => (int)$user_id,
        'time' => time(),
    ));

    return true;
}

function cpc_docs_release_edit_lock($doc_id, $user_id = 0, $force = false) {
    $doc_id = (int)$doc_id;
    if ($doc_id <= 0) {
        return false;
    }

    $lock = cpc_docs_get_edit_lock($doc_id);
    if (empty($lock)) {
        return true;
    }

    if ($force) {
        delete_post_meta($doc_id, 'cpc_doc_edit_lock');
        return true;
    }

    if ($user_id <= 0) {
        $user_id = get_current_user_id();
    }

    if ((int)$lock['user_id'] !== (int)$user_id) {
        return false;
    }

    delete_post_meta($doc_id, 'cpc_doc_edit_lock');
    return true;
}

function cpc_docs_get_parent_options($component, $component_id, $exclude_doc_id = 0) {
    $component = sanitize_key((string)$component);
    $component_id = (int)$component_id;
    $exclude_doc_id = (int)$exclude_doc_id;

    $args = array(
        'post_type' => 'cpc_doc',
        'post_status' => 'publish',
        'posts_per_page' => 200,
        'orderby' => 'title',
        'order' => 'ASC',
        'component' => $component,
        'component_id' => $component_id,
    );

    $docs = cpc_docs_get_docs($args);
    if (empty($docs)) {
        return array();
    }

    if ($exclude_doc_id > 0) {
        $docs = array_filter($docs, function($doc) use ($exclude_doc_id) {
            return (int)$doc->ID !== $exclude_doc_id;
        });
    }

    return array_values($docs);
}

function cpc_docs_doc_matches_context($doc, $component, $component_id) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return false;
    }

    return cpc_docs_get_component($doc->ID) === sanitize_key((string)$component)
        && cpc_docs_get_component_id($doc->ID) === (int)$component_id;
}

function cpc_docs_get_ancestor_ids($doc_id) {
    $doc_id = (int)$doc_id;
    if ($doc_id <= 0) {
        return array();
    }

    $ancestor_ids = array();
    $seen = array($doc_id => true);
    $parent_id = (int)wp_get_post_parent_id($doc_id);

    while ($parent_id > 0 && empty($seen[$parent_id])) {
        $parent = get_post($parent_id);
        if (!$parent || $parent->post_type !== 'cpc_doc') {
            break;
        }

        $ancestor_ids[] = $parent_id;
        $seen[$parent_id] = true;
        $parent_id = (int)$parent->post_parent;
    }

    return array_reverse($ancestor_ids);
}

function cpc_docs_get_child_docs($doc_id, $args = array()) {
    $doc_id = (int)$doc_id;
    if ($doc_id <= 0) {
        return array();
    }

    $defaults = array(
        'post_type' => 'cpc_doc',
        'post_status' => 'publish',
        'posts_per_page' => 200,
        'post_parent' => $doc_id,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
    );

    return get_posts(wp_parse_args($args, $defaults));
}

function cpc_docs_validate_parent_id($parent_id, $component, $component_id, $doc_id = 0) {
    $parent_id = (int)$parent_id;
    $doc_id = (int)$doc_id;
    $component = sanitize_key((string)$component);
    $component_id = (int)$component_id;

    if ($parent_id <= 0) {
        return 0;
    }

    if ($doc_id > 0 && $parent_id === $doc_id) {
        return 0;
    }

    $parent = get_post($parent_id);
    if (!$parent || $parent->post_type !== 'cpc_doc' || !cpc_docs_doc_matches_context($parent, $component, $component_id)) {
        return 0;
    }

    if ($doc_id > 0) {
        $ancestor_ids = cpc_docs_get_ancestor_ids($parent_id);
        if (in_array($doc_id, $ancestor_ids, true)) {
            return 0;
        }
    }

    return $parent_id;
}

function cpc_docs_get_sibling_docs($doc_id, $args = array()) {
    $doc = get_post((int)$doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return array();
    }

    $parent_id = (int)$doc->post_parent;
    $defaults = array(
        'post_type' => 'cpc_doc',
        'post_status' => 'publish',
        'posts_per_page' => 500,
        'post_parent' => $parent_id,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
    );

    return get_posts(wp_parse_args($args, $defaults));
}

function cpc_docs_get_prev_sibling($doc_id) {
    $doc = get_post((int)$doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return null;
    }

    $siblings = cpc_docs_get_sibling_docs($doc_id);
    $previous = null;

    foreach ($siblings as $sibling) {
        if ((int)$sibling->ID === (int)$doc_id) {
            return $previous;
        }
        if (cpc_docs_user_can_view_doc($sibling->ID)) {
            $previous = $sibling;
        }
    }

    return null;
}

function cpc_docs_get_next_sibling($doc_id) {
    $doc = get_post((int)$doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return null;
    }

    $siblings = cpc_docs_get_sibling_docs($doc_id);
    $found = false;

    foreach ($siblings as $sibling) {
        if ($found && cpc_docs_user_can_view_doc($sibling->ID)) {
            return $sibling;
        }
        if ((int)$sibling->ID === (int)$doc_id) {
            $found = true;
        }
    }

    return null;
}

function cpc_docs_is_folder($doc_id) {
    $doc = get_post((int)$doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return false;
    }

    // A document is considered a folder if it has no content (empty post_content)
    return empty(trim($doc->post_content));
}

function cpc_docs_set_doc_tags($doc_id, $tags_csv) {
    $doc_id = (int)$doc_id;
    if ($doc_id <= 0) {
        return;
    }

    $tags_csv = trim((string)$tags_csv);
    if ($tags_csv === '') {
        wp_set_post_terms($doc_id, array(), 'cpc_doc_tag', false);
        return;
    }

    $parts = array_map('trim', explode(',', $tags_csv));
    $parts = array_filter($parts, function($tag) {
        return $tag !== '';
    });

    if (empty($parts)) {
        wp_set_post_terms($doc_id, array(), 'cpc_doc_tag', false);
        return;
    }

    wp_set_post_terms($doc_id, array_values($parts), 'cpc_doc_tag', false);
}

function cpc_docs_get_doc_tags_string($doc_id) {
    $terms = wp_get_post_terms((int)$doc_id, 'cpc_doc_tag', array('fields' => 'names'));
    if (is_wp_error($terms) || empty($terms)) {
        return '';
    }

    return implode(', ', $terms);
}

function cpc_docs_upload_files_to_doc($doc_id, $field_name = 'cpc_docs_attachments', $author_id = 0) {
    $doc_id = (int)$doc_id;
    $author_id = (int)$author_id;
    if ($doc_id <= 0 || empty($_FILES[$field_name]) || !is_array($_FILES[$field_name])) {
        return array('created' => 0, 'failed' => 0);
    }

    if (!function_exists('wp_handle_upload')) {
        require_once ABSPATH.'wp-admin/includes/file.php';
    }
    if (!function_exists('wp_generate_attachment_metadata')) {
        require_once ABSPATH.'wp-admin/includes/image.php';
    }

    $uploads = wp_upload_dir();
    $target_dir = trailingslashit($uploads['basedir']).'cpc-docs/'.(int)$doc_id;
    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);
    }

    $created = 0;
    $failed = 0;

    $files = $_FILES[$field_name];
    $count = isset($files['name']) && is_array($files['name']) ? count($files['name']) : 0;
    if ($count <= 0) {
        return array('created' => 0, 'failed' => 0);
    }

    for ($i = 0; $i < $count; $i++) {
        $name = isset($files['name'][$i]) ? (string)$files['name'][$i] : '';
        $tmp_name = isset($files['tmp_name'][$i]) ? (string)$files['tmp_name'][$i] : '';
        $error = isset($files['error'][$i]) ? (int)$files['error'][$i] : UPLOAD_ERR_NO_FILE;

        if ($name === '' || $tmp_name === '' || $error === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if ($error !== UPLOAD_ERR_OK) {
            $failed++;
            continue;
        }

        $filename = wp_unique_filename($target_dir, wp_basename($name));
        $target_path = trailingslashit($target_dir).$filename;

        if (!@move_uploaded_file($tmp_name, $target_path)) {
            if (!@copy($tmp_name, $target_path)) {
                $failed++;
                continue;
            }
        }

        $mime = wp_check_filetype($filename, null);
        $attachment_id = wp_insert_attachment(array(
            'post_mime_type' => !empty($mime['type']) ? $mime['type'] : 'application/octet-stream',
            'post_title' => sanitize_text_field(pathinfo($filename, PATHINFO_FILENAME)),
            'post_status' => 'inherit',
            'post_parent' => $doc_id,
            'post_author' => $author_id > 0 ? $author_id : get_current_user_id(),
        ), $target_path, $doc_id, true);

        if (is_wp_error($attachment_id) || !$attachment_id) {
            @unlink($target_path);
            $failed++;
            continue;
        }

        update_attached_file($attachment_id, $target_path);
        $metadata = wp_generate_attachment_metadata($attachment_id, $target_path);
        if (!empty($metadata)) {
            wp_update_attachment_metadata($attachment_id, $metadata);
        }

        $created++;
    }

    return array('created' => $created, 'failed' => $failed);
}

function cpc_docs_delete_attachment($attachment_id, $doc_id = 0, $user_id = 0) {
    $attachment_id = (int)$attachment_id;
    $doc_id = (int)$doc_id;
    $user_id = (int)$user_id;

    $attachment = get_post($attachment_id);
    if (!$attachment || $attachment->post_type !== 'attachment') {
        return false;
    }

    if ($doc_id <= 0) {
        $doc_id = (int)$attachment->post_parent;
    }

    if ($doc_id <= 0 || (int)$attachment->post_parent !== $doc_id) {
        return false;
    }

    if (!cpc_docs_user_can_manage_doc($doc_id, $user_id)) {
        return false;
    }

    return (bool)wp_delete_attachment($attachment_id, true);
}

function cpc_docs_handle_frontend_requests() {
    if (!cpc_docs_is_enabled() || empty($_POST['cpc_docs_action'])) {
        return;
    }

    $action = sanitize_key(wp_unslash($_POST['cpc_docs_action']));
    $redirect = cpc_docs_build_safe_redirect();

    if (!is_user_logged_in()) {
        wp_safe_redirect(add_query_arg('cpc_docs_notice', 'denied', $redirect));
        exit;
    }

    $nonce = isset($_POST['cpc_docs_nonce']) ? sanitize_text_field(wp_unslash($_POST['cpc_docs_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'cpc_docs_frontend_action')) {
        wp_safe_redirect(add_query_arg('cpc_docs_notice', 'invalid', $redirect));
        exit;
    }

    if ($action === 'folder_create') {
        $component = isset($_POST['cpc_docs_component']) ? sanitize_key(wp_unslash($_POST['cpc_docs_component'])) : 'members';
        $component_id = isset($_POST['cpc_docs_component_id']) ? (int)$_POST['cpc_docs_component_id'] : 0;
        $title = isset($_POST['cpc_docs_folder_title']) ? sanitize_text_field(wp_unslash($_POST['cpc_docs_folder_title'])) : '';
        $status = isset($_POST['cpc_docs_status']) ? sanitize_key(wp_unslash($_POST['cpc_docs_status'])) : 'public';
        $parent_id = isset($_POST['cpc_docs_parent_id']) ? (int)$_POST['cpc_docs_parent_id'] : 0;

        if (!cpc_docs_user_can_create_for_context($component, $component_id)) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'denied', $redirect));
            exit;
        }

        if ($title === '') {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'invalid', $redirect));
            exit;
        }

        $parent_id = cpc_docs_validate_parent_id($parent_id, $component, $component_id, 0);

        $doc_id = wp_insert_post(array(
            'post_type' => 'cpc_doc',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
            'post_title' => $title,
            'post_content' => '',
            'comment_status' => 'open',
            'post_parent' => $parent_id,
        ), true);

        if (is_wp_error($doc_id) || !$doc_id) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'failed', $redirect));
            exit;
        }

        cpc_docs_set_doc_meta($doc_id, $component, $component_id, $status);

        wp_safe_redirect(add_query_arg(array('cpc_docs_notice' => 'created', 'cpc_docs_doc_id' => $doc_id), $redirect));
        exit;
    }

    if ($action === 'folder_rename') {
        $folder_id = isset($_POST['cpc_docs_folder_id']) ? (int)$_POST['cpc_docs_folder_id'] : 0;
        $new_title = isset($_POST['cpc_docs_folder_new_title']) ? sanitize_text_field(wp_unslash($_POST['cpc_docs_folder_new_title'])) : '';
        $folder = get_post($folder_id);

        if (!$folder || $folder->post_type !== 'cpc_doc' || !cpc_docs_is_folder($folder_id) || !cpc_docs_user_can_manage_doc($folder_id)) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'denied', $redirect));
            exit;
        }

        if ($new_title === '') {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'invalid', $redirect));
            exit;
        }

        wp_update_post(array(
            'ID' => $folder_id,
            'post_title' => $new_title,
        ));

        wp_safe_redirect(add_query_arg(array('cpc_docs_notice' => 'updated', 'cpc_docs_doc_id' => $folder_id), $redirect));
        exit;
    }

    if ($action === 'folder_delete') {
        $folder_id = isset($_POST['cpc_docs_folder_id']) ? (int)$_POST['cpc_docs_folder_id'] : 0;
        $folder = get_post($folder_id);

        if (!$folder || $folder->post_type !== 'cpc_doc' || !cpc_docs_is_folder($folder_id) || !cpc_docs_user_can_manage_doc($folder_id)) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'denied', $redirect));
            exit;
        }

        $children = get_posts(array(
            'post_type' => 'cpc_doc',
            'post_parent' => $folder_id,
            'post_status' => array('publish', 'draft', 'pending', 'private'),
            'numberposts' => -1,
            'fields' => 'ids',
            'no_found_rows' => true,
        ));

        if (!empty($children)) {
            $new_parent = (int)$folder->post_parent;
            foreach ($children as $child_id) {
                wp_update_post(array(
                    'ID' => (int)$child_id,
                    'post_parent' => $new_parent,
                ));
            }
        }

        cpc_docs_release_edit_lock($folder_id, get_current_user_id(), true);
        wp_delete_post($folder_id, true);
        wp_safe_redirect(add_query_arg('cpc_docs_notice', 'deleted', $redirect));
        exit;
    }

    if ($action === 'create_doc') {
        $component = isset($_POST['cpc_docs_component']) ? sanitize_key(wp_unslash($_POST['cpc_docs_component'])) : 'members';
        $component_id = isset($_POST['cpc_docs_component_id']) ? (int)$_POST['cpc_docs_component_id'] : 0;
        $title = isset($_POST['cpc_docs_title']) ? sanitize_text_field(wp_unslash($_POST['cpc_docs_title'])) : '';
        $content = isset($_POST['cpc_docs_content']) ? wp_kses_post(wp_unslash($_POST['cpc_docs_content'])) : '';
        $status = isset($_POST['cpc_docs_status']) ? sanitize_key(wp_unslash($_POST['cpc_docs_status'])) : 'public';

        $parent_id = isset($_POST['cpc_docs_parent_id']) ? (int)$_POST['cpc_docs_parent_id'] : 0;
        $tags = isset($_POST['cpc_docs_tags']) ? sanitize_text_field(wp_unslash($_POST['cpc_docs_tags'])) : '';
        $permissions = array(
            'edit' => isset($_POST['cpc_docs_perm_edit']) ? sanitize_key(wp_unslash($_POST['cpc_docs_perm_edit'])) : '',
            'manage' => isset($_POST['cpc_docs_perm_manage']) ? sanitize_key(wp_unslash($_POST['cpc_docs_perm_manage'])) : '',
            'read_comments' => isset($_POST['cpc_docs_perm_read_comments']) ? sanitize_key(wp_unslash($_POST['cpc_docs_perm_read_comments'])) : '',
            'post_comments' => isset($_POST['cpc_docs_perm_post_comments']) ? sanitize_key(wp_unslash($_POST['cpc_docs_perm_post_comments'])) : '',
            'view_history' => isset($_POST['cpc_docs_perm_view_history']) ? sanitize_key(wp_unslash($_POST['cpc_docs_perm_view_history'])) : '',
        );

        if (!cpc_docs_user_can_create_for_context($component, $component_id)) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'denied', $redirect));
            exit;
        }

        if ($title === '' || $content === '') {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'invalid', $redirect));
            exit;
        }

        $parent_id = cpc_docs_validate_parent_id($parent_id, $component, $component_id, 0);

        $doc_id = wp_insert_post(array(
            'post_type' => 'cpc_doc',
            'post_status' => 'publish',
            'post_author' => get_current_user_id(),
            'post_title' => $title,
            'post_content' => $content,
            'comment_status' => 'open',
            'post_parent' => $parent_id,
        ), true);

        if (is_wp_error($doc_id) || !$doc_id) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'failed', $redirect));
            exit;
        }

        cpc_docs_set_doc_meta($doc_id, $component, $component_id, $status, $permissions);
        cpc_docs_set_doc_tags($doc_id, $tags);
        if (cpc_docs_enable_attachments()) {
            cpc_docs_upload_files_to_doc($doc_id, 'cpc_docs_attachments', get_current_user_id());
        }

        wp_safe_redirect(add_query_arg(array('cpc_docs_notice' => 'created', 'cpc_docs_doc_id' => $doc_id), $redirect));
        exit;
    }

    if ($action === 'update_doc') {
        $doc_id = isset($_POST['cpc_docs_doc_id']) ? (int)$_POST['cpc_docs_doc_id'] : 0;
        $doc = get_post($doc_id);
        if (!$doc || $doc->post_type !== 'cpc_doc' || !cpc_docs_user_can_edit_doc($doc_id)) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'denied', $redirect));
            exit;
        }

        if (cpc_docs_is_doc_locked($doc_id, get_current_user_id())) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'locked', get_permalink($doc_id)));
            exit;
        }

        $title = isset($_POST['cpc_docs_title']) ? sanitize_text_field(wp_unslash($_POST['cpc_docs_title'])) : '';
        $content = isset($_POST['cpc_docs_content']) ? wp_kses_post(wp_unslash($_POST['cpc_docs_content'])) : '';
        $status = isset($_POST['cpc_docs_status']) ? sanitize_key(wp_unslash($_POST['cpc_docs_status'])) : cpc_docs_get_status($doc_id);
        $component = cpc_docs_get_component($doc_id);
        $component_id = cpc_docs_get_component_id($doc_id);
        $parent_id = isset($_POST['cpc_docs_parent_id']) ? (int)$_POST['cpc_docs_parent_id'] : 0;
        $tags = isset($_POST['cpc_docs_tags']) ? sanitize_text_field(wp_unslash($_POST['cpc_docs_tags'])) : '';
        $doc_is_folder = cpc_docs_is_folder($doc_id);
        $permissions = array(
            'edit' => isset($_POST['cpc_docs_perm_edit']) ? sanitize_key(wp_unslash($_POST['cpc_docs_perm_edit'])) : cpc_docs_get_doc_permission($doc_id, 'edit', $component),
            'manage' => isset($_POST['cpc_docs_perm_manage']) ? sanitize_key(wp_unslash($_POST['cpc_docs_perm_manage'])) : cpc_docs_get_doc_permission($doc_id, 'manage', $component),
            'read_comments' => isset($_POST['cpc_docs_perm_read_comments']) ? sanitize_key(wp_unslash($_POST['cpc_docs_perm_read_comments'])) : cpc_docs_get_doc_permission($doc_id, 'read_comments', $component),
            'post_comments' => isset($_POST['cpc_docs_perm_post_comments']) ? sanitize_key(wp_unslash($_POST['cpc_docs_perm_post_comments'])) : cpc_docs_get_doc_permission($doc_id, 'post_comments', $component),
            'view_history' => isset($_POST['cpc_docs_perm_view_history']) ? sanitize_key(wp_unslash($_POST['cpc_docs_perm_view_history'])) : cpc_docs_get_doc_permission($doc_id, 'view_history', $component),
        );

        if ($title === '' || (!$doc_is_folder && $content === '')) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'invalid', $redirect));
            exit;
        }

        if ($doc_is_folder) {
            $content = '';
        }

        $parent_id = cpc_docs_validate_parent_id($parent_id, $component, $component_id, $doc_id);

        wp_update_post(array(
            'ID' => $doc_id,
            'post_title' => $title,
            'post_content' => $content,
            'post_parent' => $parent_id,
        ));

        cpc_docs_set_doc_meta($doc_id, $component, $component_id, $status, $permissions);
        cpc_docs_set_doc_tags($doc_id, $tags);
        if (cpc_docs_enable_attachments()) {
            cpc_docs_upload_files_to_doc($doc_id, 'cpc_docs_attachments', (int)$doc->post_author);
        }
        cpc_docs_release_edit_lock($doc_id, get_current_user_id(), false);

        wp_safe_redirect(add_query_arg(array('cpc_docs_notice' => 'updated', 'cpc_docs_doc_id' => $doc_id), get_permalink($doc_id)));
        exit;
    }

    if ($action === 'unlock_doc') {
        $doc_id = isset($_POST['cpc_docs_doc_id']) ? (int)$_POST['cpc_docs_doc_id'] : 0;
        if (!$doc_id) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'invalid', $redirect));
            exit;
        }

        if (!current_user_can('manage_options') && !cpc_docs_user_can_manage_doc($doc_id)) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'denied', get_permalink($doc_id)));
            exit;
        }

        cpc_docs_release_edit_lock($doc_id, get_current_user_id(), true);
        wp_safe_redirect(add_query_arg('cpc_docs_notice', 'unlocked', get_permalink($doc_id)));
        exit;
    }

    if ($action === 'delete_attachment') {
        $doc_id = isset($_POST['cpc_docs_doc_id']) ? (int)$_POST['cpc_docs_doc_id'] : 0;
        $attachment_id = isset($_POST['cpc_docs_attachment_id']) ? (int)$_POST['cpc_docs_attachment_id'] : 0;
        if (!$doc_id || !$attachment_id || !cpc_docs_delete_attachment($attachment_id, $doc_id)) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'denied', $redirect));
            exit;
        }

        wp_safe_redirect(add_query_arg(array('cpc_docs_notice' => 'attachment_deleted', 'cpc_docs_doc_id' => $doc_id), get_permalink($doc_id)));
        exit;
    }

    if ($action === 'delete_doc') {
        $doc_id = isset($_POST['cpc_docs_doc_id']) ? (int)$_POST['cpc_docs_doc_id'] : 0;
        $doc = get_post($doc_id);

        if (!$doc_id || !$doc || $doc->post_type !== 'cpc_doc' || !cpc_docs_user_can_manage_doc($doc_id)) {
            wp_safe_redirect(add_query_arg('cpc_docs_notice', 'denied', $redirect));
            exit;
        }

        if (cpc_docs_is_folder($doc_id)) {
            $children = get_posts(array(
                'post_type' => 'cpc_doc',
                'post_parent' => $doc_id,
                'post_status' => array('publish', 'draft', 'pending', 'private'),
                'numberposts' => -1,
                'fields' => 'ids',
                'no_found_rows' => true,
            ));

            if (!empty($children)) {
                $new_parent = (int)$doc->post_parent;
                foreach ($children as $child_id) {
                    wp_update_post(array(
                        'ID' => (int)$child_id,
                        'post_parent' => $new_parent,
                    ));
                }
            }
        }

        cpc_docs_release_edit_lock($doc_id, get_current_user_id(), true);
        wp_delete_post($doc_id, true);
        wp_safe_redirect(add_query_arg('cpc_docs_notice', 'deleted', $redirect));
        exit;
    }
}

function cpc_docs_load_folder_contents_ajax() {
    if (!cpc_docs_is_enabled() || empty($_GET['cpc_docs_folder'])) {
        wp_send_json_error(array('message' => 'Invalid request'));
    }

    $folder_id = isset($_GET['cpc_docs_folder']) ? (int)$_GET['cpc_docs_folder'] : 0;
    $component = isset($_GET['cpc_docs_component']) ? sanitize_key(wp_unslash($_GET['cpc_docs_component'])) : '';
    $status = isset($_GET['cpc_docs_status']) ? sanitize_key(wp_unslash($_GET['cpc_docs_status'])) : '';
    $perm_edit = isset($_GET['cpc_docs_perm_edit']) ? sanitize_key(wp_unslash($_GET['cpc_docs_perm_edit'])) : '';
    $perm_history = isset($_GET['cpc_docs_perm_history']) ? sanitize_key(wp_unslash($_GET['cpc_docs_perm_history'])) : '';
    $search = isset($_GET['cpc_docs_q']) ? sanitize_text_field(wp_unslash($_GET['cpc_docs_q'])) : '';

    if (is_admin()) {
        wp_send_json_error(array('message' => 'Not allowed'));
    }

    if (!function_exists('cpc_docs_render_docs_table')) {
        wp_send_json_error(array('message' => 'Function not found'));
    }

    $docs = cpc_docs_get_docs(array(
        'posts_per_page' => 250,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
    ));

    if ($component !== '') {
        $docs = array_filter($docs, function($doc) use ($component) {
            return cpc_docs_get_component($doc->ID) === $component;
        });
    }

    if ($status !== '') {
        $docs = array_filter($docs, function($doc) use ($status) {
            return cpc_docs_get_status($doc->ID) === $status;
        });
    }

    if ($search !== '') {
        $docs = array_filter($docs, function($doc) use ($search) {
            $search_lower = strtolower($search);
            return stripos($doc->post_title, $search_lower) !== false
                || stripos($doc->post_content, $search_lower) !== false;
        });
    }

    if ($perm_edit !== '') {
        $docs = array_filter($docs, function($doc) use ($perm_edit) {
            return cpc_docs_get_doc_permission($doc->ID, 'edit', cpc_docs_get_component($doc->ID)) === $perm_edit;
        });
    }

    if ($perm_history !== '') {
        $docs = array_filter($docs, function($doc) use ($perm_history) {
            return cpc_docs_get_doc_permission($doc->ID, 'view_history', cpc_docs_get_component($doc->ID)) === $perm_history;
        });
    }

    ob_start();

    $grouped = array();
    foreach ($docs as $doc) {
        $parent_id = (int)$doc->post_parent;
        if (!isset($grouped[$parent_id])) {
            $grouped[$parent_id] = array();
        }
        $grouped[$parent_id][] = $doc;
    }

    $current_items = isset($grouped[$folder_id]) ? $grouped[$folder_id] : array();

    if ($folder_id > 0) {
        $parent_folder_id = (int)wp_get_post_parent_id($folder_id);
        echo '<tr class="folder-row cpc_docs_up_row">';
        echo '<td class="attachment-clip-cell"></td>';
        echo '<td class="title-cell folder-row-name"><a class="up-one-folder" href="#"><span class="dashicons dashicons-category"></span>..</a></td>';
        echo '<td class="author-cell"></td>';
        echo '<td class="edited-date-cell"></td>';
        echo '</tr>';
    }

    $rendered = 0;
    $current_url = cpc_docs_get_current_request_url();
    foreach ($current_items as $doc) {
        if (!cpc_docs_user_can_view_doc($doc->ID)) {
            continue;
        }

        $child_count = isset($grouped[$doc->ID]) ? count($grouped[$doc->ID]) : 0;
        if ($child_count > 0) {
            $can_manage = cpc_docs_user_can_manage_doc($doc->ID);
            $can_edit = cpc_docs_user_can_edit_doc($doc->ID);
            $can_history = cpc_docs_user_can_view_history($doc->ID);
            $has_attachments = cpc_docs_enable_attachments() && !empty(cpc_docs_get_doc_attachments($doc->ID));

            echo '<tr class="folder-row cpc_docs_folder_row" data-folder-id="'.(int)$doc->ID.'">';
            echo '<td class="attachment-clip-cell">'.($has_attachments ? '<span class="dashicons dashicons-paperclip"></span>' : '').'</td>';
            echo '<td class="title-cell folder-row-name">';
            echo '<span class="cpc_docs_folder_title toggle-folder-link"><span class="dashicons dashicons-category"></span><a href="#">'.esc_html($doc->post_title).'</a></span>';
            echo '<div class="cpc_docs_folder_meta">'.sprintf(esc_html__('%d Unterdokumente', CPC2_TEXT_DOMAIN), (int)$child_count).'</div>';
            if (function_exists('cpc_docs_render_access_badges')) {
                echo cpc_docs_render_access_badges($doc->ID);
            }
            echo '<div class="row-actions">';
            echo '<a href="'.esc_url(get_permalink($doc)).'">'.esc_html__('Ansehen', CPC2_TEXT_DOMAIN).'</a>';
            if ($can_edit) {
                echo ' | <a href="'.esc_url(cpc_docs_get_edit_link($doc->ID)).'">'.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
            }
            if ($can_history) {
                echo ' | <a href="'.esc_url(cpc_docs_get_history_link($doc->ID)).'">'.esc_html__('Verlauf', CPC2_TEXT_DOMAIN).'</a>';
            }
            if ($can_manage) {
                echo ' | <a href="#cpc_docs_folder_manage_panel" class="cpc_docs_rename_folder_link" data-folder-id="'.(int)$doc->ID.'" data-folder-title="'.esc_attr($doc->post_title).'">'.esc_html__('Umbenennen', CPC2_TEXT_DOMAIN).'</a>';
                echo ' | <form method="post" class="cpc_docs_inline_form">';
                echo '<input type="hidden" name="cpc_docs_action" value="folder_delete" />';
                echo '<input type="hidden" name="cpc_docs_folder_id" value="'.(int)$doc->ID.'" />';
                echo '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
                echo '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url($current_url).'" />';
                echo '<button type="submit" class="cpc_docs_inline_button" onclick="return confirm(\''.esc_js(__('Ordner wirklich loeschen? Unterordner werden in den Parent verschoben.', CPC2_TEXT_DOMAIN)).'\');">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
                echo '</form>';
            }
            echo '</div>';
            echo '</td>';
            echo '<td class="author-cell">'.esc_html(get_the_author_meta('display_name', $doc->post_author)).'</td>';
            echo '<td class="edited-date-cell">'.esc_html(get_the_modified_date('', $doc)).'</td>';
            echo '</tr>';
        } else {
            $can_manage = cpc_docs_user_can_manage_doc($doc->ID);
            $can_edit = cpc_docs_user_can_edit_doc($doc->ID);
            $can_history = cpc_docs_user_can_view_history($doc->ID);
            $has_attachments = cpc_docs_enable_attachments() && !empty(cpc_docs_get_doc_attachments($doc->ID));

            echo '<tr>';
            echo '<td class="attachment-clip-cell">'.($has_attachments ? '<span class="dashicons dashicons-paperclip"></span>' : '').'</td>';
            echo '<td class="title-cell">';
            echo '<span class="cpc_docs_doc_title"><span class="dashicons dashicons-media-document"></span><a href="'.esc_url(get_permalink($doc)).'">'.esc_html($doc->post_title).'</a></span>';
            if (function_exists('cpc_docs_render_access_badges')) {
                echo cpc_docs_render_access_badges($doc->ID);
            }
            echo '<div class="row-actions">';
            echo '<a href="'.esc_url(get_permalink($doc)).'">'.esc_html__('Ansehen', CPC2_TEXT_DOMAIN).'</a>';
            if ($can_edit) {
                echo ' | <a href="'.esc_url(cpc_docs_get_edit_link($doc->ID)).'">'.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
            }
            if ($can_history) {
                echo ' | <a href="'.esc_url(cpc_docs_get_history_link($doc->ID)).'">'.esc_html__('Verlauf', CPC2_TEXT_DOMAIN).'</a>';
            }
            echo '</div>';
            echo '</td>';
            echo '<td class="author-cell">'.esc_html(get_the_author_meta('display_name', $doc->post_author)).'</td>';
            echo '<td class="edited-date-cell">'.esc_html(get_the_modified_date('', $doc)).'</td>';
            echo '</tr>';
        }

        $rendered++;
    }

    if ($rendered === 0) {
        echo '<tr class="no-docs-row"><td class="attachment-clip-cell"></td><td class="title-cell">'.esc_html__('Keine Dokumente gefunden.', CPC2_TEXT_DOMAIN).'</td><td class="author-cell"></td><td class="edited-date-cell"></td></tr>';
    }

    $html = ob_get_clean();

    wp_send_json_success(array('html' => $html));
}

function cpc_docs_notice_message($code) {
    $map = array(
        'created' => __('Dokument wurde erstellt.', CPC2_TEXT_DOMAIN),
        'updated' => __('Dokument wurde aktualisiert.', CPC2_TEXT_DOMAIN),
        'deleted' => __('Dokument wurde geloescht.', CPC2_TEXT_DOMAIN),
        'attachment_deleted' => __('Attachment wurde geloescht.', CPC2_TEXT_DOMAIN),
        'locked' => __('Dokument ist aktuell durch einen anderen Benutzer gesperrt.', CPC2_TEXT_DOMAIN),
        'unlocked' => __('Bearbeitungssperre wurde aufgehoben.', CPC2_TEXT_DOMAIN),
        'denied' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN),
        'invalid' => __('Ungueltige Anfrage.', CPC2_TEXT_DOMAIN),
        'failed' => __('Aktion fehlgeschlagen.', CPC2_TEXT_DOMAIN),
        'imported' => __('BuddyPress Docs wurden importiert.', CPC2_TEXT_DOMAIN),
    );

    return isset($map[$code]) ? $map[$code] : '';
}

function cpc_docs_filter_comments_open($open, $post_id) {
    $post = get_post((int)$post_id);
    if (!$post || $post->post_type !== 'cpc_doc') {
        return $open;
    }

    return cpc_docs_user_can_post_comments($post_id);
}
add_filter('comments_open', 'cpc_docs_filter_comments_open', 20, 2);

function cpc_docs_filter_comments_array($comments, $post_id) {
    $post = get_post((int)$post_id);
    if (!$post || $post->post_type !== 'cpc_doc') {
        return $comments;
    }

    if (!cpc_docs_user_can_read_comments($post_id)) {
        return array();
    }

    return $comments;
}
add_filter('comments_array', 'cpc_docs_filter_comments_array', 20, 2);

function cpc_docs_preprocess_comment($commentdata) {
    if (empty($commentdata['comment_post_ID'])) {
        return $commentdata;
    }

    $post_id = (int)$commentdata['comment_post_ID'];
    $post = get_post($post_id);
    if (!$post || $post->post_type !== 'cpc_doc') {
        return $commentdata;
    }

    if (!cpc_docs_user_can_post_comments($post_id)) {
        wp_die(
            esc_html__('Keine Berechtigung zum Kommentieren dieses Dokuments.', CPC2_TEXT_DOMAIN),
            esc_html__('Keine Berechtigung', CPC2_TEXT_DOMAIN),
            array('response' => 403)
        );
    }

    return $commentdata;
}
add_filter('preprocess_comment', 'cpc_docs_preprocess_comment', 20);

function cpc_docs_get_associated_group_id_from_source($source_doc_id) {
    $terms = get_the_terms((int)$source_doc_id, 'bp_docs_associated_item');
    if (is_wp_error($terms) || empty($terms)) {
        return 0;
    }

    foreach ($terms as $term) {
        if (strpos((string)$term->slug, 'bp_docs_associated_group_') === 0) {
            $gid = (int)str_replace('bp_docs_associated_group_', '', (string)$term->slug);
            if ($gid > 0) {
                return $gid;
            }
        }
    }

    return 0;
}

function cpc_docs_get_source_access_terms($source_doc_id, $taxonomy = 'bp_docs_access') {
    $terms = get_the_terms((int)$source_doc_id, sanitize_key((string)$taxonomy));
    if (is_wp_error($terms) || empty($terms)) {
        return array();
    }

    $slugs = array();
    foreach ($terms as $term) {
        if (!empty($term->slug)) {
            $slugs[] = sanitize_key((string)$term->slug);
        }
    }

    return array_values(array_unique($slugs));
}

function cpc_docs_map_source_access_status($source_doc_id, $component, $component_id, $author_id) {
    $component = sanitize_key((string)$component);
    $component_id = (int)$component_id;
    $author_id = (int)$author_id;
    $terms = cpc_docs_get_source_access_terms($source_doc_id, 'bp_docs_access');

    if (empty($terms)) {
        return $component === 'groups' ? 'members' : 'public';
    }

    if (in_array('bp_docs_access_anyone', $terms, true)) {
        return 'public';
    }

    if ($component === 'groups' && $component_id > 0) {
        if (in_array('bp_docs_access_group_adminmod_'.$component_id, $terms, true)) {
            return 'admins';
        }
        if (in_array('bp_docs_access_group_member_'.$component_id, $terms, true)) {
            return 'members';
        }
        if (in_array('bp_docs_access_loggedin', $terms, true)) {
            return 'members';
        }
        if ($author_id > 0 && in_array('bp_docs_access_user_'.$author_id, $terms, true)) {
            return 'private';
        }

        return 'members';
    }

    if (in_array('bp_docs_access_loggedin', $terms, true)) {
        return 'loggedin';
    }

    if ($author_id > 0 && in_array('bp_docs_access_user_'.$author_id, $terms, true)) {
        return 'private';
    }

    return 'public';
}

function cpc_docs_map_source_comment_access_status($source_doc_id, $component, $component_id, $author_id) {
    $component = sanitize_key((string)$component);
    $component_id = (int)$component_id;
    $author_id = (int)$author_id;
    $terms = cpc_docs_get_source_access_terms($source_doc_id, 'bp_docs_comment_access');

    if (empty($terms)) {
        return 'public';
    }

    if (in_array('bp_docs_comment_access_anyone', $terms, true)) {
        return 'public';
    }

    if ($component === 'groups' && $component_id > 0) {
        if (in_array('bp_docs_comment_access_group_adminmod_'.$component_id, $terms, true)) {
            return 'admins';
        }
        if (in_array('bp_docs_comment_access_group_member_'.$component_id, $terms, true)) {
            return 'members';
        }
    }

    if (in_array('bp_docs_comment_access_loggedin', $terms, true)) {
        return 'loggedin';
    }

    if ($author_id > 0 && in_array('bp_docs_comment_access_user_'.$author_id, $terms, true)) {
        return 'private';
    }

    return 'public';
}

function cpc_docs_get_existing_imported_doc_id($source_id) {
    $posts = get_posts(array(
        'post_type' => 'cpc_doc',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'cpc_doc_source',
                'value' => 'bp-docs',
            ),
            array(
                'key' => 'cpc_doc_source_id',
                'value' => (int)$source_id,
            ),
        ),
    ));

    return !empty($posts) ? (int)$posts[0] : 0;
}

function cpc_docs_get_existing_imported_attachment_id($target_doc_id, $source_attachment_id) {
    $posts = get_posts(array(
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'post_parent' => (int)$target_doc_id,
        'meta_query' => array(
            array(
                'key' => 'cpc_doc_source_attachment_id',
                'value' => (int)$source_attachment_id,
            ),
        ),
    ));

    return !empty($posts) ? (int)$posts[0] : 0;
}

function cpc_docs_get_source_attachment_ids($source_doc_id) {
    $attachments = get_children(array(
        'post_parent' => (int)$source_doc_id,
        'post_type' => 'attachment',
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'orderby' => 'menu_order ID',
        'order' => 'ASC',
        'fields' => 'ids',
    ));

    return $attachments ? array_map('intval', $attachments) : array();
}

function cpc_docs_resolve_source_attachment_file($attachment_id) {
    $attachment_id = (int)$attachment_id;
    if ($attachment_id <= 0) {
        return '';
    }

    $path = get_attached_file($attachment_id);
    if ($path && file_exists($path)) {
        return $path;
    }

    $relative = get_post_meta($attachment_id, '_wp_attached_file', true);
    if (!$relative) {
        return '';
    }

    $uploads = wp_upload_dir();
    $fallback = trailingslashit($uploads['basedir']).ltrim((string)$relative, '/');
    return file_exists($fallback) ? $fallback : '';
}

function cpc_docs_copy_attachment_to_doc($source_attachment_id, $target_doc_id, $target_author_id) {
    $source_attachment_id = (int)$source_attachment_id;
    $target_doc_id = (int)$target_doc_id;
    $target_author_id = (int)$target_author_id;
    if ($source_attachment_id <= 0 || $target_doc_id <= 0) {
        return 0;
    }

    $existing = cpc_docs_get_existing_imported_attachment_id($target_doc_id, $source_attachment_id);
    if ($existing) {
        return $existing;
    }

    $source_path = cpc_docs_resolve_source_attachment_file($source_attachment_id);
    if ($source_path === '' || !is_readable($source_path)) {
        return 0;
    }

    $uploads = wp_upload_dir();
    $subdir = '/cpc-docs/'.(int)$target_doc_id;
    $target_dir = trailingslashit($uploads['basedir']).'cpc-docs/'.(int)$target_doc_id;
    if (!file_exists($target_dir)) {
        wp_mkdir_p($target_dir);
    }

    $filename = wp_unique_filename($target_dir, wp_basename($source_path));
    $target_path = trailingslashit($target_dir).$filename;

    if (!@copy($source_path, $target_path)) {
        return 0;
    }

    $mime = wp_check_filetype($filename, null);
    $attachment_id = wp_insert_attachment(array(
        'post_mime_type' => !empty($mime['type']) ? $mime['type'] : 'application/octet-stream',
        'post_title' => sanitize_text_field(get_the_title($source_attachment_id)),
        'post_excerpt' => get_post_field('post_excerpt', $source_attachment_id),
        'post_content' => get_post_field('post_content', $source_attachment_id),
        'post_status' => 'inherit',
        'post_parent' => $target_doc_id,
        'post_author' => $target_author_id > 0 ? $target_author_id : get_current_user_id(),
        'menu_order' => (int)get_post_field('menu_order', $source_attachment_id),
    ), $target_path, $target_doc_id, true);

    if (is_wp_error($attachment_id) || !$attachment_id) {
        @unlink($target_path);
        return 0;
    }

    $relative = ltrim($subdir.'/'.$filename, '/');
    update_attached_file($attachment_id, trailingslashit($uploads['basedir']).$relative);
    update_post_meta($attachment_id, 'cpc_doc_source_attachment_id', $source_attachment_id);
    update_post_meta($attachment_id, 'cpc_doc_source', 'bp-docs');

    if (!function_exists('wp_generate_attachment_metadata')) {
        require_once ABSPATH.'wp-admin/includes/image.php';
    }
    $metadata = wp_generate_attachment_metadata($attachment_id, $target_path);
    if (!empty($metadata)) {
        wp_update_attachment_metadata($attachment_id, $metadata);
    }

    return (int)$attachment_id;
}

function cpc_docs_import_attachments($source_doc_id, $target_doc_id, $target_author_id) {
    $source_doc_id = (int)$source_doc_id;
    $target_doc_id = (int)$target_doc_id;
    $target_author_id = (int)$target_author_id;

    $source_attachment_ids = cpc_docs_get_source_attachment_ids($source_doc_id);
    if (empty($source_attachment_ids)) {
        return array('created' => 0, 'failed' => 0);
    }

    $created = 0;
    $failed = 0;

    foreach ($source_attachment_ids as $source_attachment_id) {
        if (cpc_docs_get_existing_imported_attachment_id($target_doc_id, $source_attachment_id)) {
            continue;
        }

        $new_attachment_id = cpc_docs_copy_attachment_to_doc($source_attachment_id, $target_doc_id, $target_author_id);
        if ($new_attachment_id) {
            $created++;
        } else {
            $failed++;
        }
    }

    return array('created' => $created, 'failed' => $failed);
}

function cpc_docs_copy_comments($source_post_id, $target_post_id) {
    $comments = get_comments(array(
        'post_id' => (int)$source_post_id,
        'status' => 'approve',
        'orderby' => 'comment_date_gmt',
        'order' => 'ASC',
    ));

    if (!$comments) {
        return 0;
    }

    $created = 0;
    foreach ($comments as $comment) {
        $new_comment_id = wp_insert_comment(array(
            'comment_post_ID' => (int)$target_post_id,
            'comment_author' => $comment->comment_author,
            'comment_author_email' => $comment->comment_author_email,
            'comment_author_url' => $comment->comment_author_url,
            'comment_author_IP' => $comment->comment_author_IP,
            'comment_date' => $comment->comment_date,
            'comment_date_gmt' => $comment->comment_date_gmt,
            'comment_content' => $comment->comment_content,
            'comment_karma' => (int)$comment->comment_karma,
            'comment_approved' => $comment->comment_approved,
            'comment_agent' => $comment->comment_agent,
            'comment_type' => $comment->comment_type,
            'user_id' => (int)$comment->user_id,
            'comment_parent' => 0,
        ));

        if ($new_comment_id) {
            $created++;
        }
    }

    return $created;
}

function cpc_docs_import_from_bp_docs($limit = 500, $with_attachments = true) {
    $limit = max(1, min(2000, (int)$limit));

    $source_docs = get_posts(array(
        'post_type' => 'bp_doc',
        'post_status' => array('publish', 'private', 'pending'),
        'posts_per_page' => $limit,
        'orderby' => 'ID',
        'order' => 'ASC',
    ));

    $result = array(
        'processed' => 0,
        'created' => 0,
        'skipped' => 0,
        'comments' => 0,
        'attachments_created' => 0,
        'attachments_failed' => 0,
    );

    if (!$source_docs) {
        return $result;
    }

    $parent_map = array();

    foreach ($source_docs as $source_doc) {
        $result['processed']++;

        $existing = cpc_docs_get_existing_imported_doc_id($source_doc->ID);
        if ($existing) {
            if ($with_attachments) {
                $attachments = cpc_docs_import_attachments($source_doc->ID, $existing, (int)$source_doc->post_author);
                $result['attachments_created'] += (int)$attachments['created'];
                $result['attachments_failed'] += (int)$attachments['failed'];
            }
            $result['skipped']++;
            $parent_map[$source_doc->ID] = $existing;
            continue;
        }

        $component = 'members';
        $component_id = (int)$source_doc->post_author;
        $status = 'public';

        $group_id = cpc_docs_get_associated_group_id_from_source($source_doc->ID);
        if ($group_id > 0 && get_post($group_id) && get_post($group_id)->post_type === 'cpc_group') {
            $component = 'groups';
            $component_id = $group_id;
        }

        $status = cpc_docs_map_source_access_status($source_doc->ID, $component, $component_id, (int)$source_doc->post_author);
        $comment_visibility = cpc_docs_map_source_comment_access_status($source_doc->ID, $component, $component_id, (int)$source_doc->post_author);

        $doc_id = wp_insert_post(array(
            'post_type' => 'cpc_doc',
            'post_status' => 'publish',
            'post_author' => (int)$source_doc->post_author,
            'post_title' => $source_doc->post_title,
            'post_name' => $source_doc->post_name,
            'post_content' => $source_doc->post_content,
            'post_excerpt' => $source_doc->post_excerpt,
            'post_date' => $source_doc->post_date,
            'post_date_gmt' => $source_doc->post_date_gmt,
            'post_modified' => $source_doc->post_modified,
            'post_modified_gmt' => $source_doc->post_modified_gmt,
            'comment_status' => $source_doc->comment_status,
            'menu_order' => (int)$source_doc->menu_order,
            'post_parent' => 0,
        ), true);

        if (is_wp_error($doc_id) || !$doc_id) {
            $result['skipped']++;
            continue;
        }

        cpc_docs_set_doc_meta($doc_id, $component, $component_id, $status);
        update_post_meta($doc_id, 'cpc_doc_comment_status', $comment_visibility);
        update_post_meta($doc_id, 'cpc_doc_source', 'bp-docs');
        update_post_meta($doc_id, 'cpc_doc_source_id', (int)$source_doc->ID);
        update_post_meta($doc_id, 'cpc_doc_source_parent_id', (int)$source_doc->post_parent);

        $result['comments'] += cpc_docs_copy_comments($source_doc->ID, $doc_id);
        if ($with_attachments) {
            $attachments = cpc_docs_import_attachments($source_doc->ID, $doc_id, (int)$source_doc->post_author);
            $result['attachments_created'] += (int)$attachments['created'];
            $result['attachments_failed'] += (int)$attachments['failed'];
        }
        $result['created']++;
        $parent_map[$source_doc->ID] = (int)$doc_id;
    }

    foreach ($parent_map as $source_id => $new_id) {
        $source_parent_id = (int)get_post_meta($new_id, 'cpc_doc_source_parent_id', true);
        if ($source_parent_id > 0 && isset($parent_map[$source_parent_id])) {
            wp_update_post(array(
                'ID' => $new_id,
                'post_parent' => (int)$parent_map[$source_parent_id],
            ));
        }
    }

    return $result;
}
