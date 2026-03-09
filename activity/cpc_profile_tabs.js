/**
 * Profile Tabs AJAX Switching
 * Provides smooth tab navigation without page reload
 */
jQuery(document).ready(function($) {

	function cpcInjectTabAssets(stylesHtml, scriptsHtml) {
		if (stylesHtml) {
			var $styles = $('<div>').html(stylesHtml).find('link[rel="stylesheet"]');
			$styles.each(function() {
				var href = $(this).attr('href');
				if (href && $('head link[rel="stylesheet"][href="' + href + '"]').length === 0) {
					$('head').append($(this).clone());
				}
			});
		}

		if (scriptsHtml) {
			var $scripts = $('<div>').html(scriptsHtml).find('script[src]');
			$scripts.each(function() {
				var src = $(this).attr('src');
				if (src && $('script[src="' + src + '"]').length === 0) {
					var script = document.createElement('script');
					script.src = src;
					script.async = false;
					document.body.appendChild(script);
				}
			});
		}
	}

	function cpcInitLoadedTab(tab) {
		if (tab === 'activity' && typeof cpc_get_ajax_activity === 'function') {
			var pageSize = $('#cpc_page_size').html();
			if (!pageSize) pageSize = 10;
			if ($('#cpc_activity_ajax_div').length) {
				$('#cpc_activity_ajax_div').html('');
				cpc_get_ajax_activity(0, pageSize, 'replace');
			}
			$('#cpc_activity_items').show();
			$('#cpc_activity_post_div').show();
			$('.cpc_activity_settings').show();
			$('#cpc_activity_post_button').prop('disabled', false);
		}

		$(document).trigger('cpc_profile_tab_loaded', [tab]);
	}
	
	// Profile Tab AJAX Switching
	$('body').on('click', '.cpc-profile-tab-link', function(e) {
		e.preventDefault();
		
		var $link = $(this);
		var $currentItem = $link.closest('.cpc-profile-tab-item');
		var $tabList = $link.closest('.cpc-profile-tabs-list');
		var tab = $link.data('tab');
		var userId = $tabList.data('user-id');
		var nonce = $tabList.data('nonce');
		var $contentWrapper = $('.cpc-profile-tab-content-wrapper');
		
		// Don't reload if already active
		if ($currentItem.hasClass('active')) {
			return false;
		}
		
		// Update active state
		$tabList.find('.cpc-profile-tab-item').removeClass('active');
		$currentItem.addClass('active');
		
		// Show loading state
		$contentWrapper.addClass('loading').css('opacity', '0.5');
		
		// Update URL without reload
		if (history.pushState) {
			var newUrl = $link.attr('href');
			history.pushState({tab: tab}, '', newUrl);
		}
		
		// Load tab content via AJAX
		$.ajax({
			url: cpc_activity_ajax.ajaxurl,
			type: 'POST',
			data: {
				action: 'cpc_load_profile_tab',
				tab: tab,
				user_id: userId,
				nonce: nonce,
				atts: {}
			},
				success: function(response) {
				var tabHtml = '';
				var stylesHtml = '';
				var scriptsHtml = '';
				var parsed = response;

				if (typeof response === 'string') {
					try {
						parsed = JSON.parse(response);
					} catch (err) {
						parsed = null;
					}
				}

				if (parsed && parsed.success && parsed.data && typeof parsed.data.content !== 'undefined') {
					tabHtml = parsed.data.content;
					stylesHtml = parsed.data.styles || '';
					scriptsHtml = parsed.data.scripts || '';
				} else if (typeof response === 'string' && response.length) {
					// Fallback: use raw response when JSON is not parseable
					tabHtml = response;
				}

				if (tabHtml !== '') {
					cpcInjectTabAssets(stylesHtml, scriptsHtml);

					// Fade out, replace content, fade in
					$contentWrapper.fadeOut(200, function() {
						$(this).html(tabHtml);
						$(this).removeClass('loading').css('opacity', '1').fadeIn(300, function() {
							cpcInitLoadedTab(tab);
						});
					});
				} else {
					$contentWrapper.removeClass('loading').css('opacity', '1');
					$contentWrapper.html('<div class="cpc-error">Tab-Inhalt konnte nicht geladen werden.</div>');
				}
			},
			error: function(xhr) {
				$contentWrapper.removeClass('loading').css('opacity', '1');

				if (xhr && xhr.responseText) {
					$contentWrapper.html(xhr.responseText);
				} else {
					$contentWrapper.html('<div class="cpc-error">Tab-Inhalt konnte nicht geladen werden.</div>');
				}
			}
		});
		
		return false;
	});
	
	// Handle browser back/forward buttons
	$(window).on('popstate', function(e) {
		if (e.originalEvent.state && e.originalEvent.state.tab) {
			// Reload page on back/forward for now (could be made smoother)
			window.location.reload();
		}
	});
	
});
