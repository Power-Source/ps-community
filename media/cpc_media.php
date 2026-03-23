<?php

require_once(__DIR__.'/lib_media.php');
require_once(__DIR__.'/cpc_custom_post_gallery.php');
require_once(__DIR__.'/cpc_custom_post_media.php');
require_once(__DIR__.'/cpc_media_shortcodes.php');
require_once(__DIR__.'/cpc_media_views.php');
require_once(__DIR__.'/cpc_media_ajax.php');

add_action('cpc_activity_post_add_hook', 'cpc_media_sync_member_activity_gallery', 30, 3);
add_action('cpc_group_activity_post_add_hook', 'cpc_media_sync_group_activity_gallery', 20, 4);
add_filter('cpc_activity_plus_user_cloud_dirs', 'cpc_media_extend_user_cloud_dirs', 20, 2);
add_filter('cpc_activity_plus_group_cloud_dirs', 'cpc_media_extend_group_cloud_dirs', 20, 2);
add_filter('cpc_activity_plus_user_cloud_limit_mb', 'cpc_media_filter_user_cloud_limit_mb', 20, 3);
add_filter('cpc_activity_plus_group_cloud_limit_mb', 'cpc_media_filter_group_cloud_limit_mb', 20, 2);

function cpc_media_enqueue_assets() {
    if (!cpc_media_is_enabled()) {
        return;
    }

    // Magnific Popup Library
    wp_enqueue_style('magnificpopup-css', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/magnific-popup.min.css', array(), '1.1.0');
    wp_enqueue_script('magnificpopup-js', 'https://cdnjs.cloudflare.com/ajax/libs/magnific-popup.js/1.1.0/jquery.magnific-popup.min.js', array('jquery'), '1.1.0', true);

    // PSource Sortable (native drag/drop - replacement for deprecated jQuery UI sortable)
    wp_enqueue_style('psource-sortable-css', plugins_url('../assets/psource-ui/sortable/psource-sortable.css', __FILE__), array(), '1.0.0');
    wp_enqueue_script('psource-sortable-js', plugins_url('../assets/psource-ui/sortable/psource-sortable.js', __FILE__), array(), '1.0.0', true);

    // CPC Media Assets
    wp_enqueue_style('cpc-media-css', plugins_url('cpc_media.css', __FILE__), array('magnificpopup-css'), '1.0.0');
    wp_enqueue_script('cpc-media-js', plugins_url('cpc_media.js', __FILE__), array('jquery', 'magnificpopup-js', 'psource-sortable-js'), '1.0.0', true);

    wp_localize_script('cpc-media-js', 'cpc_media_ajax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('cpc_media_ajax_nonce'),
        'confirmDeleteGallery' => __('Galerie wirklich loeschen?', CPC2_TEXT_DOMAIN),
        'confirmDeleteMedia' => __('Medium wirklich loeschen?', CPC2_TEXT_DOMAIN),
        'uploading' => __('Dateien werden hochgeladen...', CPC2_TEXT_DOMAIN),
        'uploadDone' => __('Upload abgeschlossen.', CPC2_TEXT_DOMAIN),
        'uploadError' => __('Upload fehlgeschlagen.', CPC2_TEXT_DOMAIN),
        'lightbox_enabled' => cpc_media_lightbox_enabled() ? 1 : 0,
        'reorder_enabled' => cpc_media_reorder_enabled() ? 1 : 0,
        'cover_selector_enabled' => cpc_media_cover_selector_enabled() ? 1 : 0,
    ));
}
add_action('wp_enqueue_scripts', 'cpc_media_enqueue_assets', 20);

if (is_admin()):
    require_once(__DIR__.'/cpc_media_setup.php');
endif;
