<?php
if ($style == 'classic'):

    $html .= '<div class="'.$slug.' cpc_forum_posts_classic">';

        // Get previous login
        $last_logged_in = cpc_get_previous_login($current_user->ID);

		$c = 0;
        global $wpdb;

		foreach ($forum_posts as $forum_post):

			if ($forum_post['post_status'] == 'publish' || current_user_can('edit_posts') || $forum_post['post_author'] = $current_user->ID):
            // echo '<span style="color:red">'.$forum_post['post_title'].'</span><br />';

                // read it?
                $read = get_post_meta( $forum_post['ID'], 'cpc_forum_read', true );
                if (!$read || (!in_array($current_user->user_login, $read) && !in_array($current_user->ID, $read)))
                    $forum_post['read'] = true;

                $c++;
                $args = array(
                    'status' => 1,
                    'orderby' => 'comment_ID',
                    'order' => 'DESC',
                    'post_id' => $forum_post['ID'],
                    'parent' => 0 // set to 0 to exlude comments to replies
                );
                $comments = get_comments($args);

                // set to following as default (ie. original post creator)
                $author = cpc_display_name(array('user_id'=>$forum_post['post_author'], 'link'=>1));
                $author_id = $forum_post['post_author'];
                $created_original_post = sprintf($date_format, human_time_diff(strtotime($forum_post[$base_date]), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                $comment_date = false;  // set in case nothing to show
                
                // now we need to check for new replies and comments and update the above if need be
                $reply_count = 0;
                $checked_date = 0; // keep track if already checked a later date

				if ($comments):
                    $comments_ptr = 0;
                    foreach($comments as $comment): // loop through replies
                        // echo '<strong>'.$comment->comment_content.'</strong><br />';
                        // check to see if involved in all the comments to this reply
                        $args = array(
                            'status' => 1,
                            'orderby' => 'comment_ID',
                            'order' => 'DESC',
                            'post_id' => $forum_post['ID'],
                            'offset' => 0,
                            'number' => 100,
                            'parent' => $comment->comment_ID
                        );
                        $subcomments = get_comments($args); 
                        $involved_in_replies = false;
                        if ($subcomments):
                            // echo 'comment authors...<br />';
                            foreach ($subcomments as $subcomment):
                                // echo $current_user->ID.'-'.$subcomment->user_id.'<br />';
                                if ($subcomment->user_id == $current_user->ID) $involved_in_replies = true;
                            endforeach;
                        endif;
                        reset($subcomments);
                        // echo 'involved? '.($involved_in_replies ? 'YES' : 'NO').'<br />';
                        // echo 'original poster? '.($current_user->ID == $forum_post['post_author'] ? 'YES' : 'NO').'<br />';
                        // echo 'reply author? '.($comment->user_id == $current_user->ID ? 'YES' : 'NO').'<br />';
                        // $private = get_comment_meta( $comment->comment_ID, 'cpc_private_post', true );
                        // echo 'private? '.($private ? 'YES' : 'NO').'<br />';
                    	if ($comment->user_id): // not an auto-close comment
                            // .. reply is applicable if current user is post author, or the reply author or involved in comments (or not a private reply)
	                        $private = get_comment_meta( $comment->comment_ID, 'cpc_private_post', true );
	                        if ($involved_in_replies || !$private || $current_user->ID == $forum_post['post_author'] || $comment->user_id == $current_user->ID):
                                // echo 'reply is applicable (or post author, etc)<br />';
                                if (!$comments_ptr):
                                    // echo 'tracking first reply<br />';
                                    // .. track the first reply found (as ordered to first is latest)
                                    $comment_author = $comment->user_id;
                                    $author = cpc_display_name(array('user_id'=>$comment_author, 'link'=>1));
                                    // echo 'first reply author set to '.$author.'<br>';                                    
                                    $author_id = $comment_author;
                                    $comment_date = $base_date == 'post_date_gmt' ? $comment->comment_date_gmt : $comment->comment_date;
                                    $created = sprintf($date_format, human_time_diff(strtotime($comment_date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                                    $checked_date = $comment_date;
									$read = get_comment_meta( $comment->comment_ID, 'cpc_forum_reply_read', true );
									$new_topic_reply = ( !$read || (!in_array($current_user->user_login, $read) && !in_array($current_user->ID, $read)) );
                                    if ($comment_author != $current_user->ID && $new_topic_reply):
                                    	$forum_post['read'] = false;
                                        // echo 'not read the reply, so mark as new<br />';
									endif;
                                    if ($comment_author != $current_user->ID && cpc_since_last_logged_in($comment_date, $new_seconds)):
                                        if (!$new_item_read || ($new_item_read && !$forum_post['read'])) $forum_post['new'] = true;
                                        // echo 'new reply since previous login, so mark as new<br />';
                                    endif;

                                endif;
                                // Now check if any comments on replies are more recent than the stored reply
                                if ($subcomments):
                                    // echo 'checking comments on this reply...<br />';
                                    foreach ($subcomments as $subcomment):
                                        $subcomment_date = $base_date == 'post_date_gmt' ? $subcomment->comment_date_gmt : $subcomment->comment_date;
                                        // echo '<span style="color:orange">'.$subcomment->comment_content.' ('.$subcomment_date.':'.$checked_date.')</span><br />';
                                        if ($subcomment_date > $checked_date):
                                            // echo '<span style="color:green">newer comment... '.$subcomment->comment_content.'</span> '.$subcomment_date.' '.$checked_date.'<br />';
                                            $subcomment_author = $subcomment->user_id;
                                            $comment_author = $subcomment_author;
                                            $author = cpc_display_name(array('user_id'=>$subcomment_author, 'link'=>1));
                                            $author_id = $subcomment_author;
                                            // echo 'set to '.$author.'<br />';
                                            $comment_date = $subcomment_date;
                                            $created = sprintf($date_format, human_time_diff(strtotime($subcomment_date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                                            $checked_date = $subcomment_date; // to check against next loop
                                        	$read = get_comment_meta( $subcomment->comment_ID, 'cpc_forum_comment_read', true );
                                            $new_reply_comment = ( !$read || (!in_array($current_user->user_login, $read) && !in_array($current_user->ID, $read)) );
                                        	if ($subcomment_author != $current_user->ID && $new_reply_comment):
                                        		$forum_post['read'] = false;
                                                // echo 'not read the comment, so mark as unread<br />';
											endif;      
                                            if ($subcomment_author != $current_user->ID && cpc_since_last_logged_in($subcomment_date, $new_seconds)):
                                                if (!$new_item_read || ($new_item_read && !$forum_post['read'])) $forum_post['new'] = true;
                                                // echo 'new since previous login, so mark as new<br />';
                                            endif;
                                        endif;
                                    endforeach;
                                else:
                                // echo 'no comments on this reply<br />';
                                endif;
                                $reply_count++; // increase count of replies
                                $comments_ptr++;
	                        endif;
	                    endif;
                    endforeach;
				else:
					$author = cpc_display_name(array('user_id'=>$forum_post['post_author'], 'link'=>1));
                    $author_id = $forum_post['post_author'];
					$created = sprintf($date_format, human_time_diff(strtotime($forum_post[$base_date]), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
				endif;

                // if last activity is current user, can't be new
                if ($author_id == $current_user->ID || !is_user_logged_in()) $forum_post['read'] = true;
                // echo 'reply count:'.$reply_count.'<br />';
                // echo 'show as new? '.(!$forum_post['read'] ? 'NEW' : 'read').'<br />';

                // start building HTML for this post
				$forum_html = '';
				$forum_html .= '<div class="cpc_forum_post_classic';
						if ($forum_post['new']) $forum_html .= ' cpc_forum_post_new_classic';
						if ($forum_post['comment_status'] == 'closed') $forum_html .= ' cpc_forum_post_closed';
						if ($forum_post['is_sticky']) $forum_html .= ' cpc_forum_post_sticky';
					$forum_html .= '"'; // end of class

					// Hide if closed and chosen not to show
					if ($closed_switch && $forum_post['comment_status'] == 'closed' && $closed_switch_state == 'off' && !$forum_post['is_sticky']) $forum_html .= ' style="display:none"';

					$forum_html .= '>'; // end of opening div

					// Any more columns? (apply float:right)
					$forum_html = apply_filters( 'cpc_forum_post_columns_filter', $forum_html, $forum_post['ID'], $atts );

                    // Show title
                    global $blog;
                    if ( cpc_using_permalinks() ):
                        if (!is_multisite()):
                            $url = get_bloginfo('url').'/'.$slug.'/'.$forum_post['post_name'].'/?topic='.$forum_post['post_name'];
                        else:
                            $blog_details = get_blog_details(get_current_blog_id());
                            $url = $blog_details->path.$slug.'/'.$forum_post['post_name'].'/?topic='.$forum_post['post_name'];
                        endif;
                    else:
                        if (!is_multisite()):
                            $forum_page_id = cpc_get_term_meta($term->term_id, 'cpc_forum_cat_page', true);
                            $url = get_bloginfo('url')."/?page_id=".$forum_page_id."&topic=".$forum_post['post_name'];
                        else:
                            $forum_page_id = cpc_get_term_meta($term->term_id, 'cpc_forum_cat_page', true);
                            $blog_details = get_blog_details(get_current_blog_id());
                            $url = $blog_details->path."?page_id=".$forum_page_id."&topic=".$forum_post['post_name'];
                        endif;
                    endif;

                    $forum_title = esc_attr($forum_post['post_title']);

                    $forum_title = str_replace(array_keys(array('[' => '&#91;',']' => '&#93;','<' => '&lt;','>' => '&gt;',)), array_values(array('[' => '&#91;',']' => '&#93;','<' => '&lt;','>' => '&gt;',)), $forum_title);  
                    if (strlen($forum_title) > $title_length) $forum_title = substr($forum_title, 0, $title_length).'...';
                    $multiline = (strpos($forum_title, chr(10)) !== false) ? true : false;
                    if ($multiline) $forum_title = str_replace(chr(10), '<br />', $forum_title);

                    $forum_html .= '<div class="cpc_forum_title_classic_content_title"><a href="'.$url.'">'.$forum_title.'</a>';
                        // New label?
                        if ($forum_post['new'])  $forum_html .= ' <span class="cpc_forum_new_label">'.convert_smilies($new_item_label).'</span>';
                    $forum_html .= '</div>';

					$forum_html .= '<div class="cpc_forum_title_classic_content_row" style="padding-left: '.($size_posts).'px;">';

                        $forum_html .= '<div class="cpc_forum_title_classic_content_avatar" style="margin-left: -'.($size_posts).'px;">';
                            $forum_html .= user_avatar_get_avatar( $forum_post['post_author'], $size_posts, true, 'thumb' );
                        $forum_html .= '</div>';

                        // Counts
                        $sql = "SELECT * FROM ".$wpdb->prefix."comments WHERE comment_approved = '1' AND comment_post_ID = %d AND comment_parent = 0";
                        $comments = $wpdb->get_results($wpdb->prepare($sql, $forum_post['ID']));
                        $comment_count = 0;
                        foreach ($comments as $comment):
                            $private = get_comment_meta( $comment->comment_ID, 'cpc_private_post', true );
                            if (!$private) $comment_count++;
                        endforeach;

                        // Replies/comments count
                        $forum_html .= '<div class="cpc_forum_count_classic">';
                            $forum_html .= $comment_count;
                            $label = ($comment_count != 1) ? $replies_count_label : $reply_count_label;
                            $forum_html .= '<div class="cpc_forum_count_classic_label">'.$label.'</div>';
                        $forum_html .= '</div>';
    
                        // Views count
                        $forum_html .= '<div class="cpc_forum_count_classic">';
                            $view_count = get_post_meta( $forum_post['ID'], 'cpc_forum_view_count', true );
                            if (!$view_count) $view_count = 0;
                            $forum_html .= $view_count;
                            $label = ($view_count != 1) ? $views_count_label : $view_count_label;
                            $forum_html .= '<div class="cpc_forum_count_classic_label">'.$label.'</div>';
                        $forum_html .= '</div>';
    
                        $forum_html .= '<div class="cpc_forum_title_classic_content';
                            if ($reply_icon && $forum_post['commented']) $forum_html .= ' cpc_forum_post_classic_commented';
                            $forum_html .= '">';
                            // Started by...
                            if ($forum_post['comment_status'] == 'closed' && $closed_prefix) $started .= ' ['.$closed_prefix.']';
                            $forum_html .= sprintf($started, cpc_display_name(array('user_id'=>$forum_post['post_author'], 'link'=>1)), $created_original_post);
                            // Add post content
                            $forum_post_content = $forum_post['post_content'];
                            $forum_post_content = strip_tags(html_entity_decode(htmlspecialchars_decode(strip_tags($forum_post_content), ENT_QUOTES)));
                            $forum_post_content = str_replace(array_keys(array('[' => '&#91;',']' => '&#93;','<' => '&lt;','>' => '&gt;',)), array_values(array('[' => '&#91;',']' => '&#93;','<' => '&lt;','>' => '&gt;',)), $forum_post_content);  
                            if (strlen($forum_post_content) > $post_preview) $forum_post_content = substr($forum_post_content, 0, $post_preview).'...';
                            $forum_html .= '<br />'.cpc_bbcode_replace($forum_post_content);

                            // Get all recent replies, record most recent, but check for later comments just in case

                            $sql = "SELECT * FROM ".$wpdb->prefix."comments WHERE ( comment_approved = '1' ) AND comment_post_ID = %d AND comment_parent = 0 AND user_id > 0 ORDER BY comment_ID DESC LIMIT 0,20";
                            $replies = $wpdb->get_results($wpdb->prepare($sql, $forum_post['ID']));

                            $r = 0;
                            $author = false; // in case none to show, if private?

                            if ($replies):

                                foreach($replies as $reply) :

                                    $private = get_comment_meta( $reply->comment_ID, 'cpc_private_post', true );
                                    if (!$private || $current_user->ID == $post->post_author || $reply->user_id == $current_user->ID || current_user_can('manage_options')):
                                    
                                        $r++;
                                        if ($r == 1):   
                                            // This is most recent reply, so store (and use if no later comments)
                                            $content = $reply->comment_content;
                                            $author = $reply->user_id;
                                            $comment_date = sprintf($date_format, human_time_diff(strtotime($reply->comment_date_gmt), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                                            $original_date = $reply->comment_date_gmt;
                                            $action = $replied;                                           
                                        endif;

                                        // check for comments (in case later than latest reply)
                                        $sql = "SELECT * FROM ".$wpdb->prefix."comments WHERE ( comment_approved = '1' ) AND comment_post_ID = %d AND comment_parent = %d AND user_id > 0 ORDER BY comment_ID DESC LIMIT 1";
                                        $comments = $wpdb->get_results($wpdb->prepare($sql, $forum_post['ID'], $reply->comment_ID));

                                        if ($comments):
                                            // There is a comment, this is latest one, so check date in case later than last reply
                                            foreach ($comments as $comment):
                                                if ($comment->comment_date_gmt > $original_date):                                                   
                                                    $original_date = $comment->comment_date_gmt;
                                                    $content = $comment->comment_content;
                                                    $comment_date = sprintf($date_format, human_time_diff(strtotime($comment->comment_date_gmt), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                                                    $author = $comment->user_id;
                                                    $action = $commented;
                                                endif;
                                            endforeach;
                                        endif;

                                    endif;

                                endforeach;

                                if ($author): // latest reply found, and not private

                                    // Get comment's post forum term ID
                                    $forum_html .= '<div class="cpc_forum_post_classic_comments';
                                        if ($forum_post['new'])  $forum_html .= ' cpc_forum_reply_new_classic';
                                        if (!$forum_post['read']) $forum_html .= ' cpc_forum_post_unread';
                                        $forum_html .= '" style="padding-left: '.($size_replies).'px;">';

                                        $forum_html .= '<div class="cpc_forum_post_classic_comment_avatar" style="margin-left: -'.($size_replies).'px;">';
                                            $forum_html .= user_avatar_get_avatar( $author, $size_replies, true );
                                        $forum_html .= '</div>';
                                        $forum_html .= '<div class="cpc_forum_post_classic_comment_content">';
                                            $forum_html .= sprintf($action, cpc_display_name(array('user_id'=>$author, 'link'=>1)), $comment_date);

                                            // Add post content
                                            $forum_post_content = $content;
                                            $forum_post_content = strip_tags(html_entity_decode(htmlspecialchars_decode($forum_post_content, ENT_QUOTES)));
                                            $forum_post_content = str_replace(array_keys(array('[' => '&#91;',']' => '&#93;','<' => '&lt;','>' => '&gt;',)), array_values(array('[' => '&#91;',']' => '&#93;','<' => '&lt;','>' => '&gt;',)), $forum_post_content);  
                                            if (strlen($forum_post_content) > $reply_preview) $forum_post_content = substr($forum_post_content, 0, $reply_preview).'...';
                                            $forum_html .= '<br />'.$forum_post_content;

                                        $forum_html .= '</div>';

                                    $forum_html .= '</div>';

                                    endif;

                            else:

                                // No replies

                            endif;

                        $forum_html .= '</div>';

					$forum_html .= '</div>';

				$forum_html .= '</div>';

				$forum_html = apply_filters( 'cpc_forum_post_item', $forum_html );
				$html .= $forum_html;

			endif;

			if ($c == $count) break;

		endforeach;

	$html .= '</div>';
    
endif;
?>