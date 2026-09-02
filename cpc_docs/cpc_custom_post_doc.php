<?php

function cpc_custom_post_doc() {
    $labels = array(
        'name' => __('Dokumente', 'cp-community'),
        'singular_name' => __('Dokument', 'cp-community'),
        'add_new' => __('Neu hinzufuegen', 'cp-community'),
        'add_new_item' => __('Neues Dokument hinzufuegen', 'cp-community'),
        'edit_item' => __('Dokument bearbeiten', 'cp-community'),
        'new_item' => __('Neues Dokument', 'cp-community'),
        'view_item' => __('Dokument anzeigen', 'cp-community'),
        'search_items' => __('Dokumente durchsuchen', 'cp-community'),
        'not_found' => __('Keine Dokumente gefunden', 'cp-community'),
        'not_found_in_trash' => __('Keine Dokumente im Papierkorb gefunden', 'cp-community'),
        'menu_name' => __('Dokumente', 'cp-community'),
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
            'name' => __('Dokument-Tags', 'cp-community'),
            'singular_name' => __('Dokument-Tag', 'cp-community'),
        ),
        'show_ui' => true,
        'show_admin_column' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'cpc-doc-tag'),
    ));
}
add_action('init', 'cpc_custom_post_doc', 20);
