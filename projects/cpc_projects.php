<?php

require_once(__DIR__.'/lib_projects.php');
require_once(__DIR__.'/cpc_custom_post_project.php');
require_once(__DIR__.'/cpc_projects_views.php');
require_once(__DIR__.'/ajax_projects.php');
require_once(__DIR__.'/cpc_projects_setup.php');
require_once(__DIR__.'/cpc_projects_widget.php');

add_action('init', 'cpc_projects_maybe_install', 12);
add_action('init', 'cpc_projects_handle_frontend_requests', 35);

function cpc_projects_enqueue_assets() {
    if (!cpc_projects_is_enabled()) {
        return;
    }

    wp_enqueue_script('cpc-select2-js', plugins_url('../js/select2.js', __FILE__), array('jquery'), '4.0.13', true);
    wp_enqueue_style('cpc-select2-css', plugins_url('../js/select2.css', __FILE__), array(), '4.0.13');

    wp_enqueue_style('cpc-projects-css', plugins_url('cpc_projects.css', __FILE__), array(), '1.0.0');
    wp_enqueue_script('cpc-projects-js', plugins_url('cpc_projects.js', __FILE__), array('jquery', 'cpc-select2-js'), '1.0.0', true);
    wp_localize_script('cpc-projects-js', 'cpc_projects_ajax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cpc_projects_ajax_nonce'),
        'confirmDeleteTask' => __('Task wirklich loeschen?', CPC2_TEXT_DOMAIN),
        'confirmDeleteAttachment' => __('Datei wirklich loeschen?', CPC2_TEXT_DOMAIN),
        'addTaskError' => __('Task konnte nicht erstellt werden.', CPC2_TEXT_DOMAIN),
        'updateTaskError' => __('Task konnte nicht aktualisiert werden.', CPC2_TEXT_DOMAIN),
        'addCommentError' => __('Kommentar konnte nicht gespeichert werden.', CPC2_TEXT_DOMAIN),
        'deleteAttachmentError' => __('Datei konnte nicht geloescht werden.', CPC2_TEXT_DOMAIN),
        'assigneesPlaceholder' => __('Zuweisung waehlen', CPC2_TEXT_DOMAIN),
        'loadTaskError' => __('Tasks konnten nicht geladen werden.', CPC2_TEXT_DOMAIN),
    ));
}
add_action('wp_enqueue_scripts', 'cpc_projects_enqueue_assets', 20);

function cpc_projects_render_single_project_content($content) {
    if (!is_singular('cpc_project') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $project_id = get_the_ID();
    if (!$project_id || !cpc_projects_user_can_view_project($project_id)) {
        return '<p>'.esc_html__('Keine Berechtigung.', CPC2_TEXT_DOMAIN).'</p>';
    }

    $project = get_post($project_id);
    if (!$project) {
        return $content;
    }

    $events = cpc_projects_get_project_events($project_id, 60);
    $events_html = '';
    if (!empty($events)) {
        $events_html .= '<ul class="cpc_projects_single_events">';
        foreach ($events as $event) {
            $who = $event->comment_author ? $event->comment_author : __('Mitglied', CPC2_TEXT_DOMAIN);
            $when = sprintf(__('vor %s', CPC2_TEXT_DOMAIN), human_time_diff(strtotime($event->comment_date_gmt), current_time('timestamp', 1)));
            $events_html .= '<li><strong>'.esc_html($who).'</strong> <span>'.esc_html($when).'</span><div>'.esc_html(wp_strip_all_tags((string)$event->comment_content)).'</div></li>';
        }
        $events_html .= '</ul>';
    } else {
        $events_html .= '<p>'.esc_html__('Noch keine Events vorhanden.', CPC2_TEXT_DOMAIN).'</p>';
    }

    $html = '';
    $html .= '<div class="cpc_projects_single">';
    $html .= '<h2 class="cpc_projects_single_title">'.esc_html(get_the_title($project_id)).'</h2>';
    $html .= '<div class="cpc_projects_single_nav">';
    $html .= '<button type="button" class="cpc_projects_single_nav_link is-active" data-target="overview">'.esc_html__('Uebersicht', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '<button type="button" class="cpc_projects_single_nav_link" data-target="tasks">'.esc_html__('Tasks', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '<button type="button" class="cpc_projects_single_nav_link" data-target="activity">'.esc_html__('Aktivitaet', CPC2_TEXT_DOMAIN).'</button>';
    $html .= '</div>';

    $html .= '<section class="cpc_projects_single_section is-active" data-section="overview">';
    $html .= '<div class="cpc_projects_single_content">'.wp_kses_post($project->post_content).'</div>';
    $html .= '</section>';

    $html .= '<section class="cpc_projects_single_section" data-section="tasks">';
    $html .= cpc_projects_render_task_panel($project_id);
    $html .= '</section>';

    $html .= '<section class="cpc_projects_single_section" data-section="activity">';
    $html .= $events_html;
    $html .= '</section>';
    $html .= '</div>';

    return $html;
}
add_filter('the_content', 'cpc_projects_render_single_project_content', 20);

if (is_admin()):
    // Setup page wiring can be added in phase 2.
endif;
