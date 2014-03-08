( function( $ ) {

fm.richtextarea = {
	add_rte_to_visible_textareas: function() {
		$( '.fm-richtext:visible' ).each( function() {
			if ( !$( this ).hasClass( 'fm-tinymce' ) ) {
				var opts = $( this ).data( 'mce-options' );
				if ( opts ) {
					opts['elements'] = $( this ).attr( 'id' );
					opts['contentEditable'] = true;
					tinyMCE.init( opts );
					$( this ).addClass( 'fm-tinymce' );
				}
			}
		} );
	},
	drag_rte_disable_control: function() {
		$( this ).find( '.fm-tinymce' ).each( function() {
			tinymce.execCommand( 'mceRemoveControl', true, $( this ).attr( 'id' ) );
		});
	},
	drop_rte_enable_control: function() {
		$( this ).find( '.fm-tinymce' ).each( function() {
			tinymce.execCommand( 'mceAddControl', true, $( this ).attr( 'id' ) );
		});
	}
}
$( document ).ready( fm.richtextarea.add_rte_to_visible_textareas );
$( document ).on( 'fm_collapsible_toggle fm_added_element fm_activate_tab fm_displayif_toggle', fm.richtextarea.add_rte_to_visible_textareas );
$( document ).on( 'fm-sortable-drag', fm.richtextarea.drag_rte_disable_control );
$( document ).on( 'fm-sortable-drop', fm.richtextarea.drop_rte_enable_control );
} ) ( jQuery );