jQuery(document).ready(function() {

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
	
	// Add activity post
	if (jQuery('#cpc_activity_post').length) {

		if (cpc_activity_ajax.activity_post_focus)
			jQuery('#cpc_activity_post').focus();

		jQuery("#cpc_activity_post_button").on('click', function (event) {

			event.preventDefault();

			if (jQuery('#cpc_activity_post').val().length || jQuery('.file-input-name').length) {

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
			        link : jQuery(this).data('link')
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
			        post_id : id
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
                post_id : id
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
		        post_id : id
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
		        id : id
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
		        id : id
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