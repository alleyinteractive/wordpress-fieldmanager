/* global document, jQuery, wp, _, fm */
/**
 * Integrate Fieldmanager and the Customizer.
 *
 * @param {function} $ jQuery
 * @param {function} api Customizer API.
 * @param {function} _ Underscore
 * @param {Object} fm Fieldmanager API.
 */
(function( $, api, _, fm ) {
	'use strict';

	fm.customize = {
		/**
		 * jQuery selector targeting all elements to include in a Fieldmanager setting value.
		 *
		 * @type {String}
		 */
		targetSelector: '.fm-element',

		/**
		 * Set the values of all Fieldmanager controls.
		 */
		setEachControl: function () {
			var that = this;

			api.control.each(function( control ) {
				that.setControl( control );
			});
		},

		/**
		 * Set the value of any Fieldmanager control with a given element in its container.
		 *
		 * @param {Element} el Element to look for.
		 */
		setControlsContainingElement: function ( el ) {
			var that = this;

			api.control.each(function( control ) {
				if ( control.container.find( el ).length ) {
					that.setControl( control );
				}
			});
		},

		/**
		 * Set a Fieldmanager setting to its control's form values.
		 *
		 * @param {Object} control Customizer Control object.
		 * @return {Object} The updated Control.
		 */
		setControl: function ( control ) {
			var $element;
			var serialized;
			var value;

			if ( 'fieldmanager' !== control.params.type ) {
				return;
			}

			if ( ! control.setting ) {
				return;
			}

			$element = control.container.find( this.targetSelector );

			if ( $element.serializeJSON ) {
				serialized = $element.serializeJSON();
				value = serialized[ control.id ];
			} else {
				value = $element.serialize();
			}

			return control.setting.set( value );
		},
	};

	/**
	 * Fires when an .fm-element input triggers a 'change' event.
	 *
	 * @param {Event} e Event object.
	 */
	var onFmElementChange = function( e ) {
		fm.customize.setControlsContainingElement( e.target );
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
			/*
			 * Don't update when typing into the autocomplete input. The hidden
			 * field actually contains the value and is handled onFmElementChange().
			 */
			return;
		}

		fm.customize.setControlsContainingElement( e.target );
	};

	/**
	 * Fires when a Fieldmanager object is dropped while sorting.
	 *
	 * @param {Event} e Event object.
	 * @param {Element} el The sorted element.
	 */
	var onFmSortableDrop = function ( e, el ) {
		fm.customize.setControlsContainingElement( el );
	};

	/**
	 * Fires when Fieldmanager adds a new element in a repeatable field.
	 *
	 * @param {Event} e Event object.
	 */
	var onFmAddedElement = function( e ) {
		fm.customize.setControlsContainingElement( e.target );
	};

	/**
	 * Fires when an item is selected and previewed in a Fieldmanager media field.
	 *
	 * @param {Event} e Event object.
	 * @param {jQuery} $wrapper .media-wrapper jQuery object.
	 * @param {Object} attachment Attachment attributes.
	 * @param {Object} wp Global WordPress JS API.
	 */
	var onFieldmanagerMediaPreview = function( e, $wrapper, attachment, wp ) {
		fm.customize.setControlsContainingElement( e.target );
	};

	/**
	 * Fires after TinyMCE initializes in a Fieldmanager richtext field.
	 *
	 * @param {Event} e Event object.
	 * @param {Object} ed TinyMCE instance.
	 */
	var onFmRichtextInit = function( e, ed ) {
		ed.on( 'keyup AddUndo', function () {
			ed.save();
			fm.customize.setControlsContainingElement( document.getElementById( ed.id ) );
		} );
	};

	/**
	 * Fires after a Fieldmanager colorpicker field updates.
	 *
	 * @param {Event} e Event object.
	 * @param {Element} el Colorpicker element.
	 */
	var onFmColorpickerUpdate = function( e, el ) {
		fm.customize.setControlsContainingElement( el );
	};

	/**
	 * Fires after clicking the "Remove" link of a Fieldmanager media field.
	 *
	 * @param {Event} e Event object.
	 */
	 var onFmMediaRemoveClick = function ( e ) {
		// The control no longer contains the element, so set all of them.
		fm.customize.setEachControl();
	 };

	 /**
	  * Fires after clicking the "Remove" link of a Fieldmanager repeatable field.
	  *
	  * @param {Event} e Event object.
	  */
	 var onFmjsRemoveClick = function ( e ) {
		// The control no longer contains the element, so set all of them.
		fm.customize.setEachControl();
	 };

	/**
	 * Fires when a Customizer Section expands.
	 *
	 * @param {Object} section Customizer Section object.
	 */
	var onSectionExpanded = function( section ) {
		/*
		 * Trigger a Fieldmanager event when a Customizer section expands.
		 *
		 * We bind to sections whether or not they have FM controls in case a
		 * control is added dynamically.
		 */
		$( document ).trigger( 'fm_customizer_control_section_expanded' );

		if ( fm.richtextarea ) {
			fm.richtextarea.add_rte_to_visible_textareas();
		}

		if ( fm.colorpicker ) {
			fm.colorpicker.init();
		}

		/*
		 * Reserialize any Fieldmanager controls in this section with null
		 * values. We assume null indicates nothing has been saved to the
		 * database, so we want to make sure default values take effect in the
		 * preview and are submitted on save as they would be in other contexts.
		 */
		_.each( section.controls(), function ( control ) {
			if (
				control.settings.default &&
				null === control.settings.default.get()
			) {
				fm.customize.setControl( control );
			}
		});
	};

	/**
	 * Fires when the Customizer is loaded.
	 */
	var ready = function() {
		var $document = $( document );

		$document.on( 'keyup', '.fm-element', onFmElementKeyup );
		$document.on( 'change', '.fm-element', onFmElementChange );
		$document.on( 'click', '.fm-media-remove', onFmMediaRemoveClick );
		$document.on( 'click', '.fmjs-remove', onFmjsRemoveClick );
		$document.on( 'fm_sortable_drop', onFmSortableDrop );
		$document.on( 'fieldmanager_media_preview', onFieldmanagerMediaPreview );
		$document.on( 'fm_richtext_init', onFmRichtextInit );
		$document.on( 'fm_colorpicker_update', onFmColorpickerUpdate );
	};

	/**
	 * Fires when a Customizer request to save values fails.
	 *
	 * @return {Mixed} response The response from the server.
	 */
	var error = function( response ) {
		if ( ! response.fieldmanager ) {
			return;
		}

		// There isn't yet an official way to signal a save failure, but this mimics the AYS prompt.
		alert( response.fieldmanager );
	};

	/**
	 * Fires when a Customizer Section is added.
	 *
	 * @param {Object} section Customizer Section object.
	 */
	var addSection = function( section ) {
		// It would be more efficient to do this only when adding an FM control to a section.
		section.container.bind( 'expanded', function () {
			onSectionExpanded( section );
		} );
	};

	if ( typeof api !== 'undefined' ) {
		api.bind( 'ready', ready );
		api.bind( 'error', error );
		api.section.bind( 'add', addSection );
	}
})( jQuery, wp.customize, _, fm );
