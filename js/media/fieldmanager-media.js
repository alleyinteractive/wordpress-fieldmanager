
jQuery(document).ready(function($){

  var _custom_media = true,
      _orig_send_attachment = wp.media.editor.send.attachment;
  $('.fm-media-button').click(function(e) {
    var send_attachment_bkp = wp.media.editor.send.attachment;
    var button = $(this);
    var id = button.attr('id').replace('_button', '');
    _custom_media = true;
    wp.media.editor.send.attachment = function(props, attachment){
      if ( _custom_media ) {
        $("#"+id).val(attachment.id);
        var img = $("#"+id+'_thumb').find('img');
        var len = img.length;
        if ( len > 0 ) {
          if ( fmIsValidImageUrl( attachment.url ) >= 0 ) {
            img.attr('src',attachment.url);
            img.attr('style','display:all');          
          } else {
            //Hide audio and video thumbs
            img.attr('src','');
            img.attr('style','display:none');
          }
        } else {
          //No Image yet
          if ( fmIsValidImageUrl( attachment.url ) >= 0 ) {
            $("#"+id+'_thumb').prepend("<img src='"+attachment.url+"'>");
          }
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

