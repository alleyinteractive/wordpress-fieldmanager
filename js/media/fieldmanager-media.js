var fm_media_frame = [];
( function( $ ) {

$( document ).on( 'click', '.fm-media-remove', function(e) {
	e.preventDefault();
	$(this).parents( '.fm-item.fm-media' ).find( '.fm-media-id' ).val( 0 );
	$(this).parents( '.fm-item.fm-media' ).find( '.media-wrapper' ).html( '' );
});

$( document ).on( 'click', '.media-wrapper a', function( event ){
	event.preventDefault();
	$(this).closest('.media-wrapper').siblings('.fm-media-button').click();
} );
$( document ).on( 'click', '.fm-media-button', function( event ) {
	var $el = $(this);
	event.preventDefault();

	// If the media frame already exists, reopen it.
	if ( fm_media_frame[ $el.attr('id') ] ) {
		fm_media_frame[ $el.attr('id') ].open();
		return;
	}

	// Create the media frame.
	fm_media_frame[ $el.attr('id') ] = wp.media({
		// Set the title of the modal.
		title: $el.data('choose'),

		// Customize the submit button.
		button: {
			// Set the text of the button.
			text: $el.data('update'),
		}
	});

	// When an image is selected, run a callback.
	fm_media_frame[ $el.attr('id') ].on( 'select', function() {
		// Grab the selected attachment.
		var attachment = fm_media_frame[ $el.attr('id') ].state().get('selection').first().attributes;
		props = { size: fm_preview_size[ $el.attr('id') ] || 'thumbnail' };
		props = wp.media.string.props( props, attachment );
		props.align = 'none';
		props.link = 'custom';
		props.linkUrl = '#';
		$el.parent().find('.fm-media-id').val( attachment.id );
		if ( attachment.type == 'image' ) {
			props.url = props.src;
			var preview = 'Uploaded file:<br />';
			preview += wp.media.string.image( props );
		} else {
			var preview = 'Uploaded file:&nbsp;';
			preview += wp.media.string.link( props );
		}
		preview += '<br /><a class="fm-media-remove fm-delete" href="#">remove</a>';
		var $wrapper = $el.parent().find( '.media-wrapper' );
		$wrapper.html( preview ).trigger( 'fieldmanager_media_preview', [ $wrapper, attachment, wp ] );
	});

	fm_media_frame[ $el.attr('id') ].open();
} );

} )( jQuery );