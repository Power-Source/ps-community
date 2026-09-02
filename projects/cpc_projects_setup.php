<?php

add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_projects', 8);
function cpc_admin_getting_started_projects() {
    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === 'cpc_admin_getting_started_projects' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';
    echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" rel="cpc_admin_getting_started_projects" id="cpc_admin_getting_started_projects_div">'.__('Projekte', 'cp-community').'</div>';

    $display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === 'cpc_admin_getting_started_projects' ? 'block' : 'none';
    echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_projects" style="display:'.$display.'">';

    echo '<table class="form-table">';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Profil-Tab: Projekt erstellen erlauben', 'cp-community').'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_projects_profile_allow_create" '.(cpc_projects_show_profile_create_form() ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Standardmaessig aus, damit Projekte zentral im Gruppen-Tab verwaltet werden.', 'cp-community').'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('User-Tab-Name', 'cp-community').'</label></td>';
    echo '<td><input type="text" name="cpc_projects_user_tab_name" value="'.esc_attr(cpc_projects_get_user_tab_name()).'" class="regular-text" /></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Gruppen-Tab-Name', 'cp-community').'</label></td>';
    echo '<td><input type="text" name="cpc_projects_group_tab_name" value="'.esc_attr(cpc_projects_get_group_tab_name()).'" class="regular-text" /></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Directory-Seite (Übersicht)', 'cp-community').'</label></td>';
    echo '<td>';
    wp_dropdown_pages(array(
        'name' => 'cpc_projects_directory_page',
        'selected' => cpc_projects_get_directory_page_id(),
        'show_option_none' => __('Keine/Deaktiviert', 'cp-community'),
    ));
    echo '<span class="description">'.__('Seite für die öffentliche Projekte-Übersicht mit Shortcode [cpc-projects-directory]', 'cp-community').'</span>';
    echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Directory-Titel', 'cp-community').'</label></td>';
    echo '<td><input type="text" name="cpc_projects_directory_title" value="'.esc_attr(cpc_projects_get_directory_title()).'" class="regular-text" /></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Items pro Seite', 'cp-community').'</label></td>';
    echo '<td><input type="number" min="6" max="60" name="cpc_projects_directory_items_per_page" value="'.(int)cpc_projects_get_directory_items_per_page().'" class="small-text" style="max-width:60px;" /></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Task-Kommentar-Anhaenge', 'cp-community').'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_projects_comment_attachments_enabled" '.(cpc_projects_task_comment_attachments_enabled() ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Datei-Uploads direkt an Task-Kommentare.', 'cp-community').'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Max. Attachment-Groesse (MB)', 'cp-community').'</label></td>';
    echo '<td><input type="number" min="1" max="50" name="cpc_projects_comment_max_attachment_mb" value="'.(int)cpc_projects_task_comment_max_attachment_mb().'" class="small-text" style="max-width:80px;" /></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Erlaubte Attachment-Endungen', 'cp-community').'</label></td>';
    echo '<td><input type="text" name="cpc_projects_comment_allowed_exts" value="'.esc_attr(implode(',', cpc_projects_comment_allowed_extensions())).'" class="regular-text" />';
    echo '<span class="description">'.__('Kommagetrennt, z.B. jpg,png,pdf,zip,mp4', 'cp-community').'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Max. Bild-Size (MB)', 'cp-community').'</label></td>';
    echo '<td><input type="number" min="1" max="200" name="cpc_projects_comment_max_attachment_mb_image" value="'.(int)cpc_projects_comment_attachment_type_limit_mb('image').'" class="small-text" style="max-width:80px;" /></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Max. Video-Size (MB)', 'cp-community').'</label></td>';
    echo '<td><input type="number" min="1" max="200" name="cpc_projects_comment_max_attachment_mb_video" value="'.(int)cpc_projects_comment_attachment_type_limit_mb('video').'" class="small-text" style="max-width:80px;" /></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Max. Audio-Size (MB)', 'cp-community').'</label></td>';
    echo '<td><input type="number" min="1" max="200" name="cpc_projects_comment_max_attachment_mb_audio" value="'.(int)cpc_projects_comment_attachment_type_limit_mb('audio').'" class="small-text" style="max-width:80px;" /></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Max. Dokument-Size (MB)', 'cp-community').'</label></td>';
    echo '<td><input type="number" min="1" max="200" name="cpc_projects_comment_max_attachment_mb_document" value="'.(int)cpc_projects_comment_attachment_type_limit_mb('document').'" class="small-text" style="max-width:80px;" /></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Task-Deadline Standard-Offset (Tage)', 'cp-community').'</label></td>';
    echo '<td><input type="number" min="1" max="365" name="cpc_projects_task_default_deadline_days" value="'.(int)cpc_projects_task_default_deadline_days().'" class="small-text" style="max-width:80px;" />';
    echo '<span class="description">'.__('Standard-Anzahl von Tagen ab heute für Aufgaben-Deadline (z.B. 7 = 7 Tage ab heute)', 'cp-community').'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Task-Deadline Standard-Zeit', 'cp-community').'</label></td>';
    echo '<td><input type="time" name="cpc_projects_task_default_deadline_time" value="'.esc_attr(cpc_projects_task_default_deadline_time()).'" />';
    echo '<span class="description">'.__('Standard-Uhrzeit für Aufgaben-Deadline (z.B. 09:00 = 9:00 Uhr morgens)', 'cp-community').'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Alerts bei Task-Events', 'cp-community').'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_projects_alerts_enabled" '.(cpc_projects_alerts_enabled() ? 'CHECKED' : '').' /></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Activity bei Task-Events', 'cp-community').'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_projects_activity_enabled" '.(cpc_projects_activity_enabled() ? 'CHECKED' : '').' /></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Gruppen-Empfaenger fuer Alerts', 'cp-community').'</label></td>';
    echo '<td><select name="cpc_projects_group_alert_scope">';
    echo '<option value="moderators" '.selected(cpc_projects_group_alert_scope(), 'moderators', false).'>'.esc_html__('Nur Admins/Moderatoren', 'cp-community').'</option>';
    echo '<option value="all_members" '.selected(cpc_projects_group_alert_scope(), 'all_members', false).'>'.esc_html__('Alle Gruppenmitglieder', 'cp-community').'</option>';
    echo '</select>';
    echo '<span class="description">'.__('Feinsteuerung fuer Benachrichtigungen bei Gruppen-Projekten.', 'cp-community').'</span></td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>';
}

add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_projects_save', 10, 1);
add_action('cpc_admin_setup_form_get_hook', 'cpc_admin_projects_save', 10, 1);
function cpc_admin_projects_save($the_post) {
    // Module activation is now controlled centrally via cpc_default_core (core-projects).
    delete_option('cpc_projects_module_enabled');

    if (isset($the_post['cpc_projects_profile_allow_create'])) {
        update_option('cpc_projects_profile_allow_create', 1);
    } else {
        delete_option('cpc_projects_profile_allow_create');
    }

    if (isset($the_post['cpc_projects_user_tab_name'])) {
        update_option('cpc_projects_user_tab_name', sanitize_text_field($the_post['cpc_projects_user_tab_name']));
    }

    if (isset($the_post['cpc_projects_group_tab_name'])) {
        update_option('cpc_projects_group_tab_name', sanitize_text_field($the_post['cpc_projects_group_tab_name']));
    }

    if (isset($the_post['cpc_projects_directory_page'])) {
        update_option('cpc_projects_directory_page', max(0, (int)$the_post['cpc_projects_directory_page']));
    }

    if (isset($the_post['cpc_projects_directory_title'])) {
        update_option('cpc_projects_directory_title', sanitize_text_field($the_post['cpc_projects_directory_title']));
    }

    if (isset($the_post['cpc_projects_directory_items_per_page'])) {
        update_option('cpc_projects_directory_items_per_page', max(6, min(60, (int)$the_post['cpc_projects_directory_items_per_page'])));
    }

    if (isset($the_post['cpc_projects_comment_attachments_enabled'])) {
        update_option('cpc_projects_comment_attachments_enabled', 1);
    } else {
        delete_option('cpc_projects_comment_attachments_enabled');
    }

    if (isset($the_post['cpc_projects_comment_max_attachment_mb'])) {
        update_option('cpc_projects_comment_max_attachment_mb', max(1, min(50, (int)$the_post['cpc_projects_comment_max_attachment_mb'])));
    }

    if (isset($the_post['cpc_projects_comment_allowed_exts'])) {
        $exts = strtolower((string)$the_post['cpc_projects_comment_allowed_exts']);
        $exts = preg_replace('/[^a-z0-9,]/', '', $exts);
        update_option('cpc_projects_comment_allowed_exts', $exts);
    }

    foreach (array('image', 'video', 'audio', 'document') as $bucket) {
        $key = 'cpc_projects_comment_max_attachment_mb_'.$bucket;
        if (isset($the_post[$key])) {
            update_option($key, max(1, min(200, (int)$the_post[$key])));
        }
    }

    if (isset($the_post['cpc_projects_task_default_deadline_days'])) {
        update_option('cpc_projects_task_default_deadline_days', max(1, min(365, (int)$the_post['cpc_projects_task_default_deadline_days'])));
    }

    if (isset($the_post['cpc_projects_task_default_deadline_time'])) {
        $time = sanitize_text_field((string)$the_post['cpc_projects_task_default_deadline_time']);
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            update_option('cpc_projects_task_default_deadline_time', $time);
        }
    }

    if (isset($the_post['cpc_projects_alerts_enabled'])) {
        update_option('cpc_projects_alerts_enabled', 1);
    } else {
        delete_option('cpc_projects_alerts_enabled');
    }

    if (isset($the_post['cpc_projects_activity_enabled'])) {
        update_option('cpc_projects_activity_enabled', 1);
    } else {
        delete_option('cpc_projects_activity_enabled');
    }

    if (isset($the_post['cpc_projects_group_alert_scope'])) {
        $scope = sanitize_key($the_post['cpc_projects_group_alert_scope']);
        if (!in_array($scope, array('moderators', 'all_members'), true)) {
            $scope = 'moderators';
        }
        update_option('cpc_projects_group_alert_scope', $scope);
    }
}
