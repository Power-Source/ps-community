<?php

// Helper function to close options div
function cpc_show_options_close() {
    return '</div>';
}

// Default settings header
add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_options_header', 1);
function cpc_admin_getting_started_options_header() {
    // Default settings hook
    do_action( 'cpc_admin_getting_started_options_hook' );
    
}

// Add Default settings information
add_action('cpc_admin_getting_started_shortcodes_hook', 'cpc_admin_getting_started_options', 1);
function cpc_admin_getting_started_options() {
    
    echo '<div class="wrap">';
            
        echo '<style>';
            echo '.wrap { margin-top: 30px !important; margin-right: 10px !important; margin-left: 5px !important; }';
        echo '</style>';
        echo '<div id="cpc_release_notes">';
            echo '<div id="cpc_welcome_bar" style="margin-top: 20px;">';
                echo '<img id="cpc_welcome_logo" style="width:56px; height:56px; float:left;" src="'.plugins_url('../ps-community/css/images/cpc_logo.png', __FILE__).'" title="'.__('help', CPC2_TEXT_DOMAIN).'" />';
                echo '<div style="font-size:2em; line-height:1em; font-weight:100; color:#fff;">'.__('Willkommen bei CP-Community', CPC2_TEXT_DOMAIN).'</div>';
                echo '<p style="color:#fff;"><em>'.__('Das ultimative Plugin für soziale Netzwerke für ClassicPress', CPC2_TEXT_DOMAIN).'</em></p>';
            echo '</div>';

            $css = 'cpc_admin_getting_started_menu_item_remove_icon ';    
          	echo '<div style="margin-top:25px" class="'.$css.'cpc_admin_getting_started_menu_item_no_click" >'.__('CP-Community-Shortcodes und Standardeinstellungen', CPC2_TEXT_DOMAIN).'</div>';    
        	$display = 'block';
          	echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_options" style="display:'.$display.'">';
            
                echo '<div id="cpc_admin_getting_started_options_outline">';
            
                    // reset options?
                    if (isset($_GET['cpc_reset_options'])) {

                        global $wpdb;
                        $sql = "DELETE FROM ".$wpdb->prefix."options WHERE option_name like 'cpc_shortcode_options%'";
                        $wpdb->query($sql);
                        delete_option('cpccom_global_styles'); // global styles flag
                        echo '<div class="cpc_success" style="margin-top:20px">';
                            echo sprintf(__('Die Shortcode-Optionen der CP-Community wurden alle zurückgesetzt! <a href="%s">Weiter...</a>', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpc_com_shortcodes' ));
                        echo '</div>';

                    } else {
            
                        echo '<div id="cpc_admin_getting_started_options_help" style="margin-bottom:20px;'.(true || !isset($_POST['cpc_expand_shortcode']) ? '' : 'display:none;').'">';
                        echo __('Dieser Abschnitt bietet eine schnelle und einfache Möglichkeit, alle Shortcodes der CP-Community anzuzeigen und anzupassen, die jeweils zu jeder ClassicPress-Seite, jedem Beitrag oder jedem Text-Widget hinzugefügt werden können.', CPC2_TEXT_DOMAIN).'<br />';
                        echo sprintf(__('Wenn Du nicht sicher bist, welcher Shortcode auf einer ClassicPress-Seite verwendet wird, <a href="%s">bearbeite die Seite</a> und schaue im Seiteninhaltseditor nach.', CPC2_TEXT_DOMAIN), admin_url( 'edit.php?post_type=page' )).'</p>';
                        echo '<p style="margin-top:-8px">'.sprintf(__('Wähle in der linken Spalte einen allgemeinen Bereich und dann einen angezeigten Shortcode aus. Du kannst dann die Standardwerte sehen und festlegen und weitere Hilfe für diesen Shortcode erhalten. Um einen Wert zurückzusetzen, entferne den Wert und speichere ihn, oder <a onclick="return confirm(\''.__('Bist Du Dir sicher, dass dies nicht rückgängig gemacht werden kann?', CPC2_TEXT_DOMAIN).'\')" href="% s">Alle Shortcode-Optionen zurücksetzen</a>.', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpc_com_shortcodes&cpc_reset_options=1' )).' ';
                        echo __('Du kannst jedem Shortcode auch Optionen hinzufügen, wenn Du eine Seite/einen Beitrag/ein Widget bearbeitest, indem Du Shortcode-Optionen verwendest.', CPC2_TEXT_DOMAIN).'</p>';
                        echo '<div style="width:100%;text-align:center;"><div style="margin-left:auto;margin-right:auto;width:25%;padding:6px;background-color:#cfcfcf">'.sprintf('%s','<a href="javascript:void(0);" id="cpc_show_shortcodes_show">Beispiele zeigen</a><a href="javascript:void(0);" id="cpc_show_shortcodes_hide" style="display:none">Beispiele ausblenden</a>').'&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
                        echo sprintf('%s','<a href="javascript:void(0);" id="cpc_show_shortcodes_desc_show">Hilfeinformationen anzeigen</a><a href="javascript:void(0);" id="cpc_show_shortcodes_desc_hide" style="display:none">Hilfeinformationen ausblenden</a>').'</div></div>';
                        echo '</div>';

                        echo '<div id="cpc_admin_getting_started_options_please_wait">';
                            echo __('Bitte warten, Werte werden geladen...', CPC2_TEXT_DOMAIN);
                        echo '</div>';

                        echo '<div id="cpc_admin_getting_started_options_left_and_middle" style="display: none;">';
                            echo '<div id="cpc_admin_getting_started_options_left">';
                                /* TABS (1st column) */
                                $cpc_expand_tab = isset($_POST['cpc_expand_tab']) ? $_POST['cpc_expand_tab'] : 'activity';
                                $tabs = array();
                                array_push($tabs, array('tab' => 'cpc_option_activity',     'option' => 'activity',     'title' => __('Aktivität', CPC2_TEXT_DOMAIN)));
                                array_push($tabs, array('tab' => 'cpc_option_alerts',       'option' => 'alerts',       'title' => __('Benachrichtigungen', CPC2_TEXT_DOMAIN)));
                                array_push($tabs, array('tab' => 'cpc_option_avatar',       'option' => 'avatar',       'title' => __('Avatar', CPC2_TEXT_DOMAIN)));
                                array_push($tabs, array('tab' => 'cpc_option_forums',       'option' => 'forums',       'title' => __('Forum', CPC2_TEXT_DOMAIN)));
                                array_push($tabs, array('tab' => 'cpc_option_groups',       'option' => 'groups',       'title' => __('Gruppen', CPC2_TEXT_DOMAIN)));
                                array_push($tabs, array('tab' => 'cpc_option_friends',      'option' => 'friends',      'title' => __('Freunde', CPC2_TEXT_DOMAIN)));
                                array_push($tabs, array('tab' => 'cpc_option_conditional',  'option' => 'conditional',  'title' => __('Bedingt', CPC2_TEXT_DOMAIN)));
                                array_push($tabs, array('tab' => 'cpc_option_profile',      'option' => 'profile',      'title' => __('Profil', CPC2_TEXT_DOMAIN)));

                                // any more tabs?
                                $tabs = apply_filters( 'cpc_options_show_tab_filter', $tabs );

                                $sort = array();
                                foreach($tabs as $k=>$v) {
                                    $sort['title'][$k] = $v['title'];
                                }
                                array_multisort($sort['title'], SORT_ASC, $tabs);    

                                foreach ($tabs as $tab):
                                    echo cpc_show_tab($cpc_expand_tab, $tab['tab'], $tab['option'], $tab['title']);
                                endforeach;

                                echo '<div id="cpc_options_save_button" style="text-align:left"><input type="submit" id="cpc_shortcode_options_save_submit" name="Submit" class="button-primary" value="'.__('Shortcode-Optionen speichern', CPC2_TEXT_DOMAIN).'" /></div>';
                                echo '<span style="float:left" class="spinner"></span>';

                            echo '</div>';

                            echo '<div id="cpc_admin_getting_started_options_middle">';
                                /* SHORTCODES (2nd column) */
                                $cpc_expand_shortcode = isset($_POST['cpc_expand_shortcode']) ? $_POST['cpc_expand_shortcode'] : 'cpc_activity_page_tab';
                                // Activity Tab
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'activity', 'cpc_activity_tab', CPC_PREFIX.'-activity');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'activity', 'cpc_activity_page_tab', CPC_PREFIX.'-activity-page');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'activity', 'cpc_activity_post_tab', CPC_PREFIX.'-activity-post');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'activity', 'cpc_alerts_activity_tab', CPC_PREFIX.'-alerts-activity');
                                
                                // Alerts Tab
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'alerts', 'cpc_alerts_activity_tab', CPC_PREFIX.'-alerts-activity');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'alerts', 'cpc_alerts_friends_tab', CPC_PREFIX.'-alerts-friends');
                                
                                // Avatar Tab
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'avatar', 'cpc_avatar_tab', CPC_PREFIX.'-avatar');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'avatar', 'cpc_avatar_change_tab', CPC_PREFIX.'-avatar-change');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'avatar', 'cpc_avatar_change_link_tab', CPC_PREFIX.'-avatar-change-link');
                                
                                // Forums
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'forums', 'cpc_forum_tab', CPC_PREFIX.'-forum');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'forums', 'cpc_forum_backto_tab', CPC_PREFIX.'-forum-backto');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'forums', 'cpc_forum_comment_tab', CPC_PREFIX.'-forum-reply');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'forums', 'cpc_forum_page_tab', CPC_PREFIX.'-forum-page');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'forums', 'cpc_forum_post_tab', CPC_PREFIX.'-forum-post');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'forums', 'cpc_forums_tab', CPC_PREFIX.'-forums');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'forums', 'cpc_forum_sharethis_insert_tab', CPC_PREFIX.'-forum-sharethis');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'forums', 'cpc_forum_show_posts_tab', CPC_PREFIX.'-forum-show-posts');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'forums', 'cpc_forum_children_tab', CPC_PREFIX.'-forum-children');
                        
                                // Groups
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'groups', 'cpc_groups_list_tab', CPC_PREFIX.'-groups');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'groups', 'cpc_group_single_tab', CPC_PREFIX.'-group-single');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'groups', 'cpc_group_members_tab', CPC_PREFIX.'-group-members');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'groups', 'cpc_my_groups_tab', CPC_PREFIX.'-my-groups');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'groups', 'cpc_group_create_tab', CPC_PREFIX.'-group-create');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'groups', 'cpc_group_join_button_tab', CPC_PREFIX.'-group-join-button');
                        
                                // Conditional Tab
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'conditional', 'cpc_user_id_tab', CPC_PREFIX.'-user-id');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'conditional', 'cpc_is_logged_in_tab', CPC_PREFIX.'-is-logged-in');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'conditional', 'cpc_not_logged_in_tab', CPC_PREFIX.'-not-logged-in');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'conditional', 'cpc_is_forum_posts_list_tab', CPC_PREFIX.'-is-forum-posts-list');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'conditional', 'cpc_is_forum_single_post_tab', CPC_PREFIX.'-is-forum-single-post');

                                // Friendships
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'friends', 'cpc_friends_tab', CPC_PREFIX.'-friends');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'friends', 'cpc_friends_add_button_tab', CPC_PREFIX.'-friends-add-button');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'friends', 'cpc_friends_status_tab', CPC_PREFIX.'-friends-status');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'friends', 'cpc_friends_pending_tab', CPC_PREFIX.'-friends-pending');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'friends', 'cpc_alerts_friends_tab', CPC_PREFIX.'-alerts-friends');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'friends', 'cpc_friends_count_tab', CPC_PREFIX.'-friends-count');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'friends', 'cpc_favourite_friend_tab', CPC_PREFIX.'-favourite-friend');
                        
                                // Profile Tab
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'profile', 'cpc_activity_page_tab', CPC_PREFIX.'-activity-page');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'profile', 'cpc_display_name_tab', CPC_PREFIX.'-display-name');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'profile', 'cpc_usermeta_button_tab', CPC_PREFIX.'-usermeta-button');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'profile', 'cpc_usermeta_change_tab', CPC_PREFIX.'-usermeta-change');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'profile', 'cpc_usermeta_change_link_tab', CPC_PREFIX.'-usermeta-change-link');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'profile', 'cpc_usermeta_tab', CPC_PREFIX.'-usermeta');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'profile', 'cpc_close_account_tab', CPC_PREFIX.'-close-account');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'profile', 'cpc_join_site_tab', CPC_PREFIX.'-join-site');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'profile', 'cpc_last_logged_in_tab', CPC_PREFIX.'-last-logged-in');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'profile', 'cpc_last_active_tab', CPC_PREFIX.'-last-active');
                                echo cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, 'conditional', 'cpc_no_user_check', CPC_PREFIX.'-no-user-check');

                                // Group Tab

                                // any more shortcodes?
                                do_action('cpc_options_shortcode_hook', $cpc_expand_tab, $cpc_expand_shortcode);    

                            echo '</div>';
                        echo '</div>';    

                        echo '<div id="cpc_admin_getting_started_options_right" style="display: none;">';

                            /* ----------------------- GROUPS TAB ----------------------- */

                            // [cpc-groups]
                            $values = get_option('cpc_shortcode_options_'.'cpc_groups_list') ? get_option('cpc_shortcode_options_'.'cpc_groups_list') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_groups_list_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt eine Liste aller Gruppen an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-groups] zu einer ClassicPress-Seite hinzu.', CPC2_TEXT_DOMAIN);
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0" class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Gruppentyp', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $type = cpc_get_shortcode_default($values, 'cpc_groups_list-type', 'all');
                                        echo '<select name="cpc_groups_list-type">';
                                            echo '<option value="all"'.($type == 'all' ? ' SELECTED' : '').'>'.__('Alle', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="public"'.($type == 'public' ? ' SELECTED' : '').'>'.__('Öffentlich', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="private"'.($type == 'private' ? ' SELECTED' : '').'>'.__('Privat', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="hidden"'.($type == 'hidden' ? ' SELECTED' : '').'>'.__('Versteckt', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(type="'.$type.'")</td></tr>';
                                    echo '<tr><td>'.__('Spalten', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $columns = cpc_get_shortcode_default($values, 'cpc_groups_list-columns', '2');
                                        echo '<input type="text" name="cpc_groups_list-columns" value="'.$columns.'" style="width:50px"></td><td>(columns="'.$columns.'")</td></tr>';
                                    echo '<tr><td>'.__('Avatar anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_avatar = cpc_get_shortcode_default($values, 'cpc_groups_list-show_avatar', true);
                                        echo '<input type="checkbox" name="cpc_groups_list-show_avatar"'.($show_avatar ? ' CHECKED' : '').'></td><td>(show_avatar="'.($show_avatar ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Avatar-Größe', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $avatar_size = cpc_get_shortcode_default($values, 'cpc_groups_list-avatar_size', '50');
                                        echo '<input type="text" name="cpc_groups_list-avatar_size" value="'.$avatar_size.'" style="width:50px"></td><td>(avatar_size="'.$avatar_size.'")</td></tr>';
                                    echo '<tr><td>'.__('Beschreibung anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_description = cpc_get_shortcode_default($values, 'cpc_groups_list-show_description', true);
                                        echo '<input type="checkbox" name="cpc_groups_list-show_description"'.($show_description ? ' CHECKED' : '').'></td><td>(show_description="'.($show_description ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Beschreibungslänge', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $description_length = cpc_get_shortcode_default($values, 'cpc_groups_list-description_length', '150');
                                        echo '<input type="text" name="cpc_groups_list-description_length" value="'.$description_length.'" style="width:50px"></td><td>(description_length="'.$description_length.'")</td></tr>';
                                    echo '<tr><td>'.__('Mitgliederzahl anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_member_count = cpc_get_shortcode_default($values, 'cpc_groups_list-show_member_count', true);
                                        echo '<input type="checkbox" name="cpc_groups_list-show_member_count"'.($show_member_count ? ' CHECKED' : '').'></td><td>(show_member_count="'.($show_member_count ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Beitrittsbutton anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_join_button = cpc_get_shortcode_default($values, 'cpc_groups_list-show_join_button', true);
                                        echo '<input type="checkbox" name="cpc_groups_list-show_join_button"'.($show_join_button ? ' CHECKED' : '').'></td><td>(show_join_button="'.($show_join_button ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Suchfeld anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $search = cpc_get_shortcode_default($values, 'cpc_groups_list-search', true);
                                        echo '<input type="checkbox" name="cpc_groups_list-search"'.($search ? ' CHECKED' : '').'></td><td>(search="'.($search ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Limit', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $limit = cpc_get_shortcode_default($values, 'cpc_groups_list-limit', '-1');
                                        echo '<input type="text" name="cpc_groups_list-limit" value="'.$limit.'" style="width:50px"></td><td>(limit="'.$limit.'")</td></tr>';
                                echo '</table>';
                            echo cpc_show_options_close();

                            // [cpc-group-single]
                            $values = get_option('cpc_shortcode_options_'.'cpc_group_single') ? get_option('cpc_shortcode_options_'.'cpc_group_single') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_group_single_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt Details einer einzelnen Gruppe an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-group-single] zu einer Gruppenseite hinzu (wird automatisch die aktuelle Gruppe anzeigen).', CPC2_TEXT_DOMAIN);
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0" class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Gruppen-ID', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $group_id = cpc_get_shortcode_default($values, 'cpc_group_single-group_id', '');
                                        echo '<input type="text" name="cpc_group_single-group_id" value="'.$group_id.'" style="width:100px"></td><td>(group_id="'.$group_id.'")</td></tr>';
                                    echo '<tr><td colspan="3"><small>'.__('Leer lassen, um aktuelle Gruppe zu verwenden', CPC2_TEXT_DOMAIN).'</small></td></tr>';
                                    echo '<tr><td>'.__('Avatar anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_avatar = cpc_get_shortcode_default($values, 'cpc_group_single-show_avatar', true);
                                        echo '<input type="checkbox" name="cpc_group_single-show_avatar"'.($show_avatar ? ' CHECKED' : '').'></td><td>(show_avatar="'.($show_avatar ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Avatar-Größe', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $avatar_size = cpc_get_shortcode_default($values, 'cpc_group_single-avatar_size', '100');
                                        echo '<input type="text" name="cpc_group_single-avatar_size" value="'.$avatar_size.'" style="width:50px"></td><td>(avatar_size="'.$avatar_size.'")</td></tr>';
                                    echo '<tr><td>'.__('Beschreibung anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_description = cpc_get_shortcode_default($values, 'cpc_group_single-show_description', true);
                                        echo '<input type="checkbox" name="cpc_group_single-show_description"'.($show_description ? ' CHECKED' : '').'></td><td>(show_description="'.($show_description ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Mitglieder anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_members = cpc_get_shortcode_default($values, 'cpc_group_single-show_members', true);
                                        echo '<input type="checkbox" name="cpc_group_single-show_members"'.($show_members ? ' CHECKED' : '').'></td><td>(show_members="'.($show_members ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Aktionen anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_actions = cpc_get_shortcode_default($values, 'cpc_group_single-show_actions', true);
                                        echo '<input type="checkbox" name="cpc_group_single-show_actions"'.($show_actions ? ' CHECKED' : '').'></td><td>(show_actions="'.($show_actions ? '1' : '0').'")</td></tr>';
                                echo '</table>';
                            echo cpc_show_options_close();

                            // [cpc-group-members]
                            $values = get_option('cpc_shortcode_options_'.'cpc_group_members') ? get_option('cpc_shortcode_options_'.'cpc_group_members') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_group_members_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt die Mitglieder einer Gruppe an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-group-members] zu einer Seite hinzu.', CPC2_TEXT_DOMAIN);
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0" class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Gruppen-ID', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $group_id = cpc_get_shortcode_default($values, 'cpc_group_members-group_id', '');
                                        echo '<input type="text" name="cpc_group_members-group_id" value="'.$group_id.'" style="width:100px"></td><td>(group_id="'.$group_id.'")</td></tr>';
                                    echo '<tr><td colspan="3"><small>'.__('Leer lassen, um aktuelle Gruppe zu verwenden', CPC2_TEXT_DOMAIN).'</small></td></tr>';
                                    echo '<tr><td>'.__('Rolle filtern', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $role = cpc_get_shortcode_default($values, 'cpc_group_members-role', '');
                                        echo '<select name="cpc_group_members-role">';
                                            echo '<option value=""'.($role == '' ? ' SELECTED' : '').'>'.__('Alle', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="admin"'.($role == 'admin' ? ' SELECTED' : '').'>'.__('Admin', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="moderator"'.($role == 'moderator' ? ' SELECTED' : '').'>'.__('Moderator', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="member"'.($role == 'member' ? ' SELECTED' : '').'>'.__('Mitglied', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(role="'.$role.'")</td></tr>';
                                    echo '<tr><td>'.__('Spalten', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $columns = cpc_get_shortcode_default($values, 'cpc_group_members-columns', '4');
                                        echo '<input type="text" name="cpc_group_members-columns" value="'.$columns.'" style="width:50px"></td><td>(columns="'.$columns.'")</td></tr>';
                                    echo '<tr><td>'.__('Avatar anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_avatar = cpc_get_shortcode_default($values, 'cpc_group_members-show_avatar', true);
                                        echo '<input type="checkbox" name="cpc_group_members-show_avatar"'.($show_avatar ? ' CHECKED' : '').'></td><td>(show_avatar="'.($show_avatar ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Avatar-Größe', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $avatar_size = cpc_get_shortcode_default($values, 'cpc_group_members-avatar_size', '50');
                                        echo '<input type="text" name="cpc_group_members-avatar_size" value="'.$avatar_size.'" style="width:50px"></td><td>(avatar_size="'.$avatar_size.'")</td></tr>';
                                    echo '<tr><td>'.__('Rolle anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_role = cpc_get_shortcode_default($values, 'cpc_group_members-show_role', true);
                                        echo '<input type="checkbox" name="cpc_group_members-show_role"'.($show_role ? ' CHECKED' : '').'></td><td>(show_role="'.($show_role ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Limit', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $limit = cpc_get_shortcode_default($values, 'cpc_group_members-limit', '-1');
                                        echo '<input type="text" name="cpc_group_members-limit" value="'.$limit.'" style="width:50px"></td><td>(limit="'.$limit.'")</td></tr>';
                                echo '</table>';
                            echo cpc_show_options_close();

                            // [cpc-my-groups]
                            $values = get_option('cpc_shortcode_options_'.'cpc_my_groups') ? get_option('cpc_shortcode_options_'.'cpc_my_groups') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_my_groups_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt die Gruppen des aktuell angemeldeten Benutzers an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-my-groups] zu einer ClassicPress-Seite hinzu.', CPC2_TEXT_DOMAIN);
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0" class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Spalten', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $columns = cpc_get_shortcode_default($values, 'cpc_my_groups-columns', '3');
                                        echo '<input type="text" name="cpc_my_groups-columns" value="'.$columns.'" style="width:50px"></td><td>(columns="'.$columns.'")</td></tr>';
                                    echo '<tr><td>'.__('Avatar anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_avatar = cpc_get_shortcode_default($values, 'cpc_my_groups-show_avatar', true);
                                        echo '<input type="checkbox" name="cpc_my_groups-show_avatar"'.($show_avatar ? ' CHECKED' : '').'></td><td>(show_avatar="'.($show_avatar ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Avatar-Größe', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $avatar_size = cpc_get_shortcode_default($values, 'cpc_my_groups-avatar_size', '50');
                                        echo '<input type="text" name="cpc_my_groups-avatar_size" value="'.$avatar_size.'" style="width:50px"></td><td>(avatar_size="'.$avatar_size.'")</td></tr>';
                                    echo '<tr><td>'.__('Rolle anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_role = cpc_get_shortcode_default($values, 'cpc_my_groups-show_role', true);
                                        echo '<input type="checkbox" name="cpc_my_groups-show_role"'.($show_role ? ' CHECKED' : '').'></td><td>(show_role="'.($show_role ? '1' : '0').'")</td></tr>';
                                echo '</table>';
                            echo cpc_show_options_close();

                            // [cpc-group-create]
                            $values = get_option('cpc_shortcode_options_'.'cpc_group_create') ? get_option('cpc_shortcode_options_'.'cpc_group_create') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_group_create_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt ein Formular zum Erstellen einer neuen Gruppe an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-group-create] zu einer ClassicPress-Seite hinzu.', CPC2_TEXT_DOMAIN);
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0" class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Weiterleitung nach Erstellung', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $redirect = cpc_get_shortcode_default($values, 'cpc_group_create-redirect', '');
                                        echo '<input type="text" name="cpc_group_create-redirect" value="'.$redirect.'" style="width:200px"></td><td>(redirect="'.$redirect.'")</td></tr>';
                                    echo '<tr><td colspan="3"><small>'.__('URL zur Weiterleitung nach erfolgreicher Gruppenerstellung (optional)', CPC2_TEXT_DOMAIN).'</small></td></tr>';
                                echo '</table>';
                            echo cpc_show_options_close();

                            // [cpc-group-join-button]
                            $values = get_option('cpc_shortcode_options_'.'cpc_group_join_button') ? get_option('cpc_shortcode_options_'.'cpc_group_join_button') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_group_join_button_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt einen Beitreten/Verlassen-Button an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-group-join-button] zu einer Seite hinzu.', CPC2_TEXT_DOMAIN);
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0" class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Gruppen-ID', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $group_id = cpc_get_shortcode_default($values, 'cpc_group_join_button-group_id', '');
                                        echo '<input type="text" name="cpc_group_join_button-group_id" value="'.$group_id.'" style="width:100px"></td><td>(group_id="'.$group_id.'")</td></tr>';
                                    echo '<tr><td colspan="3"><small>'.__('Leer lassen, um aktuelle Gruppe zu verwenden', CPC2_TEXT_DOMAIN).'</small></td></tr>';
                                    echo '<tr><td>'.__('Beitrittstext', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $join_text = cpc_get_shortcode_default($values, 'cpc_group_join_button-join_text', __('Beitreten', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_group_join_button-join_text" value="'.$join_text.'" style="width:150px"></td><td>(join_text="'.$join_text.'")</td></tr>';
                                    echo '<tr><td>'.__('Verlassen-Text', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $leave_text = cpc_get_shortcode_default($values, 'cpc_group_join_button-leave_text', __('Verlassen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_group_join_button-leave_text" value="'.$leave_text.'" style="width:150px"></td><td>(leave_text="'.$leave_text.'")</td></tr>';
                                echo '</table>';
                            echo cpc_show_options_close();

                            /* ----------------------- CONDITIONAL TAB ----------------------- */    

                            // [cpc-user-id]
                            $values = get_option('cpc_shortcode_options_'.'cpc_conditional') ? get_option('cpc_shortcode_options_'.'cpc_conditional') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_user_id_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Gibt die Benutzer-ID des aktuellen Benutzers aus, oder, wenn auf einer Profilseite, diese Benutzer-ID.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-user-id] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-user-id/');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Keine Optionen', CPC2_TEXT_DOMAIN).'</td></tr>';

                                echo '</table>';
                            echo '</div>';    

                            // [cpc-is-logged-in]
                            $values = get_option('cpc_shortcode_options_'.'cpc_conditional') ? get_option('cpc_shortcode_options_'.'cpc_conditional') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_is_logged_in_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt Inhalte nur an, wenn der Browser (Benutzer) angemeldet ist.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-is-logged-in]INHALT[/cpc-is-logged-in] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-is-logged-in/');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';

                                    do_action('cpc_show_styling_options_hook', 'cpc_is_logged_in', $values);        

                                echo '</table>';
                            echo '</div>';    

                            // [cpc-not-logged-in]
                            $values = get_option('cpc_shortcode_options_'.'cpc_conditional') ? get_option('cpc_shortcode_options_'.'cpc_conditional') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_not_logged_in_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt Inhalte nur an, wenn der Browser (Benutzer) <strong>nicht</strong> angemeldet ist.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-not-logged-in]INHALT[/cpc-not-logged-in] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-not-logged-in/');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';

                                    do_action('cpc_show_styling_options_hook', 'cpc_not_logged_in', $values);        

                                echo '</table>';
                            echo '</div>';    

                            // [cpc-is-forum-posts-list]
                            $values = get_option('cpc_shortcode_options_'.'cpc_conditional') ? get_option('cpc_shortcode_options_'.'cpc_conditional') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_is_forum_posts_list_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt nur Inhalte an, wenn eine Liste von Forenbeiträgen angezeigt wird.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-is-forum-posts-list]INHALT[/cpc-is-forum-posts-list] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-is-forum-posts-list');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';

                                    do_action('cpc_show_styling_options_hook', 'cpc_is_forum_posts_list', $values);        

                                echo '</table>';
                            echo '</div>';    

                            // [cpc-is-forum-single-post]
                            $values = get_option('cpc_shortcode_options_'.'cpc_conditional') ? get_option('cpc_shortcode_options_'.'cpc_conditional') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_is_forum_single_post_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt Inhalte nur an, wenn ein einzelner Forumsbeitrag mit Antworten/Kommentaren angezeigt wird.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc_is_forum_single_post]INHALT[/cpc_is_forum_single_post] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-is-forum-single-post');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';

                                    do_action('cpc_show_styling_options_hook', 'cpc_is_forum_single_post', $values);        

                                echo '</table>';
                            echo '</div>';    

                                                
                            /* ----------------------- ACTIVITY TAB ----------------------- */    

                            // [cpc-activity]
                            $values = get_option('cpc_shortcode_options_'.'cpc_activity') ? get_option('cpc_shortcode_options_'.'cpc_activity') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_activity_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt den Aktivitätsfeed des Benutzers an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-activity] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-activity/');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Beziehe die eigene Aktivität des Benutzers ein", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $include_self = cpc_get_shortcode_default($values, 'cpc_activity-include_self', true);
                                        echo '<input type="checkbox" name="cpc_activity-include_self"'.($include_self ? ' CHECKED' : '').'></td><td>(include_self="'.($include_self ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Ob die Aktivität des Benutzers in den Aktivitätsstream aufgenommen werden soll oder nicht.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Beziehe die Aktivitäten der Freunde des Benutzers ein", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $include_friends = cpc_get_shortcode_default($values, 'cpc_activity-include_friends', true);
                                        echo '<input type="checkbox" name="cpc_activity-include_friends"'.($include_friends ? ' CHECKED' : '').'></td><td>(include_friends="'.($include_friends ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Ob Aktivitäten von Freunden des Benutzers einbezogen werden sollen oder nicht. Wenn Du dies tust, erhöht sich die Belastung des Servers.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Gib an, wie lange der Freund schon aktiv ist, damit die Aktivität einbezogen wird", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $active_friends = cpc_get_shortcode_default($values, 'cpc_activity-active_friends', 30);
                                        echo '<input type="text" name="cpc_activity-active_friends" value="'.$active_friends.'" /> '.__('Tage', CPC2_TEXT_DOMAIN).'</td><td>(active_friends="'.$active_friends.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Inaktive Freunde seit "x" Tagen ausschließen.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Anzahl der gleichzeitig angezeigten Aktivitätselemente', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $page_size = cpc_get_shortcode_default($values, 'cpc_activity-page_size', 10);
                                        echo '<input type="text" name="cpc_activity-page_size" value="'.$page_size.'" /></td><td>(page_size="'.$page_size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Wie viele angezeigt werden sollen, bevor mehr geladen wird. Halte einen niedrigeren Wert, um die Leistung zu verbessern.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Maximale Aktivitätsbeiträge, die für den Benutzer abgerufen wurden', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $get_max = cpc_get_shortcode_default($values, 'cpc_activity-get_max', 50);
                                        echo '<input type="text" name="cpc_activity-get_max" value="'.$get_max.'" /></td><td>(get_max="'.$get_max.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Vermeide eine zusätzliche Belastung Deines Servers, indem Du diese Zahl kleiner hältst.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Maximale Aktivitätsbeiträge, die von Freunden abgerufen wurden', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $get_max_friends = cpc_get_shortcode_default($values, 'cpc_activity-get_max_friends', 50);
                                        echo '<input type="text" name="cpc_activity-get_max_friends" value="'.$get_max.'" /></td><td>(get_max_friends="'.$get_max_friends.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Vermeide eine zusätzliche Belastung Deines Servers, indem Du diese Zahl kleiner hältst.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Avatargröße', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $avatar_size = cpc_get_shortcode_default($values, 'cpc_activity-avatar_size', 64);
                                        echo '<input type="text" name="cpc_activity-avatar_size" value="'.$avatar_size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(avatar_size="'.$avatar_size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Die Größe des Avatars des Benutzers, der neben dem Aktivitätsbeitrag angezeigt wird.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Wortbeschränkung für Beiträge', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $more = cpc_get_shortcode_default($values, 'cpc_activity-more', 50);
                                        echo '<input type="text" name="cpc_activity-more" value="'.$more.'" /> '.__('Wörter', CPC2_TEXT_DOMAIN).'</td><td>(more="'.$more.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Die maximal zulässige Anzahl von Wörtern in einem einzelnen Beitrag.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Text zum Anzeigen des vollständigen Beitrags', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $more_label = cpc_get_shortcode_default($values, 'cpc_activity-more_label', __('Mehr zeigen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-more_label" value="'.$more_label.'" /></td><td>(more_label="'.$more_label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Wenn lange Beiträge abgeschnitten werden, welcher Text angezeigt werden soll, um darauf zu klicken, um den Rest des Beitrags anzuzeigen.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Aktivität ausblenden, bis die Aktivität vollständig geladen ist", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $hide_until_loaded = cpc_get_shortcode_default($values, 'cpc_activity-hide_until_loaded', false);
                                        echo '<input type="checkbox" name="cpc_activity-hide_until_loaded"'.($hide_until_loaded ? ' CHECKED' : '').'></td><td>(hide_until_loaded="'.($hide_until_loaded ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Lege fest, dass mit der Anzeige gewartet wird, bis alle Aktivitäten geladen sind. Dadurch kann das Erscheinungsbild der Seite beim Laden besser verwaltet werden.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Größe des Kommentar-Avatars', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $comment_avatar_size = cpc_get_shortcode_default($values, 'cpc_activity-comment_avatar_size', 40);
                                        echo '<input type="text" name="cpc_activity-comment_avatar_size" value="'.$comment_avatar_size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(comment_avatar_size="'.$comment_avatar_size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Avatargröße für Kommentar.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Anzahl der angezeigten Kommentare', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $comment_size = cpc_get_shortcode_default($values, 'cpc_activity-comment_size', 5);
                                        echo '<input type="text" name="cpc_activity-comment_size" value="'.$comment_size.'" /></td><td>(comment_size="'.$comment_size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Anzahl der angezeigten Kommentare, vorherige werden ausgeblendet.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Text für mehrere vorherige Kommentare', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $comment_size_text_plural = cpc_get_shortcode_default($values, 'cpc_activity-comment_size_text_plural', __('Vorherige %d Kommentare anzeigen...', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-comment_size_text_plural" value="'.$comment_size_text_plural.'" /></td><td>(comment_size_text_plural="'.$comment_size_text_plural.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Text, der angezeigt wird, wenn mehr als 2 vorherige Kommentare vorhanden sind.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Text für einen vorherigen Kommentar', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $comment_size_text_singular = cpc_get_shortcode_default($values, 'cpc_activity-comment_size_text_singular', __('Vorherigen Kommentar anzeigen...', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-comment_size_text_singular" value="'.$comment_size_text_singular.'" /></td><td>(comment_size_text_singular="'.$comment_size_text_singular.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Text, der angezeigt wird, wenn 1 vorheriger Kommentar vorhanden ist.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Beschriftung für die Kommentar Schaltfläche.', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $label = cpc_get_shortcode_default($values, 'cpc_activity-label', __('Kommentar', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-label" value="'.$label.'" /></td><td>(label="'.$label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Beschriftung für den Kommentar-Button', CPC2_TEXT_DOMAIN).' :)';
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Optionale CSS-Klasse für die Schaltfläche', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $class = cpc_get_shortcode_default($values, 'cpc_activity-class', '');
                                        echo '<input type="text" name="cpc_activity-class" value="'.$class.'" /></td><td>(class="'.$class.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Füge der Schaltfläche eine CSS-Klasse hinzu.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Benutzernamen verweisen auf die Profilseite", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $link = cpc_get_shortcode_default($values, 'cpc_activity-link', true);
                                        echo '<input type="checkbox" name="cpc_activity-link"'.($link ? ' CHECKED' : '').'></td><td>(link="'.$link.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Ob Benutzernamen auf ihre Profilseite verweisen oder nicht.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Aktivität ist eine private Nachricht', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $private_msg = cpc_get_shortcode_default($values, 'cpc_activity-private_msg', __('Die Aktivität ist privat', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-private_msg" value="'.$private_msg.'" /></td><td>(private_msg="'.$private_msg.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Meldung wird angezeigt, wenn die Aktivität aufgrund der Datenschutzeinstellungen nicht sichtbar ist.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Beitrag existiert nicht mehr Nachricht', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $not_found = cpc_get_shortcode_default($values, 'cpc_activity-not_found', __('Leider ist dieser Aktivitätsbeitrag nicht mehr verfügbar.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-not_found" value="'.$not_found.'" /></td><td>(not_found="'.$not_found.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Meldung wird angezeigt, wenn die Nachricht nicht mehr existiert, wahrscheinlich gelöscht wurde.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Löschen Optionsbeschriftung', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $delete_label = cpc_get_shortcode_default($values, 'cpc_activity-delete_label', __('Löschen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-delete_label" value="'.$delete_label.'" /></td><td>(delete_label="'.$delete_label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Wort(e) für die Löschoption.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Sticky Options-Label', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $sticky_label = cpc_get_shortcode_default($values, 'cpc_activity-sticky_label', __('Sticky', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-sticky_label" value="'.$sticky_label.'" /></td><td>(sticky_label="'.$sticky_label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Wort(e) für die Sticky-Option.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Lösen Option-Label', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $unsticky_label = cpc_get_shortcode_default($values, 'cpc_activity-unsticky_label', __('Lösen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-unsticky_label" value="'.$unsticky_label.'" /></td><td>(unsticky_label="'.$unsticky_label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Wort(e) für die Option zum Aufheben des Stickys.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Ausblenden Optionsbeschriftung', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $hide_label = cpc_get_shortcode_default($values, 'cpc_activity-hide_label', __('Verstecken', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-hide_label" value="'.$hide_label.'" /></td><td>(hide_label="'.$hide_label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Wort(e) für die Ausblenden-Option.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Option Bericht einschließen.", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $report = cpc_get_shortcode_default($values, 'cpc_activity-report', true);
                                        echo '<input type="checkbox" name="cpc_activity-report"'.($report ? ' CHECKED' : '').'></td><td>(report="'.($report ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Ob die Berichtsoption angezeigt wird oder nicht.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Beschriftung der Berichtsoption', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $report_label = cpc_get_shortcode_default($values, 'cpc_activity-report_label', __('Melden', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-report_label" value="'.$report_label.'" /></td><td>(report_label="'.$report_label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Die Bezeichnung für die Berichtsoption, falls angezeigt.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Bericht E-Mail-Empfänger', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $report_email = cpc_get_shortcode_default($values, 'cpc_activity-report_email', get_bloginfo('admin_email'));
                                        echo '<input type="text" name="cpc_activity-report_email" value="'.$report_email.'" /></td><td>(report_email="'.$report_email.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Eine E-Mail-Adresse, an die Berichte gesendet werden.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Ehre die Sticky-Posts Deiner Freunde", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $stick_others = cpc_get_shortcode_default($values, 'cpc_activity-stick_others', false);
                                        echo '<input type="checkbox" name="cpc_activity-stick_others"'.($stick_others ? ' CHECKED' : '').'></td><td>(stick_others="'.($stick_others ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Wenn der Beitrag eines Freundes festgehalten wird, sollte er auch in der Profilaktivität des Benutzers festgehalten werden.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Kommentare erlauben", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $allow_replies = cpc_get_shortcode_default($values, 'cpc_activity-allow_replies', true);
                                        echo '<input type="checkbox" name="cpc_activity-allow_replies"'.($allow_replies ? ' CHECKED' : '').'></td><td>(allow_replies="'.($allow_replies ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Sollten Kommentare erlaubt sein?.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Datumsformat', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $date_format = cpc_get_shortcode_default($values, 'cpc_activity-date_format', __('vor %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-date_format" value="'.$date_format.'" /></td><td>(date_format="'.$date_format.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Das Format des Datums %s wird verwendet, um zu ersetzen, wie lange es her ist, dass der Beitrag verfasst wurde.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Abgemeldet-Meldung.', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $logged_out_msg = cpc_get_shortcode_default($values, 'cpc_activity-logged_out_msg', __('Du musst angemeldet sein, um die Profilseite anzuzeigen.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity-logged_out_msg" value="'.$logged_out_msg.'" /></td><td>(logged_out_msg="'.$logged_out_msg.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Es wird eine Meldung angezeigt, wenn man angemeldet sein muss, um die Aktivität zu sehen.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Optionale URL zum Anmelden", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $login_url = cpc_get_shortcode_default($values, 'cpc_activity-login_url', '');
                                        echo '<input type="text" name="cpc_activity-login_url" value="'.$login_url.'" /></td><td>(login_url="'.$login_url.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Ein optionaler Link zum Anmelden, kombiniert mit dem oben Gesagten.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_activity', $values);        

                                echo '</table>';
                            echo '</div>';    

                            // [cpc-activity-page]
                            $values = get_option('cpc_shortcode_options_'.'cpc_activity_page') ? get_option('cpc_shortcode_options_'.'cpc_activity_page') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_activity_page_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__('Zeigt eine Standardprofilseite mit allen eingerichteten allgemeinen Elementen an.', CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-activity-page] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-activity-page');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong></p>';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Größe des Avatars des Benutzers", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $user_avatar_size = cpc_get_shortcode_default($values, 'cpc_activity_page-user_avatar_size', 150);
                                        echo '<input type="text" name="cpc_activity_page-user_avatar_size" value="'.$user_avatar_size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(user_avatar_size="'.$user_avatar_size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Wie groß der Avatar des Benutzers ist", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Stil von Google Map', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $map_style = cpc_get_shortcode_default($values, 'cpc_activity_page-map_style', 'dynamic');
                                        echo '<select name="cpc_activity_page-map_style">';
                                            echo '<option value="static"'.($map_style == 'static' ? ' SELECTED' : '').'>'.__('Statisch', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="dynamic"'.($map_style == 'dynamic' ? ' SELECTED' : '').'>'.__('Dynamisch', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(map_style="'.$map_style.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Statisch ist ein Bild, dynamisch kann verschoben und vergrößert und verkleinert werden.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Größe von Google Map', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $map_size = cpc_get_shortcode_default($values, 'cpc_activity_page-map_size', '150,150');
                                        echo '<input type="text" name="cpc_activity_page-map_size" value="'.$map_size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(map_size="'.$map_size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Wie groß in Pixel.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Zoomstufe von Google Map', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $map_zoom = cpc_get_shortcode_default($values, 'cpc_activity_page-map_zoom', 4);
                                        echo '<input type="text" name="cpc_activity_page-map_zoom" value="'.$map_zoom.'" /></td><td>(map_zoom="'.$map_zoom.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Basierend auf dem von Google verwendeten Maßstab, der anfänglichen Zoomstufe.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Label für Stadt/Gemeinde', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $town_label = cpc_get_shortcode_default($values, 'cpc_activity_page-town_label', __('Stadt/Gemeinde', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity_page-town_label" value="'.$town_label.'" /></td><td>(town_label="'.$town_label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Normalerweise eine Stadt, kann aber jede Detailebene haben.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Beschriftung für Land', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $country_label = cpc_get_shortcode_default($values, 'cpc_activity_page-country_label', __('Land', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity_page-country_label" value="'.$country_label.'" /></td><td>(country_label="'.$country_label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Bezieht sich auf die Stadt, normalerweise auf das Land, kann jedoch einen anderen Detaillierungsgrad aufweisen.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Freundschaftsanfragen-Label.', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $requests_label = cpc_get_shortcode_default($values, 'cpc_activity_page-requests_label', __('Freundschaftsanfragen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity_page-requests_label" value="'.$requests_label.'" /></td><td>(requests_label="'.$requests_label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Label für Freundschaftsanfragen.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_activity_page', $values);        

                                echo '</table>';
                            echo '</div>';

                            // [cpc-activity-post]
                            $values = get_option('cpc_shortcode_options_'.'cpc_activity_post') ? get_option('cpc_shortcode_options_'.'cpc_activity_post') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_activity_post_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt einen Textbereich zum Hinzufügen eines Aktivitätsbeitrags an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-activity-post] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-activity-post/');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Optionale CSS-Klasse für die Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $class = cpc_get_shortcode_default($values, 'cpc_activity_post-class', '');
                                        echo '<input type="text" name="cpc_activity_post-class" value="'.$class.'" /></td><td>(class="'.$class.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Eine Klasse, die der Schaltfläche zum Stylen hinzugefügt werden soll.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung für Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $label = cpc_get_shortcode_default($values, 'cpc_activity_post-label', __('Beitrag hinzufügen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity_post-label" value="'.$label.'" /></td><td>(label="'.$label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Die Beschriftung auf der Schaltfläche.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Nachricht, das nur Freunde posten können", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $private_msg = cpc_get_shortcode_default($values, 'cpc_activity_post-private_msg', __('Du hast keine Berechtigung, hier etwas zu posten', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity_post-private_msg" value="'.$private_msg.'" /></td><td>(private_msg="'.$private_msg.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Meldung wird angezeigt, wenn das Posten in der Aktivität nicht gestattet ist.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Nachricht, dass das Konto geschlossen ist", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $account_closed_msg = cpc_get_shortcode_default($values, 'cpc_activity_post-account_closed_msg', __('Konto geschlossen.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_activity_post-account_closed_msg" value="'.$account_closed_msg.'" /></td><td>(account_closed_msg="'.$account_closed_msg.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Nachricht Konto ist geschlossen.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Symbol im Textbereich für neue Aktivitätsbeiträge", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $background_icon = cpc_get_shortcode_default($values, 'cpc_activity_post-background_icon', false);
                                        echo '<input type="checkbox" name="cpc_activity_post-background_icon"'.($background_icon ? ' CHECKED' : '').'></td><td>(background_icon="'.($background_icon ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Sollte im Textbereich des Beitrags ein kleines Symbol angezeigt werden?", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_activity_post', $values);

                                echo '</table>';
                            echo '</div>';
                        
                            // [cpc-display-name]
                            $values = get_option('cpc_shortcode_options_'.'cpc_display_name') ? get_option('cpc_shortcode_options_'.'cpc_display_name') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_display_name_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt den Anzeigenamen eines Benutzers an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-display-name] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-display-name/');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';

                                    echo '<tr><td>'.__('Benutzer', CPC2_TEXT_DOMAIN).'</td><td>';
                                    $user_id = cpc_get_shortcode_default($values, 'cpc_display_name-user_id', '');
                                    echo '<select name="cpc_display_name-user_id">';
                                        echo '<option value=""'.($user_id == '' ? ' SELECTED' : '').'>'.__('Spiegelt den Seitenkontext wider', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '<option value="user"'.($user_id == 'user' ? ' SELECTED' : '').'>'.__('Aktueller Benutzer', CPC2_TEXT_DOMAIN).'</option>';
                                    echo '</select> '.__('oder im Shortcode auf eine Benutzer-ID setzen', CPC2_TEXT_DOMAIN).'</td><td>(user_id="'.$user_id.'")</td></tr>';    
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Welcher Benutzer angezeigt werden soll. Kann auch über den Shortcode selbst auf eine Benutzer-ID festgelegt werden.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';    
                                    echo '<tr><td>'.__("Name verlinkt zur Profilseite", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $link = cpc_get_shortcode_default($values, 'cpc_display_name-link', false);
                                        echo '<input type="checkbox" name="cpc_display_name-link"'.($link ? ' CHECKED' : '').'></td><td>(link="'.($link ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Soll der Name auf die Profilseite verweisen?', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';    
                                    echo '<tr><td>'.__('Vor-/Nachnamen statt Anzeigenamen anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $firstlast = cpc_get_shortcode_default($values, 'cpc_display_name-firstlast', false);
                                        echo '<input type="checkbox" name="cpc_display_name-firstlast"'.($firstlast ? ' CHECKED' : '').'></td><td>(firstlast="'.($firstlast ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __('Zeigt Vor- und Nachnamen an, nützlich beim Sortieren nach Nachnamen. Überlege, ob die Leute diese Angaben tatsächlich auf Deiner Webseite ausfüllen.', CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';    
                        
                                    do_action('cpc_show_styling_options_hook', 'cpc_display_name', $values);

                                echo '</table>';
                            echo '</div>';                        

                            // [cpc-last-logged-in]
                            $values = get_option('cpc_shortcode_options_'.'cpc_last_logged_in') ? get_option('cpc_shortcode_options_'.'cpc_last_logged_in') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_last_logged_in_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt an, wann sich ein Benutzer zuletzt angemeldet hat.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-last-logged-in] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-last-logged-in/');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';

                                    echo '<tr><td>'.__('Benutzer', CPC2_TEXT_DOMAIN).'</td><td>';
                                    $user_id = cpc_get_shortcode_default($values, 'cpc_last_logged_in-user_id', '');
                                    echo '<select name="cpc_last_logged_in-user_id">';
                                        echo '<option value=""'.($user_id == '' ? ' SELECTED' : '').'>'.__('Spiegelt Seitenkontext wider', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '<option value="user"'.($user_id == 'user' ? ' SELECTED' : '').'>'.__('Aktueller Benutzer', CPC2_TEXT_DOMAIN).'</option>';
                                    echo '</select> '.__('oder im Shortcode auf eine Benutzer-ID setzen', CPC2_TEXT_DOMAIN).'</td><td>(user_id="'.$user_id.'")</td></tr>';   
                                    echo '<tr><td>'.__("Format für Datum", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $date_format = cpc_get_shortcode_default($values, 'cpc_last_logged_in-date_format', __('vor %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_last_logged_in-date_format" value="'.$date_format.'" /></td><td>(date_format="'.$date_format.'")</td></tr>';                        
                                    echo '<tr><td>'.__("Kein letzter Login-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $not_logged_in_msg = cpc_get_shortcode_default($values, 'cpc_last_logged_in-not_logged_in_msg', __('Kürzlich nicht angemeldet.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_last_logged_in-not_logged_in_msg" value="'.$not_logged_in_msg.'" /></td><td>(not_logged_in_msg="'.$not_logged_in_msg.'")</td></tr>';                        
                        
                                    do_action('cpc_show_styling_options_hook', 'cpc_last_logged_in', $values);

                                echo '</table>';
                            echo '</div>';
                        
                            // [cpc-last-active]
                            $values = get_option('cpc_shortcode_options_'.'cpc_last_active') ? get_option('cpc_shortcode_options_'.'cpc_last_active') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_last_active_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt an, wann ein Benutzer zuletzt aktiv war.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-last-active] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-last-active/');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';

                                    echo '<tr><td>'.__('Benutzer', CPC2_TEXT_DOMAIN).'</td><td>';
                                    $user_id = cpc_get_shortcode_default($values, 'cpc_last_active-user_id', '');
                                    echo '<select name="cpc_last_active-user_id">';
                                        echo '<option value=""'.($user_id == '' ? ' SELECTED' : '').'>'.__('Spiegelt Seitenkontext wider', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '<option value="user"'.($user_id == 'user' ? ' SELECTED' : '').'>'.__('Aktueller Benutzer', CPC2_TEXT_DOMAIN).'</option>';
                                    echo '</select> '.__('oder im Shortcode auf eine Benutzer-ID setzen', CPC2_TEXT_DOMAIN).'</td><td>(user_id="'.$user_id.'")</td></tr>';    

                                    echo '<tr><td>'.__("Format für Datum", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $date_format = cpc_get_shortcode_default($values, 'cpc_last_active-date_format', __('vor %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_last_active-date_format" value="'.$date_format.'" /></td><td>(date_format="'.$date_format.'")</td></tr>';                        
                                    echo '<tr><td>'.__("Kein letzter Login-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $not_logged_in_msg = cpc_get_shortcode_default($values, 'cpc_last_active-not_logged_in_msg', __('Kürzlich nicht angemeldet.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_last_active-not_logged_in_msg" value="'.$not_logged_in_msg.'" /></td><td>(not_logged_in_msg="'.$not_logged_in_msg.'")</td></tr>';                        
                        
                                    do_action('cpc_show_styling_options_hook', 'cpc_last_active', $values);

                                echo '</table>';
                            echo '</div>';                            

                            /* ----------------------- ALERTS TAB ----------------------- */

                            // [cpc-alerts-activity]
                            $values = get_option('cpc_shortcode_options_'.'cpc_alerts_activity') ? get_option('cpc_shortcode_options_'.'cpc_alerts_activity') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_alerts_activity_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt eine Dropdown-Liste mit Benachrichtigungen für den Benutzer an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-alerts-activity] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-alerts-activity');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Stil der Liste', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $style = cpc_get_shortcode_default($values, 'cpc_alerts_activity-style', 'dropdown');
                                        echo '<select name="cpc_alerts_activity-style">';
                                            echo '<option value="dropdown"'.($style == 'dropdown' ? ' SELECTED' : '').'>'.__('Dropdown Liste', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="list"'.($style == 'list' ? ' SELECTED' : '').'>'.__('Liste', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="flag"'.($style == 'flag' ? ' SELECTED' : '').'>'.__('Symbol', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(style="'.$style.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Wie die Benachrichtigungen dargestellt werden.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Symbolgröße", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_size = cpc_get_shortcode_default($values, 'cpc_alerts_activity-flag_size', 24);
                                        echo '<input type="text" name="cpc_alerts_activity-flag_size" value="'.$flag_size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(flag_size="'.$flag_size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Größe des Symbols (falls Symbol ausgewählt).", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Größe des Symbols für ungelesene Zahlen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_unread_size = cpc_get_shortcode_default($values, 'cpc_alerts_activity-flag_unread_size', 10);
                                        echo '<input type="text" name="cpc_alerts_activity-flag_unread_size" value="'.$flag_unread_size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(flag_unread_size="'.$flag_unread_size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Größe der ungelesenen Anzahl (falls Symbol ausgewählt).", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Symbol für ungelesene Nummer am oberen Rand", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_unread_top = cpc_get_shortcode_default($values, 'cpc_alerts_activity-flag_unread_top', 6);
                                        echo '<input type="text" name="cpc_alerts_activity-flag_unread_top" value="'.$flag_unread_top.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(flag_unread_top="'.$flag_unread_top.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Ermöglicht den oberen Rand der ungelesenen Nummer anzupassen (falls Symbol ausgewählt).", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Symbol ungelesene Nummer am linken Rand", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_unread_left = cpc_get_shortcode_default($values, 'cpc_alerts_activity-flag_unread_left', 8);
                                        echo '<input type="text" name="cpc_alerts_activity-flag_unread_left" value="'.$flag_unread_left.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(flag_unread_left="'.$flag_unread_left.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Ermöglicht den linken Rand der ungelesenen Nummer anzupassen (falls das Symbol ausgewählt wurde).", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Symbol für ungelesenen Zahlenradius", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_unread_radius = cpc_get_shortcode_default($values, 'cpc_alerts_activity-flag_unread_radius', 8);
                                        echo '<input type="text" name="cpc_alerts_activity-flag_unread_radius" value="'.$flag_unread_radius.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(flag_unread_radius="'.$flag_unread_radius.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Radius der Ecken für ungelesene Nachrichten, für ein Quadrat auf 0 gesetzt.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Symbol-URL", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_url = cpc_get_shortcode_default($values, 'cpc_alerts_activity-flag_url', '');
                                        echo '<input type="text" name="cpc_alerts_activity-flag_url" value="'.$flag_url.'" /></td><td>(flag_url="'.$flag_url.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("URL, zu der der Benutzer weitergeleitet wird, wenn er auf das Symbol klickt.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Alternativ-URL für das Symbolbild", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_src = cpc_get_shortcode_default($values, 'cpc_alerts_activity-flag_src', '');
                                        echo '<input type="text" name="cpc_alerts_activity-flag_src" value="'.$flag_src.'" /></td><td>(flag_src="'.$flag_src.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("URL eines Bildes, das anstelle des Standardsymbols als Symbol verwendet werden soll.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Aktueller Benachrichtigungstext", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $recent_alerts_text = cpc_get_shortcode_default($values, 'cpc_alerts_activity-recent_alerts_text', __('Aktuelle Benachrichtigungen...', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_alerts_activity-recent_alerts_text" value="'.$recent_alerts_text.'" /></td><td>(recent_alerts_text="'.$recent_alerts_text.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Text zur Kennzeichnung aktueller Benachrichtigungen.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Keine Benachrichtigungen-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $no_activity_text = cpc_get_shortcode_default($values, 'cpc_alerts_activity-no_activity_text', __('Keine Benachrichtigungen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_alerts_activity-no_activity_text" value="'.$no_activity_text.'" /></td><td>(no_activity_text="'.$no_activity_text.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Text für Benachrichtigungen über keine Aktivität.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Text für neue Benachrichtigungen, durch Kommas getrennt", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $select_activity_text = cpc_get_shortcode_default($values, 'cpc_alerts_activity-select_activity_text', __('Du hast 1 neue Benachrichtigung, Du hast %d neue Benachrichtigungen, Du hast keine neuen Benachrichtigungen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_alerts_activity-select_activity_text" value="'.$select_activity_text.'" /></td><td>(select_activity_text="'.$select_activity_text.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Die drei zu verwendenden Textversionen, getrennt durch ein Komma, für 1, 2+ und keine neuen Benachrichtigungen.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Alle als gelesenen markieren-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $make_all_read_text = cpc_get_shortcode_default($values, 'cpc_alerts_activity-make_all_read_text', __('Alles als gelesen markieren', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_alerts_activity-make_all_read_text" value="'.$make_all_read_text.'" /></td><td>(make_all_read_text="'.$make_all_read_text.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Text, um alle Benachrichtigungen als gelesen zu markieren.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Alles löschen-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $delete_all_text = cpc_get_shortcode_default($values, 'cpc_alerts_activity-delete_all_text', __('Alles löschen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_alerts_activity-delete_all_text" value="'.$delete_all_text.'" /></td><td>(delete_all_text="'.$delete_all_text.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Text zum Löschen aller Benachrichtigungen.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Datumsformat", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $date_format = cpc_get_shortcode_default($values, 'cpc_alerts_activity-date_format', __('vor %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_alerts_activity-date_format" value="'.$date_format.'" /></td><td>(date_format)</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Text für die Angabe, wie lange es her ist. Das %s wird durch die Anzahl der Tage (usw.) ersetzt.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Mit Klick löschen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $delete_on_click = cpc_get_shortcode_default($values, 'cpc_alerts_activity-delete_on_click', false);
                                        echo '<input type="checkbox" name="cpc_alerts_activity-delete_on_click"'.($delete_on_click ? ' CHECKED' : '').'></td><td>(delete_on_click="'.($delete_on_click ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Aktiviere diese Option, um die Warnung automatisch zu löschen, wenn darauf geklickt wird.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_alerts_activity', $values);        

                                echo '</table>';
                            echo '</div>';   

                            /* ----------------------- AVATAR TAB ----------------------- */

                            // [cpc-avatar]
                            $values = get_option('cpc_shortcode_options_'.'cpc_avatar') ? get_option('cpc_shortcode_options_'.'cpc_avatar') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_avatar_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt den Avatar eines Benutzers an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-avatar] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-avatar');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Größe des Avatars", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $size = cpc_get_shortcode_default($values, 'cpc_avatar-size', 256);
                                        echo '<input type="text" name="cpc_avatar-size" value="'.$size.'" /> '.__('Pixel oder ein %', CPC2_TEXT_DOMAIN).'</td><td>(size="'.$size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Größe des angezeigten Avatars in Pixel.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Link zum Ändern des Avatars anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $change_link = cpc_get_shortcode_default($values, 'cpc_avatar-change_link', false);
                                        echo '<input type="checkbox" name="cpc_avatar-change_link"'.($change_link ? ' CHECKED' : '').'></td><td>(change_link="'.($change_link ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Stil der Avatar-Änderung', CPC2_TEXT_DOMAIN).'</td><td>';
                                    $avatar_style = cpc_get_shortcode_default($values, 'cpc_avatar-avatar_style', 'page');
                                    echo '<select name="cpc_avatar-avatar_style">';
                                        echo '<option value="page"'.($avatar_style == 'page' ? ' SELECTED' : '').'>'.__('Gehe zur Seite Avatar ändern.', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '<option value="popup"'.($avatar_style == 'popup' ? ' SELECTED' : '').'>'.__('Verwende ein Popup', CPC2_TEXT_DOMAIN).'</option>';
                                    echo '</select></td><td>(avatar_style="'.$avatar_style.'")</td></tr>';    
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Bei Einstellung auf Seite wird ein Link zu einer Seite mit [cpc-avatar-change] angezeigt. Bei einem Popup wird auf demselben Bildschirm ein Feld angezeigt.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Text für Änderungslink", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $change_avatar_text = cpc_get_shortcode_default($values, 'cpc_avatar-change_avatar_text', __('Bild ändern', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar-change_avatar_text" value="'.$change_avatar_text.'" /></td><td>(change_avatar_text="'.$change_avatar_text.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Text, der angezeigt wird, um den Benutzer aufzufordern, seinen Avatar zu ändern.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Titel für Änderungslink/-feld", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $change_avatar_title = cpc_get_shortcode_default($values, 'cpc_avatar-change_avatar_title', __('Lade ein Bild hoch und schneide es zu, um es anzuzeigen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar-change_avatar_title" value="'.$change_avatar_title.'" /></td><td>(change_avatar_title="'.$change_avatar_title.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Titel für den Änderungslink, besonders relevant bei Verwendung des Popup-Stils.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';                        
                                    echo '<tr><td>'.__("Aufforderung ein Bild vom Computer auszuwählen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $upload_prompt = cpc_get_shortcode_default($values, 'cpc_avatar-upload_prompt', __('Wähle ein Bild von Deinem Gerät:', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar-upload_prompt" value="'.$upload_prompt.'" /></td><td>(upload_prompt="'.$upload_prompt.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Aufforderung bei Verwendung des Popup-Stils, ein Bild vom Computer auszuwählen.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';                        
                                    echo '<tr><td>'.__("Schaltfläche Hochladen (nur für Popup-Stil)", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $upload_button = cpc_get_shortcode_default($values, 'cpc_avatar-upload_button', __('Hochladen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar-upload_button" value="'.$upload_button.'" /></td><td>(upload_button="'.$upload_button.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Beschriftung für die Schaltfläche zum Hochladen für den Popup-Stil.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';                        
                                    echo '<tr><td>'.__("Breite des Popups (nur für Popup-Stil)", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $popup_width = cpc_get_shortcode_default($values, 'cpc_avatar-popup_width', 750);
                                        echo '<input type="text" name="cpc_avatar-popup_width" value="'.$popup_width.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(popup_width="'.$popup_width.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Breite des Popups, falls verwendet (in Pixel).", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';                        
                                    echo '<tr><td>'.__("Höhe des Popups (nur für Popup-Stil)", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $popup_height = cpc_get_shortcode_default($values, 'cpc_avatar-popup_height', 450);
                                        echo '<input type="text" name="cpc_avatar-popup_height" value="'.$popup_height.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(popup_height="'.$popup_height.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Höhe des Popups, falls verwendet (in Pixel).", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';                        
                                    echo '<tr><td>'.__("Avatar-Links zur Profilseite (falls nicht der Avatar des aktuellen Benutzers)", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $profile_link = cpc_get_shortcode_default($values, 'cpc_avatar-profile_link', false);
                                        echo '<input type="checkbox" name="cpc_avatar-profile_link"'.($profile_link ? ' CHECKED' : '').'></td><td>(profile_link="'.($profile_link ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Sollte das Avatarbild auf die Profilseite dieses Benutzers verweisen?", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Benutzer', CPC2_TEXT_DOMAIN).'</td><td>';
                                    $user_id = cpc_get_shortcode_default($values, 'cpc_avatar-user_id', '');
                                    echo '<select name="cpc_avatar-user_id">';
                                        echo '<option value=""'.($user_id == '' ? ' SELECTED' : '').'>'.__('Spiegelt Seitenkontext wider', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '<option value="user"'.($user_id == 'user' ? ' SELECTED' : '').'>'.__('Aktueller Benutzer', CPC2_TEXT_DOMAIN).'</option>';
                                    echo '</select> '.__('oder im Shortcode auf eine Benutzer-ID setzen', CPC2_TEXT_DOMAIN).'</td><td>(user_id="'.$user_id.'")</td></tr>';    
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Ob der Seitenkontext wiedergegeben werden soll (wie auf einer Profilseite) oder auf den aktuell angemeldeten Benutzer festgelegt werden soll. Kann auch auf eine bestimmte ClassicPress-Benutzer-ID eingestellt werden.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';

                        
                                    do_action('cpc_show_styling_options_hook', 'cpc_avatar', $values);        

                                echo '</table>';    
                            echo '</div>';    

                            // [cpc-avatar-change]
                            $values = get_option('cpc_shortcode_options_'.'cpc_avatar_change') ? get_option('cpc_shortcode_options_'.'cpc_avatar_change') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_avatar_change_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt das Formular an, mit dem Benutzer einen Avatar hochladen können.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-avatar-change] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-avatar-change');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Aufforderung zu Schritt 1", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $step1 = cpc_get_shortcode_default($values, 'cpc_avatar_change-step1', __('Schritt 1: Klicke auf diesen Link, um ein Bild auszuwählen, und klicke anschließend auf die Schaltfläche unten.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-step1" value="'.$step1.'" /></td><td>(step1="'.$step1.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Text, der für den ersten Schritt beim Hochladen eines neuen Avatars angezeigt werden soll.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Aufforderung zu Schritt 2", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $step2 = cpc_get_shortcode_default($values, 'cpc_avatar_change-step2', __('Schritt 2: Wähle zunächst einen Bereich auf Deinem hochgeladenen Bild aus und klicke dann auf die Schaltfläche Zuschneiden.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-step2" value="'.$step2.'" /></td><td>(step2="'.$step2.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Text für den zweiten Schritt.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung für die Schaltfläche Hochladen.", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $label = cpc_get_shortcode_default($values, 'cpc_avatar_change-label', __('Hochladen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-label" value="'.$label.'" /></td><td>(label="'.$label.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Beschriftung für die Schaltfläche zum Hochladen eines Bildes.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Text für Link zur Bildauswahl", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $choose = cpc_get_shortcode_default($values, 'cpc_avatar_change-choose', __('Klicke hier, um ein Bild auszuwählen... (maximal %dKB)', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-choose" value="'.$choose.'" /></td><td>(choose="'.$choose.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Text für den Link zur Bildauswahl (mache deutlich, was zu tun ist!).", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Versuche es erneut-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $try_again_msg = cpc_get_shortcode_default($values, 'cpc_avatar_change-try_again_msg', __('Versuche es erneut...', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-try_again_msg" value="'.$try_again_msg.'" /></td><td>(try_again_msg="'.$try_again_msg.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Versuche es erneut-Text.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Zulässige Dateitypen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $file_types_msg = cpc_get_shortcode_default($values, 'cpc_avatar_change-file_types_msg', __("Bitte lade eine Bilddatei hoch (.jpeg, .gif, .png).", CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-file_types_msg" value="'.$file_types_msg.'" /></td><td>(file_types_msg="'.$file_types_msg.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Der angezeigte Text erklärt, welche Dateitypen hochgeladen werden dürfen.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Avatar darf nicht geändert werden-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $not_permitted = cpc_get_shortcode_default($values, 'cpc_avatar_change-not_permitted', __("Es ist Dir nicht gestattet, diesen Avatar zu ändern.", CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-not_permitted" value="'.$not_permitted.'" /></td><td>(not_permitted="'.$not_permitted.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Text wird angezeigt, wenn der Avatar nicht geändert werden darf.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Datei zu groß-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $file_too_big_msg = cpc_get_shortcode_default($values, 'cpc_avatar_change-file_too_big_msg', __("Bitte lade eine Bilddatei hoch, die nicht größer als %dKB ist. Deine Datei war %dKB groß.", CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-file_too_big_msg" value="'.$file_too_big_msg.'" /></td><td>(file_too_big_msg="'.$file_too_big_msg.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Meldung, wenn die hochgeladene Datei zu groß ist.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Maximale Datei-Upload-Größe (KB)", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $max_file_size = cpc_get_shortcode_default($values, 'cpc_avatar_change-max_file_size', 500);
                                        echo '<input type="text" name="cpc_avatar_change-max_file_size" value="'.$max_file_size.'" /></td><td>(max_file_size="'.$max_file_size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Maximal zulässige Dateigröße zum Hochladen <strong>in KiloBytes</strong>.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Ermögliche Benutzern das Zuschneiden von Avataren', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $crop = cpc_get_shortcode_default($values, 'cpc_avatar_change-crop', true);
                                        echo '<input type="checkbox" name="cpc_avatar_change-crop"'.($crop ? ' CHECKED' : '').'></td><td>(crop="'.($crop ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Ob Benutzer hochgeladene Bilder zuschneiden dürfen (wähle einen zu verwendenden Bereich aus).", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__('Spezialeffekte aktivieren', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $effects = cpc_get_shortcode_default($values, 'cpc_avatar_change-effects', false);
                                        echo '<input type="checkbox" name="cpc_avatar_change-effects"'.($effects ? ' CHECKED' : '').'></td><td>(effects="'.($effects ? '1' : '0').'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Können Benutzer Spezialeffekte auf hochgeladene Bilder anwenden?", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Label für Flip", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flip = cpc_get_shortcode_default($values, 'cpc_avatar_change-flip', __("Flip", CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-flip" value="'.$flip.'" /></td><td>(flip="'.$flip.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Vertikaler Flip", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Label für Drehen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $rotate = cpc_get_shortcode_default($values, 'cpc_avatar_change-rotate', __("Drehen", CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-rotate" value="'.$rotate.'" /></td><td>(rotate="'.$rotate.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Um 90 Grad nach rechts drehen (kann mehrmals durchgeführt werden).", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Label für Invertieren", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $invert = cpc_get_shortcode_default($values, 'cpc_avatar_change-invert', __("Invertieren", CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-invert" value="'.$invert.'" /></td><td>(invert="'.$invert.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Kehre die Farbpalette um.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung für Skizze", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $sketch = cpc_get_shortcode_default($values, 'cpc_avatar_change-sketch', __("Skizzieren", CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-sketch" value="'.$sketch.'" /></td><td>(sketch="'.$sketch.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Lasse das Bild wie eine Skizze aussehen.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Label for Pixelate", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pixelate = cpc_get_shortcode_default($values, 'cpc_avatar_change-pixelate', __("Pixelate", CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-pixelate" value="'.$pixelate.'" /></td><td>(pixelate="'.$pixelate.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Vergrößere die Pixel im Bild, damit es blockiger aussieht...", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Label für Sepia", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $sepia = cpc_get_shortcode_default($values, 'cpc_avatar_change-sepia', __("Sepia", CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-sepia" value="'.$sepia.'" /></td><td>(sepia="'.$sepia.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Verwende dezente Creme- und Brauntöne.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Label zum Prägen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $emboss = cpc_get_shortcode_default($values, 'cpc_avatar_change-emboss', __("Prägen", CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-emboss" value="'.$emboss.'" /></td><td>(emboss="'.$emboss.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Verleihe dem Bild einen 3D-Look.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Muss angemeldet sein-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $logged_out_msg = cpc_get_shortcode_default($values, 'cpc_avatar_change-logged_out_msg', __("Du musst angemeldet sein, um diese Seite anzuzeigen.", CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change-logged_out_msg" value="'.$logged_out_msg.'" /></td><td>(logged_out_msg="'.$logged_out_msg.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Ist nicht angemeldet-Text", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Optionale URL zum Anmelden", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $login_url = cpc_get_shortcode_default($values, 'cpc_avatar_change-login_url', '');
                                        echo '<input type="text" name="cpc_avatar_change-login_url" value="'.$login_url.'" /></td><td>(login_url="'.$login_url.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Eine optionale URL einer Anmeldeseite, um den Besucher zu dieser Anmeldeseite zu leiten (und anschließend zurückzukehren).", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_avatar_change', $values);        

                                echo '</table>';    
                            echo '</div>';  

                            // [cpc-avatar-change-link]
                            $values = get_option('cpc_shortcode_options_'.'cpc_avatar_change_link') ? get_option('cpc_shortcode_options_'.'cpc_avatar_change_link') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_avatar_change_link_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt einen Link an, über den ein Benutzer seinen Avatar ändern kann.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-avatar-change-link] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-avatar-change-link');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Stil der Avatar-Änderung', CPC2_TEXT_DOMAIN).'</td><td>';
                                    $change_style = cpc_get_shortcode_default($values, 'cpc_avatar_change_link-change_style', 'page');
                                    echo '<select name="cpc_avatar_change_link-change_style">';
                                        echo '<option value="page"'.($change_style == 'page' ? ' SELECTED' : '').'>'.__('Gehe zur Seite Avatar ändern.', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '<option value="popup"'.($change_style == 'popup' ? ' SELECTED' : '').'>'.__('Verwende ein Popup', CPC2_TEXT_DOMAIN).'</option>';
                                    echo '</select></td><td>(change_style="'.$change_style.'")</td></tr>';    
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Bei Einstellung auf Seite wird ein Link zu einer Seite mit [cpc-avatar-change] angezeigt. Bei einem Popup wird auf demselben Bildschirm ein Feld angezeigt.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Für den Link angezeigter Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $text = cpc_get_shortcode_default($values, 'cpc_avatar_change_link-text', __('Bild ändern', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change_link-text" value="'.$text.'" /></td><td>($text="'.$text.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Text, der dem Benutzer angezeigt werden soll, damit er seinen Avatar ändern kann.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Titel für Änderungslink/-feld", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $change_avatar_title = cpc_get_shortcode_default($values, 'cpc_avatar_change_link-change_avatar_title', __('Lade ein Bild hoch und schneide es zu, um es anzuzeigen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_avatar_change_link-change_avatar_title" value="'.$change_avatar_title.'" /></td><td>(change_avatar_title="'.$change_avatar_title.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Titel für den Änderungslink, besonders relevant bei Verwendung des Popup-Stils.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';                        
                        
                                    do_action('cpc_show_styling_options_hook', 'cpc_avatar_change_link', $values);            

                                echo '</table>';    
                            echo '</div>';   

                            /* ----------------------- FORUMS TAB ----------------------- */

                            // [cpc-forum]
                            $values = get_option('cpc_shortcode_options_'.'cpc_forum') ? get_option('cpc_shortcode_options_'.'cpc_forum') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_forum_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt Themen (und Antworten, wenn der Stil auf klassisch eingestellt ist) eines Forums an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.sprintf(__('Füge [cpc-forum slug="xxx"] zu einer ClassicPress-Seite hinzu, wobei xxx der <a href="%s">Slug Deines Forums</a> ist.', CPC2_TEXT_DOMAIN), admin_url( 'edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post' ));
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-forum');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Stil', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $style = cpc_get_shortcode_default($values, 'cpc_forum-style', 'table');
                                        echo '<select name="cpc_forum-style">';
                                            echo '<option value="table"'.($style == 'table' ? ' SELECTED' : '').'>'.__('Tabelle', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="classic"'.($style == 'classic' ? ' SELECTED' : '').'>'.__('Klassisch', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(style="'.$style.'")</td></tr>';    
                                    echo '<tr><td>'.__('Basisdatum', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $base_date = cpc_get_shortcode_default($values, 'cpc_forum_page-base_date', 'post_date_gmt');
                                        echo '<select name="cpc_forum_page-base_date">';
                                            echo '<option value="post_date_gmt"'.($base_date == 'post_date_gmt' ? ' SELECTED' : '').'>'.__('GMT', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="post_date"'.($base_date == 'post_date' ? ' SELECTED' : '').'>'.__('Lokal', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(base_date="'.$base_date.'")</td></tr>';
                                    echo '<tr><td>'.__('Basisdatum des Kommentars', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $comment_base_date = cpc_get_shortcode_default($values, 'cpc_forum_page-comment_base_date', 'comment_date_gmt');
                                        echo '<select name="cpc_forum_page-comment_base_date">';
                                            echo '<option value="comment_date_gmt"'.($base_date == 'comment_date_gmt' ? ' SELECTED' : '').'>'.__('GMT', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="comment_date"'.($base_date == 'comment_date' ? ' SELECTED' : '').'>'.__('Lokal', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(comment_base_date="'.$base_date.'")</td></tr>';
                                    echo '<tr><td colspan=3 class="cpc_section">'.__('Für den Tabellenstil...', CPC2_TEXT_DOMAIN).'</td></tr>';
                                    echo '<tr><td>'.__('Tabellenkopf anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_header = cpc_get_shortcode_default($values, 'cpc_forum-show_header', true);
                                        echo '<input type="checkbox" name="cpc_forum-show_header"'.($show_header ? ' CHECKED' : '').'></td><td>(show_header="'.($show_header ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Geschlossene Themen anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_closed = cpc_get_shortcode_default($values, 'cpc_forum-show_closed', true);
                                        echo '<input type="checkbox" name="cpc_forum-show_closed"'.($show_closed ? ' CHECKED' : '').'></td><td>(show_closed="'.($show_closed ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Themenanzahl anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_count = cpc_get_shortcode_default($values, 'cpc_forum-show_count', true);
                                        echo '<input type="checkbox" name="cpc_forum-show_count"'.($show_count ? ' CHECKED' : '').'></td><td>(show_count="'.($show_count ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Zeige Frische Themen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_freshness = cpc_get_shortcode_default($values, 'cpc_forum-show_freshness', true);
                                        echo '<input type="checkbox" name="cpc_forum-show_freshness"'.($show_freshness ? ' CHECKED' : '').'></td><td>(show_freshness="'.($show_freshness ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Letzte Aktivität anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_last_activity = cpc_get_shortcode_default($values, 'cpc_forum-show_last_activity', true);
                                        echo '<input type="checkbox" name="cpc_forum-show_last_activity"'.($show_last_activity ? ' CHECKED' : '').'></td><td>(show_last_activity="'.($show_last_activity ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Kommentaranzahl anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_comments_count = cpc_get_shortcode_default($values, 'cpc_forum-show_comments_count', true);
                                        echo '<input type="checkbox" name="cpc_forum-show_comments_count"'.($show_comments_count ? ' CHECKED' : '').'></td><td>(show_comments_count="'.($show_comments_count ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Beitragsautor anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_originator = cpc_get_shortcode_default($values, 'cpc_forum-show_originator', true);
                                        echo '<input type="checkbox" name="cpc_forum-show_originator"'.($show_originator ? ' CHECKED' : '').'></td><td>(show_originator="'.($show_originator ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Text für den Autor des Beitrags", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $originator = cpc_get_shortcode_default($values, 'cpc_forum-originator', __(' von %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-originator" value="'.$originator.'" /></td><td>(originator="'.$originator.'")</td></tr>';
                                    echo '<tr><td>'.__("Titel der Kopfzeile", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $header_title = cpc_get_shortcode_default($values, 'cpc_forum-header_title', __('Thema', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-header_title" value="'.$header_title.'" /></td><td>(header_title="'.$header_title.'")</td></tr>';
                                    echo '<tr><td>'.__("Titel der Antworten", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $header_count = cpc_get_shortcode_default($values, 'cpc_forum-header_count', __('Antworten', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-header_count" value="'.$header_count.'" /></td><td>(header_count="'.$header_count.'")</td></tr>';
                                    echo '<tr><td>'.__("Titel der letzten Aktivität", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $header_last_activity = cpc_get_shortcode_default($values, 'cpc_forum-header_last_activity', __('Letzte Aktivität', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-header_last_activity" value="'.$header_last_activity.'" /></td><td>(header_last_activity="'.$header_last_activity.'")</td></tr>';
                                    echo '<tr><td>'.__("Frische Themen-Titel", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $header_freshness = cpc_get_shortcode_default($values, 'cpc_forum-header_freshness', __('Wann', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-header_freshness" value="'.$header_freshness.'" /></td><td>(header_freshness="'.$header_freshness.'")</td></tr>';                        

                                    echo '<tr><td colspan=3 class="cpc_section">'.__('Für klassischen Stil...', CPC2_TEXT_DOMAIN).'</td></tr>';
                                    echo '<tr><td>'.__("Thema gestartet-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $started = cpc_get_shortcode_default($values, 'cpc_forum-started', __('Gestartet von %s %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-started" value="'.$started.'" /></td><td>(started="'.$started.'")</td></tr>';
                                    echo '<tr><td>'.__("Text für die letzte Antwort", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $replied = cpc_get_shortcode_default($values, 'cpc_forum-replied', __('Zuletzt geantwortet von %s %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-replied" value="'.$replied.'" /></td><td>(replied="'.$replied.'")</td></tr>';
                                    echo '<tr><td>'.__("Text für letzten Kommentar", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $commented = cpc_get_shortcode_default($values, 'cpc_forum-commented', __('Zuletzt kommentiert von %s %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-commented" value="'.$commented.'" /></td><td>(commented="'.$commented.'")</td></tr>';
                                    echo '<tr><td>'.__("Avatargröße für Themen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $size_posts = cpc_get_shortcode_default($values, 'cpc_forum-size_posts', 96);
                                        echo '<input type="text" name="cpc_forum-size_posts" value="'.$size_posts.'" /></td><td>(size_posts="'.$size_posts.'")</td></tr>';
                                    echo '<tr><td>'.__("Avatargröße für Antworten", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $size_replies = cpc_get_shortcode_default($values, 'cpc_forum-size_replies', 48);
                                        echo '<input type="text" name="cpc_forum-size_replies" value="'.$size_replies.'" /></td><td>(size_replies="'.$size_replies.'")</td></tr>';
                                    echo '<tr><td>'.__("Maximale Länge der Themenvorschau", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $post_preview = cpc_get_shortcode_default($values, 'cpc_forum-post_preview', 250);
                                        echo '<input type="text" name="cpc_forum-post_preview" value="'.$post_preview.'" /> '.__('Zeichen', CPC2_TEXT_DOMAIN).'</td><td>(post_preview="'.$post_preview.'")</td></tr>';
                                    echo '<tr><td>'.__("Maximale Länge der Antwortvorschau", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $reply_preview = cpc_get_shortcode_default($values, 'cpc_forum-reply_preview', 120);
                                        echo '<input type="text" name="cpc_forum-reply_preview" value="'.$reply_preview.'" /> '.__('Zeichen', CPC2_TEXT_DOMAIN).'</td><td>(reply_preview="'.$reply_preview.'")</td></tr>';
                                    echo '<tr><td>'.__("Bezeichnung für die Anzahl der Aufrufe", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $view_count_label = cpc_get_shortcode_default($values, 'cpc_forum-view_count_label', __('ANSEHEN', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-view_count_label" value="'.$view_count_label.'" /> '.__('Singular', CPC2_TEXT_DOMAIN).'</td><td>(view_count_label="'.$view_count_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Bezeichnung für die Anzahl der Aufrufe", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $views_count_label = cpc_get_shortcode_default($values, 'cpc_forum-views_count_label', __('ANSICHTEN', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-views_count_label" value="'.$views_count_label.'" /> '.__('Plural', CPC2_TEXT_DOMAIN).'</td><td>(views_count_label="'.$views_count_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Bezeichnung für die Anzahl der Antworten", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $reply_count_label = cpc_get_shortcode_default($values, 'cpc_forum-reply_count_label', __('ANTWORT', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-reply_count_label" value="'.$reply_count_label.'" /> '.__('Singular', CPC2_TEXT_DOMAIN).'</td><td>(reply_count_label="'.$reply_count_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Bezeichnung für die Anzahl der Aufrufe", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $replies_count_label = cpc_get_shortcode_default($values, 'cpc_forum-replies_count_label', __('ANTWORTEN', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-replies_count_label" value="'.$replies_count_label.'" /> '.__('Plural', CPC2_TEXT_DOMAIN).'</td><td>(replies_count_label="'.$replies_count_label.'")</td></tr>';
                        
                                    echo '<tr><td colspan=3 class="cpc_section">'.__('Für beide Stile...', CPC2_TEXT_DOMAIN).'</td></tr>';

                                    echo '<tr><td>'.__('Klicke auf das Thema', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $topic_action = cpc_get_shortcode_default($values, 'cpc_forum-topic_action', '');
                                        echo '<select name="cpc_forum-topic_action">';
                                            echo '<option value=""'.($topic_action == '' ? ' SELECTED' : '').'>'.__('Gehe immer zum Anfang der Antworten', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="last"'.($topic_action == 'last' ? ' SELECTED' : '').'>'.__('Gehe immer zum Ende der Antworten', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(topic_action="'.$topic_action.'")</td></tr>';    
                        
                                    echo '<tr><td>'.__('Markiere neue Inhalte', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $new_item = cpc_get_shortcode_default($values, 'cpc_forum-new_item', true);
                                        echo '<input type="checkbox" class="cpc_shortcode_tip_available" name="cpc_forum-new_item"'.($new_item ? ' CHECKED' : '').'></td><td>(new_item="'.($new_item ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Wie lange Inhalte neu bleiben', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $new_seconds = cpc_get_shortcode_default($values, 'cpc_forum-new_seconds', 259200);
                                        echo '<input type="text" name="cpc_forum-new_seconds" class="cpc_shortcode_tip_available" value="'.$new_seconds.'" /> seconds</td><td>(new_seconds="'.$new_seconds.'")</td></tr>';
                                    echo '<tr id="cpc_shortcode_tip" style="display:none"';
                                        echo '><td colspan="3" class="cpc_admin_shortcode_tip">'.sprintf(__('Styling-Tipps findest Du im <a href="%s" target="_blank">PS Community Codex</a>.', CPC2_TEXT_DOMAIN), 'https://cp-community.n3rds.work/cpc-forum/').'</td></tr>';

                                    echo '<tr><td>'.__('Markiere gelesene Artikel als nicht mehr neu', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $new_item_read = cpc_get_shortcode_default($values, 'cpc_forum-new_item_read', true);
                                        echo '<input type="checkbox" name="cpc_forum-new_item_read"'.($new_item_read ? ' CHECKED' : '').'></td><td>(new_item_read="'.($new_item_read ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Wort für neuen Inhalt", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $new_item_label = cpc_get_shortcode_default($values, 'cpc_forum-new_item_label', __('NEU!', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-new_item_label" value="'.$new_item_label.'" /></td><td>(new_item_label="'.$new_item_label.'")</td></tr>';

                                    echo '<tr><td>'.__('Aktiviere die Paginierung', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_posts = cpc_get_shortcode_default($values, 'cpc_forum-pagination_posts', true);
                                        echo '<input type="checkbox" name="cpc_forum-pagination_posts"'.($pagination_posts ? ' CHECKED' : '').'></td><td>(pagination_posts="'.($pagination_posts ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Paginierung über Beiträgen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_top_posts = cpc_get_shortcode_default($values, 'cpc_forum-pagination_top_posts', true);
                                        echo '<input type="checkbox" name="cpc_forum-pagination_top_posts"'.($pagination_top_posts ? ' CHECKED' : '').'></td><td>(pagination_top_posts="'.($pagination_top_posts ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Paginierung unter Beiträgen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_bottom_posts = cpc_get_shortcode_default($values, 'cpc_forum-pagination_bottom_posts', true);
                                        echo '<input type="checkbox" name="cpc_forum-pagination_bottom_posts"'.($pagination_bottom_posts ? ' CHECKED' : '').'></td><td>(pagination_bottom_posts="'.($pagination_bottom_posts ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Seitengröße der Paginierung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $page_size_posts = cpc_get_shortcode_default($values, 'cpc_forum-page_size_posts', 10);
                                        echo '<input type="text" name="cpc_forum-page_size_posts" value="'.$page_size_posts.'" /></td><td>(page_size_posts="'.$page_size_posts.'")</td></tr>';
                                    echo '<tr><td>'.__("Paginierung erstes Label", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_first_posts = cpc_get_shortcode_default($values, 'cpc_forum-pagination_first_posts', __('Erste', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-pagination_first_posts" value="'.$pagination_first_posts.'" /></td><td>(pagination_first_posts="'.$pagination_first_posts.'")</td></tr>';                        
                                    echo '<tr><td>'.__("Paginierung vorheriges Label", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_previous_posts = cpc_get_shortcode_default($values, 'cpc_forum-pagination_previous_posts', __('Vorherige', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-pagination_previous_posts" value="'.$pagination_previous_posts.'" /></td><td>(pagination_previous_posts="'.$pagination_previous_posts.'")</td></tr>';
                                    echo '<tr><td>'.__("Paginierung nächstes Label", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_next_posts = cpc_get_shortcode_default($values, 'cpc_forum-pagination_next_posts', __('Nächste', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-pagination_next_posts" value="'.$pagination_next_posts.'" /></td><td>(pagination_next_posts="'.$pagination_next_posts.'")</td></tr>';
                                    echo '<tr><td>'.__("Paginierung der aktuellen Seite-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $page_x_of_y_posts = cpc_get_shortcode_default($values, 'cpc_forum-page_x_of_y_posts', __('Seite %d von %d', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-page_x_of_y_posts" value="'.$page_x_of_y_posts.'" /></td><td>(page_x_of_y_posts="'.$page_x_of_y_posts.'")</td></tr>';
                                    echo '<tr><td>'.__("Maximale Seitenzahl", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $max_pages_posts = cpc_get_shortcode_default($values, 'cpc_forum-max_pages_posts', 10);
                                        echo '<input type="text" name="cpc_forum-max_pages_posts" value="'.$max_pages_posts.'" /></td><td>(max_pages_posts="'.$max_pages_posts.'")</td></tr>';
                                    echo '<tr><td>'.__("Maximale Anzahl an Beiträgen (wenn keine Paginierung vorhanden ist)", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $max_posts_no_pagination_posts = cpc_get_shortcode_default($values, 'cpc_forum-max_posts_no_pagination_posts', 100);
                                        echo '<input type="text" name="cpc_forum-max_posts_no_pagination_posts" value="'.$max_posts_no_pagination_posts.'" /></td><td>(max_posts_no_pagination_posts="'.$max_posts_no_pagination_posts.'")</td></tr>';
                        
                                    echo '<tr><td>'.__('Antwortsymbol anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $reply_icon = cpc_get_shortcode_default($values, 'cpc_forum-reply_icon', true);
                                        echo '<input type="checkbox" name="cpc_forum-reply_icon" '.($reply_icon ? ' CHECKED' : '').'></td><td>(reply_icon="'.($reply_icon ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Maximale Titellänge", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $title_length = cpc_get_shortcode_default($values, 'cpc_forum-title_length', 150);
                                        echo '<input type="text" name="cpc_forum-title_length" value="'.$title_length.'" /> '.__('Zeichen', CPC2_TEXT_DOMAIN).'</td><td>(title_length="'.$title_length.'")</td></tr>';
                                    echo '<tr><td>'.__('Antwortstatus', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $status = cpc_get_shortcode_default($values, 'cpc_forum-status', '');
                                        echo '<select name="cpc_forum-status">';
                                            echo '<option value=""'.($status == '' ? ' SELECTED' : '').'>'.__('Offen und Geschlossen', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="open"'.($status == 'open' ? ' SELECTED' : '').'>'.__('Offen', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="closed"'.($status == 'closed' ? ' SELECTED' : '').'>'.__('Geschlossen', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(status="'.$status.'")</td></tr>';    
                                    echo '<tr><td>'.__('Standardzustand des Geschlossen Schalters', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $closed_switch = cpc_get_shortcode_default($values, 'cpc_forum-closed_switch', '');
                                        echo '<select name="cpc_forum-closed_switch">';
                                            echo '<option value=""'.($status == '' ? ' SELECTED' : '').'>'.__('Nicht zeigen', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="on"'.($status == 'on' ? ' SELECTED' : '').'>'.__('An', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="off"'.($status == 'off' ? ' SELECTED' : '').'>'.__('Aus', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(closed_switch="'.$closed_switch.'")</td></tr>';    
                                    echo '<tr><td>'.__("Geschlossen Schalter Aufforderung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $closed_switch_msg = cpc_get_shortcode_default($values, 'cpc_forum-closed_switch_msg', __('Schließe geschlossene Beiträge ein', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-closed_switch_msg" value="'.$closed_switch_msg.'" /></td><td>(closed_switch_msg="'.$closed_switch_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Muss angemeldet sein Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $private_msg = cpc_get_shortcode_default($values, 'cpc_forum-private_msg', __('Du musst angemeldet sein, um dieses Forum anzuzeigen.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-private_msg" value="'.$private_msg.'" /></td><td>(private_msg="'.$private_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Optionale URL zum Anmelden", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $login_url = cpc_get_shortcode_default($values, 'cpc_forum-login_url', '');
                                        echo '<input type="text" name="cpc_forum-login_url" value="'.$login_url.'" /></td><td>(login_url)</td></tr>';
                                    echo '<tr><td>'.__("Keine Berechtigung-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $secure_msg = cpc_get_shortcode_default($values, 'cpc_forum-secure_msg', __('Du hast keine Berechtigung, dieses Forum anzuzeigen.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-secure_msg" value="'.$secure_msg.'" /> '.__('für Forum', CPC2_TEXT_DOMAIN).'</td><td>(secure_msg="'.$secure_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Keine Berechtigung-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $secure_post_msg = cpc_get_shortcode_default($values, 'cpc_forum-secure_post_msg', __('Du hast keine Berechtigung, diesen Beitrag anzuzeigen.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-secure_post_msg" value="'.$secure_post_msg.'" /> '.__('zum Thema', CPC2_TEXT_DOMAIN).'</td><td>(secure_post_msg="'.$secure_post_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Leere Foren-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $empty_msg = cpc_get_shortcode_default($values, 'cpc_forum-empty_msg', __('Keine Forumbeiträge.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-empty_msg" value="'.$empty_msg.'" /></td><td>(empty_msg="'.$empty_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Thema gelöscht-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $post_deleted = cpc_get_shortcode_default($values, 'cpc_forum-post_deleted', __('Beitrag gelöscht.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-post_deleted" value="'.$post_deleted.'" /></td><td>(post_deleted="'.$post_deleted.'")</td></tr>';
                                    echo '<tr><td>'.__("Wort für ausstehendes Thema", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pending = cpc_get_shortcode_default($values, 'cpc_forum-pending', '('.__('ausstehend', CPC2_TEXT_DOMAIN).')');
                                        echo '<input type="text" name="cpc_forum-pending" value="'.$pending.'" /></td><td>(pending="'.$pending.'")</td></tr>';
                                    echo '<tr><td>'.__("Wort für die ausstehende Antwort", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $comment_pending = cpc_get_shortcode_default($values, 'cpc_forum-comment_pending', '('.__('ausstehend', CPC2_TEXT_DOMAIN).')');
                                        echo '<input type="text" name="cpc_forum-comment_pending" value="'.$comment_pending.'" /></td><td>(comment_pending="'.$comment_pending.'")</td></tr>';
                                    echo '<tr><td>'.__("Geschlossenes Präfix", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $closed_prefix = cpc_get_shortcode_default($values, 'cpc_forum-closed_prefix', __('geschlossen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-closed_prefix" value="'.$closed_prefix.'" /></td><td>(closed_prefix="'.$closed_prefix.'")</td></tr>';
                                    echo '<tr><td>'.__("Thema verschoben-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $moved_to = cpc_get_shortcode_default($values, 'cpc_forum-moved_to', __('%s wurde erfolgreich nach %s verschoben', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-moved_to" value="'.$moved_to.'" /></td><td>(moved_to)</td></tr>';
                                    echo '<tr><td>'.__("Datumsformat", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $date_format = cpc_get_shortcode_default($values, 'cpc_forum-date_format', __('vor %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-date_format" value="'.$date_format.'" /></td><td>(date_format="'.$date_format.'")</td></tr>';
                        
                                    echo '<tr><td>'.__('Zeitüberschreitung aktivieren', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $enable_timeout = cpc_get_shortcode_default($values, 'cpc_forum-enable_timeout', true);
                                        echo '<input type="checkbox" name="cpc_forum-enable_timeout"'.($enable_timeout ? ' CHECKED' : '').'></td><td>(enable_timeout="'.($enable_timeout ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Zeitüberschreitung vor Bearbeitung nicht mehr möglich", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $timeout = cpc_get_shortcode_default($values, 'cpc_forum-timeout', 120);
                                        echo '<input type="text" name="cpc_forum-timeout" value="'.$timeout.'" /> '.__('Sekunden', CPC2_TEXT_DOMAIN).'</td><td>(timeout="'.$timeout.'")</td></tr>';
                                    echo '<tr><td>'.__("Anzahl der anzuzeigenden Themen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $count = cpc_get_shortcode_default($values, 'cpc_forum-count', 0);
                                        echo '<input type="text" name="cpc_forum-count" value="'.$count.'" /> '.__('0 = Alle', CPC2_TEXT_DOMAIN).'</td><td>(count="'.$count.'")</td></tr>';

                                    echo '<tr><td colspan=3 class="cpc_section">'.__('Für die Einzelthemenansicht...', CPC2_TEXT_DOMAIN).'</td></tr>';    

                                    echo '<tr><td>'.__("Keine Antwort-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $reply_comment_none = cpc_get_shortcode_default($values, 'cpc_forum-reply_comment_none', __('Keine Antworten', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-reply_comment_none" value="'.$reply_comment_none.'" /></td><td>(reply_comment_none="'.$reply_comment_none.'")</td></tr>';
                                    echo '<tr><td>'.__("Eine Antwort-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $reply_comment_one = cpc_get_shortcode_default($values, 'cpc_forum-reply_comment_one', __('1 Antwort', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-reply_comment_one" value="'.$reply_comment_one.'" /></td><td>(reply_comment_one="'.$reply_comment_one.'")</td></tr>';
                                    echo '<tr><td>'.__("Mehrere Antworten-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $reply_comment_multiple = cpc_get_shortcode_default($values, 'cpc_forum-reply_comment_multiple', __('%d Antworten', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-reply_comment_multiple" value="'.$reply_comment_multiple.'" /></td><td>(reply_comment_multiple="'.$reply_comment_multiple.'")</td></tr>';
                                    echo '<tr><td>'.__("1 Kommentar-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $reply_comment_one_comment = cpc_get_shortcode_default($values, 'cpc_forum-reply_comment_one_comment', __('und 1 Kommentar', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-reply_comment_one_comment" value="'.$reply_comment_one_comment.'" /></td><td>(reply_comment_one_comment="'.$reply_comment_one_comment.'")</td></tr>';
                                    echo '<tr><td>'.__("Mehrere Kommentare-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $reply_comment_multiple_comments = cpc_get_shortcode_default($values, 'cpc_forum-reply_comment_multiple_comments', __('und %d Kommentare', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-reply_comment_multiple_comments" value="'.$reply_comment_multiple_comments.'" /></td><td>(reply_comment_multiple_comments="'.$reply_comment_multiple_comments.'")</td></tr>';
                        
                                    echo '<tr><td>'.__("Zusätzliche Forumadministratoren", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $forum_admins = cpc_get_shortcode_default($values, 'cpc_forum-forum_admins', '');
                                        echo '<input type="text" name="cpc_forum-forum_admins" value="'.$forum_admins.'" /> '.__('Benutzer, durch Kommas getrennt', CPC2_TEXT_DOMAIN).'</td><td>(forum_admins="'.$forum_admins.'")</td></tr>';
                                    echo '<tr><td>'.__("Avatar des Themenautors", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $size = cpc_get_shortcode_default($values, 'cpc_forum-size', 96);
                                        echo '<input type="text" name="cpc_forum-size" value="'.$size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(size="'.$size.'")</td></tr>';
                                    echo '<tr><td>'.__("Antwort-Autor-Avatar", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $comments_avatar_size = cpc_get_shortcode_default($values, 'cpc_forum-comments_avatar_size', 96);
                                        echo '<input type="text" name="cpc_forum-comments_avatar_size" value="'.$comments_avatar_size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(comments_avatar_size="'.$comments_avatar_size.'")</td></tr>';

                                    echo '<tr><td>'.__('Aktiviere die Paginierung', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination = cpc_get_shortcode_default($values, 'cpc_forum-pagination', true);
                                        echo '<input type="checkbox" name="cpc_forum-pagination"'.($pagination ? ' CHECKED' : '').'></td><td>(pagination="'.($pagination ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Paginierung über dem Thema', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_above = cpc_get_shortcode_default($values, 'cpc_forum-pagination_above', false);
                                        echo '<input type="checkbox" name="cpc_forum-pagination_above"'.($pagination_above ? ' CHECKED' : '').'></td><td>(pagination_above="'.($pagination_above ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Paginierung über den Antworten', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_top = cpc_get_shortcode_default($values, 'cpc_forum-pagination_top', true);
                                        echo '<input type="checkbox" name="cpc_forum-pagination_top"'.($pagination_top ? ' CHECKED' : '').'></td><td>(pagination_top="'.($pagination_top ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Paginierung unter den Antworten', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_bottom = cpc_get_shortcode_default($values, 'cpc_forum-pagination_bottom', true);
                                        echo '<input type="checkbox" name="cpc_forum-pagination_bottom"'.($pagination_bottom ? ' CHECKED' : '').'></td><td>(pagination_bottom="'.($pagination_bottom ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Seitengröße der Paginierung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $page_size = cpc_get_shortcode_default($values, 'cpc_forum-page_size', 10);
                                        echo '<input type="text" name="cpc_forum-page_size" value="'.$page_size.'" /></td><td>(page_size="'.$page_size.'")</td></tr>';
                                    echo '<tr><td>'.__("Paginierung Erstes Label", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_first = cpc_get_shortcode_default($values, 'cpc_forum-pagination_first', __('Erste', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-pagination_first" value="'.$pagination_first.'" /></td><td>(pagination_first="'.$pagination_first.'")</td></tr>';
                                    echo '<tr><td>'.__("Paginierung Vorheriges Label", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_previous = cpc_get_shortcode_default($values, 'cpc_forum-pagination_previous', __('Vorherige', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-pagination_previous" value="'.$pagination_previous.'" /></td><td>(pagination_previous="'.$pagination_previous.'")</td></tr>';
                                    echo '<tr><td>'.__("Paginierung Nächstes Label", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $pagination_next = cpc_get_shortcode_default($values, 'cpc_forum-pagination_next', __('Nächste', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-pagination_next" value="'.$pagination_next.'" /></td><td>(pagination_next="'.$pagination_next.'")</td></tr>';
                                    echo '<tr><td>'.__("Paginierung der aktuellen Seite-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $page_x_of_y = cpc_get_shortcode_default($values, 'cpc_forum-page_x_of_y', __('Auf Seite %d von %d', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-page_x_of_y" value="'.$page_x_of_y.'" /></td><td>(page_x_of_y="'.$page_x_of_y.'")</td></tr>';
                        
                                    echo '<tr><td>'.__('Reihenfolge der Antworten', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $replies_order = cpc_get_shortcode_default($values, 'cpc_forum-replies_order', 'ASC');
                                        echo '<select name="cpc_forum-replies_order">';
                                            echo '<option value="ASC"'.($replies_order == 'ASC' ? ' SELECTED' : '').'>'.__('Die ältesten zuerst', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="DESC"'.($replies_order == 'DESC' ? ' SELECTED' : '').'>'.__('Die neusten zuerst', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(replies_order="'.$replies_order.'")</td></tr>';    

                                    echo '<tr><td>'.__("Melden Option einbinden", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $report = cpc_get_shortcode_default($values, 'cpc_forum-report', true);
                                        echo '<input type="checkbox" name="cpc_forum-report"'.($report ? ' CHECKED' : '').'></td><td>(report="'.($report ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Label der Meldenoption', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $report_label = cpc_get_shortcode_default($values, 'cpc_forum-report_label', __('Melden', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-report_label" value="'.$report_label.'" /></td><td>(report_label="'.$report_label.'")</td></tr>';
                                    echo '<tr><td>'.__('Meldung E-Mail Empfänger', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $report_email = cpc_get_shortcode_default($values, 'cpc_forum-report_email', get_bloginfo('admin_email'));
                                        echo '<input type="text" name="cpc_forum-report_email" value="'.$report_email.'" /></td><td>(report_email="'.$report_email.'")</td></tr>';
                        
                                    echo '<tr><td>'.__('Ersten Beitrag auf Seite 2+ ausblenden', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $hide_initial = cpc_get_shortcode_default($values, 'cpc_forum-hide_initial', false);
                                        echo '<input type="checkbox" name="cpc_forum-hide_initial"'.($hide_initial ? ' CHECKED' : '').'></td><td>(hide_initial="'.($hide_initial ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Kommentare einschalten', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_comments = cpc_get_shortcode_default($values, 'cpc_forum-show_comments', true);
                                        echo '<input type="checkbox" name="cpc_forum-show_comments"'.($show_comments ? ' CHECKED' : '').'></td><td>(show_comments="'.($show_comments ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Kommentar als Standard anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_comment_form = cpc_get_shortcode_default($values, 'cpc_forum-show_comment_form', true);
                                        echo '<input type="checkbox" name="cpc_forum-show_comment_form"'.($show_comment_form ? ' CHECKED' : '').'></td><td>(show_comment_form="'.($show_comment_form ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Neue Kommentare zulassen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $allow_comments = cpc_get_shortcode_default($values, 'cpc_forum-allow_comments', true);
                                        echo '<input type="checkbox" name="cpc_forum-allow_comments"'.($allow_comments ? ' CHECKED' : '').'></td><td>(allow_comments="'.($allow_comments ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung für Kommentar-Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $comment_add_label = cpc_get_shortcode_default($values, 'cpc_forum-comment_add_label', __('Kommentar hinzufügen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-comment_add_label" value="'.$comment_add_label.'" /></td><td>(comment_add_label="'.$comment_add_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung für die Schaltfläche Aktualisieren", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $update_label = cpc_get_shortcode_default($values, 'cpc_forum-update_label', __('Aktualisieren', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-update_label" value="'.$update_label.'" /></td><td>(update_label="'.$update_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung für die Abbruch-Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $cancel_label = cpc_get_shortcode_default($values, 'cpc_forum-cancel_label', __('Abbrechen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-cancel_label" value="'.$cancel_label.'" /></td><td>(cancel_label="'.$cancel_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung für Moderations-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $moderate_msg = cpc_get_shortcode_default($values, 'cpc_forum-moderate_msg', __('Dein Beitrag erscheint, sobald er moderiert worden ist.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-moderate_msg" value="'.$moderate_msg.'" /></td><td>(moderate_msg="'.$moderate_msg.'")</td></tr>';
                                    echo '<tr><td>'.__('Optionale CSS-Klasse für die Kommentar-Schaltfläche', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $comment_class = cpc_get_shortcode_default($values, 'cpc_forum-comment_class', '');
                                        echo '<input type="text" name="cpc_forum-comment_class" value="'.$comment_class.'" /></td><td>(comment_class="'.$comment_class.'")</td></tr>';
                                    echo '<tr><td>'.__('Angezeigter Text für private Kommentare', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $private_reply_msg = cpc_get_shortcode_default($values, 'cpc_forum-private_reply_msg', __('PRIVATE ANTWORT', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum-private_reply_msg" value="'.$private_reply_msg.'" /></td><td>(private_reply_msg="'.$private_reply_msg.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_forum', $values);            

                                    echo '</table>';    
                            echo '</div>';    

                            // [cpc-forum-backto]
                            $values = get_option('cpc_shortcode_options_'.'cpc_forum_backto') ? get_option('cpc_shortcode_options_'.'cpc_forum_backto') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_forum_backto_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt einen Link zurück zu den Forumsthemen an. Wird nur angezeigt, wenn ein einzelnes Thema angezeigt wird.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.sprintf(__('Füge [cpc-forum-backto slug="xxx"] zur ClassicPress-Seite Deines Forums hinzu (klicke <a href="%s">hier</a> auf den Link zur <strong>Seite</strong>), wobei xxx der <a href="%s">Slug Deines Forums</a> ist.', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpccom_forum_setup' ), admin_url( 'edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post' ));
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-forum-backto');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Text für den Link", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $label = cpc_get_shortcode_default($values, 'cpc_forum_backto-label', __('Zurück zu %s...', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_backto-label" value="'.$label.'" /></td><td>(label="'.$label.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_forum_backto', $values);            

                                echo '</table>';    
                            echo '</div>';    

                            // [cpc-forum-reply]
                            $values = get_option('cpc_shortcode_options_'.'cpc_forum_comment') ? get_option('cpc_shortcode_options_'.'cpc_forum_comment') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_forum_comment_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt einen Textbereich zum Hinzufügen einer Antwort zu einem Forumsthema an. Wird nur angezeigt, wenn ein einzelnes Thema angezeigt wird.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.sprintf(__('Füge [cpc-forum-reply slug="xxx"] zur ClassicPress-Seite Deines Forums hinzu (klicke <a href="%s">hier</a> auf den Link zur <strong>Seite</strong>), wobei xxx der <a href="%s">Slug Deines Forums</a> ist.', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpccom_forum_setup' ), admin_url( 'edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post' ));
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-forum-reply');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Etikett für die Schaltfläche Antwort hinzufügen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $label = cpc_get_shortcode_default($values, 'cpc_forum_comment-label', __('Antwort hinzufügen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_comment-label" value="'.$label.'" /></td><td>(label="'.$label.'")</td></tr>';
                                    echo '<tr><td>'.__("Optionale CSS-Klasse für die Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $class = cpc_get_shortcode_default($values, 'cpc_forum_comment-class', '');
                                        echo '<input type="text" name="cpc_forum_comment-class" value="'.$class.'" /></td><td>(class="'.$class.'")</td></tr>';
                                    echo '<tr><td>'.__("Text über dem Antworttextbereich", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $content_label = cpc_get_shortcode_default($values, 'cpc_forum_comment-content_label', '');
                                        echo '<input type="text" name="cpc_forum_comment-content_label" value="'.$content_label.'" /></td><td>(content_label="'.$content_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Du hast keine Berechtigung zum Anzeigen der Nachricht zum Thema", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $private_msg = cpc_get_shortcode_default($values, 'cpc_forum_comment-private_msg', '');
                                        echo '<input type="text" name="cpc_forum_comment-private_msg" value="'.$private_msg.'" /></td><td>(private_msg="'.$private_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Keine Berechtigung zum Beantworten der Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $no_permission_msg = cpc_get_shortcode_default($values, 'cpc_forum_comment-no_permission_msg', __('Du hast keine Berechtigung, in diesem Forum zu antworten.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_comment-no_permission_msg" value="'.$no_permission_msg.'" /></td><td>(no_permission_msg="'.$no_permission_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Meldung Forum ist gesperrt", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $locked_msg = cpc_get_shortcode_default($values, 'cpc_forum_comment-locked_msg', __('Dieses Forum ist gesperrt. Neue Beiträge und Antworten sind nicht erlaubt.', CPC2_TEXT_DOMAIN).' ');
                                        echo '<input type="text" name="cpc_forum_comment-locked_msg" value="'.$locked_msg.'" /></td><td>(locked_msg="'.$locked_msg.'")</td></tr>';
                                    echo '<tr><td>'.__('Antwortmoderation einschalten', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $moderate = cpc_get_shortcode_default($values, 'cpc_forum_comment-moderate', false);
                                        echo '<input type="checkbox" name="cpc_forum_comment-moderate"'.($moderate ? ' CHECKED' : '').'></td><td>(moderate="'.($moderate ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Moderationsmeldung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $moderate_msg = cpc_get_shortcode_default($values, 'cpc_forum_comment-moderate_msg', __('Dein Kommentar erscheint, sobald er moderiert worden ist.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_comment-moderate_msg" value="'.$moderate_msg.'" /></td><td>(moderate_msg="'.$moderate_msg.'")</td></tr>';
                                    echo '<tr><td>'.__('Antwort-Textarea standardmäßig anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show = cpc_get_shortcode_default($values, 'cpc_forum_comment-show', true);
                                        echo '<input type="checkbox" name="cpc_forum_comment-show"'.($show ? ' CHECKED' : '').'></td><td>(show="'.($show ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Nutzern erlauben Beiträge zu schließen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $allow_close = cpc_get_shortcode_default($values, 'cpc_forum_comment-allow_close', true);
                                        echo '<input type="checkbox" name="cpc_forum_comment-allow_close"'.($allow_close ? ' CHECKED' : '').'></td><td>(allow_close="'.($allow_close ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung zum Schließen des Themas", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $close_msg = cpc_get_shortcode_default($values, 'cpc_forum_comment-close_msg', __('Anwählen um diesen Beitrag zu schließen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_comment-close_msg" value="'.$close_msg.'" /></td><td>(close_msg="'.$close_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Nachricht dass das Thema geschlossen ist", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $comments_closed_msg = cpc_get_shortcode_default($values, 'cpc_forum_comment-comments_closed_msg', __('Dieser Beitrag ist geschlossen.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_comment-comments_closed_msg" value="'.$comments_closed_msg.'" /></td><td>(comments_closed_msg="'.$comments_closed_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung zur Wiedereröffnung des Themas", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $reopen_label = cpc_get_shortcode_default($values, 'cpc_forum_comment-reopen_label', __('Diesen Beitrag erneut öffnen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_comment-reopen_label" value="'.$reopen_label.'" /></td><td>(reopen_label="'.$reopen_label.'")</td></tr>';
                                    echo '<tr><td>'.__('Nur eine Antwort pro Benutzer zulassen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $allow_one = cpc_get_shortcode_default($values, 'cpc_forum_comment-allow_one', false);
                                        echo '<input type="checkbox" name="cpc_forum_comment-allow_one"'.($allow_one ? ' CHECKED' : '').'></td><td>(allow_one="'.($allow_one ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Nachricht das bereits beantwortet wurde", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $allow_one_msg = cpc_get_shortcode_default($values, 'cpc_forum_comment-allow_one_msg', __('Du kannst in diesem Forum nur einmal antworten.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_comment-allow_one_msg" value="'.$allow_one_msg.'" /></td><td>(allow_one_msg="'.$allow_one_msg.'")</td></tr>';
                                    echo '<tr><td>'.__('Private Antworten', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $allow_private = cpc_get_shortcode_default($values, 'cpc_forum_comment-allow_private', 'disabled');
                                        echo '<select name="cpc_forum_comment-allow_private">';
                                            echo '<option value=0'.($allow_private == 'disabled' ? ' SELECTED' : '').'>'.__('Deaktiviert', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="optional"'.($allow_private == 'optional' ? ' SELECTED' : '').'>'.__('Für den Benutzer verfügbare Option', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="forced"'.($allow_private == 'forced' ? ' SELECTED' : '').'>'.__('Alle Antworten privat', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(allow_private="'.$allow_private.'")</td></tr>';    
                                    echo '<tr><td>'.__("Beschriftung Private Antwort", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $private_reply_check_msg = cpc_get_shortcode_default($values, 'cpc_forum_comment-private_reply_check_msg', __('Antwort nur mit %s teilen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_comment-private_reply_check_msg" value="'.$private_reply_check_msg.'" /></td><td>(private_reply_check_msg="'.$private_reply_check_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("In (welchem Forum) anzeigen Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_in_label = cpc_get_shortcode_default($values, 'cpc_forum_comment-show_in_label', __('Anzeigen in:', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_comment-show_in_label" value="'.$show_in_label.'" /></td><td>(show_in_label="'.$show_in_label.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_forum_comment', $values);            

                                echo '</table>';    
                            echo '</div>';    

                            // [cpc-forum-page]
                            $values = get_option('cpc_shortcode_options_'.'cpc_forum_page') ? get_option('cpc_shortcode_options_'.'cpc_forum_page') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_forum_page_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt eine vorgefertigte Seite für ein Forum an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.sprintf(__('Füge [cpc-forum-page slug="xxx"] zu einer ClassicPress-Seite hinzu, wobei xxx der <a href="%s">Slug Deines Forums</a> ist.', CPC2_TEXT_DOMAIN), admin_url( 'edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post' ));
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-forum-page');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Stil', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $style = cpc_get_shortcode_default($values, 'cpc_forum_page-style', 'table');
                                        echo '<select name="cpc_forum_page-style">';
                                            echo '<option value="table"'.($style == 'table' ? ' SELECTED' : '').'>'.__('Tabelle', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="classic"'.($style == 'classic' ? ' SELECTED' : '').'>'.__('Klassisch', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(style="'.$style.'")</td></tr>';    
                                    echo '<tr><td>'.__('Neues Themenformular anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show = cpc_get_shortcode_default($values, 'cpc_forum_page-show', false);
                                        echo '<input type="checkbox" name="cpc_forum_page-show"'.($show ? ' CHECKED' : '').'></td><td>(show="'.($show ? '1' : '0').'")</td></tr>';    
                                    echo '<tr><td>'.__("Text der Titelüberschrift", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $header_title = cpc_get_shortcode_default($values, 'cpc_forum_page-header_title', __('Thema', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_page-header_title" value="'.$header_title.'" /></td><td>(header_title="'.$header_title.'")</td></tr>';
                                    echo '<tr><td>'.__("Kopfzeilentext der Antworten", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $header_count = cpc_get_shortcode_default($values, 'cpc_forum_page-header_count', __('Antworten', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_page-header_count" value="'.$header_count.'" /></td><td>(header_count="'.$header_count.'")</td></tr>';
                                    echo '<tr><td>'.__("Kopfzeilentext der letzten Aktivität", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $header_last_activity = cpc_get_shortcode_default($values, 'cpc_forum_page-header_last_activity', __('Letzte Aktivität', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_page-header_last_activity" value="'.$header_last_activity.'" /></td><td>(header_last_activity="'.$header_last_activity.'")</td></tr>';
                                    echo '<tr><td>'.__('Basisdatum', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $base_date = cpc_get_shortcode_default($values, 'cpc_forum_page-base_date', 'post_date_gmt');
                                        echo '<select name="cpc_forum_page-base_date">';
                                            echo '<option value="post_date_gmt"'.($base_date == 'post_date_gmt' ? ' SELECTED' : '').'>'.__('GMT', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="post_date"'.($base_date == 'post_date' ? ' SELECTED' : '').'>'.__('Lokal', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(base_date="'.$base_date.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_forum_page', $values);                

                                echo '</table>';    
                            echo '</div>';    

                            // [cpc-forum-post]
                            $values = get_option('cpc_shortcode_options_'.'cpc_forum_post') ? get_option('cpc_shortcode_options_'.'cpc_forum_post') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_forum_post_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt einen Textbereich zum Hinzufügen eines Forumsthemas an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.sprintf(__('Füge [cpc-forum-post slug="xxx"] zu einer ClassicPress-Seite hinzu, wobei xxx der <a href="%s">Slug Deines Forums</a> oder slug="choose" ist, um Benutzern dies zu ermöglichen.', CPC2_TEXT_DOMAIN), admin_url( 'edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post' ));
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-forum-post');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Titeltext des Beitrags", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $title_label = cpc_get_shortcode_default($values, 'cpc_forum_post-title_label', __('Titel des Beitrags', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_post-title_label" value="'.$title_label.'" /></td><td>(title_label="'.$title_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Themeninhaltstext", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $content_label = cpc_get_shortcode_default($values, 'cpc_forum_post-content_label', __('Beitrag', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_post-content_label" value="'.$content_label.'" /></td><td>(content_label="'.$content_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung der Themenschaltfläche hinzufügen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $label = cpc_get_shortcode_default($values, 'cpc_forum_post-label', __('Thema hinzufügen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_post-label" value="'.$label.'" /></td><td>(label="'.$label.'")</td></tr>';
                                    echo '<tr><td>'.__("Aufforderung zum Abonnieren", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $subscribe_prompt = cpc_get_shortcode_default($values, 'cpc_forum_post-subscribe_prompt', __('Erhalte eine E-Mail, wenn neue Kommentare hinzugefügt werden', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_post-subscribe_prompt" value="'.$subscribe_prompt.'" /></td><td>(subscribe_prompt="'.$subscribe_prompt.'")</td></tr>';
                                    echo '<tr><td>'.__("Optionale CSS-Klasse für die Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $class = cpc_get_shortcode_default($values, 'cpc_forum_post-class', '');
                                        echo '<input type="text" name="cpc_forum_post-class" value="'.$class.'" /></td><td>(class="'.$class.'")</td></tr>';
                                    echo '<tr><td>'.__('Moderation aktivieren', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $moderate = cpc_get_shortcode_default($values, 'cpc_forum_post-moderate', false);
                                        echo '<input type="checkbox" name="cpc_forum_post-moderate"'.($moderate ? ' CHECKED' : '').'></td><td>(moderate="'.($moderate ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Warte auf Moderation-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $moderate_msg = cpc_get_shortcode_default($values, 'cpc_forum_post-moderate_msg', __('Dein Beitrag erscheint, sobald er moderiert wurde.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_post-moderate_msg" value="'.$moderate_msg.'" /></td><td>(moderate_msg="'.$moderate_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Nachricht Berechtigung verweigert.", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $private_msg = cpc_get_shortcode_default($values, 'cpc_forum_post-private_msg', '');
                                        echo '<input type="text" name="cpc_forum_post-private_msg" value="'.$private_msg.'" /></td><td>(private_msg="'.$private_msg.'")</td></tr>';
                                    echo '<tr><td>'.__('Lege den Beitragstitel als mehrzeilig fest', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $multiline = cpc_get_shortcode_default($values, 'cpc_forum_post-multiline', 0);
                                        echo '<select name="cpc_forum_post-multiline">';
                                            echo '<option value="0"'.($multiline == '0' ? ' SELECTED' : '').'>'.__('Deaktiviert', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="1"'.($multiline == '1' ? ' SELECTED' : '').'>'.__('1 Zeile', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="2"'.($multiline == '2' ? ' SELECTED' : '').'>'.__('2 Zeilen', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="3"'.($multiline == '3' ? ' SELECTED' : '').'>'.__('3 Zeilen', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="4"'.($multiline == '4' ? ' SELECTED' : '').'>'.__('4 Zeilen', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="5"'.($multiline == '5' ? ' SELECTED' : '').'>'.__('5 Zeilen', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(multiline="'.$multiline.'")</td></tr>';
                                    echo '<tr><td>'.__('Neues Themenformular anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show = cpc_get_shortcode_default($values, 'cpc_forum_post-show', false);
                                        echo '<input type="checkbox" name="cpc_forum_post-show"'.($show ? ' CHECKED' : '').'></td><td>(show="'.($show ? '1' : '0').'")</td></tr>';        
                                    echo '<tr><td>'.__('Forumauswahl zulassen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        echo '<input type="checkbox" class="cpc_shortcode_tip_available"></td><td></td></tr>';
                                    echo '<tr id="cpc_shortcode_tip" style="display:none"';
                                        echo '><td colspan="3" class="cpc_admin_shortcode_tip">'.__('Füge slug="choose" zum Shortcode auf Deiner Seite hinzu. Dieser Wert wird hier nicht eingestellt (nur zur Information).', CPC2_TEXT_DOMAIN).'</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung für die Forumsauswahl", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $post_to_label = cpc_get_shortcode_default($values, 'cpc_forum_post-post_to_label', __('Beitrag in', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_post-post_to_label" value="'.$post_to_label.'" /></td><td>(post_to_label="'.$post_to_label.'")</td></tr>';                                    

                                    do_action('cpc_show_styling_options_hook', 'cpc_forum_post', $values);                

                                echo '</table>';  
                            echo '</div>';   

                            // [cpc-forums]
                            $values = get_option('cpc_shortcode_options_'.'cpc_forums') ? get_option('cpc_shortcode_options_'.'cpc_forums') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_forums_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt eine oberste Ebene aller Foren an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-forums] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-forums');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Als Dropdown anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_as_dropdown = cpc_get_shortcode_default($values, 'cpc_forums-show_as_dropdown', false);
                                        echo '<input type="checkbox" name="cpc_forums-show_as_dropdown"'.($show_as_dropdown ? ' CHECKED' : '').'></td><td>(show_as_dropdown="'.($show_as_dropdown ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Schneller Sprungtext für Dropdown", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_as_dropdown_text = cpc_get_shortcode_default($values, 'cpc_forums-show_as_dropdown_text', __('Schneller Sprung zum Forum...', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forums-show_as_dropdown_text" value="'.$show_as_dropdown_text.'" /></td><td>(show_as_dropdown_text="'.$show_as_dropdown_text.'")</td></tr>';
                                    echo '<tr><td>'.__("Text für die Überschrift des Forumtitels", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $forum_title = cpc_get_shortcode_default($values, 'cpc_forums-forum_title', __('Forum', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forums-forum_title" value="'.$forum_title.'" /></td><td>(forum_title="'.$forum_title.'")</td></tr>';
                                    echo '<tr><td>'.__("Themenanzahl-Kopfzeilentext", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $forum_count = cpc_get_shortcode_default($values, 'cpc_forums-forum_count', __('Zähler', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forums-forum_count" value="'.$forum_count.'" /></td><td>(forum_count="'.$forum_count.'")</td></tr>';
                                    echo '<tr><td>'.__("Kopfzeilentext des letzten Posters", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $forum_last_activity = cpc_get_shortcode_default($values, 'cpc_forums-forum_last_activity', __('Letzter Poster', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forums-forum_last_activity" value="'.$forum_last_activity.'" /></td><td>(forum_last_activity="'.$forum_last_activity.'")</td></tr>';
                                    echo '<tr><td>'.__("Neueste-Kopfzeilentext", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $forum_freshness = cpc_get_shortcode_default($values, 'cpc_forums-forum_freshness', __('Neueste', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forums-forum_freshness" value="'.$forum_freshness.'" /></td><td>(forum_freshness="'.$forum_freshness.'")</td></tr>';
                                    echo '<tr><td>'.__('Basieren Poster und Aktualität auf der neuesten Antwort?', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $forum_count_include_replies = cpc_get_shortcode_default($values, 'cpc_forums-forum_count_include_replies', true);
                                        echo '<input type="checkbox" name="cpc_forums-forum_count_include_replies"'.($forum_count_include_replies ? ' CHECKED' : '').'></td><td>(forum_count_include_replies="'.($forum_count_include_replies ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Kinderforen anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_children = cpc_get_shortcode_default($values, 'cpc_forums-show_children', false);
                                        echo '<input type="checkbox" name="cpc_forums-show_children"'.($show_children ? ' CHECKED' : '').'></td><td>(show_children="'.($show_children ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Kopfzeile anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_header = cpc_get_shortcode_default($values, 'cpc_forums-show_header', false);
                                        echo '<input type="checkbox" name="cpc_forums-show_header"'.($show_header ? ' CHECKED' : '').'></td><td>(show_header="'.($show_header ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Kopfzeilentext zur Themenanzahl anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_count = cpc_get_shortcode_default($values, 'cpc_forums-show_count', true);
                                        echo '<input type="checkbox" name="cpc_forums-show_count"'.($show_count ? ' CHECKED' : '').'></td><td>(show_count="'.($show_count ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Kopfzeilentext des letzten Posters anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_last_activity = cpc_get_shortcode_default($values, 'cpc_forums-show_last_activity', true);
                                        echo '<input type="checkbox" name="cpc_forums-show_last_activity"'.($show_last_activity ? ' CHECKED' : '').'></td><td>(show_last_activity="'.($show_last_activity ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Neueste-Kopfzeilentext anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_freshness = cpc_get_shortcode_default($values, 'cpc_forums-show_freshness', true);
                                        echo '<input type="checkbox" name="cpc_forums-show_freshness"'.($show_freshness ? ' CHECKED' : '').'></td><td>(show_freshness="'.($show_freshness ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Oberste Ebene als Links', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $level_0_links = cpc_get_shortcode_default($values, 'cpc_forums-level_0_links', true);
                                        echo '<input type="checkbox" name="cpc_forums-level_0_links"'.($level_0_links ? ' CHECKED' : '').'></td><td>(level_0_links="'.($level_0_links ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Datenschutzfilter', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $include = cpc_get_shortcode_default($values, 'cpc_forums-include', 'all');
                                        echo '<select name="cpc_forums-include">';
                                            echo '<option value="all"'.($include == 'all' ? ' SELECTED' : '').'>'.__('Alle', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="private"'.($include == 'private' ? ' SELECTED' : '').'>'.__('Nur privat', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="public"'.($include == 'public' ? ' SELECTED' : '').'>'.__('Öffentlich', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(include="'.$include.'")</td></tr>';
                                    echo '<tr><td>'.__('Basisdatum', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $base_date = cpc_get_shortcode_default($values, 'cpc_forums-base_date', 'post_date_gmt');
                                        echo '<select name="cpc_forums-base_date">';
                                            echo '<option value="post_date_gmt"'.($base_date == 'post_date_gmt' ? ' SELECTED' : '').'>'.__('GMT', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="post_date"'.($base_date == 'post_date' ? ' SELECTED' : '').'>'.__('Lokal', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(base_date="'.$base_date.'")</td></tr>';

                                    echo '<tr><td colspan=3 class="cpc_section">'.__('Die neuesten Aktivitäten werden unter jedem Forum angezeigt', CPC2_TEXT_DOMAIN).'</td></tr>';    
                                    
                                    echo '<tr><td>'.__("Anzahl der anzuzeigenden Themen (oder 'none')", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_posts = cpc_get_shortcode_default($values, 'cpc_forums-show_posts', 3);
                                        echo '<input type="text" name="cpc_forums-show_posts" value="'.$show_posts.'" /></td><td>(show_posts="'.$show_posts.'")</td></tr>';
                                    echo '<tr><td>'.__('Themenüberschrift anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_posts_header = cpc_get_shortcode_default($values, 'cpc_forums-show_posts_header', true);
                                        echo '<input type="checkbox" name="cpc_forums-show_posts_header"'.($show_posts_header ? ' CHECKED' : '').'></td><td>(show_posts_header="'.($show_posts_header ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Zählsummen über den Themen anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_summary = cpc_get_shortcode_default($values, 'cpc_forums-show_summary', false);
                                        echo '<input type="checkbox" name="cpc_forums-show_summary"'.($show_summary ? ' CHECKED' : '').'></td><td>(show_summary="'.($show_summary ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Titel der Kopfzeile", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $header_title = cpc_get_shortcode_default($values, 'cpc_forums-header_title', __('Thema', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forums-header_title" value="'.$header_title.'" /></td><td>(header_title="'.$header_title.'")</td></tr>';
                                    echo '<tr><td>'.__("Titel der Antworten", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $header_count = cpc_get_shortcode_default($values, 'cpc_forums-header_count', __('Antworten', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forums-header_count" value="'.$header_count.'" /></td><td>(header_count="'.$header_count.'")</td></tr>';
                                    echo '<tr><td>'.__("Titel der letzten Aktivität", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $header_last_activity = cpc_get_shortcode_default($values, 'cpc_forums-header_last_activity', __('Letzte Aktivität', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forums-header_last_activity" value="'.$header_last_activity.'" /></td><td>(header_last_activity="'.$header_last_activity.'")</td></tr>';
                                    echo '<tr><td>'.__("Begrenzung der Titellänge", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $title_length = cpc_get_shortcode_default($values, 'cpc_forums-title_length', 50);
                                        echo '<input type="text" name="cpc_forums-title_length" value="'.$title_length.'" /> '.__('Zeichen', CPC2_TEXT_DOMAIN).'</td><td>(title_length="'.$title_length.'")</td></tr>';
                                    echo '<tr><td>'.__('Schließe geschlossene Themen ein', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_closed = cpc_get_shortcode_default($values, 'cpc_forums-show_closed', true);
                                        echo '<input type="checkbox" name="cpc_forums-show_closed"'.($show_closed ? ' CHECKED' : '').'></td><td>(show_closed="'.($show_closed ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Nicht einrücken', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $no_indent = cpc_get_shortcode_default($values, 'cpc_forums-no_indent', true);
                                        echo '<input type="checkbox" name="cpc_forums-no_indent"'.($no_indent ? ' CHECKED' : '').'></td><td>(no_indent="'.($no_indent ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.sprintf(__("Bildbreite des Forums (hinzufügen über <a href='%s'>Forum bearbeiten</a>)", CPC2_TEXT_DOMAIN), admin_url( 'edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post' )).'</td><td>';
                                        $featured_image_width = cpc_get_shortcode_default($values, 'cpc_forums-featured_image_width', 0);
                                        echo '<input type="text" name="cpc_forums-featured_image_width" value="'.$featured_image_width.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(featured_image_width="'.$featured_image_width.'")</td></tr>';    

                                    do_action('cpc_show_styling_options_hook', 'cpc_forums', $values);                

                                echo '</table>';  
                            echo '</div>';   

                            // [cpc-forum-show-posts]
                            $values = get_option('cpc_shortcode_options_'.'cpc_forum_show_posts') ? get_option('cpc_shortcode_options_'.'cpc_forum_show_posts') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_forum_show_posts_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Flexible Möglichkeit, Forenbeiträge anzuzeigen.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.sprintf(__('Füge [cpc-forum-show-posts slug="xxx"] zur ClassicPress-Seite Deines Forums hinzu (klicke <a href="%s">hier</a> auf den Link zur Seite). Dabei ist xxx der <a href="%s">Slug Deines Forums</a>.', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpccom_forum_setup' ), admin_url( 'edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post' ));    
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-forum-show-posts');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Sortierungs-Wert', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $order = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-order', 'date');
                                        echo '<select name="cpc_forum_post-order">';
                                            echo '<option value="author"'.($order == 'author' ? ' SELECTED' : '').'>'.__('Autor', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="content"'.($order == 'content' ? ' SELECTED' : '').'>'.__('Inhalt', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="date"'.($order == 'date' ? ' SELECTED' : '').'>'.__('Datum', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="title"'.($order == 'title' ? ' SELECTED' : '').'>'.__('Titel', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(order="'.$order.'")</td></tr>';
                                    echo '<tr><td>'.__('Sortierung', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $orderby = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-orderby', 'DESC');
                                        echo '<select name="cpc_forum_post-orderby">';
                                            echo '<option value="ASC"'.($orderby == 'ASC' ? ' SELECTED' : '').'>'.__('Aufsteigend', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="DESC"'.($orderby == 'DESC' ? ' SELECTED' : '').'>'.__('Absteigend', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(orderby="'.$orderby.'")</td></tr>';
                                    echo '<tr><td>'.__('Status', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $status = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-status', '');
                                        echo '<select name="cpc_forum_post-status">';
                                            echo '<option value=""'.($status == '' ? ' SELECTED' : '').'>'.__('Alle', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="open"'.($status == 'open' ? ' SELECTED' : '').'>'.__('Offen', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="closed"'.($status == 'closed' ? ' SELECTED' : '').'>'.__('Geschlossen', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(status="'.$status.'")</td></tr>';
                                    echo '<tr><td>'.__('Beziehe Themen mit ein', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $include_posts = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-include_posts', true);
                                        echo '<input type="checkbox" name="cpc_forum_show_posts-include_posts"'.($include_posts ? ' CHECKED' : '').'></td><td>(include_posts="'.($include_posts ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Beziehe Antworten mit ein', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $include_replies = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-include_replies', true);
                                        echo '<input type="checkbox" name="cpc_forum_show_posts-include_replies"'.($include_replies ? ' CHECKED' : '').'></td><td>(include_replies="'.($include_replies ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Beziehe Kommentare mit ein', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $include_comments = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-include_comments', false);
                                        echo '<input type="checkbox" name="cpc_forum_show_posts-include_comments"'.($include_comments ? ' CHECKED' : '').'></td><td>(include_comments="'.($include_comments ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Geschlossen Präfix", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $closed_prefix = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-closed_prefix', __('eschlossen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_show_posts-closed_prefix" value="'.$closed_prefix.'" /></td><td>(closed_prefix="'.$closed_prefix.'")</td></tr>';    
                                    echo '<tr><td>'.__('Autor anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_author = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-show_author', true);
                                        echo '<input type="checkbox" name="cpc_forum_show_posts-show_author"'.($show_author ? ' CHECKED' : '').'></td><td>(show_author="'.($show_author ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Format des Autorentextes (für oben)", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $author_format = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-author_format', __('Von %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_show_posts-author_format" value="'.$author_format.'" /></td><td>(author_format="'.$author_format.'")</td></tr>';    
                                    echo '<tr><td>'.__('Verlinke den Autor mit der Profilseite', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $author_link = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-author_link', true);
                                        echo '<input type="checkbox" name="cpc_forum_show_posts-author_link"'.($author_link ? ' CHECKED' : '').'></td><td>(author_link="'.($author_link ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Datum anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_date = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-show_date', true);
                                        echo '<input type="checkbox" name="cpc_forum_show_posts-show_date"'.($show_date ? ' CHECKED' : '').'></td><td>(show_date="'.($show_date ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Format des Datumstextes (für oben)", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $date_format = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-date_format', __('vor %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_show_posts-date_format" value="'.$date_format.'" /></td><td>(date_format="'.$date_format.'")</td></tr>';    
                                    echo '<tr><td>'.__('Ausschnitt anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_snippet = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-show_snippet', true);
                                        echo '<input type="checkbox" name="cpc_forum_show_posts-show_snippet"'.($show_snippet ? ' CHECKED' : '').'></td><td>(show_snippet="'.($show_snippet ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Text für Link zum Forumsbeitrag", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $more_link = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-more_link', __('lesen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_show_posts-more_link" value="'.$more_link.'" /></td><td>(more_link="'.$more_link.'")</td></tr>';    
                                    echo '<tr><td>'.__("Text wird angezeigt, wenn keine Beiträge vorhanden sind", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $no_posts = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-no_posts', __('Keine Beiträge', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_show_posts-no_posts" value="'.$no_posts.'" /></td><td>(no_posts="'.$no_posts.'")</td></tr>';    
                                    echo '<tr><td>'.__("Maximale Länge des Titels", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $title_length = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-title_length', 50);
                                        echo '<input type="text" name="cpc_forum_show_posts-title_length" value="'.$title_length.'" /></td><td>(title_length="'.$title_length.'")</td></tr>';    
                                    echo '<tr><td>'.__("Maximale Länge des Snippets", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $snippet_length = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-snippet_length', 30);
                                        echo '<input type="text" name="cpc_forum_show_posts-snippet_length" value="'.$snippet_length.'" /></td><td>(snippet_length="'.$snippet_length.'")</td></tr>';    
                                    echo '<tr><td>'.__("Anzahl der angezeigten Beiträge", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $max = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-max', 10);
                                        echo '<input type="text" name="cpc_forum_show_posts-max" value="'.$max.'" /></td><td>(max="'.$max.'")</td></tr>';    
                                    echo '<tr><td>'.__('Basisdatum', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $base_date = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-base_date', 'post_date_gmt');
                                        echo '<select name="cpc_forum_show_posts-base_date">';
                                            echo '<option value="post_date_gmt"'.($base_date == 'post_date_gmt' ? ' SELECTED' : '').'>'.__('GMT', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="post_date"'.($base_date == 'post_date' ? ' SELECTED' : '').'>'.__('Lokal', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(base_date="'.$base_date.'")</td></tr>';
                                    echo '<tr><td colspan=3 class="cpc_section">'.__('Zusammenfassender Satz und Autoren-Avatar', CPC2_TEXT_DOMAIN).'</td></tr>';        
                                    echo '<tr><td>'.__('Zusammenfassenden Satz und Avatar anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $summary = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-summary', false);
                                        echo '<input type="checkbox" name="cpc_forum_show_posts-summary"'.($summary ? ' CHECKED' : '').'></td><td>(summary="'.($summary ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Format", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $summary_format = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-summary_format', __('%s %s %s %s vor %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_show_posts-summary_format" value="'.$summary_format.'" /></td><td>(summary_format="'.$summary_format.'")</td></tr>';    
                                    echo '<tr><td>'.__("Größe des Avatars", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $summary_avatar_size = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-summary_avatar_size', 32);
                                        echo '<input type="text" name="cpc_forum_show_posts-summary_avatar_size" value="'.$summary_avatar_size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(summary_avatar_size="'.$summary_avatar_size.'")</td></tr>';    
                                    echo '<tr><td>'.__("Text für gestartet", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $summary_started = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-summary_started', __('gestartet', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_show_posts-summary_started" value="'.$summary_started.'" /></td><td>(summary_started="'.$summary_started.'")</td></tr>';    
                                    echo '<tr><td>'.__("Text für antwortet auf", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $summary_replied = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-summary_replied', __('geantwortet zu', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_show_posts-summary_replied" value="'.$summary_replied.'" /></td><td>(summary_replied="'.$summary_replied.'")</td></tr>';    
                                    echo '<tr><td>'.__("Text für kommentiert auf", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $summary_commented = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-summary_commented', __('kommentiert zu', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_show_posts-summary_commented" value="'.$summary_commented.'" /></td><td>(summary_commented="'.$summary_commented.'")</td></tr>';    
                                    echo '<tr><td>'.__("Maximale Länge für Titel", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $summary_title_length = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-summary_title_length', 150);
                                        echo '<input type="text" name="cpc_forum_show_posts-summary_title_length" value="'.$summary_title_length.'" /> '.__('Zeichen', CPC2_TEXT_DOMAIN).'</td><td>(summary_title_length="'.$summary_title_length.'")</td></tr>';    
                                    echo '<tr><td>'.__("Maximale Länge für Inhaltsausschnitt", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $summary_snippet_length = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-summary_snippet_length', 50);
                                        echo '<input type="text" name="cpc_forum_show_posts-summary_snippet_length" value="'.$summary_snippet_length.'" /> '.__('Zeichen', CPC2_TEXT_DOMAIN).'</td><td>(summary_snippet_length="'.$summary_snippet_length.'")</td></tr>';    
                                    echo '<tr><td>'.__('Gegebenenfalls ungelesen anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $summary_show_unread = cpc_get_shortcode_default($values, 'cpc_forum_show_posts-summary_show_unread', true);
                                        echo '<input type="checkbox" name="cpc_forum_show_posts-summary_show_unread"'.($summary_show_unread ? ' CHECKED' : '').'></td><td>(summary_show_unread="'.($summary_show_unread ? '1' : '0').'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_forum_show_posts', $values);                

                                echo '</table>';  
                            echo '</div>';      
                        
                            // [cpc-forum-children]
                            $values = get_option('cpc_shortcode_options_'.'cpc_forum_children') ? get_option('cpc_shortcode_options_'.'cpc_forum_children') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_forum_children_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt untergeordnete Foren als Einrichtung in Forum bearbeiten an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.sprintf(__('Füge [cpc-forum-children slug="xxx"] zur ClassicPress-Seite Deines Forums hinzu (klicke <a href="%s">hier</a> auf den Link zur <strong>Seite</strong>), wobei xxx der <a href="%s">Slug des übergeordneten Forums</a> ist.', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpccom_forum_setup' ), admin_url( 'edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post' ));    
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-forum-children');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Kopfzeile anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_header = cpc_get_shortcode_default($values, 'cpc_forum_children-show_header', true);
                                        echo '<input type="checkbox" name="cpc_forum_children-show_header"'.($show_header ? ' CHECKED' : '').'></td><td>(show_header="'.($show_header ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Zusammenfassung anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_summary = cpc_get_shortcode_default($values, 'cpc_forum_children-show_summary', true);
                                        echo '<input type="checkbox" name="cpc_forum_children-show_summary"'.($show_summary ? ' CHECKED' : '').'></td><td>(show_summary="'.($show_summary ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Anzahl der Beiträge/Antworten anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_count = cpc_get_shortcode_default($values, 'cpc_forum_children-show_count', true);
                                        echo '<input type="checkbox" name="cpc_forum_children-show_count"'.($show_count ? ' CHECKED' : '').'></td><td>(show_count="'.($show_count ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Letzte Aktivität anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_last_activity = cpc_get_shortcode_default($values, 'cpc_forum_children-show_last_activity', true);
                                        echo '<input type="checkbox" name="cpc_forum_children-show_last_activity"'.($show_last_activity ? ' CHECKED' : '').'></td><td>(show_last_activity="'.($show_last_activity ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Zeige Neueste', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_freshness = cpc_get_shortcode_default($values, 'cpc_forum_children-show_freshness', true);
                                        echo '<input type="checkbox" name="cpc_forum_children-show_freshness"'.($show_freshness ? ' CHECKED' : '').'></td><td>(show_freshness="'.($show_freshness ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Forum-Namensbeschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $forum_title = cpc_get_shortcode_default($values, 'cpc_forum_children-forum_title', __('Kind-Forum', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_children-forum_title" value="'.$forum_title.'" /></td><td>(forum_title="'.$forum_title.'")</td></tr>';                            
                                    echo '<tr><td>'.__("Anzahl der Beiträge/Antworten Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $forum_count = cpc_get_shortcode_default($values, 'cpc_forum_children-forum_count', __('Aktivität', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_children-forum_count" value="'.$forum_count.'" /></td><td>(forum_count="'.$forum_count.'")</td></tr>';                            
                                    echo '<tr><td>'.__("Letzter Poster-Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $forum_last_activity = cpc_get_shortcode_default($values, 'cpc_forum_children-forum_last_activity', __('Letzter Poster', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_children-forum_last_activity" value="'.$forum_last_activity.'" /></td><td>(forum_last_activity="'.$forum_last_activity.'")</td></tr>';                            
                                    echo '<tr><td>'.__("Neueste-Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $forum_freshness = cpc_get_shortcode_default($values, 'cpc_forum_children-forum_freshness', __('Neueste', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_children-forum_freshness" value="'.$forum_freshness.'" /></td><td>(forum_freshness="'.$forum_freshness.'")</td></tr>';                            
                                    echo '<tr><td>'.__('Forentitel mit Forum verknüpfen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $link = cpc_get_shortcode_default($values, 'cpc_forum_children-link', true);
                                        echo '<input type="checkbox" name="cpc_forum_children-link"'.($link ? ' CHECKED' : '').'></td><td>(link="'.($link ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Basisdatum', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $base_date = cpc_get_shortcode_default($values, 'cpc_forum_children-base_date', 'post_date_gmt');
                                        echo '<select name="cpc_forum_children-base_date">';
                                            echo '<option value="post_date_gmt"'.($base_date == 'post_date_gmt' ? ' SELECTED' : '').'>'.__('GMT', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="post_date"'.($base_date == 'post_date' ? ' SELECTED' : '').'>'.__('Lokal', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(base_date="'.$base_date.'")</td></tr>';

                                    echo '<tr><td colspan="3" style="font-weight:bold;background-color:#dfdfdf">'.__('Beiträge im Kinderforum', CPC2_TEXT_DOMAIN).'</td></tr>';
                                    echo '<tr><td colspan="3" style="font-style:italic">'.__('If activated, it is recommended to switch off the first 5 options above, and to style the "cpc_forum_children_description" class for the forum names.', CPC2_TEXT_DOMAIN).'</td></tr>';
                                    echo '<tr><td>'.__('Untergeordnete Forumbeiträge anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_child_posts = cpc_get_shortcode_default($values, 'cpc_forum_children-show_child_posts', false);
                                        echo '<input type="checkbox" name="cpc_forum_children-show_child_posts"'.($show_child_posts ? ' CHECKED' : '').'></td><td>(show_child_posts="'.($show_child_posts ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Antwortanzahl-Label", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $child_posts_count = cpc_get_shortcode_default($values, 'cpc_forum_children-child_posts_count', __('Antworten', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_children-child_posts_count" value="'.$child_posts_count.'" /></td><td>(child_posts_count="'.$child_posts_count.'")</td></tr>';                            
                                    echo '<tr><td>'.__("Letzter Poster-Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $child_posts_last_activity = cpc_get_shortcode_default($values, 'cpc_forum_children-child_posts_last_activity', __('Letzter Poster', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_children-child_posts_last_activity" value="'.$child_posts_last_activity.'" /></td><td>(child_posts_last_activity="'.$child_posts_last_activity.'")</td></tr>';
                                    echo '<tr><td>'.__("Neueste-Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $child_posts_freshness = cpc_get_shortcode_default($values, 'cpc_forum_children-child_posts_freshness', __('Neueste', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_forum_children-child_posts_freshness" value="'.$child_posts_freshness.'" /></td><td>(child_posts_freshness="'.$child_posts_freshness.'")</td></tr>';
                                    echo '<tr><td>'.__("Maximale Anzahl an Beiträgen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $child_posts_max = cpc_get_shortcode_default($values, 'cpc_forum_children-child_posts_max', 3);
                                        echo '<input type="text" name="cpc_forum_children-child_posts_max" value="'.$child_posts_max.'" /></td><td>(child_posts_max="'.$child_posts_max.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_forum_children', $values);                

                                echo '</table>';  
                            echo '</div>';                              

                            // [cpc-forum-sharethis]
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_forum_sharethis_insert_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.sprintf(__("Fügt ShareThis-Code ein, der <em>jedem</em> Forum <a href='%s'>hier</a> hinzugefügt wird.", CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpccom_forum_setup' )).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.sprintf(__('Füge [cpc-forum-sharethis slug="xxx"] zu einer ClassicPress-Seite hinzu, wobei xxx der <a href="%s">Slug Deines Forums</a> ist.', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpccom_forum_setup' ));
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-forum-sharethis');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Keine Optionen', CPC2_TEXT_DOMAIN).'</td></tr>';

                                echo '</table>';  
                            echo '</div>';          

                            /* ----------------------- FRIENDS TAB ----------------------- */    

                            // [cpc-friends]
                            $values = get_option('cpc_shortcode_options_'.'cpc_friends') ? get_option('cpc_shortcode_options_'.'cpc_friends') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_friends_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt die Freunde eines Benutzers an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-friends] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-friends');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Anzahl der anzuzeigenden Freunde', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $count = cpc_get_shortcode_default($values, 'cpc_friends-count', 10);
                                        echo '<input type="text" name="cpc_friends-count" value="'.$count.'" /></td><td>(count="'.$count.'")</td></tr>';
                                    echo '<tr><td>'.__('Avatargröße', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $size = cpc_get_shortcode_default($values, 'cpc_friends-size', 64);
                                        echo '<input type="text" name="cpc_friends-size" value="'.$size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(size="'.$size.'")</td></tr>';
                                    echo '<tr><td>'.__('Verknüpfe Anzeigenamen mit der Profilseite', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $link = cpc_get_shortcode_default($values, 'cpc_friends-link', true);
                                        echo '<input type="checkbox" name="cpc_friends-link"'.($link ? ' CHECKED' : '').'></td><td>(link="'.($link ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Zeigt an, wann zuletzt aktiv', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_last_active = cpc_get_shortcode_default($values, 'cpc_friends-show_last_active', true);
                                        echo '<input type="checkbox" name="cpc_friends-show_last_active"'.($show_last_active ? ' CHECKED' : '').'></td><td>(show_last_active="'.($show_last_active ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Text für die letzte Aktivität', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $last_active_text = cpc_get_shortcode_default($values, 'cpc_friends-last_active_text', __('Zuletzt gesehen:', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends-last_active_text" value="'.$last_active_text.'" /></td><td>(last_active_text="'.$last_active_text.'")</td></tr>';
                                    echo '<tr><td>'.__('Format für die letzte Aktivität', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $last_active_format = cpc_get_shortcode_default($values, 'cpc_friends-last_active_format', __('vor %s', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends-last_active_format" value="'.$last_active_format.'" /></td><td>(last_active_format="'.$last_active_format.'")</td></tr>';
                                    echo '<tr><td>'.__('Text für privat', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $private = cpc_get_shortcode_default($values, 'cpc_friends-private', __('Private Informationen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends-private" value="'.$private.'" /></td><td>(private="'.$private.'")</td></tr>';
                                    echo '<tr><td>'.__('Text für keine Freunde', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $none = cpc_get_shortcode_default($values, 'cpc_friends-none', __('Keine Freunde', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends-none" value="'.$none.'" /></td><td>(none="'.$none.'")</td></tr>';
                                    echo '<tr><td>'.__('Layout', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $layout = cpc_get_shortcode_default($values, 'cpc_friends-layout', 'list');
                                        echo '<select name="cpc_friends-layout">';
                                            echo '<option value="list"'.($layout == 'list' ? ' SELECTED' : '').'>'.__('Liste', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="fluid"'.($layout == 'fluid' ? ' SELECTED' : '').'>'.__('Flüssig', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(layout="'.$layout.'")</td></tr>';
                                    echo '<tr><td>'.__('Abgemeldet-Text', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $logged_out_msg = cpc_get_shortcode_default($values, 'cpc_friends-logged_out_msg', __('Du musst angemeldet sein, um diese Seite anzuzeigen.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends-logged_out_msg" value="'.$logged_out_msg.'" /></td><td>(logged_out_msg="'.$logged_out_msg.'")</td></tr>';
                                    echo '<tr><td>'.__('Option Alle Freunde entfernen.', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $remove_all_friends = cpc_get_shortcode_default($values, 'cpc_friends-remove_all_friends', true);
                                        echo '<input type="checkbox" name="cpc_friends-remove_all_friends"'.($remove_all_friends ? ' CHECKED' : '').'></td><td>(remove_all_friends="'.($remove_all_friends ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Alle Freunde entfernen-Text', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $remove_all_friends_msg = cpc_get_shortcode_default($values, 'cpc_friends-remove_all_friends_msg', __('Alle Freunde entfernen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends-remove_all_friends_msg" value="'.$remove_all_friends_msg.'" /></td><td>(remove_all_friends_msg="'.$remove_all_friends_msg.'")</td></tr>';
                                    echo '<tr><td>'.__('Entferne alle Freunde Bestätigung', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $remove_all_friends_sure_msg = cpc_get_shortcode_default($values, 'cpc_friends-remove_all_friends_sure_msg', __('Bist du sicher? Das kann nicht rückgängig gemacht werden!', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends-remove_all_friends_sure_msg" value="'.$remove_all_friends_sure_msg.'" /></td><td>(remove_all_friends_sure_msg="'.$remove_all_friends_sure_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Optionale URL zum Anmelden", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $login_url = cpc_get_shortcode_default($values, 'cpc_friends-login_url', '');
                                        echo '<input type="text" name="cpc_friends-login_url" value="'.$login_url.'" /></td><td>(login_url="'.$login_url.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_friends', $values);                    

                                echo '</table>';
                            echo '</div>';        

                            // [cpc-friends-status]
                            $values = get_option('cpc_shortcode_options_'.'cpc_friends_status') ? get_option('cpc_shortcode_options_'.'cpc_friends_status') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_friends_status_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt den Freundschaftsstatus eines Benutzers mit dem aktuellen Benutzer an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-friends-status] zur ClassicPress-Seite hinzu, die als Profilseite verwendet wird.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-friends-status');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Du bist ein Freund-Text.", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $friends_yes = cpc_get_shortcode_default($values, 'cpc_friends_status-friends_yes', __('Ihr seid Freunde', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends_status-friends_yes" value="'.$friends_yes.'" /></td><td>(friends_yes="'.$friends_yes.'")</td></tr>';
                                    echo '<tr><td>'.__("Freundschaftsanfrage steht noch aus-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $friends_pending = cpc_get_shortcode_default($values, 'cpc_friends_status-friends_pending', __('Du hast eine Freundschaft beantragt', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends_status-friends_pending" value="'.$friends_pending.'" /></td><td>(friends_pending="'.$friends_pending.'")</td></tr>';
                                    echo '<tr><td>'.__("Du hast eine Freundschaftsanfrage erhalten-Text", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $friend_request = cpc_get_shortcode_default($values, 'cpc_friends_status-friend_request', __('Du hast eine Freundschaftsanfrage', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends_status-friend_request" value="'.$friend_request.'" /></td><td>(friend_request="'.$friend_request.'")</td></tr>';
                                    echo '<tr><td>'.__("Du bist kein Freund-Text.", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $friends_no = cpc_get_shortcode_default($values, 'cpc_friends_status-friends_no', __('Ihr seid keine Freunde', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends_status-friends_no" value="'.$friends_no.'" /></td><td>(friends_no="'.$friends_no.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_friends_status', $values);                    

                                echo '</table>';
                            echo '</div>';        

                            // [cpc-friends-pending]
                            $values = get_option('cpc_shortcode_options_'.'cpc_friends_pending') ? get_option('cpc_shortcode_options_'.'cpc_friends_pending') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_friends_pending_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt ausstehende Freundschaftsanfragen für den aktuellen Benutzer an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-friends-pending] zur ClassicPress-Seite hinzu, die als Profilseite verwendet wird.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-friends-pending');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Maximale Anzahl der anzuzeigenden Anfragen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $count = cpc_get_shortcode_default($values, 'cpc_friends_pending-count', 10);
                                        echo '<input type="text" name="cpc_friends_pending-count" value="'.$count.'" /></td><td>(count="'.$count.'")</td></tr>';
                                    echo '<tr><td>'.__("Avatargröße", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $size = cpc_get_shortcode_default($values, 'cpc_friends_pending-size', 64);
                                        echo '<input type="text" name="cpc_friends_pending-size" value="'.$size.'" /></td><td>(size="'.$size.'")</td></tr>';
                                    echo '<tr><td>'.__('Verknüpfe den Avatar mit der Profilseite', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $link = cpc_get_shortcode_default($values, 'cpc_friends_pending-link', true);
                                        echo '<input type="checkbox" name="cpc_friends_pending-link"'.($link ? ' CHECKED' : '').'></td><td>(link="'.($link ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Optionale CSS-Klasse für die Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $class = cpc_get_shortcode_default($values, 'cpc_friends_pending-class', '');
                                        echo '<input type="text" name="cpc_friends_pending-class" value="'.$class.'" /></td><td>(class="'.$class.'")</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung für die Schaltfläche Akzeptieren.", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $accept_request_label = cpc_get_shortcode_default($values, 'cpc_friends_pending-accept_request_label', __('Akzeptieren', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends_pending-accept_request_label" value="'.$accept_request_label.'" /></td><td>(accept_request_label="'.$accept_request_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung für die Schaltfläche Ablehnen.", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $reject_request_label = cpc_get_shortcode_default($values, 'cpc_friends_pending-reject_request_label', __('Ablehnen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends_pending-reject_request_label" value="'.$reject_request_label.'" /></td><td>(reject_request_label="'.$reject_request_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Text für keine Freundschaftsanfragen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $none = cpc_get_shortcode_default($values, 'cpc_friends_pending-none', '');
                                        echo '<input type="text" name="cpc_friends_pending-none" value="'.$none.'" /></td><td>(none="'.$none.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_friends_pending', $values);                    

                                echo '</table>';
                            echo '</div>';       

                            // [cpc-friends-add-button]
                            $values = get_option('cpc_shortcode_options_'.'cpc_friends_add_button') ? get_option('cpc_shortcode_options_'.'cpc_friends_add_button') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_friends_add_button_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt eine Schaltfläche an, um eine Anfrage an einen anderen Benutzer als Freund zu richten.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-friends-add-button] zur ClassicPress-Seite hinzu, die als Profilseite verwendet wird.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-friends-add-button');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Beschriftung für die Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $label = cpc_get_shortcode_default($values, 'cpc_friends_add_button-label', __('Freundschaft schließen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends_add_button-label" value="'.$label.'" /></td><td>(label="'.$label.'")</td></tr>';
                                    echo '<tr><td>'.__("Freundschaft aufheben Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $cancel_label = cpc_get_shortcode_default($values, 'cpc_friends_add_button-cancel_label', __('Freundschaft kündigen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends_add_button-cancel_label" value="'.$cancel_label.'" /></td><td>(cancel_label="'.$cancel_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Freundschaftsanfrage löschen-Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $cancel_request_label = cpc_get_shortcode_default($values, 'cpc_friends_add_button-cancel_request_label', __('Freundschaftsanfrage abbrechen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_friends_add_button-cancel_request_label" value="'.$cancel_request_label.'" /></td><td>(cancel_request_label="'.$cancel_request_label.'")</td></tr>';
                                    echo '<tr><td>'.__("Optionale CSS-Klasse für die Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $class = cpc_get_shortcode_default($values, 'cpc_friends_add_button-class', '');
                                        echo '<input type="text" name="cpc_friends_add_button-class" value="'.$class.'" /></td><td>(class="'.$class.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_friends_add_button', $values);                    

                                echo '</table>';
                            echo '</div>';       

                            // [cpc-friends-count]
                            $values = get_option('cpc_shortcode_options_'.'cpc_friends_count') ? get_option('cpc_shortcode_options_'.'cpc_friends_count') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_friends_count_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt die Anzahl der Freunde (akzeptiert oder ausstehend) eines Benutzers an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-friends-count] zur ClassicPress-Seite hinzu, die als Profilseite verwendet wird.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-friends-count');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Benutzer', CPC2_TEXT_DOMAIN).'</td><td>';
                                    $user_id = cpc_get_shortcode_default($values, 'cpc_friends_count-user_id', '');
                                    echo '<select name="cpc_friends_count-user_id">';
                                        echo '<option value=""'.($user_id == '' ? ' SELECTED' : '').'>'.__('Spiegelt den Seitenkontext wider', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '<option value="user"'.($user_id == 'user' ? ' SELECTED' : '').'>'.__('Aktueller Benutzer', CPC2_TEXT_DOMAIN).'</option>';
                                    echo '</select> '.__('oder im Shortcode auf eine Benutzer-ID setzen', CPC2_TEXT_DOMAIN).'</td><td>(user_id="'.$user_id.'")</td></tr>';    
                                    echo '<tr><td>'.__('Status', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $layout = cpc_get_shortcode_default($values, 'cpc_friends_count-status', 'accepted');
                                        echo '<select name="cpc_friends_count-status">';
                                            echo '<option value="accepted"'.($layout == 'accepted' ? ' SELECTED' : '').'>'.__('Akzeptiert', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="pending"'.($layout == 'pending' ? ' SELECTED' : '').'>'.__('Ausstehend', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(status="'.$layout.'")</td></tr>';
                        
                                    echo '<tr><td>'.__("Link (optional)", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $url = cpc_get_shortcode_default($values, 'cpc_friends_count-url', '');
                                        echo '<input type="text" name="cpc_friends_count-url" value="'.$url.'" /></td><td>(url="'.$url.'")</td></tr>';                        

                                    do_action('cpc_show_styling_options_hook', 'cpc_friends_count', $values);                    

                                echo '</table>';
                            echo '</div>';                                   

                            // [cpc-alerts-friends]
                            $values = get_option('cpc_shortcode_options_'.'cpc_alerts_friends') ? get_option('cpc_shortcode_options_'.'cpc_alerts_friends') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_alerts_friends_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt ein Symbol für ausstehende Freundschaftsanfragen an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-alerts-friends] zu einer ClassicPress-Seite oder Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-alerts-friends');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Symbolgröße", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_size = cpc_get_shortcode_default($values, 'cpc_alerts_friends-flag_size', 24);
                                        echo '<input type="text" name="cpc_alerts_friends-flag_size" value="'.$flag_size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(flag_size="'.$flag_size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Größe des Symbols in Pixel.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Größe des ausstehenden Nummer Symbols", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_pending_size = cpc_get_shortcode_default($values, 'cpc_alerts_friends-flag_pending_size', 10);
                                        echo '<input type="text" name="cpc_alerts_friends-flag_pending_size" value="'.$flag_pending_size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(flag_pending_size="'.$flag_pending_size.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Größe der Anzahl ausstehender Anfragen", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Symbol anhängige Nummer oberer Rand (Margin)", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_pending_top = cpc_get_shortcode_default($values, 'cpc_alerts_friends-flag_pending_top', 6);
                                        echo '<input type="text" name="cpc_alerts_friends-flag_pending_top" value="'.$flag_pending_top.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(flag_pending_top="'.$flag_pending_top.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Ermöglicht die Zahl vertikal zu verschieben.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Symbol mit ausstehender Nummer am linken Rand (Margin)", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_pending_left = cpc_get_shortcode_default($values, 'cpc_alerts_friends-flag_pending_left', 8);
                                        echo '<input type="text" name="cpc_alerts_friends-flag_pending_left" value="'.$flag_pending_left.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(flag_pending_left="'.$flag_pending_left.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Ermöglicht die Zahl horizontal zu verschieben.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Symbol für ausstehenden Nummern-Radius", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_pending_radius = cpc_get_shortcode_default($values, 'cpc_alerts_friends-flag_pending_radius', 8);
                                        echo '<input type="text" name="cpc_alerts_friends-flag_pending_radius" value="'.$flag_pending_radius.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(flag_pending_radius="'.$flag_pending_radius.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Radius der Ecken für das Kästchen hinter der Zahl, für ein Quadrat auf 0 gesetzt.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Symbol-URL", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_url = cpc_get_shortcode_default($values, 'cpc_alerts_friends-flag_url', '');
                                        echo '<input type="text" name="cpc_alerts_friends-flag_url" value="'.$flag_url.'" /></td><td>(flag_url="'.$flag_url.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Wohin der Benutzer weitergeleitet wird (URL), wenn auf das Symbol geklickt wird, fast immer dort, wo der [cpc-friends]-Shortcode platziert ist.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                                    echo '<tr><td>'.__("Alternativ-URL für das Symbolbild", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $flag_src = cpc_get_shortcode_default($values, 'cpc_alerts_friends-flag_src', '');
                                        echo '<input type="text" name="cpc_alerts_friends-flag_src" value="'.$flag_src.'" /></td><td>(flag_src="'.$flag_src.'")</td></tr>';    
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("URL eines Bildes, das anstelle des Standardbilds für das Symbol verwendet werden soll.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_alerts_friends', $values);                    

                                echo '</table>';
                            echo '</div>';  
                        
                            // [cpc-favourite-friend]
                            $values = get_option('cpc_shortcode_options_'.'cpc_favourite_friend') ? get_option('cpc_shortcode_options_'.'cpc_favourite_friend') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_favourite_friend_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt Schaltfläche/Link zum Hinzufügen/Entfernen eines Benutzers als Favorit an. Außerdem Optionen für die Seite Freunde (die oben die Lieblingsprofile anzeigt).", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Add [cpc-favourite-friend] to the ClassicPress Page being used as the profile page.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-favourite-friend');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Stil', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $style = cpc_get_shortcode_default($values, 'cpc_favourite_friend-style', 'button');
                                        echo '<select name="cpc_favourite_friend-style">';
                                            echo '<option value="button"'.($style == 'button' ? ' SELECTED' : '').'>'.__('Button', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="link"'.($style == 'link' ? ' SELECTED' : '').'>'.__('Link', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(style="'.$style.'")</td></tr>';
                        
                                    echo '<tr><td>'.__("Hinzufügen Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $favourite_no = cpc_get_shortcode_default($values, 'cpc_favourite_friend-favourite_no', __('Als Favorit hinzufügen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_favourite_friend-favourite_no" value="'.$favourite_no.'" /></td><td>(favourite_no="'.$favourite_no.'")</td></tr>';
                                    echo '<tr><td>'.__("Entfernen Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $favourite_yes = cpc_get_shortcode_default($values, 'cpc_favourite_friend-favourite_yes', __('Als Favorit entfernen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_favourite_friend-favourite_yes" value="'.$favourite_yes.'" /></td><td>(favourite_yes="'.$favourite_yes.'")</td></tr>';

                                    echo '<tr><td>'.__("Hinzugefügt-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $favourite_no_msg = cpc_get_shortcode_default($values, 'cpc_favourite_friend-favourite_no_msg', __('Als Favorit hinzugefügt.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_favourite_friend-favourite_no_msg" value="'.$favourite_no_msg.'" /></td><td>(favourite_no_msg="'.$favourite_no_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Entfernt-Nachricht", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $favourite_yes_msg = cpc_get_shortcode_default($values, 'cpc_favourite_friend-favourite_yes_msg', __('Als Favorit entfernt.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_favourite_friend-favourite_yes_msg" value="'.$favourite_yes_msg.'" /></td><td>(favourite_yes_msg="'.$favourite_yes_msg.'")</td></tr>';

                                    echo '<tr><td>'.__("Rollover-Text für die Seite Freunde.", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $friends_tooltip = cpc_get_shortcode_default($values, 'cpc_favourite_friend-friends_tooltip', __('Als Favorit hinzufügen/entfernen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_favourite_friend-friends_tooltip" value="'.$friends_tooltip.'" /></td><td>(friends_tooltip="'.$friends_tooltip.'")</td></tr>';
                        
                                    do_action('cpc_show_styling_options_hook', 'cpc_favourite_friend', $values);

                                echo '</table>';
                            echo '</div>';                                   

                        

                            /* ----------------------- PROFILE TAB ----------------------- */    

                            // [cpc-usermeta]
                            $values = get_option('cpc_shortcode_options_'.'cpc_usermeta') ? get_option('cpc_shortcode_options_'.'cpc_usermeta') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_usermeta_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt einen Profilwert (Meta) eines Benutzers an, einschließlich Standard-Metawerten wie display_name.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-usermeta] zu einer ClassicPress-Seite und einem Post-of-Text-Widget hinzu. Kann mehrmals hinzugefügt werden.', CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Tip:', CPC2_TEXT_DOMAIN).'</strong> '.__('Wähle unten die Optionen aus (und speichere sie), um zu sehen, wie Du [cpc-usermeta meta="<em>value</em>"] hinzufügen kannst, um Deine Profilseite aufzubauen.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-usermeta');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $label = cpc_get_shortcode_default($values, 'cpc_usermeta-label', '');
                                        echo '<input type="text" name="cpc_usermeta-label" value="'.$label.'" /></td><td>(label="'.$label.'")</td></tr>';
                                    echo '<tr><td>'.__('Metawert', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $meta = cpc_get_shortcode_default($values, 'cpc_usermeta-meta', 'cpccom_home');
                                        echo '<select name="cpc_usermeta-meta">';
                                            echo '<option value="description"'.($meta == 'description' ? ' SELECTED' : '').'>'.__('Beschreibung', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="cpccom_last_active"'.($meta == 'cpccom_last_active' ? ' SELECTED' : '').'>'.__('Letzte Aktivität', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="display_name"'.($meta == 'display_name' ? ' SELECTED' : '').'>'.__('Anzeigename', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="first_name"'.($meta == 'first_name' ? ' SELECTED' : '').'>'.__('Vorname', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="last_name"'.($meta == 'last_name' ? ' SELECTED' : '').'>'.__('Nachname', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="user_login"'.($meta == 'user_login' ? ' SELECTED' : '').'>'.__('Benutzername', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="user_email"'.($meta == 'user_email' ? ' SELECTED' : '').'>'.__('Benutzer Email', CPC2_TEXT_DOMAIN).'</option>';    
                                            echo '<option value="user_nicename"'.($meta == 'user_nicename' ? ' SELECTED' : '').'>'.__('Schöner Benutzername', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="user_registered"'.($meta == 'user_registered' ? ' SELECTED' : '').'>'.__('Datum der Benutzerregistrierung', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="user_url"'.($meta == 'user_url' ? ' SELECTED' : '').'>'.__('Benutzer-URL', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="user_status"'.($meta == 'user_status' ? ' SELECTED' : '').'>'.__('Benutzerstatus', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="cpccom_home"'.($meta == 'cpccom_home' ? ' SELECTED' : '').'>'.__('Stadt/Gemeinde', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="cpccom_country"'.($meta == 'cpccom_country' ? ' SELECTED' : '').'>'.__('Land', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(meta="'.$meta.'")</td></tr>';

                                    echo '<tr><td colspan=3 class="cpc_section">'.__('Wenn oben Karte ausgewählt wurde...', CPC2_TEXT_DOMAIN).'</td></tr>';            
                                    echo '<tr><td>'.__("Größe", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $size = cpc_get_shortcode_default($values, 'cpc_usermeta-label', '250,250');
                                        echo '<input type="text" name="cpc_usermeta-size" value="'.$size.'" /> ',__('Pixel', CPC2_TEXT_DOMAIN).'</td><td>(size="'.$size.'")</td></tr>';
                                    echo '<tr><td>'.__('Kartenstil', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $map_style = cpc_get_shortcode_default($values, 'cpc_usermeta-map_style', 'dynamic');
                                        echo '<select name="cpc_usermeta-map_style">';
                                            echo '<option value="dynamic"'.($layout == 'dynamic' ? ' SELECTED' : '').'>'.__('Dynamisch', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="static"'.($layout == 'static' ? ' SELECTED' : '').'>'.__('Statisch', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(map_style="'.$map_style.'")</td></tr>';
                                    echo '<tr><td>'.__('Zoom Level', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $zoom = cpc_get_shortcode_default($values, 'cpc_usermeta-zoom', 5);
                                        echo '<select name="cpc_usermeta-zoom">';
                                            echo '<option value="1"'.($zoom == '1' ? ' SELECTED' : '').'>1</option>';
                                            echo '<option value="2"'.($zoom == '2' ? ' SELECTED' : '').'>2</option>';
                                            echo '<option value="3"'.($zoom == '3' ? ' SELECTED' : '').'>3</option>';
                                            echo '<option value="4"'.($zoom == '4' ? ' SELECTED' : '').'>4</option>';
                                            echo '<option value="5"'.($zoom == '5' ? ' SELECTED' : '').'>5</option>';
                                            echo '<option value="6"'.($zoom == '6' ? ' SELECTED' : '').'>6</option>';
                                            echo '<option value="7"'.($zoom == '7' ? ' SELECTED' : '').'>7</option>';
                                            echo '<option value="8"'.($zoom == '8' ? ' SELECTED' : '').'>8</option>';
                                            echo '<option value="9"'.($zoom == '9' ? ' SELECTED' : '').'>9</option>';
                                            echo '<option value="10"'.($zoom == '10' ? ' SELECTED' : '').'>10</option>';
                                            echo '<option value="11"'.($zoom == '11' ? ' SELECTED' : '').'>11</option>';
                                            echo '<option value="12"'.($zoom == '12' ? ' SELECTED' : '').'>12</option>';
                                            echo '<option value="13"'.($zoom == '13' ? ' SELECTED' : '').'>13</option>';
                                            echo '<option value="14"'.($zoom == '14' ? ' SELECTED' : '').'>14</option>';
                                            echo '<option value="15"'.($zoom == '15' ? ' SELECTED' : '').'>15</option>';
                                        echo '</select></td><td>(zoom="'.$zoom.'")</td></tr>';

                                    echo '<tr><td colspan=3 class="cpc_section">'.__('Wenn oben die E-Mail-Adresse des Benutzers ausgewählt wurde ...', CPC2_TEXT_DOMAIN).'</td></tr>';                
                                    echo '<tr><td>'.__('E-Mail als Hyperlink', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $link = cpc_get_shortcode_default($values, 'cpc_usermeta-link', true);
                                        echo '<input type="checkbox" name="cpc_usermeta-link"'.($link ? ' CHECKED' : '').'></td><td>(link="'.($link ? '1' : '0').'")</td></tr>';

                                    // any more options for this shortcode
                                    do_action('cpc_shortcode_options_hook', 'cpc_usermeta', $values);                    

                                    do_action('cpc_show_styling_options_hook', 'cpc_usermeta', $values);                    

                                echo '</table>';
                            echo '</div>';   

                            // [cpc-usermeta-button]
                            $values = get_option('cpc_shortcode_options_'.'cpc_usermeta_button') ? get_option('cpc_shortcode_options_'.'cpc_usermeta_button') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_usermeta_button_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt eine Schaltfläche zum Verknüpfen mit einer URL an und übergibt die Benutzer-ID als Parameter.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-usermeta-button] zu einer ClassicPress-Seite hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-usermeta-button');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("URL für die Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $url = cpc_get_shortcode_default($values, 'cpc_usermeta_button-url', '');
                                        echo '<input type="text" name="cpc_usermeta_button-url" value="'.$url.'" /></td><td>(url="'.$url.'")</td></tr>';
                                    echo '<tr><td>'.__("Beschriftung für Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $value = cpc_get_shortcode_default($values, 'cpc_usermeta_button-value', __('Weiter', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_button-value" value="'.$value.'" /></td><td>(value="'.$value.'")</td></tr>';
                                    echo '<tr><td>'.__("Optionale CSS-Klasse für die Schaltfläche", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $class = cpc_get_shortcode_default($values, 'cpc_usermeta_button-class', '');
                                        echo '<input type="text" name="cpc_usermeta_button-class" value="'.$class.'" /></td><td>(class="'.$class.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_usermeta_button', $values);                    

                                echo '</table>';
                            echo '</div>';         

                            // [cpc-usermeta-change]
                            $values = get_option('cpc_shortcode_options_'.'cpc_usermeta_change') ? get_option('cpc_shortcode_options_'.'cpc_usermeta_change') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_usermeta_change_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt Profilfelder für den Benutzer an, die er bearbeiten kann. Dies ist die Seite Profil bearbeiten.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-usermeta-change] zu einer ClassicPress-Seite hinzu.', CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Tip:', CPC2_TEXT_DOMAIN).'</strong> '.__('Verwende die unten stehende Einrichtung Profil bearbeiten, um Tabs zu Deiner Profilbearbeitungsseite hinzuzufügen.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-usermeta-change');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__("Beschriftung für die Schaltfläche Aktualisieren.", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $label = cpc_get_shortcode_default($values, 'cpc_usermeta_change-label', __('Aktualisieren', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-label" value="'.$label.'" /></td><td>(label="'.$label.'")</td></tr>';
                                    echo '<tr><td>'.__('Zeige Stadt/Gemeinde', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_town = cpc_get_shortcode_default($values, 'cpc_usermeta_change-show_town', true);
                                        echo '<input type="checkbox" name="cpc_usermeta_change-show_town"'.($show_town ? ' CHECKED' : '').'></td><td>(show_town="'.($show_town ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Stadt/Gemeinde Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $town = cpc_get_shortcode_default($values, 'cpc_usermeta_change-town', __('Stadt/Gemeinde', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-town" value="'.$town.'" /></td><td>(town="'.$town.'")</td></tr>';
                                    echo '<tr><td>'.__("Stadt/Gemeinde Standardwert", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $town_default = cpc_get_shortcode_default($values, 'cpc_usermeta_change-town_default', '');
                                        echo '<input type="text" name="cpc_usermeta_change-town_default" value="'.$town_default.'" /></td><td>(town="'.$town.'")</td></tr>';
                                    echo '<tr><td>'.__('Stadt/Gemeinde obligatorisch', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $town_mandatory = cpc_get_shortcode_default($values, 'cpc_usermeta_change-town_mandatory', false);
                                        echo '<input type="checkbox" name="cpc_usermeta_change-town_mandatory"'.($town_mandatory ? ' CHECKED' : '').'></td><td>(town_mandatory="'.($town_mandatory ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__('Land anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_country = cpc_get_shortcode_default($values, 'cpc_usermeta_change-show_country', true);
                                        echo '<input type="checkbox" name="cpc_usermeta_change-show_country"'.($show_country ? ' CHECKED' : '').'></td><td>(show_country="'.($show_country ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Land Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $country = cpc_get_shortcode_default($values, 'cpc_usermeta_change-country', __('Land', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-country" value="'.$country.'" /></td><td>(country="'.$country.'")</td></tr>';
                                    echo '<tr><td>'.__("Land-Standardwert", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $country_default = cpc_get_shortcode_default($values, 'cpc_usermeta_change-country_default', '');
                                        echo '<input type="text" name="cpc_usermeta_change-country_default" value="'.$country_default.'" /></td><td>(town="'.$town.'")</td></tr>';
                                    echo '<tr><td>'.__('Land obligatorisch', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $country_mandatory = cpc_get_shortcode_default($values, 'cpc_usermeta_change-country_mandatory', false);
                                        echo '<input type="checkbox" name="cpc_usermeta_change-country_mandatory"'.($country_mandatory ? ' CHECKED' : '').'></td><td>(country_mandatory="'.($country_mandatory ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Anzeigename Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $displayname = cpc_get_shortcode_default($values, 'cpc_usermeta_change-displayname', __('Anzeigename', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-displayname" value="'.$displayname.'" /></td><td>(displayname="'.$displayname.'")</td></tr>';
                                    echo '<tr><td>'.__('Vor-/Nachnamen anzeigen', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $show_name = cpc_get_shortcode_default($values, 'cpc_usermeta_change-show_name', true);
                                        echo '<input type="checkbox" name="cpc_usermeta_change-show_name"'.($show_name ? ' CHECKED' : '').'></td><td>(show_name="'.($show_name ? '1' : '0').'")</td></tr>';
                                    echo '<tr><td>'.__("Vor-/Nachnamen Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $name = cpc_get_shortcode_default($values, 'cpc_usermeta_change-name', __('Dein Vorname und Nachname', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-name" value="'.$name.'" /></td><td>(name="'.$name.'")</td></tr>';
                                    echo '<tr><td>'.__("Passwort Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $password = cpc_get_shortcode_default($values, 'cpc_usermeta_change-password', __('Ändere Dein Passwort', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-password" value="'.$password.'" /></td><td>(password="'.$password.'")</td></tr>';
                                    echo '<tr><td>'.__("Passwort erneut eingeben Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $password2 = cpc_get_shortcode_default($values, 'cpc_usermeta_change-password2', __('Passwort erneut eingeben', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-password2" value="'.$password2.'" /></td><td>(password2="'.$password2.'")</td></tr>';
                                    echo '<tr><td>'.__("Meldung Passwort ändern, erneut anmelden.", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $password_msg = cpc_get_shortcode_default($values, 'cpc_usermeta_change-password_msg', __('Passwort geändert, bitte melde Dich erneut an.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-password_msg" value="'.$password_msg.'" /></td><td>(password_msg="'.$password_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("E-Mail-Beschriftung", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $email = cpc_get_shortcode_default($values, 'cpc_usermeta_change-email', __('E-Mail-Adresse', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-email" value="'.$email.'" /></td><td>(email="'.$email.'")</td></tr>';
                                    echo '<tr><td>'.__("Meldung Abgemeldet.", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $logged_out_msg = cpc_get_shortcode_default($values, 'cpc_usermeta_change-logged_out_msg', __('Du musst angemeldet sein, um diese Seite anzuzeigen.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-logged_out_msg" value="'.$logged_out_msg.'" /></td><td>(logged_out_msg="'.$logged_out_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Obligatorisches Suffix", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $mandatory = cpc_get_shortcode_default($values, 'cpc_usermeta_change-mandatory', '&lt;span style=\'color:red;\'&gt; *&lt;/span&gt;');
                                        echo '<input type="text" name="cpc_usermeta_change-mandatory" value="'.$mandatory.'" /></td><td>(mandatory="'.$mandatory.'")</td></tr>';
                                    echo '<tr><td>'.__("Benachrichtigung über erforderliche Felder", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $required_msg = cpc_get_shortcode_default($values, 'cpc_usermeta_change-required_msg', __('Bitte überprüfe die Pflichtfelder', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-required_msg" value="'.$required_msg.'" /></td><td>(required_msg="'.$required_msg.'")</td></tr>';
                                    echo '<tr><td>'.__("Eingabeaufforderung für Aktivitätsbenachrichtigungen", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $activity_subs_subscribe = cpc_get_shortcode_default($values, 'cpc_usermeta_change-activity_subs_subscribe', __('Erhalte E-Mail-Benachrichtigungen für Aktivitäten', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change-activity_subs_subscribe" value="'.$activity_subs_subscribe.'" /></td><td>(activity_subs_subscribe="'.$activity_subs_subscribe.'")</td></tr>';
                                    echo '<tr><td>'.__("Optionale URL zum Anmelden", CPC2_TEXT_DOMAIN).'</td><td>';
                                        $login_url = cpc_get_shortcode_default($values, 'cpc_usermeta_change-login_url', '');
                                        echo '<input type="text" name="cpc_usermeta_change-login_url" value="'.$login_url.'" /></td><td>(login_url="'.$login_url.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_usermeta_change', $values);                    

                                echo '</table>';
                            echo '</div>';       

                            // [cpc-usermeta-change-link]    
                            $values = get_option('cpc_shortcode_options_'.'cpc_usermeta_change_link') ? get_option('cpc_shortcode_options_'.'cpc_usermeta_change_link') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_usermeta_change_link_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt einen Link zur Seite Profil bearbeiten an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-usermeta-change-link] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-usermeta-change-link');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Text für den Link', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $text = cpc_get_shortcode_default($values, 'cpc_usermeta_change_link-text', __('Profil bearbeiten', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_usermeta_change_link-text" value="'.$text.'" /></td><td>(text="'.$text.'")</td></tr>';
                                    do_action('cpc_show_styling_options_hook', 'cpc_usermeta_change_link', $values);                    
                                echo '</table>';
                            echo '</div>'; 

                            // [cpc-close-account]
                            $values = get_option('cpc_shortcode_options_'.'cpc_close_account') ? get_option('cpc_shortcode_options_'.'cpc_close_account') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_close_account_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt eine Schaltfläche an, mit der Benutzer ihr Konto schließen können.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-close-account] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-close-account');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Optionale CSS-Klasse für die Schaltfläche', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $class = cpc_get_shortcode_default($values, 'cpc_close_account-class', '');
                                        echo '<input type="text" name="cpc_close_account-class" value="'.$class.'" /></td><td>(class="'.$class.'")</td></tr>';
                                    echo '<tr><td>'.__('Beschriftung für Schaltfläche', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $label = cpc_get_shortcode_default($values, 'cpc_close_account-label', __('Konto schließen', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_close_account-label" value="'.$label.'" /></td><td>(label="'.$label.'")</td></tr>';
                                    echo '<tr><td>'.__('Bist du sicher-Text', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $are_you_sure_text = cpc_get_shortcode_default($values, 'cpc_close_account-are_you_sure_text', __('Bist du sicher? Du kannst ein geschlossenes Konto nicht erneut eröffnen.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_close_account-are_you_sure_text" value="'.$are_you_sure_text.'" /></td><td>(are_you_sure_text="'.$are_you_sure_text.'")</td></tr>';
                                    echo '<tr><td>'.__('Konto wurde geschlossen-Text', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $logout_text = cpc_get_shortcode_default($values, 'cpc_close_account-logout_text', __('Dein Konto wurde geschlossen.', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_close_account-logout_text" value="'.$logout_text.'" /></td><td>(logout_text="'.$logout_text.'")</td></tr>';
                                    echo '<tr><td>'.__('URL nach Kontoschließung', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $url = cpc_get_shortcode_default($values, 'cpc_close_account-url', '/');
                                        echo '<input type="text" name="cpc_close_account-url" value="'.$url.'" /></td><td>(url="'.$url.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_close_account', $values);                    

                                echo '</table>';
                            echo '</div>';             

                            // [cpc-join-site] 
                            $values = get_option('cpc_shortcode_options_'.'cpc_join_site') ? get_option('cpc_shortcode_options_'.'cpc_join_site') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_join_site_tab');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Zeigt einen Link oder eine Schaltfläche zum Beitritt zu einer Webseite an (nur Multisite).", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-join-site] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-join-site');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Optionale CSS-Klasse für die Schaltfläche', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $class = cpc_get_shortcode_default($values, 'cpc_join_site-class', '');
                                        echo '<input type="text" name="cpc_join_site-class" value="'.$class.'" /></td><td>(class="'.$class.'")</td></tr>';
                                    echo '<tr><td>'.__('Beschriftung für Link/Schaltfläche', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $label = cpc_get_shortcode_default($values, 'cpc_join_site-label', __('Tritt dieser Webseite bei', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_join_site-label" value="'.$label.'" /></td><td>(label="'.$label.'")</td></tr>';
                                    echo '<tr><td>'.__('Stil', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $style = cpc_get_shortcode_default($values, 'cpc_join_site-style', 'button');
                                        echo '<select name="cpc_join_site-style">';
                                            echo '<option value="button"'.($style == 'button' ? ' SELECTED' : '').'>'.__('Schaltfläche', CPC2_TEXT_DOMAIN).'</option>';
                                            echo '<option value="text"'.($style == 'text' ? ' SELECTED' : '').'>'.__('Text', CPC2_TEXT_DOMAIN).'</option>';
                                        echo '</select></td><td>(style="'.$style.'")</td></tr>';

                                    do_action('cpc_show_styling_options_hook', 'cpc_join_site', $values);                    

                                echo '</table>';
                            echo '</div>';    

                            // [cpc-no-user-check]
                            $values = get_option('cpc_shortcode_options_'.'cpc_no_user_check') ? get_option('cpc_shortcode_options_'.'cpc_no_user_check') : array();   
                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_no_user_check');
                                echo '<strong>'.__('Zweck:', CPC2_TEXT_DOMAIN).'</strong> '.__("Sucht nach einem gültigen Benutzer und zeigt eine Optionsmeldung an.", CPC2_TEXT_DOMAIN).'<br />';
                                echo '<strong>'.__('Wie benutzen:', CPC2_TEXT_DOMAIN).'</strong> '.__('Füge [cpc-no-user-check] zu einer ClassicPress-Seite, einem Beitrag oder einem Text-Widget hinzu.', CPC2_TEXT_DOMAIN);
                                echo cpc_codex_link('https://cp-community.n3rds.work/cpc_codex/cpc-no-user-check');
                                echo '<p><strong>'.__('Optionen', CPC2_TEXT_DOMAIN).'</strong><br />';
                                echo '<table cellpadding="0" cellspacing="0"  class="cpc_shortcode_value_row">';
                                    echo '<tr><td>'.__('Meldung Benutzer nicht gefunden.', CPC2_TEXT_DOMAIN).'</td><td>';
                                        $not_found_msg = cpc_get_shortcode_default($values, 'cpc_no_user_check-not_found_msg', __('Benutzer existiert nicht!', CPC2_TEXT_DOMAIN));
                                        echo '<input type="text" name="cpc_no_user_check-not_found_msg" value="'.$not_found_msg.'" /></td><td>(not_found_msg="'.$not_found_msg.'")</td></tr>';
                                    echo '<tr class="cpc_desc"><td colspan="3">';
                                        echo __("Meldung, die angezeigt wird, wenn der Benutzer nicht gefunden wird.", CPC2_TEXT_DOMAIN);
                                        echo '</td></tr>';
                        
                                    do_action('cpc_show_styling_options_hook', 'cpc_no_user_check', $values);                    

                                echo '</table>';
                            echo '</div>'; 


                            /* OTHERS */

                            do_action('cpc_options_shortcode_options_hook', $cpc_expand_shortcode);        


                        echo '</div>';

                        
                    }
            
                echo '</div>';

            echo '</div>';

        echo '</div>';

}


function cpc_show_tab($cpc_expand_tab, $tab, $option, $text) {
    return '<div class="'.($cpc_expand_tab == $option ? 'cpc_admin_getting_started_active' : '').' cpc_admin_getting_started_option" id="'.$option.'" data-shortcode="'.$option.'">'.$text.'</div>';
}

function cpc_show_shortcode($cpc_expand_tab, $cpc_expand_shortcode, $tab, $function, $shortcode) {
    return '<div rel="'.$function.'" class="'.($cpc_expand_shortcode == $function ? 'cpc_admin_getting_started_active' : '').' cpc_'.$tab.' cpc_admin_getting_started_option_shortcode" data-tab="'.$function.'" style="display:'.($cpc_expand_tab == $tab ? 'block' : 'none').'">['.$shortcode.']</div>';
}

function cpc_show_options($cpc_expand_shortcode, $function) {
    return '<div id="'.$function.'" class="cpc_admin_getting_started_option_value" style="display:'.($cpc_expand_shortcode == $function ? 'block' : 'none').'">';
}

function cpc_get_shortcode_default($values, $name, $default) {

    // Remove function if passed in format function-option
    if (strpos($name, '-')):
        $arr = explode('-',$name);
        $name = $arr[1];
    endif;

    // Now calculate value stored
    if ($default === false || $default === true) {
        $v = isset($values[$name]) && ($values[$name] == 'on' || $values[$name] == 'off' ) ? $values[$name] : false;
        if ($v) {
            $v = $v == 'on' ? true : false; 
        } else {
            $v = $default;
        }
    } else {
        $v = isset($values[$name]) && ($values[$name]) ? $values[$name] : $default;
    }
    return $v;
}

function cpc_save_option($values, $the_post, $name, $checkbox=false) {
    if (!$checkbox) {
        $v = isset($the_post[$name]) ? $the_post[$name] : false;
    } else {
        $v = isset($the_post[$name]) ? 'on' : 'off';        
    }
    $values[$name] = $v ? htmlentities (htmlspecialcharacters_decode(stripslashes($v), ENT_QUOTES)) : '';
    return $values;
}

// Show styling options in setup
if (is_admin()) add_action('cpc_show_styling_options_hook', 'cpc_show_styling_options', 10, 2);		
function cpc_show_styling_options($function, $values) {

    echo '<tr><td colspan=3 class="cpc_section">'.__('Stil (nicht über Shortcode-Optionen verfügbar)...', CPC2_TEXT_DOMAIN);    
    
    if (isset($_GET['global_styles'])):
        if ($_GET['global_styles'] == 'on'):
            update_option('cpccom_global_styles', 'on');
        else:
            delete_option('cpccom_global_styles');
        endif;
    endif;

    $global_styles = ($var = get_option('cpccom_global_styles')) ? $var : 'off';
    if ($global_styles == 'on'):
        echo '<br />'.__('Füge style="0" zu einem Shortcode hinzu, um die Verwendung der folgenden Stile zu vermeiden.', CPC2_TEXT_DOMAIN);
        echo '<br /><a href="'.admin_url( 'admin.php?page=cpc_com_shortcodes' ).'&global_styles=off">'.__('Stile global ausschalten (dies betrifft die gesamte Ausgabe des Shortcodes)', CPC2_TEXT_DOMAIN).'</a> ('.__('Leistungssteigerung und einfachere manuelle Anwendung von CSS', CPC2_TEXT_DOMAIN).')</td></tr>';    
    else:
        echo '<br /><a href="'.admin_url( 'admin.php?page=cpc_com_shortcodes' ).'&global_styles=on">'.__('Schalte Stile global ein</a> (dies betrifft die gesamte Ausgabe der Shortcodes, nicht dasselbe wie <a href="'.admin_url( 'admin.php?page=cpc_com_styles' ).'">Stile</a> wodurch Du Teile des Shortcodes formatieren kannst)', CPC2_TEXT_DOMAIN).'.</td></tr>';    
    endif;

    if ($global_styles != 'off'):

        echo '<tr><td>'.__('Oberer Rand (Margin)', CPC2_TEXT_DOMAIN).'</td><td>';
            $margin_top = cpc_get_shortcode_default($values, $function.'-margin_top', 0);
            echo '<input type="text" name="'.$function.'-margin_top" value="'.$margin_top.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td></td></tr>';
        echo '<tr><td>'.__('Rechter Rand (Margin)', CPC2_TEXT_DOMAIN).'</td><td>';
            $margin_right = cpc_get_shortcode_default($values, $function.'-margin_right', 0);
            echo '<input type="text" name="'.$function.'-margin_right" value="'.$margin_right.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td></td></tr>';
        echo '<tr><td>'.__('Unterer Rand (Margin)', CPC2_TEXT_DOMAIN).'</td><td>';
            $margin_bottom = cpc_get_shortcode_default($values, $function.'-margin_bottom', 0);
            echo '<input type="text" name="'.$function.'-margin_bottom" value="'.$margin_bottom.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td></td></tr>';
        echo '<tr><td>'.__('Linker Rand (Margin)', CPC2_TEXT_DOMAIN).'</td><td>';
            $margin_left = cpc_get_shortcode_default($values, $function.'-margin_left', 0);
            echo '<input type="text" name="'.$function.'-margin_left" value="'.$margin_left.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td></td></tr>';
        echo '<tr><td>'.__('Obere Polsterung (Padding)', CPC2_TEXT_DOMAIN).'</td><td>';
            $padding_top = cpc_get_shortcode_default($values, $function.'-padding_top', 0);
            echo '<input type="text" name="'.$function.'-padding_top" value="'.$padding_top.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td></td></tr>';
        echo '<tr><td>'.__('Richtige Polsterung (Padding)', CPC2_TEXT_DOMAIN).'</td><td>';
            $padding_right = cpc_get_shortcode_default($values, $function.'-padding_right', 0);
            echo '<input type="text" name="'.$function.'-padding_right" value="'.$padding_right.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td></td></tr>';
        echo '<tr><td>'.__('Untere Polsterung (Padding)', CPC2_TEXT_DOMAIN).'</td><td>';
            $padding_bottom = cpc_get_shortcode_default($values, $function.'-padding_bottom', 0);
            echo '<input type="text" name="'.$function.'-padding_bottom" value="'.$padding_bottom.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td></td></tr>';
        echo '<tr><td>'.__('Linke Polsterung (Padding)', CPC2_TEXT_DOMAIN).'</td><td>';
            $padding_left = cpc_get_shortcode_default($values, $function.'-padding_left', 0);
            echo '<input type="text" name="'.$function.'-padding_left" value="'.$padding_left.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td></td></tr>';
        echo '<tr><td>'.__("Leere vorhergehenden Float", CPC2_TEXT_DOMAIN).'</td><td>';
            $clear = cpc_get_shortcode_default($values, $function.'-clear', true);
            echo '<input type="checkbox" name="'.$function.'-clear"'.($clear ? ' CHECKED' : '').'> <em>clear: '.($clear ? 'both' : 'none').'</em></td><td></td></tr>';
        echo '<tr><td>'.__('Hintergrundfarbe', CPC2_TEXT_DOMAIN).'</td><td>';
            $background_color = cpc_get_shortcode_default($values, $function.'-background_color', 'transparent');
            echo '<input type="text" name="'.$function.'-background_color" class="cpc-color-picker" data-default-color="transparent" value="'.$background_color.'" /></td><td></td></tr>';
        echo '<tr><td>'.__('Rahmengrösse', CPC2_TEXT_DOMAIN).'</td><td>';
            $border_size = cpc_get_shortcode_default($values, $function.'-border_size', 0);
            echo '<input type="text" name="'.$function.'-border_size" value="'.$border_size.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td></td></tr>';
        echo '<tr><td>'.__('Rahmenfarbe', CPC2_TEXT_DOMAIN).'</td><td>';
            $border_color = cpc_get_shortcode_default($values, $function.'-border_color', '#000');
            echo '<input type="text" name="'.$function.'-border_color" class="cpc-color-picker" data-default-color="#000" value="'.$border_color.'" /></td><td></td></tr>';
        echo '<tr><td>'.__('Rahmenradius', CPC2_TEXT_DOMAIN).'</td><td>';
            $border_radius = cpc_get_shortcode_default($values, $function.'-border_radius', 0);
            echo '<input type="text" name="'.$function.'-border_radius" value="'.$border_radius.'" /> '.__('Pixel', CPC2_TEXT_DOMAIN).'</td><td></td></tr>';
        echo '<tr><td>'.__('Rahmenstil', CPC2_TEXT_DOMAIN).'</td><td>';
            $border_style = cpc_get_shortcode_default($values, $function.'-border_style', 'solid');
            echo '<select name="'.$function.'-border_style">';
                echo '<option value="solid"'.($border_style == 'solid' ? ' SELECTED' : '').'>'.__('Solide', CPC2_TEXT_DOMAIN).'</option>';
                echo '<option value="dotted"'.($border_style == 'dotted' ? ' SELECTED' : '').'>'.__('Gepunktet', CPC2_TEXT_DOMAIN).'</option>';
                echo '<option value="dashed"'.($border_style == 'dashed' ? ' SELECTED' : '').'>'.__('Gestrichelt', CPC2_TEXT_DOMAIN).'</option>';
            echo '</select></td><td></td></tr>';
        echo '<tr><td>'.__('Breite', CPC2_TEXT_DOMAIN).'</td><td>';
            $style_width = cpc_get_shortcode_default($values, $function.'-style_width', '100%');
            echo '<input type="text" name="'.$function.'-style_width" value="'.$style_width.'" /> ('.__('Pixel oder %', CPC2_TEXT_DOMAIN).')</td><td></td></tr>';
        echo '<tr><td>'.__('Höhe', CPC2_TEXT_DOMAIN).'</td><td>';
            $style_height = cpc_get_shortcode_default($values, $function.'-style_height', '');
            echo '<input type="text" name="'.$function.'-style_height" value="'.$style_height.'" /> ('.__('Pixel', CPC2_TEXT_DOMAIN).')</td><td></td></tr>';
    
    endif;
}
if (is_admin()) add_filter( 'cpc_show_styling_options_save_filter', 'cpc_show_styling_options_save', 10, 3 );
function cpc_show_styling_options_save($function, $the_post, $values) {
    
    $values = cpc_save_option($values, $the_post, $function.'-margin_top');      
    $values = cpc_save_option($values, $the_post, $function.'-margin_bottom');
    $values = cpc_save_option($values, $the_post, $function.'-margin_left');
    $values = cpc_save_option($values, $the_post, $function.'-margin_right');
    $values = cpc_save_option($values, $the_post, $function.'-padding_top');      
    $values = cpc_save_option($values, $the_post, $function.'-padding_bottom');
    $values = cpc_save_option($values, $the_post, $function.'-padding_left');
    $values = cpc_save_option($values, $the_post, $function.'-padding_right');
    $values = cpc_save_option($values, $the_post, $function.'-clear', true);
    $values = cpc_save_option($values, $the_post, $function.'-border_size');
    $values = cpc_save_option($values, $the_post, $function.'-border_color');
    $values = cpc_save_option($values, $the_post, $function.'-border_radius');
    $values = cpc_save_option($values, $the_post, $function.'-border_style');
    $values = cpc_save_option($values, $the_post, $function.'-background_color');
    $values = cpc_save_option($values, $the_post, $function.'-style_width');
    $values = cpc_save_option($values, $the_post, $function.'-style_height');

    return $values;
    
}

// System Options header
add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_core_header', 1);
function cpc_admin_getting_started_core_header() {
    echo '<h2>'.sprintf(__('Optionen (nicht über <a href="%s">Shortcode</a> festgelegt)', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpc_com_shortcodes' )).'</h2>';
}

// Add to Getting Started information
add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_core', 1);
function cpc_admin_getting_started_core() {

    echo '<a name="core"></a>';
    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_core' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';    
  	echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" id="cpc_admin_getting_started_core_div" rel="cpc_admin_getting_started_core">'.__('System-Optionen', CPC2_TEXT_DOMAIN).'</div>';

	$display = (isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_core') || (isset($_GET['cpc_expand']) && $_GET['cpc_expand'] == 'cpc_admin_getting_started_core') ? 'block' : 'none';
  	echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_core" style="display:'.$display.'">';

	?>
	<table class="form-table">

    <tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_core_options_tips"><?php _e('Admin-Tipps', CPC2_TEXT_DOMAIN); ?></label>
		</th>
		<td>
			<input type="checkbox" style="width:10px" name="cpc_core_options_tips" />
            <span class="description"><?php echo __('Schalte alle Admin-Tipps ein.', CPC2_TEXT_DOMAIN); ?></span>
            <?php if (isset($_POST['cpc_core_options_tips'])):
                echo '<div style="margin-top:15px" class="cpc_success">'.__('Admin-Tipps werden aktiviert', CPC2_TEXT_DOMAIN).'</div>';
            endif; ?>
		</td>
	</tr> 
        
    <tr class="form-field">
        <th scope="row" valign="top"><label for="icon_colors"><?php echo __('Symbolfarben', CPC2_TEXT_DOMAIN); ?></label></th>
        <td>
            <select name="icon_colors">
             <?php 
                $icon_colors = get_option('cpccom_icon_colors');
                echo '<option value="dark"';
                    if ($icon_colors != "_light") echo ' SELECTED';
                    echo'>'.__('Dunkel', CPC2_TEXT_DOMAIN).'</option>';
                echo '<option value="light"';
                    if ($icon_colors == "_light") echo ' SELECTED';
                    echo '>'.__('Hell', CPC2_TEXT_DOMAIN).'</option>';
             ?>						
            </select>
            <span class="description"><?php echo __('Zu verwendendes Symbolfarbschema.', CPC2_TEXT_DOMAIN); ?></span>
        </td> 
    </tr> 

    <tr class="form-field">
        <th scope="row" valign="top"><label for="flag_colors"><?php echo __('Flaggenfarben', CPC2_TEXT_DOMAIN); ?></label></th>
        <td>
            <select name="flag_colors">
             <?php 
                $flag_colors = get_option('cpccom_flag_colors');
                echo '<option value="dark"';
                    if ($flag_colors != "_light") echo ' SELECTED';
                    echo'>'.__('Dunkel', CPC2_TEXT_DOMAIN).'</option>';
                echo '<option value="light"';
                    if ($flag_colors == "_light") echo ' SELECTED';
                    echo '>'.__('Hell', CPC2_TEXT_DOMAIN).'</option>';
             ?>						
            </select>
            <span class="description"><?php echo __('Zu verwendendes Farbschema des Flaggensymbols.', CPC2_TEXT_DOMAIN); ?></span>
        </td> 
    </tr> 
        
    <tr class="form-field">
        <th scope="row" valign="top"><label for="cpc_external_links"><?php echo __('Externe Links', CPC2_TEXT_DOMAIN); ?></label></th>
        <td>
            <input name="cpc_external_links" style="width: 100px" value="<?php echo get_option('cpc_external_links'); ?>" />
            <br /><span class="description"><?php echo __('Um externe Links in einem neuen Browser-Tab zu erzwingen, gib ein Suffix ein, das an relevante Links angehängt wird, z.B. &quot;&amp;raquo;&quot; für &raquo;. Gib &quot;&amp;newtab;&quot; zum erzwingen ein, aber danach nichts mehr anzuzeigen.', CPC2_TEXT_DOMAIN); ?></span>
        </td> 
    </tr> 

    <tr class="form-field">
        <th scope="row" valign="top"><label for="cpc_api"><?php echo __('API-Sicherheitscode', CPC2_TEXT_DOMAIN); ?></label></th>
        <td>
            <input name="cpc_api" style="width: 100px" value="<?php echo get_option('cpc_api'); ?>" />
            <span class="description"><?php echo __('Code, der bei Verwendung von API-Funktionen übergeben werden soll.', CPC2_TEXT_DOMAIN); ?></span>
        </td> 
    </tr> 

    <tr class="form-field">
        <th scope="row" valign="top"><label for="cpc_api_functions"><?php echo __('API-zulässige Funktionen', CPC2_TEXT_DOMAIN); ?></label></th>
        <td>
            <input name="cpc_api_functions" style="width: 100px" value="<?php echo get_option('cpc_api_functions'); ?>" />
            <span class="description"><?php echo __('Durch Kommas getrennte Liste der zulässigen API-Funktionen.', CPC2_TEXT_DOMAIN); ?></span>
        </td> 
    </tr> 

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_filter_feed_comments"><?php _e('Feeds', CPC2_TEXT_DOMAIN); ?></label>
		</th>
		<td>
			<input type="checkbox" style="width:10px" name="cpc_filter_feed_comments" <?php if (get_option('cpc_filter_feed_comments')) echo 'CHECKED '; ?>/><span class="description"><?php _e('Füge Kommentare in Feeds ein, z.B. den RSS-Feed (Kommentare vor dem 16.02.03 werden standardmäßig einbezogen).', CPC2_TEXT_DOMAIN); ?><br /><br /> 
            <input type="checkbox" style="width:10px" name="cpc_filter_feed_comments_update" /><?php _e('Aktiviere diese Option, um alte Beiträge zu aktualisieren, damit sie aus Feeds ausgeschlossen werden können. Gilt für Kommentare, die vor dem 16.02.03 abgegeben wurden (muss nur einmal ausgeführt werden, kann einige Zeit dauern)', CPC2_TEXT_DOMAIN); ?></span>
        </td>
	</tr> 
        
	<?php
		do_action( 'cpc_admin_getting_started_core_hook' );
	?>
	
	</table>
	<?php

	echo '</div>';

}

add_action('cpc_admin_setup_form_get_hook', 'cpc_admin_getting_started_core_save', 20, 2);
add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_getting_started_core_save', 20, 2);
function cpc_admin_getting_started_core_save($the_post) {
    
	$current_core = get_option('cpc_default_core');	
    if (strpos($current_core, 'XRELOADX')):
        // extensions changed, will need to do a reload
        $current_core = str_replace('XRELOADX', '', $current_core);
        update_option('cpc_default_core', $current_core);
        // ... carry on
    endif;
	$cpc_default_core = '';
	if (isset($the_post['core-profile'])) 	   $cpc_default_core .= 'core-profile,';
	if (isset($the_post['core-activity'])) 	   $cpc_default_core .= 'core-activity,';
	if (isset($the_post['core-avatar']))       $cpc_default_core .= 'core-avatar,';
	if (isset($the_post['core-friendships']))  $cpc_default_core .= 'core-friendships,';
	if (isset($the_post['core-alerts'])) 	   $cpc_default_core .= 'core-alerts,';
	if (isset($the_post['core-forums'])) 	   $cpc_default_core .= 'core-forums,';
	if (isset($the_post['core-groups'])) 	   $cpc_default_core .= 'core-groups,';
	update_option('cpc_default_core', $cpc_default_core);  

	if (isset($the_post['cpc_core_options_tips'])):
		delete_option('cpc_admin_tips');
	endif;
        
	if (isset($the_post['cpc_external_links'])):
		update_option('cpc_external_links', $the_post['cpc_external_links']);
	else:
		delete_option('cpc_external_links');
	endif;

    if (isset($the_post['cpc_api'])):
        update_option('cpc_api', $the_post['cpc_api']);
    else:
        delete_option('cpc_api');
    endif;

    if (isset($the_post['cpc_api_functions'])):
        update_option('cpc_api_functions', $the_post['cpc_api_functions']);
    else:
        delete_option('cpc_api_functions');
    endif;

	if (isset($the_post['icon_colors']) && $the_post['icon_colors'] == 'light'):
		update_option('cpccom_icon_colors', '_light');
	else:
		delete_option('cpccom_icon_colors');
	endif;

    if (isset($the_post['flag_colors']) && $the_post['flag_colors'] == 'light'):
		update_option('cpccom_flag_colors', '_light');
	else:
		delete_option('cpccom_flag_colors');
	endif;

	if (isset($the_post['cpc_filter_feed_comments'])):
		update_option('cpc_filter_feed_comments', true);
	else:
		delete_option('cpc_filter_feed_comments');
	endif;    

    if (isset($the_post['cpc_filter_feed_comments_update'])):
        // ... update comment_type prior to version 16.02.03
        
        // Increase PHP script timeout
        set_time_limit(86400); // 24 hours    
        global $wpdb;

        $sql = "SELECT ID FROM ".$wpdb->prefix."posts WHERE post_type = 'cpc_activity'";
        $the_posts = $wpdb->get_col($sql);
        if ($the_posts):
            foreach ($the_posts as $id):
                $sql = "UPDATE ".$wpdb->prefix."comments SET comment_type = 'cpc_activity_comment' WHERE comment_post_ID = %d";
                $wpdb->query($wpdb->prepare($sql, $id));
            endforeach;
        endif;

        $sql = "SELECT ID FROM ".$wpdb->prefix."posts WHERE post_type = 'cpc_forum_post'";
        $the_posts = $wpdb->get_col($sql);
        if ($the_posts):
            foreach ($the_posts as $id):
                $sql = "UPDATE ".$wpdb->prefix."comments SET comment_type = 'cpc_forum_comment' WHERE comment_post_ID = %d";
                $wpdb->query($wpdb->prepare($sql, $id));
            endforeach;
        endif;

        $sql = "SELECT ID FROM ".$wpdb->prefix."posts WHERE post_type = 'cpc_event'";
        $the_posts = $wpdb->get_col($sql);
        if ($the_posts):
            foreach ($the_posts as $id):
                $sql = "UPDATE ".$wpdb->prefix."comments SET comment_type = 'cpc_event_comment' WHERE comment_post_ID = %d";
                $wpdb->query($wpdb->prepare($sql, $id));
            endforeach;
        endif;

        $sql = "SELECT ID FROM ".$wpdb->prefix."posts WHERE post_type = 'cpc_gallery'";
        $the_posts = $wpdb->get_col($sql);
        if ($the_posts):
            foreach ($the_posts as $id):
                $sql = "UPDATE ".$wpdb->prefix."comments SET comment_type = 'cpc_gallery_comment' WHERE comment_post_ID = %d";
                $wpdb->query($wpdb->prepare($sql, $id));
            endforeach;
        endif;

        $sql = "SELECT ID FROM ".$wpdb->prefix."posts WHERE post_type = 'cpc_mail'";
        $the_posts = $wpdb->get_col($sql);
        if ($the_posts):
            foreach ($the_posts as $id):
                $sql = "UPDATE ".$wpdb->prefix."comments SET comment_type = 'cpc_mail_comment' WHERE comment_post_ID = %d";
                $wpdb->query($wpdb->prepare($sql, $id));
            endforeach;
        endif;

    endif;    
    
	do_action( 'cpc_admin_getting_started_core_save_hook', $the_post );

	if ($current_core !== $cpc_default_core):
		echo '<script>alert("Funktionen geändert, Seite neu laden...");location.reload();</script>';
	endif;      

}

// Add to Getting Started information
if (!function_exists('cpc_admin_getting_started_extensions')):

    add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_extensions', 1);
    function cpc_admin_getting_started_extensions() {

        $css = ( (!isset($_POST['cpc_expand'])) || (isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_extensions') ) ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';         
        echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" rel="cpc_admin_getting_started_extensions" id="cpc_admin_getting_started_extensions_div">'.__('Funktionen', CPC2_TEXT_DOMAIN).'</div>';

        $display = (!isset($_POST['cpc_expand']) && !isset($_GET['cpc_expand'])) || (isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_extensions') || (isset($_GET['cpc_expand']) && $_GET['cpc_expand'] == 'cpc_admin_getting_started_extensions') ? 'block' : 'none';
        echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_extensions" style="display:'.$display.'">';

        ?>
        <table class="form-table">
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="cpc_forum_order"><?php _e('Funktionen aktivieren', CPC2_TEXT_DOMAIN); ?></label><br />
                <?php echo __('Hier kannst Du Core-Funktionen aktivieren/deaktivieren', CPC2_TEXT_DOMAIN);?>
            </th>
            <td>
                <?php

                $values = get_option('cpc_default_core');
                $values = $values ? explode(',', $values) : array();        
                echo '<p style="font-size:2.0em;">'.__('Core Plugin', CPC2_TEXT_DOMAIN).'</p>';
                echo cpc_show_core($values, 'core-profile', __('Profil', CPC2_TEXT_DOMAIN), 'https://cp-community.n3rds.work/profile-page/', '');
                echo cpc_show_core($values, 'core-activity', __('Aktivität', CPC2_TEXT_DOMAIN), 'https://cp-community.n3rds.work/profile-page/', '');
                echo cpc_show_core($values, 'core-avatar', __('Avatar', CPC2_TEXT_DOMAIN), 'https://cp-community.n3rds.work/profile-page/', '');
                echo cpc_show_core($values, 'core-friendships', __('Freundschaften', CPC2_TEXT_DOMAIN), 'https://cp-community.n3rds.work/profile-page/', '');
                echo cpc_show_core($values, 'core-alerts', __('Benachrichtigungen', CPC2_TEXT_DOMAIN), 'https://cp-community.n3rds.work/email-alerts/', '');
                echo cpc_show_core($values, 'core-forums', __('Foren', CPC2_TEXT_DOMAIN), 'https://cp-community.n3rds.work/forum-page/', '');
                echo cpc_show_core($values, 'core-groups', __('Gruppen', CPC2_TEXT_DOMAIN), 'https://cp-community.n3rds.work/groups/', '');
                ?>
            </td>
        </tr> 
        </table>
        <?php

        echo '</div>';

    }
endif;

function cpc_show_core($values, $field, $label, $help, $video) {

    $html = '<input type="checkbox" class="cpc_extension_checkbox" style="width:10px;" name="'.$field.'"';
    if (in_array($field, $values)) $html .= ' CHECKED';
    $html .= '>'.$label;

    if ($help) $html .= sprintf('<a href="%s" target="_blank"><img style="width:16px;height:16px" src="'.plugins_url('../ps-community/css/images/help.png', __FILE__).'" title="'.__('Hilfe', CPC2_TEXT_DOMAIN).'" /></a>', $help);
    if ($video) $html .= sprintf('<a href="%s" target="_blank"><img style="width:16px;height:16px" src="'.plugins_url('../ps-community/css/images/video.png', __FILE__).'" title="'.__('Video', CPC2_TEXT_DOMAIN).'" /></a>', $video);
    $html .= '<br />';
    return $html;
}

function cpc_codex_link($url) {
    return '<div class="cpc_codex_link"><a target="_blank" href="'.$url.'">'.__('Klicke hier für Hilfe, Informationen und Beispiele mit diesem Shortcode (öffnet neues Fenster)...').'</a></div>';
}

/* STYLES */

// Add Default settings information
add_action('cpc_admin_getting_started_styles_hook', 'cpc_admin_getting_started_styles', 1);
function cpc_admin_getting_started_styles() {
    
    echo '<div class="wrap">';
            
        echo '<style>';
            echo '.wrap { margin-top: 30px !important; margin-right: 10px !important; margin-left: 5px !important; }';
        echo '</style>';
        echo '<div id="cpc_release_notes">';
            echo '<div id="cpc_welcome_bar" style="margin-top: 20px;">';
                echo '<img id="cpc_welcome_logo" style="width:56px; height:56px; float:left;" src="'.plugins_url('../ps-community/css/images/cpc_logo.png', __FILE__).'" title="'.__('Hilfe', CPC2_TEXT_DOMAIN).'" />';
                echo '<div style="font-size:2em; line-height:1em; font-weight:100; color:#fff;">'.__('Willkommen bei CP-Community', CPC2_TEXT_DOMAIN).'</div>';
                echo '<p style="color:#fff;"><em>'.__('Das ultimative Plugin für soziale Netzwerke für ClassicPress', CPC2_TEXT_DOMAIN).'</em></p>';
            echo '</div>';

            $css = 'cpc_admin_getting_started_menu_item_remove_icon ';    
          	echo '<div style="margin-top:25px" class="'.$css.'cpc_admin_getting_started_menu_item_no_click" >'.__('CP-Community-Stile', CPC2_TEXT_DOMAIN).'</div>';    
        	$display = 'block';
          	echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_options" style="display:'.$display.'">';
            
                echo '<div id="cpc_admin_getting_started_options_outline">';
            
                    // reset options?
                    if (isset($_GET['cpc_reset_options'])) {

                        global $wpdb;
                        $sql = "DELETE FROM ".$wpdb->prefix."options WHERE option_name like 'cpc_styles_%'";
                        $wpdb->query($sql);
                        echo '<div class="cpc_success" style="margin-top:20px">';
                            echo sprintf(__('CP-Community-Stile wurden alle zurückgesetzt! <a href="%s">Weiter...</a>', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpc_com_styles' ));
                        echo '</div>';

                    } else {
            
                        echo '<h1>BETA</h1>';
                        echo '<p>Dies ist eine neue Funktion und wird derzeit getestet. Teile uns bitte Deine Meinung mit oder teile uns eventuelle Probleme mit, damit wir diese beheben können! Sobald die Stabilität stabil ist, werden wir die verfügbaren Optionen erweitern. Danke schön!</p>';
                        echo '<div id="cpc_admin_getting_started_options_help" style="margin-bottom:20px;'.(true || !isset($_POST['cpc_expand_shortcode']) ? '' : 'display:none;').'">';
                        echo sprintf(__('Dieser Abschnitt bietet eine schnelle und einfache Möglichkeit, alle PS Community-Bildschirmelemente zu gestalten und erspart Dir die Verwendung von CSS. Zurücksetzen <a onclick="return confirm(\''.__('Bist Du sicher? Dies kann icht rückgängig gemacht werden!', CPC2_TEXT_DOMAIN).'\')" href="%s">Alle Stiländerungen zurücksetzen</a>.', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpc_com_styles&cpc_reset_options=1')).'<br />';
                        echo '<p>'.sprintf(__('Wenn Du mit der Maus über ein Element fährst, wird die verwendete CSS-Klasse angezeigt und Du kannst dann <a href="%s">Benutzerdefiniertes CSS</a> verwenden, um erweiterte Attribute hinzuzufügen.', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpc_com_custom_css')).'</p>';
                        
                        // Ensure expand variables are set even if styles are disabled
                        $cpc_expand_tab = isset($_POST['cpc_expand_tab']) ? $_POST['cpc_expand_tab'] : 'elements';
                        $cpc_expand_shortcode = isset($_POST['cpc_expand_shortcode']) ? $_POST['cpc_expand_shortcode'] : 'cpc_elements_tab';

                        $use_styles = get_option('cpccom_use_styles');
                        if (!$use_styles):
                        
                            echo '<br />'.__('Die Verwendung von Stilen auf diese Weise belastet Deine Seiten geringfügig, daher musst Du sie zuerst aktivieren.', CPC2_TEXT_DOMAIN).'<br />';                        
                            echo '<br /><input type="submit" id="cpc_styles_enable_submit" name="Submit" class="button-primary" value="'.__('Aktiviere Stile', CPC2_TEXT_DOMAIN).'" />';
                            echo '</div>';
                        
                        else:
                        
                            echo '</div>';                        

                            echo '<div id="cpc_admin_getting_started_options_please_wait">';
                                echo __('Bitte warten, Werte werden geladen...', CPC2_TEXT_DOMAIN);
                            echo '</div>';

                            echo '<div id="cpc_admin_getting_started_options_left_and_middle" style="display: none;">';
                                echo '<div id="cpc_admin_getting_started_options_left">';
                                    /* TABS (1st column) */
                                    $cpc_expand_tab = isset($_POST['cpc_expand_tab']) ? $_POST['cpc_expand_tab'] : 'elements';
                                    $tabs = array();
                                    array_push($tabs, array('tab' => 'cpc_option_elements', 'option' => 'elements', 'title' => __('Interface', CPC2_TEXT_DOMAIN)));
                                    array_push($tabs, array('tab' => 'cpc_option_forums', 'option' => 'forums', 'title' => __('Foren', CPC2_TEXT_DOMAIN)));
                                    array_push($tabs, array('tab' => 'cpc_option_groups', 'option' => 'groups', 'title' => __('Gruppen', CPC2_TEXT_DOMAIN)));

                                    // any more tabs?
                                    $tabs = apply_filters( 'cpc_styles_show_tab_filter', $tabs );

                                    $sort = array();
                                    foreach($tabs as $k=>$v) {
                                        $sort['title'][$k] = $v['title'];
                                    }
                                    array_multisort($sort['title'], SORT_ASC, $tabs);    

                                    foreach ($tabs as $tab):
                                        echo cpc_show_tab($cpc_expand_tab, $tab['tab'], $tab['option'], $tab['title']);
                                    endforeach;

                                    echo '<div id="cpc_styles_save_button" style="text-align:left"><input type="submit" id="cpc_styles_save_submit" name="Submit" class="button-primary" value="'.__('Stile speichern', CPC2_TEXT_DOMAIN).'" /></div>';

                                    echo '<span style="display:none;float:left;margin-bottom:19px;" class="spinner"></span>';
                                    echo '<div style="clear:both"><hr />'.__('Wenn Du diese Funktion nicht verwendest, solltest Du sie deaktivieren (Du kannst sie erneut aktivieren).', CPC2_TEXT_DOMAIN).'</div>';

                                    echo '<div id="cpc_styles_save_button" style="text-align:left"><input type="submit" id="cpc_styles_disable_submit" name="Submit" class="button-secondary" value="'.__('Stile deaktivieren', CPC2_TEXT_DOMAIN).'" /></div>';

                                echo '</div>';

                                echo '<div id="cpc_admin_getting_started_options_middle">';
                                    /* SHORTCODES (2nd column) */
                                    $cpc_expand_shortcode = isset($_POST['cpc_expand_shortcode']) ? $_POST['cpc_expand_shortcode'] : 'cpc_elements_tab';
                                    // Elements
                                    echo cpc_show_style($cpc_expand_tab, $cpc_expand_shortcode, 'elements', 'cpc_elements_tab', __('Elemente', CPC2_TEXT_DOMAIN));
                                    // Forums
                                    echo cpc_show_style($cpc_expand_tab, $cpc_expand_shortcode, 'forums', 'cpc_forum_tab', CPC_PREFIX.'-forum');
                                    echo cpc_show_style($cpc_expand_tab, $cpc_expand_shortcode, 'forums', 'cpc_forums_tab', CPC_PREFIX.'-forums');
                                    // Groups
                                    echo cpc_show_style($cpc_expand_tab, $cpc_expand_shortcode, 'groups', 'cpc_groups_list_tab', CPC_PREFIX.'-groups');
                                    echo cpc_show_style($cpc_expand_tab, $cpc_expand_shortcode, 'groups', 'cpc_group_single_tab', CPC_PREFIX.'-group-single');
                                    echo cpc_show_style($cpc_expand_tab, $cpc_expand_shortcode, 'groups', 'cpc_my_groups_tab', CPC_PREFIX.'-my-groups');

                                    // any more shortcodes?
                                    do_action('cpc_styles_shortcode_hook', $cpc_expand_tab, $cpc_expand_shortcode);    

                                echo '</div>';
                            echo '</div>';    
                        
                        endif;

                        echo '<div id="cpc_admin_getting_started_options_right" style="display: none;">';

                            /* ----------------------- ELEMENTS TAB ----------------------- */

                            $function = 'cpc_elements';
                            $values = get_option('cpc_styles_'.$function) ? get_option('cpc_styles_'.$function) : array(); 

                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_elements_tab');
                                echo '<table class="widefat fixed" cellspacing="0">';
                                echo cpc_styles_header();

                                    echo cpc_styles_show_values(__('Schaltflächen', CPC2_TEXT_DOMAIN), 'cpc_button', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Schaltflächen (Mouseover)', CPC2_TEXT_DOMAIN), 'cpc_button:hover', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Schaltflächen (Klick)', CPC2_TEXT_DOMAIN), 'cpc_button:active', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Links', CPC2_TEXT_DOMAIN), 'a', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Links (Mouseover)', CPC2_TEXT_DOMAIN), 'a:hover', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Links (Klick)', CPC2_TEXT_DOMAIN), 'a:active', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Überschrift 1', CPC2_TEXT_DOMAIN), 'h1', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Überschrift 2', CPC2_TEXT_DOMAIN), 'h2', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Überschrift 3', CPC2_TEXT_DOMAIN), 'h3', '', '', '', 'off', 'off', $function, $values);

                                echo '</table>';    
                            echo '</div>';    

                            /* OTHERS */

                            do_action('cpc_styles_shortcode_options_hook', $cpc_expand_shortcode);        

                        
                            /* ----------------------- FORUMS TAB ----------------------- */

                            // [cpc-forum]
                            $function = 'cpc_forum';
                            $values = get_option('cpc_styles_'.$function) ? get_option('cpc_styles_'.$function) : array(); 

                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_forum_tab');
                                echo '<table class="widefat fixed" cellspacing="0">';
                                    echo cpc_styles_header();

                                    echo cpc_styles_show_values(__('Themen-Header', CPC2_TEXT_DOMAIN), 'cpc_forum_title_header', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Antworten-Header', CPC2_TEXT_DOMAIN), 'cpc_forum_count_header', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Letzter Poster-Header', CPC2_TEXT_DOMAIN), 'cpc_forum_last_poster_header', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Neueste-Header', CPC2_TEXT_DOMAIN), 'cpc_forum_categories_freshness_header', '', '', '', 'off', 'off', $function, $values);

                                echo '</table>';    
                            echo '</div>';    

                            // [cpc-forums]
                            $function = 'cpc_forums';
                            $values = get_option('cpc_styles_'.$function) ? get_option('cpc_styles_'.$function) : array(); 

                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_forums_tab');
                                echo '<table class="widefat fixed" cellspacing="0">';
                                    echo cpc_styles_header();

                                    echo cpc_styles_show_values(__('Forumtitel', CPC2_TEXT_DOMAIN),                'cpc_forums_forum_title',                   '', '', '', 'off', 'off', $function, $values, '');
                                    echo cpc_styles_show_values(__('Forumtitel (Mouseover)', CPC2_TEXT_DOMAIN),   'cpc_forums_forum_title:hover',                   '', '', '', 'off', 'off', $function, $values, '');
                                    echo cpc_styles_show_values(__('Forumtitel (Klick)', CPC2_TEXT_DOMAIN),        'cpc_forums_forum_title:active',                   '', '', '', 'off', 'off', $function, $values, sprintf(__('If Forum Title is styled, it would be best to <a href="%s">turn off "Top level as links"</a> via the [cpc-forums] shortcode.', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpc_com_shortcodes' )));

                                echo '</table>';    
                            echo '</div>';    

                            /* ----------------------- GROUPS TAB ----------------------- */

                            // [cpc-groups]
                            $function = 'cpc_groups';
                            $values = get_option('cpc_styles_'.$function) ? get_option('cpc_styles_'.$function) : array(); 

                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_groups_list_tab');
                                echo '<table class="widefat fixed" cellspacing="0">';
                                    echo cpc_styles_header();

                                    echo cpc_styles_show_values(__('Gruppen-Container', CPC2_TEXT_DOMAIN), 'cpc_groups_grid', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Gruppen-Karte', CPC2_TEXT_DOMAIN), 'cpc_group_card', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Gruppen-Titel', CPC2_TEXT_DOMAIN), 'cpc_group_card_title', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Gruppen-Titel (Mouseover)', CPC2_TEXT_DOMAIN), 'cpc_group_card_title:hover', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Gruppen-Meta', CPC2_TEXT_DOMAIN), 'cpc_group_meta', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Gruppen-Beschreibung', CPC2_TEXT_DOMAIN), 'cpc_group_description', '', '', '', 'off', 'off', $function, $values);

                                echo '</table>';    
                            echo '</div>';    

                            // [cpc-group-single]
                            $function = 'cpc_group_single';
                            $values = get_option('cpc_styles_'.$function) ? get_option('cpc_styles_'.$function) : array(); 

                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_group_single_tab');
                                echo '<table class="widefat fixed" cellspacing="0">';
                                    echo cpc_styles_header();

                                    echo cpc_styles_show_values(__('Gruppen-Header', CPC2_TEXT_DOMAIN), 'cpc_group_header', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Gruppen-Titel', CPC2_TEXT_DOMAIN), 'cpc_group_header h1', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Gruppen-Inhalt', CPC2_TEXT_DOMAIN), 'cpc_group_content', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Gruppen-Aktionen', CPC2_TEXT_DOMAIN), 'cpc_group_actions', '', '', '', 'off', 'off', $function, $values);

                                echo '</table>';    
                            echo '</div>';    

                            // [cpc-my-groups]
                            $function = 'cpc_my_groups';
                            $values = get_option('cpc_styles_'.$function) ? get_option('cpc_styles_'.$function) : array(); 

                            echo cpc_show_options($cpc_expand_shortcode, 'cpc_my_groups_tab');
                                echo '<table class="widefat fixed" cellspacing="0">';
                                    echo cpc_styles_header();

                                    echo cpc_styles_show_values(__('Meine Gruppen Grid', CPC2_TEXT_DOMAIN), 'cpc_my_groups_grid', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Gruppen-Karte', CPC2_TEXT_DOMAIN), 'cpc_group_card', '', '', '', 'off', 'off', $function, $values);
                                    echo cpc_styles_show_values(__('Rollen-Badge', CPC2_TEXT_DOMAIN), 'cpc_group_role_badge', '', '', '', 'off', 'off', $function, $values);

                                echo '</table>';    
                            echo '</div>';    

                            /* OTHERS */

                            do_action('cpc_styles_shortcode_options_hook', $cpc_expand_shortcode);        

                        echo '</div>';

                        
                    }
            
                echo '</div>';

            echo '</div>';

        echo '</div>';

}

function cpc_styles_header() {
    $html = '<thead><tr>';
        $html .= '<td>'.__('Element', CPC2_TEXT_DOMAIN).'</td>';
        $html .= '<td>'.__('Vordergrundfarbe', CPC2_TEXT_DOMAIN).'</td>';
        $html .= '<td>'.__('Hintergrundfarbe', CPC2_TEXT_DOMAIN).'</td>';
        $html .= '<td style="width:50px;">'.__('Fett', CPC2_TEXT_DOMAIN).'</td>';
        $html .= '<td style="width:50px;">'.__('Kursiv', CPC2_TEXT_DOMAIN).'</td>';
        $html .= '<td>'.__('Schriftgröße', CPC2_TEXT_DOMAIN).'</td>';
    $html .= '</tr></thead>';
    return $html;    
}
function cpc_styles_show_values($element, $class, $forecolor, $backcolor, $font_size, $bold, $italic, $function, $values, $notes = '') {

    echo '<tr>';
    echo '<td><a href="javascript:void(0);" title=".'.$class.'" alt=".'.$class.'">'.$element.'</a></td>';

    $default = $forecolor;
    $attr = 'forecolor';
    $value = cpc_get_shortcode_default($values, $function.'-'.$class.'_'.$attr, $default);
    echo '<td><input type="text" name="'.$function.'-'.$class.'_'.$attr.'" class="cpc-color-picker" data-default-color="'.$default.'" value="'.$value.'" /></td>';

    $default = $backcolor;
    $attr = 'backcolor';
    $value = cpc_get_shortcode_default($values, $function.'-'.$class.'_'.$attr, $default);
    echo '<td><input type="text" name="'.$function.'-'.$class.'_'.$attr.'" class="cpc-color-picker" data-default-color="'.$default.'" value="'.$value.'" /></td>';

    $default = $bold;
    $attr = 'bold';
    $value = cpc_get_shortcode_default($values, $function.'-'.$class.'_'.$attr, $default);
    echo '<td><input type="checkbox" name="'.$function.'-'.$class.'_'.$attr.'"'.($value == 'on' ? ' CHECKED' : '').'></td>';

    $default = $italic;
    $attr = 'italic';
    $value = cpc_get_shortcode_default($values, $function.'-'.$class.'_'.$attr, $default);
    echo '<td><input type="checkbox" name="'.$function.'-'.$class.'_'.$attr.'"'.($value == 'on' ? ' CHECKED' : '').'></td>';

    $default = $font_size;
    $attr = 'fontsize';
    $value = cpc_get_shortcode_default($values, $function.'-'.$class.'_'.$attr, $default);
    echo '<td><select class="cpc_fontsize_select" name="'.$function.'-'.$class.'_'.$attr.'">';
        echo '<option value=""'.($value == '' ? ' SELECTED' : '').'>'.__('Inherit', CPC2_TEXT_DOMAIN).'</option>';
        echo '<option value="1em"'.($value == '1em' ? ' SELECTED' : '').'>1em</option>';
        echo '<option value="1.2em"'.($value == '1.2em' ? ' SELECTED' : '').'>1.2em</option>';
        echo '<option value="1.4em"'.($value == '1.4em' ? ' SELECTED' : '').'>1.4em</option>';
        echo '<option value="1.6em"'.($value == '1.6em' ? ' SELECTED' : '').'>1.6em</option>';
        echo '<option value="1.8em"'.($value == '1.8em' ? ' SELECTED' : '').'>1.8em</option>';
        echo '<option value="2.0em"'.($value == '2.0em' ? ' SELECTED' : '').'>2.0em</option>';
        echo '<option value="2.2em"'.($value == '2.2em' ? ' SELECTED' : '').'>2.2em</option>';
        echo '<option value="2.4em"'.($value == '2.4em' ? ' SELECTED' : '').'>2.4em</option>';
    echo '</select></td>';

    echo '</tr>';

    if ($notes):
        echo '<tr style="background-color:#efefef">';
            echo '<td colspan=6>'.$notes.'</td>';
        echo '</tr>';
    endif;
}

function cpc_show_style($cpc_expand_tab, $cpc_expand_shortcode, $tab, $function, $shortcode) {
    if ($function != 'cpc_elements_tab'):
        return '<div rel="'.$function.'" class="'.($cpc_expand_shortcode == $function ? 'cpc_admin_getting_started_active' : '').' cpc_'.$tab.' cpc_admin_getting_started_option_shortcode" data-tab="'.$function.'" style="display:'.($cpc_expand_tab == $tab ? 'block' : 'none').'">['.$shortcode.']</div>';
    else:
        return '<div rel="'.$function.'" class="'.($cpc_expand_shortcode == $function ? 'cpc_admin_getting_started_active' : '').' cpc_'.$tab.' cpc_admin_getting_started_option_shortcode" data-tab="'.$function.'" style="display:'.($cpc_expand_tab == $tab ? 'block' : 'none').'">'.$shortcode.'</div>';
    endif;
}

?>
