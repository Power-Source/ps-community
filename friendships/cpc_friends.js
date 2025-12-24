jQuery(document).ready(function() {

	// ***** Favourite Friendships (UI) *****	
    
    jQuery('body').on('click', '.cpc_add_remove_favourite', function(event) {
        
        var action = jQuery(this).attr('rel');
        
        jQuery(this).remove();
        if (action == 'add') {
            jQuery('#cpc_favourite_no_msg').show();
            jQuery.post(
                cpc_friendships_ajax.ajaxurl,
                {
                    action : 'cpc_add_favourite',
                    user_id: jQuery(this).data('user_id')
                },
                function(response) {
                }   
            );
        } else {
            jQuery('#cpc_favourite_yes_msg').show();
            jQuery.post(
                cpc_friendships_ajax.ajaxurl,
                {
                    action : 'cpc_remove_favourite',
                    user_id: jQuery(this).data('user_id')
                },
                function(response) {
                }   
            );            
        }
        
    });
    

    jQuery('body').on('click', '.cpc_add_favourite', function(event) {

		jQuery(this).attr('src', cpc_friendships_ajax.fav_on);
		jQuery(this).removeClass('cpc_add_favourite');
		jQuery(this).addClass('cpc_remove_favourite');

		jQuery.post(
		    cpc_friendships_ajax.ajaxurl,
		    {
		        action : 'cpc_add_favourite',
		        user_id: jQuery(this).attr('rel')
		    },
		    function(response) {
		    }   
		);

	});

    jQuery('body').on('click', '.cpc_remove_favourite', function(event) {

		jQuery(this).attr('src', cpc_friendships_ajax.fav_off);
		jQuery(this).removeClass('cpc_remove_favourite');
		jQuery(this).addClass('cpc_add_favourite');

		jQuery.post(
		    cpc_friendships_ajax.ajaxurl,
		    {
		        action : 'cpc_remove_favourite',
		        user_id: jQuery(this).attr('rel')
		    },
		    function(response) {
		    }   
		);

	});


	// ***** Favourite Friendships (admin) *****	

	if (jQuery("#cpc_favourite_member1").length) {

		if (jQuery("#cpc_favourite_member1").val() == '') {
			jQuery("#cpc_favourite_member1").select2({
			    minimumInputLength: 1,
			    query: function (query) {
					jQuery.post(
					    cpc_friendships_ajax.ajaxurl,
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

		if (jQuery("#cpc_favourite_member2").val() == '') {
			jQuery("#cpc_favourite_member2").select2({
			    minimumInputLength: 1,
			    query: function (query) {
					jQuery.post(
					    cpc_friendships_ajax.ajaxurl,
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

/* -------------------------------------------------------------------- */
	// ***** Friendships (admin) *****	

	if (jQuery("#cpc_member1").length) {

		if (jQuery("#cpc_member1").val() == '') {
			jQuery("#cpc_member1").select2({
			    minimumInputLength: 1,
			    query: function (query) {
					jQuery.post(
					    cpc_friendships_ajax.ajaxurl,
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

		if (jQuery("#cpc_member2").val() == '') {
			jQuery("#cpc_member2").select2({
			    minimumInputLength: 1,
			    query: function (query) {
					jQuery.post(
					    cpc_friendships_ajax.ajaxurl,
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

	// ***** Friendships (user interface) *****	

	// Make (add) friendship request
	jQuery(".cpc_friends_add").on('click', function (event) {

		jQuery("body").addClass("cpc_wait_loading");		    			    	
		jQuery.post(
		    cpc_friendships_ajax.ajaxurl,
		    {
		        action : 'cpc_friends_add',
		        user_id: jQuery(this).attr('rel')
		    },
		    function(response) {
		    	location.reload();
		    }   
		);

	});

	// Accept friendship request
	jQuery(".cpc_pending_friends_accept").on('click', function (event) {

		jQuery("body").addClass("cpc_wait_loading");		    			    	
		jQuery.post(
		    cpc_friendships_ajax.ajaxurl,
		    {
		        action : 'cpc_friends_accept',
		        post_id: jQuery(this).attr('rel')
		    },
		    function(response) {
		    	location.reload();
		    }   
		);

	});

	// Reject friendship request
	jQuery(".cpc_pending_friends_reject").on('click', function (event) {

		jQuery("body").addClass("cpc_wait_loading");		    			    	
		jQuery.post(
		    cpc_friendships_ajax.ajaxurl,
		    {
		        action : 'cpc_friends_reject',
		        post_id: jQuery(this).attr('rel')
		    },
		    function(response) {
		    	location.reload();
		    }   
		);

	});

	// Cancel friendship
	jQuery(".cpc_friends_cancel").on('click', function (event) {

		jQuery("body").addClass("cpc_wait_loading");		    	                	
		jQuery.post(
		    cpc_friendships_ajax.ajaxurl,
		    {
		        action : 'cpc_friends_reject',
		        post_id: jQuery(this).attr('rel')
		    },
		    function(response) {
		    	location.reload();
		    }   
		);

	});

    // Remove all friends
	jQuery("#cpc_remove_all_friends").on('click', function (event) {

        var answer = confirm(jQuery(this).data('sure'));
        if (answer) {

			jQuery("body").addClass("cpc_wait_loading");		    	                	
            jQuery.post(
                cpc_friendships_ajax.ajaxurl,
                {
                    action : 'cpc_remove_all_friends'
                },
                function(response) {
                    location.reload();
                }   
            );

        }

	});
    

})
