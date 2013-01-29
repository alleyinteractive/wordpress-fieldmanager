( function( $ ) {

var dynamic_seq = 0;

var init_sortable = function() {
	$( '.fmjs-sortable' ).each( function() {
		if ( !$( this ).hasClass( 'ui-sortable' ) && $( this ).is( ':visible' ) ) {
			$( this ).sortable( {
				handle: '.fmjs-drag',
				items: '> .fm-item',
				stop: function( e, ui ) {
					var $parent = ui.item.parents( '.fm-wrapper' ).first();
					fm_renumber( $parent );
				}
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
					var token = $src.val();
					if ( token.length > 0 ) {
						$label.html( $label.data( 'label-format' ).replace( '%s', token ) );
					}
					else {
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
			$( this ).find( '> .fm-item:visible' ).each( function() {
				$( this ).find( '.fm-element:visible' ).each( function() {
					var fname = $(this).attr( 'name' );
					fname = fname.replace( /\]/g, '' );
					parts = fname.split( '[' );
					if ( parts[ level_pos ] != order ) {
						parts[ level_pos ] = order;
						var new_fname = parts[ 0 ] + '[' + parts.slice( 1 ).join( '][' ) + ']';
						$( this ).attr( 'name', new_fname );
						if ( $( this ).attr( 'id' ).match( '-proto' ) ) {
							$( this ).attr( 'id', 'fm-edit-dynamic-' + dynamic_seq );
							dynamic_seq++;
						}
					}
				} );
				order++;
			} );
		}
	} );
}

$( document ).ready( function () {
	$( '.fm-add-another' ).live( 'click', function( e ) {
		e.preventDefault();
		var el_name = $( this ).attr( 'data-related-element' );
		$new_element = $( '.fmjs-proto.fm-' + el_name ).first().clone();
		$new_element.removeClass( 'fmjs-proto' );
		$new_element = $new_element.insertBefore( $( this ).parent() );
		fm_renumber( $( this ).parents( '.fm-wrapper' ) );
		// Trigger for subclasses to do any post-add event handling for the new element
		$(this).parent().siblings().last().trigger( 'fm_added_element' );
		init_label_macros();
		init_sortable();
	} );

	// Handle remove events
	$( '.fmjs-remove' ).live( 'click', function( e ) {
		e.preventDefault();
		$wrapper = $( this ).parents( '.fm-wrapper' ).first();
		$( this ).parents( '.fm-item' ).first().remove();
		fm_renumber( $wrapper );
	} );

	// Handle collapse events
	$( '.fm-collapsible .fm-group-label-wrapper' ).live( 'click', function() {
		$( this ).parents( '.fm-group' ).first().children( '.fm-group-inner' ).toggle();
	} );

	$( '.fm-collapsed' ).each( function() {
		$( this ).find( '.fm-group-inner' ).hide();
	} );

	init_label_macros();
	init_sortable();
} );

} )( jQuery );