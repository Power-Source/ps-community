<?php

function cpc_custom_post_project() {
    $labels = array(
        'name' => __('Projekte', 'cp-community'),
        'singular_name' => __('Projekt', 'cp-community'),
        'add_new' => __('Neu hinzufuegen', 'cp-community'),
        'add_new_item' => __('Neues Projekt hinzufuegen', 'cp-community'),
        'edit_item' => __('Projekt bearbeiten', 'cp-community'),
        'new_item' => __('Neues Projekt', 'cp-community'),
        'view_item' => __('Projekt anzeigen', 'cp-community'),
        'search_items' => __('Projekte durchsuchen', 'cp-community'),
        'not_found' => __('Keine Projekte gefunden', 'cp-community'),
        'not_found_in_trash' => __('Keine Projekte im Papierkorb gefunden', 'cp-community'),
        'menu_name' => __('Projekte', 'cp-community'),
    );

    $args = array(
        'labels' => $labels,
        'description' => 'Holds PS Community project data',
        'public' => true,
        'exclude_from_search' => false,
        'show_ui' => false,
        'show_in_menu' => false,
        'publicly_queryable' => true,
        'has_archive' => false,
        'hierarchical' => false,
        'rewrite' => array('slug' => 'project', 'with_front' => false),
        'supports' => array('title', 'editor', 'excerpt', 'author', 'comments'),
    );

    register_post_type('cpc_project', $args);
}
add_action('init', 'cpc_custom_post_project', 21);
