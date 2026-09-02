<?php

function cpc_custom_post_media() {
    $labels = array(
        'name' => __('Galerie-Medien', 'cp-community'),
        'singular_name' => __('Galerie-Medium', 'cp-community'),
        'add_new' => __('Neues hinzufügen', 'cp-community'),
        'add_new_item' => __('Neues Medium hinzufügen', 'cp-community'),
        'edit_item' => __('Medium bearbeiten', 'cp-community'),
        'new_item' => __('Neues Medium', 'cp-community'),
        'all_items' => __('Alle Medien', 'cp-community'),
        'view_item' => __('Medium anzeigen', 'cp-community'),
        'search_items' => __('Medien durchsuchen', 'cp-community'),
        'not_found' => __('Kein Medium gefunden', 'cp-community'),
        'not_found_in_trash' => __('Kein Medium im Papierkorb gefunden', 'cp-community'),
        'menu_name' => __('Galerie-Medien', 'cp-community'),
    );

    $args = array(
        'labels' => $labels,
        'description' => 'Holds PS Community gallery media data',
        'public' => false,
        'exclude_from_search' => true,
        'show_in_menu' => false,
        'publicly_queryable' => false,
        'has_archive' => false,
        'hierarchical' => false,
        'rewrite' => false,
        'supports' => array('title', 'editor', 'author'),
    );

    register_post_type('cpc_media', $args);
}
add_action('init', 'cpc_custom_post_media');
