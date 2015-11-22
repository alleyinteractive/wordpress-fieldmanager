/* global document, jQuery, wp, _ */
/**
 * Integrate Fieldmanager and the Customizer.
 *
 * @param {function} $ jQuery
 * @param {function} api wp.customize API.
 * @param {function} _ Underscore
 */
(function( $, api, _ ) {
	'use strict';

	/**
	 * Fires when an .fm-element input triggers a 'change' event.
	 *
	 * @param {Event} e Event object.
	 */
	var onFmElementChange = function( e ) {
		reserializeEachControl();
	};

	/**
	 * Fires when an .fm-element input triggers a 'keyup' event.
	 *
	 * @param {Event} e Event object.
	 */
	var onFmElementKeyup = function( e ) {
		var $target = $( e.target );

		// Ignore [Escape] and [Enter].
		if ( 27 === e.keyCode || 13 === e.keyCode ) {
			return;
		}

		if ( $target.hasClass( 'fm-autocomplete' ) ) {
			// Update an autocomplete setting object when the input's text is deleted.
			if ( '' === $target.val() ) {
				// See fm.autocomplete.enable_autocomplete() for this tree.
				// @todo Risky? Autocomplete hidden fields don't typically get set to value="".
				$target.siblings( 'input[type=hidden]' ).first().val( '' );

			/*
			 * Don't update when typing into the autocomplete input. The hidden
			 * field actually contains the value and is handled onFmElementChange().
			 */
			} else {
				return;
			}
		}

		reserializeEachControl();
	};

	/**
	 * Fires when a Fieldmanager object is dropped while sorting.
	 *
	 * @param {Event} e Event object.
	 * @param {Element} el The sorted element.
	 */
	var onFmSortableDrop = function( e, el ) {
		reserializeEachControl();
	};

	/**
	 * Fires when Fieldmanager adds a new element in a repeatable field.
	 *
	 * @param {Event} e Event object.
	 */
	var onFmAddedElement = function( e ) {
		reserializeEachControl();
	};

	/**
	 * Fires when an item is selected and previewed in a Fieldmanager media field.
	 *
	 * @param {Event} e Event object.
	 * @param {jQuery} $wrapper .media-wrapper jQuery object.
	 * @param {object} attachment Attachment attributes.
	 * @param {object} wp Global WordPress JS API.
	 */
	var onFieldmanagerMediaPreview = function( e, $wrapper, attachment, wp ) {
		reserializeEachControl();
	};

	/**
	 * Fires after clicking the "Remove" link of a Fieldmanager media field.
	 *
	 * @param {Event} e Event object.
	 */
	var onFmMediaRemoveClick = function( e ) {
		reserializeEachControl();
	};

	/**
	 * Fires after clicking the "Remove" link of a Fieldmanager repeatable field.
	 *
	 * @param {Event} e Event object.
	 */
	var onFmjsRemoveClick = function( e ) {
		reserializeEachControl();
	};

	/**
	 * Set the values of all Fieldmanager controls.
	 */
	var reserializeEachControl = function() {
		api.control.each( reserializeControl );
	};

	/**
	 * Set a Fieldmanager control to its form values.
	 *
	 * @param {Object} control Customizer Control object.
	 */
	var reserializeControl = function( control ) {
		if ( 'fieldmanager' !== control.params.type ) {
			return;
		}

		control.setting.set( control.container.find( '.fm-element' ).serialize() );
	};

	/**
	 * Trigger an event when a Customizer section with a Fieldmanager control expands.
	 */
	var bindToSectionsWithFm = function() {
		// @todo Also do this on section add
		api.section.each(function( section ) {
			$.each( section.controls(), function( i, control ) {
				if ( 'fieldmanager' !== control.params.type ) {
					return;
				}

				section.container.bind( 'expanded', function() {
					$( document ).trigger( 'fm_customizer_control_section_expanded' );
				});

				return false;
			});
		});
	};

	/**
	 * Fires when the Customizer is loaded.
	 */
	var ready = function() {
		var $document = $( document );

		/*
		 * We debounce() most keyup events to avoid refreshing the Customizer
		 * preview every single time the user types a letter. But typing into
		 * the autocomplete input does not itself trigger a refresh -- the only
		 * time it should affect the preview is when removing an autocomplete
		 * selection. We allow that to occur normally.
		 */
		$document.on( 'keyup', '.fm-element:not(.fm-autocomplete)', _.debounce( onFmElementKeyup, 500 ) );
		$document.on( 'keyup', '.fm-autocomplete', onFmElementKeyup );

		$document.on( 'change', '.fm-element', onFmElementChange );
		$document.on( 'click', '.fm-media-remove', onFmMediaRemoveClick );
		$document.on( 'click', '.fmjs-remove', onFmjsRemoveClick );
		$document.on( 'fm_sortable_drop', onFmSortableDrop );
		$document.on( 'fieldmanager_media_preview', onFieldmanagerMediaPreview );

		bindToSectionsWithFm();

		/*
		 * Hacky, because it always prompts the user to save. Unlike when we
		 * create individual settings, the field "value" is always going to
		 * change when it's reserialized. It also ensures defaults are applied
		 * when the Customizer loads. But if the user saved those changes, the
		 * defaults would be applied, as opposed to a submenu page, where there
		 * isn't an AYS prompt. Creating a query string on the PHP side might
		 * work, but that's even weirder.
		 */
		reserializeEachControl();
	};

	if ( typeof api !== 'undefined' ) {
		api.bind( 'ready', ready );
	}
})( jQuery, wp.customize, _ );
