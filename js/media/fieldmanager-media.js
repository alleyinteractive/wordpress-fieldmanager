( function( $ ) {

$( document ).on( 'click', '.fm-media-remove', function(e) {
	e.preventDefault();
	$(this).parents( '.fm-item.fm-media' ).find( '.fm-media-id' ).val( 0 );
	$(this).parents( '.fm-item.fm-media' ).find( '.media-wrapper' ).html( '' );
});

$( document ).on( 'click', '.fm-media-button', function() {
	var old_send_to_editor = wp.media.editor.send.attachment;
	var input = this;
	wp.media.editor.send.attachment = function( props, attachment ) {
		props.size = 'thumbnail';
		props = wp.media.string.props( props, attachment );
		props.align = null;
		$(input).parent().find('.fm-media-id').val( attachment.id );
		if ( attachment.type == 'image' ) {
			props.url = props.src;
			var preview = 'Uploaded file:<br />';
			preview += wp.media.string.image( props );
		} else {
			var preview = 'Uploaded file:&nbsp;';
			preview += wp.media.string.link( props );
		}
		preview += '<br /><a class="fm-media-remove fm-delete" href="#">remove</a>';
		$( input ).parent().find( '.media-wrapper' ).html( preview );
		wp.media.editor.send.attachment = old_send_to_editor;
	}
	wp.media.editor.open( input );
} );

} )( jQuery );