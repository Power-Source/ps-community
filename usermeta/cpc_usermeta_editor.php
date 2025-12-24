<?php

// Add menu item(s) to PS Community Editor toolbar button
add_filter('cpc_admin_shortcodes', 'cpc_admin_shortcodes_add_usermeta', 10, 1);
function cpc_admin_shortcodes_add_usermeta($items) {
	
	$item = array();
	$item['div'] = 'cpc_admin_shortcodes_add_usermeta_dialog'; // DIV to show in dialog
	$item['label'] = __('Benutzer / Avatar', CPC2_TEXT_DOMAIN); // Shows on the menu
	$items['cpc_usermeta'] = $item; // Unique ID

	return $items;

}

// Add dialog to show when menu item(s) are clicked on
add_action('cpc_admin_shortcodes_dialog', 'cpc_admin_shortcodes_add_usermeta_dialog');
function cpc_admin_shortcodes_add_usermeta_dialog() {
	echo '<div id="cpc_admin_shortcodes_add_usermeta_dialog" class="cpc_admin_shortcodes_add_dialog" style="display:none;">';

		// List of shortcodes
    	echo '<p><select id="cpc_admin_shortcodes_select_usermeta">';
    		echo '<option value="display_name">'.__('Anzeigenamen des Benutzers anzeigen', CPC2_TEXT_DOMAIN).'</option>';    		
    		echo '<option value="avatar">'.__('Benutzeravatar anzeigen', CPC2_TEXT_DOMAIN).'</option>';    		
    		echo '<option value="usermeta">'.__('Benutzerinformationen anzeigen (Meta)', CPC2_TEXT_DOMAIN).'</option>';    		
    		echo '<option value="usermeta_change">'.__('Benutzerprofil bearbeiten', CPC2_TEXT_DOMAIN).'</option>';    		
    	echo '</select></p>';

    	// [cpc-display-name]
    	echo '<div id="cpc_admin_shortcodes_usermeta_display_name" class="cpc_admin_shortcodes_dialog_div">';

    		echo '<p>'.__('Als Hyperlink fungieren?', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<select id="cpc_display_name_link">';
    			echo '<option value="1">'.__('Ja', CPC2_TEXT_DOMAIN).'</option>';
    			echo '<option value="0">'.__('Nein', CPC2_TEXT_DOMAIN).'</option>';
    		echo '</select></p>';

    		echo '<p>'.__('HTML vor Shortcode', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_display_name_before" style="width:100%" /></p>';

    		echo '<p>'.__('HTML nach Shortcode', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_display_name_after" style="width:100%" /></p>';

   			echo '<p><input id="insert_cpc_display_name" type="button" class="button-primary" value="'.__('Shortcode einfügen', CPC2_TEXT_DOMAIN).'"></p>';

   		echo '</div>';

    	// [cpc-avatar]
    	echo '<div id="cpc_admin_shortcodes_usermeta_avatar" class="cpc_admin_shortcodes_dialog_div" style="display:none">';

    		echo '<p>'.__('Größe des Avatars in Pixel', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_avatar_size" /></p>';

    		echo '<p>'.__('Link zum Ändern des Avatars anzeigen?', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<select id="cpc_avatar_change_link">';
    			echo '<option value="1">'.__('Ja', CPC2_TEXT_DOMAIN).'</option>';
    			echo '<option value="0">'.__('Nein', CPC2_TEXT_DOMAIN).'</option>';
    		echo '</select></p>';

    		echo '<p>'.__('HTML vor Shortcode', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_avatar_before" style="width:100%" /></p>';

    		echo '<p>'.__('HTML nach Shortcode', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_avatar_after" style="width:100%" /></p>';

   			echo '<p><input id="insert_cpc_avatar" type="button" class="button-primary" value="'.__('Shortcode einfügen', CPC2_TEXT_DOMAIN).'"></p>';

   		echo '</div>';   

    	// [cpc-usermeta]
    	echo '<div id="cpc_admin_shortcodes_usermeta_usermeta" class="cpc_admin_shortcodes_dialog_div" style="display:none">';

    		echo '<p>'.__('Benutzer-Metafeld', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<select id="cpc_usermeta_meta">';
    			echo '<option value="cpccom_home">'.__('Stadt/Gemeinde', CPC2_TEXT_DOMAIN).'</option>';
    			echo '<option value="cpccom_country">'.__('Land', CPC2_TEXT_DOMAIN).'</option>';
    		echo '</select></p>';

    		echo '<p>'.__('Beschriftung zur Anzeige', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_label" /></p>';

    		echo '<p>'.__('Größe der Google-Karte in Pixel (z. B. 250.250)', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_size" /></p>';

    		echo '<p>'.__('Zoomstufe der Google-Karte', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_zoom" /></p>';

    		echo '<p>'.__('HTML vor Shortcode', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_before" style="width:100%" /></p>';

    		echo '<p>'.__('HTML nach Shortcode', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_after" style="width:100%" /></p>';

   			echo '<p><input id="insert_cpc_usermeta" type="button" class="button-primary" value="'.__('Shortcode einfügen', CPC2_TEXT_DOMAIN).'"></p>';

   		echo '</div>';   

    	// [cpc-usermeta-change]
    	echo '<div id="cpc_admin_shortcodes_usermeta_usermeta_change" class="cpc_admin_shortcodes_dialog_div" style="display:none">';

    		echo '<p>'.__('Beschriftung für Schaltfläche', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_label" /></p>';

    		echo '<p>'.__('Beschriftung für den Anzeigenamen', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_displayname" /></p>';

    		echo '<p>'.__('Beschriftung für E-Mail-Adresse', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_email" /></p>';

    		echo '<p>'.__('Beschriftung für Stadt/Gemeinde', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_town" /></p>';

    		echo '<p>'.__('Beschriftung für Land', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_country" /></p>';

    		echo '<p>'.__('Beschriftung für Passwort', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_password" /></p>';

    		echo '<p>'.__('Beschriftung zum erneuten Eingeben des Passworts', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_password2" /></p>';

    		echo '<p>'.__('Beschriftung zum Anmelden nach Passwortänderung', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_password_msg" /></p>';

    		echo '<p>'.__('CSS-Klasse für Beschriftungen', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_meta_class" /></p>';

    		echo '<p>'.__('CSS-Klasse für Schaltfläche', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_class" /></p>';

    		echo '<p>'.__('HTML vor Shortcode', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_before" style="width:100%" /></p>';

    		echo '<p>'.__('HTML nach Shortcode', CPC2_TEXT_DOMAIN).'<br />';
    		echo '<input type="text" id="cpc_usermeta_change_after" style="width:100%" /></p>';

   			echo '<p><input id="insert_cpc_usermeta_change" type="button" class="button-primary" value="'.__('Shortcode einfügen', CPC2_TEXT_DOMAIN).'"></p>';

   		echo '</div>';  


	echo '</div>';
}

// Javascript that re-acts to button(s) on dialog(s)
add_action( 'admin_head', 'cpc_admin_shortcodes_add_usermeta_js' );
function cpc_admin_shortcodes_add_usermeta_js() {
	$js = '

	jQuery(document).ready(function() {

		jQuery("#cpc_admin_shortcodes_select_usermeta").change(function() {
			jQuery(".cpc_admin_shortcodes_dialog_div").hide();
			jQuery("#cpc_admin_shortcodes_usermeta_"+jQuery(this).val()).show();
		});

		// Display name
		jQuery("#insert_cpc_display_name").click(function (event) {

			var link = jQuery("#cpc_display_name_link").val();
			var before = jQuery("#cpc_display_name_before").val().replace(/\"/g, "\'");
			var after = jQuery("#cpc_display_name_after").val().replace(/\"/g, "\'");

			var code = "[cpc-display-name";

			code += " link=\"1\"";
			if (before != "") code += " before=\""+before+"\"";
			if (after != "") code += " after=\""+after+"\"";

			code += "]";

			code = jQuery("<div/>").text(code).html();

			tinyMCE.activeEditor.insertContent(code);
		});

		// Avatar
		jQuery("#insert_cpc_avatar").click(function (event) {

			var size = jQuery("#cpc_avatar_size").val().replace(/px/g, "");
			var change_link = jQuery("#cpc_avatar_change_link").val();
			var before = jQuery("#cpc_avatar_before").val().replace(/\"/g, "\'");
			var after = jQuery("#cpc_avatar_after").val().replace(/\"/g, "\'");

			var code = "[cpc-avatar";

			if (size != "") code += " size=\""+size+"\"";
			code += " change_link=\""+change_link+"\"";
			if (before != "") code += " before=\""+before+"\"";
			if (after != "") code += " after=\""+after+"\"";

			code += "]";

			code = jQuery("<div/>").text(code).html();

			tinyMCE.activeEditor.insertContent(code);
		});

		// User meta
		jQuery("#insert_cpc_usermeta").click(function (event) {

			var meta = jQuery("#cpc_usermeta_meta").val();
			var label = jQuery("#cpc_usermeta_label").val();
			var size = jQuery("#cpc_usermeta_size").val().replace(/px/g, "");
			var zoom = jQuery("#cpc_usermeta_zoom").val();
			var before = jQuery("#cpc_usermeta_before").val().replace(/\"/g, "\'");
			var after = jQuery("#cpc_usermeta_after").val().replace(/\"/g, "\'");

			var code = "[cpc-usermeta";

			code += " meta=\""+meta+"\"";
			code += " label=\""+label+"\"";
			if (size != "") code += " size=\""+size+"\"";
			if (zoom != "") code += " zoom=\""+zoom+"\"";
			if (before != "") code += " before=\""+before+"\"";
			if (after != "") code += " after=\""+after+"\"";

			code += "]";

			code = jQuery("<div/>").text(code).html();

			tinyMCE.activeEditor.insertContent(code);
		});

		// User meta (edit profile)
		jQuery("#insert_cpc_usermeta_change").click(function (event) {

			var label = jQuery("#cpc_usermeta_change_label").val();
			var displayname = jQuery("#cpc_usermeta_change_displayname").val();
			var email = jQuery("#cpc_usermeta_change_email").val();
			var town = jQuery("#cpc_usermeta_change_town").val();
			var country = jQuery("#cpc_usermeta_change_country").val();
			var password = jQuery("#cpc_usermeta_change_password").val();
			var password2 = jQuery("#cpc_usermeta_change_password2").val();
			var password_msg = jQuery("#cpc_usermeta_change_password_msg").val();
			var meta_class = jQuery("#cpc_usermeta_change_meta_class").val();
			var x_class = jQuery("#cpc_usermeta_change_class").val();
			var before = jQuery("#cpc_usermeta_change_before").val().replace(/\"/g, "\'");
			var after = jQuery("#cpc_usermeta_change_after").val().replace(/\"/g, "\'");

			var code = "[cpc-usermeta-change";

			if (label != "") code += " label=\""+label+"\"";
			if (displayname != "") code += " displayname=\""+displayname+"\"";
			if (email != "") code += " email=\""+email+"\"";
			if (town != "") code += " town=\""+town+"\"";
			if (country != "") code += " country=\""+country+"\"";
			if (password != "") code += " password=\""+password+"\"";
			if (password2 != "") code += " password2=\""+password2+"\"";
			if (password_msg != "") code += " password_msg=\""+password_msg+"\"";
			if (meta_class != "") code += " meta_class=\""+meta_class+"\"";
			if (x_class != "") code += " x_class=\""+x_class+"\"";

			if (before != "") code += " before=\""+before+"\"";
			if (after != "") code += " after=\""+after+"\"";

			code += "]";

			code = jQuery("<div/>").text(code).html();

			tinyMCE.activeEditor.insertContent(code);
		});

	});';

	echo '<script type="text/javascript">' . $js . '</script>';
}


?>