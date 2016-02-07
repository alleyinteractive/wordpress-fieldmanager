( function( $ ) {

	fm.colorpicker = {
		init: function() {
			$( '.fm-colorpicker-popup:visible' ).wpColorPicker({
				change: function ( e, ui ) {
					// Make sure the input's value attribute also changes.
					$( this ).attr( 'value', ui.color.toString() );
					fm.colorpicker.triggerUpdateEvent( this );
				},
				clear: function () {
					// Make sure the input's value attribute also changes.
					$( this ).attr( 'value', '' );
					fm.colorpicker.triggerUpdateEvent( this );
				},
			});
		},
		triggerUpdateEvent: function ( el ) {
			/**
			 * Trigger a common event for a value 'change' or 'clear'.
			 *
			 * @var {Element} Colorpicker element.
			 */
			$( document ).trigger( 'fm_colorpicker_update', el );
		}
	};

	$( document ).ready( fm.colorpicker.init );
	$( document ).on( 'fm_collapsible_toggle fm_added_element fm_displayif_toggle fm_activate_tab', fm.colorpicker.init );

} )( jQuery );