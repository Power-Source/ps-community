<?php

add_action('wp_ajax_cpc_media_ajax_upload', 'cpc_media_ajax_upload');
add_action('wp_ajax_cpc_media_ajax_update_gallery', 'cpc_media_ajax_update_gallery');
add_action('wp_ajax_cpc_media_ajax_delete_gallery', 'cpc_media_ajax_delete_gallery');
add_action('wp_ajax_cpc_media_ajax_update_media', 'cpc_media_ajax_update_media');
add_action('wp_ajax_cpc_media_ajax_delete_media', 'cpc_media_ajax_delete_media');

// Lightbox & Single Media
add_action('wp_ajax_cpc_media_fetch_gallery_media', 'cpc_media_ajax_fetch_gallery_media');
add_action('wp_ajax_nopriv_cpc_media_fetch_gallery_media', 'cpc_media_ajax_fetch_gallery_media');
add_action('wp_ajax_cpc_media_fetch_media', 'cpc_media_ajax_fetch_media');
add_action('wp_ajax_nopriv_cpc_media_fetch_media', 'cpc_media_ajax_fetch_media');

// Cover Management
add_action('wp_ajax_cpc_media_set_gallery_cover', 'cpc_media_ajax_set_gallery_cover');
add_action('wp_ajax_cpc_media_get_cover_selector', 'cpc_media_ajax_get_cover_selector');

// Reorder
add_action('wp_ajax_cpc_media_reorder_items', 'cpc_media_ajax_reorder_items');

function cpc_media_ajax_verify() {
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => __('Nicht angemeldet.', CPC2_TEXT_DOMAIN)), 403);
    }

    $nonce = isset($_POST['nonce']) ? sanitize_text_field(wp_unslash($_POST['nonce'])) : '';
    if (!wp_verify_nonce($nonce, 'cpc_media_ajax_nonce')) {
        wp_send_json_error(array('message' => __('Ungueltige Anfrage.', CPC2_TEXT_DOMAIN)), 403);
    }
}

function cpc_media_ajax_upload() {
    cpc_media_ajax_verify();

    $gallery_id = isset($_POST['gallery_id']) ? (int)$_POST['gallery_id'] : 0;
    $user_id = get_current_user_id();

    if (!$gallery_id || !cpc_media_user_can_upload_to_gallery($gallery_id, $user_id)) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
    }

    if (empty($_FILES['files'])) {
        wp_send_json_error(array('message' => __('Keine Dateien uebergeben.', CPC2_TEXT_DOMAIN)), 400);
    }

    $files = $_FILES['files'];
    $uploaded_items = array();
    $success = 0;
    $failed = 0;

    $names = isset($files['name']) && is_array($files['name']) ? $files['name'] : array();
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

        if (!$media_id) {
            $failed++;
            continue;
        }

        $item = get_post($media_id);
        if ($item && function_exists('cpc_media_render_media_item_html')) {
            $uploaded_items[] = cpc_media_render_media_item_html($item);
        }

        $success++;
    }

    if ($success > 0) {
        $current_count = cpc_media_get_gallery_media_count($gallery_id);
        cpc_media_update_gallery_media_count($gallery_id, $current_count + $success);
    }

    wp_send_json_success(array(
        'message' => $failed > 0 ? __('Upload teilweise abgeschlossen.', CPC2_TEXT_DOMAIN) : __('Upload abgeschlossen.', CPC2_TEXT_DOMAIN),
        'uploaded' => $success,
        'failed' => $failed,
        'items_html' => $uploaded_items,
        'gallery_count' => cpc_media_get_gallery_media_count($gallery_id),
    ));
}

function cpc_media_ajax_update_gallery() {
    cpc_media_ajax_verify();

    $gallery_id = isset($_POST['gallery_id']) ? (int)$_POST['gallery_id'] : 0;
    $user_id = get_current_user_id();

    if (!$gallery_id || !cpc_media_user_can_manage_gallery($gallery_id, $user_id)) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
    }

    $ok = cpc_media_update_gallery($gallery_id, array(
        'title' => isset($_POST['title']) ? wp_unslash($_POST['title']) : null,
        'description' => isset($_POST['description']) ? wp_unslash($_POST['description']) : null,
        'status' => isset($_POST['status']) ? wp_unslash($_POST['status']) : null,
        'type' => isset($_POST['type']) ? wp_unslash($_POST['type']) : null,
    ));

    if (!$ok) {
        wp_send_json_error(array('message' => __('Galerie konnte nicht gespeichert werden.', CPC2_TEXT_DOMAIN)), 500);
    }

    $gallery = get_post($gallery_id);
    wp_send_json_success(array(
        'message' => __('Galerie gespeichert.', CPC2_TEXT_DOMAIN),
        'title' => $gallery ? $gallery->post_title : '',
        'description' => $gallery ? $gallery->post_content : '',
        'status' => cpc_media_get_gallery_status($gallery_id),
        'type' => cpc_media_get_gallery_type($gallery_id),
    ));
}

function cpc_media_ajax_delete_gallery() {
    cpc_media_ajax_verify();

    $gallery_id = isset($_POST['gallery_id']) ? (int)$_POST['gallery_id'] : 0;
    $user_id = get_current_user_id();

    if (!$gallery_id || !cpc_media_user_can_manage_gallery($gallery_id, $user_id)) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
    }

    if (!cpc_media_delete_gallery($gallery_id, true)) {
        wp_send_json_error(array('message' => __('Galerie konnte nicht geloescht werden.', CPC2_TEXT_DOMAIN)), 500);
    }

    wp_send_json_success(array('message' => __('Galerie geloescht.', CPC2_TEXT_DOMAIN)));
}

function cpc_media_ajax_update_media() {
    cpc_media_ajax_verify();

    $media_id = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;
    $user_id = get_current_user_id();

    if (!$media_id || !cpc_media_user_can_manage_media($media_id, $user_id)) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
    }

    $ok = cpc_media_update_item($media_id, array(
        'title' => isset($_POST['title']) ? wp_unslash($_POST['title']) : null,
        'description' => isset($_POST['description']) ? wp_unslash($_POST['description']) : null,
    ));

    if (!$ok) {
        wp_send_json_error(array('message' => __('Medium konnte nicht gespeichert werden.', CPC2_TEXT_DOMAIN)), 500);
    }

    $media = get_post($media_id);
    wp_send_json_success(array(
        'message' => __('Medium gespeichert.', CPC2_TEXT_DOMAIN),
        'title' => $media ? $media->post_title : '',
        'description' => $media ? $media->post_content : '',
    ));
}

function cpc_media_ajax_delete_media() {
    cpc_media_ajax_verify();

    $media_id = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;
    $user_id = get_current_user_id();

    if (!$media_id || !cpc_media_user_can_manage_media($media_id, $user_id)) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
    }

    $gallery_id = (int)get_post_meta($media_id, 'cpc_media_gallery_id', true);
    if (!cpc_media_delete_item($media_id, true)) {
        wp_send_json_error(array('message' => __('Medium konnte nicht geloescht werden.', CPC2_TEXT_DOMAIN)), 500);
    }

    wp_send_json_success(array(
        'message' => __('Medium geloescht.', CPC2_TEXT_DOMAIN),
        'gallery_count' => $gallery_id ? cpc_media_get_gallery_media_count($gallery_id) : 0,
    ));
}

/**
 * Fetch all media from a gallery for lightbox display
 * Similar to MediaPress mpp_fetch_gallery_media
 */
function cpc_media_ajax_fetch_gallery_media() {
    $gallery_id = isset($_POST['gallery_id']) ? (int)$_POST['gallery_id'] : 0;
    $user_id = get_current_user_id();

    if (!$gallery_id) {
        wp_send_json_error(array('message' => __('Galerie nicht gefunden.', CPC2_TEXT_DOMAIN)), 400);
    }

    $gallery = get_post($gallery_id);
    if (!$gallery || $gallery->post_type !== 'cpc_gallery') {
        wp_send_json_error(array('message' => __('Galerie nicht gefunden.', CPC2_TEXT_DOMAIN)), 404);
    }

    // Permission check
    if (!cpc_media_user_can_view_gallery($gallery_id, $user_id)) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
    }

    $items = cpc_media_get_gallery_items($gallery_id, array(
        'posts_per_page' => -1,
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ));

    if (empty($items)) {
        wp_send_json_success(array('items' => array()));
    }

    $lightbox_items = array();
    foreach ($items as $item) {
        $lightbox_items[] = array(
            'src' => cpc_media_render_lightbox_content($item),
            'type' => 'inline',
            'data' => array(
                'media_id' => $item->ID,
                'title' => $item->post_title,
                'description' => $item->post_content,
            ),
        );
    }

    wp_send_json_success(array('items' => $lightbox_items));
}

/**
 * Fetch single media item with details
 */
function cpc_media_ajax_fetch_media() {
    $media_id = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;
    $user_id = get_current_user_id();

    if (!$media_id) {
        wp_send_json_error(array('message' => __('Medium nicht gefunden.', CPC2_TEXT_DOMAIN)), 400);
    }

    $media = get_post($media_id);
    if (!$media || $media->post_type !== 'cpc_media') {
        wp_send_json_error(array('message' => __('Medium nicht gefunden.', CPC2_TEXT_DOMAIN)), 404);
    }

    $gallery_id = (int)get_post_meta($media_id, 'cpc_media_gallery_id', true);
    if (!$gallery_id || !cpc_media_user_can_view_gallery($gallery_id, $user_id)) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
    }

    $lightbox_html = cpc_media_render_lightbox_content($media);

    wp_send_json_success(array(
        'media_id' => $media_id,
        'title' => $media->post_title,
        'description' => $media->post_content,
        'content' => $lightbox_html,
        'author' => get_the_author_meta('display_name', $media->post_author),
        'date' => get_the_date('', $media),
        'file_url' => cpc_media_get_item_url($media_id),
    ));
}

/**
 * Set gallery cover image
 */
function cpc_media_ajax_set_gallery_cover() {
    $gallery_id = isset($_POST['gallery_id']) ? (int)$_POST['gallery_id'] : 0;
    $media_id = isset($_POST['media_id']) ? (int)$_POST['media_id'] : 0;
    $user_id = get_current_user_id();

    if (!$gallery_id || !cpc_media_user_can_manage_gallery($gallery_id, $user_id)) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
    }

    // Verify media belongs to gallery
    $media = get_post($media_id);
    if (!$media || (int)get_post_meta($media_id, 'cpc_media_gallery_id', true) !== $gallery_id) {
        wp_send_json_error(array('message' => __('Medium nicht in dieser Galerie.', CPC2_TEXT_DOMAIN)), 400);
    }

    update_post_meta($gallery_id, 'cpc_media_gallery_cover_id', $media_id);
    do_action('cpc_media_gallery_cover_changed', $gallery_id, $media_id);

    $cover_url = cpc_media_get_gallery_cover_url($gallery_id);
    wp_send_json_success(array(
        'message' => __('Cover georessourcet.', CPC2_TEXT_DOMAIN),
        'cover_url' => $cover_url,
        'media_id' => $media_id,
    ));
}

/**
 * Get cover selector HTML for gallery management
 */
function cpc_media_ajax_get_cover_selector() {
    $gallery_id = isset($_POST['gallery_id']) ? (int)$_POST['gallery_id'] : 0;
    $user_id = get_current_user_id();

    if (!$gallery_id || !cpc_media_user_can_manage_gallery($gallery_id, $user_id)) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
    }

    $current_cover_id = (int)get_post_meta($gallery_id, 'cpc_media_gallery_cover_id', true);
    $items = cpc_media_get_gallery_items($gallery_id, array('posts_per_page' => 12));

    $html = '<div class="cpc_media_cover_selector">';
    $html .= '<div class="cpc_media_cover_options">';

    if (!empty($items)) {
        foreach ($items as $item) {
            $is_selected = $item->ID === $current_cover_id;
            $thumb_url = cpc_media_get_item_thumbnail_url($item->ID);
            $html .= '<label class="cpc_media_cover_option' . ($is_selected ? ' cpc_media_cover_selected' : '') . '">';
            $html .= '<input type="radio" name="cover_id" value="' . esc_attr($item->ID) . '" ' . ($is_selected ? 'checked' : '') . ' />';
            if ($thumb_url) {
                $html .= '<img src="' . esc_url($thumb_url) . '" alt="' . esc_attr($item->post_title) . '" />';
            }
            $html .= '</label>';
        }
    }

    $html .= '</div>';
    $html .= '</div>';

    wp_send_json_success(array('html' => $html));
}

/**
 * Reorder media items in gallery
 */
function cpc_media_ajax_reorder_items() {
    $gallery_id = isset($_POST['gallery_id']) ? (int)$_POST['gallery_id'] : 0;
    $order = isset($_POST['order']) && is_array($_POST['order']) ? array_map('intval', wp_unslash($_POST['order'])) : array();
    $user_id = get_current_user_id();

    if (!$gallery_id || !cpc_media_user_can_manage_gallery($gallery_id, $user_id)) {
        wp_send_json_error(array('message' => __('Keine Berechtigung.', CPC2_TEXT_DOMAIN)), 403);
    }

    if (empty($order)) {
        wp_send_json_error(array('message' => __('Keine Reihenfolge angegeben.', CPC2_TEXT_DOMAIN)), 400);
    }

    foreach ($order as $position => $media_id) {
        wp_update_post(array(
            'ID' => $media_id,
            'menu_order' => $position,
        ));
    }

    do_action('cpc_media_gallery_items_reordered', $gallery_id, $order);

    wp_send_json_success(array(
        'message' => __('Reihenfolge gespeichert.', CPC2_TEXT_DOMAIN),
    ));
}
