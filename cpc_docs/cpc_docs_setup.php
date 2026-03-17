<?php

add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_docs', 7);
function cpc_admin_getting_started_docs() {
    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === 'cpc_admin_getting_started_docs' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';
    echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" rel="cpc_admin_getting_started_docs" id="cpc_admin_getting_started_docs_div">'.__('Dokumente', CPC2_TEXT_DOMAIN).'</div>';

    $display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === 'cpc_admin_getting_started_docs' ? 'block' : 'none';
    echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_docs" style="display:'.$display.'">';

    echo '<table class="form-table">';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Docs-Slug', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="text" name="cpc_docs_slug" value="'.esc_attr(cpc_docs_get_slug()).'" class="regular-text" />';
    echo '<span class="description">'.__('URL-Slug fuer Dokumente, z.B. docs oder wiki.', CPC2_TEXT_DOMAIN).'</span></td>';
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
    echo '<td scope="row" valign="top"><label>'.__('Verzeichnis-Titel', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="text" name="cpc_docs_directory_title" value="'.esc_attr(cpc_docs_get_directory_title()).'" class="regular-text" />';
    echo '<span class="description">'.__('Titel oberhalb des globalen Dokumenten-Verzeichnisses.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Verzeichnis-Excerpt-Laenge', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="number" min="0" max="120" name="cpc_docs_directory_excerpt_length" value="'.(int)cpc_docs_get_directory_excerpt_length().'" class="small-text" style="max-width:70px;" />';
    echo '<span class="description">'.__('Wortanzahl fuer Kurztexte in Listen/Karten (0 deaktiviert Excerpts).', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('User-Tab-Name', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="text" name="cpc_docs_user_tab_name" value="'.esc_attr(cpc_docs_get_user_tab_name()).'" class="regular-text" />';
    echo '<span class="description">'.__('Beschriftung des Docs-Tabs im Benutzerprofil.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Gruppen-Tab-Name', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="text" name="cpc_docs_group_tab_name" value="'.esc_attr(cpc_docs_get_group_tab_name()).'" class="regular-text" />';
    echo '<span class="description">'.__('Beschriftung des Docs-Tabs in Gruppen.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Attachments aktivieren', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><select name="cpc_docs_enable_attachments">';
    echo '<option value="1" '.selected(cpc_docs_enable_attachments(), true, false).'>'.esc_html__('Aktiviert', CPC2_TEXT_DOMAIN).'</option>';
    echo '<option value="0" '.selected(cpc_docs_enable_attachments(), false, false).'>'.esc_html__('Deaktiviert', CPC2_TEXT_DOMAIN).'</option>';
    echo '</select>';
    echo '<span class="description">'.__('Erlaubt Upload und Anzeige von Datei-Anhaengen in Docs.', CPC2_TEXT_DOMAIN).'</span></td>';
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
    $old_slug = cpc_docs_get_slug();

    // Module activation is now controlled centrally via cpc_default_core (core-docs).
    delete_option('cpc_docs_module_enabled');

    if (isset($the_post['cpc_docs_slug'])) {
        update_option('cpc_docs_slug', sanitize_title($the_post['cpc_docs_slug']));
    }

    if (isset($the_post['cpc_docs_directory_title'])) {
        update_option('cpc_docs_directory_title', sanitize_text_field($the_post['cpc_docs_directory_title']));
    }

    if (isset($the_post['cpc_docs_directory_excerpt_length'])) {
        update_option('cpc_docs_directory_excerpt_length', max(0, min(120, (int)$the_post['cpc_docs_directory_excerpt_length'])));
    }

    if (isset($the_post['cpc_docs_user_tab_name'])) {
        update_option('cpc_docs_user_tab_name', sanitize_text_field($the_post['cpc_docs_user_tab_name']));
    }

    if (isset($the_post['cpc_docs_group_tab_name'])) {
        update_option('cpc_docs_group_tab_name', sanitize_text_field($the_post['cpc_docs_group_tab_name']));
    }

    if (isset($the_post['cpc_docs_enable_attachments'])) {
        update_option('cpc_docs_enable_attachments', (int)$the_post['cpc_docs_enable_attachments'] === 1 ? 1 : 0);
    }

    if (isset($the_post['cpc_docs_directory_page'])) {
        update_option('cpc_docs_directory_page', max(0, (int)$the_post['cpc_docs_directory_page']));
    }

    if (isset($the_post['cpc_docs_directory_items_per_page'])) {
        update_option('cpc_docs_directory_items_per_page', max(6, min(60, (int)$the_post['cpc_docs_directory_items_per_page'])));
    }

    $new_slug = cpc_docs_get_slug();
    if ($old_slug !== $new_slug) {
        flush_rewrite_rules(false);
    }

    // Cleanup legacy migration status option when opening/saving settings.
    delete_option('cpc_docs_last_import_result');
}
