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

        $html .= '<div class="cpc_gallery_list_item mpp-gallery-card">';
        if ($cover_url) {
            $html .= '<a class="cpc_gallery_list_cover" href="'.esc_url(get_permalink($gallery)).'"><img src="'.esc_url($cover_url).'" alt="'.esc_attr($gallery->post_title).'" /></a>';
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
    if (!empty($item->post_content)) {
        $html .= '<div class="cpc_gallery_item_desc">'.esc_html($item->post_content).'</div>';
    }
    $html .= '</div>';

    if ($can_manage) {
        $html .= '<div class="cpc_gallery_item_actions">';
        $html .= '<button type="button" class="cpc_button cpc_media_edit_media_btn">'.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</button> ';
        $html .= '<button type="button" class="cpc_button cpc_media_delete_media_btn">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</div>';

        $html .= '<form class="cpc_media_edit_media_form" style="display:none; margin-top:6px;">';
        $html .= '<input type="text" name="title" value="'.esc_attr($item->post_title).'" style="width:100%;max-width:360px;" />';
        $html .= '<textarea name="description" rows="2" style="width:100%;max-width:360px; margin-top:4px;">'.esc_textarea($item->post_content).'</textarea>';
        $html .= '<div style="margin-top:4px;">';
        $html .= '<button type="submit" class="cpc_button">'.esc_html__('Speichern', CPC2_TEXT_DOMAIN).'</button> ';
        $html .= '<button type="button" class="cpc_button cpc_media_cancel_edit_media_btn">'.esc_html__('Abbrechen', CPC2_TEXT_DOMAIN).'</button>';
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
    $css_classes = 'cpc_gallery_items cpc_gallery_items_'.$layout;
    
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
