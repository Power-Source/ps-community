<?php

/* Create Friendships custom post type */


/* =========================== LABELS FOR ADMIN =========================== */


function cpc_custom_post_friendship() {
	$labels = array(
		'name'               => __( 'Freundschaften', CPC2_TEXT_DOMAIN ),
		'singular_name'      => __( 'Freundschaft', CPC2_TEXT_DOMAIN ),
		'add_new'            => __( 'Neue hinzufügen', CPC2_TEXT_DOMAIN ),
		'add_new_item'       => __( 'Neue Freundschaft hinzufügen', CPC2_TEXT_DOMAIN ),
		'edit_item'          => __( 'Freundschaft bearbeiten', CPC2_TEXT_DOMAIN ),
		'new_item'           => __( 'Neue Freundschaft', CPC2_TEXT_DOMAIN ),
		'all_items'          => __( 'Freundschaften', CPC2_TEXT_DOMAIN ),
		'view_item'          => __( 'Freundschaft ansehen', CPC2_TEXT_DOMAIN ),
		'search_items'       => __( 'Suche nach Freundschaften', CPC2_TEXT_DOMAIN ),
		'not_found'          => __( 'Keine Freundschaften gefunden', CPC2_TEXT_DOMAIN ),
		'not_found_in_trash' => __( 'Im Papierkorb wurden keine Freundschaften gefunden', CPC2_TEXT_DOMAIN ), 
		'parent_item_colon'  => '',
		'menu_name'          => __( 'Freundschaften', CPC2_TEXT_DOMAIN ),
	);
	$args = array(
		'labels'        		=> $labels,
		'description'   		=> __('Enthält die spezifischen Daten unserer Freundschaften', CPC2_TEXT_DOMAIN ),
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
		'supports'      		=> array( 'title' ),
		'has_archive'   		=> false,
	);
	register_post_type( 'cpc_friendship', $args );
}
add_action( 'init', 'cpc_custom_post_friendship' );

/* =========================== MESSAGES FOR ADMIN =========================== */

function cpc_updated_friendship_messages( $messages ) {
	global $post, $post_ID;
	$messages['cpc_friendship'] = array(
		0 => '', 
		1 => __('Freundschaft aktualisiert.'),
		2 => __('Benutzerdefiniertes Feld aktualisiert.'),
		3 => __('Benutzerdefiniertes Feld gelöscht.'),
		4 => __('Freundschaft aktualisiert.'),
		5 => isset($_GET['revision']) ? sprintf( __('Die Freundschaft wurde in der Revision von %s wiederhergestellt'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Freundschaften veröffentlicht.'),
		7 => __('Freundschaft gerettet.'),
		8 => __('Freundschaft eingereicht.'),
		9 => sprintf( __('Freundschaft geplant für: <strong>%1$s</strong>.'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __('Freundschaftsentwurf aktualisiert.'),
	);
	return $messages;
}
add_filter( 'post_updated_messages', 'cpc_updated_friendship_messages' );


/* =========================== META FIELDS CONTENT BOX WHEN EDITING =========================== */

add_action( 'add_meta_boxes', 'friendship_info_box' );
function friendship_info_box() {
    add_meta_box( 
        'friendship_info_box',
        __('Erstelle eine Freundschaft', CPC2_TEXT_DOMAIN ),
        'friendship_info_box_content',
        'cpc_friendship',
        'normal',
        'high'
    );
}

function friendship_info_box_content( $post ) {
	global $wpdb;
	wp_nonce_field( 'friendship_info_box_content', 'friendship_info_box_content_nonce' );

	echo '<div style="margin-top:10px;font-weight:bold">'.__('Benutzer 1', CPC2_TEXT_DOMAIN).'</div>';
	$member = get_user_by( 'id', get_post_meta( $post->ID, 'cpc_member1', true ) );
	$member_text = ($member) ? $member->user_login : '';
	echo '<input type="text" id="cpc_member1" style="width:300px" name="cpc_member1" placeholder="'.__('Ersten Benutzer auswählen...', CPC2_TEXT_DOMAIN).'" value="'.$member_text.'" />';

	echo '<div style="margin-top:10px;font-style:italic;">'.__('ist befreundet mit...', CPC2_TEXT_DOMAIN).'</div>';

	echo '<div style="margin-top:10px;font-weight:bold">'.__('Benutzer 2', CPC2_TEXT_DOMAIN).'</div>';
	$member = get_user_by( 'id', get_post_meta( $post->ID, 'cpc_member2', true ) );
	$member_text = ($member) ? $member->user_login : '';
	echo '<input type="text" id="cpc_member2" style="width:300px" name="cpc_member2" placeholder="'.__('Zweiten Benutzer auswählen...', CPC2_TEXT_DOMAIN).'" value="'.$member_text.'" />';

}

add_action( 'save_post', 'friendship_info_box_save' );
function friendship_info_box_save( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	return;

	if ( !isset($_POST['friendship_info_box_content_nonce']) || !wp_verify_nonce( $_POST['friendship_info_box_content_nonce'], 'friendship_info_box_content' ) )
	return;

	if ( !current_user_can( 'edit_post', $post_id ) ) return;

	$member1 = get_user_by( 'login', $_POST['cpc_member1'] );
	$member2 = get_user_by( 'login', $_POST['cpc_member2'] );

	if ($member1 && $member2) {

		global $wpdb;

		$status = cpc_are_friends($member1->ID, $member2->ID);
		if (!$status['status']) {

			update_post_meta( $post_id, 'cpc_member1', $member1->ID );
			update_post_meta( $post_id, 'cpc_member2', $member2->ID );
			update_post_meta( $post_id, 'cpc_friendship_since', date('Y-m-d H:i:s') );

			remove_action( 'save_post', 'friendship_info_box_save' );
			$my_post = array(
			      'ID'         	=> $post_id,
			      'post_title' 	=> $member1->user_login.' - '.$member2->user_login,
			      'post_name'	=> sanitize_title_with_dashes($member1->user_login.' '.$member2->user_login),
			      'post_type'	=> 'cpc_friendship',
			      'post_status'	=> 'publish'
			);
			wp_update_post( $my_post );			
			add_action( 'save_post', 'friendship_info_box_save' );

		} else {

			// Already exists, delete newly created friendship
			wp_delete_post( $post_id, true );
			die(__('Friendship already exists.', CPC2_TEXT_DOMAIN));

		}

	}

}

/* =========================== COLUMNS WHEN VIEWING =========================== */

/* Columns for Posts list */
add_filter('manage_posts_columns', 'friendship_columns_head');
add_action('manage_posts_custom_column', 'friendship_columns_content', 10, 2);

// ADD NEW COLUMN
function friendship_columns_head($defaults) {
    global $post;
	if ($post && $post->post_type == 'cpc_friendship') {
		$defaults['col_friendship_member1'] = 'User 1 display name';
    	$defaults['col_friendship_member2'] = 'User 2 display name';
    	$defaults['col_friendship_status'] = 'Status';
    	$defaults['cpc_friendship_since'] = 'Friends since';
    	unset($defaults['date']);
    }
    return $defaults;
}
 
// SHOW THE COLUMN CONTENT
function friendship_columns_content($column_name, $post_ID) {
    if ($column_name == 'col_friendship_member1') {
    	$post = get_post($post_ID); 
    	$user = get_user_by('id', $post->cpc_member1);
    	if ($user) {
    		echo $user->display_name.' ('.$post->cpc_member1.')';
    	} else {
    		echo __('Benutzer nicht gefunden', CPC2_TEXT_DOMAIN);
    	}
    }
    if ($column_name == 'col_friendship_member2') {
    	$post = get_post($post_ID); 
    	$user = get_user_by('id', $post->cpc_member2);
    	if ($user) {
    		echo $user->display_name.' ('.$post->cpc_member2.')';
    	} else {
    		echo __('Benutzer nicht gefunden', CPC2_TEXT_DOMAIN);
    	}
    }
    if ($column_name == 'col_friendship_status') {
    	$post = get_post($post_ID); 
    	if ($post->post_status == 'publish'):
    		echo __('Freunde', CPC2_TEXT_DOMAIN);
    	else:
    		echo __('Ausstehend', CPC2_TEXT_DOMAIN);
    	endif;
    }
    if ($column_name == 'cpc_friendship_since') {
    	$post = get_post($post_ID); 
    	echo date("F j, Y h:m:s a", strtotime($post->cpc_friendship_since));
    }
}

/* -------------------------------------------------------------------*/

/* Create Favourite Friendships custom post type */


/* =========================== LABELS FOR ADMIN =========================== */


function cpc_custom_post_favourite_friendship() {
	$labels = array(
		'name'               => __( 'Lieblingsfreundschaften', CPC2_TEXT_DOMAIN ),
		'singular_name'      => __( 'Lieblingsfreundschaft', CPC2_TEXT_DOMAIN ),
		'add_new'            => __( 'Neue hinzufügen', CPC2_TEXT_DOMAIN ),
		'add_new_item'       => __( 'Neuen Favoriten hinzufügen', CPC2_TEXT_DOMAIN ),
		'edit_item'          => __( 'Bearbeite Deine Lieblingsfreundschaft', CPC2_TEXT_DOMAIN ),
		'new_item'           => __( 'Neue Lieblingsfreundschaft', CPC2_TEXT_DOMAIN ),
		'all_items'          => __( 'Lieblingsfreundschaften', CPC2_TEXT_DOMAIN ),
		'view_item'          => __( 'Lieblingsfreundschaft anzeigen', CPC2_TEXT_DOMAIN ),
		'search_items'       => __( 'Suche nach Lieblingsfreundschaften', CPC2_TEXT_DOMAIN ),
		'not_found'          => __( 'Keine Lieblingsfreundschaften gefunden', CPC2_TEXT_DOMAIN ),
		'not_found_in_trash' => __( 'Im Papierkorb wurden keine Lieblingsfreundschaften gefunden', CPC2_TEXT_DOMAIN ), 
		'parent_item_colon'  => '',
		'menu_name'          => __( 'Lieblingsfreundschaften', CPC2_TEXT_DOMAIN ),
	);
	$args = array(
		'labels'        		=> $labels,
		'description'   		=> __('Enthält die spezifischen Daten unserer Lieblingsfreundschaften', CPC2_TEXT_DOMAIN ),
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
		'supports'      		=> array( 'title' ),
		'has_archive'   		=> false,
	);
	register_post_type( 'cpc_favourite_friend', $args );
}
add_action( 'init', 'cpc_custom_post_favourite_friendship' );

/* =========================== MESSAGES FOR ADMIN =========================== */

function cpc_updated_favourite_friendship_messages( $messages ) {
	global $post, $post_ID;
	$messages['cpc_favourite_friend'] = array(
		0 => '', 
		1 => __('Lieblingsfreundschaft aktualisiert.', CPC2_TEXT_DOMAIN),
		2 => __('Benutzerdefiniertes Feld aktualisiert.', CPC2_TEXT_DOMAIN),
		3 => __('Benutzerdefiniertes Feld gelöscht.', CPC2_TEXT_DOMAIN),
		4 => __('Lieblingsfreundschaft aktualisiert.', CPC2_TEXT_DOMAIN),
		5 => isset($_GET['revision']) ? sprintf( __('Lieblingsfreundschaft wurde in der Revision von %s wiederhergestellt', CPC2_TEXT_DOMAIN), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Lieblingsfreundschaften veröffentlicht.', CPC2_TEXT_DOMAIN),
		7 => __('Lieblingsfreundschaft gespeichert.', CPC2_TEXT_DOMAIN),
		8 => __('Freundschaft eingereicht.', CPC2_TEXT_DOMAIN),
		9 => sprintf( __('Lieblingsfreundschaft geplant für: <strong>%1$s</strong>.', CPC2_TEXT_DOMAIN), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __('Lieblings-Freundschaftsentwurf aktualisiert.', CPC2_TEXT_DOMAIN),
	);
	return $messages;
}
add_filter( 'post_updated_messages', 'cpc_updated_favourite_friendship_messages' );


/* =========================== META FIELDS CONTENT BOX WHEN EDITING =========================== */

add_action( 'add_meta_boxes', 'favourite_friendship_info_box' );
function favourite_friendship_info_box() {
    add_meta_box( 
        'favourite_friendship_info_box',
        __( 'Lieblingsfreund', CPC2_TEXT_DOMAIN ),
        'favourite_friendship_info_box_content',
        'cpc_favourite_friend',
        'normal',
        'high'
    );
}

function favourite_friendship_info_box_content( $post ) {
	global $wpdb;
	wp_nonce_field( 'favourite_friendship_info_box_content', 'favourite_friendship_info_box_content_nonce' );

	echo '<div style="margin-top:10px;font-weight:bold">'.__('Benutzer 1', CPC2_TEXT_DOMAIN).'</div>';
	$member = get_user_by( 'id', get_post_meta( $post->ID, 'cpc_favourite_member1', true ) );
	$member_text = ($member) ? $member->user_login : '';
	echo '<input type="text" id="cpc_favourite_member1" style="width:300px" name="cpc_favourite_member1" placeholder="'.__('Nutzer wählen...', CPC2_TEXT_DOMAIN).'" value="'.$member_text.'" />';

	echo '<div style="margin-top:10px;font-style:italic;">'.__('hat einen Lieblingsfreund...', CPC2_TEXT_DOMAIN).'</div>';

	echo '<div style="margin-top:10px;font-weight:bold">'.__('Benutzer 2', CPC2_TEXT_DOMAIN).'</div>';
	$member = get_user_by( 'id', get_post_meta( $post->ID, 'cpc_favourite_member2', true ) );
	$member_text = ($member) ? $member->user_login : '';
	echo '<input type="text" id="cpc_favourite_member2" style="width:300px" name="cpc_favourite_member2" placeholder="'.__('Lieblingsbenutzer auswählen...', CPC2_TEXT_DOMAIN).'" value="'.$member_text.'" />';

}

add_action( 'save_post', 'favourite_friendship_info_box_save' );
function favourite_friendship_info_box_save( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	return;

	if ( !isset($_POST['favourite_friendship_info_box_content_nonce']) || !wp_verify_nonce( $_POST['favourite_friendship_info_box_content_nonce'], 'favourite_friendship_info_box_content' ) )
	return;

	if ( !current_user_can( 'edit_post', $post_id ) ) return;

	$member1 = get_user_by( 'login', $_POST['cpc_favourite_member1'] );
	$member2 = get_user_by( 'login', $_POST['cpc_favourite_member2'] );

	if ($member1 && $member2) {

		global $wpdb;

		$status = cpc_is_a_favourite_friend($member1->ID, $member2->ID);
		if (!$status['status']) {

			update_post_meta( $post_id, 'cpc_favourite_member1', $member1->ID );
			update_post_meta( $post_id, 'cpc_favourite_member2', $member2->ID );
			update_post_meta( $post_id, 'cpc_favourite_friendship_since', date('Y-m-d H:i:s') );

			remove_action( 'save_post', 'favourite_friendship_info_box_save' );
			$my_post = array(
			      'ID'         	=> $post_id,
			      'post_title' 	=> $member1->user_login.' - '.$member2->user_login,
			      'post_name'	=> sanitize_title_with_dashes($member1->user_login.' '.$member2->user_login),
			      'post_type'	=> 'cpc_favourite_friend',
			      'post_status'	=> 'publish'
			);
			wp_update_post( $my_post );			
			add_action( 'save_post', 'favourite_friendship_info_box_save' );

		} else {

			// Already exists, delete newly created friendship
			wp_delete_post( $post_id, true );
			die(__('Lieblingsfreundschaft existiert bereits.', CPC2_TEXT_DOMAIN));

		}

	}

}

/* =========================== COLUMNS WHEN VIEWING =========================== */

/* Columns for Posts list */
add_filter('manage_posts_columns', 'favourite_friendship_columns_head');
add_action('manage_posts_custom_column', 'favourite_friendship_columns_content', 10, 2);

// ADD NEW COLUMN
function favourite_friendship_columns_head($defaults) {
    global $post;
	if ($post && $post->post_type == 'cpc_favourite_friend') {
		$defaults['col_favourite_friendship_member1'] = __('Benutzeranzeigename', CPC2_TEXT_DOMAIN);
    	$defaults['col_favourite_friendship_member2'] = __('Bevorzugter Anzeigename', CPC2_TEXT_DOMAIN);
    	$defaults['col_favourite_friendship_status'] = __('Status', CPC2_TEXT_DOMAIN);
    	$defaults['cpc_favourite_friendship_since'] = __('Favorit seit', CPC2_TEXT_DOMAIN);
    	unset($defaults['date']);
    }
    return $defaults;
}
 
// SHOW THE COLUMN CONTENT
function favourite_friendship_columns_content($column_name, $post_ID) {
    if ($column_name == 'col_favourite_friendship_member1') {
    	$post = get_post($post_ID); 
    	$user = get_user_by('id', $post->cpc_favourite_member1);
    	if ($user) {
    		echo $user->display_name.' ('.$post->cpc_favourite_member1.')';
    	} else {
    		echo __('Benutzer nicht gefunden', CPC2_TEXT_DOMAIN);
    	}
    }
    if ($column_name == 'col_favourite_friendship_member2') {
    	$post = get_post($post_ID); 
    	$user = get_user_by('id', $post->cpc_favourite_member2);
    	if ($user) {
    		echo $user->display_name.' ('.$post->cpc_favourite_member2.')';
    	} else {
    		echo __('Benutzer nicht gefunden', CPC2_TEXT_DOMAIN);
    	}
    }
    if ($column_name == 'col_favourite_friendship_status') {
    	$post = get_post($post_ID); 
    	if ($post->post_status == 'publish'):
    		echo __('Favourite', CPC2_TEXT_DOMAIN);
    	else:
    		echo __('Ausstehend', CPC2_TEXT_DOMAIN);
    	endif;
    }
    if ($column_name == 'cpc_favourite_friendship_since') {
    	$post = get_post($post_ID); 
    	echo date("F j, Y h:m:s a", strtotime($post->cpc_favourite_friendship_since));
    }
}




?>