( function( $ ) {

fm.datepicker = {
	add_datepicker: function() {
		$( '.fm-datepicker-popup:visible' ).each( function() {
			if ( !$( this ).hasClass( 'fm-has-date-picker' ) ) {
				var opts = $( this ).data( 'datepicker-opts' );
				$( this ).datepicker( opts ).addClass( 'fm-has-date-picker' );
			}
		} );
	}
}

$( document ).ready( fm.datepicker.add_datepicker );
$( document ).on( 'fm_collapsible_toggle', fm.datepicker.add_datepicker );
$( document ).on( 'fm_added_element', fm.datepicker.add_datepicker );

} ) ( jQuery );