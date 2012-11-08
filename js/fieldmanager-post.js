( function( $ ) {

var fm_typeahead_results;

var fm_show_post_type = function( $element, post_type ) {
	if ( $element.data( 'showPostType' ) == 1 ) $element.parent().siblings(".fmjs-remove, .fmjs-clear").after('<div class="fmjs-post-type">' + post_type + '</div>');
}

var fm_typeahead_action = function( $element ) {
	$element.typeahead( {
		source: function ( query, process ) {
			// Check if the field already has a post set. If so, disable typeahead to allow for editing of field title.
			if ( $element.data('id') == "" ) {
				// Query for posts matching the current text in the field
				$.post( ajaxurl, { action: 'fm_search_posts', fm_post_search_term: query }, function ( result ) {
					resultObj = JSON.parse( result );
					if ( $.type( resultObj ) == "object" ) {
						fm_typeahead_results = resultObj;
						process( resultObj.names );
					} else {
						fm_typeahead_results = "";
						process( [] );
					}
				});
			}
		},
		updater: function ( item ) {		
			// Get the post ID and post type and store them in data attributes
			$element.data( 'id', fm_typeahead_results[item]['id'] );
			$element.data( 'postType', fm_typeahead_results[item]['post_type'] );
			
			// If the clear handle is enabled, show it
			$element.parent().siblings('.fmjs-clear').show();
			
			// Show the selected post type after the clear/remove handle
			fm_show_post_type($element, fm_typeahead_results[item]['post_type']);
			
			// Remove the post type from the title and return the title for the text field
			return item.substr( 0, item.lastIndexOf( ' (' ) );
		},
		items:5
	} );
}

$( document ).ready( function () {
	$( '.fm-post-element' ).each( function( index ) {
		// Enable typeahead for each post field
    	fm_typeahead_action( $(this) );
    	
    	// Show the post type and clear handle (if exists) if the field is not empty
    	var post_type = $(this).data('postType');
    	if ( post_type != '' ) { 
    		fm_show_post_type($(this), post_type);
			$(this).parent().siblings('.fmjs-clear').show();
    	}
    	
	});
	$( '.fmjs-clear' ).live( 'click', function( e ) {
		// Typeahead is disabled once a post is selected to allow editing of the title for use in frontend display
		// The clear action is enabled when sortable/deletable items aren't used to allow selection of a new post
		e.preventDefault();
		$( this ).siblings('.fmjs-clearable-element').children('.fm-post-element').data( 'id', '' );
		$( this ).siblings('.fmjs-clearable-element').children('.fm-post-element').data( 'postType', '' );
		$( this ).siblings('.fmjs-clearable-element').children('.fm-post-element').val( '' );
		$( this ).siblings('.fmjs-post-type').remove();
		$( this ).hide();
	} );
	$( "#post" ).submit( function() {
		$( '.fm-post-element' ).each( function( index ) {
			// Create a JSON object from the data to be parsed and handled on save, if the field is not empty
			if ( $(this).val() != "" ) {
				var json_val = '{"id":"' + $(this).data('id') + '","title":"' + $(this).val() + '","post_type":"' + $(this).data('postType') + '"}';
				$(this).val(json_val);
			}
		});
	});
	$( '.fm-wrapper' ).bind('fm_added_element', function( event ) {
  		$post_element = $(event.target).find( '.fm-post-element' );
		if ( $post_element.length != 0 ) fm_typeahead_action( $post_element );
	});
} );

} )( jQuery );