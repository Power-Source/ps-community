<?php

function cpc_gallery_list($atts) {
    $atts = shortcode_atts(array(
        'user_id' => 0,
        'component' => '',
        'component_id' => 0,
        'status' => '',
        'type' => '',
        'limit' => 20,
        'show_count' => true,
        'show_context' => true,
    ), $atts, 'cpc-gallery-list');

    $query_args = array(
        'posts_per_page' => max(1, min(100, (int)$atts['limit'])),
    );

    if (!empty($atts['user_id'])) {
        $user_id = $atts['user_id'] === 'user' ? get_current_user_id() : (int)$atts['user_id'];
        if ($user_id) {
            $query_args['author'] = $user_id;
        }
    }

    if (!empty($atts['component'])) {
        $query_args['component'] = sanitize_text_field($atts['component']);
    }
    if (!empty($atts['component_id'])) {
        $query_args['component_id'] = (int)$atts['component_id'];
    }
    if (!empty($atts['status'])) {
        $query_args['gallery_status'] = sanitize_text_field($atts['status']);
    }
    if (!empty($atts['type'])) {
        $query_args['gallery_type'] = sanitize_text_field($atts['type']);
    }

    $galleries = cpc_media_get_galleries($query_args);
    if (!$galleries) {
        return '<div class="cpc_gallery_list_empty">'.esc_html__('Keine Galerien gefunden.', CPC2_TEXT_DOMAIN).'</div>';
    }

    $html = '<div class="cpc_gallery_list cpc_gallery_list_'.esc_attr(cpc_media_get_gallery_layout()).'">';
    foreach ($galleries as $gallery) {
        if (!cpc_media_user_can_view_gallery($gallery->ID)) {
            continue;
        }

        $cover_url = cpc_media_get_gallery_cover_url($gallery->ID);
        $component = cpc_media_get_gallery_component($gallery->ID);
        $status = cpc_media_get_gallery_status($gallery->ID);
        $type = cpc_media_get_gallery_type($gallery->ID);

        $html .= '<div class="cpc_gallery_list_item mpp-gallery-card" data-gallery-id="'.esc_attr($gallery->ID).'">';
        if ($cover_url) {
            $html .= '<a class="cpc_gallery_list_cover cpc_media_lightbox_trigger" data-gallery-id="'.esc_attr($gallery->ID).'" href="'.esc_url(get_permalink($gallery)).'"><img src="'.esc_url($cover_url).'" alt="'.esc_attr($gallery->post_title).'" /></a>';
        }
        $html .= '<div class="cpc_gallery_list_body">';
        $html .= '<div class="cpc_gallery_list_badges"><span class="cpc_media_gallery_badge cpc_media_gallery_badge_type">'.esc_html(cpc_media_get_gallery_type_label($type)).'</span><span class="cpc_media_gallery_badge cpc_media_gallery_badge_status">'.esc_html(cpc_media_get_gallery_status_label($status, $component)).'</span></div>';
        $html .= '<h4 class="cpc_gallery_list_title"><a href="'.esc_url(get_permalink($gallery)).'">'.esc_html($gallery->post_title).'</a></h4>';

        if ($atts['show_context']) {
            $html .= '<div class="cpc_gallery_list_meta">';
            $html .= esc_html(cpc_media_get_gallery_component_label($component));
            $html .= ' / '.esc_html(cpc_media_get_gallery_status_label($status, $component));
            $html .= ' / '.esc_html(cpc_media_get_gallery_type_label($type));
            $html .= '</div>';
        }

        if ($atts['show_count']) {
            $html .= '<div class="cpc_gallery_list_count">';
            $html .= sprintf(esc_html__('%d Medien', CPC2_TEXT_DOMAIN), cpc_media_get_gallery_media_count($gallery->ID));
            $html .= '</div>';
        }

        if ($gallery->post_content) {
            $html .= '<div class="cpc_gallery_list_excerpt">'.wp_kses_post(wpautop(wp_trim_words($gallery->post_content, 28))).'</div>';
        }

        $html .= '</div>';
        $html .= '</div>';
    }
    $html .= '</div>';

    return $html;
}
add_shortcode('cpc-gallery-list', 'cpc_gallery_list');

function cpc_media_directory_get_base_url() {
    if (is_page()) {
        return get_permalink(get_queried_object_id());
    }

    return cpc_media_get_current_request_url();
}

function cpc_media_directory_build_url($args = array()) {
    $url = cpc_media_directory_get_base_url();
    $defaults = array(
        'cpc_media_view' => isset($_GET['cpc_media_view']) ? sanitize_key(wp_unslash($_GET['cpc_media_view'])) : 'galleries',
        'cpc_media_type' => isset($_GET['cpc_media_type']) ? sanitize_key(wp_unslash($_GET['cpc_media_type'])) : '',
        'cpc_media_q' => isset($_GET['cpc_media_q']) ? sanitize_text_field(wp_unslash($_GET['cpc_media_q'])) : '',
        'cpc_media_page' => isset($_GET['cpc_media_page']) ? max(1, (int)$_GET['cpc_media_page']) : 1,
    );

    $params = array_merge($defaults, $args);
    foreach ($params as $key => $value) {
        if ($value === '' || $value === null || $value === 0 || $value === '0') {
            $url = remove_query_arg($key, $url);
            continue;
        }
        $url = add_query_arg($key, $value, $url);
    }

    return $url;
}

function cpc_media_directory_get_gallery_results($type = '', $search = '', $page = 1, $per_page = 12) {
    $visible = array();
    $offset = 0;
    $loops = 0;
    $target_count = ($page * $per_page) + 1;
    $batch_size = max(24, $per_page * 4);
    $has_more = false;

    while ($loops < 12 && count($visible) < $target_count) {
        $query_args = array(
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        if ($type !== '') {
            $query_args['gallery_type'] = $type;
        }
        if ($search !== '') {
            $query_args['s'] = $search;
        }

        $batch = cpc_media_get_galleries($query_args);
        if (!$batch) {
            break;
        }

        foreach ($batch as $gallery) {
            if (cpc_media_user_can_view_gallery($gallery->ID)) {
                $visible[] = $gallery;
                if (count($visible) >= $target_count) {
                    $has_more = true;
                    break 2;
                }
            }
        }

        if (count($batch) < $batch_size) {
            break;
        }

        $offset += $batch_size;
        $loops++;
    }

    if (!$has_more && count($visible) > ($page * $per_page)) {
        $has_more = true;
    }

    return array(
        'items' => array_slice($visible, max(0, ($page - 1) * $per_page), $per_page),
        'has_more' => $has_more,
    );
}

function cpc_media_directory_get_media_results($type = '', $search = '', $page = 1, $per_page = 12) {
    $visible = array();
    $offset = 0;
    $loops = 0;
    $target_count = ($page * $per_page) + 1;
    $batch_size = max(24, $per_page * 4);
    $has_more = false;

    while ($loops < 12 && count($visible) < $target_count) {
        $query_args = array(
            'post_type' => 'cpc_media',
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'date',
            'order' => 'DESC',
        );

        if ($search !== '') {
            $query_args['s'] = $search;
        }
        if ($type !== '') {
            $query_args['meta_query'] = array(
                array(
                    'key' => 'cpc_media_type',
                    'value' => $type,
                ),
            );
        }

        $batch = get_posts($query_args);
        if (!$batch) {
            break;
        }

        foreach ($batch as $media) {
            $gallery_id = (int)get_post_meta($media->ID, 'cpc_media_gallery_id', true);
            if ($gallery_id && !cpc_media_is_system_gallery($gallery_id) && cpc_media_user_can_view_gallery($gallery_id)) {
                $visible[] = $media;
                if (count($visible) >= $target_count) {
                    $has_more = true;
                    break 2;
                }
            }
        }

        if (count($batch) < $batch_size) {
            break;
        }

        $offset += $batch_size;
        $loops++;
    }

    if (!$has_more && count($visible) > ($page * $per_page)) {
        $has_more = true;
    }

    return array(
        'items' => array_slice($visible, max(0, ($page - 1) * $per_page), $per_page),
        'has_more' => $has_more,
    );
}

function cpc_media_render_directory_filters($view, $type, $search) {
    $base_url = cpc_media_directory_get_base_url();
    $html = '<form method="get" action="'.esc_url($base_url).'" class="cpc_media_directory_filters">';
    $html .= '<input type="hidden" name="cpc_media_view" value="'.esc_attr($view).'" />';
    $html .= '<div class="cpc_media_directory_filter_field">';
    $html .= '<label>'.esc_html__('Suche', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<input type="search" name="cpc_media_q" value="'.esc_attr($search).'" placeholder="'.esc_attr__('Titel oder Beschreibung', CPC2_TEXT_DOMAIN).'" />';
    $html .= '</div>';
    $html .= '<div class="cpc_media_directory_filter_field">';
    $html .= '<label>'.esc_html__('Typ', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select name="cpc_media_type">';
    $html .= '<option value="">'.esc_html__('Alle Typen', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="photo"'.selected($type, 'photo', false).'>'.esc_html__('Bilder', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="video"'.selected($type, 'video', false).'>'.esc_html__('Videos', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="audio"'.selected($type, 'audio', false).'>'.esc_html__('Audio', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="doc"'.selected($type, 'doc', false).'>'.esc_html__('Dokumente', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '</select>';
    $html .= '</div>';
    $html .= '<div class="cpc_media_directory_filter_actions">';
    $html .= '<button type="submit" class="cpc_button cpc_media_btn_primary">'.esc_html__('Filtern', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '<a class="cpc_button cpc_media_btn_secondary" href="'.esc_url(cpc_media_directory_build_url(array('cpc_media_type' => '', 'cpc_media_q' => '', 'cpc_media_page' => 1))).'">'.esc_html__('Zurücksetzen', CPC2_TEXT_DOMAIN).'</a>';
    $html .= '</div>';
    $html .= '</form>';

    return $html;
}

function cpc_media_render_directory_pagination($page, $has_more) {
    $page = max(1, (int)$page);
    if ($page <= 1 && !$has_more) {
        return '';
    }

    $html = '<div class="cpc_media_directory_pagination">';
    if ($page > 1) {
        $html .= '<a class="cpc_button cpc_media_btn_secondary" href="'.esc_url(cpc_media_directory_build_url(array('cpc_media_page' => $page - 1))).'">'.esc_html__('Vorherige', CPC2_TEXT_DOMAIN).'</a>';
    }
    $html .= '<span class="cpc_media_directory_page_label">'.sprintf(esc_html__('Seite %d', CPC2_TEXT_DOMAIN), $page).'</span>';
    if ($has_more) {
        $html .= '<a class="cpc_button cpc_media_btn_secondary" href="'.esc_url(cpc_media_directory_build_url(array('cpc_media_page' => $page + 1))).'">'.esc_html__('Nächste', CPC2_TEXT_DOMAIN).'</a>';
    }
    $html .= '</div>';

    return $html;
}

function cpc_media_directory_shortcode($atts) {
    $atts = shortcode_atts(array(
        'view' => 'galleries',
        'per_page' => cpc_media_get_directory_items_per_page(),
    ), $atts, 'cpc-media-directory');

    $view = isset($_GET['cpc_media_view']) ? sanitize_key(wp_unslash($_GET['cpc_media_view'])) : sanitize_key($atts['view']);
    if (!in_array($view, array('galleries', 'media'), true)) {
        $view = 'galleries';
    }

    $type = isset($_GET['cpc_media_type']) ? sanitize_key(wp_unslash($_GET['cpc_media_type'])) : '';
    $search = isset($_GET['cpc_media_q']) ? sanitize_text_field(wp_unslash($_GET['cpc_media_q'])) : '';
    $page = isset($_GET['cpc_media_page']) ? max(1, (int)$_GET['cpc_media_page']) : 1;
    $per_page = max(6, min(60, (int)$atts['per_page']));

    $html = '<div class="cpc_media_directory">';
    $html .= '<div class="cpc_media_directory_header">';
    $html .= '<div class="cpc_media_directory_tabs">';
    $html .= '<a class="cpc_media_directory_tab'.($view === 'galleries' ? ' active' : '').'" href="'.esc_url(cpc_media_directory_build_url(array('cpc_media_view' => 'galleries', 'cpc_media_page' => 1))).'">'.esc_html__('Galerien', CPC2_TEXT_DOMAIN).'</a>';
    $html .= '<a class="cpc_media_directory_tab'.($view === 'media' ? ' active' : '').'" href="'.esc_url(cpc_media_directory_build_url(array('cpc_media_view' => 'media', 'cpc_media_page' => 1))).'">'.esc_html__('Medien', CPC2_TEXT_DOMAIN).'</a>';
    $html .= '</div>';
    $html .= cpc_media_render_directory_filters($view, $type, $search);
    $html .= '</div>';

    if ($view === 'media') {
        $results = cpc_media_directory_get_media_results($type, $search, $page, $per_page);
        if (empty($results['items'])) {
            $html .= '<div class="cpc_gallery_items_empty">'.esc_html__('Keine Medien gefunden.', CPC2_TEXT_DOMAIN).'</div>';
        } else {
            $layout = cpc_media_get_gallery_layout();
            $grid_cols = cpc_media_get_gallery_grid_columns();
            $classes = 'cpc_gallery_items cpc_gallery_items_'.$layout;
            if ($layout === 'grid') {
                $classes .= ' cpc_gallery_cols_'.intval($grid_cols);
            }
            $html .= '<div class="'.esc_attr($classes).' cpc_media_directory_media_grid">';
            foreach ($results['items'] as $item) {
                $html .= cpc_media_render_media_item_html($item);
            }
            $html .= '</div>';
        }
        $html .= cpc_media_render_directory_pagination($page, !empty($results['has_more']));
    } else {
        $results = cpc_media_directory_get_gallery_results($type, $search, $page, $per_page);
        if (empty($results['items'])) {
            $html .= '<div class="cpc_gallery_list_empty">'.esc_html__('Keine Galerien gefunden.', CPC2_TEXT_DOMAIN).'</div>';
        } else {
            $html .= '<div class="cpc_gallery_list cpc_media_directory_gallery_list">';
            foreach ($results['items'] as $gallery) {
                $cover_url = cpc_media_get_gallery_cover_url($gallery->ID);
                $component = cpc_media_get_gallery_component($gallery->ID);
                $status = cpc_media_get_gallery_status($gallery->ID);
                $gallery_type = cpc_media_get_gallery_type($gallery->ID);

                $html .= '<div class="cpc_gallery_list_item mpp-gallery-card" data-gallery-id="'.esc_attr($gallery->ID).'">';
                if ($cover_url) {
                    $html .= '<a class="cpc_gallery_list_cover cpc_media_lightbox_trigger" data-gallery-id="'.esc_attr($gallery->ID).'" href="'.esc_url(get_permalink($gallery)).'"><img src="'.esc_url($cover_url).'" alt="'.esc_attr($gallery->post_title).'" /></a>';
                }
                $html .= '<div class="cpc_gallery_list_body">';
                $html .= '<div class="cpc_gallery_list_badges"><span class="cpc_media_gallery_badge cpc_media_gallery_badge_type">'.esc_html(cpc_media_get_gallery_type_label($gallery_type)).'</span><span class="cpc_media_gallery_badge cpc_media_gallery_badge_status">'.esc_html(cpc_media_get_gallery_status_label($status, $component)).'</span></div>';
                $html .= '<h4 class="cpc_gallery_list_title"><a href="'.esc_url(get_permalink($gallery)).'">'.esc_html($gallery->post_title).'</a></h4>';
                $html .= '<div class="cpc_gallery_list_meta">'.esc_html(cpc_media_get_gallery_component_label($component)).' / '.esc_html(cpc_media_get_gallery_type_label($gallery_type)).'</div>';
                $html .= '<div class="cpc_gallery_list_count">'.sprintf(esc_html__('%d Medien', CPC2_TEXT_DOMAIN), cpc_media_get_gallery_media_count($gallery->ID)).'</div>';
                if ($gallery->post_content) {
                    $html .= '<div class="cpc_gallery_list_excerpt">'.wp_kses_post(wpautop(wp_trim_words($gallery->post_content, 28))).'</div>';
                }
                $html .= '</div>';
                $html .= '</div>';
            }
            $html .= '</div>';
        }
        $html .= cpc_media_render_directory_pagination($page, !empty($results['has_more']));
    }

    $html .= '</div>';

    return $html;
}
add_shortcode('cpc-media-directory', 'cpc_media_directory_shortcode');
add_shortcode('cpc-gallery-directory', 'cpc_media_directory_shortcode');

function cpc_media_render_directory_page_content($content) {
    if (!cpc_media_is_enabled() || !is_page() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $page_id = cpc_media_get_directory_page_id();
    if (!$page_id || (int)get_queried_object_id() !== $page_id) {
        return $content;
    }

    global $post;
    if ($post && has_shortcode((string)$post->post_content, 'cpc-media-directory')) {
        return $content;
    }

    $directory = cpc_media_directory_shortcode(array());
    if (trim((string)$content) === '') {
        return $directory;
    }

    return $content.$directory;
}
add_filter('the_content', 'cpc_media_render_directory_page_content', 25);

function cpc_media_render_media_item_html($item) {
    if (!$item || $item->post_type !== 'cpc_media') {
        return '';
    }

    $media_id = (int)$item->ID;
    $gallery_id = (int)get_post_meta($media_id, 'cpc_media_gallery_id', true);
    $display_url = cpc_media_get_media_file_url($media_id);
    $mime_type = cpc_media_get_media_mime_type($media_id);
    $can_manage = cpc_media_user_can_manage_media($media_id);
    $extension = strtoupper(pathinfo((string)$display_url, PATHINFO_EXTENSION));
    $date = get_the_date('', $item);
    $lightbox_enabled = cpc_media_lightbox_enabled();
    $reorder_enabled = cpc_media_reorder_enabled() && $can_manage;

    $html = '<div class="cpc_gallery_item mpp-media-card" data-media-id="'.$media_id.'" data-gallery-id="'.$gallery_id.'">';
    
    // Reorder handle (if managing and enabled)
    if ($reorder_enabled) {
        $html .= '<span class="cpc_gallery_item_drag_handle" title="'.esc_attr__('Ziehen zum Umordnen', CPC2_TEXT_DOMAIN).'">⋮⋮</span>';
    }
    
    $html .= '<div class="cpc_gallery_item_preview mpp-item-entry">';
    if ($display_url && strpos((string)$mime_type, 'image/') === 0) {
        // Image - make clickable for lightbox if enabled
        if ($lightbox_enabled) {
            $html .= '<a href="#" class="cpc_media_lightbox_trigger" data-media-id="'.esc_attr($media_id).'" data-gallery-id="'.esc_attr($gallery_id).'">';
            $html .= '<img src="'.esc_url($display_url).'" alt="'.esc_attr($item->post_title).'" />';
            $html .= '</a>';
        } else {
            $html .= '<a href="'.esc_url($display_url).'" target="_blank" rel="noopener noreferrer">';
            $html .= '<img src="'.esc_url($display_url).'" alt="'.esc_attr($item->post_title).'" />';
            $html .= '</a>';
        }
    } elseif ($display_url) {
        $trigger_class = $lightbox_enabled ? 'cpc_media_lightbox_trigger' : '';
        $html .= '<a class="cpc_gallery_item_file '.$trigger_class.'" href="'.($lightbox_enabled ? '#' : esc_url($display_url)).'" '.($lightbox_enabled ? 'data-media-id="'.esc_attr($media_id).'" data-gallery-id="'.esc_attr($gallery_id).'"' : 'target="_blank" rel="noopener noreferrer"').'>'.esc_html($item->post_title ? $item->post_title : basename($display_url)).'</a>';
    } else {
        $html .= '<span>'.esc_html($item->post_title).'</span>';
    }
    $html .= '</div>';

    $html .= '<div class="cpc_gallery_item_meta mpp-item-meta">';
    $html .= '<div class="cpc_gallery_item_meta_top">';
    if ($extension) {
        $html .= '<span class="cpc_gallery_item_badge">'.esc_html($extension).'</span>';
    }
    if ($date) {
        $html .= '<span class="cpc_gallery_item_date">'.esc_html($date).'</span>';
    }
    $html .= '</div>';
    $html .= '<div class="cpc_gallery_item_title">'.esc_html($item->post_title).'</div>';
    if (!empty($item->post_content) && cpc_media_show_item_descriptions()) {
        $html .= '<div class="cpc_gallery_item_desc">'.esc_html($item->post_content).'</div>';
    }
    $html .= '</div>';

    if ($can_manage) {
        $html .= '<div class="cpc_gallery_item_actions" style="display:flex; gap:8px; flex-wrap:wrap; margin-top:8px;">';
        $html .= '<button type="button" class="cpc_button cpc_media_btn_secondary cpc_media_edit_media_btn"><span class="dashicons dashicons-edit"></span> '.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '<button type="button" class="cpc_button cpc_media_btn_secondary cpc_media_btn_danger cpc_media_delete_media_btn"><span class="dashicons dashicons-trash"></span> '.esc_html__('Löschen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</div>';

        $html .= '<form class="cpc_media_edit_media_form" style="display:none; margin-top:12px; padding:14px; background:#f9f9f9; border-radius:4px; border:1px solid #ddd;">';
        $html .= '<h5 style="margin:0 0 12px 0; font-size:14px;">'.esc_html__('Medien-Element bearbeiten', CPC2_TEXT_DOMAIN).'</h5>';
        $html .= '<div>';
        $html .= '<label style="display:block; margin-bottom:8px; font-weight:600; font-size:12px;">'.esc_html__('Titel', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<input type="text" name="title" value="'.esc_attr($item->post_title).'" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:3px; box-sizing:border-box;" />';
        $html .= '</div>';
        $html .= '<div style="margin-top:10px;">';
        $html .= '<label style="display:block; margin-bottom:8px; font-weight:600; font-size:12px;">'.esc_html__('Beschreibung', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<textarea name="description" rows="3" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:3px; box-sizing:border-box; font-family:inherit;">'.esc_textarea($item->post_content).'</textarea>';
        $html .= '</div>';
        $html .= '<div style="margin-top:12px; display:flex; gap:6px;">';
        $html .= '<button type="submit" class="cpc_button cpc_media_btn_primary"><span class="dashicons dashicons-yes"></span> '.esc_html__('Speichern', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '<button type="button" class="cpc_button cpc_media_btn_secondary cpc_media_cancel_edit_media_btn"><span class="dashicons dashicons-no"></span> '.esc_html__('Abbrechen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</div>';
        $html .= '</form>';
    }

    $html .= '</div>';

    return $html;
}

function cpc_gallery_items($atts) {
    $atts = shortcode_atts(array(
        'gallery_id' => 0,
        'limit' => 24,
    ), $atts, 'cpc-gallery-items');

    $gallery_id = (int)$atts['gallery_id'];
    if (!$gallery_id || !cpc_media_user_can_view_gallery($gallery_id)) {
        return '';
    }

    $items = cpc_media_get_gallery_items($gallery_id, array(
        'posts_per_page' => max(1, min(100, (int)$atts['limit'])),
        'orderby' => 'menu_order',
        'order' => 'ASC',
    ));

    if (!$items) {
        return '<div class="cpc_gallery_items_empty">'.esc_html__('Keine Medien gefunden.', CPC2_TEXT_DOMAIN).'</div>';
    }

    $can_manage = cpc_media_user_can_manage_gallery($gallery_id);
    $layout = cpc_media_get_gallery_layout();
    $grid_cols = cpc_media_get_gallery_grid_columns();
    $css_classes = 'cpc_gallery_items cpc_gallery_items_'.$layout;
    
    // Add column class for grid layout
    if ($layout === 'grid') {
        $css_classes .= ' cpc_gallery_cols_'.intval($grid_cols);
    }
    
    // Add sortable class if user can manage
    if ($can_manage) {
        $css_classes .= ' cpc_media_sortable';
    }

    $html = '<div class="'.esc_attr($css_classes).'" data-gallery-id="'.esc_attr($gallery_id).'">';
    foreach ($items as $item) {
        $html .= cpc_media_render_media_item_html($item);
    }
    $html .= '</div>';

    return $html;
}
add_shortcode('cpc-gallery-items', 'cpc_gallery_items');
