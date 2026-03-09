<?php

add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_activity_plus', 5);
function cpc_admin_getting_started_activity_plus() {

    if (strpos(CPC_CORE_PLUGINS, 'core-activity') === false) {
        return;
    }

    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_activity_plus' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';
    echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" id="cpc_admin_getting_started_activity_plus_div" rel="cpc_admin_getting_started_activity_plus">'.__('Aktivität Plus', CPC2_TEXT_DOMAIN).'</div>';

    $display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_activity_plus' ? 'block' : 'none';
    echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_activity_plus" style="display:'.$display.'">';

    $enabled = get_option('cpc_activity_plus_enabled');
    $allow_images = get_option('cpc_activity_plus_allow_images');
    $allow_links = get_option('cpc_activity_plus_allow_links');
    $allow_video = get_option('cpc_activity_plus_allow_video');
    $max_images = get_option('cpc_activity_plus_max_images') ? (int)get_option('cpc_activity_plus_max_images') : 5;
    $cleanup_on_delete = get_option('cpc_activity_plus_cleanup_on_delete');
    $theme = function_exists('cpc_activity_plus_normalize_theme') ? cpc_activity_plus_normalize_theme(get_option('cpc_activity_plus_theme', 'default')) : get_option('cpc_activity_plus_theme', 'default');
    $alignment = function_exists('cpc_activity_plus_normalize_alignment') ? cpc_activity_plus_normalize_alignment(get_option('cpc_activity_plus_alignment', 'left')) : get_option('cpc_activity_plus_alignment', 'left');

    echo '<table class="form-table">';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_enabled">'.__('Aktivität Plus aktivieren', CPC2_TEXT_DOMAIN).'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_enabled" id="cpc_activity_plus_enabled" type="checkbox" style="width:10px" '.($enabled ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Aktiviert Medien-, Link- und Video-Erweiterungen für Aktivitätsbeiträge.', CPC2_TEXT_DOMAIN).'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_allow_images">'.__('Bilder erlauben', CPC2_TEXT_DOMAIN).'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_allow_images" id="cpc_activity_plus_allow_images" type="checkbox" style="width:10px" '.($allow_images ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Erlaubt das Hinzufügen von Bildern zu Aktivitätsbeiträgen.', CPC2_TEXT_DOMAIN).'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_allow_links">'.__('Link-Vorschau erlauben', CPC2_TEXT_DOMAIN).'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_allow_links" id="cpc_activity_plus_allow_links" type="checkbox" style="width:10px" '.($allow_links ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Erlaubt das Erstellen von Link-Vorschauen in Aktivitätsbeiträgen.', CPC2_TEXT_DOMAIN).'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_allow_video">'.__('Video-Embeds erlauben', CPC2_TEXT_DOMAIN).'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_allow_video" id="cpc_activity_plus_allow_video" type="checkbox" style="width:10px" '.($allow_video ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Erlaubt das Einbetten von Videos über URL in Aktivitätsbeiträgen.', CPC2_TEXT_DOMAIN).'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_max_images">'.__('Maximale Bilder pro Beitrag', CPC2_TEXT_DOMAIN).'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_max_images" id="cpc_activity_plus_max_images" type="text" style="width:60px" value="'.esc_attr($max_images).'" />';
            echo '<span class="description">'.__('Maximale Anzahl von Bildern pro Aktivitätsbeitrag.', CPC2_TEXT_DOMAIN).'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top"><label for="cpc_activity_plus_cleanup_on_delete">'.__('Dateien beim Löschen entfernen', CPC2_TEXT_DOMAIN).'</label></th>';
        echo '<td>';
            echo '<input name="cpc_activity_plus_cleanup_on_delete" id="cpc_activity_plus_cleanup_on_delete" type="checkbox" style="width:10px" '.($cleanup_on_delete ? 'CHECKED' : '').' />';
            echo '<span class="description">'.__('Entfernt zugehörige Aktivitätsdateien beim Löschen eines Beitrags.', CPC2_TEXT_DOMAIN).'</span>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top">'.__('Erscheinungsstil', CPC2_TEXT_DOMAIN).'</th>';
        echo '<td>';
            echo '<fieldset>';
                echo '<label style="display:block;margin-bottom:10px">';
                    echo '<input name="cpc_activity_plus_theme" type="radio" value="default" '.($theme === 'default' ? 'checked' : '').' /> ';
                    echo '<strong>'.__('Standard (klassisch)', CPC2_TEXT_DOMAIN).'</strong> - '.__('Einfaches Tabellen-Layout', CPC2_TEXT_DOMAIN);
                echo '</label>';
                echo '<label style="display:block;margin-bottom:10px">';
                    echo '<input name="cpc_activity_plus_theme" type="radio" value="new" '.($theme === 'new' ? 'checked' : '').' /> ';
                    echo '<strong>'.__('New', CPC2_TEXT_DOMAIN).'</strong> - '.__('Original Activity Plus Icon-Style', CPC2_TEXT_DOMAIN);
                echo '</label>';
                echo '<label style="display:block;margin-bottom:10px">';
                    echo '<input name="cpc_activity_plus_theme" type="radio" value="round" '.($theme === 'round' ? 'checked' : '').' /> ';
                    echo '<strong>'.__('Round', CPC2_TEXT_DOMAIN).'</strong> - '.__('Original Activity Plus Round-Buttons', CPC2_TEXT_DOMAIN);
                echo '</label>';
            echo '</fieldset>';
        echo '</td>';
    echo '</tr>';

    echo '<tr class="form-field">';
        echo '<th scope="row" valign="top">'.__('Bild-Ausrichtung', CPC2_TEXT_DOMAIN).'</th>';
        echo '<td>';
            echo '<fieldset>';
                echo '<label style="margin-right:20px">';
                    echo '<input name="cpc_activity_plus_alignment" type="radio" value="left" '.($alignment === 'left' ? 'checked' : '').' /> ';
                    echo __('Links', CPC2_TEXT_DOMAIN);
                echo '</label>';
                echo '<label>';
                    echo '<input name="cpc_activity_plus_alignment" type="radio" value="right" '.($alignment === 'right' ? 'checked' : '').' /> ';
                    echo __('Rechts', CPC2_TEXT_DOMAIN);
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

    if (isset($the_post['cpc_activity_plus_cleanup_on_delete'])):
        update_option('cpc_activity_plus_cleanup_on_delete', true);
    else:
        delete_option('cpc_activity_plus_cleanup_on_delete');
    endif;

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