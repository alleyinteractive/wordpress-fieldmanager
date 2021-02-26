(function( $ ) {
	fm.datepicker = {
		add_datepicker: function() {
			$( '.fm-datepicker-popup:visible' ).each( function() {
				var $el = $( this );

				if ( ! $el.hasClass( 'fm-has-date-picker' ) ) {
					var opts = $el.data( 'datepicker-opts' );
					$el.datepicker( opts ).addClass( 'fm-has-date-picker' );
				}
			} );
		}
	};

	fmLoadModule( fm.datepicker.add_datepicker );

	$( document ).on(
		'focus',
		'input[class*="fm-datepicker-popup"]:not(.fm-has-date-picker)',
		fm.datepicker.add_datepicker
	);
})( jQuery );
