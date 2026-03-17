<?php

function cpc_media_is_enabled() {
    return (bool)apply_filters('cpc_media_is_enabled', defined('CPC_CORE_PLUGINS') && strpos(CPC_CORE_PLUGINS, 'core-media') !== false);
}

function cpc_media_get_user_cloud_folder_name($user_id) {
    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return 'user-0';
    }

    if (function_exists('cpc_activity_plus_get_user_cloud_folder_name')) {
        return cpc_activity_plus_get_user_cloud_folder_name($user_id);
    }

    if (function_exists('user_avatar_core_get_cloud_folder_name')) {
        return user_avatar_core_get_cloud_folder_name($user_id);
    }

    $user = get_user_by('id', $user_id);
    $slug = $user ? sanitize_title($user->user_login) : '';
    if ($slug === '') {
        $slug = 'user';
    }

    return $slug.'-'.$user_id;
}

function cpc_media_get_user_media_dir($user_id, $create = true) {
    $folder = cpc_media_get_user_cloud_folder_name($user_id);
    $path = WP_CONTENT_DIR.'/cpc-pro-content/members/'.$folder.'/media/';

    if ($create && !file_exists($path)) {
        wp_mkdir_p($path);
    }

    return $path;
}

function cpc_media_get_user_media_url($user_id) {
    $folder = cpc_media_get_user_cloud_folder_name($user_id);
    return content_url('/cpc-pro-content/members/'.$folder.'/media/');
}

function cpc_media_get_group_cloud_folder_name($group_id) {
    $group_id = (int)$group_id;
    if ($group_id <= 0) {
        return 'group-0';
    }

    if (function_exists('cpc_activity_plus_get_group_cloud_folder_name')) {
        return cpc_activity_plus_get_group_cloud_folder_name($group_id);
    }

    $group = get_post($group_id);
    $slug = $group ? sanitize_title($group->post_name) : '';
    if ($slug === '') {
        $slug = 'group';
    }

    return $slug.'-'.$group_id;
}

function cpc_media_get_group_media_dir($group_id, $create = true) {
    $folder = cpc_media_get_group_cloud_folder_name($group_id);
    $path = WP_CONTENT_DIR.'/cpc-pro-content/groups/'.$folder.'/media/';

    if ($create && !file_exists($path)) {
        wp_mkdir_p($path);
    }

    return $path;
}

function cpc_media_get_group_media_url($group_id) {
    $folder = cpc_media_get_group_cloud_folder_name($group_id);
    return content_url('/cpc-pro-content/groups/'.$folder.'/media/');
}

function cpc_media_get_gallery_dir($gallery_id, $create = true) {
    $gallery = get_post($gallery_id);
    if (!$gallery || $gallery->post_type !== 'cpc_gallery') {
        return '';
    }

    $component = cpc_media_get_gallery_component($gallery_id);
    $component_id = cpc_media_get_gallery_component_id($gallery_id);

    if ($component === 'groups' && $component_id > 0) {
        $base = cpc_media_get_group_media_dir($component_id, $create);
    } else {
        $base = cpc_media_get_user_media_dir($gallery->post_author, $create);
    }

    if (!$base) {
        return '';
    }

    $path = trailingslashit($base).'gallery-'.(int)$gallery_id.'/';
    if ($create && !file_exists($path)) {
        wp_mkdir_p($path);
    }

    return $path;
}

function cpc_media_get_gallery_url($gallery_id) {
    $gallery = get_post($gallery_id);
    if (!$gallery || $gallery->post_type !== 'cpc_gallery') {
        return '';
    }

    $component = cpc_media_get_gallery_component($gallery_id);
    $component_id = cpc_media_get_gallery_component_id($gallery_id);

    if ($component === 'groups' && $component_id > 0) {
        return trailingslashit(cpc_media_get_group_media_url($component_id)).'gallery-'.(int)$gallery_id.'/';
    }

    return trailingslashit(cpc_media_get_user_media_url($gallery->post_author)).'gallery-'.(int)$gallery_id.'/';
}

function cpc_media_friendships_enabled() {
    return defined('CPC_CORE_PLUGINS') && strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false && function_exists('cpc_are_friends');
}

function cpc_media_are_users_friends($user_id, $other_user_id) {
    $user_id = (int)$user_id;
    $other_user_id = (int)$other_user_id;

    if (!$user_id || !$other_user_id) {
        return false;
    }

    if ($user_id === $other_user_id) {
        return true;
    }

    if (!cpc_media_friendships_enabled()) {
        return false;
    }

    $friends = cpc_are_friends($user_id, $other_user_id);
    return !empty($friends['status']) && $friends['status'] === 'publish';
}

function cpc_media_get_gallery_status_options($component = 'members') {
    $component = sanitize_key((string)$component);
    $options = array(
        'public' => __('Öffentlich', CPC2_TEXT_DOMAIN),
        'loggedin' => __('Angemeldet', CPC2_TEXT_DOMAIN),
    );

    if ($component === 'members' && cpc_media_friendships_enabled() && get_option('cpc_media_enable_friend_visibility', 1)) {
        $options['friends'] = __('Nur Freunde', CPC2_TEXT_DOMAIN);
    }

    $options['private'] = __('Privat', CPC2_TEXT_DOMAIN);

    return $options;
}

function cpc_media_get_gallery_status_label($status, $component = 'members') {
    $options = cpc_media_get_gallery_status_options($component);
    $status = sanitize_key((string)$status);
    return isset($options[$status]) ? $options[$status] : ucfirst($status);
}

function cpc_media_get_gallery_type_label($type) {
    $map = array(
        'photo' => __('Bilder', CPC2_TEXT_DOMAIN),
        'video' => __('Videos', CPC2_TEXT_DOMAIN),
        'audio' => __('Audio', CPC2_TEXT_DOMAIN),
        'doc' => __('Dokumente', CPC2_TEXT_DOMAIN),
    );

    $type = sanitize_key((string)$type);
    return isset($map[$type]) ? $map[$type] : ucfirst($type);
}

function cpc_media_get_gallery_component_label($component) {
    $map = array(
        'members' => __('Profil', CPC2_TEXT_DOMAIN),
        'groups' => __('Gruppe', CPC2_TEXT_DOMAIN),
    );

    $component = sanitize_key((string)$component);
    return isset($map[$component]) ? $map[$component] : ucfirst($component);
}

function cpc_media_get_default_gallery_status($component = 'members') {
    $component = sanitize_key((string)$component);
    $default = $component === 'groups' ? get_option('cpc_media_default_group_status', 'public') : get_option('cpc_media_default_member_status', 'public');
    $options = cpc_media_get_gallery_status_options($component);

    return isset($options[$default]) ? $default : 'public';
}

function cpc_media_get_gallery_layout() {
    $layout = sanitize_key((string)get_option('cpc_media_gallery_layout', 'grid'));
    return in_array($layout, array('grid', 'list'), true) ? $layout : 'grid';
}

function cpc_media_get_gallery_items_limit() {
    return max(1, min(60, (int)get_option('cpc_media_gallery_items_limit', 24)));
}

function cpc_media_get_directory_page_id() {
    return max(0, (int)get_option('cpc_media_directory_page', 0));
}

function cpc_media_get_directory_items_per_page() {
    return max(6, min(60, (int)get_option('cpc_media_directory_items_per_page', 12)));
}

function cpc_media_activity_wall_sync_enabled() {
    return (bool)get_option('cpc_media_activity_wall_sync', 1);
}

function cpc_media_get_group_stream_album_visibility_options() {
    return array(
        'admins' => __('Nur Gruppen-Admins', CPC2_TEXT_DOMAIN),
        'members' => __('Alle Gruppenmitglieder', CPC2_TEXT_DOMAIN),
        'public' => __('Alle mit Gruppenzugriff', CPC2_TEXT_DOMAIN),
        'hidden' => __('Versteckt (nur Moderation)', CPC2_TEXT_DOMAIN),
    );
}

function cpc_media_normalize_group_stream_album_visibility($value) {
    $value = sanitize_key((string)$value);
    $allowed = array_keys(cpc_media_get_group_stream_album_visibility_options());
    return in_array($value, $allowed, true) ? $value : 'admins';
}

function cpc_media_get_group_stream_album_visibility($group_id) {
    $group_id = (int)$group_id;
    if ($group_id <= 0) {
        return 'admins';
    }

    $stored = get_post_meta($group_id, 'cpc_group_stream_album_visibility', true);
    return cpc_media_normalize_group_stream_album_visibility($stored);
}

function cpc_media_map_group_stream_visibility_to_gallery_status($visibility) {
    $visibility = cpc_media_normalize_group_stream_album_visibility($visibility);
    return $visibility === 'public' ? 'public' : 'private';
}

function cpc_media_can_view_group_stream_album($group_id, $user_id = 0) {
    $group_id = (int)$group_id;
    if ($group_id <= 0) {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (current_user_can('manage_options')) {
        return true;
    }

    $visibility = cpc_media_get_group_stream_album_visibility($group_id);

    if ($visibility === 'hidden') {
        return false;
    }

    if ($visibility === 'admins') {
        return function_exists('cpc_is_group_admin') && cpc_is_group_admin($user_id, $group_id);
    }

    if ($visibility === 'members') {
        return function_exists('cpc_is_group_member') && cpc_is_group_member($user_id, $group_id);
    }

    return function_exists('cpc_can_view_group') ? cpc_can_view_group($user_id, $group_id) : false;
}

function cpc_media_get_user_storage_limit_mb() {
    return max(0, min(10240, (int)get_option('cpc_media_user_storage_limit_mb', 0)));
}

function cpc_media_get_group_storage_limit_mb() {
    return max(0, min(10240, (int)get_option('cpc_media_group_storage_limit_mb', 0)));
}

function cpc_media_is_system_gallery($gallery_id) {
    return (bool)get_post_meta((int)$gallery_id, 'cpc_gallery_is_system', true);
}

function cpc_media_get_directory_size_bytes($dir) {
    if (!file_exists($dir) || !is_dir($dir)) {
        return 0;
    }

    if (function_exists('cpc_activity_plus_get_directory_size_bytes')) {
        return (int)cpc_activity_plus_get_directory_size_bytes($dir);
    }

    $size = 0;
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $file) {
        if ($file->isFile()) {
            $size += (int)$file->getSize();
        }
    }

    return max(0, (int)$size);
}

function cpc_media_get_storage_summary($component, $component_id, $user_id = 0) {
    $component = sanitize_key((string)$component);
    $component_id = (int)$component_id;
    $user_id = (int)$user_id;

    if ($component === 'groups' && $component_id > 0) {
        if (function_exists('cpc_activity_plus_get_group_cloud_summary')) {
            return cpc_activity_plus_get_group_cloud_summary($component_id);
        }

        $used = cpc_media_get_directory_size_bytes(cpc_media_get_group_media_dir($component_id, false));
        $limit_mb = cpc_media_get_group_storage_limit_mb();
        $limit = $limit_mb > 0 ? $limit_mb * 1024 * 1024 : 0;
    } else {
        if ($component_id <= 0) {
            $component_id = $user_id;
        }

        if ($component_id > 0 && function_exists('cpc_activity_plus_get_user_cloud_summary')) {
            return cpc_activity_plus_get_user_cloud_summary($component_id);
        }

        $used = cpc_media_get_directory_size_bytes(cpc_media_get_user_media_dir($component_id, false));
        $limit_mb = cpc_media_get_user_storage_limit_mb();
        $limit = $limit_mb > 0 ? $limit_mb * 1024 * 1024 : 0;
    }

    $remaining = $limit > 0 ? max(0, $limit - $used) : 0;
    $percent = ($limit > 0) ? min(100, round(($used / $limit) * 100, 1)) : 0;

    return array(
        'used_bytes' => $used,
        'used_human' => size_format($used),
        'limit_bytes' => $limit,
        'limit_human' => $limit > 0 ? size_format($limit) : __('Unbegrenzt', CPC2_TEXT_DOMAIN),
        'remaining_bytes' => $remaining,
        'remaining_human' => $limit > 0 ? size_format($remaining) : __('Unbegrenzt', CPC2_TEXT_DOMAIN),
        'percent' => $percent,
    );
}

function cpc_media_map_item_type_to_allowed_type($media_type) {
    $map = array(
        'photo' => 'image',
        'video' => 'video',
        'audio' => 'audio',
        'doc' => 'document',
    );

    $media_type = sanitize_key((string)$media_type);
    return isset($map[$media_type]) ? $map[$media_type] : 'document';
}

function cpc_media_get_activity_image_urls($activity_id) {
    $activity = get_post((int)$activity_id);
    if (!$activity) {
        return array();
    }

    $content = (string)$activity->post_title;
    if (!preg_match('/\[cpcap_images\](.*?)\[\/cpcap_images\]/is', $content, $matches)) {
        return array();
    }

    $urls = preg_split('/\r\n|\r|\n/', trim($matches[1]));
    if (!$urls || !is_array($urls)) {
        return array();
    }

    $clean = array();
    foreach ($urls as $url) {
        $url = esc_url_raw(trim($url));
        if ($url) {
            $clean[] = $url;
        }
    }

    return array_values(array_unique($clean));
}

function cpc_media_get_file_path_from_url($url) {
    $url = (string)$url;
    if ($url === '') {
        return '';
    }

    $content_base = content_url('/');
    if (strpos($url, $content_base) !== 0) {
        return '';
    }

    $relative = ltrim(substr($url, strlen($content_base)), '/');
    if ($relative === '') {
        return '';
    }

    $path = trailingslashit(WP_CONTENT_DIR).str_replace('/', DIRECTORY_SEPARATOR, $relative);
    return file_exists($path) ? $path : '';
}

function cpc_media_find_activity_item($activity_id, $url) {
    $signature = md5((int)$activity_id.'|'.(string)$url);
    $posts = get_posts(array(
        'post_type' => 'cpc_media',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'cpc_media_activity_signature',
                'value' => $signature,
            ),
        ),
    ));

    return !empty($posts) ? (int)$posts[0] : 0;
}

function cpc_media_get_or_create_wall_gallery($component, $component_id, $user_id = 0) {
    $component = sanitize_key((string)$component);
    $component_id = (int)$component_id;
    $user_id = (int)$user_id;

    if ($component !== 'groups') {
        $component = 'members';
        if ($component_id <= 0) {
            $component_id = $user_id;
        }
    }

    if ($component_id <= 0 || $user_id <= 0) {
        return 0;
    }

    $posts = get_posts(array(
        'post_type' => 'cpc_gallery',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'cpc_gallery_source',
                'value' => 'activity-wall',
            ),
            array(
                'key' => 'cpc_gallery_component',
                'value' => $component,
            ),
            array(
                'key' => 'cpc_gallery_component_id',
                'value' => $component_id,
            ),
        ),
    ));

    if (!empty($posts)) {
        if ($component === 'groups') {
            $visibility = cpc_media_get_group_stream_album_visibility($component_id);
            update_post_meta((int)$posts[0], 'cpc_gallery_wall_visibility', $visibility);
            update_post_meta((int)$posts[0], 'cpc_gallery_status', cpc_media_map_group_stream_visibility_to_gallery_status($visibility));
        }
        return (int)$posts[0];
    }

    $group_visibility = ($component === 'groups') ? cpc_media_get_group_stream_album_visibility($component_id) : 'admins';

    $gallery_id = cpc_media_create_gallery(array(
        'title' => $component === 'groups' ? __('Gruppen-Wall Medien', CPC2_TEXT_DOMAIN) : __('Wall Medien', CPC2_TEXT_DOMAIN),
        'description' => '',
        'user_id' => $user_id,
        'component' => $component,
        'component_id' => $component_id,
        'status' => ($component === 'groups') ? cpc_media_map_group_stream_visibility_to_gallery_status($group_visibility) : 'private',
        'type' => 'photo',
        'source' => 'activity-wall',
        'source_id' => $component_id,
    ));

    if ($gallery_id) {
        update_post_meta($gallery_id, 'cpc_gallery_is_system', 1);
        if ($component === 'groups') {
            update_post_meta($gallery_id, 'cpc_gallery_wall_visibility', $group_visibility);
        }
    }

    return (int)$gallery_id;
}

function cpc_media_import_activity_urls_to_gallery($gallery_id, $activity_id, $urls, $source = 'psc-activity-plus') {
    $gallery_id = (int)$gallery_id;
    $activity_id = (int)$activity_id;
    $gallery = get_post($gallery_id);
    if (!$gallery || $gallery->post_type !== 'cpc_gallery' || empty($urls) || !is_array($urls)) {
        return 0;
    }

    $created = 0;
    $order = cpc_media_get_gallery_media_count($gallery_id);

    foreach ($urls as $url) {
        $url = esc_url_raw($url);
        if (!$url || cpc_media_find_activity_item($activity_id, $url)) {
            continue;
        }

        $path = cpc_media_get_file_path_from_url($url);
        $path_for_info = $path ? $path : wp_parse_url($url, PHP_URL_PATH);
        $filename = $path_for_info ? wp_basename($path_for_info) : 'activity-media';
        $title = pathinfo($filename, PATHINFO_FILENAME);
        $mime_type = '';
        $metadata = array();

        if ($path) {
            $filetype = wp_check_filetype($filename);
            $mime_type = !empty($filetype['type']) ? (string)$filetype['type'] : '';
            if (strpos($mime_type, 'image/') === 0) {
                $image_size = @getimagesize($path);
                if ($image_size && isset($image_size[0], $image_size[1])) {
                    $metadata['width'] = (int)$image_size[0];
                    $metadata['height'] = (int)$image_size[1];
                }
            }
        }

        $media_id = cpc_media_create_item(array(
            'gallery_id' => $gallery_id,
            'user_id' => (int)$gallery->post_author,
            'title' => $title,
            'description' => '',
            'mime_type' => $mime_type,
            'media_type' => cpc_media_map_mime_to_type($mime_type, 'photo'),
            'source' => $source,
            'source_id' => $activity_id,
            'source_url' => $url,
            'source_file' => $path,
            'file_url' => $url,
            'file_path' => $path,
            'metadata' => $metadata,
            'migrated_files' => $path ? array(
                array(
                    'role' => 'original',
                    'path' => $path,
                    'url' => $url,
                ),
            ) : array(),
            'menu_order' => $order,
        ));

        if ($media_id) {
            update_post_meta($media_id, 'cpc_media_activity_id', $activity_id);
            update_post_meta($media_id, 'cpc_media_activity_signature', md5($activity_id.'|'.$url));
            $created++;
            $order++;
        }
    }

    if ($created > 0) {
        cpc_media_update_gallery_media_count($gallery_id, cpc_media_get_gallery_media_count($gallery_id) + $created);
    }

    return $created;
}

function cpc_media_sync_member_activity_gallery($the_post, $the_files, $activity_id) {
    if (!cpc_media_is_enabled() || !cpc_media_activity_wall_sync_enabled()) {
        return;
    }

    $activity_id = (int)$activity_id;
    $group_id = (int)get_post_meta($activity_id, 'cpc_activity_group_id', true);
    if ($group_id > 0) {
        return;
    }

    $activity = get_post($activity_id);
    if (!$activity || $activity->post_type !== 'cpc_activity') {
        return;
    }

    $urls = cpc_media_get_activity_image_urls($activity_id);
    if (!$urls) {
        return;
    }

    $gallery_id = cpc_media_get_or_create_wall_gallery('members', (int)$activity->post_author, (int)$activity->post_author);
    if (!$gallery_id) {
        return;
    }

    cpc_media_import_activity_urls_to_gallery($gallery_id, $activity_id, $urls, 'psc-activity-plus');
    update_post_meta($activity_id, 'cpc_media_wall_gallery_id', $gallery_id);
}

function cpc_media_sync_group_activity_gallery($the_post, $the_files, $activity_id, $group_id) {
    if (!cpc_media_is_enabled() || !cpc_media_activity_wall_sync_enabled()) {
        return;
    }

    $activity_id = (int)$activity_id;
    $group_id = (int)$group_id;
    if ($activity_id <= 0 || $group_id <= 0) {
        return;
    }

    $activity = get_post($activity_id);
    if (!$activity || $activity->post_type !== 'cpc_activity') {
        return;
    }

    $urls = cpc_media_get_activity_image_urls($activity_id);
    if (!$urls) {
        return;
    }

    $gallery_id = cpc_media_get_or_create_wall_gallery('groups', $group_id, (int)$activity->post_author);
    if (!$gallery_id) {
        return;
    }

    cpc_media_import_activity_urls_to_gallery($gallery_id, $activity_id, $urls, 'psc-activity-plus-group');
    update_post_meta($activity_id, 'cpc_media_wall_gallery_id', $gallery_id);
}

function cpc_media_extend_user_cloud_dirs($dirs, $user_id) {
    $media_dir = cpc_media_get_user_media_dir((int)$user_id, false);
    if ($media_dir && file_exists($media_dir) && is_dir($media_dir)) {
        $dirs[] = $media_dir;
    }

    return array_values(array_unique($dirs));
}

function cpc_media_extend_group_cloud_dirs($dirs, $group_id) {
    $media_dir = cpc_media_get_group_media_dir((int)$group_id, false);
    if ($media_dir && file_exists($media_dir) && is_dir($media_dir)) {
        $dirs[] = $media_dir;
    }

    return array_values(array_unique($dirs));
}

function cpc_media_filter_user_cloud_limit_mb($limit_mb, $user_id, $settings = array()) {
    $override = cpc_media_get_user_storage_limit_mb();
    return $override > 0 ? $override : $limit_mb;
}

function cpc_media_filter_group_cloud_limit_mb($limit_mb, $group_id) {
    $override = cpc_media_get_group_storage_limit_mb();
    return $override > 0 ? $override : $limit_mb;
}

function cpc_media_show_gallery_descriptions() {
    return (bool)get_option('cpc_media_show_gallery_descriptions', 1);
}

function cpc_media_get_gallery_cover_url($gallery_id) {
    $gallery_id = (int)$gallery_id;
    $cover_id = (int)get_post_meta($gallery_id, 'cpc_media_gallery_cover_id', true);
    if ($cover_id <= 0) {
        $cover_id = (int)get_post_meta($gallery_id, 'cpc_gallery_cover_id', true);
    }
    if ($cover_id > 0) {
        $cover_url = cpc_media_get_media_file_url($cover_id);
        if ($cover_url) {
            return $cover_url;
        }
    }

    $items = cpc_media_get_gallery_items($gallery_id, array(
        'posts_per_page' => 1,
        'orderby' => 'menu_order date',
        'order' => 'ASC',
    ));

    if (!$items) {
        return '';
    }

    return cpc_media_get_media_file_url($items[0]->ID);
}

function cpc_media_get_gallery_preview_items($gallery_id, $limit = 3) {
    return cpc_media_get_gallery_items($gallery_id, array(
        'posts_per_page' => max(1, min(8, (int)$limit)),
        'orderby' => 'menu_order date',
        'order' => 'ASC',
    ));
}

function cpc_media_get_gallery_component($gallery_id) {
    $component = get_post_meta($gallery_id, 'cpc_gallery_component', true);
    return $component ? $component : 'members';
}

function cpc_media_get_gallery_component_id($gallery_id) {
    return (int)get_post_meta($gallery_id, 'cpc_gallery_component_id', true);
}

function cpc_media_get_gallery_status($gallery_id) {
    $status = get_post_meta($gallery_id, 'cpc_gallery_status', true);
    return $status ? $status : 'public';
}

function cpc_media_get_gallery_type($gallery_id) {
    $type = get_post_meta($gallery_id, 'cpc_gallery_type', true);
    return $type ? $type : 'photo';
}

function cpc_media_get_gallery_media_count($gallery_id) {
    $count = get_post_meta($gallery_id, 'cpc_gallery_media_count', true);
    return max(0, (int)$count);
}

function cpc_media_update_gallery_media_count($gallery_id, $count) {
    $count = max(0, (int)$count);
    update_post_meta($gallery_id, 'cpc_gallery_media_count', $count);
    return $count;
}

function cpc_media_get_gallery_items($gallery_id, $args = array()) {
    $defaults = array(
        'post_type' => 'cpc_media',
        'post_status' => 'publish',
        'posts_per_page' => -1,
        'orderby' => 'menu_order date',
        'order' => 'ASC',
        'meta_query' => array(
            array(
                'key' => 'cpc_media_gallery_id',
                'value' => (int)$gallery_id,
            ),
        ),
    );

    return get_posts(wp_parse_args($args, $defaults));
}

function cpc_media_get_media_file_url($media_id) {
    $media_id = (int)$media_id;
    if (!$media_id) {
        return '';
    }

    $file_url = get_post_meta($media_id, 'cpc_media_file_url', true);
    if ($file_url) {
        return $file_url;
    }

    return (string)get_post_meta($media_id, 'cpc_media_source_url', true);
}

function cpc_media_get_media_mime_type($media_id) {
    return (string)get_post_meta((int)$media_id, 'cpc_media_mime_type', true);
}

function cpc_media_user_can_manage_media($media_id, $user_id = 0) {
    $media_id = (int)$media_id;
    $media = get_post($media_id);
    if (!$media || $media->post_type !== 'cpc_media') {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    if (current_user_can('manage_options') || (int)$media->post_author === (int)$user_id) {
        return true;
    }

    $gallery_id = (int)get_post_meta($media_id, 'cpc_media_gallery_id', true);
    return $gallery_id ? cpc_media_user_can_manage_gallery($gallery_id, $user_id) : false;
}

function cpc_media_user_can_manage_gallery($gallery_id, $user_id = 0) {
    $gallery_id = (int)$gallery_id;
    $gallery = get_post($gallery_id);
    if (!$gallery || $gallery->post_type !== 'cpc_gallery') {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (!$user_id) {
        return false;
    }

    if (current_user_can('manage_options') || (int)$gallery->post_author === (int)$user_id) {
        return true;
    }

    $component = cpc_media_get_gallery_component($gallery_id);
    $component_id = cpc_media_get_gallery_component_id($gallery_id);

    if ($component === 'groups' && $component_id) {
        if (function_exists('cpc_is_group_moderator') && cpc_is_group_moderator($user_id, $component_id)) {
            return true;
        }
        if (function_exists('cpc_is_group_admin') && cpc_is_group_admin($user_id, $component_id)) {
            return true;
        }
    }

    return false;
}

function cpc_media_user_can_view_gallery($gallery_id, $user_id = 0) {
    $gallery_id = (int)$gallery_id;
    $gallery = get_post($gallery_id);
    if (!$gallery || $gallery->post_type !== 'cpc_gallery') {
        return false;
    }

    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (cpc_media_user_can_manage_gallery($gallery_id, $user_id)) {
        return true;
    }

    $component = cpc_media_get_gallery_component($gallery_id);
    $component_id = cpc_media_get_gallery_component_id($gallery_id);
    $status = cpc_media_get_gallery_status($gallery_id);

    if (cpc_media_is_system_gallery($gallery_id) && $component === 'groups' && $component_id > 0) {
        $source = (string)get_post_meta($gallery_id, 'cpc_gallery_source', true);
        if ($source === 'activity-wall') {
            return cpc_media_can_view_group_stream_album($component_id, $user_id);
        }
    }

    if ($component === 'groups' && $component_id && function_exists('cpc_can_view_group')) {
        if (!cpc_can_view_group($user_id, $component_id)) {
            return false;
        }
        if ($status === 'private' && function_exists('cpc_is_group_member')) {
            return cpc_is_group_member($user_id, $component_id);
        }
        return true;
    }

    if ($status === 'public') {
        return true;
    }

    if ($status === 'loggedin') {
        return is_user_logged_in();
    }

    if ($status === 'friends') {
        return $user_id ? cpc_media_are_users_friends($user_id, (int)$gallery->post_author) : false;
    }

    return false;
}

function cpc_media_get_galleries($args = array()) {
    $defaults = array(
        'post_type' => 'cpc_gallery',
        'posts_per_page' => -1,
        'post_status' => 'publish',
        'orderby' => 'date',
        'order' => 'DESC',
        'include_system' => false,
    );

    $query_args = wp_parse_args($args, $defaults);
    $meta_query = array();
    $include_system = !empty($query_args['include_system']);
    unset($query_args['include_system']);

    if (!empty($query_args['component'])) {
        $meta_query[] = array(
            'key' => 'cpc_gallery_component',
            'value' => sanitize_text_field($query_args['component']),
        );
        unset($query_args['component']);
    }

    if (!empty($query_args['component_id'])) {
        $meta_query[] = array(
            'key' => 'cpc_gallery_component_id',
            'value' => (int)$query_args['component_id'],
        );
        unset($query_args['component_id']);
    }

    if (!empty($query_args['gallery_status'])) {
        $meta_query[] = array(
            'key' => 'cpc_gallery_status',
            'value' => sanitize_text_field($query_args['gallery_status']),
        );
        unset($query_args['gallery_status']);
    }

    if (!empty($query_args['gallery_type'])) {
        $meta_query[] = array(
            'key' => 'cpc_gallery_type',
            'value' => sanitize_text_field($query_args['gallery_type']),
        );
        unset($query_args['gallery_type']);
    }

    if (!$include_system) {
        $meta_query[] = array(
            'relation' => 'OR',
            array(
                'key' => 'cpc_gallery_is_system',
                'compare' => 'NOT EXISTS',
            ),
            array(
                'key' => 'cpc_gallery_is_system',
                'value' => '1',
                'compare' => '!=',
            ),
        );
    }

    if ($meta_query) {
        $query_args['meta_query'] = $meta_query;
    }

    return get_posts($query_args);
}

function cpc_media_create_gallery($args = array()) {
    $defaults = array(
        'title' => '',
        'description' => '',
        'user_id' => get_current_user_id(),
        'component' => 'members',
        'component_id' => 0,
        'status' => 'public',
        'type' => 'photo',
        'source' => '',
        'source_id' => 0,
        'media_count' => 0,
    );

    $args = wp_parse_args($args, $defaults);
    $user_id = (int)$args['user_id'];

    if (!$user_id || $args['title'] === '') {
        return 0;
    }

    if ($args['component'] === 'members' && !$args['component_id']) {
        $args['component_id'] = $user_id;
    }

    $gallery_id = wp_insert_post(array(
        'post_type' => 'cpc_gallery',
        'post_status' => 'publish',
        'post_author' => $user_id,
        'post_title' => sanitize_text_field($args['title']),
        'post_content' => wp_kses_post($args['description']),
        'comment_status' => 'open',
    ), true);

    if (is_wp_error($gallery_id) || !$gallery_id) {
        return 0;
    }

    update_post_meta($gallery_id, 'cpc_gallery_component', sanitize_text_field($args['component']));
    update_post_meta($gallery_id, 'cpc_gallery_component_id', (int)$args['component_id']);
    update_post_meta($gallery_id, 'cpc_gallery_status', sanitize_text_field($args['status']));
    update_post_meta($gallery_id, 'cpc_gallery_type', sanitize_text_field($args['type']));
    update_post_meta($gallery_id, 'cpc_gallery_media_count', max(0, (int)$args['media_count']));

    if (!empty($args['source'])) {
        update_post_meta($gallery_id, 'cpc_gallery_source', sanitize_text_field($args['source']));
    }
    if (!empty($args['source_id'])) {
        update_post_meta($gallery_id, 'cpc_gallery_source_id', (int)$args['source_id']);
    }

    cpc_media_get_gallery_dir($gallery_id, true);

    return (int)$gallery_id;
}

function cpc_media_create_item($args = array()) {
    $defaults = array(
        'gallery_id' => 0,
        'user_id' => get_current_user_id(),
        'title' => '',
        'description' => '',
        'mime_type' => '',
        'media_type' => 'photo',
        'source' => '',
        'source_id' => 0,
        'source_url' => '',
        'source_file' => '',
        'file_url' => '',
        'file_path' => '',
        'metadata' => array(),
        'migrated_files' => array(),
        'menu_order' => 0,
    );

    $args = wp_parse_args($args, $defaults);
    $gallery_id = (int)$args['gallery_id'];
    $user_id = (int)$args['user_id'];

    if (!$gallery_id || !$user_id) {
        return 0;
    }

    $media_id = wp_insert_post(array(
        'post_type' => 'cpc_media',
        'post_status' => 'publish',
        'post_author' => $user_id,
        'post_parent' => $gallery_id,
        'menu_order' => (int)$args['menu_order'],
        'post_title' => sanitize_text_field($args['title']),
        'post_content' => wp_kses_post($args['description']),
    ), true);

    if (is_wp_error($media_id) || !$media_id) {
        return 0;
    }

    update_post_meta($media_id, 'cpc_media_gallery_id', $gallery_id);
    update_post_meta($media_id, 'cpc_media_mime_type', sanitize_text_field($args['mime_type']));
    update_post_meta($media_id, 'cpc_media_type', sanitize_text_field($args['media_type']));
    update_post_meta($media_id, 'cpc_media_source', sanitize_text_field($args['source']));
    update_post_meta($media_id, 'cpc_media_source_id', (int)$args['source_id']);
    update_post_meta($media_id, 'cpc_media_source_url', esc_url_raw($args['source_url']));
    update_post_meta($media_id, 'cpc_media_source_file', (string)$args['source_file']);
    update_post_meta($media_id, 'cpc_media_file_url', esc_url_raw($args['file_url']));
    update_post_meta($media_id, 'cpc_media_file_path', (string)$args['file_path']);

    if (!empty($args['metadata']) && is_array($args['metadata'])) {
        update_post_meta($media_id, 'cpc_media_source_metadata', $args['metadata']);
    }

    if (!empty($args['migrated_files']) && is_array($args['migrated_files'])) {
        update_post_meta($media_id, 'cpc_media_migrated_files', $args['migrated_files']);
    }

    return (int)$media_id;
}

function cpc_media_update_gallery($gallery_id, $args = array()) {
    $gallery_id = (int)$gallery_id;
    $gallery = get_post($gallery_id);
    if (!$gallery || $gallery->post_type !== 'cpc_gallery') {
        return false;
    }

    $post_update = array('ID' => $gallery_id);

    if (isset($args['title'])) {
        $post_update['post_title'] = sanitize_text_field($args['title']);
    }
    if (isset($args['description'])) {
        $post_update['post_content'] = wp_kses_post($args['description']);
    }

    if (count($post_update) > 1) {
        $updated = wp_update_post($post_update, true);
        if (is_wp_error($updated)) {
            return false;
        }
    }

    if (isset($args['status'])) {
        update_post_meta($gallery_id, 'cpc_gallery_status', cpc_media_normalize_status($args['status']));
    }
    if (isset($args['type'])) {
        update_post_meta($gallery_id, 'cpc_gallery_type', cpc_media_normalize_type($args['type']));
    }

    return true;
}

function cpc_media_update_item($media_id, $args = array()) {
    $media_id = (int)$media_id;
    $media = get_post($media_id);
    if (!$media || $media->post_type !== 'cpc_media') {
        return false;
    }

    $post_update = array('ID' => $media_id);

    if (isset($args['title'])) {
        $post_update['post_title'] = sanitize_text_field($args['title']);
    }
    if (isset($args['description'])) {
        $post_update['post_content'] = wp_kses_post($args['description']);
    }

    if (count($post_update) <= 1) {
        return true;
    }

    $updated = wp_update_post($post_update, true);
    return !is_wp_error($updated);
}

function cpc_media_delete_media_files($media_id) {
    $media_id = (int)$media_id;
    if (!$media_id) {
        return;
    }

    $files = get_post_meta($media_id, 'cpc_media_migrated_files', true);
    if (empty($files) || !is_array($files)) {
        $single_path = (string)get_post_meta($media_id, 'cpc_media_file_path', true);
        if ($single_path && file_exists($single_path) && is_file($single_path)) {
            @unlink($single_path);
        }
        return;
    }

    foreach ($files as $entry) {
        if (empty($entry['path'])) {
            continue;
        }
        $path = (string)$entry['path'];
        if ($path && file_exists($path) && is_file($path)) {
            @unlink($path);
        }
    }
}

function cpc_media_delete_item($media_id, $delete_files = true) {
    $media_id = (int)$media_id;
    $media = get_post($media_id);
    if (!$media || $media->post_type !== 'cpc_media') {
        return false;
    }

    $gallery_id = (int)get_post_meta($media_id, 'cpc_media_gallery_id', true);

    if ($delete_files) {
        cpc_media_delete_media_files($media_id);
    }

    wp_delete_post($media_id, true);

    if ($gallery_id) {
        $count = count(cpc_media_get_gallery_items($gallery_id, array('posts_per_page' => -1, 'fields' => 'ids')));
        cpc_media_update_gallery_media_count($gallery_id, $count);
    }

    return true;
}

function cpc_media_delete_gallery($gallery_id, $delete_files = true) {
    $gallery_id = (int)$gallery_id;
    $gallery = get_post($gallery_id);
    if (!$gallery || $gallery->post_type !== 'cpc_gallery') {
        return false;
    }

    $media_items = cpc_media_get_gallery_items($gallery_id, array('posts_per_page' => -1));
    foreach ($media_items as $item) {
        cpc_media_delete_item($item->ID, $delete_files);
    }

    $gallery_dir = cpc_media_get_gallery_dir($gallery_id, false);
    if ($delete_files && $gallery_dir && is_dir($gallery_dir)) {
        cpc_media_delete_dir_recursive($gallery_dir);
    }

    wp_delete_post($gallery_id, true);
    return true;
}

function cpc_media_delete_dir_recursive($dir) {
    if (!$dir || !is_dir($dir)) {
        return;
    }

    $files = scandir($dir);
    if (!is_array($files)) {
        return;
    }

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $path = rtrim($dir, '/').'/'.$file;
        if (is_dir($path)) {
            cpc_media_delete_dir_recursive($path);
        } elseif (is_file($path)) {
            @unlink($path);
        }
    }

    @rmdir($dir);
}

function cpc_media_get_mediapress_term_slug($post_id, $taxonomy, $default = '') {
    $terms = get_the_terms($post_id, $taxonomy);
    if (is_wp_error($terms) || empty($terms)) {
        return $default;
    }

    $term = reset($terms);
    return !empty($term->slug) ? $term->slug : $default;
}

function cpc_media_find_imported_gallery_by_source($source, $source_id) {
    $posts = get_posts(array(
        'post_type' => 'cpc_gallery',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'cpc_gallery_source',
                'value' => sanitize_text_field($source),
            ),
            array(
                'key' => 'cpc_gallery_source_id',
                'value' => (int)$source_id,
            ),
        ),
    ));

    return !empty($posts) ? (int)$posts[0] : 0;
}

function cpc_media_find_imported_item_by_source($source, $source_id) {
    $posts = get_posts(array(
        'post_type' => 'cpc_media',
        'post_status' => 'publish',
        'posts_per_page' => 1,
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => 'cpc_media_source',
                'value' => sanitize_text_field($source),
            ),
            array(
                'key' => 'cpc_media_source_id',
                'value' => (int)$source_id,
            ),
        ),
    ));

    return !empty($posts) ? (int)$posts[0] : 0;
}

function cpc_media_map_mime_to_type($mime_type, $fallback = 'photo') {
    $mime_type = strtolower((string)$mime_type);

    if (strpos($mime_type, 'image/') === 0) {
        return 'photo';
    }
    if (strpos($mime_type, 'video/') === 0) {
        return 'video';
    }
    if (strpos($mime_type, 'audio/') === 0) {
        return 'audio';
    }

    return $fallback ? $fallback : 'doc';
}

function cpc_media_normalize_status($status) {
    $status = sanitize_key((string)$status);
    $allowed = array('public', 'loggedin', 'private');
    if (cpc_media_friendships_enabled() && get_option('cpc_media_enable_friend_visibility', 1)) {
        $allowed[] = 'friends';
    }

    return in_array($status, $allowed, true) ? $status : 'public';
}

function cpc_media_normalize_type($type) {
    $type = sanitize_key((string)$type);
    return in_array($type, array('photo', 'video', 'audio', 'doc'), true) ? $type : 'photo';
}

function cpc_media_user_can_create_gallery_for_context($component, $component_id, $user_id = 0) {
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

    if ($component === 'groups') {
        if ($component_id <= 0) {
            return false;
        }

        if (function_exists('cpc_is_group_member') && cpc_is_group_member($user_id, $component_id)) {
            return true;
        }
    }

    return false;
}

function cpc_media_user_can_upload_to_gallery($gallery_id, $user_id = 0) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }

    if (cpc_media_user_can_manage_gallery($gallery_id, $user_id)) {
        return true;
    }

    $component = cpc_media_get_gallery_component($gallery_id);
    $component_id = cpc_media_get_gallery_component_id($gallery_id);

    if ($component === 'groups' && $component_id > 0 && function_exists('cpc_is_group_member')) {
        return cpc_is_group_member($user_id, $component_id);
    }

    return false;
}

function cpc_media_upload_file_to_gallery($file, $gallery_id) {
    $result = array(
        'ok' => false,
        'error' => '',
        'url' => '',
        'path' => '',
        'mime_type' => '',
        'media_type' => 'photo',
        'metadata' => array(),
        'filename' => '',
    );

    $gallery_id = (int)$gallery_id;
    if (!$gallery_id || empty($file) || !is_array($file)) {
        $result['error'] = 'invalid_input';
        return $result;
    }

    if (!isset($file['error']) || (int)$file['error'] !== 0 || empty($file['tmp_name'])) {
        $result['error'] = 'upload_error';
        return $result;
    }

    $file_size = isset($file['size']) ? (int)$file['size'] : 0;
    $max_size_bytes = cpc_media_get_max_file_size() * 1024 * 1024;
    if ($file_size > 0 && $max_size_bytes > 0 && $file_size > $max_size_bytes) {
        $result['error'] = 'file_too_large';
        return $result;
    }

    $component = cpc_media_get_gallery_component($gallery_id);
    $component_id = cpc_media_get_gallery_component_id($gallery_id);
    $gallery = get_post($gallery_id);
    $summary = cpc_media_get_storage_summary($component, $component_id, $gallery ? (int)$gallery->post_author : 0);
    $remaining_bytes = isset($summary['remaining_bytes']) ? (int)$summary['remaining_bytes'] : 0;
    $limit_bytes = isset($summary['limit_bytes']) ? (int)$summary['limit_bytes'] : 0;
    if ($limit_bytes > 0 && $file_size > 0 && $file_size > $remaining_bytes) {
        $result['error'] = 'quota_exceeded';
        return $result;
    }

    $gallery_dir = cpc_media_get_gallery_dir($gallery_id, true);
    $gallery_url = cpc_media_get_gallery_url($gallery_id);
    if (!$gallery_dir || !$gallery_url) {
        $result['error'] = 'gallery_path_error';
        return $result;
    }

    $raw_name = isset($file['name']) ? (string)$file['name'] : '';
    $clean_name = sanitize_file_name($raw_name);
    if ($clean_name === '') {
        $clean_name = 'media-'.time();
    }

    $target_name = wp_unique_filename($gallery_dir, $clean_name);
    $target_path = trailingslashit($gallery_dir).$target_name;

    $moved = @move_uploaded_file($file['tmp_name'], $target_path);
    if (!$moved) {
        $moved = @rename($file['tmp_name'], $target_path);
    }

    if (!$moved) {
        $result['error'] = 'move_failed';
        return $result;
    }

    $filetype = wp_check_filetype($target_name);
    $mime_type = !empty($filetype['type']) ? $filetype['type'] : (isset($file['type']) ? (string)$file['type'] : '');
    $allowed_type = cpc_media_map_item_type_to_allowed_type(cpc_media_map_mime_to_type($mime_type, cpc_media_get_gallery_type($gallery_id)));
    if (!cpc_media_is_type_allowed($allowed_type)) {
        @unlink($target_path);
        $result['error'] = 'type_not_allowed';
        return $result;
    }

    $result['ok'] = true;
    $result['path'] = $target_path;
    $result['url'] = trailingslashit($gallery_url).$target_name;
    $result['mime_type'] = $mime_type;
    $result['media_type'] = cpc_media_map_mime_to_type($mime_type, cpc_media_get_gallery_type($gallery_id));
    $result['filename'] = $target_name;

    if (strpos((string)$mime_type, 'image/') === 0) {
        $image_size = @getimagesize($target_path);
        if ($image_size && isset($image_size[0], $image_size[1])) {
            $result['metadata']['width'] = (int)$image_size[0];
            $result['metadata']['height'] = (int)$image_size[1];
        }
    }

    return $result;
}

function cpc_media_notice_message($code) {
    $map = array(
        'created' => __('Galerie wurde erstellt.', CPC2_TEXT_DOMAIN),
        'uploaded' => __('Dateien wurden hochgeladen.', CPC2_TEXT_DOMAIN),
        'denied' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN),
        'invalid' => __('Ungültige Anfrage.', CPC2_TEXT_DOMAIN),
        'failed' => __('Aktion fehlgeschlagen.', CPC2_TEXT_DOMAIN),
        'upload_failed' => __('Mindestens eine Datei konnte nicht hochgeladen werden.', CPC2_TEXT_DOMAIN),
    );

    return isset($map[$code]) ? $map[$code] : '';
}

function cpc_media_get_current_request_url() {
    if (empty($_SERVER['REQUEST_URI'])) {
        return '';
    }

    $request_uri = wp_unslash($_SERVER['REQUEST_URI']);
    $request_uri = preg_replace('/[\r\n].*/', '', $request_uri);
    if (!$request_uri) {
        return '';
    }

    return home_url($request_uri);
}

function cpc_media_normalize_redirect_url($redirect = '', $fallback = '') {
    $redirect = is_string($redirect) ? trim(wp_unslash($redirect)) : '';

    if ($redirect !== '') {
        $validated = wp_validate_redirect($redirect, '');
        if ($validated && !cpc_is_admin_ajax_url($validated)) {
            $redirect = $validated;
        } elseif (strpos($redirect, '/') === 0) {
            $candidate = home_url($redirect);
            $redirect = cpc_is_admin_ajax_url($candidate) ? '' : $candidate;
        } else {
            $redirect = '';
        }
    }

    $redirect = cpc_normalize_frontend_redirect($redirect, $fallback ? $fallback : home_url('/'));

    $redirect = remove_query_arg(array('cpc_media_notice', 'cpc_media_gallery_id', 'cpc_media_uploaded'), $redirect);

    return $redirect;
}

function cpc_media_build_safe_redirect($fallback = '') {
    $posted_redirect = isset($_POST['cpc_media_redirect']) ? $_POST['cpc_media_redirect'] : '';
    $request_url = cpc_media_get_current_request_url();
    $referer = wp_get_referer();

    if (!$fallback) {
        $fallback = $request_url ? $request_url : ($referer ? $referer : home_url('/'));
    }

    if ($posted_redirect) {
        return cpc_media_normalize_redirect_url($posted_redirect, $fallback);
    }

    if ($request_url) {
        return cpc_media_normalize_redirect_url($request_url, $fallback);
    }

    if ($referer) {
        return cpc_media_normalize_redirect_url($referer, $fallback);
    }

    return cpc_media_normalize_redirect_url('', $fallback);
}

function cpc_media_handle_create_gallery_request() {
    if (empty($_POST['cpc_media_action']) || $_POST['cpc_media_action'] !== 'create_gallery') {
        return;
    }

    $redirect = cpc_media_build_safe_redirect();

    if (!is_user_logged_in()) {
        wp_safe_redirect(add_query_arg('cpc_media_notice', 'denied', $redirect));
        exit;
    }

    $nonce = isset($_POST['cpc_media_nonce']) ? sanitize_text_field(wp_unslash($_POST['cpc_media_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'cpc_media_frontend_action')) {
        wp_safe_redirect(add_query_arg('cpc_media_notice', 'invalid', $redirect));
        exit;
    }

    $component = isset($_POST['cpc_media_component']) ? sanitize_key(wp_unslash($_POST['cpc_media_component'])) : 'members';
    $component_id = isset($_POST['cpc_media_component_id']) ? (int)$_POST['cpc_media_component_id'] : 0;
    $title = isset($_POST['cpc_media_gallery_title']) ? sanitize_text_field(wp_unslash($_POST['cpc_media_gallery_title'])) : '';
    $description = isset($_POST['cpc_media_gallery_description']) ? wp_kses_post(wp_unslash($_POST['cpc_media_gallery_description'])) : '';
    $status = isset($_POST['cpc_media_gallery_status']) ? cpc_media_normalize_status(wp_unslash($_POST['cpc_media_gallery_status'])) : 'public';
    $type = isset($_POST['cpc_media_gallery_type']) ? cpc_media_normalize_type(wp_unslash($_POST['cpc_media_gallery_type'])) : 'photo';
    $user_id = get_current_user_id();

    if (!$title || !cpc_media_user_can_create_gallery_for_context($component, $component_id, $user_id)) {
        wp_safe_redirect(add_query_arg('cpc_media_notice', 'denied', $redirect));
        exit;
    }

    $gallery_id = cpc_media_create_gallery(array(
        'title' => $title,
        'description' => $description,
        'user_id' => $user_id,
        'component' => $component,
        'component_id' => $component_id,
        'status' => $status,
        'type' => $type,
    ));

    if (!$gallery_id) {
        wp_safe_redirect(add_query_arg('cpc_media_notice', 'failed', $redirect));
        exit;
    }

    $redirect = add_query_arg('cpc_media_notice', 'created', $redirect);
    $redirect = add_query_arg('cpc_media_gallery_id', (int)$gallery_id, $redirect);
    wp_safe_redirect($redirect);
    exit;
}

function cpc_media_handle_upload_request() {
    if (empty($_POST['cpc_media_action']) || $_POST['cpc_media_action'] !== 'upload_media') {
        return;
    }

    $redirect = cpc_media_build_safe_redirect();

    if (!is_user_logged_in()) {
        wp_safe_redirect(add_query_arg('cpc_media_notice', 'denied', $redirect));
        exit;
    }

    $nonce = isset($_POST['cpc_media_nonce']) ? sanitize_text_field(wp_unslash($_POST['cpc_media_nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'cpc_media_frontend_action')) {
        wp_safe_redirect(add_query_arg('cpc_media_notice', 'invalid', $redirect));
        exit;
    }

    $gallery_id = isset($_POST['cpc_media_gallery_id']) ? (int)$_POST['cpc_media_gallery_id'] : 0;
    $user_id = get_current_user_id();

    if (!$gallery_id || !cpc_media_user_can_upload_to_gallery($gallery_id, $user_id)) {
        wp_safe_redirect(add_query_arg('cpc_media_notice', 'denied', $redirect));
        exit;
    }

    if (empty($_FILES['cpc_media_files'])) {
        wp_safe_redirect(add_query_arg('cpc_media_notice', 'invalid', $redirect));
        exit;
    }

    $files = $_FILES['cpc_media_files'];
    $names = isset($files['name']) && is_array($files['name']) ? $files['name'] : array();
    $success = 0;
    $failed = 0;

    foreach ($names as $index => $name) {
        $file = array(
            'name' => isset($files['name'][$index]) ? $files['name'][$index] : '',
            'type' => isset($files['type'][$index]) ? $files['type'][$index] : '',
            'tmp_name' => isset($files['tmp_name'][$index]) ? $files['tmp_name'][$index] : '',
            'error' => isset($files['error'][$index]) ? (int)$files['error'][$index] : 0,
            'size' => isset($files['size'][$index]) ? (int)$files['size'][$index] : 0,
        );

        if (empty($file['name'])) {
            continue;
        }

        $uploaded = cpc_media_upload_file_to_gallery($file, $gallery_id);
        if (empty($uploaded['ok'])) {
            $failed++;
            continue;
        }

        $media_id = cpc_media_create_item(array(
            'gallery_id' => $gallery_id,
            'user_id' => $user_id,
            'title' => pathinfo((string)$file['name'], PATHINFO_FILENAME),
            'description' => '',
            'mime_type' => $uploaded['mime_type'],
            'media_type' => $uploaded['media_type'],
            'source' => 'psc-local',
            'source_id' => 0,
            'source_url' => $uploaded['url'],
            'source_file' => $uploaded['path'],
            'file_url' => $uploaded['url'],
            'file_path' => $uploaded['path'],
            'metadata' => $uploaded['metadata'],
            'migrated_files' => array(
                array(
                    'role' => 'original',
                    'path' => $uploaded['path'],
                    'url' => $uploaded['url'],
                ),
            ),
        ));

        if ($media_id) {
            $success++;
        } else {
            $failed++;
        }
    }

    if ($success > 0) {
        $current_count = cpc_media_get_gallery_media_count($gallery_id);
        cpc_media_update_gallery_media_count($gallery_id, $current_count + $success);
    }

    $notice = $success > 0 ? 'uploaded' : 'failed';
    if ($failed > 0 && $success > 0) {
        $notice = 'upload_failed';
    }

    $redirect = add_query_arg('cpc_media_notice', $notice, $redirect);
    $redirect = add_query_arg('cpc_media_uploaded', $success, $redirect);
    wp_safe_redirect($redirect);
    exit;
}

add_action('init', 'cpc_media_handle_create_gallery_request');
add_action('init', 'cpc_media_handle_upload_request');

function cpc_media_copy_file($source_file, $target_file) {
    if (!$source_file || !$target_file) {
        return false;
    }

    if (!file_exists($source_file) || !is_readable($source_file)) {
        return false;
    }

    $target_dir = dirname($target_file);
    if (!file_exists($target_dir) && !wp_mkdir_p($target_dir)) {
        return false;
    }

    return @copy($source_file, $target_file);
}

function cpc_media_migrate_attachment_files_to_gallery($source_media_id, $gallery_id) {
    $source_media_id = (int)$source_media_id;
    $gallery_id = (int)$gallery_id;

    $result = array(
        'primary_url' => '',
        'primary_path' => '',
        'files' => array(),
        'errors' => array(),
    );

    if (!$source_media_id || !$gallery_id) {
        $result['errors'][] = 'invalid_input';
        return $result;
    }

    $gallery_dir = cpc_media_get_gallery_dir($gallery_id, true);
    $gallery_url = cpc_media_get_gallery_url($gallery_id);
    if (!$gallery_dir || !$gallery_url) {
        $result['errors'][] = 'missing_gallery_path';
        return $result;
    }

    $source_file = get_attached_file($source_media_id);
    if (!$source_file || !file_exists($source_file)) {
        $result['errors'][] = 'missing_source_file';
        return $result;
    }

    $source_basename = sanitize_file_name(wp_basename($source_file));
    if ($source_basename === '') {
        $result['errors'][] = 'invalid_source_filename';
        return $result;
    }

    $base_name = $source_media_id.'-'.$source_basename;
    $target_name = wp_unique_filename($gallery_dir, $base_name);
    $target_file = trailingslashit($gallery_dir).$target_name;

    if (!cpc_media_copy_file($source_file, $target_file)) {
        $result['errors'][] = 'copy_original_failed';
        return $result;
    }

    $result['primary_path'] = $target_file;
    $result['primary_url'] = trailingslashit($gallery_url).$target_name;
    $result['files'][] = array(
        'role' => 'original',
        'source' => $source_file,
        'path' => $target_file,
        'url' => $result['primary_url'],
    );

    $metadata = wp_get_attachment_metadata($source_media_id);
    $source_dir = trailingslashit(dirname($source_file));

    if (!empty($metadata['sizes']) && is_array($metadata['sizes'])) {
        foreach ($metadata['sizes'] as $size_key => $size_info) {
            if (empty($size_info['file'])) {
                continue;
            }

            $size_file_name = sanitize_file_name($size_info['file']);
            if ($size_file_name === '') {
                continue;
            }

            $size_source_file = $source_dir.$size_file_name;
            if (!file_exists($size_source_file)) {
                continue;
            }

            $size_target_name = wp_unique_filename($gallery_dir, $source_media_id.'-'.$size_key.'-'.$size_file_name);
            $size_target_file = trailingslashit($gallery_dir).$size_target_name;

            if (!cpc_media_copy_file($size_source_file, $size_target_file)) {
                $result['errors'][] = 'copy_size_failed:'.$size_key;
                continue;
            }

            $result['files'][] = array(
                'role' => 'size',
                'size' => $size_key,
                'source' => $size_source_file,
                'path' => $size_target_file,
                'url' => trailingslashit($gallery_url).$size_target_name,
            );
        }
    }

    return $result;
}

function cpc_media_import_mediapress_media_item($source_media_id, $gallery_id, $copy_files = true) {
    $source_media_id = (int)$source_media_id;
    $gallery_id = (int)$gallery_id;

    if (!$source_media_id || !$gallery_id) {
        return 0;
    }

    $existing = cpc_media_find_imported_item_by_source('mediapress', $source_media_id);
    if ($existing) {
        return $existing;
    }

    $attachment = get_post($source_media_id);
    if (!$attachment || $attachment->post_type !== 'attachment') {
        return 0;
    }

    $gallery = get_post($gallery_id);
    if (!$gallery || $gallery->post_type !== 'cpc_gallery') {
        return 0;
    }

    $mime_type = get_post_mime_type($attachment);
    $source_url = wp_get_attachment_url($source_media_id);
    $source_file = get_attached_file($source_media_id);
    $metadata = wp_get_attachment_metadata($source_media_id);
    $migration = array('files' => array());
    if ($copy_files) {
        $migration = cpc_media_migrate_attachment_files_to_gallery($source_media_id, $gallery_id);
    }

    $file_url = !empty($migration['primary_url']) ? $migration['primary_url'] : $source_url;
    $file_path = !empty($migration['primary_path']) ? $migration['primary_path'] : $source_file;

    return cpc_media_create_item(array(
        'gallery_id' => $gallery_id,
        'user_id' => (int)$gallery->post_author,
        'title' => $attachment->post_title,
        'description' => $attachment->post_content,
        'mime_type' => $mime_type,
        'media_type' => cpc_media_map_mime_to_type($mime_type, cpc_media_get_gallery_type($gallery_id)),
        'source' => 'mediapress',
        'source_id' => $source_media_id,
        'source_url' => $source_url,
        'source_file' => $source_file,
        'file_url' => $file_url,
        'file_path' => $file_path,
        'metadata' => is_array($metadata) ? $metadata : array(),
        'migrated_files' => isset($migration['files']) ? $migration['files'] : array(),
        'menu_order' => isset($attachment->menu_order) ? (int)$attachment->menu_order : 0,
    ));
}

function cpc_media_import_mediapress_gallery($source_gallery_id, $copy_files = true) {
    $source_gallery_id = (int)$source_gallery_id;
    if (!$source_gallery_id) {
        return 0;
    }

    $existing = cpc_media_find_imported_gallery_by_source('mediapress', $source_gallery_id);
    if ($existing) {
        return $existing;
    }

    $source_gallery = get_post($source_gallery_id);
    if (!$source_gallery || $source_gallery->post_type !== 'mpp-gallery') {
        return 0;
    }

    $component = cpc_media_get_mediapress_term_slug($source_gallery_id, 'mpp-component', 'members');
    if (!in_array($component, array('members', 'groups', 'sitewide'), true)) {
        $component = 'members';
    }

    $component_id = (int)get_post_meta($source_gallery_id, '_mpp_component_id', true);
    $status = cpc_media_get_mediapress_term_slug($source_gallery_id, 'mpp-status', 'public');
    $type = cpc_media_get_mediapress_term_slug($source_gallery_id, 'mpp-type', 'photo');
    $media_items = get_posts(array(
        'post_type' => 'attachment',
        'post_parent' => $source_gallery_id,
        'post_status' => 'inherit',
        'posts_per_page' => -1,
        'fields' => 'ids',
    ));

    $gallery_id = cpc_media_create_gallery(array(
        'title' => $source_gallery->post_title,
        'description' => $source_gallery->post_content,
        'user_id' => (int)$source_gallery->post_author,
        'component' => $component,
        'component_id' => $component_id,
        'status' => $status,
        'type' => $type,
        'source' => 'mediapress',
        'source_id' => $source_gallery_id,
        'media_count' => count($media_items),
    ));

    if (!$gallery_id) {
        return 0;
    }

    $imported_media_ids = array();
    foreach ($media_items as $media_item_id) {
        $imported_media_id = cpc_media_import_mediapress_media_item($media_item_id, $gallery_id, $copy_files);
        if ($imported_media_id) {
            $imported_media_ids[] = (int)$imported_media_id;
        }
    }

    update_post_meta($gallery_id, 'cpc_gallery_source_media_ids', array_map('intval', $media_items));
    update_post_meta($gallery_id, 'cpc_gallery_media_ids', $imported_media_ids);
    cpc_media_update_gallery_media_count($gallery_id, count($imported_media_ids));
    update_post_meta($gallery_id, 'cpc_gallery_source_permalink', get_permalink($source_gallery_id));

    $thumbnail_id = (int)get_post_meta($source_gallery_id, '_thumbnail_id', true);
    if ($thumbnail_id) {
        update_post_meta($gallery_id, 'cpc_gallery_cover_id', $thumbnail_id);
    }

    return $gallery_id;
}

function cpc_media_import_mediapress_galleries($limit = 25, $copy_files = true) {
    $limit = max(1, min(200, (int)$limit));

    $source_galleries = get_posts(array(
        'post_type' => 'mpp-gallery',
        'post_status' => 'any',
        'posts_per_page' => $limit,
        'orderby' => 'ID',
        'order' => 'ASC',
    ));

    $results = array(
        'processed' => 0,
        'imported' => 0,
        'skipped' => 0,
        'items' => array(),
    );

    foreach ($source_galleries as $source_gallery) {
        $results['processed']++;

        $existing = cpc_media_find_imported_gallery_by_source('mediapress', $source_gallery->ID);
        if ($existing) {
            $results['skipped']++;
            $results['items'][] = array(
                'source_id' => (int)$source_gallery->ID,
                'gallery_id' => $existing,
                'status' => 'skipped',
            );
            continue;
        }

        $gallery_id = cpc_media_import_mediapress_gallery($source_gallery->ID, $copy_files);
        if ($gallery_id) {
            $results['imported']++;
            $results['items'][] = array(
                'source_id' => (int)$source_gallery->ID,
                'gallery_id' => $gallery_id,
                'status' => 'imported',
            );
        } else {
            $results['skipped']++;
            $results['items'][] = array(
                'source_id' => (int)$source_gallery->ID,
                'gallery_id' => 0,
                'status' => 'failed',
            );
        }
    }

    return $results;
}

/**
 * ========== LIGHTBOX & SINGLE MEDIA VIEW ==========
 */

/**
 * Check if lightbox feature is enabled
 */
function cpc_media_lightbox_enabled() {
    return (bool)apply_filters('cpc_media_lightbox_enabled', (bool)get_option('cpc_media_enable_lightbox', 1));
}

/**
 * Check if media reorder is enabled
 */
function cpc_media_reorder_enabled() {
    return (bool)apply_filters('cpc_media_reorder_enabled', (bool)get_option('cpc_media_enable_reorder', 1));
}

/**
 * Check if cover selector is enabled
 */
function cpc_media_cover_selector_enabled() {
    return (bool)apply_filters('cpc_media_cover_selector_enabled', (bool)get_option('cpc_media_enable_cover_selector', 1));
}

/**
 * Get gallery grid columns setting
 */
function cpc_media_get_gallery_grid_columns() {
    return max(1, min(6, (int)apply_filters('cpc_media_gallery_grid_columns', (int)get_option('cpc_media_gallery_grid_columns', 3))));
}

/**
 * Get max file size in MB
 */
function cpc_media_get_max_file_size() {
    return max(1, min(500, (int)apply_filters('cpc_media_max_file_size', (int)get_option('cpc_media_max_file_size', 50))));
}

/**
 * Get thumbnail quality percentage
 */
function cpc_media_get_thumbnail_quality() {
    return max(40, min(100, (int)apply_filters('cpc_media_thumbnail_quality', (int)get_option('cpc_media_thumbnail_quality', 85))));
}

/**
 * Get allowed media types
 */
function cpc_media_get_allowed_types() {
    $types = get_option('cpc_media_allowed_types', array('image', 'video', 'audio', 'document'));
    if (empty($types)) {
        $types = array('image', 'video', 'audio', 'document');
    }
    return apply_filters('cpc_media_allowed_types', $types);
}

/**
 * Check if media type is allowed
 */
function cpc_media_is_type_allowed($type) {
    $allowed = cpc_media_get_allowed_types();
    return in_array($type, $allowed, true);
}

/**
 * Check if lightbox auto-play is enabled
 */
function cpc_media_lightbox_autoplay() {
    return (bool)apply_filters('cpc_media_lightbox_autoplay', (bool)get_option('cpc_media_lightbox_autoplay', 0));
}

/**
 * Check if lightbox loop is enabled
 */
function cpc_media_lightbox_loop() {
    return (bool)apply_filters('cpc_media_lightbox_loop', (bool)get_option('cpc_media_lightbox_loop', 1));
}

/**
 * Check if item descriptions should be shown
 */
function cpc_media_show_item_descriptions() {
    return (bool)apply_filters('cpc_media_show_item_descriptions', (bool)get_option('cpc_media_show_item_descriptions', 1));
}

/**
 * Get thumbnail URL for media item
 * Used for preview strips, cover selectors, etc.
 */
function cpc_media_get_item_thumbnail_url($media_id, $size = 'medium') {
    $media = get_post($media_id);
    if (!$media) {
        return '';
    }

    $file_url = cpc_media_get_item_url($media_id);
    if (!$file_url) {
        return '';
    }

    $media_type = cpc_media_get_item_type($media_id);
    
    // For images, generate thumbnail via WP image processing
    if (in_array($media_type, array('photo', 'image'), true)) {
        $attachment_id = cpc_media_get_item_attachment_id($media_id);
        if ($attachment_id) {
            $img_data = wp_get_attachment_image_src($attachment_id, $size);
            if ($img_data) {
                return $img_data[0];
            }
        }
        // Fallback: return original if no attachment
        return $file_url;
    }

    // For non-images, return placeholder
    return apply_filters('cpc_media_get_item_thumbnail_url', '', $media_id, $size);
}

/**
 * Get full URL to media file
 */
function cpc_media_get_item_url($media_id) {
    $media = get_post($media_id);
    if (!$media || $media->post_type !== 'cpc_media') {
        return '';
    }

    $file_url = (string)get_post_meta($media_id, 'cpc_media_file_url', true);
    if ($file_url) {
        return esc_url($file_url);
    }

    // Fallback to source_url
    $source_url = (string)get_post_meta($media_id, 'cpc_media_source_url', true);
    if ($source_url) {
        return esc_url($source_url);
    }

    return '';
}

/**
 * Get normalized media item type
 */
function cpc_media_get_item_type($media_id) {
    $media_id = (int)$media_id;
    $media = get_post($media_id);
    if (!$media || $media->post_type !== 'cpc_media') {
        return 'doc';
    }

    $type = sanitize_key((string)get_post_meta($media_id, 'cpc_media_type', true));

    // Backward compatibility for alternate labels.
    if ($type === 'image') {
        $type = 'photo';
    }
    if ($type === 'document') {
        $type = 'doc';
    }

    if (in_array($type, array('photo', 'video', 'audio', 'doc'), true)) {
        return $type;
    }

    $mime_type = cpc_media_get_media_mime_type($media_id);
    if ($mime_type) {
        return cpc_media_map_mime_to_type($mime_type, 'doc');
    }

    return 'doc';
}

/**
 * Get attachment ID linked to media item if exists
 */
function cpc_media_get_item_attachment_id($media_id) {
    $attachment_id = (int)get_post_meta($media_id, 'cpc_media_attachment_id', true);
    if ($attachment_id > 0 && get_post($attachment_id)) {
        return $attachment_id;
    }
    return 0;
}

/**
 * Render media item for lightbox display
 * Returns HTML string that will be shown in modal
 */
function cpc_media_render_lightbox_content($media) {
    if (is_int($media)) {
        $media = get_post($media);
    }

    if (!$media || $media->post_type !== 'cpc_media') {
        return '';
    }

    $media_type = cpc_media_get_item_type($media->ID);
    $file_url = cpc_media_get_item_url($media->ID);
    $gallery_id = (int)get_post_meta($media->ID, 'cpc_media_gallery_id', true);
    $gallery = $gallery_id ? get_post($gallery_id) : null;

    ob_start();
    ?>
    <div class="cpc_media_lightbox_entry mpp-lightbox-media-entry" data-media-id="<?php echo esc_attr($media->ID); ?>">
        
        <!-- Media Display -->
        <div class="cpc_media_lightbox_media">
            <?php if ($media_type === 'photo' || $media_type === 'image'): ?>
                <img src="<?php echo esc_url($file_url); ?>" 
                     alt="<?php echo esc_attr($media->post_title); ?>"
                     class="cpc_media_lightbox_image" />
            <?php elseif ($media_type === 'video'): ?>
                <div class="cpc_media_lightbox_video cpc_media_video_container">
                    <video controls style="max-width: 100%; max-height: 600px;">
                        <source src="<?php echo esc_url($file_url); ?>" />
                        <?php esc_html_e('Ihr Browser unterstützt dieses Video nicht.', CPC2_TEXT_DOMAIN); ?>
                    </video>
                </div>
            <?php elseif ($media_type === 'audio'): ?>
                <div class="cpc_media_lightbox_audio cpc_media_audio_container">
                    <audio controls style="width: 100%; max-width: 100%;">
                        <source src="<?php echo esc_url($file_url); ?>" />
                        <?php esc_html_e('Ihr Browser unterstützt dieses Audio nicht.', CPC2_TEXT_DOMAIN); ?>
                    </audio>
                </div>
            <?php else: ?>
                <div class="cpc_media_lightbox_file cpc_media_file_container">
                    <div class="cpc_media_file_icon">
                        <span class="dashicons dashicons-media-default"></span>
                    </div>
                    <p class="cpc_media_file_title"><?php echo esc_html($media->post_title); ?></p>
                    <a href="<?php echo esc_url($file_url); ?>" 
                       class="button button-primary cpc_media_file_download"
                       download>
                        <?php esc_html_e('Datei herunterladen', CPC2_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>

        <!-- Media Info -->
        <div class="cpc_media_lightbox_info mpp-item-meta mpp-media-meta">
            <h2 class="cpc_media_lightbox_title"><?php echo esc_html($media->post_title); ?></h2>
            
            <?php if ($media->post_content): ?>
                <div class="cpc_media_lightbox_description">
                    <?php echo wp_kses_post($media->post_content); ?>
                </div>
            <?php endif; ?>

            <!-- Meta Info Row -->
            <div class="cpc_media_lightbox_meta">
                <span class="cpc_media_lightbox_author">
                    <?php echo esc_html(get_the_author_meta('display_name', $media->post_author)); ?>
                </span>
                <span class="cpc_media_lightbox_date">
                    <?php echo esc_html(get_the_date('j. M Y', $media)); ?>
                </span>
                <?php if ($gallery): ?>
                    <span class="cpc_media_lightbox_gallery">
                        <a href="<?php echo esc_url(get_permalink($gallery_id)); ?>">
                            <?php echo esc_html($gallery->post_title); ?>
                        </a>
                    </span>
                <?php endif; ?>
            </div>

            <!-- Actions for logged-in users -->
            <?php if (is_user_logged_in()):
                $current_user = get_current_user_id();
                if (cpc_media_user_can_manage_media($media->ID, $current_user)):
            ?>
                <div class="cpc_media_lightbox_actions">
                    <a href="#" class="cpc_media_lightbox_edit" data-media-id="<?php echo esc_attr($media->ID); ?>">
                        <?php esc_html_e('Bearbeiten', CPC2_TEXT_DOMAIN); ?>
                    </a>
                    <a href="#" class="cpc_media_lightbox_delete" data-media-id="<?php echo esc_attr($media->ID); ?>">
                        <?php esc_html_e('Löschen', CPC2_TEXT_DOMAIN); ?>
                    </a>
                </div>
            <?php endif; endif; ?>
        </div>

    </div>
    <?php
    return ob_get_clean();
}

/**
 * Get gallery cover ID
 */
function cpc_media_get_gallery_cover_id($gallery_id) {
    $gallery_id = (int)$gallery_id;
    $cover_id = (int)get_post_meta($gallery_id, 'cpc_media_gallery_cover_id', true);
    if ($cover_id <= 0) {
        $cover_id = (int)get_post_meta($gallery_id, 'cpc_gallery_cover_id', true);
    }
    if ($cover_id > 0 && get_post($cover_id)) {
        return $cover_id;
    }
    
    // Auto-select first item if no cover set
    $items = cpc_media_get_gallery_items($gallery_id, array('posts_per_page' => 1));
    return (!empty($items)) ? $items[0]->ID : 0;
}

/**
 * Update gallery cover URL (already exists, but alias for clarity)
 */
function cpc_media_set_gallery_cover($gallery_id, $media_id) {
    if (!get_post($media_id) || !get_post($gallery_id)) {
        return false;
    }

    // Keep both keys in sync for backward compatibility with older data.
    update_post_meta($gallery_id, 'cpc_media_gallery_cover_id', (int)$media_id);
    update_post_meta($gallery_id, 'cpc_gallery_cover_id', (int)$media_id);
    return true;
}
