<?php

/* Create cpc_group custom post type */

/* =========================== LABELS FOR ADMIN =========================== */

/* Redirect single group posts to the group page shortcode instead of showing CPT template */
add_action( 'template_redirect', 'cpc_group_template_redirect' );
function cpc_group_template_redirect() {
	if ( is_singular( 'cpc_group' ) ) {
		$group = get_queried_object();
		$group_page_id = get_option('cpccom_group_single_page');
		
		if ( $group_page_id ) {
			// Redirect to group page with group_name parameter
			$redirect_url = add_query_arg( 'group_name', $group->post_name, get_permalink( $group_page_id ) );
			wp_redirect( $redirect_url, 301 );
			exit;
		}
	}
}
function cpc_custom_post_group() {
	$labels = array(
		'name'               => __( 'Gruppen', 'cp-community' ),
		'singular_name'      => __( 'Gruppe', 'cp-community' ),
		'add_new'            => __( 'Neue hinzufügen', 'cp-community' ),
		'add_new_item'       => __( 'Neue Gruppe hinzufügen', 'cp-community' ),
		'edit_item'          => __( 'Gruppe bearbeiten', 'cp-community' ),
		'new_item'           => __( 'Neue Gruppe', 'cp-community' ),
		'all_items'          => __( 'Alle Gruppen', 'cp-community' ),
		'view_item'          => __( 'Gruppe anzeigen', 'cp-community' ),
		'search_items'       => __( 'Gruppen durchsuchen', 'cp-community' ),
		'not_found'          => __( 'Keine Gruppe gefunden', 'cp-community' ),
		'not_found_in_trash' => __( 'Keine Gruppe im Papierkorb gefunden', 'cp-community' ), 
		'parent_item_colon'  => __( 'Übergeordnete Gruppe:', 'cp-community' ),
		'menu_name'          => __( 'Gruppen', 'cp-community' ),
	);
	$args = array(
		'labels'        		=> $labels,
		'description'   		=> 'Holds our groups specific data',
		'public'        		=> true,
        'capabilities' => array(
            'publish_posts' => 'read',
            'edit_posts' => 'read',
            'edit_others_posts' => 'manage_options',
            'delete_posts' => 'read',
            'delete_others_posts' => 'manage_options',
            'read_private_posts' => 'read',
            'edit_post' => 'read',
            'delete_post' => 'read',
            'read_post' => 'read',
        ),              
		'exclude_from_search' 	=> false,
		'show_in_menu' 			=> get_option('cpc_core_admin_icons') ? 'cpc_com' : true,
		'publicly_queryable'	=> true,
		'has_archive'			=> false,
		'hierarchical'			=> true,
		'rewrite'				=> array( 'slug' => 'groups', 'with_front' => false ),
		'supports'      		=> array( 'title', 'editor', 'thumbnail', 'comments', 'page-attributes' ),
	);
	register_post_type( 'cpc_group', $args );
}
add_action( 'init', 'cpc_custom_post_group' );

/* =========================== MESSAGES FOR ADMIN =========================== */

function cpc_updated_group_messages( $messages ) {
	global $post, $post_ID;
	$messages['cpc_group'] = array(
		0 => '', 
		1 => __('Gruppe aktualisiert.', 'cp-community'),
		2 => __('Benutzerdefiniertes Feld aktualisiert.', 'cp-community'),
		3 => __('Benutzerdefiniertes Feld gelöscht.', 'cp-community'),
		4 => __('Gruppe aktualisiert.', 'cp-community'),
		5 => isset($_GET['revision']) ? sprintf( __('Gruppe wiederhergestellt von Revision vom %s', 'cp-community'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Gruppe veröffentlicht.', 'cp-community'),
		7 => __('Gruppe gespeichert.', 'cp-community'),
		8 => __('Gruppe eingereicht.', 'cp-community'),
		9 => sprintf( __('Gruppe geplant für: <strong>%1$s</strong>.', 'cp-community'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __('Gruppenentwurf aktualisiert.', 'cp-community'),
	);
	return $messages;
}
add_filter( 'post_updated_messages', 'cpc_updated_group_messages' );


/* =========================== META FIELDS CONTENT BOX WHEN EDITING =========================== */

add_action( 'add_meta_boxes', 'group_info_box' );
function group_info_box() {
    add_meta_box( 
        'group_info_box',
        __( 'Gruppendetails', 'cp-community' ),
        'group_info_box_content',
        'cpc_group',
        'side',
        'high'
    );
}

function group_info_box_content( $post ) {
	global $wpdb;
	wp_nonce_field( 'group_info_box_content', 'group_info_box_content_nonce' );

	echo '<strong>'.__('Gruppenersteller', 'cp-community').'</strong><br />';
	$creator_id = get_post_meta($post->ID, 'cpc_group_creator', true);
	if (!$creator_id) $creator_id = $post->post_author;
	$creator = get_user_by('id', $creator_id);
	if ($creator) {
		echo $creator->display_name.'<br />';
		echo 'ID: '.$creator->ID;
	}

	echo '<br /><br />';
	echo '<strong>'.__('Gruppentyp', 'cp-community').'</strong><br />';
	$group_type = get_post_meta($post->ID, 'cpc_group_type', true);
	if (!$group_type) $group_type = 'public';
	echo '<select name="cpc_group_type" style="width:100%">';
		echo '<option value="public"'.selected($group_type, 'public', false).'>'.__('Öffentlich', 'cp-community').'</option>';
		echo '<option value="private"'.selected($group_type, 'private', false).'>'.__('Privat', 'cp-community').'</option>';
		echo '<option value="hidden"'.selected($group_type, 'hidden', false).'>'.__('Versteckt', 'cp-community').'</option>';
	echo '</select>';
	echo '<p class="description">'.__('Öffentlich: Jeder kann sehen und beitreten<br>Privat: Jeder kann sehen, Beitritt auf Anfrage<br>Versteckt: Nur Mitglieder können sehen', 'cp-community').'</p>';

	echo '<br />';
	echo '<strong>'.__('Mitgliederzahl', 'cp-community').'</strong><br />';
	$member_count = cpc_get_group_member_count($post->ID);
	echo $member_count;

	echo '<br /><br />';
	echo '<strong>'.__('Letzte Aktivität', 'cp-community').'</strong><br />';
	$last_active = get_post_meta($post->ID, 'cpc_group_updated', true);
	if ($last_active && $last_active != 1) {
		echo date_i18n(get_option('date_format').' '.get_option('time_format'), $last_active);
	} else {
		echo __('Noch keine Aktivität', 'cp-community');
	}
}

add_action( 'save_post', 'group_info_box_save' );
function group_info_box_save( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	return;

	if ( !isset($_POST['group_info_box_content_nonce']) || !wp_verify_nonce( $_POST['group_info_box_content_nonce'], 'group_info_box_content' ) )
	return;

	if ( !current_user_can( 'edit_post', $post_id ) ) return;

	// Save group type
	if (isset($_POST['cpc_group_type'])):
		update_post_meta($post_id, 'cpc_group_type', sanitize_text_field($_POST['cpc_group_type']));
	endif;

	// Save creator if not already set
	$creator = get_post_meta($post_id, 'cpc_group_creator', true);
	if (!$creator):
		update_post_meta($post_id, 'cpc_group_creator', get_current_user_id());
	endif;

	// Update last activity
	update_post_meta($post_id, 'cpc_group_updated', current_time('timestamp'));
}

/* =========================== COLUMNS WHEN VIEWING =========================== */

add_filter('manage_cpc_group_posts_columns', 'group_columns_head');
add_action('manage_cpc_group_posts_custom_column', 'group_columns_content', 10, 2);

// ADD NEW COLUMN
function group_columns_head($defaults) {
    $defaults['group_type'] = __('Typ', 'cp-community');
    $defaults['group_members'] = __('Mitglieder', 'cp-community');
    $defaults['group_creator'] = __('Ersteller', 'cp-community');
    return $defaults;
}
 
// SHOW THE COLUMN CONTENT
function group_columns_content($column_name, $post_ID) {
	if ($column_name == 'group_type') {
		$type = get_post_meta($post_ID, 'cpc_group_type', true);
		if (!$type) $type = 'public';
		switch($type) {
			case 'public':
				echo '<span style="color:green">●</span> '.__('Öffentlich', 'cp-community');
				break;
			case 'private':
				echo '<span style="color:orange">●</span> '.__('Privat', 'cp-community');
				break;
			case 'hidden':
				echo '<span style="color:red">●</span> '.__('Versteckt', 'cp-community');
				break;
		}
	}
	if ($column_name == 'group_members') {
		echo cpc_get_group_member_count($post_ID);
	}
	if ($column_name == 'group_creator') {
		$creator_id = get_post_meta($post_ID, 'cpc_group_creator', true);
		if ($creator_id) {
			$creator = get_user_by('id', $creator_id);
			if ($creator) echo $creator->display_name;
		}
	}
}

/* =========================== ALTER VIEW POST LINKS =========================== */

function cpc_change_group_link( $permalink, $post ) {
	if ($post->post_type == 'cpc_group'):
		if ( cpc_using_permalinks() ):	
			$permalink = home_url( 'groups/'.$post->post_name );	    	
		else:
			$permalink = home_url( "/?post_type=cpc_group&p=".$post->ID );
		endif;
	endif;
    return $permalink;
}
add_filter('post_type_link',"cpc_change_group_link",10,2);
?>
