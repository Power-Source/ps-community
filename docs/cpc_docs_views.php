<?php

function cpc_docs_add_profile_tab($tabs, $user_id, $viewer_id) {
    if (!cpc_docs_is_enabled()) {
        return $tabs;
    }

    $tabs['docs'] = array(
        'label' => __('Dokumente', CPC2_TEXT_DOMAIN),
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
            'label' => __('Dokumente', CPC2_TEXT_DOMAIN),
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
    if (!cpc_docs_user_can_create_for_context($component, $component_id)) {
        return '';
    }

    $options = cpc_docs_get_status_options($component);

    $html = '';
    $parent_options = cpc_docs_get_parent_options($component, $component_id, 0);

    $html .= '<form method="post" enctype="multipart/form-data" class="cpc_docs_create_form" style="margin-bottom:16px;">';
    $html .= '<input type="hidden" name="cpc_docs_action" value="create_doc" />';
    $html .= '<input type="hidden" name="cpc_docs_component" value="'.esc_attr($component).'" />';
    $html .= '<input type="hidden" name="cpc_docs_component_id" value="'.(int)$component_id.'" />';
    $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
    $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url(cpc_curPageURL()).'" />';

    $html .= '<div style="display:grid; gap:8px;">';
    $html .= '<input type="text" name="cpc_docs_title" placeholder="'.esc_attr__('Titel', CPC2_TEXT_DOMAIN).'" required />';
    $html .= '<textarea name="cpc_docs_content" rows="5" placeholder="'.esc_attr__('Dokumentinhalt', CPC2_TEXT_DOMAIN).'" required></textarea>';
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
    $html .= '<div>';
    $html .= '<label>'.esc_html__('Attachments', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<input type="file" name="cpc_docs_attachments[]" multiple />';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">';
    $html .= '<label>'.esc_html__('Sichtbarkeit', CPC2_TEXT_DOMAIN).':</label>';
    $html .= '<select name="cpc_docs_status">';
    foreach ($options as $value => $label) {
        $html .= '<option value="'.esc_attr($value).'">'.esc_html($label).'</option>';
    }
    $html .= '</select>';
    $html .= '<button type="submit" class="cpc_button">'.esc_html__('Dokument erstellen', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</form>';

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

    $status_options = cpc_docs_get_status_options($component);
    $status_label = isset($status_options[$status]) ? $status_options[$status] : ucfirst($status);

    $html = '';
    $html .= '<article class="cpc_docs_card" style="padding:14px; border:1px solid #ddd; border-radius:6px; margin-bottom:12px; background:#fff;">';
    $html .= '<h4 style="margin:0 0 8px;"><a href="'.esc_url(get_permalink($doc)).'">'.esc_html($doc->post_title).'</a></h4>';
    $html .= '<div style="font-size:12px; color:#666; margin-bottom:8px;">';
    $html .= esc_html($author).' / '.esc_html($status_label).' / '.esc_html(get_the_modified_date('', $doc));
    $html .= '</div>';

    if ($doc->post_excerpt) {
        $html .= '<div>'.wp_kses_post(wpautop(wp_trim_words($doc->post_excerpt, 26))).'</div>';
    } else {
        $html .= '<div>'.wp_kses_post(wpautop(wp_trim_words(wp_strip_all_tags($doc->post_content), 36))).'</div>';
    }

    if ($can_manage) {
        $html .= '<div class="row-actions" style="margin:6px 0 8px;">';
        $html .= '<a href="'.esc_url(cpc_docs_get_edit_link($doc->ID)).'">'.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
        $html .= '</div>';

        $html .= '<form method="post" style="margin-top:8px;">';
        $html .= '<input type="hidden" name="cpc_docs_action" value="delete_doc" />';
        $html .= '<input type="hidden" name="cpc_docs_doc_id" value="'.(int)$doc->ID.'" />';
        $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
        $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url(cpc_curPageURL()).'" />';
        $html .= '<button type="submit" class="cpc_button" onclick="return confirm(\''.esc_js(__('Dokument wirklich loeschen?', CPC2_TEXT_DOMAIN)).'\');">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</form>';
    }

    $html .= '</article>';

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
        'posts_per_page' => 50,
    ));

    $html = '';
    $html .= cpc_docs_render_notice_html();
    $html .= '<div class="cpc_docs_profile_tab">';
    $html .= '<h3>'.esc_html__('Profil-Dokumente', CPC2_TEXT_DOMAIN).'</h3>';
    $html .= cpc_docs_render_create_form('members', $user_id);

    if (!$docs) {
        $html .= '<p>'.esc_html__('Noch keine Dokumente vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
    } else {
        $rendered = 0;
        foreach ($docs as $doc) {
            $card = cpc_docs_render_doc_card($doc);
            if ($card !== '') {
                $html .= $card;
                $rendered++;
            }
        }

        if ($rendered === 0) {
            $html .= '<p>'.esc_html__('Keine Dokumente sichtbar.', CPC2_TEXT_DOMAIN).'</p>';
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
        'posts_per_page' => 50,
    ));

    $html = '';
    $html .= cpc_docs_render_notice_html();
    $html .= '<div class="cpc_docs_group_tab">';
    $html .= '<h3>'.esc_html__('Gruppen-Dokumente', CPC2_TEXT_DOMAIN).'</h3>';
    $html .= cpc_docs_render_create_form('groups', $group_id);

    if (!$docs) {
        $html .= '<p>'.esc_html__('Noch keine Dokumente vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
    } else {
        $rendered = 0;
        foreach ($docs as $doc) {
            $card = cpc_docs_render_doc_card($doc);
            if ($card !== '') {
                $html .= $card;
                $rendered++;
            }
        }

        if ($rendered === 0) {
            $html .= '<p>'.esc_html__('Keine Dokumente sichtbar.', CPC2_TEXT_DOMAIN).'</p>';
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

    $action = isset($_GET['cpc_docs_action']) ? sanitize_key(wp_unslash($_GET['cpc_docs_action'])) : '';
    $is_edit = ($action === 'edit');
    $is_history = ($action === 'history');

    $body = '';
    $body .= '<article class="cpc_doc_single bp-docs-container">';
    $body .= '<header id="bp-docs-single-doc-header" class="cpc_doc_header">';
    $body .= '<h2 class="doc-title">'.esc_html($doc->post_title).'</h2>';
    $body .= cpc_docs_render_single_doc_actions($doc);
    $body .= '</header>';

    if ($is_history) {
        $body .= cpc_docs_render_history_view($doc);
    } elseif ($is_edit && cpc_docs_user_can_manage_doc($doc->ID)) {
        if (cpc_docs_is_doc_locked($doc->ID, get_current_user_id())) {
            $body .= cpc_docs_render_locked_notice($doc);
        } else {
            cpc_docs_acquire_edit_lock($doc->ID, get_current_user_id());
            $body .= cpc_docs_render_edit_form($doc);
        }
    } else {
        $body .= '<div class="doc-content cpc_doc_content">'.wp_kses_post(wpautop($doc->post_content)).'</div>';
        $body .= cpc_docs_render_single_doc_attachments($doc);
        $body .= cpc_docs_render_single_doc_meta($doc);
    }

    $body .= '</article>';

    return cpc_docs_render_notice_html().$body;
}
add_filter('the_content', 'cpc_docs_render_single_doc_content', 20);

function cpc_docs_render_single_doc_actions($doc) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return '';
    }

    $can_manage = cpc_docs_user_can_manage_doc($doc->ID);
    $action = isset($_GET['cpc_docs_action']) ? sanitize_key(wp_unslash($_GET['cpc_docs_action'])) : '';
    $is_edit = ($action === 'edit');
    $is_history = ($action === 'history');

    $html = '<div class="doc-edit-link cpc_doc_actions">';
    $html .= '<a class="cpc_button'.(!$is_edit && !$is_history ? ' active' : '').'" href="'.esc_url(get_permalink($doc)).'">'.esc_html__('Ansicht', CPC2_TEXT_DOMAIN).'</a>';
    $html .= '<a class="cpc_button'.($is_history ? ' active' : '').'" href="'.esc_url(cpc_docs_get_history_link($doc->ID)).'">'.esc_html__('Verlauf', CPC2_TEXT_DOMAIN).'</a>';

    if ($can_manage) {
        $html .= '<a class="cpc_button'.($is_edit ? ' active' : '').'" href="'.esc_url(cpc_docs_get_edit_link($doc->ID)).'">'.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';

        $html .= '<form method="post" style="display:inline-block; margin-left:8px;">';
        $html .= '<input type="hidden" name="cpc_docs_action" value="delete_doc" />';
        $html .= '<input type="hidden" name="cpc_docs_doc_id" value="'.(int)$doc->ID.'" />';
        $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
        $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url(get_permalink($doc)).'" />';
        $html .= '<button type="submit" class="cpc_button" onclick="return confirm(\''.esc_js(__('Dokument wirklich loeschen?', CPC2_TEXT_DOMAIN)).'\');">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</form>';
    }

    $html .= '</div>';
    return $html;
}

function cpc_docs_render_single_doc_attachments($doc) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return '';
    }

    $attachments = cpc_docs_get_doc_attachments($doc->ID);
    if (empty($attachments)) {
        return '';
    }

    $can_manage = cpc_docs_user_can_manage_doc($doc->ID);
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
            $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url(get_permalink($doc)).'" />';
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

function cpc_docs_render_edit_form($doc) {
    if (!$doc || $doc->post_type !== 'cpc_doc' || !cpc_docs_user_can_manage_doc($doc->ID)) {
        return '';
    }

    $component = cpc_docs_get_component($doc->ID);
    $component_id = cpc_docs_get_component_id($doc->ID);
    $status = cpc_docs_get_status($doc->ID);
    $status_options = cpc_docs_get_status_options($component);
    $parent_options = cpc_docs_get_parent_options($component, $component_id, $doc->ID);

    $html = '<form method="post" enctype="multipart/form-data" class="standard-form" id="doc-form">';
    $html .= '<input type="hidden" name="cpc_docs_action" value="update_doc" />';
    $html .= '<input type="hidden" name="cpc_docs_doc_id" value="'.(int)$doc->ID.'" />';
    $html .= '<input type="hidden" name="cpc_docs_nonce" value="'.esc_attr(wp_create_nonce('cpc_docs_frontend_action')).'" />';
    $html .= '<input type="hidden" name="cpc_docs_redirect" value="'.esc_url(get_permalink($doc)).'" />';

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
        ));
        $html .= ob_get_clean();
    } else {
        $html .= '<textarea id="cpc_docs_content" name="cpc_docs_content" rows="12" required>'.esc_textarea($doc->post_content).'</textarea>';
    }

    $html .= '<div class="cpc_docs_edit_grid">';
    $html .= '<p><label for="cpc_docs_status">'.esc_html__('Sichtbarkeit', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select id="cpc_docs_status" name="cpc_docs_status">';
    foreach ($status_options as $value => $label) {
        $html .= '<option value="'.esc_attr($value).'"'.selected($status, $value, false).'>'.esc_html($label).'</option>';
    }
    $html .= '</select></p>';

    $html .= '<p><label for="cpc_docs_parent_id">'.esc_html__('Parent', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select id="cpc_docs_parent_id" name="cpc_docs_parent_id">';
    $html .= '<option value="0">'.esc_html__('Kein Parent', CPC2_TEXT_DOMAIN).'</option>';
    foreach ($parent_options as $parent_doc) {
        $html .= '<option value="'.(int)$parent_doc->ID.'"'.selected((int)$doc->post_parent, (int)$parent_doc->ID, false).'>'.esc_html($parent_doc->post_title).'</option>';
    }
    $html .= '</select></p>';

    $html .= '<p><label for="cpc_docs_attachments">'.esc_html__('Neue Attachments', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<input id="cpc_docs_attachments" type="file" name="cpc_docs_attachments[]" multiple /></p>';
    $html .= '</div>';

    $html .= '<p id="doc-submit-options">';
    $html .= '<button type="submit" class="cpc_button">'.esc_html__('Speichern', CPC2_TEXT_DOMAIN).'</button> ';
    $html .= '<a href="'.esc_url(get_permalink($doc)).'" class="cpc_button">'.esc_html__('Abbrechen', CPC2_TEXT_DOMAIN).'</a>';
    $html .= '</p>';
    $html .= '</div>';
    $html .= '</form>';

    $html .= cpc_docs_render_single_doc_attachments($doc);

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

function cpc_docs_render_history_view($doc) {
    if (!$doc || $doc->post_type !== 'cpc_doc') {
        return '';
    }

    $revisions = wp_get_post_revisions($doc->ID, array(
        'order' => 'DESC',
        'orderby' => 'date',
    ));

    $html = '<div class="doc-content cpc_doc_history">';
    $html .= '<h3>'.esc_html__('Versionen', CPC2_TEXT_DOMAIN).'</h3>';

    $from_id = isset($_GET['cpc_docs_compare_from']) ? (int)$_GET['cpc_docs_compare_from'] : 0;
    $to_id = isset($_GET['cpc_docs_compare_to']) ? (int)$_GET['cpc_docs_compare_to'] : 0;
    if ($from_id > 0 && $to_id > 0) {
        $from_post = get_post($from_id);
        $to_post = get_post($to_id);
        if ($from_post && $to_post && (int)$from_post->post_parent === (int)$doc->ID && (int)$to_post->post_parent === (int)$doc->ID) {
            $html .= '<div class="cpc_doc_history_diff">';
            $html .= '<h4>'.esc_html__('Vergleich', CPC2_TEXT_DOMAIN).'</h4>';
            $html .= wp_text_diff((string)$from_post->post_content, (string)$to_post->post_content);
            $html .= '</div>';
        }
    }

    if (empty($revisions)) {
        $html .= '<p>'.esc_html__('Noch keine Revisionen vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
        $html .= '</div>';
        return $html;
    }

    $html .= '<table class="doctable cpc_doc_history_table">';
    $html .= '<thead><tr><th>'.esc_html__('Datum', CPC2_TEXT_DOMAIN).'</th><th>'.esc_html__('Autor', CPC2_TEXT_DOMAIN).'</th><th>'.esc_html__('Aktion', CPC2_TEXT_DOMAIN).'</th></tr></thead>';
    $html .= '<tbody>';

    $revisions = array_values($revisions);
    for ($i = 0; $i < count($revisions); $i++) {
        $revision = $revisions[$i];
        $author = get_the_author_meta('display_name', (int)$revision->post_author);
        $html .= '<tr>';
        $html .= '<td>'.esc_html(get_date_from_gmt($revision->post_date_gmt, 'Y-m-d H:i')).'</td>';
        $html .= '<td>'.esc_html($author).'</td>';
        $html .= '<td>';
        if (isset($revisions[$i + 1])) {
            $compare_link = cpc_docs_get_history_compare_link($doc->ID, (int)$revisions[$i + 1]->ID, (int)$revision->ID);
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

function cpc_docs_directory_get_results($component = '', $status = '', $search = '', $page = 1, $per_page = 12) {
    $visible = array();
    $offset = 0;
    $loops = 0;
    $target_count = ($page * $per_page) + 1;
    $batch_size = max(24, $per_page * 4);
    $has_more = false;

    while ($loops < 12 && count($visible) < $target_count) {
        $args = array(
            'post_type' => 'cpc_doc',
            'post_status' => 'publish',
            'posts_per_page' => $batch_size,
            'offset' => $offset,
            'orderby' => 'modified',
            'order' => 'DESC',
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
            if (cpc_docs_user_can_view_doc($doc->ID)) {
                $visible[] = $doc;
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

function cpc_docs_render_directory_filters($component, $status, $search) {
    $base_url = cpc_docs_directory_get_base_url();
    $html = '<form method="get" action="'.esc_url($base_url).'" class="cpc_docs_directory_filters">';

    $html .= '<div class="cpc_docs_directory_filter_field">';
    $html .= '<label>'.esc_html__('Suche', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<input type="search" name="cpc_docs_q" value="'.esc_attr($search).'" placeholder="'.esc_attr__('Titel oder Inhalt', CPC2_TEXT_DOMAIN).'" />';
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
    $html .= '<a class="cpc_button" href="'.esc_url(cpc_docs_directory_build_url(array('cpc_docs_q' => '', 'cpc_docs_component' => '', 'cpc_docs_status' => '', 'cpc_docs_page' => 1))).'">'.esc_html__('Zuruecksetzen', CPC2_TEXT_DOMAIN).'</a>';
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
    $page = isset($_GET['cpc_docs_page']) ? max(1, (int)$_GET['cpc_docs_page']) : 1;
    $per_page = max(6, min(60, (int)$atts['per_page']));

    if (!in_array($component, array('', 'members', 'groups'), true)) {
        $component = '';
    }
    if (!in_array($status, array('', 'public', 'loggedin', 'members', 'admins', 'private'), true)) {
        $status = '';
    }

    $results = cpc_docs_directory_get_results($component, $status, $search, $page, $per_page);

    $html = '<div class="cpc_docs_directory">';
    $html .= '<div class="cpc_docs_directory_header">';
    $html .= cpc_docs_render_directory_filters($component, $status, $search);
    $html .= '</div>';

    if (empty($results['items'])) {
        $html .= '<p>'.esc_html__('Keine Dokumente gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    } else {
        foreach ($results['items'] as $doc) {
            $html .= cpc_docs_render_doc_card($doc);
        }
    }

    $html .= cpc_docs_render_directory_pagination($page, !empty($results['has_more']));
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
