<?php

function cpc_projects_add_profile_tab($tabs, $user_id, $viewer_id) {
    if (!cpc_projects_is_enabled()) {
        return $tabs;
    }

    $tabs['projects'] = array(
        'label' => cpc_projects_get_user_tab_name(),
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

function cpc_projects_render_task_panel($project_id) {
    $project_id = (int)$project_id;
    if (!$project_id || !cpc_projects_user_can_view_project($project_id)) {
        return '';
    }

    $can_manage = cpc_projects_user_can_manage_project($project_id);
    $tasks = cpc_projects_get_tasks($project_id, array('limit' => 300));
    $assignable_users = cpc_projects_get_assignable_users($project_id);

    $html = '';
    $html .= '<div class="cpc_projects_task_panel" data-project-id="'.(int)$project_id.'">';
    $html .= '<h5 class="cpc_projects_task_heading">'.esc_html__('Tasks', CPC2_TEXT_DOMAIN).'</h5>';

    $html .= '<div class="cpc_projects_task_filters">';
    $html .= '<input type="text" class="cpc_projects_task_filter_text" placeholder="'.esc_attr__('Tasks durchsuchen...', CPC2_TEXT_DOMAIN).'" />';
    $html .= '<select class="cpc_projects_task_filter_status">';
    $html .= '<option value="all">'.esc_html__('Alle', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="open">'.esc_html__('Offen', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="done">'.esc_html__('Erledigt', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '</select>';
    $html .= '<select class="cpc_projects_task_filter_priority">';
    $html .= '<option value="all">'.esc_html__('Alle Prioritaeten', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="1">'.esc_html__('Normal', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="2">'.esc_html__('Hoch', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '<option value="3">'.esc_html__('Kritisch', CPC2_TEXT_DOMAIN).'</option>';
    $html .= '</select>';
    $html .= '<select class="cpc_projects_task_filter_assignee">';
    $html .= '<option value="all">'.esc_html__('Alle Zuweisungen', CPC2_TEXT_DOMAIN).'</option>';
    foreach ($assignable_users as $assign_user) {
        $html .= '<option value="'.(int)$assign_user->ID.'">'.esc_html($assign_user->display_name).'</option>';
    }
    $html .= '</select>';
    $html .= '<label class="cpc_projects_task_filter_overdue_wrap"><input type="checkbox" class="cpc_projects_task_filter_overdue" value="1" /> '.esc_html__('Nur ueberfaellig', CPC2_TEXT_DOMAIN).'</label>';
    $html .= '</div>';

    if ($can_manage) {
        $html .= '<form class="cpc_projects_task_form" data-project-id="'.(int)$project_id.'">';
        $html .= '<div class="cpc_projects_task_form_row">';
        $html .= '<input type="text" name="title" placeholder="'.esc_attr__('Task-Titel', CPC2_TEXT_DOMAIN).'" required />';
        $html .= '<select name="priority">';
        $html .= '<option value="1">'.esc_html__('Normal', CPC2_TEXT_DOMAIN).'</option>';
        $html .= '<option value="2">'.esc_html__('Hoch', CPC2_TEXT_DOMAIN).'</option>';
        $html .= '<option value="3">'.esc_html__('Kritisch', CPC2_TEXT_DOMAIN).'</option>';
        $html .= '</select>';
        $html .= '<button type="submit" class="cpc_button cpc_projects_add_task_btn">'.esc_html__('Task hinzufuegen', CPC2_TEXT_DOMAIN).'</button>';
        $html .= '</div>';
        $html .= '<div class="cpc_projects_task_form_row cpc_projects_task_form_row_secondary">';
        $html .= '<input type="datetime-local" name="deadline" placeholder="'.esc_attr__('Deadline', CPC2_TEXT_DOMAIN).'" />';
        $html .= '<select name="assigned_user_ids[]" multiple class="cpc_projects_task_assignees">';
        foreach ($assignable_users as $assign_user) {
            $html .= '<option value="'.(int)$assign_user->ID.'">'.esc_html($assign_user->display_name).'</option>';
        }
        $html .= '</select>';
        $html .= '</div>';
        $html .= '<textarea name="description" rows="2" placeholder="'.esc_attr__('Kurzbeschreibung (optional)', CPC2_TEXT_DOMAIN).'"></textarea>';
        $html .= '</form>';
    }

    $html .= '<div class="cpc_projects_tasks_list" data-project-id="'.(int)$project_id.'">';
    if (empty($tasks)) {
        $html .= '<p class="cpc_projects_no_tasks">'.esc_html__('Noch keine Tasks vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
    } else {
        $html .= '<ul>';
        foreach ($tasks as $task) {
            $is_done = ((string)$task->status === 'done');
            $item_class = 'cpc_projects_task_item'.($is_done ? ' is-done' : '');
            $task_title_attr = strtolower(wp_strip_all_tags((string)$task->title));
            $task_assigned_csv = implode(',', array_map('intval', cpc_projects_get_task_assigned_user_ids($task)));
            $deadline_ts = !empty($task->deadline) ? (int)strtotime((string)$task->deadline) : 0;
            $is_overdue = ($deadline_ts > 0 && !$is_done && $deadline_ts < current_time('timestamp', 1)) ? '1' : '0';
            $html .= '<li id="cpc-project-task-'.(int)$task->id.'" class="'.esc_attr($item_class).'" data-task-id="'.(int)$task->id.'" data-task-status="'.esc_attr($is_done ? 'done' : 'open').'" data-task-title="'.esc_attr($task_title_attr).'" data-task-priority="'.(int)$task->priority.'" data-task-assigned="'.esc_attr($task_assigned_csv).'" data-task-overdue="'.esc_attr($is_overdue).'">';
            $html .= '<label class="cpc_projects_task_toggle_wrap">';
            if ($can_manage) {
                $html .= '<input class="cpc_projects_task_toggle" type="checkbox" '.checked($is_done, true, false).' data-task-id="'.(int)$task->id.'" />';
            } else {
                $html .= '<input type="checkbox" '.checked($is_done, true, false).' disabled />';
            }
            $html .= '<span class="cpc_projects_task_title">'.esc_html($task->title).'</span>';
            $html .= '</label>';
            $html .= '<span class="cpc_projects_task_priority priority-'.(int)$task->priority.'">'.esc_html(cpc_projects_render_task_priority_label($task->priority)).'</span>';

            $task_meta_bits = array();
            if (!empty($task->deadline)) {
                $task_meta_bits[] = sprintf(__('Faellig: %s', CPC2_TEXT_DOMAIN), wp_date(get_option('date_format').' '.get_option('time_format'), strtotime((string)$task->deadline)));
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
                $html .= '<div class="cpc_projects_task_description">'.esc_html(wp_trim_words(wp_strip_all_tags((string)$task->description), 22)).'</div>';
            }
            if ($can_manage) {
                $deadline_value = '';
                if (!empty($task->deadline)) {
                    $deadline_value = wp_date('Y-m-d\\TH:i', strtotime((string)$task->deadline));
                }

                $html .= '<button type="button" class="cpc_projects_task_delete cpc_projects_inline_link" data-task-id="'.(int)$task->id.'">'.esc_html__('Loeschen', CPC2_TEXT_DOMAIN).'</button>';
                $html .= ' <button type="button" class="cpc_projects_task_edit_toggle cpc_projects_inline_link" data-task-id="'.(int)$task->id.'">'.esc_html__('Bearbeiten', CPC2_TEXT_DOMAIN).'</button>';

                $html .= '<form class="cpc_projects_task_edit_form" data-project-id="'.(int)$project_id.'" data-task-id="'.(int)$task->id.'" style="display:none">';
                $html .= '<input type="text" name="title" value="'.esc_attr((string)$task->title).'" required />';
                $html .= '<select name="priority">';
                $html .= '<option value="1" '.selected((int)$task->priority, 1, false).'>'.esc_html__('Normal', CPC2_TEXT_DOMAIN).'</option>';
                $html .= '<option value="2" '.selected((int)$task->priority, 2, false).'>'.esc_html__('Hoch', CPC2_TEXT_DOMAIN).'</option>';
                $html .= '<option value="3" '.selected((int)$task->priority, 3, false).'>'.esc_html__('Kritisch', CPC2_TEXT_DOMAIN).'</option>';
                $html .= '</select>';
                $html .= '<input type="datetime-local" name="deadline" value="'.esc_attr($deadline_value).'" />';
                $html .= '<select name="assigned_user_ids[]" multiple class="cpc_projects_task_assignees">';
                foreach ($assignable_users as $assign_user) {
                    $is_selected = in_array((int)$assign_user->ID, $task_assigned_ids, true);
                    $html .= '<option value="'.(int)$assign_user->ID.'" '.selected($is_selected, true, false).'>'.esc_html($assign_user->display_name).'</option>';
                }
                $html .= '</select>';
                $html .= '<textarea name="description" rows="2">'.esc_textarea((string)$task->description).'</textarea>';
                $html .= '<button type="submit" class="cpc_projects_inline_link">'.esc_html__('Speichern', CPC2_TEXT_DOMAIN).'</button>';
                $html .= '</form>';
            }

            $comments = cpc_projects_get_task_comments($project_id, (int)$task->id, array('limit' => 30));

            $task_creator = get_user_by('id', (int)$task->user_id);
            $task_completer = !empty($task->completed_by) ? get_user_by('id', (int)$task->completed_by) : false;
            $task_events = cpc_projects_get_task_events($project_id, (int)$task->id, 20);

            $html .= '<button type="button" class="cpc_projects_task_details_toggle cpc_projects_inline_link" data-task-id="'.(int)$task->id.'">'.esc_html__('Details', CPC2_TEXT_DOMAIN).'</button>';
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
            $html .= '<div><strong>'.esc_html__('Kommentare', CPC2_TEXT_DOMAIN).':</strong> '.(int)count($comments).'</div>';
            $html .= '</div>';

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
            if (!empty($comments)) {
                $html .= '<ul class="cpc_projects_task_comment_list">';
                foreach ($comments as $comment) {
                    $author_name = $comment->comment_author ? $comment->comment_author : __('Mitglied', CPC2_TEXT_DOMAIN);
                    $time = sprintf(__('vor %s', CPC2_TEXT_DOMAIN), human_time_diff(strtotime($comment->comment_date_gmt), current_time('timestamp', 1)));
                    $html .= '<li class="cpc_projects_task_comment_item">';
                    $html .= '<div class="cpc_projects_task_comment_meta"><strong>'.esc_html($author_name).'</strong> <span>'.esc_html($time).'</span></div>';
                    $html .= '<div class="cpc_projects_task_comment_text">'.esc_html(wp_trim_words(wp_strip_all_tags((string)$comment->comment_content), 45)).'</div>';

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

                    $html .= '</li>';
                }
                $html .= '</ul>';
            }

            if (is_user_logged_in() && cpc_projects_user_can_view_project($project_id)) {
                $html .= '<form class="cpc_projects_task_comment_form" data-project-id="'.(int)$project_id.'" data-task-id="'.(int)$task->id.'">';
                $html .= '<textarea name="comment" rows="2" maxlength="1500" placeholder="'.esc_attr__('Kommentar schreiben...', CPC2_TEXT_DOMAIN).'" required></textarea>';
                if (cpc_projects_task_comment_attachments_enabled()) {
                    $html .= '<input type="file" name="attachments[]" class="cpc_projects_task_comment_files" multiple />';
                }
                $html .= '<button type="submit" class="cpc_projects_inline_link">'.esc_html__('Kommentieren', CPC2_TEXT_DOMAIN).'</button>';
                $html .= '</form>';
            }
            $html .= '</div>';

            $html .= '</li>';
        }
        $html .= '</ul>';
        $html .= '<p class="cpc_projects_no_tasks cpc_projects_no_tasks_filtered" style="display:none">'.esc_html__('Keine passenden Tasks gefunden.', CPC2_TEXT_DOMAIN).'</p>';
    }
    $html .= '</div>';
    $html .= '</div>';

    return $html;
}

function cpc_projects_render_projects_list($projects) {
    if (empty($projects)) {
        return '<p>'.esc_html__('Noch keine Projekte vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
    }

    $html = '<div class="cpc_projects_list">';

    foreach ($projects as $project) {
        $excerpt = wp_trim_words(wp_strip_all_tags((string)$project->post_content), 24);
        $html .= '<article class="cpc_projects_item">';
        $html .= '<h4 class="cpc_projects_item_title"><a href="'.esc_url(get_permalink($project)).'">'.esc_html($project->post_title).'</a></h4>';
        if ($excerpt !== '') {
            $html .= '<p class="cpc_projects_item_excerpt">'.esc_html($excerpt).'</p>';
        }
        $html .= '<div class="cpc_projects_item_meta">'.esc_html(get_the_date('', $project)).'</div>';
        $html .= cpc_projects_render_task_panel($project->ID);
        $html .= '</article>';
    }

    $html .= '</div>';

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
    $html .= '<h3>'.esc_html__('Profil-Projekte', CPC2_TEXT_DOMAIN).'</h3>';
    if (cpc_projects_show_profile_create_form()) {
        $html .= cpc_projects_render_create_form('members', $user_id);
    }
    $html .= cpc_projects_render_projects_list($projects);
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
    $html .= cpc_projects_render_projects_list($projects);
    $html .= '</div>';

    return $html;
}
add_filter('cpc_group_tab_content_projects', 'cpc_projects_render_group_tab_content', 20, 3);
