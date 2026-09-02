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

    wp_enqueue_style('cpc-projects-css', plugins_url('cpc_projects.css', __FILE__), array(), '1.1.0');
    wp_enqueue_script('cpc-projects-js', plugins_url('cpc_projects.js', __FILE__), array('jquery', 'cpc-select2-js'), '1.0.0', true);
    wp_localize_script('cpc-projects-js', 'cpc_projects_ajax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cpc_projects_ajax_nonce'),
        'confirmDeleteTask' => __('Task wirklich loeschen?', 'cp-community'),
        'confirmDeleteAttachment' => __('Datei wirklich loeschen?', 'cp-community'),
        'confirmDeleteProject' => __('Projekt und alle Tasks wirklich dauerhaft loeschen?', 'cp-community'),
        'confirmDeleteComment' => __('Kommentar wirklich loeschen?', 'cp-community'),
        'addTaskError' => __('Task konnte nicht erstellt werden.', 'cp-community'),
        'updateTaskError' => __('Task konnte nicht aktualisiert werden.', 'cp-community'),
        'addCommentError' => __('Kommentar konnte nicht gespeichert werden.', 'cp-community'),
        'deleteAttachmentError' => __('Datei konnte nicht geloescht werden.', 'cp-community'),
        'updateProjectError' => __('Projekt konnte nicht aktualisiert werden.', 'cp-community'),
        'deleteProjectError' => __('Projekt konnte nicht geloescht werden.', 'cp-community'),
        'assigneesPlaceholder' => __('Zuweisung waehlen', 'cp-community'),
        'loadTaskError' => __('Tasks konnten nicht geladen werden.', 'cp-community'),
    ));
}
add_action('wp_enqueue_scripts', 'cpc_projects_enqueue_assets', 20);

function cpc_projects_archive_template_include($template) {
    if (!cpc_projects_is_enabled() || !is_post_type_archive('cpc_project')) {
        return $template;
    }

    $archive_template = __DIR__.'/archive-cpc_project.php';
    if (file_exists($archive_template)) {
        return $archive_template;
    }

    return $template;
}
add_filter('template_include', 'cpc_projects_archive_template_include', 50);

function cpc_projects_render_single_project_content($content) {
    if (!is_singular('cpc_project') || !in_the_loop() || !is_main_query()) {
        return $content;
    }

    $project_id = get_the_ID();
    if (!$project_id || !cpc_projects_user_can_view_project($project_id)) {
        return '<p>'.esc_html__('Keine Berechtigung.', 'cp-community').'</p>';
    }

    $project = get_post($project_id);
    if (!$project) {
        return $content;
    }

    return cpc_projects_render_single_project_html($project_id);
}
add_filter('the_content', 'cpc_projects_render_single_project_content', 20);

if (is_admin()):
    // Setup page wiring can be added in phase 2.
endif;
