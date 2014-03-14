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
					$hidden.val( ui.item.value );
				};
				ac_params.focus = function( e, ui ) {
					e.preventDefault();
					$el.val( ui.item.label );
				}
				if ( $el.data( 'action' ) ) {
					ac_params.source = function( request, response ) {
						$.post( ajaxurl, {
							action: $el.data( 'action' ),
							fm_context: $el.data( 'context' ),
							fm_subcontext: $el.data( 'subcontext' ),
							fm_autocomplete_search: request.term,
							fm_search_nonce: fm_search.nonce
						}, function( result ) {
							var results = JSON.parse( result );
							if ( $.type( results ) == 'object' ) {
								response( fm.autocomplete.prepare_options( results ) );
							}
							else response( [] );
						} );
					};
				} else if ( $el.data( 'options' ) ) {
					ac_params.source = fm.autocomplete.prepare_options( $el.data( 'options' ) );
				}

				if ( $el.data( 'exact-match' ) ) {
					ac_params.change = function( e, ui ) {
						if ( !ui.item ) {
							$hidden.val( '' );
							$el.val( '' );
						}
					}
				} else {
					$( this ).on( 'keyup', function( e ) {
						if ( e.keyCode == 27 || e.keyCode == 13 ) return;
						$hidden.val( '=' + $el.val() );
					} );
				}

				$( this ).autocomplete( ac_params );
				$( this ).addClass( 'fm-autocomplete-enabled' );
			}
		} );
	}
}

$( document ).ready( fm.autocomplete.enable_autocomplete );
$( document ).on( 'fm_collapsible_toggle fm_added_element fm_displayif_toggle fm_activate_tab', fm.autocomplete.enable_autocomplete );

} ) ( jQuery );