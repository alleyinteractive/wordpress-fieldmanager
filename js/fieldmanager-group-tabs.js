var FieldmanagerGroupTabs;
( function( $ ) {

	FieldmanagerGroupTabs = {

		/**
		 * Initialize group tabs
		 */
		init: function() {

			this.bindEvents();

			this.restoreSelectedTabs();

		},

		/**
		 * Bind events
		 */
		bindEvents: function() {

			$( '.fm-tab-bar a' ).on( 'click', $.proxy( function( e ) {
				e.preventDefault();
				this.selectTab( $( e.currentTarget ) );
			}, this ) );
			$( '.fm-tab-bar li' ).on( 'click', $.proxy( function( e ) {
				e.preventDefault();
				this.selectTab( $( e.currentTarget ).children('a') );
			}, this ) );

			if ( this.supportsLocalStorage() ) {
				$('.fm-tab-bar .fm-tab a').on('click', function(){
					var el = $(this);
					var id = el.closest('.fm-tab-bar').attr('id');
					localStorage[ id ] = el.attr('href');
				});
			}

			$('.fm-has-submenu').hoverIntent({
				over: function(e){

					var el = $(this);

					var b, h, o, f, m = el.find('.fm-submenu'), menutop, wintop, maxtop;

					if ( m.is(':visible') )
						return;

					menutop = el.offset().top;
					menuleft = el.position().left;
					menuwidth = el.width();
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

					el.find('.fm-submenu').css('left', menuleft);

					el.find('.fm-submenu').removeClass('sub-open');
					m.addClass('sub-open');
				},
				out: function(){
					$(this).find('.fm-submenu').removeClass('sub-open').css('margin-top', '');
				},
				timeout: 200,
				sensitivity: 7,
				interval: 90
			});

		},

		/**
		 * Select a given tab
		 */
		selectTab: function( $element ) {

			var t = $element.attr( 'href' );
			$element.parents('.fm-tab').addClass( 'wp-tab-active' ).siblings( 'li' ).removeClass( 'wp-tab-active' );
			$( t ).siblings( 'div' ).hide();
			$( t ).show().trigger( 'fm_activate_tab' );

		},

		/**
		 * Restore selected tabs
		 */
		restoreSelectedTabs: function() {

			// requires localStorage
			if ( ! this.supportsLocalStorage() ) {
				return;
			}

			$('.fm-tab-bar').each( function(){
				var el = $(this);
				var id = el.attr('id');
				if ( localStorage[ id ] ) {
					setTimeout( function(){
						el.find('a[href="' + localStorage[ id ] + '"]').trigger('click');
					}, 1 );
				}
			});

		},

		/**
		 * Check whether the browser supports local storage
		 */
		supportsLocalStorage: function() {
			try {
				return 'localStorage' in window && window['localStorage'] !== null;
			} catch (e) {
				return false;
			}
		}

	};

	$(document).ready( function(){

		FieldmanagerGroupTabs.init();

	});

} )( jQuery );