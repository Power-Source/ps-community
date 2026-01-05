<?php

/* **** */ /* INIT */ /* **** */

function cpc_activity_init() {
    // JavaScript-Datei einbinden
    wp_enqueue_script('cpc-activity-js', plugins_url('cpc_activity.js', __FILE__), array('jquery'));
    
    // JavaScript lokalisieren, um PHP-Variablen verfügbar zu machen
    wp_localize_script('cpc-activity-js', 'cpc_activity_ajax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'plugins_url' => plugins_url('', __FILE__),
        'activity_post_focus' => get_option('cpccom_activity_set_focus')
    ));
    
    // CSS-Datei einbinden
    wp_enqueue_style('cpc-activity-css', plugins_url('cpc_activity.css', __FILE__), array(), '1.0.0');
    
    // Select2-Bibliothek einbinden
    wp_enqueue_script('cpc-select2-js', plugins_url('../js/select2.js', __FILE__), array('jquery'), '4.0.13', true);
    wp_enqueue_style('cpc-select2-css', plugins_url('../js/select2.css', __FILE__), array(), '4.0.13');
    
    // Zusätzliche Hooks für andere Plugins oder Themes
    do_action('cpc_activity_init_hook');
}


/* ********** */ /* SHORTCODES */ /* ********** */

function cpc_activity_page($atts){

	// Init
	cpc_activity_init();

    global $current_user;
	$html = '';
    
	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_activity_page');
	extract( shortcode_atts( array(
		'user_id' => false,
        'mimic_user_id' => false,
		'user_avatar_size' => cpc_get_shortcode_value($values, 'cpc_activity_page-user_avatar_size', 150),
		'map_style' => cpc_get_shortcode_value($values, 'cpc_activity_page-map_style', 'dynamic'),
		'map_size' => cpc_get_shortcode_value($values, 'cpc_activity_page-map_size', '150,150'),
		'map_zoom' => cpc_get_shortcode_value($values, 'cpc_activity_page-map_zoom', 4),
		'town_label' => cpc_get_shortcode_value($values, 'cpc_activity_page-town_label', __('Stadt/Gemeinde', CPC2_TEXT_DOMAIN)),
        'country_label' => cpc_get_shortcode_value($values, 'cpc_activity_page-country_label', __('Land', CPC2_TEXT_DOMAIN)),
        'requests_label' => cpc_get_shortcode_value($values, 'cpc_activity_page-requests_label', __('Freundschaftsanfragen', CPC2_TEXT_DOMAIN)),
        'styles' => true,
	), $atts, 'cpc_activity_page' ) );
    
	if (!$user_id):
        $user_id = cpc_get_user_id();
        $this_user = $current_user->ID;
    else:
        if ($mimic_user_id):
            $this_user = $user_id;
        else:
            $this_user = $current_user->ID;
        endif;
    endif;

	$html .= '<style>.cpc_avatar img { border-radius:0px; }</style>';
	$html .= cpc_display_name(array('user_id'=>$user_id, 'before'=>'<div id="cpc_display_name" style="font-size:2.5em; line-height:2.5em; margin-bottom:20px;">', 'after'=>'</div>'));
	$html .= '<div style="overflow:auto;overflow-y:hidden;margin-bottom:15px">';
    $html .= '<div id="cpc_activity_page_avatar" style="float: left; margin-right: 20px;">';
    if (strpos(CPC_CORE_PLUGINS, 'core-avatar') !== false):
        $html .= cpc_avatar(array('user_id'=>$user_id, 'change_link'=>1, 'size'=>$user_avatar_size, 'before'=>'<div id="cpc_display_avatar" style="float:left; margin-right:15px;">', 'after'=>'</div>'));
    else:
        $html .= '<div id="cpc_display_avatar" style="float:left; margin-right:15px;">';
            $html .= get_avatar($user_id, $user_avatar_size);
        $html .= '</div>';
    endif;
    $html .= '</div>';
    if (strpos(CPC_CORE_PLUGINS, 'core-profile') !== false):
        $html .= '<div style="float:left;margin-right:15px;">';
        $html .= cpc_usermeta(array('user_id'=>$user_id, 'meta'=>'cpccom_home', 'before'=>'<strong>'.$town_label.'</strong><br />', 'after'=>'<br />'));
        $html .= cpc_usermeta(array('user_id'=>$user_id, 'meta'=>'cpccom_country', 'before'=>'<strong>'.$country_label.'</strong><br />', 'after'=>'<br />'));
        $html .= cpc_usermeta_change_link($atts);
    endif;
	$html .= '</div>';
    if (strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false):
        $html .= '<div id="cpc_display_friend_requests" style="margin-left:10px;float:left;min-width:200px;">';
        $html .= cpc_friends_pending(array('user_id'=>$user_id, 'count' => 10, 'before'=>'<div class="cpc_20px_gap"><div style="font-weight:bold;margin-bottom: 10px">'.$requests_label.'</div>', 'after'=>'</div>'));
        $html .= cpc_friends_add_button(array());
        $html .= '</div>';
    endif;
	$html .= '</div>';
	$html .= cpc_activity_post($atts);
	$html .= cpc_activity($atts);

    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_activity_page', '', '', $styles, $values);    
    
	return $html;

}

function cpc_activity_post($atts) {

    if (!isset($_GET['view'])):

    	// Init
    	cpc_activity_init();

    	$html = '';

    	global $current_user;

    	// Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_activity_post');    
    	extract( shortcode_atts( array(
            'user_id' => false,
    		'class' => cpc_get_shortcode_value($values, 'cpc_activity_post-class', ''),
    		'label' => cpc_get_shortcode_value($values, 'cpc_activity_post-label', __('Beitrag hinzufügen', CPC2_TEXT_DOMAIN)),
    		'private_msg' => cpc_get_shortcode_value($values, 'cpc_activity_post-private_msg', __('Du hast keine Berechtigung, hier etwas zu posten', CPC2_TEXT_DOMAIN)),
            'account_closed_msg' => cpc_get_shortcode_value($values, 'cpc_activity_post-private_msg', __('Konto geschlossen.', CPC2_TEXT_DOMAIN)),
            'background_icon' => cpc_get_shortcode_value($values, 'cpc_activity_post-background_icon', false),
    		'before' => '',
    		'styles' => true,
            'after' => '',
    	), $atts, 'cpc_activity_post' ) );

    	if (!$user_id) $user_id = cpc_get_user_id();

        if (is_user_logged_in() && $user_id):

            if (strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false):
                $friends = cpc_are_friends($current_user->ID, $user_id);
                // By default same user, and friends of user, can see profile
                $user_can_see_activity = ($current_user->ID == $user_id || $friends['status'] == 'publish') ? true : false;
                $user_can_see_activity = apply_filters( 'cpc_check_activity_security_filter', $user_can_see_activity, $user_id, $current_user->ID );
            else:
                $user_can_see_activity = $current_user->ID == $user_id ? true : false;
            endif;

        	if ($user_can_see_activity):

        		$form_html = '';
                if (!cpc_is_account_closed($user_id)):
                    $form_html .= '<div id="cpc_activity_post_div" style="display:none">';
                        $form_html .= '<form id="theuploadform">';
                        $form_html .= '<input type="hidden" id="cpc_activity_post_action" name="action" value="cpc_activity_post_add" />';
                        $form_html .= '<input type="hidden" name="cpc_activity_post_author" value="'.$current_user->ID.'" />';
                        $form_html .= '<input type="hidden" name="cpc_activity_post_target" value="'.$user_id.'" />';
                        $form_html = apply_filters( 'cpc_activity_post_pre_form_filter', $form_html, $atts, $user_id, $current_user->ID );
                        $background_icon = $background_icon ? 'class="cpc_background_edit_icon" ' : ''; 
                        $form_html .= '<textarea id="cpc_activity_post" autocomplete="off" '.$background_icon.'name="cpc_activity_post"></textarea>';
                        $form_html = apply_filters( 'cpc_activity_post_post_form_filter', $form_html, $atts, $user_id, $current_user->ID );
                        $form_html .= '<button id="cpc_activity_post_button" class="cpc_button '.$class.'">'.$label.'</button>';
                        $form_html .= '</form>';
                    $form_html .= '</div>';
                else:
                    $form_html .= '<div class="cpc_account_closed">'.$account_closed_msg.'</div>';
                endif;

        		$html .= $form_html;


        	else:

        		if ($user_id) $html .= '<div id="cpc_activity_post_private_msg">'.$private_msg.'</div>';

        	endif;

        endif;

    	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_activity_post', $before, $after, $styles, $values);    

    	return $html;

    endif;

}

function cpc_activity($atts) {

$debug_html = '';
$debug = ($_SERVER['REQUEST_URI'] == '/test-page/' || $_SERVER['REQUEST_URI'] == '/test-page/?debug_queries=true') ? false : false;

if ($debug) $debug_html .= 'Start: '.date('Y-m-d H:i:s').'<br />';

	// Init
	cpc_activity_init();

	$html = '';
	global $current_user, $wpdb;
    
	$html .= '<br style="clear:both" />';
	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_activity');    
	extract( shortcode_atts( array(
		'user_id' => false,
        'mimic_user_id' => false,
		'post_id' => false,
		'include_self' => cpc_get_shortcode_value($values, 'cpc_activity-include_self', true),
		'include_friends' => cpc_get_shortcode_value($values, 'cpc_activity-include_friends', true),
		'active_friends' => cpc_get_shortcode_value($values, 'cpc_activity-active_friends', 30),
        'page_size' => cpc_get_shortcode_value($values, 'cpc_activity-page_size', 10),
        'get_max' => cpc_get_shortcode_value($values, 'cpc_activity-get_max', 50),
        'get_max_friends' => cpc_get_shortcode_value($values, 'cpc_activity-get_max_friends', 50),
		'hide_until_loaded' => cpc_get_shortcode_value($values, 'cpc_activity-hide_until_loaded', false),
		'type' => '',
		'class' => cpc_get_shortcode_value($values, 'cpc_activity-class', ''),
        'private_msg' => cpc_get_shortcode_value($values, 'cpc_activity-private_msg', __('Die Aktivität ist privat', CPC2_TEXT_DOMAIN)),
		'not_found' => cpc_get_shortcode_value($values, 'cpc_activity-not_found', __('Leider ist dieser Aktivitätsbeitrag nicht mehr verfügbar.', CPC2_TEXT_DOMAIN)),
        'stick_others' => cpc_get_shortcode_value($values, 'cpc_activity-hide_until_loaded', 0), // set to 1 to stick other's activity to own stream
        'logged_out_msg' => cpc_get_shortcode_value($values, 'cpc_activity-logged_out_msg', __('Du musst angemeldet sein, um die Profilseite anzuzeigen.', CPC2_TEXT_DOMAIN)),
        'login_url' => cpc_get_shortcode_value($values, 'cpc_activity-login_url', ''),
		'before' => '',
		'styles' => true,
        'after' => '',
	), $atts, 'cpc_activity' ) );
    
    if (!$user_id):
        $user_id = cpc_get_user_id();
        $this_user = $current_user->ID;
    else:
        if ($mimic_user_id):
            $this_user = $user_id;
        else:
            $this_user = $current_user->ID;
        endif;
    endif;
    
    if (strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false):
        $friends = cpc_are_friends($this_user, $user_id);
        // By default same user, and friends of user, can see profile
        $user_can_see_activity = ($this_user == $user_id || $friends['status'] == 'publish') ? true : false;
        $user_can_see_activity = apply_filters( 'cpc_check_activity_security_filter', $user_can_see_activity, $user_id, $this_user );
    else:
        $user_can_see_activity = $this_user == $user_id ? true : false;
    endif;

    if ($user_can_see_activity && $user_id):

        if (current_user_can('manage_options') && !$login_url && function_exists('cpc_login_init')):
            $html = cpc_admin_tip($html, 'cpc_activity_login', __('Füge login_url="/example" zum Shortcode [cpc-activity] hinzu, damit sich Benutzer anmelden und hierher zurückleiten können, wenn sie nicht angemeldet sind.', CPC2_TEXT_DOMAIN));
        endif;    
    
        // Check for single post view
        if (!$post_id && isset($_GET['view'])) $post_id = $_GET['view'];

        $activity = array();

        // Pre activity filter
        $html = apply_filters( 'cpc_activity_pre_filter', $html, $atts, $user_id, $this_user );

        if ($user_can_see_activity):

            if (!$post_id):

                if ($type == ''): // Activity only

                    // Get user's activity (and posts targeted to user)
                    if ($include_self):
                        $sql = "SELECT p.ID, p.post_title, p.post_author, p.post_date_gmt as post_date, c.comment_date_gmt as comment_date, m.meta_value AS target_ids
                            FROM ".$wpdb->prefix."posts p 
                            LEFT JOIN ".$wpdb->prefix."comments c ON p.ID = c.comment_post_ID
                            LEFT JOIN ".$wpdb->prefix."postmeta m ON p.ID = m.post_id
                            WHERE p.post_type = %s
                            AND m.meta_key = 'cpc_target'
                            AND p.post_status = 'publish'
                            AND (
                                p.post_author = %d OR
                                c.comment_author = %d OR
                                m.meta_value LIKE '%%\"%d\"%%' OR
                                m.meta_value = %d
                            )
                            ORDER BY p.ID DESC
                            LIMIT 0,%d";

                        $results = $wpdb->get_results($wpdb->prepare($sql, 'cpc_activity', $user_id, $user_id, $user_id, $user_id, $get_max));

                        if ($debug) $debug_html .= 'activity from self<br />'.$wpdb->prepare($sql, 'cpc_activity', $user_id, $user_id, $user_id, $user_id, $get_max).'<br /><br />';
    
                        $added_count = 0;
                        $added_sticked = 0;
                        foreach ($results as $r):

                            // Check this is a normal activity post
                            $activity_type = get_post_meta($r->ID, 'cpc_activity_type', true);
                            if (!$activity_type):

                                $target_users = array();
                                $target_ids = $r->target_ids;
                                // Make a note of any target users (excluding post author)
                                if ($target_ids):
                                    if (is_array($target_ids) && $target_ids_array = unserialize($target_ids)):
                                        // Target is to multiple users
                                        $target_users = array_merge($target_users, $target_ids_array);
                                    else:
                                        // Target is one user
                                        array_push($target_users, $target_ids);
                                    endif;
                                endif;
                                $add = false;
    
                                if ($user_id == $this_user):	   // ------------ On user's own page

                                    // If author is this user
                                    if ($r->post_author == $user_id) { $add = true; };
                                    // If this user is a target (and a friend)
                                    if (strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false && cpc_are_friends($r->post_author, $user_id)) { $add = true; };
                                    // Exclude if this is just a friend sharing to friends
                                    if ($r->post_author != $user_id && (string)$r->post_author == $target_ids) { $add = false; };

                                else: 							   // ------------ On a friends page
 
                                    // If to a friend, and current user is a friend of this user
                                    if ($r->post_author == $user_id && (strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false && cpc_are_friends($r->post_author, $this_user))) { $add = true; };
                                    // If from this user to current user
                                    if ($r->post_author == $user_id && in_array((string)$this_user, $target_users)) { $add = true; };
                                    // If from current user to this user
                                    if ($r->post_author == $this_user && in_array((string)$user_id, $target_users)) { $add = true; };
                                    // If to this user and from current user (handle array)
                                    if ( preg_match( '/^a:\d+:{.*?}$/', $target_ids ) ): 
                                        $target_ids_array = unserialize($target_ids);
                                        if ($r->post_author == $this_user && in_array((int)$user_id, $target_ids_array)) { $add = true; };
                                    endif;

                                    // Exclude own posts to friends
                                    //if ($r->post_author != $user_id && $target_ids == $r->post_author) { $add = false; };

                                endif;

                                if ($add):
                                    $is_sticky = (($stick_others || $r->post_author == $user_id) && get_post_meta( $r->ID, 'cpc_sticky', true )) ? 2 : 1;
                                    if ($r->post_date > $r->comment_date):
                                        array_push($activity, array('ID' => $r->ID, 'datetime' => strtotime($r->post_date), 'date' => $r->post_date, 'is_sticky' => $is_sticky));
                                    else:
                                        array_push($activity, array('ID' => $r->ID, 'datetime' => strtotime($r->comment_date), 'date' => $r->comment_date, 'is_sticky' => $is_sticky));
                                    endif;
                                    $added_count++;
                                    if ($is_sticky == 2) $added_sticked++;
                                endif;
                            endif;
                        endforeach;
                    endif;
    
                    // Get activity from all friends of this page user
                    if (strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false && $include_friends):

                        // get list of most recently logged in friends to reduce processing
                        $sql = "SELECT user_id FROM ".$wpdb->base_prefix."usermeta WHERE meta_key = 'cpccom_last_active' AND STR_TO_DATE(meta_value,'%%Y-%%m-%%d %%H:%%i:%%s') >= date(now()-interval %d day)";
                        $recently_online_users = $wpdb->get_col($wpdb->prepare($sql, $active_friends));                
                        $friends = cpc_get_friends($user_id, $recently_online_users);

                        if ($friends):
    
                            foreach ($friends as $friend):

                                $sql = "SELECT p.ID, p.post_date_gmt as post_date, p.post_author, c.comment_date_gmt as comment_date, m.meta_value AS target_ids FROM ".$wpdb->prefix."posts p 
                                    LEFT JOIN ".$wpdb->prefix."comments c ON p.ID = c.comment_post_ID
                                    LEFT JOIN ".$wpdb->prefix."postmeta m ON p.ID = m.post_id
                                    WHERE p.post_type = %s
                                    AND m.meta_key = 'cpc_target'
                                    AND p.post_status = 'publish'
                                    AND p.post_author = %d
                                    ORDER BY p.ID DESC
                                    LIMIT 0, %d";

                                $results = $wpdb->get_results($wpdb->prepare($sql, 'cpc_activity', $friend['ID'], $get_max_friends));
    
                                if ($debug) $debug_html .= 'activity from all friends<br />'.$wpdb->prepare($sql, 'cpc_activity', $friend['ID'], $get_max_friends).'<br /><br />';

                                foreach ($results as $r):
                                    $add = false;
                                    $target_ids = $r->target_ids;
                                    if (is_array($target_ids)):
                                        // Show if this user is in the list of target user IDs
                                        $target_ids_array = unserialize($target_ids);
                                        if (in_array((string)$user_id, $target_ids_array)) { $add = true; };
                                    else:
                                        // Show if this user is the target, or the user is posting to all friends
                                        if ($user_id == $target_ids || $r->post_author == $r->target_ids):
                                            $add = true;
                                        endif;
                                    endif;
                                    // Check that author's permissions for their activity
                                    if ($add):
                                        $user_can_see_activity = apply_filters( 'cpc_check_activity_security_filter', $add, $r->post_author, $this_user );
                                        if (!$user_can_see_activity) $add = false;
                                    endif;

                                    // Current user is the author, always show
                                    if ($r->post_author == $this_user) { $add = true; };
    
                                    // Over-write if current user is the author, and $include_self = false
                                    if (!$include_self && $r->post_author == $this_user) { $add = false; }

                                    if ($add):
                                        $is_sticky = (($stick_others || $r->post_author == $user_id) && get_post_meta( $r->ID, 'cpc_sticky', true )) ? 2 : 1;
                                        if ($r->post_date > $r->comment_date):
                                            array_push($activity, array('ID' => $r->ID, 'datetime' => strtotime($r->post_date), 'date' => $r->post_date, 'is_sticky' => $is_sticky));
                                        else:
                                            array_push($activity, array('ID' => $r->ID, 'datetime' => strtotime($r->comment_date), 'date' => $r->comment_date, 'is_sticky' => $is_sticky));
                                        endif;									
                                    endif;
                                endforeach;

                            endforeach;
                        endif;
                    endif;

                endif;

                // Any more activity?
                $activity = apply_filters( 'cpc_activity_items_filter', $activity, $atts, $user_id, $this_user );

                else:

                // Single post view
                $single = get_post($post_id);

                if ($single):

                    $target_ids = get_post_meta($post_id, 'cpc_target', true);

                    $add = false;
                    if (is_array($target_ids)):
                        // Show if this user is in the list of target user IDs
                        if (in_array((string)$this_user, $target_ids)) { $add = true; };
                    else:
                        // Show if this user is the target, or the user is posting to all friends
                        if ($this_user == $target_ids || $single->post_author == $target_ids) $add = true;
                    endif;
                    // Check that author's permissions for their activity
                    if ($add):
                        $user_can_see_activity = apply_filters( 'cpc_check_activity_security_filter', $add, $single->post_author, $this_user );
                        if (!$user_can_see_activity) $add = false;
                    endif;

                    // Current user is the author, always show
                    if ($single->post_author == $this_user) { $add = true; };

                    if ($add) array_push($activity, array('ID' => $post_id, 'datetime' => strtotime($single->post_date), 'date' => $single->post_date, 'is_sticky' => 0));														

                    // Any more activity?
                    $activity = apply_filters( 'cpc_activity_single_item_filter', $activity, $atts, $user_id, $this_user );

                else:

                    $html .= $not_found;

                endif;

            endif;

            if ($activity):

                // First remove duplicate rows by ID that may have been added when collecting activity
                foreach ($activity as $key => $value):
                    $id = $value['ID'];
                    $found = 0;
                    foreach ($activity as $key2 => $value2):
                        if ($id == $value2['ID']):
                            $found++;
                            if ($found > 1):
                                unset($activity[$key]);
                            endif;
                        endif;
                    endforeach;
                endforeach;

                // Sort... (requires PHP 4+)
				foreach($activity as $key => $row) {
                    $is_sticky_sort[$key] = (int)$row['is_sticky'];
                    $datetime_sort[$key] = (int)$row['datetime'];
                }
                array_multisort($is_sticky_sort, SORT_DESC, $datetime_sort, SORT_DESC, $activity);

                // Output...
                $html .= '<div id="cpc_activity_items"';
                    if ($hide_until_loaded) $html .= 'style="display:none"';
                    $html .= '>';

                    $html .= '<div id="cpc_activity_ajax_div"><img style="width:20px;height:20px;" src="'.plugins_url('../css/images/wait.gif', __FILE__).'" /></div>';

                    $html .= '<div style="display:none" id="cpc_atts_array">'.serialize($atts).'</div>';
                    $html .= '<div style="display:none" id="cpc_activity_array">'.serialize($activity).'</div>';
                    $html .= '<div style="display:none" id="cpc_this_user">'.$this_user.'</div>';
                    $html .= '<div style="display:none" id="cpc_user_id">'.$user_id.'</div>';
                    $html .= '<div style="display:none" id="cpc_wait_url">'.plugins_url('../css/images/wait.gif', __FILE__).'</div>';
                    $html .= '<div style="display:none" id="cpc_page_size">'.$page_size.'</div>';
                    $html .= '<div style="display:none" id="cpc_nonce_'.$user_id.'">'.wp_create_nonce( 'cpc_get_activity_nonce_'.$user_id ).'</div>';

                $html .= '</div>';
    
            else:
    
                $html .= '<div id="cpc_activity_post_private_msg">'.__('Keine Aktivität zum Anzeigen...', CPC2_TEXT_DOMAIN).'</div>';
                $html .= '<div style="display:none" id="cpc_wait_url">'.plugins_url('../css/images/wait.gif', __FILE__).'</div>';
                $html .= '<div id="cpc_activity_items"></div>';

            endif;

        else:

            $html .= '<div id="cpc_activity_post_private_msg">'.$private_msg.'</div>';

        endif;
    
    else:

        if (!is_user_logged_in() && $logged_out_msg):
            $query = cpc_query_mark(get_bloginfo('url').$login_url);
            if ($login_url) $html .= sprintf('<a href="%s%s%sredirect=%s">', get_bloginfo('url'), $login_url, $query, cpc_root( $_SERVER['REQUEST_URI'] ));
            $html .= $logged_out_msg;
            if ($login_url) $html .= '</a>';
        endif;
    
    endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_activity', $before, $after, $styles, $values);

    if ($debug) $debug_html .= 'End: '.date('Y-m-d H:i:s').'<br />';

	return $debug_html . $html;
}
if (!is_admin()) {
    add_shortcode(CPC_PREFIX.'-activity-page', 'cpc_activity_page');
	add_shortcode(CPC_PREFIX.'-activity-post', 'cpc_activity_post');
	add_shortcode(CPC_PREFIX.'-activity', 'cpc_activity');
}


?>
