<?php

function cpc_custom_post_media() {
    $labels = array(
        'name' => __('Galerie-Medien', CPC2_TEXT_DOMAIN),
        'singular_name' => __('Galerie-Medium', CPC2_TEXT_DOMAIN),
        'add_new' => __('Neues hinzufügen', CPC2_TEXT_DOMAIN),
        'add_new_item' => __('Neues Medium hinzufügen', CPC2_TEXT_DOMAIN),
        'edit_item' => __('Medium bearbeiten', CPC2_TEXT_DOMAIN),
        'new_item' => __('Neues Medium', CPC2_TEXT_DOMAIN),
        'all_items' => __('Alle Medien', CPC2_TEXT_DOMAIN),
        'view_item' => __('Medium anzeigen', CPC2_TEXT_DOMAIN),
        'search_items' => __('Medien durchsuchen', CPC2_TEXT_DOMAIN),
        'not_found' => __('Kein Medium gefunden', CPC2_TEXT_DOMAIN),
        'not_found_in_trash' => __('Kein Medium im Papierkorb gefunden', CPC2_TEXT_DOMAIN),
        'menu_name' => __('Galerie-Medien', CPC2_TEXT_DOMAIN),
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
