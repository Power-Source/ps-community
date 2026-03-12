<?php

require_once(__DIR__.'/lib_docs.php');
require_once(__DIR__.'/cpc_custom_post_doc.php');
require_once(__DIR__.'/cpc_docs_views.php');

add_action('init', 'cpc_docs_handle_frontend_requests', 30);

function cpc_docs_enqueue_assets() {
    if (!cpc_docs_is_enabled()) {
        return;
    }

    wp_enqueue_style('cpc-docs-css', plugins_url('cpc_docs.css', __FILE__), array(), '1.0.0');
    wp_enqueue_script('cpc-docs-js', plugins_url('cpc_docs.js', __FILE__), array(), '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'cpc_docs_enqueue_assets', 20);

function cpc_docs_handle_ajax_load_folder() {
    if (!cpc_docs_is_enabled()) {
        wp_send_json_error(array('message' => 'Docs not enabled'));
    }

    cpc_docs_load_folder_contents_ajax();
}
add_action('wp_ajax_cpc_docs_load_folder', 'cpc_docs_handle_ajax_load_folder');
add_action('wp_ajax_nopriv_cpc_docs_load_folder', 'cpc_docs_handle_ajax_load_folder');

if (is_admin()):
    require_once(__DIR__.'/cpc_docs_setup.php');
endif;
