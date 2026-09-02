<?php

/* Create Alerts custom post type */

/* =========================== LABELS FOR ADMIN =========================== */


function cpc_custom_post_alerts() {
	$labels = array(
		'name'               => __( 'Benachrichtigungen', 'cp-community' ),
		'singular_name'      => __( 'Benachrichtigungen', 'cp-community' ),
		'add_new'            => __( 'Neue hinzufügen', 'cp-community' ),
		'add_new_item'       => __( 'Neue Benachrichtigung hinzufügen', 'cp-community' ),
		'edit_item'          => __( 'Benachrichtigung bearbeiten', 'cp-community' ),
		'new_item'           => __( 'Neue Benachrichtigung', 'cp-community' ),
		'all_items'          => __( 'Benachrichtigungen', 'cp-community' ),
		'view_item'          => __( 'Benachrichtigung anzeigen', 'cp-community' ),
		'search_items'       => __( 'Suche Benachrichtigungen', 'cp-community' ),
		'not_found'          => __( 'Keine Benachrichtigungen gefunden', 'cp-community' ),
		'not_found_in_trash' => __( 'Im Papierkorb wurden keine Benachrichtigungen gefunden', 'cp-community' ), 
		'parent_item_colon'  => '',
		'menu_name'          => __('Benachrichtigungen', 'cp-community'),
	);
	$args = array(
		'labels'        		=> $labels,
		'description'   		=> 'Holds our alerts specific data',
		'public'        		=> true,
		'rewrite'				=> false,
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
		'supports'      		=> array( 'title', 'editor', 'excerpt' ),
		'has_archive'   		=> false,
	);
	register_post_type( 'cpc_alerts', $args );
}
add_action( 'init', 'cpc_custom_post_alerts' );

/* =========================== MESSAGES FOR ADMIN =========================== */

function cpc_updated_alerts_messages( $messages ) {
	global $post, $post_ID;
	$messages['cpc_alerts'] = array(
		0 => '', 
		1 => __('Alert updated.', 'cp-community'),
		2 => __('Custom field updated.', 'cp-community'),
		3 => __('Custom field deleted.', 'cp-community'),
		4 => __('Alert updated.', 'cp-community'),
		5 => isset($_GET['revision']) ? sprintf( __('Alerts restored to revision from %s', 'cp-community'), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6 => __('Alert published.', 'cp-community'),
		7 => __('Alert saved.', 'cp-community'),
		8 => __('Alert submitted.', 'cp-community'),
		9 => sprintf( __('Alert scheduled for: <strong>%1$s</strong>.', 'cp-community'), date_i18n( __( 'M j, Y @ G:i' ), strtotime( $post->post_date ) ) ),
		10 => __('Alerts draft updated.', 'cp-community'),
	);
	return $messages;
}
add_filter( 'post_updated_messages', 'cpc_updated_alerts_messages' );


/* =========================== META FIELDS CONTENT BOX WHEN EDITING =========================== */


add_action( 'add_meta_boxes', 'alerts_info_box' );
function alerts_info_box() {
    add_meta_box( 
        'alerts_info_box',
        __( 'Alert Details', 'cp-community' ),
        'alerts_info_box_content',
        'cpc_alerts',
        'side',
        'high'
    );
}

function alerts_info_box_content( $post ) {
	global $wpdb;
	wp_nonce_field( 'alerts_info_box_content', 'alerts_info_box_content_nonce' );

	if ($sent_datetime = get_post_meta( $post->ID, 'cpc_alert_sent_datetime', true ) ):
		echo '<div style="margin-top:10px;font-weight:bold">'.__('Sent date and time', 'cp-community').'</div>';
		echo $sent_datetime;
	endif;

	if ($failed_datetime = get_post_meta( $post->ID, 'cpc_alert_failed_datetime', true ) ):
		echo '<div style="margin-top:10px;font-weight:bold">'.__('Failed to send date and time', 'cp-community').'</div>';
		echo $failed_datetime.'<br />';
		echo get_post_meta( $post->ID, 'cpc_alert_note', true );	    		
	endif;

	echo '<div style="margin-top:10px;font-weight:bold">'.__('Recipient', 'cp-community').'</div>';
	echo '<input type="text" id="cpc_alert_recipient" style="width:100%" name="cpc_alert_recipient" placeholder="'.__('User login', 'cp-community').'" value="'.get_post_meta( $post->ID, 'cpc_alert_recipient', true ).'" />';
	$user = get_user_by('login', get_post_meta( $post->ID, 'cpc_alert_recipient', true ));
	if ($user):
		echo '<br />'.$user->display_name;
		echo '<br />'.$user->user_email;
	endif;

	echo '<div style="margin-top:10px;font-weight:bold">'.__('Page slug', 'cp-community').'</div>';
	echo '<input type="text" id="cpc_alert_target" name="cpc_alert_target" placeholder="'.__('Page slug', 'cp-community').'" value="'.get_post_meta( $post->ID, 'cpc_alert_target', true ).'" />';

	echo '<div style="margin-top:10px;font-weight:bold">'.__('Parameters', 'cp-community').'</div>';
	echo '<input type="text" id="cpc_alert_parameters" name="cpc_alert_parameters" placeholder="'.__('Querystring parameters', 'cp-community').'" value="'.get_post_meta( $post->ID, 'cpc_alert_parameters', true ).'" />';

	echo '<div style="margin-top:10px;font-weight:bold">'.__('URL', 'cp-community').'</div>';
	echo '<input type="text" id="cpc_alert_url" name="cpc_alert_url" value="'.get_post_meta( $post->ID, 'cpc_alert_url', true ).'" />';
}

add_action( 'save_post', 'alerts_info_box_save' );
function alerts_info_box_save( $post_id ) {

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
	return;

	if ( !isset($_POST['alerts_info_box_content_nonce']) || !wp_verify_nonce( $_POST['alerts_info_box_content_nonce'], 'alerts_info_box_content' ) )
	return;

	if ( !current_user_can( 'edit_post', $post_id ) ) return;

	update_post_meta( $post_id, 'cpc_alert_recipient', $_POST['cpc_alert_recipient'] );	
	update_post_meta( $post_id, 'cpc_alert_target', $_POST['cpc_alert_target'] );	
	update_post_meta( $post_id, 'cpc_alert_url', $_POST['cpc_alert_url'] );	
	update_post_meta( $post_id, 'cpc_alert_parameters', $_POST['cpc_alert_parameters'] );	

}

/* =========================== COLUMNS WHEN VIEWING =========================== */

/* Columns for Posts list */
add_filter('manage_posts_columns', 'alerts_columns_head');
add_action('manage_posts_custom_column', 'alerts_columns_content', 10, 2);

// ADD NEW COLUMN
function alerts_columns_head($defaults) {
    global $post;
	if ($post && $post->post_type == 'cpc_alerts') {
		//$defaults['col_id'] = 'ID';
		$defaults['col_content'] = 'Content';
		$defaults['col_sent'] = 'Sent';
		$defaults['col_recipient'] = 'Recipient';
		$defaults['col_recipient_email'] = 'Email';
    }
    return $defaults;
}
 
// SHOW THE COLUMN CONTENT
function alerts_columns_content($column_name, $post_ID) {
    if ($column_name == 'col_id') {
		echo $post_ID;
    }
    if ($column_name == 'col_content') {
    	$post = get_post($post_ID);
    	if ($post):
			$content = preg_replace('#<[^>]+>#', ' ', $post->post_content);
			$max_len = 100;
			if (strlen($content) > $max_len) $content = substr($content, 0, $max_len).'...';
			echo $content;
		endif;
    }
    if ($column_name == 'col_sent') {
    	$post = get_post($post_ID);
    	if ($post):
	    	$success_date = get_post_meta( $post->ID, 'cpc_alert_sent_datetime', true );
	    	if ($success_date):
	    		echo $success_date;
	    	else:
	    		$failed_date = get_post_meta( $post->ID, 'cpc_alert_failed_datetime', true );
	    		if ($failed_date):
		    		echo '<div style="color: #f00">'.$failed_date.'</div>';
		    		echo get_post_meta( $post_ID, 'cpc_alert_note', true );
		    	else:
		    		echo __('waiting...', 'cp-community');
		    	endif;
	    	endif;
	    endif;
    }
    if ($column_name == 'col_recipient') {
		$user = get_user_by('login', get_post_meta( $post_ID, 'cpc_alert_recipient', true ));
		if ($user):
			echo $user->user_login;
		else:
			echo '<div style="color: #f00">'.sprintf(__('No recipient, <a href="%s">clean up subscriptions</a>?', 'cp-community'), admin_url( 'edit.php?post_type=cpc_alerts&cpc_cleanup=1' )).'</div>';
		endif;
    }
    if ($column_name == 'col_recipient_email') {
		$user = get_user_by('login', get_post_meta( $post_ID, 'cpc_alert_recipient', true ));
		if ($user)
			echo $user->user_email;
    }

}


/* =========================== EXTRA ACTIONS =========================== */

add_filter( 'views_edit-cpc_alerts', 'cpc_alerts_clear_sent' );
function cpc_alerts_clear_sent( $views )
{
	if ( current_user_can( 'manage_options' ) ):

		if (isset($_REQUEST['cpc_action'])):

			$nonce = $_REQUEST['_wpnonce'];
			if ( wp_verify_nonce( $nonce, 'cpc_alerts_clear' ) ) {

				global $wpdb;

			if (isset($_REQUEST['cpc_action']) && $_REQUEST['cpc_action'] == 'cpc_alerts_clear_sent'):
					$wpdb->query($sql);

					echo '<div class="updated"><p>';
					echo __('Sent alerts removed (please wait, refreshing page...).', 'cp-community');
					echo '</p></div>';

				endif;

			if (isset($_REQUEST['cpc_action']) && $_REQUEST['cpc_action'] == 'cpc_alerts_clear_pending'):
					$wpdb->query($sql);

					echo '<div class="updated"><p>';
					echo __('Pending alerts removed (please wait, refreshing page...).', 'cp-community');
					echo '</p></div>';

				endif;

			if (isset($_REQUEST['cpc_action']) && $_REQUEST['cpc_action'] == 'cpc_alerts_clear_all'):
					$wpdb->query($sql);

					echo '<div class="updated"><p>';
					echo __('All alerts removed (please wait, refreshing page...).', 'cp-community');
					echo '</p></div>';

				endif;

				echo '<script type="text/javascript">';
				echo 'window.location = window.location.pathname+\'?post_type=cpc_alerts\';';
				echo '</script>';

			};

		endif;

		$nonce = wp_create_nonce( 'cpc_alerts_clear' );
	    $views['cpc-alerts-clear-sent'] = '<a onclick="return confirm(\''.__('Are you sure, this cannot be undone?', 'cp-community').'\')" id="cpc_alerts_clear_sent" href="edit.php?post_type=cpc_alerts&cpc_action=cpc_alerts_clear_sent&_wpnonce='.$nonce.'">'.__('Remove all sent alerts', 'cp-community').'</a>';
	    $views['cpc-alerts-pending-sent'] = '<a onclick="return confirm(\''.__('Are you sure, this cannot be undone?', 'cp-community').'\')" id="cpc_alerts_clear_sent" href="edit.php?post_type=cpc_alerts&cpc_action=cpc_alerts_clear_pending&_wpnonce='.$nonce.'">'.__('Remove all pending alerts', 'cp-community').'</a>';
        if (get_option('cpc_alert_resend')) $views['cpc-alerts-pending-sent'] .= ' ('.__('re-send enabled', 'cp-community').')';
    	$views['cpc-alerts-clear-all'] = '<a onclick="return confirm(\''.__('Are you sure, this cannot be undone?', 'cp-community').'\')" id="cpc_alerts_clear_sent" href="edit.php?post_type=cpc_alerts&cpc_action=cpc_alerts_clear_all&_wpnonce='.$nonce.'">'.__('Remove all alerts', 'cp-community').'</a>';   	
    	$views['cpc-server-time'] = __('Server time', 'cp-community').': '.current_time('mysql', 1);

    	return $views;

    endif;
}



?>