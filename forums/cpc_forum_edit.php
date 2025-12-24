<?php
require_once plugin_dir_path(__FILE__).'cpc_forum_toolbar.php';

function cpc_post_delete($post_id, $atts) {

	global $current_user;

	$html = '';

	$the_post = get_post($post_id);

	// Get post forum term ID
	$post_terms = get_the_terms( $post_id, 'cpc_forum' );
	$the_post_terms = $post_terms[0];
	$post_term_slug = $the_post_terms->slug;
	$post_term_term_id = $the_post_terms->term_id;
    
    // Get list of forum admins
    $values = cpc_get_shortcode_options('cpc_forum');  
    extract( shortcode_atts( array('forum_admins' => cpc_get_shortcode_value($values, 'cpc_forum-forum_admins', '')), $atts, 'cpc_forum' ) );     
    $forum_admin_list = ($forum_admins) ? explode(',', $forum_admins) : array();
    $is_forum_admin = (in_array($current_user->user_login, $forum_admin_list) || current_user_can('manage_options'));

	$user_can_delete_forum = $the_post->post_author == $current_user->ID ? true : false;
	$user_can_delete_forum = apply_filters( 'cpc_forum_post_user_can_delete_filter', $user_can_delete_forum, $the_post, $current_user->ID, $post_term_term_id );

	if ($the_post && ($user_can_delete_forum || $is_forum_admin)):

		// Shortcode parameters
		extract( shortcode_atts( array(
			'class' => '',
			'delete_msg' => __('Bist Du sicher, dass Du diesen Beitrag und alle Antworten löschen möchtest?', CPC2_TEXT_DOMAIN),
			'delete_label' => __('Ja, lösche den Beitrag', CPC2_TEXT_DOMAIN),
			'delete_cancel_label' => __('Nein', CPC2_TEXT_DOMAIN),
		), $atts, 'cpc_forum_post' ) );

		$html .= '<h2>'.$delete_msg.'</h2>';

        $html .= cpc_formatted_content($the_post->post_content);    

		$url = cpc_curPageURL();
		$url = preg_replace("/[&?]forum_action=delete&post_id=[0-9]+/","",$url);

		$html .= '<div id="cpc_forum_post_edit_form">';

			$html .= '<form action="'.$url.'" method="POST">';
				$html .= '<input type="hidden" name="action" value="cpc_forum_post_delete" />';
				$html .= '<input type="hidden" name="cpc_post_id" value="'.$post_id.'" />';
				$html .= '<button id="cpc_forum_comment_delete_button" class="cpc_button '.$class.'">'.$delete_label.'</button>';
			$html .= '</form>';
			$html .= '<form ACTION="'.$url.'" METHOD="POST">';
				$html .= '<button id="cpc_forum_comment_cancel_button" class="cpc_button '.$class.'">'.$delete_cancel_label.'</button>';
			$html .= '</form>';

		$html .= '</div>';

	endif;

	return $html;

}

function cpc_comment_delete($the_post, $atts) {

	global $current_user;

	$html = '';

	$comment_id = $_GET['comment_id'];
	$current_comment = get_comment($comment_id);

	// Get comment's post forum term ID
	$the_post = get_post( $current_comment->comment_post_ID );
	$post_terms = get_the_terms( $the_post->ID, 'cpc_forum' );
	$the_post_terms = $post_terms[0];
	$post_term_term_id = $the_post_terms->term_id;	
    
    // Get list of forum admins
    $values = cpc_get_shortcode_options('cpc_forum');  
    extract( shortcode_atts( array('forum_admins' => cpc_get_shortcode_value($values, 'cpc_forum-forum_admins', '')), $atts, 'cpc_forum' ) );    
    $forum_admin_list = ($forum_admins) ? explode(',', $forum_admins) : array();
    $is_forum_admin = (in_array($current_user->user_login, $forum_admin_list) || current_user_can('manage_options'));    

	$user_can_delete_comment = $current_comment->user_id == $current_user->ID ? true : false;
	$user_can_delete_comment = apply_filters( 'cpc_forum_post_user_can_delete_comment_filter', $user_can_delete_comment, $current_comment, $current_user->ID, $post_term_term_id );

	if ($user_can_delete_comment || $is_forum_admin):

		// Shortcode parameters
		extract( shortcode_atts( array(
			'class' => '',
			'delete_comment_msg' => __('Bist Du sicher, dass Du dies löschen möchtest?', CPC2_TEXT_DOMAIN),
			'delete_comment_label' => __('Ja, löschen', CPC2_TEXT_DOMAIN),
			'delete_comment_cancel_label' => __('Nein', CPC2_TEXT_DOMAIN),
		), $atts, 'cpc_forum_post' ) );

		$html .= '<h2>'.$delete_comment_msg.'</h2>';

        $html .= cpc_formatted_content($current_comment->comment_content);
    
		$url = cpc_curPageURL();
		$url = preg_replace("/[&?]forum_action=delete&comment_id=[0-9]+/","",$url);

		$html .= '<div id="cpc_forum_post_edit_form">';

			$html .= '<form action="'.$url.'" method="POST">';
				$html .= '<input type="hidden" name="action" value="cpc_forum_comment_delete" />';
				$html .= '<input type="hidden" name="cpc_comment_id" value="'.$comment_id.'" />';
				$html .= '<button id="cpc_forum_comment_delete_button" type="submit" class="cpc_button '.$class.'">'.$delete_comment_label.'</button>';
			$html .= '</form>';
			$html .= '<form ACTION="'.$url.'" METHOD="POST">';
				$html .= '<button id="cpc_forum_comment_cancel_button" type="submit" class="cpc_button '.$class.'">'.$delete_comment_cancel_label.'</button>';
			$html .= '</form>';

		$html .= '</div>';

	endif;

	return $html;

}


function cpc_forum_delete_comment($post_data, $files_data, $atts) {

	global $current_user,$wpdb;
	
	$comment_id = $post_data['cpc_comment_id'];
	if ($comment_id):

		$current_comment = get_comment($comment_id);
    
        // Get list of forum admins
        $values = cpc_get_shortcode_options('cpc_forum');  
        extract( shortcode_atts( array('forum_admins' => cpc_get_shortcode_value($values, 'cpc_forum-forum_admins', '')), $atts, 'cpc_forum' ) );     
        $forum_admin_list = ($forum_admins) ? explode(',', $forum_admins) : array();
        $is_forum_admin = (in_array($current_user->user_login, $forum_admin_list) || current_user_can('manage_options'));     

		if ( ($current_comment) && ($current_user->user_login == $current_comment->comment_author || $is_forum_admin ) ):

			wp_delete_comment($comment_id, false); // soft delete
    
            // Now set any forum comments on that comment to trash for that post
            $sql = "SELECT comment_ID FROM ".$wpdb->prefix."comments WHERE comment_parent = %d";
            $comments = $wpdb->get_col($wpdb->prepare($sql, $comment_id));

            if ($comments):
                foreach ($comments as $comment_id):
                    wp_delete_comment($comment_id, false); // soft delete
                endforeach;
            endif;    

			// Any further actions?
			do_action( 'cpc_forum_comment_delete_hook', $post_data, $files_data, $comment_id );

		endif;

	endif;

}


function cpc_post_edit($post_id, $atts) {

	global $current_user;

	$html = '';

	$the_post = get_post($post_id);

	// Get post forum term ID
	$post_terms = get_the_terms( $post_id, 'cpc_forum' );
	foreach ($post_terms as $term):
		$post_term_slug = $term->slug;
		$post_term_term_id = $term->term_id;
	endforeach;
    
    // Get list of forum admins
    $values = cpc_get_shortcode_options('cpc_forum');  
    extract( shortcode_atts( array('forum_admins' => cpc_get_shortcode_value($values, 'cpc_forum-forum_admins', '')), $atts, 'cpc_forum' ) );    
    $forum_admin_list = ($forum_admins) ? explode(',', $forum_admins) : array();
    $is_forum_admin = (in_array($current_user->user_login, $forum_admin_list) || current_user_can('manage_options'));      

	$user_can_edit_forum = $the_post->post_author == $current_user->ID ? true : false;
    $user_can_edit_forum = apply_filters( 'cpc_forum_post_user_can_edit_filter', $user_can_edit_forum, $the_post, $current_user->ID, $post_term_term_id );

    if ($user_can_edit_forum || $is_forum_admin):

		// Shortcode parameters
    	$values = cpc_get_shortcode_options('cpc_forum');   		
		extract( shortcode_atts( array(
			'class' => '',
			'title_label' => 'Post title',
			'content_label' => 'Post',
			'update_label' => cpc_get_shortcode_value($values, 'cpc_forum-update_label', __('Aktualisieren', CPC2_TEXT_DOMAIN)),
			'cancel_label' => cpc_get_shortcode_value($values, 'cpc_forum-cancel_label', __('Abbrechen', CPC2_TEXT_DOMAIN)),
			'moderate_msg' => cpc_get_shortcode_value($values, 'cpc_forum-moderate_msg', __('Dein Beitrag erscheint, sobald er moderiert wurde.', CPC2_TEXT_DOMAIN)),
			'moderate' => false,
			'can_move_forum' => true,
			'slug' => '',
			'before' => '',
			'styles' => true,
            'after' => '',
		), $atts, 'cpc_forum_post' ) );
    
		$form_html = '';
		$form_html .= '<div id="cpc_forum_post_edit_div">';
		$form_html .= '<div id="cpc_forum_post_edit_form">';

		$url = cpc_curPageURL();
		$url = preg_replace("/[&?]forum_action=edit&post_id=[0-9]+/","",$url);

		$form_html .= '<form ACTION="'.$url.'" onsubmit="if (typeof cpc_forum_extended_mandatory_check == \'function\') { return cpc_forum_extended_mandatory_check(); }" METHOD="POST">';
		$form_html .= '<input type="hidden" name="action" value="cpc_forum_post_edit" />';
		$form_html .= '<input type="hidden" name="cpc_post_id" value="'.$post_id.'" />';
		$form_html .= '<input type="hidden" name="cpc_forum_moderate" value="'.$moderate.'" />';

		$form_html .= '<div id="cpc_forum_post_title_label">'.$title_label.'</div>';
		$form_html .= '<input type="text" id="cpc_forum_post_edit_title" name="cpc_forum_post_edit_title" value="'.$the_post->post_title.'" />';

		$form_html = apply_filters( 'cpc_forum_post_edit_pre_form_filter', $form_html, $atts, $current_user->ID, $post_id );

		$form_html .= '<div id="cpc_forum_post_content_label">'.$content_label.'</div>';

		$the_content = cpc_formatted_content($the_post->post_content, false);
		$the_content = preg_replace('/\t/', '', $the_content);

		// Hole immer den rohen BBCODE aus der Datenbank:
		$raw_content = $the_post->post_content;

		// Jetzt je nach Modus umwandeln:
		if ( defined( 'CPC_FORUM_TOOLBAR' ) && get_option( 'cpc_com_toolbar' ) == 'wysiwyg' ):
			// Für WYSIWYG: BBCODE nach HTML umwandeln
			$the_content = cpc_bbcode_replace($raw_content);
			$form_html .= cpc_get_wp_editor($the_content, 'cpc_forum_post_edit_textarea', 'margin-bottom:10px;');
		elseif ( defined( 'CPC_FORUM_TOOLBAR' ) && get_option( 'cpc_com_toolbar' ) == 'bbcodes' ):
			// Für BBCode-Editor: BBCODE direkt anzeigen
			$the_content = $raw_content;
			$form_html .= cpc_get_bbcode_toolbar('cpc_forum_post_edit_textarea', 'cpc_forum_post_edit_textarea', $the_content);
		else:
			// Fallback
			$the_content = $raw_content;
			$form_html .= '<textarea id="cpc_forum_post_edit_textarea" name="cpc_forum_post_edit_textarea">'.$the_content.'</textarea>';
		endif;

		$user_can_move_post = $the_post->post_author == $current_user->ID ? true : false;
		$user_can_move_post = apply_filters( 'cpc_forum_post_user_can_move_post_filter', $user_can_move_post, $the_post, $current_user->ID, $post_term_term_id );

		if ($user_can_move_post || $is_forum_admin):

			$terms = get_terms( "cpc_forum", array(
				'hide_empty'    => false, 
				'fields'        => 'all', 
				'hierarchical'  => false, 
			) );

			if ($can_move_forum):
				$form_html .= '<select name="cpc_post_forum_slug" id="cpc_post_forum_slug" style="float:right; width:50%; margin-top:5px">';
				foreach ( $terms as $term ):
					if (user_can_see_forum($current_user->ID, $term->term_id) || $is_forum_admin):
						$selected_as_default = ($post_term_slug == $term->slug) ? ' SELECTED' : '';
						$form_html .= '<option value="'.$term->slug.'" '.$selected_as_default.'>'.$term->name.'</option>';
					endif;
				endforeach;
				$form_html .= '</select>';
			else:
				$form_html .= '<input type="hidden" name="cpc_post_forum_slug" value="'.$post_term_slug.'" />';
			endif;

			if (!get_option('cpc_forum_sticky_admin_only') || $is_forum_admin):
				$form_html .= '<input type="checkbox" name="cpc_sticky"';
				if (get_post_meta($post_id, 'cpc_sticky', true)) $form_html .= ' CHECKED';
				$form_html .= '> '.__('Am Anfang der Beiträge anheften?', CPC2_TEXT_DOMAIN);
			endif;

		else:
			$form_html .= '<input type="hidden" name="cpc_post_forum_slug" value="'.$post_term_slug.'" />';
			$form_html .= '<input type="checkbox" style="display:none" name="cpc_sticky"';
			if (get_post_meta($post_id, 'cpc_sticky', true)) $form_html .= ' CHECKED';
			$form_html .= '>';
		endif;

		$form_html .= '<input type="hidden" name="cpc_post_forum_term_id" value="'.$post_term_term_id.'" />';

		if ($moderate) $form_html .= '<div id="cpc_forum_post_edit_moderate">'.$moderate_msg.'</div>';

		$form_html .= '<button id="cpc_forum_post_edit_button" type="submit" name="submit_edit" class="cpc_button '.$class.'">'.$update_label.'</button>';
		$form_html .= '&nbsp;<button id="cpc_forum_post_cancel_button" type="submit" name="submit_cancel" class="cpc_button '.$class.'">'.$cancel_label.'</button>';
		$form_html .= '</form>';

		$form_html .= '</div>';
		$form_html .= '</div>';

		$html .= $form_html;

			$form_html .= '<form ACTION="'.$url.'" onsubmit="if (typeof cpc_forum_extended_mandatory_check == \'function\') { return cpc_forum_extended_mandatory_check(); }" METHOD="POST">';
			$form_html .= '<input type="hidden" name="action" value="cpc_forum_post_edit" />';
			$form_html .= '<input type="hidden" name="cpc_post_id" value="'.$post_id.'" />';
			$form_html .= '<input type="hidden" name="cpc_forum_moderate" value="'.$moderate.'" />';
			// ... weitere Felder ...
			$form_html .= '<button id="cpc_forum_post_edit_button" type="submit" name="submit_edit" class="cpc_button '.$class.'">'.$update_label.'</button>';
			$form_html .= '&nbsp;<button id="cpc_forum_post_cancel_button" type="submit" name="submit_cancel" class="cpc_button '.$class.'">'.$cancel_label.'</button>';
			$form_html .= '</form>';
		
		$form_html .= '</div>';

		$html .= $form_html;

	else:

		$html .= __('Keine Berechtigung zum Bearbeiten.', CPC2_TEXT_DOMAIN);

	endif;

	return $html;

}

function cpc_comment_edit($comment_id, $atts) {

	global $current_user;
	$html = '';

	$the_comment = get_comment($comment_id);

	// Get comment's post forum term ID
	$the_post = get_post( $the_comment->comment_post_ID );
	$post_terms = get_the_terms( $the_post->ID, 'cpc_forum' );
	$the_post_terms = $post_terms[0];
	$post_term_term_id = $the_post_terms->term_id;
    
    // Get list of forum admins
    $values = cpc_get_shortcode_options('cpc_forum');  
    extract( shortcode_atts( array('forum_admins' => cpc_get_shortcode_value($values, 'cpc_forum-forum_admins', '')), $atts, 'cpc_forum' ) );      
    $forum_admin_list = ($forum_admins) ? explode(',', $forum_admins) : array();
    $is_forum_admin = (in_array($current_user->user_login, $forum_admin_list) || current_user_can('manage_options'));        

	$user_can_edit_comment = $the_comment->user_id == $current_user->ID ? true : false;
	$user_can_edit_comment = apply_filters( 'cpc_forum_post_user_can_edit_comment_filter', $user_can_edit_comment, $the_comment, $current_user->ID, $post_term_term_id );

	if ($user_can_edit_comment || $is_forum_admin):

		// Shortcode parameters
    	$values = cpc_get_shortcode_options('cpc_forum');   
    	extract( shortcode_atts( array(
			'class' => '',
			'content_label' => '',
			'update_label' => cpc_get_shortcode_value($values, 'cpc_forum-update_label', __('Aktualisieren', CPC2_TEXT_DOMAIN)),
			'cancel_label' => cpc_get_shortcode_value($values, 'cpc_forum-cancel_label', __('Abbrechen', CPC2_TEXT_DOMAIN)),
			'moderate_msg' => cpc_get_shortcode_value($values, 'cpc_forum-moderate_msg', __('Dein Beitrag erscheint, sobald er moderiert wurde.', CPC2_TEXT_DOMAIN)),
			'moderate' => false,
			'slug' => '',
			'before' => '',
			'styles' => true,
            'after' => '',
		), $atts, 'cpc_forum_comment' ) );

		$form_html = '';
		$form_html .= '<div id="cpc_forum_post_edit_div">';
		$form_html .= '<div id="cpc_forum_post_edit_form">';
		$url = cpc_curPageURL();
		$url = preg_replace("/[&?]forum_action=edit&comment_id=[0-9]+/","",$url);
		$form_html .= '<form ACTION="'.$url.'" METHOD="POST" onsubmit="return cpc_validate_forum_reply_edit();">';
		$form_html .= '<input type="hidden" name="action" value="cpc_forum_comment_edit" />';
		$form_html .= '<input type="hidden" name="cpc_comment_id" value="'.$comment_id.'" />';
		$form_html .= '<input type="hidden" name="cpc_forum_moderate" value="'.$moderate.'" />';

		$form_html .= '<div id="cpc_forum_comment_content_label">'.$content_label.'</div>';
		$form_html = apply_filters( 'cpc_forum_comment_edit_pre_form_filter', $form_html, $atts, $current_user->ID );

		$the_content = cpc_formatted_content($the_comment->comment_content, false);
		$the_content = preg_replace('/\t/', '', $the_content);

		// Hole immer den rohen BBCODE aus der Datenbank:
		$raw_content = $the_comment->comment_content;

		// Jetzt je nach Modus umwandeln:
		if ( defined( 'CPC_FORUM_TOOLBAR' ) && get_option( 'cpc_com_toolbar' ) == 'wysiwyg' ):
			// Für WYSIWYG: BBCODE nach HTML umwandeln
			$the_content = cpc_bbcode_replace($raw_content);
			$form_html .= cpc_get_wp_editor($the_content, 'cpc_forum_comment_edit_textarea', 'margin-top:20px;margin-bottom:20px;');
		elseif ( defined( 'CPC_FORUM_TOOLBAR' ) && get_option( 'cpc_com_toolbar' ) == 'bbcodes' ):
			// Für BBCode-Editor: BBCODE direkt anzeigen
			$the_content = $raw_content;
			$form_html .= cpc_get_bbcode_toolbar('cpc_forum_comment_edit_textarea', 'cpc_forum_comment_edit_textarea', $the_content);
		else:
			// Fallback
			$the_content = $raw_content;
			$form_html .= '<textarea id="cpc_forum_comment_edit_textarea" name="cpc_forum_comment_edit_textarea">'.$the_content.'</textarea>';
		endif;
		if ($moderate) $form_html .= '<div id="cpc_forum_comment_edit_moderate">'.$moderate_msg.'</div>';

		if (!$the_comment->comment_parent):
			$form_html = apply_filters( 'cpc_forum_comment_edit_post_form_filter', $form_html, $atts, $current_user->ID, $the_post->ID, $the_comment->comment_ID );
		else:
			$form_html = apply_filters( 'cpc_forum_subcomment_edit_post_form_filter', $form_html, $atts, $current_user->ID, $the_post->ID, $the_comment->comment_ID );
		endif;

		$form_html .= '<button id="cpc_forum_comment_edit_button" type="submit" name="submit_edit" class="cpc_button '.$class.'">'.$update_label.'</button>';
		$form_html .= '&nbsp;<button id="cpc_forum_post_cancel_button" type="submit" name="submit_cancel" class="cpc_button '.$class.'">'.$cancel_label.'</button>';
		$form_html .= '</form>';
		$form_html .= '</div>';
		$form_html .= '</div>';

		$html .= $form_html;

	else:

		$html .= __('Nicht der Kommentarbesitzer', CPC2_TEXT_DOMAIN);

	endif;

	return $html;

}

function cpc_save_post($post_data, $files_data, $moved_to, $atts) {

	global $current_user;
	$return_html = '';
	
	$post_id = $post_data['cpc_post_id'];
	if ($post_id):

		$current_post = get_post($post_id);

		// Get post forum term ID
		$post_terms = get_the_terms( $post_id, 'cpc_forum' );
		foreach ($post_terms as $term):
			$post_term_term_id = $term->term_id;
			$post_term_slug = $term->slug;
		endforeach;
    
        // Get list of forum admins
        $values = cpc_get_shortcode_options('cpc_forum');  
        extract( shortcode_atts( array('forum_admins' => cpc_get_shortcode_value($values, 'cpc_forum-forum_admins', '')), $atts, 'cpc_forum' ) );        
        $forum_admin_list = ($forum_admins) ? explode(',', $forum_admins) : array();
        $is_forum_admin = (in_array($current_user->user_login, $forum_admin_list) || current_user_can('manage_options'));        

		$user_can_edit_forum = $current_post->post_author == $current_user->ID ? true : false;
		$user_can_edit_forum = apply_filters( 'cpc_forum_post_user_can_edit_filter', $user_can_edit_forum, $current_post, $current_user->ID, $post_term_term_id );

		if ( $user_can_edit_forum || $is_forum_admin ):

			// Titel holen
			if (!empty($post_data['cpc_forum_post_edit_title'])) {
				$title = esc_html($post_data['cpc_forum_post_edit_title']);
			} elseif (!empty($post_data['cpc_forum_post_title'])) {
				$title = esc_html($post_data['cpc_forum_post_title']);
			} else {
				$title = $current_post->post_title; // Fallback: alter Titel
			}

			// Inhalt holen
			if (!empty($post_data['cpc_forum_post_edit_textarea'])) {
				$content = esc_html($post_data['cpc_forum_post_edit_textarea']);
			} elseif (!empty($post_data['cpc_forum_post_textarea'])) {
				$content = esc_html($post_data['cpc_forum_post_textarea']);
			} else {
				$content = $current_post->post_content; // Fallback: alter Inhalt
			}

			$my_post = array(
				'ID'           => $post_id,
				'post_title'   => $title,
				'post_content' => $content,
			);
			wp_update_post( $my_post ); // sanitises

		  	// Sticky?
		  	if (isset($_POST['cpc_sticky'])):
		  		update_post_meta($post_id, 'cpc_sticky', true);
		  	else:
		  		delete_post_meta($post_id, 'cpc_sticky', true);
		  	endif;

			// Change forum?
			if (!empty($post_data['cpc_post_forum_slug'])) {
				$new_slug = $post_data['cpc_post_forum_slug'];
				$current_post_terms = get_the_terms($post_id, 'cpc_forum');
				$current_post_term = $current_post_terms[0];
				if ($current_post_term->slug != $new_slug) {
					// Optional: Erfolgsmeldung bauen
					$the_post = get_post($post_id);
					if (is_multisite()) {
						$blog_details = get_blog_details(get_current_blog_id());
						$url = $blog_details->path . $new_slug . '/' . $the_post->post_name;
						$forum_url = $blog_details->path . $new_slug;
					} else {
						if (cpc_using_permalinks()) {
							$url = get_bloginfo('url') . '/' . $new_slug . '/' . $the_post->post_name;
							$forum_url = get_bloginfo('url') . '/' . $new_slug;
						} else {
							$new_term = get_term_by('slug', $new_slug, 'cpc_forum');
							$forum_page_id = cpc_get_term_meta($new_term->term_id, 'cpc_forum_cat_page', true);
							$url = get_bloginfo('url') . "/?page_id=" . $forum_page_id . "&topic=" . $the_post->post_name;
							$forum_url = get_bloginfo('url') . "/?page_id=" . $forum_page_id;
						}
					}
					$new_term = get_term_by('slug', $new_slug, 'cpc_forum');
					$return_html = '<div class="cpc_success">' . sprintf($moved_to, '<a href="' . $url . '">' . esc_attr($the_post->post_title) . '</a>', '<a href="' . $forum_url . '">' . esc_attr($new_term->name) . '</a>') . '</div>';

					// Save post forum (term)
					wp_set_object_terms($post_id, $new_slug, 'cpc_forum');
				}
			}

			// Any further actions?
			do_action( 'cpc_forum_post_edit_hook', $post_data, $files_data, $post_id );

			endif; // $user_can_edit_forum || $is_forum_admin

			endif; // $post_id

			return $return_html;

		}

function cpc_save_comment($post_data, $files_data, $atts) {
	//error_log('cpc_save_comment called: '.print_r($post_data, true));

	global $current_user;

	$comment_id = $post_data['cpc_comment_id'];
	if ($comment_id):

		$current_comment = get_comment($comment_id);

		// Get comment's post forum term ID
		$the_post = get_post( $current_comment->comment_post_ID );
		$post_terms = get_the_terms( $the_post->ID, 'cpc_forum' );
		$the_post_terms = $post_terms[0];
		$post_term_term_id = $the_post_terms->term_id;
    
        // Get list of forum admins
        $values = cpc_get_shortcode_options('cpc_forum');  
        extract( shortcode_atts( array('forum_admins' => cpc_get_shortcode_value($values, 'cpc_forum-forum_admins', '')), $atts, 'cpc_forum' ) );     
        $forum_admin_list = ($forum_admins) ? explode(',', $forum_admins) : array();
        $is_forum_admin = (in_array($current_user->user_login, $forum_admin_list) || current_user_can('manage_options'));          

		$user_can_edit_comment = $current_comment->user_id == $current_user->ID ? true : false;
		$user_can_edit_comment = apply_filters( 'cpc_forum_post_user_can_edit_comment_filter', $user_can_edit_comment, $current_comment, $current_user->ID, $post_term_term_id );

		$the_content = trim($post_data['cpc_forum_comment_edit_textarea']);
		$the_content = preg_replace('/\t/', '', $the_content);

		if ( $user_can_edit_comment || $is_forum_admin ):
			$commentarr = array();
			$commentarr['comment_ID'] = $comment_id;
			$commentarr['comment_content'] = $the_content;
		if (wp_update_comment( $commentarr )) {
			sleep(1);
			clean_comment_cache($comment_id);
			clean_post_cache($the_post->ID);
			wp_cache_flush();
			wp_suspend_cache_invalidation(false);
			$forum_slug = $the_post_terms->slug;
			$url = get_bloginfo('url').'/'.$forum_slug.'/?topic='.$the_post->post_name;
			$url = add_query_arg('cpc_updated', time(), $url); // <-- HIER EINFÜGEN
			wp_redirect($url);
			exit;
			} else {
				error_log('Could not update comment: ' . $the_content);
			}
			do_action( 'cpc_forum_comment_edit_hook', $post_data, $files_data, $comment_id );
		endif;

	endif;

}
?>
