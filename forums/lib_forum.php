<?php
while(!is_file('wp-config.php')){
	if(is_dir('../')) chdir('../');
	else die('Could not find WordPress config file.');
}
include_once( 'wp-config.php' );

$action = isset($_POST['action']) ? $_POST['action'] : false;

if ($action) {

	global $current_user;

	if ( is_user_logged_in() ) {

		/* ADD POST */
		if ($action == 'cpc_forum_post_add') {

			$the_post = $_POST;
			$status = $the_post['cpc_forum_moderate'] == '1' ? 'pending' : 'publish';
            
            $the_title = esc_html($the_post['cpc_forum_post_title']);
            $the_content = esc_html($the_post['cpc_forum_post_textarea']);
            $the_content = preg_replace('/\t/', '', $the_content); // remove tabs
            $cpc_forum_slug_length = get_option('cpc_forum_slug_length') ? get_option('cpc_forum_slug_length') : 50;
            $post_name = strlen($the_title) < $cpc_forum_slug_length ? $post_name = $the_title : $post_name = substr($the_title, 0, $cpc_forum_slug_length);
            
			$post = array(
			  'post_title'     => $the_title,
              'post_name'      => $post_name,
			  'post_content'   => $the_content,
			  'post_status'    => $status,
			  'author'		   => $current_user->ID,
			  'post_type'      => 'cpc_forum_post',
			  'post_author'    => $current_user->ID,
			  'ping_status'    => 'closed',
			  'comment_status' => 'open',
			);  
			$new_id = wp_insert_post( $post ); // sanitises 

            if ($new_id):

                wp_set_object_terms( $new_id, $the_post['cpc_forum_slug'], 'cpc_forum' );
            
                // Any further actions?
                do_action( 'cpc_forum_post_add_hook', $_POST, $_FILES, $new_id );

            endif;             

            $new_post = get_post($new_id);
            if (true || $the_post['cpc_forum_choose']): // everybody can choose

                // IMMER Query-Variante bauen!
                $url = get_bloginfo('url').'/'.$the_post['cpc_forum_slug'].'/?topic='.$new_post->post_name;

                // set meta for this post at the forum level
                $the_forum_term = get_term_by('slug', $the_post['cpc_forum_slug'], 'cpc_forum');
                $term_id = $the_forum_term->term_id;
                cpc_update_term_meta($term_id, 'cpc_last_post_id', $new_id);
                cpc_update_term_meta($term_id, 'cpc_last_post_created', date('Y-m-d H:i:s'));
                cpc_update_term_meta($term_id, 'cpc_last_post_created_gmt', gmdate('Y-m-d H:i:s'));
                cpc_update_term_meta($term_id, 'cpc_last_post_author', $current_user->ID);
            
                echo $new_id.'|'.$url.'|'.$status;

            else:
                echo $new_id.'|reload|'.$status;
            endif;

		}
        
		/* ADD REPLY **************************************************************************************** */
        
		if ($action == 'cpc_forum_comment_add') {

			$the_comment = $_POST;
			$status = $the_comment['cpc_forum_moderate'] == '1' ? '0' : '1';

            $the_content = esc_html($the_comment['cpc_forum_comment']);
            $the_content = preg_replace('/\t/', '', $the_content);

            if ($the_comment['cpc_forum_comment']):
                $data = array(
                    'comment_post_ID' => $the_comment['post_id'],
                    'comment_content' => $the_content,
                    'comment_type' => 'cpc_forum_comment',
                    'comment_parent' => 0,
                    'comment_author' => $current_user->user_login,
                    'comment_author_email' => $current_user->user_email,
                    'user_id' => $current_user->ID,
                    'comment_author_IP' => $_SERVER['REMOTE_ADDR'],
                    'comment_agent' => $_SERVER['HTTP_USER_AGENT'],
                    'comment_approved' => $status,
                );
                $new_id = wp_insert_comment($data);
            else:
                $new_id = false;
            endif;
            $return_html = 'reload';

            // Close Post?
            if (isset($_POST['cpc_close_post']) && $_POST['cpc_close_post'] == 'on'):

                // Get post of this reply
                $my_post = array(
                      'ID'           	=> $the_comment['post_id'],
                      'comment_status' 	=> 'closed',
                );

                wp_update_post( $my_post );
            
            else:
            
                // set meta for this comment at the forum level (if not closing it above)
                $the_forum_term = get_term_by('slug', $the_comment['cpc_forum_slug'], 'cpc_forum');
                $term_id = $the_forum_term->term_id;
                cpc_update_term_meta($term_id, 'cpc_last_reply_id', $new_id);
                cpc_update_term_meta($term_id, 'cpc_last_reply_created', date('Y-m-d H:i:s'));
                cpc_update_term_meta($term_id, 'cpc_last_reply_created_gmt', gmdate('Y-m-d H:i:s'));
                cpc_update_term_meta($term_id, 'cpc_last_reply_author', $current_user->ID);
            
                // set meta for this comment at the post level
                update_post_meta($the_comment['post_id'], 'cpc_last_reply_id', $new_id);
                update_post_meta($the_comment['post_id'], 'cpc_last_reply_created', date('Y-m-d H:i:s'));
                update_post_meta($the_comment['post_id'], 'cpc_last_reply_created_gmt', gmdate('Y-m-d H:i:s'));
                update_post_meta($the_comment['post_id'], 'cpc_last_reply_author', $current_user->ID);
              
            endif;

            // .. used a couple of times below so get now
            $original_post = get_post($the_comment['post_id']);
            $post_author_id = $original_post->post_author;
            
            // Is this a private reply? Not applicable if replying to own post
            if ( (isset($the_comment['cpc_private_post'])) && ($current_user->ID != $post_author_id) ):
                update_comment_meta($new_id, 'cpc_private_post', true);            
            endif;
            
            /*
            // Reset read flags for everyone as not private (apart from current user)
            // .. if not a private reply, just reset read with current user
            if (!isset($the_comment['cpc_private_post'])):
                $read = array();
                $read[] = $current_user->ID;
                // .. if a private reply, get current read, remove the author of original post and save
            else:
                $read = get_post_meta( $the_comment['post_id'], 'cpc_forum_read', true );
                if (!$read):
                    // .. no current status for read, so reset with current user
                    $read = array();
                    $read[] = $current_user->ID;
                else:
                    // .. read status exists, so just remove author of original post
                    if(($key = array_search($post_author_id, $read)) !== false) {
                        unset($read[$key]);
                    }
                endif;
            endif;
            // ... and update read status
            update_post_meta ( $the_comment['post_id'], 'cpc_forum_read', $read);
            */

            // Move forums?
            if (isset($the_comment['cpc_post_forum_slug'])) :

                $current_post_terms = get_the_terms( $the_comment['post_id'], 'cpc_forum' );
                $current_post_term = $current_post_terms[0];
                if ($current_post_term->slug != $the_comment['cpc_post_forum_slug']):

                    $the_post = get_post($the_comment['post_id']);
                    if (is_multisite()) {

                        $blog_details = get_blog_details($blog->blog_id);
                        $url = $blog_details->path.$the_comment['cpc_post_forum_slug'].'/'.$the_post->post_name;

                    } else {

                        if ( cpc_using_permalinks() ):
                            $url = get_bloginfo('url').'/'.$the_comment['cpc_post_forum_slug'].'/'.$the_post->post_name;
                        else:
                            // Get term, and then page for forum
                            $new_term = get_term_by('slug', $the_comment['cpc_post_forum_slug'], 'cpc_forum');
                            $forum_page_id = cpc_get_term_meta($new_term->term_id, 'cpc_forum_cat_page', true);
                            $url = get_bloginfo('url')."/?page_id=".$forum_page_id."&topic=".$the_post->post_name;
                        endif;

                    }

                    $return_html = $url;

                    // Save post forum (term)
                    wp_set_object_terms( $the_comment['post_id'], $the_comment['cpc_post_forum_slug'], 'cpc_forum' );

                endif;

            endif;

            // Any further actions?
            do_action( 'cpc_forum_reply_add_hook', $the_comment, $_FILES, $the_comment['post_id'], $new_id );
            
            echo $return_html;            

		}
		
	}


}

?>
