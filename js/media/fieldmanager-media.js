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
	var $el = $(this),
		library = {};
	event.preventDefault();

	// If the media frame already exists, reopen it.
	if ( fm_media_frame[ $el.attr('id') ] ) {
		fm_media_frame[ $el.attr('id') ].open();
		return;
	}

	// If mime type has been restricted, make sure the library only shows that
	// type.
	if ( $el.data( 'mime-type' ) && 'all' !== $el.data( 'mime-type' ) ) {
		library.type = $el.data( 'mime-type' );
	}

	// Create the media frame.
	fm_media_frame[ $el.attr('id') ] = wp.media({
		// Set the library attributes.
		library: library,

		// Set the title of the modal.
		title: $el.data('choose'),

		// Customize the submit button.
		button: {
			// Set the text of the button.
			text: $el.data('update'),
		}
	});

	// If mime type has been restricted, make sure the library doesn't autoselect
	// an uploaded file if it's the wrong mime type
	if ( $el.data( 'mime-type' ) && 'all' !== $el.data( 'mime-type' ) ) {
		// This event is only fired when a file is uploaded.
		// @see {wp.media.controller.Library:uploading()}
		fm_media_frame[ $el.attr('id') ].on( 'library:selection:add', function() {
			// This event gets fired for every frame that has ever been created on
			// the current page, which causes errors. We only care about the visible
			// frame. Also, FM can change the ID of buttons, which means some older
			// frames may no longer be valid.
			if ( 'undefined' === typeof fm_media_frame[ $el.attr('id') ] || ! fm_media_frame[ $el.attr('id') ].$el.is(':visible') ) {
				return;
			}

			// Get the Selection object and the currently selected attachment.
			var selection = fm_media_frame[ $el.attr('id') ].state().get('selection'),
				attachment = selection.first();
			// If the mime type is wrong, deselect the file.
			if ( attachment.attributes.type !== $el.data( 'mime-type' ) ) {
				selection.remove(attachment);
			}
		});
	}

	// When an image is selected, run a callback.
	fm_media_frame[ $el.attr('id') ].on( 'select', function() {
		// Grab the selected attachment.
		var attachment = fm_media_frame[ $el.attr('id') ].state().get('selection').first().attributes;
		props = { size: $el.data('preview-size') || 'thumbnail' };
		props = wp.media.string.props( props, attachment );
		props.align = 'none';
		props.link = 'custom';
		props.linkUrl = '#';
		props.caption = '';
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

	// Select the attachment when the frame opens
	fm_media_frame[ $el.attr('id') ].on( 'open', function() {
		// Select the current attachment inside the frame
		var selection = fm_media_frame[ $el.attr('id') ].state().get('selection'),
			id = $el.parent().find('.fm-media-id').val(),
			attachment;

		// If there is a saved attachment, use it
		if ( '' !== id && -1 !== id && typeof wp.media.attachment !== "undefined" ) {
			attachment = wp.media.attachment( id );
			attachment.fetch();
		}

		selection.reset( attachment ? [ attachment ] : [] );
	} );

	fm_media_frame[ $el.attr('id') ].open();
} );

} )( jQuery );
