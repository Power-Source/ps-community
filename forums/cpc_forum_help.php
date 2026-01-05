<?php
// Add menu items for forum
add_action( 'admin_menu', 'cpc_add_forums_menu' );
function cpc_add_forums_menu() {
    add_submenu_page(get_option('cpc_core_admin_icons') ? 'cpc_com' : '', __('Forum Setup', CPC2_TEXT_DOMAIN), __('Forum Setup', CPC2_TEXT_DOMAIN), 'manage_options', 'edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post');
    add_submenu_page(get_option('cpc_core_admin_icons') ? 'cpc_com' : '', __('Alle Foren', CPC2_TEXT_DOMAIN), __('Alle Foren', CPC2_TEXT_DOMAIN), 'manage_options', 'cpccom_forum_setup', 'cpccom_forum_setup');
}

// Quick Start
add_action('cpc_admin_quick_start_hook', 'cpc_admin_quick_start_forum');
function cpc_admin_quick_start_forum() {

	echo '<div style="margin-right:10px; float:left">';
	echo '<input type="submit" id="cpc_admin_forum_add" class="button-secondary" value="'.__('Forum hinzufügen', CPC2_TEXT_DOMAIN).'" />';
	echo '</div>';

	echo '<div id="cpc_admin_forum_add_details" style="clear:both;display:none">';
		echo '<form action="" method="POST">';
		echo '<input type="hidden" name="cpccom_quick_start" value="forum" />';
		echo '<br /><strong>'.__('Gib den Namen des neuen Forums ein', CPC2_TEXT_DOMAIN).'</strong><br />';
		echo '<input type="input" style="margin-top:4px;" id="cpc_admin_forum_add_name" name="cpc_admin_forum_add_name" /><br />';
		echo '<br /><strong>'.__('Gib eine Beschreibung des neuen Forums ein', CPC2_TEXT_DOMAIN).'</strong><br />';
		echo '<input type="input" style="margin-top:4px;width:300px;" id="cpc_admin_forum_add_description" name="cpc_admin_forum_add_description" /><br /><br />';
		echo '<input type="submit" id="cpc_admin_forum_add_button" class="button-primary" value="'.__('Veröffentlichen', CPC2_TEXT_DOMAIN).'" />';
		echo '</form>';
	echo '</div>';


}


add_action('cpc_admin_quick_start_form_save_hook', 'cpc_admin_quick_start_forum_save', 10, 1);
function cpc_admin_quick_start_forum_save($the_post) {

	if (isset($the_post['cpccom_quick_start']) && $the_post['cpccom_quick_start'] == 'forum'):

		$name = $the_post['cpc_admin_forum_add_name'];
		$description = $the_post['cpc_admin_forum_add_description'];
		$slug = sanitize_title_with_dashes($name);

		$new_term = wp_insert_term(
		  $name, 
		  'cpc_forum', 
		  array(
		    'description'=> $description,
		    'slug' => $slug,
		  )
		);	

		if (is_wp_error($new_term)):
			
			echo '<div class="cpc_error">'.__('Du hast dieses Forum bereits hinzugefügt.', CPC2_TEXT_DOMAIN).'</div>';

		else:

			$post_content = '['.CPC_PREFIX.'-forum-post slug="'.$slug.'"]['.CPC_PREFIX.'-forum-backto slug="'.$slug.'"]['.CPC_PREFIX.'-forum slug="'.$slug.'"]';
 			$post_content .= '['.CPC_PREFIX.'-forum-reply slug="'.$slug.'"]['.CPC_PREFIX.'-forum-backto slug="'.$slug.'"]';

			// Forum Page
			$post = array(
			  'post_content'   => $post_content,
			  'post_name'      => $slug,
			  'post_title'     => $name,
			  'post_status'    => 'publish',
			  'post_type'      => 'page',
			  'ping_status'    => 'closed',
			  'comment_status' => 'closed',
			);  

			$new_id = wp_insert_post( $post );	

			cpc_update_term_meta( $new_term['term_id'], 'cpc_forum_public', true );
			cpc_update_term_meta( $new_term['term_id'], 'cpc_forum_cat_page', $new_id );
			cpc_update_term_meta( $new_term['term_id'], 'cpc_forum_order', 1 );

			echo '<div class="cpc_success">';
				echo sprintf(__('Forumseite (%s) hinzugefügt. [<a href="%s">view</a>]', CPC2_TEXT_DOMAIN), urldecode(get_permalink($new_id)), urldecode(get_permalink($new_id))).'<br /><br />';
				echo sprintf(__('Vielleicht möchtest Du es zu Deinem <a href="%s">ClassicPress-Menü</a> hinzufügen.', CPC2_TEXT_DOMAIN), "nav-menus.php");
			echo '</div>';

		endif;

	endif;

}

// Add to Getting Started information
add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_forum', 4);
function cpc_admin_getting_started_forum() {

    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_forum' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';    
  	echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" rel="cpc_admin_getting_started_forum" id="cpc_admin_getting_started_forum_div">'.__('Forum', CPC2_TEXT_DOMAIN).'</div>';

  	$display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_forum' ? 'block' : 'none';
  	echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_forum" style="display:'.$display.'">';

		?>
		<table class="form-table">
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_forum_auto_close"><?php _e('Automatischer Schließzeitraum', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<input type="text" style="width:50px" name="cpc_forum_auto_close" value="<?php echo get_option('cpc_forum_auto_close'); ?>" /> 
				<span class="description"><?php echo sprintf(__('Standardanzahl der Tage nach Inaktivität, in denen ein Forumsbeitrag automatisch geschlossen wird (leer für Nie). Kann für einzelne Foren über Bearbeiten unter <a href="%s">Alle Foren verwalten</a> überschrieben werden.', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpccom_forum_setup' ) ); ?></span>
			</td>
        </tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_forum_slug_length"><?php _e('Slug Länge', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
                <?php $cpc_forum_slug_length = get_option('cpc_forum_slug_length') ? get_option('cpc_forum_slug_length') : 50; ?>
				<input type="text" style="width:50px" name="cpc_forum_slug_length" value="<?php echo $cpc_forum_slug_length; ?>" /> 
				<span class="description"><?php echo __('Maximale Länge für Forenbeitragstitel in URLs.', CPC2_TEXT_DOMAIN) ; ?></span>
			</td>
        </tr>
        <tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_forum_sticky_admin_only"><?php _e('Sticky Beiträge', CPC2_TEXT_DOMAIN); ?></label>
			</td>            
            <td>
                <input type="checkbox" name="cpc_forum_sticky_admin_only" 
                <?php if (get_option('cpc_forum_sticky_admin_only')) echo ' CHECKED'; ?>
                />
                <span class="description">
                    <?php _e('Sticky-Option nur dem Webseiten-Administrator anzeigen.', CPC2_TEXT_DOMAIN); ?>
                </span>
            </td>            
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_com_toolbar"><?php _e('Editor im Frontend', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<?php $toolbar = get_option('cpc_com_toolbar') ? get_option('cpc_com_toolbar') : 'none'; ?>
				<select name="cpc_com_toolbar" id="cpc_com_toolbar">
					<option value="none" <?php selected($toolbar, 'none'); ?>><?php _e('Kein Editor', CPC2_TEXT_DOMAIN); ?></option>
					<option value="wysiwyg" <?php selected($toolbar, 'wysiwyg'); ?>><?php _e('WYSIWYG (TinyMCE)', CPC2_TEXT_DOMAIN); ?></option>
					<option value="bbcodes" <?php selected($toolbar, 'bbcodes'); ?>><?php _e('BBCode-Editor', CPC2_TEXT_DOMAIN); ?></option>
				</select>
				<span class="description"><?php _e('Wähle den Editor-Typ für das Forum-Frontend.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		<?php 
				do_action('cpc_admin_getting_started_forum_hook');
		?>
		</table>
        <?php

	echo '</div>';

}

add_action('cpc_admin_setup_form_get_hook', 'cpc_admin_forum_save', 10, 2);
add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_forum_save', 10, 2);
function cpc_admin_forum_save($the_post) {
        
	if (isset($the_post['cpc_forum_auto_close']) && $the_post['cpc_forum_auto_close'] != ''):
		update_option('cpc_forum_auto_close', $the_post['cpc_forum_auto_close']);
	else:
		delete_option('cpc_forum_auto_close');
	endif;
    
	if (isset($the_post['cpc_forum_slug_length']) && $the_post['cpc_forum_slug_length'] != ''):
		update_option('cpc_forum_slug_length', $the_post['cpc_forum_slug_length']);
	else:
		update_option('cpc_forum_slug_length', 50);
	endif;
    
	if (isset($the_post['cpc_forum_sticky_admin_only'])):
		update_option('cpc_forum_sticky_admin_only', true);
	else:
		delete_option('cpc_forum_sticky_admin_only');
	endif;
	
	if (isset($the_post['cpc_com_toolbar'])) {
		update_option('cpc_com_toolbar', $the_post['cpc_com_toolbar']);
	}

	do_action('cpc_admin_forum_save_hook', $the_post);


}

?>