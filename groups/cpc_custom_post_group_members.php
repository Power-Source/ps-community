<?php

/* Create cpc_group_members custom post type for managing group memberships */

/* =========================== LABELS FOR ADMIN =========================== */

function cpc_custom_post_group_members() {
	$labels = array(
		'name'               => __( 'Gruppenmitglieder', CPC2_TEXT_DOMAIN ),
		'singular_name'      => __( 'Gruppenmitglied', CPC2_TEXT_DOMAIN ),
		'add_new'            => __( 'Neues hinzufügen', CPC2_TEXT_DOMAIN ),
		'add_new_item'       => __( 'Neues Mitglied hinzufügen', CPC2_TEXT_DOMAIN ),
		'edit_item'          => __( 'Mitglied bearbeiten', CPC2_TEXT_DOMAIN ),
		'new_item'           => __( 'Neues Mitglied', CPC2_TEXT_DOMAIN ),
		'all_items'          => __( 'Alle Mitglieder', CPC2_TEXT_DOMAIN ),
		'view_item'          => __( 'Mitglied anzeigen', CPC2_TEXT_DOMAIN ),
		'search_items'       => __( 'Mitglieder durchsuchen', CPC2_TEXT_DOMAIN ),
		'not_found'          => __( 'Kein Mitglied gefunden', CPC2_TEXT_DOMAIN ),
		'not_found_in_trash' => __( 'Kein Mitglied im Papierkorb gefunden', CPC2_TEXT_DOMAIN ), 
		'parent_item_colon'  => '',
		'menu_name'          => __( 'Gruppenmitglieder', CPC2_TEXT_DOMAIN ),
	);
	$args = array(
		'labels'        		=> $labels,
		'description'   		=> 'Holds group membership data',
		'public'        		=> false,
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
		'show_in_menu' 			=> get_option('cpc_core_admin_icons') ? 'cpc_com' : false,
		'publicly_queryable'	=> false,
		'has_archive'			=> false,
		'rewrite'				=> false,
		'supports'      		=> array( 'title' ),
	);
	register_post_type( 'cpc_group_members', $args );
}
add_action( 'init', 'cpc_custom_post_group_members' );

/* =========================== MESSAGES FOR ADMIN =========================== */

function cpc_updated_group_member_messages( $messages ) {
	global $post, $post_ID;
	$messages['cpc_group_members'] = array(
		0 => '', 
		1 => __('Mitgliedschaft aktualisiert.', CPC2_TEXT_DOMAIN),
		2 => __('Benutzerdefiniertes Feld aktualisiert.', CPC2_TEXT_DOMAIN),
		3 => __('Benutzerdefiniertes Feld gelöscht.', CPC2_TEXT_DOMAIN),
		4 => __('Mitgliedschaft aktualisiert.', CPC2_TEXT_DOMAIN),
		5 => isset($_GET['revision']) ? sprintf( __('Mitgliedschaft wiederhergestellt von Revision vom %s'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Mitgliedschaft veröffentlicht.', CPC2_TEXT_DOMAIN),
		7 => __('Mitgliedschaft gespeichert.', CPC2_TEXT_DOMAIN),
		8 => __('Mitgliedschaft eingereicht.', CPC2_TEXT_DOMAIN),
		9 => sprintf( __('Mitgliedschaft geplant für: <strong>%1$s</strong>.'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __('Mitgliedschaftsentwurf aktualisiert.', CPC2_TEXT_DOMAIN),
	);
	return $messages;
}
add_filter( 'post_updated_messages', 'cpc_updated_group_member_messages' );

/* =========================== META FIELDS CONTENT BOX WHEN EDITING =========================== */

add_action( 'add_meta_boxes', 'group_member_info_box' );
function group_member_info_box() {
    add_meta_box( 
        'group_member_info_box',
        __( 'Mitgliedschaftsdetails', CPC2_TEXT_DOMAIN ),
        'group_member_info_box_content',
        'cpc_group_members',
        'normal',
        'high'
    );
}

function group_member_info_box_content( $post ) {
	global $wpdb;
	wp_nonce_field( 'group_member_info_box_content', 'group_member_info_box_content_nonce' );

	$user_id = get_post_meta($post->ID, 'cpc_member_user_id', true);
	$group_id = get_post_meta($post->ID, 'cpc_member_group_id', true);
	$role = get_post_meta($post->ID, 'cpc_member_role', true);
	$status = get_post_meta($post->ID, 'cpc_member_status', true);
	$joined = get_post_meta($post->ID, 'cpc_member_joined', true);

	if (!$role) $role = 'member';
	if (!$status) $status = 'active';

	echo '<table class="form-table">';
	
	echo '<tr>';
	echo '<th><label for="cpc_member_user_id">'.__('Benutzer', CPC2_TEXT_DOMAIN).'</label></th>';
	echo '<td>';
	wp_dropdown_users(array(
		'name' => 'cpc_member_user_id',
		'selected' => $user_id,
		'show_option_none' => __('Benutzer wählen...', CPC2_TEXT_DOMAIN)
	));
	if ($user_id) {
		$user = get_user_by('id', $user_id);
		if ($user) echo '<br><small>Aktuell: '.$user->display_name.'</small>';
	}
	echo '</td>';
	echo '</tr>';

	echo '<tr>';
	echo '<th><label for="cpc_member_group_id">'.__('Gruppe', CPC2_TEXT_DOMAIN).'</label></th>';
	echo '<td>';
	wp_dropdown_pages(array(
		'post_type' => 'cpc_group',
		'name' => 'cpc_member_group_id',
		'selected' => $group_id,
		'show_option_none' => __('Gruppe wählen...', CPC2_TEXT_DOMAIN)
	));
	echo '</td>';
	echo '</tr>';

	echo '<tr>';
	echo '<th><label for="cpc_member_role">'.__('Rolle', CPC2_TEXT_DOMAIN).'</label></th>';
	echo '<td>';
	echo '<select name="cpc_member_role" id="cpc_member_role" style="width:200px">';
		echo '<option value="member"'.selected($role, 'member', false).'>'.__('Mitglied', CPC2_TEXT_DOMAIN).'</option>';
		echo '<option value="moderator"'.selected($role, 'moderator', false).'>'.__('Moderator', CPC2_TEXT_DOMAIN).'</option>';
		echo '<option value="admin"'.selected($role, 'admin', false).'>'.__('Admin', CPC2_TEXT_DOMAIN).'</option>';
	echo '</select>';
	echo '</td>';
	echo '</tr>';

	echo '<tr>';
	echo '<th><label for="cpc_member_status">'.__('Status', CPC2_TEXT_DOMAIN).'</label></th>';
	echo '<td>';
	echo '<select name="cpc_member_status" id="cpc_member_status" style="width:200px">';
		echo '<option value="active"'.selected($status, 'active', false).'>'.__('Aktiv', CPC2_TEXT_DOMAIN).'</option>';
		echo '<option value="pending"'.selected($status, 'pending', false).'>'.__('Ausstehend', CPC2_TEXT_DOMAIN).'</option>';
		echo '<option value="banned"'.selected($status, 'banned', false).'>'.__('Gesperrt', CPC2_TEXT_DOMAIN).'</option>';
	echo '</select>';
	echo '</td>';
	echo '</tr>';

	echo '<tr>';
	echo '<th><label>'.__('Beigetreten am', CPC2_TEXT_DOMAIN).'</label></th>';
	echo '<td>';
	if ($joined) {
		echo date_i18n(get_option('date_format').' '.get_option('time_format'), $joined);
	} else {
		echo __('Noch nicht gesetzt', CPC2_TEXT_DOMAIN);
	}
	echo '</td>';
	echo '</tr>';

	echo '</table>';
}

add_action( 'save_post', 'group_member_info_box_save' );
function group_member_info_box_save( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	return;

	if ( !isset($_POST['group_member_info_box_content_nonce']) || !wp_verify_nonce( $_POST['group_member_info_box_content_nonce'], 'group_member_info_box_content' ) )
	return;

	if ( !current_user_can( 'edit_post', $post_id ) ) return;

	if (isset($_POST['cpc_member_user_id'])):
		update_post_meta($post_id, 'cpc_member_user_id', intval($_POST['cpc_member_user_id']));
	endif;

	if (isset($_POST['cpc_member_group_id'])):
		update_post_meta($post_id, 'cpc_member_group_id', intval($_POST['cpc_member_group_id']));
	endif;

	if (isset($_POST['cpc_member_role'])):
		update_post_meta($post_id, 'cpc_member_role', sanitize_text_field($_POST['cpc_member_role']));
	endif;

	if (isset($_POST['cpc_member_status'])):
		update_post_meta($post_id, 'cpc_member_status', sanitize_text_field($_POST['cpc_member_status']));
	endif;

	// Set joined date if not already set
	$joined = get_post_meta($post_id, 'cpc_member_joined', true);
	if (!$joined):
		update_post_meta($post_id, 'cpc_member_joined', current_time('timestamp'));
	endif;

	// Update member count in group
	$group_id = get_post_meta($post_id, 'cpc_member_group_id', true);
	if ($group_id) {
		cpc_update_group_member_count($group_id);
	}
}

/* =========================== COLUMNS WHEN VIEWING =========================== */

add_filter('manage_cpc_group_members_posts_columns', 'group_member_columns_head');
add_action('manage_cpc_group_members_posts_custom_column', 'group_member_columns_content', 10, 2);

// ADD NEW COLUMN
function group_member_columns_head($defaults) {
	$defaults['member_user'] = __('Benutzer', CPC2_TEXT_DOMAIN);
    $defaults['member_group'] = __('Gruppe', CPC2_TEXT_DOMAIN);
    $defaults['member_role'] = __('Rolle', CPC2_TEXT_DOMAIN);
    $defaults['member_status'] = __('Status', CPC2_TEXT_DOMAIN);
    $defaults['member_joined'] = __('Beigetreten', CPC2_TEXT_DOMAIN);
    return $defaults;
}
 
// SHOW THE COLUMN CONTENT
function group_member_columns_content($column_name, $post_ID) {
	if ($column_name == 'member_user') {
		$user_id = get_post_meta($post_ID, 'cpc_member_user_id', true);
		if ($user_id) {
			$user = get_user_by('id', $user_id);
			if ($user) echo $user->display_name;
		}
	}
	if ($column_name == 'member_group') {
		$group_id = get_post_meta($post_ID, 'cpc_member_group_id', true);
		if ($group_id) {
			echo '<a href="'.get_edit_post_link($group_id).'">'.get_the_title($group_id).'</a>';
		}
	}
	if ($column_name == 'member_role') {
		$role = get_post_meta($post_ID, 'cpc_member_role', true);
		if ($role) {
			switch($role) {
				case 'member':
					echo __('Mitglied', CPC2_TEXT_DOMAIN);
					break;
				case 'moderator':
					echo __('Moderator', CPC2_TEXT_DOMAIN);
					break;
				case 'admin':
					echo __('Admin', CPC2_TEXT_DOMAIN);
					break;
			}
		}
	}
	if ($column_name == 'member_status') {
		$status = get_post_meta($post_ID, 'cpc_member_status', true);
		if ($status) {
			switch($status) {
				case 'active':
					echo '<span style="color:green">●</span> '.__('Aktiv', CPC2_TEXT_DOMAIN);
					break;
				case 'pending':
					echo '<span style="color:orange">●</span> '.__('Ausstehend', CPC2_TEXT_DOMAIN);
					break;
				case 'banned':
					echo '<span style="color:red">●</span> '.__('Gesperrt', CPC2_TEXT_DOMAIN);
					break;
			}
		}
	}
	if ($column_name == 'member_joined') {
		$joined = get_post_meta($post_ID, 'cpc_member_joined', true);
		if ($joined) {
			echo date_i18n(get_option('date_format'), $joined);
		}
	}
}
?>
