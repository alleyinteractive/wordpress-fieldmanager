( function( $ ) {

fm.richtextarea = {
	add_rte_to_visible_textareas: function() {
		$( '.fm-richtext:visible' ).each( function() {
			if ( !$( this ).hasClass( 'fm-tinymce' ) ) {
				var opts = $( this ).data( 'mce-options' );
				if ( opts ) {
					opts['elements'] = $( this ).attr( 'id' );
					tinyMCE.init( opts );
					$( this ).addClass( 'fm-tinymce' );
				}
			}
		} );
	},
	drag_rte_disable_control: function( e, el ) {
		$( el ).find( '.fm-tinymce' ).each( function() {
			var cmd = 'mceRemoveControl';
			if ( parseInt( tinymce.majorVersion ) >= 4 ) {
				var cmd = 'mceRemoveEditor';
			}
			tinymce.execCommand( cmd, false, $( this ).attr( 'id' ) );
		});
	},
	drop_rte_enable_control: function( e, el ) {
		$( el ).find( '.fm-tinymce' ).each( function() {
			var cmd = 'mceAddControl';
			if ( parseInt( tinymce.majorVersion ) >= 4 ) {
				var cmd = 'mceAddEditor';
			}
			tinymce.execCommand( cmd, false, $( this ).attr( 'id' ) );
		});
	},
	collapse_rte_toggle_control: function( e, el ) {
		var display = el.children( '.fm-group-inner' ).css( 'display' );
		if ( 'none' == display ) {
			$( el ).find( '.fm-tinymce' ).each( function() {
				var cmd = 'mceRemoveControl';
				if ( parseInt( tinymce.majorVersion ) >= 4 ) {
					var cmd = 'mceRemoveEditor';
				}
				tinymce.execCommand( cmd, false, $( this ).attr( 'id' ) );
			});
		} else {
			$( el ).find( '.fm-tinymce' ).each( function() {
				var cmd = 'mceAddControl';
				if ( parseInt( tinymce.majorVersion ) >= 4 ) {
					var cmd = 'mceAddEditor';
				}
				tinymce.execCommand( cmd, false, $( this ).attr( 'id' ) );
				$( this ).tabs('refresh');
			});
		}
	}
}
$( document ).ready( fm.richtextarea.add_rte_to_visible_textareas );
$( document ).on( 'fm_collapsible_toggle fm_added_element fm_activate_tab fm_displayif_toggle', fm.richtextarea.add_rte_to_visible_textareas );
$( document ).on( 'fm_sortable_drag', fm.richtextarea.drag_rte_disable_control );
$( document ).on( 'fm_sortable_drop', fm.richtextarea.drop_rte_enable_control );
$( document ).on( 'fm_collapsible_toggle', fm.richtextarea.collapse_rte_toggle_control );
} ) ( jQuery );