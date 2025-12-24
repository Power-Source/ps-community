<?php 

add_action('show_user_profile', 'cpc_usermeta_form');
add_action('edit_user_profile', 'cpc_usermeta_form');

add_action( 'personal_options_update', 'cpc_usermeta_form_save' );
add_action( 'edit_user_profile_update', 'cpc_usermeta_form_save' );

add_action( 'wp_ajax_cpc_deactivate_account', 'cpc_deactivate_account' ); 

add_filter('authenticate', 'cpc_check_login', 30, 3);

add_action('wp_ajax_cpc_add_to_site', 'cpc_add_to_site');

/* ADD TO SITE (MULTI-SITE) */
function cpc_add_to_site() {

    global $current_user;
    if ( is_user_logged_in() ) {
        $user = get_user_by('login', $current_user->user_login); 
        if (!is_user_member_of_blog( intval($user->ID), get_current_blog_id()))
        if (add_user_to_blog(get_current_blog_id(), intval($user->ID), 'subscriber')) {
            echo get_site_url();
        } else {
            echo 'failed';
        }
    }
    exit;
}


/* CHECK IF CLOSED ON WP LOGIN */
function cpc_check_login($user, $username, $password) {

    $return = $user;

    if ($user && !is_wp_error($user) && $user->ID && $username && cpc_is_account_closed($user->ID)) $return = new WP_Error('cpc_login_fail', __('Dieses Konto ist geschlossen.', CPC2_TEXT_DOMAIN));
        
    return $return;

}

/* DE-ACTIVATE (CLOSE) ACCOUNT */
function cpc_deactivate_account($id=false) {

    global $wpdb, $current_user;
    $user_id = $id ? $id : $_POST['user_id'];
    
    if ($user_id == $current_user->ID || current_user_can('manage_options')):
	    
        // get the user
        $get_the_user = get_user_by('id', $user_id);
        // remove user email
        $update_user = wp_update_user( array(
            'ID'            => $user_id,
            'user_pass'     => wp_generate_password( 12, false ),
            'user_email'    => $get_the_user->user_login.'@example.com',
            'display_name'  => $get_the_user->user_login,
			'nickname'      => $get_the_user->user_login,
			'first_name'    => '',
			'last_name'     => ''
        ));
        // remove avatar
        user_avatar_delete_files($get_the_user->ID);
        // remove CPC meta
        $sql = "DELETE FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d and meta_key like 'cpc_%%'";
        $wpdb->query($wpdb->prepare($sql, $get_the_user->ID));
        $sql = "DELETE FROM ".$wpdb->base_prefix."usermeta WHERE user_id = %d and meta_key like 'cpccom_%%'";
        $wpdb->query($wpdb->prepare($sql, $get_the_user->ID));
        // set as closed
        $info = array (
            'date' => current_time('mysql', 1),
            'user_id' => $get_the_user->ID,
            'user_login' => $get_the_user->user_login,
            'client_ip' => $_SERVER['REMOTE_ADDR']
        );
        update_user_meta($get_the_user->ID, 'cpc_account_closed', $info);
        // logout (if being closed by the user)
        if (!$id):
            wp_logout();
        else:
            wp_redirect(admin_url('user-edit.php?user_id='.$get_the_user->ID));    
        endif;
    
    endif;

	exit;
}

function cpc_usermeta_form($user)
{

	global $current_user;
	
	// Check if it is current user or super admin role
	if( $user->ID == $current_user->ID || current_user_can('edit_user', $current_user->ID) || is_super_admin($current_user->ID) )
	{
		?>

		<h3><?php _e('PS Community', CPC2_TEXT_DOMAIN); ?></h3>

		<table class="form-table">

            <?php if (current_user_can('manage_options')): ?>

                <?php if (!cpc_is_account_closed($user->ID)): ?>

                    <tr>
                        <th><label for="cpc_close_account"><?php _e('Konto schließen', CPC2_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <input type="checkbox" name="cpc_close_account" id="cpc_close_account" />
                            <span class="description"><?php _e('Alle personenbezogenen Daten werden gelöscht, dies kann nicht rückgängig gemacht werden.', CPC2_TEXT_DOMAIN); ?></span>
                        </td>
                    </tr>

                <?php else: ?>

                    <tr>
                        <th><label for="cpc_reopen_account"><?php _e('Konto erneut eröffnen', CPC2_TEXT_DOMAIN); ?></label></th>
                        <td>
                            <input type="checkbox" name="cpc_reopen_account" id="cpc_reopen_account" />
                            <span class="description"><?php _e('Du kannst oben vor dem Speichern optional das Passwort, die E-Mail-Adresse usw. festlegen.', CPC2_TEXT_DOMAIN); ?></span>
                        </td>
                    </tr>

                <?php endif; ?>

            <?php endif; ?>

			<tr>
				<th><label for="cpccom_home"><?php _e('Stadt/Gemeinde', CPC2_TEXT_DOMAIN); ?></label></th>
				<td>
					<input type="text" name="cpccom_home" id="cpccom_home" value="<?php echo esc_attr( get_the_author_meta( 'cpccom_home', $user->ID ) ); ?>" class="regular-text" /><br />
					<span class="description"><?php _e('Bitte gib Deinen Ort ein.', CPC2_TEXT_DOMAIN); ?></span>
				</td>
			</tr>

			<tr>
				<th><label for="cpccom_country"><?php _e('Land', CPC2_TEXT_DOMAIN); ?></label></th>
				<td>
					<input type="text" name="cpccom_country" id="cpccom_country" value="<?php echo esc_attr( get_the_author_meta( 'cpccom_country', $user->ID ) ); ?>" class="regular-text" /><br />
					<span class="description"><?php _e('Bitte gib Dein Land ein.', CPC2_TEXT_DOMAIN); ?></span>
				</td>
			</tr>

		</table>

		<?php

	}
	
} 

function cpc_usermeta_form_save( $user_id ) {

	if ( !current_user_can( 'edit_user', $user_id ) )
		return false;

    $home_form = strip_tags($_POST['cpccom_home']);
    $country_form = strip_tags($_POST['cpccom_country']);
    
	update_user_meta($user_id, 'cpccom_home', $home_form);
	update_user_meta($user_id, 'cpccom_country', $country_form);

    // This must be last
    if (current_user_can('manage_options')):
        if (isset($_POST['cpc_close_account'])) cpc_deactivate_account($user_id);
        if (isset($_POST['cpc_reopen_account'])) delete_user_meta($user_id, 'cpc_account_closed');
    endif;
        
}


?>