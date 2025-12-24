<?php

// Add to Getting Started information
add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_friendships', 4);
function cpc_admin_getting_started_friendships() {

    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_friendships' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';    
  	echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" rel="cpc_admin_getting_started_friendships" id="cpc_admin_getting_started_friendships_div">'.__('Freundschaften', CPC2_TEXT_DOMAIN).'</div>';

  	$display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_friendships' ? 'block' : 'none';
  	echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_friendships" style="display:'.$display.'">';

		?>
		<table class="form-table">
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_friendships_all"><?php _e('Alle Freunde', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<input type="checkbox" style="width:10px" name="cpc_friendships_all" <?php if (get_option('cpc_friendships_all')) echo 'CHECKED'; ?> /> 
				<span class="description"><?php _e('Macht jeden Benutzer immer mit allen anderen befreundet. Gut für private soziale Netzwerke.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		<tr class="form-field">
        <td scope="row" valign="top">
            <label for="cpc_friends_layout"><?php _e('Freunde-Layout', CPC2_TEXT_DOMAIN); ?></label>
        </td>
        <td>
            <select name="cpc_friends_layout">
                <option value="list" <?php selected(get_option('cpc_friends_layout', 'list'), 'list'); ?>><?php _e('Liste', CPC2_TEXT_DOMAIN); ?></option>
                <option value="fluid" <?php selected(get_option('cpc_friends_layout', 'list'), 'fluid'); ?>><?php _e('Flüssig', CPC2_TEXT_DOMAIN); ?></option>
            </select>
        </td>
    	</tr> 
		</table>
        <?php
	echo '</div>';

}

/* AJAX */

add_action('cpc_admin_setup_form_get_hook', 'cpc_admin_friendships_save', 10, 2);
add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_friendships_save', 10, 2);

function cpc_admin_friendships_save($the_post) {

	if (isset($the_post['cpc_friendships_all'])):
		update_option('cpc_friendships_all', true);
	else:
		delete_option('cpc_friendships_all');
	endif;

	// Speichern der Layout-Option
    if (isset($the_post['cpc_friends_layout'])) {
        update_option('cpc_friends_layout', sanitize_text_field($the_post['cpc_friends_layout']));
    }

}

add_action( 'wp_ajax_cpc_add_favourite', 'cpc_add_favourite' ); 
add_action( 'wp_ajax_cpc_remove_favourite', 'cpc_remove_favourite' ); 

function cpc_add_favourite() {

	global $current_user;
	$the_user = get_user_by('id', $_POST['user_id']);

    $post = array(
    	'post_title'     => $current_user->user_login.' - '.$the_user->user_login,
		'post_name'	=> sanitize_title_with_dashes($member1->user_login.' '.$member2->user_login),      
		'post_status'    => 'publish',
		'post_type'      => 'cpc_favourite_friend',
		'post_author'    => $current_user->ID,
		'ping_status'    => 'closed',
		'comment_status' => 'closed',
    );  
    $new_id = wp_insert_post( $post );
    if ($new_id):
		update_post_meta( $new_id, 'cpc_favourite_member1', $current_user->ID );
		update_post_meta( $new_id, 'cpc_favourite_member2', $_POST['user_id'] );
		update_post_meta( $new_id, 'cpc_favourite_friendship_since', date('Y-m-d H:i:s') );
	endif;

	exit;

}

function cpc_remove_favourite() {

	global $current_user;

	$friendship = cpc_is_a_favourite_friend($current_user->ID, $_POST['user_id']);
	if ($friendship):
		wp_delete_post($friendship['ID'], true);
	endif;

	exit;

}
?>