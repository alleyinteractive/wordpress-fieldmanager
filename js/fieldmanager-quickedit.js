( function( $ ) {
	if ( typeof( inlineEditPost ) == 'null' ) {
		return;
	}

	$( document ).ready( function() {
		var wp_inline_edit = inlineEditPost.edit;

		inlineEditPost.edit = function( id ) {
			wp_inline_edit.apply( this, arguments );

			var post_id = 0;
			
			if ( typeof( id ) == 'object' ) {
				post_id = parseInt( this.getId( id ) );
			}

			if ( post_id > 0 ) {
				$( '.fm-quickedit' ).each( function() {
					var self = this;
					var id = $( this ).attr( 'id' );
					if ( id.substring( 0, 12 ) != 'fm-quickedit' ) {
						return;
					}
					var column_name = id.substring( 13 );
					$.get( ajaxurl, { action: 'fm_quickedit_render', 'column_name': column_name, 'post_id': post_id }, function( resp ) {
						$( self ).replaceWith( resp );
					} );
				} );
			}
		}
	} );

} )( jQuery );