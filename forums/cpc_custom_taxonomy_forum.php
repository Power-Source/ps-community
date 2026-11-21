<?php

function cpc_forum_page_shortcode($slug) {
	return '['.CPC_PREFIX.'-forum-page slug="'.$slug.'"]';
}

function cpc_forum_create_page($term_id, $legacy_content = false) {
	$term = get_term($term_id, 'cpc_forum');
	if (!$term || is_wp_error($term)) {
		return new WP_Error('cpc_forum_invalid', __('Das Forum wurde nicht gefunden.', 'cp-community'));
	}
	if (cpc_get_term_meta($term_id, 'cpc_forum_cat_page', true)) {
		return new WP_Error('cpc_forum_page_assigned', __('Diesem Forum ist bereits eine Seite zugeordnet.', 'cp-community'));
	}
	if (get_page_by_path($term->slug, OBJECT, 'page')) {
		return new WP_Error('cpc_forum_page_exists', __('Eine Seite mit diesem Slug existiert bereits. Bitte wähle sie aus oder ändere den Forum-Slug.', 'cp-community'));
	}
	if (wp_unique_post_slug($term->slug, 0, 'publish', 'page', 0) !== $term->slug) {
		return new WP_Error('cpc_forum_page_slug', __('Der Forum-Slug ist für eine neue Seite nicht verfügbar. Bitte ändere den Forum-Slug.', 'cp-community'));
	}

	$content = cpc_forum_page_shortcode($term->slug);
	if ($legacy_content) {
		$content = '['.CPC_PREFIX.'-forum-post slug="'.$term->slug.'"]['.CPC_PREFIX.'-forum-backto slug="'.$term->slug.'"]['.CPC_PREFIX.'-forum slug="'.$term->slug.'"]';
		$content .= '['.CPC_PREFIX.'-forum-reply slug="'.$term->slug.'"]['.CPC_PREFIX.'-forum-backto slug="'.$term->slug.'"]';
	}
	$page_id = wp_insert_post(array(
		'post_content' => $content,
		'post_name' => $term->slug,
		'post_title' => $term->name,
		'post_status' => 'publish',
		'post_type' => 'page',
		'ping_status' => 'closed',
		'comment_status' => 'closed',
	), true);
	if (is_wp_error($page_id)) {
		return $page_id;
	}
	if (!$page_id || get_post_field('post_name', $page_id) !== $term->slug) {
		return new WP_Error('cpc_forum_page_slug', __('Die Seite konnte nicht mit dem Forum-Slug erstellt werden. Bitte prüfe die Seiteneinstellungen.', 'cp-community'));
	}
	if (!cpc_update_term_meta($term_id, 'cpc_forum_cat_page', $page_id)) {
		return new WP_Error('cpc_forum_page_assignment', sprintf(__('Die Seite wurde erstellt, konnte aber nicht zugeordnet werden. Bitte wähle Seite %d manuell aus.', 'cp-community'), $page_id));
	}
	return $page_id;
}

// Following are so media library can be used for featured image
add_action('admin_enqueue_scripts', function($hook) {
    if ($hook !== 'edit-tags.php?taxonomy=cpc_forum') return;
    wp_enqueue_media();
    wp_enqueue_script('cpc-forum-media', plugins_url('assets/psource-ui/modal/media-modal.js', __FILE__), array('jquery'), false, true);
});

/* Create forum_post custom taxonomy */

function add_cpc_forum_custom_taxonomies() {

	register_taxonomy('cpc_forum', 'cpc_forum_post', array(

        'hierarchical'               => true,
		// Hierarchical taxonomy (like categories)
		'hierarchical' => true,
		// This array of options controls the labels displayed in the ClassicPress Admin UI
		'labels' => array(
			'name' 				=> __( 'Foren', 'cp-community' ),
			'singular_name' 	=> __( 'Forum', 'cp-community' ),
			'search_items' 		=> __( 'Suche Foren', 'cp-community' ),
			'all_items' 		=> __( 'Alle Foren', 'cp-community' ),
			'parent_item' 		=> __( 'Elternforum', 'cp-community' ),
			'parent_item_colon' => __( 'Elternforum:', 'cp-community' ),
			'edit_item' 		=> __( 'Forum bearbeiten', 'cp-community' ),
			'update_item' 		=> __( 'Forum aktualisieren', 'cp-community' ),
			'add_new_item' 		=> __( 'Neues Forum hinzufügen', 'cp-community' ),
			'new_item_name' 	=> __( 'Neuer Forumsname', 'cp-community' ),
			'menu_name'			=> __( 'Foren', 'cp-community' ),
		),
        // Control the slugs used for this taxonomy
		'rewrite' => array(
			'slug' => 'cpc-forums', // This controls the base slug that will display before each term
			'with_front' => false, // Don't display the category base before "/locations/"
			'hierarchical' => true // This will allow URL's like "/locations/boston/cambridge/"
		),
	));

}
add_action( 'init', 'add_cpc_forum_custom_taxonomies', 0 );


add_action("cpc_forum_add_form_fields", 'cpc_taxonomy_metadata_add', 10, 1);
function cpc_taxonomy_metadata_add( $tag ) {
	// Only allow users with capability to publish content
	if ( current_user_can( 'publish_posts' ) ): ?>
	<div class="form-field">
		<label for="cpc_forum_public"><?php _e('Sichtbarkeit', 'cp-community'); ?></label>
		<input name="cpc_forum_public" id="cpc_forum_public" type="checkbox" style="width:10px" />
		<span class="description"><?php _e('Kann dieses Forum auch ohne Anmeldung besucht werden?', 'cp-community'); ?></span>
	</div>

	<div class="form-field">
		<label for="cpc_forum_order"><?php _e('Sortierung', 'cp-community'); ?></label>
		<input type="text" name="cpc_forum_order" id="cpc_forum_order" style="width:50px" />
		<span class="description"><?php _e('Reihenfolge, in der das Forum in einer Forenliste angezeigt wird.', 'cp-community'); ?></span>
	</div> 


	<div class="form-field">
		<label for="cpc_forum_cat_page"><?php _e('ClassicPress-Seite', 'cp-community'); ?></label>
		<select name="cpc_forum_cat_page" id="cpc_forum_cat_page">
		 <?php 
		  echo '<option value="0">'.__('Seite auswählen...', 'cp-community').'</option>';
		  $pages = get_pages(); 
		  foreach ( $pages as $page ) {
		  	$option = '<option value="' . $page->ID . '">';
			$option .= $page->post_title;
			$option .= '</option>';
			echo $option;
		  }
		 ?>						
		</select>
		<?php if (current_user_can('publish_pages')) : ?>
		<?php wp_nonce_field('cpc_forum_create_page', 'cpc_forum_create_page_nonce'); ?>
		<p><label><input type="checkbox" name="cpc_forum_create_page" value="1" style="width:10px" /> <?php _e('Forumseite beim Erstellen automatisch anlegen und Shortcode einfügen', 'cp-community'); ?></label></p>
		<?php endif; ?>
		<div class="description"><?php echo sprintf(__('ClassicPress-Seite, auf der die Shortcodes dieses Forums verfügbar sind.<br />Weitere Informationen findest Du unter <a href="%s">Erste Schritte</a>.', 'cp-community'), 'admin.php?page=cpc_com'); ?></div>
		<div class="description"><?php _e('Der Forum-Slug muss mit dem Slug der Forumseite übereinstimmen. Die Forumseite kann auch eine Unterseite sein.', 'cp-community'); ?></div>
    </div> 
    <p><a href="admin.php?page=cpccom_forum_setup"><?php _e('Gehe zur Forumverwaltung', 'cp-community'); ?></a></p><br />

<?php endif;
}

add_action("cpc_forum_edit_form_fields", 'cpc_taxonomy_metadata_edit', 10, 1);
function cpc_taxonomy_metadata_edit( $tag ) {
	// Only allow users with capability to publish content
	if ( current_user_can( 'publish_posts' ) ): ?>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_public"><?php _e('Sichtbarkeit', 'cp-community'); ?></label>
		</th>
		<td>
			<input name="cpc_forum_public" id="cpc_forum_public" type="checkbox" <?php if ( cpc_get_term_meta($tag->term_id, 'cpc_forum_public', true) ) echo 'CHECKED'; ?> style="width:10px" />
			<span class="description"><?php _e('Kann dieses Forum auch ohne Anmeldung besucht werden?', 'cp-community'); ?></span>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_closed"><?php _e('Zeitraum für automatisches Schließen', 'cp-community'); ?></label>
		</th>
		<td>
			<?php
			$cpc_forum_auto_close = cpc_get_term_meta($tag->term_id, 'cpc_forum_auto_close', true) ? cpc_get_term_meta($tag->term_id, 'cpc_forum_auto_close', true) : '';
			if (!$cpc_forum_auto_close)
				$cpc_forum_auto_close = get_option( 'cpc_forum_auto_close' ) ? get_option( 'cpc_forum_auto_close' ) : '';
			?>
			<input type="text" name="cpc_forum_auto_close" id="cpc_forum_auto_close" style="width:50px" value="<?php echo $cpc_forum_auto_close; ?>" />
			<span class="description"><?php echo sprintf(__('Anzahl der Tage nach Inaktivität, in denen ein Forumsbeitrag automatisch geschlossen wird (wenn leer gelassen, wird die Standardeinstellung aus <a href="%s">Setup->Forum</a> verwendet).', 'cp-community'), admin_url( 'admin.php?page=cpc_com_setup' )); ?></span>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_closed"><?php _e('Forum sperren', 'cp-community'); ?></label>
		</th>
		<td>
			<input name="cpc_forum_closed" id="cpc_forum_closed" type="checkbox" <?php if ( cpc_get_term_meta($tag->term_id, 'cpc_forum_closed', true) ) echo 'CHECKED'; ?> style="width:10px" />
			<span class="description"><?php _e('Sperre dieses Forum und verhindere neue Beiträge und Antworten', 'cp-community'); ?></span>
		</td>
	</tr>

    <?php if (function_exists('cpc_forum_subs_extension_insert_rewrite_rules')): ?>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_auto"><?php _e('Automatisch abonnieren', 'cp-community'); ?></label>
		</th>
		<td>
			<input name="cpc_forum_auto" id="cpc_forum_auto" type="checkbox" <?php if ( cpc_get_term_meta($tag->term_id, 'cpc_forum_auto', true) ) echo 'CHECKED'; ?> style="width:10px" />
			<span class="description"><?php _e('Neue Benutzer automatisch für dieses Forum abonnieren (Benutzer können sich dann abmelden)', 'cp-community'); ?></span>
		</td>
	</tr>
    <?php endif; ?>

    <?php if (function_exists('cpc_forum_subs_extension_insert_rewrite_rules')): ?>
	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_email_all"><?php _e('Sende allen Mitgliedern eine E-Mail', 'cp-community'); ?></label>
		</th>
		<td>
			<input name="cpc_forum_email_all" id="cpc_forum_email_all" type="checkbox" <?php if ( cpc_get_term_meta($tag->term_id, 'cpc_forum_email_all', true) ) echo 'CHECKED'; ?> style="width:10px" />
			<span class="description"><?php _e('Sende immer eine E-Mail-Benachrichtigung an alle Webseiten-Mitglieder, wenn neue Themen hinzugefügt werden (kein Opt-out)', 'cp-community'); ?></span>
		</td>
	</tr>
    <?php endif; ?>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_author"><?php _e('Eigene Beiträge anzeigen', 'cp-community'); ?></label>
		</th>
		<td>
			<input name="cpc_forum_author" id="cpc_forum_author" type="checkbox" <?php if ( cpc_get_term_meta($tag->term_id, 'cpc_forum_author', true) ) echo 'CHECKED'; ?> style="width:10px" />
			<span class="description"><?php _e('Nur Beiträge des aktuellen Benutzers anzeigen (Administratoren sehen immer alle)', 'cp-community'); ?></span>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_mode"><?php _e('Forum-Modus', 'cp-community'); ?></label>
		</th>
		<td>
			<?php $mode = cpc_get_term_meta($tag->term_id, 'cpc_forum_mode', true); ?>
			<select name="cpc_forum_mode" id="cpc_forum_mode">
				<option value="discussion"<?php selected($mode, 'discussion'); ?>><?php _e('Diskussion', 'cp-community'); ?></option>
				<option value="qa"<?php selected($mode, 'qa'); ?>><?php _e('Frage und Antwort', 'cp-community'); ?></option>
			</select>
			<span class="description"><?php _e('Im Frage-und-Antwort-Modus können Themenstarter und Moderatoren Antworten akzeptieren.', 'cp-community'); ?></span>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_moderators"><?php _e('Moderatoren', 'cp-community'); ?></label>
		</th>
		<td>
			<input name="cpc_forum_moderators" id="cpc_forum_moderators" type="text" class="regular-text" value="<?php echo esc_attr(cpc_get_term_meta($tag->term_id, 'cpc_forum_moderators', true)); ?>" />
			<span class="description"><?php _e('Benutzer-IDs, durch Kommas getrennt. Moderatoren dürfen Themen und Antworten verwalten.', 'cp-community'); ?></span>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label><?php _e('Vorabmoderation', 'cp-community'); ?></label>
		</th>
		<td>
			<label><input name="cpc_forum_moderate_topic" type="checkbox" value="1" <?php checked(cpc_get_term_meta($tag->term_id, 'cpc_forum_moderate_topic', true), 1); ?> /> <?php _e('Neue Themen freigeben', 'cp-community'); ?></label><br />
			<label><input name="cpc_forum_moderate_reply" type="checkbox" value="1" <?php checked(cpc_get_term_meta($tag->term_id, 'cpc_forum_moderate_reply', true), 1); ?> /> <?php _e('Neue Antworten freigeben', 'cp-community'); ?></label>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_minimum_length"><?php _e('Beitragsschutz', 'cp-community'); ?></label>
		</th>
		<td>
			<input name="cpc_forum_minimum_length" id="cpc_forum_minimum_length" type="number" min="0" class="small-text" value="<?php echo absint(cpc_get_term_meta($tag->term_id, 'cpc_forum_minimum_length', true)); ?>" />
			<?php _e('Mindestzeichen', 'cp-community'); ?><br />
			<input name="cpc_forum_flood_interval" id="cpc_forum_flood_interval" type="number" min="0" class="small-text" value="<?php echo absint(cpc_get_term_meta($tag->term_id, 'cpc_forum_flood_interval', true)); ?>" />
			<?php _e('Sekunden Wartezeit zwischen Beiträgen (0 deaktiviert).', 'cp-community'); ?>
		</td>
	</tr>

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_order"><?php _e('Sortierung', 'cp-community'); ?></label>
		</th>
		<td>
			<input type="text" name="cpc_forum_order" id="cpc_forum_order" style="width:50px" value="<?php echo cpc_get_term_meta($tag->term_id, 'cpc_forum_order', true); ?>" />
			<span class="description"><?php _e('Reihenfolge, in der das Forum in einer Forenliste angezeigt wird.', 'cp-community'); ?></span>
		</td>
	</tr> 

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_cat_page"><?php _e('ShareThis', 'cp-community'); ?></label>
		</th>
		<td>
			<p><span class="description">
                <?php echo sprintf(__('Wähle auf <a href="%s" target="_blank">ShareThis</a> Webseiten-Code (nicht Wordpress) und kopiere die beiden unten bereitgestellten Codesätze (gilt für alle Foren). Verwende zum Anzeigen [cpc-forum-sharethis slug="xxx"].', 'cp-community'), "http://www.sharethis.com"); ?>
            </span></p>
            <?php
            $buttons = get_option('cpc_forum_sharethis_buttons') ? get_option('cpc_forum_sharethis_buttons') : '';
            $js = get_option('cpc_forum_sharethis_js') ? get_option('cpc_forum_sharethis_js') : '';
            ?>
            <p><strong><?php _e('1. Span-Tags (Code zum Anzeigen Deiner Schaltflächen)', 'cp-community'); ?></strong></p>
            <textarea style="height:100px;" name="cpc_forum_sharethis_buttons"><?php echo $buttons; ?></textarea>
            <p><strong><?php _e('2. Skript-Tags', 'cp-community'); ?></strong></p>
            <textarea style="height:100px;" name="cpc_forum_sharethis_js"><?php echo $js; ?></textarea>
		</td>
	</tr> 

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_cat_page"><?php _e('ClassicPress-Seite', 'cp-community'); ?></label>
		</th>
		<td>
			<?php 
		  	$forum_page = cpc_get_term_meta($tag->term_id, 'cpc_forum_cat_page', true);

            echo '<select name="cpc_forum_cat_page" id="cpc_forum_cat_page">';

				echo '<option value="0">'.__('Seite auswählen...', 'cp-community').'</option>';
			  	$pages = get_pages(); 
			  	foreach ( $pages as $page ):
			  		$option = '<option value="' . $page->ID . '"';
			  			if ($page->ID == $forum_page) $option .= ' SELECTED';
			  			$option .= '>';
						$option .= $page->post_title.' ('.$page->ID.')';
					$option .= '</option>';
					echo $option;
				endforeach;

			echo '</select>';
			 ?>						
			<?php if (!$forum_page && current_user_can('publish_pages')) : ?>
			<?php wp_nonce_field('cpc_forum_create_page', 'cpc_forum_create_page_nonce'); ?>
			<button type="submit" class="button-secondary" name="cpc_forum_create_page" value="1"><?php _e('Seite erstellen', 'cp-community'); ?></button>
			<?php endif; ?>
			<br />
			<span class="description"><?php _e('ClassicPress-Seite, auf der die Shortcodes dieses Forums verfügbar sind.', 'cp-community'); ?>
			<?php if ($forum_page) { ?> [<a href="post.php?post=<?php echo $forum_page; ?>&action=edit"><?php _e('Bearbeiten', 'cp-community'); ?></a>]<?php } ?></span><br />
			<p><label for="cpc_forum_shortcode"><?php _e('Shortcode für dieses Forum:', 'cp-community'); ?></label><br />
			<input id="cpc_forum_shortcode" type="text" class="regular-text" readonly value="<?php echo esc_attr(cpc_forum_page_shortcode($tag->slug)); ?>" />
			<span class="description"><?php _e('Nach einer Änderung des Forum-Slugs aktualisiert sich der Shortcode beim Speichern.', 'cp-community'); ?></span></p>
			<div class="description"><?php _e('Der Forum-Slug muss mit dem Slug der Forumseite übereinstimmen. Die Forumseite kann auch eine Unterseite sein.', 'cp-community'); ?></div>
		</td>
	</tr> 

	<tr class="form-field">
		<th scope="row" valign="top">
			<label for="cpc_forum_featured_image"><?php _e('Ausgewähltes Bild', 'cp-community'); ?></label>
		</th>
		<td>
            <input id="cpc_forum_featured_upload_image" type="text" style="width: 50%" name="cpc_forum_featured_image" value="<?php echo cpc_get_term_meta($tag->term_id, 'cpc_forum_featured_image', true); ?>" /> 
            <button id="cpc_forum_featured_upload_image_button" class="button-secondary"><?php _e('Wähle aus der Medienbibliothek', 'cp-community'); ?></button><br />
            <span class="description"><?php _e('URL eines Bildes, das, sofern festgelegt, mit dem Shortcode [cpc-forums] verwendet wird.', 'cp-community'); ?>
		</td>
	</tr> 


	<?php 

	// Any further options?
	do_action('cpc_forum_taxonomy_metadata_edit_hook', $tag);

	endif;
}


add_action("created_cpc_forum", 'cpc_save_taxonomy_metadata', 10, 1);
add_action("edited_cpc_forum", 'cpc_save_taxonomy_metadata', 10, 1);
function cpc_save_taxonomy_metadata( $term_id ) {
    
	if (isset($_POST['cpc_forum_featured_image']))
		cpc_update_term_meta( $term_id, 'cpc_forum_featured_image', $_POST['cpc_forum_featured_image'] );

    if ( isset($_POST['cpc_forum_public']) ):
		cpc_update_term_meta( $term_id, 'cpc_forum_public', true );
	else:
		cpc_update_term_meta( $term_id, 'cpc_forum_public', false );
	endif;

	if (isset($_POST['cpc_forum_cat_page']))
		cpc_update_term_meta( $term_id, 'cpc_forum_cat_page', $_POST['cpc_forum_cat_page'] );

	if (isset($_POST['cpc_forum_auto_close'])):
		cpc_update_term_meta( $term_id, 'cpc_forum_auto_close', $_POST['cpc_forum_auto_close'] );
	else:
		cpc_update_term_meta( $term_id, 'cpc_forum_auto_close', 0 );
	endif;

	if (isset($_POST['cpc_forum_closed'])):
		cpc_update_term_meta( $term_id, 'cpc_forum_closed', $_POST['cpc_forum_closed'] );
	else:
		cpc_update_term_meta( $term_id, 'cpc_forum_closed', 0 );
	endif;

    if (function_exists('cpc_forum_subs_extension_insert_rewrite_rules')):        
        if (isset($_POST['cpc_forum_auto'])):
            cpc_update_term_meta( $term_id, 'cpc_forum_auto', $_POST['cpc_forum_auto'] );
        else:
            cpc_update_term_meta( $term_id, 'cpc_forum_auto', 0 );
        endif;
    endif;

	if (isset($_POST['cpc_forum_order'])):
		cpc_update_term_meta( $term_id, 'cpc_forum_order', $_POST['cpc_forum_order'] );
	else:
		cpc_update_term_meta( $term_id, 'cpc_forum_order', 0 );
	endif;

	if (isset($_POST['cpc_forum_author'])):
		cpc_update_term_meta( $term_id, 'cpc_forum_author', $_POST['cpc_forum_author'] );
	else:
		cpc_update_term_meta( $term_id, 'cpc_forum_author', 0 );
	endif;

	$mode = isset($_POST['cpc_forum_mode']) && $_POST['cpc_forum_mode'] === 'qa' ? 'qa' : 'discussion';
	cpc_update_term_meta($term_id, 'cpc_forum_mode', $mode);
	cpc_update_term_meta($term_id, 'cpc_forum_moderators', isset($_POST['cpc_forum_moderators']) ? sanitize_text_field(wp_unslash($_POST['cpc_forum_moderators'])) : '');
	cpc_update_term_meta($term_id, 'cpc_forum_moderate_topic', isset($_POST['cpc_forum_moderate_topic']) ? 1 : 0);
	cpc_update_term_meta($term_id, 'cpc_forum_moderate_reply', isset($_POST['cpc_forum_moderate_reply']) ? 1 : 0);
	cpc_update_term_meta($term_id, 'cpc_forum_minimum_length', isset($_POST['cpc_forum_minimum_length']) ? absint($_POST['cpc_forum_minimum_length']) : 0);
	cpc_update_term_meta($term_id, 'cpc_forum_flood_interval', isset($_POST['cpc_forum_flood_interval']) ? absint($_POST['cpc_forum_flood_interval']) : 0);

	if (isset($_POST['cpc_forum_email_all'])):
		cpc_update_term_meta( $term_id, 'cpc_forum_email_all', $_POST['cpc_forum_email_all'] );
	else:
		cpc_update_term_meta( $term_id, 'cpc_forum_email_all', 0 );
	endif;

    $buttons = isset($_POST['cpc_forum_sharethis_buttons']) ? stripslashes($_POST['cpc_forum_sharethis_buttons']) : false;
    $js = isset($_POST['cpc_forum_sharethis_js']) ? stripslashes($_POST['cpc_forum_sharethis_js']) : false;
    update_option( 'cpc_forum_sharethis_buttons', $buttons );
    update_option( 'cpc_forum_sharethis_js', $js );
        
	// Any further options to save?
	do_action('cpc_forum_taxonomy_metadata_edit_roles_save_hook', $term_id, $_POST);

	if (isset($_POST['cpc_forum_create_page'])) {
		if (!current_user_can('edit_term', $term_id) || !current_user_can('publish_pages') || !isset($_POST['cpc_forum_create_page_nonce']) || !wp_verify_nonce(wp_unslash($_POST['cpc_forum_create_page_nonce']), 'cpc_forum_create_page')) {
			wp_die(esc_html__('Die Forumseite konnte nicht erstellt werden: Berechtigung oder Sicherheitsprüfung fehlgeschlagen.', 'cp-community'), '', array('back_link' => true));
		}
		$page_id = cpc_forum_create_page($term_id);
		if (is_wp_error($page_id)) {
			$message = sprintf(__('Das Forum wurde gespeichert, aber die Forumseite nicht zugeordnet: %s', 'cp-community'), $page_id->get_error_message());
			if (wp_doing_ajax()) {
				$response = new WP_Ajax_Response(array('what' => 'taxonomy', 'id' => new WP_Error('cpc_forum_page_error', $message)));
				$response->send();
			}
			wp_die(esc_html($message), '', array('back_link' => true));
		}
	}

	// Ready for re-writing
	global $wp_rewrite;
   	$wp_rewrite->flush_rules();

}

/* Add notice to forum setup */
function cpc_save_taxonomy_metadata_notice(){
    $screen = get_current_screen();
    
    if( $screen->id !='edit-cpc_forum' )
        return;

	echo '<style>#message { display: none; }</style>';

	echo '<div class="cpc_success" style="margin-top:24px;">&larr; ';
		echo sprintf('<a href="%s">', admin_url( 'admin.php?page=cpccom_forum_setup' )).__('Zurück zu Alle Foren verwalten.', 'cp-community').'</a>';
	echo '</div>';
    
    echo '<p style="font-weight:bold">'.__('Wähle für jedes Forum die Seite mit dem passenden Forum-Shortcode. Der Forum-Slug muss dem Seiten-Slug entsprechen; die Seite kann auch eine Unterseite sein.', 'cp-community').'</p>';
    

}
add_action('admin_notices','cpc_save_taxonomy_metadata_notice');

/* Add filter to posts screen */

if (!class_exists('cpc_Tax_CTP_Filter')){

  	/**
    * Tax CTP Filter Class 
    * Simple class to add custom taxonomy dropdown to a custom post type admin edit list
    * @version 0.1
    */
    class cpc_Tax_CTP_Filter {

		private $cpt;

        /**
         * __construct 
         * @since 0.1
         * @param array $cpt [description]
         */
        function __construct($cpt = array()){
            $this->cpt = $cpt;
            // Adding a Taxonomy Filter to Admin List for a Custom Post Type
            add_action( 'restrict_manage_posts', array($this,'my_restrict_manage_posts' ));
        }
  
        /**
         * my_restrict_manage_posts  add the slelect dropdown per taxonomy
         * @since 0.1
         * @return void
         */
        public function my_restrict_manage_posts() {
            // only display these taxonomy filters on desired custom post_type listings
            global $typenow;
            $types = array_keys($this->cpt);
            if (in_array($typenow, $types)) {
                // create an array of taxonomy slugs you want to filter by - if you want to retrieve all taxonomies, could use get_taxonomies() to build the list
                $filters = $this->cpt[$typenow];
                foreach ($filters as $tax_slug) {
                    // retrieve the taxonomy object
                    $tax_obj = get_taxonomy($tax_slug);
                    $tax_name = $tax_obj->labels->name;
  
                    // output html for taxonomy dropdown filter
                    echo "<select name='".strtolower($tax_slug)."' id='".strtolower($tax_slug)."' class='postform'>";
                    echo "<option value=''>Show All $tax_name</option>";
                    $this->generate_taxonomy_options($tax_slug,0,0,(isset($_GET[strtolower($tax_slug)])? $_GET[strtolower($tax_slug)] : null));
                    echo "</select>";
                }
            }
        }
         
        /**
         * generate_taxonomy_options generate dropdown
         * @since 0.1
         * @param  string  $tax_slug 
         * @param  string  $parent   
         * @param  integer $level    
         * @param  string  $selected 
         * @return void            
         */
        public function generate_taxonomy_options($tax_slug, $parent = '', $level = 0,$selected = null) {
            $args = array('show_empty' => 1);
            if(!is_null($parent)) {
                $args = array('parent' => $parent);
            }
            $terms = get_terms($tax_slug,$args);
            $tab='';
            for($i=0;$i<$level;$i++){
                $tab.='--';
            }
  
            foreach ($terms as $term) {
                // output each select option line, check against the last $_GET to show the current option selected
                echo '<option value='. $term->slug, $selected == $term->slug ? ' selected="selected"' : '','>' .$tab. $term->name .' (' . $term->count .')</option>';
                $this->generate_taxonomy_options($tax_slug, $term->term_id, $level+1,$selected);
            }
  
        }
    }//end class
}//end if
new cpc_Tax_CTP_Filter(array('cpc_forum_post' => array('cpc_forum')));



?>