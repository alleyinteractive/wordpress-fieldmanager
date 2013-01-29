( function( $ ) {

$( document ).on( 'click', '.fm-media-button', function() {
	tb_show( '', 'media-upload.php?TB_iframe=true' );
	var old_send_to_editor = window.send_to_editor;
	var input = this;
	window.send_to_editor = function( html ) {
		var src;
		if ( $( 'img', html ).length > 0 ) {
			$( input ).parent().find( '.media-wrapper' ).html( html );
			var newheight = 150 * ( $( input ).parent().find( '.media-wrapper img' ).height() / $( input ).parent().find( '.media-wrapper img' ).width() );
			$( input ).parent().find( '.media-wrapper img' ).css({'width': 150, 'height': newheight});
			src = $( input ).parent().find( '.media-wrapper img' ).attr( 'src' );
		}
		else {
			$( input ).parent().find( '.media-wrapper' ).html( 'Uploaded file: ' + html );
			src = $( input ).parent().find( '.media-wrapper a' ).attr( 'href' );
		}
		$( input ).parent().find( '.fm-media-id' ).val( src );
		window.send_to_editor = old_send_to_editor;
		tb_remove();
	}
} );

} )( jQuery );