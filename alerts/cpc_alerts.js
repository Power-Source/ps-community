jQuery(document).ready(function() {

	if (jQuery("#cpc_alerts_activity").length) {
		jQuery("#cpc_alerts_activity").select2({
			minimumInputLength: -1,
            minimumResultsForSearch: -1,
			dropdownCssClass: 'cpc_alerts_activity',
		});
	};

    jQuery(".select2, .select2-multiple").on('select2:open', function (e) {
         jQuery('.select2-search input').prop('focus',false);
    });

	jQuery('#cpc_alerts_activity').on("change", function(e) { 

		var alert_id = jQuery(this).val();
		var selected = jQuery(this).find('option:selected');
		var url = selected.data('url');

		if (url == 'make_all_read') {

            jQuery(".cpc_alerts_unread").removeClass("cpc_alerts_unread");
            jQuery("#cpc_alerts_activity option[value='count']").remove();
            jQuery(this).parent().find(".select2-chosen").html('');
                    
			jQuery.post(
			    cpc_alerts.ajaxurl,
			    {
			        action : 'cpc_alerts_make_all_read',
			        alert_id : alert_id,
			        url : url
			    },
			    function(response) {
			    }   
			);

		} else {
            
            if (url == 'delete_all_text') {  

                jQuery(".cpc_alert_item").remove();
                jQuery("#cpc_alerts_activity option[value='count']").remove();
                jQuery(this).parent().find(".select2-chosen").html('');

                jQuery.post(
                    cpc_alerts.ajaxurl,
                    {
                        action : 'cpc_alerts_delete_all',
                    },
                    function(response) {
                    }   
                );                
                
            } else {

                jQuery("body").addClass("cpc_wait_loading");
                
                jQuery.post(
                    cpc_alerts.ajaxurl,
                    {
                        action : 'cpc_alerts_activity_redirect',
                        alert_id : alert_id,
                        url : url,
                        delete_alert : jQuery(this).attr('rel')
                    },
                    function(response) {
                        window.location.assign(response);
                    }   
                );
                
            }
		}

	});	

	// ***** Users for custom post *****	
	if (jQuery("#cpc_alert_recipient").length) {

		if (jQuery("#cpc_alert_recipient").val() == '') {
			jQuery("#cpc_alert_recipient").select2({
			    minimumInputLength: 1,
			    query: function (query) {
					jQuery.post(
					    cpc_alerts.ajaxurl,
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

	// Clear all sent alerts
	jQuery(".cpc_alerts_list_item_link").on('click', function (event) {
        var url = jQuery(this).data('url');
        var alert_id = jQuery(this).data('id');
        
        jQuery.post(
            cpc_alerts.ajaxurl,
            {
                action : 'cpc_alerts_activity_redirect',
                alert_id : alert_id,
                url : url
            },
            function(response) {
                window.location.assign(response);
            }   
        );
        
    });	

    // Mark all as read (for list)
    jQuery("#cpc_make_all_read").on('click', function (event) {

        jQuery(this).parent().remove();
        jQuery('#cpc_alerts_flag_unread').remove();
        jQuery(".cpc_alerts_unread").removeClass("cpc_alerts_unread");
        
        jQuery.post(
            cpc_alerts.ajaxurl,
            {
                action : 'cpc_alerts_make_all_read',
            },
            function(response) {
            }   
        );

	});	
    
    // Delete alert from list
    jQuery(".cpc_alerts_list_item_delete").on('click', function (event) {
        
        jQuery(this).parent().slideUp('fast');
        
        jQuery.post(
            cpc_alerts.ajaxurl,
            {
                action : 'cpc_alerts_list_item_delete',
                alert_id : jQuery(this).attr('rel'),
            },
            function(response) {
            }   
        );

	});	    

    // Show delete icon on hover
    jQuery(".cpc_alerts_list_item").on('mouseenter', function() {
        $(this).children('.cpc_alerts_list_item_delete').show();
    }).on('mouseleave', function() {
        $(this).children('.cpc_alerts_list_item_delete').hide();
    }); 
    
    // Hide delete icon when mouse leaves
    jQuery(".cpc_alerts_list_item").on('mouseleave', function (event) {
        
        jQuery(".cpc_alerts_list_item_delete").hide();

	});	   
    
    // Delete all
    jQuery("#cpc_alerts_delete_all").on('click', function (event) {

        jQuery(".cpc_alerts_list_item").slideUp('fast');
        jQuery(this).hide();
        jQuery("#cpc_mark_all_as_read_div").hide();
        
        jQuery.post(
            cpc_alerts.ajaxurl, {
                action : 'cpc_alerts_delete_all',
            },
            function(response) {
            }   
        );
	});	        
})
