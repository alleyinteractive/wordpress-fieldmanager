
jQuery(document).ready(function($){

  var _custom_media = true,
      _orig_send_attachment = wp.media.editor.send.attachment;
  $('.fm-media-add-link').click(function(e) {
    var send_attachment_bkp = wp.media.editor.send.attachment;
    var button = $(this);
    var id = button.attr('id').replace('_button', '');
    _custom_media = true;
    wp.media.editor.send.attachment = function(props, attachment){
      if ( _custom_media ) {
        $("#"+id).val(attachment.url);
        if ( fmIsValidImageUrl( attachment.url ) >= 0 ) {
          $("#"+id+'_thumb').attr('src',attachment.url);
          $("#"+id+'_thumb').attr('style','display:all');          
        } else {
          $("#"+id+'_thumb').attr('src','');
          $("#"+id+'_thumb').attr('style','display:none');
        }
      } else {
        return _orig_send_attachment.apply( this, [props, attachment] );
      };
    }
    wp.media.editor.open(button);
    return false;
  });

  $('.add_media').on('click', function(){
    _custom_media = false;
  });
});

function fmIsValidImageUrl( url ) {
  var arr = [ "jpeg", "jpg", "gif", "png" ];
  var ext = url.substring(url.lastIndexOf(".")+1);
  return jQuery.inArray(ext.toLowerCase(),arr);
}
