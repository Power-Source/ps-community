<?php

/* Create forum_post custom post type */

/* =========================== LABELS FOR ADMIN =========================== */


function cpc_custom_post_forum_post() {
	$labels = array(
		'name'               => __( 'Beiträge', 'cp-community' ),
		'singular_name'      => __( 'Beitrag', 'cp-community' ),
		'add_new'            => __( 'Neuen hinzufügen', 'cp-community' ),
		'add_new_item'       => __( 'Neuen Beitrag hinzufügen', 'cp-community' ),
		'edit_item'          => __( 'Beitrag bearbeiten', 'cp-community' ),
		'new_item'           => __( 'Neuer Beitrag', 'cp-community' ),
		'all_items'          => __( 'Forumbeiträge', 'cp-community' ),
		'view_item'          => __( 'Forumbeitrag anzeigen', 'cp-community' ),
		'search_items'       => __( 'Forenbeiträge durchsuchen', 'cp-community' ),
		'not_found'          => __( 'Kein Forumsbeitrag gefunden', 'cp-community' ),
		'not_found_in_trash' => __( 'Kein Forumsbeitrag im Papierkorb gefunden', 'cp-community' ), 
		'parent_item_colon'  => '',
		'menu_name'          => __('Forumbeiträge', 'cp-community'),
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
		1 => __('Beitrag aktualisiert.', 'cp-community'),
		2 => __('Benutzerdefiniertes Feld aktualisiert.', 'cp-community'),
		3 => __('Benutzerdefiniertes Feld gelöscht.', 'cp-community'),
		4 => __('Beitrag aktualisiert.', 'cp-community'),
		5 => isset($_GET['revision']) ? sprintf( __('Post restored to revision from %s', 'cp-community'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Beitrag veröffentlicht.', 'cp-community'),
		7 => __('Beitrag gespeichert.', 'cp-community'),
		8 => __('Beitrag eingereicht.', 'cp-community'),
		9 => sprintf( __('Beitrag geplant für: <strong>%1$s</strong>.', 'cp-community'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __('Beitragsentwurf aktualisiert.', 'cp-community'),
	);
	return $messages;
}
add_filter( 'post_updated_messages', 'cpc_updated_forum_post_messages' );


/* =========================== META FIELDS CONTENT BOX WHEN EDITING =========================== */


add_action( 'add_meta_boxes', 'forum_post_info_box' );
function forum_post_info_box() {
    add_meta_box( 
        'forum_post_info_box',
        __( 'Beitragsdetails', 'cp-community' ),
        'forum_post_info_box_content',
        'cpc_forum_post',
        'side',
        'high'
    );
}

function forum_post_info_box_content( $post ) {
	global $wpdb;
	wp_nonce_field( 'forum_post_info_box_content', 'forum_post_info_box_content_nonce' );

	echo '<strong>'.__('Autor des Beitrags', 'cp-community').'</strong><br />';
	$author = get_user_by('id', $post->post_author);
	echo $author->display_name.'<br />';
	echo 'ID: '.$author->ID;

	echo '<br /><br >';
	echo '<input type="checkbox" name="cpc_sticky"';
		if (get_post_meta($post->ID, 'cpc_sticky', true)) echo ' CHECKED';
		echo '> '.__('Am Anfang der Beiträge anheften?', 'cp-community');
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