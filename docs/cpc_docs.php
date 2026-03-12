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
}
add_action('wp_enqueue_scripts', 'cpc_docs_enqueue_assets', 20);

if (is_admin()):
    require_once(__DIR__.'/cpc_docs_setup.php');
endif;
