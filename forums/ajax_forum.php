<?php
// AJAX functions for activity
add_action( 'wp_ajax_cpc_forum_closed_switch', 'cpc_forum_closed_switch' ); 
add_action( 'wp_ajax_cpc_forum_comment_reopen', 'cpc_forum_comment_reopen' ); 
add_action( 'wp_ajax_cpc_forum_add_subcomment', 'cpc_forum_add_subcomment' ); 
add_action( 'wp_ajax_cpc_forum_post_add_ajax_hook', 'cpc_forum_post_add_ajax_hook' ); 

/* HOOK FOR ADD NEW POST */
function cpc_forum_post_add_ajax_hook() {
    do_action( 'cpc_forum_post_add_subs_hook', $_POST, $_POST['post_id'] );
    exit;
}

/* SAVE COMMENT (TO REPLY) */
function cpc_forum_add_subcomment() {

	global $wpdb,$current_user;

	$the_comment = $_POST;

    $the_content = esc_html($the_comment['comment']);
    $the_content = preg_replace('/\t/', '', $the_content);
    
	$data = array(
	    'comment_post_ID' => $the_comment['post_id'],
	    'comment_content' => $the_content,
	    'comment_type' => 'cpc_forum_comment',
	    'comment_parent' => $the_comment['comment_id'],
	    'comment_author' => $current_user->user_login,
	    'comment_author_email' => $current_user->user_email,
	    'user_id' => $current_user->ID,
	    'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
	    'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
	    'comment_approved' => 1,
	);

	$new_id = wp_new_comment($data); // sanitises

	if ($new_id):

        // Check if parent is private, and copy if so
        $private = get_comment_meta( $the_comment['comment_id'], 'cpc_private_post', true );
        if ($private)
            update_comment_meta($new_id, 'cpc_private_post', true);
    
        // Reset read flags (apart from current user)
        // If not private, reset whole post to unread
        /*
        if (!$private) {
            $read = array();
            $read[] = $current_user->ID;
            update_comment_meta ( $the_comment['comment_id'], 'cpc_forum_read', $read);
        } else {
            // ... otherwise get the author of the reply and those who commented on it, and remove them from the read list
            $read = get_post_meta ($the_comment['post_id'], 'cpc_forum_read', true);
            // ... remove original reply author fromm read array
            $the_reply = get_comment($the_comment['post_id']);
            if(($key = array_search($the_reply->user_id, $read)) !== false) {
                unset($read[$key]);
            }
            // ... get author of comments to this reply and remove from read array
            $sql = "SELECT * FROM ".$wpdb->prefix."comments WHERE comment_parent = %d";
            $comments = $wpdb->get_results($wpdb->prepare($sql, $the_comment['comment_id']));
            if ($comments): 
                foreach ($comments as $comment):
                    if(($key = array_search($comment->user_id, $read)) !== false) {
                        unset($read[$key]);
                    }    
                endforeach;
            endif;
            update_comment_meta ( $the_comment['comment_id'], 'cpc_forum_read', $read);
            var_dump($read);
        }
        */

		// Any further actions?
		do_action( 'cpc_forum_comment_add_hook', $the_comment, $_FILES, $the_comment['post_id'], $new_id );

		// HTML to show
		$sub_comment_html = '<div class="cpc_forum_post_subcomment" style="display:none; padding-left: '.$the_comment['size'].'px;">';

			$sub_comment_html .= '<div class="cpc_forum_post_comment_author" style="max-width: '.$the_comment['size'].'px; margin-left: -'.$the_comment['size'].'px;">';
				$sub_comment_html .= '<div class="cpc_forum_post_comment_author_avatar">';
                    if (strpos(CPC_CORE_PLUGINS, 'core-avatar') !== false):
					   $sub_comment_html .= user_avatar_get_avatar( $current_user->ID, $the_comment['size'], true, 'thumb' );
                    else:
                        $sub_comment_html .= get_avatar( $current_user->ID, $the_comment['size'] );
                    endif;
				$sub_comment_html .= '</div>';
			$sub_comment_html .= '</div>';

			$sub_comment_html .= '<div class="cpc_forum_post_comment_content">';

                $sub_comment_content = convert_smilies(cpc_make_clickable(wpautop($the_content)));

                $sub_comment_author = '<div class="cpc_forum_post_comment_author_display_name">';
					$sub_comment_author .= cpc_display_name(array('user_id'=>$current_user->ID, 'link'=>1));
				$sub_comment_author .= '</div>';

				$sub_comment_html .= $sub_comment_author . $sub_comment_content;

			$sub_comment_html .= '</div>';

		$sub_comment_html .= '</div>';

        echo stripslashes($sub_comment_html);
    
    else:

		echo 0;

	endif;

	exit;
}

/* REOPEN COMMENT */
function cpc_forum_comment_reopen() {

	global $current_user;
	$the_post = $_POST;

	$my_post = array(
	      'ID'           	=> $the_post['post_id'],
	      'comment_status' 	=> 'open',
	);
	wp_update_post( $my_post );

	// Add re-opened flag/datetime
	update_post_meta($the_post['post_id'], 'cpc_reopened_date', date('Y-m-d H:i:s'));

	// Any further actions?
	do_action( 'cpc_forum_post_reopen_hook', $the_post, $_FILES, $the_post['post_id'] );

}

/* SAVE CLOSED SWITCH STATE FOR USER */
function cpc_forum_closed_switch() {

	global $current_user;
	if (is_user_logged_in()) update_user_meta($current_user->ID, 'forum_closed_switch', $_POST['state']);

}

?>
