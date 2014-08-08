( function( $ ) {

var fm_select_tab = function( $element ) {
	var t = $element.attr( 'href' );
	$element.parents('.fm-tab').addClass( 'wp-tab-active' ).siblings( 'li' ).removeClass( 'wp-tab-active' );
	$( t ).siblings( 'div' ).hide();
	$( t ).show().trigger( 'fm_activate_tab' );
}

$( document ).ready( function () {
	$( '.fm-tab-bar a' ).on( 'click', function( e ) {
		e.preventDefault();
		fm_select_tab( $(this) );
	} );
	$( '.fm-tab-bar li' ).on( 'click', function( e ) {
		e.preventDefault();
		fm_select_tab( $(this).children('a') );
	} );
} );

} )( jQuery );