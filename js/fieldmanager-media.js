
jQuery(document).ready(function($){

  var _custom_media = true,
      _orig_send_attachment = wp.media.editor.send.attachment;
  $('.fm-media-button').click(function(e) {
    var send_attachment_bkp = wp.media.editor.send.attachment;
    var button = $(this);
    var id = button.attr('id').replace('_button', '');
    _custom_media = true;
    var input = this;
    wp.media.editor.send.attachment = function(props, attachment){
      if ( _custom_media ) {

        var src;
        if ( $( 'img', html ).length > 0 ) {
          $( input ).parent().find( '.media-wrapper' ).html( html );
          src = $( input ).parent().find( '.media-wrapper img' ).attr( 'src' );
        }
        else {
          $( input ).parent().find( '.media-wrapper' ).html( 'Uploaded file: ' + html );
          src = $( input ).parent().find( '.media-wrapper a' ).attr( 'href' );
        }
        $( input ).parent().find( '.fm-media-id' ).val( src );

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

