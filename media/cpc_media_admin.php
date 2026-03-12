<?php

add_action('admin_menu', 'cpc_media_admin_menu', 20);
function cpc_media_admin_menu() {
    add_submenu_page(
        'cpc_com',
        __('Galerie-Migration', CPC2_TEXT_DOMAIN),
        __('Galerie-Migration', CPC2_TEXT_DOMAIN),
        'manage_options',
        'cpc-media-migration',
        'cpc_media_admin_page'
    );
}

function cpc_media_admin_page() {
    $results = false;

    if (!empty($_POST['cpc_media_import_mediapress']) && !empty($_POST['cpc_media_import_mediapress_nonce'])) {
        check_admin_referer('cpc_media_import_mediapress', 'cpc_media_import_mediapress_nonce');
        $limit = isset($_POST['cpc_media_import_limit']) ? (int)$_POST['cpc_media_import_limit'] : 25;
        $copy_files = !empty($_POST['cpc_media_import_copy_files']);
        $results = cpc_media_import_mediapress_galleries($limit, $copy_files);
    }

    echo '<div class="wrap">';
    echo '<h1>'.esc_html__('PS Community Galerie-Migration', CPC2_TEXT_DOMAIN).'</h1>';
    echo '<p>'.esc_html__('Dieser erste Migrationsschritt uebernimmt MediaPress-Galerien als PS-Community-Galerien und speichert die Quellzuordnung fuer die weitere Medienmigration.', CPC2_TEXT_DOMAIN).'</p>';

    if ($results) {
        echo '<div class="notice notice-success"><p>';
        echo sprintf(
            esc_html__('Verarbeitet: %1$d, importiert: %2$d, uebersprungen: %3$d', CPC2_TEXT_DOMAIN),
            (int)$results['processed'],
            (int)$results['imported'],
            (int)$results['skipped']
        );
        echo '</p></div>';
    }

    echo '<form method="post">';
    wp_nonce_field('cpc_media_import_mediapress', 'cpc_media_import_mediapress_nonce');
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="cpc_media_import_limit">'.esc_html__('Maximale Anzahl', CPC2_TEXT_DOMAIN).'</label></th>';
    echo '<td><input type="number" min="1" max="200" value="25" name="cpc_media_import_limit" id="cpc_media_import_limit" class="small-text" /></td>';
    echo '</tr>';
    echo '<tr>';
    echo '<th scope="row"><label for="cpc_media_import_copy_files">'.esc_html__('Dateien kopieren', CPC2_TEXT_DOMAIN).'</label></th>';
    echo '<td><label><input type="checkbox" checked="checked" name="cpc_media_import_copy_files" id="cpc_media_import_copy_files" value="1" /> '.esc_html__('Originaldateien und bekannte Varianten in den PS-Community-User-Cloudpfad kopieren', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '</tr>';
    echo '</table>';
    submit_button(__('MediaPress-Galerien importieren', CPC2_TEXT_DOMAIN), 'primary', 'cpc_media_import_mediapress');
    echo '</form>';

    if ($results && !empty($results['items'])) {
        echo '<h2>'.esc_html__('Ergebnis', CPC2_TEXT_DOMAIN).'</h2>';
        echo '<table class="widefat striped"><thead><tr>';
        echo '<th>'.esc_html__('MediaPress-ID', CPC2_TEXT_DOMAIN).'</th>';
        echo '<th>'.esc_html__('PS Community Galerie', CPC2_TEXT_DOMAIN).'</th>';
        echo '<th>'.esc_html__('Status', CPC2_TEXT_DOMAIN).'</th>';
        echo '</tr></thead><tbody>';
        foreach ($results['items'] as $item) {
            echo '<tr>';
            echo '<td>'.(int)$item['source_id'].'</td>';
            echo '<td>'.($item['gallery_id'] ? (int)$item['gallery_id'] : '&ndash;').'</td>';
            echo '<td>'.esc_html($item['status']).'</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }

    echo '</div>';
}