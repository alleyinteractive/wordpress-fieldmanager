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
	$('.fm-has-submenu').hoverIntent({
		over: function(e){

			var b, h, o, f, m = $(this).find('.fm-submenu'), menutop, wintop, maxtop;

			if ( m.is(':visible') )
				return;

			menutop = $(this).offset().top;
			menuleft = $(this).position().left;
			menuwidth = $(this).width();
			wintop = $(window).scrollTop();
			maxtop = menutop - wintop - 30; // max = make the top of the sub almost touch admin bar

			b = menutop + m.height() + 1; // Bottom offset of the menu
			h = $('#wpwrap').height(); // Height of the entire page
			o = 60 + b - h;
			f = $(window).height() + wintop - 15; // The fold

			if ( f < (b - o) )
				o = b - f;

			if ( o > maxtop )
				o = maxtop;

			if ( o > 1 )
				m.css('margin-top', '-'+o+'px');
			else
				m.css('margin-top', '');

			$(this).find('.fm-submenu').css('left', menuleft);

			$(this).find('.fm-submenu').removeClass('sub-open');
			m.addClass('sub-open');
		},
		out: function(){
			$(this).find('.fm-submenu').removeClass('sub-open').css('margin-top', '');
		},
		timeout: 200,
		sensitivity: 7,
		interval: 90
	});
} );

} )( jQuery );