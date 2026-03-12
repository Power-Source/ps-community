<?php

function cpc_docs_add_profile_tab($tabs, $user_id, $viewer_id) {
    if (!cpc_docs_is_enabled()) {
        return $tabs;
    }

    $tabs['docs'] = array(
        'label' => cpc_docs_get_user_tab_name(),
        'icon' => 'media-document',
        'priority' => 24,
    );

    return $tabs;
}
add_filter('cpc_profile_tabs', 'cpc_docs_add_profile_tab', 20, 3);

function cpc_docs_add_group_tab($tabs, $group_id, $user_id) {
    if (!cpc_docs_is_enabled()) {
        return $tabs;
    }

    if (function_exists('cpc_can_view_group') && cpc_can_view_group($user_id, $group_id)) {
        $tabs['docs'] = array(
            'label' => cpc_docs_get_group_tab_name(),
            'icon' => 'media-document',
            'priority' => 23,
        );
    }

    return $tabs;
}
add_filter('cpc_group_tabs', 'cpc_docs_add_group_tab', 20, 3);

function cpc_docs_render_notice_html() {
    if (empty($_GET['cpc_docs_notice'])) {
        return '';
    }

    $notice = sanitize_key(wp_unslash($_GET['cpc_docs_notice']));
    $message = cpc_docs_notice_message($notice);
    if (!$message) {
        return '';
    }

    $class = 'cpc_docs_notice';
    $class .= in_array($notice, array('failed', 'invalid', 'denied'), true) ? ' cpc_docs_notice_error' : ' cpc_docs_notice_success';

    return '<div class="'.esc_attr($class).'">'.esc_html($message).'</div>';
}

function cpc_docs_render_create_form($component, $component_id) {
    $attachments_enabled = cpc_docs_enable_attachments();
    if (!cpc_docs_user_can_create_for_context($component, $component_id)) {
        return '';
    }

    $options = cpc_docs_get_status_options($component);
    $permission_options = cpc_docs_get_permission_options($component);
    $permission_defaults = cpc_docs_get_permission_defaults($component);

    $html = '';
    $parent_options = cpc_docs_get_parent_options($component, $component_id, 0);

    $add_label = __('Dokument hinzufuegen', CPC2_TEXT_DOMAIN);

    $html .= '<details class="cpc_docs_create_toggle">';
    $html .= '<summary class="cpc_button cpc_docs_create_summary">'.esc_html($add_label).'</summary>';
    $html .= '<form method="post" enctype="multipart/form-data" class="cpc_docs_create_form" style="margin-bottom:16px;">';
    $html .= '<input type="hidden" name="cpc_docs_action" value="create_doc" />';
    $html .= '<input type="hidden" name="cpc_docs_component" value="'.esc_attr($component).'" />';
    $html .= '<input type="hidden" name="cpc_docs_component_id" value="'.(int)$component_id.'" />';
    $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
    $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url(cpc_curPageURL()).'" />';

    $editor_id = 'cpc_docs_create_content_'.sanitize_key((string)$component).'_'.(int)$component_id;
    $editor_html = '';
    if (function_exists('wp_editor')) {
        ob_start();
        wp_editor('', $editor_id, array(
            'textarea_name' => 'cpc_docs_content',
            'media_buttons' => false,
            'dfw' => false,
            'textarea_rows' => 14,
            'editor_height' => 420,
            'editor_class' => 'cpc_docs_content_field cpc_docs_create_editor',
        ));
        $editor_html = ob_get_clean();
    } else {
        $editor_html = '<textarea name="cpc_docs_content" class="cpc_docs_content_field cpc_docs_create_editor" rows="12" placeholder="'.esc_attr__('Dokumentinhalt', CPC2_TEXT_DOMAIN).'" required></textarea>';
    }

    $html .= '<div style="display:grid; gap:8px;">';
    $html .= '<input type="text" name="cpc_docs_title" placeholder="'.esc_attr__('Titel', CPC2_TEXT_DOMAIN).'" required />';
    $html .= '<div class="cpc_docs_create_editor_wrap">'.$editor_html.'</div>';
    $html .= '<div style="display:grid; gap:8px; grid-template-columns:minmax(0,1fr) minmax(0,1fr);">';
    $html .= '<div>';
    $html .= '<label>'.esc_html__('Parent', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select name="cpc_docs_parent_id">';
    $html .= '<option value="0">'.esc_html__('Kein Parent', CPC2_TEXT_DOMAIN).'</option>';
    foreach ($parent_options as $parent_doc) {
        $html .= '<option value="'.(int)$parent_doc->ID.'">'.esc_html($parent_doc->post_title).'</option>';
    }
    $html .= '</select>';
    $html .= '</div>';
    if ($attachments_enabled) {
        $html .= '<div>';
        $html .= '<label>'.esc_html__('Attachments', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<input type="file" name="cpc_docs_attachments[]" multiple />';
        $html .= '</div>';
    }
    $html .= '</div>';
    $html .= '<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">';
    $html .= '<label>'.esc_html__('Sichtbarkeit', CPC2_TEXT_DOMAIN).':</label>';
    $html .= '<select name="cpc_docs_status">';
    foreach ($options as $value => $label) {
        $html .= '<option value="'.esc_attr($value).'">'.esc_html($label).'</option>';
    }
    $html .= '</select>';
    $html .= '</div>';

    $html .= '<div style="display:grid; gap:8px; grid-template-columns:repeat(2,minmax(0,1fr));">';
    $perm_fields = array(
        'edit' => __('Bearbeiten', CPC2_TEXT_DOMAIN),
        'manage' => __('Verwalten', CPC2_TEXT_DOMAIN),
        'read_comments' => __('Kommentare lesen', CPC2_TEXT_DOMAIN),
        'post_comments' => __('Kommentare schreiben', CPC2_TEXT_DOMAIN),
        'view_history' => __('Verlauf sehen', CPC2_TEXT_DOMAIN),
    );
    foreach ($perm_fields as $field => $label) {
        $html .= '<div>';
        $html .= '<label>'.esc_html($label).'</label>';
        $html .= '<select name="cpc_docs_perm_'.esc_attr($field).'">';
        foreach ($permission_options as $value => $option_label) {
            $html .= '<option value="'.esc_attr($value).'"'.selected($permission_defaults[$field], $value, false).'>'.esc_html($option_label).'</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
    }
    $html .= '</div>';

    $html .= '<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">';
    $html .= '<button type="submit" class="cpc_button">'.esc_html__('Dokument erstellen', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</form>';
    $html .= '</details>';

    return $html;
}

function cpc_docs_get_folder_options($component, $component_id, $exclude_doc_id = 0) {
    $docs = cpc_docs_get_parent_options($component, $component_id, $exclude_doc_id);
    if (empty($docs)) {
        return array();
    }

    $folders = array();
    foreach ($docs as $doc) {
        if ($doc && cpc_docs_is_folder($doc->ID)) {
            $folders[] = $doc;
        }
    }

    return $folders;
}

function cpc_docs_render_folder_manage_panel($component, $component_id) {
    if (!cpc_docs_user_can_create_for_context($component, $component_id)) {
        return '';
    }

    $status_options = cpc_docs_get_status_options($component);
    $folder_options = cpc_docs_get_folder_options($component, $component_id, 0);

    $html = '';
    $html .= '<details class="cpc_docs_folder_manage_toggle" id="cpc_docs_folder_manage_panel">';
    $html .= '<summary class="cpc_button cpc_docs_create_summary">'.esc_html__('Ordner verwalten', CPC2_TEXT_DOMAIN).'</summary>';
    $html .= '<div class="cpc_docs_folder_manage_forms">';

    $html .= '<form method="post" class="cpc_docs_folder_manage_form">';
    $html .= '<h4>'.esc_html__('Ordner erstellen', CPC2_TEXT_DOMAIN).'</h4>';
    $html .= '<input type="hidden" name="cpc_docs_action" value="folder_create" />';
    $html .= '<input type="hidden" name="cpc_docs_component" value="'.esc_attr($component).'" />';
    $html .= '<input type="hidden" name="cpc_docs_component_id" value="'.(int)$component_id.'" />';
    $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
    $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url(cpc_curPageURL()).'" />';
    $html .= '<label>'.esc_html__('Ordnername', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<input type="text" name="cpc_docs_folder_title" required />';
    $html .= '<label>'.esc_html__('Parent-Ordner', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select name="cpc_docs_parent_id">';
    $html .= '<option value="0">'.esc_html__('Kein Parent', CPC2_TEXT_DOMAIN).'</option>';
    foreach ($folder_options as $folder) {
        $html .= '<option value="'.(int)$folder->ID.'">'.esc_html($folder->post_title).'</option>';
    }
    $html .= '</select>';
    $html .= '<label>'.esc_html__('Sichtbarkeit', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select name="cpc_docs_status">';
    foreach ($status_options as $value => $label) {
        $html .= '<option value="'.esc_attr($value).'">'.esc_html($label).'</option>';
    }
    $html .= '</select>';
    $html .= '<button type="submit" class="cpc_button">'.esc_html__('Ordner erstellen', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '</form>';

    $html .= '<form method="post" class="cpc_docs_folder_manage_form cpc_docs_folder_rename_form">';
    $html .= '<h4>'.esc_html__('Ordner umbenennen', CPC2_TEXT_DOMAIN).'</h4>';
    $html .= '<input type="hidden" name="cpc_docs_action" value="folder_rename" />';
    $html .= '<input type="hidden" name="cpc_docs_component" value="'.esc_attr($component).'" />';
    $html .= '<input type="hidden" name="cpc_docs_component_id" value="'.(int)$component_id.'" />';
    $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
    $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url(cpc_curPageURL()).'" />';
    $html .= '<label>'.esc_html__('Ordner', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select name="cpc_docs_folder_id" class="cpc_docs_folder_rename_select" required>';
    $html .= '<option value="">'.esc_html__('Bitte auswaehlen', CPC2_TEXT_DOMAIN).'</option>';
    foreach ($folder_options as $folder) {
        $html .= '<option value="'.(int)$folder->ID.'">'.esc_html($folder->post_title).'</option>';
    }
    $html .= '</select>';
    $html .= '<label>'.esc_html__('Neuer Name', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<input type="text" name="cpc_docs_folder_new_title" class="cpc_docs_folder_rename_title" required />';
    $html .= '<button type="submit" class="cpc_button">'.esc_html__('Ordner umbenennen', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '</form>';

    $html .= '</div>';
    $html .= '</details>';

    return $html;
}

function cpc_docs_render_doc_card($doc) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return '';
    }

    if (!cpc_docs_user_can_view_doc($doc->ID)) {
        return '';
    }

    $component = cpc_docs_get_component($doc->ID);
    $status = cpc_docs_get_status($doc->ID);
    $author = get_the_author_meta('display_name', $doc->post_author);
    $can_manage = cpc_docs_user_can_manage_doc($doc->ID);
    $can_edit = cpc_docs_user_can_edit_doc($doc->ID);

    $status_options = cpc_docs_get_status_options($component);
    $status_label = isset($status_options[$status]) ? $status_options[$status] : ucfirst($status);

    $html = '';
    $html .= '<article class="cpc_docs_card" style="padding:14px; border:1px solid #ddd; border-radius:6px; margin-bottom:12px; background:#fff;">';
    $html .= '<h4 style="margin:0 0 8px;"><a href="'.esc_url(get_permalink($doc)).'">'.esc_html($doc->post_title).'</a></h4>';
    $html .= '<div style="font-size:12px; color:#666; margin-bottom:8px;">';
    $html .= esc_html($author).' / '.esc_html($status_label).' / '.esc_html(get_the_modified_date('', $doc));
    $html .= '</div>';

    if ($doc->post_excerpt) {
        $html .= '<div>'.wp_kses_post(wpautop(wp_trim_words($doc->post_excerpt, cpc_docs_get_directory_excerpt_length()))).'</div>';
    } else {
        $html .= '<div>'.wp_kses_post(wpautop(wp_trim_words(wp_strip_all_tags($doc->post_content), cpc_docs_get_directory_excerpt_length()))).'</div>';
    }

    if ($can_manage || $can_edit) {
        $html .= '<div class="row-actions" style="margin:6px 0 8px;">';
        if ($can_edit) {
            $html .= '<a href="'.esc_url(cpc_docs_get_edit_link($doc->ID)).'">'.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
        }
        $html .= '</div>';

        if ($can_manage) {
        $html .= '<form method="post" style="margin-top:8px;">';
        $html .= '<input type="hidden" name="cpc_docs_action" value="delete_doc" />';
        $html .= '<input type="hidden" name="cpc_docs_doc_id" value="'.(int)$doc->ID.'" />';
        $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
        $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url(cpc_curPageURL()).'" />';
        $html .= '<button type="submit" class="cpc_button" onclick="return confirm(\''.esc_js(__('Dokument wirklich loeschen?', CPC2_TEXT_DOMAIN)).'\');">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</form>';
        }
    }

    $html .= '</article>';

    return $html;
}

function cpc_docs_filter_visible_docs($docs) {
    if (empty($docs) || !is_array($docs)) {
        return array();
    }

    $visible = array();
    foreach ($docs as $doc) {
        if ($doc && $doc->post_type === 'cpc_doc' && cpc_docs_user_can_view_doc($doc->ID)) {
            $visible[] = $doc;
        }
    }

    return $visible;
}

function cpc_docs_group_docs_by_parent($docs) {
    $grouped = array();
    foreach ($docs as $doc) {
        $parent_id = (int)$doc->post_parent;
        if (!isset($grouped[$parent_id])) {
            $grouped[$parent_id] = array();
        }
        $grouped[$parent_id][] = $doc;
    }

    return $grouped;
}

function cpc_docs_get_loop_folder_id($docs) {
    $requested_folder_id = isset($_GET['cpc_docs_folder']) ? (int)$_GET['cpc_docs_folder'] : 0;
    if ($requested_folder_id <= 0) {
        return 0;
    }

    foreach ($docs as $doc) {
        if ((int)$doc->ID === $requested_folder_id) {
            return $requested_folder_id;
        }
    }

    return 0;
}

function cpc_docs_build_loop_folder_url($folder_id, $args = array()) {
    $folder_id = (int)$folder_id;
    $defaults = array(
        'mode' => 'current',
    );
    $args = wp_parse_args($args, $defaults);

    if ($args['mode'] === 'directory') {
        return cpc_docs_directory_build_url(array(
            'cpc_docs_folder' => $folder_id > 0 ? $folder_id : '',
            'cpc_docs_page' => 1,
        ));
    }

    $url = remove_query_arg(array('cpc_docs_notice', 'cpc_docs_doc_id'), cpc_docs_get_current_request_url());
    if ($folder_id > 0) {
        return add_query_arg('cpc_docs_folder', $folder_id, $url);
    }

    return remove_query_arg('cpc_docs_folder', $url);
}

function cpc_docs_get_inline_base_url($args = array()) {
    $base = !empty($args['inline_base_url']) ? (string)$args['inline_base_url'] : cpc_docs_get_current_request_url();
    return remove_query_arg(array('cpc_docs_doc_id', 'cpc_docs_action', 'cpc_docs_compare_from', 'cpc_docs_compare_to'), $base);
}

function cpc_docs_build_inline_doc_url($doc_id, $action = '', $args = array()) {
    $doc_id = (int)$doc_id;
    if ($doc_id <= 0) {
        return '';
    }

    $url = cpc_docs_get_inline_base_url($args);
    $url = add_query_arg('cpc_docs_doc_id', $doc_id, $url);
    if ($action !== '') {
        $url = add_query_arg('cpc_docs_action', sanitize_key($action), $url);
    } else {
        $url = remove_query_arg('cpc_docs_action', $url);
    }

    return remove_query_arg(array('cpc_docs_compare_from', 'cpc_docs_compare_to'), $url);
}

function cpc_docs_get_requested_doc_for_context($component, $component_id) {
    $doc_id = isset($_GET['cpc_docs_doc_id']) ? (int)$_GET['cpc_docs_doc_id'] : 0;
    if ($doc_id <= 0) {
        return null;
    }

    $doc = get_post($doc_id);
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return null;
    }

    if (cpc_docs_get_component($doc->ID) !== $component || cpc_docs_get_component_id($doc->ID) !== (int)$component_id) {
        return null;
    }

    if (!cpc_docs_user_can_view_doc($doc->ID)) {
        return null;
    }

    return $doc;
}

function cpc_docs_render_loop_breadcrumbs($folder_id, $args = array()) {
    $folder_id = (int)$folder_id;
    if ($folder_id <= 0) {
        return '';
    }

    $links = array();
    $links[] = '<a href="'.esc_url(cpc_docs_build_loop_folder_url(0, $args)).'">'.esc_html__('Alle Dokumente', CPC2_TEXT_DOMAIN).'</a>';

    foreach (cpc_docs_get_ancestor_ids($folder_id) as $ancestor_id) {
        $ancestor = get_post($ancestor_id);
        if (!$ancestor || $ancestor->post_type !== 'cpc_doc' || !cpc_docs_user_can_view_doc($ancestor_id)) {
            continue;
        }
        $links[] = '<a href="'.esc_url(cpc_docs_build_loop_folder_url($ancestor_id, $args)).'">'.esc_html($ancestor->post_title).'</a>';
    }

    $folder = get_post($folder_id);
    if ($folder && $folder->post_type === 'cpc_doc') {
        $links[] = '<span>'.esc_html($folder->post_title).'</span>';
    }

    return '<div class="cpc_docs_loop_breadcrumbs">'.implode('<span class="sep">/</span>', $links).'</div>';
}

function cpc_docs_render_folder_row($doc, $child_count, $args = array()) {
    $can_manage = cpc_docs_user_can_manage_doc($doc->ID);
    $can_edit = cpc_docs_user_can_edit_doc($doc->ID);
    $can_history = cpc_docs_user_can_view_history($doc->ID);
    $has_attachments = cpc_docs_enable_attachments() && !empty(cpc_docs_get_doc_attachments($doc->ID));

    $view_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, '', $args) : get_permalink($doc);
    $edit_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, 'edit', $args) : cpc_docs_get_edit_link($doc->ID);
    $history_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, 'history', $args) : cpc_docs_get_history_link($doc->ID);

    $html = '<tr class="folder-row cpc_docs_folder_row" data-folder-id="'.(int)$doc->ID.'">';
    $html .= '<td class="attachment-clip-cell">'.($has_attachments ? '<span class="dashicons dashicons-paperclip"></span>' : '').'</td>';
    $html .= '<td class="title-cell folder-row-name">';
    $html .= '<span class="cpc_docs_folder_title"><span class="dashicons dashicons-category"></span><a href="'.esc_url($view_url).'">'.esc_html($doc->post_title).'</a></span>';
    $html .= '<div class="cpc_docs_folder_meta">'.sprintf(esc_html__('%d Unterdokumente', CPC2_TEXT_DOMAIN), (int)$child_count).'</div>';
    $html .= cpc_docs_render_access_badges($doc->ID);
    $html .= '<div class="row-actions">';
    $html .= '<span class="toggle-folder-link"><a href="'.esc_url(cpc_docs_build_loop_folder_url($doc->ID, $args)).'">'.esc_html__('Ordner oeffnen', CPC2_TEXT_DOMAIN).'</a></span>';
    $html .= ' | <a href="'.esc_url($view_url).'">'.esc_html__('Ansehen', CPC2_TEXT_DOMAIN).'</a>';
    if ($can_manage || $can_edit || $can_history) {
        $current_url = cpc_curPageURL();
        if ($can_edit) {
            $html .= ' | <a href="'.esc_url($edit_url).'">'.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
        }
        if ($can_history) {
            $html .= ' | <a href="'.esc_url($history_url).'">'.esc_html__('Verlauf', CPC2_TEXT_DOMAIN).'</a>';
        }
    }
    if ($can_manage) {
        $html .= ' | <a href="#cpc_docs_folder_manage_panel" class="cpc_docs_rename_folder_link" data-folder-id="'.(int)$doc->ID.'" data-folder-title="'.esc_attr($doc->post_title).'">'.esc_html__('Umbenennen', CPC2_TEXT_DOMAIN).'</a>';
        $html .= ' | <form method="post" class="cpc_docs_inline_form">';
        $html .= '<input type="hidden" name="cpc_docs_action" value="folder_delete" />';
        $html .= '<input type="hidden" name="cpc_docs_folder_id" value="'.(int)$doc->ID.'" />';
        $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
        $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url($current_url).'" />';
        $html .= '<button type="submit" class="cpc_docs_inline_button" onclick="return confirm(\''.esc_js(__('Ordner wirklich loeschen? Unterordner werden in den Parent verschoben.', CPC2_TEXT_DOMAIN)).'\');">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</form>';
    }
    $html .= '</div>';
    $html .= '</td>';
    $html .= '<td class="author-cell">'.esc_html(get_the_author_meta('display_name', $doc->post_author)).'</td>';
    $html .= '<td class="edited-date-cell">'.esc_html(get_the_modified_date('', $doc)).'</td>';
    $html .= '</tr>';

    return $html;
}

function cpc_docs_render_leaf_row($doc, $args = array()) {
    $can_manage = cpc_docs_user_can_manage_doc($doc->ID);
    $can_edit = cpc_docs_user_can_edit_doc($doc->ID);
    $can_history = cpc_docs_user_can_view_history($doc->ID);
    $has_attachments = cpc_docs_enable_attachments() && !empty(cpc_docs_get_doc_attachments($doc->ID));

    $view_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, '', $args) : get_permalink($doc);
    $edit_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, 'edit', $args) : cpc_docs_get_edit_link($doc->ID);
    $history_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, 'history', $args) : cpc_docs_get_history_link($doc->ID);

    $html = '<tr>';
    $html .= '<td class="attachment-clip-cell">'.($has_attachments ? '<span class="dashicons dashicons-paperclip"></span>' : '').'</td>';
    $html .= '<td class="title-cell">';
    $html .= '<span class="cpc_docs_doc_title"><span class="dashicons dashicons-media-document"></span><a href="'.esc_url($view_url).'">'.esc_html($doc->post_title).'</a></span>';
    $html .= cpc_docs_render_access_badges($doc->ID);
    $html .= '<div class="row-actions">';
    $html .= '<a href="'.esc_url($view_url).'">'.esc_html__('Ansehen', CPC2_TEXT_DOMAIN).'</a>';
    if ($can_edit) {
        $html .= ' | <a href="'.esc_url($edit_url).'">'.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
    }
    if ($can_history) {
        $html .= ' | <a href="'.esc_url($history_url).'">'.esc_html__('Verlauf', CPC2_TEXT_DOMAIN).'</a>';
    }
    $html .= '</div>';
    $html .= '</td>';
    $html .= '<td class="author-cell">'.esc_html(get_the_author_meta('display_name', $doc->post_author)).'</td>';
    $html .= '<td class="edited-date-cell">'.esc_html(get_the_modified_date('', $doc)).'</td>';
    $html .= '</tr>';

    return $html;
}

function cpc_docs_render_docs_table($docs, $empty_message = '', $args = array()) {
    $docs = cpc_docs_filter_visible_docs($docs);
    if (empty($docs)) {
        return $empty_message !== '' ? '<p>'.esc_html($empty_message).'</p>' : '';
    }

    $args = wp_parse_args($args, array(
        'mode' => 'current',
    ));
    $attachments_enabled = cpc_docs_enable_attachments();
    $grouped = cpc_docs_group_docs_by_parent($docs);
    $current_folder_id = cpc_docs_get_loop_folder_id($docs);
    $current_items = isset($grouped[$current_folder_id]) ? $grouped[$current_folder_id] : array();

    $html = '';
    $html .= cpc_docs_render_loop_breadcrumbs($current_folder_id, $args);
    $table_classes = 'doctable cpc_docs_directory_table cpc_docs_folder_browser';
    if (!$attachments_enabled) {
        $table_classes .= ' cpc_docs_no_attachments';
    }
    $html .= '<table class="'.esc_attr($table_classes).'" data-folder-id="'.(int)$current_folder_id.'">';
    $html .= '<thead><tr>';
    $html .= '<th scope="col" class="attachment-clip-cell">&nbsp;</th>';
    $html .= '<th scope="col" class="title-cell">'.esc_html__('Titel', CPC2_TEXT_DOMAIN).'</th>';
    $html .= '<th scope="col" class="author-cell">'.esc_html__('Autor', CPC2_TEXT_DOMAIN).'</th>';
    $html .= '<th scope="col" class="edited-date-cell">'.esc_html__('Aktualisiert', CPC2_TEXT_DOMAIN).'</th>';
    $html .= '</tr></thead><tbody>';

    if ($current_folder_id > 0) {
        $parent_folder_id = (int)wp_get_post_parent_id($current_folder_id);
        $html .= '<tr class="folder-row cpc_docs_up_row">';
        $html .= '<td class="attachment-clip-cell"></td>';
        $html .= '<td class="title-cell folder-row-name"><a class="up-one-folder" href="'.esc_url(cpc_docs_build_loop_folder_url($parent_folder_id, $args)).'"><span class="dashicons dashicons-category"></span>..</a></td>';
        $html .= '<td class="author-cell"></td>';
        $html .= '<td class="edited-date-cell"></td>';
        $html .= '</tr>';
    }

    $rendered = 0;
    foreach ($current_items as $doc) {
        $child_count = isset($grouped[$doc->ID]) ? count($grouped[$doc->ID]) : 0;
        if ($child_count > 0) {
            $html .= cpc_docs_render_folder_row($doc, $child_count, $args);
        } else {
            $html .= cpc_docs_render_leaf_row($doc, $args);
        }
        $rendered++;
    }

    if ($rendered === 0) {
        $html .= '<tr class="no-docs-row"><td class="attachment-clip-cell"></td><td class="title-cell">'.esc_html($empty_message !== '' ? $empty_message : __('Keine Dokumente gefunden.', CPC2_TEXT_DOMAIN)).'</td><td class="author-cell"></td><td class="edited-date-cell"></td></tr>';
    }

    $html .= '</tbody></table>';

    return $html;
}

function cpc_docs_render_single_doc_breadcrumbs($doc, $args = array()) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return '';
    }

    $links = array();
    foreach (cpc_docs_get_ancestor_ids($doc->ID) as $ancestor_id) {
        $ancestor = get_post($ancestor_id);
        if (!$ancestor || $ancestor->post_type !== 'cpc_doc' || !cpc_docs_user_can_view_doc($ancestor_id)) {
            continue;
        }
        $ancestor_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($ancestor->ID, '', $args) : get_permalink($ancestor);
        $links[] = '<a href="'.esc_url($ancestor_url).'">'.esc_html($ancestor->post_title).'</a>';
    }

    if (empty($links)) {
        return '';
    }

    $links[] = '<span>'.esc_html($doc->post_title).'</span>';
    return '<nav class="cpc_doc_breadcrumbs" aria-label="'.esc_attr__('Dokument-Hierarchie', CPC2_TEXT_DOMAIN).'">'.implode('<span class="sep">/</span>', $links).'</nav>';
}

function cpc_docs_render_single_doc_relations($doc, $args = array()) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return '';
    }

    $parent = $doc->post_parent ? get_post((int)$doc->post_parent) : null;
    $children = array_filter(cpc_docs_get_child_docs($doc->ID), function($child_doc) {
        return cpc_docs_user_can_view_doc($child_doc->ID);
    });
    $prev_sibling = cpc_docs_get_prev_sibling($doc->ID);
    $next_sibling = cpc_docs_get_next_sibling($doc->ID);

    if ((!$parent || !cpc_docs_user_can_view_doc($parent->ID)) && empty($children) && !$prev_sibling && !$next_sibling) {
        return '';
    }

    $html = '<aside class="cpc_doc_relations">';
    
    if ($parent && cpc_docs_user_can_view_doc($parent->ID)) {
        $html .= '<div class="cpc_doc_relation_block">';
        $html .= '<h3>'.esc_html__('Parent-Dokument', CPC2_TEXT_DOMAIN).'</h3>';
        $parent_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($parent->ID, '', $args) : get_permalink($parent);
        $html .= '<p><a href="'.esc_url($parent_url).'">'.esc_html($parent->post_title).'</a></p>';
        $html .= '</div>';
    }

    if (!empty($children)) {
        $html .= '<div class="cpc_doc_relation_block">';
        $html .= '<h3>'.esc_html__('Unterdokumente', CPC2_TEXT_DOMAIN).'</h3>';
        $html .= '<ul class="cpc_doc_children_list">';
        foreach ($children as $child) {
            $child_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($child->ID, '', $args) : get_permalink($child);
            $html .= '<li><a href="'.esc_url($child_url).'">'.esc_html($child->post_title).'</a></li>';
        }
        $html .= '</ul>';
        $html .= '</div>';
    }

    if ($prev_sibling || $next_sibling) {
        $html .= '<div class="cpc_doc_relation_block cpc_doc_siblings">';
        $html .= '<h3>'.esc_html__('Geschwister-Navigation', CPC2_TEXT_DOMAIN).'</h3>';
        $html .= '<div class="cpc_doc_siblings_nav">';
        
        if ($prev_sibling) {
            $prev_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($prev_sibling->ID, '', $args) : get_permalink($prev_sibling);
            $html .= '<a class="prev-sibling" href="'.esc_url($prev_url).'">← '.esc_html($prev_sibling->post_title).'</a>';
        }
        
        if ($next_sibling) {
            $next_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($next_sibling->ID, '', $args) : get_permalink($next_sibling);
            $html .= '<a class="next-sibling" href="'.esc_url($next_url).'">'.esc_html($next_sibling->post_title).' →</a>';
        }
        
        $html .= '</div>';
        $html .= '</div>';
    }

    $html .= '</aside>';

    return $html;
}

function cpc_docs_render_profile_tab_content($html, $active_tab, $user_id, $shortcode_atts) {
    if ($active_tab !== 'docs') {
        return $html;
    }

    $user_id = (int)$user_id;
    if (!$user_id) {
        return '<p>'.esc_html__('Benutzer nicht gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    }

    $docs = cpc_docs_get_docs(array(
        'component' => 'members',
        'component_id' => $user_id,
        'posts_per_page' => 250,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
    ));

    $inline_args = array(
        'inline' => true,
        'inline_base_url' => cpc_docs_get_inline_base_url(),
    );
    $selected_doc = cpc_docs_get_requested_doc_for_context('members', $user_id);

    $html = '';
    $html .= cpc_docs_render_notice_html();
    $html .= '<div class="cpc_docs_profile_tab">';
    $html .= '<h3>'.esc_html__('Profil-Dokumente', CPC2_TEXT_DOMAIN).'</h3>';

    if ($selected_doc) {
        $html .= '<p class="cpc_docs_inline_back"><a href="'.esc_url(cpc_docs_get_inline_base_url($inline_args)).'">'.esc_html__('Zur Dokumentenliste', CPC2_TEXT_DOMAIN).'</a></p>';
        $html .= cpc_docs_render_doc_panel($selected_doc, $inline_args);
    } else {
        $html .= cpc_docs_render_create_form('members', $user_id);
        $html .= cpc_docs_render_folder_manage_panel('members', $user_id);
    }

    if (!$selected_doc) {
        if (!$docs) {
            $html .= '<p>'.esc_html__('Noch keine Dokumente vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
        } else {
            $html .= cpc_docs_render_docs_table($docs, __('Keine Dokumente sichtbar.', CPC2_TEXT_DOMAIN), $inline_args);
        }
    }

    $html .= '</div>';

    return $html;
}
add_filter('cpc_profile_tab_content', 'cpc_docs_render_profile_tab_content', 20, 4);

function cpc_docs_render_group_tab_content($html, $group_id, $shortcode_atts) {
    $group_id = (int)$group_id;
    if (!$group_id) {
        return '<p>'.esc_html__('Gruppe nicht gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    }

    if (function_exists('cpc_can_view_group') && !cpc_can_view_group(get_current_user_id(), $group_id)) {
        return '<p>'.esc_html__('Keine Berechtigung.', CPC2_TEXT_DOMAIN).'</p>';
    }

    $docs = cpc_docs_get_docs(array(
        'component' => 'groups',
        'component_id' => $group_id,
        'posts_per_page' => 250,
        'orderby' => 'menu_order title',
        'order' => 'ASC',
    ));

    $inline_args = array(
        'inline' => true,
        'inline_base_url' => cpc_docs_get_inline_base_url(),
    );
    $selected_doc = cpc_docs_get_requested_doc_for_context('groups', $group_id);

    $html = '';
    $html .= cpc_docs_render_notice_html();
    $html .= '<div class="cpc_docs_group_tab">';
    $html .= '<h3>'.esc_html__('Gruppen-Dokumente', CPC2_TEXT_DOMAIN).'</h3>';

    if ($selected_doc) {
        $html .= '<p class="cpc_docs_inline_back"><a href="'.esc_url(cpc_docs_get_inline_base_url($inline_args)).'">'.esc_html__('Zur Dokumentenliste', CPC2_TEXT_DOMAIN).'</a></p>';
        $html .= cpc_docs_render_doc_panel($selected_doc, $inline_args);
    } else {
        $html .= cpc_docs_render_create_form('groups', $group_id);
        $html .= cpc_docs_render_folder_manage_panel('groups', $group_id);
    }

    if (!$selected_doc) {
        if (!$docs) {
            $html .= '<p>'.esc_html__('Noch keine Dokumente vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
        } else {
            $html .= cpc_docs_render_docs_table($docs, __('Keine Dokumente sichtbar.', CPC2_TEXT_DOMAIN), $inline_args);
        }
    }

    $html .= '</div>';

    return $html;
}
add_filter('cpc_group_tab_content_docs', 'cpc_docs_render_group_tab_content', 20, 3);

function cpc_docs_render_single_doc_content($content) {
    if (!cpc_docs_is_enabled()) {
        return $content;
    }

    if (!is_singular('cpc_doc') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $doc = get_post();
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return $content;
    }

    if (!cpc_docs_user_can_view_doc($doc->ID)) {
        return '<p>'.esc_html__('Keine Berechtigung.', CPC2_TEXT_DOMAIN).'</p>';
    }

    return cpc_docs_render_notice_html().cpc_docs_render_doc_panel($doc);
}
add_filter('the_content', 'cpc_docs_render_single_doc_content', 20);

function cpc_docs_render_doc_panel($doc, $args = array()) {
    if (!$doc || $doc->post_type !== 'cpc_doc' || !cpc_docs_user_can_view_doc($doc->ID)) {
        return '<p>'.esc_html__('Keine Berechtigung.', CPC2_TEXT_DOMAIN).'</p>';
    }

    $action = isset($_GET['cpc_docs_action']) ? sanitize_key(wp_unslash($_GET['cpc_docs_action'])) : '';
    $is_edit = ($action === 'edit');
    $is_history = ($action === 'history');

    $body = '';
    $body .= '<article class="cpc_doc_single bp-docs-container">';
    $body .= cpc_docs_render_single_doc_breadcrumbs($doc, $args);
    $body .= '<header id="bp-docs-single-doc-header" class="cpc_doc_header">';
    $body .= '<h2 class="doc-title">'.esc_html($doc->post_title).'</h2>';
    $body .= cpc_docs_render_single_doc_actions($doc, $args);
    $body .= '</header>';

    if ($is_history && cpc_docs_user_can_view_history($doc->ID)) {
        $body .= cpc_docs_render_history_view($doc, $args);
    } elseif ($is_history) {
        $body .= '<p>'.esc_html__('Keine Berechtigung fuer den Verlauf.', CPC2_TEXT_DOMAIN).'</p>';
    } elseif ($is_edit && cpc_docs_user_can_edit_doc($doc->ID)) {
        if (cpc_docs_is_doc_locked($doc->ID, get_current_user_id())) {
            $body .= cpc_docs_render_locked_notice($doc);
        } else {
            cpc_docs_acquire_edit_lock($doc->ID, get_current_user_id());
            $body .= cpc_docs_render_edit_form($doc, $args);
        }
    } else {
        $body .= '<div class="doc-content cpc_doc_content">'.wp_kses_post(wpautop($doc->post_content)).'</div>';
        $body .= cpc_docs_render_single_doc_relations($doc, $args);
        $body .= cpc_docs_render_single_doc_attachments($doc, $args);
        $body .= cpc_docs_render_single_doc_meta($doc);
    }

    $body .= '</article>';

    return $body;
}

function cpc_docs_render_single_doc_actions($doc, $args = array()) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return '';
    }

    $can_manage = cpc_docs_user_can_manage_doc($doc->ID);
    $can_edit = cpc_docs_user_can_edit_doc($doc->ID);
    $can_history = cpc_docs_user_can_view_history($doc->ID);
    $action = isset($_GET['cpc_docs_action']) ? sanitize_key(wp_unslash($_GET['cpc_docs_action'])) : '';
    $is_edit = ($action === 'edit');
    $is_history = ($action === 'history');

    $view_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, '', $args) : get_permalink($doc);
    $history_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, 'history', $args) : cpc_docs_get_history_link($doc->ID);
    $edit_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, 'edit', $args) : cpc_docs_get_edit_link($doc->ID);
    $redirect_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, '', $args) : get_permalink($doc);

    $html = '<div class="doc-edit-link cpc_doc_actions">';
    $html .= '<a class="cpc_button'.(!$is_edit && !$is_history ? ' active' : '').'" href="'.esc_url($view_url).'">'.esc_html__('Ansicht', CPC2_TEXT_DOMAIN).'</a>';
    if ($can_history) {
        $html .= '<a class="cpc_button'.($is_history ? ' active' : '').'" href="'.esc_url($history_url).'">'.esc_html__('Verlauf', CPC2_TEXT_DOMAIN).'</a>';
    }

    if ($can_edit) {
        $html .= '<a class="cpc_button'.($is_edit ? ' active' : '').'" href="'.esc_url($edit_url).'">'.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
    }

    if ($can_manage) {
        $html .= '<form method="post" style="display:inline-block; margin-left:8px;">';
        $html .= '<input type="hidden" name="cpc_docs_action" value="delete_doc" />';
        $html .= '<input type="hidden" name="cpc_docs_doc_id" value="'.(int)$doc->ID.'" />';
        $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
        $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url($redirect_url).'" />';
        $html .= '<button type="submit" class="cpc_button" onclick="return confirm(\''.esc_js(__('Dokument wirklich loeschen?', CPC2_TEXT_DOMAIN)).'\');">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</form>';
    }

    $html .= '</div>';
    return $html;
}

function cpc_docs_render_single_doc_attachments($doc, $args = array()) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return '';
    }

    if (!cpc_docs_enable_attachments()) {
        return '';
    }

    $attachments = cpc_docs_get_doc_attachments($doc->ID);
    if (empty($attachments)) {
        return '';
    }

    $can_manage = cpc_docs_user_can_manage_doc($doc->ID);
    $redirect_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, '', $args) : get_permalink($doc);
    $html = '<div class="doc-attachments">';
    $html .= '<h3>'.esc_html__('Attachments', CPC2_TEXT_DOMAIN).'</h3>';
    $html .= '<ul id="doc-attachments-ul">';

    $index = 0;
    foreach ($attachments as $attachment) {
        $index++;
        $class = ($index % 2 === 0) ? 'even' : 'odd';
        $url = wp_get_attachment_url($attachment->ID);
        $title = $attachment->post_title ? $attachment->post_title : wp_basename((string)$url);

        $html .= '<li class="'.esc_attr($class).'">';
        $html .= '<a href="'.esc_url($url).'" target="_blank" rel="noopener noreferrer">'.esc_html($title).'</a>';

        if ($can_manage) {
            $html .= '<form method="post" style="display:inline-block; float:right;">';
            $html .= '<input type="hidden" name="cpc_docs_action" value="delete_attachment" />';
            $html .= '<input type="hidden" name="cpc_docs_doc_id" value="'.(int)$doc->ID.'" />';
            $html .= '<input type="hidden" name="cpc_docs_attachment_id" value="'.(int)$attachment->ID.'" />';
            $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
            $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url($redirect_url).'" />';
            $html .= '<button type="submit" class="doc-attachment-delete cpc_button" onclick="return confirm(\''.esc_js(__('Attachment wirklich loeschen?', CPC2_TEXT_DOMAIN)).'\');">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
            $html .= '</form>';
        }

        $html .= '</li>';
    }

    $html .= '</ul>';
    $html .= '</div>';

    return $html;
}

function cpc_docs_render_single_doc_meta($doc) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return '';
    }

    $component = cpc_docs_get_component($doc->ID);
    $status = cpc_docs_get_status($doc->ID);
    $status_label = cpc_docs_get_status_label($status, $component);
    $author = get_the_author_meta('display_name', $doc->post_author);

    $html = '<div class="doc-meta cpc_doc_meta">';
    $html .= '<p><strong>'.esc_html__('Autor', CPC2_TEXT_DOMAIN).':</strong> '.esc_html($author).'</p>';
    $html .= '<p><strong>'.esc_html__('Sichtbarkeit', CPC2_TEXT_DOMAIN).':</strong> '.esc_html($status_label).'</p>';
    $html .= '<p><strong>'.esc_html__('Zuletzt aktualisiert', CPC2_TEXT_DOMAIN).':</strong> '.esc_html(get_the_modified_date('', $doc)).'</p>';
    $html .= '</div>';

    return $html;
}

function cpc_docs_get_status_label($status, $component) {
    $options = cpc_docs_get_status_options($component);
    $status = sanitize_key((string)$status);
    return isset($options[$status]) ? $options[$status] : ucfirst($status);
}

function cpc_docs_get_permission_label($permission, $component) {
    $options = cpc_docs_get_permission_options($component);
    $permission = sanitize_key((string)$permission);
    return isset($options[$permission]) ? $options[$permission] : ucfirst($permission);
}

function cpc_docs_render_access_badges($doc_id) {
    $doc_id = (int)$doc_id;
    if ($doc_id <= 0) {
        return '';
    }

    $component = cpc_docs_get_component($doc_id);
    $status_label = cpc_docs_get_status_label(cpc_docs_get_status($doc_id), $component);
    $edit_label = cpc_docs_get_permission_label(cpc_docs_get_doc_permission($doc_id, 'edit', $component), $component);
    $manage_label = cpc_docs_get_permission_label(cpc_docs_get_doc_permission($doc_id, 'manage', $component), $component);
    $history_label = cpc_docs_get_permission_label(cpc_docs_get_doc_permission($doc_id, 'view_history', $component), $component);

    $html = '<div class="cpc_docs_access_badges">';
    $html .= '<span class="cpc_docs_badge cpc_docs_badge_status">'.esc_html__('Sichtbar', CPC2_TEXT_DOMAIN).': '.esc_html($status_label).'</span>';
    $html .= '<span class="cpc_docs_badge">'.esc_html__('Edit', CPC2_TEXT_DOMAIN).': '.esc_html($edit_label).'</span>';
    $html .= '<span class="cpc_docs_badge">'.esc_html__('Manage', CPC2_TEXT_DOMAIN).': '.esc_html($manage_label).'</span>';
    $html .= '<span class="cpc_docs_badge">'.esc_html__('History', CPC2_TEXT_DOMAIN).': '.esc_html($history_label).'</span>';
    $html .= '</div>';

    return $html;
}

function cpc_docs_render_history_compare_form($doc, $revisions, $from_id, $to_id, $args = array()) {
    if (!$doc || $doc->post_type !== 'cpc_doc' || empty($revisions)) {
        return '';
    }

    $base_url = !empty($args['inline']) ? cpc_docs_get_inline_base_url($args) : get_permalink($doc);
    $html = '<form method="get" action="'.esc_url($base_url).'" class="cpc_doc_history_compare_form">';
    $html .= '<input type="hidden" name="cpc_docs_action" value="history" />';
    $html .= '<input type="hidden" name="cpc_docs_doc_id" value="'.(int)$doc->ID.'" />';
    $html .= '<div class="cpc_doc_history_compare_grid">';

    $html .= '<div><label for="cpc_docs_compare_from">'.esc_html__('Von Revision', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select id="cpc_docs_compare_from" name="cpc_docs_compare_from">';
    foreach ($revisions as $revision) {
        $label = wp_strip_all_tags(wp_post_revision_title($revision->ID, false));
        $html .= '<option value="'.(int)$revision->ID.'"'.selected((int)$from_id, (int)$revision->ID, false).'>'.esc_html($label).'</option>';
    }
    $html .= '</select></div>';

    $html .= '<div><label for="cpc_docs_compare_to">'.esc_html__('Zu Revision', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select id="cpc_docs_compare_to" name="cpc_docs_compare_to">';
    foreach ($revisions as $revision) {
        $label = wp_strip_all_tags(wp_post_revision_title($revision->ID, false));
        $html .= '<option value="'.(int)$revision->ID.'"'.selected((int)$to_id, (int)$revision->ID, false).'>'.esc_html($label).'</option>';
    }
    $html .= '</select></div>';

    $html .= '</div>';
    $html .= '<button type="submit" class="cpc_button">'.esc_html__('Revisionen vergleichen', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '</form>';

    return $html;
}

function cpc_docs_render_edit_form($doc, $args = array()) {
    $attachments_enabled = cpc_docs_enable_attachments();
    if (!$doc || $doc->post_type !== 'cpc_doc' || !cpc_docs_user_can_edit_doc($doc->ID)) {
        return '';
    }

    $component = cpc_docs_get_component($doc->ID);
    $component_id = cpc_docs_get_component_id($doc->ID);
    $status = cpc_docs_get_status($doc->ID);
    $status_options = cpc_docs_get_status_options($component);
    $permission_options = cpc_docs_get_permission_options($component);
    $parent_options = cpc_docs_get_parent_options($component, $component_id, $doc->ID);
    $tags_string = cpc_docs_get_doc_tags_string($doc->ID);

    $html = '<form method="post" enctype="multipart/form-data" class="standard-form" id="doc-form">';
    $html .= '<input type="hidden" name="cpc_docs_action" value="update_doc" />';
    $html .= '<input type="hidden" name="cpc_docs_doc_id" value="'.(int)$doc->ID.'" />';
    $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
    $redirect_url = !empty($args['inline']) ? cpc_docs_build_inline_doc_url($doc->ID, '', $args) : get_permalink($doc);
    $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url($redirect_url).'" />';

    $html .= '<div class="doc-content">';
    $html .= '<p><label for="cpc_docs_title">'.esc_html__('Title', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<input type="text" id="cpc_docs_title" name="cpc_docs_title" value="'.esc_attr($doc->post_title).'" required /></p>';

    $html .= '<p><label for="cpc_docs_content">'.esc_html__('Content', CPC2_TEXT_DOMAIN).'</label></p>';
    if (function_exists('wp_editor')) {
        ob_start();
        wp_editor($doc->post_content, 'cpc_docs_content', array(
            'textarea_name' => 'cpc_docs_content',
            'media_buttons' => false,
            'dfw' => false,
            'textarea_rows' => 12,
            'editor_height' => 380,
            'editor_class' => 'cpc_docs_content_field',
        ));
        $html .= ob_get_clean();
    } else {
        $html .= '<textarea id="cpc_docs_content" class="cpc_docs_content_field" name="cpc_docs_content" rows="12">'.esc_textarea($doc->post_content).'</textarea>';
    }

    $html .= '<div id="doc-meta">';

    $html .= '<details class="doc-meta-box toggleable" open>';
    $html .= '<summary class="toggle-switch">'.esc_html__('Access', CPC2_TEXT_DOMAIN).'</summary>';
    $html .= '<div class="toggle-content"><table class="toggle-table"><tr><td class="desc-column">';
    $html .= '<label for="cpc_docs_status">'.esc_html__('Sichtbarkeit', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<span class="description">'.esc_html__('Wer darf das Dokument sehen.', CPC2_TEXT_DOMAIN).'</span>';
    $html .= '</td><td class="content-column">';
    $html .= '<select id="cpc_docs_status" name="cpc_docs_status">';
    foreach ($status_options as $value => $label) {
        $html .= '<option value="'.esc_attr($value).'"'.selected($status, $value, false).'>'.esc_html($label).'</option>';
    }
    $html .= '</select>';
    $html .= '</td></tr></table></div>';
    $html .= '</details>';

    $perm_values = array(
        'edit' => cpc_docs_get_doc_permission($doc->ID, 'edit', $component),
        'manage' => cpc_docs_get_doc_permission($doc->ID, 'manage', $component),
        'read_comments' => cpc_docs_get_doc_permission($doc->ID, 'read_comments', $component),
        'post_comments' => cpc_docs_get_doc_permission($doc->ID, 'post_comments', $component),
        'view_history' => cpc_docs_get_doc_permission($doc->ID, 'view_history', $component),
    );

    $perm_labels = array(
        'edit' => __('Bearbeiten', CPC2_TEXT_DOMAIN),
        'manage' => __('Verwalten', CPC2_TEXT_DOMAIN),
        'read_comments' => __('Kommentare lesen', CPC2_TEXT_DOMAIN),
        'post_comments' => __('Kommentare schreiben', CPC2_TEXT_DOMAIN),
        'view_history' => __('Verlauf sehen', CPC2_TEXT_DOMAIN),
    );

    $html .= '<details class="doc-meta-box toggleable">';
    $html .= '<summary class="toggle-switch">'.esc_html__('Berechtigungen', CPC2_TEXT_DOMAIN).'</summary>';
    $html .= '<div class="toggle-content"><table class="toggle-table">';
    foreach ($perm_labels as $field => $label) {
        $html .= '<tr><td class="desc-column">';
        $html .= '<label for="cpc_docs_perm_'.esc_attr($field).'">'.esc_html($label).'</label>';
        $html .= '</td><td class="content-column">';
        $html .= '<select id="cpc_docs_perm_'.esc_attr($field).'" name="cpc_docs_perm_'.esc_attr($field).'">';
        foreach ($permission_options as $value => $option_label) {
            $html .= '<option value="'.esc_attr($value).'"'.selected($perm_values[$field], $value, false).'>'.esc_html($option_label).'</option>';
        }
        $html .= '</select>';
        $html .= '</td></tr>';
    }
    $html .= '</table></div>';
    $html .= '</details>';

    $html .= '<details class="doc-meta-box toggleable">';
    $html .= '<summary class="toggle-switch">'.esc_html__('Tags', CPC2_TEXT_DOMAIN).'</summary>';
    $html .= '<div class="toggle-content"><table class="toggle-table"><tr><td class="desc-column">';
    $html .= '<label for="cpc_docs_tags">'.esc_html__('Tags', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<span class="description">'.esc_html__('Mit Komma trennen, z.B. wiki, leitfaden, projekt', CPC2_TEXT_DOMAIN).'</span>';
    $html .= '</td><td class="content-column">';
    $html .= '<input type="text" id="cpc_docs_tags" name="cpc_docs_tags" value="'.esc_attr($tags_string).'" />';
    $html .= '</td></tr></table></div>';
    $html .= '</details>';

    $html .= '<details class="doc-meta-box toggleable">';
    $html .= '<summary class="toggle-switch">'.esc_html__('Parent', CPC2_TEXT_DOMAIN).'</summary>';
    $html .= '<div class="toggle-content"><table class="toggle-table"><tr><td class="desc-column">';
    $html .= '<label for="cpc_docs_parent_id">'.esc_html__('Parent-Dokument', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<span class="description">'.esc_html__('Optionales Parent fuer Hierarchie/Breadcrumb.', CPC2_TEXT_DOMAIN).'</span>';
    $html .= '</td><td class="content-column">';
    $html .= '<select id="cpc_docs_parent_id" name="cpc_docs_parent_id">';
    $html .= '<option value="0">'.esc_html__('Kein Parent', CPC2_TEXT_DOMAIN).'</option>';
    foreach ($parent_options as $parent_doc) {
        $html .= '<option value="'.(int)$parent_doc->ID.'"'.selected((int)$doc->post_parent, (int)$parent_doc->ID, false).'>'.esc_html($parent_doc->post_title).'</option>';
    }
    $html .= '</select>';
    $html .= '</td></tr></table></div>';
    $html .= '</details>';

    if ($attachments_enabled) {
        $html .= '<details class="doc-meta-box toggleable">';
        $html .= '<summary class="toggle-switch">'.esc_html__('Attachments', CPC2_TEXT_DOMAIN).'</summary>';
        $html .= '<div class="toggle-content"><table class="toggle-table"><tr><td class="desc-column">';
        $html .= '<label for="cpc_docs_attachments">'.esc_html__('Neue Attachments', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<span class="description">'.esc_html__('Mehrere Dateien gleichzeitig moeglich.', CPC2_TEXT_DOMAIN).'</span>';
        $html .= '</td><td class="content-column">';
        $html .= '<input id="cpc_docs_attachments" type="file" name="cpc_docs_attachments[]" multiple />';
        $html .= '</td></tr></table></div>';
        $html .= '</details>';
    }

    $html .= '</div>';

    $html .= '<p id="doc-submit-options">';
    $html .= '<button type="submit" class="cpc_button">'.esc_html__('Speichern', CPC2_TEXT_DOMAIN).'</button> ';
    $html .= '<a href="'.esc_url($redirect_url).'" class="cpc_button">'.esc_html__('Abbrechen', CPC2_TEXT_DOMAIN).'</a>';
    $html .= '</p>';
    $html .= '</div>';
    $html .= '</form>';

    $html .= cpc_docs_render_single_doc_attachments($doc, $args);

    return $html;
}

function cpc_docs_render_locked_notice($doc) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return '';
    }

    $locker = cpc_docs_get_doc_locker_name($doc->ID);
    $html = '<div class="toggleable doc-is-locked">';
    $html .= '<p><strong>'.esc_html__('Locked', CPC2_TEXT_DOMAIN).'</strong></p>';
    if ($locker !== '') {
        $html .= '<p>'.esc_html(sprintf(__('Dieses Dokument wird derzeit von %s bearbeitet.', CPC2_TEXT_DOMAIN), $locker)).'</p>';
    } else {
        $html .= '<p>'.esc_html__('Dieses Dokument wird derzeit von einem anderen Benutzer bearbeitet.', CPC2_TEXT_DOMAIN).'</p>';
    }
    $html .= '<p>'.esc_html__('Bitte versuche es in ein paar Minuten erneut.', CPC2_TEXT_DOMAIN).'</p>';

    if (current_user_can('manage_options')) {
        $html .= '<form method="post">';
        $html .= '<input type="hidden" name="cpc_docs_action" value="unlock_doc" />';
        $html .= '<input type="hidden" name="cpc_docs_doc_id" value="'.(int)$doc->ID.'" />';
        $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
        $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url(get_permalink($doc)).'" />';
        $html .= '<button type="submit" class="cpc_button">'.esc_html__('Sperre aufheben', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</form>';
    }

    $html .= '</div>';
    return $html;
}

function cpc_docs_render_history_view($doc, $args = array()) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return '';
    }

    if (!cpc_docs_user_can_view_history($doc->ID)) {
        return '<p>'.esc_html__('Keine Berechtigung fuer den Verlauf.', CPC2_TEXT_DOMAIN).'</p>';
    }

    $revisions = wp_get_post_revisions($doc->ID, array(
        'order' => 'DESC',
        'orderby' => 'date',
    ));

    $html = '<div class="doc-content cpc_doc_history">';
    $html .= '<h3>'.esc_html__('Versionen', CPC2_TEXT_DOMAIN).'</h3>';

    $from_id = isset($_GET['cpc_docs_compare_from']) ? (int)$_GET['cpc_docs_compare_from'] : 0;
    $to_id = isset($_GET['cpc_docs_compare_to']) ? (int)$_GET['cpc_docs_compare_to'] : 0;

    if (empty($revisions)) {
        $html .= '<p>'.esc_html__('Noch keine Revisionen vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
        $html .= '</div>';
        return $html;
    }

    $revisions = array_values($revisions);
    if ($from_id <= 0 || $to_id <= 0) {
        $to_id = (int)$revisions[0]->ID;
        $from_id = isset($revisions[1]) ? (int)$revisions[1]->ID : $to_id;
    }

    $html .= cpc_docs_render_history_compare_form($doc, $revisions, $from_id, $to_id, $args);

    if ($from_id > 0 && $to_id > 0 && $from_id !== $to_id) {
        $from_post = get_post($from_id);
        $to_post = get_post($to_id);
        if ($from_post && $to_post && (int)$from_post->post_parent === (int)$doc->ID && (int)$to_post->post_parent === (int)$doc->ID) {
            $html .= '<div class="cpc_doc_history_diff">';
            $html .= '<h4>'.esc_html__('Vergleich', CPC2_TEXT_DOMAIN).'</h4>';
            $html .= '<div class="cpc_doc_history_diff_meta">';
            $html .= '<span><strong>'.esc_html__('Von', CPC2_TEXT_DOMAIN).':</strong> '.esc_html(wp_strip_all_tags(wp_post_revision_title($from_post->ID, false))).'</span>';
            $html .= '<span><strong>'.esc_html__('Zu', CPC2_TEXT_DOMAIN).':</strong> '.esc_html(wp_strip_all_tags(wp_post_revision_title($to_post->ID, false))).'</span>';
            $html .= '</div>';

            $title_diff = wp_text_diff((string)$from_post->post_title, (string)$to_post->post_title);
            if ($title_diff) {
                $html .= '<h5>'.esc_html__('Titel-Aenderungen', CPC2_TEXT_DOMAIN).'</h5>';
                $html .= $title_diff;
            }

            $content_diff = wp_text_diff((string)$from_post->post_content, (string)$to_post->post_content);
            if ($content_diff) {
                $html .= '<h5>'.esc_html__('Inhalt-Aenderungen', CPC2_TEXT_DOMAIN).'</h5>';
                $html .= $content_diff;
            }

            $excerpt_diff = wp_text_diff((string)$from_post->post_excerpt, (string)$to_post->post_excerpt);
            if ($excerpt_diff) {
                $html .= '<h5>'.esc_html__('Excerpt-Aenderungen', CPC2_TEXT_DOMAIN).'</h5>';
                $html .= $excerpt_diff;
            }
            $html .= '</div>';
        }
    }

    $html .= '<table class="doctable cpc_doc_history_table">';
    $html .= '<thead><tr><th>'.esc_html__('Datum', CPC2_TEXT_DOMAIN).'</th><th>'.esc_html__('Autor', CPC2_TEXT_DOMAIN).'</th><th>'.esc_html__('Aktion', CPC2_TEXT_DOMAIN).'</th></tr></thead>';
    $html .= '<tbody>';

    for ($i = 0; $i < count($revisions); $i++) {
        $revision = $revisions[$i];
        $author = get_the_author_meta('display_name', (int)$revision->post_author);
        $html .= '<tr>';
        $html .= '<td>'.esc_html(get_date_from_gmt($revision->post_date_gmt, 'Y-m-d H:i')).'</td>';
        $html .= '<td>'.esc_html($author).'</td>';
        $html .= '<td>';
        if (isset($revisions[$i + 1])) {
            $compare_link = cpc_docs_get_history_compare_link(
                $doc->ID,
                (int)$revisions[$i + 1]->ID,
                (int)$revision->ID,
                !empty($args['inline']) ? cpc_docs_get_inline_base_url($args) : ''
            );
            $html .= '<a href="'.esc_url($compare_link).'">'.esc_html__('Mit vorheriger vergleichen', CPC2_TEXT_DOMAIN).'</a>';
        } else {
            $html .= '&ndash;';
        }
        $html .= '</td>';
        $html .= '</tr>';
    }

    $html .= '</tbody></table>';
    $html .= '</div>';

    return $html;
}

function cpc_docs_directory_get_base_url() {
    if (is_page()) {
        return get_permalink(get_queried_object_id());
    }

    return cpc_docs_get_current_request_url();
}

function cpc_docs_directory_build_url($args = array()) {
    $url = cpc_docs_directory_get_base_url();
    $defaults = array(
        'cpc_docs_q' => isset($_GET['cpc_docs_q']) ? sanitize_text_field(wp_unslash($_GET['cpc_docs_q'])) : '',
        'cpc_docs_component' => isset($_GET['cpc_docs_component']) ? sanitize_key(wp_unslash($_GET['cpc_docs_component'])) : '',
        'cpc_docs_status' => isset($_GET['cpc_docs_status']) ? sanitize_key(wp_unslash($_GET['cpc_docs_status'])) : '',
        'cpc_docs_perm_edit' => isset($_GET['cpc_docs_perm_edit']) ? sanitize_key(wp_unslash($_GET['cpc_docs_perm_edit'])) : '',
        'cpc_docs_perm_history' => isset($_GET['cpc_docs_perm_history']) ? sanitize_key(wp_unslash($_GET['cpc_docs_perm_history'])) : '',
        'cpc_docs_folder' => isset($_GET['cpc_docs_folder']) ? (int)$_GET['cpc_docs_folder'] : 0,
        'cpc_docs_page' => isset($_GET['cpc_docs_page']) ? max(1, (int)$_GET['cpc_docs_page']) : 1,
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

function cpc_docs_directory_get_results($component = '', $status = '', $search = '', $page = 1, $per_page = 12, $perm_edit = '', $perm_history = '') {
    $visible = array();
    $offset = 0;
    $loops = 0;
    $batch_size = max(48, $per_page * 6);

    while ($loops < 16) {
        $args = array(
            'post_type' => 'cpc_doc',
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
        );

        if ($component !== '') {
            $args['component'] = $component;
        }
        if ($status !== '') {
            $args['doc_status'] = $status;
        }
        if ($search !== '') {
            $args['s'] = $search;
        }

        $batch = cpc_docs_get_docs($args);
        if (!$batch) {
            break;
        }

        foreach ($batch as $doc) {
            if (!cpc_docs_user_can_view_doc($doc->ID)) {
                continue;
            }

            if ($perm_edit !== '' && cpc_docs_get_doc_permission($doc->ID, 'edit', cpc_docs_get_component($doc->ID)) !== $perm_edit) {
                continue;
            }

            if ($perm_history !== '' && cpc_docs_get_doc_permission($doc->ID, 'view_history', cpc_docs_get_component($doc->ID)) !== $perm_history) {
                continue;
            }

                $visible[] = $doc;
        }

        if (count($batch) < $batch_size) {
            break;
        }

        $offset += $batch_size;
        $loops++;
    }

    return array(
        'items' => $visible,
        'has_more' => false,
    );
}

function cpc_docs_render_directory_filters($component, $status, $search, $perm_edit = '', $perm_history = '') {
    $base_url = cpc_docs_directory_get_base_url();
    $current_folder_id = isset($_GET['cpc_docs_folder']) ? (int)$_GET['cpc_docs_folder'] : 0;
    $permission_options = cpc_docs_get_permission_options('groups');
    $html = '<form method="get" action="'.esc_url($base_url).'" class="cpc_docs_directory_filters">';
    if ($current_folder_id > 0) {
        $html .= '<input type="hidden" name="cpc_docs_folder" value="'.(int)$current_folder_id.'" />';
    }

    $html .= '<div class="cpc_docs_directory_filter_field">';
    $html .= '<label>'.esc_html__('Suche', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<input type="search" name="cpc_docs_q" value="'.esc_attr($search).'" placeholder="'.esc_attr__('Titel oder Inhalt', CPC2_TEXT_DOMAIN).'" />';
    $html .= '</div>';

    $html .= '<div class="cpc_docs_directory_filter_field">';
    $html .= '<label>'.esc_html__('Edit-Recht', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select name="cpc_docs_perm_edit">';
    $html .= '<option value="">'.esc_html__('Alle', CPC2_TEXT_DOMAIN).'</option>';
    foreach ($permission_options as $value => $label) {
        $html .= '<option value="'.esc_attr($value).'"'.selected($perm_edit, $value, false).'>'.esc_html($label).'</option>';
    }
    $html .= '</select>';
    $html .= '</div>';

    $html .= '<div class="cpc_docs_directory_filter_field">';
    $html .= '<label>'.esc_html__('History-Recht', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select name="cpc_docs_perm_history">';
    $html .= '<option value="">'.esc_html__('Alle', CPC2_TEXT_DOMAIN).'</option>';
    foreach ($permission_options as $value => $label) {
        $html .= '<option value="'.esc_attr($value).'"'.selected($perm_history, $value, false).'>'.esc_html($label).'</option>';
    }
    $html .= '</select>';
    $html .= '</div>';

    $html .= '<div class="cpc_docs_directory_filter_field">';
    $html .= '<label>'.esc_html__('Kontext', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select name="cpc_docs_component">';
    $html .= '<option value="">'.esc_html__('Alle', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="members"'.selected($component, 'members', false).'>'.esc_html__('Profile', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="groups"'.selected($component, 'groups', false).'>'.esc_html__('Gruppen', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '</select>';
    $html .= '</div>';

    $html .= '<div class="cpc_docs_directory_filter_field">';
    $html .= '<label>'.esc_html__('Sichtbarkeit', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select name="cpc_docs_status">';
    $html .= '<option value="">'.esc_html__('Alle', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="public"'.selected($status, 'public', false).'>'.esc_html__('Oeffentlich', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="loggedin"'.selected($status, 'loggedin', false).'>'.esc_html__('Angemeldet', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="members"'.selected($status, 'members', false).'>'.esc_html__('Mitglieder', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="admins"'.selected($status, 'admins', false).'>'.esc_html__('Admins', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="private"'.selected($status, 'private', false).'>'.esc_html__('Privat', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '</select>';
    $html .= '</div>';

    $html .= '<div class="cpc_docs_directory_actions">';
    $html .= '<button type="submit" class="cpc_button">'.esc_html__('Filtern', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '<a class="cpc_button" href="'.esc_url(cpc_docs_directory_build_url(array('cpc_docs_q' => '', 'cpc_docs_component' => '', 'cpc_docs_status' => '', 'cpc_docs_perm_edit' => '', 'cpc_docs_perm_history' => '', 'cpc_docs_folder' => '', 'cpc_docs_page' => 1))).'">'.esc_html__('Zuruecksetzen', CPC2_TEXT_DOMAIN).'</a>';
    $html .= '</div>';

    $html .= '</form>';

    return $html;
}

function cpc_docs_render_directory_pagination($page, $has_more) {
    $page = max(1, (int)$page);
    if ($page <= 1 && !$has_more) {
        return '';
    }

    $html = '<div class="cpc_docs_directory_pagination">';
    if ($page > 1) {
        $html .= '<a class="cpc_button" href="'.esc_url(cpc_docs_directory_build_url(array('cpc_docs_page' => $page - 1))).'">'.esc_html__('Vorherige', CPC2_TEXT_DOMAIN).'</a>';
    }
    $html .= '<span class="cpc_docs_directory_page_label">'.sprintf(esc_html__('Seite %d', CPC2_TEXT_DOMAIN), $page).'</span>';
    if ($has_more) {
        $html .= '<a class="cpc_button" href="'.esc_url(cpc_docs_directory_build_url(array('cpc_docs_page' => $page + 1))).'">'.esc_html__('Naechste', CPC2_TEXT_DOMAIN).'</a>';
    }
    $html .= '</div>';

    return $html;
}

function cpc_docs_directory_shortcode($atts) {
    $atts = shortcode_atts(array(
        'per_page' => cpc_docs_get_directory_items_per_page(),
    ), $atts, 'cpc-docs-directory');

    $search = isset($_GET['cpc_docs_q']) ? sanitize_text_field(wp_unslash($_GET['cpc_docs_q'])) : '';
    $component = isset($_GET['cpc_docs_component']) ? sanitize_key(wp_unslash($_GET['cpc_docs_component'])) : '';
    $status = isset($_GET['cpc_docs_status']) ? sanitize_key(wp_unslash($_GET['cpc_docs_status'])) : '';
    $perm_edit = isset($_GET['cpc_docs_perm_edit']) ? sanitize_key(wp_unslash($_GET['cpc_docs_perm_edit'])) : '';
    $perm_history = isset($_GET['cpc_docs_perm_history']) ? sanitize_key(wp_unslash($_GET['cpc_docs_perm_history'])) : '';
    $page = isset($_GET['cpc_docs_page']) ? max(1, (int)$_GET['cpc_docs_page']) : 1;
    $per_page = max(6, min(60, (int)$atts['per_page']));

    if (!in_array($component, array('', 'members', 'groups'), true)) {
        $component = '';
    }
    if (!in_array($status, array('', 'public', 'loggedin', 'members', 'admins', 'private'), true)) {
        $status = '';
    }
    $valid_permissions = array_keys(cpc_docs_get_permission_options('groups'));
    if ($perm_edit !== '' && !in_array($perm_edit, $valid_permissions, true)) {
        $perm_edit = '';
    }
    if ($perm_history !== '' && !in_array($perm_history, $valid_permissions, true)) {
        $perm_history = '';
    }

    $results = cpc_docs_directory_get_results($component, $status, $search, $page, $per_page, $perm_edit, $perm_history);

    $html = '<div class="cpc_docs_directory">';
    $html .= '<div class="cpc_docs_directory_header">';
    $html .= '<h3 class="cpc_docs_directory_title">'.esc_html(cpc_docs_get_directory_title()).'</h3>';
    $html .= cpc_docs_render_directory_filters($component, $status, $search, $perm_edit, $perm_history);
    $html .= '</div>';

    if (empty($results['items'])) {
        $html .= '<p>'.esc_html__('Keine Dokumente gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    } else {
        $html .= cpc_docs_render_docs_table($results['items'], __('Keine Dokumente gefunden.', CPC2_TEXT_DOMAIN), array('mode' => 'directory'));
    }

    $html .= '</div>';

    return $html;
}
add_shortcode('cpc-docs-directory', 'cpc_docs_directory_shortcode');
add_shortcode('cpc-doc-directory', 'cpc_docs_directory_shortcode');

function cpc_docs_render_directory_page_content($content) {
    if (!cpc_docs_is_enabled() || !is_page() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $page_id = cpc_docs_get_directory_page_id();
    if (!$page_id || (int)get_queried_object_id() !== $page_id) {
        return $content;
    }

    global $post;
    if ($post && (has_shortcode((string)$post->post_content, 'cpc-docs-directory') || has_shortcode((string)$post->post_content, 'cpc-doc-directory'))) {
        return $content;
    }

    $directory = cpc_docs_directory_shortcode(array());
    if (trim((string)$content) === '') {
        return $directory;
    }

    return $content.$directory;
}
add_filter('the_content', 'cpc_docs_render_directory_page_content', 25);
