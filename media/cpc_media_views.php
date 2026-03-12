<?php

function cpc_media_add_profile_tab($tabs, $user_id, $viewer_id) {
    if (!cpc_media_is_enabled()) {
        return $tabs;
    }

    $tabs['galleries'] = array(
        'label' => __('Galerien', CPC2_TEXT_DOMAIN),
        'icon' => 'format-gallery',
        'priority' => 25,
    );

    return $tabs;
}
add_filter('cpc_profile_tabs', 'cpc_media_add_profile_tab', 20, 3);

function cpc_media_add_group_tab($tabs, $group_id, $user_id) {
    if (!cpc_media_is_enabled()) {
        return $tabs;
    }

    if (cpc_can_view_group($user_id, $group_id)) {
        $tabs['gallery'] = array(
            'label' => __('Galerien', CPC2_TEXT_DOMAIN),
            'icon' => 'format-gallery',
            'priority' => 22,
        );
    }

    return $tabs;
}
add_filter('cpc_group_tabs', 'cpc_media_add_group_tab', 20, 3);

function cpc_media_render_notice_html() {
    if (empty($_GET['cpc_media_notice'])) {
        return '';
    }

    $notice_code = sanitize_key(wp_unslash($_GET['cpc_media_notice']));
    $message = cpc_media_notice_message($notice_code);
    if (!$message) {
        return '';
    }

    $class = 'cpc_media_notice';
    if (in_array($notice_code, array('failed', 'invalid', 'denied', 'upload_failed'), true)) {
        $class .= ' cpc_media_notice_error';
    } else {
        $class .= ' cpc_media_notice_success';
    }

    return '<div class="'.$class.'">'.esc_html($message).'</div>';
}

function cpc_media_render_status_select_html($field_name, $selected, $component = 'members') {
    $options = cpc_media_get_gallery_status_options($component);
    $html = '<select name="'.esc_attr($field_name).'">';

    foreach ($options as $value => $label) {
        $html .= '<option value="'.esc_attr($value).'"'.selected($selected, $value, false).'>'.esc_html($label).'</option>';
    }

    $html .= '</select>';

    return $html;
}

function cpc_media_render_create_gallery_form($component, $component_id, $default_type = 'photo') {
    if (!is_user_logged_in() || !cpc_media_user_can_create_gallery_for_context($component, $component_id)) {
        return '';
    }

    $default_status = cpc_media_get_default_gallery_status($component);

    $html = '';
    $html .= '<form method="post" class="cpc_media_create_gallery_form mpp-form mpp-form-stacked">';
    $html .= '<input type="hidden" name="cpc_media_action" value="create_gallery" />';
    $html .= '<input type="hidden" name="cpc_media_component" value="'.esc_attr($component).'" />';
    $html .= '<input type="hidden" name="cpc_media_component_id" value="'.(int)$component_id.'" />';
    $html .= '<input type="hidden" name="cpc_media_redirect" class="cpc_media_redirect_field" value="" />';
    $html .= '<input type="hidden" name="cpc_media_nonce" value="'.esc_attr(wp_create_nonce('cpc_media_frontend_action')).'" />';
    $html .= '<div class="cpc_media_form_grid">';
    $html .= '<div class="cpc_media_form_main">';
    $html .= '<label>'.esc_html__('Name der Galerie', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<input type="text" name="cpc_media_gallery_title" class="mpp-input-1" required />';
    $html .= '<div class="cpc_media_form_spacer"></div>';
    $html .= '<label>'.esc_html__('Beschreibung', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<textarea name="cpc_media_gallery_description" rows="3" class="mpp-input-1"></textarea>';
    $html .= '</div>';
    $html .= '<div class="cpc_media_form_side">';
    $html .= '<label>'.esc_html__('Status', CPC2_TEXT_DOMAIN).'</label>';
    $html .= cpc_media_render_status_select_html('cpc_media_gallery_status', $default_status, $component);
    $html .= '<div class="cpc_media_form_spacer"></div>';
    $html .= '<label>'.esc_html__('Typ', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select name="cpc_media_gallery_type"><option value="photo"'.selected($default_type, 'photo', false).'>'.esc_html__('Bilder', CPC2_TEXT_DOMAIN).'</option><option value="video"'.selected($default_type, 'video', false).'>'.esc_html__('Videos', CPC2_TEXT_DOMAIN).'</option><option value="audio"'.selected($default_type, 'audio', false).'>'.esc_html__('Audio', CPC2_TEXT_DOMAIN).'</option><option value="doc"'.selected($default_type, 'doc', false).'>'.esc_html__('Dokumente', CPC2_TEXT_DOMAIN).'</option></select>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="cpc_media_form_submit"><button type="submit" class="cpc_button cpc_media_primary_button">'.esc_html__('Galerie erstellen', CPC2_TEXT_DOMAIN).'</button></div>';
    $html .= '</form>';

    return $html;
}

function cpc_media_render_upload_form($gallery_id) {
    $gallery_id = (int)$gallery_id;
    if (!$gallery_id || !is_user_logged_in() || !cpc_media_user_can_upload_to_gallery($gallery_id)) {
        return '';
    }

    $html = '';
    $html .= '<form method="post" enctype="multipart/form-data" class="cpc_media_upload_form" data-gallery-id="'.$gallery_id.'" style="margin-top:8px;">';
    $html .= '<input type="hidden" name="cpc_media_action" value="upload_media" />';
    $html .= '<input type="hidden" name="cpc_media_gallery_id" value="'.$gallery_id.'" />';
    $html .= '<input type="hidden" name="cpc_media_redirect" class="cpc_media_redirect_field" value="" />';
    $html .= '<input type="hidden" name="cpc_media_nonce" value="'.esc_attr(wp_create_nonce('cpc_media_frontend_action')).'" />';
    $html .= '<div class="cpc_media_dropzone" data-gallery-id="'.$gallery_id.'">'.esc_html__('Dateien hierher ziehen oder klicken zum Auswaehlen', CPC2_TEXT_DOMAIN).'</div>';
    $html .= '<input type="file" class="cpc_media_file_input" name="cpc_media_files[]" multiple style="display:none;" /> ';
    $html .= '<button type="submit" class="cpc_button">'.esc_html__('Dateien hochladen', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '<div class="cpc_media_upload_progress" style="display:none;"><span class="cpc_media_upload_progress_bar"></span></div>';
    $html .= '<div class="cpc_media_upload_status"></div>';
    $html .= '</form>';

    return $html;
}

function cpc_media_render_gallery_block($gallery) {
    if (!$gallery || $gallery->post_type !== 'cpc_gallery') {
        return '';
    }

    $gallery_id = (int)$gallery->ID;
    if (!cpc_media_user_can_view_gallery($gallery_id)) {
        return '';
    }

    $html = '';
    $can_manage = cpc_media_user_can_manage_gallery($gallery_id);
    $component = cpc_media_get_gallery_component($gallery_id);
    $status = cpc_media_get_gallery_status($gallery_id);
    $type = cpc_media_get_gallery_type($gallery_id);
    $cover_url = cpc_media_get_gallery_cover_url($gallery_id);
    $layout = cpc_media_get_gallery_layout();
    $preview_items = cpc_media_get_gallery_preview_items($gallery_id, 4);
    $author_name = get_the_author_meta('display_name', $gallery->post_author);

    $html .= '<div class="cpc_media_gallery_block mpp-gallery-card cpc_media_layout_'.esc_attr($layout).'" data-gallery-id="'.$gallery_id.'">';
    $html .= '<div class="cpc_media_gallery_shell">';
    $html .= '<div class="cpc_media_gallery_cover_wrap">';
    if ($cover_url) {
        $html .= '<div class="cpc_media_gallery_cover cpc_media_lightbox_trigger" data-gallery-id="'.esc_attr($gallery_id).'" role="button" tabindex="0"><img src="'.esc_url($cover_url).'" alt="'.esc_attr($gallery->post_title).'" /></div>';
    } else {
        $html .= '<div class="cpc_media_gallery_cover cpc_media_gallery_cover_empty cpc_media_lightbox_trigger" data-gallery-id="'.esc_attr($gallery_id).'" role="button" tabindex="0"><span>'.esc_html__('Keine Vorschau', CPC2_TEXT_DOMAIN).'</span></div>';
    }

    if ($preview_items) {
        $html .= '<div class="cpc_media_gallery_preview_strip">';
        foreach ($preview_items as $preview_item) {
            $preview_url = cpc_media_get_media_file_url($preview_item->ID);
            if (!$preview_url) {
                continue;
            }
            $html .= '<span class="cpc_media_gallery_preview_thumb"><img src="'.esc_url($preview_url).'" alt="'.esc_attr($preview_item->post_title).'" /></span>';
        }
        $html .= '</div>';
    }
    $html .= '</div>';

    $html .= '<div class="cpc_media_gallery_body">';
    $html .= '<div class="cpc_media_gallery_meta_top">';
    $html .= '<span class="cpc_media_gallery_badge cpc_media_gallery_badge_type">'.esc_html(cpc_media_get_gallery_type_label($type)).'</span>';
    $html .= '<span class="cpc_media_gallery_badge cpc_media_gallery_badge_status">'.esc_html(cpc_media_get_gallery_status_label($status, $component)).'</span>';
    $html .= '</div>';
    $html .= '<h4 class="cpc_media_gallery_title">'.esc_html($gallery->post_title).'</h4>';
    if ($gallery->post_content && cpc_media_show_gallery_descriptions()) {
        $html .= '<div class="cpc_media_gallery_desc">'.wp_kses_post(wpautop(wp_trim_words($gallery->post_content, 36))).'</div>';
    }
    $html .= '<div class="cpc_media_gallery_meta cpc_media_gallery_meta_bottom">';
    $html .= '<span class="cpc_media_gallery_meta_item">'.esc_html(cpc_media_get_gallery_component_label($component)).'</span>';
    if ($author_name) {
        $html .= '<span class="cpc_media_gallery_meta_item">'.sprintf(esc_html__('von %s', CPC2_TEXT_DOMAIN), esc_html($author_name)).'</span>';
    }
    $html .= '<span class="cpc_media_gallery_count">'.sprintf(esc_html__('%d Medien', CPC2_TEXT_DOMAIN), cpc_media_get_gallery_media_count($gallery_id)).'</span>';
    $html .= '</div>';

    if ($can_manage) {
        $html .= '<div class="cpc_media_gallery_actions">';
        $html .= '<button type="button" class="cpc_button cpc_media_btn_secondary cpc_media_edit_gallery_btn"><span class="dashicons dashicons-edit"></span> '.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</button>';
        if (cpc_media_cover_selector_enabled()) {
            $html .= '<button type="button" class="cpc_button cpc_media_btn_secondary cpc_media_cover_gallery_btn"><span class="dashicons dashicons-image-filter"></span> '.esc_html__('Cover', CPC2_TEXT_DOMAIN).'</button>';
        }
        $html .= '<button type="button" class="cpc_button cpc_media_btn_secondary cpc_media_btn_danger cpc_media_delete_gallery_btn"><span class="dashicons dashicons-trash"></span> '.esc_html__('Löschen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</div>';

        $html .= '<form class="cpc_media_edit_gallery_form" style="display:none; margin-top:12px; padding:14px; background:#f9f9f9; border-radius:4px; border:1px solid #ddd;">';
        $html .= '<h5 style="margin:0 0 12px 0; font-size:14px;">'.esc_html__('Galerie bearbeiten', CPC2_TEXT_DOMAIN).'</h5>';
        $html .= '<div>';
        $html .= '<label style="display:block; margin-bottom:8px; font-weight:600; font-size:12px;">'.esc_html__('Titel', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<input type="text" name="title" value="'.esc_attr($gallery->post_title).'" style="max-width:100%; width:100%; padding:8px; border:1px solid #ddd; border-radius:3px;" />';
        $html .= '</div>';
        $html .= '<div style="margin-top:10px;">';
        $html .= '<label style="display:block; margin-bottom:8px; font-weight:600; font-size:12px;">'.esc_html__('Beschreibung', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<textarea name="description" rows="3" style="max-width:100%; width:100%; padding:8px; border:1px solid #ddd; border-radius:3px; font-family:inherit;">'.esc_textarea($gallery->post_content).'</textarea>';
        $html .= '</div>';
        $html .= '<div style="margin-top:10px; display:grid; grid-template-columns:1fr 1fr; gap:10px;">';
        $html .= '<div>';
        $html .= '<label style="display:block; margin-bottom:6px; font-weight:600; font-size:12px;">'.esc_html__('Status', CPC2_TEXT_DOMAIN).'</label>';
        $html .= cpc_media_render_status_select_html('status', $status, $component);
        $html .= '</div>';
        $html .= '<div>';
        $html .= '<label style="display:block; margin-bottom:6px; font-weight:600; font-size:12px;">'.esc_html__('Typ', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<select name="type" style="width:100%; padding:8px; border:1px solid #ddd; border-radius:3px;"><option value="photo"'.selected(cpc_media_get_gallery_type($gallery_id), 'photo', false).'>'.esc_html__('Bilder', CPC2_TEXT_DOMAIN).'</option><option value="video"'.selected(cpc_media_get_gallery_type($gallery_id), 'video', false).'>'.esc_html__('Videos', CPC2_TEXT_DOMAIN).'</option><option value="audio"'.selected(cpc_media_get_gallery_type($gallery_id), 'audio', false).'>'.esc_html__('Audio', CPC2_TEXT_DOMAIN).'</option><option value="doc"'.selected(cpc_media_get_gallery_type($gallery_id), 'doc', false).'>'.esc_html__('Dokumente', CPC2_TEXT_DOMAIN).'</option></select>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div style="margin-top:12px; display:flex; gap:6px;">';
        $html .= '<button type="submit" class="cpc_button cpc_media_btn_primary"><span class="dashicons dashicons-yes"></span> '.esc_html__('Speichern', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '<button type="button" class="cpc_button cpc_media_btn_secondary cpc_media_cancel_edit_gallery_btn"><span class="dashicons dashicons-no"></span> '.esc_html__('Abbrechen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</div>';
        $html .= '</form>';

        // Cover selector form
        $html .= '<div class="cpc_media_cover_selector_form" style="display:none; margin-top:8px; border: 1px solid #ddd; padding: 14px; border-radius: 4px;">';
        $html .= '<h5>'.esc_html__('Cover waehlen', CPC2_TEXT_DOMAIN).'</h5>';
        $html .= '<div class="cpc_media_cover_selector" data-gallery-id="'.esc_attr($gallery_id).'" style="margin-top:10px;"></div>';
        $html .= '<div style="margin-top:10px;">';
        $html .= '<button type="button" class="cpc_button cpc_media_close_cover_selector_btn">'.esc_html__('Schliessen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</div>';
        $html .= '</div>';
    }

    $html .= cpc_media_render_upload_form($gallery_id);
    $html .= do_shortcode('[cpc-gallery-items gallery_id="'.$gallery_id.'" limit="'.cpc_media_get_gallery_items_limit().'"]');
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function cpc_media_render_profile_tab_content($html, $active_tab, $user_id, $shortcode_atts) {
    if ($active_tab !== 'galleries') {
        return $html;
    }

    $user_id = (int)$user_id;
    if (!$user_id) {
        return '<p>'.esc_html__('Benutzer nicht gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    }

    $html = '';
    $html .= cpc_media_render_notice_html();
    $html .= '<div class="cpc_media_profile_tab">';
    $html .= '<h3>'.esc_html__('Profil-Galerien', CPC2_TEXT_DOMAIN).'</h3>';
    $html .= cpc_media_render_create_gallery_form('members', $user_id, 'photo');

    $galleries = cpc_media_get_galleries(array(
        'author' => $user_id,
        'component' => 'members',
        'component_id' => $user_id,
        'posts_per_page' => 50,
    ));

    if (!$galleries) {
        $html .= '<p>'.esc_html__('Noch keine Galerien vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
    } else {
        foreach ($galleries as $gallery) {
            $html .= cpc_media_render_gallery_block($gallery);
        }
    }

    $html .= '</div>';

    return $html;
}
add_filter('cpc_profile_tab_content', 'cpc_media_render_profile_tab_content', 20, 4);

function cpc_media_render_group_tab_content($html, $group_id, $shortcode_atts) {
    $group_id = (int)$group_id;
    if (!$group_id) {
        return '<p>'.esc_html__('Gruppe nicht gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    }

    if (!cpc_can_view_group(get_current_user_id(), $group_id)) {
        return '<p>'.esc_html__('Keine Berechtigung.', CPC2_TEXT_DOMAIN).'</p>';
    }

    $html = '';
    $html .= cpc_media_render_notice_html();
    $html .= '<div class="cpc_media_group_tab">';
    $html .= '<h3>'.esc_html__('Gruppen-Galerien', CPC2_TEXT_DOMAIN).'</h3>';
    $html .= cpc_media_render_create_gallery_form('groups', $group_id, 'photo');

    $galleries = cpc_media_get_galleries(array(
        'component' => 'groups',
        'component_id' => $group_id,
        'posts_per_page' => 50,
    ));

    if (!$galleries) {
        $html .= '<p>'.esc_html__('Noch keine Galerien vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
    } else {
        foreach ($galleries as $gallery) {
            $html .= cpc_media_render_gallery_block($gallery);
        }
    }

    $html .= '</div>';

    return $html;
}
add_filter('cpc_group_tab_content_gallery', 'cpc_media_render_group_tab_content', 20, 3);