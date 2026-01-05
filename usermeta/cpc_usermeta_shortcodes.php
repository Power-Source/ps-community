<?php

/* **** */ /* INIT */ /* **** */

function cpc_usermeta_init() {

    $tabs_array = get_option('cpc_comfile_tabs');
    $cpc_comfile_tab_animation = (isset($tabs_array['cpc_comfile_tab_animation'])) ? $tabs_array['cpc_comfile_tab_animation'] : 'slide';
    
	// JS and CSS
	wp_enqueue_style('cpc-usermeta-css', plugins_url('cpc_usermeta.css', __FILE__), 'css');
	wp_enqueue_script('cpc-usermeta-js', plugins_url('cpc_usermeta.js', __FILE__), array('jquery'));	
    $cpc_strength_array = get_option('cpc_strength_array');
    if (!$cpc_strength_array) $cpc_strength_array = array('Weak','Poor','Good','Strong','Mismatch');
	wp_localize_script('cpc-usermeta-js', 'cpc_usermeta', array(
        'ajaxurl' => admin_url( 'admin-ajax.php' ),
        'animation' => $cpc_comfile_tab_animation,
        'score1' => stripslashes($cpc_strength_array[0]),
        'score2' => stripslashes($cpc_strength_array[1]),
        'score3' => stripslashes($cpc_strength_array[2]),
        'score4' => stripslashes($cpc_strength_array[3]),
        'score5' => stripslashes($cpc_strength_array[4])
    ));    	
    // Password security
    wp_enqueue_script('password-strength-meter');
    
	// Anything else?
	do_action('cpc_usermeta_init_hook');

}

function cpc_add_tab_css(){    
    
    $tabs_array = get_option('cpc_comfile_tabs');
    $cpc_comfile_tab_active_color = (isset($tabs_array['cpc_comfile_tab_active_color'])) ? $tabs_array['cpc_comfile_tab_active_color'] : '#fff';
    $cpc_comfile_tab_inactive_color = (isset($tabs_array['cpc_comfile_tab_inactive_color'])) ? $tabs_array['cpc_comfile_tab_inactive_color'] : '#d2d2d2';
    $cpc_comfile_tab_active_text_color = (isset($tabs_array['cpc_comfile_tab_active_text_color'])) ? $tabs_array['cpc_comfile_tab_active_text_color'] : '#000';
    $cpc_comfile_tab_inactive_text_color = (isset($tabs_array['cpc_comfile_tab_inactive_text_color'])) ? $tabs_array['cpc_comfile_tab_inactive_text_color'] : '#000';

    echo '<style>';
    
    echo '.cpc-tab-links a:hover {';
    echo 'background-color:'.$cpc_comfile_tab_active_color.';';
    echo 'color:'.$cpc_comfile_tab_active_text_color.';';    
    echo 'border-bottom: 1px solid '.$cpc_comfile_tab_inactive_color.';';
    echo '}';

    echo '.cpc-tab-links li.active a:hover {';
    echo 'border-bottom: 1px solid transparent;';
    echo '}';
    
    echo '.cpc-tab-content {';
    echo 'background-color:'.$cpc_comfile_tab_active_color.';';
    echo 'border: 1px solid '.$cpc_comfile_tab_inactive_color.';';
    echo '}';
    
    echo '.cpc-tab-links li a, .cpc-tab-links li a:visited {';
    echo 'border-top: 1px solid '.$cpc_comfile_tab_inactive_color.';';
    echo 'border-left: 1px solid '.$cpc_comfile_tab_inactive_color.';';
    echo 'border-right: 1px solid '.$cpc_comfile_tab_inactive_color.';';
    echo 'border-bottom: 1px solid transparent;';
    echo 'background-color:'.$cpc_comfile_tab_inactive_color.';';
    echo 'color:'.$cpc_comfile_tab_inactive_text_color.';';
    echo 'text-decoration:none;';
    echo '}';
    
    echo '.cpc-tab-links li.active a {';
    echo 'background-color:'.$cpc_comfile_tab_active_color.' !important;';
    echo 'color:'.$cpc_comfile_tab_active_text_color.' !important;';
    echo '}';
        
    echo '</style>';
    
    
}   

/* ********** */ /* SHORTCODES */ /* ********** */

function cpc_user_id($atts) {
    global $current_user;
    $html = '';
    $user_id = cpc_get_user_id();
    $html .= $user_id;
    return $html;
}

function cpc_usermeta_button($atts) {

	// Init
	cpc_usermeta_init();

	global $current_user;
	$html = '';

	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_usermeta_button');        
	extract( shortcode_atts( array(
		'user_id' => false,
		'url' => cpc_get_shortcode_value($values, 'cpc_usermeta_button-url', ''),
		'value' => cpc_get_shortcode_value($values, 'cpc_usermeta_button-value', __('Go', CPC2_TEXT_DOMAIN)),
		'class' => cpc_get_shortcode_value($values, 'cpc_usermeta_button-class', ''),
		'styles' => true,
        'after' => '',
		'before' => '',
	), $atts, 'cpc_usermeta_button' ) );

	if (!$user_id) $user_id = cpc_get_user_id();

	if (!$url):

		$html .= '<div class="cpc_error">'.__('Bitte lege die URL-Option im Shortcode fest.', CPC2_TEXT_DOMAIN).'</div>';

	else:

		$html .= '<form action="" method="POST">';
		$url .= cpc_query_mark($url).'user_id='.$user_id;
		$html .= '<input class="cpc_user_button '.$class.'" rel="'.$url.'" type="submit" class="cpc_button '.$class.'" value="'.$value.'" />';
		$html .= '</form>';

	endif;

	if ($html) $html = htmlspecialchars_decode($before).$html.htmlspecialchars_decode($after);

	return $html;	
}

function cpc_usermeta($atts) {

	// Init
	cpc_usermeta_init();

	global $current_user;
	$html = '';

	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_usermeta');    
	extract( shortcode_atts( array(
		'user_id' => false,
		'meta' => cpc_get_shortcode_value($values, 'cpc_usermeta-meta', 'cpccom_home'),
		'label' => cpc_get_shortcode_value($values, 'cpc_usermeta-label', ''),
		'size' => cpc_get_shortcode_value($values, 'cpc_usermeta-size', '250,250'),
		'map_style' => cpc_get_shortcode_value($values, 'cpc_usermeta-map_style', 'dynamic'),
		'zoom' => cpc_get_shortcode_value($values, 'cpc_usermeta-zoom', 5),
        'link' => cpc_get_shortcode_value($values, 'cpc_usermeta-link', true),
		'styles' => true,
        'after' => '',
		'before' => '',
	), $atts, 'cpc_usermeta' ) );
	$size = explode(',', $size);

	if (!$user_id) $user_id = cpc_get_user_id();

    if ($user_id):

    	if (strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false):
            $friends = cpc_are_friends($current_user->ID, $user_id);
            // By default same user, and friends of user, can see profile
            $user_can_see_profile = ($current_user->ID == $user_id || $friends['status'] == 'publish') ? true : false;
            $user_can_see_profile = apply_filters( 'cpc_check_profile_security_filter', $user_can_see_profile, $user_id, $current_user->ID );
        else:
            $user_can_see_profile = $current_user->ID == $user_id ? true : false;
        endif;

    	if ($user_can_see_profile):

        $user = get_user_by('id', $user_id);
        if ($user):

            $user_values = array('ID', 'display_name', 'user_firstname', 'user_lastname', 'user_login', 'user_nicename', 'user_email', 'user_url', 'user_registered', 'user_status');
            if (in_array($meta, $user_values)) {

                if ($label) $html .= '<span class="cpc_usermeta_label">'.$label.'</span> ';
                if ($meta == 'user_email' && $link) {
                    $html .= '<a href="mailto:'.$user->$meta.'">'.$user->$meta.'</a>';
                } else {
                    $html .= $user->$meta;
                }

            } else {

                $value = get_user_meta($user_id, $meta, true);
                if (!$value) {
                    $value = get_user_meta($user_id, 'cpc_'.$meta, true);
                    $value = apply_filters('cpc_usermeta_value_filter', $value, $atts, $user_id);
                }

                if ($label) $html .= '<span class="cpc_usermeta_label">'.$label.'</span> ';
                $html .= $value;
            }

        endif;

    endif;

    endif;
    
	if ($html) $html = htmlspecialchars_decode($before).$html.htmlspecialchars_decode($after);
    
	return $html;

}

function cpc_usermeta_change($atts) {

	// Init
	cpc_usermeta_init();
    cpc_add_tab_css();

	global $current_user, $wpdb;
	$html = '';

    // Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_usermeta_change');    
    extract( shortcode_atts( array(
        'user_id' => 0,
        'meta_class' => 'cpc_usermeta_change_label',
        'show_town' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-show_town', true),
        'show_country' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-show_country', true),
        'show_name' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-show_name', true),
        'class' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-class', ''),
        'label' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-label', __('Aktualisieren', CPC2_TEXT_DOMAIN)),
        'town' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-town', __('Stadt/Gemeinde', CPC2_TEXT_DOMAIN)),
        'town_default' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-town_default', ''),
        'town_mandatory' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-town_mandatory', false),
        'country' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-country', __('Land', CPC2_TEXT_DOMAIN)),
        'country_default' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-country_default', ''),
        'country_mandatory' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-country_mandatory', false),
        'displayname' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-displayname', __('Anzeigename', CPC2_TEXT_DOMAIN)),
        'name' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-name', __('Dein Vorname und Nachname', CPC2_TEXT_DOMAIN)),
        'language' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-language', __('Wähle deine Sprache', CPC2_TEXT_DOMAIN)),
        'password' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-password', __('Ändere Dein Passwort', CPC2_TEXT_DOMAIN)),
        'password2' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-password2', __('Passwort erneut eingeben', CPC2_TEXT_DOMAIN)),
        'password_msg' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-password_msg', __('Passwort geändert, bitte melde Dich erneut an.', CPC2_TEXT_DOMAIN)),
        'email' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-email', __('E-Mail-Adresse', CPC2_TEXT_DOMAIN)),
        'logged_out_msg' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-logged_out_msg', __('Du musst angemeldet sein, um diese Seite anzuzeigen.', CPC2_TEXT_DOMAIN)),
        'mandatory' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-mandatory', '<span style="color:red;"> *</span>'),        
        'login_url' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-login_url', ''),
        'required_msg' => cpc_get_shortcode_value($values, 'cpc_usermeta_change-required_msg', __('Bitte überprüfe die Pflichtfelder', CPC2_TEXT_DOMAIN)),
        'styles' => true,
        'after' => '',
        'before' => '',

    ), $atts, 'cpc_usermeta' ) );

	if (is_user_logged_in()) {
    
		if (!$user_id)
			$user_id = cpc_get_user_id();

		$user_can_see_profile = ($current_user->ID == $user_id || current_user_can('manage_options')) ? true : false;

        if (current_user_can('manage_options') && !$login_url && function_exists('cpc_login_init')):
            $html = cpc_admin_tip($html, 'cpc_usermeta_change', __('Füge login_url="/example" zum Shortcode [cpc-usermeta-change] hinzu, damit sich Benutzer anmelden und hierher zurückleiten können, wenn sie nicht angemeldet sind.', CPC2_TEXT_DOMAIN));
        endif;    
        
		if ($user_can_see_profile):
        
            $mandatory = html_entity_decode($mandatory, ENT_QUOTES);

			// Start building tabs array
			$tabs = array();
            $tabs_array = get_option('cpc_comfile_tabs');        
                
			// Update if POSTing
			if (isset($_POST['cpc_usermeta_change_update'])):
        
                // First do nonce check to ensure being posted from trusted source
                if ( 
                    ! isset( $_POST['cpc_usermeta_change_nonce_field'] ) 
                    || ! wp_verify_nonce( $_POST['cpc_usermeta_change_nonce_field'], 'cpc_usermeta_change_nonce' ) 
                ) {        
                    
                    $html .= '<div class="cpc_error">'.__('Das Sicherheitsfeld wurde nicht validiert').'</div>';
                    
                } else {

                    if ($display_name = $_POST['cpccom_display_name'])
                        wp_update_user( array ( 'ID' => $user_id, 'display_name' => $display_name ) ) ;

                    if ($first_name = $_POST['cpccom_firstname'])
                        wp_update_user( array ( 'ID' => $user_id, 'first_name' => $first_name ) ) ;
                    if ($last_name = $_POST['cpccom_lastname'])
                        wp_update_user( array ( 'ID' => $user_id, 'last_name' => $last_name ) ) ;

                    if ($user_email = $_POST['cpccom_email'])
                        wp_update_user( array ( 'ID' => $user_id, 'user_email' => $user_email ) ) ;

                    if (isset($_POST['cpccom_home'])):
						$home_form = str_replace('"', '', (str_replace('\'', '', (str_replace('<', '', (str_replace('>', '', strip_tags($_POST['cpccom_home']))))))));
						update_user_meta( $user_id, 'cpccom_home', $home_form);		        
                    endif;

                    if (isset($_POST['cpccom_country'])):
						$country_form = str_replace('"', '', (str_replace('\'', '', (str_replace('<', '', (str_replace('>', '', strip_tags($_POST['cpccom_country']))))))));
						update_user_meta( $user_id, 'cpccom_country', $country_form);
                    endif;

                    // Update lat and long from location fields?
                    if (isset($_POST['cpccom_home']) && isset($_POST['cpccom_country'])):

                        // Change spaces to %20 for Google maps API and geo-code
                        $city = str_replace(' ','%20',$_POST['cpccom_home']);
                        $country_value = str_replace(' ','%20',$_POST['cpccom_country']);
                        $fgc = 'http://maps.googleapis.com/maps/api/geocode/json?address='.$city.'+'.$country_value.'&sensor=false';

                        if ($json = @file_get_contents($fgc) ):
                            $json_output = json_decode($json, true);
                            
                            @$lat = $json_output['results'][0]['geometry']['location']['lat'];
                            @$lng = $json_output['results'][0]['geometry']['location']['lng'];

                            update_user_meta($user_id, 'cpccom_lat', $lat);
                            update_user_meta($user_id, 'cpccom_long', $lng);

                        else:

                            update_user_meta($user_id, 'cpccom_lat', 0);
                            update_user_meta($user_id, 'cpccom_long', 0);

                        endif;

                    else:
                        // can't find out lat and long so save as 0 so (for example) they still appear in the directory feature
                        update_user_meta($user_id, 'cpccom_lat', 0);
                        update_user_meta($user_id, 'cpccom_long', 0);                        
                    endif;                    

                    if (isset($_POST['cpccom_password']) && $_POST['cpccom_password'] != ''):
                        $pw = $_POST['cpccom_password'];
                        wp_set_password($pw, $user_id);
                        $html .= '<div class="cpc_success password_msg">'.$password_msg.'</div>';
                    endif;

                    $refresh = false;
                    if (isset($_POST['cpccom_lang'])):
                        $user_lang = get_user_meta($user_id, 'cpccom_lang', true);
                        if ($user_lang != $_POST['cpccom_lang']) $refresh = true;
                        if ($_POST['cpccom_lang']):
                            update_user_meta( $user_id, 'cpccom_lang', $_POST['cpccom_lang']);
                        else:
                            delete_user_meta($user_id, 'cpccom_lang');
                        endif;             
                    endif;

                    do_action( 'cpc_usermeta_change_hook', $user_id, $atts, $_POST, $_FILES );

                    if ($refresh):
                        // .. need a refresh to change language (wait 1 second for page to load)
                        echo '<script>';
                            echo "window.setTimeout(cpc_reload_page,1000);";
                            echo "function cpc_reload_page() {";
                                echo "alert('".__('Die Seite wird für die von Dir gewählte Sprache aktualisiert.', CPC2_TEXT_DOMAIN)."');";
                                echo "window.location.reload();"; 
                            echo "}";
                        echo '</script>';
                    endif;
                } // End of nonce check

			endif;

			if (!isset($_POST['cpccom_password']) || $_POST['cpccom_password'] == ''):

                $the_user = get_user_by('id', $user_id);

                $value = isset($_POST['cpccom_display_name']) ? stripslashes($_POST['cpccom_display_name']) : $the_user->display_name;
                    $form_html = '<div class="cpc_usermeta_change_item">';
                    $form_html .= '<div class="'.$meta_class.'">'.$displayname.'</div>';
                    $form_html .= '<input type="text" id="cpccom_display_name" class="cpc_mandatory_field" name="cpccom_display_name" value="'.$value.'" />';
                    $form_html .= $mandatory;
                    $form_html .= '</div>';
                    $tab_row['tab'] = isset($tabs_array['cpc_comfile_tab_names']) ? $tabs_array['cpc_comfile_tab_names'] : 1;
                    $tab_row['html'] = $form_html;        
                    $tab_row['mandatory'] = false;     
                    array_push($tabs,$tab_row);        

                if ($name && $show_name):
                    $firstname = isset($_POST['cpccom_firstname']) ? $_POST['cpccom_firstname'] : $the_user->first_name;
                    $lastname = isset($_POST['cpccom_lastname']) ? $_POST['cpccom_lastname'] : $the_user->last_name;
                    $form_html = '<div class="cpc_usermeta_change_item">';
                        $form_html .= '<div class="'.$meta_class.'">'.$name.'</div>';
                        $form_html .= '<div class="cpc_usermeta_change_name"><input type="text" name="cpccom_firstname" id="cpccom_firstname" class="cpc_mandatory_field" value="'.$firstname.'"> ';
                        $form_html .= '<input type="text" name="cpccom_lastname" id="cpccom_lastname" class="cpc_mandatory_field" value="'.$lastname.'">'.$mandatory.'</div>';
                    $form_html .= '</div>';
                    $tab_row['tab'] = isset($tabs_array['cpc_comfile_tab_names']) ? $tabs_array['cpc_comfile_tab_names'] : 1;
                    $tab_row['html'] = $form_html;        
                    $tab_row['mandatory'] = false;     
                    array_push($tabs,$tab_row);                
                endif;

                $value = isset($_POST['cpccom_email']) ? $_POST['cpccom_email'] : $the_user->user_email;
                    $form_html = '<div class="cpc_usermeta_change_item">';
                    $form_html .= '<div class="'.$meta_class.'">'.$email.'</div>';
                    $form_html .= '<input type="text" id="cpccom_email" class="cpc_mandatory_field" name="cpccom_email" value="'.$value.'" />';
                    $form_html .= $mandatory;
                    $form_html .= '</div>';
                    $tab_row['tab'] = isset($tabs_array['cpc_comfile_tab_email']) ? $tabs_array['cpc_comfile_tab_email'] : 1;
                    $tab_row['html'] = $form_html;        
                    $tab_row['mandatory'] = false;     
                    array_push($tabs,$tab_row);                

                $value = get_user_meta( $user_id, 'cpccom_country', true );
					$country_value = $value;
                    if ($country && $show_country):
                        $form_html = '<div id="cpccom_country_div" class="cpc_usermeta_change_item">';
                        $form_html .= '<div class="'.$meta_class.'">'.$country.'</div>';
						$country_id = get_user_meta( $user_id, 'cpccom_country_id', true ) ? get_user_meta( $user_id, 'cpccom_country_id', true ) : 0;
						$form_html .= '<input style="display:none" type="text" id="cpccom_country_id" name="cpccom_country_id" value="'.$country_id.'" />';
                        if (!$value && $country_default) $value = $country_default;
                        $form_html .= '<input type="text" id="cpccom_country" ';
                            if ($country_mandatory) $form_html .= 'class="cpc_mandatory_field" ';        
                            $form_html .= 'name="cpccom_country" value="'.$value.'" />';
                        if ($country_mandatory) $form_html .= $mandatory;
                        $form_html .= '</div>';
                        $tab_row['tab'] = isset($tabs_array['cpc_comfile_tab_location']) ? $tabs_array['cpc_comfile_tab_location'] : 1;
                        //$form_html .= '<div id="cpccom_geo" class="cpc_usermeta_change_item">'.__('Geo co-ordinates:', CPC2_TEXT_DOMAIN).' '.get_user_meta($user_id, 'cpccom_lat', true).'/'.get_user_meta($user_id, 'cpccom_long', true).'</div>';
                        $tab_row['html'] = $form_html;      
                        $tab_row['mandatory'] = $country_mandatory;
                        array_push($tabs,$tab_row);                
                    endif;
					
                $value = get_user_meta( $user_id, 'cpccom_home', true );
                    if ($town && $show_town):
                        $form_html = '<div id="cpccom_home_div" class="cpc_usermeta_change_item">';
                        $form_html .= '<div class="'.$meta_class.'">'.$town.'</div>';
                        if (!$value && $town_default) $value = $town_default;
                        $form_html .= '<input type="text" id="cpccom_home" ';
                            if ($town_mandatory) $form_html .= 'class="cpc_mandatory_field" ';
                            $form_html .= 'name="cpccom_home" value="'.$value.'" />';
                        if ($town_mandatory) $form_html .= $mandatory;
                        $form_html .= '</div>';
                        $tab_row['tab'] = isset($tabs_array['cpc_comfile_tab_location']) ? $tabs_array['cpc_comfile_tab_location'] : 1;
                        $tab_row['html'] = $form_html; 
                        $tab_row['mandatory'] = $town_mandatory;
                        array_push($tabs,$tab_row);                
                    endif;
        
                // Language select
                    $cpc_com_lang = ($l = get_option('cpc_com_lang')) ? $l : false;
                    if ($cpc_com_lang):
                        $user_lang = get_user_meta($user_id, 'cpccom_lang', true);

                        $form_html = '<div class="cpc_usermeta_change_item">';
                        $form_html .= '<div class="'.$meta_class.'">'.$language.'</div>';
                        $form_html .= '<select name="cpccom_lang" id="cpccom_lang" style="width:200px">';
                        $options = '';

                        $langs = explode("\n", str_replace("\r", "", $cpc_com_lang));
                        foreach ($langs as $lang):
                            if (strpos($lang, ',') !== false):
                                list($text, $value) = explode(',', $lang);
                            else:
                                $text = $lang;
                                $value = '';
                            endif;
                            $options .= '<option value="'.$value.'"';
                                if ($user_lang == $value) $options .= ' SELECTED';
                                $options .= '>'.$text.'</option>';
                        endforeach;

                        $form_html .= $options;
                        $form_html .= '</select>';
                        $form_html .= '</div>';
                        $tab_row['tab'] = isset($tabs_array['cpc_comfile_tab_lang']) ? $tabs_array['cpc_comfile_tab_lang'] : 1;
                        $tab_row['html'] = $form_html;        
                        $tab_row['mandatory'] = false;     
                        array_push($tabs,$tab_row);
                    endif;                

                // Password change
                    $form_html = '<div class="cpc_usermeta_change_item">';
                    $form_html .= '<div class="'.$meta_class.'">'.$password.'</div>';
                    $form_html .= '<input type="password" name="cpccom_password" id="cpccom_password" />';
                    $form_html .= '<div class="'.$meta_class.'">'.$password2.'</div>';
                    $form_html .= '<input type="password" name="cpccom_password2" id="cpccom_password2" />';
                    if (!get_option('cpc_password_strength_meter')) $form_html .= '<div id="cpc_password_strength_result" style="display:none"></div>';
                    $form_html .= '</div>';
                    $tab_row['tab'] = isset($tabs_array['cpc_comfile_tab_password']) ? $tabs_array['cpc_comfile_tab_password'] : 1;
                    $tab_row['html'] = $form_html;  
                    $tab_row['mandatory'] = false;     
                    array_push($tabs,$tab_row);                

                // Anything else?
                $tabs = apply_filters( 'cpc_usermeta_change_filter', $tabs, $atts, $user_id );

			endif;

            // Build output
            $cpc_comfile_tab1 = (isset($tabs_array['cpc_comfile_tab1'])) ? $tabs_array['cpc_comfile_tab1'] : false;
            $cpc_comfile_tab2 = (isset($tabs_array['cpc_comfile_tab2'])) ? $tabs_array['cpc_comfile_tab2'] : false;
            $cpc_comfile_tab3 = (isset($tabs_array['cpc_comfile_tab3'])) ? $tabs_array['cpc_comfile_tab3'] : false;
            $cpc_comfile_tab4 = (isset($tabs_array['cpc_comfile_tab4'])) ? $tabs_array['cpc_comfile_tab4'] : false;
            $cpc_comfile_tab5 = (isset($tabs_array['cpc_comfile_tab5'])) ? $tabs_array['cpc_comfile_tab5'] : false;
            $cpc_comfile_tab6 = (isset($tabs_array['cpc_comfile_tab6'])) ? $tabs_array['cpc_comfile_tab6'] : false;
            $cpc_comfile_tab7 = (isset($tabs_array['cpc_comfile_tab7'])) ? $tabs_array['cpc_comfile_tab7'] : false;
            $cpc_comfile_tab8 = (isset($tabs_array['cpc_comfile_tab8'])) ? $tabs_array['cpc_comfile_tab8'] : false;
            $cpc_comfile_tab9 = (isset($tabs_array['cpc_comfile_tab9'])) ? $tabs_array['cpc_comfile_tab9'] : false;
            $cpc_comfile_tab10 = (isset($tabs_array['cpc_comfile_tab10'])) ? $tabs_array['cpc_comfile_tab10'] : false;

            $default_tab = (isset($tabs_array['cpc_comfile_tab_default_tab'])) ? $tabs_array['cpc_comfile_tab_default_tab'] : 1;
            $tab_ptr = 1;
            $max_tabs = false;
            if ($cpc_comfile_tab1) $max_tabs = 1;
            if ($cpc_comfile_tab2) $max_tabs = 2;
            if ($cpc_comfile_tab3) $max_tabs = 3;
            if ($cpc_comfile_tab4) $max_tabs = 4;
            if ($cpc_comfile_tab5) $max_tabs = 5;
            if ($cpc_comfile_tab6) $max_tabs = 6;
            if ($cpc_comfile_tab7) $max_tabs = 7;
            if ($cpc_comfile_tab8) $max_tabs = 8;
            if ($cpc_comfile_tab9) $max_tabs = 9;
            if ($cpc_comfile_tab10) $max_tabs = 10;
        
            // Show form
            $html .= '<form enctype="multipart/form-data" id="cpc_usermeta_change" action="#" method="POST">';
                $html .= '<input type="hidden" name="cpc_usermeta_change_update" value="yes" />';
                $html .= wp_nonce_field( 'cpc_usermeta_change_nonce', 'cpc_usermeta_change_nonce_field' );

                if ($max_tabs):
        
                    $html .= '<div class="cpc-tabs">';
                        $html .= '<ul class="cpc-tab-links">';
                            $html .= '<li id="cpc-tab1"'.($default_tab == 1 ? ' class="active"' : '').'><a href="#tab1">'.$cpc_comfile_tab1.'</a></li>';
                            if ($cpc_comfile_tab2) $html .= '<li id="cpc-tab2"'.($default_tab == 2 ? ' class="active"' : '').'><a href="#tab2">'.$cpc_comfile_tab2.'</a></li>';
                            if ($cpc_comfile_tab3) $html .= '<li id="cpc-tab3"'.($default_tab == 3 ? ' class="active"' : '').'><a href="#tab3">'.$cpc_comfile_tab3.'</a></li>';
                            if ($cpc_comfile_tab4) $html .= '<li id="cpc-tab4"'.($default_tab == 4 ? ' class="active"' : '').'><a href="#tab4">'.$cpc_comfile_tab4.'</a></li>';
                            if ($cpc_comfile_tab5) $html .= '<li id="cpc-tab5"'.($default_tab == 5 ? ' class="active"' : '').'><a href="#tab5">'.$cpc_comfile_tab5.'</a></li>';
                            if ($cpc_comfile_tab6) $html .= '<li id="cpc-tab6"'.($default_tab == 6 ? ' class="active"' : '').'><a href="#tab6">'.$cpc_comfile_tab6.'</a></li>';
                            if ($cpc_comfile_tab7) $html .= '<li id="cpc-tab7"'.($default_tab == 7 ? ' class="active"' : '').'><a href="#tab7">'.$cpc_comfile_tab7.'</a></li>';
                            if ($cpc_comfile_tab8) $html .= '<li id="cpc-tab8"'.($default_tab == 8 ? ' class="active"' : '').'><a href="#tab8">'.$cpc_comfile_tab8.'</a></li>';
                            if ($cpc_comfile_tab9) $html .= '<li id="cpc-tab9"'.($default_tab == 9 ? ' class="active"' : '').'><a href="#tab9">'.$cpc_comfile_tab9.'</a></li>';
                            if ($cpc_comfile_tab10) $html .= '<li id="cpc-tab10"'.($default_tab == 10 ? ' class="active"' : '').'><a href="#tab10">'.$cpc_comfile_tab10.'</a></li>';
                        $html .= '</ul>';

                        $html .= '<div class="cpc-tab-content">';

                            while ($tab_ptr <= $max_tabs)
                            {
                                $html .= '<div id="tab'.$tab_ptr.'" class="cpc-tab ';
                                if ($tab_ptr == $default_tab) $html .= 'active';
                                $html .= '"><div id="cpc-tab-content-'.$tab_ptr.'" class="cpc-tab-content-inner">';
                                foreach ($tabs as $tab):
                                    if ($tab['tab'] == $tab_ptr):
                                        $html .= '<p>'.$tab['html'].'</p>';     
                                    endif;
                                endforeach;
                                $html .= '</div></div>';
                                $tab_ptr++;
                            }


                        $html .= '</div>';
                    $html .= '</div>';
        
                else:
        
                    while ($tab_ptr <= 10)
                    {
                        foreach ($tabs as $tab):
                            if ($tab['tab'] == $tab_ptr):
                                $html .= $tab['html'];  
                            endif;
                        endforeach;
                        $tab_ptr++;
                    }
        
                endif;

                $html .= '<div id="cpc_required_msg" class="cpc_error" style="display:none">'.$required_msg.'</div>';
                $html .= '<button type="submit" id="cpc_usermeta_change_submit" class="cpc_button '.$class.'">'.$label.'</button>';
            $html .= '</form>';
        

		endif;

		if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_usermeta_change', $before, $after, $styles, $values);

    } else {

        if (!is_user_logged_in() && $logged_out_msg):
            $query = cpc_query_mark(get_bloginfo('url').$login_url);
            if ($login_url) $html .= sprintf('<a href="%s%s%sredirect=%s">', get_bloginfo('url'), $login_url, $query, cpc_root( $_SERVER['REQUEST_URI'] ));
            $html .= $logged_out_msg;
            if ($login_url) $html .= '</a>';
        endif;
    
    }
    
	return $html;

}

function cpc_usermeta_change_link($atts) {

	// Init
	cpc_usermeta_init();

	global $current_user;
	$html = '';

	if (is_user_logged_in()) {

		// Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_usermeta_change_link');    
		extract( shortcode_atts( array(
			'text' => cpc_get_shortcode_value($values, 'cpc_usermeta_change_link-text', __('Profil bearbeiten', CPC2_TEXT_DOMAIN)),
			'user_id' => 0,
			'styles' => true,
            'after' => '',
			'before' => '',
		), $atts, 'cpc_usermeta_change_link' ) );

		if (!$user_id)
			$user_id = cpc_get_user_id();

        if ($user_id):

    		if ($current_user->ID == $user_id || current_user_can('manage_options')):
    			$url = get_page_link(get_option('cpccom_edit_profile_page'));
    			$html .= '<a href="'.$url.cpc_query_mark($url).'user_id='.$user_id.'">'.$text.'</a>';
    		endif;

        endif;
        
		if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_usermeta_change_link', $before, $after, $styles, $values);

	}

	return $html;

}

function cpc_close_account($atts) {

	// Init
	cpc_usermeta_init();

	global $current_user;
	$html = '';

	if (is_user_logged_in()) {
        
		// Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_close_account');    
		extract( shortcode_atts( array(
			'class' => cpc_get_shortcode_value($values, 'cpc_close_account-class', ''),
			'label' => cpc_get_shortcode_value($values, 'cpc_close_account-label', __('Konto schließen', CPC2_TEXT_DOMAIN)),
			'are_you_sure_text' => cpc_get_shortcode_value($values, 'cpc_close_account-are_you_sure_text', __('Bist du sicher? Du kannst ein geschlossenes Konto nicht erneut eröffnen.', CPC2_TEXT_DOMAIN)),
			'logout_text' => cpc_get_shortcode_value($values, 'cpc_close_account-logout_text', __('Dein Konto wurde geschlossen.', CPC2_TEXT_DOMAIN)),
            'url' => cpc_get_shortcode_value($values, 'cpc_close_account-url', '/'), // set URL to go to after de-activation, probably a logout page, or '' for current page
			'styles' => true,
            'after' => '',
			'before' => '',

		), $atts, 'cpc_usermeta' ) );
		
        $user_id = cpc_get_user_id();
        if ($user_id == $current_user->ID || current_user_can('manage_options')):

            $html .= '<input type="button" data-sure="'.$are_you_sure_text.'" data-url="'.$url.'" data-logout="'.$logout_text.'" id="cpc_close_account" data-user="'.$user_id.'" class="cpc_button '.$class.'" value="'.$label.'" />';

            if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_close_account', $before, $after, $styles, $values);

        endif;
        
            
    }

    return $html;
}

function cpc_join_site($atts) {
    
    $html = '';

    // Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_join_site');    
    extract( shortcode_atts( array(
        'class' => cpc_get_shortcode_value($values, 'cpc_join_site-label', ''),
        'label' => cpc_get_shortcode_value($values, 'cpc_join_site-label', __('Treten dieser Webseite bei', CPC2_TEXT_DOMAIN)),
        'style' => cpc_get_shortcode_value($values, 'cpc_join_site-label', 'button'), // button|text
        'styles' => true,
        'after' => '',
        'before' => '',
    ), $atts, 'cpc_join_site' ) );
    
    if (is_multisite()):
    
        if ($style == 'button'):
            $html .= '<input type="button" class="cpc_button '.$class.'" id="cpc_join_site" value="'.$label.'" />';
        else:
            $html .= '<a href="javascript:void(0);" id="cpc_join_site">'.$label.'</a>';
        endif;

        if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_join_site', $before, $after, $styles, $values);

    endif;
    
    return $html;
    
}


function cpc_no_user_check($atts){

    // Init
    cpc_usermeta_init();

    global $current_user;
    $html = '';
    
    // Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_no_user_check');
    extract( shortcode_atts( array(
        'not_found_msg' => cpc_get_shortcode_value($values, 'cpc_no_user_check-not_found_msg', __('Benutzer existiert nicht!', CPC2_TEXT_DOMAIN)),
        'styles' => true,
        'after' => '',
        'before' => '',        
    ), $atts, 'cpc_no_user_check' ) );
    
    if (get_query_var('user')):
        $username = get_query_var('user');
        $get_user = get_user_by('login', urldecode($username));
        $user_id = $get_user ? $get_user->ID : 0;
    else:
        $username = false;
        if (isset($_GET['user_id'])):
            $user_id = $_GET['user_id'];
        else:
            $user_id = $current_user ? $current_user->ID : 0;
        endif;
    endif;

    $user_id = cpc_get_user_id();

    if (!$user_id):
        $html .= '<div id="cpc_user_not_found">';
        $html .= $not_found_msg;
        if ($username) $html .= ' ('.$username.')';
        $html .= '</div>';
    endif;

    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_no_user_check', $before, $after, $styles, $values);    

    return $html;

}

// Show content if users are friends
function cpc_is_friend_content($atts, $content="") {

    if (strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false):

        // Init
        cpc_usermeta_init();

        $html = '';
        global $current_user;

        // Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_is_friend_content');
        extract( shortcode_atts( array(
            'not_friends_msg' => cpc_get_shortcode_value($values, 'cpc_is_friend_content-not_friends_msg', __('Tut mir leid, ihr seid keine Freunde.', CPC2_TEXT_DOMAIN)),
            'include_friendship_action' => cpc_get_shortcode_value($values, 'cpc_is_friend_content-include_friendship_action', true),
            'friend_add_label' => cpc_get_shortcode_value($values, 'cpc_is_friend_content-friend_add_label', __('Freundschaft schließen', CPC2_TEXT_DOMAIN)),
            'friend_cancel_request_label' => cpc_get_shortcode_value($values, 'cpc_is_friend_content-friend_cancel_request_label', __('Anfrage abbrechen', CPC2_TEXT_DOMAIN)),     
            'accept_request_label' => cpc_get_shortcode_value($values, 'cpc_is_friend_content-accept_request_label', __('Akzeptiere Freundschaft', CPC2_TEXT_DOMAIN)),
            'reject_request_label' => cpc_get_shortcode_value($values, 'cpc_is_friend_content-reject_request_label', __('Ablehnen', CPC2_TEXT_DOMAIN)),
            'request_made_msg' => cpc_get_shortcode_value($values, 'cpc_is_friend_content-request_made_msg', __('Du hast eine Freundschaftsanfrage von diesem Benutzer erhalten.', CPC2_TEXT_DOMAIN)),
            'friendship_class' => cpc_get_shortcode_value($values, 'cpc_is_friend_content-friendship_class', ''),
            'styles' => true,
            'after' => '',
            'before' => '',        
        ), $atts, 'cpc_is_friend_content' ) );

        $user_id = cpc_get_user_id();    

        if ($user_id):

            $friends = cpc_are_friends($current_user->ID, $user_id);

            if ($friends['status'] == 'publish'):

                // Shortcode parameters
                extract( shortcode_atts( array(
                    'before' => '',
                    'after' => '',
                ), $atts, 'cpc_user_exists_content' ) );

                $html .= do_shortcode($content);

            else:

                $html .= '<div id="cpc_is_friend_content">'.$not_friends_msg.'</div>';

                if ($include_friendship_action):
                    $friend_cancel_label = '';

                    $item_html = '<div class="cpc_is_friend_content_item_friends_status">';
                        if ($friends['status']):
                            if ($friends['status'] == 'pending' && $friends['direction'] == 'from'):
                                if ($user_id != $current_user->ID):
                                    // Request made to this user
                                    $item_html .= '<div id="cpc_friendship_request_made">'.$request_made_msg.'</div>';
                                    $item_html .= '<button type="submit" rel="'.$friends['ID'].'" class="cpc_button cpc_pending_friends_accept '.$friendship_class.'">'.$accept_request_label.'</button>';
                                    $item_html .= '<button type="submit" rel="'.$friends['ID'].'" class="cpc_button cpc_pending_friends_reject '.$friendship_class.'">'.$reject_request_label.'</button>';
                                else:
                                    $item_html .= cpc_friends_add_button(array('user_id' => $user_id, 'label' => $friend_add_label, 'cancel_label' => $friend_cancel_label, 'cancel_request_label' => $friend_cancel_request_label));
                                endif;
                            else:
                                // Request made from this user
                                $item_html .= cpc_friends_add_button(array('user_id' => $user_id, 'label' => $friend_add_label, 'cancel_label' => $friend_cancel_label, 'cancel_request_label' => $friend_cancel_request_label));
                            endif;
                        else:
                            // Not friends
                            $item_html .= cpc_friends_add_button(array('user_id' => $user_id, 'label' => $friend_add_label, 'cancel_label' => $friend_cancel_label, 'cancel_request_label' => $friend_cancel_request_label));
                        endif;
                    $item_html .= '</div>';
                    $html .= $item_html;
                endif;


            endif;

        endif;
    
    endif;

    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_is_friend_content', $before, $after, $styles, $values);        

    return $html;    

}

// Show content if user exists
function cpc_user_exists_content($atts, $content="") {

    // Init
    cpc_usermeta_init();

    $html = '';
    global $current_user;

    // Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_user_exists_content');
    extract( shortcode_atts( array(
        'not_found_msg' => cpc_get_shortcode_value($values, 'cpc_user_exists_content-not_found_msg', __('Benutzer existiert nicht!', CPC2_TEXT_DOMAIN)),
        'styles' => true,
        'after' => '',
        'before' => '',        
    ), $atts, 'cpc_user_exists_content' ) );

    $user_id = cpc_get_user_id();    

    if ( $user_id ):
    
        $html .= do_shortcode($content);

    else:

        $html .= '<div id="cpc_user_exists_content">'.$not_found_msg.'</div>';

    endif;

    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_user_exists_content', $before, $after, $styles, $values);        

    return $html;    

}

// Show content if no user logged in
function cpc_not_logged_in($atts, $content="") {

    // Init
    cpc_usermeta_init();

    // Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_not_logged_in');
    extract( shortcode_atts( array(
        'styles' => true,
        'after' => '',
        'before' => '',        
    ), $atts, 'cpc_not_logged_in' ) );    

    $html = '';
    global $current_user;

    if ( !is_user_logged_in() )    
        $html .= do_shortcode($content);

    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_not_logged_in', $before, $after, $styles, $values);        

    return $html;    

}

// Show content if user is logged in
function cpc_is_logged_in($atts, $content="") {

    // Init
    cpc_usermeta_init();

    // Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_is_logged_in');
    extract( shortcode_atts( array(
        'styles' => true,
        'after' => '',
        'before' => '',        
    ), $atts, 'cpc_is_logged_in' ) );        

    $html = '';
    global $current_user;

    if ( is_user_logged_in() )    
        $html .= do_shortcode($content);

    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_is_logged_in', $before, $after, $styles, $values);        

    return $html;    

}

// Backup for [cpc-activity-page] if activity is not enabled
function cpc_backup_activity_page($atts){

	// Init
	cpc_usermeta_init();

    global $current_user;
	$html = '';
    
	// Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_activity_page');
	extract( shortcode_atts( array(
		'user_id' => false,
        'mimic_user_id' => false,
		'user_avatar_size' => cpc_get_shortcode_value($values, 'cpc_activity_page-user_avatar_size', 150),
		'map_style' => cpc_get_shortcode_value($values, 'cpc_activity_page-map_style', 'static'),
		'map_size' => cpc_get_shortcode_value($values, 'cpc_activity_page-map_size', '150,150'),
		'map_zoom' => cpc_get_shortcode_value($values, 'cpc_activity_page-map_zoom', 4),
		'town_label' => cpc_get_shortcode_value($values, 'cpc_activity_page-town_label', __('Stadt/Gemeinde', CPC2_TEXT_DOMAIN)),
        'country_label' => cpc_get_shortcode_value($values, 'cpc_activity_page-country_label', __('Land', CPC2_TEXT_DOMAIN)),
        'requests_label' => cpc_get_shortcode_value($values, 'cpc_activity_page-requests_label', __('Freundschaftsanfragen', CPC2_TEXT_DOMAIN)),
        'styles' => true,
	), $atts, 'cpc_activity_page' ) );
    
	if (!$user_id):
        $user_id = cpc_get_user_id();
        $this_user = $current_user->ID;
    else:
        if ($mimic_user_id):
            $this_user = $user_id;
        else:
            $this_user = $current_user->ID;
        endif;
    endif;

	$html .= '<style>.cpc_avatar img { border-radius:0px; }</style>';
	$html .= cpc_display_name(array('user_id'=>$user_id, 'before'=>'<div id="cpc_display_name" style="font-size:2.5em; line-height:2.5em; margin-bottom:20px;">', 'after'=>'</div>'));
	$html .= '<div style="overflow:auto;overflow-y:hidden;margin-bottom:15px">';
    $html .= '<div id="cpc_activity_page_avatar" style="float: left; margin-right: 20px;">';
    if (strpos(CPC_CORE_PLUGINS, 'core-avatar') !== false):
        $html .= cpc_avatar(array('user_id'=>$user_id, 'change_link'=>1, 'size'=>$user_avatar_size, 'before'=>'<div id="cpc_display_avatar" style="float:left; margin-right:15px;">', 'after'=>'</div>'));
    else:
        $html .= '<div id="cpc_display_avatar" style="float:left; margin-right:15px;">';
            $html .= get_avatar($user_id, $user_avatar_size);
        $html .= '</div>';
    endif;
    if (strpos(CPC_CORE_PLUGINS, 'core-profile') !== false):
        $html .= '<div style="float:left;margin-right:15px;">';
        $html .= cpc_usermeta(array('user_id'=>$user_id, 'meta'=>'cpccom_home', 'before'=>'<strong>'.$town_label.'</strong><br />', 'after'=>'<br />'));
        $html .= cpc_usermeta(array('user_id'=>$user_id, 'meta'=>'cpccom_country', 'before'=>'<strong>'.$country_label.'</strong><br />', 'after'=>'<br />'));
        $html .= cpc_usermeta_change_link($atts);
    endif;
	$html .= '</div>';
    if (strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false):
        $html .= '<div id="cpc_display_friend_requests" style="margin-left:10px;float:left;min-width:200px;">';
        $html .= cpc_friends_pending(array('user_id'=>$user_id, 'count' => 10, 'before'=>'<div class="cpc_20px_gap"><div style="font-weight:bold;margin-bottom: 10px">'.$requests_label.'</div>', 'after'=>'</div>'));
        $html .= cpc_friends_add_button(array());
        $html .= '</div>';
    endif;
	$html .= '</div>';

    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_activity_page', '', '', $styles, $values);    
    
	return $html;

}

// Displays when last active
function cpc_last_active($atts) {
    // Init
    cpc_usermeta_init();

    $html = '';
    if (is_user_logged_in()) {
    
        // Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_last_active');
        extract( shortcode_atts( array(
            'user_id' => cpc_get_shortcode_value($values, 'cpc_last_active-user_id', ''),
            'show_as_date' => cpc_get_shortcode_value($values, 'cpc_last_active-show_as_date', false),                    
            'date_format' => cpc_get_shortcode_value($values, 'cpc_last_active-date_format', __('vor %s', CPC2_TEXT_DOMAIN)),
            'not_active_msg' => cpc_get_shortcode_value($values, 'cpc_last_active-not_logged_in_msg', __('In letzter Zeit nicht aktiv.', CPC2_TEXT_DOMAIN)),                    
            'after' => '',
            'before' => '',            
            'styles' => true,
        ), $atts, 'cpc_last_active' ) );
        
        if ($user_id == 'user'):
            global $current_user;
            $user_id = $current_user->ID;
        else:
            if (!$user_id) $user_id = cpc_get_user_id();
        endif;    

        $last_active = get_user_meta($user_id, 'cpccom_last_active', true);
        $html .= '<span class="cpc_last_active">';
        if ($last_active):
            $last_active_date = new DateTime();
            $last_active_date->setTimestamp(strtotime($last_active));
            if ($show_as_date):
                $html .= date_format($last_active_date, $date_format);
            else:
                $from = strtotime($last_active_date->format('Y-m-d H:i:s'));
                $to = current_time('timestamp', 1);
                $html .= sprintf($date_format, human_time_diff($from, $to));
            endif;
        else:
            $html .= $not_active_msg;
        endif;
        $html .= '</span>';

        if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_last_active', $before, $after, $styles, $values);
        
    }
    
    return $html;
}

// Displays when last logged in
function cpc_last_logged_in($atts) {
    // Init
    cpc_usermeta_init();

    $html = '';
    
    if (is_user_logged_in()) {
    
        // Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_last_logged_in');
        extract( shortcode_atts( array(
            'user_id' => cpc_get_shortcode_value($values, 'cpc_last_logged_in-user_id', ''),
            'show_as_date' => cpc_get_shortcode_value($values, 'cpc_last_logged_in-show_as_date', false),                    
            'date_format' => cpc_get_shortcode_value($values, 'cpc_last_logged_in-date_format', __('vor %s', CPC2_TEXT_DOMAIN)),
            'previous' => cpc_get_shortcode_value($values, 'cpc_last_logged_in-previous', false),                    
            'not_logged_in_msg' => cpc_get_shortcode_value($values, 'cpc_last_logged_in-not_logged_in_msg', __('Kürzlich nicht angemeldet.', CPC2_TEXT_DOMAIN)),
            'after' => '',
            'before' => '',            
            'styles' => true,
        ), $atts, 'cpc_last_logged_in' ) );

        if ($user_id == 'user'):
            global $current_user;
            $user_id = $current_user->ID;
        else:
            if (!$user_id) $user_id = cpc_get_user_id();
        endif;    

        if (!$user_id) $user_id = cpc_get_user_id();

        $last_logged_in = !$previous ? get_user_meta($user_id, 'cpccom_last_login', true) : get_user_meta($user_id, 'cpccom_previous_login', true);
        $html .= '<span class="cpc_last_logged_in">';
        if ($last_logged_in):
            $last_logged_in_date = new DateTime();
            $last_logged_in_date->setTimestamp(strtotime($last_logged_in));
            if ($show_as_date):
                $html .= date_format($last_logged_in_date, $date_format);
            else:
                $from = strtotime($last_logged_in_date->format('Y-m-d H:i:s'));
                $to = current_time('timestamp', 1);
                $html .= sprintf($date_format, human_time_diff($from, $to));
            endif;
        else:
            $html .= $not_logged_in_msg;
        endif;
        $html .= '</span>';

        if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_last_logged_in', $before, $after, $styles, $values);
        
    }
    
    return $html;

}

add_shortcode(CPC_PREFIX.'-user-id', 'cpc_user_id');
add_shortcode(CPC_PREFIX.'-usermeta', 'cpc_usermeta');
add_shortcode(CPC_PREFIX.'-no-user-check', 'cpc_no_user_check');
add_shortcode(CPC_PREFIX.'-is-friend-content', 'cpc_is_friend_content');
add_shortcode(CPC_PREFIX.'-user-exists-content', 'cpc_user_exists_content');
add_shortcode(CPC_PREFIX.'-is-logged-in', 'cpc_is_logged_in');
add_shortcode(CPC_PREFIX.'-not-logged-in', 'cpc_not_logged_in');
add_shortcode(CPC_PREFIX.'-usermeta-change', 'cpc_usermeta_change');
add_shortcode(CPC_PREFIX.'-usermeta-change-link', 'cpc_usermeta_change_link');
add_shortcode(CPC_PREFIX.'-usermeta-button', 'cpc_usermeta_button');
add_shortcode(CPC_PREFIX.'-close-account', 'cpc_close_account');
add_shortcode(CPC_PREFIX.'-join-site', 'cpc_join_site');
add_shortcode(CPC_PREFIX.'-last-active', 'cpc_last_active');
add_shortcode(CPC_PREFIX.'-last-logged-in', 'cpc_last_logged_in');

// Backup for [cpc-activity-page]
if (strpos(CPC_CORE_PLUGINS, 'core-activity') === false)
    add_shortcode(CPC_PREFIX.'-activity-page', 'cpc_backup_activity_page');

?>
