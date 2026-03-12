<?php

add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_media', 6);
function cpc_admin_getting_started_media() {
    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === 'cpc_admin_getting_started_media' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';
    echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" rel="cpc_admin_getting_started_media" id="cpc_admin_getting_started_media_div">'.__('Medien', CPC2_TEXT_DOMAIN).'</div>';

    $display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === 'cpc_admin_getting_started_media' ? 'block' : 'none';
    echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_media" style="display:'.$display.'">';
    echo '<table class="form-table">';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label for="cpc_media_enable_friend_visibility">'.__('Freundes-Sichtbarkeit', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:10px" name="cpc_media_enable_friend_visibility" '.(get_option('cpc_media_enable_friend_visibility', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Erlaubt für Profil-Galerien die Sichtbarkeitsstufe Nur Freunde, sofern das Freundschaftsmodul aktiv ist.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label for="cpc_media_gallery_layout">'.__('Galerie-Layout', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><select name="cpc_media_gallery_layout">';
    echo '<option value="grid"'.selected(cpc_media_get_gallery_layout(), 'grid', false).'>'.__('Grid', CPC2_TEXT_DOMAIN).'</option>';
    echo '<option value="list"'.selected(cpc_media_get_gallery_layout(), 'list', false).'>'.__('Liste', CPC2_TEXT_DOMAIN).'</option>';
    echo '</select> <span class="description">'.__('Steuert die Standarddarstellung der Medien innerhalb einer Galerie.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label for="cpc_media_default_member_status">'.__('Standardstatus Profil-Galerien', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><select name="cpc_media_default_member_status">';
    foreach (cpc_media_get_gallery_status_options('members') as $value => $label) {
        echo '<option value="'.esc_attr($value).'"'.selected(cpc_media_get_default_gallery_status('members'), $value, false).'>'.esc_html($label).'</option>';
    }
    echo '</select></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label for="cpc_media_default_group_status">'.__('Standardstatus Gruppen-Galerien', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><select name="cpc_media_default_group_status">';
    foreach (cpc_media_get_gallery_status_options('groups') as $value => $label) {
        echo '<option value="'.esc_attr($value).'"'.selected(cpc_media_get_default_gallery_status('groups'), $value, false).'>'.esc_html($label).'</option>';
    }
    echo '</select></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label for="cpc_media_gallery_items_limit">'.__('Medien pro Galerie', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="number" min="1" max="60" name="cpc_media_gallery_items_limit" value="'.(int)cpc_media_get_gallery_items_limit().'" class="small-text" />';
    echo '<span class="description">'.__('Begrenzt, wie viele Medien im Profil- oder Gruppen-Tab direkt angezeigt werden.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label for="cpc_media_show_gallery_descriptions">'.__('Beschreibungen anzeigen', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:10px" name="cpc_media_show_gallery_descriptions" '.(cpc_media_show_gallery_descriptions() ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Zeigt Galerie-Beschreibungen in den Profil- und Gruppenkarten an.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label for="cpc_media_enable_lightbox">'.__('Lightbox-Modal aktivieren', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:10px" name="cpc_media_enable_lightbox" '.(get_option('cpc_media_enable_lightbox', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Aktiviert die Modal-Lightbox fuer Bilder und Videos zur Full-Screen-Anzeige.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label for="cpc_media_enable_reorder">'.__('Drag-and-Drop Umordnung aktivieren', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:10px" name="cpc_media_enable_reorder" '.(get_option('cpc_media_enable_reorder', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Erlaubt Upload-Besitzer, die Reihenfolge der Medien in einer Galerie durch Drag-and-Drop zu aendern.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label for="cpc_media_enable_cover_selector">'.__('Cover-Auswahl Funktion aktivieren', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:10px" name="cpc_media_enable_cover_selector" '.(get_option('cpc_media_enable_cover_selector', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Erlaubt Besitzern, aus ihren Medien ein Vorschau-Bild fuer die Galerie auszuwaehlen.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>';
}

add_action('cpc_admin_setup_form_get_hook', 'cpc_admin_media_save', 10, 2);
add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_media_save', 10, 2);
function cpc_admin_media_save($the_post) {
    if (isset($the_post['cpc_media_enable_friend_visibility'])) {
        update_option('cpc_media_enable_friend_visibility', 1);
    } else {
        delete_option('cpc_media_enable_friend_visibility');
    }

    if (isset($the_post['cpc_media_show_gallery_descriptions'])) {
        update_option('cpc_media_show_gallery_descriptions', 1);
    } else {
        delete_option('cpc_media_show_gallery_descriptions');
    }

    if (isset($the_post['cpc_media_enable_lightbox'])) {
        update_option('cpc_media_enable_lightbox', 1);
    } else {
        delete_option('cpc_media_enable_lightbox');
    }

    if (isset($the_post['cpc_media_enable_reorder'])) {
        update_option('cpc_media_enable_reorder', 1);
    } else {
        delete_option('cpc_media_enable_reorder');
    }

    if (isset($the_post['cpc_media_enable_cover_selector'])) {
        update_option('cpc_media_enable_cover_selector', 1);
    } else {
        delete_option('cpc_media_enable_cover_selector');
    }

    if (isset($the_post['cpc_media_gallery_layout'])) {
        update_option('cpc_media_gallery_layout', sanitize_key($the_post['cpc_media_gallery_layout']));
    }

    if (isset($the_post['cpc_media_gallery_items_limit'])) {
        update_option('cpc_media_gallery_items_limit', max(1, min(60, (int)$the_post['cpc_media_gallery_items_limit'])));
    }

    if (isset($the_post['cpc_media_default_member_status'])) {
        update_option('cpc_media_default_member_status', cpc_media_normalize_status($the_post['cpc_media_default_member_status']));
    }

    if (isset($the_post['cpc_media_default_group_status'])) {
        $status = sanitize_key($the_post['cpc_media_default_group_status']);
        if (!in_array($status, array('public', 'loggedin', 'private'), true)) {
            $status = 'public';
        }
        update_option('cpc_media_default_group_status', $status);
    }
}