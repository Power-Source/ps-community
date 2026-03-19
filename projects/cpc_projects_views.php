<?php

function cpc_projects_add_profile_tab($tabs, $user_id, $viewer_id) {
    if (!cpc_projects_is_enabled()) {
        return $tabs;
    }

    $count = 0;
    $user_id = (int)$user_id;
    if ($user_id > 0) {
        $projects = cpc_projects_get_profile_projects($user_id, array(
            'posts_per_page' => 120,
            'viewer_id' => (int)$viewer_id,
        ));
        $count = count($projects);
    }

    $label = cpc_projects_get_user_tab_name();
    if ($count > 0) {
        $label .= ' ('.(int)$count.')';
    }

    $tabs['projects'] = array(
        'label' => $label,
        'icon' => 'portfolio',
        'priority' => 26,
    );

    return $tabs;
}
add_filter('cpc_profile_tabs', 'cpc_projects_add_profile_tab', 20, 3);

function cpc_projects_add_group_tab($tabs, $group_id, $user_id) {
    if (!cpc_projects_is_enabled()) {
        return $tabs;
    }

        if (!get_post_meta($group_id, 'cpc_group_has_projects', true)) {
            return $tabs;
        }

        if (function_exists('cpc_can_view_group') && cpc_can_view_group($user_id, $group_id)) {
        $tabs['projects'] = array(
            'label' => cpc_projects_get_group_tab_name(),
            'icon' => 'portfolio',
            'priority' => 24,
        );
    }

    return $tabs;
}
add_filter('cpc_group_tabs', 'cpc_projects_add_group_tab', 20, 3);

/**
 * Gibt die korrekte URL fuer ein Projekt zurueck.
 * Gruppontext → Gruppen-Tab-URL mit cpc_project_id-Parameter.
 * Sonst → Standard-CPT-Permalink.
 */
function cpc_projects_get_project_url($project_id) {
    $project_id = (int)$project_id;
    if (!$project_id) {
        return '';
    }
    $component    = cpc_projects_get_component($project_id);
    $component_id = cpc_projects_get_component_id($project_id);

    if ($component === 'groups' && $component_id > 0 && function_exists('cpc_get_group_link')) {
        $group_link = cpc_get_group_link($component_id);
        if ($group_link) {
            return add_query_arg(
                array('tab' => 'projects', 'cpc_project_id' => $project_id),
                remove_query_arg(array('tab', 'cpc_project_id'), $group_link)
            );
        }
    }

    return get_permalink($project_id);
}

function cpc_projects_render_events_html($project_id, $limit = 60) {
    $project_id = (int)$project_id;
    $limit = max(1, min(300, (int)$limit));

    if ($project_id <= 0) {
        return '<p>' . esc_html__('Noch keine Events vorhanden.', CPC2_TEXT_DOMAIN) . '</p>';
    }

    $activities = cpc_projects_get_project_activities($project_id, $limit);
    if (empty($activities)) {
        return '<p>' . esc_html__('Noch keine Events vorhanden.', CPC2_TEXT_DOMAIN) . '</p>';
    }

    $html = '<ul class="cpc_projects_single_events">';
    foreach ($activities as $activity) {
        $author_name = __('Mitglied', CPC2_TEXT_DOMAIN);
        $author = get_user_by('id', (int)$activity->post_author);
        if ($author) {
            $author_name = $author->display_name;
        }

        $ts = strtotime((string)$activity->post_date_gmt . ' GMT');
        if (!$ts) {
            $ts = current_time('timestamp', 1);
        }
        $when = sprintf(__('vor %s', CPC2_TEXT_DOMAIN), human_time_diff($ts, current_time('timestamp', 1)));

        $text_html = trim((string)$activity->post_content);
        if ($text_html === '') {
            $text_html = trim((string)$activity->post_title);
        }

        $task_id = (int)get_post_meta((int)$activity->ID, 'cpc_project_task_id', true);
        $target_url = $task_id > 0 ? cpc_projects_get_task_url($project_id, $task_id) : cpc_projects_get_project_url($project_id);

        $html .= '<li>';
        $html .= '<strong>' . esc_html($author_name) . '</strong> <span>' . esc_html($when) . '</span>';
        $html .= '<div>' . wp_kses_post($text_html) . '</div>';
        if (!empty($target_url)) {
            $html .= '<div><a href="' . esc_url($target_url) . '">' . esc_html__('Zum Eintrag', CPC2_TEXT_DOMAIN) . '</a></div>';
        }
        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
}

/**
 * Rendert die komplette Detail-Ansicht eines einzelnen Projekts (Tabs: Uebersicht, Tasks, Aktivitaet, Einstellungen).
 * Wird sowohl vom CPT-Einzel-Template als auch vom Gruppen-Tab genutzt.
 */
function cpc_projects_render_single_project_html($project_id, $args = array()) {
    $args = wp_parse_args($args, array(
        'back_url' => '',
    ));

    $project_id = (int)$project_id;
    if (!$project_id || !cpc_projects_user_can_view_project($project_id)) {
        return '<p>' . esc_html__('Keine Berechtigung.', CPC2_TEXT_DOMAIN) . '</p>';
    }

    $project    = get_post($project_id);
    if (!$project) {
        return '';
    }

    $can_manage = cpc_projects_user_can_manage_project($project_id);
    $progress   = cpc_projects_get_task_progress($project_id);

    $events_html = cpc_projects_render_events_html($project_id, 60);

    $html = '';
    if (!empty($args['back_url'])) {
        $html .= '<p class="cpc_projects_back_link"><a href="' . esc_url($args['back_url']) . '">&larr; ' . esc_html__('Alle Projekte', CPC2_TEXT_DOMAIN) . '</a></p>';
    }
    $html .= '<div class="cpc_projects_single" data-project-id="' . (int)$project_id . '">';
    $html .= '<h2 class="cpc_projects_single_title">' . esc_html(get_the_title($project_id)) . '</h2>';
    $html .= '<div class="cpc_projects_single_nav">';
    $html .= '<button type="button" class="cpc_projects_single_nav_link is-active" data-target="overview">' . esc_html__('Uebersicht', CPC2_TEXT_DOMAIN) . '</button>';
    $html .= '<button type="button" class="cpc_projects_single_nav_link" data-target="tasks">' . esc_html__('Tasks', CPC2_TEXT_DOMAIN) . '</button>';
    $html .= '<button type="button" class="cpc_projects_single_nav_link" data-target="activity">' . esc_html__('Aktivitaet', CPC2_TEXT_DOMAIN) . '</button>';
    if ($can_manage) {
        $html .= '<button type="button" class="cpc_projects_single_nav_link" data-target="settings">' . esc_html__('Einstellungen', CPC2_TEXT_DOMAIN) . '</button>';
    }
    $html .= '</div>';

    $html .= '<section class="cpc_projects_single_section is-active" data-section="overview">';
    if ((int)$progress['total'] > 0) {
        $html .= '<div class="cpc_projects_ataglance">';
        $html .= '<div class="cpc_projects_ataglance_item"><span class="cpc_projects_ataglance_num">' . (int)$progress['total'] . '</span><span class="cpc_projects_ataglance_label">' . esc_html__('Gesamt', CPC2_TEXT_DOMAIN) . '</span></div>';
        $html .= '<div class="cpc_projects_ataglance_item"><span class="cpc_projects_ataglance_num cpc_projects_ataglance_done">' . (int)$progress['completed'] . '</span><span class="cpc_projects_ataglance_label">' . esc_html__('Erledigt', CPC2_TEXT_DOMAIN) . '</span></div>';
        $html .= '<div class="cpc_projects_ataglance_item"><span class="cpc_projects_ataglance_num cpc_projects_ataglance_open">' . (int)$progress['remaining'] . '</span><span class="cpc_projects_ataglance_label">' . esc_html__('Offen', CPC2_TEXT_DOMAIN) . '</span></div>';
        $html .= '<div class="cpc_projects_ataglance_bar">' . cpc_projects_render_project_progress($project_id) . '</div>';
        $html .= '</div>';
    }
    $html .= '<div class="cpc_projects_single_content">' . wp_kses_post($project->post_content) . '</div>';
    $html .= '</section>';

    $html .= '<section class="cpc_projects_single_section" data-section="tasks">';
    $html .= cpc_projects_render_task_panel($project_id);
    $html .= '</section>';

    $html .= '<section class="cpc_projects_single_section" data-section="activity">';
    $html .= $events_html;
    $html .= '</section>';

    if ($can_manage) {
        $current_status = cpc_projects_get_status($project_id);
        $html .= '<section class="cpc_projects_single_section" data-section="settings">';
        $html .= '<div class="cpc_projects_settings_panel">';
        $html .= '<div class="cpc_projects_settings_notice" style="display:none"></div>';
        $html .= '<form class="cpc_projects_settings_form" data-project-id="' . (int)$project_id . '">';
        $html .= '<div class="cpc_projects_form_grid">';
        $html .= '<label>' . esc_html__('Titel', CPC2_TEXT_DOMAIN) . '</label>';
        $html .= '<input type="text" name="title" value="' . esc_attr(get_the_title($project_id)) . '" required />';
        $html .= '<label>' . esc_html__('Beschreibung', CPC2_TEXT_DOMAIN) . '</label>';
        $html .= '<textarea name="description" rows="6">' . esc_textarea($project->post_content) . '</textarea>';
        $html .= '<label>' . esc_html__('Sichtbarkeit', CPC2_TEXT_DOMAIN) . '</label>';
        $html .= '<select name="status">';
        $html .= '<option value="public"' . selected($current_status, 'public', false) . '>' . esc_html__('Oeffentlich', CPC2_TEXT_DOMAIN) . '</option>';
        $html .= '<option value="members"' . selected($current_status, 'members', false) . '>' . esc_html__('Nur Mitglieder', CPC2_TEXT_DOMAIN) . '</option>';
        $html .= '<option value="private"' . selected($current_status, 'private', false) . '>' . esc_html__('Privat', CPC2_TEXT_DOMAIN) . '</option>';
        $html .= '</select>';
        $html .= '</div>';
        $html .= '<p><button type="submit" class="cpc_button">' . esc_html__('Projekt aktualisieren', CPC2_TEXT_DOMAIN) . '</button></p>';
        $html .= '</form>';
        $html .= '<hr class="cpc_projects_settings_divider" />';
        $html .= '<div class="cpc_projects_settings_danger">';
        $html .= '<p class="cpc_projects_settings_danger_hint">' . esc_html__('Projekt und alle Tasks dauerhaft loeschen. Diese Aktion kann nicht rueckgaengig gemacht werden.', CPC2_TEXT_DOMAIN) . '</p>';
        $html .= '<button type="button" class="cpc_button cpc_button_danger cpc_projects_delete_project_btn" data-project-id="' . (int)$project_id . '">' . esc_html__('Projekt loeschen', CPC2_TEXT_DOMAIN) . '</button>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '</section>';
    }

    $html .= '</div>';

    return $html;
}

function cpc_projects_render_notice_html() {
    if (empty($_GET['cpc_projects_notice'])) {
        return '';
    }

    $notice = sanitize_key(wp_unslash($_GET['cpc_projects_notice']));
    $message = cpc_projects_notice_message($notice);
    if (!$message) {
        return '';
    }

    $class = 'cpc_projects_notice';
    $class .= in_array($notice, array('failed', 'invalid', 'denied'), true) ? ' cpc_projects_notice_error' : ' cpc_projects_notice_success';

    return '<div class="'.esc_attr($class).'">'.esc_html($message).'</div>';
}

function cpc_projects_render_create_form($component, $component_id) {
    if (!cpc_projects_user_can_create_for_context($component, $component_id)) {
        return '';
    }

    $html = '';
    $html .= '<details class="cpc_projects_create_toggle">';
    $html .= '<summary class="cpc_button cpc_projects_create_summary">'.esc_html__('Projekt hinzufuegen', CPC2_TEXT_DOMAIN).'</summary>';
    $html .= '<form method="post" class="cpc_projects_create_form">';
    $html .= '<input type="hidden" name="cpc_projects_action" value="create_project" />';
    $html .= '<input type="hidden" name="cpc_projects_component" value="'.esc_attr($component).'" />';
    $html .= '<input type="hidden" name="cpc_projects_component_id" value="'.(int)$component_id.'" />';
    $html .= '<input type="hidden" name="cpc_projects_nonce" value="'.esc_attr(wp_create_nonce('cpc_projects_frontend_action')).'" />';
    $html .= '<input type="hidden" name="cpc_projects_redirect" value="'.esc_url(cpc_curPageURL()).'" />';
    $html .= '<div class="cpc_projects_form_grid">';
    $html .= '<label>'.esc_html__('Titel', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<input type="text" name="cpc_projects_title" required />';
    $html .= '<label>'.esc_html__('Beschreibung', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<textarea name="cpc_projects_description" rows="4"></textarea>';
    $html .= '<label>'.esc_html__('Sichtbarkeit', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '<select name="cpc_projects_status">';
    $html .= '<option value="public">'.esc_html__('Oeffentlich', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="members">'.esc_html__('Nur Mitglieder', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="private">'.esc_html__('Privat', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '</select>';
    $html .= '</div>';
    $html .= '<p><button type="submit" class="cpc_button">'.esc_html__('Projekt erstellen', CPC2_TEXT_DOMAIN).'</button></p>';
    $html .= '</form>';
    $html .= '</details>';

    return $html;
}

function cpc_projects_render_task_priority_label($priority) {
    $priority = (int)$priority;
    if ($priority >= 3) {
        return __('Kritisch', CPC2_TEXT_DOMAIN);
    }
    if ($priority === 2) {
        return __('Hoch', CPC2_TEXT_DOMAIN);
    }
    return __('Normal', CPC2_TEXT_DOMAIN);
}

function cpc_projects_get_assignable_user_labels($users) {
    $users = is_array($users) ? $users : array();
    if (empty($users)) {
        return array();
    }

    $name_counts = array();
    foreach ($users as $user) {
        $name = isset($user->display_name) ? trim((string)$user->display_name) : '';
        if ($name === '') {
            $name = __('Mitglied', CPC2_TEXT_DOMAIN);
        }
        if (!isset($name_counts[$name])) {
            $name_counts[$name] = 0;
        }
        $name_counts[$name]++;
    }

    $labels = array();
    foreach ($users as $user) {
        $id = !empty($user->ID) ? (int)$user->ID : 0;
        if ($id <= 0) {
            continue;
        }
        $name = isset($user->display_name) ? trim((string)$user->display_name) : '';
        if ($name === '') {
            $name = __('Mitglied', CPC2_TEXT_DOMAIN);
        }
        $label = $name;
        if (!empty($name_counts[$name]) && $name_counts[$name] > 1 && !empty($user->user_login)) {
            $label .= ' (@'.sanitize_text_field((string)$user->user_login).')';
        }
        $labels[$id] = $label;
    }

    return $labels;
}

function cpc_projects_render_task_panel($project_id) {
    $project_id = (int)$project_id;
    if (!$project_id || !cpc_projects_user_can_view_project($project_id)) {
        return '';
    }

    $can_manage = cpc_projects_user_can_manage_project($project_id);
    $tasks = cpc_projects_get_tasks($project_id, array('limit' => 300));
    $assignable_users = cpc_projects_get_assignable_users($project_id);
    $assignable_labels = cpc_projects_get_assignable_user_labels($assignable_users);

    $html = '';
    $html .= '<div class="cpc_projects_task_panel" data-project-id="'.(int)$project_id.'">';
    $html .= '<h5 class="cpc_projects_task_heading">'.esc_html__('Tasks', CPC2_TEXT_DOMAIN).'</h5>';

    // Compact filter bar
    $html .= '<div class="cpc_projects_task_filters_bar">';
    $html .= '<input type="text" class="cpc_projects_task_filter_text" placeholder="'.esc_attr__('Suchen...', CPC2_TEXT_DOMAIN).'" />';
    $html .= '<select class="cpc_projects_task_filter_status">';
    $html .= '<option value="all">'.esc_html__('Status', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="open" selected="selected">'.esc_html__('Offen', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="done">'.esc_html__('Erledigt', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '</select>';
    $html .= '<select class="cpc_projects_task_filter_priority">';
    $html .= '<option value="all">'.esc_html__('Priorität', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="1">'.esc_html__('Normal', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="2">'.esc_html__('Hoch', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="3">'.esc_html__('Kritisch', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '</select>';
    $html .= '<select class="cpc_projects_task_filter_overdue">';
    $html .= '<option value="0">'.esc_html__('Fristen', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="1">'.esc_html__('Überfällig', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '</select>';
    $html .= '<select class="cpc_projects_task_filter_assignee">';
    $html .= '<option value="all">'.esc_html__('Zugewiesen', CPC2_TEXT_DOMAIN).'</option>';
    foreach ($assignable_labels as $uid => $label) {
        $html .= '<option value="'.(int)$uid.'">'.esc_html($label).'</option>';
    }
    $html .= '</select>';
    $html .= '</div>';

    if ($can_manage) {
        $html .= '<details class="cpc_projects_task_form_toggle" style="margin-bottom:12px;">';
        $html .= '<summary class="cpc_button cpc_projects_task_form_summary">'.esc_html__('+ Task hinzufuegen', CPC2_TEXT_DOMAIN).'</summary>';
        $html .= '<form class="cpc_projects_task_form" data-project-id="'.(int)$project_id.'" style="margin-top:12px;">';
        $html .= '<div class="cpc_projects_task_form_row">';
        $html .= '<div class="cpc_projects_task_field">';
        $html .= '<label class="cpc_projects_task_field_label">'.esc_html__('Titel', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<input type="text" name="title" placeholder="'.esc_attr__('Task-Titel', CPC2_TEXT_DOMAIN).'" required />';
        $html .= '</div>';
        $html .= '<div class="cpc_projects_task_field">';
        $html .= '<label class="cpc_projects_task_field_label">'.esc_html__('Prioritaet', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<select name="priority">';
        $html .= '<option value="1">'.esc_html__('Normal', CPC2_TEXT_DOMAIN).'</option>';
        $html .= '<option value="2">'.esc_html__('Hoch', CPC2_TEXT_DOMAIN).'</option>';
        $html .= '<option value="3">'.esc_html__('Kritisch', CPC2_TEXT_DOMAIN).'</option>';
        $html .= '</select>';
        $html .= '</div>';
        $html .= '<button type="submit" class="cpc_button cpc_projects_add_task_btn">'.esc_html__('Task hinzufuegen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</div>';
        $html .= '<div class="cpc_projects_task_form_row cpc_projects_task_form_row_secondary">';
        $html .= '<div class="cpc_projects_task_field">';
        $html .= '<label class="cpc_projects_task_field_label">'.esc_html__('Deadline', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<input type="datetime-local" name="deadline" placeholder="'.esc_attr__('Deadline', CPC2_TEXT_DOMAIN).'" value="'.esc_attr(cpc_projects_get_default_deadline_datetime()).'" />';
        $html .= '</div>';
        $html .= '<div class="cpc_projects_task_field">';
        $html .= '<label class="cpc_projects_task_field_label">'.esc_html__('Zuweisen an', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<select name="assigned_user_ids[]" multiple class="cpc_projects_task_assignees">';
        foreach ($assignable_labels as $uid => $label) {
            $html .= '<option value="'.(int)$uid.'">'.esc_html($label).'</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
        $html .= '</div>';
        $html .= '<div class="cpc_projects_task_hint">'.esc_html__('Zuweisung (Mehrfachauswahl moeglich)', CPC2_TEXT_DOMAIN).'</div>';
        $html .= '<label class="cpc_projects_task_field_label">'.esc_html__('Beschreibung', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<textarea name="description" rows="2" placeholder="'.esc_attr__('Kurzbeschreibung (optional)', CPC2_TEXT_DOMAIN).'"></textarea>';
        if (cpc_projects_task_comment_attachments_enabled()) {
            $html .= '<div class="cpc_projects_task_file_upload">';
            $html .= '<label class="cpc_projects_task_file_label">'.esc_html__('Datei anhaengen (optional)', CPC2_TEXT_DOMAIN).'</label>';
            $html .= '<input type="file" name="task_attachments[]" class="cpc_projects_task_files" multiple />';
            $html .= '</div>';
        }
        $html .= '</form>';
        $html .= '</details>';
    }

    $html .= '<div class="cpc_projects_tasks_list" data-project-id="'.(int)$project_id.'">';
    if (empty($tasks)) {
        $html .= '<p class="cpc_projects_no_tasks">'.esc_html__('Noch keine Tasks vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
    } else {
        $html .= '<div class="cpc_projects_tasks_items">';
        foreach ($tasks as $task) {
            $is_done = ((string)$task->status === 'done');
            $item_class = 'cpc_projects_task_item'.($is_done ? ' is-done' : '');
            $task_title_attr = strtolower(wp_strip_all_tags((string)$task->title));
            $task_assigned_csv = implode(',', array_map('intval', cpc_projects_get_task_assigned_user_ids($task)));
            $deadline_ts = !empty($task->deadline) ? (int)strtotime((string)$task->deadline) : 0;
            $is_overdue = ($deadline_ts > 0 && !$is_done && $deadline_ts < current_time('timestamp', 1)) ? '1' : '0';
            $html .= '<div id="cpc-project-task-'.(int)$task->id.'" class="'.esc_attr($item_class).'" data-task-id="'.(int)$task->id.'" data-task-status="'.esc_attr($is_done ? 'done' : 'open').'" data-task-title="'.esc_attr($task_title_attr).'" data-task-priority="'.(int)$task->priority.'" data-task-assigned="'.esc_attr($task_assigned_csv).'" data-task-overdue="'.esc_attr($is_overdue).'">';
            $html .= '<label class="cpc_projects_task_toggle_wrap">';
            if ($can_manage) {
                $html .= '<input class="cpc_projects_task_toggle" type="checkbox" '.checked($is_done, true, false).' data-task-id="'.(int)$task->id.'" />';
            } else {
                $html .= '<input type="checkbox" '.checked($is_done, true, false).' disabled />';
            }
            $html .= '<span class="cpc_projects_task_title">'.esc_html($task->title).'</span>';
            $html .= '</label>';
            $html .= '<span class="cpc_projects_task_priority priority-'.(int)$task->priority.'">'.esc_html(cpc_projects_render_task_priority_label($task->priority)).'</span>';
            $html .= '<span class="cpc_projects_task_status status-'.esc_attr($is_done ? 'done' : 'open').'">'.esc_html($is_done ? __('Erledigt', CPC2_TEXT_DOMAIN) : __('Offen', CPC2_TEXT_DOMAIN)).'</span>';

            $task_meta_bits = array();
            if (!empty($task->deadline)) {
                $deadline_ts_meta = (int)strtotime((string)$task->deadline);
                $now_ts_meta = current_time('timestamp', 1);
                $deadline_date_str = wp_date(get_option('date_format').' '.get_option('time_format'), $deadline_ts_meta);
                if ($deadline_ts_meta > $now_ts_meta && !$is_done) {
                    $countdown_str = sprintf(__('(%s verbleibend)', CPC2_TEXT_DOMAIN), human_time_diff($now_ts_meta, $deadline_ts_meta));
                } elseif ($deadline_ts_meta <= $now_ts_meta && !$is_done) {
                    $countdown_str = __('(UEBERFAELLIG)', CPC2_TEXT_DOMAIN);
                } else {
                    $countdown_str = '';
                }
                $task_meta_bits[] = sprintf(__('Faellig: %s', CPC2_TEXT_DOMAIN), $deadline_date_str.(($countdown_str !== '') ? ' '.$countdown_str : ''));
            }
            $task_assigned_ids = cpc_projects_get_task_assigned_user_ids($task);
            if (!empty($task_assigned_ids)) {
                $assigned_names = array();
                foreach ($task_assigned_ids as $assigned_id) {
                    $u = get_user_by('id', (int)$assigned_id);
                    if ($u) {
                        $assigned_names[] = $u->display_name;
                    }
                }
                if (!empty($assigned_names)) {
                    $task_meta_bits[] = sprintf(__('Zugewiesen: %s', CPC2_TEXT_DOMAIN), implode(', ', $assigned_names));
                }
            }
            if (!empty($task_meta_bits)) {
                $html .= '<div class="cpc_projects_task_meta">'.esc_html(implode(' | ', $task_meta_bits)).'</div>';
            }

            if (!empty($task->description)) {
                $html .= '<div class="cpc_projects_task_description">'.nl2br(esc_html((string)$task->description)).'</div>';
            }
            if ($can_manage) {
                $deadline_value = '';
                if (!empty($task->deadline)) {
                    $deadline_value = wp_date('Y-m-d\\TH:i', strtotime((string)$task->deadline));
                }

                $html .= '<div class="cpc_projects_task_actions">';
                $html .= '<button type="button" class="cpc_projects_task_delete cpc_projects_inline_link" data-task-id="'.(int)$task->id.'">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
                $html .= '<button type="button" class="cpc_projects_task_edit_toggle cpc_projects_inline_link" data-task-id="'.(int)$task->id.'">'.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</button>';
                $html .= '<button type="button" class="cpc_projects_task_details_toggle cpc_projects_inline_link" data-task-id="'.(int)$task->id.'">'.esc_html__('Details', CPC2_TEXT_DOMAIN).'</button>';
                $html .= '</div>';

                $html .= '<form class="cpc_projects_task_edit_form" data-project-id="'.(int)$project_id.'" data-task-id="'.(int)$task->id.'" style="display:none">';
                $html .= '<label class="cpc_projects_task_field_label">'.esc_html__('Titel', CPC2_TEXT_DOMAIN).'</label>';
                $html .= '<input type="text" name="title" value="'.esc_attr((string)$task->title).'" required />';
                $html .= '<label class="cpc_projects_task_field_label">'.esc_html__('Prioritaet', CPC2_TEXT_DOMAIN).'</label>';
                $html .= '<select name="priority">';
                $html .= '<option value="1" '.selected((int)$task->priority, 1, false).'>'.esc_html__('Normal', CPC2_TEXT_DOMAIN).'</option>';
                $html .= '<option value="2" '.selected((int)$task->priority, 2, false).'>'.esc_html__('Hoch', CPC2_TEXT_DOMAIN).'</option>';
                $html .= '<option value="3" '.selected((int)$task->priority, 3, false).'>'.esc_html__('Kritisch', CPC2_TEXT_DOMAIN).'</option>';
                $html .= '</select>';
                $html .= '<label class="cpc_projects_task_field_label">'.esc_html__('Deadline', CPC2_TEXT_DOMAIN).'</label>';
                $html .= '<input type="datetime-local" name="deadline" value="'.esc_attr($deadline_value).'" />';
                $html .= '<label class="cpc_projects_task_field_label">'.esc_html__('Zuweisen an', CPC2_TEXT_DOMAIN).'</label>';
                $html .= '<select name="assigned_user_ids[]" multiple class="cpc_projects_task_assignees">';
                foreach ($assignable_labels as $uid => $label) {
                    $is_selected = in_array((int)$uid, $task_assigned_ids, true);
                    $html .= '<option value="'.(int)$uid.'" '.selected($is_selected, true, false).'>'.esc_html($label).'</option>';
                }
                $html .= '</select>';
                $html .= '<div class="cpc_projects_task_hint">'.esc_html__('Zuweisung (Mehrfachauswahl moeglich)', CPC2_TEXT_DOMAIN).'</div>';
                $html .= '<label class="cpc_projects_task_field_label">'.esc_html__('Beschreibung', CPC2_TEXT_DOMAIN).'</label>';
                $html .= '<textarea name="description" rows="2">'.esc_textarea((string)$task->description).'</textarea>';
                if (cpc_projects_task_comment_attachments_enabled()) {
                    $html .= '<div class="cpc_projects_task_file_upload">';
                    $html .= '<label class="cpc_projects_task_file_label">'.esc_html__('Weitere Datei anhaengen (optional)', CPC2_TEXT_DOMAIN).'</label>';
                    $html .= '<input type="file" name="task_attachments[]" class="cpc_projects_task_files" multiple />';
                    $html .= '</div>';
                }
                $html .= '<button type="submit" class="cpc_projects_inline_link">'.esc_html__('Speichern', CPC2_TEXT_DOMAIN).'</button>';
                $html .= '</form>';
            }

            $comments = cpc_projects_get_task_comments($project_id, (int)$task->id, array('limit' => 30));

            $task_creator = get_user_by('id', (int)$task->user_id);
            $task_completer = !empty($task->completed_by) ? get_user_by('id', (int)$task->completed_by) : false;
            $task_events = cpc_projects_get_task_events($project_id, (int)$task->id, 20);

            if (!$can_manage) {
                $html .= '<div class="cpc_projects_task_actions">';
                $html .= '<button type="button" class="cpc_projects_task_details_toggle cpc_projects_inline_link" data-task-id="'.(int)$task->id.'">'.esc_html__('Details', CPC2_TEXT_DOMAIN).'</button>';
                $html .= '</div>';
            }
            $html .= '<div class="cpc_projects_task_details" data-task-id="'.(int)$task->id.'" style="display:none">';
            $html .= '<div class="cpc_projects_task_details_meta">';
            $html .= '<div><strong>'.esc_html__('Erstellt', CPC2_TEXT_DOMAIN).':</strong> '.esc_html(wp_date(get_option('date_format').' '.get_option('time_format'), strtotime((string)$task->date_added))).'</div>';
            $html .= '<div><strong>'.esc_html__('Zuletzt geaendert', CPC2_TEXT_DOMAIN).':</strong> '.esc_html(wp_date(get_option('date_format').' '.get_option('time_format'), strtotime((string)$task->date_updated))).'</div>';
            if ($task_creator) {
                $html .= '<div><strong>'.esc_html__('Ersteller', CPC2_TEXT_DOMAIN).':</strong> '.esc_html($task_creator->display_name).'</div>';
            }
            if ($task_completer) {
                $html .= '<div><strong>'.esc_html__('Abgeschlossen von', CPC2_TEXT_DOMAIN).':</strong> '.esc_html($task_completer->display_name).'</div>';
            }
            if (!empty($task_assigned_ids)) {
                $html .= '<div class="cpc_projects_task_assignee_avatars"><strong>'.esc_html__('Zugewiesen an', CPC2_TEXT_DOMAIN).':</strong>';
                foreach ($task_assigned_ids as $aid) {
                    $ua = get_user_by('id', (int)$aid);
                    if ($ua) {
                        $html .= '<span class="cpc_projects_task_assignee_chip">'.get_avatar($aid, 24).'<span>'.esc_html($ua->display_name).'</span></span>';
                    }
                }
                $html .= '</div>';
            }
            $html .= '<div><strong>'.esc_html__('Kommentare', CPC2_TEXT_DOMAIN).':</strong> '.(int)count($comments).'</div>';
            $html .= '</div>';

            $task_direct_attachments = cpc_projects_get_task_attachment_ids((int)$task->id);
            if (!empty($task_direct_attachments)) {
                $html .= '<div class="cpc_projects_task_direct_attachments">';
                $html .= '<h6>'.esc_html__('Dateianhaenge', CPC2_TEXT_DOMAIN).'</h6>';
                foreach ($task_direct_attachments as $att_id) {
                    $att_url  = wp_get_attachment_url($att_id);
                    if (!$att_url) { continue; }
                    $att_name = get_the_title($att_id);
                    if ($att_name === '') { $att_name = basename($att_url); }
                    $html .= '<div class="cpc_projects_task_direct_attachment_item">';
                    $html .= '<a class="cpc_projects_task_comment_attachment" href="'.esc_url($att_url).'" target="_blank" rel="noopener">'.esc_html($att_name).'</a>';
                    if ($can_manage) {
                        $html .= ' <button type="button" class="cpc_projects_task_direct_attachment_delete cpc_projects_inline_link" data-attachment-id="'.(int)$att_id.'">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
                    }
                    $html .= '</div>';
                }
                $html .= '</div>';
            }

            if (!empty($task_events)) {
                $html .= '<div class="cpc_projects_task_event_history">';
                $html .= '<h6>'.esc_html__('Verlauf', CPC2_TEXT_DOMAIN).'</h6>';
                $html .= '<ul>';
                foreach ($task_events as $event) {
                    $event_author = $event->comment_author ? $event->comment_author : __('Mitglied', CPC2_TEXT_DOMAIN);
                    $event_time = sprintf(__('vor %s', CPC2_TEXT_DOMAIN), human_time_diff(strtotime($event->comment_date_gmt), current_time('timestamp', 1)));
                    $html .= '<li><span class="cpc_projects_task_event_meta"><strong>'.esc_html($event_author).'</strong> - '.esc_html($event_time).'</span> '.esc_html(wp_strip_all_tags((string)$event->comment_content)).'</li>';
                }
                $html .= '</ul>';
                $html .= '</div>';
            }
            $html .= '</div>';

            $html .= '<div class="cpc_projects_task_comments" data-task-id="'.(int)$task->id.'">';
            $html .= '<h6 class="cpc_projects_discussion_heading">'.esc_html__('Diskussion', CPC2_TEXT_DOMAIN).'</h6>';
            if (!empty($comments)) {
                $html .= '<ul class="cpc_projects_task_comment_list">';
                foreach ($comments as $comment) {
                    $author_name = $comment->comment_author ? $comment->comment_author : __('Mitglied', CPC2_TEXT_DOMAIN);
                    $time = sprintf(__('vor %s', CPC2_TEXT_DOMAIN), human_time_diff(strtotime($comment->comment_date_gmt), current_time('timestamp', 1)));
                    $html .= '<li class="cpc_projects_task_comment_item">';
                    $html .= '<div class="cpc_projects_task_comment_layout">';
                    $html .= '<div class="cpc_projects_task_comment_avatar">'.get_avatar((int)$comment->user_id, 34).'</div>';
                    $html .= '<div class="cpc_projects_task_comment_content">';
                    $current_uid_comment = get_current_user_id();
                    $can_delete_comment  = $can_manage || ((int)$comment->user_id === $current_uid_comment && $current_uid_comment > 0);
                    $html .= '<div class="cpc_projects_task_comment_meta">';
                    $html .= '<span><strong>'.esc_html($author_name).'</strong> <span>'.esc_html($time).'</span></span>';
                    if ($can_delete_comment) {
                        $html .= '<button type="button" class="cpc_projects_comment_delete cpc_projects_inline_link" data-comment-id="'.(int)$comment->comment_ID.'">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
                    }
                    $html .= '</div>';
                    $html .= '<div class="cpc_projects_task_comment_text">'.esc_html(wp_strip_all_tags((string)$comment->comment_content)).'</div>';

                    $attachment_ids = cpc_projects_get_comment_attachment_ids((int)$comment->comment_ID);
                    if (!empty($attachment_ids)) {
                        $html .= '<div class="cpc_projects_task_comment_attachments">';
                        foreach ($attachment_ids as $attachment_id) {
                            $file_url = wp_get_attachment_url($attachment_id);
                            if (!$file_url) {
                                continue;
                            }
                            $file_name = get_the_title($attachment_id);
                            if ($file_name === '') {
                                $file_name = basename((string)$file_url);
                            }
                            $html .= '<a class="cpc_projects_task_comment_attachment" href="'.esc_url($file_url).'" target="_blank" rel="noopener">'.esc_html($file_name).'</a>';
                            if (cpc_projects_user_can_delete_comment_attachment($attachment_id, get_current_user_id())) {
                                $html .= '<button type="button" class="cpc_projects_comment_attachment_delete cpc_projects_inline_link" data-attachment-id="'.(int)$attachment_id.'">'.esc_html__('Datei loeschen', CPC2_TEXT_DOMAIN).'</button>';
                            }
                        }
                        $html .= '</div>';
                    }

                    $html .= '</div>';
                    $html .= '</div>';
                    $html .= '</li>';
                }
                $html .= '</ul>';
            }

            if (is_user_logged_in() && cpc_projects_user_can_view_project($project_id)) {
                $html .= '<form method="post" action="" class="cpc_projects_task_comment_form" data-project-id="'.(int)$project_id.'" data-task-id="'.(int)$task->id.'">';
                $html .= '<textarea name="comment" rows="2" maxlength="1500" placeholder="'.esc_attr__('Kommentar schreiben...', CPC2_TEXT_DOMAIN).'" required></textarea>';
                if (cpc_projects_task_comment_attachments_enabled()) {
                    $html .= '<input type="file" name="attachments[]" class="cpc_projects_task_comment_files" multiple />';
                }
                $html .= '<button type="submit" class="cpc_projects_inline_link">'.esc_html__('Kommentieren', CPC2_TEXT_DOMAIN).'</button>';
                $html .= '</form>';
            }
            $html .= '</div>';

            $html .= '</div>';
        }
        $html .= '</div>';
        $html .= '<p class="cpc_projects_no_tasks cpc_projects_no_tasks_filtered" style="display:none">'.esc_html__('Keine passenden Tasks gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    }
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function cpc_projects_render_project_progress($project_id) {
    $stats = cpc_projects_get_task_progress($project_id);
    if ((int)$stats['total'] <= 0) {
        return '';
    }

    $html = '';
    $html .= '<div class="cpc_projects_progress">';
    $html .= '<div class="cpc_projects_progress_bar">';
    $html .= '<span class="cpc_projects_progress_fill" style="width:'.(int)$stats['progress'].'%"></span>';
    $html .= '</div>';
    $html .= '<div class="cpc_projects_progress_meta">';
    $html .= sprintf(
        esc_html__('%1$s Tasks, %2$s%% erledigt (%3$s offen)', CPC2_TEXT_DOMAIN),
        (int)$stats['total'],
        (int)$stats['progress'],
        (int)$stats['remaining']
    );
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function cpc_projects_render_project_owner_line($project) {
    $project = get_post($project);
    if (!$project) {
        return '';
    }

    $author_id = (int)$project->post_author;
    $author = $author_id > 0 ? get_user_by('id', $author_id) : false;
    $author_name = $author ? $author->display_name : __('Mitglied', CPC2_TEXT_DOMAIN);
    $author_url = $author_id > 0 ? get_author_posts_url($author_id) : '';

    $line = '';
    $line .= '<div class="cpc_projects_item_owner">';
    $line .= '<span>'.esc_html__('Started by', CPC2_TEXT_DOMAIN).' </span>';
    if ($author_url !== '') {
        $line .= '<a href="'.esc_url($author_url).'">'.get_avatar($author_id, 24).'<span>'.esc_html($author_name).'</span></a>';
    } else {
        $line .= '<span>'.esc_html($author_name).'</span>';
    }

    $component = cpc_projects_get_component($project->ID);
    $component_id = cpc_projects_get_component_id($project->ID);
    if ($component === 'groups' && $component_id > 0) {
        $group = get_post($component_id);
        if ($group && $group->post_type === 'cpc_group') {
            $line .= '<span class="cpc_projects_item_owner_sep">'.esc_html__('under', CPC2_TEXT_DOMAIN).' </span>';
            $group_link = function_exists('cpc_get_group_link') ? cpc_get_group_link($group->ID) : '';
            if ($group_link !== '') {
                $line .= '<a href="'.esc_url($group_link).'"><span>'.esc_html($group->post_title).'</span></a>';
            } else {
                $line .= '<span>'.esc_html($group->post_title).'</span>';
            }
        }
    }

    $line .= '</div>';

    return $line;
}

function cpc_projects_render_profile_summary($projects, $user_id) {
    $user = get_user_by('id', (int)$user_id);
    $name = $user ? $user->display_name : __('Mitglied', CPC2_TEXT_DOMAIN);

    $total = is_array($projects) ? count($projects) : 0;
    $group_ids = array();
    if (!empty($projects)) {
        foreach ($projects as $project) {
            $component = cpc_projects_get_component($project->ID);
            if ($component === 'groups') {
                $group_id = cpc_projects_get_component_id($project->ID);
                if ($group_id > 0) {
                    $group_ids[] = (int)$group_id;
                }
            }
        }
    }

    $group_count = count(array_unique($group_ids));

    return '<p id="group-projects-explainer" class="cpc_projects_profile_summary mg-top-15 no-mg-bottom">'.sprintf(
        esc_html__('Es wurden %1$s Projekte in %2$s Gruppen fuer %3$s gefunden.', CPC2_TEXT_DOMAIN),
        '<strong>'.(int)$total.'</strong>',
        '<strong>'.(int)$group_count.'</strong>',
        '<strong>'.esc_html($name).'</strong>'
    ).'</p>';
}

function cpc_projects_render_projects_list($projects, $args = array()) {
    $args = wp_parse_args($args, array(
        'show_tasks'     => true,
        'page'           => 1,
        'per_page'       => 10,
        'pagination_url' => '',
    ));

    if (empty($projects)) {
        return '<p id="message" class="info">'.esc_html__('Derzeit wurden keine Projekte gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    }

    $page        = max(1, (int)$args['page']);
    $per_page    = max(1, (int)$args['per_page']);
    $total       = count($projects);
    $total_pages = (int)ceil($total / $per_page);
    $offset      = ($page - 1) * $per_page;
    $page_projs  = array_slice($projects, $offset, $per_page);

    $html = '<ul id="task_breaker-projects-lists" class="cpc_projects_list">';

    foreach ($page_projs as $project) {
        $excerpt = wp_trim_words(wp_strip_all_tags((string)$project->post_content), 24);
        $html .= '<li class="cpc_projects_item taskbreaker-project-item">';
        $html .= '<div class="cpc_projects_item_wrap taskbreaker-project-item-wrap">';
        $html .= '<div class="task_breaker-project-title"><h4 class="cpc_projects_item_title"><a href="'.esc_url(cpc_projects_get_project_url($project->ID)).'">'.esc_html($project->post_title).'</a></h4></div>';
        $html .= '<div class="task_breaker-project-meta">'.cpc_projects_render_project_progress($project->ID).'</div>';
        if ($excerpt !== '') {
            $html .= '<p class="cpc_projects_item_excerpt task_breaker-project-excerpt">'.esc_html($excerpt).'</p>';
        }
        $html .= '<div class="cpc_projects_item_meta">'.esc_html(get_the_date('', $project)).'</div>';
        $html .= '<div class="task_breaker-project-author">'.cpc_projects_render_project_owner_line($project).'</div>';
        $html .= '<div class="cpc_projects_item_actions"><a class="cpc_button" href="'.esc_url(cpc_projects_get_project_url($project->ID)).'">'.esc_html__('Projekt oeffnen', CPC2_TEXT_DOMAIN).'</a></div>';
        if (!empty($args['show_tasks'])) {
            $html .= cpc_projects_render_task_panel($project->ID);
        }
        $html .= '</div>';
        $html .= '</li>';
    }

    $html .= '</ul>';

    if ($total_pages > 1) {
        $base_url = $args['pagination_url'] ? $args['pagination_url'] : cpc_curPageURL();
        $html .= '<nav class="cpc_projects_pagination">';
        for ($i = 1; $i <= $total_pages; $i++) {
            $page_url   = add_query_arg('cpc_paged', $i, $base_url);
            $active_cls = ($i === $page) ? ' is-active' : '';
            $html .= '<a class="cpc_projects_page_link'.esc_attr($active_cls).'" href="'.esc_url($page_url).'">'.(int)$i.'</a>';
        }
        $html .= '</nav>';
    }

    return $html;
}

function cpc_projects_render_profile_tab_content($html, $active_tab, $user_id, $shortcode_atts) {
    if ($active_tab !== 'projects' || !cpc_projects_is_enabled()) {
        return $html;
    }

    $user_id = (int)$user_id;
    if (!$user_id) {
        return '<p>'.esc_html__('Benutzer nicht gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    }

    $projects = cpc_projects_get_profile_projects($user_id, array(
        'posts_per_page' => 120,
        'viewer_id' => get_current_user_id(),
    ));

    $html = '';
    $html .= cpc_projects_render_notice_html();
    $html .= '<div class="cpc_projects_profile_tab">';
    $html .= cpc_projects_render_profile_summary($projects, $user_id);
    if (cpc_projects_show_profile_create_form()) {
        $html .= cpc_projects_render_create_form('members', $user_id);
    }
    $paged   = isset($_GET['cpc_paged']) ? max(1, (int)$_GET['cpc_paged']) : 1;
    $tab_url = add_query_arg('cpc_projects_tab', 'projects', cpc_curPageURL());
    $html .= cpc_projects_render_projects_list($projects, array(
        'show_tasks'     => false,
        'page'           => $paged,
        'per_page'       => 10,
        'pagination_url' => $tab_url,
    ));

    if (get_current_user_id() === $user_id) {
        $notify_task    = get_user_meta($user_id, 'cpc_projects_notify_task',    true);
        $notify_comment = get_user_meta($user_id, 'cpc_projects_notify_comment', true);
        $notify_task    = ($notify_task    === '') ? 1 : (int)$notify_task;
        $notify_comment = ($notify_comment === '') ? 1 : (int)$notify_comment;

        $html .= '<div class="cpc_projects_notification_prefs">';
        $html .= '<h5>'.esc_html__('E-Mail-Benachrichtigungen', CPC2_TEXT_DOMAIN).'</h5>';
        $html .= '<form class="cpc_projects_notification_prefs_form">';
        $html .= '<div class="cpc_projects_prefs_notice" style="display:none"></div>';
        $html .= '<label class="cpc_projects_pref_label"><input type="checkbox" name="notify_task" value="1" '.checked($notify_task, 1, false).' /> '.esc_html__('Bei neuer Aufgabe benachrichtigen', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<label class="cpc_projects_pref_label"><input type="checkbox" name="notify_comment" value="1" '.checked($notify_comment, 1, false).' /> '.esc_html__('Bei neuem Kommentar benachrichtigen', CPC2_TEXT_DOMAIN).'</label>';
        $html .= '<p><button type="submit" class="cpc_button">'.esc_html__('Einstellungen speichern', CPC2_TEXT_DOMAIN).'</button></p>';
        $html .= '</form>';
        $html .= '</div>';
    }

    $html .= '</div>';

    return $html;
}
add_filter('cpc_profile_tab_content', 'cpc_projects_render_profile_tab_content', 20, 4);

function cpc_projects_render_group_tab_content($html, $group_id, $shortcode_atts) {
    if (!cpc_projects_is_enabled()) {
        return $html;
    }

    $group_id = (int)$group_id;
    if (!$group_id) {
        return '<p>'.esc_html__('Gruppe nicht gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    }

    if (function_exists('cpc_can_view_group') && !cpc_can_view_group(get_current_user_id(), $group_id)) {
        return '<p>'.esc_html__('Keine Berechtigung.', CPC2_TEXT_DOMAIN).'</p>';
    }

    // — Einzelprojekt-Detailansicht —
    $single_project_id = isset($_GET['cpc_project_id']) ? (int)$_GET['cpc_project_id'] : 0;
    if ($single_project_id > 0) {
        // Sicherheitscheck: Projekt muss wirklich zu dieser Gruppe gehoeren
        $component    = cpc_projects_get_component($single_project_id);
        $component_id = cpc_projects_get_component_id($single_project_id);
        if ($component === 'groups' && $component_id === $group_id) {
            $back_url = add_query_arg(
                'tab', 'projects',
                remove_query_arg(array('cpc_project_id', 'cpc_paged'), function_exists('cpc_get_group_link') ? cpc_get_group_link($group_id) : cpc_curPageURL())
            );
            $html = '';
            $html .= cpc_projects_render_notice_html();
            $html .= '<div class="cpc_projects_group_tab">';
            $html .= cpc_projects_render_single_project_html($single_project_id, array('back_url' => $back_url));
            $html .= '</div>';
            return $html;
        }
    }

    // — Projektliste —
    $projects = cpc_projects_get_projects(array(
        'component' => 'groups',
        'component_id' => $group_id,
        'posts_per_page' => 120,
    ));

    $html = '';
    $html .= cpc_projects_render_notice_html();
    $html .= '<div class="cpc_projects_group_tab">';
    $html .= '<h3>'.esc_html__('Gruppen-Projekte', CPC2_TEXT_DOMAIN).'</h3>';
    $html .= cpc_projects_render_create_form('groups', $group_id);
    $paged_g   = isset($_GET['cpc_paged']) ? max(1, (int)$_GET['cpc_paged']) : 1;
    $tab_url_g = add_query_arg('tab', 'projects', remove_query_arg(array('cpc_paged', 'cpc_project_id'), function_exists('cpc_get_group_link') ? cpc_get_group_link($group_id) : cpc_curPageURL()));
    $html .= cpc_projects_render_projects_list($projects, array(
        'show_tasks'     => false,
        'page'           => $paged_g,
        'per_page'       => 10,
        'pagination_url' => $tab_url_g,
    ));
    $html .= '</div>';

    return $html;
}

function cpc_projects_directory_shortcode($atts) {
    $atts = shortcode_atts(array(
        'per_page' => cpc_projects_get_directory_items_per_page(),
    ), $atts, 'cpc-projects-directory');

    $page = isset($_GET['cpc_projects_page']) ? max(1, (int)$_GET['cpc_projects_page']) : 1;
    $per_page = max(6, min(60, (int)$atts['per_page']));
    $search = isset($_GET['cpc_projects_q']) ? sanitize_text_field(wp_unslash($_GET['cpc_projects_q'])) : '';

    $html = '<div class="cpc_projects_directory">';
    $html .= '<div class="cpc_projects_directory_header">';
    $html .= '<h3 class="cpc_projects_directory_title">'.esc_html(cpc_projects_get_directory_title()).'</h3>';
    $html .= cpc_projects_render_directory_filters($search);
    $html .= '</div>';

    $results = cpc_projects_directory_get_results($search, $page, $per_page);

    if (empty($results['items'])) {
        $html .= '<p>'.esc_html__('Keine Projekte gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    } else {
        $html .= cpc_projects_render_directory_list($results['items']);
        if ($results['has_more'] || $page > 1) {
            $html .= cpc_projects_render_directory_pagination($results, $page, $per_page, $search);
        }
    }

    $html .= '</div>';

    return $html;
}
add_shortcode('cpc-projects-directory', 'cpc_projects_directory_shortcode');
add_shortcode('cpc-project-directory', 'cpc_projects_directory_shortcode');

function cpc_projects_directory_get_results($search = '', $page = 1, $per_page = 12) {
    $offset = max(0, ($page - 1) * $per_page);
    $visible = array();
    $current_user_id = get_current_user_id();

    $args = array(
        'posts_per_page' => $per_page * 3,
        'offset' => $offset,
        'orderby' => 'date',
        'order' => 'DESC',
    );

    if ($search !== '') {
        $args['s'] = $search;
    }

    $all_projects = cpc_projects_get_projects($args);
    if (empty($all_projects)) {
        return array('items' => array(), 'has_more' => false);
    }

    foreach ($all_projects as $project) {
        if (!cpc_projects_user_can_view_project($project->ID, $current_user_id)) {
            continue;
        }
        $visible[] = $project;
        if (count($visible) >= $per_page) {
            break;
        }
    }

    return array(
        'items' => $visible,
        'has_more' => count($all_projects) >= $per_page * 3,
    );
}

function cpc_projects_render_directory_filters($search = '') {
    $base_url = cpc_projects_directory_get_base_url();
    $html = '<form method="get" action="'.esc_url($base_url).'" class="cpc_projects_directory_filters">';
    $html .= '<input type="search" name="cpc_projects_q" value="'.esc_attr($search).'" placeholder="'.esc_attr__('Titel suchen...', CPC2_TEXT_DOMAIN).'" />';
    $html .= '<button type="submit" class="cpc_button">'.esc_html__('Suchen', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '</form>';
    return $html;
}

function cpc_projects_render_directory_list($projects) {
    if (empty($projects)) {
        return '';
    }

    $html = '<ul id="cpc_projects_directory_list" class="cpc_projects_list cpc_projects_directory_list">';
    foreach ($projects as $project) {
        $excerpt = wp_trim_words(wp_strip_all_tags((string)$project->post_content), 24);
        $status = cpc_projects_get_status($project->ID);
        $status_label = '';
        
        if ($status === 'members') {
            $status_label = __('Nur Mitglieder', CPC2_TEXT_DOMAIN);
        } elseif ($status === 'private') {
            $status_label = __('Privat', CPC2_TEXT_DOMAIN);
        }

        $html .= '<li class="cpc_projects_item cpc_projects_directory_item">';
        $html .= '<div class="cpc_projects_item_wrap">';
        $html .= '<div class="task_breaker-project-title"><h4 class="cpc_projects_item_title"><a href="'.esc_url(cpc_projects_get_project_url($project->ID)).'">'.esc_html($project->post_title).'</a></h4></div>';
        
        if ($excerpt !== '') {
            $html .= '<p class="cpc_projects_item_excerpt">'.esc_html($excerpt).'</p>';
        }
        
        if ($status_label !== '') {
            $html .= '<span class="cpc_projects_directory_status">'.esc_html($status_label).'</span>';
        }
        
        $html .= '<div class="cpc_projects_item_meta">'.esc_html(get_the_date('', $project)).'</div>';
        $html .= '<div class="task_breaker-project-author">'.cpc_projects_render_project_owner_line($project).'</div>';
        $html .= '<div class="cpc_projects_item_actions"><a class="cpc_button" href="'.esc_url(cpc_projects_get_project_url($project->ID)).'">'.esc_html__('Projekt oeffnen', CPC2_TEXT_DOMAIN).'</a></div>';
        $html .= '</div>';
        $html .= '</li>';
    }
    $html .= '</ul>';

    return $html;
}

function cpc_projects_render_directory_pagination($results, $page, $per_page, $search = '') {
    $base_url = cpc_projects_directory_get_base_url();
    if ($search !== '') {
        $base_url = add_query_arg('cpc_projects_q', urlencode($search), $base_url);
    }

    $html = '<div class="cpc_projects_directory_pagination">';
    if ($page > 1) {
        $html .= '<a class="cpc_button" href="'.esc_url(add_query_arg('cpc_projects_page', $page - 1, $base_url)).'">← '.esc_html__('Vorherige', CPC2_TEXT_DOMAIN).'</a>';
    }
    $html .= '<span class="cpc_projects_directory_page_label">'.sprintf(esc_html__('Seite %d', CPC2_TEXT_DOMAIN), $page).'</span>';
    if ($results['has_more']) {
        $html .= '<a class="cpc_button" href="'.esc_url(add_query_arg('cpc_projects_page', $page + 1, $base_url)).'">'.esc_html__('Nächste', CPC2_TEXT_DOMAIN).' →</a>';
    }
    $html .= '</div>';
    return $html;
}

function cpc_projects_directory_get_base_url() {
    $page_id = cpc_projects_get_directory_page_id();
    if ($page_id > 0) {
        return get_permalink($page_id);
    }
    return cpc_curPageURL();
}

function cpc_projects_render_directory_page_content($content) {
    if (!cpc_projects_is_enabled() || !is_page() || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $page_id = cpc_projects_get_directory_page_id();
    if (!$page_id || (int)get_queried_object_id() !== $page_id) {
        return $content;
    }

    global $post;
    if ($post && (has_shortcode((string)$post->post_content, 'cpc-projects-directory') || has_shortcode((string)$post->post_content, 'cpc-project-directory'))) {
        return $content;
    }

    $directory = cpc_projects_directory_shortcode(array());
    if (trim((string)$content) === '') {
        return $directory;
    }

    return $content.$directory;
}
add_filter('the_content', 'cpc_projects_render_directory_page_content', 25);

add_filter('cpc_group_tab_content_projects', 'cpc_projects_render_group_tab_content', 20, 3);
