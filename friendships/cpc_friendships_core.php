<?php
/* ******** */ /*   AJAX   */ /* ******** */
if (is_admin()) add_action('admin_enqueue_scripts', 'cpc_friendships_admin_init');
function cpc_friendships_admin_init() {
	wp_enqueue_script('cpc-friendship-js', plugins_url('cpc_friends.js', __FILE__), array('jquery'));	
    wp_localize_script('cpc-friendship-js', 'cpc_friendships_ajax', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'nonce' => wp_create_nonce('cpc-friendship-nonce')
    ));
}


add_action( 'wp_ajax_nopriv_cpc_get_users', 'cpc_get_users_ajax' ); // Logged out
add_action( 'wp_ajax_cpc_get_users', 'cpc_get_users_ajax' ); // Logged in 

function cpc_friendships_ajax_rate_limited($scope, $max_requests = 30, $window_seconds = 60) {
    $user_part = is_user_logged_in() ? ('u:' . get_current_user_id()) : ('ip:' . md5(isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'guest'));
    $key = 'cpc_rl_fr_' . md5($scope . '|' . $user_part);
    $count = (int) get_transient($key);
    if ($count >= $max_requests) {
        return true;
    }
    set_transient($key, $count + 1, $window_seconds);
    return false;
}

function cpc_get_blocked_users($user_id) {
    $blocked = get_user_meta((int)$user_id, 'cpc_blocked_users', true);
    if (!is_array($blocked)) {
        return array();
    }

    $blocked = array_map('absint', $blocked);
    $blocked = array_values(array_unique(array_filter($blocked)));
    return $blocked;
}

function cpc_set_blocked_users($user_id, $blocked) {
    $user_id = (int)$user_id;
    $blocked = is_array($blocked) ? $blocked : array();
    $blocked = array_map('absint', $blocked);
    $blocked = array_values(array_unique(array_filter($blocked, function($id) use ($user_id) {
        return $id > 0 && $id !== $user_id;
    })));

    if (empty($blocked)) {
        delete_user_meta($user_id, 'cpc_blocked_users');
    } else {
        update_user_meta($user_id, 'cpc_blocked_users', $blocked);
    }
}

function cpc_is_user_blocked($user_id, $target_user_id) {
    $blocked = cpc_get_blocked_users((int)$user_id);
    return in_array((int)$target_user_id, $blocked, true);
}

function cpc_is_blocked_either_direction($user_a, $user_b) {
    $user_a = (int)$user_a;
    $user_b = (int)$user_b;
    if ($user_a <= 0 || $user_b <= 0 || $user_a === $user_b) {
        return false;
    }

    return cpc_is_user_blocked($user_a, $user_b) || cpc_is_user_blocked($user_b, $user_a);
}

function cpc_remove_friendships_between_users($user_a, $user_b) {
    $user_a = (int)$user_a;
    $user_b = (int)$user_b;
    if ($user_a <= 0 || $user_b <= 0 || $user_a === $user_b) {
        return;
    }

    global $wpdb;
    $sql = "SELECT p.ID FROM {$wpdb->prefix}posts p
            INNER JOIN {$wpdb->prefix}postmeta m1 ON m1.post_id = p.ID AND m1.meta_key = 'cpc_member1'
            INNER JOIN {$wpdb->prefix}postmeta m2 ON m2.post_id = p.ID AND m2.meta_key = 'cpc_member2'
            WHERE p.post_type = 'cpc_friendship'
              AND p.post_status IN ('pending','publish')
              AND ((m1.meta_value = %d AND m2.meta_value = %d) OR (m1.meta_value = %d AND m2.meta_value = %d))";
    $friendship_ids = $wpdb->get_col($wpdb->prepare($sql, $user_a, $user_b, $user_b, $user_a));
    if (!empty($friendship_ids)) {
        foreach ($friendship_ids as $friendship_id) {
            wp_delete_post((int)$friendship_id, true);
        }
    }

    $fav_sql = "SELECT p.ID FROM {$wpdb->prefix}posts p
                INNER JOIN {$wpdb->prefix}postmeta m1 ON m1.post_id = p.ID AND m1.meta_key = 'cpc_favourite_member1'
                INNER JOIN {$wpdb->prefix}postmeta m2 ON m2.post_id = p.ID AND m2.meta_key = 'cpc_favourite_member2'
                WHERE p.post_type = 'cpc_favourite_friend'
                  AND p.post_status IN ('pending','publish')
                  AND ((m1.meta_value = %d AND m2.meta_value = %d) OR (m1.meta_value = %d AND m2.meta_value = %d))";
    $fav_ids = $wpdb->get_col($wpdb->prepare($fav_sql, $user_a, $user_b, $user_b, $user_a));
    if (!empty($fav_ids)) {
        foreach ($fav_ids as $fav_id) {
            wp_delete_post((int)$fav_id, true);
        }
    }
}

add_action('wp_ajax_cpc_friends_block', 'cpc_friends_block');
function cpc_friends_block() {
    check_ajax_referer('cpc-friendship-nonce', 'security');
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'not_logged_in'), 403);
    }

    $target_user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    $current_user_id = get_current_user_id();
    if ($target_user_id <= 0 || $target_user_id === $current_user_id) {
        wp_send_json_error(array('message' => 'invalid_user'), 400);
    }

    $blocked = cpc_get_blocked_users($current_user_id);
    if (!in_array($target_user_id, $blocked, true)) {
        $blocked[] = $target_user_id;
    }
    cpc_set_blocked_users($current_user_id, $blocked);

    cpc_remove_friendships_between_users($current_user_id, $target_user_id);
    wp_send_json_success(array('status' => 'blocked'));
}

add_action('wp_ajax_cpc_friends_unblock', 'cpc_friends_unblock');
function cpc_friends_unblock() {
    check_ajax_referer('cpc-friendship-nonce', 'security');
    if (!is_user_logged_in()) {
        wp_send_json_error(array('message' => 'not_logged_in'), 403);
    }

    $target_user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    $current_user_id = get_current_user_id();
    if ($target_user_id <= 0 || $target_user_id === $current_user_id) {
        wp_send_json_error(array('message' => 'invalid_user'), 400);
    }

    $blocked = cpc_get_blocked_users($current_user_id);
    $blocked = array_values(array_filter($blocked, function($id) use ($target_user_id) {
        return (int)$id !== (int)$target_user_id;
    }));
    cpc_set_blocked_users($current_user_id, $blocked);

    wp_send_json_success(array('status' => 'unblocked'));
}

function cpc_privacy_register_friendship_exporter($exporters) {
    $exporters['cpc-friendships-blocked-users'] = array(
        'exporter_friendly_name' => __('PS Community blockierte Benutzer', CPC2_TEXT_DOMAIN),
        'callback' => 'cpc_privacy_friendship_exporter_callback',
    );
    return $exporters;
}
add_filter('wp_privacy_personal_data_exporters', 'cpc_privacy_register_friendship_exporter');

function cpc_privacy_friendship_exporter_callback($email_address, $page = 1) {
    $user = get_user_by('email', $email_address);
    if (!$user) {
        return array('data' => array(), 'done' => true);
    }

    $blocked_ids = cpc_get_blocked_users((int)$user->ID);
    $blocked_text = array();
    foreach ($blocked_ids as $blocked_id) {
        $blocked_user = get_user_by('id', $blocked_id);
        $blocked_text[] = $blocked_user
            ? sprintf('%s (%d)', $blocked_user->user_login, $blocked_id)
            : sprintf('%d', $blocked_id);
    }

    $data = array();
    if (!empty($blocked_text)) {
        $data[] = array(
            'group_id' => 'cpc_friendships',
            'group_label' => __('PS Community Freundschaften', CPC2_TEXT_DOMAIN),
            'item_id' => 'cpc_blocked_users',
            'data' => array(
                array(
                    'name' => __('Blockierte Benutzer', CPC2_TEXT_DOMAIN),
                    'value' => implode(', ', $blocked_text),
                ),
            ),
        );
    }

    return array(
        'data' => $data,
        'done' => true,
    );
}

function cpc_privacy_register_friendship_eraser($erasers) {
    $erasers['cpc-friendships-blocked-users'] = array(
        'eraser_friendly_name' => __('PS Community blockierte Benutzer', CPC2_TEXT_DOMAIN),
        'callback' => 'cpc_privacy_friendship_eraser_callback',
    );
    return $erasers;
}
add_filter('wp_privacy_personal_data_erasers', 'cpc_privacy_register_friendship_eraser');

function cpc_privacy_friendship_eraser_callback($email_address, $page = 1) {
    $user = get_user_by('email', $email_address);
    if (!$user) {
        return array(
            'items_removed' => false,
            'items_retained' => false,
            'messages' => array(),
            'done' => true,
        );
    }

    $had_items = !empty(cpc_get_blocked_users((int)$user->ID));
    delete_user_meta((int)$user->ID, 'cpc_blocked_users');

    return array(
        'items_removed' => $had_items,
        'items_retained' => false,
        'messages' => array(),
        'done' => true,
    );
}

function cpc_get_users_ajax() {

    check_ajax_referer('cpc-friendship-nonce', 'security');
    if (!is_user_logged_in() && cpc_friendships_ajax_rate_limited('get_users', 20, 60)) {
        wp_send_json_error(array());
    }

	global $wpdb;
    $term = isset($_POST['term']) ? sanitize_text_field(wp_unslash($_POST['term'])) : '';
    if (mb_strlen($term) < 2) {
        echo json_encode(array());
        exit;
    }
    $sql = "SELECT ID, user_login FROM ".$wpdb->base_prefix."users WHERE user_login like '%%%s%%' ORDER BY user_login LIMIT 20";
	$rows = $wpdb->get_results($wpdb->prepare($sql, $term));

	$return_arr = array();
	foreach ($rows as $row) {
	    $row_array['value'] = $row->user_login;
	    $row_array['label'] = $row->user_login;
	    array_push($return_arr,$row_array);
	}
	echo json_encode($return_arr);	
	exit;

}

add_action( 'wp_ajax_cpc_friends_add', 'cpc_friends_add' ); // Logged in 
function cpc_friends_add() {

	// CSRF-Schutz
	check_ajax_referer('cpc-friendship-nonce', 'security');

    $user_id = absint($_POST['user_id']);
    $request_message = isset($_POST['request_message']) ? sanitize_textarea_field(wp_unslash($_POST['request_message'])) : '';
    if (function_exists('mb_substr')) {
        $request_message = trim(mb_substr($request_message, 0, 500));
    } else {
        $request_message = trim(substr($request_message, 0, 500));
    }
	global $current_user;

	if ($user_id != $current_user->ID):

        if (cpc_is_blocked_either_direction($current_user->ID, $user_id)) {
            echo 'blocked';
            exit;
        }

		$friends = cpc_are_friends($current_user->ID, $user_id);
		if (!$friends['status']):

			// Create post object
			$user = get_user_by('id', $user_id);
			$my_post = array(
		    	'post_title' 	=> $current_user->user_login.' - '.$user->user_login,
		    	'post_name'	=> sanitize_title_with_dashes($current_user->user_login.' '.$user->user_login),
		    	'post_type'	=> 'cpc_friendship',
		    	'post_status'	=> 'pending'
			);

			// Insert the post into the database
			if ($post_id = wp_insert_post( $my_post )):

				// Update meta data
				update_post_meta( $post_id, 'cpc_member1', $current_user->ID );
				update_post_meta( $post_id, 'cpc_member2', $user_id );
				// Since date, as from pending (until accepted/rejected)
				update_post_meta( $post_id, 'cpc_friendship_since', date('Y-m-d H:i:s') );
                if ($request_message !== '') {
                    update_post_meta($post_id, 'cpc_friendship_message', $request_message);
                }

				// Add alert
				$subject = __('Neue Freundschaftsanfrage', CPC2_TEXT_DOMAIN);
				$subject = get_bloginfo('name').': '.$subject;

				$content = '';

				$content = apply_filters( 'cpc_alert_before', $content );
		
				$content .= '<h1>'.$user->display_name.'</h1>';

				$msg = sprintf(__('Freundschaftsanfrage von %s.', CPC2_TEXT_DOMAIN), $current_user->display_name);
				$content .= '<p>'.$msg.'</p>';

				$url = get_permalink(get_option('cpccom_profile_page'));

				$content .= '<p><a href="'.$url.'">'.$url.'</a></p>';

				$content = apply_filters( 'cpc_alert_after', $content );

				if (function_exists('cpc_com_insert_alert')) cpc_com_insert_alert('friendship', $subject, $content, $current_user->ID, $user->ID, '', $url, $msg, 'pending', 'Chosen not to receive friendship requests by email');

				echo $post_id;

			else:

				echo false;

			endif;

		endif;

	endif;

}

// Remove all friends
add_action( 'wp_ajax_cpc_remove_all_friends', 'cpc_remove_all_friends' ); // Logged in 
function cpc_remove_all_friends() {

	// CSRF-Schutz
	check_ajax_referer('cpc-friendship-nonce', 'security');

	global $wpdb;
	if (is_user_logged_in()):    

        $sql = "SELECT post_id FROM ".$wpdb->prefix."postmeta
                WHERE (meta_key='cpc_member1' AND meta_value=%d)
                   OR (meta_key='cpc_member2' AND meta_value=%d)";
        $friendships = $wpdb->get_col($wpdb->prepare($sql, get_current_user_id(), get_current_user_id()));
        foreach ($friendships as $friendship):
            $sql = "DELETE FROM ".$wpdb->prefix."posts WHERE ID = %d";
            $wpdb->query($wpdb->prepare($sql, $friendship));
        endforeach;
        
        echo 'ok';

    endif;
    
    exit();

}

// Reject friendship
add_action( 'wp_ajax_cpc_friends_reject', 'cpc_friends_reject' ); // Logged in 
function cpc_friends_reject() {

	// CSRF-Schutz
	check_ajax_referer('cpc-friendship-nonce', 'security');

	global $current_user;
	$post = get_post(absint($_POST['post_id']));
	if ($post):
		$member1 = get_post_meta ($post->ID, 'cpc_member1', true);
		$member2 = get_post_meta ($post->ID, 'cpc_member2', true);

		if ($member1 == $current_user->ID || $member2 == $current_user->ID) {
			wp_delete_post( absint($_POST['post_id']), true );
		}
		echo 'ok';
	else:
		echo 'Post not found: ' . absint($_POST['post_id']);
	endif;

}

// Accept friendship
add_action( 'wp_ajax_cpc_friends_accept', 'cpc_friends_accept' ); // Logged in 
function cpc_friends_accept() {

	// CSRF-Schutz
	check_ajax_referer('cpc-friendship-nonce', 'security');

	global $current_user;
	$post = get_post(absint($_POST['post_id']));
	$member1 = get_post_meta ($post->ID, 'cpc_member1', true);
	$member2 = get_post_meta ($post->ID, 'cpc_member2', true);

	if ($member1 == $current_user->ID || $member2 == $current_user->ID):

        if (cpc_is_blocked_either_direction($member1, $member2)) {
            wp_delete_post(absint($_POST['post_id']), true);
            echo 'blocked';
            exit;
        }

		$my_post = array(
			'ID'           => $_POST['post_id'],
			'post_status' => 'publish',
		);

		wp_update_post( $my_post );

		// Add alert
		$subject = __('Freundschaftsanfrage angenommen', CPC2_TEXT_DOMAIN);
		$subject = get_bloginfo('name').': '.$subject;

		$content = '';

		$content = apply_filters( 'cpc_alert_before', $content );

		if ($member1 == $current_user->ID):
			$accepted = get_user_by('id', $member1);
			$sent = get_user_by('id', $member2);
		else:
			$sent = get_user_by('id', $member1);
			$accepted = get_user_by('id', $member2);
		endif;
		$content .= '<h1>'.$sent->display_name.'</h1>';

		$msg = sprintf(__('Freundschaftsanfrage von %s angenommen.', CPC2_TEXT_DOMAIN), $accepted->display_name);
		$content .= '<p>'.$msg.'</p>';

		$permalink = get_permalink(get_option('cpccom_profile_page'));
		if (get_option('cpccom_profile_permalinks')):
            $parameters = cpc_query_mark($permalink).'user_id='.$accepted->ID;
        else:
            $parameters = sprintf('%s', $accepted->user_login);
        endif;
        
		$url = $permalink.$parameters;

		$content .= '<p><a href="'.$url.'">'.$url.'</a></p>';

		$content = apply_filters( 'cpc_alert_after', $content );

		// Add alert
		if (function_exists('cpc_com_insert_alert')) cpc_com_insert_alert('friendship', $subject, $content, $accepted->ID, $sent->ID, '', $url, $msg, 'pending', 'Chosen not to receive friendship acceptances by email');

		// Any further actions?
		do_action( 'cpc_friends_accept_hook', $sent->ID, $accepted->ID );

	endif;

}

/* ********* */ /* FUNCTIONS */ /* ********* */

function cpc_friend_avatar($id, $avatar_size, $link) {

    if (strpos(CPC_CORE_PLUGINS, 'core-avatar') !== false):
        if ($link && strpos(CPC_CORE_PLUGINS, 'core-profile') !== false):
            return '<a href="'.get_page_link(get_option('cpccom_profile_page')).'?user_id='.$id.'">'.user_avatar_get_avatar( $id, $avatar_size, true, 'thumb' ).'</a>';
        else:
            return user_avatar_get_avatar( $id, $avatar_size );
        endif;
    else:
        return get_avatar( $id, $avatar_size );
    endif;

}

function cpc_get_friends($user_id, $array) {

    $friends = array();
    
    if (!get_option('cpc_friendships_all')):
        $args = array (
            'post_type'              => 'cpc_friendship',
            'post_status'			 => array( 'publish' ),
            'posts_per_page'         => '1000',
            'meta_query'             => array(
                'relation'		 => 'OR',
                array(
                    'key'       => 'cpc_member1',
                    'compare'   => '=',
                    'value'     => $user_id,
                ),
                array(
                    'key'       => 'cpc_member2',
                    'compare'   => '=',
                    'value'     => $user_id,
                ),
            ),
        );
        $loop = new WP_Query( $args );

        global $post;
        if ($loop->have_posts()) {
            while ( $loop->have_posts() ) : $loop->the_post();
                $member1 = get_post_meta( $post->ID, 'cpc_member1', true );
                $member2 = get_post_meta( $post->ID, 'cpc_member2', true );
                $other_member = ($member1 == $user_id) ? $member2 : $member1;
				$skip_blocked = cpc_is_blocked_either_direction($user_id, $other_member);
                if ((!$array || in_array($other_member, $array)) && !$skip_blocked)
                	array_push($friends, array('ID' => $other_member));
            endwhile;
        }
        wp_reset_query();
    else:
        global $wpdb;
        $sql = "SELECT ID FROM ".$wpdb->base_prefix."users WHERE ID != %d";
        $loop = $wpdb->get_results($wpdb->prepare($sql, $user_id));
        if ($loop) {
            foreach ($loop as $u):
                array_push($friends, array('ID' => $u->ID));
            endforeach;
        }
    endif;

	return $friends;

}

function cpc_get_pending_friends($user_id, $array) {

    $friends = array();
    
    if (!get_option('cpc_friendships_all')):
        $args = array (
            'post_type'              => 'cpc_friendship',
            'post_status'			 => array( 'pending' ),
            'posts_per_page'         => '1000',
            'meta_query'             => array(
                array(
                    'key'       => 'cpc_member2',
                    'compare'   => '=',
                    'value'     => $user_id,
                ),
            ),
        );
        $loop = new WP_Query( $args );

        global $post;
        if ($loop->have_posts()) {
            while ( $loop->have_posts() ) : $loop->the_post();
                $member1 = get_post_meta( $post->ID, 'cpc_member1', true );
                $member2 = get_post_meta( $post->ID, 'cpc_member2', true );
                $other_member = ($member1 == $user_id) ? $member2 : $member1;
				$skip_blocked = cpc_is_blocked_either_direction($user_id, $other_member);
                if ((!$array || in_array($other_member, $array)) && !$skip_blocked)
                	array_push($friends, array('ID' => $other_member));
            endwhile;
        }
        wp_reset_query();
    else:
        global $wpdb;
        $sql = "SELECT ID FROM ".$wpdb->base_prefix."users WHERE ID != %d";
        $loop = $wpdb->get_results($wpdb->prepare($sql, $user_id));
        if ($loop) {
            foreach ($loop as $u):
                array_push($friends, array('ID' => $u->ID));
            endforeach;
        }
    endif;

	return $friends;

}

function cpc_is_a_favourite_friend($user_id, $user_id_to_check) {

    if ($user_id == $user_id_to_check):

        $ret = array("ID"=>0, "status"=>'publish');

    else:

        global $wpdb;

        $sql = "SELECT p.ID, p.post_status, m1.meta_value as cpc_favourite_member1, m2.meta_value as cpc_favourite_member2 FROM ".$wpdb->prefix."posts p 
        LEFT JOIN ".$wpdb->prefix."postmeta m1 ON m1.post_id = p.ID
        LEFT JOIN ".$wpdb->prefix."postmeta m2 ON m2.post_id = p.ID
        WHERE p.post_type = 'cpc_favourite_friend'
          AND (p.post_status = 'pending' OR p.post_status = 'publish')
          AND (m1.meta_key = 'cpc_favourite_member1' AND m2.meta_key = 'cpc_favourite_member2')
          AND (m1.meta_value = %d AND m2.meta_value = %d)";

        $friendship = $wpdb->get_row($wpdb->prepare($sql, $user_id, $user_id_to_check));
        if ($friendship):
            $ret = array("ID"=>$friendship->ID, "status"=>$friendship->post_status);
        else:
            $ret = array("ID"=>0, "status"=>false, "direction"=>false);
        endif;

    endif;
    
    return $ret;

}

function cpc_are_friends($user_id, $user_id_to_check) {

    if (!get_option('cpc_friendships_all')):
    
        if ($user_id == $user_id_to_check):

            return array("ID"=>0, "status"=>'publish');

        else:

            global $wpdb;

            $sql = "SELECT p.ID, p.post_status, m1.meta_value as cpc_member1, m2.meta_value as cpc_member2 FROM ".$wpdb->prefix."posts p 
            LEFT JOIN ".$wpdb->prefix."postmeta m1 ON m1.post_id = p.ID
            LEFT JOIN ".$wpdb->prefix."postmeta m2 ON m2.post_id = p.ID
            WHERE p.post_type = 'cpc_friendship'
              AND (p.post_status = 'pending' OR p.post_status = 'publish')
              AND (m1.meta_key = 'cpc_member1' AND m2.meta_key = 'cpc_member2')
              AND (m1.meta_value = %d OR m2.meta_value = %d)";

            $friendships = $wpdb->get_results($wpdb->prepare($sql, $user_id, $user_id));
            if ($friendships):
                foreach ($friendships as $friendship):
                    if (
                        ($friendship->cpc_member1 == $user_id && $friendship->cpc_member2 == $user_id_to_check) ||
                        ($friendship->cpc_member2 == $user_id && $friendship->cpc_member1 == $user_id_to_check)
                        ):
                        $direction = ($friendship->cpc_member1 == $user_id) ? 'to' : 'from';
                        return array("ID"=>$friendship->ID, "status"=>$friendship->post_status, "direction"=>$direction);
                        break;
                    endif;				
                endforeach;
            endif;

            return array("ID"=>0, "status"=>false, "direction"=>false);

        endif;
    
    else:
    
        return array("ID"=>0, "status"=>"publish", "direction"=>"to");
    
    endif;

}

add_filter( 'wp_nav_menu_items', 'cpc_pending_friends' );
function cpc_pending_friends($items){ 

    global $wpdb, $current_user;
    if (is_user_logged_in()):
    
        $pending_friendships = cpc_get_pending_friends($current_user->ID, false);
        $pending_friendships = count($pending_friendships);
     
        if ($pending_friendships > 0) {
            $items = str_replace("%f", " <span class='cpc_pending_friends_count'>(".$pending_friendships.")</span>", $items);
        } else {
            $items = str_replace("%f", "", $items);
        }

    else:

        $items = str_replace("%f", "", $items);

    endif;

    return $items;
    
}      
?>