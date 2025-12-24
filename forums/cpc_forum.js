jQuery(document).ready(function() {
    
	/* Admin - add forum */

	jQuery("#cpc_admin_forum_add_button").on('click', function (event) {
		if (jQuery('#cpc_admin_forum_add_details').css('display') != 'none') {

			if ( jQuery('#cpc_admin_forum_add_name').val().length != 0) {
				jQuery('#cpc_admin_forum_add_name').css('background-color', '#fff').css('color', '#000');
				if (jQuery('#cpc_admin_forum_add_description').val().length == 0) {
					event.preventDefault();
					jQuery('#cpc_admin_forum_add_description').css('background-color', '#faa').css('color', '#000');
				}
			} else {
				event.preventDefault();
				jQuery('#cpc_admin_forum_add_name').css('background-color', '#faa').css('color', '#000');				
			}
		}
	});

	jQuery("#cpc_admin_forum_add").on('click', function (event) {
		event.preventDefault();
		if (jQuery('#cpc_admin_forum_add_details').css('display') == 'none') {
			jQuery('#cpc_admin_forum_add_details').slideDown('fast');
		}
	});

	/* Quick jump */

	if (jQuery("#cpc_forums_go_to").length) {

		jQuery("#cpc_forums_go_to").change(function() {
			var url = jQuery(this).val();
			if (url != '') { 
				jQuery("body").addClass("cpc_wait_loading");
				window.location = url;
			}
		});

	}
	
	/* Add Post */

	if (jQuery("#cpc_forum_post_button").length) {

		jQuery('#cpc_forum_post_button').prop("disabled", false);
		jQuery('#cpc_forum_post_title').val('');
		jQuery('#cpc_forum_post_textarea').val('');
		jQuery('.cpc_forum_extension_text').val(''); // Possible forum extensions
		jQuery('.cpc_forum_extension_textarea').val(''); // Possible forum extensions
		
		jQuery("#cpc_forum_post_button").on('click', function (event) {
            
			event.preventDefault();
            jQuery('.cpc_field_error').removeClass('cpc_field_error'); // remove all highlighed fields before re-checking

            if (jQuery('#cpc_forum_post_form').css('position') == 'fixed') {
                
                // for all
                if (jQuery("#closed_switch").length) {
                    jQuery("#closed_switch").parent().hide();
                }
                // for classic...
                if (jQuery(".cpc_forum_posts_classic").length) {
                    jQuery(".cpc_forum_posts_classic").slideUp();
                }
                // for table...
                if (jQuery(".cpc_forum_posts").length) {
                    jQuery(".cpc_forum_posts").slideUp();
                }
                if (jQuery(".cpc_forum_posts_header").length) {
                    jQuery(".cpc_forum_posts_header").slideUp();
                }
                if (jQuery(".cpc_pagination_numbers").length) {
                    jQuery(".cpc_pagination_numbers").hide();
                }
                
				jQuery('#cpc_forum_post_form').css('position', 'relative').css('left', '0px').css('top', '0px');
				document.getElementById('cpc_forum_post_title').focus();

			} else {

				if (jQuery('#cpc_forum_post_title').val().length) {
                    if (typeof tinyMCE !== 'undefined') {
                        /* check if Visual or Text mode */
                        if (tinyMCE.activeEditor != null) {
                            var editor = tinyMCE.get('cpc_forum_post_textarea');
                            var content = editor.getContent();
                        } else {
                            var content = jQuery('#cpc_forum_post_textarea').val();
                        }
                    } else {
                        var content = jQuery('#cpc_forum_post_textarea').val();
                    }
					if (content.length) {

						// Check for mandatory fields
						var all_filled = true;
						jQuery('.cpc_mandatory_field').each(function(i, obj) {
                            var value = jQuery(this).val();
                            if (value.trim() == '') {
								jQuery(this).addClass('cpc_field_error');
								all_filled = false;
							}
						});
                        
						if (all_filled) {
                            
                            /* First add the post */
							jQuery(this).attr("disabled", true);
							jQuery("body").addClass("cpc_wait_loading");
					        var iframe = jQuery('<iframe name="cpc_forum_postiframe" id="cpc_forum_postiframe" style="display:none;" />');
					        jQuery("body").append(iframe);

					        var form = jQuery('#cpc_forum_post_theuploadform');
					        form.attr("action", jQuery('#cpc_forum_plugins_url').val()+"/lib_forum.php");
					        form.attr("method", "post");
					        form.attr("enctype", "multipart/form-data");
					        form.attr("encoding", "multipart/form-data");
					        form.attr("target", "cpc_forum_postiframe");
					        form.attr("file", jQuery('#cpc_forum_image_upload').val());
					        form.submit();

					        jQuery("#cpc_forum_postiframe").load(function () {
                                iframeContents = jQuery("#cpc_forum_postiframe")[0].contentWindow.document.body.innerHTML;
                                if (iframeContents.indexOf("*") == 0) {
                                    alert(jQuery('#valid_exts_msg').val());
                                    iframeContents = iframeContents.replace('*', '');
                                }
                                iframeContents = iframeContents.split("|");
                                var reload_loc = document.location; // reload current page
                                var post_id = iframeContents[0];
								var cpc_forum_moderate = iframeContents[2];
								if (iframeContents[1] != 'reload') {
                                    reload_loc = iframeContents[1].replace('&amp;','&'); // to go straight to new post or url to redirect to	
                                }
                                // now call AJAX to do hook, like subscribers, so can skip any delay
                                jQuery.post(
                                    cpc_forum_ajax.ajaxurl,
                                    {
                                        action : 'cpc_forum_post_add_ajax_hook',
                                        post_id : post_id
                                    },
                                    function(response) {
                                        //alert(response); // Will show debugging info
                                    }                                     
                                );                                
                                
                                // and reload page whilst the hook carrys on (wait a little to ensure above is fired)
                                setTimeout(function(){ document.location = reload_loc }, 2000);

					        });
                            
					    }

					} else {

						jQuery('#cpc_forum_post_content_label').addClass('cpc_field_error');

					}

				} else {

					jQuery('#cpc_forum_post_title').addClass('cpc_field_error');

				}

			}

		});

	}

	/* Add Reply */
	
	if (jQuery("#cpc_forum_comment_button").length) {
                
		jQuery('#cpc_forum_comment_button').prop("disabled", false);
		jQuery('#cpc_forum_comment').val('');
		
		jQuery("#cpc_forum_comment_button").on('click', function (event) {

			event.preventDefault();

			if(jQuery('#cpc_forum_comment_form').css('display') == 'none') {

				jQuery('#cpc_forum_comment_form').show();
				document.getElementById('cpc_forum_comment').focus();

			} else {

                if (typeof tinyMCE !== 'undefined') {
                    /* check if Visual or Text mode */
                    if (tinyMCE.activeEditor != null) {
                        var editor = tinyMCE.get('cpc_forum_comment');
                        var content = editor.getContent();
                    } else {
                        var content = jQuery('#cpc_forum_comment').val();
                    }
                } else {
                    var content = jQuery('#cpc_forum_comment').val();
                }                

                if (content.length || cpc_forum_ajax.is_admin) {

                    // Check for mandatory fields
                    var all_filled = true;
                    jQuery('.cpc_mandatory_field').each(function(i, obj) {
                        if (jQuery(this).val() == '') {
                            jQuery(this).addClass('cpc_field_error');
                            all_filled = false;
                        }
                    });
                    
                    if (all_filled) {
                        
                        jQuery(this).attr("disabled", true);

                        jQuery("body").addClass("cpc_wait_loading");

                        var iframe = jQuery('<iframe name="cpc_forum_commentiframe" id="cpc_forum_commentiframe" style="display: none;" />');
                        jQuery("body").append(iframe);

                        var form = jQuery('#cpc_forum_comment_theuploadform');
                        form.attr("action", jQuery('#cpc_forum_plugins_url').val()+"/lib_forum.php");
                        form.attr("method", "post");
                        form.attr("enctype", "multipart/form-data");
                        form.attr("encoding", "multipart/form-data");
                        form.attr("target", "cpc_forum_commentiframe");
                        form.submit();

                        jQuery("#cpc_forum_commentiframe").load(function () {
                            iframeContents = jQuery("#cpc_forum_commentiframe")[0].contentWindow.document.body.innerHTML;
                            if (iframeContents.indexOf("*") == 0) {
                                alert(jQuery('#valid_exts_msg').val());
                                iframeContents = iframeContents.replace('*', '');
                            }                            
                            if (iframeContents == 'reload') {
                            	var url = document.location.toString();
                            	url = url.replace(/[?,&]gotoend=1/g,'');
                            	if(url.indexOf('?') > 0) {
                            		url += '&gotoend=1';
                            	} else {
                            		url += '?gotoend=1';
                            	}
                                window.location = url;
                            } else {
                                window.location = iframeContents;
                            }
                        });
                        
                    } else {

                        jQuery('#cpc_forum_comment').addClass('cpc_field_error');

                    }                        

				} else {

					jQuery('#cpc_forum_comment').addClass('cpc_field_error');

				}

			}

		});

	}

	// Add comment (comment on reply)

	if (jQuery(".cpc_forum_post_comment_form_submit").length) {

		jQuery('.cpc_forum_post_comment_form_submit').prop("disabled", false);
		jQuery('.cpc_forum_post_comment_form').val('');
		
		jQuery(".cpc_forum_post_comment_form_submit").on('click', function (event) {

            /*
            alert('start');
            jQuery.post(
                cpc_forum_ajax.ajaxurl,
                {
                    action : 'cpc_forum_add_subcomment'
                },
                function(response) {
                    alert('done');
                }   
            );
            */
            
			event.preventDefault();
			var id = jQuery(this).attr('rel');

			if(jQuery('#sub_comment_div_'+id).css('display') == 'none') {

				jQuery('#sub_comment_div_'+id).slideDown('fast');
				document.getElementById('sub_comment_'+id).focus();

			} else {

                var the_button = this;
				var the_textarea = jQuery('#sub_comment_'+id);
				jQuery(this).parent().append('<div id="cpc_tmp" style="width:20px;height:20px;margin-bottom:20px"><img src="'+jQuery('#cpc_wait_url').html()+'" /></div>');
				jQuery(the_button).hide();
				jQuery(the_textarea).hide();

				if (jQuery('#sub_comment_'+id).val().length) {

					var comment = jQuery('#sub_comment_'+id).val();
					jQuery('#sub_comment_'+id).val('');

					jQuery.post(
					    cpc_forum_ajax.ajaxurl,
					    {
					        action : 'cpc_forum_add_subcomment',
					        post_id : jQuery(this).data('post-id'),
					        comment_id : id,
					        comment : comment,
					        size : jQuery(this).data('size'),
					        cpc_forum_moderate : 1,
					    },
					    function(response) {
					    	if (jQuery('#sub_comment_div_'+id).prev('.cpc_forum_post_subcomments').length) {
								jQuery('#sub_comment_div_'+id).prev('.cpc_forum_post_subcomments').append(response);
							} else {
								jQuery('#sub_comment_div_'+id).prepend(response);
							}
							jQuery('.cpc_forum_post_subcomment').slideDown('fast');
							jQuery("body").removeClass("cpc_wait_loading");
							document.getElementById('sub_comment_'+id).focus();							
                            
                            // Show any content marked for after page has loaded from returned content
                            if (jQuery('.cpc_show_after_page_load').length) {
                                jQuery('.cpc_show_after_page_load').show();
                            }
                            
                            jQuery('#sub_comment_'+id).removeClass('cpc_field_error');
                			jQuery("#cpc_tmp").remove();
                			jQuery(the_button).show();
                			jQuery(the_textarea).show();
                            
					    }   
					);                  

				} else {
                    
                    jQuery('#sub_comment_'+id).addClass('cpc_field_error');
                	jQuery("#cpc_tmp").remove();
                	jQuery(the_button).show();
                	jQuery(the_textarea).show();

				}

			}

		});

	}

	// Reopen post
	
	if (jQuery("#cpc_forum_comment_reopen_button").length) {

		jQuery('#cpc_forum_comment_reopen_button').prop("disabled", false);
		
		jQuery("#cpc_forum_comment_reopen_button").on('click', function (event) {

			event.preventDefault();
			jQuery(this).attr("disabled", true);
			jQuery("body").addClass("cpc_wait_loading");

			var post_id = jQuery('#reopen_post_id').val();

			jQuery.post(
			    cpc_forum_ajax.ajaxurl,
			    {
			        action : 'cpc_forum_comment_reopen',
			        post_id : post_id
			    },
			    function(response) {
			    	location.reload();
			    }   
			);

		});

	}

	/* Choose forum */
	if (jQuery("#cpc_forum_post_choose").length) {
		jQuery("#cpc_forum_post_choose").select2();
	}

	/* Edit Post */

	if (jQuery("#cpc_post_forum_slug").length) {
		jQuery("#cpc_post_forum_slug").select2();
	}
    
	/* Forum Settings */
	
    jQuery('body').on('click', '.cpc_forum_settings', function() {    	
		jQuery('.cpc_forum_settings_options').hide();
		jQuery('.cpc_comment_settings_options').hide();
		jQuery(this).next('.cpc_forum_settings_options').show();
	});	

    jQuery('body').on('click', '.cpc_forum_comment_settings', function() {    	
		jQuery('.cpc_forum_settings_options').hide();
		jQuery('.cpc_comment_settings_options').hide();
		jQuery(this).next('.cpc_forum_comment_settings_options').show();
	});	

	jQuery(document).on('mouseup', function (e) {
		jQuery('.cpc_forum_settings_options').hide();
		jQuery('.cpc_comment_settings_options').hide();
	});

	/* Closed switch */

	jQuery("#closed_switch").on('click', function (event) {
		var state = 'off';
		if (jQuery(this).is(":checked")) {
			jQuery('.cpc_forum_post_closed').slideDown('fast');
			state = 'on';
		} else {
			jQuery('.cpc_forum_post_closed').slideUp('fast');
		}
		jQuery.post(
		    cpc_forum_ajax.ajaxurl,
		    {
		        action : 'cpc_forum_closed_switch',
		        state : state
		    },
		    function(response) {
		    }   
		);
	});	
	

});

    
/* Edit Reply */

function cpc_validate_forum_reply_edit() {

    var r = true;
	
    if (jQuery('#cpc_forum_comment_edit_textarea').val().length) {

        // Check for mandatory fields
        var all_filled = true;
        jQuery('.cpc_mandatory_field').each(function(i, obj) {
            if (jQuery(this).val() == '') {
                jQuery(this).addClass('cpc_field_error');
                all_filled = false;
                r = false;
            }
        });

        if (!all_filled)
            jQuery('#cpc_forum_comment').addClass('cpc_field_error');

    } else {
        jQuery('#cpc_forum_comment_edit_textarea').addClass('cpc_field_error');
        r = false;
    } 

    return r;

}

document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.cpc_bbcode_toolbar button').forEach(function(btn) {
        btn.addEventListener('click', function() {
            // Das nächste Textarea nach der Toolbar finden
            var toolbar = btn.closest('.cpc_bbcode_toolbar');
            var textarea = toolbar ? toolbar.nextElementSibling : null;
            if (!textarea || textarea.tagName.toLowerCase() !== 'textarea') return;
            var start = textarea.selectionStart;
            var end = textarea.selectionEnd;
            var selected = textarea.value.substring(start, end);
            var before = textarea.value.substring(0, start);
            var after = textarea.value.substring(end);

            var tag = btn.getAttribute('data-tag');
            var insert = '';
            if (tag === 'url') {
                insert = '[url=LINK]'+(selected||'Linktext')+'[/url]';
            } else if (tag === 'img') {
                insert = '[img]'+(selected||'URL')+'[/img]';
            } else {
                insert = '['+tag+']'+selected+'[/'+tag+']';
            }
            textarea.value = before + insert + after;
            textarea.focus();
            textarea.selectionStart = textarea.selectionEnd = before.length + insert.length;
        });
    });
});
