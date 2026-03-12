<?php

function cpc_custom_post_gallery() {
    $labels = array(
        'name' => __('Galerien', CPC2_TEXT_DOMAIN),
        'singular_name' => __('Galerie', CPC2_TEXT_DOMAIN),
        'add_new' => __('Neue hinzufügen', CPC2_TEXT_DOMAIN),
        'add_new_item' => __('Neue Galerie hinzufügen', CPC2_TEXT_DOMAIN),
        'edit_item' => __('Galerie bearbeiten', CPC2_TEXT_DOMAIN),
        'new_item' => __('Neue Galerie', CPC2_TEXT_DOMAIN),
        'all_items' => __('Alle Galerien', CPC2_TEXT_DOMAIN),
        'view_item' => __('Galerie anzeigen', CPC2_TEXT_DOMAIN),
        'search_items' => __('Galerien durchsuchen', CPC2_TEXT_DOMAIN),
        'not_found' => __('Keine Galerie gefunden', CPC2_TEXT_DOMAIN),
        'not_found_in_trash' => __('Keine Galerie im Papierkorb gefunden', CPC2_TEXT_DOMAIN),
        'parent_item_colon' => __('Übergeordnete Galerie:', CPC2_TEXT_DOMAIN),
        'menu_name' => __('Galerien', CPC2_TEXT_DOMAIN),
    );

    $args = array(
        'labels' => $labels,
        'description' => 'Holds PS Community gallery data',
        'public' => true,
        'exclude_from_search' => false,
        'show_in_menu' => get_option('cpc_core_admin_icons') ? 'cpc_com' : true,
        'publicly_queryable' => true,
        'has_archive' => false,
        'hierarchical' => false,
        'rewrite' => array('slug' => 'gallery', 'with_front' => false),
        'supports' => array('title', 'editor', 'thumbnail', 'comments', 'author'),
    );

    register_post_type('cpc_gallery', $args);
}
add_action('init', 'cpc_custom_post_gallery');

function cpc_updated_gallery_messages($messages) {
    global $post;

    $messages['cpc_gallery'] = array(
        0 => '',
        1 => __('Galerie aktualisiert.', CPC2_TEXT_DOMAIN),
        2 => __('Benutzerdefiniertes Feld aktualisiert.', CPC2_TEXT_DOMAIN),
        3 => __('Benutzerdefiniertes Feld gelöscht.', CPC2_TEXT_DOMAIN),
        4 => __('Galerie aktualisiert.', CPC2_TEXT_DOMAIN),
        5 => isset($_GET['revision']) ? sprintf(__('Galerie wiederhergestellt von Revision vom %s', CPC2_TEXT_DOMAIN), wp_post_revision_title((int)$_GET['revision'], false)) : false,
        6 => __('Galerie veröffentlicht.', CPC2_TEXT_DOMAIN),
        7 => __('Galerie gespeichert.', CPC2_TEXT_DOMAIN),
        8 => __('Galerie eingereicht.', CPC2_TEXT_DOMAIN),
        9 => sprintf(__('Galerie geplant für: <strong>%1$s</strong>.', CPC2_TEXT_DOMAIN), date_i18n(__('M j, Y @ G:i', CPC2_TEXT_DOMAIN), strtotime($post->post_date))),
        10 => __('Galerieentwurf aktualisiert.', CPC2_TEXT_DOMAIN),
    );

    return $messages;
}
add_filter('post_updated_messages', 'cpc_updated_gallery_messages');

add_action('add_meta_boxes', 'cpc_gallery_info_box');
function cpc_gallery_info_box() {
    add_meta_box(
        'cpc_gallery_info_box',
        __('Galeriedetails', CPC2_TEXT_DOMAIN),
        'cpc_gallery_info_box_content',
        'cpc_gallery',
        'side',
        'high'
    );
}

function cpc_gallery_info_box_content($post) {
    wp_nonce_field('cpc_gallery_info_box_content', 'cpc_gallery_info_box_content_nonce');

    $component = cpc_media_get_gallery_component($post->ID);
    $component_id = cpc_media_get_gallery_component_id($post->ID);
    $status = cpc_media_get_gallery_status($post->ID);
    $type = cpc_media_get_gallery_type($post->ID);
    $media_count = cpc_media_get_gallery_media_count($post->ID);

    echo '<p><strong>'.__('Kontext', CPC2_TEXT_DOMAIN).'</strong><br />';
    echo esc_html($component).' #'.(int)$component_id.'</p>';

    echo '<p><strong>'.__('Status', CPC2_TEXT_DOMAIN).'</strong><br />';
    echo '<select name="cpc_gallery_status" style="width:100%">';
    foreach (cpc_media_get_gallery_status_options($component) as $value => $label) {
        echo '<option value="'.esc_attr($value).'"'.selected($status, $value, false).'>'.esc_html($label).'</option>';
    }
    echo '</select></p>';

    echo '<p><strong>'.__('Typ', CPC2_TEXT_DOMAIN).'</strong><br />';
    echo '<select name="cpc_gallery_type" style="width:100%">';
    echo '<option value="photo"'.selected($type, 'photo', false).'>'.__('Bilder', CPC2_TEXT_DOMAIN).'</option>';
    echo '<option value="video"'.selected($type, 'video', false).'>'.__('Videos', CPC2_TEXT_DOMAIN).'</option>';
    echo '<option value="audio"'.selected($type, 'audio', false).'>'.__('Audio', CPC2_TEXT_DOMAIN).'</option>';
    echo '<option value="doc"'.selected($type, 'doc', false).'>'.__('Dokumente', CPC2_TEXT_DOMAIN).'</option>';
    echo '</select></p>';

    echo '<p><strong>'.__('Mediendateien', CPC2_TEXT_DOMAIN).'</strong><br />'.(int)$media_count.'</p>';
}

add_action('save_post', 'cpc_gallery_info_box_save');
function cpc_gallery_info_box_save($post_id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (!isset($_POST['cpc_gallery_info_box_content_nonce']) || !wp_verify_nonce($_POST['cpc_gallery_info_box_content_nonce'], 'cpc_gallery_info_box_content')) {
        return;
    }

    if (!current_user_can('edit_post', $post_id)) {
        return;
    }

    if (isset($_POST['cpc_gallery_status'])) {
        update_post_meta($post_id, 'cpc_gallery_status', sanitize_text_field($_POST['cpc_gallery_status']));
    }

    if (isset($_POST['cpc_gallery_type'])) {
        update_post_meta($post_id, 'cpc_gallery_type', sanitize_text_field($_POST['cpc_gallery_type']));
    }
}
