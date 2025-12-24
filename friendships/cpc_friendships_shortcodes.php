<?php

/* **** */ /* INIT */ /* **** */

function cpc_friends_init() {
	wp_enqueue_script('cpc-friendship-js', plugins_url('cpc_friends.js', __FILE__), array('jquery'));	
	wp_localize_script('cpc-friendship-js', 'cpc_friendships_ajax', array( 
		'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'fav_on' => plugins_url('images/star.png', __FILE__),
        'fav_off' => plugins_url('images/star_empty.png', __FILE__),
	));
	wp_enqueue_style('cpc-friends', plugins_url('cpc_friends.css', __FILE__), 'css');
	// Anything else?
	do_action('cpc_friends_init_hook');

}

/* ********** */ /* SHORTCODES */ /* ********** */

function cpc_favourite_friend($atts) {

	// Init
	add_action('wp_footer', 'cpc_friends_init');

	$html = '';
	global $current_user;

	if (is_user_logged_in()):

		// Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_favourite_friend');
		extract( shortcode_atts( array(
			'user_id' => '',
			'class' => '',
			'style' => cpc_get_shortcode_value($values, 'cpc_favourite_friend-style', 'button'),			
			'favourite_yes' => cpc_get_shortcode_value($values, 'cpc_favourite_friend-favourite_yes', __('Als Favorit entfernen', CPC2_TEXT_DOMAIN)),
			'favourite_no' => cpc_get_shortcode_value($values, 'cpc_favourite_friend-favourite_no', __('Als Favorit hinzufügen', CPC2_TEXT_DOMAIN)),
			'favourite_yes_msg' => cpc_get_shortcode_value($values, 'cpc_favourite_friend-subscribed_msg', __('Als Favorit entfernt.', CPC2_TEXT_DOMAIN)),
			'favourite_no_msg' => cpc_get_shortcode_value($values, 'cpc_favourite_friend-unsubscribed_msg', __('Als Favorit hinzugefügt.', CPC2_TEXT_DOMAIN)),
			'before' => '',
			'styles' => true,
            'after' => '',
		), $atts, 'cpc_favourite_friend' ) );

		if (!$user_id) $user_id = cpc_get_user_id();

		if ($user_id != $current_user->ID):

			$favourite = cpc_is_a_favourite_friend($current_user->ID, $user_id);

			$html .= '<div style="display:none" id="cpc_favourite_yes_msg">'.$favourite_yes_msg.'</div>';
			$html .= '<div style="display:none" id="cpc_favourite_no_msg">'.$favourite_no_msg.'</div>';

			$html .= '<div class="cpc_add_remove_favourite_div">';

				if ($style == 'button'):
					if ($favourite['status']):
						$html .= '<button rel="remove" data-user_id="'.$user_id.'" class="cpc_add_remove_favourite cpc_button '.$class.'">'.$favourite_yes.'</button>';
					else:
						$html .= '<button rel="add" data-user_id="'.$user_id.'" class="cpc_add_remove_favourite cpc_button '.$class.'">'.$favourite_no.'</button>';
					endif;
				else:
					if ($favourite['status']):
						$html .= '<a rel="remove" data-user_id="'.$user_id.'" class="cpc_add_remove_favourite" href="javascript:void(0);">'.$favourite_yes.'</a>';
					else:
						$html .= '<a rel="add" data-user_id="'.$user_id.'" class="cpc_add_remove_favourite" href="javascript:void(0);">'.$favourite_no.'</a>';
					endif;
				endif;

			$html .= '</div>';

		endif;

	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_favourite_friends_status', $before, $after, $styles, $values);

	return $html;

}

function cpc_friends_status($atts) {

	// Init
	add_action('wp_footer', 'cpc_friends_init');

	$html = '';
	global $current_user;

	if (is_user_logged_in()):

		// Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_friends_status');
		extract( shortcode_atts( array(
			'user_id' => '',
			'friends_yes' => cpc_get_shortcode_value($values, 'cpc_friends_status-friends_yes', __('Ihr seid Freunde', CPC2_TEXT_DOMAIN)),
			'friends_pending' => cpc_get_shortcode_value($values, 'cpc_friends_status-friends_pending', __('Du hast darum gebeten, Freunde zu werden', CPC2_TEXT_DOMAIN)),
			'friend_request' => cpc_get_shortcode_value($values, 'cpc_friends_status-friend_request', __('Du hast eine Freundschaftsanfrage', CPC2_TEXT_DOMAIN)),
			'friends_no' => cpc_get_shortcode_value($values, 'cpc_friends_status-friends_no', __('Ihr seid keine Freunde', CPC2_TEXT_DOMAIN)),
			'before' => '',
			'styles' => true,
            'after' => '',
		), $atts, 'cpc_friends_status' ) );

		if (!$user_id) $user_id = cpc_get_user_id();

		if ($user_id != $current_user->ID):

			$friends = cpc_are_friends($current_user->ID, $user_id);

			if ($friends['status']):
				if ($friends['status'] == 'publish'):
					$html .= $friends_yes;
				else:
					if ($friends['direction'] == 'to'):
						$html .= $friends_pending;
					else:
						$html .= $friend_request;
					endif;
				endif;
			else:
				$html .= $friends_no;
			endif;

		endif;

	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_friends_status', $before, $after, $styles, $values);

	return $html;

}

function cpc_friends_add_button($atts) {

	// Init
	add_action('wp_footer', 'cpc_friends_init');

	$html = '';
	global $current_user;

	if (is_user_logged_in() && !get_option('cpc_friendships_all')):

		// Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_friends_add_button');
		extract( shortcode_atts( array(
			'user_id' => 0,
			'label' => cpc_get_shortcode_value($values, 'cpc_friends_add_button-label', __('Freundschaft schließen', CPC2_TEXT_DOMAIN)),
			'cancel_label' => cpc_get_shortcode_value($values, 'cpc_friends_add_button-cancel_label', __('Freundschaft kündigen', CPC2_TEXT_DOMAIN)),
			'cancel_request_label' => cpc_get_shortcode_value($values, 'cpc_friends_add_button-cancel_request_label', __('Freundschaftsanfrage abbrechen', CPC2_TEXT_DOMAIN)),
			'class' => cpc_get_shortcode_value($values, 'cpc_friends_add_button-class', ''),
			'before' => '',
			'styles' => true,
            'after' => '',
		), $atts, 'cpc_friends_add' ) );

		if (!$user_id) $user_id = cpc_get_user_id();

		if ($user_id && $user_id != $current_user->ID):

			$html .= '<div class="cpc_friends_add_button">';

				$html .= '<input type="hidden" id="plugins_url" value="'.plugins_url( '', __FILE__ ).'" />';

				$friends = cpc_are_friends($current_user->ID, $user_id);
				if (!$friends['status']):

					$html .= '<button type="submit" rel="'.$user_id.'" class="cpc_button cpc_friends_add '.$class.'">'.$label.'</button>';

				else:

					if ($friends['status'] == 'publish'):
						$html .= '<button type="submit" rel="'.$friends['ID'].'" class="cpc_button cpc_friends_cancel '.$class.'">'.$cancel_label.'</button>';
					else:
						$html .= '<button type="submit" rel="'.$friends['ID'].'" class="cpc_button cpc_pending_friends_reject '.$class.'">'.$cancel_request_label.'</button>';
					endif;

				endif;

			$html .= '</div>';

		endif;


	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_friends_add_button', $before, $after, $styles, $values);

	return $html;

}

function cpc_friends($atts) {

	// Init
	add_action('wp_footer', 'cpc_friends_init');

	$html = '';
	global $current_user;

	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_friends');
	extract( shortcode_atts( array(
		'user_id' => false,
		'count' => cpc_get_shortcode_value($values, 'cpc_friends-count', 100),
		'size' => cpc_get_shortcode_value($values, 'cpc_friends-size', 64),
		'link' => cpc_get_shortcode_value($values, 'cpc_friends-link', true),
		'show_last_active' => cpc_get_shortcode_value($values, 'cpc_friends-show_last_active', true),
		'last_active_text' => cpc_get_shortcode_value($values, 'cpc_friends-last_active_text', __('Zuletzt gesehen:', CPC2_TEXT_DOMAIN)),
		'last_active_format' => cpc_get_shortcode_value($values, 'cpc_friends-last_active_format', __('vor %s', CPC2_TEXT_DOMAIN)),
		'private' => cpc_get_shortcode_value($values, 'cpc_friends-private', __('Private Informationen', CPC2_TEXT_DOMAIN)),
		'none' => cpc_get_shortcode_value($values, 'cpc_friends-none', __('Keine Freunde', CPC2_TEXT_DOMAIN)),
		'layout' => cpc_get_shortcode_value($values, 'cpc_friends-layout', get_option('cpc_friends_layout', 'list')), // list|fluid
        'logged_out_msg' => cpc_get_shortcode_value($values, 'cpc_friends-logged_out_msg', __('Du musst angemeldet sein, um diese Seite anzuzeigen.', CPC2_TEXT_DOMAIN)),
		'remove_all_friends' => cpc_get_shortcode_value($values, 'cpc_friends-remove_all_friends', true),
        'remove_all_friends_msg' => cpc_get_shortcode_value($values, 'cpc_friends-remove_all_friends_msg', __('Alle Freunde entfernen', CPC2_TEXT_DOMAIN)),
        'remove_all_friends_sure_msg' => cpc_get_shortcode_value($values, 'cpc_friends-remove_all_friends_sure_msg', __('Bist du sicher? Das kann nicht rückgängig gemacht werden!', CPC2_TEXT_DOMAIN)),
        'login_url' => cpc_get_shortcode_value($values, 'cpc_friends-login_url', ''),        
		'before' => '',
		'styles' => true,
        'after' => '',
	), $atts, 'cpc_friends' ) );
    
	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_favourite_friend');
	extract( shortcode_atts( array(
		'friends_tooltip' => cpc_get_shortcode_value($values, 'cpc_favourite_friend-friends_tooltip', __('Als Favorit hinzufügen/entfernen', CPC2_TEXT_DOMAIN)),
	), $atts, 'cpc_favourite_friend' ) );

	if (!$user_id)
		$user_id = cpc_get_user_id();
    
    if (isset($_GET['user_id'])) $user_id = $_GET['user_id'];

    if (is_user_logged_in()) {
        
        if (current_user_can('manage_options') && !$login_url && function_exists('cpc_login_init')):
            $html = cpc_admin_tip($html, 'cpc_friends', __('Füge login_url="/example" zum Shortcode [cpc-friends] hinzu, damit sich Benutzer anmelden und hierher zurückleiten können, wenn sie nicht angemeldet sind.', CPC2_TEXT_DOMAIN));
        endif;                
    
        $friends = cpc_are_friends($current_user->ID, $user_id);
        // By default same user, and friends of user, can see profile
        $user_can_see_friends = ($current_user->ID == $user_id || $friends['status'] == 'publish') ? true : false;
        $user_can_see_friends = apply_filters( 'cpc_check_friends_security_filter', $user_can_see_friends, $user_id, $current_user->ID );

        if ($user_can_see_friends):

            global $wpdb;
            if (!get_option('cpc_friendships_all')):
                $sql = "SELECT p.ID, m1.meta_value as cpc_member1, m2.meta_value as cpc_member2
                    FROM ".$wpdb->prefix."posts p 
                    LEFT JOIN ".$wpdb->prefix."postmeta m1 ON p.ID = m1.post_id
                    LEFT JOIN ".$wpdb->prefix."postmeta m2 ON p.ID = m2.post_id
                    WHERE p.post_type='cpc_friendship'
                      AND p.post_status='publish'
                      AND m1.meta_key = 'cpc_member1'
                      AND m2.meta_key = 'cpc_member2'
                      AND (m1.meta_value = %d OR m2.meta_value = %d)";
                $get_friends = $wpdb->get_results($wpdb->prepare($sql, $user_id, $user_id));
            else:
                $site_members = get_users( 'blog_id='.get_current_blog_id() );
                $get_friends = array();
                foreach ($site_members as $member):
                    $row_array['cpc_member1'] = $user_id;
                    $row_array['cpc_member2'] = $member->ID;
                    array_push($get_friends,$row_array);
                endforeach;
            endif;

            if ($get_friends):
        
                // Show remove all friends option, if on their own page
                if ($remove_all_friends && $user_id == $current_user->ID)
                	$html .= '<p><a id="cpc_remove_all_friends" data-sure="'.$remove_all_friends_sure_msg.'" href="javascript:void(0);">'.$remove_all_friends_msg.'</a></p>';

                // Put into array so they can be sorted
                $friends = array();
                foreach ($get_friends as $friend):
        
                    $row_array = array();
                            
                    if (is_array($friend)):
                        $other_member = $friend['cpc_member1'] == $user_id ? $friend['cpc_member2'] : $friend['cpc_member1'];
                    else:
                        $other_member = $friend->cpc_member1 == $user_id ? $friend->cpc_member2 : $friend->cpc_member1;
                    endif;
        
                    if (!cpc_is_account_closed($other_member)):
	                    // .. is a favourite?
	                    $favourite = cpc_is_a_favourite_friend($current_user->ID, $other_member);
	                    $row_array['favourite'] = $favourite['status'] == 'publish' ? 1 : 0;
                        $row_array['friend_id'] = $other_member;
                        $row_array['last_active'] = strtotime(get_user_meta($other_member, 'cpccom_last_active', true));
                        array_push($friends,$row_array);
                    endif;

                endforeach;

                // Sort friends by when last active
                $sort = array();
                $order = 'last_active';
                $orderby = 'DESC';
                foreach($friends as $k=>$v) {
	    			$sort['favourite'][$k] = $v['favourite'];
                    $sort[$order][$k] = $v[$order];
                }
                $orderby = $orderby == "ASC" ? SORT_ASC : SORT_DESC;
                array_multisort($sort['favourite'], SORT_DESC, $sort[$order], $orderby, $friends);

                // Show $count number of friends
                $c=0;
                foreach ($friends as $friend):

                    $the_friend = get_user_by('id', $friend['friend_id']);
                    if ($the_friend):

                        // Get profile_security of the_friend
                        $user_can_see_friend = true;
                        $user_can_see_friend = apply_filters( 'cpc_check_friends_security_filter', $user_can_see_friend, $friend['friend_id'], $current_user->ID );

                        if ($user_can_see_friend):

                            $html .= '<div id="cpc_friends"';
                                if ($layout == 'fluid') $html .= ' style="min-width: 235px; float:left;"';
                                $html .= '>';

                                $html .= '<div class="cpc_friends_friend" style="position:relative;padding-left: '.($size+10).'px">';
                                if ($size):
                                    $html .= '<div class="cpc_friends_friend_avatar" style="margin-left: -'.($size+10).'px">';
                                        $html .= cpc_friend_avatar($friend['friend_id'], $size, $link);
                                    $html .= '</div>';
                                endif;
                                $html .= '<div class="cpc_friends_friend_avatar_display_name">';
                                    $html .= cpc_display_name(array('user_id'=>$friend['friend_id'], 'link'=>$link));
                                    if ($friend['favourite']):
                                    	$html .= ' <div style="cursor:pointer;float:right;"><img title="'.$friends_tooltip.'" class="cpc_remove_favourite" rel="'.$friend['friend_id'].'" style="height:15px;width:15px;left:5px;top:5px;" src="'.plugins_url('images/star.png', __FILE__).'" /></div>';
                                    else:
                                    	$html .= ' <div style="cursor:pointer;float:right;"><img title="'.$friends_tooltip.'" class="cpc_add_favourite" rel="'.$friend['friend_id'].'" style="height:15px;width:15px;left:5px;top:5px;" src="'.plugins_url('images/star_empty.png', __FILE__).'" /></div>';
                                    endif;
                                $html .= '</div>';
                                if ($show_last_active && $friend['last_active']):
                                    $html .= '<div class="cpc_friends_friend_avatar_last_active">';
                                        $html .= html_entity_decode($last_active_text).' ';
                                        $html .= sprintf(html_entity_decode($last_active_format), human_time_diff($friend['last_active'], current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                                    $html .= '</div>';
                                endif;
                                $html .= '</div>';

                            $html .= '</div>';

                        endif;

                    endif;

                    $c++;
                    if ($c == $count) break;		
                endforeach;
            else:
                if ($user_id) $html .= '<div id="cpc_friends_none_msg">'.$none.'</div>';
            endif;

        else:

            if ($user_id) $html .= '<div id="cpc_friends_private_msg">'.$private.'</div>';

        endif;

    } else {
        
        if (!is_user_logged_in() && $logged_out_msg):
            $query = cpc_query_mark(get_bloginfo('url').$login_url);
            if ($login_url) $html .= sprintf('<a href="%s%s%sredirect=%s">', get_bloginfo('url'), $login_url, $query, cpc_root( $_SERVER['REQUEST_URI'] ));
            $html .= $logged_out_msg;
            if ($login_url) $html .= '</a>';
        endif;
        
    }
    
	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_friends', $before, $after, $styles, $values);

	return $html;
}

function cpc_friends_pending($atts) {

	// Init
	add_action('wp_footer', 'cpc_friends_init');

	$html = '';
	global $current_user;

	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_friends_pending');
	extract( shortcode_atts( array(
        'user_id' => false,
		'count' => cpc_get_shortcode_value($values, 'cpc_friends_pending-count', 10),
		'size' => cpc_get_shortcode_value($values, 'cpc_friends_pending-size', 64),
		'link' => cpc_get_shortcode_value($values, 'cpc_friends_pending-link', true),
		'class' => cpc_get_shortcode_value($values, 'cpc_friends_pending-class', ''),
		'accept_request_label' => cpc_get_shortcode_value($values, 'cpc_friends_pending-accept_request_label', __('Akzeptieren', CPC2_TEXT_DOMAIN)),
		'reject_request_label' => cpc_get_shortcode_value($values, 'cpc_friends_pending-reject_request_label', __('Ablehnen', CPC2_TEXT_DOMAIN)),
		'none' => cpc_get_shortcode_value($values, 'cpc_friends_pending-none', ''),
		'before' => '',
		'styles' => true,
        'after' => '',
	), $atts, 'cpc_friends' ) );

    if (!$user_id) $user_id = cpc_get_user_id();

    if ($user_id):

		if (isset($_POST['cpc_friends_pending'])):

			if ($_POST['cpc_friends_pending'] == 'reject'):

				$post = get_post ($_POST['cpc_friends_post_id']);
				if ($post):
					$member1 = get_post_meta($post->ID, 'cpc_member1', true);
					$member2 = get_post_meta($post->ID, 'cpc_member2', true);
					if ($member1 == $current_user->ID || $member2 == $current_user->ID)
						wp_delete_post( $post->ID, true );
				endif;

			endif;		

		endif;

		if ($current_user->ID == $user_id):

			$args = array (
				'post_type'              => 'cpc_friendship',
				'posts_per_page'         => $count,
				'post_status'			 => 'pending',
				'meta_query' => array(
					array(
						'key'       => 'cpc_member2', // recipient of request is second user meta field
						'compare'   => '=',
						'value'     => $user_id,
					),
				),		
			);


			global $post;
			$loop = new WP_Query( $args );
			if ($loop->have_posts()) {
				$html .= '<div class="cpc_pending_friends">';
				while ( $loop->have_posts() ) : $loop->the_post();
					$member1 = get_post_meta( $post->ID, 'cpc_member1', true );
	                
	                $html .= '<div class="cpc_pending_friends_friend">';
	                    if ($size):
	                        $html .= '<div class="cpc_pending_friends_friend_avatar">';
	                            $html .= cpc_friend_avatar($member1, $size, $link);
	                        $html .= '</div>';
	                    endif;
	                    $html .= '<div class="cpc_pending_friends_friend_display_name">';
	                        $html .= cpc_display_name(array('user_id'=>$member1, 'link'=>$link));
	                        $html .= '<div class="cpc_pending_friends_accept_reject">';
	                        $html .= '<button type="submit" rel="'.$post->ID.'" class="cpc_button cpc_pending_friends_accept '.$class.'">'.$accept_request_label.'</button>';
	                        $html .= '<button type="submit" rel="'.$post->ID.'" class="cpc_button cpc_pending_friends_reject '.$class.'">'.$reject_request_label.'</button>';
	                        $html .= '<input type="hidden" id="plugins_url" value="'.plugins_url( '', __FILE__ ).'" />';
	                        $html .= '</div>';
	                    $html .= '</div>';
	                $html .= '</div>';

				endwhile; 
				$html .= '</div>';		
			} else {
				$html .= $none;
			}
			wp_reset_query();

			if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_friends_pending', $before, $after, $styles, $values);
	    
		endif;

	endif;
	
	return $html;

}

function cpc_friends_count($atts) {

    // Init
    add_action('wp_footer', 'cpc_friends_init');

    $html = '';
    global $current_user;

    // Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_friends_count');        
    extract( shortcode_atts( array(
    	'user_id' => cpc_get_shortcode_value($values, 'cpc_friends_count-user_id', ''),
        'status' => cpc_get_shortcode_value($values, 'cpc_friends_count-status', 'accepted'),
        'url' => cpc_get_shortcode_value($values, 'cpc_friends_count-url', ''),
        'before' => '',
        'styles' => true,
        'after' => '',
    ), $atts, 'cpc_friends_count' ) );    
    
    $html = '';

    if (is_user_logged_in()) {	

		if (!$user_id) {
			$user_id = cpc_get_user_id();
		} else {
			if ($user_id == 'user') $user_id = $current_user->ID;
		}

		if ($status == 'accepted'):
	    	$friends = cpc_get_friends($user_id, false);
	    else:
	    	$friends = cpc_get_pending_friends($user_id, false);
	    endif;
        if ($url) $html .= '<a class="cpc_friends_count_link" href="'.$url.cpc_query_mark($url).'user_id='.$user_id.'">';
    	$html .= '<span class="cpc_friends_count">'.count($friends).'</span>';
        if ($url) $html .= '</a>';

        if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_friends_count', $before, $after, $styles, $values);

    }
    
    return $html;

}

function cpc_alerts_friends($atts) {

    // Init
    add_action('wp_footer', 'cpc_friends_init');

    $html = '';
    global $current_user;

    if (is_user_logged_in()) {	
        
        // Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_alerts_friends');        
        extract( shortcode_atts( array(
            'flag_size' => cpc_get_shortcode_value($values, 'cpc_alerts_friends-flag_size', 24),
            'flag_pending_size' => cpc_get_shortcode_value($values, 'cpc_alerts_friends-flag_pending_size', 10),
            'flag_pending_top' => cpc_get_shortcode_value($values, 'cpc_alerts_friends-flag_pending_top', 6),
            'flag_pending_left' => cpc_get_shortcode_value($values, 'cpc_alerts_friends-flag_pending_left', 8),
            'flag_pending_radius' => cpc_get_shortcode_value($values, 'cpc_alerts_friends-flag_pending_radius', 8),
            'flag_url' => cpc_get_shortcode_value($values, 'cpc_alerts_friends-flag_url', ''),
            'flag_src' => cpc_get_shortcode_value($values, 'cpc_alerts_friends-flag_src', ''),
            'before' => '',
            'styles' => true,
            'after' => '',
        ), $atts, 'cpc_alerts_friends' ) );

        $args = array (
            'post_type'              => 'cpc_friendship',
            'posts_per_page'         => -1,
            'post_status'			 => 'pending',
            'meta_query' => array(
                array(
                    'key'       => 'cpc_member2', // recipient of request is second user meta field
                    'compare'   => '=',
                    'value'     => $current_user->ID
                ),
            ),		
        );


        global $post;
        $loop = new WP_Query( $args );
        $unread_count = $loop->found_posts;

        wp_reset_query();

        $html .= '<div id="cpc_alerts_friends_flag" style="width:'.$flag_size.'px; height:'.$flag_size.'px;" >';
        $html .= '<a href="'.$flag_url.'">';
        $src = (!$flag_src) ? plugins_url('images/friends'.get_option('cpccom_flag_colors').'.png', __FILE__) : $flag_src;
        $html .= '<img style="width:'.$flag_size.'px; height:'.$flag_size.'px;" src="'.$src.'" />';
        if ($unread_count):
            $html .= '<div id="cpc_alerts_friends_flag_unread" style="position: absolute; padding-top: '.($flag_pending_size*0.2).'px; line-height:'.($flag_pending_size*0.8).'px; font-size:'.($flag_pending_size*0.8).'px; border-radius: '.$flag_pending_radius.'px; top:'.$flag_pending_top.'px; left:'.$flag_pending_left.'px; width:'.$flag_pending_size.'px; height:'.$flag_pending_size.'px;">'.$unread_count.'</div>';
        endif;
        $html .= '</a></div>';
        if (!$flag_url) $html .= '<div class="cpc_error">'.__('Lege flag_url in PS Community->Setup->Standard-Shortcode-Einstellungen (Freunde) oder im Shortcode fest, im Shortcode für den Link, wahrscheinlich auf die Seite mit [cpc-friends] darauf.', CPC2_TEXT_DOMAIN).'</div>';
        
        if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_alerts_friends', $before, $after, $styles, $values);

    }

    return $html;
    
}


if (!is_admin()) add_shortcode(CPC_PREFIX.'-friends', 'cpc_friends');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-friends-status', 'cpc_friends_status');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-friends-pending', 'cpc_friends_pending');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-friends-add-button', 'cpc_friends_add_button');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-friends-count', 'cpc_friends_count');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-alerts-friends', 'cpc_alerts_friends');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-favourite-friend', 'cpc_favourite_friend');


?>