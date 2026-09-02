<?php

/* Create Activity custom post type */


/* =========================== LABELS FOR ADMIN =========================== */


function cpc_custom_post_activity() {
	$labels = array(
		'name'               => __( 'Aktivitäten', 'cp-community' ),
		'singular_name'      => __( 'Aktivität',  'cp-community' ),
		'add_new'            => __( 'Neue hinzufügen',  'cp-community' ),
		'add_new_item'       => __( 'Neue Aktivität hinzufügen', 'cp-community' ),
		'edit_item'          => __( 'Aktivität bearbeiten', 'cp-community' ),
		'new_item'           => __( 'Neue Aktivität', 'cp-community' ),
		'all_items'          => __( 'Aktivitäten', 'cp-community' ),
		'view_item'          => __( 'Aktivität anzeigen', 'cp-community' ),
		'search_items'       => __( 'Suche Aktivitäten', 'cp-community' ),
		'not_found'          => __( 'Keine Aktivität gefunden', 'cp-community' ),
		'not_found_in_trash' => __( 'Im Papierkorb wurden keine Aktivitäten gefunden', 'cp-community' ), 
		'parent_item_colon'  => '',
		'menu_name'          => __('Aktivität', 'cp-community'),
	);
	$args = array(
		'labels'        		=> $labels,
		'description'   		=> 'Holds our activity specific data',
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
		'rewrite'				=> false,
		'show_in_menu' 			=> get_option('cpc_core_admin_icons') ? 'cpc_com' : '',
		'supports'      		=> array( 'title', 'thumbnail' ),
		'has_archive'   		=> false,
	);
	register_post_type( 'cpc_activity', $args );
}
add_action( 'init', 'cpc_custom_post_activity' );

/* =========================== MESSAGES FOR ADMIN =========================== */

function cpc_updated_activity_messages( $messages ) {
	global $post, $post_ID;
	$messages['cpc_activity'] = array(
		0 => '', 
		1 => __('Activity updated.', 'cp-community'),
		2 => __('Custom field updated.', 'cp-community'),
		3 => __('Custom field deleted.', 'cp-community'),
		4 => __('Activity updated.', 'cp-community'),
		5 => isset($_GET['revision']) ? sprintf( __('Die Aktivität wurde in der Revision von %s wiederhergestellt', 'cp-community'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Activity published.', 'cp-community'),
		7 => __('Activity saved.', 'cp-community'),
		8 => __('Activity submitted.', 'cp-community'),
		9 => sprintf( __('Aktivität geplant für: <strong>%1$s</strong>.', 'cp-community'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __('Aktivitätsentwurf aktualisiert.', 'cp-community'),
	);
	return $messages;
}
add_filter( 'post_updated_messages', 'cpc_updated_activity_messages' );


/* =========================== META FIELDS CONTENT BOX WHEN EDITING =========================== */


add_action( 'add_meta_boxes', 'activity_info_box' );
function activity_info_box() {
    add_meta_box( 
        'activity_info_box',
        __( 'Aktivitätsdetails', 'cp-community' ),
        'activity_info_box_content',
        'cpc_activity',
        'normal',
        'high'
    );
}

function activity_info_box_content( $post ) {
	global $wpdb;
	wp_nonce_field( 'activity_info_box_content', 'activity_info_box_content_nonce' );

	echo '<div style="margin-top:10px;font-weight:bold">'.__('Autor', 'cp-community').'</div>';
	$author = get_user_by( 'id', $post->post_author );
	echo '<input type="text" id="cpc_author" name="cpc_author" placeholder="Select author..." value="'.$author->user_login.'" />';

	echo '<div style="margin-top:10px;font-weight:bold">'.__('Ziel(e)', 'cp-community').'</div>';
	$target_ids = get_post_meta( $post->ID, 'cpc_target', true );
	$targets = array();
	if (is_array($target_ids)):
		foreach ($target_ids as $target):
			array_push($targets, $target);
			$member = get_user_by( 'id', $target );
			echo ($member) ? $member->user_login.'<br />' : '';
		endforeach;
	else:
		if (!get_post_meta( $post->ID, 'cpc_target_type', true )): // Standard activity
			$member = get_user_by( 'id', $target_ids );
			$member_text = ($member) ? $member->user_login : '';
			echo '<input type="text" id="cpc_target" name="cpc_target" style="width:300px" placeholder="Select target user..." value="'.$member_text.'" />';
		else:
			echo $target_ids.' ('.get_post_meta( $post->ID, 'cpc_target_type', true ).')';
		endif;
	endif;
    
	echo '<div style="margin-top:10px;font-weight:bold">'.__('Einblenden', 'cp-community').'</div>';
    echo '<a id="cpc_activity_unhide_all" rel="'.$post->ID.'" href="javascript:void(0);">'.__('Entferne alle versteckten Flags', 'cp-community').'</a>';

}

add_action( 'save_post', 'activity_info_box_save' );
function activity_info_box_save( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	return;

	if ( !isset($_POST['activity_info_box_content_nonce']) || !wp_verify_nonce( $_POST['activity_info_box_content_nonce'], 'activity_info_box_content' ) )
	return;

	if ( !current_user_can( 'edit_post', $post_id ) ) return;

	$target = get_user_by( 'login', $_POST['cpc_target'] );
	if ($target) {
		update_post_meta( $post_id, 'cpc_target', $target->ID );
	}

	$author = get_user_by( 'login', $_POST['cpc_author'] );
	remove_action( 'save_post', 'activity_info_box_save' );
	$my_post = array(
	      'ID'         	=> $post_id,
	      'post_author' => $author->ID,
	);
	wp_update_post( $my_post );			
	add_action( 'save_post', 'activity_info_box_save' );	

}

/* =========================== COLUMNS WHEN VIEWING =========================== */

/* Columns for Posts list */
add_filter('manage_posts_columns', 'activity_columns_head');
add_action('manage_posts_custom_column', 'activity_columns_content', 10, 2);

// ADD NEW COLUMN
function activity_columns_head($defaults) {
    global $post;
	if ($post && $post->post_type == 'cpc_activity') {
		$defaults['activity_id'] = 'ID';
		$defaults['activity_post'] = 'Post';
		$defaults['col_author'] = 'Author';
		$defaults['col_target'] = 'Target';
		$defaults['col_image'] = 'Image';
    	unset($defaults['title']);
    }
    return $defaults;
}
 
// SHOW THE COLUMN CONTENT
function activity_columns_content($column_name, $post_ID) {
    if ($column_name == 'activity_id') {
    	echo $post_ID;
    }
    if ($column_name == 'activity_post') {
    	$post = get_post($post_ID);
    	echo '<a style="font-weight:bold" href="post.php?post='.$post_ID.'&action=edit">'.wp_trim_words($post->post_title, 30).'</a>';
    }
    if ($column_name == 'col_author') {
    	$post = get_post($post_ID);
    	$user = get_user_by ('id', $post->post_author );
        if ($user):
            echo $user->user_login.' ';
            echo '('.$user->display_name.') &rarr;';
        else:
            echo sprintf(__('Benutzer %d nicht gefunden', 'cp-community'), $post->post_author);
        endif;
    }
    if ($column_name == 'col_target') {
    	$target_ids = get_post_meta( $post_ID, 'cpc_target', true );
		if (!get_post_meta( $post_ID, 'cpc_target_type', true )): // Standard activity
			if (is_array($target_ids)):
				foreach ($target_ids as $target):
					$member = get_user_by( 'id', $target );
					echo ($member) ? $member->user_login.' ('.$member->display_name.')<br />' : '';
				endforeach;
			else:
		    	$user = get_user_by ('id', $target_ids );
                if ($user):
                    echo $user->user_login.' ';
                    echo '('.$user->display_name.')';			
                else:
                    echo sprintf(__('Benutzer %d nicht gefunden', 'cp-community'), $target_ids);
                endif;
			endif;
		else:
			echo $target_ids.' ('.get_post_meta( $post_ID, 'cpc_target_type', true ).')';
		endif;
    }
    if ($column_name == 'col_image') {
		$image = @get_the_post_thumbnail($post_ID, array (30,30));
		if (is_string($image)) echo $image;
    }
}

/* =========================== ALTER VIEW POST LINKS =========================== */

function cpc_change_activity_link( $permalink, $post ) {

	if ($post->post_type == 'cpc_activity'):

		if ( cpc_using_permalinks() ):	
			$u = get_user_by('id', $post->post_author);
            if ($u):
                $parameters = sprintf('%s?view=%d', $u->user_login, $post->ID);
                $permalink = get_permalink(get_option('cpccom_profile_page'));
                $permalink = $permalink.$parameters;
            endif;
		else:
			$parameters = sprintf('user_id=%d&view=%d', $post->post_author, $post->ID);
			$permalink = get_permalink(get_option('cpccom_profile_page'));
			$permalink = $permalink.'&'.$parameters;
		endif;

	endif;

    return $permalink;

}
add_filter('post_type_link',"cpc_change_activity_link",10,2);

?>