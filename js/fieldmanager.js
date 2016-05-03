var fm = {};

( function( $ ) {

var dynamic_seq = 0;

var init_sortable_container = function( el ) {
	if ( !$( el ).hasClass( 'ui-sortable' ) ) {
		$( el ).sortable( {
			handle: '.fmjs-drag',
			items: '> .fm-item',
			placeholder: "sortable-placeholder",
			forcePlaceholderSize: true,
			start: function( e, ui ) {
				$( document ).trigger( 'fm_sortable_drag', el );
			},
			stop: function( e, ui ) {
				var $parent = ui.item.parents( '.fm-wrapper' ).first();
				fm_renumber( $parent );
				$( document ).trigger( 'fm_sortable_drop', el );
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
		var level_pos = $( this ).data( 'fm-array-position' ) - 0;
		var order = 0;
		if ( level_pos > 0 ) {
			$( this ).find( '> .fm-item' ).each( function() {
				if ( $( this ).hasClass( 'fmjs-proto' ) ) {
					return; // continue
				}
				$( this ).find( '.fm-element, .fm-incrementable' ).each( function() {
					var fname = $(this).attr( 'name' );
					if ( fname ) {
						fname = fname.replace( /\]/g, '' );
						parts = fname.split( '[' );
						if ( parts[ level_pos ] != order ) {
							parts[ level_pos ] = order;
							var new_fname = parts[ 0 ] + '[' + parts.slice( 1 ).join( '][' ) + ']';
							$( this ).attr( 'name', new_fname );
							if ( $( this ).attr( 'id' ) && $( this ).attr( 'id' ).match( '-proto' ) && ! new_fname.match( 'proto' ) ) {
								$( this ).attr( 'id', 'fm-edit-dynamic-' + dynamic_seq );
								if ( $( this ).parent().hasClass( 'fm-option' ) ) {
									$( this ).parent().find( 'label' ).attr( 'for', 'fm-edit-dynamic-' + dynamic_seq );
								} else {
									var parent = $( this ).closest( '.fm-item' );
									if ( parent.length && parent.find( '.fm-label label' ).length ) {
										parent.find( '.fm-label label' ).attr( 'for', 'fm-edit-dynamic-' + dynamic_seq );
									}
								}
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

/**
 * Get data attribute display-value(s).
 *
 * Accounts for jQuery converting string to number automatically.
 *
 * @param HTMLDivElement el Wrapper with the data attribute.
 * @return string|number|array Single string or number, or array if data attr contains CSV.
 */
var getCompareValues = function( el ) {
	var values = $( el ).data( 'display-value' );
	try {
		values = values.split( ',' );
	} catch( e ) {
		// If jQuery already converted string to number.
		values = [ values ];
	}
	return values;
};

var match_value = function( values, match_string ) {
	for ( var index in values ) {
		if ( values[index] == match_string ) {
			return true;
		}
	}
	return false;
}

fm_add_another = function( $element ) {
	var el_name = $element.data( 'related-element' )
		, limit = $element.data( 'limit' ) - 0
		, siblings = $element.parent().siblings( '.fm-item' ).not( '.fmjs-proto' )
		, add_more_position = $element.data( 'add-more-position' ) || "bottom";

	if ( limit > 0 && siblings.length >= limit ) {
		return;
	}

	var $new_element = $( '.fmjs-proto.fm-' + el_name, $element.closest( '.fm-wrapper' ) ).first().clone();

	$new_element.removeClass( 'fmjs-proto' );
	$new_element = add_more_position == "bottom" ? $new_element.insertBefore( $element.parent() ) :
						$new_element.insertAfter( $element.parent() )	;
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
	$( document ).on( 'click', '.fm-add-another', function( e ) {
		e.preventDefault();
		fm_add_another( $( this ) );
	} );

	// Handle remove events
	$( document ).on( 'click', '.fmjs-remove', function( e ) {
		e.preventDefault();
		fm_remove( $( this ) );
	} );

	// Handle collapse events
	$( document ).on( 'click', '.fmjs-collapsible-handle', function() {
		$( this ).parents( '.fm-group' ).first().children( '.fm-group-inner' ).slideToggle( 'fast' );
		fm_renumber( $( this ).parents( '.fm-wrapper' ).first() );
		$( this ).parents( '.fm-group' ).first().trigger( 'fm_collapsible_toggle' );
		$( this ).toggleClass( 'closed' );
		if ( $( this ).hasClass( 'closed' ) ) {
			$( this ).attr( 'aria-expanded', 'false' );
		} else {
			$( this ).attr( 'aria-expanded', 'true' );
		}
	} );

	$( '.fm-collapsed > .fm-group:not(.fmjs-proto) > .fm-group-inner' ).hide();

	// Initializes triggers to conditionally hide or show fields
	$( '.display-if' ).each( function() {
		var val;
		var src = $( this ).data( 'display-src' );
		var values = getCompareValues( this );
		// Wrapper divs sometimes receive .fm-element, but don't use them as
		// triggers. Also don't use autocomplete inputs as triggers, because the
		// value is in their sibling hidden fields (which this still matches).
		var trigger = $( this ).siblings( '.fm-' + src + '-wrapper' ).find( '.fm-element' ).not( 'div, .fm-autocomplete' );
		if ( trigger.is( ':checkbox' ) ) {
			if ( trigger.is( ':checked' ) ) {
				// If checked, use the checkbox value.
				val = trigger.val();
			} else {
				// Otherwise, use the hidden sibling field with the "unchecked" value.
				val = trigger.siblings( 'input[type=hidden][name="' + trigger.attr( 'name' ) + '"]' ).val();
			}
		} else if ( trigger.is( ':radio' ) ) {
			if ( trigger.filter( ':checked' ).length ) {
				val = trigger.filter( ':checked' ).val();
			} else {
				// On load, there might not be any selected radio, in which case call the value blank.
				val = '';
			}
		} else {
			val = trigger.val().split( ',' );
		}
		trigger.addClass( 'display-trigger' );
		if ( ! match_value( values, val ) ) {
			$( this ).hide();
		}
	} );

	// Controls the trigger to show or hide fields
	$( document ).on( 'change', '.display-trigger', function() {
		var val;
		var $this = $( this );
		var name = $this.attr( 'name' );
		if ( $this.is( ':checkbox' ) ) {
			if ( $this.is( ':checked' ) ) {
				val = $this.val();
			} else {
				val = $this.siblings( 'input[type=hidden][name="' + name + '"]' ).val();
			}
		} else if ( $this.is( ':radio' ) ) {
			val = $this.filter( ':checked' ).val();
		} else {
			val = $this.val().split( ',' );
		}
		$( this ).closest( '.fm-wrapper' ).siblings().each( function() {
			if ( $( this ).hasClass( 'display-if' ) ) {
				if ( name && name.match( $( this ).data( 'display-src' ) ) != null ) {
					if ( match_value( getCompareValues( this ), val ) ) {
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

	$( document ).on( 'fm_activate_tab', init_sortable );
} );

} )( jQuery );
