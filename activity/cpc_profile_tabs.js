/**
 * Profile Tabs AJAX Switching
 * Provides smooth tab navigation without page reload
 */
jQuery(document).ready(function($) {
var cpcLoadedExternalScripts = {};

function cpcAssetHash(content) {
var hash = 0, i, chr;
if (!content) { return '0'; }
for (i = 0; i < content.length; i++) {
chr = content.charCodeAt(i);
hash = ((hash << 5) - hash) + chr;
hash |= 0;
}
return String(hash);
}

function cpcResolvedPromise() {
return $.Deferred().resolve().promise();
}

function cpcLoadExternalScript(src) {
if (!src) { return cpcResolvedPromise(); }
if (cpcLoadedExternalScripts[src]) { return cpcLoadedExternalScripts[src]; }
if ($('script[src="' + src + '"]').length) {
cpcLoadedExternalScripts[src] = cpcResolvedPromise();
return cpcLoadedExternalScripts[src];
}
var loader = $.Deferred();
var script = document.createElement('script');
script.src = src;
script.async = false;
script.onload  = function() { loader.resolve(); };
script.onerror = function() { loader.reject(); };
document.body.appendChild(script);
cpcLoadedExternalScripts[src] = loader.promise();
return cpcLoadedExternalScripts[src];
}

// Inject stylesheet/link nodes into <head> from parsed HTML.
function cpcInjectStyles(stylesHtml) {
if (!stylesHtml || !stylesHtml.trim()) { return cpcResolvedPromise(); }
var parser = new DOMParser();
var doc = parser.parseFromString('<body>' + stylesHtml + '</body>', 'text/html');
var styleEls = doc.body.querySelectorAll('link[rel="stylesheet"], style');
var loaders = [];

Array.prototype.forEach.call(styleEls, function(el) {
if (el.tagName.toLowerCase() === 'link') {
var href = el.getAttribute('href');
if (!href) { return; }
var hrefBase = href.split('?')[0];
var already = false;
$('head link[rel="stylesheet"]').each(function() {
if (($(this).attr('href') || '').split('?')[0] === hrefBase) {
already = true;
return false;
}
});
if (already) { return; }
var linkEl = document.createElement('link');
linkEl.rel = 'stylesheet';
linkEl.href = href;
linkEl.media = el.getAttribute('media') || 'all';
if (el.id) { linkEl.id = el.id; }
var d = $.Deferred();
var done = false;
linkEl.onload = function() {
if (done) { return; }
done = true;
d.resolve();
};
linkEl.onerror = function() {
if (done) { return; }
done = true;
d.resolve();
};
setTimeout(function() {
if (done) { return; }
done = true;
d.resolve();
}, 2500);
loaders.push(d.promise());
document.head.appendChild(linkEl);
return;
}

var cssText = el.textContent || '';
if (!cssText.trim()) { return; }
var styleHash = cpcAssetHash(cssText);
if (document.head.querySelector('style[data-cpc-inline-style="' + styleHash + '"]')) { return; }
var styleNode = document.createElement('style');
styleNode.type = 'text/css';
styleNode.setAttribute('data-cpc-inline-style', styleHash);
styleNode.textContent = cssText;
document.head.appendChild(styleNode);
});

if (!loaders.length) { return cpcResolvedPromise(); }
return $.when.apply($, loaders).promise();
}

// Inject scripts using DOMParser (avoids jQuery stripping inline scripts), returns Promise
function cpcInjectScripts(scriptsHtml) {
var chain = cpcResolvedPromise();
if (!scriptsHtml || !scriptsHtml.trim()) { return chain; }
var parser = new DOMParser();
var doc = parser.parseFromString('<body>' + scriptsHtml + '</body>', 'text/html');
var scriptEls = doc.body.querySelectorAll('script');
Array.prototype.forEach.call(scriptEls, function(el) {
var src = el.getAttribute('src');
var inlineJs = el.textContent || el.innerHTML;
chain = chain.then(function() {
if (src) { return cpcLoadExternalScript(src); }
if (!inlineJs || !inlineJs.trim()) { return cpcResolvedPromise(); }
var h = cpcAssetHash(inlineJs);
if ($('body script[data-cpc-inline="' + h + '"]').length) { return cpcResolvedPromise(); }
var marker = document.createElement('script');
marker.type = 'text/plain';
marker.setAttribute('data-cpc-inline', h);
document.body.appendChild(marker);
try { $.globalEval(inlineJs); } catch(e) { }
return cpcResolvedPromise();
});
});
return chain;
}

// Extract <link>, <style>, <script> from HTML using DOMParser.
function cpcExtractAssetsFromHtml(html) {
if (!html || typeof html !== 'string') { return { html: html || '', styles: '', scripts: '' }; }
var parser = new DOMParser();
var doc = parser.parseFromString('<body>' + html + '</body>', 'text/html');
var body = doc.body;
var stylesArr = [];
var scriptsArr = [];

Array.prototype.slice.call(body.querySelectorAll('link[rel="stylesheet"], style')).forEach(function(node) {
stylesArr.push(node.outerHTML);
node.parentNode.removeChild(node);
});

Array.prototype.slice.call(body.querySelectorAll('script')).forEach(function(node) {
scriptsArr.push(node.outerHTML);
node.parentNode.removeChild(node);
});

return { html: body.innerHTML, styles: stylesArr.join('\n'), scripts: scriptsArr.join('\n') };
}

function cpcRenderHtmlInto($target, html) {
var parser = new DOMParser();
var doc = parser.parseFromString('<body>' + (html || '') + '</body>', 'text/html');
var fragment = document.createDocumentFragment();
while (doc.body.firstChild) {
fragment.appendChild(doc.body.firstChild);
}
$target.empty().append(fragment);
}

function cpcFixJobboardExpertLayout() {
var $avatars = $('.jbp-pro-list .expert-avatar');
if (!$avatars.length) { return; }

$avatars.each(function() {
var $avatar = $(this);
var width = $avatar.outerWidth();
if (width > 0) {
$avatar.css('height', width + 'px');
}
});

$('.jbp-pro-list .expert-avatar img').off('load.cpcJobboard').on('load.cpcJobboard', function() {
var $avatar = $(this).closest('.expert-avatar');
var width = $avatar.outerWidth();
if (width > 0) {
$avatar.css('height', width + 'px');
}
});
}

function cpcInitLoadedTab(tab) {
if (tab === 'activity' && typeof cpc_get_ajax_activity === 'function') {
var pageSize = $('#cpc_page_size').html();
if (!pageSize) { pageSize = 10; }
if ($('#cpc_activity_ajax_div').length) {
$('#cpc_activity_ajax_div').html('');
cpc_get_ajax_activity(0, pageSize, 'replace');
}
$('#cpc_activity_items').show();
$('#cpc_activity_post_div').show();
$('.cpc_activity_settings').show();
$('#cpc_activity_post_button').prop('disabled', false);
}

if (tab === 'jobboard') {
setTimeout(cpcFixJobboardExpertLayout, 10);
setTimeout(cpcFixJobboardExpertLayout, 250);
}

$(document).trigger('cpc_profile_tab_loaded', [tab]);
}

$('body').on('click', '.cpc-profile-tab-link', function(e) {
e.preventDefault();

var $link           = $(this);
var $currentItem    = $link.closest('.cpc-profile-tab-item');
var $tabList        = $link.closest('.cpc-profile-tabs-list');
var tab             = $link.data('tab');
var userId          = $tabList.data('user-id');
var nonce           = $tabList.data('nonce');
var $contentWrapper = $('.cpc-profile-tab-content-wrapper');

if ($currentItem.hasClass('active')) { return false; }

$tabList.find('.cpc-profile-tab-item').removeClass('active');
$currentItem.addClass('active');
$contentWrapper.addClass('loading').css('opacity', '0.5');

if (history.pushState) {
history.pushState({tab: tab}, '', $link.attr('href'));
}

$.ajax({
url:  cpc_activity_ajax.ajaxurl,
type: 'POST',
data: { action: 'cpc_load_profile_tab', tab: tab, user_id: userId, nonce: nonce, atts: {} },
success: function(response) {
var tabHtml = '', stylesHtml = '', scriptsHtml = '';
var parsed  = response;

if (typeof response === 'string') {
try { parsed = JSON.parse(response); } catch(e) { parsed = null; }
}

if (parsed && parsed.success && parsed.data && typeof parsed.data.content !== 'undefined') {
tabHtml     = parsed.data.content  || '';
stylesHtml  = parsed.data.styles   || '';
scriptsHtml = parsed.data.scripts  || '';
} else if (typeof response === 'string' && response.length) {
tabHtml = response;
}

if (!tabHtml) {
$contentWrapper.removeClass('loading').css('opacity', '1');
$contentWrapper.text('Tab-Inhalt konnte nicht geladen werden.');
return;
}

var extracted = cpcExtractAssetsFromHtml(tabHtml);

// 1. Inject styles into <head> before content swap (await stylesheet load)
$.when(cpcInjectStyles(stylesHtml + '\n' + extracted.styles)).always(function() {

// 2. Swap content, then load scripts
$contentWrapper.fadeOut(200, function() {
var $w = $(this);
cpcRenderHtmlInto($w, extracted.html);
if (tab === 'jobboard') {
cpcFixJobboardExpertLayout();
}

$w.removeClass('loading').css('opacity', '1').fadeIn(300, function() {
$.when(
cpcInjectScripts(scriptsHtml + '\n' + extracted.scripts)
).always(function() {
cpcInitLoadedTab(tab);
if (tab === 'jobboard') {
cpcFixJobboardExpertLayout();
}
});
});
});
});
},
error: function(xhr) {
$contentWrapper.removeClass('loading').css('opacity', '1');
$contentWrapper.text(xhr && xhr.responseText ? xhr.responseText : 'Tab-Inhalt konnte nicht geladen werden.');
}
});

return false;
});

$(window).on('popstate', function(e) {
if (e.originalEvent.state && e.originalEvent.state.tab) {
window.location.reload();
}
});
});
