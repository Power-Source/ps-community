<?php

global $wpdb, $post, $current_user;

$this_user = $current_user;

require_once('cpc_forum_edit.php');

// Clicked on Edit via Settings icon
if ( (isset($_GET['forum_action']) && $_GET['forum_action'] == 'edit') ):

	if ( isset($_GET['post_id']) ):
		$html = cpc_post_edit($_GET['post_id'], $atts);
	else:
		$html = cpc_comment_edit($_GET['comment_id'], $atts);
	endif;

endif;

// Clicked on Delete via Settings icon
if ( (isset($_GET['forum_action']) && $_GET['forum_action'] == 'delete') ):

	if ( isset($_GET['post_id']) ):
		$html = cpc_post_delete($_GET['post_id'], $atts);
	else:
		$html = cpc_comment_delete($_GET['comment_id'], $atts);
	endif;

endif;

if (!isset($_GET['forum_action']) || ($_GET['forum_action'] != 'edit' && $_GET['forum_action'] != 'delete')):

	// Saving from edit
	if ( ( isset($_POST['action']) && $_POST['action'] == 'cpc_forum_post_edit') ) $html .= cpc_save_post($_POST, $_FILES, $moved_to, $atts);
	if ( ( isset($_POST['action']) && $_POST['action'] == 'cpc_forum_comment_edit') ) $html .= cpc_save_comment($_POST, $_FILES, $atts);

	// Delete comment confirmed
	if ( ( isset($_POST['action']) && $_POST['action'] == 'cpc_forum_comment_delete') ) cpc_forum_delete_comment($_POST, $_FILES, $atts);

	if (!isset($_GET['topic_id'])):
		$post_slug = get_query_var('topic');
	else:
		$the_post = get_post($_GET['topic_id']);
		if ($the_post):
			$post_slug = $the_post->post_name;
		else:
			echo '<div class="cpc_error">'.__('Forumbeitrag mit topic_id konnte nicht gefunden werden', CPC2_TEXT_DOMAIN).'</div>';
		endif;
	endif;

	$loop = new WP_Query( array(
		'post_type' => 'cpc_forum_post',
		'name' => $post_slug,
		'post_status' => 'publish',	
		'posts_per_page' => 1		
	) );

    if ($loop->have_posts()):
		while ( $loop->have_posts() ) : $loop->the_post();

			// First check can see this post
			$post_terms = get_the_terms( $post->ID, 'cpc_forum' );
			if( $post_terms && !is_wp_error( $post_terms ) ):

				$user_can_see = false;
                $locked = false;
				foreach( $post_terms as $term ):
					if (user_can_see_forum($current_user->ID, $term->term_id) || current_user_can('manage_options')) $user_can_see = true;
                    if (cpc_get_term_meta($term->term_id, 'cpc_forum_closed', true)) $locked = true;
				endforeach;
    
                $current_user = $this_user;

				if ($user_can_see || current_user_can('manage_options')):

					if (user_can_see_post($current_user->ID, $post->ID)):

                        // Get read status as used in next couple of things
                        $read = get_post_meta( $post->ID, 'cpc_forum_read', true );

                        // Is this a new post?
                        $forum_post['new'] = false;
                        $date = $base_date == 'post_date_gmt' ? $post->post_date_gmt : $post->post_date;
                        if ($post->post_author != $current_user->ID && cpc_since_last_logged_in($date, $new_seconds)) {
                            $forum_post['new'] = true;
                            // echo 'new since previous login, so mark as new<br />';
                            if ($new_item_read && $read && in_array($current_user->ID, $read)) {
                                $forum_post['new'] = false;
                                // echo 'but marking new posts as not new when read, so cancel<br />';
                            }
                        }

						// Add read flag for this user
						if (is_user_logged_in()) {
							if (!$read):
								$read = array();
								$read[] = $current_user->ID;
							else:
								if (!in_array($current_user->user_login, $read) && !in_array($current_user->ID, $read)):
									$read[] = $current_user->ID;
								endif;
							endif;
							update_post_meta ( $post->ID, 'cpc_forum_read', $read);
						}

                        // Update view counter for this post (if not post author)
                        if ($post->post_author != $current_user->ID):
                            $view_count = get_post_meta( $post->ID, 'cpc_forum_view_count', true );
                            if (!$view_count) $view_count = 0;
                            $view_count++;
                            update_post_meta ( $post->ID, 'cpc_forum_view_count', $view_count);
                        endif;

						// Get count of replies
						$sql = "SELECT * FROM ".$wpdb->prefix."comments WHERE comment_post_ID = %d AND comment_parent = 0 AND comment_approved = 1";
                        $comments = $wpdb->get_results($wpdb->prepare($sql, $post->ID));
                        $num_comments = 0;
                        $num_comment_comments = 0;
                        if ($comments):
                            foreach ($comments as $comment):
                                $private = get_comment_meta( $comment->comment_ID, 'cpc_private_post', true );
                                if (!$private || $current_user->ID == $post->post_author || $current_user->ID == $comment->user_id || current_user_can('manage_options')):
                                    $num_comments++;
                                    // Get count of comments on this reply
                                    $sql = "SELECT COUNT(comment_ID) FROM ".$wpdb->prefix."comments WHERE comment_parent = %d AND comment_parent > 0 AND comment_approved = 1";
                                    $num_comment_comments += $wpdb->get_var($wpdb->prepare($sql, $comment->comment_ID));
                                endif;
                            endforeach;
                        endif;
						if ( $num_comments == 0 ) {
							$comments_count = $reply_comment_none;
						} elseif ( $num_comments > 1 ) {
							$comments_count = sprintf($reply_comment_multiple, $num_comments);
						} else {
							$comments_count = $reply_comment_one;
						}
                        if ( $num_comment_comments == 0 ) {
                            $comment_comments_count = '';
                        } elseif ( $num_comment_comments > 1 ) {
                            $comment_comments_count = ' '.sprintf($reply_comment_multiple_comments, $num_comment_comments);
                        } else {
                            $comment_comments_count = ' '.$reply_comment_one_comment;
                        }

						// Prepare pagination
						$limit = $page_size;
						$pages = ceil($num_comments/$limit);
						if (isset($_GET['page'])):
							$page = isset($_GET['page']) ? $_GET['page'] : 1; // No permalinks
						else:
							$page = trim(get_query_var('fpage')); // Permalinks
							if ($page):
								if (strpos($page, '-') !== false):
									$page = explode('-', $page);
									$page = (int)$page[1];
								endif;
							else:
								if ($topic_action == '') {
									$page = 1;
								} else {
									$page = $pages;
								}
							endif;
						endif;
						// ... check if got to go to end (eg. after posting reply)?
						if (isset($_GET['gotoend'])) $page = $pages;

						$offset = ($page * $limit) - $limit;

						if ($page > $pages):
							$page = 1;
							$offset = 0;
						endif;

						// Add following to DOM for use in JS (eg. commenting on a reply)
						$html .= '<div style="display:none" id="cpc_wait_url">'.plugins_url('../css/images/wait.gif', __FILE__).'</div>';

						// Original Post

						$post_html = '';
						$author = get_user_by('id', $post->post_author);

						$post_title_html = '<h2 class="cpc_forum_post_title">';
                        
                        if (function_exists('user_avatar_get_avatar')):
                            if ($author):
                                $cpc_post_avatar = user_avatar_get_avatar( $author->ID, $size, true, 'thumb' );
                            else:
                                $cpc_post_avatar = user_avatar_get_avatar( 0, $size, true );
                            endif;
                        else:
                            if ($author):
                                $cpc_post_avatar = get_avatar( $author->ID, $size );
                            else:
                                $cpc_post_avatar = get_avatar( 0, $size );
                            endif;
                        endif;

                        $post_title_avatar_html = '<div class="cpc_author_meta_avatar_mobile">';
                            $post_title_avatar_html .= $cpc_post_avatar;
                        $post_title_avatar_html .= '</div>';

						$post_title = $post->post_title;
                        $multiline = (strpos($post_title, chr(10)) !== false) ? true : false;
                        if ($multiline) $post_title = str_replace(chr(10), '<br />', $post_title);
						if (strlen($post_title) > $title_length) $post_title = substr($post_title, 0, $title_length).' ...';
						$post_title_html .= $post_title;
						if ($show_comments_count):
                            if ($multiline) $post_title_html .= '<br />';
                            $post_title_html .= ' ('.$comments_count.$comment_comments_count.')';
                        endif;
						$post_title_html .= '</h2>';

						$post_title_html = apply_filters( 'cpc_forum_post_post_title_filter', $post_title_html, $post, $atts, $current_user->ID );

						// work out URL for pagination
						if ($pagination):
		                    global $blog;
		                    if ( cpc_using_permalinks() ):
		                        if (!is_multisite()):
									$url = get_bloginfo('url').'/'.$term->slug.'/'.$post_slug.'/?topic='.$_GET['topic'].'&page=%d';
		                        else:
		                            $blog_details = get_blog_details(get_current_blog_id());
		                            $url = $blog_details->path.$term->slug.'/'.$post_slug.'/?topic='.$_GET['topic'].'&page=%d';
		                        endif;
		                    else:
		                        if (!is_multisite()):
									$url = get_bloginfo('url').'?page_id='.$_GET['page_id'].'&topic='.$_GET['topic'].'&page=%d';
		                        else:
		                            $blog_details = get_blog_details(get_current_blog_id());
		                            $url = $blog_details->path.$term->slug."?topic=".$_GET['topic'].'&fpage=%d';
		                        endif;
		                    endif;
		                endif;

                        $pagination_html = cpc_insert_pagination($page, $pages, $pagination_first, $pagination_previous, $pagination_next, $url);

                        if ($pages > 1 && $pagination && $pagination_above)
                            $post_html .= '<div id="cpc_forum_pagination_above">'.$pagination_html.'</div>';

						if ($page == 1 || !$hide_initial):

							// Only show original post on page 1

                            $post_html .= '<div id="cpc_initial_post"';
                                $post_html .= ($forum_post['new']) ? ' class="cpc_forum_post_new"' : ''; // new post?
                                $post_html .= '>';
                                
                                $post_html .= $post_title_html;

                                $post_html .= '<div class="cpc_forum_post_comment" style="padding-left: '.($size).'px;">';

                                    $post_html .= '<div class="cpc_forum_post_comment_author" style="max-width: '.($size).'px; margin-left: -'.($size).'px;">';
                                        $post_html .= '<div class="cpc_forum_post_comment_author_avatar">';
                                            $post_html .= $cpc_post_avatar;
                                        $post_html .= '</div>';
                                        $post_html .= '<div class="cpc_forum_post_comment_author_display_name">';
                                            if ($author):
                                                $cpc_display_name = cpc_display_name(array('user_id'=>$author->ID, 'link'=>1));
                                            else:
                                                $cpc_display_name = __('(unknown)', CPC2_TEXT_DOMAIN);
                                            endif;
                                            $post_html .= $cpc_display_name;
                                        $post_html .= '</div>';
                                        $post_html .= '<div class="cpc_forum_post_comment_author_freshness">';
                                            $date = $base_date == 'post_date_gmt' ? $post->post_date_gmt : $post->post_date;
                                            $cpc_timestamp = sprintf($date_format, human_time_diff(strtotime($date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                                            $post_html .= $cpc_timestamp;
                                        $post_html .= '</div>';
                                    $post_html .= '</div>';

                                    $post_html .= '<div class="cpc_forum_post_comment_content">';

                                        $post_html .= '<div class="cpc_author_meta_mobile">';
                                            $post_html .= '<span class="cpc_author_meta_mobile_display_name">'.$cpc_display_name.'</span>';
                                            $post_html .= ' <span class="cpc_author_meta_mobile_date">'.$cpc_timestamp.'</span>';
                                        $post_html .= '</div>';

                                        // Post Settings
                                        $age = current_time('timestamp', 1) - strtotime($post->post_date);
                                        $user_can_edit_forum = $post->post_author == $current_user->ID ? true : false;
                                        $user_can_edit_forum = apply_filters( 'cpc_forum_post_user_can_edit_filter', $user_can_edit_forum, $post, $current_user->ID, $term->term_id );

                                        $user_can_delete_forum = $post->post_author == $current_user->ID ? true : false;
                                        $user_can_delete_forum = apply_filters( 'cpc_forum_post_user_can_delete_filter', $user_can_delete_forum, $post, $current_user->ID, $term->term_id );

                                        // Check if timed out
                                        $timed_out = $age > $timeout ? true : false;
                                        $timed_out = apply_filters( 'cpc_forum_post_timed_out_filter', $timed_out, $current_user->ID, $age, $timeout, $term->term_id );
                                        if (!$enable_timeout) $timed_out = false; // disabled timeouts via shortcode option

                                        if ( ( ($user_can_edit_forum || $user_can_delete_forum) && !$timed_out) || current_user_can('manage_options') || $is_forum_admin):

                                            $post_html .= '<div class="cpc_forum_settings">';
                                                $post_html .= '<img style="height:15px;width:15px;" src="'.plugins_url('images/wrench'.get_option('cpccom_icon_colors').'.svg', __FILE__).'" />';
                                            $post_html .= '</div>';
                                            $post_html .= '<div class="cpc_forum_settings_options">';

                                                $url = cpc_curPageURL();
                                                if ($user_can_edit_forum || $is_forum_admin) $post_html .= '<a href="'.$url.cpc_query_mark($url).'forum_action=edit&post_id='.$post->ID.'">'.__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
                                                if (($user_can_edit_forum || $is_forum_admin) && $timeout-$age >= 0 && $enable_timeout) $post_html .= '<br />('.sprintf(__('Sperre in %d Sekunden', CPC2_TEXT_DOMAIN), ($timeout-$age)).')';
                                                if (($user_can_edit_forum && $user_can_delete_forum) || $is_forum_admin) $post_html .= ' | ';
                                                if ($user_can_delete_forum || $is_forum_admin) $post_html .= '<a href="'.$url.cpc_query_mark($url).'forum_action=delete&post_id='.$post->ID.'">'.__('Löschen', CPC2_TEXT_DOMAIN).'</a>';
                                                if ($report) $post_html .= ' | <a class="cpc_activity_settings_report" rel="'.$post->ID.'" href="mailto:'.$report_email.'?subject='.$url.'">'.$report_label.'</a>';
                                            $post_html .= '</div>';	

                                        elseif  ( is_user_logged_in() && $report):

                                            $post_html .= '<div class="cpc_forum_settings">';
                                                $post_html .= '<img style="height:15px;width:15px;" src="'.plugins_url('images/wrench'.get_option('cpccom_icon_colors').'.svg', __FILE__).'" />';
                                            $post_html .= '</div>';
                                            $post_html .= '<div class="cpc_forum_settings_options">';
                                                $url = cpc_curPageURL();
                                                $post_html .= '<a class="cpc_activity_settings_report" rel="'.$post->ID.'" href="mailto:'.$report_email.'?subject='.$url.'">'.$report_label.'</a>';
                                            $post_html .= '</div>';	

                                        endif;

                                        // Topic
										$post_content = $post->post_content;
                                        $post_content = cpc_formatted_content($post_content, true);

                                        $post_content = apply_filters( 'cpc_forum_item_content_filter', $post_content, $post, $atts );

                                        // New label?
                                        if ($forum_post['new']) $post_content = '<span class="cpc_forum_new_post_label cpc_forum_new_label">'.convert_smilies($new_item_label).'</span> '.$post_content;

                                        $post_html .= '<div class="cpc_forum_item_content">'.$post_content.'</div>'; 

                                        // Filter for handling anything else
                                        // Passes $post_html, shortcodes options ($atts), mail post ($post), message ($post->post_content))
                                        $post_html = apply_filters( 'cpc_forum_item_filter', $post_html, $atts, $post, $post->post_content );

                                    $post_html .= '</div>';

                                $post_html .= '</div>';

                            $post_html .= '</div>';

						else:

                            $post_html .= $post_title_html;

							// If page > 1, show "page" subtitle
							if ($page_x_of_y) $post_html .= '<h3>'.sprintf($page_x_of_y, $page, $pages).'</h3>';

						endif;


						if ($pages > 1 && $pagination && $pagination_top)
                            $post_html .= '<div id="cpc_forum_pagination_top">'.$pagination_html.'</div>';


						// Published replies

						$args = array(
							'status' => 1,
							'orderby' => 'comment_date',
							'order' => $replies_order,
							'post_id' => $post->ID,
							'offset' => $offset,
							'number' => $limit,
							'parent' => 0
						);

						$comments = get_comments($args);
						if ($comments):

							// Get comment's post forum term ID
							$first_comment = $comments[0];
							$the_post = get_post( $first_comment->comment_post_ID );
							$post_terms = get_the_terms( $the_post->ID, 'cpc_forum' );
							foreach( $post_terms as $term ):
								$post_term_term_id = $term->term_id;
							endforeach;

							$post_html .= '<div id="cpc_forum_post_comments">';

								foreach($comments as $comment) :

                                    // get read status for this comment (used by next couple of things)
                                    $read = get_comment_meta( $comment->comment_ID, 'cpc_forum_reply_read', true );

                                    // is this a new reply?
                                    $new_topic_reply = false;
                                    $comment_date = $base_date == 'post_date_gmt' ? $comment->comment_date_gmt : $comment->comment_date;
                                    if ($comment->user_id != $current_user->ID && cpc_since_last_logged_in($comment_date, $new_seconds)) {
                                        $new_topic_reply = true;
// echo 'new reply since previous login, so mark as new<br />';
                                        if ($new_item_read && $read && in_array($current_user->ID, $read)) {
                                            $new_topic_reply = false;
// echo 'but marking new comments as not new when read, so cancel<br />';
                                        }                                        
                                    }

									// Add read flag for this user for this reply
									if (is_user_logged_in()) {
										if (!$read):
											$read = array();
											$read[] = $current_user->ID;
										else:
											if (!in_array($current_user->user_login, $read) && !in_array($current_user->ID, $read)):
												$read[] = $current_user->ID;
											endif;
										endif;
										update_comment_meta ( $comment->comment_ID, 'cpc_forum_reply_read', $read);
                                    }
                                        
                                    $private = get_comment_meta( $comment->comment_ID, 'cpc_private_post', true );
                                    if (!$private || $current_user->ID == $post->post_author || $comment->user_id == $current_user->ID || current_user_can('manage_options')):

                                        $comment_html = '';
                                        $private_div_style = ($private && $current_user->ID != $comment->user_id) ? ' cpc_private_post_div' : '';
                                		$read_div_style = ($new_topic_reply) ? ' cpc_forum_post_new_reply' : ''; // new reply?
                                		$new_padding = ($new_topic_reply && $current_user->ID != $comment->user_id) ? 10 : 0; // new reply?
                                        $comment_html .= '<div class="cpc_forum_post_comment'.$private_div_style.$read_div_style.'" style="padding-left: '.($size+$new_padding).'px;">';
                                            if ($private) $comment_html .= '<div class="cpc_private_post" style="margin-left: -'.($size/2).'px;">'.$private_reply_msg.'</div>';

                                            $comment_html .= '<div class="cpc_forum_post_comment_author" style="max-width: '.($size).'px; margin-left: -'.($size).'px;">';
                                                if ($comment->user_id):
                                                    $comment_html .= '<div class="cpc_forum_post_comment_author_avatar">';
                                                        if (function_exists('user_avatar_get_avatar')):
                                                            if (get_user_by('id', $comment->user_id)):
                                                                $comment_html .= user_avatar_get_avatar( $comment->user_id, $size, true, 'thumb' );
                                                            else:
                                                                $comment_html .= user_avatar_get_avatar( 0, $size, true );
                                                            endif;
                                                        else:
                                                            if (get_user_by('id', $comment->user_id)):
                                                                $comment_html .= get_avatar( $comment->user_id, $size );
                                                            else:
                                                                $comment_html .= get_avatar( 0, $size );
                                                            endif;
                                                        endif;
                                                    $comment_html .= '</div>';
                                                    $comment_html .= '<div class="cpc_forum_post_comment_author_display_name">';
                                                        if (get_user_by('id', $comment->user_id)):
                                                            $cpc_display_name = cpc_display_name(array('user_id'=>$comment->user_id, 'link'=>1));
                                                        else:
                                                            $cpc_display_name = __('(unknown)', CPC2_TEXT_DOMAIN);
                                                        endif;
                                                        $comment_html .= $cpc_display_name;
                                                    $comment_html .= '</div>';
                                                else:
                                                    $comment_html .= '<div style="width:'.$size.'px; height:0"></div>';
                                                endif;
                                                $comment_html .= '<div class="cpc_forum_post_comment_author_freshness">';
                                                    $date = $base_date == 'post_date_gmt' ? $comment->comment_date_gmt : $comment->comment_date;
                                                    $cpc_timestamp = sprintf($date_format, human_time_diff(strtotime($date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                                                    $comment_html .= $cpc_timestamp;
                                                $comment_html .= '</div>';
                                            $comment_html .= '</div>';

                                            $comment_html .= '<div class="cpc_forum_post_comment_content">';

                                                $comment_html .= '<div class="cpc_author_meta_mobile">';
                                                    $comment_html .= '<span class="cpc_author_meta_mobile_display_name">'.$cpc_display_name.'</span>';
                                                    $comment_html .= ' <span class="cpc_author_meta_mobile_date">'.$cpc_timestamp.'</span>';
                                                $comment_html .= '</div>';

                                                $comment_html = apply_filters( 'cpc_forum_item_pre_comment_filter', $comment_html, $atts, $comment, $comment->comment_content );

                                                $user_can_edit_comment = $comment->user_id == $current_user->ID ? true : false;
                                                $user_can_edit_comment = apply_filters( 'cpc_forum_post_user_can_edit_comment_filter', $user_can_edit_comment, $comment, $current_user->ID, $post_term_term_id );
                                                $user_can_delete_comment = $comment->user_id == $current_user->ID ? true : false;
                                                $user_can_delete_comment = apply_filters( 'cpc_forum_post_user_can_delete_comment_filter', $user_can_delete_comment, $comment, $current_user->ID, $post_term_term_id );

                                                // Check if timed out
                                                $age = current_time('timestamp', 1) - strtotime($comment->comment_date);
                                                $timed_out = $age > $timeout ? true : false;
                                                $timed_out = apply_filters( 'cpc_forum_post_timed_out_filter', $timed_out, $current_user->ID, $age, $timeout, $term->term_id );
                                                if (!$enable_timeout) $timed_out = false; // disabled timeouts via shortcode option

                                                // Comment Settings
                                                if ( (($user_can_edit_comment || $user_can_delete_comment) && !$timed_out) || $is_forum_admin):

                                                    $comment_html .= '<div class="cpc_forum_settings">';
                                                        $comment_html .= '<img style="height:15px;width:15px;" src="'.plugins_url('images/wrench'.get_option('cpccom_icon_colors').'.svg', __FILE__).'" />';
                                                    $comment_html .= '</div>';
                                                    $comment_html .= '<div class="cpc_forum_settings_options">';

                                                        $url = cpc_curPageURL();
                                                        if ($user_can_edit_comment || $is_forum_admin) $comment_html .= '<a href="'.$url.cpc_query_mark($url).'forum_action=edit&comment_id='.$comment->comment_ID.'">'.__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
                                                        if (($user_can_edit_comment || $is_forum_admin) && $timeout-$age >= 0 && $enable_timeout) $comment_html .= '<br />('.sprintf(__('Sperre in %d Sekunden', CPC2_TEXT_DOMAIN), ($timeout-$age)).')';
                                                        if (($user_can_edit_comment && $user_can_delete_comment) || $is_forum_admin) $comment_html .= ' | ';
                                                        if ($user_can_delete_comment || $is_forum_admin) $comment_html .= '<a href="'.$url.cpc_query_mark($url).'forum_action=delete&comment_id='.$comment->comment_ID.'">'.__('Löschen', CPC2_TEXT_DOMAIN).'</a>';
                                                        if ($report) $comment_html .= ' | <a class="cpc_activity_settings_report" rel="'.$comment->comment_ID.'" href="mailto:'.$report_email.'?subject='.$url.'">'.$report_label.'</a>';
                                                    $comment_html .= '</div>';	

                                                elseif (is_user_logged_in() && $report):

                                                    $comment_html .= '<div class="cpc_forum_settings">';
                                                        $comment_html .= '<img style="height:15px;width:15px;" src="'.plugins_url('images/wrench'.get_option('cpccom_icon_colors').'.svg', __FILE__).'" />';
                                                    $comment_html .= '</div>';
                                                    $comment_html .= '<div class="cpc_forum_settings_options">';
                                                        $url = cpc_curPageURL();
                                                        $comment_html .= '<a class="cpc_activity_settings_report" rel="'.$comment->comment_ID.'" href="mailto:'.$report_email.'?subject='.$url.'">'.$report_label.'</a>';
                                                    $comment_html .= '</div>';	

                                                endif;           

                                                // Show the reply
                                                $comment_content = $comment->comment_content;
                                                $comment_content = cpc_formatted_content($comment_content);
                                                if ($new_topic_reply && $current_user->ID != $comment->user_id) $comment_content = '<div class="cpc_forum_new_label">'.convert_smilies($new_item_label).'</div> '.$comment_content;
                                                $comment_content = apply_filters( 'cpc_forum_item_comment_content_filter', $comment_content, $comment, $atts );

                                                $comment_html .= '<div class="cpc_forum_item_content_comment">'.$comment_content.'</div>';

                                                // Filter for handling anything else
                                                // Passes $comment_html, shortcodes options ($atts), mail comment ($comment), message ($comment->comment_content))
                                                $comment_html = apply_filters( 'cpc_forum_item_comment_filter', $comment_html, $atts, $comment, $comment->comment_content );

                                                // Comments to replies (if allowed)
                                                if ($show_comments):

                                                    // Show all comments so far

                                                    $args = array(
                                                        'status' => 1,
                                                        'orderby' => 'comment_ID',
                                                        'order' => 'ASC',
                                                        'post_id' => $post->ID,
                                                        'offset' => 0,
                                                        'number' => 100,
                                                        'parent' => $comment->comment_ID
                                                    );

                                                    $subcomments = get_comments($args);

                                                    if ($subcomments):

                                                        $comment_html .= '<div class="cpc_forum_post_subcomments">';

                                                            foreach($subcomments as $subcomment) :

                                                                // get read status for this comment (used by next couple of things)
                                                                $read = get_comment_meta( $subcomment->comment_ID, 'cpc_forum_comment_read', true );

                                                                // is this a new comment?
                                                                $new_comment_reply = false;
                                                                $subcomment_date = $base_date == 'post_date_gmt' ? $subcomment->comment_date_gmt : $subcomment->comment_date;
                                                                if ($subcomment->user_id != $current_user->ID && cpc_since_last_logged_in($subcomment_date, $new_seconds)) {
                                                                    $new_comment_reply = true;
                                                                    // echo 'new comment since previous login, so mark as new<br />';
                                                                    if ($new_item_read && $read && in_array($current_user->ID, $read)) {
                                                                        $new_comment_reply = false;
                                                                        // echo 'but marking new comments as not new when read, so cancel<br />';
                                                                    }
                                                                }

																// Add read flag for this user for this comment
																if (is_user_logged_in()) {
																	if (!$read):
																		$read = array();
																		$read[] = $current_user->ID;
																	else:
																		if (!in_array($current_user->user_login, $read) && !in_array($current_user->ID, $read)):
																			$read[] = $current_user->ID;
																		endif;
																	endif;
																	update_comment_meta ( $subcomment->comment_ID, 'cpc_forum_comment_read', $read);
                                                                }

                                                                $sub_comment_html = '';

																$read_div_style = ($new_comment_reply) ? ' cpc_forum_post_new_comment' : ''; // new topic?
                                								$new_padding = ($new_comment_reply && $current_user->ID != $subcomment->user_id) ? 10 : 0; // new comment?																
                                        						$sub_comment_html .= '<div id="cpc_forum_post_comments_'.$comment->comment_ID.'" class="cpc_forum_post_subcomment'.$read_div_style.'" style="padding-left: '.($comments_avatar_size+$new_padding).'px;">';

                                                                    $sub_comment_html .= '<div class="cpc_forum_post_comment_author" style="max-width: '.($comments_avatar_size).'px; margin-left: -'.($comments_avatar_size).'px;">';
                                                                        if ($subcomment->user_id):
                                                                            $sub_comment_html .= '<div class="cpc_forum_post_comment_author_avatar">';
                                                                                if (function_exists('user_avatar_get_avatar')):
                                                                                    if (get_user_by('id', $subcomment->user_id)):
                                                                                        $sub_comment_html .= user_avatar_get_avatar( $subcomment->user_id, $comments_avatar_size, true, 'thumb' );
                                                                                    else:
                                                                                        $sub_comment_html .= user_avatar_get_avatar( 0, $comments_avatar_size, true );
                                                                                    endif;
                                                                                else:
                                                                                    if (get_user_by('id', $subcomment->user_id)):
                                                                                        $sub_comment_html .= get_avatar( $subcomment->user_id, $comments_avatar_size );
                                                                                    else:
                                                                                        $sub_comment_html .= get_avatar( 0, $comments_avatar_size );
                                                                                    endif;
                                                                                endif;
                                                                            $sub_comment_html .= '</div>';
                                                                        else:
                                                                            $sub_comment_html .= '<div style="width:'.$comments_avatar_size.'px; height:0"></div>';
                                                                        endif;
                                                                    $sub_comment_html .= '</div>';

                                                                    $sub_comment_html .= '<div class="cpc_forum_post_comment_content">';

                                                                        $user_can_edit_comment = $subcomment->user_id == $current_user->ID ? true : false;
                                                                        $user_can_delete_comment = $subcomment->user_id == $current_user->ID ? true : false;

                                                                        // Comment Settings
                                                                        if ( ($user_can_edit_comment || $user_can_delete_comment) || $is_forum_admin ):

                                                                            $sub_comment_html .= '<div class="cpc_forum_settings">';
                                                                                $sub_comment_html .= '<img style="height:15px;width:15px;" src="'.plugins_url('images/wrench'.get_option('cpccom_icon_colors').'.svg', __FILE__).'" />';
                                                                            $sub_comment_html .= '</div>';
                                                                            $sub_comment_html .= '<div class="cpc_forum_settings_options">';
                                                                                $url = cpc_curPageURL();
                                                                                if ($user_can_edit_comment || $is_forum_admin) $sub_comment_html .= '<a href="'.$url.cpc_query_mark($url).'forum_action=edit&comment_id='.$subcomment->comment_ID.'">'.__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
                                                                                if (($user_can_edit_comment || $is_forum_admin) && $timeout-$age >= 0 && $enable_timeout) $sub_comment_html .= '<br />('.sprintf(__('Sperre in %d Sekunden', CPC2_TEXT_DOMAIN), ($timeout-$age)).')';
                                                                                if (($user_can_edit_comment && $user_can_delete_comment) || $is_forum_admin) $sub_comment_html .= ' | ';
                                                                                if ($user_can_delete_comment || $is_forum_admin) $sub_comment_html .= '<a href="'.$url.cpc_query_mark($url).'forum_action=delete&comment_id='.$subcomment->comment_ID.'">'.__('Löschen', CPC2_TEXT_DOMAIN).'</a>';
                                                                                if ($report) $sub_comment_html .= ' | <a class="cpc_activity_settings_report" rel="'.$subcomment->comment_ID.'" href="mailto:'.$report_email.'?subject='.$url.'">'.$report_label.'</a>';
                                                                            $sub_comment_html .= '</div>';	

                                                                        elseif  (is_user_logged_in() && $report):

                                                                            $sub_comment_html .= '<div class="cpc_forum_settings">';
                                                                                $sub_comment_html .= '<img style="height:15px;width:15px;" src="'.plugins_url('images/wrench'.get_option('cpccom_icon_colors').'.svg', __FILE__).'" />';
                                                                            $sub_comment_html .= '</div>';
                                                                            $sub_comment_html .= '<div class="cpc_forum_settings_options">';
                                                                                $url = cpc_curPageURL();
                                                                                $sub_comment_html .= '<a class="cpc_activity_settings_report" rel="'.$subcomment->comment_ID.'" href="mailto:'.$report_email.'?subject='.$url.'">'.$report_label.'</a>';
                                                                            $sub_comment_html .= '</div>';	

                                                                        endif;

                                                                        // Show the subcomment
						                                                $sub_comment_content = $subcomment->comment_content;
																		if ($new_comment_reply) $sub_comment_content = '<div style="float:left" class="cpc_forum_new_label">'.convert_smilies($new_item_label).'</div> '.$sub_comment_content;
						                                                $sub_comment_content = cpc_formatted_content($sub_comment_content);

                                                                        $sub_comment_content = apply_filters( 'cpc_forum_item_sub_comment_content_filter', $sub_comment_content, $subcomment, $atts );

                                                                        $sub_comment_content = '<div class="cpc_forum_item_content_subcomment">'.$sub_comment_content.'</div>';


                                                                        $sub_comment_author = '<div class="cpc_forum_post_comment_author_display_name">';
                                                                            if (get_user_by('id', $subcomment->user_id)):
                                                                                $sub_comment_author .= cpc_display_name(array('user_id'=>$subcomment->user_id, 'link'=>1));
                                                                            else:
                                                                                $sub_comment_author .= __('(Unbekannt)', CPC2_TEXT_DOMAIN);
                                                                            endif;
                                                                        $sub_comment_author .= '</div>';

                                                                        $sub_comment_date = '<div class="cpc_forum_post_comment_author_freshness">';
                                                                            $date = $base_date == 'post_date_gmt' ? $subcomment->comment_date_gmt : $subcomment->comment_date;
                                                                            $sub_comment_date .= sprintf($date_format, human_time_diff(strtotime($date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                                                                        $sub_comment_date .= '</div>';
                                                                        $sub_comment_html .= $sub_comment_author . $sub_comment_date . $sub_comment_content;

                                                                        // Filter for handling anything else
                                                                        $sub_comment_html = apply_filters( 'cpc_forum_item_sub_comment_filter', $sub_comment_html, $atts, $subcomment, $sub_comment_content );

                                                                    $sub_comment_html .= '</div>';

                                                                $sub_comment_html .= '</div>';

                                                                $comment_html .= $sub_comment_html;

                                                            endforeach;

                                                        $comment_html .= '</div>';

                                                    endif;

                                                    if ($allow_comments&& !$locked && $post->comment_status != 'closed' && is_user_logged_in()):

                                                        $show = $show_comment_form ? '' : 'display:none';
                                                        $sub_comment_form = '<div id="sub_comment_div_'.$comment->comment_ID.'" class="cpc_forum_post_comment_div" style="'.$show.'">';
                                                            $sub_comment_form .= '<textarea id="sub_comment_'.$comment->comment_ID.'" class="cpc_forum_post_comment_form"></textarea>';
                                                        $sub_comment_form .= '</div>';
                                                        $sub_comment_form .= '<button class="cpc_button cpc_forum_post_comment_form_submit '.$comment_class.'" data-post-id="'.$post->ID.'" data-size="'.$comments_avatar_size.'" rel="'.$comment->comment_ID.'">'.$comment_add_label.'</button>';
                                                        $comment_html .= $sub_comment_form;

                                                    endif;

                                                endif;

                                            $comment_html .= '</div>';

                                        $comment_html .= '</div>';

                                        $comment_html = apply_filters( 'cpc_forum_post_comment_filter', $comment_html, $comment, $atts, $current_user->ID );

                                        $post_html .= $comment_html;

                                    endif;

								endforeach;

							$post_html .= '</div>';

						endif;

						// Pending replies
						$args = array(
							'status' => 0,
							'orderby' => 'comment_date',
							'order' => 'ASC',
							'post_id' => $post->ID,
						);

						$comments = get_comments($args);

						if ($comments):

							$post_html .= '<div class="cpc_forum_post_comments">';

								foreach($comments as $comment) :

									if (current_user_can('edit_posts') || $comment->user_id = $current_user->ID):

										$comment_html = '';

										$comment_html .= '<div class="cpc_forum_post_comment_pending" style="padding-left: '.($size).'px;">';

											$comment_html .= '<div class="cpc_forum_post_comment_author" style="margin-left: -'.($size).'px;">';
												$comment_html .= '<div class="cpc_forum_post_comment_author_avatar">';
													$comment_html .= user_avatar_get_avatar( $comment->user_id, $size, true, 'thumb' );
												$comment_html .= '</div>';
												$comment_html .= '<div class="cpc_forum_post_comment_author_display_name">';
													$comment_html .= cpc_display_name(array('user_id'=>$comment->user_id, 'link'=>1));
												$comment_html .= '</div>';
												$comment_html .= '<div class="cpc_forum_post_comment_author_freshness">';
													$date = $base_date == 'post_date_gmt' ? $comment->comment_date_gmt : $comment->comment_date;
													$comment_html .= sprintf($date_format, human_time_diff(strtotime($date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
												$comment_html .= '</div>';
											$comment_html .= '</div>';

											$comment_html .= '<div class="cpc_forum_post_comment_content">';
												if ($comment->comment_approved != 'publish') $post_html .= '<div class="cpc_forum_post_comment_pending">'.$comment_pending.'</div>';

                                                // Pending reply
												$comment_content_html = cpc_formatted_content($comment->comment_content);
												$comment_content_html = apply_filters( 'cpc_forum_item_content_filter', $comment->comment_content, $atts );
                                                $comment_html .= '<div class="cpc_forum_item_content_comment">'.$comment_content.'</div>';

											$comment_html .= '</div>';

										$comment_html .= '</div>';

										$comment_html = apply_filters( 'cpc_forum_post_comment_pending_filter', $comment_html, $comment, $atts, $current_user->ID );							

										$post_html .= $comment_html;

									endif;

								endforeach;

							$post_html .= '</div>';

						endif;

						if ($pages > 1 && $pagination && $pagination_bottom)
                            $post_html .= '<div id="cpc_forum_pagination_bottom">'.$pagination_html.'</div>';


                        // Parse for protected email addresses (to handle tags)
                        $post_html = str_replace('[@]', '@', $post_html);

						$html .= $post_html;

					else:

						$html .= $secure_post_msg;

					endif;

				else:

					$html .= $secure_post_msg;

				endif;

			endif;

		endwhile;
		wp_reset_query();

	else:

		$html .= 'Ooops ('.$slug.'/'.$post_slug.')';

	endif;

endif;



?>