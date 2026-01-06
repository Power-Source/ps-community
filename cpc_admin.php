<?php
// Admin dependencies
add_action('admin_enqueue_scripts', 'cpc_usermeta_admin_init');

// Load integration modules
require_once(plugin_dir_path(__FILE__) . 'lib/psource_chat_integration.php');

function cpc_usermeta_admin_init() {
    wp_enqueue_style('wp-color-picker');
    wp_enqueue_script('cpc-usermeta-js', plugins_url('usermeta/cpc_usermeta.js?rndstr='.strval(time()), __FILE__), array('wp-color-picker'));
}

if (isset($_GET['cpc_commo']) && $_GET['cpc_commo'] == 'close') {
	// dismiss promo
	update_option('cpc_commo_hide', true);
}
if (isset($_GET['cpc_commo']) && $_GET['cpc_commo'] == 'reset') {
	// reset promo (show again)
	delete_option('cpc_commo_hide');
}

function cpc_menu() {
	
    $admin_favs = get_option('cpc_admin_favs');

	$menu_label = (defined('CPC_MENU')) ? CPC_MENU : 'PS Community';
	add_menu_page($menu_label, $menu_label, 'manage_options', 'cpc_com', 'cpccom_setup', 'none'); 
	add_submenu_page('cpc_com', __('Versionshinweise', CPC2_TEXT_DOMAIN), __('Versionshinweise', CPC2_TEXT_DOMAIN), 'manage_options', 'cpc_com_release_notes', 'cpccom_release_notes');
	add_submenu_page('cpc_com', __('Einstellungen', CPC2_TEXT_DOMAIN), __('Einstellungen', CPC2_TEXT_DOMAIN), 'manage_options', 'cpc_com_setup', 'cpccom_setup');
	add_submenu_page('cpc_com', __('Shortcodes', CPC2_TEXT_DOMAIN), __('Shortcodes', CPC2_TEXT_DOMAIN), 'manage_options', 'cpc_com_shortcodes', 'cpc_com_shortcodes');
	add_submenu_page('cpc_com', __('Styles (BETA)', CPC2_TEXT_DOMAIN), __('Styles (BETA)', CPC2_TEXT_DOMAIN), 'manage_options', 'cpc_com_styles', 'cpc_com_styles');
	add_submenu_page(get_option('cpc_core_admin_icons') ? 'cpc_com' : '', __('Benutzerdefiniertes CSS', CPC2_TEXT_DOMAIN), __('Benutzerdefiniertes CSS', CPC2_TEXT_DOMAIN), 'manage_options', 'cpc_com_custom_css', 'cpccom_custom_css');
	add_submenu_page(get_option('cpc_core_admin_icons') ? 'cpc_com' : '', __('Lösche alle CPC-Daten', CPC2_TEXT_DOMAIN), __('Lösche CPC-Daten', CPC2_TEXT_DOMAIN), 'manage_options', 'cpc_com_reset', 'cpc_com_reset');
	add_submenu_page('cpc_com', __('Übersetzungen', CPC2_TEXT_DOMAIN), __('Übersetzungen', CPC2_TEXT_DOMAIN), 'manage_options', 'cpc_com_translations', 'cpc_com_translations');
	add_submenu_page('cpc_com', __('Integrationen', CPC2_TEXT_DOMAIN), __('Integrationen', CPC2_TEXT_DOMAIN), 'manage_options', 'cpc_integrations', 'cpc_integrations_page');
    
    add_submenu_page(get_option('cpc_core_admin_icons') ? 'cpc_com' : '', __('Benachrichtigungen (pro Benutzer)', CPC2_TEXT_DOMAIN), __('Benachrichtigungen (pro Benutzer)', CPC2_TEXT_DOMAIN), 'manage_options', 'cpc_alerts_per_user', 'cpc_alerts_per_user');
    
	remove_submenu_page('cpc_com','cpc_com');

    // Add any favourites to CPC admin menu
    add_action('admin_menu', 'cpc_add_external_link_admin_submenu');    

    
    // Need to set last posts/replies for each forum?  
    if (!get_option('cpc_forum_meta_update_all')):
	
        // get all forums
        $terms = get_terms( "cpc_forum", array(
            'hide_empty'    => false, 
            'fields'        => 'all', 
            'hierarchical'  => false, 
        ) );

        if ( count($terms) > 0 && current_user_can('manage_options')):

            global $post;

            foreach ( $terms as $term ):
    
                // .. for each forum

                $term_id = $term->term_id;
                $name = $term->name;
                $slug = $term->slug;
                $term_children = get_term_children($term_id, 'cpc_forum');
    
                // .. get latest post in that forum
                $loop = new WP_Query( array(
                    'post_type' => 'cpc_forum_post',
                    'posts_per_page' => 1,
                    'no_found_rows' => true,
                    'nopaging' => false,
                    'orderby' => 'ID',
                    'order'   => 'DESC',                    
                    'tax_query' => array(
                        'relation' => 'AND',
                        array(
                            'taxonomy' => 'cpc_forum',
                            'field' => 'slug',
                            'terms' => $slug,
                        ),
                        array( 
                            'taxonomy' => 'cpc_forum',
                            'field' => 'id',
                            'terms' => $term_children,
                            'operator' => 'NOT IN'
                            )
                        ),
                ) );
        
                if ($loop->have_posts()):
                    while ( $loop->have_posts() ) : $loop->the_post();
                        // set meta for this post at the forum level
                        cpc_update_term_meta($term_id, 'cpc_last_post_id', $post->ID);
                        cpc_update_term_meta($term_id, 'cpc_last_post_created', $post->post_date);
                        cpc_update_term_meta($term_id, 'cpc_last_post_created_gmt', $post->post_date_gmt);
                        cpc_update_term_meta($term_id, 'cpc_last_post_author', $post->post_author);    
                    endwhile;
                endif;

            endforeach;
    
            update_option('cpc_forum_meta_update_all', true);
    
            echo '<div class="error"><p>'.__('PS Community-Update erfolgreich.', CPC2_TEXT_DOMAIN).'</p></div>';    
    
        endif;
    
    endif;

}



function cpc_add_external_link_admin_submenu() {
    global $submenu;

    // Any admin favourites?
    $admin_favs = get_option('cpc_admin_favs');
    if (is_array($admin_favs)):
        // Configure
        if (in_array('custom_css', $admin_favs))
            $submenu['cpc_com'][] = array( 'Custom CSS', 'manage_options', admin_url('admin.php?page=cpc_com_custom_css') );
        if (in_array('profile_extensions', $admin_favs))
            $submenu['cpc_com'][] = array( 'Profile Extensions', 'manage_options', admin_url('edit.php?post_type=cpc_extension') );
        if (in_array('setup_rewards', $admin_favs))
            $submenu['cpc_com'][] = array( 'Rewards (setup)', 'manage_options', admin_url('edit.php?post_type=cpc_rewards') );
        if (in_array('cpc_com_reset', $admin_favs))
            $submenu['cpc_com'][] = array( 'PS Community Reset', 'manage_options', admin_url('admin.php?page=cpc_com_reset') );
        // User
        if (in_array('activity_posts', $admin_favs))
            $submenu['cpc_com'][] = array( 'Activity Posts (all)', 'manage_options', admin_url('edit.php?post_type=cpc_activity') );
        if (in_array('friendships', $admin_favs))
            $submenu['cpc_com'][] = array( 'Friendships (all)', 'manage_options', admin_url('edit.php?post_type=cpc_friendship') );
        if (in_array('favourite_friendships', $admin_favs))
            $submenu['cpc_com'][] = array( 'Favourite Friendships', 'manage_options', admin_url('edit.php?post_type=cpc_favourite_friend') );
        if (in_array('rewards', $admin_favs))
            $submenu['cpc_com'][] = array( 'Rewards (awarded)', 'manage_options', admin_url('edit.php?post_type=cpc_reward') );
        if (in_array('whoto', $admin_favs))
            $submenu['cpc_com'][] = array( 'Activity ("whoto" lists)', 'manage_options', admin_url('edit.php?post_type=cpc_crowd') );
        if (in_array('reviews', $admin_favs))
            $submenu['cpc_com'][] = array( 'Reviews', 'manage_options', admin_url('edit.php?post_type=cpc_review') );
        // Forums
        if (in_array('manage_all_forums', $admin_favs))
            $submenu['cpc_com'][] = array( 'Forums (all)', 'manage_options', admin_url('admin.php?page=cpccom_forum_setup') );
        if (in_array('forum_posts', $admin_favs))
            $submenu['cpc_com'][] = array( 'Forums Posts', 'manage_options', admin_url('edit.php?post_type=cpc_forum_post') );
        if (in_array('forum_extensions', $admin_favs))
            $submenu['cpc_com'][] = array( 'Forum Exts (posts)', 'manage_options', admin_url('edit.php?post_type=cpc_forum_extension') );
        if (in_array('forum_reply_extensions', $admin_favs))
            $submenu['cpc_com'][] = array( 'Forum Exts (replies)', 'manage_options', admin_url('edit.php?post_type=cpc_forum_reply_ext') );
        if (in_array('cpc_forum_subs', $admin_favs))
            $submenu['cpc_com'][] = array( 'Forum Subscriptions', 'manage_options', admin_url('edit.php?post_type=cpc_forum_subs') );
        if (in_array('forum_reply_extensions', $admin_favs))
            $submenu['cpc_com'][] = array( 'Forum Topic Subs', 'manage_options', admin_url('edit.php?post_type=cpc_subs') );
        // Alerts
        if (in_array('manage_alerts', $admin_favs)) {
            $submenu['cpc_com'][] = array( 'Alerts (manage)', 'manage_options', admin_url('edit.php?post_type=cpc_alerts') );
        }
        // Groups
        if (in_array('manage_groups', $admin_favs))
            $submenu['cpc_com'][] = array( 'Groups (manage)', 'manage_options', admin_url('edit.php?post_type=cpc_group') );
        if (in_array('group_members', $admin_favs))
            $submenu['cpc_com'][] = array( 'Groups (members)', 'manage_options', admin_url('edit.php?post_type=cpc_group_members') );
        // Galleries
        if (in_array('galleries', $admin_favs))
            $submenu['cpc_com'][] = array( 'Galleries', 'manage_options', admin_url('edit.php?post_type=cpc_gallery') );
        // Private Messages
        if (in_array('messages', $admin_favs))
            $submenu['cpc_com'][] = array( 'Private Messages', 'manage_options', admin_url('edit.php?post_type=cpc_mail') );
        // Lounge
        if (in_array('lounge', $admin_favs))
            $submenu['cpc_com'][] = array( 'Lounge Chat', 'manage_options', admin_url('edit.php?post_type=cpc_lounge') );
        // Calendars
        if (in_array('calendars', $admin_favs))
            $submenu['cpc_com'][] = array( 'Calendars', 'manage_options', admin_url('edit.php?post_type=cpc_calendar') );
        if (in_array('calendar_events', $admin_favs))
            $submenu['cpc_com'][] = array( 'Calendars (events)', 'manage_options', admin_url('edit.php?post_type=cpc_event') );

        usort($submenu['cpc_com'], 'compareByName');    

    endif;
    
}
function compareByName($a, $b) {
  return strcmp($a[0], $b[0]);
}

function cpccom_manage() {

    if (!get_option('cpc_core_admin_icons')):

    $values = isset($values) ? explode(',', $values) : array();

	  	echo '<div id="cpc_admin_admin_links">';

		  	echo '<div class="cpc_manage_left">';
			  	echo '<h3>'.__('Konfigurieren', CPC2_TEXT_DOMAIN).'</h3>';
			  	echo '<ul class="cpc_manage_icons">';
			  	echo '<li class="cpc_icon_css'.cpc_admin_fav('custom_css').'"><a href="admin.php?page=cpc_com_custom_css">'.__('Benutzerdefinierte CSS', CPC2_TEXT_DOMAIN).'</a></li>';
			  	echo '<li class="cpc_icon_reset'.cpc_admin_fav('cpc_com_reset').'"><a href="admin.php?page=cpc_com_reset">'.__('Lösche alle CP-Community-Daten', CPC2_TEXT_DOMAIN).'</a></li>';
			  	echo '</ul>';
		  	echo '</div>';

            if (strpos(CPC_CORE_PLUGINS, 'core-activity') !== false || strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false || (strpos(CPC_CORE_PLUGINS, 'core-profile') !== false && ((in_array('core-rewards', $values) || in_array('core-rewards', $values)))) ):
                echo '<div class="cpc_manage_left">';
                    echo '<h3>'.__('Benutzer', CPC2_TEXT_DOMAIN).'</h3>';
                    echo '<ul class="cpc_manage_icons">';
                    if (strpos(CPC_CORE_PLUGINS, 'core-activity') !== false)
                        echo '<li class="cpc_icon_activity'.cpc_admin_fav('activity_posts').'"><a href="edit.php?post_type=cpc_activity">'.__('Aktivitätsbeiträge verwalten', CPC2_TEXT_DOMAIN).'</a></li>';
                    if (strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false):
                        echo '<li class="cpc_icon_friends'.cpc_admin_fav('friendships').'"><a href="edit.php?post_type=cpc_friendship">'.__('Freundschaften verwalten', CPC2_TEXT_DOMAIN).'</a></li>';
                        echo '<li class="cpc_icon_friends'.cpc_admin_fav('favourite_friendships').'"><a href="edit.php?post_type=cpc_favourite_friend">'.__('Favoriten verwalten', CPC2_TEXT_DOMAIN).'</a></li>';
                    endif;
                    echo '</ul>';
                echo '</div>';
            endif;

            if (strpos(CPC_CORE_PLUGINS, 'core-forums') !== false):
                echo '<div class="cpc_manage_left">';
                    echo '<h3>'.__('Foren', CPC2_TEXT_DOMAIN).'</h3>';
                    echo '<ul class="cpc_manage_icons">';
                    echo '<li class="cpc_icon_forums'.cpc_admin_fav('manage_all_forums').'"><a href="admin.php?page=cpccom_forum_setup">'.__('Alle Foren verwalten', CPC2_TEXT_DOMAIN).'</a></li>';
                    echo '<li class="cpc_icon_forums'.cpc_admin_fav('forum_posts').'"><a href="edit.php?post_type=cpc_forum_post">'.__('Forenbeiträge verwalten', CPC2_TEXT_DOMAIN).'</a></li>';
                    echo '</ul>';
                echo '</div>';
            endif;

            if (strpos(CPC_CORE_PLUGINS, 'core-alerts') !== false):
                echo '<div class="cpc_manage_left">';
                    echo '<h3>'.__('Benachrichtigungen', CPC2_TEXT_DOMAIN).'</h3>';
                    echo '<ul class="cpc_manage_icons">';
                    echo '<li class="cpc_icon_alerts'.cpc_admin_fav('manage_alerts').'"><a href="edit.php?post_type=cpc_alerts">'.__('Benachrichtigungen verwalten', CPC2_TEXT_DOMAIN).'</a></li>';
                    echo '<li class="cpc_icon_alerts'.cpc_admin_fav('manage_user_alerts').'"><a href="edit.php?page=cpc_alerts_per_user">'.__('Benutzer-Benachrichtigungen verwalten', CPC2_TEXT_DOMAIN).'</a></li>';
                    echo '</ul>';
                    echo '<p>'.sprintf(__('Lösche regelmäßig Deine <a href="%s">gesendeten und ausstehenden Benachrichtigungen</a>.', CPC2_TEXT_DOMAIN), admin_url( 'edit.php?post_type=cpc_alerts' )).'</p>';
                echo '</div>';
            endif;

            if (strpos(CPC_CORE_PLUGINS, 'core-groups') !== false):
		  	    echo '<div class="cpc_manage_left">';
			  	    echo '<h3>'.__('Gruppen', CPC2_TEXT_DOMAIN).'</h3>';
			  	    echo '<ul class="cpc_manage_icons">';
			  	    echo '<li class="cpc_icon_groups'.cpc_admin_fav('manage_groups').'"><a href="edit.php?post_type=cpc_group">'.__('Gruppen verwalten', CPC2_TEXT_DOMAIN).'</a></li>';
			  	    echo '<li class="cpc_icon_groups'.cpc_admin_fav('group_members').'"><a href="edit.php?post_type=cpc_group_members">'.__('Gruppenmitglieder', CPC2_TEXT_DOMAIN).'</a></li>';
			  	    echo '</ul>';
		  	    echo '</div>';
		  	endif;

		echo '</div>';

		echo '<div style="clear:both"></div>';

	endif;
}

function cpc_admin_fav($item) {
    $admin_favs = get_option('cpc_admin_favs');
    if (is_array($admin_favs)):
        if (in_array($item, $admin_favs)):
            return ' cpc_admin_fav cpc_admin_fav_on'.' cpc_fav_'.$item;
        else:
            return ' cpc_admin_fav'.' cpc_fav_'.$item;
        endif;
    else:
        return ' cpc_admin_fav'.' cpc_fav_'.$item;
    endif;
}

function cpccom_release_notes() {

  	echo '<div class="wrap">';
        	
	  	echo '<style>';
            echo '.wrap { margin-top: 30px !important; margin-left: 10px !important; }';
	  		echo '#cpc_release_notes p, td, ol, a { font-size:14px; line-height: 1.3em; font-family:arial; }';
	  		echo '#cpc_release_notes h1 { color: #510051; font-weight: bold; line-height: 1.2em; }';
	  		echo '#cpc_release_notes h2 { color: #510051; margin-top: 10px; font-weight: bold; }';
	  		echo '#cpc_release_notes h3 { color: #333; }';
	  	echo '</style>';
	  	echo '<div id="cpc_release_notes">';
	  		echo '<div id="cpc_welcome_bar" style="margin-top: 20px;">';
		  		echo '<img id="cpc_welcome_logo" style="width:56px; height:56px; float:left;" src="'.plugins_url('../ps-community/css/images/cpc_logo.png', __FILE__).'" title="'.__('help', CPC2_TEXT_DOMAIN).'" />';
		  		echo '<div style="font-size:2em; line-height:1em; font-weight:100; color:#fff;">'.__('Willkommen bei PS Community', CPC2_TEXT_DOMAIN).'</div>';
		  		echo '<p style="color:#fff;"><em>'.__('Das ultimative Plugin für soziale Netzwerke für ClassicPress', CPC2_TEXT_DOMAIN).'</em></p>';
	  		echo '</div>';

	  		echo '<div style="font-size:1.4em; margin-top:20px">'.__('Vielen Dank, dass Du PS Community installiert hast!', CPC2_TEXT_DOMAIN).'</div>';

	  		?>

            <div id="cpc_release_notes_psource" class="cpc_release_notes" style="float:right; width:280px; margin-left: 20px;">
                <div style="float:right;width:200px"><?php echo sprintf(__('Bitte besuche unsere <a href="%s" target="_blank">Projektseite</a>, oder wirke auf <a href="%s" target="_blank">GitHub</a> mit, um PS Community noch großartiger zu machen!', CPC2_TEXT_DOMAIN), 'https://cp-community.n3rds.work/', 'http://twitter.com/cpcymposium'); ?></div>
            </div>
            <p>
            	<?php echo sprintf(__('Wenn Du neu bei CP-Community bist, solltest Du die <a href="%s">Setup-Seite</a> besuchen...', CPC2_TEXT_DOMAIN), admin_url('admin.php?page=cpc_com_setup')); ?>
            </p>
            
            <p>
            	<?php echo __('Wenn Du einen Cache oder ein CDN (vielleicht wie CloudFlare) verwendest, empfehlen wir, nach dem Upgrade aller Plugins alle Deine Dateien zu löschen/löschen.', CPC2_TEXT_DOMAIN); ?>
            </p>

            <em><strong>DerN3rd, PS Community-Entwickler</strong></em></p>

            <?php
            echo '<div style="font-size:1.4em; margin:20px 0 20px 0">'.sprintf(__('Versionshinweise für den aktuellen Build (%s)', CPC2_TEXT_DOMAIN), get_option('cp_community_ver')).'...</div>';

            $cup_position = 'right';
            if ($cup_position == 'left'):
                $cup_of_tea_left = "background-position: bottom left; background-repeat: no-repeat; background-image: url('".plugins_url( '/css/images/cup_of_tea.png', __FILE__ )."');";
                $cup_of_tea_right = "";
            elseif ($cup_position == 'right'):
                $cup_of_tea_left = "";
                $cup_of_tea_right = "background-position: bottom right; background-repeat: no-repeat; background-image: url('".plugins_url( '/css/images/cup_of_tea.png', __FILE__ )."');";
            else: // center (of left)
                $cup_of_tea_left = "background-position: bottom center; background-repeat: no-repeat; background-image: url('".plugins_url( '/css/images/cup_of_tea.png', __FILE__ )."');";
                $cup_of_tea_right = "";
            endif;
            ?>

            <table><tr>
				<td valign="top" class="cpc_release_notes" style="<?php echo $cup_of_tea_left; ?>width:45%;">

					<div style="font-size:1.6em; line-height:1.6em; color: #510051; font-weight: bold;">Core PS Community plugin</div>
					<a href="https://cp-psource.github.io/cp-community/" target-"_blank">Verfügbar im PSOURCE GitHub-Repository</a><br />

                    <h2 style="font-style:italic; margin-top:20px;">Changelog Versionsnummer 1.0.6.:</h2>
					<p>Wir haben eine Gruppenfunktion hinzugefügt (BETA).</p>
                    <p>Etliche kleine Bugfixes umgesetzt.<p>
                    <p>Weitere JQUERY unabhängigkeit umgesetzt für volle ClassicPress Kompatibilität.<p>
                    <p>Getestet mit WP 6.8.1, CP 2.6.0 und PhP 8.4<p> 
                  
                </td>
				<td style="width:1%">&nbsp;</td>
				<td valign="top" class="cpc_release_notes" style="<?php echo $cup_of_tea_right; ?>">

                    <div style="font-size:1.6em; line-height:1.6em; color: #510051; font-weight: bold;">PS Community Entwicklung</div>
					<a href="https://n3rds.work/licenses/" target-"_blank">Hilf mit PS Community noch besser zu machen!</a><br />			

                    <h2 style="font-style:italic; margin-top:20px;">Entwicklung</h2>

                    <h3>Aktivität</h3>
                    <p>Wir planen die Entwicklungsumgebung auf Github weiter auszubauen und zugänglicher zu machen</p>
                    <a href="https://github.com/Power-Source/cp-community/issues" target-"_blank">Hier kannst Du Fehler melden!</a><br />
                    <p>Wir freuen uns über jeden, der dieses Projekt mitgestalten möchte:</p>
                    <a href="https://github.com/orgs/cp-psource/projects/8" target-"_blank">Entwickler-Board</a><br />
					
				</td>
			</tr></table>

			<br style="clear:both">
	  	<?php
	  	echo '</div>';
		
	echo '</div>';	

}

function cpccom_setup() {

	// Flush re-write rules, good idea if problem with linking, saves having to re-save permalink
	global $wp_rewrite;
	$wp_rewrite->flush_rules();	
  	echo '<style>';
        echo '.wrap { margin-top: 20px !important; margin-left: 10px !important; }';
  	echo '</style>';

  	echo '<div class="wrap">';
        	
        // Backdoor to de-activate everything
        if (isset($_GET['cpc_deactivate_all'])):
            delete_option('cpc_default_core');
            echo '<div class="cpc_warning">'.__('Alle Funktionen deaktiviert', CPC2_TEXT_DOMAIN).'</div>';
        endif;

		$show_header = get_option('cpc_show_welcome_header') ? ' style="display:none; "' : '';

		echo '<div '.$show_header.'id="cpc_welcome">';
			echo '<div id="cpc_welcome_bar">';
				echo '<img id="cpc_welcome_logo" style="width:56px; height:56px; float:left;" src="'.plugins_url('../ps-community/css/images/cpc_logo.png', __FILE__).'" title="'.__('Hilfe', CPC2_TEXT_DOMAIN).'" />';
				echo '<div style="font-size:2em; line-height:1em; font-weight:100; color:#fff;">'.__('Willkommen bei CP-Community', CPC2_TEXT_DOMAIN).'</div>';
				echo '<p style="color:#fff;"><em>'.__('Das ultimative Plugin für soziale Netzwerke für ClassicPress', CPC2_TEXT_DOMAIN).'</em></p>';
			echo '</div>';
			echo '<div style="width:30%; min-width:200px; margin-right:10px; float: left;">';
				echo '<p style="font-size:1.4em; font-weight:100;">'.__('Erste Schritte...', CPC2_TEXT_DOMAIN).'</p>';
				echo '<p style="font-weight:100;">'.__('Verwende die Schnellstart-Schaltflächen unten,', CPC2_TEXT_DOMAIN).'<br />';
				echo sprintf(__('füge dann Deine neuen Seiten zu Deinem <a href="%s">ClassicPress-Menü</a> hinzu.', CPC2_TEXT_DOMAIN), 'nav-menus.php').'<br />';
				echo sprintf(__('Anpassen über <a href="%s">Shortcodes</a> (über das Menü).', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpc_com_shortcodes' )).'</p>';
				echo '<p style="font-size:1.4em; font-weight:100;">'.__('So erhältst Du Unterstützung', CPC2_TEXT_DOMAIN).'</p>';
				echo '<p style="font-weight:100;">'.sprintf(__('Support gibt es unter <a target="_blank" href="%s">cp-community.n3rds.work</a>', CPC2_TEXT_DOMAIN), 'https://cp-community.n3rds.work/').'<br />';
				echo sprintf(__('mit <a href="%s" target="_blank">Forum</a>, <a href="https://cp-community.n3rds.work/help/" target="_blank">helpdesk</a>, und Live-Chat-Unterstützung.', CPC2_TEXT_DOMAIN), 'https://cp-community.n3rds.work/forums/', 'https://cp-community.n3rds.work/help/').'</p>';
			echo '</div>';
		echo '</div>';

		// Do any saving from quick start hook
		if (isset($_POST)):
			if (isset($_POST['cpc_expand'])) echo '<input type="hidden" id="cpc_expand" value="'.$_POST['cpc_expand'].'" />';
			if (isset($_POST['cpccom_quick_start'])):
				do_action( 'cpc_admin_quick_start_form_save_hook', $_POST);
			endif;
		endif;

		// Show and hide header
		echo '<div style="float:right"><a id="cpc_hide_welcome_header" style="text-decoration:none;" href="javascript:void(0); return false;">'.__('Willkommen ein-/ausblenden', CPC2_TEXT_DOMAIN).'</a></div>';

		// Check that profile pages are set up
		if (!get_option('cpccom_profile_page')):
			echo '<div class="cpc_error">'.__('Du musst die Profilseiten unter "Profilseite" unten festlegen...', CPC2_TEXT_DOMAIN).'</div>';
		endif;

		// Quick start hook
		echo '<div style="width: 300px; float: left; font-size:1.8em; margin-bottom:15px;">'.__('Schnellstart', CPC2_TEXT_DOMAIN).'</div>';
		echo '<div style="clear: both; margin-bottom:15px;overflow:auto;">';
		do_action( 'cpc_admin_quick_start_hook' );
		echo '</div>';

		// Admin links
		$hide_icons = get_option('cpc_core_admin_icons');
		if ($hide_icons):
			echo '<div style="float:right"><a id="cpc_hide_admin_links_show" style="text-decoration:none;" href="javascript:void(0); return false;">'.__('Admin-Links hierher verschieben', CPC2_TEXT_DOMAIN).'</a></div>';
		else:
			echo '<div style="float:right; text-align:right;"><a id="cpc_hide_admin_links" style="text-decoration:none;" href="javascript:void(0); return false;">'.__('Verschiebe Admin-Links in das Dashboard-Menü', CPC2_TEXT_DOMAIN).'</a><br />('.__('Klicke auf einzelne Symbole, um sie einzeln zu verschieben', CPC2_TEXT_DOMAIN).')</div>';
		endif;
		cpccom_manage();		

		// Option Sections
		echo '<p style="clear:both;">'.__('Klicke unten auf einen Abschnittstitel, um Optionen und Hilfe für den Einstieg anzuzeigen.', CPC2_TEXT_DOMAIN).'</p>';
	
		// Do any saving
		if (isset($_POST['cpccom_update']) && $_POST['cpccom_update'] == 'yes'):
			do_action( 'cpc_admin_setup_form_save_hook', $_POST);
		endif;
		if ( isset($_GET['cpccom_update']) ):
			do_action( 'cpc_admin_setup_form_get_hook', $_GET);
		endif;		
		echo '<form id="cpc_setup" action="'.admin_url( 'admin.php?page=cpc_com_setup' ).'" method="POST">';
		echo '<input type="hidden" name="cpccom_update" value="yes" />';

			// Getting Started/Help hook
			do_action( 'cpc_admin_getting_started_hook' );

		echo '<p><input type="submit" id="cpc_setup_submit" name="Submit" class="button-primary" value="'.__('Änderungen speichern', CPC2_TEXT_DOMAIN).'" /></p>';

		echo '</form>';
		
	echo '</div>';	  	

}

/**
 * Initialisiert die Shortcodes-Seite im Admin-Bereich
 *
 * @since 0.0.1
 */
function cpc_com_shortcodes() {

	// Flush re-write rules, good idea if problem with linking, saves having to re-save permalink
	global $wp_rewrite;
	$wp_rewrite->flush_rules();	
    
    // Do any saving
    if (isset($_POST['cpccom_update']) && $_POST['cpccom_update'] == 'yes'):
        do_action( 'cpc_admin_getting_started_shortcodes_save_hook', $_POST);
    endif;

  	echo '<div class="wrap">';
        	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';

        // Getting Started/Help hook
        do_action( 'cpc_admin_getting_started_shortcodes_hook' );
		
	echo '</div>';	  	

}

/**
 * Initialisiert die Stile-Seite im Admin-Bereich
 *
 * @since 0.0.1
 */
function cpc_com_styles() {

	// Flush re-write rules, good idea if problem with linking, saves having to re-save permalink
	global $wp_rewrite;
	$wp_rewrite->flush_rules();	
    
    // Do any saving
    if (isset($_POST['cpccom_update']) && $_POST['cpccom_update'] == 'yes'):
        do_action( 'cpc_admin_getting_started_styles_save_hook', $_POST);
    endif;

  	echo '<div class="wrap">';
        	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';

        // Getting Started/Help hook
        do_action( 'cpc_admin_getting_started_styles_hook' );
		
	echo '</div>';	  	

}

function cpc_com_translations() {

	if (current_user_can('manage_options')):
		if (isset($_POST['cpc_com_lang'])):
            update_option('cpc_com_lang', $_POST['cpc_com_lang']);
            if (isset($_POST['cpc_com_lang_site'])):
                update_option('cpc_com_lang_site', true);
            else:
                delete_option('cpc_com_lang_site');
            endif;
        endif;
	endif;

  	echo '<div class="wrap">';
        	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
    
	  	echo '<h2>'.__('Übersetzungen', CPC2_TEXT_DOMAIN).'</h2>';

		$path = WP_CONTENT_DIR.'/languages/plugins/cp-community/';

		if (is_admin() && !file_exists($path)) {
			// ... make folder for translation files
	    	@mkdir($path, 0777, true);	
		}

		$locale = get_locale();
		$deprecated = false;
		$domain = CPC2_TEXT_DOMAIN;

		// Load the textdomain according to the plugin first
		$sep = $locale ? '-' : '';
		$mofile = $domain . $sep . $locale . '.mo';
		if ( $loaded = load_textdomain( $domain, $mofile ) )
			return $loaded;

		// Otherwise, load from the languages directory
		$mofile = $path . $mofile;
		$loaded_file = load_textdomain( $domain, $mofile );

		echo '<h3>'.__('Shortcode-Optionen', CPC2_TEXT_DOMAIN).'</h3>';
	  	echo '<p>'.sprintf(__('Du kannst von Shortcodes verwendete Texte und Beschriftungen auf der Admin-Seite <a href="%s">Shortcodes</a> ändern', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpc_com_shortcodes' )).'.</p>';

        echo '<h3>'.__('Standardsprache (Gebietsschema), wie in Einstellungen->Allgemein definiert', CPC2_TEXT_DOMAIN).'</h3>';
        echo '<p>'.__('Dies ist die Standardsprache Deiner Webseite:', CPC2_TEXT_DOMAIN).' '.$locale.'</p>';
    
		echo '<h3>'.__('Standardmäßige .mo-Datei basierend auf dem Standardgebietsschema', CPC2_TEXT_DOMAIN).'</h3>';
        echo '<p>'.sprintf(__('Wenn Du die Übersetzungen für PS Community für die Standardsprache/das Standardgebietsschema Deiner Webseite ändern möchtest, <a href="%s" target="_blank">hole Dir die Basis-.pot-Datei</a> und dann die .mo Die Datei, die Du erstellen solltest (mit einer Anwendung wie <a href="%s" target="_blank">PoEdit</a>), ist:', CPC2_TEXT_DOMAIN), "https://n3rds.work/translation/", "http://www.poedit.com").'</p>';
		echo '<p><span style="padding:4px 8px 4px 8px;border-radius: 3px; border: 1px solid #aaa; background-color:white">'.$mofile.'</span></p>';    

		echo '<h3>'.__('Hinzufügen von Sprachen, die Benutzer selbst auswählen können', CPC2_TEXT_DOMAIN).'</h3>';
		echo '<p>'.sprintf(__('Du musst mit PoEdit eine .mo-Datei generieren, die auf heruntergeladenen .po-Dateien von <a href="%s" target="_blank">CrowdIn.Net</a> basiert, die Du Deinen Benutzern anbietest (oder eine wie oben beschrieben erstellen). mit einer Anwendung wie PoEdit) wäre cp-community-fr_FR.mo beispielsweise die .mo-Datei für Französisch. Du legst die .mo-Dateien im Übersetzungsordner ab, wie im nächsten Abschnitt unten auf dieser Seite gezeigt. Lies mehr darüber im <a href="%s" target="_blank">PS Community Codex</a>.', CPC2_TEXT_DOMAIN), "https://crowdin.com/project/community", "https://cp-community.n3rds.work/translating-cp-community/").'</p>';

        echo '<p>'.__('Um Benutzern die Möglichkeit zu geben, zwischen alternativen Sprachen zu wählen, gib unten zusätzliche Sprachen und Gebietsschemas ein, siehe Beispiel weiter unten auf der Seite.', CPC2_TEXT_DOMAIN).'</p>';
        echo '<p>'.__('Gib die Standardsprache Deiner Webseite ganz oben ein und gib kein Komma und kein Gebietsschema ein (sieh Dir das Beispiel für eine englische Webseite an).', CPC2_TEXT_DOMAIN).'</p>';

		echo '<form action="" method="POST">';
        echo '<textarea name="cpc_com_lang" style="border:1px solid black; width:500px;height:100px">';
        echo get_option('cpc_com_lang');
        echo '</textarea><br />';
        echo '<input type="submit" class="button-primary" value="'.__('Speichern', CPC2_TEXT_DOMAIN).'" />';			

        echo '<p>'.__('Nach dem Speichern können Benutzer auf der Seite "Profil bearbeiten" eine der Sprachen auswählen, die Du oben festgelegt hast.', CPC2_TEXT_DOMAIN).'</p>';
		echo '<p>'.sprintf(__('Beachte dass wenn Du <strong>TABS</strong> für Deine Seite "Profil bearbeiten" auf der Webseite verwendest, im Abschnitt "Profil bearbeiten" von <a href="%s">Setup-Seite</a> auswählen kannst, auf welcher Registerkarte die Sprachauswahl angezeigt wird.', CPC2_TEXT_DOMAIN), admin_url( 'admin.php?page=cpc_com_setup' )).'</p>';

		echo '<p>'.__('Wenn Deine Webseite beispielsweise Englisch war und Du eine .mo-Datei für Französisch (cp-community-fr_FR.mo), Deutsch (cp-community-de_DE.mo) und Russisch (cp-community-ru_RU.mo) und Spanisch (cp-community-es_ES.mo) hast, kannst Du im Textbereich oben Folgendes eingeben:', CPC2_TEXT_DOMAIN).'</p>';
		echo '<div style="font-family:courier">English<br />';
		echo 'Français,fr_FR<br />';
		echo 'Deutsche,de_DE<br />';
		echo 'Pусский,ru_RU<br />';
		echo 'Español,es_ES</div>';

		echo '<h3>'.__('Übersetzungs-Ordner.', CPC2_TEXT_DOMAIN).'</h3>';
        echo '<p>'.__('Dies ist der Ordner, in dem Du Deine Übersetzungsdateien (.mo-Dateien) ablegst, wenn Du Deinen Benutzern alternative Sprachen anbieten möchtest.', CPC2_TEXT_DOMAIN).'</p>';
		echo '<p><span style="padding:4px 8px 4px 8px;border-radius: 3px; border: 1px solid #aaa; background-color:white">'.$path.'</span></p>';
    
        $files = scandir($path);
        $valid_files = false;
        $list = '<ul>';
        if ($files):
            foreach ($files as $file):
                if ( (strpos($file,  $domain.'-') !== false) && (strpos($file, '.mo') !== false) ):
                    $list .= '<li>'.$file.'</li>';
                    $valid_files = true;
                endif;
            endforeach;
        endif;
        $list .= '</ul>';
        if ($valid_files):
            echo '<p>'.__('Gefundene Sprachdateien:', CPC2_TEXT_DOMAIN).'</p>';
            echo $list;
        else:
            echo '<p>'.__('Derzeit sind keine gültigen Sprachdateien im Verzeichnis vorhanden.', CPC2_TEXT_DOMAIN).'</p>';            
        endif;

		echo '<h3>'.__('Ändern der Sprache der gesamten Webseite', CPC2_TEXT_DOMAIN).'</h3>';
	  	echo '<p>'.sprintf(__('Wenn Du die entsprechende Webseiten-Sprache für ClassicPress installiert hast (siehe <a target="_blank" href="%s">hier</a>), kann diese optional auch für den Benutzer verwendet werden, wenn er eine Sprache auswählt!', CPC2_TEXT_DOMAIN ), "https://codex.wordpress.org/Installing_ClassicPress_in_Your_Language").'</p>';
        echo '<p><input type="checkbox" ';
        if (get_option('cpc_com_lang_site')) echo 'CHECKED ';
            echo 'name="cpc_com_lang_site" />'.__('Aktiviere dieses Kontrollkästchen, um den automatischen Sprach-/Gebietsschemawechsel für die ClassicPress-Installation zu bestätigen.', CPC2_TEXT_DOMAIN).'</p>';
        echo '<input type="submit" class="button-primary" value="'.__('Speichern', CPC2_TEXT_DOMAIN).'" />';			
		echo '</form>';
        
    echo '</div>';	  	

}

function cpc_com_reset() {

  	echo '<div class="wrap">';
        	
	  	echo '<style>';
            echo '.wrap { margin-top: 30px !important; margin-left: 10px !important; }';
	  	echo '</style>';
        	
  		echo '<div id="cpc_welcome_bar" style="margin-top: 20px;">';
	  		echo '<img id="cpc_welcome_logo" style="width:56px; height:56px; float:left;" src="'.plugins_url('../ps-community/css/images/cpc_logo.png', __FILE__).'" title="'.__('Hilfe', CPC2_TEXT_DOMAIN).'" />';
	  		echo '<div style="font-size:2em; line-height:1em; font-weight:100; color:#fff;">'.__('Willkommen bei CP-Community', CPC2_TEXT_DOMAIN).'</div>';
	  		echo '<p style="color:#fff;"><em>'.__('Das ultimative Plugin für soziale Netzwerke für ClassicPress', CPC2_TEXT_DOMAIN).'</em></p>';
  		echo '</div>';

  		echo '<div style="font-size:1.4em; margin-top:20px">'.__('CP-Community-Datenentfernung (Zurücksetzen)', CPC2_TEXT_DOMAIN).'</div>';

		echo '<p>'.__('Verwende diesen Bildschirm, um PS Community zurückzusetzen oder alle Daten zu entfernen, bevor Du das Plugin deinstallierst.', CPC2_TEXT_DOMAIN).'</p>';

		// admins only!
		if (current_user_can('manage_options')):

			// ... instructed to reset?
			if (isset($_POST['cpc_com_reset_confirm'])):
				if (wp_verify_nonce( $_POST['cpc_com_reset_nonce'], 'cpc_com_reset' )) {
					// reset!
                    global $wpdb, $wp_rewrite;
                    if (is_multisite()) {
                        $blogs = $wpdb->get_results("SELECT blog_id FROM ".$wpdb->base_prefix."blogs");
                        if ($blogs) {
                            foreach($blogs as $blog) {
                              switch_to_blog($blog->blog_id);
                              echo '<div class="cpc_warning">'.sprintf(__('Wechsel zur Blog-ID %d', CPC2_TEXT_DOMAIN), $blog->blog_id).'</div>';
                                    echo '<div class="cpc_warning">';
                                    __cpc_com_uninstall_delete();
                                    echo __('Lokale Dateien entfernen', CPC2_TEXT_DOMAIN).'... ';
                                    __cpc_com_uninstall_rrmdir(WP_CONTENT_DIR.'/cpc-pro-content');
                                    echo __('ok', CPC2_TEXT_DOMAIN).'<br />';
                                    echo __('ClassicPress spülen', CPC2_TEXT_DOMAIN).'... ';                        
                        			$wp_rewrite->flush_rules();
                        			echo __('ok', CPC2_TEXT_DOMAIN).'<br />';
									echo '</div><div class="cpc_success">'.__('Vollständig', CPC2_TEXT_DOMAIN).'</div>';
									echo '<p>'.__('Du musst alle von Dir erstellten Seiten entfernen.', CPC2_TEXT_DOMAIN).'</p>';
                            }
                            restore_current_blog();
                        }   
                    } else {
                    	echo '<div class="cpc_warning">';
                        __cpc_com_uninstall_delete();
						echo __('Lokale Dateien entfernen', CPC2_TEXT_DOMAIN).'... ';                        
                        __cpc_com_uninstall_rrmdir(WP_CONTENT_DIR.'/cpc-pro-content');
						echo __('ok', CPC2_TEXT_DOMAIN).'<br />';
						echo __('ClassicPress spülen', CPC2_TEXT_DOMAIN).'... ';                        
                        $wp_rewrite->flush_rules();
						echo __('ok', CPC2_TEXT_DOMAIN).'<br />';
						echo '</div><div class="cpc_success">'.__('Vollständig', CPC2_TEXT_DOMAIN).'</div>';
						echo '<p>'.__('Du musst alle von Dir erstellten Seiten entfernen.', CPC2_TEXT_DOMAIN).'</p>';
                    }

				} else {
					echo '<div class="cpc_error">'.__('NONCE fehlgeschlagen – verdächtige Aktivität, Zurücksetzen abgebrochen', CPC2_TEXT_DOMAIN).'</div>';
				}

			else:

				echo '<div class="cpc_warning">'.__('Dies kann nicht rückgängig gemacht werden – bitte stelle sicher, dass Du zuerst eine Sicherung der Webseiten-Datenbank erstellst (für den Fall von Problemen oder Fehlern)!', CPC2_TEXT_DOMAIN).'</div>';

			endif;

			echo '<form onsubmit="return confirm(\''.__('Bist du sicher? Letzte Möglichkeit!', CPC2_TEXT_DOMAIN).'\')" action="'.admin_url( 'admin.php?page=cpc_com_reset' ).'" method="POST">';
				wp_nonce_field( 'cpc_com_reset', 'cpc_com_reset_nonce' );				
				echo '<input type="hidden" name="cpc_com_reset_confirm" value="Y" />';
				echo '<input type="submit" class="button-primary" value="'.__('Lösche alle PS Community-Daten', CPC2_TEXT_DOMAIN).'" />';			
			echo '</form>';

		else:

			echo '<div class="cpc_error">'.__('Nur für Webseiten-Administratoren verfügbar.', CPC2_TEXT_DOMAIN).'</div>';

		endif;

}

/**
 * Initialisiert die benutzerdefinierte CSS-Seite im Admin-Bereich
 *
 * @since 0.0.1
 */
function cpccom_custom_css() {

	// React to POSTed information
	if (isset($_POST['cpccom_update_css'])):

		update_option('cpccom_custom_css', $_POST['cpccom_custom_css']);

		// Re-act to any more options?
		do_action( 'cpc_admin_custom_css_form_save_hook', $_POST );

	endif;
	

  	echo '<div class="wrap">';
        	
	  	echo '<div id="icon-themes" class="icon32"><br /></div>';
	  	echo '<h2>'.__('Benutzerdefinierte CSS', CPC2_TEXT_DOMAIN).'</h2>';

	  	echo __('Um Designstile zu überschreiben, musst Du möglicherweise !important zu den Stilen hinzufügen.', CPC2_TEXT_DOMAIN);
	  	?>
		<form action="" method="POST">

			<input type="hidden" name="cpccom_update_css" value="yes" />

			<table class="form-table">

				<tr><td colspan="2">

					<textarea name="cpccom_custom_css" id="cpccom_custom_css" style="width:100%; height:500px"><?php echo stripslashes(get_option('cpccom_custom_css')); ?></textarea>

				</td></tr>

				<?php 
				// Any more options?
				do_action( 'cpc_admin_custom_css_form_hook' );
				?>

			</table> 
			
			<p style="margin-left:6px"> 
			<input type="submit" name="Submit" class="button-primary" value="<?php echo __('Speichere benutzerdefiniertes CSS', CPC2_TEXT_DOMAIN); ?>" /> 
			</p> 
			
		</form> 
		<?php

	echo '</div>';	  	

}


function __cpc_com_uninstall_delete () {
    global $wpdb;

    // delete shortcode options
    $sql = "DELETE FROM ".$wpdb->prefix."options WHERE option_name like 'cpc_shortcode_options%'";
    echo __('Removing shortcode options', CPC2_TEXT_DOMAIN).'... '; 
    $wpdb->query($sql);
	echo __('ok', CPC2_TEXT_DOMAIN).'<br />';
    // delete other options
    $sql = "DELETE FROM ".$wpdb->prefix."options WHERE option_name like 'cpc_%'";
    echo __('Removing application options', CPC2_TEXT_DOMAIN).'... '; 
    $wpdb->query($sql);
	echo __('ok', CPC2_TEXT_DOMAIN).'<br />';
    // delete user meta data
    echo __('Removing user meta', CPC2_TEXT_DOMAIN).'... ';    
    $wpdb->query("DELETE FROM ".$wpdb->base_prefix."usermeta WHERE meta_key like 'cpc_%'");
	echo __('ok', CPC2_TEXT_DOMAIN).'<br />';
	// removing custom posts (core)
    $sql = "DELETE FROM ".$wpdb->prefix."posts WHERE post_type = 'cpc_activity' OR post_type = 'cpc_alerts' OR post_type = 'cpc_forum_post' OR post_type = 'cpc_friendship'";
    echo __('Removing core custom post types', CPC2_TEXT_DOMAIN).'... '; 
    $wpdb->query($sql);
	echo __('ok', CPC2_TEXT_DOMAIN).'<br />';
	// removing custom posts (extensions)
    $sql = "DELETE FROM ".$wpdb->prefix."posts WHERE post_type = 'cpc_calendar' OR post_type = 'cpc_event' OR post_type = 'cpc_crowd' OR post_type = 'cpc_extension' OR post_type = 'cpc_forum_extension' OR post_type = 'cpc_forum_subs' OR post_type = 'cpc_subs' OR post_type = 'cpc_gallery' OR post_type = 'cpc_group_members' OR post_type = 'cpc_group' OR post_type = 'cpc_lounge' OR post_type = 'cpc_mail' OR post_type = 'cpc_reward' OR post_type = 'cpc_rewards'";
    echo __('Removing additional custom post types', CPC2_TEXT_DOMAIN).'... '; 
    $wpdb->query($sql);
	echo __('ok', CPC2_TEXT_DOMAIN).'<br />';
    // clear schedules
    echo __('Removing ClassicPress schedule', CPC2_TEXT_DOMAIN).'... ';    
    wp_clear_scheduled_hook( 'cpc_community_alerts_hook' );
	echo __('ok', CPC2_TEXT_DOMAIN).'<br />';
}

function __cpc_com_uninstall_rrmdir($dir) {
   if (is_dir($dir)) {
     $objects = scandir($dir);
     foreach ($objects as $object) {
       if ($object != "." && $object != "..") {
         if (filetype($dir."/".$object) == "dir") __cpc_com_uninstall_rrmdir($dir."/".$object); else unlink($dir."/".$object);
       }
     }
     reset($objects);
     rmdir($dir);
   }
} 
?>