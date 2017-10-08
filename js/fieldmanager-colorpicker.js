( function( $ ) {

	fm.colorpicker = {
		init: function() {
			$( '.fm-colorpicker-popup:visible' ).wpColorPicker({
				change: function ( event, ui ) {
					/**
					 * Fires when a Colorpicker element value changes.
					 *
					 * @param {Element} target The Colorpicker input element.
					 * @param {Object}  ui     Object with data from jQuery UI.
					 */
					$( document ).trigger( 'fm_colorpicker_change', [ event.target, ui ] );
				},
				clear: function ( event ) {
					/**
					 * Fires when a Colorpicker element value is cleared.
					 *
					 * Clearing the value doesn't trigger the 'change' callback.
					 * @see https://github.com/Automattic/Iris/issues/57.
					 *
					 * @param {Element} input The Colorpicker input element,
					 *                        inferred via DOM placement.
					 */
					$( document ).trigger( 'fm_colorpicker_clear', [ event.target.parentNode.querySelector( '.fm-colorpicker-popup' ) ] );
				},
			});
		}
	}

	$( document ).ready( fm.colorpicker.init );
	$( document ).on( 'fm_collapsible_toggle fm_added_element fm_displayif_toggle fm_activate_tab', fm.colorpicker.init );

} )( jQuery );
