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
	}
}

$( document ).ready( fm.richtextarea.add_rte_to_visible_textareas );
$( document ).on( 'fm_collapsible_toggle', fm.richtextarea.add_rte_to_visible_textareas );
$( document ).on( 'fm_added_element', fm.richtextarea.add_rte_to_visible_textareas );

} ) ( jQuery );