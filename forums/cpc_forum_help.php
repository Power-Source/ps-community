<?php
// Add menu items for forum
add_action( 'admin_menu', 'cpc_add_forums_menu' );
function cpc_add_forums_menu() {
    add_submenu_page(get_option('cpc_core_admin_icons') ? 'cpc_com' : '', __('Forum Setup', 'cp-community'), __('Forum Setup', 'cp-community'), 'manage_options', 'edit-tags.php?taxonomy=cpc_forum&post_type=cpc_forum_post');
    add_submenu_page(get_option('cpc_core_admin_icons') ? 'cpc_com' : '', __('Alle Foren', 'cp-community'), __('Alle Foren', 'cp-community'), 'manage_options', 'cpccom_forum_setup', 'cpccom_forum_setup');
	add_submenu_page(get_option('cpc_core_admin_icons') ? 'cpc_com' : '', __('Forum-Moderation', 'cp-community'), __('Forum-Moderation', 'cp-community'), 'manage_options', 'cpc_forum_moderation', 'cpc_forum_render_moderation_page');
}

function cpc_forum_render_moderation_page() {
	if (!current_user_can('manage_options')) {
		wp_die(__('Keine Berechtigung.', 'cp-community'));
	}

	if (!empty($_POST['cpc_forum_moderation_action']) && check_admin_referer('cpc_forum_moderation')) {
		$item_type = sanitize_key($_POST['cpc_forum_item_type']);
		$item_id = absint($_POST['cpc_forum_item_id']);
		$action = sanitize_key($_POST['cpc_forum_moderation_action']);

		if ($item_type === 'topic' && get_post_type($item_id) === 'cpc_forum_post') {
			wp_update_post(array('ID' => $item_id, 'post_status' => $action === 'approve' ? 'publish' : 'trash'));
		}
		if ($item_type === 'reply' && get_comment($item_id)) {
			wp_set_comment_status($item_id, $action === 'approve' ? 'approve' : 'trash');
		}
	}

	$topics = get_posts(array(
		'post_type' => 'cpc_forum_post',
		'post_status' => 'pending',
		'posts_per_page' => 100,
		'orderby' => 'date',
		'order' => 'ASC',
	));
	$replies = get_comments(array(
		'type' => 'cpc_forum_comment',
		'status' => 'hold',
		'number' => 100,
		'orderby' => 'comment_date_gmt',
		'order' => 'ASC',
	));

	echo '<div class="wrap"><h1>'.esc_html__('Forum-Moderation', 'cp-community').'</h1>';
	echo '<p>'.esc_html__('Ausstehende Themen und Antworten. Abgelehnte Inhalte werden in den Papierkorb verschoben.', 'cp-community').'</p>';
	echo '<h2>'.esc_html__('Themen', 'cp-community').'</h2>';
	cpc_forum_render_moderation_items($topics, 'topic');
	echo '<h2>'.esc_html__('Antworten', 'cp-community').'</h2>';
	cpc_forum_render_moderation_items($replies, 'reply');
	echo '</div>';
}

function cpc_forum_render_moderation_items($items, $item_type) {
	if (empty($items)) {
		echo '<p>'.esc_html__('Keine ausstehenden Inhalte.', 'cp-community').'</p>';
		return;
	}

	echo '<table class="widefat striped"><thead><tr><th>'.esc_html__('Inhalt', 'cp-community').'</th><th>'.esc_html__('Autor', 'cp-community').'</th><th>'.esc_html__('Eingereicht', 'cp-community').'</th><th>'.esc_html__('Aktion', 'cp-community').'</th></tr></thead><tbody>';
	foreach ($items as $item) {
		$item_id = $item_type === 'topic' ? $item->ID : $item->comment_ID;
		$author_id = $item_type === 'topic' ? $item->post_author : $item->user_id;
		$content = $item_type === 'topic' ? '<strong>'.get_the_title($item).'</strong><br />'.wp_trim_words($item->post_content, 30) : wp_trim_words($item->comment_content, 30);
		$date = $item_type === 'topic' ? $item->post_date : $item->comment_date;
		echo '<tr><td>'.wp_kses_post($content).'</td><td>'.esc_html(get_the_author_meta('display_name', $author_id)).'</td><td>'.esc_html($date).'</td><td>';
		echo '<form method="post" style="display:inline">';
		wp_nonce_field('cpc_forum_moderation');
		echo '<input type="hidden" name="cpc_forum_item_type" value="'.esc_attr($item_type).'" /><input type="hidden" name="cpc_forum_item_id" value="'.absint($item_id).'" />';
		echo '<button class="button button-primary" name="cpc_forum_moderation_action" value="approve">'.esc_html__('Freigeben', 'cp-community').'</button> ';
		echo '<button class="button-link-delete" name="cpc_forum_moderation_action" value="reject">'.esc_html__('Verwerfen', 'cp-community').'</button>';
		echo '</form></td></tr>';
	}
	echo '</tbody></table>';
}

// Quick Start
add_action('cpc_admin_quick_start_hook', 'cpc_admin_quick_start_forum');
function cpc_admin_quick_start_forum() {

	echo '<div style="margin-right:10px; float:left">';
	echo '<input type="submit" id="cpc_admin_forum_add" class="button-secondary" value="'.__('Forum hinzufügen', 'cp-community').'" />';
	echo '</div>';

	echo '<div id="cpc_admin_forum_add_details" style="clear:both;display:none">';
		echo '<form action="" method="POST">';
		echo '<input type="hidden" name="cpccom_quick_start" value="forum" />';
		echo '<br /><strong>'.__('Gib den Namen des neuen Forums ein', 'cp-community').'</strong><br />';
		echo '<input type="input" style="margin-top:4px;" id="cpc_admin_forum_add_name" name="cpc_admin_forum_add_name" /><br />';
		echo '<br /><strong>'.__('Gib eine Beschreibung des neuen Forums ein', 'cp-community').'</strong><br />';
		echo '<input type="input" style="margin-top:4px;width:300px;" id="cpc_admin_forum_add_description" name="cpc_admin_forum_add_description" /><br /><br />';
		echo '<input type="submit" id="cpc_admin_forum_add_button" class="button-primary" value="'.__('Veröffentlichen', 'cp-community').'" />';
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
			
			echo '<div class="cpc_error">'.__('Du hast dieses Forum bereits hinzugefügt.', 'cp-community').'</div>';

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
				echo sprintf(__('Forumseite (%s) hinzugefügt. [<a href="%s">view</a>]', 'cp-community'), urldecode(get_permalink($new_id)), urldecode(get_permalink($new_id))).'<br /><br />';
				echo sprintf(__('Vielleicht möchtest Du es zu Deinem <a href="%s">ClassicPress-Menü</a> hinzufügen.', 'cp-community'), "nav-menus.php");
			echo '</div>';

		endif;

	endif;

}

// Add to Getting Started information
add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_forum', 4);
function cpc_admin_getting_started_forum() {

    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_forum' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';    
  	echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" rel="cpc_admin_getting_started_forum" id="cpc_admin_getting_started_forum_div">'.__('Forum', 'cp-community').'</div>';

  	$display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_forum' ? 'block' : 'none';
  	echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_forum" style="display:'.$display.'">';

		?>
		<table class="form-table">
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_forum_auto_close"><?php _e('Automatischer Schließzeitraum', 'cp-community'); ?></label>
			</td>
			<td>
				<input type="text" style="width:50px" name="cpc_forum_auto_close" value="<?php echo get_option('cpc_forum_auto_close'); ?>" /> 
				<span class="description"><?php echo sprintf(__('Standardanzahl der Tage nach Inaktivität, in denen ein Forumsbeitrag automatisch geschlossen wird (leer für Nie). Kann für einzelne Foren über Bearbeiten unter <a href="%s">Alle Foren verwalten</a> überschrieben werden.', 'cp-community'), admin_url( 'admin.php?page=cpccom_forum_setup' ) ); ?></span>
			</td>
        </tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_forum_slug_length"><?php _e('Slug Länge', 'cp-community'); ?></label>
			</td>
			<td>
                <?php $cpc_forum_slug_length = get_option('cpc_forum_slug_length') ? get_option('cpc_forum_slug_length') : 50; ?>
				<input type="text" style="width:50px" name="cpc_forum_slug_length" value="<?php echo $cpc_forum_slug_length; ?>" /> 
				<span class="description"><?php echo __('Maximale Länge für Forenbeitragstitel in URLs.', 'cp-community') ; ?></span>
			</td>
        </tr>
        <tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_forum_sticky_admin_only"><?php _e('Sticky Beiträge', 'cp-community'); ?></label>
			</td>            
            <td>
                <input type="checkbox" name="cpc_forum_sticky_admin_only" 
                <?php if (get_option('cpc_forum_sticky_admin_only')) echo ' CHECKED'; ?>
                />
                <span class="description">
                    <?php _e('Sticky-Option nur dem Webseiten-Administrator anzeigen.', 'cp-community'); ?>
                </span>
            </td>            
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_com_toolbar"><?php _e('Editor im Frontend', 'cp-community'); ?></label>
			</td>
			<td>
				<?php $toolbar = get_option('cpc_com_toolbar') ? get_option('cpc_com_toolbar') : 'none'; ?>
				<select name="cpc_com_toolbar" id="cpc_com_toolbar">
					<option value="none" <?php selected($toolbar, 'none'); ?>><?php _e('Kein Editor', 'cp-community'); ?></option>
					<option value="wysiwyg" <?php selected($toolbar, 'wysiwyg'); ?>><?php _e('WYSIWYG (TinyMCE)', 'cp-community'); ?></option>
					<option value="bbcodes" <?php selected($toolbar, 'bbcodes'); ?>><?php _e('BBCode-Editor', 'cp-community'); ?></option>
				</select>
				<span class="description"><?php _e('Wähle den Editor-Typ für das Forum-Frontend.', 'cp-community'); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_forum_moderate_first_posts"><?php _e('Moderation neuer Mitglieder', 'cp-community'); ?></label>
			</td>
			<td>
				<label><input type="checkbox" name="cpc_forum_moderate_first_posts" value="1" <?php checked(cpc_forum_get_setting('moderate_first_posts', 0), 1); ?> /> <?php _e('Erstes Thema und erste Antwort eines Mitglieds freigeben', 'cp-community'); ?></label>
			</td>
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_forum_notifications_enabled"><?php _e('Benachrichtigungen', 'cp-community'); ?></label>
			</td>
			<td>
				<label><input type="checkbox" name="cpc_forum_notifications_enabled" value="1" <?php checked(cpc_forum_get_setting('notifications_enabled', 1), 1); ?> /> <?php _e('E-Mail an Themenstarter bei neuen Antworten senden', 'cp-community'); ?></label>
				<p class="description"><?php _e('Mitglieder können E-Mails in ihrem Profil deaktivieren. Moderatoren erhalten Freigabehinweise.', 'cp-community'); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label><?php _e('Experten-Ränge', 'cp-community'); ?></label>
			</td>
			<td>
				<?php
				$ranks = array(
					'newbie' => array(__('Einsteiger', 'cp-community'), 1),
					'helper' => array(__('Helfer', 'cp-community'), 3),
					'pro' => array(__('Profi', 'cp-community'), 10),
					'master' => array(__('Meister', 'cp-community'), 25),
				);
				foreach ($ranks as $key => $rank) :
					$label = cpc_forum_get_setting('rank_'.$key.'_label', $rank[0]);
					$threshold = cpc_forum_get_setting('rank_'.$key.'_threshold', $rank[1]);
				?>
				<p><input type="text" name="cpc_forum_rank_<?php echo esc_attr($key); ?>_label" value="<?php echo esc_attr($label); ?>" class="regular-text" />
				<input type="number" name="cpc_forum_rank_<?php echo esc_attr($key); ?>_threshold" value="<?php echo absint($threshold); ?>" min="1" class="small-text" /> <?php _e('akzeptierte Antworten', 'cp-community'); ?></p>
				<?php endforeach; ?>
				<p class="description"><?php _e('Diese Ränge werden im Shortcode [cpc-forum-experts] angezeigt. Die Schwellen müssen aufsteigend sein.', 'cp-community'); ?></p>
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

	update_option('cpc_forum_moderate_first_posts', isset($the_post['cpc_forum_moderate_first_posts']) ? 1 : 0);
	update_option('cpc_forum_notifications_enabled', isset($the_post['cpc_forum_notifications_enabled']) ? 1 : 0);

	$rank_defaults = array('newbie' => array('Einsteiger', 1), 'helper' => array('Helfer', 3), 'pro' => array('Profi', 10), 'master' => array('Meister', 25));
	$last_threshold = 0;
	foreach ($rank_defaults as $key => $defaults) {
		$label = isset($the_post['cpc_forum_rank_'.$key.'_label']) ? sanitize_text_field(wp_unslash($the_post['cpc_forum_rank_'.$key.'_label'])) : $defaults[0];
		$threshold = isset($the_post['cpc_forum_rank_'.$key.'_threshold']) ? max($last_threshold + 1, absint($the_post['cpc_forum_rank_'.$key.'_threshold'])) : $defaults[1];
		update_option('cpc_forum_rank_'.$key.'_label', $label !== '' ? $label : $defaults[0]);
		update_option('cpc_forum_rank_'.$key.'_threshold', $threshold);
		$last_threshold = $threshold;
	}

	do_action('cpc_admin_forum_save_hook', $the_post);


}

?>