( function( $ ) {

var fm_typeahead_results;

var fm_show_post_type = function( $element, post_type ) {
	if ( $element.data( 'showPostType' ) == 1 ) $element.parent().siblings(".fmjs-remove, .fmjs-clear").after('<div class="fmjs-post-type">' + post_type + '</div>');
}

var fm_show_post_date = function( $element, post_date ) {
	if ( $element.data( 'showPostDate' ) == 1 ) $element.parent().siblings(".fmjs-remove, .fmjs-clear").after('<div class="fmjs-post-date">' + post_date + '</div>');
}

var fm_typeahead_action = function( $element ) {
	$element.typeahead( {
		source: function ( query, process ) {
			// Check if the field already has a post set. If so, disable typeahead to allow for editing of field title.
			if ( $element.data('id') == "" ) {
				// Query for posts matching the current text in the field
				//console.log(fm_post);
				$.post( ajaxurl, { action: 'fm_search_posts', fm_post_search_term: query, fm_post_search_nonce: fm_post.nonce }, function ( result ) {
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
			$element.data( 'postDate', fm_typeahead_results[item]['post_date'] );
			
			// If the clear handle is enabled, show it
			$element.parent().siblings('.fmjs-clear').show();
			
			// Show the selected post type and/or date after the clear/remove handle
			fm_show_post_type($element, fm_typeahead_results[item]['post_type']);
			fm_show_post_date($element, fm_typeahead_results[item]['post_date']);
			
			// Remove the post type and/or date from the title and return the title for the text field
			return fm_typeahead_results[item]['post_title'];
		},
		items:5
	} );
}

$( document ).ready( function () {
	$( '.fm-post-element' ).each( function( index ) {
		// Enable typeahead for each post field
    	fm_typeahead_action( $(this) );
    	
    	// Show the post type, date and/or clear handle (if exists) if the field is not empty and those fields are specified for display
    	if ( $(this).data('postType') != '' ) { 
    		fm_show_post_type( $(this), $(this).data('postType') );
    	}
    	
    	if ( $(this).data('postDate') != '' ) { 
    		fm_show_post_date( $(this), $(this).data('postDate') );
    	}
    	
    	if ( $(this).data('id') != '' ) { 
    		$(this).parent().siblings('.fmjs-clear').show();
    	}
    	
	});
	$( '.fmjs-clear' ).live( 'click', function( e ) {
		// Typeahead is disabled once a post is selected to allow editing of the title for use in frontend display
		// The clear action is enabled when sortable/deletable items aren't used to allow selection of a new post
		e.preventDefault();
		$( this ).siblings('.fmjs-clearable-element').children('.fm-post-element').data( 'id', '' );
		$( this ).siblings('.fmjs-clearable-element').children('.fm-post-element').data( 'postType', '' );
		$( this ).siblings('.fmjs-clearable-element').children('.fm-post-element').data( 'postDate', '' );
		$( this ).siblings('.fmjs-clearable-element').children('.fm-post-element').val( '' );
		$( this ).siblings('.fmjs-post-type').remove();
		$( this ).hide();
	} );
	$( "#post" ).submit( function() {
		$( '.fm-post-element' ).each( function( index ) {
			// Create a JSON object from the data to be parsed and handled on save, if the field is not empty
			if ( $(this).val() != "" ) {
				var json_val = '{"id":"' + $(this).data('id') + '","title":"' + $(this).val() + '","post_type":"' + $(this).data('postType') + '","post_date":"' + $(this).data('postDate') + '"}';
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