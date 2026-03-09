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

function cpc_activity_plus_get_settings() {

    $settings = array(
        'enabled' => cpc_activity_plus_is_enabled(),
        'allow_images' => (bool)get_option('cpc_activity_plus_allow_images'),
        'allow_links' => (bool)get_option('cpc_activity_plus_allow_links'),
        'allow_video' => (bool)get_option('cpc_activity_plus_allow_video'),
        'max_images' => (int)get_option('cpc_activity_plus_max_images'),
        'cleanup_on_delete' => (bool)get_option('cpc_activity_plus_cleanup_on_delete'),
        'theme' => cpc_activity_plus_normalize_theme(get_option('cpc_activity_plus_theme', 'default')),
        'alignment' => cpc_activity_plus_normalize_alignment(get_option('cpc_activity_plus_alignment', 'left')),
    );

    if (!$settings['max_images']) {
        $settings['max_images'] = 5;
    }

    return $settings;
}

function cpc_activity_plus_user_activity_dir($user_id) {
    return WP_CONTENT_DIR.'/cpc-pro-content/members/'.(int)$user_id.'/activity/';
}

function cpc_activity_plus_user_activity_url($user_id) {
    return content_url('/cpc-pro-content/members/'.(int)$user_id.'/activity/');
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

    $processed = 0;
    for ($index = 0; $index < $count; $index++) {

        if ($settings['max_images'] && $processed >= $settings['max_images']) {
            break;
        }

        if (empty($images['tmp_name'][$index]) || (int)$images['error'][$index] !== 0) {
            continue;
        }

        $tmp_name = $images['tmp_name'][$index];
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
        $processed++;
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
    $theme_class = 'cpcap-theme-'.$settings['theme'];
    $alignment_class = 'cpcap-alignment-'.$settings['alignment'];

    $form_html .= '<input type="hidden" name="cpc_activity_plus_nonce" value="'.wp_create_nonce('cpc_activity_plus_nonce').'" />';
    $form_html .= '<div id="cpc_activity_plus" class="cpc_activity_plus '.$theme_class.' '.$alignment_class.'">';
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
            $parts[] = '[cpcap_link url="'.esc_attr($link_url).'"][/cpcap_link]';
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

function cpc_activity_plus_render_tags($item_html, $atts, $item_id, $post_title, $user_id, $this_user, $shown_count) {

    if (!cpc_activity_plus_is_enabled()) {
        return $item_html;
    }

    $plus_html = '';

    $images = array();
    if (preg_match('/\[cpcap_images\](.*?)\[\/cpcap_images\]/s', $post_title, $matches)) {
        $images = array_filter(array_map('trim', explode("\n", trim($matches[1]))));
    }

    $link_url = '';
    if (preg_match('/\[cpcap_link\s+url="([^"]+)"\]\[\/cpcap_link\]/', $post_title, $matches)) {
        $link_url = esc_url($matches[1]);
    }

    $video_url = '';
    if (preg_match('/\[cpcap_video\](.*?)\[\/cpcap_video\]/s', $post_title, $matches)) {
        $video_url = esc_url(trim($matches[1]));
    }

    $item_html = preg_replace('/\[cpcap_images\].*?\[\/cpcap_images\]/s', '', $item_html);
    $item_html = preg_replace('/\[cpcap_link\s+url="[^"]+"\]\[\/cpcap_link\]/s', '', $item_html);
    $item_html = preg_replace('/\[cpcap_video\].*?\[\/cpcap_video\]/s', '', $item_html);

    if ($images) {
        $plus_html .= '<div class="cpc_activity_plus_images">';
        foreach ($images as $image_url) {
            $url = esc_url($image_url);
            if (!$url) {
                continue;
            }
            $plus_html .= '<a class="cpc_activity_plus_image" href="'.$url.'" target="_blank" rel="noopener noreferrer">';
            $plus_html .= '<img src="'.$url.'" alt="" loading="lazy" />';
            $plus_html .= '</a>';
        }
        $plus_html .= '<div style="clear:both"></div></div>';
    }

    if ($link_url) {
        $plus_html .= '<div class="cpc_activity_plus_link">';
        $plus_html .= '<a href="'.$link_url.'" target="_blank" rel="noopener noreferrer">'.$link_url.'</a>';
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

    $response = wp_remote_get($url, array('timeout' => 8, 'redirection' => 3));
    if (is_wp_error($response)) {
        wp_send_json_error();
    }

    $body = wp_remote_retrieve_body($response);
    if (!$body) {
        wp_send_json_error();
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

    wp_send_json_success(array(
        'title' => $title,
        'description' => $description,
        'url' => esc_url($url),
        'image' => $primary_image ? esc_url($primary_image) : '',
        'images' => array_map('esc_url', $images),
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

?>