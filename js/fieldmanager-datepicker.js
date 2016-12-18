( function( $ ) {
	fm.datepicker = {
		add_datepicker: function() {
			$( '.fm-datepicker-popup:visible' ).each( function() {
				var $this = $( this );

				if ( ! $this.hasClass( 'fm-has-date-picker' ) ) {
					var opts = $this.data( 'datepicker-opts' );

					if ( ! opts.altField && ! opts.altFormat && $this.siblings( '.fm-datepicker-altfield' ).length ) {
						// altField should reference an element or a selector that won't be renumbered.
						opts.altField  = $this.siblings( '.fm-datepicker-altfield' ).get( 0 );
						opts.altFormat = 'yy-mm-dd';

						// https://bugs.jqueryui.com/ticket/5734.
						opts.onClose = function( date ) {
							if ( '' === date ) {
								opts.altField.value = '';
							}
						};
					}

					$this.datepicker( opts ).addClass( 'fm-has-date-picker' );
				}
			} );
		}
	}

	$( document ).ready( fm.datepicker.add_datepicker );
	$( document ).on( 'fm_collapsible_toggle fm_added_element fm_displayif_toggle fm_activate_tab', fm.datepicker.add_datepicker );
} ) ( jQuery );
