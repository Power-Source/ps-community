<?php
require_once plugin_dir_path(__FILE__).'cpc_forum_toolbar.php';
/* **** */ /* INIT */ /* **** */

function cpc_forum_init() {
    // JS and CSS
    wp_enqueue_script('cpc-forum-js', plugins_url('cpc_forum.js', __FILE__), array('jquery'));	
    wp_localize_script( 'cpc-forum-js', 'cpc_forum_ajax', array( 
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'is_admin' => current_user_can('manage_options'),
    ) );		
    wp_enqueue_style('cpc-forum-css', plugins_url('cpc_forum.css', __FILE__), array(), '1.0');
    // Select2 replacement drop-down list from core (ready for dependenent plugins like who-to that only uses hooks/filters)
    wp_enqueue_script('cpc-forum-select2-js', plugins_url('../js/select2.js', __FILE__), array('jquery'));	
    wp_enqueue_style('cpc-forum-select2-css', plugins_url('../js/select2.css', __FILE__), array(), '1.0');

    // HIER: BBCode Toolbar CSS laden
    if (defined('CPC_FORUM_TOOLBAR') && get_option('cpc_com_toolbar') == 'bbcodes') {
        wp_enqueue_style(
            'cpc-forum-toolbar-css',
            plugins_url('cpc_forum_toolbar.css', __FILE__),
            array(),
            '1.0'
        );
    }

    // Anything else?
    do_action('cpc_forum_init_hook');
}
/* ********** */ /* SHORTCODES */ /* ********** */

function cpc_forum_page($atts) {

	// Init
	add_action('wp_footer', 'cpc_forum_init');

	$html = '';

	global $current_user;

	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_forum_page');    
	extract( shortcode_atts( array(
		'slug' => '',
        'style' => cpc_get_shortcode_value($values, 'cpc_forum_page-style', 'table'), // layout look and feel, table|classic
		'show' => cpc_get_shortcode_value($values, 'cpc_forum_page-show', false),
		'header_title' => cpc_get_shortcode_value($values, 'cpc_forum_page-header_title', __('Thema', CPC2_TEXT_DOMAIN)),
		'header_count' => cpc_get_shortcode_value($values, 'cpc_forum_page-header_count', __('Antworten', CPC2_TEXT_DOMAIN)),
		'header_last_activity' => cpc_get_shortcode_value($values, 'cpc_forum_page-header_last_activity', __('Letzte Aktivität', CPC2_TEXT_DOMAIN)),
		'base_date' => cpc_get_shortcode_value($values, 'cpc_forum_page-base_date', 'post_date_gmt'),
        'styles' => true,
	), $atts, 'cpc_forum_page' ) );

	if ($slug == ''):

		$html .= sprintf(__('Bitte füge slug="xxx" zum Shortcode hinzu, wobei xxx der <a href="%s">Slug des Forums</a> ist. Beispiel: [cpc-forum-page slug="my-general-forum"].', CPC2_TEXT_DOMAIN), admin_url('edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post'));

	else:

		$html .= cpc_forum_post(array('slug'=>$slug, 'show'=>$show));
		$html .= cpc_forum_backto(array('slug'=>$slug));
		$html .= cpc_forum(array('slug'=>$slug, 'style'=>$style,'header_title'=>$header_title, 'base_date'=>$base_date, 'header_count'=>$header_count, 'header_last_activity' => $header_last_activity));
		$html .= cpc_forum_comment(array('slug'=>$slug));

	endif;

    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_forum_page', '', '', $styles, $values);
    
	return $html;

}

function cpc_forum_show_posts($atts) {

	// Init
	add_action('wp_footer', 'cpc_forum_init');

	$html = '';

	global $current_user;
    
	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_forum_show_posts');    
	extract( shortcode_atts( array(
		'slug' => '',
		'order' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-order', 'date'),
		'orderby' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-orderby', 'DESC'),
		'status' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-status', ''), // all (or '')|open|closed
		'include_posts' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-include_posts', true),
		'include_replies' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-include_replies', true),
		'include_comments' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-include_comments', false),
		'include_closed' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-include_closed', true),
        'summary' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-summary', false),
        'summary_format' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-summary_format', __('%s %s %s %s vor %s', CPC2_TEXT_DOMAIN)), // eg: [robert] [replied to] [This topic] [5 mins] ago [the snippet]
        'summary_started' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-summary_started', __('gestartet', CPC2_TEXT_DOMAIN)),
        'summary_replied' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-summary_replied', __('geantwortet auf', CPC2_TEXT_DOMAIN)),
        'summary_commented' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-summary_commented', __('kommentiert zu', CPC2_TEXT_DOMAIN)),
        'summary_title_length' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-summary_title_length', 150),
        'summary_snippet_length' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-summary_snippet_length', 50),
        'summary_avatar_size' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-summary_avatar_size', 32),
        'summary_show_unread' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-summary_show_unread', true),
		'closed_prefix' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-closed_prefix', __('geschlossen', CPC2_TEXT_DOMAIN)),
		'show_author' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-show_author', true),
		'author_format' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-author_format', __('Von %s', CPC2_TEXT_DOMAIN)),
		'author_link' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-author_link', true),
		'show_date' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-show_date', true),
		'date_format' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-date_format', __('vor %s', CPC2_TEXT_DOMAIN)),
		'show_snippet' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-show_snippet', true),
		'more_link' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-more_link', __('lesen', CPC2_TEXT_DOMAIN)),
		'no_posts' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-no_posts', __('Keine Beiträge', CPC2_TEXT_DOMAIN)),
		'title_length' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-title_length', 50),
		'snippet_length' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-snippet_length', 30),
		'base_date' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-base_date', 'post_date_gmt'),
		'max' => cpc_get_shortcode_value($values, 'cpc_forum_show_posts-max', 10),
		'before' => '',
		'styles' => true,
        'after' => '',
	), $atts, 'cpc_forum_show_posts' ) );

	// Shortcode parameters for "new!" items
    $values = cpc_get_shortcode_options('cpc_forum');    
	extract( shortcode_atts( array(
        'new_item' => cpc_get_shortcode_value($values, 'cpc_forum-new_item', true),
        'new_seconds' => cpc_get_shortcode_value($values, 'cpc_forum-new_seconds', 259200),
        'new_item_read' => cpc_get_shortcode_value($values, 'cpc_forum-new_item_read', true),
        'new_item_label' => cpc_get_shortcode_value($values, 'cpc_forum-new_item_label', __('NEU!', CPC2_TEXT_DOMAIN)),
	), $atts, 'cpc_forum' ) );
    
	$forum_posts = array();
	global $post, $current_user;
    
    // Translate include_closed
    $status = ($include_closed) ? '' : 'open';

	// Get posts
	if ($include_posts):
		$loop = new WP_Query( array(
			'post_type' => 'cpc_forum_post',
			'post_status' => 'publish',
			'posts_per_page' => (($max * 10)+100),
		) );
    
		if ($loop->have_posts()):

			$forum_posts = array();

			while ( $loop->have_posts() ) : $loop->the_post();

				if ($status == 'all' || $status == '' || $status == $post->comment_status):

					if ($include_closed || $post->comment_status == 'open'):

						$forum_post = array();
						$forum_post['ID'] = $post->ID;
						$forum_post['comment_ID'] = false;									
						$forum_post['post_author'] = $post->post_author;
						$forum_post['post_name'] = $post->post_name;
						$forum_post['post_title'] = $post->post_title;
						$forum_post['post_title_lower'] = strtolower($post->post_title);
						$forum_post['post_date'] = $post->post_date;
						$forum_post['post_date_gmt'] = $post->post_date_gmt;
						$forum_post['post_content'] = $post->post_content;
						$forum_post['comment_status'] = $post->comment_status;
						$forum_post['type'] = 'post';
                        // default read status to true (ie. not new)
                		$forum_post['read'] = true;

						$forum_posts['p_'.$post->ID] = $forum_post;

					endif;

				endif;

			endwhile;

		endif;

	endif;

	// Get replies
	if ($include_replies):

		global $wpdb;
		$sql = "SELECT * FROM ".$wpdb->prefix."comments c LEFT JOIN ".$wpdb->prefix."posts p ON c.comment_post_ID = p.ID WHERE comment_approved=1 AND comment_parent=0 AND user_id>0 AND p.post_type = %s ORDER BY comment_ID DESC LIMIT %d, %d";
		$comments = $wpdb->get_results($wpdb->prepare($sql, 'cpc_forum_post', 0, ($max * 10)));

		if ($comments):
			foreach($comments as $comment):
    
                $parent_post = get_post($comment->comment_post_ID);
                $private = get_comment_meta( $comment->comment_ID, 'cpc_private_post', true );
                if (!$private || $current_user->ID == $parent_post->post_author || $comment->user_id == $current_user->ID || current_user_can('manage_options')):
    
                    $forum_post = array();
					$forum_post['comment_ID'] = $comment->comment_ID;			                
                    $forum_post['post_author'] = $comment->user_id;
                    $forum_post['post_date'] = $comment->comment_date;
                    $forum_post['post_date_gmt'] = $comment->comment_date_gmt;
                    $forum_post['post_content'] = $comment->comment_content;

                    if ($parent_post->post_status == 'publish'):

                        if ($include_closed || $parent_post->comment_status == 'open'):

                            $forum_post['ID'] = $parent_post->ID;
                            $forum_post['post_name'] = $parent_post->post_name;
                            $forum_post['post_title'] = $parent_post->post_title;
                            $forum_post['post_title_lower'] = strtolower($parent_post->post_title);
                            $forum_post['comment_status'] = $parent_post->comment_status;
                            // default read status to true (ie. not new)
                			$forum_post['read'] = true;

                            $forum_post['type'] = 'reply';
                            $forum_posts['r_'.$comment->comment_ID] = $forum_post;

                        endif;

                    endif;
		    
                endif;
			
			endforeach;

		endif;

	endif;

	// Get comments
	if ($include_comments):

		global $wpdb;
		$sql = "SELECT * FROM ".$wpdb->prefix."comments c LEFT JOIN ".$wpdb->prefix."posts p ON c.comment_post_ID = p.ID WHERE comment_approved=1 AND comment_parent>0 AND p.post_type = %s ORDER BY comment_ID DESC LIMIT %d, %d";
		$comments = $wpdb->get_results($wpdb->prepare($sql, 'cpc_forum_post', 0, ($max * 10)));

		if ($comments):
			foreach($comments as $comment):

				$forum_post = array();
				$forum_post['comment_ID'] = $comment->comment_ID;			
				$forum_post['post_author'] = $comment->user_id;
				$forum_post['post_date'] = $comment->comment_date;
				$forum_post['post_date_gmt'] = $comment->comment_date_gmt;
				$forum_post['post_content'] = $comment->comment_content;

				$parent_post = get_post($comment->comment_post_ID);
				if ($parent_post->post_status == 'publish'):

					if ($include_closed || $parent_post->comment_status == 'open'):

						$parent_comment = get_comment($comment->comment_parent);
						$parent_private = get_comment_meta( $comment->comment_parent, 'cpc_private_post', true );
						$can_see = false;
						if (!$parent_private) {
							$can_see = true; // not a private reply
						} else {
							if ($parent_post->post_author == $current_user->ID || $parent_comment->user_id == $current_user->ID) {
								$can_see = true; // author of original post or private reply, so can see
							} else {
								if ($comment->user_id == $current_user->ID)
									$can_see = true; // author of this comment
							}
						}
						if ($can_see):

							$forum_post['ID'] = $parent_post->ID;
							$forum_post['post_name'] = $parent_post->post_name;
							$forum_post['post_title'] = $parent_post->post_title;
							$forum_post['post_title_lower'] = strtolower($parent_post->post_title);
							$forum_post['comment_status'] = $parent_post->comment_status;
	                        // default read status to true (ie. not new)
	                		$forum_post['read'] = true;

							$forum_post['type'] = 'comment';
							$forum_posts['c_'.$comment->comment_ID] = $forum_post;

						endif;
					endif;

				endif;
			
			endforeach;
		endif;

	endif;

	// Show results
	if ( !empty( $forum_posts ) ):

		// Sort the posts by "order", then name
		$sort = array();
		$order = $order != 'title' ? $order : 'title_lower';
		$order = 'post_'.$order;
		foreach($forum_posts as $k=>$v) {
		    $sort[$order][$k] = $v[$order];
		    $sort['post_title'][$k] = $v['post_title'];
		}
		$orderby = strtoupper($orderby);
		if ($orderby != 'RAND'):
			$orderby = $orderby == "ASC" ? SORT_ASC : SORT_DESC;
			array_multisort($sort[$order], $orderby, $sort['post_title'], $orderby, $forum_posts);
		else:
			uksort($forum_posts, "cpc_rand_cmp");
		endif;

        // keep track of new's shown
        $new_array = array();

		// Show results
		$html .= '<div class="cpc_forum_get_posts">';

			$c = 0;
			$continue = true;
			$previous_title = '';

			foreach ($forum_posts as $forum_post):

				$post_terms = get_the_terms( $forum_post['ID'], 'cpc_forum' );

				if( $post_terms && !is_wp_error( $post_terms ) ):
				    foreach( $post_terms as $term ):

				    	if (!$slug || $slug == $term->slug):

							if (user_can_see_forum($current_user->ID, $term->term_id) || current_user_can('manage_options')):

								// Only see own posts?
								if (user_can_see_post($current_user->ID, $forum_post['ID'])):

									$forum_html = '';
									$forum_html .= '<div class="cpc_forum_get_post">';

                                        // New label (get list of those read this post/reply/comment)?
										if ($forum_post['type'] == 'post'):
											$read = get_post_meta( $forum_post['ID'], 'cpc_forum_read', true );
                                        elseif ($forum_post['type'] == 'reply'):
											$read = get_comment_meta( $forum_post['comment_ID'], 'cpc_forum_reply_read', true );
                                        else:
                                        	$read = get_comment_meta( $forum_post['comment_ID'], 'cpc_forum_comment_read', true );
                                        endif;                                             	
										$new_element = ( cpc_since_last_logged_in($forum_post[$base_date],$new_seconds) && ($forum_post['post_author'] != $current_user->ID) && (!$read || (!in_array($current_user->user_login, $read) && !in_array($current_user->ID, $read))) );

                                        $summary_title = '';
                                        // ... check if same title in the same forum
										if ($previous_title != esc_attr($forum_post['post_name']) || $summary):
											$forum_html .= '<div class="';
												if ($forum_post['type'] == 'post'):
													$read = get_post_meta( $forum_post['ID'], 'cpc_forum_read', true );
                                                elseif ($forum_post['type'] == 'reply'):
													$read = get_comment_meta( $forum_post['comment_ID'], 'cpc_forum_reply_read', true );
                                                else:
                                                	$read = get_comment_meta( $forum_post['comment_ID'], 'cpc_forum_comment_read', true );
                                                endif; 
						                        $forum_html .= 'cpc_forum_get_title">';

												if ( cpc_using_permalinks() ):

													if (is_multisite()) {

														$blog_details = get_blog_details($blog->blog_id);
														$url = $blog_details->path.$term->slug.'/'.$forum_post['post_name'];

													} else {

														$url = '/'.$term->slug.'/'.$forum_post['post_name'];

													}
												
												else:

													if (!is_multisite()):
														$forum_page_id = cpc_get_term_meta($term->term_id, 'cpc_forum_cat_page', true);
														$url = "/?page_id=".$forum_page_id."&topic=".$forum_post['post_name'];
													else:
														$forum_page_id = cpc_get_term_meta($term->term_id, 'cpc_forum_cat_page', true);
														$blog_details = get_blog_details($blog->blog_id);
														$url = $blog_details->path."?page_id=".$forum_page_id."&topic=".$forum_post['post_name'];
													endif;

												endif;
    
												$the_title = esc_attr($forum_post['post_title']);
												$the_title = str_replace(
												  array('[', ']', '<', '>'), 
												  array('&#91;', '&#93;', '&lt;', '&gt;'), 
												  $the_title
												);				
                                                if (strlen($the_title) > $title_length):
                                                    $the_title = preg_replace("/&#?[a-z0-9]+;/i","",$the_title);
                                                    $the_title = substr($the_title, 0, $title_length).'...';
                                                endif;
												if ($forum_post['comment_status'] == 'closed' && $closed_prefix) $the_title = '['.$closed_prefix.'] '.$the_title;
												$class = (!$show_snippet && $new_element && !in_array($forum_post['ID'], $new_array)) ? 'cpc_forum_post_new_show_posts' : '';
												$forum_html .= '<h3 class="cpc_forum_post_show_posts '.$class.'">';
                                                if (!$show_snippet) $forum_html .= '<a href="'.$url.'">';
                                                $forum_html .= $the_title;
                                                if (!$show_snippet) $forum_html .= '</a>';
                                                if (!$show_snippet && $new_element && !in_array($forum_post['ID'], $new_array)):
                                                    $forum_html .= ' <span class="cpc_forum_new_label">'.convert_smilies($new_item_label).'</span>';
                                                    $new_array[] = $forum_post['ID'];
                                                endif;
                                                $forum_html .= '</h3>';
                                                if ($summary && strlen($the_title) > $summary_title_length) $the_title = substr($the_title, 0, $summary_title_length).'...';
                                                $summary_title = '<a href="'.$url.'">'.$the_title.'</a>';
    
                                                $forum_html .= '</div>';
											$previous_title = esc_attr($forum_post['post_name']);
										endif;

										if ($show_date):
											$forum_html .= '<div class="cpc_forum_get_date">';
                                                $the_date = human_time_diff(strtotime($forum_post[$base_date]), current_time('timestamp', 1));
												$forum_html .= sprintf($date_format, $the_date);
                                                $summary_date = $the_date;
											$forum_html .= '</div>';										
                                        else:
                                            $summary_date = false;
										endif;

										if ($show_author):
											$forum_html .= '<div class="cpc_forum_get_author">';
                                                $summary_author_id = $forum_post['post_author'];
                                                $the_author = cpc_display_name(array('user_id'=>$summary_author_id, 'link'=>$author_link));
												$forum_html .= sprintf($author_format, $the_author);
                                                $summary_author = $the_author;
												if ($new_element && !$show_snippet) $forum_html .= ' <span class="cpc_forum_new_label">'.convert_smilies($new_item_label).'</span>';        
											$forum_html .= '</div>';		
                                        else:
                                            $summary_author = false;
										endif;

										if ($forum_post['type'] == 'post'):
											$read = get_post_meta( $forum_post['ID'], 'cpc_forum_read', true );
                                        elseif ($forum_post['type'] == 'reply'):
											$read = get_comment_meta( $forum_post['comment_ID'], 'cpc_forum_reply_read', true );
                                        else:
                                        	$read = get_comment_meta( $forum_post['comment_ID'], 'cpc_forum_comment_read', true );
                                        endif; 
										if ($show_snippet):

											$forum_html .= '<div class="';
                                                if (!$new_element):
                                                    $forum_html .= 'cpc_forum_get_snippet';
                                                else:
                                                    $forum_html .= 'cpc_forum_post_new_show_posts_reply_or_comment';
                                                endif;
						                        $forum_html .= '">';

                                                $content = cpc_formatted_content($forum_post['post_content']);
    
                                                $snippet_text = strip_tags($content);
												$snippet_text = preg_replace('#\[[^\]]+\]#', '', $snippet_text); // also strip out BBcodes
												$snippet_text = cpc_get_words($snippet_text, $snippet_length);
                                                $snippet_text = cpc_make_clickable($snippet_text);
												$forum_html .= $snippet_text;
                                                $summary_snippet = $snippet_text;
                                                if ($summary):
                                                    if ($summary_snippet_length) {
                                                        if (strlen($summary_snippet) > $summary_snippet_length)
                                                            $summary_snippet = strip_tags(substr($summary_snippet, 0, $summary_snippet_length)).'...';
                                                    } else {
                                                        $summary_snippet = '';
                                                    }
                                                endif;
                                                $forum_html .= ' <a href="'.$url.'">'.$more_link.'</a>';
                                                if ($new_element) $forum_html .= ' <span class="cpc_forum_new_label">'.convert_smilies($new_item_label).'</span>';        
											$forum_html .= '</div>';
										endif;

									$forum_html .= '</div>';
    
                                    // Summary version?
                                    if ($summary) {
                                        if ($summary_author_id) {
                                            $style = $summary_avatar_size ? ' style="position:relative;padding-left: '.($summary_avatar_size+10).'px"' : '';
                                            $read_style = (!$summary_show_unread || $forum_post['read'] || (is_user_logged_in() && $summary_author_id == $current_user->ID)) ? '' : ' cpc_forum_post_unread';
                                            $forum_html = '<div class="cpc_forum_get_post'.$read_style.'"'.$style.'>';
                                                if ($forum_post['type'] == 'post'):
                                                    $summary_action = $summary_started;
                                                elseif ($forum_post['type'] == 'reply'):
                                                    $summary_action = $summary_replied;
                                                else:
                                                    $summary_action = $summary_commented;
                                                endif;
                                                if ($summary_avatar_size):
                                                    $forum_html .= '<div class="cpc_summary_avatar" style="float: left; margin-left: -'.($summary_avatar_size+10).'px">';
                                                        $forum_html .= user_avatar_get_avatar($summary_author_id, $summary_avatar_size, true, 'thumb');
                                                    $forum_html .= '</div>';
                                                endif;
                                                $forum_html .= '<div class="cpc_summary_post">';
													$summary_snippet_new = $new_element ? ' <span class="cpc_forum_new_label">'.convert_smilies($new_item_label).'</span>' : '';                                                        
                                                    $forum_html .= sprintf($summary_format, '<span class="cpc_summary_author">'.$summary_author.'</span>', '<span class="cpc_summary_action">'.$summary_action.'</span>', '<span class="cpc_summary_title">'.$summary_title.'</span>', '<span class="cpc_summary_date">'.$summary_date.'</span>', '<span class="cpc_summary_snippet">'.cpc_bbcode_replace($summary_snippet).$summary_snippet_new.'</span>');
                                                $forum_html .= '</div>';
                                            $forum_html .= '</div>';
                                            $c++;
                                            if ($c == $max) $continue = false;
                                            $forum_html = apply_filters( 'cpc_forum_get_post_item', $forum_html );
                                            $html .= $forum_html;
                                        }
                                    } else {
										$c++;
                                        if ($c == $max) $continue = false;
                                        $forum_html = apply_filters( 'cpc_forum_get_post_item', $forum_html );
                                        $html .= $forum_html;
                                    }

								endif;

							endif;

							if (!$continue) break; // maximum reached

						endif;

				    endforeach;

				endif;

				if (!$continue) break; // maximum reached

			endforeach;

			if (!$c) $html .= $no_posts;

		$html .= '</div>';

	else:

		$html .= $no_posts;

	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_forum_show_posts', $before, $after, $styles, $values);

	wp_reset_query();
	
	return $html;

}

function cpc_forum_backto($atts) {

	// Init
	add_action('wp_footer', 'cpc_forum_init');

	$html = '';

	if ( get_query_var('topic') || isset($_GET['topic_id'])): // showing a single post

		global $current_user;

		// Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_forum_backto');    
		extract( shortcode_atts( array(
			'slug' => '',
			'label' => cpc_get_shortcode_value($values, 'cpc_forum_backto-label', __('Zurück zu %s...', CPC2_TEXT_DOMAIN)),
			'before' => '',
			'styles' => true,
            'after' => '',
		), $atts, 'cpc_forum_backto' ) );

		if ($slug == ''):

			$html .= __('Bitte füge slug="xxx" zum Shortcode hinzu, wobei xxx der Slug des Forums ist.', CPC2_TEXT_DOMAIN);

		else:

			$term = get_term_by( 'slug', $slug, 'cpc_forum' );
			if (user_can_see_forum($current_user->ID, $term->term_id) || current_user_can('manage_options')):

				$page_id = cpc_get_term_meta($term->term_id, 'cpc_forum_cat_page', true);
				if ( cpc_using_permalinks() ):
                    $url = get_permalink($page_id);
                    $html .= '<a href="'.$url.'">'.sprintf($label, $term->name).'</a>';
				else:
					if (!is_multisite()):
						$html .= '<a href="'.get_bloginfo('url')."/?page_id=".$page_id.'">'.sprintf($label, $term->name).'</a>';
					else:
						$blog_details = get_blog_details(get_current_blog_id());
						$url = $blog_details->path."?page_id=".$page_id;
						$html .= '<a href="'.$url.'">'.sprintf($label, $term->name).'</a>';
					endif;
				endif;

			endif;

		endif;

		if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_forum_backto', $before, $after, $styles, $values);

	endif;

	return $html;

}


function cpc_forum_comment($atts) {

	// Init
	add_action('wp_footer', 'cpc_forum_init');

	$html = '';

	if ((!isset($_GET['forum_action']) || ($_GET['forum_action'] != 'edit' && $_GET['forum_action'] != 'delete')) && (get_query_var('topic') || isset($_GET['topic_id'])) ): // showing a single post

		global $current_user;

		// Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_forum_comment');
		extract( shortcode_atts( array(
			'class' => cpc_get_shortcode_value($values, 'cpc_forum_comment-class', ''),
			'content_label' => cpc_get_shortcode_value($values, 'cpc_forum_comment-content_label', ''),
			'label' => cpc_get_shortcode_value($values, 'cpc_forum_comment-label', __('Antwort hinzufügen', CPC2_TEXT_DOMAIN)),
			'private_msg' => cpc_get_shortcode_value($values, 'cpc_forum_comment-private_msg', ''),
			'locked_msg' => cpc_get_shortcode_value($values, 'cpc_forum_comment-locked_msg', __('Dieses Forum ist gesperrt. Neue Beiträge und Antworten sind nicht erlaubt.', CPC2_TEXT_DOMAIN).' '),
            'no_permission_msg' => cpc_get_shortcode_value($values, 'cpc_forum_comment-no_permission_msg', __('Du hast keine Berechtigung, in diesem Forum zu antworten.', CPC2_TEXT_DOMAIN)),
			'moderate' => cpc_get_shortcode_value($values, 'cpc_forum_comment-moderate', false),
			'show' => cpc_get_shortcode_value($values, 'cpc_forum_comment-show', true),
			'moderate_msg' => cpc_get_shortcode_value($values, 'cpc_forum_comment-moderate_msg', __('Dein Kommentar erscheint, sobald er moderiert wurde.', CPC2_TEXT_DOMAIN)),
			'allow_close' => cpc_get_shortcode_value($values, 'cpc_forum_comment-allow_close', true),
			'close_msg' => cpc_get_shortcode_value($values, 'cpc_forum_comment-close_msg', __('Klicke hier, um diesen Beitrag zu schließen', CPC2_TEXT_DOMAIN)),
			'comments_closed_msg' => cpc_get_shortcode_value($values, 'cpc_forum_comment-comments_closed_msg', __('Dieser Beitrag ist geschlossen.', CPC2_TEXT_DOMAIN)),
			'reopen_label' => cpc_get_shortcode_value($values, 'cpc_forum_comment-reopen_label', __('Öffne diesen Beitrag erneut', CPC2_TEXT_DOMAIN)),
            'allow_one' => cpc_get_shortcode_value($values, 'cpc_forum_comment-allow_one', false),
			'allow_one_msg' => cpc_get_shortcode_value($values, 'cpc_forum_comment-allow_one_msg', __('Du kannst in diesem Forum nur einmal antworten.', CPC2_TEXT_DOMAIN)),
            'allow_private' => cpc_get_shortcode_value($values, 'cpc_forum_comment-allow_private', 'disabled'),
            'private_reply_check_msg' => cpc_get_shortcode_value($values, 'cpc_forum_comment-private_reply_check_msg', __('Antwort nur mit %s teilen', CPC2_TEXT_DOMAIN)),
			'show_in_label' => cpc_get_shortcode_value($values, 'cpc_forum_comment-show_in_label', __('Zeigen in:', CPC2_TEXT_DOMAIN)),
			'slug' => '',
			'before' => '',
			'styles' => true,
            'after' => '',
		), $atts, 'cpc_forum_comment' ) );

        if ($slug == ''):

			$html .= __('Bitte füge slug="xxx" zum Shortcode hinzu, wobei xxx der Slug des Forums ist.', CPC2_TEXT_DOMAIN);

		else:
    
			$term = get_term_by( 'slug', $slug, 'cpc_forum' );
			if (is_user_logged_in() && user_can_see_forum($current_user->ID, $term->term_id) || current_user_can('manage_options')):

				if (!cpc_get_term_meta($term->term_id, 'cpc_forum_closed', true) || current_user_can('manage_options') ):

					if (!isset($_GET['topic_id'])):
						$post_slug = get_query_var('topic');
					else:
						$the_post = get_post($_GET['topic_id']);
						if ($the_post):
							$post_slug = $the_post->post_name;
						else:
							$html .= '<div class="cpc_error">'.__('Forumbeitrag mit topic_id konnte nicht gefunden werden', CPC2_TEXT_DOMAIN).'</div>';
						endif;
					endif;

					$args=array(
						'name' => $post_slug,
						'post_type' => 'cpc_forum_post',
						'posts_per_page' => 1
					);
					$my_posts = get_posts( $args );
					if ( $my_posts ):

						if (user_can_see_post($current_user->ID, $my_posts[0]->ID)):
    
                            $user_can_comment = is_user_logged_in();
                            // Filter to check if can comment
                            $user_can_comment = apply_filters( 'cpc_forum_post_user_can_comment_filter', $user_can_comment, $current_user->ID, $term->term_id );

                            if ($user_can_comment || current_user_can('manage_options')):

                                $form_html = '';

                                if ($my_posts[0]->comment_status != 'closed'):

                                    $form_html .= '<div id="cpc_forum_comment_div">';
    
                                        $continue = true;
                                        if ($allow_one):
    
                                            $args = array(
                                                'status' => 1,
                                                'orderby' => 'comment_date',
                                                'order' => 'ASC',
                                                'post_id' => $my_posts[0]->ID,
                                                'parent' => 0
                                            );

                                            $comments = get_comments($args);
                                            if ($comments):  
                                                foreach ($comments as $comment):
                                                    if ($comment->user_id == $current_user->ID):
                                                        $form_html .= $allow_one_msg;
                                                        $continue = false;
                                                    endif;
                                                endforeach;
                                            endif;
    
                                        endif;
    
                                        if ($continue):
    
                                            $form_html .= '<div id="cpc_forum_comment_form"';

                                                if (!$show) $form_html .= ' style="display:none;"';
                                                $form_html .= '>';

                                                $form_html .= '<form enctype="multipart/form-data" id="cpc_forum_comment_theuploadform">';
                                                $form_html .= '<input type="hidden" id="cpc_forum_plugins_url" value="'.plugins_url( '', __FILE__ ).'" />';
                                                $form_html .= '<input type="hidden" name="action" value="cpc_forum_comment_add" />';
                                                $form_html .= '<input type="hidden" name="post_id" value="'.$my_posts[0]->ID.'" />';
                                                $form_html .= '<input type="hidden" name="cpc_forum_slug" value="'.$slug.'" />';
                                                $form_html .= '<input type="hidden" name="cpc_forum_moderate" value="'.$moderate.'" />';

                                                $form_html .= '<div id="cpc_forum_comment_content_label">'.$content_label.'</div>';
                                                $form_html = apply_filters( 'cpc_forum_comment_pre_form_filter', $form_html, $atts, $current_user->ID );
                                                
												// Hole immer den rohen BBCODE aus der Datenbank
												$raw_content = '';
												if ($my_posts && isset($my_posts[0]->post_content)) {
													$raw_content = $my_posts[0]->post_content;
												}

												if ( defined( 'CPC_FORUM_TOOLBAR' ) && get_option( 'cpc_com_toolbar' ) == 'wysiwyg' ):
													// Für WYSIWYG: BBCODE nach HTML umwandeln
													$the_content = cpc_bbcode_replace($raw_content);
													$form_html .= cpc_get_wp_editor($the_content, 'cpc_forum_comment', 'margin-top:20px;margin-bottom:20px;');
												elseif ( defined( 'CPC_FORUM_TOOLBAR' ) && get_option( 'cpc_com_toolbar' ) == 'bbcodes' ):
													// Für BBCode-Editor: BBCODE direkt anzeigen
													$the_content = $raw_content;
													$form_html .= cpc_get_bbcode_toolbar('cpc_forum_comment', 'cpc_forum_comment', $the_content);
												else:
													// Fallback
													$the_content = $raw_content;
													$form_html .= '<textarea id="cpc_forum_comment" name="cpc_forum_comment">'.$the_content.'</textarea>';
												endif;

                                                // If can move, show list
                                                $user_can_move_post = $my_posts[0]->post_author == $current_user->ID ? true : false;
                                                $user_can_move_post = apply_filters( 'cpc_forum_post_user_can_move_post_filter', $user_can_move_post, $my_posts[0], $current_user->ID, $term->term_id );

                                                if ($user_can_move_post || current_user_can('manage_options')):

                                                    $forum_terms = get_terms( "cpc_forum", array(
                                                        'hide_empty'    => false, 
                                                        'fields'        => 'all', 
                                                        'hierarchical'  => false, 
                                                    ) );

                                                    if ($forum_terms && count($forum_terms) > 1):

                                                        $form_html .= '<div id="cpc_post_forum_slug_div">'.$show_in_label.'&nbsp;&nbsp;';
                                                        $form_html .= '<select name="cpc_post_forum_slug" id="cpc_post_forum_slug">';

                                                            foreach ( $forum_terms as $forum_term ):
                                                                if (user_can_see_forum($current_user->ID, $forum_term->term_id) || current_user_can('manage_options')):
                                                                    $selected_as_default = ($term->slug == $forum_term->slug) ? ' SELECTED' : '';
                                                                    $form_html .= '<option value="'.$forum_term->slug.'" '.$selected_as_default.'>'.$forum_term->name.'</option>';
                                                                endif;
                                                            endforeach;

                                                        $form_html .= '</select></div>';

                                                    endif;

                                                endif;
                                                // Close post option
                                                if (($my_posts[0]->post_author == $current_user->ID && $allow_close) || current_user_can('edit_posts')):
                                                    $form_html .= '<div id="cpc_close_post_div">';
                                                    $form_html .= '<input type="checkbox" name="cpc_close_post" id="cpc_close_post" style="margin-right:10px;" /><label for="cpc_close_post">'.$close_msg.'</label>';
                                                    $form_html .= '</div>';
                                                endif;	
                                                // private reply
                                                $originator = get_user_by('id', $my_posts[0]->post_author); 
                                                if ($allow_private && $originator && $my_posts[0]->post_author != $current_user->ID && ($allow_private == 'optional' || $allow_private == 'forced')):
                                                    $form_html .= '<div id="cpc_private_post_div">';
                                                    if ($allow_private == 'optional') $form_html .= '<input type="checkbox" name="cpc_private_post" id="cpc_private_post" style="margin-right:10px;" />';
                                                    if ($allow_private == 'forced') $form_html .= '<input type="hidden" name="cpc_private_post" id="cpc_private_post" value="on" />';
                                                    $form_html .= '<label for="cpc_private_post">'.sprintf($private_reply_check_msg, $originator->display_name).'</label>';
                                                    $form_html .= '</div>';
                                                endif;
                                                $form_html = apply_filters( 'cpc_forum_comment_post_form_filter', $form_html, $atts, $current_user->ID, $term, $my_posts[0]->ID );

                                                if ($moderate) $form_html .= '<div id="cpc_forum_comment_moderate">'.$moderate_msg.'</div>';

                                            $form_html .= '</div>';


                                            $form_html .= '<button id="cpc_forum_comment_button" class="cpc_button '.$class.'">'.$label.'</button>';

                                            $form_html .= '</form>';
    
                                        endif;

                                    $form_html .= '</div>';

                                else:

                                    $form_html .= '<div id="cpc_forum_post_closed">'.$comments_closed_msg.'</div>';

                                    if ($my_posts[0]->post_author == $current_user->ID || current_user_can('edit_posts')):
                                        $form_html .= '<form id="cpc_forum_comment_reopen_theuploadform">';
                                            $form_html .= '<input type="hidden" id="cpc_forum_plugins_url" value="'.plugins_url( '', __FILE__ ).'" />';
                                            $form_html .= '<input type="hidden" name="action" value="cpc_forum_comment_reopen" />';
                                            $form_html .= '<input type="hidden" id="reopen_post_id" value="'.$my_posts[0]->ID.'" />';
                                            $form_html .= '<button id="cpc_forum_comment_reopen_button" class="cpc_button '.$class.'">'.$reopen_label.'</button>';
                                        $form_html .= '</form>';
                                    endif;


                                endif;
    
                            else:
    
                                $form_html = '<p>'.$no_permission_msg.'</p>';
    
                            endif;

							$html .= $form_html;

						endif;

					endif;

				else:

					$html .= $locked_msg;

				endif;

			else:

				$html .= '<div class="cpc_forum_comment_private_msg">'.$private_msg.'</div>';

			endif;

		endif;

	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_forum_comment', $before, $after, $styles, $values);

	return $html;
}

function cpc_forum_post($atts) {

	// Init
	add_action('wp_footer', 'cpc_forum_init');

	$html = '';

	$show_forum = false;
	if (is_user_logged_in() && ( 
		(!isset($_GET['forum_action']) && !get_query_var('topic') && !(isset($_GET['topic_id']))) || 
		(isset($_POST['action']) && $_POST['action'] == 'cpc_forum_post_delete') 
		) ) $show_forum = true;

		// check if in process of deleting a post
		if ( ( isset($_POST['action']) && $_POST['action'] == 'cpc_forum_post_delete') ) $show_forum = false;

	if ( $show_forum ): // not showing a single post or just deleted a topic

		global $current_user;
    
		// Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_forum_post');    
		extract( shortcode_atts( array(
			'class' => cpc_get_shortcode_value($values, 'cpc_forum_post-class', ''), // layout look and feel, table|classic
			'title_label' => cpc_get_shortcode_value($values, 'cpc_forum_post-title_label', __('Titel des Beitrags', CPC2_TEXT_DOMAIN)),
			'post_to_label' => cpc_get_shortcode_value($values, 'cpc_forum_post-post_to_label', __('Post an', CPC2_TEXT_DOMAIN)),
			'content_label' => cpc_get_shortcode_value($values, 'cpc_forum_post-content_label', __('Post', CPC2_TEXT_DOMAIN)),
			'label' => cpc_get_shortcode_value($values, 'cpc_forum_post-label', __('Add Topic', CPC2_TEXT_DOMAIN)),
			'moderate_msg' => cpc_get_shortcode_value($values, 'cpc_forum_post-moderate_msg', __('Dein Beitrag erscheint, sobald er moderiert wurde.', CPC2_TEXT_DOMAIN)),
			'locked_msg' => cpc_get_shortcode_value($values, 'cpc_forum_post-locked_msg', __('Dieses Forum ist gesperrt. Neue Beiträge und Antworten sind nicht erlaubt.', CPC2_TEXT_DOMAIN)),
			'private_msg' => cpc_get_shortcode_value($values, 'cpc_forum_post-private_msg', ''),
			'moderate' => cpc_get_shortcode_value($values, 'cpc_forum_post-moderate', false),
            'multiline' => cpc_get_shortcode_value($values, 'cpc_forum_post-multiline', 0), // set to number of lines
			'show' => cpc_get_shortcode_value($values, 'cpc_forum_post-show', false),
			'slug' => '',
			'before' => '',
			'styles' => true,
            'after' => '',
		), $atts, 'cpc_forum_post' ) );

		if ($slug == ''):

			$html .= __('Bitte füge slug="xxx" zum Shortcode hinzu, wobei xxx der Slug des Forums ist, oder slug="choose", damit Benutzer auswählen können, in welchem Forum sie posten möchten.', CPC2_TEXT_DOMAIN);

		else:
    
			$term = get_term_by( 'slug', $slug, 'cpc_forum' );
			if ($term || $slug == 'choose'):
    		
	    		if ($slug != 'choose'):
					$closed = cpc_get_term_meta($term->term_id, 'cpc_forum_closed', true);
				else:
					$closed = false;
				endif;
					
				if (!$closed || current_user_can('manage_options')):

					if ($slug != 'choose'):
						$user_can_see_forum = user_can_see_forum($current_user->ID, $term->term_id);
						$user_can_see_forum = apply_filters( 'cpc_forum_post_user_can_post_filter', $user_can_see_forum, $current_user->ID, $term->term_id );
					else:
						$user_can_see_forum = true;
						$user_can_see_forum = apply_filters( 'cpc_forum_post_user_can_post_filter', $user_can_see_forum, $current_user->ID, false );
					endif;

					if ($user_can_see_forum || current_user_can('manage_options')):

						$form_html = '';			
						$form_html .= '<div id="cpc_forum_post_div">';
							
							$form_html .= '<div id="cpc_forum_post_form"';
								if (!$show) $form_html .= ' style="position:fixed;left:-1000px;top:-2000px;"';
								$form_html .= '>';

								$form_html .= '<form enctype="multipart/form-data" id="cpc_forum_post_theuploadform">';
								$form_html .= '<input type="hidden" id="cpc_forum_plugins_url" value="'.plugins_url( '', __FILE__ ).'" />';
								$form_html .= '<input type="hidden" name="action" value="cpc_forum_post_add" />';
								$form_html .= '<input type="hidden" name="cpc_forum_moderate" value="'.$moderate.'" />';

								$form_html .= '<div id="cpc_forum_post_title_label">'.$title_label.'</div>';
	                            if (!$multiline):
	                                $form_html .= '<input type="text" id="cpc_forum_post_title" name="cpc_forum_post_title" />';
	                            else:
	                                $form_html .= '<textarea rows="'.$multiline.'" id="cpc_forum_post_title" name="cpc_forum_post_title"></textarea>';
	                            endif;

	                            if ($slug != 'choose'):	
									$form_html .= '<input type="hidden" name="cpc_forum_choose" value="0" />';								
									$form_html .= '<input type="hidden" name="cpc_forum_slug" value="'.$slug.'" />';
									$form_html = apply_filters( 'cpc_forum_post_pre_form_filter', $form_html, $atts, $current_user->ID, $term );
								else:
									$form_html .= '<input type="hidden" name="cpc_forum_choose" value="1" />';								
									$form_html .= '<div id="cpc_forum_choose_label">'. $post_to_label.'</div>';
									
									$form_html .= '<select name="cpc_forum_slug" id="cpc_forum_post_choose">';
									
										$forum_terms = get_terms( "cpc_forum", array(
										    'hide_empty'    => false, 
										    'fields'        => 'all', 
										    'hierarchical'  => false, 
										) );

										foreach ( $forum_terms as $forum_term ):
											if (user_can_see_forum($current_user->ID, $forum_term->term_id) || current_user_can('manage_options')):
									            $form_html .= '<option value="'.$forum_term->slug.'">'.$forum_term->name.'</option>';
									        endif;
									    endforeach;
				    
									$form_html .= '</select><div id="cpc_forum_post_choose_post_div"></div>';
									$form_html = apply_filters( 'cpc_forum_post_pre_form_filter', $form_html, $atts, $current_user->ID, false );
								endif;								
								
								$form_html .= '<div id="cpc_forum_post_content_label">'.$content_label.'</div>';

								// Hole immer den rohen BBCODE aus der Datenbank
								$raw_content = '';
								if ($my_posts && isset($my_posts[0]->post_content)) {
									$raw_content = $my_posts[0]->post_content;
								}

								if ( defined( 'CPC_FORUM_TOOLBAR' ) && get_option( 'cpc_com_toolbar' ) == 'wysiwyg' ):
									// Für WYSIWYG: BBCODE nach HTML umwandeln
									$the_content = cpc_bbcode_replace($raw_content);
									$form_html .= cpc_get_wp_editor($the_content, 'cpc_forum_post_textarea', '');
								elseif ( defined( 'CPC_FORUM_TOOLBAR' ) && get_option( 'cpc_com_toolbar' ) == 'bbcodes' ):
									// Für BBCode-Editor: BBCODE direkt anzeigen
									$the_content = $raw_content;
									$form_html .= cpc_get_bbcode_toolbar('cpc_forum_post_textarea', 'cpc_forum_post_textarea', $the_content);
								else:
									// Fallback
									$the_content = $raw_content;
									$form_html .= '<textarea id="cpc_forum_post_textarea" name="cpc_forum_post_textarea">'.$the_content.'</textarea>';
								endif;
								if ($moderate) $form_html .= '<div id="cpc_forum_post_moderate">'.$moderate_msg.'</div>';

								$form_html = apply_filters( 'cpc_forum_post_post_form_filter', $form_html, $atts, $current_user->ID );

							$form_html .= '</div>';

							$form_html .= '<button id="cpc_forum_post_button" class="cpc_button '.$class.'">'.$label.'</button>';

							$form_html .= '</form>';
						
						$form_html .= '</div>';

						$html .= $form_html;

					else:

						$html .= $private_msg;

					endif;

				else:

					$html .= $locked_msg;

				endif;

			endif;

		endif;

	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_forum_post', $before, $after, $styles, $values);

	return $html;
}


function cpc_forum($atts) {

	// Init
	add_action('wp_footer', 'cpc_forum_init');
    
	global $current_user;
	
	$html = '';
    
    // Styles
    if (get_option('cpccom_use_styles')):
    	$html .= '<!-- start of cpc_forum styles -->';
        $values = get_option('cpc_styles_'.'cpc_forum') ? get_option('cpc_styles_'.'cpc_forum') : array();
        $html .= cpc_styles($values, 'cpc_forum', array('.cpc_forum_title_header','.cpc_forum_count_header','.cpc_forum_last_poster_header','.cpc_forum_categories_freshness_header'));
        $values = get_option('cpc_styles_'.'cpc_forums') ? get_option('cpc_styles_'.'cpc_forums') : array();
        $html .= cpc_styles($values, 'cpc_forums', array('.cpc_forums_forum_title', '.cpc_forums_forum_title:hover', '.cpc_forums_forum_title:active'));
    	$html .= '<!-- end of cpc_forum styles -->';
    endif;    
    
	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_forum');    
	extract( shortcode_atts( array(
		'slug' => '',
        'style' => cpc_get_shortcode_value($values, 'cpc_forum-style', 'table'), // layout look and feel, table|classic, // layout look and feel, table|classic
        // table... (default)
		'show_header' => cpc_get_shortcode_value($values, 'cpc_forum-show_header', true),
	    'show_closed' => cpc_get_shortcode_value($values, 'cpc_forum-show_closed', true),
        'show_count' => cpc_get_shortcode_value($values, 'cpc_forum-show_count', true),
        'show_freshness' => cpc_get_shortcode_value($values, 'cpc_forum-show_freshness', true),
		'show_last_activity' => cpc_get_shortcode_value($values, 'cpc_forum-show_last_activity', true),
		'show_comments_count' => cpc_get_shortcode_value($values, 'cpc_forum-show_comments_count', true),
        // classic...
        'started' => cpc_get_shortcode_value($values, 'cpc_forum-started', __('Gestartet von %s %s', CPC2_TEXT_DOMAIN)),
        'replied' => cpc_get_shortcode_value($values, 'cpc_forum-replied', __('Zuletzt geantwortet von %s %s', CPC2_TEXT_DOMAIN)),
        'commented' => cpc_get_shortcode_value($values, 'cpc_forum-commented', __('Zuletzt kommentiert von %s %s', CPC2_TEXT_DOMAIN)),
        'size_posts' => cpc_get_shortcode_value($values, 'cpc_forum-size_posts', 96),
        'size_replies' => cpc_get_shortcode_value($values, 'cpc_forum-size_replies', 48),
        'post_preview' => cpc_get_shortcode_value($values, 'cpc_forum-post_preview', 250),
        'reply_preview' => cpc_get_shortcode_value($values, 'cpc_forum-reply_preview', 120),
        'view_count_label' => cpc_get_shortcode_value($values, 'cpc_forum-view_count_label', __('ANSEHEN', CPC2_TEXT_DOMAIN)),
        'views_count_label' => cpc_get_shortcode_value($values, 'cpc_forum-views_count_label', __('ANSICHTEN', CPC2_TEXT_DOMAIN)),
        'reply_count_label' => cpc_get_shortcode_value($values, 'cpc_forum-reply_count_label', __('ANTWORT', CPC2_TEXT_DOMAIN)),
        'replies_count_label' => cpc_get_shortcode_value($values, 'cpc_forum-replies_count_label', __('ANTWORTEN', CPC2_TEXT_DOMAIN)),
        // all layout options...   
        'topic_action' => cpc_get_shortcode_value($values, 'cpc_forum-topic_action', ''),
        'new_item' => cpc_get_shortcode_value($values, 'cpc_forum-new_item', true),
        'new_seconds' => cpc_get_shortcode_value($values, 'cpc_forum-new_seconds', 259200),        
        'new_item_read' => cpc_get_shortcode_value($values, 'cpc_forum-new_item_read', true),
        'new_item_label' => cpc_get_shortcode_value($values, 'cpc_forum-new_item_label', __('NEU!', CPC2_TEXT_DOMAIN)),
        'reply_comment_none' => cpc_get_shortcode_value($values, 'cpc_forum-reply_comment_none', __('Keine Antworten', CPC2_TEXT_DOMAIN)),
        'reply_comment_one' => cpc_get_shortcode_value($values, 'cpc_forum-reply_comment_none', __('1 Antwort', CPC2_TEXT_DOMAIN)),
        'reply_comment_multiple' => cpc_get_shortcode_value($values, 'cpc_forum-reply_comment_none', __('%d Antworten', CPC2_TEXT_DOMAIN)),
        'reply_comment_one_comment' => cpc_get_shortcode_value($values, 'cpc_forum-reply_comment_none', __('und 1 Kommentar', CPC2_TEXT_DOMAIN)),
        'reply_comment_multiple_comments' => cpc_get_shortcode_value($values, 'cpc_forum-reply_comment_none', __('und %d Kommentare', CPC2_TEXT_DOMAIN)),        
        'pagination_posts' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_posts', true),
        'pagination_top_posts' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_top_posts', true),
        'pagination_bottom_posts' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_bottom_posts', true),
        'page_size_posts' => cpc_get_shortcode_value($values, 'cpc_forum-page_size_posts', 10),
        'pagination_first_posts' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_first_posts', __('Erste', CPC2_TEXT_DOMAIN)),
        'pagination_previous_posts' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_previous_posts', __('Vorherige', CPC2_TEXT_DOMAIN)),
        'pagination_next_posts' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_next_posts', __('Nächste', CPC2_TEXT_DOMAIN)),
        'page_x_of_y_posts' => cpc_get_shortcode_value($values, 'cpc_forum-page_x_of_y_posts', __('Auf Seite %d von %d', CPC2_TEXT_DOMAIN)),            
        'max_pages_posts' => cpc_get_shortcode_value($values, 'cpc_forum-max_pages_posts', 10), // maximum number of pages        
        'max_posts_no_pagination_posts' => cpc_get_shortcode_value($values, 'cpc_forum-max_posts_no_pagination_posts', 100), // maximum number of posts if pagination disabled
        'reply_comment_none' => cpc_get_shortcode_value($values, 'cpc_forum-reply_comment_none', __('Keine Antworten', CPC2_TEXT_DOMAIN)),
        'reply_comment_one' => cpc_get_shortcode_value($values, 'cpc_forum-reply_comment_one', __('1 Antwort', CPC2_TEXT_DOMAIN)),
        'reply_comment_multiple' => cpc_get_shortcode_value($values, 'cpc_forum-reply_comment_multiple', __('%d Antworten', CPC2_TEXT_DOMAIN)),
        'reply_comment_one_comment' => cpc_get_shortcode_value($values, 'cpc_forum-reply_comment_one_comment', __('und 1 Kommentar', CPC2_TEXT_DOMAIN)),
        'reply_comment_multiple_comments' => cpc_get_shortcode_value($values, 'cpc_forum-reply_comment_multiple_comments', __('und %d Kommentare', CPC2_TEXT_DOMAIN)),                
        'forum_admins' => cpc_get_shortcode_value($values, 'cpc_forum-forum_admins', ''),      
        'title_length' => cpc_get_shortcode_value($values, 'cpc_forum-title_length', 150),      
		'show_originator' => cpc_get_shortcode_value($values, 'cpc_forum-show_originator', true),
		'originator' => cpc_get_shortcode_value($values, 'cpc_forum-originator', __(' von %s', CPC2_TEXT_DOMAIN)),
		'parent' => 0,
		'status' => cpc_get_shortcode_value($values, 'cpc_forum-status', ''), // open|closed (ie. post comment_status, default to all, ie. '')
		'closed_switch' => cpc_get_shortcode_value($values, 'cpc_forum-closed_switch', ''), // default state, on|off or '' to not show - if logged in and not '', user choice is saved
		'closed_switch_msg' => cpc_get_shortcode_value($values, 'cpc_forum-closed_switch_msg', __('Schließe geschlossene Beiträge ein', CPC2_TEXT_DOMAIN)),
		'private_msg' => cpc_get_shortcode_value($values, 'cpc_forum-private_msg', __('Du musst angemeldet sein, um dieses Forum anzuzeigen.', CPC2_TEXT_DOMAIN)),
		'login_url' => cpc_get_shortcode_value($values, 'cpc_forum-login_url', ''),
		'secure_msg' => cpc_get_shortcode_value($values, 'cpc_forum-secure_msg', __('Du hast keine Berechtigung, dieses Forum anzuzeigen.', CPC2_TEXT_DOMAIN)),
		'secure_post_msg' => cpc_get_shortcode_value($values, 'cpc_forum-secure_post_msg', __('Du hast keine Berechtigung, diesen Beitrag anzuzeigen.', CPC2_TEXT_DOMAIN)),
		'empty_msg' => cpc_get_shortcode_value($values, 'cpc_forum-empty_msg', __('Keine Forumbeiträge.', CPC2_TEXT_DOMAIN)),
        'post_deleted' => cpc_get_shortcode_value($values, 'cpc_forum-post_deleted', __('Beitrag gelöscht.', CPC2_TEXT_DOMAIN)),
		'pending' => cpc_get_shortcode_value($values, 'cpc_forum-pending', '('.__('ausstehend', CPC2_TEXT_DOMAIN).')'),
		'comment_pending' => cpc_get_shortcode_value($values, 'cpc_forum-comment_pending', '('.__('ausstehend', CPC2_TEXT_DOMAIN).')'),
		'closed_prefix' => cpc_get_shortcode_value($values, 'cpc_forum-closed_prefix', __('geschlossen', CPC2_TEXT_DOMAIN)),
		'header_title' => cpc_get_shortcode_value($values, 'cpc_forum-header_title', __('Thema', CPC2_TEXT_DOMAIN)),
		'header_count' => cpc_get_shortcode_value($values, 'cpc_forum-header_count', __('Antworten', CPC2_TEXT_DOMAIN)),
		'header_last_activity' => cpc_get_shortcode_value($values, 'cpc_forum-header_last_activity', __('Letzte Aktivität', CPC2_TEXT_DOMAIN)),
		'header_freshness' => cpc_get_shortcode_value($values, 'cpc_forum-header_freshness', __('Wann', CPC2_TEXT_DOMAIN)),
		'moved_to' => cpc_get_shortcode_value($values, 'cpc_forum-moved_to', __('%s wurde erfolgreich nach %s verschoben', CPC2_TEXT_DOMAIN)),
		'date_format' => cpc_get_shortcode_value($values, 'cpc_forum-date_format', __('vor %s', CPC2_TEXT_DOMAIN)),
		'enable_timeout' => cpc_get_shortcode_value($values, 'cpc_forum-enable_timeout', true),
		'timeout' => cpc_get_shortcode_value($values, 'cpc_forum-timeout', 120),
		'count' => cpc_get_shortcode_value($values, 'cpc_forum-count', 0),
        'size' => cpc_get_shortcode_value($values, 'cpc_forum-size', 96), // size of user avatar's on single post view
		'comments_avatar_size' => cpc_get_shortcode_value($values, 'cpc_forum-comments_avatar_size', 48),
		'pagination' => cpc_get_shortcode_value($values, 'cpc_forum-pagination', true),
		'pagination_above' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_above', false),
		'pagination_top' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_top', true),
		'pagination_bottom' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_bottom', true),
		'page_size' => cpc_get_shortcode_value($values, 'cpc_forum-page_size', 10),
        'pagination_first' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_first', __('Erste', CPC2_TEXT_DOMAIN)),        
		'pagination_previous' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_previous', __('Vorherige', CPC2_TEXT_DOMAIN)),
		'pagination_next' => cpc_get_shortcode_value($values, 'cpc_forum-pagination_next', __('Nächste', CPC2_TEXT_DOMAIN)),
		'page_x_of_y' => cpc_get_shortcode_value($values, 'cpc_forum-page_x_of_y', __('Auf Seite %d von %d', CPC2_TEXT_DOMAIN)),
		'replies_order' => cpc_get_shortcode_value($values, 'cpc_forum-replies_order', 'ASC'),
        'report' => cpc_get_shortcode_value($values, 'cpc_forum-report', true),
        'report_label' => cpc_get_shortcode_value($values, 'cpc_forum-report_label', __('Melden', CPC2_TEXT_DOMAIN)), 
        'report_email' => cpc_get_shortcode_value($values, 'cpc_forum-report_email', get_bloginfo('admin_email')), 
		'hide_initial' => cpc_get_shortcode_value($values, 'cpc_forum-hide_initial', false),
		'show_comments' => cpc_get_shortcode_value($values, 'cpc_forum-show_comments', true), // Whether comments are shown
		'show_comment_form' => cpc_get_shortcode_value($values, 'cpc_forum-show_comment_form', true), // Default state of comment textarea
		'allow_comments' => cpc_get_shortcode_value($values, 'cpc_forum-allow_comments', true), // Whether new comments are allowed
		'comment_add_label' => cpc_get_shortcode_value($values, 'cpc_forum-comment_add_label', __('Einen Kommentar hinzufügen', CPC2_TEXT_DOMAIN)),
		'comment_class' => cpc_get_shortcode_value($values, 'cpc_forum-comment_class', ''), // Class for comments button
        'private_reply_msg' => cpc_get_shortcode_value($values, 'cpc_forum-private_reply_msg', __('PRIVATE ANTWORT', CPC2_TEXT_DOMAIN)),
        'reply_icon' => cpc_get_shortcode_value($values, 'cpc_forum-reply_icon', true),
		'base_date' => cpc_get_shortcode_value($values, 'cpc_forum-base_date', 'post_date_gmt'),
		'comment_base_date' => cpc_get_shortcode_value($values, 'cpc_forum-comment_base_date', 'comment_date_gmt'),
		'before' => '',
		'styles' => true,
        'after' => '',
	), $atts, 'cpc_forum' ) );
    
    // can't have pagination and closed switch option
    if ($pagination_posts) $closed_switch = '';
        
	if ($slug == ''):

		$html .= __('Bitte füge slug="xxx" zum Shortcode [cpc-forum] hinzu, wobei xxx der Slug des Forums ist. Beispiel: [cpc-forum slug="my-general-forum"].', CPC2_TEXT_DOMAIN);

	else:
    
		$term = get_term_by( 'slug', $slug, 'cpc_forum' );

		if ($term):		

	        // Protect email from tags
	        $report_email = str_replace('@', '[@]', $report_email);    

	        // Get list of forum admins
	        $forum_admin_list = ($forum_admins) ? explode(',', $forum_admins) : array();
	        $is_forum_admin = (in_array($current_user->user_login, $forum_admin_list) || current_user_can('manage_options'));
	    
	        if (user_can_see_forum($current_user->ID, $term->term_id) || current_user_can('manage_options')):

	            if (current_user_can('manage_options') && !$login_url && function_exists('cpc_login_init')):
	                $html = cpc_admin_tip($html, 'cpc_forum_login', __('Füge login_url="/example" zum Shortcode [cpc-forum] hinzu, damit sich Benutzer anmelden und hierher zurückleiten können, wenn sie nicht angemeldet sind, und das Forum als privat festgelegt wird.', CPC2_TEXT_DOMAIN));
	            endif;   

				if ( ( isset($_POST['action']) && $_POST['action'] == 'cpc_forum_post_delete') ) {
	                
					// Delete entire post and then show remaining forum posts
					$post_id = $_POST['cpc_post_id'];
	                $deleted = false;
					if ($post_id):
						$current_post = get_post($post_id);
						if ($current_post):

							$user_can_delete_forum = $current_post->post_author == $current_user->ID ? true : false;
							$user_can_delete_forum = apply_filters( 'cpc_forum_post_user_can_delete_filter', $user_can_delete_forum, $current_post, $current_user->ID, $term->term_id );

							if ( $user_can_delete_forum || $is_forum_admin ):

								$my_trashed_post = array(
									'ID'			=> $post_id,
									'post_status'	=> 'trash'
								);

								wp_update_post( $my_trashed_post );	
	                            $deleted = true;
	                                
	                            // Now set any forum comments to trash for that post
	                            global $wpdb;
	                            $sql = "SELECT comment_ID FROM ".$wpdb->prefix."comments WHERE comment_post_ID = %d";
	                            $comments = $wpdb->get_col($wpdb->prepare($sql, $post_id));

	                            if ($comments):
	                                foreach ($comments as $comment_id):
	                                    wp_delete_comment($comment_id, false); // soft delete
	                                endforeach;
	                            endif;
	                
	                            $html .= '<div class="cpc_success" style="margin-top:20px">'.$post_deleted.'</div>';

								// Any further actions?
								do_action( 'cpc_forum_post_delete_hook', $_POST, $_FILES, $post_id );

							endif;

						endif;
					endif;
					if (!$deleted) { require('cpc_forum_posts.php'); }

	            } else {

					// check if viewing single post
					if ( get_query_var('topic') || isset($_GET['topic_id'])):

						require('cpc_forum_post.php');	

					else:

	                    require('cpc_forum_posts.php');

					endif;

	            }

			else:

				$public = cpc_get_term_meta($term->term_id, 'cpc_forum_public', true);
				if (!$public && !is_user_logged_in()) {
					$query = cpc_query_mark(get_bloginfo('url').$login_url);
					if ($login_url) $html .= sprintf('<a href="%s%s%sredirect=%s">', get_bloginfo('url'), $login_url, $query, cpc_root( $_SERVER['REQUEST_URI'] ));
					$html .= $private_msg;
					if ($login_url) $html .= '</a>';
				} else {
					$html .= $secure_msg;
				}

			endif;

		else:

			$html .= '<div class="cpc_error">'.sprintf(__('Forum (%s) existiert nicht.', CPC2_TEXT_DOMAIN), $slug).'</div>';

		endif;

	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_forum', $before, $after, $styles, $values);

	return $html;
}

function cpc_forum_children($atts) {

	// Init
	add_action('wp_footer', 'cpc_forum_init');

	$html = '';

	global $post, $current_user;

	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_forum_children');    
	extract( shortcode_atts( array(
		'show_header' => cpc_get_shortcode_value($values, 'cpc_forum_children-show_header', true),
	    'show_summary' => cpc_get_shortcode_value($values, 'cpc_forum_children-show_summary', true),		
        'show_count' => cpc_get_shortcode_value($values, 'cpc_forum_children-show_count', true),
        'show_last_activity' => cpc_get_shortcode_value($values, 'cpc_forum_children-show_last_activity', true),
        'show_freshness' => cpc_get_shortcode_value($values, 'cpc_forum_children-show_freshness', true),
		'forum_title' => cpc_get_shortcode_value($values, 'cpc_forum_children-forum_title', __('Kind-Forum', CPC2_TEXT_DOMAIN)),
		'forum_count' => cpc_get_shortcode_value($values, 'cpc_forum_children-forum_count', __('Aktivität', CPC2_TEXT_DOMAIN)),
		'forum_last_activity' => cpc_get_shortcode_value($values, 'cpc_forum_children-forum_last_activity', __('Letzter Poster', CPC2_TEXT_DOMAIN)),		
		'forum_freshness' => cpc_get_shortcode_value($values, 'cpc_forum_children-forum_freshness', __('Neueste', CPC2_TEXT_DOMAIN)),		
	    'link' => cpc_get_shortcode_value($values, 'cpc_forum_children-link', true),		
		'base_date' => cpc_get_shortcode_value($values, 'cpc_forum_children-base_date', 'post_date_gmt'),
	    'show_child_posts' => cpc_get_shortcode_value($values, 'cpc_forum_children-show_child_posts', false),		
		'child_posts_count' => cpc_get_shortcode_value($values, 'cpc_forum_children-child_posts_count', __('Antworten', CPC2_TEXT_DOMAIN)),
		'child_posts_last_activity' => cpc_get_shortcode_value($values, 'cpc_forum_children-child_posts_last_activity', __('Letzter Poster', CPC2_TEXT_DOMAIN)),		
		'child_posts_freshness' => cpc_get_shortcode_value($values, 'cpc_forum_children-child_posts_freshness', __('Neueste', CPC2_TEXT_DOMAIN)),		
		'child_posts_max' => cpc_get_shortcode_value($values, 'cpc_forum_children-child_posts_max', 3),		
		'slug' => '',
		'before' => '',
		'styles' => true,
        'after' => '',
	), $atts, 'cpc_forum_children' ) );

	if ($slug == ''):

		$html .= '<div class="cpc_error">'.__('Bitte füge slug="xxx" zum Shortcode hinzu, wobei xxx der Slug des übergeordneten Forums ist.', CPC2_TEXT_DOMAIN).'</div>';

	else:

		// Get current forum, to get forum ID
		$terms = get_terms( "cpc_forum", array(
		    'hide_empty'    => false, 
		    'fields'        => 'all', 
		    'slug'			=> $slug,
		    'hierarchical'  => false,  
		) );

		if ($terms):

			foreach ( $terms as $term ):
				$term_id = $term->term_id;
			endforeach;

			// Now get all forums with a parent of this forum
			$terms = get_terms( "cpc_forum", array(
			    'hide_empty'    => false, 
			    'fields'        => 'all', 
			    'parent'		=> $term_id,
			    'hierarchical'  => false,  
			) );

			if ($terms):

				$html .= '<div id="cpc_forum_children_div">';

					if ($show_header):
						$html .= '<div class="cpc_forum_categories_header">';
							$html .= '<div class="cpc_forum_categories_description">'.$forum_title.'</div>';
							if ($show_freshness) $html .= '<div class="cpc_forum_categories_freshness">'.$forum_freshness.'</div>';
							if ($show_last_activity) $html .= '<div class="cpc_forum_categories_last_poster">'.$forum_last_activity.'</div>';
							if ($show_count) $html .= '<div class="cpc_forum_categories_count">'.$forum_count.'</div>';
						$html .= '</div>';
					endif;

					$forums = array();

					foreach ( $terms as $term ):

						if (user_can_see_forum($current_user->ID, $term->term_id) || current_user_can('manage_options')):

							$forum = array();
							$forum['term_id'] = $term->term_id;
							$forum['order'] = cpc_get_term_meta($term->term_id, 'cpc_forum_order', true);
							$forum['name'] = $term->name;
							$forum['slug'] = $term->slug;
							if ($term->description):
								$forum['description'] = $term->description;
							else:
								$forum['description'] = '&nbsp;';
							endif;
							$forum['count'] = $term->count;

							$forums[$term->term_id] = $forum;

						endif;

					endforeach;

					if ($forums):

						// Sort the forums by order first, then name
						$sort = array();
						foreach($forums as $k=>$v) {
						    $sort['order'][$k] = $v['order'];
						    $sort['name'][$k] = $v['name'];
						}
						array_multisort($sort['order'], SORT_ASC, $sort['name'], SORT_ASC, $forums);

						foreach ($forums as $forum):

			                $posts_per_page = 1;
							$loop = new WP_Query( array(
								'post_type' => 'cpc_forum_post',
								'posts_per_page' => $posts_per_page,
								'post_status' => 'publish',
								'tax_query' => array(
									array(
										'taxonomy' => 'cpc_forum',
										'field' => 'slug',
										'terms' => $forum['slug'],
									)
								)				
							) );

							global $post,$wpdb;
			                $comment_count = 0;
			                $post_ptr = 0;
							if ($loop->have_posts()):
								while ( $loop->have_posts() ) : $loop->the_post();
			                        if (!$post_ptr):
			                            $user = get_user_by('id', $post->post_author);
			                            $author = $user->display_name;
			                            $date = $base_date == 'post_date_gmt' ? $post->post_date_gmt : $post->post_date;
			                            $created = sprintf(__('vor %s', CPC2_TEXT_DOMAIN), human_time_diff(strtotime($date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
			                        endif;
			                        $comment_count++; // add count of post itself
			                        // Get count of comments if needed
			                        if (true):
			                            $sql = "SELECT * FROM ".$wpdb->prefix."comments WHERE comment_post_ID = %d ORDER BY comment_ID DESC";
			                            $comments = $wpdb->get_results($wpdb->prepare($sql, $post->ID));
			                            if ($comments):
			                                $comments_count = 0;
			                                foreach ($comments as $comment):
			                                	if ($comment->user_id): // exclude auto-closed
				                                    $comment_user = get_user_by('id', $comment->user_id);
				                                    $private = get_comment_meta( $comment->comment_ID, 'cpc_private_post', true );
				                                    if (!$private || $current_user->ID == $post->post_author || $comment->user_id == $current_user->ID || current_user_can('manage_options')):

				                                        $comment_author = $user->display_name;
				                                        $comment_date = $base_date == 'post_date_gmt' ? $comment->comment_date_gmt : $comment->comment_date;
				                                        $comment_created = sprintf(__('vor %s', CPC2_TEXT_DOMAIN), human_time_diff(strtotime($comment_date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
				                                        if ($comment_date > $date):
				                                            $author = $comment_author;
				                                            $date = $comment_date;
				                                            $created = $comment_created;
				                                        endif;
				                                        $comments_count++;
				    
				                                    endif;
				                                endif;
			                                endforeach;
			                                $comment_count = $comment_count + $comments_count;
			                            endif;
			                        endif;
			                        $post_ptr++;
								endwhile;
							else:
								$author = '-';
								$created = '-';
							endif;
							wp_reset_query();

							$page_id = cpc_get_term_meta($forum['term_id'], 'cpc_forum_cat_page', true);
							$url = get_permalink($page_id);
			                $forum_html = '';
		            
			                $forum_html .= '<div class="cpc_forum_featured_content" style="position:relative; padding-left: 0px; ">';

			                    $forum_html .= '<div style="width: 100%;">';

			                        $forum_html .= '<div class="cpc_forum_categories_item_info cpc_forum_children_info';
			                        	if ($show_child_posts) $forum_html .= ' cpc_forum_showing_children';
			                            $forum_html .= '">';    
						 
			                            $forum_html .= '<div class="cpc_forum_categories_description cpc_forum_children_description">';
			                                if ($link):
												$page_id = cpc_get_term_meta($forum['term_id'], 'cpc_forum_cat_page', true);
												$url = get_permalink($page_id);
				                                $forum_html .= '<a class="cpc_forum_children_link" href="'.$url.'">'.$forum['name'].'</a>';
			                                else:
				                                $forum_html .= $forum['name'];
			                                endif;
			                            $forum_html .= '</div>';

			                            if ($show_summary):
			                                if ($show_freshness):
			                                    $forum_html .= '<div class="cpc_forum_categories_freshness">';
			                                        $forum_html .= $created;
			                                    $forum_html .= '</div>';
			                                endif;
			                                if ($show_last_activity):
			                                    $forum_html .= '<div class="cpc_forum_categories_last_poster">';
			                                        $forum_html .= $author;
			                                    $forum_html .= '</div>';
			                                endif;
			                                if ($show_count):
			                                    $forum_html .= '<div class="cpc_forum_categories_count">';
			                                        $count = $comment_count;
			                                        $forum_html .= $count;
			                                    $forum_html .= '</div>';
			                                endif;
			                            endif;

			                        $forum_html .= '</div>';

			                        $forum_html = apply_filters( 'cpc_forum_categories_item_filter', $forum_html );

			                        if ($show_child_posts):

				                        $forum_html .= '<div class="cpc_forum_categories_items">';
				                        	$forum_html .= cpc_forum(array(
			                                    'style' => 'table', 
			                                    'slug' => $forum['slug'], 
				                                'pagination_posts' => false,	
				                                'count' => 10,
				                                'header_count' => $child_posts_count,
				                                'header_last_activity' => $child_posts_last_activity,
				                                'header_freshness' => $child_posts_freshness,
				                                'base_date' => $base_date, 	
			                                    'max_forum_posts' => $child_posts_max, 
			                                    'count' => $child_posts_max, 				                                			                                
				                            ));
				                            // xxxx
					                        if ('none' != 'none'):
					                            $forum_html .= '<div class="cpc_forum_categories_item_sep cpc_forum_categories_item_sep_'.$heading_indent_h.'"></div>';
					                            $forum_html .= '<div class="cpc_forum_child cpc_forum_child_'.$heading_indent_h.'">';
					                            
				                                $forum_html .= cpc_forum(array(
				                                    'style' => 'table', 
				                                    'slug' => $forum['slug'], 
				                                    'show_closed' => $show_closed, 
				                                    'status' => $show_closed, 
				                                    'show_header' => $show_posts_header, 
				                                    'max_forum_posts' => $show_posts, 
				                                    'count' => $show_posts, 
				                                    'max_posts_no_pagination_posts' => $show_posts,
				                                    'header_title'=>$header_title, 
				                                    'header_count'=>$header_count, 
				                                    'header_last_activity' => $header_last_activity, 
				                                    'title_length' => $title_length, 
				                                    'show_count' => $show_count, 
				                                    'show_last_activity' => $show_last_activity, 
				                                    'show_freshness' => $show_freshness, 
				                                    'pagination_posts' => false,
				                                )); 
				    
					                            $forum_html .= '</div>';
					                        endif;

				                        $forum_html .= '</div>';

				                     endif;

			                    $forum_html .= '</div>';

			                $forum_html .= '</div>';    

			                $html .= $forum_html;

						endforeach;

					endif;

				$html .= '</div>'; // #cpc_forum_children_div

			endif;

		endif;

	endif;

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_forums', $before, $after, $styles, $values);

	return $html;
}

function cpc_forums($atts) {

	// Init
	add_action('wp_footer', 'cpc_forum_init');

	$html = '';

	global $post, $current_user;

	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_forums');    
	extract( shortcode_atts( array(
		'parent' => 0,
        'show_children' 	=> cpc_get_shortcode_value($values, 'cpc_forums-show_children', false),
		'show_as_dropdown' 	=> cpc_get_shortcode_value($values, 'cpc_forums-show_as_dropdown', false),
		'show_as_dropdown_text' => cpc_get_shortcode_value($values, 'cpc_forums-show_as_dropdown_text', __('Schneller Sprung zum Forum...', CPC2_TEXT_DOMAIN)),		
		'forum_title' => cpc_get_shortcode_value($values, 'cpc_forums-forum_title', __('Forum', CPC2_TEXT_DOMAIN)),
		'forum_count' => cpc_get_shortcode_value($values, 'cpc_forums-forum_count', __('Zähler', CPC2_TEXT_DOMAIN)),
		'forum_last_activity' => cpc_get_shortcode_value($values, 'cpc_forums-forum_last_activity', __('Letzter Poster', CPC2_TEXT_DOMAIN)),		
		'forum_freshness' => cpc_get_shortcode_value($values, 'cpc_forums-forum_freshness', __('Neueste', CPC2_TEXT_DOMAIN)),		
		'show_header' => cpc_get_shortcode_value($values, 'cpc_forums-show_header', false),
        'show_closed' => cpc_get_shortcode_value($values, 'cpc_forums-show_closed', true),
        'show_count' => cpc_get_shortcode_value($values, 'cpc_forums-show_count', true),
        'show_last_activity' => cpc_get_shortcode_value($values, 'cpc_forums-show_last_activity', true),
        'show_freshness' => cpc_get_shortcode_value($values, 'cpc_forums-show_freshness', true),
		'base_date' => cpc_get_shortcode_value($values, 'cpc_forums-base_date', 'post_date_gmt'),
		'slug' => '',
		'before' => '',
		'styles' => true,
        'after' => '',
	), $atts, 'cpc_forums' ) );

	if (!$show_as_dropdown):

		// Show as web page

		if ($show_header):
			$html .= '<div class="cpc_forum_categories_header">';
				$html .= '<div class="cpc_forum_categories_description">'.$forum_title.'</div>';
				if ($show_freshness) $html .= '<div class="cpc_forum_categories_freshness">'.$forum_freshness.'</div>';
				if ($show_last_activity) $html .= '<div class="cpc_forum_categories_last_poster">'.$forum_last_activity.'</div>';
				if ($show_count) $html .= '<div class="cpc_forum_categories_count">'.$forum_count.'</div>';
			$html .= '</div>';
		endif;

	else:

		// Show as drop down

		$html .= '<select id="cpc_forums_go_to">';
		$html .= '<option value="">'.$show_as_dropdown_text.'</option>';

	endif;

	$html = cpc_forum_categories_children($html, $values, $slug, $parent, $show_children, $atts, 0);

	if ($show_as_dropdown)
		$html .= '</select>';

	if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_forums', $before, $after, $styles, $values);

	return $html;
}

function cpc_forum_categories_children($html, $values, $slug, $forum_id, $show_children, $atts, $indent) {

	global $current_user;

	// Shortcode parameters
	extract( shortcode_atts( array(
		'include'           => cpc_get_shortcode_value($values, 'cpc_forums-include', 'all'),        
		'show_as_dropdown' 	=> cpc_get_shortcode_value($values, 'cpc_forums-show_as_dropdown', false),
		'show_posts_header' => cpc_get_shortcode_value($values, 'cpc_forums-show_posts_header', true),
	    'show_posts'		=> cpc_get_shortcode_value($values, 'cpc_forums-show_posts', 3),
	    'show_summary' 		=> cpc_get_shortcode_value($values, 'cpc_forums-show_summary', false),
	    'show_closed'		=> cpc_get_shortcode_value($values, 'cpc_forums-show_closed', true),
        'show_count'        => cpc_get_shortcode_value($values, 'cpc_forums-show_count', true),
        'show_last_activity' => cpc_get_shortcode_value($values, 'cpc_forums-show_last_activity', true),
        'show_freshness'    => cpc_get_shortcode_value($values, 'cpc_forums-show_freshness', true),
        'forum_count_include_replies' => cpc_get_shortcode_value($values, 'cpc_forums-forum_count_include_replies', true),
        'no_indent'         => cpc_get_shortcode_value($values, 'cpc_forums-no_indent', true),        
        'level_0_links'     => cpc_get_shortcode_value($values, 'cpc_forums-level_0_links', true),        
	    'title_length'		=> (int)cpc_get_shortcode_value($values, 'cpc_forums-title_length', 50),
		'header_title' => cpc_get_shortcode_value($values, 'cpc_forums-header_title', __('Thema', CPC2_TEXT_DOMAIN)),
		'header_count' => cpc_get_shortcode_value($values, 'cpc_forums-header_count', __('Antworten', CPC2_TEXT_DOMAIN)),
		'header_last_activity' => cpc_get_shortcode_value($values, 'cpc_forums-header_last_activity', __('Letzte Aktivität', CPC2_TEXT_DOMAIN)),
        'featured_image_width' => cpc_get_shortcode_value($values, 'cpc_forums-featured_image_width', 0),
		'base_date' => cpc_get_shortcode_value($values, 'cpc_forums-base_date', 'post_date_gmt'),
	), $atts, 'cpc_forums_children' ) );
    
	$terms = get_terms( "cpc_forum", array(
		'parent'		=> $forum_id,
	    'hide_empty'    => false, 
	    'fields'        => 'all', 
	    'slug'			=> $slug,
	    'hierarchical'  => false, 
	    'child_of'      => $forum_id, 
	) );
    
    $heading_indent_h = $indent;
    $heading_indent = ($no_indent) ? 0 : $indent;

	// Translate show_closed
	$show_closed = ($show_closed) ? '' : 'open';

	if ( count($terms) > 0 ):

		$forums = array();

		foreach ( $terms as $term ):

			if (user_can_see_forum($current_user->ID, $term->term_id) || current_user_can('manage_options')):

                if (($include == 'all') || ($include == 'public' && cpc_get_term_meta($term->term_id, 'cpc_forum_public', true)) || ($include == 'private' && !cpc_get_term_meta($term->term_id, 'cpc_forum_public', true))):
    
                    $forum = array();
                    $forum['term_id'] = $term->term_id;
                    $forum['order'] = cpc_get_term_meta($term->term_id, 'cpc_forum_order', true);
                    $forum['name'] = $term->name;
                    $forum['slug'] = $term->slug;
                    if ($term->description):
                        $forum['description'] = $term->description;
                    else:
                        $forum['description'] = '&nbsp;';
                    endif;
                    $forum['count'] = $term->count;

                    $forums[$term->term_id] = $forum;
    
                endif;

			endif;

		endforeach;

		if ($forums):
        
			// Sort the forums by order first, then name
			$sort = array();
			foreach($forums as $k=>$v) {
			    $sort['order'][$k] = $v['order'];
			    $sort['name'][$k] = $v['name'];
			}
			array_multisort($sort['order'], SORT_ASC, $sort['name'], SORT_ASC, $forums);

            global $wpdb;
    
			foreach ($forums as $forum):
    
                // Does this forum have meta data for the last post?
                if ($last_post_id = cpc_get_term_meta($forum['term_id'], 'cpc_last_post_id', true)):
    
                    if ($base_date == 'post_date_gmt'):
                        $post_date = cpc_get_term_meta($forum['term_id'], 'cpc_last_post_created_gmt', true);
                    else:
                        $post_date = cpc_get_term_meta($forum['term_id'], 'cpc_last_post_created', true);
                    endif;
                    $created = sprintf(__('vor %s', CPC2_TEXT_DOMAIN), human_time_diff(strtotime($post_date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                    $last_post_author = cpc_get_term_meta($forum['term_id'], 'cpc_last_post_author', true);
                    $user = get_user_by('id', $last_post_author);
                    if ($user):
                        $author = $user->display_name;
                    else:
                        $author = __('Kein Benutzer gefunden', CPC2_TEXT_DOMAIN);
                    endif;

                    // now check for latest reply
                    if ($forum_count_include_replies && $last_reply_id = cpc_get_term_meta($forum['term_id'], 'cpc_last_reply_id', true)):

                        if ($base_date == 'post_date_gmt'):
                            $comment_date = cpc_get_term_meta($forum['term_id'], 'cpc_last_reply_created_gmt', true);
                        else:
                            $comment_date = cpc_get_term_meta($forum['term_id'], 'cpc_last_reply_created', true);
                        endif;
                        // ... is this later than the last post?
                        if (strtotime($comment_date) > strtotime($post_date)):
                            $created = sprintf(__('%s ago', CPC2_TEXT_DOMAIN), human_time_diff(strtotime($comment_date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                            $last_reply_author = cpc_get_term_meta($forum['term_id'], 'cpc_last_reply_author', true);
                            $user = get_user_by('id', $last_reply_author);
                            if ($user):
                                $author = $user->display_name;
                            else:
                                $author = __('Kein Benutzer gefunden', CPC2_TEXT_DOMAIN);
                            endif;                            
                        endif;

                    endif;
    
                else:
    
                    // this is the older way, and is much slower on big forums...
    
                    $posts_per_page = $forum_count_include_replies ? -1 : 1;

                    $loop = new WP_Query( array(
                        'post_type' => 'cpc_forum_post',
                        'posts_per_page' => $posts_per_page,
                        'no_found_rows' => true,
                        'nopaging' => true,
                        'post_status' => 'publish',
                        'tax_query' => array(
                            array(
                                'taxonomy' => 'cpc_forum',
                                'field' => 'slug',
                                'terms' => $forum['slug'],
                            )
                        )				
                    ) );

                    global $post;
                    $comment_count = 0;
                    $post_ptr = 0;

                    if ($loop->have_posts()):

                        while ( $loop->have_posts() ) : $loop->the_post();

                            if (!$post_ptr):
                                $user = get_user_by('id', $post->post_author);
                                $author = $user->display_name;
                                $date = $base_date == 'post_date_gmt' ? $post->post_date_gmt : $post->post_date;
                                $created = sprintf(__('%s ago', CPC2_TEXT_DOMAIN), human_time_diff(strtotime($date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                            endif;
                            $comment_count++; // add count of post itself
                            // Get count of comments if needed
                            if ($forum_count_include_replies):
                                $sql = "SELECT COUNT(comment_ID) FROM ".$wpdb->prefix."comments WHERE comment_post_ID = %d AND comment_approved = 1 AND comment_type = 'cpc_forum_comment'";
                                $comments_count = $wpdb->get_var($wpdb->prepare($sql, $post->ID));
                                $comment_count = $comment_count + $comments_count.'<br>';
                                $sql = "SELECT * FROM ".$wpdb->prefix."comments WHERE comment_post_ID = %d ORDER BY comment_ID DESC LIMIT 0,1";
                                $comments = $wpdb->get_results($wpdb->prepare($sql, $post->ID));

                                if ($comments):
                                    foreach ($comments as $comment):    

                                        if ($comment->user_id): // exclude auto-closed
                                            $comment_user = get_user_by('id', $comment->user_id);
                                            $private = get_comment_meta( $comment->comment_ID, 'cpc_private_post', true );
                                            if (!$private || $current_user->ID == $post->post_author || $comment->user_id == $current_user->ID || current_user_can('manage_options')):

                                                $comment_author = $user->display_name;
                                                $comment_date = $base_date == 'post_date_gmt' ? $comment->comment_date_gmt : $comment->comment_date;
                                                $comment_created = sprintf(__('%s ago', CPC2_TEXT_DOMAIN), human_time_diff(strtotime($comment_date), current_time('timestamp', 1)), CPC2_TEXT_DOMAIN);
                                                if ($comment_date > $date):
                                                    $author = $comment_author;
                                                    $date = $comment_date;
                                                    $created = $comment_created;
                                                endif;
                                            endif;
                                        endif;

                                    endforeach;
                                endif;

                            endif;
                            $post_ptr++;
                        endwhile;
                    else:
                        $author = '-';
                        $created = '-';
                    endif;
    
                endif; // Finished getting last post/comment info

				wp_reset_query();

				$page_id = cpc_get_term_meta($forum['term_id'], 'cpc_forum_cat_page', true);
				$url = get_permalink($page_id);
                $forum_html = '';
    
                $featured_image = cpc_get_term_meta($forum['term_id'], 'cpc_forum_featured_image', true);
                if (!$featured_image):
                    $featured_image = '';
                    $featured_image_width = 0;
                    $featured_image_padding = 0;
                else:
                    $featured_image_padding = $featured_image_width ? 15 : 0;
                endif;
                    
                if (!$show_as_dropdown):  
    
                	// Show as web page

	                $forum_html .= '<div class="cpc_forum_featured_content" style="position:relative; padding-left: '.($featured_image_width+$featured_image_padding).'px; ">';

	                    $forum_html .= '<div class="cpc_forum_featured_image" style="margin-left: -'.($featured_image_width+$featured_image_padding).'px;float: left;width: '.$featured_image_width.'px">';
	                        if ($featured_image) $forum_html .= '<img style="width:100%" src="'.$featured_image.'" />';
	                    $forum_html .= '</div>';

	                    $forum_html .= '<div style="width: 100%; " class="cpc_forum_categories_item cpc_forum_categories_item_'.$heading_indent_h.'">';
	                        $forum_html .= '<div class="cpc_forum_categories_name cpc_forum_categories_name_'.$heading_indent_h.'" style="padding-left:'.($heading_indent*20).'px;">';
	                        $forum_name = $forum['name'];
	                        $forum_suffix = apply_filters( 'cpc_forum_name_filter', '', $forum_name, $forum['term_id'] );
	                        $forum_html .= '<h'.($heading_indent_h+2).' style="margin-top:0">';
	                        if ($heading_indent_h || (!$heading_indent_h && $level_0_links)):
	                            $forum_html .= '<a class="cpc_forums_forum_title" href="'.$url.'">'.$forum_name.'</a>';
	                        else:
	                            $forum_html .= '<span class="cpc_forums_forum_title">'.$forum_name.'</span>';
	                        endif;
	                        $forum_html .= $forum_suffix.'</h'.($heading_indent_h+2).'>';
	                        $forum_html .= '</div>';
	                        $forum_html .= '<div class="cpc_forum_categories_item_info cpc_forum_categories_item_info_'.$heading_indent_h.'">';    

	                            $forum_html .= '<div class="cpc_forum_categories_description" style="padding-left:'.($heading_indent*20).'px;">';
	                                $forum_html .= $forum['description'];
	                            $forum_html .= '</div>';
	                            if ($show_summary):
	                                if ($show_freshness):
	                                    $forum_html .= '<div class="cpc_forum_categories_freshness">';
	                                        $forum_html .= $created;
	                                    $forum_html .= '</div>';
	                                endif;
	                                if ($show_last_activity):
	                                    $forum_html .= '<div class="cpc_forum_categories_last_poster">';
	                                        $forum_html .= $author;
	                                    $forum_html .= '</div>';
	                                endif;
	                                if ($show_count):
	                                    $forum_html .= '<div class="cpc_forum_categories_count">';
	                                        $count = $forum['count'];
	                                        $forum_html .= $count;
	                                    $forum_html .= '</div>';
	                                endif;
	                            endif;

	                        $forum_html .= '</div>';
	                        if ($show_posts != 'none'):
	                            $forum_html .= '<div class="cpc_forum_categories_item_sep cpc_forum_categories_item_sep_'.$heading_indent_h.'"></div>';
	                            $forum_html .= '<div class="cpc_forum_child cpc_forum_child_'.$heading_indent_h.'">';
	                            $forum_html .= cpc_forum(array(
                                    'style' => 'table', 
                                    'slug' => $forum['slug'], 
                                    'show_closed' => $show_closed, 
                                    'base_date' => $base_date, 
                                    'status' => $show_closed, 
                                    'show_header' => $show_posts_header, 
                                    'max_forum_posts' => $show_posts, 
                                    'count' => $show_posts, 
                                    'max_posts_no_pagination_posts' => $show_posts,
                                    'header_title'=>$header_title, 
                                    'header_count'=>$header_count, 
                                    'header_last_activity' => $header_last_activity, 
                                    'title_length' => $title_length, 
                                    'show_count' => $show_count, 
                                    'show_last_activity' => $show_last_activity, 
                                    'show_freshness' => $show_freshness, 
                                    'pagination_posts' => false,
                                )); 
    
	                            $forum_html .= '</div>';
	                        endif;

	                        $forum_html = apply_filters( 'cpc_forum_categories_item_filter', $forum_html );

	                    $forum_html .= '</div>';

	                $forum_html .= '</div>';    

	            else:

	            	// Show as drop-down list

	            	$forum_html .= '<option value="'.$url.'">';
		            	$forum_html .= str_repeat('&nbsp;', $heading_indent_h*3);
		            	$forum_html .= $forum['name'];
	            	$forum_html .= '</option>';


	            endif;

                $html .= $forum_html;

                if ($show_children)
                    $html = cpc_forum_categories_children ($html, $values, $slug, $forum['term_id'], $show_children, $atts, $indent+1);

            endforeach;

		endif;

	endif;

	return $html;

}

function cpc_forum_sharethis_insert($atts) {
    
    // Init
	add_action('wp_footer', 'cpc_forum_init');
    
    global $current_user;
    
	// Shortcode parameters
	extract( shortcode_atts( array(
		'slug' => '',
	), $atts, 'cpc_forum_page' ) );

	if ($slug == ''):

		return sprintf(__('Bitte füge slug="xxx" zum Shortcode hinzu, wobei xxx der <a href="%s">Slug des Forums</a> ist.', CPC2_TEXT_DOMAIN), admin_url('edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post'));

	else:

        $term = get_term_by('slug', $slug, 'cpc_forum');
        if ($term):
            if (user_can_see_forum($current_user->ID, $term->term_id) || current_user_can('manage_options')):   
                if ( get_query_var('topic') || isset($_GET['topic_id'])):
                    if ($sharethis = get_option('cpc_forum_sharethis_buttons'))
                        return get_option('cpc_forum_sharethis_buttons');
                endif;
            endif;
        endif;

	endif;

}

// Show content if is a single forum post
function cpc_is_forum_posts_list($atts, $content="") {

    // Init
    add_action('wp_footer', 'cpc_forum_init');

    // Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_is_forum_posts_list');
    extract( shortcode_atts( array(
        'styles' => true,
        'after' => '',
        'before' => '',        
    ), $atts, 'cpc_is_forum_posts_list' ) );        

    $html = '';
    global $current_user;

    if ( !get_query_var('topic') && !isset($_GET['topic_id']))
        $html .= do_shortcode($content);

    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_is_forum_posts_list', $before, $after, $styles, $values);        

    return $html;    

}

// Show content if is a single forum post
function cpc_is_forum_single_post($atts, $content="") {

    // Init
    add_action('wp_footer', 'cpc_forum_init');

    // Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_is_forum_single_post');
    extract( shortcode_atts( array(
        'styles' => true,
        'after' => '',
        'before' => '',        
    ), $atts, 'cpc_is_forum_single_post' ) );        

    $html = '';
    global $current_user;

    if ( get_query_var('topic') || isset($_GET['topic_id']))
        $html .= do_shortcode($content);

    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_is_forum_single_post', $before, $after, $styles, $values);        

    return $html;    

}

if (!is_admin()) add_shortcode(CPC_PREFIX.'-forum-page', 'cpc_forum_page');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-forum', 'cpc_forum');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-forum-post', 'cpc_forum_post');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-forum-backto', 'cpc_forum_backto');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-forum-comment', 'cpc_forum_comment');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-forum-reply', 'cpc_forum_comment');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-forums', 'cpc_forums');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-forum-show-posts', 'cpc_forum_show_posts');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-forum-sharethis', 'cpc_forum_sharethis_insert');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-forum-children', 'cpc_forum_children');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-is-forum-posts-list', 'cpc_is_forum_posts_list');
if (!is_admin()) add_shortcode(CPC_PREFIX.'-is-forum-single-post', 'cpc_is_forum_single_post');


// Function used to sort randomly
function cpc_rand_cmp($a, $b){
    return rand() > rand();
}


function cpc_insert_pagination($page, $page_count, $pagination_first, $pagination_previous, $pagination_next, $pagination_url) {

    $pagination_url = urldecode($pagination_url); // remove any dodgy characters that could mess up sprintf below
    $h = '<div class="cpc_pagination_numbers">';

    if ($pagination_first && $page > 3):
        if (cpc_using_permalinks()) {
            $prev_page_url = sprintf($pagination_url, 1);
        } else {
            $prev_page_url = sprintf($pagination_url, 1);
        }			
        $h .= '<div class="cpc_pagination_number">';
        $h .= '<a style="text-decoration: none;" href="'.$prev_page_url.'">'.$pagination_first.'</a>';
        $h .= '</div>';
    endif;
    if ($pagination_previous && $page > 1):
        if (cpc_using_permalinks()) {
            $prev_page_url = sprintf($pagination_url, $page-1);
        } else {
            $prev_page_url = sprintf($pagination_url, $page-1);
        }			
        $h .= '<div class="cpc_pagination_number">';
        $h .= '<a style="text-decoration: none;" href="'.$prev_page_url.'">'.$pagination_previous.'</a>';
        $h .= '</div>';
    endif;
    $shown_dots = false;
    for ($x=1; $x<=$page_count; $x++) {
		$page_url = sprintf($pagination_url, $x);
        $show_number = ($x >= $page+3 && $x <= $page_count-3) ? false : true;
        if ($show_number):
            if ($x > $page-3):
                $h .= '<div class="cpc_pagination_number';
                if ($x == $page) $h .= ' cpc_pagination_number_current';
                $h .= '">';
                $h .= '<a style="text-decoration: none;" href="'.$page_url.'">'.$x.'</a>';
                $h .= '</div>';
            endif;
        else:
            if (!$shown_dots)
                $h .= '<div class="cpc_pagination_number_dots">...</div>';
            $shown_dots = true;
        endif;
    } 

    if ($pagination_next && $page < $page_count):
        $prev_page_url = sprintf($pagination_url, $page+1);
        $h .= '<div class="cpc_pagination_number">';
        $h .= '<a style="text-decoration: none;" href="'.$prev_page_url.'">'.$pagination_next.'</a>';
        $h .= '</div>';
    endif;

    $h .= '</div>';

    return $h;
}

?>
