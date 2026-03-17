<?php

function cpc_activity_plus_is_enabled() {
    return (bool)get_option('cpc_activity_plus_enabled');
}

function cpc_activity_plus_normalize_theme($theme) {
    $theme = sanitize_key((string)$theme);
    if ($theme === 'modern') {
        $theme = 'new';
    }
    if ($theme === 'compact') {
        $theme = 'round';
    }
    return in_array($theme, array('default', 'new', 'round'), true) ? $theme : 'default';
}

function cpc_activity_plus_normalize_alignment($alignment) {
    $alignment = sanitize_key((string)$alignment);
    return in_array($alignment, array('left', 'right'), true) ? $alignment : 'left';
}

function cpc_activity_plus_normalize_media_max_width($width) {
    return cpc_activity_plus_normalize_media_max_width_value($width, '%');
}

function cpc_activity_plus_normalize_media_max_width_unit($unit) {
    $unit = trim((string)$unit);
    return in_array($unit, array('%', 'px'), true) ? $unit : '%';
}

function cpc_activity_plus_normalize_media_max_width_value($width, $unit = '%') {
    $unit = cpc_activity_plus_normalize_media_max_width_unit($unit);
    $width = (int)$width;

    if ($unit === 'px') {
        if ($width < 80) {
            $width = 80;
        }
        if ($width > 2400) {
            $width = 2400;
        }
        return $width;
    }

    if ($width < 10) {
        $width = 10;
    }
    if ($width > 100) {
        $width = 100;
    }
    return $width;
}

function cpc_activity_plus_normalize_user_cloud_limit_mb($limit_mb) {
    $limit_mb = (int)$limit_mb;
    if ($limit_mb < 1) {
        $limit_mb = 1;
    }
    if ($limit_mb > 10240) {
        $limit_mb = 10240;
    }
    return $limit_mb;
}

function cpc_activity_plus_get_user_cloud_folder_name($user_id) {
    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return 'user-0';
    }

    $stored = get_user_meta($user_id, 'cpc_activity_plus_cloud_folder', true);
    if (!empty($stored) && preg_match('/^[a-z0-9\-]+$/', $stored)) {
        return $stored;
    }

    $user = get_user_by('id', $user_id);
    $slug = $user ? sanitize_title($user->user_login) : '';
    if ($slug === '') {
        $slug = 'user';
    }

    $folder = $slug.'-'.$user_id;
    $folder = apply_filters('cpc_activity_plus_user_cloud_folder_name', $folder, $user_id, $user);
    $folder = sanitize_title($folder);
    if ($folder === '') {
        $folder = 'user-'.$user_id;
    }

    update_user_meta($user_id, 'cpc_activity_plus_cloud_folder', $folder);

    return $folder;
}

function cpc_activity_plus_get_settings() {

    $media_max_width_unit = cpc_activity_plus_normalize_media_max_width_unit(get_option('cpc_activity_plus_media_max_width_unit', '%'));
    $media_max_width_value = cpc_activity_plus_normalize_media_max_width_value(
        get_option('cpc_activity_plus_media_max_width', 100),
        $media_max_width_unit
    );

    $settings = array(
        'enabled' => cpc_activity_plus_is_enabled(),
        'allow_images' => (bool)get_option('cpc_activity_plus_allow_images'),
        'allow_links' => (bool)get_option('cpc_activity_plus_allow_links'),
        'allow_video' => (bool)get_option('cpc_activity_plus_allow_video'),
        'max_images' => (int)get_option('cpc_activity_plus_max_images'),
        'cleanup_on_delete' => (bool)get_option('cpc_activity_plus_cleanup_on_delete'),
        'theme' => cpc_activity_plus_normalize_theme(get_option('cpc_activity_plus_theme', 'default')),
        'alignment' => cpc_activity_plus_normalize_alignment(get_option('cpc_activity_plus_alignment', 'left')),
        'media_max_width' => $media_max_width_value,
        'media_max_width_value' => $media_max_width_value,
        'media_max_width_unit' => $media_max_width_unit,
        'use_builtin_lightbox' => (bool)get_option('cpc_activity_plus_use_builtin_lightbox'),
        'user_cloud_limit_mb' => cpc_activity_plus_normalize_user_cloud_limit_mb(get_option('cpc_activity_plus_user_cloud_limit_mb', 50)),
    );

    if (!$settings['max_images']) {
        $settings['max_images'] = 5;
    }

    return $settings;
}

function cpc_activity_plus_global_wall_enabled() {
    return (bool)get_option('cpc_activity_plus_global_wall_enabled');
}

function cpc_activity_plus_global_wall_page_id() {
    return max(0, (int)get_option('cpc_activity_plus_global_wall_page', 0));
}

function cpc_activity_plus_global_wall_title() {
    $title = trim((string)get_option('cpc_activity_plus_global_wall_title', 'Aktivitätswall'));
    return $title !== '' ? $title : __('Aktivitätswall', CPC2_TEXT_DOMAIN);
}

function cpc_activity_plus_global_wall_per_page() {
    return max(5, min(50, (int)get_option('cpc_activity_plus_global_wall_per_page', 12)));
}

function cpc_activity_plus_global_wall_include_group_posts() {
    return (bool)get_option('cpc_activity_plus_global_wall_include_group_posts', 1);
}

function cpc_activity_plus_global_wall_exclude_system_posts() {
    return (bool)get_option('cpc_activity_plus_global_wall_exclude_system_posts', 1);
}

function cpc_activity_plus_global_wall_push_enabled() {
    return (bool)get_option('cpc_activity_plus_global_wall_push_enabled', 0);
}

function cpc_activity_plus_get_global_wall_settings() {
    return array(
        'enabled' => cpc_activity_plus_global_wall_enabled(),
        'page_id' => cpc_activity_plus_global_wall_page_id(),
        'title' => cpc_activity_plus_global_wall_title(),
        'per_page' => cpc_activity_plus_global_wall_per_page(),
        'include_group_posts' => cpc_activity_plus_global_wall_include_group_posts(),
        'exclude_system_posts' => cpc_activity_plus_global_wall_exclude_system_posts(),
        'push_enabled' => cpc_activity_plus_global_wall_push_enabled(),
    );
}

function cpc_activity_plus_maybe_create_global_wall_page() {
    $existing_page_id = cpc_activity_plus_global_wall_page_id();
    if ($existing_page_id > 0 && get_post($existing_page_id)) {
        return $existing_page_id;
    }

    $page_slug = sanitize_title(cpc_activity_plus_global_wall_title());
    if ($page_slug === '') {
        $page_slug = 'aktivitaetswall';
    }

    $existing_page = get_page_by_path($page_slug, OBJECT, 'page');
    if ($existing_page && !empty($existing_page->ID)) {
        update_option('cpc_activity_plus_global_wall_page', (int)$existing_page->ID);
        return (int)$existing_page->ID;
    }

    $page_id = wp_insert_post(array(
        'post_type' => 'page',
        'post_title' => cpc_activity_plus_global_wall_title(),
        'post_name' => $page_slug,
        'post_content' => '[cpc-activity-wall]',
        'post_status' => 'publish',
    ));

    if (!is_wp_error($page_id) && $page_id) {
        update_option('cpc_activity_plus_global_wall_page', (int)$page_id);
        return (int)$page_id;
    }

    return 0;
}

function cpc_activity_plus_is_public_group($group_id) {
    $group_id = (int)$group_id;
    if ($group_id <= 0) {
        return false;
    }

    $group_type = get_post_meta($group_id, 'cpc_group_type', true);
    if (!$group_type) {
        $group_type = 'public';
    }

    return $group_type === 'public';
}

function cpc_activity_plus_is_manual_profile_wall_post($activity_id) {
    $activity = get_post((int)$activity_id);
    if (!$activity || $activity->post_type !== 'cpc_activity') {
        return false;
    }

    $activity_type = (string)get_post_meta($activity->ID, 'cpc_activity_type', true);
    $target_type = (string)get_post_meta($activity->ID, 'cpc_target_type', true);
    if ($activity_type !== '' || $target_type !== '') {
        return false;
    }

    $target_id = get_post_meta($activity->ID, 'cpc_target', true);
    return (string)$target_id !== '' && (int)$target_id === (int)$activity->post_author;
}

function cpc_activity_plus_is_manual_group_wall_post($activity_id) {
    $activity = get_post((int)$activity_id);
    if (!$activity || $activity->post_type !== 'cpc_activity') {
        return false;
    }

    $activity_type = (string)get_post_meta($activity->ID, 'cpc_activity_type', true);
    $target_type = (string)get_post_meta($activity->ID, 'cpc_target_type', true);
    $group_id = (int)get_post_meta($activity->ID, 'cpc_activity_group_id', true);

    if ($activity_type !== 'group_activity' || $target_type !== '' || $group_id <= 0) {
        return false;
    }

    return cpc_activity_plus_is_public_group($group_id);
}

function cpc_activity_plus_mark_members_post_for_wall($activity_id, $target_id) {
    $activity = get_post((int)$activity_id);
    if (!$activity || $activity->post_type !== 'cpc_activity') {
        return;
    }

    $target_id = (int)$target_id;
    $is_public_wall_post = $target_id > 0 && $target_id === (int)$activity->post_author;

    update_post_meta($activity->ID, 'cpc_activity_wall_origin', 'user');
    update_post_meta($activity->ID, 'cpc_activity_wall_context', 'members');
    update_post_meta($activity->ID, 'cpc_activity_wall_visibility', $is_public_wall_post ? 'public' : 'private');
    update_post_meta($activity->ID, 'cpc_activity_wall_include', $is_public_wall_post ? 1 : 0);
}

function cpc_activity_plus_mark_group_post_for_wall($activity_id, $group_id) {
    $activity = get_post((int)$activity_id);
    if (!$activity || $activity->post_type !== 'cpc_activity') {
        return;
    }

    $group_id = (int)$group_id;
    $is_public_group = cpc_activity_plus_is_public_group($group_id);

    update_post_meta($activity->ID, 'cpc_activity_wall_origin', 'user');
    update_post_meta($activity->ID, 'cpc_activity_wall_context', 'groups');
    update_post_meta($activity->ID, 'cpc_activity_wall_visibility', $is_public_group ? 'public' : 'private');
    update_post_meta($activity->ID, 'cpc_activity_wall_include', $is_public_group ? 1 : 0);
}

function cpc_activity_plus_is_wall_eligible_activity($activity_id, $settings = null) {
    $activity = get_post((int)$activity_id);
    if (!$activity || $activity->post_type !== 'cpc_activity' || $activity->post_status !== 'publish') {
        return false;
    }

    if (!is_array($settings)) {
        $settings = cpc_activity_plus_get_global_wall_settings();
    }

    if (cpc_activity_plus_is_manual_profile_wall_post($activity->ID)) {
        return true;
    }

    if (!empty($settings['include_group_posts']) && cpc_activity_plus_is_manual_group_wall_post($activity->ID)) {
        return true;
    }

    if (!empty($settings['exclude_system_posts'])) {
        return false;
    }

    $group_id = (int)get_post_meta($activity->ID, 'cpc_activity_group_id', true);
    if ($group_id > 0 && cpc_activity_plus_is_public_group($group_id)) {
        return !empty($settings['include_group_posts']);
    }

    return false;
}

function cpc_activity_plus_get_wall_activity_url($activity_id) {
    $activity_id = (int)$activity_id;
    $page_id = cpc_activity_plus_global_wall_page_id();
    if ($page_id > 0) {
        return add_query_arg('view', $activity_id, get_permalink($page_id));
    }
    return get_permalink($activity_id);
}

function cpc_activity_plus_dispatch_wall_push($activity_id) {
    if (!cpc_activity_plus_global_wall_push_enabled()) {
        return;
    }

    $activity = get_post((int)$activity_id);
    if (!$activity || !cpc_activity_plus_is_wall_eligible_activity($activity->ID)) {
        return;
    }

    $group_id = (int)get_post_meta($activity->ID, 'cpc_activity_group_id', true);
    $payload = array(
        'activity_id' => (int)$activity->ID,
        'author_id' => (int)$activity->post_author,
        'group_id' => $group_id,
        'url' => cpc_activity_plus_get_wall_activity_url($activity->ID),
        'message' => wp_trim_words(wp_strip_all_tags((string)$activity->post_title), 18),
        'context' => (string)get_post_meta($activity->ID, 'cpc_activity_wall_context', true),
    );

    do_action('cpc_activity_wall_post_published', $activity->ID, $payload, $activity);
    do_action('cpc_activity_wall_push_hook', $activity->ID, $payload, $activity);
}

function cpc_activity_plus_user_activity_dir($user_id) {
    $folder = cpc_activity_plus_get_user_cloud_folder_name($user_id);
    return WP_CONTENT_DIR.'/cpc-pro-content/members/'.$folder.'/activity/';
}

function cpc_activity_plus_user_activity_url($user_id) {
    $folder = cpc_activity_plus_get_user_cloud_folder_name($user_id);
    return content_url('/cpc-pro-content/members/'.$folder.'/activity/');
}

function cpc_activity_plus_user_activity_legacy_dir($user_id) {
    return WP_CONTENT_DIR.'/cpc-pro-content/members/'.(int)$user_id.'/activity/';
}

function cpc_activity_plus_get_user_cloud_dirs($user_id) {
    $dirs = array(
        cpc_activity_plus_user_activity_dir($user_id),
    );

    $legacy = cpc_activity_plus_user_activity_legacy_dir($user_id);
    if ($legacy !== $dirs[0] && file_exists($legacy) && is_dir($legacy)) {
        $dirs[] = $legacy;
    }

    return apply_filters('cpc_activity_plus_user_cloud_dirs', array_values(array_unique($dirs)), (int)$user_id);
}

function cpc_activity_plus_get_directory_size_bytes($dir) {
    if (!file_exists($dir) || !is_dir($dir)) {
        return 0;
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

function cpc_activity_plus_get_user_cloud_limit_bytes($user_id = 0) {
    $settings = cpc_activity_plus_get_settings();
    $limit_mb = isset($settings['user_cloud_limit_mb']) ? (int)$settings['user_cloud_limit_mb'] : 50;
    $limit_mb = (int)apply_filters('cpc_activity_plus_user_cloud_limit_mb', $limit_mb, (int)$user_id, $settings);

    if ($limit_mb <= 0) {
        return 0;
    }

    return $limit_mb * 1024 * 1024;
}

function cpc_activity_plus_get_user_cloud_usage_bytes($user_id) {
    $user_id = (int)$user_id;
    if ($user_id <= 0) {
        return 0;
    }

    $dirs = cpc_activity_plus_get_user_cloud_dirs($user_id);
    $usage = 0;
    foreach ($dirs as $dir) {
        $usage += cpc_activity_plus_get_directory_size_bytes($dir);
    }

    return (int)apply_filters('cpc_activity_plus_user_cloud_usage_bytes', $usage, $user_id, $dirs);
}

function cpc_activity_plus_get_user_cloud_summary($user_id) {
    $user_id = (int)$user_id;
    $used = cpc_activity_plus_get_user_cloud_usage_bytes($user_id);
    $limit = cpc_activity_plus_get_user_cloud_limit_bytes($user_id);
    $remaining = $limit > 0 ? max(0, $limit - $used) : 0;
    $percent = ($limit > 0) ? min(100, round(($used / $limit) * 100, 1)) : 0;

    $summary = array(
        'user_id' => $user_id,
        'used_bytes' => $used,
        'used_human' => size_format($used),
        'limit_bytes' => $limit,
        'limit_human' => $limit > 0 ? size_format($limit) : __('Unbegrenzt', CPC2_TEXT_DOMAIN),
        'remaining_bytes' => $remaining,
        'remaining_human' => $limit > 0 ? size_format($remaining) : __('Unbegrenzt', CPC2_TEXT_DOMAIN),
        'percent' => $percent,
    );

    return apply_filters('cpc_activity_plus_user_cloud_summary', $summary, $user_id);
}

function cpc_activity_plus_mkdir($dir) {
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
    }
    return file_exists($dir) && is_dir($dir);
}

function cpc_activity_plus_safe_ext($filename) {
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    $allowed = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    return in_array($ext, $allowed, true) ? $ext : false;
}

function cpc_activity_plus_process_uploaded_images($user_id, $files) {

    $urls = array();
    $settings = cpc_activity_plus_get_settings();

    if (empty($files['cpc_activity_plus_images']) || empty($files['cpc_activity_plus_images']['name'])) {
        return $urls;
    }

    $images = $files['cpc_activity_plus_images'];
    $count = is_array($images['name']) ? count($images['name']) : 0;
    if (!$count) {
        return $urls;
    }

    $base_dir = cpc_activity_plus_user_activity_dir($user_id);
    if (!cpc_activity_plus_mkdir($base_dir)) {
        return $urls;
    }

    $summary = cpc_activity_plus_get_user_cloud_summary($user_id);
    $limit_bytes = isset($summary['limit_bytes']) ? (int)$summary['limit_bytes'] : 0;
    $used_bytes = isset($summary['used_bytes']) ? (int)$summary['used_bytes'] : 0;
    $batch_bytes = 0;
    $quota_exceeded = false;

    $processed = 0;
    for ($index = 0; $index < $count; $index++) {

        if ($settings['max_images'] && $processed >= $settings['max_images']) {
            break;
        }

        if (empty($images['tmp_name'][$index]) || (int)$images['error'][$index] !== 0) {
            continue;
        }

        $tmp_name = $images['tmp_name'][$index];
        $tmp_size = @filesize($tmp_name);
        if ($tmp_size === false) {
            $tmp_size = 0;
        }

        if ($limit_bytes > 0 && ($used_bytes + $batch_bytes + $tmp_size) > $limit_bytes) {
            $quota_exceeded = true;
            do_action('cpc_activity_plus_user_cloud_quota_exceeded', $user_id, $images['name'][$index], $tmp_size, $summary);
            continue;
        }

        $original_name = sanitize_file_name($images['name'][$index]);
        $ext = cpc_activity_plus_safe_ext($original_name);
        if (!$ext) {
            continue;
        }

        $filename = wp_unique_filename($base_dir, time().'-'.$original_name);
        $target = trailingslashit($base_dir).$filename;

        if (!@move_uploaded_file($tmp_name, $target)) {
            continue;
        }

        $urls[] = trailingslashit(cpc_activity_plus_user_activity_url($user_id)).$filename;
        $batch_bytes += $tmp_size;
        $processed++;
    }

    if ($quota_exceeded) {
        set_transient('cpc_activity_plus_cloud_notice_'.$user_id, __('Ein oder mehrere Bilder wurden nicht hochgeladen, weil dein Cloud-Speicherlimit erreicht ist.', CPC2_TEXT_DOMAIN), 120);
    }

    return $urls;
}

function cpc_activity_plus_extract_remote_images($raw_value) {
    $urls = array();
    $lines = preg_split('/\r\n|\r|\n/', (string)$raw_value);
    if (!$lines || !is_array($lines)) {
        return $urls;
    }
    foreach ($lines as $line) {
        $line = trim($line);
        if (!$line) {
            continue;
        }
        $url = esc_url_raw($line);
        if ($url) {
            $urls[] = $url;
        }
    }
    return $urls;
}

function cpc_activity_plus_add_form_fields($form_html, $atts, $user_id, $this_user) {

    if (!cpc_activity_plus_is_enabled()) {
        return $form_html;
    }

    $settings = cpc_activity_plus_get_settings();
    $cloud_user_id = (int)$this_user;
    if (!$cloud_user_id) {
        $cloud_user_id = (int)get_current_user_id();
    }
    if (!$cloud_user_id) {
        $cloud_user_id = (int)$user_id;
    }

    $cloud_summary = cpc_activity_plus_get_user_cloud_summary($cloud_user_id);
    $cloud_notice = get_transient('cpc_activity_plus_cloud_notice_'.$cloud_user_id);
    if ($cloud_notice) {
        delete_transient('cpc_activity_plus_cloud_notice_'.$cloud_user_id);
    }
    $theme_class = 'cpcap-theme-'.$settings['theme'];
    $alignment_class = 'cpcap-alignment-'.$settings['alignment'];

    $form_html .= '<input type="hidden" name="cpc_activity_plus_nonce" value="'.wp_create_nonce('cpc_activity_plus_nonce').'" />';
    $form_html .= '<div id="cpc_activity_plus" class="cpc_activity_plus '.$theme_class.' '.$alignment_class.'">';
        $form_html .= '<div class="cpc_activity_plus_cloud_info">';
            $form_html .= '<strong>'.__('Cloud-Speicher', CPC2_TEXT_DOMAIN).':</strong> ';
            $form_html .= esc_html($cloud_summary['used_human']).' '.__('genutzt von', CPC2_TEXT_DOMAIN).' '.esc_html($cloud_summary['limit_human']);
            if ($cloud_summary['limit_bytes'] > 0) {
                $form_html .= ' ('.esc_html($cloud_summary['remaining_human']).' '.__('frei', CPC2_TEXT_DOMAIN).')';
            }
        $form_html .= '</div>';
        if ($cloud_notice) {
            $form_html .= '<div class="cpc_activity_plus_cloud_notice">'.esc_html($cloud_notice).'</div>';
        }
        $form_html .= '<div class="cpc_activity_plus_toolbar">';
            if ($settings['allow_images']) {
                $form_html .= '<button type="button" class="cpc_button cpc_activity_plus_toggle cpc_activity_plus_toggle_images" data-target="cpc_activity_plus_images_wrap"><span>'.__('Bild', CPC2_TEXT_DOMAIN).'</span></button>';
            }
            if ($settings['allow_links']) {
                $form_html .= '<button type="button" class="cpc_button cpc_activity_plus_toggle cpc_activity_plus_toggle_links" data-target="cpc_activity_plus_link_wrap"><span>'.__('Link', CPC2_TEXT_DOMAIN).'</span></button>';
            }
            if ($settings['allow_video']) {
                $form_html .= '<button type="button" class="cpc_button cpc_activity_plus_toggle cpc_activity_plus_toggle_videos" data-target="cpc_activity_plus_video_wrap"><span>'.__('Video', CPC2_TEXT_DOMAIN).'</span></button>';
            }
        $form_html .= '</div>';

        if ($settings['allow_images']) {
            $form_html .= '<div id="cpc_activity_plus_images_wrap" class="cpc_activity_plus_wrap" style="display:none">';
                $form_html .= '<div style="margin-bottom:8px">';
                    $form_html .= '<input type="file" id="cpc_activity_plus_images" name="cpc_activity_plus_images[]" accept="image/*" multiple />';
                $form_html .= '</div>';
                $form_html .= '<textarea name="cpc_activity_plus_remote_images" id="cpc_activity_plus_remote_images" placeholder="'.esc_attr__('Bild-URLs (eine pro Zeile)', CPC2_TEXT_DOMAIN).'" rows="3"></textarea>';
            $form_html .= '</div>';
        }

        if ($settings['allow_links']) {
            $form_html .= '<div id="cpc_activity_plus_link_wrap" class="cpc_activity_plus_wrap" style="display:none">';
                $form_html .= '<input type="url" name="cpc_activity_plus_link_url" id="cpc_activity_plus_link_url" placeholder="'.esc_attr__('Link-URL', CPC2_TEXT_DOMAIN).'" />';
                $form_html .= '<div id="cpc_activity_plus_link_preview"></div>';
            $form_html .= '</div>';
        }

        if ($settings['allow_video']) {
            $form_html .= '<div id="cpc_activity_plus_video_wrap" class="cpc_activity_plus_wrap" style="display:none">';
                $form_html .= '<input type="url" name="cpc_activity_plus_video_url" id="cpc_activity_plus_video_url" placeholder="'.esc_attr__('Video-URL (YouTube, Vimeo, ...)', CPC2_TEXT_DOMAIN).'" />';
            $form_html .= '</div>';
        }
    $form_html .= '</div>';

    return $form_html;
}
add_filter('cpc_activity_post_post_form_filter', 'cpc_activity_plus_add_form_fields', 20, 4);

function cpc_activity_plus_on_post_add($the_post, $the_files, $new_id) {

    if (!cpc_activity_plus_is_enabled()) {
        return;
    }

    if (empty($the_post['cpc_activity_plus_nonce']) || !wp_verify_nonce($the_post['cpc_activity_plus_nonce'], 'cpc_activity_plus_nonce')) {
        return;
    }

    $settings = cpc_activity_plus_get_settings();
    $user_id = isset($the_post['cpc_activity_post_author']) ? (int)$the_post['cpc_activity_post_author'] : 0;
    if (!$user_id) {
        return;
    }

    $parts = array();

    if ($settings['allow_images']) {
        $image_urls = cpc_activity_plus_process_uploaded_images($user_id, $the_files);
        $remote_images = cpc_activity_plus_extract_remote_images(isset($the_post['cpc_activity_plus_remote_images']) ? $the_post['cpc_activity_plus_remote_images'] : '');
        if ($remote_images) {
            $image_urls = array_merge($image_urls, $remote_images);
        }
        $image_urls = array_values(array_unique(array_filter($image_urls)));
        if ($settings['max_images']) {
            $image_urls = array_slice($image_urls, 0, $settings['max_images']);
        }
        if ($image_urls) {
            $parts[] = "[cpcap_images]\n".implode("\n", $image_urls)."\n[/cpcap_images]";
        }
    }

    if ($settings['allow_links'] && !empty($the_post['cpc_activity_plus_link_url'])) {
        $link_url = esc_url_raw($the_post['cpc_activity_plus_link_url']);
        if ($link_url) {
            $link_preview = cpc_activity_plus_fetch_link_preview_data($link_url);
            $link_attrs = 'url="'.esc_attr($link_url).'"';
            if (!empty($link_preview['title'])) {
                $link_attrs .= ' title="'.esc_attr($link_preview['title']).'"';
            }
            if (!empty($link_preview['description'])) {
                $link_attrs .= ' description="'.esc_attr($link_preview['description']).'"';
            }
            if (!empty($link_preview['image'])) {
                $link_attrs .= ' image="'.esc_attr($link_preview['image']).'"';
            }
            $parts[] = '[cpcap_link '.$link_attrs.'][/cpcap_link]';
        }
    }

    if ($settings['allow_video'] && !empty($the_post['cpc_activity_plus_video_url'])) {
        $video_url = esc_url_raw($the_post['cpc_activity_plus_video_url']);
        if ($video_url) {
            $parts[] = '[cpcap_video]'.$video_url.'[/cpcap_video]';
        }
    }

    if (!$parts) {
        return;
    }

    $post = get_post($new_id);
    if (!$post) {
        return;
    }

    $content = rtrim($post->post_title);
    $content .= "\n".implode("\n", $parts);

    wp_update_post(array(
        'ID' => $new_id,
        'post_title' => $content,
    ));
}
add_action('cpc_activity_post_add_hook', 'cpc_activity_plus_on_post_add', 20, 3);

function cpc_activity_plus_on_members_post_add_wall_meta($the_post, $the_files, $new_id) {
    $target_id = isset($the_post['cpc_activity_post_target']) ? (int)$the_post['cpc_activity_post_target'] : 0;
    cpc_activity_plus_mark_members_post_for_wall($new_id, $target_id);
    cpc_activity_plus_dispatch_wall_push($new_id);
}
add_action('cpc_activity_post_add_hook', 'cpc_activity_plus_on_members_post_add_wall_meta', 30, 3);

function cpc_activity_plus_on_group_post_add_wall_meta($the_post, $the_files, $new_id, $group_id) {
    cpc_activity_plus_mark_group_post_for_wall($new_id, $group_id);
    cpc_activity_plus_dispatch_wall_push($new_id);
}
add_action('cpc_group_activity_post_add_hook', 'cpc_activity_plus_on_group_post_add_wall_meta', 20, 4);

function cpc_activity_plus_render_tags($item_html, $atts, $item_id, $post_title, $user_id, $this_user, $shown_count) {

    if (!cpc_activity_plus_is_enabled()) {
        return $item_html;
    }

    $settings = cpc_activity_plus_get_settings();

    $plus_html = '';

    $images = array();
    if (preg_match('/\[cpcap_images\](.*?)\[\/cpcap_images\]/s', $post_title, $matches)) {
        $images = array_filter(array_map('trim', explode("\n", trim($matches[1]))));
    }

    $link_url = '';
    $link_title = '';
    $link_description = '';
    $link_image = '';
    if (preg_match('/\[cpcap_link([^\]]*)\]\[\/cpcap_link\]/', $post_title, $matches)) {
        $atts = shortcode_parse_atts(trim($matches[1]));
        if (is_array($atts)) {
            $link_url = !empty($atts['url']) ? esc_url($atts['url']) : '';
            $link_title = !empty($atts['title']) ? sanitize_text_field($atts['title']) : '';
            $link_description = !empty($atts['description']) ? sanitize_text_field($atts['description']) : '';
            $link_image = !empty($atts['image']) ? esc_url($atts['image']) : '';
        }
    }

    $video_url = '';
    if (preg_match('/\[cpcap_video\](.*?)\[\/cpcap_video\]/s', $post_title, $matches)) {
        $video_url = esc_url(trim($matches[1]));
    }

    $item_html = preg_replace('/\[cpcap_images\].*?\[\/cpcap_images\]/s', '', $item_html);
    $item_html = preg_replace('/\[cpcap_link[^\]]*\]\[\/cpcap_link\]/s', '', $item_html);
    $item_html = preg_replace('/\[cpcap_video\].*?\[\/cpcap_video\]/s', '', $item_html);

    $caption_source = preg_replace('/\[cpcap_images\].*?\[\/cpcap_images\]/s', '', $post_title);
    $caption_source = preg_replace('/\[cpcap_link[^\]]*\]\[\/cpcap_link\]/s', '', $caption_source);
    $caption_source = preg_replace('/\[cpcap_video\].*?\[\/cpcap_video\]/s', '', $caption_source);
    $caption_text = trim(wp_strip_all_tags($caption_source));

    if ($images) {
        $plus_html .= '<div class="cpc_activity_plus_images">';
        $image_max_width_value = isset($settings['media_max_width_value']) ? (int)$settings['media_max_width_value'] : 100;
        $image_max_width_unit = isset($settings['media_max_width_unit']) ? cpc_activity_plus_normalize_media_max_width_unit($settings['media_max_width_unit']) : '%';
        foreach ($images as $image_url) {
            $url = esc_url($image_url);
            if (!$url) {
                continue;
            }
            $caption_attr = $caption_text !== '' ? ' data-caption="'.esc_attr($caption_text).'"' : '';
            $plus_html .= '<a class="cpc_activity_plus_image" href="'.$url.'" target="_blank" rel="noopener noreferrer" data-lightbox-group="activity-'.(int)$item_id.'"'.$caption_attr.'>';
            $plus_html .= '<img src="'.$url.'" alt="" loading="lazy" style="max-width:'.$image_max_width_value.$image_max_width_unit.';" />';
            $plus_html .= '</a>';
        }
        $plus_html .= '<div style="clear:both"></div></div>';
    }

    if ($link_url) {
        $display_title = $link_title !== '' ? $link_title : $link_url;
        $plus_html .= '<div class="cpc_activity_plus_link cpc_activity_plus_link_preview_card">';
        $plus_html .= '<a class="cpc_activity_plus_link_preview_anchor" href="'.$link_url.'" target="_blank" rel="noopener noreferrer">';
        if ($link_image) {
            $plus_html .= '<div class="cpc_activity_plus_link_preview_image_wrap">';
            $plus_html .= '<img src="'.$link_image.'" alt="" class="cpc_activity_plus_link_preview_image" loading="lazy" />';
            $plus_html .= '</div>';
        }
        $plus_html .= '<div class="cpc_activity_plus_link_preview_content">';
        $plus_html .= '<div class="cpc_activity_plus_link_preview_title">'.esc_html($display_title).'</div>';
        if ($link_description !== '') {
            $plus_html .= '<div class="cpc_activity_plus_link_preview_desc">'.esc_html($link_description).'</div>';
        }
        $plus_html .= '<div class="cpc_activity_plus_link_preview_url">'.esc_html($link_url).'</div>';
        $plus_html .= '</div>';
        $plus_html .= '</a>';
        $plus_html .= '</div>';
    }

    if ($video_url) {
        $embed = wp_oembed_get($video_url);
        if ($embed) {
            $plus_html .= '<div class="cpc_activity_plus_video">'.$embed.'</div>';
        } else {
            $plus_html .= '<div class="cpc_activity_plus_video"><a href="'.$video_url.'" target="_blank" rel="noopener noreferrer">'.$video_url.'</a></div>';
        }
    }

    if ($plus_html) {
        $item_html .= $plus_html;
    }

    return $item_html;
}
add_filter('cpc_activity_item_filter', 'cpc_activity_plus_render_tags', 20, 7);

function cpc_activity_plus_extract_thumbnail($body, $base_url) {
    $images = array();

    // Try og:image first (highest priority)
    if (preg_match('/<meta[^>]+property=["\']og:image["\'][^>]+content=["\'](.*?)["\']/is', $body, $matches)) {
        $img_url = trim($matches[1]);
        if ($img_url) {
            $img_url = cpc_activity_plus_resolve_url($img_url, $base_url);
            $images['og:image'] = $img_url;
        }
    }

    // Try twitter:image
    if (preg_match('/<meta[^>]+name=["\']twitter:image["\'][^>]+content=["\'](.*?)["\']/is', $body, $matches)) {
        $img_url = trim($matches[1]);
        if ($img_url && !isset($images['twitter:image'])) {
            $img_url = cpc_activity_plus_resolve_url($img_url, $base_url);
            $images['twitter:image'] = $img_url;
        }
    }

    // Try first image in body as fallback
    if (preg_match('/<img[^>]+src=["\'](.*?)["\'].*?>/is', $body, $matches)) {
        $img_url = trim($matches[1]);
        if ($img_url && !isset($images['body'])) {
            $img_url = cpc_activity_plus_resolve_url($img_url, $base_url);
            $images['body'] = $img_url;
        }
    }

    return array_filter($images);
}

function cpc_activity_plus_fetch_link_preview_data($url) {

    $url = esc_url_raw($url);
    if (!$url) {
        return array();
    }

    $response = wp_remote_get($url, array('timeout' => 8, 'redirection' => 3));
    if (is_wp_error($response)) {
        return array();
    }

    $body = wp_remote_retrieve_body($response);
    if (!$body) {
        return array();
    }

    $title = $url;
    if (preg_match('/<title>(.*?)<\/title>/is', $body, $matches)) {
        $title = wp_strip_all_tags($matches[1]);
    }

    $description = '';
    if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/is', $body, $matches)) {
        $description = wp_strip_all_tags($matches[1]);
    }

    $images = cpc_activity_plus_extract_thumbnail($body, $url);
    $primary_image = reset($images);

    return array(
        'url' => esc_url($url),
        'title' => sanitize_text_field($title),
        'description' => sanitize_text_field($description),
        'image' => $primary_image ? esc_url($primary_image) : '',
        'images' => array_map('esc_url', $images),
    );
}

function cpc_activity_plus_resolve_url($relative_url, $base_url) {
    if (filter_var($relative_url, FILTER_VALIDATE_URL)) {
        return $relative_url;
    }

    $base_parts = parse_url($base_url);
    $base_scheme = isset($base_parts['scheme']) ? $base_parts['scheme'].'://' : 'http://';
    $base_host = isset($base_parts['host']) ? $base_parts['host'] : '';
    $base_path = isset($base_parts['path']) ? dirname($base_parts['path']) : '/';

    if (strpos($relative_url, '//') === 0) {
        return $base_scheme.$relative_url;
    }

    if (strpos($relative_url, '/') === 0) {
        return $base_scheme.$base_host.$relative_url;
    }

    return $base_scheme.$base_host.trailingslashit($base_path).$relative_url;
}

function cpc_activity_plus_ajax_preview_link() {

    if (!cpc_activity_plus_is_enabled() || !current_user_can('read')) {
        wp_send_json_error();
    }

    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    if (!$url) {
        wp_send_json_error();
    }

    $preview = cpc_activity_plus_fetch_link_preview_data($url);
    if (empty($preview)) {
        wp_send_json_error();
    }

    wp_send_json_success(array(
        'title' => $preview['title'],
        'description' => $preview['description'],
        'url' => $preview['url'],
        'image' => !empty($preview['image']) ? $preview['image'] : '',
        'images' => !empty($preview['images']) ? $preview['images'] : array(),
    ));
}
add_action('wp_ajax_cpc_activity_plus_preview_link', 'cpc_activity_plus_ajax_preview_link');

function cpc_activity_plus_ajax_preview_video() {

    if (!cpc_activity_plus_is_enabled() || !current_user_can('read')) {
        wp_send_json_error();
    }

    $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
    if (!$url) {
        wp_send_json_error();
    }

    $oembed = wp_oembed_get($url, array('width' => 300));
    if (!$oembed) {
        wp_send_json_error(array('message' => 'Video platform not supported'));
    }

    preg_match('/<iframe[^>]+src=["\']([^"\']+)["\'][^>]*><\/iframe>/i', $oembed, $iframe_matches);
    $thumb_url = '';

    // Extract thumbnail from common video platforms
    if (preg_match('/youtu(?:\.be|be\.com).*[?&]v=([^&]+)/i', $url, $matches)) {
        $video_id = $matches[1];
        $thumb_url = 'https://img.youtube.com/vi/'.$video_id.'/0.jpg';
    } elseif (preg_match('/vimeo\.com\/(\d+)/i', $url, $matches)) {
        // Vimeo would need API call, skip for now
    }

    wp_send_json_success(array(
        'embed' => $oembed,
        'url' => esc_url($url),
        'thumbnail' => $thumb_url ? esc_url($thumb_url) : '',
    ));
}
add_action('wp_ajax_cpc_activity_plus_preview_video', 'cpc_activity_plus_ajax_preview_video');

function cpc_activity_plus_cleanup_on_delete($post_id) {

    if (!cpc_activity_plus_is_enabled() || !get_option('cpc_activity_plus_cleanup_on_delete')) {
        return;
    }

    if (get_post_type($post_id) !== 'cpc_activity') {
        return;
    }

    $post = get_post($post_id);
    if (!$post) {
        return;
    }

    if (!preg_match('/\[cpcap_images\](.*?)\[\/cpcap_images\]/s', $post->post_title, $matches)) {
        return;
    }

    $images = array_filter(array_map('trim', explode("\n", trim($matches[1]))));
    if (!$images) {
        return;
    }

    $content_base = content_url('/cpc-pro-content/');
    foreach ($images as $image_url) {
        $url = esc_url_raw($image_url);
        if (!$url || strpos($url, $content_base) !== 0) {
            continue;
        }

        $relative = ltrim(str_replace($content_base, '', $url), '/');
        $full_path = WP_CONTENT_DIR.'/cpc-pro-content/'.$relative;
        if (file_exists($full_path) && is_writable($full_path)) {
            @unlink($full_path);
        }
    }
}
add_action('before_delete_post', 'cpc_activity_plus_cleanup_on_delete');

/**
 * Group-Cloud Functions 
 * Manages media storage for group activities
 */

function cpc_activity_plus_get_group_cloud_folder_name($group_id) {
    $group_id = (int)$group_id;
    if ($group_id <= 0) {
        return 'group-0';
    }

    $stored = get_post_meta($group_id, 'cpc_activity_plus_cloud_folder', true);
    if (!empty($stored) && preg_match('/^[a-z0-9\-]+$/', $stored)) {
        return $stored;
    }

    $group = get_post($group_id);
    $slug = $group ? sanitize_title($group->post_name) : '';
    if ($slug === '') {
        $slug = 'group';
    }

    $folder = $slug.'-'.$group_id;
    $folder = apply_filters('cpc_activity_plus_group_cloud_folder_name', $folder, $group_id, $group);
    $folder = sanitize_title($folder);
    if ($folder === '') {
        $folder = 'group-'.$group_id;
    }

    update_post_meta($group_id, 'cpc_activity_plus_cloud_folder', $folder);

    return $folder;
}

function cpc_activity_plus_group_activity_dir($group_id) {
    $folder = cpc_activity_plus_get_group_cloud_folder_name($group_id);
    return WP_CONTENT_DIR.'/cpc-pro-content/groups/'.$folder.'/activity/';
}

function cpc_activity_plus_group_activity_url($group_id) {
    $folder = cpc_activity_plus_get_group_cloud_folder_name($group_id);
    return content_url('/cpc-pro-content/groups/'.$folder.'/activity/');
}

function cpc_activity_plus_get_group_cloud_dirs($group_id) {
    $dirs = array(
        cpc_activity_plus_group_activity_dir($group_id),
    );

    return apply_filters('cpc_activity_plus_group_cloud_dirs', array_values(array_unique($dirs)), (int)$group_id);
}

function cpc_activity_plus_get_group_cloud_limit_bytes($group_id = 0) {
    $limit_mb = (int)get_option('cpc_groups_cloud_limit_mb', 100);
    $limit_mb = (int)apply_filters('cpc_activity_plus_group_cloud_limit_mb', $limit_mb, (int)$group_id);

    if ($limit_mb <= 0) {
        return 0;
    }

    return $limit_mb * 1024 * 1024;
}

function cpc_activity_plus_get_group_cloud_usage_bytes($group_id) {
    $group_id = (int)$group_id;
    if ($group_id <= 0) {
        return 0;
    }

    $dirs = cpc_activity_plus_get_group_cloud_dirs($group_id);
    $usage = 0;
    foreach ($dirs as $dir) {
        $usage += cpc_activity_plus_get_directory_size_bytes($dir);
    }

    return (int)apply_filters('cpc_activity_plus_group_cloud_usage_bytes', $usage, $group_id, $dirs);
}

function cpc_activity_plus_get_group_cloud_summary($group_id) {
    $group_id = (int)$group_id;
    $used = cpc_activity_plus_get_group_cloud_usage_bytes($group_id);
    $limit = cpc_activity_plus_get_group_cloud_limit_bytes($group_id);
    $remaining = $limit > 0 ? max(0, $limit - $used) : 0;
    $percent = ($limit > 0) ? min(100, round(($used / $limit) * 100, 1)) : 0;

    $summary = array(
        'group_id' => $group_id,
        'used_bytes' => $used,
        'used_human' => size_format($used),
        'limit_bytes' => $limit,
        'limit_human' => $limit > 0 ? size_format($limit) : __('Unbegrenzt', CPC2_TEXT_DOMAIN),
        'remaining_bytes' => $remaining,
        'remaining_human' => $limit > 0 ? size_format($remaining) : __('Unbegrenzt', CPC2_TEXT_DOMAIN),
        'percent' => $percent,
    );

    return apply_filters('cpc_activity_plus_group_cloud_summary', $summary, $group_id);
}

/**
 * Process Activity Plus uploads for group activities
 * Similar to cpc_activity_plus_on_post_add but adapted for groups
 */
function cpc_activity_plus_process_group_uploads($the_post, $the_files, $activity_id, $group_id) {
    
    if (!cpc_activity_plus_is_enabled()) {
        return;
    }

    if (empty($the_post['cpc_activity_plus_nonce']) || !wp_verify_nonce($the_post['cpc_activity_plus_nonce'], 'cpc_activity_plus_nonce')) {
        return;
    }

    $settings = cpc_activity_plus_get_settings();
    
    $parts = array();

    // Process uploaded images
    if ($settings['allow_images']) {
        $image_urls = cpc_activity_plus_process_uploaded_group_images($group_id, $the_files);
        $remote_images = cpc_activity_plus_extract_remote_images(isset($the_post['cpc_activity_plus_remote_images']) ? $the_post['cpc_activity_plus_remote_images'] : '');
        if ($remote_images) {
            $image_urls = array_merge($image_urls, $remote_images);
        }
        $image_urls = array_values(array_unique(array_filter($image_urls)));
        if ($settings['max_images']) {
            $image_urls = array_slice($image_urls, 0, $settings['max_images']);
        }
        if ($image_urls) {
            $parts[] = "[cpcap_images]\n".implode("\n", $image_urls)."\n[/cpcap_images]";
        }
    }

    // Process link
    if ($settings['allow_links'] && !empty($the_post['cpc_activity_plus_link_url'])) {
        $link_url = esc_url_raw($the_post['cpc_activity_plus_link_url']);
        if ($link_url) {
            $link_preview = cpc_activity_plus_fetch_link_preview_data($link_url);
            $link_attrs = 'url="'.esc_attr($link_url).'"';
            if (!empty($link_preview['title'])) {
                $link_attrs .= ' title="'.esc_attr($link_preview['title']).'"';
            }
            if (!empty($link_preview['description'])) {
                $link_attrs .= ' description="'.esc_attr($link_preview['description']).'"';
            }
            if (!empty($link_preview['image'])) {
                $link_attrs .= ' image="'.esc_attr($link_preview['image']).'"';
            }
            $parts[] = '[cpcap_link '.$link_attrs.'][/cpcap_link]';
        }
    }

    // Process video
    if ($settings['allow_video'] && !empty($the_post['cpc_activity_plus_video_url'])) {
        $video_url = esc_url_raw($the_post['cpc_activity_plus_video_url']);
        if ($video_url) {
            $parts[] = '[cpcap_video]'.$video_url.'[/cpcap_video]';
        }
    }

    if (!$parts) {
        return;
    }

    // Update activity post with media content
    $post = get_post($activity_id);
    if (!$post) {
        return;
    }

    $content = rtrim($post->post_title);
    $content .= "\n".implode("\n", $parts);

    wp_update_post(array(
        'ID' => $activity_id,
        'post_title' => $content,
    ));
}

/**
 * Process uploaded images for group activities
 * Adapted from cpc_activity_plus_process_uploaded_images
 */
function cpc_activity_plus_process_uploaded_group_images($group_id, $files) {

    $urls = array();
    $settings = cpc_activity_plus_get_settings();

    if (empty($files['cpc_activity_plus_images']) || empty($files['cpc_activity_plus_images']['name'])) {
        return $urls;
    }

    $images = $files['cpc_activity_plus_images'];
    $count = is_array($images['name']) ? count($images['name']) : 0;
    if (!$count) {
        return $urls;
    }

    $base_dir = cpc_activity_plus_group_activity_dir($group_id);
    if (!cpc_activity_plus_mkdir($base_dir)) {
        return $urls;
    }

    $summary = cpc_activity_plus_get_group_cloud_summary($group_id);
    $limit_bytes = isset($summary['limit_bytes']) ? (int)$summary['limit_bytes'] : 0;
    $used_bytes = isset($summary['used_bytes']) ? (int)$summary['used_bytes'] : 0;
    $batch_bytes = 0;
    $quota_exceeded = false;

    $processed = 0;
    for ($index = 0; $index < $count; $index++) {

        if ($settings['max_images'] && $processed >= $settings['max_images']) {
            break;
        }

        if (empty($images['tmp_name'][$index]) || (int)$images['error'][$index] !== 0) {
            continue;
        }

        $tmp_name = $images['tmp_name'][$index];
        $tmp_size = @filesize($tmp_name);
        if ($tmp_size === false) {
            $tmp_size = 0;
        }

        if ($limit_bytes > 0 && ($used_bytes + $batch_bytes + $tmp_size) > $limit_bytes) {
            $quota_exceeded = true;
            do_action('cpc_activity_plus_group_cloud_quota_exceeded', $group_id, $images['name'][$index], $tmp_size, $summary);
            continue;
        }

        $original_name = sanitize_file_name($images['name'][$index]);
        $ext = cpc_activity_plus_safe_ext($original_name);
        if (!$ext) {
            continue;
        }

        $filename = wp_unique_filename($base_dir, time().'-'.$original_name);
        $target = trailingslashit($base_dir).$filename;

        if (!@move_uploaded_file($tmp_name, $target)) {
            continue;
        }

        $urls[] = trailingslashit(cpc_activity_plus_group_activity_url($group_id)).$filename;
        $batch_bytes += $tmp_size;
        $processed++;
    }

    if ($quota_exceeded) {
        do_action('cpc_activity_plus_group_cloud_quota_soft_exceeded', $group_id, $processed, $summary);
    }

    return $urls;
}

/**
 * Cleanup group media when group is deleted
 */
function cpc_activity_plus_cleanup_group_on_delete($post_id) {
    
    if (!cpc_activity_plus_is_enabled() || !get_option('cpc_activity_plus_cleanup_on_delete')) {
        return;
    }

    if (get_post_type($post_id) !== 'cpc_group') {
        return;
    }

    // Delete entire group activity directory
    $group_dir = cpc_activity_plus_group_activity_dir($post_id);
    if (file_exists($group_dir) && is_dir($group_dir)) {
        cpc_activity_plus_recursive_rmdir($group_dir);
    }
}
add_action('before_delete_post', 'cpc_activity_plus_cleanup_group_on_delete');

/**
 * Recursively delete a directory and its contents
 */
function cpc_activity_plus_recursive_rmdir($dir) {
    if (!file_exists($dir) || !is_dir($dir)) {
        return false;
    }

    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $fileinfo) {
        $action = $fileinfo->isDir() ? 'rmdir' : 'unlink';
        @$action($fileinfo->getRealPath());
    }

    return @rmdir($dir);
}

?>