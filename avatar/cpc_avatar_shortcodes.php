<?php

/* **** */ /* INIT */ /* **** */

function cpc_avatar_init() {

	wp_enqueue_script('cpc-avatar-js', plugins_url('cpc_avatar.js', __FILE__), array('jquery'));	
	wp_enqueue_style('user-avatar', plugins_url('user-avatar.css', __FILE__), 'css');
	wp_enqueue_style('imgareaselect');
	wp_enqueue_script('imgareaselect');
	wp_enqueue_style('psource-modal', plugins_url('../assets/psource-ui/modal/psource-modal.css', __FILE__));
    wp_enqueue_script('psource-modal', plugins_url('../assets/psource-ui/modal/psource-modal.js', __FILE__), array(), false, true);
	do_action('cpc_avatar_init_hook');

    // Modal-HTML nur einmal ausgeben!
    static $modal_output = false;
    if (!$modal_output) {
        $modal_output = true;
        ?>
        <dialog id="user-avatar-modal" class="psource-modal" style="width: 750px; max-width: 95vw;">
            <button class="psource-modal-close" aria-label="Schließen" style="float:right;">&times;</button>
            <iframe id="user-avatar-iframe" src="" width="720" height="450" style="border:0;width:100%;height:450px;"></iframe>
        </dialog>
        <?php
    }
    do_action('cpc_avatar_init_hook');
}

/* ********** */ /* SHORTCODES */ /* ********** */

function cpc_avatar($atts) {

	// Init
	cpc_avatar_init();

	global $current_user;
	$html = '';

	// Shortcode parameters
	$values = cpc_get_shortcode_options('cpc_avatar');  
	extract( shortcode_atts( array(
		'user_id' => cpc_get_shortcode_value($values, 'cpc_avatar-user_id', ''),
		'size' => cpc_get_shortcode_value($values, 'cpc_avatar-size', 256),
		'change_link' => cpc_get_shortcode_value($values, 'cpc_avatar-change_link', false),
        	'profile_link' => cpc_get_shortcode_value($values, 'cpc_avatar-profile_link', false), // only if avatar is NOT current user
	        'change_avatar_text' => cpc_get_shortcode_value($values, 'cpc_avatar-change_avatar_text', __('Bild ändern', CPC2_TEXT_DOMAIN)),
        	'change_avatar_title' => cpc_get_shortcode_value($values, 'cpc_avatar-change_avatar_title', __('Bild hochladen und zuschneiden, um es anzuzeigen', CPC2_TEXT_DOMAIN)),
	        'avatar_style' => cpc_get_shortcode_value($values, 'cpc_avatar-avatar_style', 'popup'),
        	'popup_width' => cpc_get_shortcode_value($values, 'cpc_avatar-popup_width', 750),            
	        'popup_height' => cpc_get_shortcode_value($values, 'cpc_avatar-popup_height', 450),            
		'styles' => true,
		'check_privacy' => false,
        	'after' => '',
		'before' => '',
	), $atts, 'cpc_avatar' ) );

	if ($user_id == 'user'):
		$user_id = $current_user->ID;
	else:
		if (!$user_id) $user_id = cpc_get_user_id();
	endif;

	if ($user_id):
    
		if ($check_privacy && strpos(CPC_CORE_PLUGINS, 'core-friendships') !== false):
			$friends = cpc_are_friends($current_user->ID, $user_id);
			// By default same user, and friends of user, can see profile
			$user_can_see_profile = ($current_user->ID == $user_id || $friends['status'] == 'publish') ? true : false;
			$user_can_see_profile = apply_filters( 'cpc_check_profile_security_filter', $user_can_see_profile, $user_id, $current_user->ID );
		else:
			$user_can_see_profile = true;
		endif;
		
		if ($user_can_see_profile):

			// I moved this code out of the '} else {' block below.  It was not being inserted if the $user_id != $current_user->ID thereby not inserting the <div> element.
			if (!strpos($size, '%')):
				$html .= sprintf('<div class="cpc_avatar" style="width: %dpx; height: %dpx;">', $size, $size);
			else:
				$html .= sprintf('<div class="cpc_avatar" style="width: %d; height: %d;">', $size, $size);
			endif;
			// End modification
			if ($user_id != $current_user->ID) {
				if ($profile_link)
					$html .= '<a href="'.get_page_link(get_option('cpccom_profile_page')).'?user_id='.$user_id.'">';
				$html .= user_avatar_get_avatar( $user_id, $size );
				if ($profile_link)
					$html .= '</a>';
			} else {
				$profile = get_user_by('id', $user_id);
				global $current_user;

				if ($profile_link && !$change_link)
					$html .= '<a href="'.get_page_link(get_option('cpccom_profile_page')).'?user_id='.$user_id.'">';
				$html .= user_avatar_get_avatar( $user_id, $size );
				if ($change_link):
					if ($avatar_style == 'popup'):
                        $url = admin_url('admin-ajax.php').'?action=user_avatar_add_photo&step=1&uid='.$current_user->ID.'&modal=1';
                        $html .= '<a id="user-avatar-link" class="button-secondary" data-psource-modal-open="user-avatar-modal" style="text-decoration: none;opacity:0.7;background-color: #000; color:#fff !important; padding: 3px 8px 3px 8px; position:absolute; bottom:18px; left: 10px;" href="'.$url.'" title="'.$change_avatar_title.'">'.$change_avatar_text.'</a>';
                    else:
						$html .= '<a id="user-avatar-link" style="text-decoration: none;opacity:0.7;background-color: #000; color:#fff !important; padding: 3px 8px 3px 8px; position:absolute; bottom:18px; left: 10px;" href="'.get_page_link(get_option('cpccom_change_avatar_page')).'?user_id='.$user_id.'&action=change_avatar" title="'.$change_avatar_title.'" >'.$change_avatar_text.'</a>';
					endif;
				endif;
				if ($profile_link && !$change_link)
					$html .= '</a>';
			}
			$html .= '</div>';

	    		if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_avatar', $before, $after, $styles, $values, $size, $size);

		endif;

	endif;
	
	return $html;

}


function cpc_avatar_change_link($atts) {

    // Init
    cpc_avatar_init();

    global $current_user;
    $html = '';

    if (is_user_logged_in()) {
        
        // Shortcode parameters
        $values = cpc_get_shortcode_options('cpc_avatar_change_link');
        extract( shortcode_atts( array(
            'text' => cpc_get_shortcode_value($values, 'cpc_avatar_change_link-text', __('Bild ändern', CPC2_TEXT_DOMAIN)),
            'change_style' => cpc_get_shortcode_value($values, 'cpc_avatar_change_link-change_style', 'page'),            
            'change_avatar_title' => cpc_get_shortcode_value($values, 'cpc_avatar_change_link-change_avatar_title', __('Bild hochladen und zuschneiden, um es anzuzeigen', CPC2_TEXT_DOMAIN)),
            'styles' => true,
            'after' => '',
            'before' => '',
        ), $atts, 'cpc_avatar_change' ) );

        $values = cpc_get_shortcode_options('cpc_avatar');
        extract( shortcode_atts( array(
            'popup_width' => cpc_get_shortcode_value($values, 'cpc_avatar-popup_width', 750),            
            'popup_height' => cpc_get_shortcode_value($values, 'cpc_avatar-popup_height', 450),            
        ), $atts, 'cpc_avatar' ) );

        $user_id = cpc_get_user_id();

        if ($current_user->ID == $user_id):
            if ($change_style == 'popup'):
                // NEU: Modal-Link ohne Thickbox
                $url = admin_url('admin-ajax.php').'?action=user_avatar_add_photo&step=1&uid='.$current_user->ID.'&modal=1';
                $html .= '<a id="user-avatar-link" class="button-secondary" data-psource-modal-open="user-avatar-modal" href="'.$url.'" title="'.$change_avatar_title.'">'.$text.'</a>';
            else:
                $html .= '<a href="'.get_page_link(get_option('cpccom_change_avatar_page')).'?user_id='.$user_id.'" title="'.$change_avatar_title.'">'.$text.'</a>';
            endif;
        endif;

    }

    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_avatar_change_link', $before, $after, $styles, $values);
    return $html;

}

function cpc_avatar_change($atts) {

	// Init
	cpc_avatar_init();

	global $current_user;
	$html = '';

    // Shortcode parameters
    $values = cpc_get_shortcode_options('cpc_avatar_change');
    extract( shortcode_atts( array(
        'label' => cpc_get_shortcode_value($values, 'cpc_avatar_change-label', __('Hochladen', CPC2_TEXT_DOMAIN)),
        'step1' => cpc_get_shortcode_value($values, 'cpc_avatar_change-step1', __('Schritt 1: Klicke auf diesen Link, um ein Bild auszuwählen, und klicke anschließend auf die Schaltfläche unten.', CPC2_TEXT_DOMAIN)),
        'step2' => cpc_get_shortcode_value($values, 'cpc_avatar_change-step2', __('Schritt 2: Wähle zunächst einen Bereich auf Deinem hochgeladenen Bild aus und klicke dann auf die Schaltfläche Zuschneiden.', CPC2_TEXT_DOMAIN)),
        'choose' => cpc_get_shortcode_value($values, 'cpc_avatar_change-choose', __('Klicke hier, um ein Bild auszuwählen... (maximal %dKB)', CPC2_TEXT_DOMAIN)),
        'try_again_msg' => cpc_get_shortcode_value($values, 'cpc_avatar_change-try_again_msg', __('Versuche es erneut...', CPC2_TEXT_DOMAIN)),
        'file_types_msg' => cpc_get_shortcode_value($values, 'cpc_avatar_change-file_types_msg', __("Bitte lade eine Bilddatei hoch (.jpeg, .gif, .png).", CPC2_TEXT_DOMAIN)),
        'not_permitted' => cpc_get_shortcode_value($values, 'cpc_avatar_change-not_permitted', __('Es ist Dir nicht gestattet, diesen Avatar zu ändern.', CPC2_TEXT_DOMAIN)),
        'file_too_big_msg' => cpc_get_shortcode_value($values, 'cpc_avatar_change-file_too_big_msg', __('Bitte lade eine Bilddatei hoch, die nicht größer als %dKB ist. Deine Datei war %dKB groß.', CPC2_TEXT_DOMAIN)),
        'max_file_size' => cpc_get_shortcode_value($values, 'cpc_avatar_change-max_file_size', 500),
        'crop' => cpc_get_shortcode_value($values, 'cpc_avatar_change-crop', true),
        'effects' => cpc_get_shortcode_value($values, 'cpc_avatar_change-effects', false),
        'logged_out_msg' => cpc_get_shortcode_value($values, 'cpc_avatar_change-logged_out_msg', __('Du musst angemeldet sein, um diese Seite anzuzeigen.', CPC2_TEXT_DOMAIN)),
        'login_url' => cpc_get_shortcode_value($values, 'cpc_avatar_change-login_url', ''),
        'flip' => cpc_get_shortcode_value($values, 'cpc_avatar_change-flip', __('Umdrehen', CPC2_TEXT_DOMAIN)),
        'rotate' => cpc_get_shortcode_value($values, 'cpc_avatar_change-rotate', __('Drehen', CPC2_TEXT_DOMAIN)),
        'invert' => cpc_get_shortcode_value($values, 'cpc_avatar_change-invert', __('Umkehren', CPC2_TEXT_DOMAIN)),
        'sketch' => cpc_get_shortcode_value($values, 'cpc_avatar_change-sketch', __('Skizzieren', CPC2_TEXT_DOMAIN)),
        'pixelate' => cpc_get_shortcode_value($values, 'cpc_avatar_change-pixelate', __('Pixelieren', CPC2_TEXT_DOMAIN)),
        'sepia' => cpc_get_shortcode_value($values, 'cpc_avatar_change-sepia', __('Sepia', CPC2_TEXT_DOMAIN)),
        'emboss' => cpc_get_shortcode_value($values, 'cpc_avatar_change-emboss', __('Prägen', CPC2_TEXT_DOMAIN)),
        'styles' => true,
    ), $atts, 'cpc_avatar_change' ) );
    
	if (is_user_logged_in()):

        $user_id = cpc_get_user_id();

        if (current_user_can('manage_options') && !$login_url && function_exists('cpc_login_init')):
            $html = cpc_admin_tip($html, 'cpc_avatar_change', __('Füge login_url="/example" zum Shortcode [cpc-avatar-change] hinzu, damit sich Benutzer anmelden und hierher zurückleiten können, wenn sie nicht angemeldet sind.', CPC2_TEXT_DOMAIN));
        endif;        
    
        $useragent=$_SERVER['HTTP_USER_AGENT'];

        if(preg_match('/(android|bb\d+|meego).+mobile|avantgo|bada\/|blackberry|blazer|compal|elaine|fennec|hiptop|iemobile|ip(hone|od)|iris|kindle|lge |maemo|midp|mmp|netfront|opera m(ob|in)i|palm( os)?|phone|p(ixi|re)\/|plucker|pocket|psp|series(4|6)0|symbian|treo|up\.(browser|link)|vodafone|wap|windows (ce|phone)|xda|xiino/i',$useragent)||preg_match('/1207|6310|6590|3gso|4thp|50[1-6]i|770s|802s|a wa|abac|ac(er|oo|s\-)|ai(ko|rn)|al(av|ca|co)|amoi|an(ex|ny|yw)|aptu|ar(ch|go)|as(te|us)|attw|au(di|\-m|r |s )|avan|be(ck|ll|nq)|bi(lb|rd)|bl(ac|az)|br(e|v)w|bumb|bw\-(n|u)|c55\/|capi|ccwa|cdm\-|cell|chtm|cldc|cmd\-|co(mp|nd)|craw|da(it|ll|ng)|dbte|dc\-s|devi|dica|dmob|do(c|p)o|ds(12|\-d)|el(49|ai)|em(l2|ul)|er(ic|k0)|esl8|ez([4-7]0|os|wa|ze)|fetc|fly(\-|_)|g1 u|g560|gene|gf\-5|g\-mo|go(\.w|od)|gr(ad|un)|haie|hcit|hd\-(m|p|t)|hei\-|hi(pt|ta)|hp( i|ip)|hs\-c|ht(c(\-| |_|a|g|p|s|t)|tp)|hu(aw|tc)|i\-(20|go|ma)|i230|iac( |\-|\/)|ibro|idea|ig01|ikom|im1k|inno|ipaq|iris|ja(t|v)a|jbro|jemu|jigs|kddi|keji|kgt( |\/)|klon|kpt |kwc\-|kyo(c|k)|le(no|xi)|lg( g|\/(k|l|u)|50|54|\-[a-w])|libw|lynx|m1\-w|m3ga|m50\/|ma(te|ui|xo)|mc(01|21|ca)|m\-cr|me(rc|ri)|mi(o8|oa|ts)|mmef|mo(01|02|bi|de|do|t(\-| |o|v)|zz)|mt(50|p1|v )|mwbp|mywa|n10[0-2]|n20[2-3]|n30(0|2)|n50(0|2|5)|n7(0(0|1)|10)|ne((c|m)\-|on|tf|wf|wg|wt)|nok(6|i)|nzph|o2im|op(ti|wv)|oran|owg1|p800|pan(a|d|t)|pdxg|pg(13|\-([1-8]|c))|phil|pire|pl(ay|uc)|pn\-2|po(ck|rt|se)|prox|psio|pt\-g|qa\-a|qc(07|12|21|32|60|\-[2-7]|i\-)|qtek|r380|r600|raks|rim9|ro(ve|zo)|s55\/|sa(ge|ma|mm|ms|ny|va)|sc(01|h\-|oo|p\-)|sdk\/|se(c(\-|0|1)|47|mc|nd|ri)|sgh\-|shar|sie(\-|m)|sk\-0|sl(45|id)|sm(al|ar|b3|it|t5)|so(ft|ny)|sp(01|h\-|v\-|v )|sy(01|mb)|t2(18|50)|t6(00|10|18)|ta(gt|lk)|tcl\-|tdg\-|tel(i|m)|tim\-|t\-mo|to(pl|sh)|ts(70|m\-|m3|m5)|tx\-9|up(\.b|g1|si)|utst|v400|v750|veri|vi(rg|te)|vk(40|5[0-3]|\-v)|vm40|voda|vulc|vx(52|53|60|61|70|80|81|83|85|98)|w3c(\-| )|webc|whit|wi(g |nc|nw)|wmlb|wonu|x700|yas\-|your|zeto|zte\-/i',substr($useragent,0,4))) {
            $crop = false;
        }

		if ($current_user->ID == $user_id || current_user_can('manage_options') || is_super_admin($current_user->ID) ):

			include_once ABSPATH . 'wp-admin/includes/media.php';
			include_once ABSPATH . 'wp-admin/includes/file.php';
			include_once ABSPATH . 'wp-admin/includes/image.php';

            $step = 1;
            if (isset($_POST['cpc_avatar_change_step'])):
                $step = $_POST['cpc_avatar_change_step'];
            elseif (isset($_GET['cpc_avatar_change_step'])):
                $step = $_GET['cpc_avatar_change_step'];
            endif;
    
			if ($step == 1):

                $html .= '<div id="cpc_avatar_change_step_1">'.$step1.'</div>';
				$html .= '<form enctype="multipart/form-data" id="avatarUploadForm" method="POST" action="#" >';
					$html .= '<input type="hidden" name="cpc_avatar_change_step" value="2" />';
                    $choose = sprintf($choose, $max_file_size);
					$html .= '<input title="'.$choose.'" type="file" id="avatar_file_upload" name="uploadedfile" style="display:none" /><br /><br />';
					wp_nonce_field('user-avatar');
					$html .= '<button class="cpc_button">'.$label.'</button>';
				$html .= '</form>';

			elseif ($step == '2' && $crop):
    
                $img_action = isset($_GET['flip_file']) || isset($_GET['rotate_file']) || isset($_GET['invert_file']) || isset($_GET['sketch_file']) || isset($_GET['pixelate_file']) || isset($_GET['sepia_file']) || isset($_GET['emboss_file']);
				if ( (!$img_action) && (!(($_FILES["uploadedfile"]["type"] == "image/gif") || ($_FILES["uploadedfile"]["type"] == "image/jpeg") || ($_FILES["uploadedfile"]["type"] == "image/png") || ($_FILES["uploadedfile"]["type"] == "image/pjpeg") || ($_FILES["uploadedfile"]["type"] == "image/x-png"))) ):
					
					$html .= "<div class='cpc_error'>".$file_types_msg." (".$_FILES["uploadedfile"]["type"].")</div>";
					$html .= "<p><a href=''>".$try_again_msg.'</a></p>';

				else:

                    // check file size
                    if (!$img_action):
                        $file_size = $_FILES["uploadedfile"]["size"];
                    else:
                        $file_size = $_GET['file_size'];
                    endif;
                    $file_size = $file_size / 1024; // KB
                    if ($file_size > $max_file_size):
                        $html .= "<div class='cpc_error'>".sprintf($file_too_big_msg, $max_file_size, $file_size)."</div>";
                        $html .= "<p><a href=''>".$try_again_msg.'</a></p>';
                    else:    

                        if (!$img_action):
    
                            $overrides = array('test_form' => false);

                            $file = wp_handle_upload($_FILES['uploadedfile'], $overrides);

                            if ( isset($file['error']) ){
                                die( $file['error'] );
                            }

                            $url = $file['url'];
                            $type = $file['type'];
                            $file = $file['file'];
                            $filename = basename($file);
                            set_transient( 'avatar_file_'.$user_id, $file, 60 * 60 * 5 );
                
                        else:

                            if (!class_exists('SimpleImage')) require_once('SimpleImage.php');
    
                            if (isset($_GET['flip_file'])):
                                // flip...
                                $file = stripslashes($_GET['flip_file']);
                                $url = stripslashes($_GET['url']);
                                // update the files 
                                $image = new SimpleImage();
                                $image->load($file);
                                $image->flip('y');
                                $image->save($file);
                            endif;

                            if (isset($_GET['rotate_file'])):
                                // rotating...
                                $file = stripslashes($_GET['rotate_file']);
                                $url = stripslashes($_GET['url']);
                                // update the files 
                                $image = new SimpleImage();
                                $image->load($file);
                                $image->rotate(90);
                                $image->save($file);
                            endif;

                            if (isset($_GET['invert_file'])):
                                // inverting...
                                $file = stripslashes($_GET['invert_file']);
                                $url = stripslashes($_GET['url']);
                                // update the files 
                                $image = new SimpleImage();
                                $image->load($file);
                                $image->invert();
                                $image->save($file);
                            endif;

                            if (isset($_GET['sketch_file'])):
                                // sketch...
                                $file = stripslashes($_GET['sketch_file']);
                                $url = stripslashes($_GET['url']);
                                // update the files 
                                $image = new SimpleImage();
                                $image->load($file);
                                $image->sketch();
                                $image->save($file);
                            endif;

                            if (isset($_GET['pixelate_file'])):
                                // pixelate...
                                $file = stripslashes($_GET['pixelate_file']);
                                $url = stripslashes($_GET['url']);
                                // update the files 
                                $image = new SimpleImage();
                                $image->load($file);
                                $image->pixelate(4);
                                $image->save($file);
                            endif;

                            if (isset($_GET['sepia_file'])):
                                // sepia...
                                $file = stripslashes($_GET['sepia_file']);
                                $url = stripslashes($_GET['url']);
                                // update the files 
                                $image = new SimpleImage();
                                $image->load($file);
                                $image->sepia();
                                $image->save($file);
                            endif;

                            if (isset($_GET['emboss_file'])):
                                // emboss...
                                $file = stripslashes($_GET['emboss_file']);
                                $url = stripslashes($_GET['url']);
                                // update the files 
                                $image = new SimpleImage();
                                $image->load($file);
                                $image->emboss();
                                $image->save($file);
                            endif;    

                        endif;

                        // Save the data
                        list($width, $height, $type, $attr) = getimagesize( $file );

                        if ( $width > 420 ) {

                            $oitar = $width / 420;
                            if (!class_exists('SimpleImage')) require_once('SimpleImage.php');
                            $image = new SimpleImage();
                            $image->load($file);
                            $image->fit_to_width(420);
                            $image->save($file);        

                            $url = str_replace(basename($url), basename($file), $url);

                            $width = $width / $oitar;
                            $height = $height / $oitar;

                        } else {
                            $oitar = 1;
                        }

                        $div_width = esc_attr($width) + 20;
                        $html .= '<div style="padding:0 !important;overflow:auto !important; min-width: '.$div_width.'px !important;">';
    
                            $html .= '<form id="iframe-crop-form" method="POST" action="#">';
                            $html .= '<input type="hidden" name="cpc_avatar_change_step" value="3" />';
                            $html .= '<div id="cpc_avatar_change_step_2">'.$step2.'</div>';                        

                            $page_id = isset($_GET['page_id']) ? '?page_id='.$_GET['page_id'] : '';
                            $page_url = strtok(cpc_curPageURL(), '?').$page_id;

                            $cpc_change_avatar_effects = get_option('cpc_change_avatar_effects');
                            if (!$cpc_change_avatar_effects) $cpc_change_avatar_effects = 'flip,rotate,invert,sketch,pixelate,sepia,emboss';   
                            if ($effects):
                                $effects = explode(',', $cpc_change_avatar_effects);
                            else:
                                $effects = array();
                            endif;
                            $page_url = $page_url.cpc_query_mark($page_url);
                            if (in_array('flip', $effects)) $html .= '<a class="cpc_avatar_upload_effect"  href="'.$page_url.'cpc_avatar_change_step=2&flip_file='.$file.'&url='.$url.'&file_size='.$file_size.'">'.$flip.'</a>';
                            if (in_array('rotate', $effects)) $html .= '<a class="cpc_avatar_upload_effect"  href="'.$page_url.'cpc_avatar_change_step=2&rotate_file='.$file.'&url='.$url.'&file_size='.$file_size.'">'.$rotate.'</a>';
                            if (in_array('invert', $effects)) $html .= '<a class="cpc_avatar_upload_effect" href="'.$page_url.'cpc_avatar_change_step=2&invert_file='.$file.'&url='.$url.'&file_size='.$file_size.'">'.$invert.'</a>';
                            if (in_array('sketch', $effects)) $html .= '<a class="cpc_avatar_upload_effect" href="'.$page_url.'cpc_avatar_change_step=2&sketch_file='.$file.'&url='.$url.'&file_size='.$file_size.'">'.$sketch.'</a>';
                            if (in_array('pixelate', $effects)) $html .= '<a class="cpc_avatar_upload_effect" href="'.$page_url.'cpc_avatar_change_step=2&pixelate_file='.$file.'&url='.$url.'&file_size='.$file_size.'">'.$pixelate.'</a>';
                            if (in_array('sepia', $effects)) $html .= '<a class="cpc_avatar_upload_effect" href="'.$page_url.'cpc_avatar_change_step=2&sepia_file='.$file.'&url='.$url.'&file_size='.$file_size.'">'.$sepia.'</a>';
                            if (in_array('emboss', $effects)) $html .= '<a class="cpc_avatar_upload_effect" href="'.$page_url.'cpc_avatar_change_step=2&emboss_file='.$file.'&url='.$url.'&file_size='.$file_size.'">'.$emboss.'</a>';
                            $html .= '<div id="cpc_uploaded_avatar_to_crop">';
                            $html .= '<img src="'.$url.'" id="cpc_upload" width="'.esc_attr($width).'" height="'.esc_attr($height).'" />';
                            $html .= '</div>';

                            $html .= '<div id="cpc_preview" style="float:left; margin-top:20px !important; width: 150px; height: 150px; overflow: hidden;">';
                            $html .= '<img src="'.esc_url_raw($url).'" width="'.esc_attr($width).'" height="'.esc_attr($height).'" style="max-width:none" />';
                            $html .= '</div>';

                            $html .= '<input type="hidden" name="x1" id="x1" value="0" />';
                            $html .= '<input type="hidden" name="y1" id="y1" value="0" />';
                            $html .= '<input type="hidden" name="x2" id="x2" />';
                            $html .= '<input type="hidden" name="y2" id="y2" />';
                            $html .= '<input type="hidden" name="width" id="width" value="'.esc_attr($width).'" />';
                            $html .= '<input type="hidden" name="height" id="height" value="'.esc_attr($height).'" />';
                            $html .= '<input type="hidden" id="init_width" value="'.esc_attr($width).'" />';
                            $html .= '<input type="hidden" id="init_height" value="'.esc_attr($height).'" />';

                            $html .= '<input type="hidden" name="oitar" id="oitar" value="'.esc_attr($oitar).'" />';
                            wp_nonce_field('user-avatar');
                            $html .= '<button class="cpc_button" style="clear:both; margin-top:20px !important; margin-left:20px !important;" id="user-avatar-crop-button">'.__('Zuschneiden', CPC2_TEXT_DOMAIN).'</button>';

                            $html .= '</form>';

                        $html .= '</div>';
        
                    endif;

				endif;

			else: // $step == 3


                if (isset($_POST['oitar'])):
    
                    // Doing crop
                    $x1_post = floatval($_POST['x1']);
                    $y1_post = floatval($_POST['y1']);
                    $oitar_post = floatval($_POST['oitar']);
                    $width_post = floatval($_POST['width']);
                    $height_post = floatval($_POST['height']);

                    $original_file = get_transient( 'avatar_file_'.$user_id );
                    delete_transient('avatar_file_'.$user_id );

                    $time_now = time();

                    if( !file_exists($original_file) ):

                        $html .= "<div class='error'><p>". __('Leider ist keine Datei verfügbar', CPC2_TEXT_DOMAIN)."</p></div>";

                    else:

                        // Create avatar folder if not already existing
                        $continue = true;
                        if( !file_exists(WP_CONTENT_DIR."/cpc-pro-content/members/".$user_id."/avatar/") ):
    
                            if (!mkdir(WP_CONTENT_DIR."/cpc-pro-content/members/".$user_id."/avatar/", 0777 ,true)):
                                $error = error_get_last();
                                $html .= $error['message'].'<br />';
                                $html .= WP_CONTENT_DIR."/cpc-pro-content/members/".$user_id."/avatar/<br>";
                                $continue = false;
                            else:
                                $path = WP_CONTENT_DIR."/cpc-pro-content/members/".$user_id."/avatar/";
                                $cropped_full = $path.$time_now."-cpcfull.jpg";
                                $cropped_thumb = $path.$time_now."-cpcthumb.jpg";
                            endif;
                        else:
                            $cropped_full = user_avatar_core_avatar_upload_path($user_id).$time_now."-cpcfull.jpg";
                            $cropped_thumb = user_avatar_core_avatar_upload_path($user_id).$time_now."-cpcthumb.jpg";
                        endif;

                        if ($continue):

                            // delete the previous files
                            user_avatar_delete_files($user_id);
                            @mkdir(WP_CONTENT_DIR."/cpc-pro-content/members/".$user_id."/avatar/", 0777 ,true);

                            if (!class_exists('SimpleImage')) require_once('SimpleImage.php');

                            // update the files 
                            $img = $original_file;
	                            $image = new SimpleImage();
	                            $image->load($img);
	                            $image->crop($x1_post, $y1_post, $x1_post+$width_post, $y1_post+$height_post);
	                            $image->save($cropped_full);
    
                            $img = $original_file;
	                            $image = new SimpleImage();
	                            $image->load($img);
	                            $image->crop($x1_post, $y1_post, $x1_post+$width_post, $y1_post+$height_post);
                                $image->resize(250,250); // size of thumbnail
	                            $image->save($cropped_thumb);

							/* Update user's meta data for quick reference */
							update_user_meta( $user_id, 'cpc_com_avatar', "/cpc-pro-content/members/".$user_id."/avatar/".$time_now."-cpcfull.jpg" );	 
    
                            if ( is_wp_error( $cropped_full ) ):
                                $html .= __( 'Bild konnte nicht verarbeitet werden. Bitte versuche es erneut.', CPC2_TEXT_DOMAIN);	
                                //var_dump($cropped_full);	
                            else:
                                /* Remove the original */
                                @unlink( $original_file );
                                $html .= '<script>window.location.replace("'.get_page_link(get_option('cpccom_profile_page')).'?user_id='.$user_id.'");</script>';
                            endif;

                        endif;

                    endif;
    
                else:
    
                    // Skip crop
    
					$overrides = array('test_form' => false);
					$file = wp_handle_upload($_FILES['uploadedfile'], $overrides);

					if ( isset($file['error']) ){
						die( $file['error'] );
					}

					$url = $file['url'];
					$type = $file['type'];
					$original_file = $file['file'];
					$filename = basename($original_file);

					$time_now = time();

                    if( !file_exists($original_file) ):

                        $html .= "<div class='error'><p>". __('Leider ist keine Datei verfügbar', CPC2_TEXT_DOMAIN)."</p></div>";

                    else:

                        // Create avatar folder if not already existing
                        $continue = true;
                        if( !file_exists(WP_CONTENT_DIR."/cpc-pro-content/members/".$user_id."/avatar/") ):
                            if (!mkdir(WP_CONTENT_DIR."/cpc-pro-content/members/".$user_id."/avatar/", 0777 ,true)):
                                $error = error_get_last();
                                $html .= $error['message'].'<br />';
                                $html .= WP_CONTENT_DIR."/cpc-pro-content/members/".$user_id."/avatar/<br>";
                                $continue = false;
                            else:
                                $path = WP_CONTENT_DIR."/cpc-pro-content/members/".$user_id."/avatar/";
                                $cropped_full = $path.$time_now."-cpcfull.jpg";
                                $cropped_thumb = $path.$time_now."-cpcthumb.jpg";
                            endif;
                        else:
                            $cropped_full = user_avatar_core_avatar_upload_path($user_id).$time_now."-cpcfull.jpg";
                            $cropped_thumb = user_avatar_core_avatar_upload_path($user_id).$time_now."-cpcthumb.jpg";
                        endif;

                        if ($continue):

                            // delete the previous files
                            user_avatar_delete_files($user_id);

                            // update the files 
                            list($width, $height, $type, $attr) = getimagesize( $original_file );    
                            $cropped_full = wp_crop_image( $original_file, 0, 0, $width, $height, 300, 300, false, $cropped_full );
                            $cropped_thumb = wp_crop_image( $original_file, 0, 0, $width, $height, 300, 300, false, $cropped_thumb );

							/* Update user's meta data for quick reference */
							update_user_meta( $user_id, 'cpc_com_avatar', "/cpc-pro-content/members/".$user_id."/avatar/".$time_now."-cpcfull.jpg" );	 

                            if ( is_wp_error( $cropped_full ) ):
                                $html .= __( 'Bild konnte nicht verarbeitet werden. Bitte versuche es erneut.', CPC2_TEXT_DOMAIN);	
                                //var_dump($cropped_full);	
                            else:
                                /* Remove the original */
                                @unlink( $original_file );
                                $html .= '<script>window.location.replace("'.get_page_link(get_option('cpccom_profile_page')).'?user_id='.$user_id.'");</script>';
                            endif;

                        endif;

                    endif;
    
                endif;


			endif;

		else:

			$html .= $not_permitted;

		endif;

    else:

        if (!is_user_logged_in() && $logged_out_msg):
            $query = cpc_query_mark(get_bloginfo('url').$login_url);
            if ($login_url) $html .= sprintf('<a href="%s%s%sredirect=%s">', get_bloginfo('url'), $login_url, $query, cpc_root( $_SERVER['REQUEST_URI'] ));
            $html .= $logged_out_msg;
            if ($login_url) $html .= '</a>';
        endif;
    
    endif;
    
    if ($html) $html = apply_filters ('cpc_wrap_shortcode_styles_filter', $html, 'cpc_avatar_change', '', '', $styles, $values);
    
	return $html;
}

add_shortcode(CPC_PREFIX.'-avatar', 'cpc_avatar');
add_shortcode(CPC_PREFIX.'-avatar-change-link', 'cpc_avatar_change_link');
add_shortcode(CPC_PREFIX.'-avatar-change', 'cpc_avatar_change');

?>
