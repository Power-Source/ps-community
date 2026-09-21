<?php

function cpc_additional_shortcode_registry() {
    return array(
        array('function' => 'cpc_activity_wall', 'shortcode' => 'cpc-activity-wall', 'module' => 'Aktivitaet', 'description' => 'Zeigt die globale Aktivitaetswall.', 'fields' => array('title' => array('text', 'Titel', 'Aktivitaetswall'), 'page_size' => array('number', 'Eintraege pro Seite', 12), 'show_post_form' => array('checkbox', 'Beitragsformular anzeigen', true)), 'styles' => array('cpc_activity_wall' => 'Wall', 'cpc_activity_wall_title' => 'Titel', 'cpc_activity_items' => 'Eintragsliste', 'cpc_activity_item' => 'Eintrag')),
        array('function' => 'cpc_profile_slot', 'shortcode' => 'cpc-profile-slot', 'module' => 'Aktivitaet', 'description' => 'Gibt Inhalte in einem Profil-Slot aus.', 'fields' => array('slot' => array('text', 'Slot', 'profile_header_right'), 'fallback' => array('text', 'Ersatztext', '')), 'styles' => array('cpc_profile_slot' => 'Slot-Container')),
        array('function' => 'cpc_events', 'shortcode' => 'cpc-events', 'module' => 'Events', 'description' => 'Zeigt kommende oder vergangene Events.', 'fields' => array('limit' => array('number', 'Maximale Events', 12), 'upcoming' => array('checkbox', 'Nur kommende Events', true)), 'styles' => array('cpc-events-list' => 'Event-Liste', 'cpc-event-card' => 'Event-Karte')),
        array('function' => 'cpc_docs_directory', 'shortcode' => 'cpc-docs-directory', 'module' => 'Verzeichnisse', 'description' => 'Zeigt das globale Dokumentenverzeichnis.', 'fields' => array('per_page' => array('number', 'Eintraege pro Seite', 12)), 'styles' => array('cpc_docs_directory' => 'Verzeichnis', 'cpc_docs_directory_header' => 'Kopfbereich', 'cpc_docs_directory_title' => 'Titel', 'cpc_docs_directory_filters' => 'Filter')),
        array('function' => 'cpc_members_directory', 'shortcode' => 'cpc-members-directory', 'module' => 'Mitglieder', 'description' => 'Zeigt das Mitgliederverzeichnis.', 'fields' => array('per_page' => array('number', 'Eintraege pro Seite', 24), 'show_search' => array('checkbox', 'Suche anzeigen', true), 'show_atoz' => array('checkbox', 'A-Z-Filter anzeigen', true), 'show_last_active' => array('checkbox', 'Letzte Aktivitaet anzeigen', true), 'show_actions' => array('checkbox', 'Aktionen anzeigen', true)), 'styles' => array('cpc-members-directory' => 'Verzeichnis', 'cpc-members-grid' => 'Raster', 'cpc-member-card' => 'Mitgliederkarte', 'cpc-member-name' => 'Mitgliedername')),
        array('function' => 'cpc_invite', 'shortcode' => 'cpc-invite', 'module' => 'Einladungen', 'description' => 'Erlaubt angemeldeten Mitgliedern, Einladungen zu versenden.', 'fields' => array('redirect' => array('text', 'Weiterleitungsziel', '/')), 'styles' => array('cpc-invite-form-wrap' => 'Formularbereich', 'cpc-invite-form' => 'Formular')),
        array('function' => 'cpc_gallery_list', 'shortcode' => 'cpc-gallery-list', 'module' => 'Medien', 'description' => 'Listet Galerien auf.', 'fields' => array('limit' => array('number', 'Maximale Galerien', 20), 'show_count' => array('checkbox', 'Medienanzahl anzeigen', true), 'show_context' => array('checkbox', 'Kontext anzeigen', true)), 'styles' => array('cpc_gallery_list' => 'Galerieliste', 'cpc_gallery_list_item' => 'Galerie-Eintrag', 'cpc_gallery_list_title' => 'Galerietitel')),
        array('function' => 'cpc_media_directory', 'shortcode' => 'cpc-media-directory', 'module' => 'Medien', 'description' => 'Zeigt das Medien- oder Galerieverzeichnis.', 'fields' => array('view' => array('select', 'Startansicht', 'galleries', array('galleries' => 'Galerien', 'media' => 'Medien')), 'per_page' => array('number', 'Eintraege pro Seite', 12)), 'styles' => array('cpc_media_directory' => 'Verzeichnis', 'cpc_media_directory_header' => 'Kopfbereich', 'cpc_media_directory_tabs' => 'Tabs', 'mpp-media-card' => 'Medienkarte')),
        array('function' => 'cpc_gallery_items', 'shortcode' => 'cpc-gallery-items', 'module' => 'Medien', 'description' => 'Zeigt Medien einer Galerie.', 'fields' => array('limit' => array('number', 'Maximale Medien', 24)), 'styles' => array('cpc_gallery_items' => 'Medienraster', 'cpc_gallery_item' => 'Medieneintrag', 'cpc_gallery_item_meta' => 'Metadaten')),
        array('function' => 'cpc_projects_directory', 'shortcode' => 'cpc-projects-directory', 'module' => 'Verzeichnisse', 'description' => 'Zeigt das Projektverzeichnis.', 'fields' => array('per_page' => array('number', 'Eintraege pro Seite', 12)), 'styles' => array('cpc_projects_directory' => 'Verzeichnis', 'cpc_projects_directory_header' => 'Kopfbereich', 'cpc_projects_cards' => 'Projektkarten')),
        array('function' => 'cpc_friends_block_button', 'shortcode' => 'cpc-friends-block-button', 'module' => 'Freunde', 'description' => 'Zeigt eine Schaltflaeche zum Blockieren eines Mitglieds.', 'fields' => array('block_label' => array('text', 'Blockieren-Label', 'Blockieren'), 'unblock_label' => array('text', 'Aufheben-Label', 'Blockierung aufheben')), 'styles' => array('cpc_friends_block' => 'Blockieren-Schaltflaeche', 'cpc_friends_unblock' => 'Aufheben-Schaltflaeche')),
        array('function' => 'cpc_group_leave_button', 'shortcode' => 'cpc-group-leave-button', 'module' => 'Gruppen', 'description' => 'Zeigt eine Schaltflaeche zum Verlassen einer Gruppe.', 'fields' => array('text' => array('text', 'Beschriftung', 'Gruppe verlassen')), 'styles' => array('cpc-group-leave-btn' => 'Schaltflaeche')),
        array('function' => 'cpc_is_friend_content', 'shortcode' => 'cpc-is-friend-content', 'module' => 'Bedingt', 'description' => 'Zeigt eingeschlossene Inhalte nur bei Freundschaften.', 'fields' => array('not_friends_msg' => array('text', 'Text ohne Freundschaft', 'Tut mir leid, ihr seid keine Freunde.'), 'include_friendship_action' => array('checkbox', 'Freundschaftsaktion anzeigen', true)), 'styles' => array('cpc_is_friend_content' => 'Inhaltsbereich')),
        array('function' => 'cpc_user_exists_content', 'shortcode' => 'cpc-user-exists-content', 'module' => 'Bedingt', 'description' => 'Zeigt eingeschlossene Inhalte nur fuer existierende Mitglieder.', 'fields' => array('not_found_msg' => array('text', 'Text bei fehlendem Mitglied', 'Benutzer existiert nicht!')), 'styles' => array('cpc_user_exists_content' => 'Inhaltsbereich')),
        array('function' => 'cpc_groups_list', 'shortcode' => 'cpc-groups', 'module' => 'Gruppen', 'description' => 'Zeigt die Gruppenliste.', 'fields' => array('limit' => array('number', 'Maximale Gruppen', -1), 'columns' => array('number', 'Spalten', 2)), 'styles' => array('cpc-groups-list' => 'Gruppenliste', 'cpc-groups-grid' => 'Raster', 'cpc-group-card' => 'Gruppenkarte')),
        array('function' => 'cpc_group_single', 'shortcode' => 'cpc-group-single', 'module' => 'Gruppen', 'description' => 'Zeigt eine einzelne Gruppe.', 'fields' => array('show_description' => array('checkbox', 'Beschreibung anzeigen', true), 'show_members' => array('checkbox', 'Mitglieder anzeigen', true), 'show_actions' => array('checkbox', 'Aktionen anzeigen', true)), 'styles' => array('cpc-group-single' => 'Gruppe', 'cpc-group-header' => 'Kopfbereich', 'cpc-group-title' => 'Titel')),
        array('function' => 'cpc_group_members', 'shortcode' => 'cpc-group-members', 'module' => 'Gruppen', 'description' => 'Zeigt Mitglieder einer Gruppe.', 'fields' => array('limit' => array('number', 'Maximale Mitglieder', -1), 'columns' => array('number', 'Spalten', 4), 'show_role' => array('checkbox', 'Rolle anzeigen', true)), 'styles' => array('cpc-group-members' => 'Mitgliederbereich', 'cpc-members-grid' => 'Raster', 'cpc-member-card' => 'Mitgliederkarte')),
        array('function' => 'cpc_my_groups', 'shortcode' => 'cpc-my-groups', 'module' => 'Gruppen', 'description' => 'Zeigt Gruppen des aktuellen Mitglieds.', 'fields' => array('columns' => array('number', 'Spalten', 3), 'show_role' => array('checkbox', 'Rolle anzeigen', true)), 'styles' => array('cpc-my-groups' => 'Meine Gruppen', 'cpc-my-groups-grid' => 'Raster', 'cpc-my-groups-card' => 'Gruppenkarte')),
        array('function' => 'cpc_group_create', 'shortcode' => 'cpc-group-create', 'module' => 'Gruppen', 'description' => 'Zeigt das Formular zum Erstellen einer Gruppe.', 'fields' => array('redirect' => array('text', 'Weiterleitungsziel', '')), 'styles' => array('cpc-group-create-form' => 'Formular', 'cpc-group-create-submit' => 'Speichern-Schaltflaeche')),
        array('function' => 'cpc_group_join_button', 'shortcode' => 'cpc-group-join-button', 'module' => 'Gruppen', 'description' => 'Zeigt die Beitreten- oder Verlassen-Schaltflaeche.', 'fields' => array('join_text' => array('text', 'Beitreten-Label', 'Beitreten'), 'leave_text' => array('text', 'Verlassen-Label', 'Verlassen')), 'styles' => array('cpc-group-join-btn' => 'Beitreten-Schaltflaeche', 'cpc-group-leave-btn' => 'Verlassen-Schaltflaeche')),
    );
}

function cpc_additional_shortcodes_add_tab($tabs) {
    $tabs[] = array('tab' => 'cpc_option_additional', 'option' => 'additional', 'title' => __('Weitere Shortcodes', 'cp-community'));
    return $tabs;
}
add_filter('cpc_options_show_tab_filter', 'cpc_additional_shortcodes_add_tab');
add_filter('cpc_styles_show_tab_filter', 'cpc_additional_shortcodes_add_tab');

function cpc_additional_shortcodes_show_list($tab, $selected) {
    if ($tab !== 'additional') {
        return;
    }

    foreach (cpc_additional_shortcode_registry() as $item) {
        echo cpc_show_shortcode($tab, $selected, 'additional', $item['function'].'_tab', $item['shortcode']);
    }
}
add_action('cpc_options_shortcode_hook', 'cpc_additional_shortcodes_show_list', 10, 2);

function cpc_additional_shortcodes_show_options($selected) {
    foreach (cpc_additional_shortcode_registry() as $item) {
        $values = get_option('cpc_shortcode_options_'.$item['function']);
        $values = is_array($values) ? $values : array();
        echo cpc_show_options($selected, $item['function'].'_tab');
        echo '<strong>'.esc_html__('Zweck:', 'cp-community').'</strong> '.esc_html($item['description']).'<br />';
        echo '<strong>'.esc_html__('Wie benutzen:', 'cp-community').'</strong> <code>['.esc_html($item['shortcode']).']</code>';
        echo '<p><strong>'.esc_html__('Optionen', 'cp-community').'</strong></p><table cellpadding="0" cellspacing="0" class="cpc_shortcode_value_row">';
        foreach ($item['fields'] as $key => $field) {
            $type = $field[0];
            $label = $field[1];
            $default = $field[2];
            $value = cpc_get_shortcode_default($values, $item['function'].'-'.$key, $default);
            echo '<tr><td>'.esc_html__($label, 'cp-community').'</td><td>';
            $name = esc_attr($item['function'].'-'.$key);
            if ($type === 'checkbox') {
                echo '<input type="checkbox" name="'.$name.'"'.($value ? ' CHECKED' : '').' />';
            } elseif ($type === 'select') {
                echo '<select name="'.$name.'">';
                foreach ($field[3] as $option => $option_label) {
                    echo '<option value="'.esc_attr($option).'"'.selected($value, $option, false).'>'.esc_html($option_label).'</option>';
                }
                echo '</select>';
            } else {
                echo '<input type="'.$type.'" name="'.$name.'" value="'.esc_attr($value).'" />';
            }
            echo '</td><td>('.esc_html($key).'="'.esc_html($value).'")</td></tr>';
        }
        do_action('cpc_show_styling_options_hook', $item['function'], $values);
        echo '</table></div>';
    }
}
add_action('cpc_options_shortcode_options_hook', 'cpc_additional_shortcodes_show_options');

function cpc_additional_shortcodes_show_style_list($tab, $selected) {
    if ($tab !== 'additional') {
        return;
    }

    foreach (cpc_additional_shortcode_registry() as $item) {
        echo cpc_show_style($tab, $selected, 'additional', $item['function'].'_tab', $item['shortcode']);
    }
}
add_action('cpc_styles_shortcode_hook', 'cpc_additional_shortcodes_show_style_list', 10, 2);

function cpc_additional_shortcodes_show_style_options($selected) {
    static $rendered = false;

    if ($rendered) {
        return;
    }
    $rendered = true;

    foreach (cpc_additional_shortcode_registry() as $item) {
        $values = get_option('cpc_styles_'.$item['function']);
        $values = is_array($values) ? $values : array();
        echo cpc_show_options($selected, $item['function'].'_tab');
        echo '<table class="widefat fixed" cellspacing="0">';
        echo cpc_styles_header();
        foreach ($item['styles'] as $selector => $label) {
            echo cpc_styles_show_values(__($label, 'cp-community'), $selector, '#333333', '', '', 'off', 'off', $item['function'], $values);
        }
        echo '</table></div>';
    }
}
add_action('cpc_styles_shortcode_options_hook', 'cpc_additional_shortcodes_show_style_options');
