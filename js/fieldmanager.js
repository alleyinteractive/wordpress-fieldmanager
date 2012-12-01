( function( $ ) {

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
		init_sortable();
	} );
	$( '.fmjs-remove' ).live( 'click', function( e ) {
		e.preventDefault();
		$wrapper = $( this ).parents( '.fm-wrapper' ).first();
		$( this ).parents( '.fm-item' ).first().remove();
		fm_renumber( $wrapper );
	} );
	$( '.fm-collapsible .fm-group-label-wrapper' ).live( 'click', function() {
		$( this ).parents( '.fm-group' ).first().find( '.fm-group-inner' ).toggle();
	} );
	init_sortable();
} );

} )( jQuery );