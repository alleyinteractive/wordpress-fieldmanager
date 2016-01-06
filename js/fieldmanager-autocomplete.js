( function( $ ) {

fm.autocomplete = {

	prepare_options: function( raw_opts ) {
		var opts = [];
		for ( var k in raw_opts ) opts.push( { label: raw_opts[k], value: k } );
		return opts;
	},

	enable_autocomplete: function() {
		$( 'input.fm-autocomplete:visible' ).each( function() {
			if ( !$( this ).hasClass( 'fm-autocomplete-enabled' ) ) {
				var ac_params = {};
				var $el = $( this );
				var $hidden = $el.siblings( 'input[type=hidden]' ).first();
				ac_params.select = function( e, ui ) {
					e.preventDefault();
					$el.val( ui.item.label );
					$hidden.val( ui.item.value ).trigger( 'change' );
				};
				ac_params.focus = function( e, ui ) {
					e.preventDefault();
					$el.val( ui.item.label );
				}
				if ( $el.data( 'action' ) ) {
					ac_params.source = function( request, response ) {
						// Check for custom args
						var custom_args_js_event = $el.data( 'customArgsJsEvent' );
						var custom_data = '';
						if ( 'undefined' !== typeof custom_args_js_event && null !== custom_args_js_event ) {
							var custom_result = $el.triggerHandler( custom_args_js_event );
							if ( 'undefined' !== typeof custom_result && null !== custom_result ) {
								custom_data = custom_result;
							}
						}
						
						$.post( ajaxurl, {
							action: $el.data( 'action' ),
							fm_context: $el.data( 'context' ),
							fm_subcontext: $el.data( 'subcontext' ),
							fm_autocomplete_search: request.term,
							fm_search_nonce: fm_search.nonce,
							fm_custom_args: custom_data
						}, function( result ) {
							response( result );
						} );
					};
				} else if ( $el.data( 'options' ) ) {
					ac_params.source = fm.autocomplete.prepare_options( $el.data( 'options' ) );
				}

				// data-exact-match is a minimized attribute (see Fieldmanager_Field::get_element_attributes)
				if ( typeof $el.data( 'exact-match' ) !== 'undefined' ) {
					ac_params.change = function( e, ui ) {
						if ( !ui.item ) {
							$hidden.val( '' );
							$el.val( '' );
						}
					}
				} else {
					$( this ).on( 'keyup', function( e ) {
						if ( e.keyCode == 27 || e.keyCode == 13 ) {
							return;
						}

						$hidden.val( '=' + $el.val() );
					} );
				}

				// Set autocomplete highligth
				if ( typeof $el.data( 'autocomplete-highlight' ) !== 'undefined' ) {
					ac_params.highlight = true;
				}
				$( this ).autocomplete( ac_params );
				$( this ).addClass( 'fm-autocomplete-enabled' );
			}
		} );
	}
}

$( document ).ready( function() {

	// Highlighted autocomplete instance
	$.widget( "fm.autocomplete", $.ui.autocomplete, {

		// Default options
		options: {
			// Enable highlighting
			highlight: false,
			// Which class get's applied to the matched text
			highlightClass: "fm-autocomplete-highlight"
		},

		_renderItem: function( ul, item ) {

			// Wrap the matched text with a span
			if ( this.options.highlight === true ) {
				return $( "<li>" )
					.append( item.label.replace( new RegExp( "(" + this.term + ")", "gi" ), "<span class='" + this.options.highlightClass + "'>$1</span>" ) )
					.appendTo( ul );
			}

			// Return parent method (no highlight)
			return this._super(ul, item);
		}
	});
});

$( document ).ready( fm.autocomplete.enable_autocomplete );
$( document ).on( 'fm_collapsible_toggle fm_added_element fm_displayif_toggle fm_activate_tab', fm.autocomplete.enable_autocomplete );

} ) ( jQuery );
