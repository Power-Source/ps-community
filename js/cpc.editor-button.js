(function() {
  
    tinymce.create('tinymce.plugins.pushortcodes', {

        init : function(ed, url) {

          var t = this;
          t.editor = ed;

          ed.addButton('cpc_com', {
              title : 'PS Community',
              cmd : 'cpc_com_cmd',
              icon : 'cpc_com',
          });
          ed.addCommand('cpc_com_cmd', function() {

            if (jQuery('#content_cpc_com').length > 0) {
              var offset = jQuery('#content_cpc_com').offset();
              var top = offset.top + 24;
              var left = offset.left;

            } else {

              var offset = jQuery('.mce-i-cpc_com').offset();
              var top = offset.top + 23;
              var left = offset.left - 4;

            }
          
            jQuery('#cpc_admin_shortcodes').css('top', top).css('left', left).show();

          });       

        },

    });

    tinymce.PluginManager.add('cpc_com', tinymce.plugins.pushortcodes);

})();

jQuery(document).on('mouseup', function (e) {
  jQuery('#cpc_admin_shortcodes').hide();
});
