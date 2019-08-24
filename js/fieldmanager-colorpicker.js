( function( $ ) {

	fm.colorpicker = {
		init: function() {
			$( '.fm-colorpicker-popup:visible' ).wpColorPicker();
		}
	}

	$( document ).ready( fm.colorpicker.init );
	$( document ).on( 'fm_collapsible_toggle fm_added_element fm_displayif_toggle fm_activate_tab', fm.colorpicker.init );

} )( jQuery );