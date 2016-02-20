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
								var parent = $(this).closest( '.fm-item' );
								if ( parent.find( '.fm-label label' ).length ) {
									parent.find( '.fm-label label' ).attr( 'for', 'fm-edit-dynamic-' + dynamic_seq );
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
 * get data attribute value(s), accounting for jQuery converting string to number automatically
 * @param HTMLDivElement el Wrapper with the data attribute
 * @return string|number|array Single string or number, or array if data attr contains CSV
 */
var get_compare_values = function( el ) {
	var values = $( el ).data( 'display-value' );
	try {
		values = values.split( ',' );
	} catch(e) {
		// if jQuery already converted string to number
		values = [ values ];
	}
	return values;
}

/**
 * @param array values List of possible values to match against
 * @param string match_string Current value of field
 * @param string comparison Type of match, i.e. 'equals', 'not-equals', 'contains'
 * @return bool True if we want to display the field, false if we want to hide the field
 */
var match_value = function( values, match_string, comparison ) {
	comparison = comparison || 'equals';
	for ( var index in values ) {
		// test if current value of the field contains any of the possible values
		if ( 'contains' === comparison && match_string.indexOf( values[index] ) >= 0 ) {
			return true;
		}
		// test equality of field against list of possible values
		else if ( values[index] == match_string ) {
			if ( 'equals' === comparison ) {
				return true;
			} else if ( 'not-equals' === comparison ) {
				return false;
			}
		}
	}
	// not-equals returns true if nothing found, otherwise return false
	return 'not-equals' === comparison;
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
		var src = $( this ).data( 'display-src' );
		var values = get_compare_values( this );
		var compare = $( this ).data( 'display-compare' );
		var trigger = $( this ).siblings( '.fm-' + src + '-wrapper' ).find( '.fm-element' );
		var val = trigger.val();
		if ( trigger.is( ':radio' ) && trigger.filter( ':checked' ).length ) {
			val = trigger.filter( ':checked' ).val();
		}
		trigger.addClass( 'display-trigger' );
		if ( !match_value( values, val, compare ) ) {
			$( this ).hide();
		}
	} );

	// Controls the trigger to show or hide fields
	$( document ).on( 'change', '.display-trigger', function() {
		var val = $( this ).val();
		var name = $( this ).attr('name');
		$( this ).closest( '.fm-wrapper' ).siblings().each( function() {
			if ( $( this ).hasClass( 'display-if' ) ) {
				if( name.match( $( this ).data( 'display-src' ) ) != null ) {
					if ( match_value( get_compare_values( this ), val, $( this ).data( 'display-compare' ) ) ) {
						$( this ).show();
					} else {
						$( this ).hide();
					}
					$( this ).trigger( 'fm_displayif_toggle' );
				}
			}
		} );
	} );

	// get list of unique display-if events
	var displayEvents = [];
	$( '.display-conditional-callback' ).each( function() {
		var eventName = $(this).data( 'display-event' );
		if ( eventName && displayEvents.indexOf( eventName ) === -1 ) {
			displayEvents.push( eventName );
		}
	} );

	// listen for those events and toggle fields according to custom JS like
	// $( document ).trigger( 'my-custom-event', [ true|false ] );
	$( document ).on( displayEvents.join( ' ' ), function( evt, showField ) {
		// default to showing field if missing arg
		if ( typeof showField === 'undefined' ) {
			showField = true;
		}
		// new search for .display-conditional-callback to account for newly added repeatable fields
		$( '.display-conditional-callback[data-display-event="' + evt.type + '"]').toggle( Boolean( showField ) );
	} );

	init_label_macros();
	init_sortable();

	$( document ).on( 'fm_activate_tab', init_sortable );
} );

} )( jQuery );
