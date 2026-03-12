<?php

add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_media', 6);
function cpc_admin_getting_started_media() {
    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === 'cpc_admin_getting_started_media' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';
    echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" rel="cpc_admin_getting_started_media" id="cpc_admin_getting_started_media_div">'.__('Medien', CPC2_TEXT_DOMAIN).'</div>';

    $display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] === 'cpc_admin_getting_started_media' ? 'block' : 'none';
    echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_media" style="display:'.$display.'">';

    // Tabs Navigation
    echo '<div class="cpc_media_admin_tabs">';
    echo '<div class="cpc_media_admin_tab active" data-tab="general" >'.esc_html__('Allgemein', CPC2_TEXT_DOMAIN).'</div>';
    echo '<div class="cpc_media_admin_tab" data-tab="display">'.esc_html__('Anzeige', CPC2_TEXT_DOMAIN).'</div>';
    echo '<div class="cpc_media_admin_tab" data-tab="upload">'.esc_html__('Upload', CPC2_TEXT_DOMAIN).'</div>';
    echo '<div class="cpc_media_admin_tab" data-tab="lightbox">'.esc_html__('Lightbox', CPC2_TEXT_DOMAIN).'</div>';
    echo '<div class="cpc_media_admin_tab" data-tab="privacy">'.esc_html__('Datenschutz', CPC2_TEXT_DOMAIN).'</div>';
    echo '</div>';

    // TAB: GENERAL
    echo '<div class="cpc_media_admin_tab_content active" id="cpc_media_admin_tab_general">';
    echo '<table class="form-table">';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Modul aktivieren', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_media_module_enabled" '.(get_option('cpc_media_module_enabled', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Aktiviert / Deaktiviert komplett das Medien-Modul fuer alle Benutzer.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Benutzer können Galerien erstellen', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_media_user_can_create_galleries" '.(get_option('cpc_media_user_can_create_galleries', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Erlaubt Benutzern, neue Galerien auf ihrem Profil zu erstellen.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Max. Galerien pro Benutzer', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="number" min="0" max="1000" name="cpc_media_max_galleries_per_user" value="'.(int)get_option('cpc_media_max_galleries_per_user', 0).'" class="small-text" />';
    echo '<span class="description">'.__('0 = unbegrenzt', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>';

    // TAB: DISPLAY
    echo '<div class="cpc_media_admin_tab_content" id="cpc_media_admin_tab_display">';
    echo '<table class="form-table">';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Standard-Layout', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><select name="cpc_media_gallery_layout" style="min-width:200px;">';
    echo '<option value="grid"'.selected(cpc_media_get_gallery_layout(), 'grid', false).'>'.__('3er-Grid (empfohlen)', CPC2_TEXT_DOMAIN).'</option>';
    echo '<option value="list"'.selected(cpc_media_get_gallery_layout(), 'list', false).'>'.__('Liste', CPC2_TEXT_DOMAIN).'</option>';
    echo '</select> <span class="description">'.__('Wie Medien innerhalb einer Galerie angezeigt werden.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Spalten im Grid', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="number" min="1" max="6" name="cpc_media_gallery_grid_columns" value="'.(int)get_option('cpc_media_gallery_grid_columns', 3).'" class="small-text" style="max-width:60px;" />';
    echo '<span class="description">'.__('Wie viele Items pro Reihe (1-6)', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Medien pro Seite anzeigen', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="number" min="1" max="100" name="cpc_media_gallery_items_limit" value="'.(int)cpc_media_get_gallery_items_limit().'" class="small-text" style="max-width:60px;" />';
    echo '<span class="description">'.__('Im Profil/Gruppen-Tab', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Mediathek-Seite', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td>';
    echo wp_dropdown_pages(array(
        'name' => 'cpc_media_directory_page',
        'echo' => 0,
        'show_option_none' => __('Keine feste Seite', CPC2_TEXT_DOMAIN),
        'option_none_value' => '0',
        'selected' => (int)get_option('cpc_media_directory_page', 0),
    ));
    echo '<span class="description">'.__('Optional: diese WordPress-Seite zeigt automatisch die globale Mediathek mit Galerie- und Medienverzeichnis.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Elemente pro Seite in Mediathek', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="number" min="6" max="60" name="cpc_media_directory_items_per_page" value="'.(int)get_option('cpc_media_directory_items_per_page', 12).'" class="small-text" style="max-width:60px;" />';
    echo '<span class="description">'.__('Für globale Galerie- und Medienübersicht.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Galerie-Beschreibungen anzeigen', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_media_show_gallery_descriptions" '.(cpc_media_show_gallery_descriptions() ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Zeigt Galerie-Beschreibungen in Karten.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Media-Item Beschreibungen anzeigen', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_media_show_item_descriptions" '.(get_option('cpc_media_show_item_descriptions', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Zeigt Beschreibungen unter jedem Media-Item.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>';

    // TAB: UPLOAD
    echo '<div class="cpc_media_admin_tab_content" id="cpc_media_admin_tab_upload">';
    echo '<table class="form-table">';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Max. Dateigröße (MB)', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="number" min="1" max="500" name="cpc_media_max_file_size" value="'.(int)get_option('cpc_media_max_file_size', 50).'" class="small-text" style="max-width:80px;" />';
    echo '<span class="description">'.__('Maximale Größe pro Upload (1-500 MB)', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Wall-Medien automatisch spiegeln', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_media_activity_wall_sync" '.(cpc_media_activity_wall_sync_enabled() ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Bilder aus Activity+ landen zusätzlich in einer versteckten privaten System-Galerie pro Profil bzw. Gruppe.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Nutzer-Speicherlimit (MB)', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="number" min="0" max="10240" name="cpc_media_user_storage_limit_mb" value="'.(int)cpc_media_get_user_storage_limit_mb().'" class="small-text" style="max-width:90px;" />';
    echo '<span class="description">'.__('0 = vorhandenes Activity+/Cloud-Limit weiterverwenden, sonst gemeinsames Limit für Galerie- und Activity-Dateien.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Gruppen-Speicherlimit (MB)', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="number" min="0" max="10240" name="cpc_media_group_storage_limit_mb" value="'.(int)cpc_media_get_group_storage_limit_mb().'" class="small-text" style="max-width:90px;" />';
    echo '<span class="description">'.__('0 = vorhandenes Gruppen-Cloud-Limit weiterverwenden, sonst gemeinsames Limit für Gruppen-Galerien und Gruppen-Activity.', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Erlaubte Dateitypen', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td>';
    $allowed_types = get_option('cpc_media_allowed_types', array('image', 'video', 'audio', 'document'));
    echo '<label><input type="checkbox" name="cpc_media_allowed_types[]" value="image" '.(in_array('image', $allowed_types) ? 'CHECKED' : '').' /> '.__('Bilder (JPG, PNG, GIF)', CPC2_TEXT_DOMAIN).'</label><br/>';
    echo '<label><input type="checkbox" name="cpc_media_allowed_types[]" value="video" '.(in_array('video', $allowed_types) ? 'CHECKED' : '').' /> '.__('Videos (MP4, WebM)', CPC2_TEXT_DOMAIN).'</label><br/>';
    echo '<label><input type="checkbox" name="cpc_media_allowed_types[]" value="audio" '.(in_array('audio', $allowed_types) ? 'CHECKED' : '').' /> '.__('Audio (MP3, WAV)', CPC2_TEXT_DOMAIN).'</label><br/>';
    echo '<label><input type="checkbox" name="cpc_media_allowed_types[]" value="document" '.(in_array('document', $allowed_types) ? 'CHECKED' : '').' /> '.__('Dokumente (PDF, ZIP)', CPC2_TEXT_DOMAIN).'</label>';
    echo '<span class="description">'.__('Welche Dateitypen dürfen hochgeladen werden?', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Thumbnail-Qualität', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="range" min="40" max="100" name="cpc_media_thumbnail_quality" value="'.(int)get_option('cpc_media_thumbnail_quality', 85).'" style="width:150px;" />&nbsp;<span id="cpc_media_quality_display">85%</span>';
    echo '<span class="description">'.__('Qualität der erzeugten Thumbnails (40-100%)', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>';

    // TAB: LIGHTBOX
    echo '<div class="cpc_media_admin_tab_content" id="cpc_media_admin_tab_lightbox">';
    echo '<table class="form-table">';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Lightbox aktivieren', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_media_enable_lightbox" '.(get_option('cpc_media_enable_lightbox', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Modal-Fullscreen-Anzeige beim Klicken auf Bilder/Videos', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Lightbox-Auto-Play', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_media_lightbox_autoplay" '.(get_option('cpc_media_lightbox_autoplay', 0) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Videos automatisch abspielen', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Lightbox-Loop', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_media_lightbox_loop" '.(get_option('cpc_media_lightbox_loop', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Nach dem letzten Item zum ersten zurückspringen', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Reorder-Funktion aktivieren', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_media_enable_reorder" '.(get_option('cpc_media_enable_reorder', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Drag-and-Drop zur Umordnung der Medien', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Cover-Auswahl aktivieren', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_media_enable_cover_selector" '.(get_option('cpc_media_enable_cover_selector', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Besitzer können Vorschau-Bild wählen', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>';

    // TAB: PRIVACY
    echo '<div class="cpc_media_admin_tab_content" id="cpc_media_admin_tab_privacy">';
    echo '<table class="form-table">';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Freundes-Sichtbarkeit', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><input type="checkbox" style="width:20px; height:20px;" name="cpc_media_enable_friend_visibility" '.(get_option('cpc_media_enable_friend_visibility', 1) ? 'CHECKED' : '').' />';
    echo '<span class="description">'.__('Option "Nur Freunde" für Profil-Galerien (benötigt Freundschafts-Modul)', CPC2_TEXT_DOMAIN).'</span></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Standardstatus Profil-Galerien', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><select name="cpc_media_default_member_status" style="min-width:200px;">';
    foreach (cpc_media_get_gallery_status_options('members') as $value => $label) {
        echo '<option value="'.esc_attr($value).'"'.selected(cpc_media_get_default_gallery_status('members'), $value, false).'>'.esc_html($label).'</option>';
    }
    echo '</select></td>';
    echo '</tr>';

    echo '<tr class="form-field">';
    echo '<td scope="row" valign="top"><label>'.__('Standardstatus Gruppen-Galerien', CPC2_TEXT_DOMAIN).'</label></td>';
    echo '<td><select name="cpc_media_default_group_status" style="min-width:200px;">';
    foreach (cpc_media_get_gallery_status_options('groups') as $value => $label) {
        echo '<option value="'.esc_attr($value).'"'.selected(cpc_media_get_default_gallery_status('groups'), $value, false).'>'.esc_html($label).'</option>';
    }
    echo '</select></td>';
    echo '</tr>';

    echo '</table>';
    echo '</div>';

    echo '</div>';

    // CSS fuer Tabs
    echo '<style>
    .cpc_media_admin_tabs {
        display: flex;
        gap: 6px;
        margin: 12px 0 0;
        border-bottom: 1px solid #ccd0d4;
        flex-wrap: wrap;
    }

    .cpc_media_admin_tab {
        padding: 8px 12px;
        background: #f1f1f1;
        border: 1px solid #ccd0d4;
        border-bottom: none;
        border-radius: 4px 4px 0 0;
        cursor: pointer;
        font-weight: 600;
        color: #2c3338;
        user-select: none;
    }

    .cpc_media_admin_tab:hover {
        background: #ffffff;
    }

    .cpc_media_admin_tab.active {
        background: #ffffff;
        color: #135e96;
        margin-bottom: -1px;
    }

    .cpc_media_admin_tab_content {
        display: none;
        border: 1px solid #ccd0d4;
        border-top: none;
        background: #fff;
        padding: 6px 16px 12px;
    }

    .cpc_media_admin_tab_content.active {
        display: block;
    }
    </style>';

    // JavaScript für Tabs
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        document.querySelectorAll(".cpc_media_admin_tab").forEach(tab => {
            tab.addEventListener("click", function() {
                const tabName = this.getAttribute("data-tab");
                document.querySelectorAll(".cpc_media_admin_tab").forEach(t => t.classList.remove("active"));
                document.querySelectorAll(".cpc_media_admin_tab_content").forEach(c => c.classList.remove("active"));
                this.classList.add("active");
                document.getElementById("cpc_media_admin_tab_" + tabName).classList.add("active");
            });
        });

        // Quality Slider
        const qualityInput = document.querySelector("input[name=\"cpc_media_thumbnail_quality\"]");
        if (qualityInput) {
            const display = document.getElementById("cpc_media_quality_display");
            qualityInput.addEventListener("input", function() {
                display.textContent = this.value + "%";
            });
        }
    });
    </script>';
}

add_action('cpc_admin_setup_form_get_hook', 'cpc_admin_media_save', 10, 2);
add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_media_save', 10, 2);
function cpc_admin_media_save($the_post) {
    // GENERAL
    if (isset($the_post['cpc_media_module_enabled'])) {
        update_option('cpc_media_module_enabled', 1);
    } else {
        delete_option('cpc_media_module_enabled');
    }

    if (isset($the_post['cpc_media_user_can_create_galleries'])) {
        update_option('cpc_media_user_can_create_galleries', 1);
    } else {
        delete_option('cpc_media_user_can_create_galleries');
    }

    if (isset($the_post['cpc_media_max_galleries_per_user'])) {
        update_option('cpc_media_max_galleries_per_user', max(0, (int)$the_post['cpc_media_max_galleries_per_user']));
    }

    // DISPLAY
    if (isset($the_post['cpc_media_gallery_layout'])) {
        update_option('cpc_media_gallery_layout', sanitize_key($the_post['cpc_media_gallery_layout']));
    }

    if (isset($the_post['cpc_media_gallery_grid_columns'])) {
        update_option('cpc_media_gallery_grid_columns', max(1, min(6, (int)$the_post['cpc_media_gallery_grid_columns'])));
    }

    if (isset($the_post['cpc_media_gallery_items_limit'])) {
        update_option('cpc_media_gallery_items_limit', max(1, min(100, (int)$the_post['cpc_media_gallery_items_limit'])));
    }

    if (isset($the_post['cpc_media_directory_page'])) {
        update_option('cpc_media_directory_page', max(0, (int)$the_post['cpc_media_directory_page']));
    }

    if (isset($the_post['cpc_media_directory_items_per_page'])) {
        update_option('cpc_media_directory_items_per_page', max(6, min(60, (int)$the_post['cpc_media_directory_items_per_page'])));
    }

    if (isset($the_post['cpc_media_show_gallery_descriptions'])) {
        update_option('cpc_media_show_gallery_descriptions', 1);
    } else {
        delete_option('cpc_media_show_gallery_descriptions');
    }

    if (isset($the_post['cpc_media_show_item_descriptions'])) {
        update_option('cpc_media_show_item_descriptions', 1);
    } else {
        delete_option('cpc_media_show_item_descriptions');
    }

    // UPLOAD
    if (isset($the_post['cpc_media_max_file_size'])) {
        update_option('cpc_media_max_file_size', max(1, min(500, (int)$the_post['cpc_media_max_file_size'])));
    }

    if (isset($the_post['cpc_media_activity_wall_sync'])) {
        update_option('cpc_media_activity_wall_sync', 1);
    } else {
        delete_option('cpc_media_activity_wall_sync');
    }

    if (isset($the_post['cpc_media_user_storage_limit_mb'])) {
        update_option('cpc_media_user_storage_limit_mb', max(0, min(10240, (int)$the_post['cpc_media_user_storage_limit_mb'])));
    }

    if (isset($the_post['cpc_media_group_storage_limit_mb'])) {
        update_option('cpc_media_group_storage_limit_mb', max(0, min(10240, (int)$the_post['cpc_media_group_storage_limit_mb'])));
    }

    if (isset($the_post['cpc_media_allowed_types']) && is_array($the_post['cpc_media_allowed_types'])) {
        $types = array_map('sanitize_key', $the_post['cpc_media_allowed_types']);
        update_option('cpc_media_allowed_types', array_filter($types));
    } else {
        update_option('cpc_media_allowed_types', array());
    }

    if (isset($the_post['cpc_media_thumbnail_quality'])) {
        update_option('cpc_media_thumbnail_quality', max(40, min(100, (int)$the_post['cpc_media_thumbnail_quality'])));
    }

    // LIGHTBOX
    if (isset($the_post['cpc_media_enable_lightbox'])) {
        update_option('cpc_media_enable_lightbox', 1);
    } else {
        delete_option('cpc_media_enable_lightbox');
    }

    if (isset($the_post['cpc_media_lightbox_autoplay'])) {
        update_option('cpc_media_lightbox_autoplay', 1);
    } else {
        delete_option('cpc_media_lightbox_autoplay');
    }

    if (isset($the_post['cpc_media_lightbox_loop'])) {
        update_option('cpc_media_lightbox_loop', 1);
    } else {
        delete_option('cpc_media_lightbox_loop');
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

    // PRIVACY
    if (isset($the_post['cpc_media_enable_friend_visibility'])) {
        update_option('cpc_media_enable_friend_visibility', 1);
    } else {
        delete_option('cpc_media_enable_friend_visibility');
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
