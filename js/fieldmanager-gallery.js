var fm_gallery_frame = [];
( function( $ ) {

var sortableCollection = function() {

	$('.fm-gallery-button[data-collection=1]').each( function() {

		var $button, $wrapper, $input;

		$button = $(this);
		$wrapper = $button.siblings( '.gallery-wrapper' );
		$input   = $button.siblings( 'input.fm-gallery-id' );

		$wrapper.sortable({

			items: '> .gallery-item',

			// Update hidden input value after sort.
			stop: function( event, ui ) {

				var val = [];

				$wrapper.children('.gallery-item').each( function() {
					val.push( $(this).data('fieldmanager-item-id') );
				} );

				$input.val( val.join(',') );

				// Remove frame
				// forces regen next time its opened - ensures correct selection.
				delete fm_gallery_frame[ $button.attr('id') ];

			}

		});


	} );

}

$( document ).ready( sortableCollection );
$( document ).on( 'fieldmanager_gallery_preview', sortableCollection );

$( document ).on( 'click', '.fm-gallery-remove, .fm-gallery-empty', function(e) {
	e.preventDefault();

	// Disable empty button
	if ( $(this).hasClass('fm-gallery-empty') ) {;
		$(this).addClass('button-disabled');
	}

	var parent = $(this).parents( '.fm-item.fm-gallery' );
	parent.find( '.fm-gallery-id' ).val('');
	parent.find( '.gallery-wrapper' ).html( '' );
	fm_gallery_frame[ parent.find( '.fm-gallery-button' ).attr('id') ] = false;
});

$( document ).on( 'click', '.gallery-wrapper a', function( event ){
	event.preventDefault();
	$(this).closest('.gallery-wrapper').siblings('.fm-gallery-button').click();
} );

$( document ).on( 'click', '.fm-gallery-button', function( event ) {
	var $el = $(this);
	event.preventDefault();

	// If the gallery frame already exists, reopen it.
	if ( fm_gallery_frame[ $el.attr('id') ] ) {
		fm_gallery_frame[ $el.attr('id') ].open();
		return;
	}
	var selectedImages = [],
		inputVal = $el.parent().find('.fm-gallery-id').val();

	if ( inputVal.length ) {
		selectedImages = inputVal.split(',');
	}

	// Modification to gallery editing workflow
	var galleryFrame = wp.media.view.MediaFrame.Post.extend({

		createStates: function() {
			var options = this.options;

			this.states.add([

				new wp.media.controller.Library({
					id:         'gallery',
					title:      options.title,
					priority:   40,
					toolbar:    'main-gallery',
					filterable: 'uploaded',
					multiple:   'add',
					editable:   false,

					library:  wp.media.query( _.defaults({
						type: 'image'
					}, options.library ) )
				}),

				new wp.media.controller.GalleryEdit({
					library: options.selection,
					editing: options.editing,
					menu:    'gallery'
				}),

				new wp.media.controller.GalleryAdd({
				})

			]);

		}

	});

	var query_args = {
		'type': 'image',
		'post__in': selectedImages,
		'orderby': 'post__in',
		'perPage': -1
	};

	var attachments = wp.media.query( query_args );
	var selection = new wp.media.model.Selection( attachments.models, {
		props:    attachments.props.toJSON(),
		multiple: true
	});

	selection.more().done( function() {
		// Break ties with the query.
		selection.props.set({ query: false });
		selection.unmirror();
		selection.props.unset('orderby');
	});

	var media_args = {
		// Set the title of the modal.
		title: $el.data('choose'),
		selection: selection,

		// Customize the submit button.
		button: {
			// Set the text of the button.
			text: $el.data('update')
		}
	};

	if ( $el.data( 'collection' ) ) {

		if ( selectedImages.length ) {
			media_args.state = 'gallery-edit';
			media_args.editing = true;
		} else {
			media_args.state = 'gallery';
		}
		fm_gallery_frame[ $el.attr('id') ] = new galleryFrame( media_args );
	} else {
		fm_gallery_frame[ $el.attr('id') ] = wp.media( media_args );
	}

	/**
	 * Handle the selection of one or more images
	 */
	var mediaFrameHandleSelect = function( attachments ) {

		// Normal selection doesn't pass us a collection
		if ( ! $el.data('collection') ) {
			attachments = fm_gallery_frame[ $el.attr('id') ].state().get('selection');
		}
		// Update empty gallery button state
		else {
			$el.siblings('.fm-gallery-empty').removeClass('button-disabled');
		}

		var ids = [],
		    galleryItems = [];

		attachments.each( function( attachment ) {

			attributes = attachment.attributes;
			ids.push( attachment.id );

			props = { size: fm_preview_size[ $el.attr('id') ] || 'thumbnail' };
			props = wp.media.string.props( props, attributes );
			props.align = 'none';
			props.link = 'custom';
			props.linkUrl = '#';
			props.caption = '';

			var galleryItem = $( '<div />', {
				'class': 'gallery-item',
				'data-fieldmanager-item-id': attachment.id,
			} );

			galleryItems.push( galleryItem );

			if ( attributes.type == 'image' ) {

				props.url = props.src;

				if ( ! $el.data('collection') ) {
					galleryItem.append( document.createTextNode( 'Uploaded file:' ) );
					galleryItem.append( $( '<br />' ) );
					galleryItem.append( wp.media.string.image( props ) );
				} else {
					galleryItem.append( wp.media.string.image( props ) );
				}

			} else {
				galleryItem.append( document.createTextNode( 'Uploaded file:&nbsp;' ) );
				galleryItem.append( $( '<br />' ) );
				galleryItem.append( wp.media.string.link( props ) );
			}

			if ( ! $el.data('collection') ) {
				galleryItem.append( $( '<br />' ) );
				galleryItem.append( $( '<a/>', { 'class': "fm-gallery-remove fm-delete", 'href': "#", html: 'remove' } ) );
				galleryItem.append( $( '<br />' ) );
			}

		});

		// Store for saving
		$el.parent().find('.fm-gallery-id').val( ids.join(',') );

		var $wrapper = $el.parent().find( '.gallery-wrapper' );
		$wrapper.html('').append( galleryItems ).trigger( 'fieldmanager_gallery_preview', [ $wrapper, attachments, wp ] );
	};

	// When an image is selected, run a callback.
	fm_gallery_frame[ $el.attr('id') ].on( 'select', mediaFrameHandleSelect );
	fm_gallery_frame[ $el.attr('id') ].on( 'update', mediaFrameHandleSelect );

	// When closing modal, deletes frame by assuring always sync with the preview (data collection only)
	fm_gallery_frame[ $el.attr('id') ].on( 'close', function(e) {
		if ( $el.data('collection') ) {
			delete fm_gallery_frame[ $el.attr('id') ];
		}
	} );

	fm_gallery_frame[ $el.attr('id') ].open();

} );

} )( jQuery );
