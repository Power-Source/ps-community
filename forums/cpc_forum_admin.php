<?php
function cpccom_forum_setup() {

	global $wpdb;

  	echo '<div class="wrap">';
        	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';

	  	echo '<h2>'.__('Alle Foren', CPC2_TEXT_DOMAIN).'</h2>';

	  	if (isset($_GET['action']) && $_GET['action'] == 'cpc_forum_delete'):

	  		$term_id = $_GET['term_ID'];
			$term = get_term_by( 'id', $term_id, 'cpc_forum' );

			if ($term):

				// Get posts
				wp_reset_query();
				$loop = new WP_Query( array(
					'post_type' => 'cpc_forum_post',
					'tax_query' => array(
						'relation' => 'AND',
						array(
							'taxonomy' => 'cpc_forum',
							'field' => 'slug',
							'terms' => $term->slug,
						),
						array( 
							'taxonomy' => 'cpc_forum',
							'field' => 'id',
							'terms' => $term->term_children,
							'operator' => 'NOT IN'
							)
					    ),
				) );

				if ($loop->have_posts()):

					$topics = $loop->posts;

					foreach ($topics as $post):

	                    // Loop through comments and delete it, and sub comments as you go
	                    
	                    $sql = "SELECT comment_ID FROM ".$wpdb->prefix."comments WHERE comment_post_ID = %d";
	                    $comments = $wpdb->get_col($wpdb->prepare($sql, $post->ID));

	                    if ($comments):
	                        foreach ($comments as $comment_id):

	                        	// delete sub comments...
			                    $sql = "SELECT comment_ID FROM ".$wpdb->prefix."comments WHERE comment_parent = %d";
			                    $sub_comments = $wpdb->get_col($wpdb->prepare($sql, $comment_id));
	                            if ($sub_comments):
	                            	foreach ($sub_comments as $subcomment_id):
	                            		wp_delete_comment($subcomment_id, true);
	                            		// delete comment meta
	                            		$sql = "DELETE FROM ".$wpdb->prefix."commentmeta WHERE comment_id = %d";
	                            		$wpdb->query($wpdb->prepare($sql, $subcomment_id));
	                            	endforeach;
	                            endif;

	                            // delete comment...
	                            wp_delete_comment($comment_id, true);
	                    		// delete comment meta
	                    		$sql = "DELETE FROM ".$wpdb->prefix."commentmeta WHERE comment_id = %d";
	                    		$wpdb->query($wpdb->prepare($sql, $comment_id));

	                        endforeach;
	                    endif;
	        
						// delete post
						wp_delete_post( $post->ID, true );	
						// and delete post meta
	            		$sql = "DELETE FROM ".$wpdb->prefix."postmeta WHERE post_id = %d";
	            		$wpdb->query($wpdb->prepare($sql, $post->ID));

					endforeach;

				endif;

        		// delete WordPress page
        		$forum_page = cpc_get_term_meta($term_id, 'cpc_forum_cat_page', true);
        		if ($forum_page) wp_delete_post( $forum_page, true ); // permanently
				// finally, delete forum
				wp_delete_term( $term_id, 'cpc_forum' );

			endif;

	  	endif;

	  	// carry on, show forum info
    
	  	if (isset($_POST['cpc_forum_id'])):
			$range = array_keys($_POST['cpc_forum_id']);
			foreach ($range as $key):
				$cpc_forum_id = $_POST['cpc_forum_id'][$key];
				$cpc_forum_order = $_POST['cpc_forum_order'][$key];
				$cpc_forum_public = $_POST['cpc_forum_public'][$key];
				$cpc_forum_closed = $_POST['cpc_forum_closed'][$key];
				$cpc_forum_author = $_POST['cpc_forum_author'][$key];
                $cpc_forum_auto = $_POST['cpc_forum_auto'][$key];

				if ($cpc_forum_order):
					cpc_update_term_meta( $cpc_forum_id, 'cpc_forum_order', $cpc_forum_order );
				else:
					cpc_update_term_meta( $cpc_forum_id, 'cpc_forum_order', 0 );
				endif;

				if ($cpc_forum_public):
					cpc_update_term_meta( $cpc_forum_id, 'cpc_forum_public', true );
				else:
					cpc_update_term_meta( $cpc_forum_id, 'cpc_forum_public', false );
				endif;

				if ($cpc_forum_closed):
					cpc_update_term_meta( $cpc_forum_id, 'cpc_forum_closed', $_POST['cpc_forum_closed'] );
				else:
					cpc_update_term_meta( $cpc_forum_id, 'cpc_forum_closed', 0 );
				endif;

				if ($cpc_forum_author):
					cpc_update_term_meta( $cpc_forum_id, 'cpc_forum_author', $cpc_forum_author );
				else:
					cpc_update_term_meta( $cpc_forum_id, 'cpc_forum_author', 0 );
				endif;

				if ($cpc_forum_auto):
					cpc_update_term_meta( $cpc_forum_id, 'cpc_forum_auto', $cpc_forum_auto );
				else:
					cpc_update_term_meta( $cpc_forum_id, 'cpc_forum_auto', 0 );
				endif;

				do_action( 'cpccom_forum_setup_save', $cpc_forum_id, $_POST );

			endforeach;

		endif;

		$terms = get_terms( "cpc_forum", array(
		    'hide_empty'    => false, 
		    'fields'        => 'all', 
		    'hierarchical'  => false, 
		    'order'			=> 'ASC',
		    'orderby'		=> 'name'
		) );

		if ($terms):

			$sort = array();
			foreach ($terms as $k=>$v):
		    	$sort['term_id'][$k] = $v->term_id;				
		    	$sort['name'][$k] = $v->name;				
		    	$sort['order'][$k] = cpc_get_term_meta($v->term_id, 'cpc_forum_order', true);				
			endforeach;
			array_multisort($sort['order'], SORT_ASC, $sort['name'], SORT_ASC, $terms);

			echo '<form action="" method="POST">';

				echo '<br /><table class="widefat">';
    
                echo '<tr>';
                    echo '<td>'.__('Forum', CPC2_TEXT_DOMAIN).'</td>';
                    echo '<td style="text-align:center">'.__('Sortierung', CPC2_TEXT_DOMAIN).'</td>';
                    echo '<td style="text-align:center">'.__('Privatsphäre', CPC2_TEXT_DOMAIN).'</td>';
                    echo '<td style="text-align:center">'.__('Status', CPC2_TEXT_DOMAIN).'</td>';
                    echo '<td style="text-align:center">'.__('Sichtbarkeit', CPC2_TEXT_DOMAIN).'</td>';
                    if (function_exists('cpc_forum_subs_extension_insert_rewrite_rules')) echo '<td style="text-align:center">'.__('Automatisch abonnieren', CPC2_TEXT_DOMAIN).'</td>';
                echo '<tr>';

				foreach ($terms as $term):

					echo '<tr>';

						$page_id = cpc_get_term_meta($term->term_id, 'cpc_forum_cat_page', true);
						$url = $page_id ? get_permalink($page_id) : false;

						echo '<td style="border-top:1px solid #cfcfcf;width:40%;">';
						echo '<strong><a style="text-decoration:none" href="edit-tags.php?action=edit&taxonomy=cpc_forum&tag_ID='.$term->term_id.'&post_type=cpc_forum_post">'.$term->name.'</a></strong><br />'.urldecode($term->slug).'<br />';
						echo '<a style="text-decoration:none" href="edit-tags.php?action=edit&taxonomy=cpc_forum&tag_ID='.$term->term_id.'&post_type=cpc_forum_post">'.__('Bearbeiten', CPC2_TEXT_DOMAIN).'</a> | ';
						if ($page_id) {
							echo '<a style="text-decoration:none" href="post.php?post='.$page_id.'&action=edit">'.__('Seite', CPC2_TEXT_DOMAIN).'</a> | ';
							if ($url) echo '<a style="text-decoration:none" href="'.$url.'">'.__('Ansehen', CPC2_TEXT_DOMAIN).'</a>';
						} else {
							echo '<a href="edit-tags.php?action=edit&taxonomy=cpc_forum&tag_ID='.$term->term_id.'&post_type=cpc_forum_post">'.__('WordPress-Seite auswählen...', CPC2_TEXT_DOMAIN).'</a>';
						}
						echo ' | <a style="text-decoration:none;color:#f00;" onclick="if (!confirm(\'Are you sure, this cannot be reversed?\')) return false;" href="admin.php?page=cpccom_forum_setup&action=cpc_forum_delete&term_ID='.$term->term_id.'">'.__('Löschen', CPC2_TEXT_DOMAIN).'</a>';
						echo '</td>';

						echo '<input name="cpc_forum_id[]" type="hidden" value="'.$term->term_id.'" />'; 

						echo '<td style="text-align:center;border-top:1px solid #cfcfcf;width:15%;">';
						echo '<input name="cpc_forum_order[]" id="cpc_forum_order" type="text" value="';
						echo cpc_get_term_meta($term->term_id, 'cpc_forum_order', true);
						echo '" style="width:50px" />';
						echo '</td>';

						echo '<td style="text-align:center;border-top:1px solid #cfcfcf;width:15%;">';
						echo '<select name="cpc_forum_public[]">';
						if ( !cpc_get_term_meta($term->term_id, 'cpc_forum_public', true) ):
							echo '<option value="0" SELECTED>'.__('Privat', CPC2_TEXT_DOMAIN).'</option>';
							echo '<option value="1">'.__('Öffentlich', CPC2_TEXT_DOMAIN).'</option>';
						else:
							echo '<option value="0">'.__('Privat', CPC2_TEXT_DOMAIN).'</option>';
							echo '<option value="1" SELECTED>'.__('Öffentlich', CPC2_TEXT_DOMAIN).'</option>';
						endif;
						echo '</select>';
						echo '</td>';

						echo '<td style="text-align:center;border-top:1px solid #cfcfcf;width:15%;">';
						echo '<select name="cpc_forum_closed[]">';
						if ( !cpc_get_term_meta($term->term_id, 'cpc_forum_closed', true) ):
							echo '<option value="0" SELECTED>'.__('Offen', CPC2_TEXT_DOMAIN).'</option>';
							echo '<option value="1">'.__('Geschlossen', CPC2_TEXT_DOMAIN).'</option>';
						else:
							echo '<option value="0">'.__('Offen', CPC2_TEXT_DOMAIN).'</option>';
							echo '<option value="1" SELECTED>'.__('Geschlossen', CPC2_TEXT_DOMAIN).'</option>';
						endif;
						echo '</select>';
						echo '</td>';

						echo '<td style="text-align:center;border-top:1px solid #cfcfcf;width:15%;">';
						echo '<select name="cpc_forum_author[]">';
						if ( !cpc_get_term_meta($term->term_id, 'cpc_forum_author', true) ):
							echo '<option value="0" SELECTED>'.__('Alle', CPC2_TEXT_DOMAIN).'</option>';
							echo '<option value="1">'.__('Nur eigene', CPC2_TEXT_DOMAIN).'</option>';
						else:
							echo '<option value="0">'.__('Alle', CPC2_TEXT_DOMAIN).'</option>';
							echo '<option value="1" SELECTED>'.__('Nur eigene', CPC2_TEXT_DOMAIN).'</option>';
						endif;
						echo '</select>';
						echo '</td>';

                        if (function_exists('cpc_forum_subs_extension_insert_rewrite_rules')):
                            echo '<td style="text-align:center;border-top:1px solid #cfcfcf;width:15%;">';
                            echo '<select name="cpc_forum_auto[]">';
                            if ( !cpc_get_term_meta($term->term_id, 'cpc_forum_auto', true) ):
                                echo '<option value="0" SELECTED>'.__('Nein', CPC2_TEXT_DOMAIN).'</option>';
                                echo '<option value="1">'.__('Ja', CPC2_TEXT_DOMAIN).'</option>';
                            else:
                                echo '<option value="0">'.__('Nein', CPC2_TEXT_DOMAIN).'</option>';
                                echo '<option value="1" SELECTED>'.__('Ja', CPC2_TEXT_DOMAIN).'</option>';
                            endif;
                            echo '</select>';
                            echo '</td>';
                        endif;
    
					echo '</tr>';

					// Any more? (eg. forum security)
					do_action( 'cpccom_forum_setup_after', $term );

				endforeach;

				echo '</table>';

			echo '<br /><input type="submit" class="button-primary" value="'.__('Aktualisieren', CPC2_TEXT_DOMAIN).'" />';
			echo '</form>';

		else:

			echo '<a href="admin.php?page=cpc_com_setup">'.__('Füge über Setup ein Forum hinzu', CPC2_TEXT_DOMAIN).'</a>';

		endif;

		
	echo '</div>';	  	

}



?>