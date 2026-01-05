<?php

/* Group Admin Settings and Help */

// Add admin help page
add_action('admin_menu', 'cpc_groups_admin_menu');
function cpc_groups_admin_menu() {
	add_submenu_page(
		'cpc_com',
		__('Gruppen Verwaltung', CPC2_TEXT_DOMAIN),
		__('Gruppen Setup', CPC2_TEXT_DOMAIN),
		'manage_options',
		'cpccom_groups_setup',
		'cpc_groups_admin_page'
	);
}

function cpc_groups_admin_page() {
	?>
	<div class="wrap">
		<h1><?php _e('Gruppen Verwaltung', CPC2_TEXT_DOMAIN); ?></h1>

		<div class="cpc-admin-help">
			<h2><?php _e('Verfügbare Shortcodes', CPC2_TEXT_DOMAIN); ?></h2>
			
			<h3>[cpc-groups]</h3>
			<p><?php _e('Zeigt eine Liste aller Gruppen an.', CPC2_TEXT_DOMAIN); ?></p>
			<p><strong><?php _e('Parameter:', CPC2_TEXT_DOMAIN); ?></strong></p>
			<ul>
				<li><code>type</code> - all|public|private|hidden (Standard: all)</li>
				<li><code>columns</code> - Anzahl der Spalten (Standard: 2)</li>
				<li><code>show_avatar</code> - true|false (Standard: true)</li>
				<li><code>show_description</code> - true|false (Standard: true)</li>
				<li><code>show_member_count</code> - true|false (Standard: true)</li>
				<li><code>show_join_button</code> - true|false (Standard: true)</li>
				<li><code>search</code> - true|false (Standard: true)</li>
			</ul>
			<p><strong><?php _e('Beispiel:', CPC2_TEXT_DOMAIN); ?></strong> <code>[cpc-groups type="public" columns="3"]</code></p>

			<h3>[cpc-group-single]</h3>
			<p><?php _e('Zeigt Details einer einzelnen Gruppe an.', CPC2_TEXT_DOMAIN); ?></p>
			<p><strong><?php _e('Parameter:', CPC2_TEXT_DOMAIN); ?></strong></p>
			<ul>
				<li><code>group_id</code> - ID der Gruppe (optional, verwendet automatisch aktuelle Gruppe)</li>
				<li><code>show_avatar</code> - true|false (Standard: true)</li>
				<li><code>show_description</code> - true|false (Standard: true)</li>
				<li><code>show_members</code> - true|false (Standard: true)</li>
				<li><code>show_actions</code> - true|false (Standard: true)</li>
			</ul>
			<p><strong><?php _e('Beispiel:', CPC2_TEXT_DOMAIN); ?></strong> <code>[cpc-group-single]</code></p>

			<h3>[cpc-group-members]</h3>
			<p><?php _e('Zeigt die Mitglieder einer Gruppe an.', CPC2_TEXT_DOMAIN); ?></p>
			<p><strong><?php _e('Parameter:', CPC2_TEXT_DOMAIN); ?></strong></p>
			<ul>
				<li><code>group_id</code> - ID der Gruppe (optional)</li>
				<li><code>role</code> - admin|moderator|member (optional)</li>
				<li><code>columns</code> - Anzahl der Spalten (Standard: 4)</li>
				<li><code>show_avatar</code> - true|false (Standard: true)</li>
				<li><code>show_role</code> - true|false (Standard: true)</li>
			</ul>
			<p><strong><?php _e('Beispiel:', CPC2_TEXT_DOMAIN); ?></strong> <code>[cpc-group-members columns="3"]</code></p>

			<h3>[cpc-my-groups]</h3>
			<p><?php _e('Zeigt die Gruppen des aktuell angemeldeten Benutzers an.', CPC2_TEXT_DOMAIN); ?></p>
			<p><strong><?php _e('Parameter:', CPC2_TEXT_DOMAIN); ?></strong></p>
			<ul>
				<li><code>columns</code> - Anzahl der Spalten (Standard: 3)</li>
				<li><code>show_avatar</code> - true|false (Standard: true)</li>
				<li><code>show_role</code> - true|false (Standard: true)</li>
			</ul>
			<p><strong><?php _e('Beispiel:', CPC2_TEXT_DOMAIN); ?></strong> <code>[cpc-my-groups columns="2"]</code></p>

			<h3>[cpc-group-create]</h3>
			<p><?php _e('Zeigt ein Formular zum Erstellen einer neuen Gruppe an.', CPC2_TEXT_DOMAIN); ?></p>
			<p><strong><?php _e('Parameter:', CPC2_TEXT_DOMAIN); ?></strong></p>
			<ul>
				<li><code>redirect</code> - URL zur Weiterleitung nach Erstellung (optional)</li>
			</ul>
			<p><strong><?php _e('Beispiel:', CPC2_TEXT_DOMAIN); ?></strong> <code>[cpc-group-create]</code></p>

			<h3>[cpc-group-join-button]</h3>
			<p><?php _e('Zeigt einen Beitreten/Verlassen-Button an.', CPC2_TEXT_DOMAIN); ?></p>
			<p><strong><?php _e('Parameter:', CPC2_TEXT_DOMAIN); ?></strong></p>
			<ul>
				<li><code>group_id</code> - ID der Gruppe (optional)</li>
				<li><code>join_text</code> - Text für Beitreten-Button</li>
				<li><code>leave_text</code> - Text für Verlassen-Button</li>
			</ul>
			<p><strong><?php _e('Beispiel:', CPC2_TEXT_DOMAIN); ?></strong> <code>[cpc-group-join-button]</code></p>

			<h2><?php _e('Gruppentypen', CPC2_TEXT_DOMAIN); ?></h2>
			<ul>
				<li><strong><?php _e('Öffentlich:', CPC2_TEXT_DOMAIN); ?></strong> <?php _e('Jeder kann die Gruppe sehen und direkt beitreten.', CPC2_TEXT_DOMAIN); ?></li>
				<li><strong><?php _e('Privat:', CPC2_TEXT_DOMAIN); ?></strong> <?php _e('Jeder kann die Gruppe sehen, aber Beitritt muss genehmigt werden.', CPC2_TEXT_DOMAIN); ?></li>
				<li><strong><?php _e('Versteckt:', CPC2_TEXT_DOMAIN); ?></strong> <?php _e('Nur Mitglieder können die Gruppe sehen und Inhalte einsehen.', CPC2_TEXT_DOMAIN); ?></li>
			</ul>

			<h2><?php _e('Erste Schritte', CPC2_TEXT_DOMAIN); ?></h2>
			<ol>
				<li><?php _e('Erstelle eine WordPress-Seite für die Gruppenliste und füge den Shortcode [cpc-groups] hinzu.', CPC2_TEXT_DOMAIN); ?></li>
				<li><?php _e('Erstelle eine Seite zum Erstellen von Gruppen und füge [cpc-group-create] hinzu.', CPC2_TEXT_DOMAIN); ?></li>
				<li><?php _e('Optional: Erstelle eine Seite für "Meine Gruppen" mit [cpc-my-groups].', CPC2_TEXT_DOMAIN); ?></li>
				<li><?php _e('Einzelne Gruppen werden automatisch auf ihrer eigenen Seite angezeigt.', CPC2_TEXT_DOMAIN); ?></li>
			</ol>

			<h2><?php _e('Statistiken', CPC2_TEXT_DOMAIN); ?></h2>
			<?php
			$total_groups = wp_count_posts('cpc_group');
			$total_memberships = wp_count_posts('cpc_group_members');
			
			$public_groups = new WP_Query(array(
				'post_type' => 'cpc_group',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => 'cpc_group_type',
						'value' => 'public',
					),
				),
			));

			$private_groups = new WP_Query(array(
				'post_type' => 'cpc_group',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => 'cpc_group_type',
						'value' => 'private',
					),
				),
			));

			$hidden_groups = new WP_Query(array(
				'post_type' => 'cpc_group',
				'posts_per_page' => -1,
				'meta_query' => array(
					array(
						'key' => 'cpc_group_type',
						'value' => 'hidden',
					),
				),
			));
			?>
			<table class="widefat">
				<tr>
					<td><?php _e('Gesamte Gruppen:', CPC2_TEXT_DOMAIN); ?></td>
					<td><strong><?php echo $total_groups->publish; ?></strong></td>
				</tr>
				<tr>
					<td><?php _e('Öffentliche Gruppen:', CPC2_TEXT_DOMAIN); ?></td>
					<td><strong><?php echo $public_groups->found_posts; ?></strong></td>
				</tr>
				<tr>
					<td><?php _e('Private Gruppen:', CPC2_TEXT_DOMAIN); ?></td>
					<td><strong><?php echo $private_groups->found_posts; ?></strong></td>
				</tr>
				<tr>
					<td><?php _e('Versteckte Gruppen:', CPC2_TEXT_DOMAIN); ?></td>
					<td><strong><?php echo $hidden_groups->found_posts; ?></strong></td>
				</tr>
				<tr>
					<td><?php _e('Gesamte Mitgliedschaften:', CPC2_TEXT_DOMAIN); ?></td>
					<td><strong><?php echo $total_memberships->publish; ?></strong></td>
				</tr>
			</table>

			<br>
			<p>
				<a href="<?php echo admin_url('edit.php?post_type=cpc_group'); ?>" class="button button-primary"><?php _e('Gruppen verwalten', CPC2_TEXT_DOMAIN); ?></a>
				<a href="<?php echo admin_url('post-new.php?post_type=cpc_group'); ?>" class="button"><?php _e('Neue Gruppe erstellen', CPC2_TEXT_DOMAIN); ?></a>
				<a href="<?php echo admin_url('edit.php?post_type=cpc_group_members'); ?>" class="button"><?php _e('Mitgliedschaften verwalten', CPC2_TEXT_DOMAIN); ?></a>
			</p>
		</div>
	</div>
	<?php
}

// Add settings to main admin page if needed
add_filter('cpc_admin_settings_sections', 'cpc_groups_add_settings_section');
function cpc_groups_add_settings_section($sections) {
	$sections['groups'] = __('Gruppen', CPC2_TEXT_DOMAIN);
	return $sections;
}

add_filter('cpc_admin_settings_fields', 'cpc_groups_add_settings_fields');
function cpc_groups_add_settings_fields($fields) {
	$fields['groups'] = array(
		'cpc_groups_allow_creation' => array(
			'title' => __('Gruppenerstellung erlauben', CPC2_TEXT_DOMAIN),
			'type' => 'checkbox',
			'description' => __('Erlaubt registrierten Benutzern, eigene Gruppen zu erstellen.', CPC2_TEXT_DOMAIN),
			'default' => true,
		),
		'cpc_groups_require_approval' => array(
			'title' => __('Gruppengenehmigung erforderlich', CPC2_TEXT_DOMAIN),
			'type' => 'checkbox',
			'description' => __('Neue Gruppen müssen von einem Admin genehmigt werden, bevor sie veröffentlicht werden.', CPC2_TEXT_DOMAIN),
			'default' => false,
		),
	);
	return $fields;
}

// Add to Getting Started information
add_action('cpc_admin_getting_started_hook', 'cpc_admin_getting_started_groups', 5);
function cpc_admin_getting_started_groups() {

    $css = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_groups' ? 'cpc_admin_getting_started_menu_item_remove_icon ' : '';    
  	echo '<div class="'.$css.'cpc_admin_getting_started_menu_item" rel="cpc_admin_getting_started_groups" id="cpc_admin_getting_started_groups_div">'.__('Gruppen', CPC2_TEXT_DOMAIN).'</div>';

  	$display = isset($_POST['cpc_expand']) && $_POST['cpc_expand'] == 'cpc_admin_getting_started_groups' ? 'block' : 'none';
  	echo '<div class="cpc_admin_getting_started_content" id="cpc_admin_getting_started_groups" style="display:'.$display.'">';

		?>
		<table class="form-table">
		<tr valign="top"> 
			<td scope="row"><label for="groups_page"><?php echo __('Gruppen-Übersichtsseite', CPC2_TEXT_DOMAIN); ?></label></td>
			<td>
				<select name="groups_page">
				 <?php 
				  $groups_page = get_option('cpccom_groups_page');
				  if (!$groups_page) echo '<option value="0">'.__('Seite auswählen...', CPC2_TEXT_DOMAIN).'</option>';
				  if ($groups_page) echo '<option value="0">'.__('Zurücksetzen...', CPC2_TEXT_DOMAIN).'</option>';						
				  $pages = get_pages(); 
				  foreach ( $pages as $page ) {
				  	$option = '<option value="' . $page->ID . '"';
				  		if ($page->ID == $groups_page) $option .= ' SELECTED';
				  		$option .= '>';
					$option .= $page->post_title;
					$option .= '</option>';
					echo $option;
				  }
				 ?>						
				</select>
				<span class="description"><?php echo __('ClassicPress-Seite, die alle Gruppen anzeigt (automatisch: [cpc-groups]).', CPC2_TEXT_DOMAIN); ?>
				<?php if ($groups_page) {
					echo ' [<a href="post.php?post='.$groups_page.'&action=edit">'.__('bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
					echo '|<a href="'.get_permalink($groups_page).'">'.__('ansehen', CPC2_TEXT_DOMAIN).'</a>]';
				}
				?>
				</span></td> 
		</tr>

		<tr valign="top"> 
			<td scope="row"><label for="group_single_page"><?php echo __('Einzelgruppen-Seite', CPC2_TEXT_DOMAIN); ?></label></td>
			<td>
				<select name="group_single_page">
				 <?php 
				  $single_page = get_option('cpccom_group_single_page');
				  if (!$single_page) echo '<option value="0">'.__('Seite auswählen...', CPC2_TEXT_DOMAIN).'</option>';
				  if ($single_page) echo '<option value="0">'.__('Zurücksetzen...', CPC2_TEXT_DOMAIN).'</option>';						
				  $pages = get_pages(); 
				  foreach ( $pages as $page ) {
				  	$option = '<option value="' . $page->ID . '"';
				  		if ($page->ID == $single_page) $option .= ' SELECTED';
				  		$option .= '>';
					$option .= $page->post_title;
					$option .= '</option>';
					echo $option;
				  }
				 ?>						
				</select>
				<span class="description"><?php echo __('ClassicPress-Seite für einzelne Gruppenansicht (automatisch: [cpc-group-single]).', CPC2_TEXT_DOMAIN); ?>
				<?php if ($single_page) {
					echo ' [<a href="post.php?post='.$single_page.'&action=edit">'.__('bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
					echo '|<a href="'.get_permalink($single_page).'">'.__('ansehen', CPC2_TEXT_DOMAIN).'</a>]';
				}
				?>
				</span></td> 
		</tr>

		<tr valign="top"> 
			<td scope="row"><label for="group_create_page"><?php echo __('Gruppe erstellen Seite', CPC2_TEXT_DOMAIN); ?></label></td>
			<td>
				<select name="group_create_page">
				 <?php 
				  $create_page = get_option('cpccom_group_create_page');
				  if (!$create_page) echo '<option value="0">'.__('Seite auswählen...', CPC2_TEXT_DOMAIN).'</option>';
				  if ($create_page) echo '<option value="0">'.__('Zurücksetzen...', CPC2_TEXT_DOMAIN).'</option>';						
				  $pages = get_pages(); 
				  foreach ( $pages as $page ) {
				  	$option = '<option value="' . $page->ID . '"';
				  		if ($page->ID == $create_page) $option .= ' SELECTED';
				  		$option .= '>';
					$option .= $page->post_title;
					$option .= '</option>';
					echo $option;
				  }
				 ?>						
				</select>
				<span class="description"><?php echo __('ClassicPress-Seite zum Erstellen neuer Gruppen (automatisch: [cpc-group-create]).', CPC2_TEXT_DOMAIN); ?>
				<?php if ($create_page) {
					echo ' [<a href="post.php?post='.$create_page.'&action=edit">'.__('bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
					echo '|<a href="'.get_permalink($create_page).'">'.__('ansehen', CPC2_TEXT_DOMAIN).'</a>]';
				}
				?>
				</span></td> 
		</tr>

		<tr valign="top"> 
			<td scope="row"><label for="my_groups_page"><?php echo __('Meine Gruppen Seite', CPC2_TEXT_DOMAIN); ?></label></td>
			<td>
				<select name="my_groups_page">
				 <?php 
				  $my_page = get_option('cpccom_my_groups_page');
				  if (!$my_page) echo '<option value="0">'.__('Seite auswählen...', CPC2_TEXT_DOMAIN).'</option>';
				  if ($my_page) echo '<option value="0">'.__('Zurücksetzen...', CPC2_TEXT_DOMAIN).'</option>';						
				  $pages = get_pages(); 
				  foreach ( $pages as $page ) {
				  	$option = '<option value="' . $page->ID . '"';
				  		if ($page->ID == $my_page) $option .= ' SELECTED';
				  		$option .= '>';
					$option .= $page->post_title;
					$option .= '</option>';
					echo $option;
				  }
				 ?>						
				</select>
				<span class="description"><?php echo __('ClassicPress-Seite für Benutzer-Gruppen (automatisch: [cpc-my-groups]).', CPC2_TEXT_DOMAIN); ?>
				<?php if ($my_page) {
					echo ' [<a href="post.php?post='.$my_page.'&action=edit">'.__('bearbeiten', CPC2_TEXT_DOMAIN).'</a>';
					echo '|<a href="'.get_permalink($my_page).'">'.__('ansehen', CPC2_TEXT_DOMAIN).'</a>]';
				}
				?>
				</span></td> 
		</tr>

		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_groups_allow_creation"><?php _e('Gruppenerstellung erlauben', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<input type="checkbox" name="cpc_groups_allow_creation" 
				<?php if (get_option('cpc_groups_allow_creation', true)) echo ' CHECKED'; ?>
				/>
				<span class="description"><?php _e('Erlaubt registrierten Benutzern, eigene Gruppen zu erstellen.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_groups_allow_creation"><?php _e('Gruppenerstellung erlauben', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<input type="checkbox" name="cpc_groups_allow_creation" 
				<?php if (get_option('cpc_groups_allow_creation', true)) echo ' CHECKED'; ?>
				/>
				<span class="description"><?php _e('Erlaubt registrierten Benutzern, eigene Gruppen zu erstellen.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_groups_require_approval"><?php _e('Gruppengenehmigung erforderlich', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<input type="checkbox" name="cpc_groups_require_approval" 
				<?php if (get_option('cpc_groups_require_approval')) echo ' CHECKED'; ?>
				/>
				<span class="description"><?php _e('Neue Gruppen müssen von einem Admin genehmigt werden, bevor sie veröffentlicht werden.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_groups_default_type"><?php _e('Standard-Gruppentyp', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<?php $default_type = get_option('cpc_groups_default_type', 'public'); ?>
				<select name="cpc_groups_default_type" id="cpc_groups_default_type">
					<option value="public" <?php selected($default_type, 'public'); ?>><?php _e('Öffentlich', CPC2_TEXT_DOMAIN); ?></option>
					<option value="private" <?php selected($default_type, 'private'); ?>><?php _e('Privat', CPC2_TEXT_DOMAIN); ?></option>
					<option value="hidden" <?php selected($default_type, 'hidden'); ?>><?php _e('Versteckt', CPC2_TEXT_DOMAIN); ?></option>
				</select>
				<span class="description"><?php _e('Standard-Sichtbarkeit für neue Gruppen.', CPC2_TEXT_DOMAIN); ?></span>
			</td>
		</tr>
		<tr class="form-field">
			<td scope="row" valign="top">
				<label for="cpc_groups_slug_length"><?php _e('Slug Länge', CPC2_TEXT_DOMAIN); ?></label>
			</td>
			<td>
				<?php $cpc_groups_slug_length = get_option('cpc_groups_slug_length') ? get_option('cpc_groups_slug_length') : 50; ?>
				<input type="text" style="width:50px" name="cpc_groups_slug_length" value="<?php echo $cpc_groups_slug_length; ?>" /> 
				<span class="description"><?php echo __('Maximale Länge für Gruppentitel in URLs.', CPC2_TEXT_DOMAIN) ; ?></span>
			</td>
		</tr>
		<?php 
				do_action('cpc_admin_getting_started_groups_hook');
		?>
		</table>
        <?php

	echo '</div>';
	
}

// Save groups settings
add_action('cpc_admin_setup_form_save_hook', 'cpc_admin_getting_started_groups_save', 20, 2);
function cpc_admin_getting_started_groups_save($the_post) {
	
	// Seitenauswahl speichern
	if (isset($the_post['groups_page'])):
		update_option('cpccom_groups_page', $the_post['groups_page']);
	else:
		delete_option('cpccom_groups_page');
	endif;

	if (isset($the_post['group_single_page'])):
		update_option('cpccom_group_single_page', $the_post['group_single_page']);
	else:
		delete_option('cpccom_group_single_page');
	endif;

	if (isset($the_post['group_create_page'])):
		update_option('cpccom_group_create_page', $the_post['group_create_page']);
	else:
		delete_option('cpccom_group_create_page');
	endif;

	if (isset($the_post['my_groups_page'])):
		update_option('cpccom_my_groups_page', $the_post['my_groups_page']);
	else:
		delete_option('cpccom_my_groups_page');
	endif;

	// Andere Einstellungen
	if (isset($the_post['cpc_groups_allow_creation'])):
		update_option('cpc_groups_allow_creation', true);
	else:
		delete_option('cpc_groups_allow_creation');
	endif;

	if (isset($the_post['cpc_groups_require_approval'])):
		update_option('cpc_groups_require_approval', true);
	else:
		delete_option('cpc_groups_require_approval');
	endif;

	if (isset($the_post['cpc_groups_default_type'])):
		update_option('cpc_groups_default_type', $the_post['cpc_groups_default_type']);
	endif;

	if (isset($the_post['cpc_groups_slug_length'])):
		update_option('cpc_groups_slug_length', $the_post['cpc_groups_slug_length']);
	endif;

}
?><?php
// Quick Start - automatische Seitenerstellung
add_action('cpc_admin_quick_start_hook', 'cpc_admin_quick_start_groups');
function cpc_admin_quick_start_groups() {

	global $wpdb;
	$sql = "SELECT * FROM ".$wpdb->prefix."posts WHERE post_content LIKE '%s'";
	if (!($wpdb->get_results($wpdb->prepare($sql, '%[cpc-groups%')))):

		echo '<div style="margin-right:10px; float:left">';
		echo '<form action="" method="POST">';
		echo '<input type="hidden" name="cpccom_quick_start" value="groups" />';
		echo '<input type="submit" class="button-secondary" value="'.__('Gruppenseiten hinzufügen', CPC2_TEXT_DOMAIN).'" />';
		echo '</form></div>';

	endif;
}

add_action('cpc_admin_quick_start_form_save_hook', 'cpc_admin_quick_start_groups_save', 10, 1);
function cpc_admin_quick_start_groups_save($the_post) {

	if (isset($the_post['cpccom_quick_start']) && $the_post['cpccom_quick_start'] == 'groups'):

		// Groups Overview Page
		$post_content = '['.CPC_PREFIX.'-groups]';

		$post = array(
		  'post_content'   => $post_content,
		  'post_name'      => 'groups',
		  'post_title'     => __('Gruppen', CPC2_TEXT_DOMAIN),
		  'post_status'    => 'publish',
		  'post_type'      => 'page',
		  'ping_status'    => 'closed',
		  'comment_status' => 'closed',
		);  

		$new_id = wp_insert_post( $post );
		update_option('cpccom_groups_page', $new_id);

		// Group Single Page
		$post = array(
		  'post_content'   => '['.CPC_PREFIX.'-group-single]',
		  'post_name'      => 'group',
		  'post_title'     => __('Gruppe', CPC2_TEXT_DOMAIN),
		  'post_status'    => 'publish',
		  'post_type'      => 'page',
		  'ping_status'    => 'closed',
		  'comment_status' => 'closed',
		);  

		$new_id = wp_insert_post( $post );
		update_option('cpccom_group_single_page', $new_id);

		// Create Group Page
		$post = array(
		  'post_content'   => '['.CPC_PREFIX.'-group-create]',
		  'post_name'      => 'create-group',
		  'post_title'     => __('Gruppe erstellen', CPC2_TEXT_DOMAIN),
		  'post_status'    => 'publish',
		  'post_type'      => 'page',
		  'ping_status'    => 'closed',
		  'comment_status' => 'closed',
		);  

		$new_id = wp_insert_post( $post );
		update_option('cpccom_group_create_page', $new_id);

		// My Groups Page
		$post = array(
		  'post_content'   => '['.CPC_PREFIX.'-my-groups]',
		  'post_name'      => 'my-groups',
		  'post_title'     => __('Meine Gruppen', CPC2_TEXT_DOMAIN),
		  'post_status'    => 'publish',
		  'post_type'      => 'page',
		  'ping_status'    => 'closed',
		  'comment_status' => 'closed',
		);  

		$new_id = wp_insert_post( $post );
		update_option('cpccom_my_groups_page', $new_id);

	endif;
}
?>