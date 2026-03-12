<?php

add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_docs', 7);
function cpc_admin_getting_started_docs() {
    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === 'cpc_admin_getting_started_docs' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';
    echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" rel="cpc_admin_getting_started_docs" id="cpc_admin_getting_started_docs_div">'.__('Dokumente', CPC2_TEXT_DOMAIN).'</div>';

    $display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === 'cpc_admin_getting_started_docs' ? 'block' : 'none';
    echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_docs" style="display:'.$display.'">';

    echo '<table class="form-table">';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Docs Modul aktivieren', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_docs_module_enabled" '.(cpc_docs_is_enabled() ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Aktiviert Dokumente in Profil- und Gruppen-Tabs.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Docs-Verzeichnis Seite', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td>';
    echo wp_dropdown_pages(array(
        'name' => 'cpc_docs_directory_page',
        'echo' => 0,
        'show_option_none' => __('Keine feste Seite', CPC2_TEXT_DOMAIN),
        'option_none_value' => '0',
        'selected' => (int)cpc_docs_get_directory_page_id(),
    ));
    echo '<span class="description">'.__('Optional: diese Seite zeigt automatisch das globale Docs-Verzeichnis mit Suche, Filter und Pagination.', CPC2_TEXT_DOMAIN).'</span>';
    echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Elemente pro Seite im Verzeichnis', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="number" min="6" max="60" name="cpc_docs_directory_items_per_page" value="'.(int)cpc_docs_get_directory_items_per_page().'" class="small-text" style="max-width:60px;" />';
    echo '<span class="description">'.__('Anzahl Dokumente pro Seite in der globalen Uebersicht.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>';
}

add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_docs_save', 10, 1);
add_action('cpc_admin_setup_form_get_hook', 'cpc_admin_docs_save', 10, 1);
function cpc_admin_docs_save($the_post) {
    if (isset($the_post['cpc_docs_module_enabled'])) {
        update_option('cpc_docs_module_enabled', 1);
    } else {
        delete_option('cpc_docs_module_enabled');
    }

    if (isset($the_post['cpc_docs_directory_page'])) {
        update_option('cpc_docs_directory_page', max(0, (int)$the_post['cpc_docs_directory_page']));
    }

    if (isset($the_post['cpc_docs_directory_items_per_page'])) {
        update_option('cpc_docs_directory_items_per_page', max(6, min(60, (int)$the_post['cpc_docs_directory_items_per_page'])));
    }

    // Cleanup legacy migration status option when opening/saving settings.
    delete_option('cpc_docs_last_import_result');
}
