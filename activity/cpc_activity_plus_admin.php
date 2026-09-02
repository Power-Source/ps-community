<?php

add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_activity_plus', 5);
function cpc_admin_getting_started_activity_plus() {

    if (strpos(CPC_CORE_PLUGINS, 'core-activity') === false) {
        return;
    }

    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_activity_plus' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';
    echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" id="cpc_admin_getting_started_activity_plus_div" rel="cpc_admin_getting_started_activity_plus">'.__('Aktivität Plus', 'cp-community').'</div>';

    $display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_activity_plus' ? 'block' : 'none';
    echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_activity_plus" style="display:'.$display.'">';

    $enabled = get_option('cpc_activity_plus_enabled');
    $allow_images = get_option('cpc_activity_plus_allow_images');
    $allow_links = get_option('cpc_activity_plus_allow_links');
    $allow_video = get_option('cpc_activity_plus_allow_video');
    $max_images = get_option('cpc_activity_plus_max_images') ? (int)get_option('cpc_activity_plus_max_images') : 5;
    $user_cloud_limit_mb = function_exists('cpc_activity_plus_normalize_user_cloud_limit_mb')
        ? cpc_activity_plus_normalize_user_cloud_limit_mb(get_option('cpc_activity_plus_user_cloud_limit_mb', 50))
        : 50;
    $media_max_width_unit = function_exists('cpc_activity_plus_normalize_media_max_width_unit')
        ? cpc_activity_plus_normalize_media_max_width_unit(get_option('cpc_activity_plus_media_max_width_unit', '%'))
        : '%';
    $media_max_width = function_exists('cpc_activity_plus_normalize_media_max_width_value')
        ? cpc_activity_plus_normalize_media_max_width_value(get_option('cpc_activity_plus_media_max_width', 100), $media_max_width_unit)
        : 100;
    $use_builtin_lightbox = get_option('cpc_activity_plus_use_builtin_lightbox');
    $cleanup_on_delete = get_option('cpc_activity_plus_cleanup_on_delete');
    $theme = function_exists('cpc_activity_plus_normalize_theme') ? cpc_activity_plus_normalize_theme(get_option('cpc_activity_plus_theme', 'default')) : get_option('cpc_activity_plus_theme', 'default');
    $alignment = function_exists('cpc_activity_plus_normalize_alignment') ? cpc_activity_plus_normalize_alignment(get_option('cpc_activity_plus_alignment', 'left')) : get_option('cpc_activity_plus_alignment', 'left');
    $wall_enabled = function_exists('cpc_activity_plus_global_wall_enabled') ? cpc_activity_plus_global_wall_enabled() : false;
    $wall_page_id = function_exists('cpc_activity_plus_global_wall_page_id') ? cpc_activity_plus_global_wall_page_id() : 0;
    $wall_title = function_exists('cpc_activity_plus_global_wall_title') ? cpc_activity_plus_global_wall_title() : __('Aktivitätswall', 'cp-community');
    $wall_per_page = function_exists('cpc_activity_plus_global_wall_per_page') ? cpc_activity_plus_global_wall_per_page() : 12;
    $wall_include_group_posts = function_exists('cpc_activity_plus_global_wall_include_group_posts') ? cpc_activity_plus_global_wall_include_group_posts() : true;
    $wall_exclude_system_posts = function_exists('cpc_activity_plus_global_wall_exclude_system_posts') ? cpc_activity_plus_global_wall_exclude_system_posts() : true;
    $wall_push_enabled = function_exists('cpc_activity_plus_global_wall_push_enabled') ? cpc_activity_plus_global_wall_push_enabled() : false;

    echo '<table class="form-table">';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_enabled">'.__('Aktivität Plus aktivieren', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_enabled" id="cpc_activity_plus_enabled" type="checkbox" style="width:10px" '.($enabled ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Aktiviert Medien-, Link- und Video-Erweiterungen für Aktivitätsbeiträge.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_allow_images">'.__('Bilder erlauben', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_allow_images" id="cpc_activity_plus_allow_images" type="checkbox" style="width:10px" '.($allow_images ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Erlaubt das Hinzufügen von Bildern zu Aktivitätsbeiträgen.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_allow_links">'.__('Link-Vorschau erlauben', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_allow_links" id="cpc_activity_plus_allow_links" type="checkbox" style="width:10px" '.($allow_links ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Erlaubt das Erstellen von Link-Vorschauen in Aktivitätsbeiträgen.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_allow_video">'.__('Video-Embeds erlauben', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_allow_video" id="cpc_activity_plus_allow_video" type="checkbox" style="width:10px" '.($allow_video ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Erlaubt das Einbetten von Videos über URL in Aktivitätsbeiträgen.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_max_images">'.__('Maximale Bilder pro Beitrag', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_max_images" id="cpc_activity_plus_max_images" type="text" style="width:60px" value="'.esc_attr($max_images).'" />';
            echo '<span class="description">'.__('Maximale Anzahl von Bildern pro Aktivitätsbeitrag.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    $user_cloud_network_managed = function_exists('cpc_multisite_is_user_cloud_network_scoped') && cpc_multisite_is_user_cloud_network_scoped();
    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_user_cloud_limit_mb">'.__('User-Cloud Größe Beschränken (MB)', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_user_cloud_limit_mb" id="cpc_activity_plus_user_cloud_limit_mb" type="number" min="1" max="10240" step="1" style="width:90px" value="'.esc_attr($user_cloud_limit_mb).'"'.($user_cloud_network_managed ? ' disabled="disabled"' : '').' />';
            echo '<span class="description">'.__('Maximaler Speicher pro Benutzer für Activity-Plus-Dateien. Standard: 50 MB.', 'cp-community').'</span>';
            if ($user_cloud_network_managed) {
                echo '<span class="description" style="display:block">'.__('Dieses Limit wird im Netzwerk verwaltet.', 'cp-community').'</span>';
            }
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_media_max_width">'.__('Medien-Max-Breite', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_media_max_width" id="cpc_activity_plus_media_max_width" type="number" step="1" style="width:90px" value="'.esc_attr($media_max_width).'" />';
            echo '<select name="cpc_activity_plus_media_max_width_unit" id="cpc_activity_plus_media_max_width_unit" style="width:80px;margin-left:8px">';
                echo '<option value="%" '.selected($media_max_width_unit, '%', false).'>%</option>';
                echo '<option value="px" '.selected($media_max_width_unit, 'px', false).'>px</option>';
            echo '</select>';
            echo '<span class="description" style="display:block">'.__('Wähle Prozent oder Pixel. Bereiche: 10-100% oder 80-2400px. Standard: 100%.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_use_builtin_lightbox">'.__('BuiltIn Lightbox benutzen', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_use_builtin_lightbox" id="cpc_activity_plus_use_builtin_lightbox" type="checkbox" style="width:10px" '.($use_builtin_lightbox ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Öffnet Activity-Plus-Bilder in einer integrierten Lightbox mit Schließen-Button und Beschreibungsoverlay.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_cleanup_on_delete">'.__('Dateien beim Löschen entfernen', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_cleanup_on_delete" id="cpc_activity_plus_cleanup_on_delete" type="checkbox" style="width:10px" '.($cleanup_on_delete ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Entfernt zugehörige Aktivitätsdateien beim Löschen eines Beitrags.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field"><th scope="row" valign="top">'.__('Globale Aktivitätswall', 'cp-community').'</th><td><strong>'.__('Öffentliche Community-Wall', 'cp-community').'</strong><br /><span class="description">'.__('Zeigt öffentliche manuelle User-Posts und optional öffentliche Gruppenposts in einem globalen Feed.', 'cp-community').'</span></td></tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_global_wall_enabled">'.__('Globale Wall aktivieren', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_global_wall_enabled" id="cpc_activity_plus_global_wall_enabled" type="checkbox" style="width:10px" '.($wall_enabled ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Aktiviert den Shortcode [cpc-activity-wall] und die automatische Ausgabe auf der gewählten Seite.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_global_wall_page">'.__('Wall-Seite', 'cp-community').'</label></th>';
        echo '<td>';
            echo wp_dropdown_pages(array(
                'name' => 'cpc_activity_plus_global_wall_page',
                'echo' => 0,
                'selected' => $wall_page_id,
                'show_option_none' => __('Keine/Deaktiviert', 'cp-community'),
            ));
            echo '<label style="display:block;margin-top:8px"><input name="cpc_activity_plus_global_wall_create_page" type="checkbox" style="width:10px" /> '.__('Falls keine Seite gewählt ist, automatisch eine Wall-Seite erstellen', 'cp-community').'</label>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_global_wall_title">'.__('Wall-Titel', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_global_wall_title" id="cpc_activity_plus_global_wall_title" type="text" class="regular-text" value="'.esc_attr($wall_title).'" />';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_global_wall_per_page">'.__('Beiträge pro Ladung', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_global_wall_per_page" id="cpc_activity_plus_global_wall_per_page" type="number" min="5" max="50" step="1" style="width:90px" value="'.esc_attr($wall_per_page).'" />';
            echo '<span class="description">'.__('Anzahl der Einträge pro AJAX-Ladung in der globalen Wall.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_global_wall_include_group_posts">'.__('Öffentliche Gruppenposts einbeziehen', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_global_wall_include_group_posts" id="cpc_activity_plus_global_wall_include_group_posts" type="checkbox" style="width:10px" '.($wall_include_group_posts ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Nimmt manuelle Aktivitätsposts aus öffentlichen Gruppen zusätzlich in die globale Wall auf.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_global_wall_exclude_system_posts">'.__('Systemmeldungen ausblenden', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_global_wall_exclude_system_posts" id="cpc_activity_plus_global_wall_exclude_system_posts" type="checkbox" style="width:10px" '.($wall_exclude_system_posts ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Zeigt nur echte User-Posts. Automatische Meldungen wie erstellt/beigetreten bleiben in den jeweiligen Kontext-Streams.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_global_wall_push_enabled">'.__('Push-Hook für neue Wall-Posts', 'cp-community').'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_global_wall_push_enabled" id="cpc_activity_plus_global_wall_push_enabled" type="checkbox" style="width:10px" '.($wall_push_enabled ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Löst bei neuen global sichtbaren Wall-Posts den Hook cpc_activity_wall_push_hook aus, damit bestehende Push-Integrationen andocken können.', 'cp-community').'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top">'.__('Erscheinungsstil', 'cp-community').'</th>';
        echo '<td>';
            echo '<fieldset>';
                echo '<label style="display:block;margin-bottom:10px">';
                    echo '<input name="cpc_activity_plus_theme" type="radio" value="default" '.($theme === 'default' ? 'checked' : '').' /> ';
                    echo '<strong>'.__('Standard (klassisch)', 'cp-community').'</strong> - '.__('Einfaches Tabellen-Layout', 'cp-community');
                echo '</label>';
                echo '<label style="display:block;margin-bottom:10px">';
                    echo '<input name="cpc_activity_plus_theme" type="radio" value="new" '.($theme === 'new' ? 'checked' : '').' /> ';
                    echo '<strong>'.__('New', 'cp-community').'</strong> - '.__('Original Activity Plus Icon-Style', 'cp-community');
                echo '</label>';
                echo '<label style="display:block;margin-bottom:10px">';
                    echo '<input name="cpc_activity_plus_theme" type="radio" value="round" '.($theme === 'round' ? 'checked' : '').' /> ';
                    echo '<strong>'.__('Round', 'cp-community').'</strong> - '.__('Original Activity Plus Round-Buttons', 'cp-community');
                echo '</label>';
            echo '</fieldset>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top">'.__('Bild-Ausrichtung', 'cp-community').'</th>';
        echo '<td>';
            echo '<fieldset>';
                echo '<label style="margin-right:20px">';
                    echo '<input name="cpc_activity_plus_alignment" type="radio" value="left" '.($alignment === 'left' ? 'checked' : '').' /> ';
                    echo __('Links', 'cp-community');
                echo '</label>';
                echo '<label>';
                    echo '<input name="cpc_activity_plus_alignment" type="radio" value="right" '.($alignment === 'right' ? 'checked' : '').' /> ';
                    echo __('Rechts', 'cp-community');
                echo '</label>';
            echo '</fieldset>';
        echo '</td>';
    echo '</tr>';

    echo '</table>';

    echo '</div>';
}

add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_activity_plus_options_save', 20, 1);
function cpc_admin_activity_plus_options_save($the_post) {

    if (strpos(CPC_CORE_PLUGINS, 'core-activity') === false) {
        return;
    }

    if (isset($the_post['cpc_activity_plus_enabled'])):
        update_option('cpc_activity_plus_enabled', true);
    else:
        delete_option('cpc_activity_plus_enabled');
    endif;

    if (isset($the_post['cpc_activity_plus_allow_images'])):
        update_option('cpc_activity_plus_allow_images', true);
    else:
        delete_option('cpc_activity_plus_allow_images');
    endif;

    if (isset($the_post['cpc_activity_plus_allow_links'])):
        update_option('cpc_activity_plus_allow_links', true);
    else:
        delete_option('cpc_activity_plus_allow_links');
    endif;

    if (isset($the_post['cpc_activity_plus_allow_video'])):
        update_option('cpc_activity_plus_allow_video', true);
    else:
        delete_option('cpc_activity_plus_allow_video');
    endif;

    if (isset($the_post['cpc_activity_plus_max_images']) && is_numeric($the_post['cpc_activity_plus_max_images'])):
        $max_images = (int)$the_post['cpc_activity_plus_max_images'];
        if ($max_images < 1) $max_images = 1;
        if ($max_images > 20) $max_images = 20;
        update_option('cpc_activity_plus_max_images', $max_images);
    else:
        update_option('cpc_activity_plus_max_images', 5);
    endif;

    if (!function_exists('cpc_multisite_is_user_cloud_network_scoped') || !cpc_multisite_is_user_cloud_network_scoped()) {
        if (isset($the_post['cpc_activity_plus_user_cloud_limit_mb']) && is_numeric($the_post['cpc_activity_plus_user_cloud_limit_mb'])):
            $user_cloud_limit_mb = (int)$the_post['cpc_activity_plus_user_cloud_limit_mb'];
            if ($user_cloud_limit_mb < 1) $user_cloud_limit_mb = 1;
            if ($user_cloud_limit_mb > 10240) $user_cloud_limit_mb = 10240;
            update_option('cpc_activity_plus_user_cloud_limit_mb', $user_cloud_limit_mb);
        else:
            update_option('cpc_activity_plus_user_cloud_limit_mb', 50);
        endif;
    }

    $media_max_width_unit = (isset($the_post['cpc_activity_plus_media_max_width_unit']) && in_array($the_post['cpc_activity_plus_media_max_width_unit'], array('%', 'px'), true))
        ? $the_post['cpc_activity_plus_media_max_width_unit']
        : '%';
    update_option('cpc_activity_plus_media_max_width_unit', $media_max_width_unit);

    if (isset($the_post['cpc_activity_plus_media_max_width']) && is_numeric($the_post['cpc_activity_plus_media_max_width'])):
        $media_max_width = (int)$the_post['cpc_activity_plus_media_max_width'];
        if ($media_max_width_unit === 'px') {
            if ($media_max_width < 80) $media_max_width = 80;
            if ($media_max_width > 2400) $media_max_width = 2400;
        } else {
            if ($media_max_width < 10) $media_max_width = 10;
            if ($media_max_width > 100) $media_max_width = 100;
        }
        update_option('cpc_activity_plus_media_max_width', $media_max_width);
    else:
        update_option('cpc_activity_plus_media_max_width', 100);
    endif;

    if (isset($the_post['cpc_activity_plus_use_builtin_lightbox'])):
        update_option('cpc_activity_plus_use_builtin_lightbox', true);
    else:
        delete_option('cpc_activity_plus_use_builtin_lightbox');
    endif;

    if (isset($the_post['cpc_activity_plus_cleanup_on_delete'])):
        update_option('cpc_activity_plus_cleanup_on_delete', true);
    else:
        delete_option('cpc_activity_plus_cleanup_on_delete');
    endif;

    if (isset($the_post['cpc_activity_plus_global_wall_enabled'])):
        update_option('cpc_activity_plus_global_wall_enabled', true);
    else:
        delete_option('cpc_activity_plus_global_wall_enabled');
    endif;

    if (isset($the_post['cpc_activity_plus_global_wall_page'])):
        update_option('cpc_activity_plus_global_wall_page', max(0, (int)$the_post['cpc_activity_plus_global_wall_page']));
    endif;

    if (isset($the_post['cpc_activity_plus_global_wall_title'])):
        update_option('cpc_activity_plus_global_wall_title', sanitize_text_field($the_post['cpc_activity_plus_global_wall_title']));
    endif;

    if (isset($the_post['cpc_activity_plus_global_wall_per_page']) && is_numeric($the_post['cpc_activity_plus_global_wall_per_page'])):
        update_option('cpc_activity_plus_global_wall_per_page', max(5, min(50, (int)$the_post['cpc_activity_plus_global_wall_per_page'])));
    else:
        update_option('cpc_activity_plus_global_wall_per_page', 12);
    endif;

    if (isset($the_post['cpc_activity_plus_global_wall_include_group_posts'])):
        update_option('cpc_activity_plus_global_wall_include_group_posts', 1);
    else:
        delete_option('cpc_activity_plus_global_wall_include_group_posts');
    endif;

    if (isset($the_post['cpc_activity_plus_global_wall_exclude_system_posts'])):
        update_option('cpc_activity_plus_global_wall_exclude_system_posts', 1);
    else:
        delete_option('cpc_activity_plus_global_wall_exclude_system_posts');
    endif;

    if (isset($the_post['cpc_activity_plus_global_wall_push_enabled'])):
        update_option('cpc_activity_plus_global_wall_push_enabled', 1);
    else:
        delete_option('cpc_activity_plus_global_wall_push_enabled');
    endif;

    if (!empty($the_post['cpc_activity_plus_global_wall_create_page']) && function_exists('cpc_activity_plus_maybe_create_global_wall_page')) {
        cpc_activity_plus_maybe_create_global_wall_page();
    }

    if (isset($the_post['cpc_activity_plus_theme']) && in_array($the_post['cpc_activity_plus_theme'], array('default', 'new', 'round', 'modern', 'compact'))):
        $theme = $the_post['cpc_activity_plus_theme'];
        if ($theme === 'modern') $theme = 'new';
        if ($theme === 'compact') $theme = 'round';
        update_option('cpc_activity_plus_theme', $theme);
    else:
        update_option('cpc_activity_plus_theme', 'default');
    endif;

    if (isset($the_post['cpc_activity_plus_alignment']) && in_array($the_post['cpc_activity_plus_alignment'], array('left', 'right'))):
        update_option('cpc_activity_plus_alignment', $the_post['cpc_activity_plus_alignment']);
    else:
        update_option('cpc_activity_plus_alignment', 'left');
    endif;
}

?>