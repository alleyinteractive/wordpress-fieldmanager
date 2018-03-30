( function( $ ) {

	$( document ).ready( function() {
		if ( typeof( inlineEditPost ) == 'undefined' ) {
			return;
		}

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
					$.get( ajaxurl, { action: 'fm_quickedit_render', 'column_name': column_name, 'post_id': post_id, 'post_type': $( self ).data( 'fm-post-type' ) }, function( resp ) {
						$( self ).replaceWith( resp );
					} );
				} );
			}
		};

		var wp_inline_bulk_edit = inlineEditPost.setBulk;
		inlineEditPost.setBulk = function() {
			wp_inline_bulk_edit.apply( this );

			$( '.fm-quickedit' ).each( function() {
				var self = this;
				var id = $( this ).attr( 'id' );
				if ( id.substring( 0, 12 ) != 'fm-quickedit' ) {
					return;
				}
				var column_name = id.substring( 13 );
				$.get( ajaxurl, { action: 'fm_quickedit_render', 'column_name': column_name, 'post_id': 'bulk_edit', 'post_type': $( self ).data( 'fm-post-type' ) }, function( resp ) {
					var temp = $( resp );
					var dest = $( 'select', temp );

					if ( dest ) {
						var label = '&mdash; ' + window.fm_quickedit_opts.i18n.no_change + ' &mdash;';
						var noChange = $('<option />').val( id ).html( label ).attr( 'selected', 'selected' );
						dest.prepend(noChange);
					}

					$( self ).replaceWith( temp );
				} );
			} );
		};
	} );

} )( jQuery );
