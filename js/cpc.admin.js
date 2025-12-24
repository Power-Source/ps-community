jQuery(document).ready(function() {
	
    jQuery('.cpc_admin_fav').on('click', function(e) {
        if(  e.offsetX <= 15 ) {
            var classes_str = jQuery(this).attr('class');
            var classes = classes_str.split(" ");
            var classes_length = classes.length;
            var classes_item = classes[2];
            if (classes_length > 3) {
                classes_item = classes[3];
            } else {
            }
            var item = classes_item.replace("cpc_fav_", "");
            jQuery.post(
                cpc_ajax.ajaxurl,
                {
                    item : item,
                    action : 'cpc_toggle_main_menu',
                },
                function(response) {
                    location.reload();
                }   
            );              
        }        
    });

    jQuery('#cpc_hide_welcome_header').on('click', function() {
        if(jQuery('#cpc_welcome').css('display') == 'block') {
            jQuery('#cpc_welcome').slideUp();
        } else {
            jQuery('#cpc_welcome').slideDown();
        }
        jQuery.post(
            cpc_ajax.ajaxurl,
            {
                action : 'cpc_hide_welcome_header_toggle',
            },
            function(response) {}   
        );         
    });

    jQuery('#cpc_hide_admin_links').on('click', function() {
        jQuery.post(
            cpc_ajax.ajaxurl,
            {
                action : 'cpc_hide_admin_links_toggle',
            },
            function(response) {
                location.reload();
            }   
        );         
    });
    jQuery('#cpc_hide_admin_links_show').on('click', function() {
        jQuery.post(
            cpc_ajax.ajaxurl,
            {
                action : 'cpc_hide_admin_links_toggle',
            },
            function(response) {
                location.reload();
            }   
        );         
    });

    jQuery('#cpc_forum_featured_upload_image_button').on('click', function(e) {
        e.preventDefault();
        var mediaUploader = wp.media({
            title: 'Bild auswählen',
            button: { text: 'Bild übernehmen' },
            multiple: false
        });
        mediaUploader.on('select', function() {
            var attachment = mediaUploader.state().get('selection').first().toJSON();
            jQuery('#cpc_forum_featured_upload_image').val(attachment.url);
        });
        mediaUploader.open();
    });

    // Un-hide shortcode options once page is ready
    if (jQuery('#cpc_admin_getting_started_options_left_and_middle').length) {
        jQuery('#cpc_admin_getting_started_options_please_wait').hide();
        jQuery('#cpc_admin_getting_started_options_left_and_middle').show();
        jQuery('#cpc_admin_getting_started_options_right').show();
    }

    // Save shortcode options
    jQuery('#cpc_shortcode_options_save_submit').on('click', function () {

        // Find shortcode that's showing (to save)
        var cpc_expand_shortcode = '';
        jQuery('.cpc_admin_getting_started_option_shortcode').each(function(i, obj) {
            if (jQuery(this).hasClass('cpc_admin_getting_started_active')) {
                cpc_expand_shortcode = jQuery(this).attr('rel');
            }
        });
        
        if (cpc_expand_shortcode != '') {   

            jQuery('#'+cpc_expand_shortcode)
                .css('opacity', '0.2')
                .css('color', '#9f9f9f')
                .css('background', '#efefef');

            var this_obj = this;
            jQuery(this_obj).addClass('admin_button_please_wait');
            jQuery('.spinner').addClass('is-active');
            jQuery(this_obj).hide();
            
            var arr = [];
            var c = 0;
            jQuery('#'+cpc_expand_shortcode+' input').each(function(i, obj) {
                var name = jQuery(this).attr('name');
                if (name === undefined) {
                    // not a CPC text field (no name), probably color picker
                } else {
                    if (name.indexOf("[]") > 0) {
                        // multi-select checkboxes
                        var type = jQuery(this).val(); // the value of the checkbox
                        var val = jQuery(this).is(":checked"); // if checked
                        // ends up as (for example):
                        //   name = cpc_directory_role[]
                        //   type = editor (one of the chosen roles, AJAX will sort out how to save)
                        //   val = true/false (checked?)
                        arr.push([name,type,val]);
                    } else {
                        var type = jQuery(this).attr('type');                    
                        if (type != 'checkbox') {
                            var val = jQuery(this).val();
                        } else {
                            if (jQuery(this).is(":checked")) {
                                var val = 'on';
                            } else {
                                var val = 'off';
                            }
                        }
                        arr.push([name,type,val]);
                    }
                }
            });
            
            jQuery('#'+cpc_expand_shortcode+' select').each(function(i, obj) {
                var name = jQuery(this).attr('name');
                var type = 'select';
                var val = jQuery(this).val();
                arr.push([name,type,val]);
            });          

            jQuery.post(
                cpc_ajax.ajaxurl,
                {
                    action : 'cpc_shortcode_options_save',
                    data: {arr: arr},
                },
                function(response) {
                    if (response != '') { alert(response) };
                    
                    jQuery('#'+cpc_expand_shortcode)
                        .css('opacity', '1.0')
                        .css('color', '#000')                    
                        .css('background', '#fff');

                    jQuery(this_obj).removeClass('admin_button_please_wait');
                    jQuery('.spinner').removeClass('is-active');
                    jQuery(this_obj).show();
                    
                }   
            ); 
            

        } else {
                                                                       
            alert('Oops - select one of the shortcodes and try again!');
            
        }
        
        
    });
                  
    // Enable styles
    jQuery('#cpc_styles_enable_submit').on('click', function () {
        jQuery('#cpc_admin_getting_started_options_outline').css('opacity', 0.5);
        jQuery.post(
                cpc_ajax.ajaxurl,
                {
                    action : 'cpc_styles_enable',
                },
                function(response) {
                    location.reload();
                }   
            ); 
    });
    
    // Disable styles
    jQuery('#cpc_styles_disable_submit').on('click', function () {
        jQuery('#cpc_admin_getting_started_options_outline').css('opacity', 0.5);
        jQuery.post(
                cpc_ajax.ajaxurl,
                {
                    action : 'cpc_styles_disable',
                },
                function(response) {
                    location.reload();
                }   
            ); 
    });
        
    // Save styles options
    jQuery('#cpc_styles_save_submit').on('click', function () {

        // Find shortcode that's showing (to save)
        var cpc_expand_shortcode = '';
        jQuery('.cpc_admin_getting_started_option_shortcode').each(function(i, obj) {
            if (jQuery(this).hasClass('cpc_admin_getting_started_active')) {
                cpc_expand_shortcode = jQuery(this).attr('rel');
            }
        });
        
        if (cpc_expand_shortcode != '') {   

            jQuery('#'+cpc_expand_shortcode)
                .css('opacity', '0.2')
                .css('color', '#9f9f9f')
                .css('background', '#efefef');

            var this_obj = this;
            jQuery(this_obj).addClass('admin_button_please_wait');
            jQuery('.spinner').show().addClass('is-active');
            jQuery(this_obj).hide();
            
            var arr = [];
            var c = 0;
            jQuery('#'+cpc_expand_shortcode+' input').each(function(i, obj) {
                var name = jQuery(this).attr('name');
                var type = jQuery(this).attr('type');
                if (type != 'checkbox') {
                    var val = jQuery(this).val();
                } else {
                    if (jQuery(this).is(":checked")) {
                        var val = 'on';
                    } else {
                        var val = 'off';
                    }
                }
                if (type !== 'button') {
                    arr.push([name,type,val]);
                }
            });
            jQuery('#'+cpc_expand_shortcode+' select').each(function(i, obj) {
                var name = jQuery(this).attr('name');
                var type = 'select';
                var val = jQuery(this).val();
                arr.push([name,type,val]);
            });   

            jQuery.post(
                cpc_ajax.ajaxurl,
                {
                    action : 'cpc_styles_options_save',
                    data: {arr: arr},
                },
                function(response) {
                    if (response != '') { alert(response) };
                    
                    jQuery('#'+cpc_expand_shortcode)
                        .css('opacity', '1.0')
                        .css('color', '#000')                    
                        .css('background', '#fff');

                    jQuery(this_obj).removeClass('admin_button_please_wait');
                    jQuery('.spinner').removeClass('is-active').hide();
                    jQuery(this_obj).show();
                    
                }   
            ); 
            

        } else {
                                                                       
            alert('Oops - select one of the shortcodes and try again!');
            
        }
        
        
    });
    
	// Remember which admin section to show after saving
    jQuery('#cpc_setup').on('submit', function () {
        
        // Sections
    	var cpc_expand = '';
		jQuery('.cpc_admin_getting_started_content').each(function(i, obj) {
		    if (jQuery(this).css('display') != 'none') {
		    	cpc_expand = jQuery(this).attr('id');
		    }
		});

		var input = jQuery("<input>")
		               .attr("type", "hidden")
		               .attr("name", "cpc_expand").val(cpc_expand);

		jQuery('#cpc_setup').append(jQuery(input));
            
    });
    
    // Show default settings tab
    jQuery('.cpc_admin_getting_started_option').on('click', function() {
        jQuery('.cpc_admin_getting_started_option_shortcode').hide();
        jQuery('.cpc_admin_getting_started_option_value').hide();
        jQuery('#cpc_admin_getting_started_options_right').hide();
        jQuery('.cpc_setup_submit_options').hide();
        //jQuery('#cpc_admin_getting_started_options_help').slideUp('slow');
        jQuery('.cpc_admin_getting_started_option').removeClass('cpc_admin_getting_started_active');
        jQuery('.cpc_admin_getting_started_option_shortcode').removeClass('cpc_admin_getting_started_active');
        jQuery(this).addClass('cpc_admin_getting_started_active');
        var tab = jQuery(this).data('shortcode');
        jQuery('.cpc_'+tab).show();
    });
    jQuery('.cpc_admin_getting_started_option_shortcode').on('click', function() {
        jQuery('.cpc_admin_getting_started_option_value').hide();
        jQuery('#cpc_admin_getting_started_options_right').show();   
        jQuery('.cpc_setup_submit_options').show();        
        jQuery('.cpc_admin_getting_started_option_shortcode').removeClass('cpc_admin_getting_started_active');
        jQuery(this).addClass('cpc_admin_getting_started_active');
        var tab = jQuery(this).data('tab');
        jQuery('#'+tab).show();
    });

    // Show/Hide shortcode examples
    jQuery('#cpc_show_shortcodes_show').on('click', function() {
        jQuery('table.cpc_shortcode_value_row tr td:nth-child(3)').show();
        jQuery('#cpc_show_shortcodes_hide').fadeIn('fast');
        jQuery(this).hide();
    });
    jQuery('#cpc_show_shortcodes_hide').on('click', function() {
        jQuery('table.cpc_shortcode_value_row tr td:nth-child(3)').hide();
        jQuery('#cpc_show_shortcodes_show').fadeIn('fast');
        jQuery(this).hide();
    });
        
    // Show/Hide shortcode help
    jQuery('#cpc_show_shortcodes_desc_show').on('click', function() {
        jQuery('.cpc_desc').fadeIn();
        jQuery(this).hide();
        jQuery('#cpc_show_shortcodes_desc_hide').fadeIn('fast');
    });
    jQuery('#cpc_show_shortcodes_desc_hide').on('click', function() {
        jQuery('.cpc_desc').fadeOut();
        jQuery(this).hide();
        jQuery('#cpc_show_shortcodes_desc_show').fadeIn('fast');
    });
        
    // Scroll to previous section after save
    if (jQuery('#cpc_expand').length) {
        var e = '#'+jQuery('#cpc_expand').val();
        jQuery('html, body').animate({
                scrollTop: jQuery(e).offset().top-100
            }, 0); // Increase 0 to scroll, higher = slower
    }

	// Show content on menu click
	jQuery(".cpc_admin_getting_started_menu_item").on('click', function (event) {
		// Tidy up
        jQuery('.cpc_admin_getting_started_menu_item').removeClass('cpc_admin_getting_started_menu_item_remove_icon');
		var t = jQuery(this);
		if (jQuery('#'+t.attr('rel')).css('display') == 'none') {
			jQuery(".cpc_admin_getting_started_content").css('opacity', '0.2').slideUp('slow');		
			jQuery('#'+t.attr('rel')).css('opacity', '1.0').slideDown('slow');
            t.addClass('cpc_admin_getting_started_menu_item_remove_icon');
		} else {
			jQuery('#'+t.attr('rel')).css('opacity', '0.2').css('border-left', '1px solid #000').css('border-right', '1px solid #000').css('border-bottom', '1px solid #000');
			jQuery('#'+t.attr('rel')).slideUp('slow');
		}
	});
    
    // Color Picker
    if (jQuery('.cpc-color-picker').length) {
        jQuery('.cpc-color-picker').wpColorPicker();
    }    

    // Show shortcode tip
    if (jQuery(".cpc_shortcode_tip_available").length) {
        jQuery(".cpc_shortcode_tip_available").on('click', function (event) {
            jQuery(this).parent().parent().nextAll('#cpc_shortcode_tip').first().fadeIn('slow').fadeOut('fast').fadeIn('slow');
        });
    }
    
});
