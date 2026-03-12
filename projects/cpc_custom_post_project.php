<?php

function cpc_custom_post_project() {
    $labels = array(
        'name' => __('Projekte', CPC2_TEXT_DOMAIN),
        'singular_name' => __('Projekt', CPC2_TEXT_DOMAIN),
        'add_new' => __('Neu hinzufuegen', CPC2_TEXT_DOMAIN),
        'add_new_item' => __('Neues Projekt hinzufuegen', CPC2_TEXT_DOMAIN),
        'edit_item' => __('Projekt bearbeiten', CPC2_TEXT_DOMAIN),
        'new_item' => __('Neues Projekt', CPC2_TEXT_DOMAIN),
        'view_item' => __('Projekt anzeigen', CPC2_TEXT_DOMAIN),
        'search_items' => __('Projekte durchsuchen', CPC2_TEXT_DOMAIN),
        'not_found' => __('Keine Projekte gefunden', CPC2_TEXT_DOMAIN),
        'not_found_in_trash' => __('Keine Projekte im Papierkorb gefunden', CPC2_TEXT_DOMAIN),
        'menu_name' => __('Projekte', CPC2_TEXT_DOMAIN),
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
