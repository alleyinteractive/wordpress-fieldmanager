( function( $ ) {

var fm_select_tab = function( $element ) {
	var t = $element.attr( 'href' );
	$element.parents('.fm-tab').addClass( 'wp-tab-active' ).siblings( 'li' ).removeClass( 'wp-tab-active' );
	$( t ).siblings( 'div' ).hide();
	$( t ).show().trigger( 'fm_activate_tab' );
}

$( document ).ready( function () {
	$( '.fm-tab-bar a' ).live( 'click', function( e ) {
		fm_select_tab( $(this) );
		return false;
	} );
	$( '.fm-tab-bar li' ).live( 'click', function( e ) {
		fm_select_tab( $(this).children('a') );
		return false;
	} );
} );

} )( jQuery );