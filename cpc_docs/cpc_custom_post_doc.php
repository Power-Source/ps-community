<?php

function cpc_custom_post_doc() {
    $labels = array(
        'name' => __('Dokumente', CPC2_TEXT_DOMAIN),
        'singular_name' => __('Dokument', CPC2_TEXT_DOMAIN),
        'add_new' => __('Neu hinzufuegen', CPC2_TEXT_DOMAIN),
        'add_new_item' => __('Neues Dokument hinzufuegen', CPC2_TEXT_DOMAIN),
        'edit_item' => __('Dokument bearbeiten', CPC2_TEXT_DOMAIN),
        'new_item' => __('Neues Dokument', CPC2_TEXT_DOMAIN),
        'view_item' => __('Dokument anzeigen', CPC2_TEXT_DOMAIN),
        'search_items' => __('Dokumente durchsuchen', CPC2_TEXT_DOMAIN),
        'not_found' => __('Keine Dokumente gefunden', CPC2_TEXT_DOMAIN),
        'not_found_in_trash' => __('Keine Dokumente im Papierkorb gefunden', CPC2_TEXT_DOMAIN),
        'menu_name' => __('Dokumente', CPC2_TEXT_DOMAIN),
    );

    $args = array(
        'labels' => $labels,
        'description' => 'Holds PS Community docs data',
        'public' => true,
        'exclude_from_search' => false,
        'show_ui' => true,
        'show_in_menu' => false,
        'publicly_queryable' => true,
        'has_archive' => true,
        'hierarchical' => true,
        'rewrite' => array('slug' => cpc_docs_get_slug(), 'with_front' => false),
        'supports' => array('title', 'editor', 'excerpt', 'revisions', 'comments', 'author', 'page-attributes'),
    );

    register_post_type('cpc_doc', $args);

    register_taxonomy('cpc_doc_tag', 'cpc_doc', array(
        'hierarchical' => false,
        'labels' => array(
            'name' => __('Dokument-Tags', CPC2_TEXT_DOMAIN),
            'singular_name' => __('Dokument-Tag', CPC2_TEXT_DOMAIN),
        ),
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'cpc-doc-tag'),
    ));
}
add_action('init', 'cpc_custom_post_doc', 20);
