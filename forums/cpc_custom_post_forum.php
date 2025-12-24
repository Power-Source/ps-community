<?php

/* Create forum_post custom post type */

/* =========================== LABELS FOR ADMIN =========================== */


function cpc_custom_post_forum_post() {
	$labels = array(
		'name'               => __( 'Beiträge', CPC2_TEXT_DOMAIN ),
		'singular_name'      => __( 'Beitrag', CPC2_TEXT_DOMAIN ),
		'add_new'            => __( 'Neuen hinzufügen', CPC2_TEXT_DOMAIN ),
		'add_new_item'       => __( 'Neuen Beitrag hinzufügen', CPC2_TEXT_DOMAIN ),
		'edit_item'          => __( 'Beitrag bearbeiten', CPC2_TEXT_DOMAIN ),
		'new_item'           => __( 'Neuer Beitrag', CPC2_TEXT_DOMAIN ),
		'all_items'          => __( 'Forumbeiträge', CPC2_TEXT_DOMAIN ),
		'view_item'          => __( 'Forumbeitrag anzeigen', CPC2_TEXT_DOMAIN ),
		'search_items'       => __( 'Forenbeiträge durchsuchen', CPC2_TEXT_DOMAIN ),
		'not_found'          => __( 'Kein Forumsbeitrag gefunden', CPC2_TEXT_DOMAIN ),
		'not_found_in_trash' => __( 'Kein Forumsbeitrag im Papierkorb gefunden', CPC2_TEXT_DOMAIN ), 
		'parent_item_colon'  => '',
		'menu_name'          => __('Forumbeiträge', CPC2_TEXT_DOMAIN),
	);
	$args = array(
		'labels'        		=> $labels,
		'description'   		=> 'Holds our forum post specific data',
		'public'        		=> true,
        'capabilities' => array(
            'publish_posts' => 'manage_options',
            'edit_posts' => 'manage_options',
            'edit_others_posts' => 'manage_options',
            'delete_posts' => 'manage_options',
            'delete_others_posts' => 'manage_options',
            'read_private_posts' => 'manage_options',
            'edit_post' => 'manage_options',
            'delete_post' => 'manage_options',
            'read_post' => 'manage_options',
        ),              
		'exclude_from_search' 	=> true,
		'show_in_menu' 			=> get_option('cpc_core_admin_icons') ? 'cpc_com' : '',
		'publicly_queryable'	=> false,
		'has_archive'			=> false,
		'rewrite'				=> false,
		'supports'      		=> array( 'title', 'editor', 'comments', 'thumbnail' ),
	);
	register_post_type( 'cpc_forum_post', $args );
}
add_action( 'init', 'cpc_custom_post_forum_post' );

/* =========================== MESSAGES FOR ADMIN =========================== */

function cpc_updated_forum_post_messages( $messages ) {
	global $post, $post_ID;
	$messages['cpc_forum_post'] = array(
		0 => '', 
		1 => __('Beitrag aktualisiert.', CPC2_TEXT_DOMAIN),
		2 => __('Benutzerdefiniertes Feld aktualisiert.', CPC2_TEXT_DOMAIN),
		3 => __('Benutzerdefiniertes Feld gelöscht.', CPC2_TEXT_DOMAIN),
		4 => __('Beitrag aktualisiert.', CPC2_TEXT_DOMAIN),
		5 => isset($_GET['revision']) ? sprintf( __('Post restored to revision from %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Beitrag veröffentlicht.', CPC2_TEXT_DOMAIN),
		7 => __('Beitrag gespeichert.', CPC2_TEXT_DOMAIN),
		8 => __('Beitrag eingereicht.', CPC2_TEXT_DOMAIN),
		9 => sprintf( __('Beitrag geplant für: <strong>%1$s</strong>.'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __('Beitragsentwurf aktualisiert.', CPC2_TEXT_DOMAIN),
	);
	return $messages;
}
add_filter( 'post_updated_messages', 'cpc_updated_forum_post_messages' );


/* =========================== META FIELDS CONTENT BOX WHEN EDITING =========================== */


add_action( 'add_meta_boxes', 'forum_post_info_box' );
function forum_post_info_box() {
    add_meta_box( 
        'forum_post_info_box',
        __( 'Beitragsdetails', CPC2_TEXT_DOMAIN ),
        'forum_post_info_box_content',
        'cpc_forum_post',
        'side',
        'high'
    );
}

function forum_post_info_box_content( $post ) {
	global $wpdb;
	wp_nonce_field( 'forum_post_info_box_content', 'forum_post_info_box_content_nonce' );

	echo '<strong>'.__('Autor des Beitrags', CPC2_TEXT_DOMAIN).'</strong><br />';
	$author = get_user_by('id', $post->post_author);
	echo $author->display_name.'<br />';
	echo 'ID: '.$author->ID;

	echo '<br /><br >';
	echo '<input type="checkbox" name="cpc_sticky"';
		if (get_post_meta($post->ID, 'cpc_sticky', true)) echo ' CHECKED';
		echo '> '.__('Am Anfang der Beiträge anheften?', CPC2_TEXT_DOMAIN);
}

add_action( 'save_post', 'forum_post_info_box_save' );
function forum_post_info_box_save( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	return;

	if ( !isset($_POST['forum_post_info_box_content_nonce']) || !wp_verify_nonce( $_POST['forum_post_info_box_content_nonce'], 'forum_post_info_box_content' ) )
	return;

	if ( !current_user_can( 'edit_post', $post_id ) ) return;

	if (isset($_POST['cpc_sticky'])):
		update_post_meta($post_id, 'cpc_sticky', true);
	else:
		delete_post_meta($post_id, 'cpc_sticky', true);
	endif;


}

/* =========================== COLUMNS WHEN VIEWING =========================== */

/* Columns for Posts list */
add_filter('manage_posts_columns', 'forum_post_columns_head');
add_action('manage_posts_custom_column', 'forum_post_columns_content', 10, 2);

// ADD NEW COLUMN
function forum_post_columns_head($defaults) {
    global $post;
	if ($post && $post->post_type == 'cpc_forum_post') {
    }
    return $defaults;
}
 
// SHOW THE COLUMN CONTENT
function forum_post_columns_content($column_name, $post_ID) {

}

/* =========================== ALTER VIEW POST LINKS =========================== */

function cpc_change_forum_link( $permalink, $post ) {

	if ($post->post_type == 'cpc_forum_post'):

		$post_terms = get_the_terms( $post->ID, 'cpc_forum' );
		if( $post_terms && !is_wp_error( $post_terms ) ):
		    foreach( $post_terms as $term ):
		    	if ( cpc_using_permalinks() ):	
		        	$permalink = home_url( $term->slug.'/'.$post->post_name );	    	
	    	    	break;
	    	    else:
	    	    	$forum_page_id = cpc_get_term_meta($term->term_id, 'cpc_forum_cat_page', true);
					$permalink = home_url( "/?page_id=".$forum_page_id."&topic=".$post->post_name );
	    	    endif;
		    endforeach;
		endif;

	endif;

    return $permalink;

}
add_filter('post_type_link',"cpc_change_forum_link",10,2);
?>