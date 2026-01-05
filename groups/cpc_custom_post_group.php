<?php

/* Create cpc_group custom post type */

/* =========================== LABELS FOR ADMIN =========================== */

function cpc_custom_post_group() {
	$labels = array(
		'name'               => __( 'Gruppen', CPC2_TEXT_DOMAIN ),
		'singular_name'      => __( 'Gruppe', CPC2_TEXT_DOMAIN ),
		'add_new'            => __( 'Neue hinzufügen', CPC2_TEXT_DOMAIN ),
		'add_new_item'       => __( 'Neue Gruppe hinzufügen', CPC2_TEXT_DOMAIN ),
		'edit_item'          => __( 'Gruppe bearbeiten', CPC2_TEXT_DOMAIN ),
		'new_item'           => __( 'Neue Gruppe', CPC2_TEXT_DOMAIN ),
		'all_items'          => __( 'Alle Gruppen', CPC2_TEXT_DOMAIN ),
		'view_item'          => __( 'Gruppe anzeigen', CPC2_TEXT_DOMAIN ),
		'search_items'       => __( 'Gruppen durchsuchen', CPC2_TEXT_DOMAIN ),
		'not_found'          => __( 'Keine Gruppe gefunden', CPC2_TEXT_DOMAIN ),
		'not_found_in_trash' => __( 'Keine Gruppe im Papierkorb gefunden', CPC2_TEXT_DOMAIN ), 
		'parent_item_colon'  => __( 'Übergeordnete Gruppe:', CPC2_TEXT_DOMAIN ),
		'menu_name'          => __( 'Gruppen', CPC2_TEXT_DOMAIN ),
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
		'has_archive'			=> true,
		'hierarchical'			=> true,
		'rewrite'				=> array( 'slug' => 'groups' ),
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
		1 => __('Gruppe aktualisiert.', CPC2_TEXT_DOMAIN),
		2 => __('Benutzerdefiniertes Feld aktualisiert.', CPC2_TEXT_DOMAIN),
		3 => __('Benutzerdefiniertes Feld gelöscht.', CPC2_TEXT_DOMAIN),
		4 => __('Gruppe aktualisiert.', CPC2_TEXT_DOMAIN),
		5 => isset($_GET['revision']) ? sprintf( __('Gruppe wiederhergestellt von Revision vom %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Gruppe veröffentlicht.', CPC2_TEXT_DOMAIN),
		7 => __('Gruppe gespeichert.', CPC2_TEXT_DOMAIN),
		8 => __('Gruppe eingereicht.', CPC2_TEXT_DOMAIN),
		9 => sprintf( __('Gruppe geplant für: <strong>%1$s</strong>.'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __('Gruppenentwurf aktualisiert.', CPC2_TEXT_DOMAIN),
	);
	return $messages;
}
add_filter( 'post_updated_messages', 'cpc_updated_group_messages' );


/* =========================== META FIELDS CONTENT BOX WHEN EDITING =========================== */

add_action( 'add_meta_boxes', 'group_info_box' );
function group_info_box() {
    add_meta_box( 
        'group_info_box',
        __( 'Gruppendetails', CPC2_TEXT_DOMAIN ),
        'group_info_box_content',
        'cpc_group',
        'side',
        'high'
    );
}

function group_info_box_content( $post ) {
	global $wpdb;
	wp_nonce_field( 'group_info_box_content', 'group_info_box_content_nonce' );

	echo '<strong>'.__('Gruppenersteller', CPC2_TEXT_DOMAIN).'</strong><br />';
	$creator_id = get_post_meta($post->ID, 'cpc_group_creator', true);
	if (!$creator_id) $creator_id = $post->post_author;
	$creator = get_user_by('id', $creator_id);
	if ($creator) {
		echo $creator->display_name.'<br />';
		echo 'ID: '.$creator->ID;
	}

	echo '<br /><br />';
	echo '<strong>'.__('Gruppentyp', CPC2_TEXT_DOMAIN).'</strong><br />';
	$group_type = get_post_meta($post->ID, 'cpc_group_type', true);
	if (!$group_type) $group_type = 'public';
	echo '<select name="cpc_group_type" style="width:100%">';
		echo '<option value="public"'.selected($group_type, 'public', false).'>'.__('Öffentlich', CPC2_TEXT_DOMAIN).'</option>';
		echo '<option value="private"'.selected($group_type, 'private', false).'>'.__('Privat', CPC2_TEXT_DOMAIN).'</option>';
		echo '<option value="hidden"'.selected($group_type, 'hidden', false).'>'.__('Versteckt', CPC2_TEXT_DOMAIN).'</option>';
	echo '</select>';
	echo '<p class="description">'.__('Öffentlich: Jeder kann sehen und beitreten<br>Privat: Jeder kann sehen, Beitritt auf Anfrage<br>Versteckt: Nur Mitglieder können sehen', CPC2_TEXT_DOMAIN).'</p>';

	echo '<br />';
	echo '<strong>'.__('Mitgliederzahl', CPC2_TEXT_DOMAIN).'</strong><br />';
	$member_count = cpc_get_group_member_count($post->ID);
	echo $member_count;

	echo '<br /><br />';
	echo '<strong>'.__('Letzte Aktivität', CPC2_TEXT_DOMAIN).'</strong><br />';
	$last_active = get_post_meta($post->ID, 'cpc_group_updated', true);
	if ($last_active && $last_active != 1) {
		echo date_i18n(get_option('date_format').' '.get_option('time_format'), $last_active);
	} else {
		echo __('Noch keine Aktivität', CPC2_TEXT_DOMAIN);
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
    $defaults['group_type'] = __('Typ', CPC2_TEXT_DOMAIN);
    $defaults['group_members'] = __('Mitglieder', CPC2_TEXT_DOMAIN);
    $defaults['group_creator'] = __('Ersteller', CPC2_TEXT_DOMAIN);
    return $defaults;
}
 
// SHOW THE COLUMN CONTENT
function group_columns_content($column_name, $post_ID) {
	if ($column_name == 'group_type') {
		$type = get_post_meta($post_ID, 'cpc_group_type', true);
		if (!$type) $type = 'public';
		switch($type) {
			case 'public':
				echo '<span style="color:green">●</span> '.__('Öffentlich', CPC2_TEXT_DOMAIN);
				break;
			case 'private':
				echo '<span style="color:orange">●</span> '.__('Privat', CPC2_TEXT_DOMAIN);
				break;
			case 'hidden':
				echo '<span style="color:red">●</span> '.__('Versteckt', CPC2_TEXT_DOMAIN);
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
