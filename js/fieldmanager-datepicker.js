( function( $ ) {
	fm.datepicker = {
		add_datepicker: function() {
			$( '.fm-datepicker-popup' ).each( function() {
				if ( !$( this ).hasClass( 'fm-has-date-picker' ) ) {
					var opts = $( this ).data( 'datepicker-opts' );
					$( this ).datepicker( opts ).addClass( 'fm-has-date-picker' );
				}
			} );
		},
		listen: function() {
			// Only listen for changes if Gutenberg is enabled.
			const editor = document.getElementById('editor');
			if (! editor.classList.contains('gutenberg__editor')) {
				return;
			}

			// Configure a MutationObserver to listen for changes.
			const observer = new MutationObserver(function (mutationsList) {
				console.log(mutationsList);
			});
			observer.observe(editor, { childList: true });
		}
	};

	$( document ).ready( fm.datepicker.add_datepicker );
	$( document ).ready( fm.datepicker.listen );
	$( document ).on( 'fm_collapsible_toggle fm_added_element fm_displayif_toggle fm_activate_tab', fm.datepicker.add_datepicker );
} ) ( jQuery );