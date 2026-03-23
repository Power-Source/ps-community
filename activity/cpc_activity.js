jQuery(document).ready(function() {

	// Theme classes are rendered server-side on #cpc_activity_plus

jQuery('#cpc_activity_items').show();
jQuery('#cpc_activity_post_div').show();
jQuery('.cpc_activity_settings').show();
jQuery('#cpc_activity_post_button').attr("disabled", false);

	// Get activity on page first load
	if (jQuery("#cpc_activity_ajax_div").length) {
        if (!jQuery('#cpc_activity_post_private_msg').length) {
		  cpc_get_ajax_activity(0,jQuery('#cpc_page_size').html(),'replace');
        }
	}
    
    // Get more activity posts
    jQuery('body').on('click', '#cpc_activity_load_more', function() {    
        var start = jQuery(this).data('count');
        jQuery('#cpc_activity_load_more_div').remove();
        jQuery('#cpc_activity_ajax_div').after('<div id="cpc_tmp" style="width:100%;text-align:center;"><img style="width:20px;height:20px;margin-top:-3px;" src="'+jQuery('#cpc_wait_url').html()+'" /></div>');
        cpc_get_ajax_activity(start+1,jQuery('#cpc_page_size').html(),'append');
    });
    
	// Activity Settings
    jQuery('body').on('hover', '.cpc_activity_content', function() {
        jQuery('.cpc_activity_settings').hide();
        jQuery('.cpc_comment_settings').hide();
        jQuery('.cpc_comment_settings_options').hide();
        jQuery(this).children('.cpc_activity_settings').show();
	});
    jQuery('body').on('click', '.cpc_activity_settings', function() {
		jQuery('.cpc_activity_settings_options').hide();
		jQuery('.cpc_comment_settings_options').hide();
		jQuery(this).next('.cpc_activity_settings_options').show();
	});

	// Comment Settings
    jQuery('body').on('hover', '.cpc_activity_comment', function() {
        jQuery('.cpc_comment_settings').hide();
        jQuery(this).children('.cpc_comment_settings').show();
	});
    jQuery('body').on('click', '.cpc_comment_settings', function() {
        jQuery('.cpc_comment_settings').hide();
		jQuery('.cpc_activity_settings_options').hide();
		jQuery('.cpc_comment_settings_options').hide();
		jQuery(this).next('.cpc_comment_settings_options').show();
	});    

	jQuery(document).on('mouseup', function(e) {
		jQuery('.cpc_activity_settings_options').hide();
		jQuery('.cpc_comment_settings').hide();
		jQuery('.cpc_comment_settings_options').hide();
	});

	// Update activity visibility for existing posts
	function cpcShowVisibilityFeedback(select, message, isSuccess) {
		var feedback = select.closest('.cpc_activity_visibility_meta').find('.cpc_activity_visibility_feedback');
		if (!feedback.length) {
			return;
		}

		feedback.removeClass('is-success is-error').text(message || '');
		if (message) {
			feedback.addClass(isSuccess ? 'is-success' : 'is-error');
			setTimeout(function() {
				feedback.removeClass('is-success is-error').text('');
			}, 2200);
		}
	}

	jQuery('body').on('focus', '.cpc_activity_visibility_edit', function() {
		jQuery(this).data('prev', jQuery(this).val());
	});

	jQuery('body').on('change', '.cpc_activity_visibility_edit', function() {
		var select = jQuery(this);
		var postId = parseInt(select.data('post-id'), 10);
		var visibility = select.val();
		var prev = select.data('prev');

		if (!postId || !visibility) {
			return;
		}

		select.prop('disabled', true);

		jQuery.post(
			cpc_activity_ajax.ajaxurl,
			{
				action: 'cpc_activity_update_visibility',
				post_id: postId,
				visibility: visibility,
				security: cpc_activity_ajax.nonce
			},
			function(response) {
				if (response && response.success && response.data) {
					if (response.data.badge_html) {
						select.closest('.cpc_activity_visibility_meta').find('.cpc_activity_visibility_label').replaceWith(response.data.badge_html);
					} else {
						select.closest('.cpc_activity_visibility_meta').find('.cpc_activity_visibility_label').html('&middot; ' + response.data.label);
					}
					select.data('prev', visibility);
					cpcShowVisibilityFeedback(select, 'Gespeichert', true);
				} else {
					if (typeof prev !== 'undefined') {
						select.val(prev);
					}
					var errorText = (response && response.data && response.data.message) ? response.data.message : 'Speichern fehlgeschlagen';
					cpcShowVisibilityFeedback(select, errorText, false);
				}
				select.prop('disabled', false);
			}
		).fail(function() {
			if (typeof prev !== 'undefined') {
				select.val(prev);
			}
			cpcShowVisibilityFeedback(select, 'Netzwerkfehler', false);
			select.prop('disabled', false);
		});
	});
	
	// Activity Plus Event Handlers (global - work for both user and group activity)
	jQuery('body').on('click', '.cpc_activity_plus_toggle', function() {
		var target = jQuery(this).data('target');
		if (!target) return;
		jQuery('.cpc_activity_plus_wrap').not('#'+target).hide();
		jQuery('#'+target).slideToggle('fast');
	});

	jQuery('body').on('blur', '#cpc_activity_plus_link_url', function() {
		if (!cpc_activity_ajax.activity_plus || !cpc_activity_ajax.activity_plus.enabled) return;
		var url = jQuery(this).val();
		if (!url.length) {
			jQuery('#cpc_activity_plus_link_preview').html('');
			return;
		}
		jQuery('#cpc_activity_plus_link_preview').html('<div class="cpc_activity_plus_video_preview_loading"></div>');
		jQuery.post(
			cpc_activity_ajax.ajaxurl,
			{
				action: 'cpc_activity_plus_preview_link',
				url: url
			},
			function(response) {
				if (response && response.success && response.data) {
					var html = '<div class="cpc_activity_plus_link_preview_card">' +
						'<table class="cpc_activity_plus_link_preview_table">' +
						'<tr>' +
							'<td><div class="cpc_activity_plus_link_preview_image_container">';
					
					if (response.data.images && response.data.images.length) {
						jQuery.each(response.data.images, function(idx, img) {
							html += '<img src="'+img+'" alt="" class="cpc_activity_plus_link_preview_image' + (idx === 0 ? ' active' : '') + '" />';
						});
					}
					
					html += '</div></td>' +
						'<td>' +
							'<div class="cpc_activity_plus_link_preview_title">'+response.data.title+'</div>' +
							'<div class="cpc_activity_plus_link_preview_url">'+response.data.url+'</div>';
					
					if (response.data.description && response.data.description.length) {
						html += '<div class="cpc_activity_plus_link_preview_desc">'+response.data.description+'</div>';
					}
					
					if (response.data.images && response.data.images.length > 1) {
						html += '<div class="cpc_activity_plus_thumbnail_chooser">' +
							'<div class="cpc_activity_plus_thumbnail_nav">' +
								'<button type="button" class="cpc_activity_plus_thumb_left">&lsaquo; zurück</button>' +
								'<span>Thumbnail</span>' +
								'<button type="button" class="cpc_activity_plus_thumb_right">weiter &rsaquo;</button>' +
							'</div>' +
							'<label class="cpc_activity_plus_no_thumbnail_option">' +
								'<input type="checkbox" id="cpc_activity_plus_no_thumbnail" /> ' +
								'Keine Vorschau' +
							'</label>' +
						'</div>';
					}
					
					html += '</td>' +
						'</tr>' +
						'</table>' +
						'</div>';
					
					jQuery('#cpc_activity_plus_link_preview').html(html);
				} else {
					jQuery('#cpc_activity_plus_link_preview').html('');
				}
			}
		);
	});

	jQuery('body').on('click', '.cpc_activity_plus_thumb_left', function(e) {
		e.preventDefault();
		var $visible = jQuery('#cpc_activity_plus_link_preview').find('.cpc_activity_plus_link_preview_image.active');
		var $prev = $visible.prev('.cpc_activity_plus_link_preview_image');
		if ($prev.length) {
			$visible.removeClass('active');
			$prev.addClass('active');
		}
	});

	jQuery('body').on('click', '.cpc_activity_plus_thumb_right', function(e) {
		e.preventDefault();
		var $visible = jQuery('#cpc_activity_plus_link_preview').find('.cpc_activity_plus_link_preview_image.active');
		var $next = $visible.next('.cpc_activity_plus_link_preview_image');
		if ($next.length) {
			$visible.removeClass('active');
			$next.addClass('active');
		}
	});

	jQuery('body').on('change', '#cpc_activity_plus_no_thumbnail', function() {
		var $container = jQuery('#cpc_activity_plus_link_preview');
		if (jQuery(this).is(':checked')) {
			$container.find('.cpc_activity_plus_link_preview_image_container').hide();
			$container.find('.cpc_activity_plus_thumbnail_nav').hide();
		} else {
			$container.find('.cpc_activity_plus_link_preview_image_container').show();
			$container.find('.cpc_activity_plus_thumbnail_nav').show();
		}
	});

	jQuery('body').on('blur', '#cpc_activity_plus_video_url', function() {
		if (!cpc_activity_ajax.activity_plus || !cpc_activity_ajax.activity_plus.enabled) return;
		var url = jQuery(this).val();
		if (!url.length) {
			jQuery('#cpc_activity_plus_video_preview').html('');
			return;
		}
		jQuery('#cpc_activity_plus_video_preview').html('<div class="cpc_activity_plus_video_preview_loading"></div>');
		jQuery.post(
			cpc_activity_ajax.ajaxurl,
			{
				action: 'cpc_activity_plus_preview_video',
				url: url
			},
			function(response) {
				if (response && response.success && response.data) {
					var html = '<div class="cpc_activity_plus_video_preview_card">';
					if (response.data.thumbnail) {
						html += '<div class="cpc_activity_plus_video_preview_thumb">';
						html += '<img src="'+response.data.thumbnail+'" alt="" />';
						html += '</div>';
					}
					html += '<div class="cpc_activity_plus_video_preview_embed">';
					html += response.data.embed;
					html += '</div>';
					html += '</div>';
					jQuery('#cpc_activity_plus_video_preview').html(html);
				} else {
					jQuery('#cpc_activity_plus_video_preview').html('<small style="color:#c00;">Video-Format wird nicht unterstützt</small>');
				}
			}
		);
	});
	
	// Add activity post (user activity)
	if (jQuery('#cpc_activity_post').length) {

		if (cpc_activity_ajax.activity_post_focus)
			jQuery('#cpc_activity_post').focus();

		jQuery("#cpc_activity_post_button").on('click', function (event) {

			event.preventDefault();

			var cpcPlusHasValue = false;
			if (cpc_activity_ajax.activity_plus && cpc_activity_ajax.activity_plus.enabled) {
				if (jQuery('#cpc_activity_plus_link_url').length && jQuery('#cpc_activity_plus_link_url').val().length) cpcPlusHasValue = true;
				if (jQuery('#cpc_activity_plus_video_url').length && jQuery('#cpc_activity_plus_video_url').val().length) cpcPlusHasValue = true;
				if (jQuery('#cpc_activity_plus_remote_images').length && jQuery('#cpc_activity_plus_remote_images').val().length) cpcPlusHasValue = true;
			}

			if (jQuery('#cpc_activity_post').val().length || jQuery('.file-input-name').length || cpcPlusHasValue) {

                jQuery('#cpc_activity_post_button').after('<div id="cpc_tmp"><img style="width:20px;height:20px;" src="'+jQuery('#cpc_wait_url').html()+'" /></div>');

		        var iframe = jQuery('<iframe name="postiframe" id="postiframe" style="display: none;" />');
		        jQuery("body").append(iframe);

		        var form = jQuery('#theuploadform');
		        form.attr("action", cpc_activity_ajax.plugins_url+"/lib_activity.php");
		        form.attr("method", "post");
		        form.attr("enctype", "multipart/form-data");
		        form.attr("encoding", "multipart/form-data");
		        form.attr("target", "postiframe");
		        form.attr("file", jQuery('#cpc_activity_image_upload').val());
		        form.submit();

		        jQuery("#postiframe").load(function () {
                    
			    	jQuery("#cpc_tmp").remove();
                    var tmp = 'cpc_'+jQuery.now();
		            iframeContents = '<div id="'+tmp+'" style="display:none">'+jQuery("#postiframe")[0].contentWindow.document.body.innerHTML+'</div>';
                    jQuery('#cpc_activity_post').val('').focus();
					// Reset Activity Plus state after successful submit
					jQuery('#cpc_activity_plus_link_url').val('');
					jQuery('#cpc_activity_plus_video_url').val('');
					jQuery('#cpc_activity_plus_remote_images').val('');
					jQuery('#cpc_activity_plus_link_preview').html('');
					jQuery('#cpc_activity_plus_video_preview').html('');
					jQuery('#cpc_activity_plus_no_thumbnail').prop('checked', false);
					if (jQuery('#cpc_activity_plus_images').length) {
						jQuery('#cpc_activity_plus_images').val('');
					}
					jQuery('.cpc_activity_plus_wrap').hide();
                    jQuery("#postiframe").remove();                    
			    	jQuery('#cpc_activity_items').prepend(iframeContents);
                    jQuery('#'+tmp).slideDown('fast');
                    
                    if (jQuery('#cpc_activity_post_private_msg').length) { jQuery('#cpc_activity_post_private_msg').remove(); }
                    jQuery.post( cpc_activity_ajax.ajaxurl, { action : 'cpc_null' } ); // kick ajaxComplete

		        });

		    } else {
		    	jQuery('#cpc_activity_post').css('border', '1px solid red');
		    	jQuery('#cpc_activity_post').css('background-color', '#faa');
		    	jQuery('#cpc_activity_post').css('color', '#000');
		    }

	        return false;

	    });

	}

	// Add activity comment
    jQuery('body').on('click', '.cpc_activity_post_comment_button', function() {

		var id = jQuery(this).attr('rel');		
		var comment = jQuery('#post_comment_'+id).val();
        var t = this;
        
		if (comment.length) {

			jQuery(t).after('<div id="cpc_tmp" style="width:20px;height:20px;"><img src="'+jQuery('#cpc_wait_url').html()+'" /></div>');
            jQuery(t).hide();
            jQuery('#post_comment_'+id).hide().val('');

            jQuery.post(
			    cpc_activity_ajax.ajaxurl,
			    {
			        action : 'cpc_activity_comment_add',
			        post_id : id,
			        comment_content: comment,
			        size : jQuery(this).data('size'),
			        link : jQuery(this).data('link'),
			        security : cpc_activity_ajax.nonce
			    },
			    function(response) {
			    	jQuery('#cpc_activity_'+id+'_content').append(response);
			    	jQuery("#cpc_tmp").remove();
                    jQuery(t).show();
                    jQuery('#post_comment_'+id).show();
			    }   
			);

		}

	});

	// Make post sticky
    jQuery('body').on('click', '.cpc_activity_settings_sticky', function() {

		var id = jQuery(this).attr('rel');
		jQuery(this).hide();
		var height = jQuery('#cpc_activity_'+id).height();
		jQuery('#cpc_activity_'+id).animate({ height: 1 }, 500, function() {
			jQuery("#cpc_activity_items").prepend(jQuery('#cpc_activity_'+id));
			jQuery('#cpc_activity_'+id).animate({ height: height }, 500);
			
			jQuery.post(
			    cpc_activity_ajax.ajaxurl,
			    {
			        action : 'cpc_activity_settings_sticky',
			        post_id : id,
			        security : cpc_activity_ajax.nonce
			    },
			    function(response) {
			    }   
			);

		});

	});

    // Hide post
    jQuery('body').on('click', '.cpc_activity_settings_hide', function() {

		var id = jQuery(this).attr('rel');
		jQuery(this).hide();
		var height = jQuery('#cpc_activity_'+id).height();
        
        jQuery('#cpc_activity_'+id).slideUp();
        //jQuery("#cpc_activity_items").prepend(jQuery('#cpc_activity_'+id));
        //jQuery('#cpc_activity_'+id).animate({ height: height }, 500);

        jQuery.post(
            cpc_activity_ajax.ajaxurl,
            {
                action : 'cpc_activity_settings_hide',
                post_id : id,
                security : cpc_activity_ajax.nonce
            },
            function(response) {
            }   
        );

	});    

	// Make post unsticky
    jQuery('body').on('click', '.cpc_activity_settings_unsticky', function() {

		var id = jQuery(this).attr('rel');
		jQuery(this).hide();

		jQuery('#cpc_activity_'+id).cpc_shake(3, 5, 100);

		jQuery.post(
		    cpc_activity_ajax.ajaxurl,
		    {
		        action : 'cpc_activity_settings_unsticky',
		        post_id : id,
		        security : cpc_activity_ajax.nonce
		    },
		    function(response) {
		    }   
		);

	});

	// Delete post from settings
    jQuery('body').on('click', '.cpc_activity_settings_delete', function() {

		var id = jQuery(this).attr('rel');
		jQuery('#cpc_activity_'+id).fadeOut('slow');

		jQuery.post(
		    cpc_activity_ajax.ajaxurl,
		    {
		        action : 'cpc_activity_settings_delete',
		        id : id,
		        security : cpc_activity_ajax.nonce
		    },
		    function(response) {
		    }   
		);

	});

	// Delete comment from settings
    jQuery('body').on('click', '.cpc_comment_settings_delete', function() {

        var id = jQuery(this).attr('rel');
		jQuery('#cpc_comment_'+id).fadeOut('slow');

		jQuery.post(
		    cpc_activity_ajax.ajaxurl,
		    {
		        action : 'cpc_comment_settings_delete',
		        id : id,
		        security : cpc_activity_ajax.nonce
		    },
		    function(response) {
		    }   
		);

	});	

	// Clicked on more... to expand post
    jQuery('body').on('click', '.activity_item_more', function() {
		var id = jQuery(this).attr('rel');
		jQuery('#activity_item_snippet_'+id).hide();
		jQuery('#activity_item_full_'+id).slideDown('slow');
	});

	// Show hidden comments
	jQuery("body").on('click', '.cpc_activity_hidden_comments', function () {
		jQuery(this).hide();
		jQuery('.cpc_activity_item_'+jQuery(this).attr('rel')).slideDown('slow');
	});
    
    // ------------------------------------------------------------------------------------- ADMIN
    
    // Admin - remove hidden flags
    jQuery("#cpc_activity_unhide_all").on('click', function (event) {

        jQuery.post(
            cpc_activity_ajax.ajaxurl,
            {
                action : 'cpc_activity_unhide_all',
                post_id : jQuery(this).attr('rel'),
                security : cpc_activity_ajax.nonce
            },
            function(response) {
                alert('OK');
            }   
        ); 

    });
    
	// Admin - new activity
	if (jQuery("#cpc_target").length) {

		if (jQuery("#cpc_target").val() == '') {
			jQuery("#cpc_target").select2({
			    minimumInputLength: 1,
			    query: function (query) {
					jQuery.post(
					    cpc_ajax.ajaxurl,
					    {
					        action : 'cpc_get_users',
					        term : query.term
					    },
					    function(response) {
					    	var json = JSON.parse(response);
					    	var data = {results: []}, i, j, s;
							for(var i = 0; i < json.length; i++) {
						    	var obj = json[i];
						    	data.results.push({id: obj.value, text: obj.label});
							}
							query.callback(data);	    	
					    }   
					);
			    }
			});
		}	

	}    

});


var QueryString = function () {
  // This function is anonymous, is executed immediately and 
  // the return value is assigned to QueryString!
  var query_string = {};
  var query = window.location.search.substring(1);
  var vars = query.split("&");
  for (var i=0;i<vars.length;i++) {
    var pair = vars[i].split("=");
    	// If first entry with this name
    if (typeof query_string[pair[0]] === "undefined") {
      query_string[pair[0]] = pair[1];
    	// If second entry with this name
    } else if (typeof query_string[pair[0]] === "string") {
      var arr = [ query_string[pair[0]], pair[1] ];
      query_string[pair[0]] = arr;
    	// If third or later entry with this name
    } else {
      query_string[pair[0]].push(pair[1]);
    }
  } 
    return query_string;
} ();

jQuery.fn.cpc_shake = function(intShakes, intDistance, intDuration) {
    this.each(function() {
        jQuery(this).css("position","relative"); 
        for (var x=1; x<=intShakes; x++) {
        	jQuery(this).animate({left:intDistance*-1}, (intDuration/intShakes)/4)
    			.animate({left:intDistance}, (intDuration/intShakes)/2)
    			.animate({left:0}, (intDuration/intShakes)/4);
    	}
  	});
	return this;
};

// Ajax function to return activity
function cpc_get_ajax_activity(start, page_size, mode) {

    var arr = jQuery('#cpc_activity_array').html();
    var atts = jQuery('#cpc_atts_array').html();
    var user_id = jQuery('#cpc_user_id').html();
    var nonce = jQuery('#cpc_nonce_'+user_id).html();

    jQuery.post(
        cpc_activity_ajax.ajaxurl,
        {
            action : 'cpc_return_activity_posts',
            this_user : jQuery('#cpc_this_user').html(),
            user_id : user_id,
            start: start,
            page: page_size,
            nonce: nonce,
            data: {arr: arr, atts: atts},
        },
        function(response) {
            if (mode == 'replace') {
                if (jQuery("#cpc_activity_post_private_msg").length) {
                    jQuery('#cpc_activity_post_private_msg').html(response);
                } else {
                    jQuery('#cpc_activity_ajax_div').html(response);
                }
            } else {
                jQuery('#cpc_tmp').remove();
                jQuery('#cpc_activity_ajax_div').append(response);
            }
        }   
    );

}

(function () {
	'use strict';

	var hasActivityPlus = typeof cpc_activity_ajax !== 'undefined' && cpc_activity_ajax.activity_plus;
	if (!hasActivityPlus || !cpc_activity_ajax.activity_plus.enabled || !cpc_activity_ajax.activity_plus.use_builtin_lightbox) {
		return;
	}

	var lightbox = null;
	var imageEl = null;
	var captionWrap = null;
	var captionText = null;
	var captionToggle = null;
	var isOpen = false;

	function closeLightbox() {
		if (!lightbox) {
			return;
		}
		lightbox.classList.remove('is-open');
		lightbox.setAttribute('aria-hidden', 'true');
		document.body.classList.remove('cpc_activity_plus_lightbox_open');
		if (imageEl) {
			imageEl.removeAttribute('src');
		}
		isOpen = false;
	}

	function ensureLightbox() {
		if (lightbox) {
			return;
		}

		lightbox = document.createElement('div');
		lightbox.className = 'cpc_activity_plus_lightbox';
		lightbox.setAttribute('aria-hidden', 'true');
		lightbox.innerHTML = '' +
			'<div class="cpc_activity_plus_lightbox__backdrop" data-lightbox-close="1"></div>' +
			'<div class="cpc_activity_plus_lightbox__content" role="dialog" aria-modal="true" aria-label="Bildvorschau">' +
				'<button type="button" class="cpc_activity_plus_lightbox__close" aria-label="Schließen">&times;</button>' +
				'<img class="cpc_activity_plus_lightbox__image" alt="" />' +
				'<div class="cpc_activity_plus_lightbox__caption_wrap is-hidden">' +
					'<button type="button" class="cpc_activity_plus_lightbox__caption_toggle">Beschreibung anzeigen</button>' +
					'<div class="cpc_activity_plus_lightbox__caption is-hidden"></div>' +
				'</div>' +
			'</div>';

		document.body.appendChild(lightbox);

		imageEl = lightbox.querySelector('.cpc_activity_plus_lightbox__image');
		captionWrap = lightbox.querySelector('.cpc_activity_plus_lightbox__caption_wrap');
		captionText = lightbox.querySelector('.cpc_activity_plus_lightbox__caption');
		captionToggle = lightbox.querySelector('.cpc_activity_plus_lightbox__caption_toggle');

		lightbox.querySelector('.cpc_activity_plus_lightbox__close').addEventListener('click', closeLightbox);
		lightbox.addEventListener('click', function (event) {
			if (event.target && event.target.getAttribute('data-lightbox-close') === '1') {
				closeLightbox();
			}
		});

		captionToggle.addEventListener('click', function () {
			if (captionText.classList.contains('is-hidden')) {
				captionText.classList.remove('is-hidden');
				captionToggle.textContent = 'Beschreibung ausblenden';
			} else {
				captionText.classList.add('is-hidden');
				captionToggle.textContent = 'Beschreibung anzeigen';
			}
		});

		document.addEventListener('keydown', function (event) {
			if (!isOpen) {
				return;
			}
			if (event.key === 'Escape') {
				closeLightbox();
			}
		});
	}

	function openLightbox(src, caption) {
		ensureLightbox();
		imageEl.setAttribute('src', src);

		if (caption && caption.trim() !== '') {
			captionWrap.classList.remove('is-hidden');
			captionText.textContent = caption;
			captionText.classList.add('is-hidden');
			captionToggle.textContent = 'Beschreibung anzeigen';
		} else {
			captionWrap.classList.add('is-hidden');
			captionText.textContent = '';
		}

		lightbox.classList.add('is-open');
		lightbox.setAttribute('aria-hidden', 'false');
		document.body.classList.add('cpc_activity_plus_lightbox_open');
		isOpen = true;
	}

	document.addEventListener('click', function (event) {
		var link = event.target && event.target.closest ? event.target.closest('a.cpc_activity_plus_image') : null;
		if (!link) {
			return;
		}

		var imageUrl = link.getAttribute('href');
		if (!imageUrl) {
			return;
		}

		event.preventDefault();
		openLightbox(imageUrl, link.getAttribute('data-caption') || '');
	});
})();