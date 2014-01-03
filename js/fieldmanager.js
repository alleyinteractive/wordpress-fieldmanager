var fm = {};

( function( $ ) {

var dynamic_seq = 0;

var init_sortable_container = function( el ) {
	if ( !$( el ).hasClass( 'ui-sortable' ) ) {
		$( el ).sortable( {
			handle: '.fmjs-drag',
			items: '> .fm-item',
			stop: function( e, ui ) {
				var $parent = ui.item.parents( '.fm-wrapper' ).first();
				fm_renumber( $parent );
			}
		} );
	}
}

var init_sortable = function() {
	$( '.fmjs-sortable' ).each( function() {
		if ( $( this ).is( ':visible' ) ) {
			init_sortable_container( this );
		} else {
			var sortable = this;
			$( sortable ).parents( '.fm-group' ).first().bind( 'fm_collapsible_toggle', function() {
				init_sortable_container( sortable );
			} );
		}
	} );
}

var init_label_macros = function() {
	// Label macro magic.
	$( '.fm-label-with-macro' ).each( function( label ) {
		$( this ).data( 'label-original', $( this ).html() );
		var src = $( this ).parents( '.fm-group' ).first().find( $( this ).data( 'label-token' ) );
		if ( src.length > 0 ) {
			var $src = $( src[0] );
			if ( typeof $src.val === 'function' ) {
				var $label = $( this );
				var title_macro = function() {
					var token = '';
					if ( $src.prop( 'tagName' ) == 'SELECT' ) {
						var $option = $src.find( 'option:selected' );
						if ( $option.val() ) {
							token = $option.text();
						}
					} else {
						token = $src.val();
					}
					if ( token.length > 0 ) {
						$label.html( $label.data( 'label-format' ).replace( '%s', token ) );
					} else {
						$label.html( $label.data( 'label-original' ) );
					}
				};
				$src.on( 'change keyup', title_macro );
				title_macro();
			}
		}
	} );
}

var fm_renumber = function( $wrappers ) {
	$wrappers.each( function() {
		var level_pos = $( this ).data( 'fm-array-position' );
		var order = 0;
		if ( level_pos > 0 ) {
			$( this ).find( '> .fm-item' ).each( function() {
				if ( $( this ).hasClass( 'fmjs-proto' ) ) return; // continue
				$( this ).find( '.fm-element, .fm-incrementable' ).each( function() {
					var fname = $(this).attr( 'name' );
					if ( fname ) {
						fname = fname.replace( /\]/g, '' );
						parts = fname.split( '[' );
						if ( parts[ level_pos ] != order ) {
							parts[ level_pos ] = order;
							var new_fname = parts[ 0 ] + '[' + parts.slice( 1 ).join( '][' ) + ']';
							$( this ).attr( 'name', new_fname );
							if ( $( this ).attr( 'id' ) && $( this ).attr( 'id' ).match( '-proto' ) ) {
								$( this ).attr( 'id', 'fm-edit-dynamic-' + dynamic_seq );
								dynamic_seq++;
								return; // continue;
							}
						}
					}
					if ( $( this ).hasClass( 'fm-incrementable' ) ) {
						$( this ).attr( 'id', 'fm-edit-dynamic-' + dynamic_seq );
						dynamic_seq++;
					}
				} );
				order++;
			} );
		}
		$( this ).find( '.fm-wrapper' ).each( function() {
			fm_renumber( $( this ) );
		} );
	} );
}

var match_value = function( values, match_string ) {
	for ( var index in values ) {
		if ( values[index] == match_string ) {
			return true;
		}
	}
	return false;
}

fm_add_another = function( $element ) {
	var el_name = $element.attr( 'data-related-element' );
	$new_element = $( '.fmjs-proto.fm-' + el_name, $element.closest( '.fm-wrapper' ) ).first().clone();
	$new_element.removeClass( 'fmjs-proto' );
	$new_element = $new_element.insertBefore( $element.parent() );
	fm_renumber( $element.parents( '.fm-wrapper' ) );
	// Trigger for subclasses to do any post-add event handling for the new element
	$element.parent().siblings().last().trigger( 'fm_added_element' );
	init_label_macros();
	init_sortable();
}

fm_remove = function( $element ) {
	$wrapper = $( this ).parents( '.fm-wrapper' ).first();
	$element.parents( '.fm-item' ).first().remove();
	fm_renumber( $wrapper );
}

$( document ).ready( function () {
	$( '.fm-add-another' ).live( 'click', function( e ) {
		e.preventDefault();
		fm_add_another( $( this ) );
	} );

	// Handle remove events
	$( '.fmjs-remove' ).live( 'click', function( e ) {
		e.preventDefault();
		fm_remove( $( this ) );
	} );

	// Handle collapse events
	$( '.fm-collapsible .fm-group-label-wrapper' ).live( 'click', function() {
		$( this ).parents( '.fm-group' ).first().children( '.fm-group-inner' ).toggle();
		fm_renumber( $( this ).parents( '.fm-wrapper' ).first() );
		$( this ).parents( '.fm-group' ).first().trigger( 'fm_collapsible_toggle' );
	} );

	$( '.fm-collapsed' ).each( function() {
		$( this ).find( '.fm-group-inner' ).hide();
	} );

	// Initializes triggers to conditionally hide or show fields
	$( '.display-if' ).each( function() {
		var src = $( this ).data( 'display-src' );
		var values = $( this ).data( 'display-value' ).split( ',' );
		var trigger = $( this ).siblings( '.fm-' + src + '-wrapper' ).find( '.fm-element' );
		trigger.addClass( 'display-trigger' );
		if ( !match_value( values, trigger.val() ) ) {
			$( this ).hide();
		}
	} );

	// Controls the trigger to show or hide fields
	$( document ).on( 'change', '.display-trigger', function() {
		var val = $( this ).val().split(',');
		var name = $( this ).attr('name');
		$( this ).closest( '.fm-wrapper' ).siblings().each( function() {
			if ( $( this ).hasClass( 'display-if' ) ) {
				if( name.match( $( this ).data( 'display-src' ) ) != null ) {
					if ( match_value( $( this ).data( 'display-value' ).split( ',' ), val ) ) {
						$( this ).show();
					} else {
						$( this ).hide();
					}
					$( this ).trigger( 'fm_displayif_toggle' );
				}
			}
		} );
	} );

	init_label_macros();
	init_sortable();
} );

} )( jQuery );