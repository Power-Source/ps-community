<?php

// Filter the menu to make profile page include current user
// Works with WP nav menu, if using other menu would have to create similar filter
add_filter( 'wp_nav_menu_items', 'cpc_activity_add_username' );
function cpc_activity_add_username($items){ 

    global $current_user;
    if (is_user_logged_in()):

        $url = get_page_link(get_option('cpccom_profile_page'));
        if (!get_option('cpccom_profile_permalinks')):
            $profile_page = get_post(get_option('cpccom_profile_page'));
            $slug = '/'.$profile_page->post_name.'/';
            $items = str_replace($slug, $slug.$current_user->user_login, $items);
        else:
            $items = str_replace($url, $url.cpc_query_mark($url).'user_id='.$current_user->ID, $items);
        endif;
    
    endif;

    return $items;
    
}   

/**
 * Sendet Benachrichtigungen für neue Beiträge zur Aktivität.
 *
 * @since 0.0.1
 *
 * @param array $the_post Die Daten des Beitrags.
 * @param array $the_files Die Dateianhänge des Beitrags.
 * @param int $new_id Die ID des neu hinzugefügten Beitrags.
 */
add_action( 'cpc_activity_post_add_hook', 'cpc_activity_post_add_alerts', 10, 3 );
function cpc_activity_post_add_alerts($the_post, $the_files, $new_id) {

	if (post_type_exists('cpc_alerts')):

		global $current_user;
	
		$recipients = array();
		$get_the_post = get_post($new_id);
		$get_recipient = $get_the_post ? get_user_by('id', $get_the_post->cpc_target) : false;
		if ($get_recipient && $get_the_post->cpc_target != $the_post['cpc_activity_post_author']) {
			// Einzelperson benachrichtigen
			array_push($recipients, $get_the_post->cpc_target);
		} else {
			// Überprüfen, ob Benachrichtigungen an alle Freunde gesendet werden sollen
			if (get_option('cpccom_all_friends_alerts')):
				global $current_user;	
				$friends = cpc_get_friends($current_user->ID, false);
				if ($friends):
					foreach ($friends as $friend):
						array_push($recipients, $friend['ID']);	
					endforeach;
				endif;
			endif;			
		}

		// Benachrichtigungen nur hinzufügen, wenn der Beitragstyp der Standardtyp ist
		if ($get_the_post && !$get_the_post->cpc_target_type):

			// Empfängerliste anpassen
			$recipients = apply_filters('cpc_activity_post_add_alerts_recipients_filter', $recipients, $the_post, $the_files, $new_id);

			if (post_type_exists('cpc_alerts') && count($recipients) > 0):

				$sent = array();
				foreach ($recipients as $target_id):

					if ( (int)$target_id != (int)$current_user->ID && !in_array($target_id, $sent) ):

						$status = 'publish';
						if (get_user_meta($target_id, 'cpc_activity_subscribe', true) != 'off') $status = 'pending';

						array_push($sent, $target_id);

						$title = get_bloginfo('name').': '.__('Neuer Aktivitätsbeitrag', CPC2_TEXT_DOMAIN);
						$content = '';

						$content = apply_filters( 'cpc_alert_before', $content );

						$recipient = get_user_by ('id', $target_id); // Get user by ID of post recipient
						$content .= '<h1>'.$recipient->display_name.'</h1>';

						$author = get_user_by('id', $the_post['cpc_activity_post_author']);
						$msg = sprintf(__('Du hast einen neuen Beitrag zu Deiner Aktivität von %s.', CPC2_TEXT_DOMAIN), $author->display_name);
						$content .= '<p>'.$msg.'</p>';
						$content .= '<p><em>'.$the_post['cpc_activity_post'].'</em></p>';
						
						if ( cpc_using_permalinks() ):	
							$u = get_user_by('id', $the_post['cpc_activity_post_author']);
							$parameters = sprintf('%s?view=%d', urlencode($u->user_login), $new_id);
							$permalink = get_permalink(get_option('cpccom_profile_page'));
							$url = $permalink.$parameters;
						else:
							$parameters = sprintf('user_id=%d&view=%d', urlencode($the_post['cpc_activity_post_author']), $new_id);
							$permalink = get_permalink(get_option('cpccom_profile_page'));
							$url = $permalink.'&'.$parameters;
						endif;
						$content .= '<p><a href="'.$url.'">'.$url.'</a></p>';

						$content = apply_filters( 'cpc_alert_after', $content );

						$post = array(
							'post_title'		=> $title,
							'post_excerpt'		=> $msg,
							'post_content'		=> $content,
						  	'post_status'   	=> $status,
						  	'post_type'     	=> 'cpc_alerts',
						  	'post_author'   	=> $the_post['cpc_activity_post_author'],
						  	'ping_status'   	=> 'closed',
						  	'comment_status'	=> 'closed',
                            'post_parent'       => $new_id
						);  
						$new_alert_id = wp_insert_post( $post );

						update_post_meta( $new_alert_id, 'cpc_alert_recipient', $recipient->user_login );	
						update_post_meta( $new_alert_id, 'cpc_alert_target', 'profile' );
						update_post_meta( $new_alert_id, 'cpc_alert_parameters', $parameters );	

						if ($status == 'publish'):
							update_post_meta( $new_alert_id, 'cpc_alert_failed_datetime', current_time('mysql', 1) );
							update_post_meta( $new_alert_id, 'cpc_alert_note', __('Ausgewählt, keine Aktivitätsbenachrichtigungen per E-Mail (Beitrag) zu erhalten #'.get_user_meta($target_id, 'cpc_activity_subscribe', true).'#', CPC2_TEXT_DOMAIN) );
						endif;

						do_action( 'cpc_alert_add_hook', $recipient->ID, $new_alert_id, $url, $msg );

					endif;

				endforeach;

			endif;

		endif;

	endif;

}

// Hook into cpc_activity_comment_add_hook to send alerts for new comments
// excluding the current user

add_action( 'cpc_activity_comment_add_hook', 'cpc_activity_comment_add_alerts', 10, 2 );
function cpc_activity_comment_add_alerts($the_post, $new_id) {

	if (post_type_exists('cpc_alerts')):

		// Get original post author
		$the_comment = get_comment($new_id);
		$post_id = $the_comment->comment_post_ID;
		$original_post = get_post($post_id);

		// alerts only added it activity type is default (ie. not set)
		// other types must add alerts themselves
		if (!$original_post->cpc_target_type):

			$recipients = array();

			// Add target of original post
			$target = get_post_meta($post_id, 'cpc_target', true);
			$get_recipient = get_user_by('id', $original_post->cpc_target);
			if ($get_recipient) {
				$recipients['target'] = $target;
			}

			// Any changes to recipients target list?
			$recipients = apply_filters('cpc_activity_comment_add_alerts_recipients_filter', $recipients, $original_post, $post_id, $new_id);

			// Add original post author and target
			$recipients['author'] = (int)$original_post->post_author;

			// Add all comment authors
			$args = array(
				'post_id' => $post_id
			);
			$comments = get_comments($args);
			if ($comments):
				foreach($comments as $comment):
					if ($comment->comment_author)
						$recipients['comment '.$comment->comment_ID] = (int)$comment->user_id;
				endforeach;
			endif;
    
			$sent = array();
			global $current_user;

			if ($recipients):
				foreach ($recipients as $key=>$value):

					if ($value):
    
						if ( (int)$value != (int)$current_user->ID && !in_array($value, $sent) ):

							$status = 'publish';
							if (get_user_meta($value, 'cpc_activity_subscribe', true) != 'off') $status = 'pending';

							array_push($sent, $value);

							if ($key == 'author'):
								$subject = __('Neuer Kommentar zu Deinem Beitrag', CPC2_TEXT_DOMAIN);
							else:
								$subject = __('Neuer Kommentar', CPC2_TEXT_DOMAIN);
							endif;
							$subject = get_bloginfo('name').': '.$subject;

							$content = '';

							$content = apply_filters( 'cpc_alert_before', $content );

							$target = get_user_by('id', $value);
							$content .= '<h1>'.$target->display_name.'</h1>';

							$author = get_user_by('login', $the_comment->comment_author);
							$msg = sprintf(__('Ein neuer Kommentar von %s.', CPC2_TEXT_DOMAIN), $author->display_name);
							$content .= '<p>'.$msg.'</p>';
							$content .= '<p><em>'.$the_comment->comment_content.'</em></p>';
    
							$parameters = sprintf('user_id=%d&view=%d', (int)$original_post->post_author, $post_id);
							$permalink = get_permalink(get_option('cpccom_profile_page'));
							$url = $permalink.cpc_query_mark($permalink).$parameters;
							$content .= '<p><a href="'.$url.'">'.$url.'</a></p>';

							$content .= '<p><strong>'.__('Ursprünglicher Beitrag', CPC2_TEXT_DOMAIN).'</strong></p>';
							$content .= '<p>'.$original_post->post_title.'</p>';

							$content = apply_filters( 'cpc_alert_after', $content );

							$post = array(
								'post_title'		=> $subject,
							  	'post_excerpt'		=> $msg,
							  	'post_content'		=> $content,
							  	'post_status'   	=> $status,
							  	'post_type'     	=> 'cpc_alerts',
							  	'post_author'   	=> (int)$the_comment->comment_author,
							  	'ping_status'   	=> 'closed',
							  	'comment_status'	=> 'closed',
                                'post_parent'       => $post_id
							);  
							$new_alert_id = wp_insert_post( $post );

							$recipient_user = get_user_by ('id', $value); // Get user by ID of email recipient
							update_post_meta( $new_alert_id, 'cpc_alert_recipient', $recipient_user->user_login );	
							update_post_meta( $new_alert_id, 'cpc_alert_target', 'profile' );
							update_post_meta( $new_alert_id, 'cpc_alert_parameters', $parameters );	

							if ($status == 'publish'):
								update_post_meta( $new_alert_id, 'cpc_alert_failed_datetime', current_time('mysql', 1) );
								if (isset($target_id)) update_post_meta( $new_alert_id, 'cpc_alert_note', __('Ausgewählt, keine Aktivitätsbenachrichtigungen per E-Mail zu erhalten (Kommentar) #'.get_user_meta($target_id, 'cpc_activity_subscribe', true).'#', CPC2_TEXT_DOMAIN) );
							endif;

							do_action( 'cpc_alert_add_hook', $target->ID, $new_alert_id, $url, $msg );

						endif;

					endif;

				endforeach;
				
			endif;

		endif;

	endif;

}

?>