<?php
$term = get_term_by('slug', $slug, 'cpc_forum');
$term_children = get_term_children($term->term_id, 'cpc_forum');

$forum_posts = array();
global $post, $current_user;

// Prepare pagination
if ($pagination_posts): // only do if using pagination
    if (isset($_GET['fpage'])):
        $current_page = isset($_GET['fpage']) ? $_GET['fpage'] : 1; // No permalinks
    else:
        $current_page = 1;
    endif;

    if ( cpc_using_permalinks() ):
        if (!is_multisite()):
            $pagination_url = get_bloginfo('url').'/'.$term->slug.'?fpage=%d';
        else:
            $blog_details = get_blog_details(get_current_blog_id());
            $pagination_url = $blog_details->path.$term->slug.'?fpage=%d';
        endif;
    else:
        if (!is_multisite()):
            $pagination_url = get_bloginfo('url').'?page_id='.$_GET['page_id'].'&fpage=%d';
        else:
            $blog_details = get_blog_details(get_current_blog_id());
            $pagination_url = $blog_details->path.$term->slug."?fpage=%d";
        endif;
    endif;    
else:
    // no pagination
    $current_page = 1;
    $page_size_posts = -1;
    $max_pages_posts = 0;
endif;

// Note state of closed switch
if ($closed_switch) {
    $closed_switch_state = is_user_logged_in() ? get_user_meta($current_user->ID, 'forum_closed_switch', true) : $closed_switch;
    if (!$closed_switch_state) { $closed_switch_state = 'on'; }
} else {
    $closed_switch_state = 'on';
}

// Get posts to use
if ($pagination_posts) {
    $loop = new WP_Query( array(
        'post_type' => 'cpc_forum_post',
        'posts_per_page' => $page_size_posts,
        'page' => $current_page,
        'offset' => ( $current_page - 1 ) * $page_size_posts,
        'tax_query' => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'cpc_forum',
                'field' => 'slug',
                'terms' => $slug,
            ),
            array( 
                'taxonomy' => 'cpc_forum',
                'field' => 'id',
                'terms' => $term_children,
                'operator' => 'NOT IN'
                )
            ),
    ) );
    $posts_count = $loop->found_posts;
    $num_of_pages = $loop->max_num_pages;
    if ($max_pages_posts < $num_of_pages) { $num_of_pages = $max_pages_posts; }
} else {
    $loop = new WP_Query( array(
        'post_type' => 'cpc_forum_post',
        'posts_per_page' => $max_posts_no_pagination_posts,
        'no_found_rows' => true,
        'nopaging' => false,
        'tax_query' => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'cpc_forum',
                'field' => 'slug',
                'terms' => $slug,
            ),
            array( 
                'taxonomy' => 'cpc_forum',
                'field' => 'id',
                'terms' => $term_children,
                'operator' => 'NOT IN'
                )
            ),
    ) );
}

// Translate show_closed
$status = (($show_closed && $show_closed != 'open') || ($show_closed == '')) ? '' : 'open';

if ($loop->have_posts()):

	while ( $loop->have_posts() ) : $loop->the_post();

        $cpc_forum_author = cpc_get_term_meta($term->term_id, 'cpc_forum_author', true);

		if (!$cpc_forum_author || $post->post_author == $current_user->ID || current_user_can('manage_options')):

			// Check status
			$the_post = get_post($post->ID);
			if ($status == '' || $status == $post->comment_status):

                $forum_post = array();
                $forum_post['ID'] = $post->ID;
                $forum_post['post_status'] = $post->post_status;
                $forum_post['post_author'] = $post->post_author;
                $forum_post['post_name'] = $post->post_name;
                $forum_post['post_title'] = $post->post_title;
                $forum_post['post_content'] = $post->post_content;
                $forum_post['post_date'] = $post->post_date;
                $forum_post['post_date_gmt'] = $post->post_date_gmt;
                $forum_post['comment_status'] = $post->comment_status;
                $forum_post['is_sticky'] = get_post_meta($post->ID, 'cpc_sticky');
                // default read status to true (ie. not new)
                $forum_post['read'] = true;
                $forum_post['new'] = false;

                // Get replies (not comments)
                $args = array(
                    'status' => 1,
                    'orderby' => 'comment_ID',
                    'order' => 'DESC',
                    'post_id' => $post->ID,
                    'number' => 99999,
                );
                $comments = get_comments($args);
                $forum_post['commented'] = 0;
                if ($comments):
                    $forum_post['last_comment'] = $comments[0]->comment_date;
                    foreach ($comments as $comment):
                        if ($comment->comment_author == $current_user->user_login) $forum_post['commented']++;
                    endforeach;
                else:
                    $forum_post['last_comment'] = $post->post_date;
                endif;

                $forum_posts[$post->ID] = $forum_post;

			endif;

		endif;

	endwhile;

endif;

if ($forum_posts):

    if ($closed_switch):
        $html .= '<input type="checkbox" id="closed_switch"';
            if ($closed_switch_state == 'on') $html .= ' CHECKED';
            $html .= ' /> ';
        $html .= html_entity_decode($closed_switch_msg);
    endif;

	// Sort the posts by sticky first, then last contributed to, finally last added
	$sort = array();
	foreach($forum_posts as $k=>$v) {
	    $sort['is_sticky'][$k] = $v['is_sticky'];
	    $sort['read'][$k] = $v['read'];
	    $sort['last_comment'][$k] = $v['last_comment'];
	    $sort['ID'][$k] = $v['ID'];
	}
	array_multisort($sort['is_sticky'], SORT_DESC, $sort['last_comment'], SORT_DESC, $sort['read'], SORT_ASC, $sort['ID'], SORT_DESC, $forum_posts); 

    // Now display based on style
    if ($style):

        // pagination, if enabled
        if ($pagination_posts && $num_of_pages > 1 && $pagination_top_posts):
            $html .= '<div id="cpc_forum_pagination_posts_top">';
                $html .= cpc_insert_pagination($current_page, $num_of_pages, $pagination_first_posts, $pagination_previous_posts, $pagination_next_posts, $pagination_url);							
            $html .= '</div>';
        endif;

        $file = dirname(__FILE__).'/cpc_forum_posts_'.$style.'.php';
        if( file_exists($file) ):
            include($file);
        else:
            $html .= sprintf(__('Die Forumdatei existiert nicht (%s).', CPC2_TEXT_DOMAIN), $file);
        endif;

        // pagination, if enabled
        if ($pagination_posts && $num_of_pages > 1 && $pagination_bottom_posts):
            $html .= '<div id="cpc_forum_pagination_posts_bottom">';
                $html .= cpc_insert_pagination($current_page, $num_of_pages, $pagination_first_posts, $pagination_previous_posts, $pagination_next_posts, $pagination_url);							
            $html .= '</div>';
        endif;

    else:
        $html .= sprintf(__('Ungültige style-Option für Forum-Shortcode (%s).', CPC2_TEXT_DOMAIN), $style);
    endif;

else:

	$html .= '<div style="clear: both">'.$empty_msg.'</div>';

endif;

wp_reset_query();

?>

