( function( $ ) {

fm_select_clear_terms = function( $fm_field, $fm_options, debug ) {

	// Iterate through all the options and remove those that still aren't selected
	$fm_field.find( 'option:not(:selected)' ).remove();

	// Inform chosen the list has been updated
	$fm_field.trigger("liszt:updated");
}

fm_append_options = function( $append_to, $options, selected ) {
	// Iterate through the options provided
	$options.each( function( index, element ) {
		// Set as selected if needed
		if( selected == "selected" ) element.attr("selected", "selected");

		// See if the option already exists
		if( $append_to.find( "option[value=" + $(element).val() + "]" ).length == 0 ) {
			$append_to.append( $(element) );
		}
	} );
}

fm_reset_chosen = function( $fm_text_field, fm_text_field_val ) {
	// Set the text
	$fm_text_field.val( fm_text_field_val );

	// Calculate the width of the text field appropriately
	// chosen.js does not do this for dynamic changes while the dropdown is open
	var w = ( fm_text_field_val.length * 14 ) + 25;
	$fm_text_field.css({
	  'width': w + 'px'
	});

	// Display optgroup labels for matched elements
	// chosen.js does not do this for dynamic changes while the dropdown is open
	$fm_text_field.parents(".chzn-choices").siblings(".chzn-drop").find("li.group-result").hide();
	$fm_text_field.parents(".chzn-choices").siblings(".chzn-drop").find("li.active-result").each( function( index, element ) {
		$(element).prevUntil(".group-result").prev(".group-result").show();
		$(element).prev(".group-result").show();
	} );
}

$( document ).ready( function() {

	// Track changes to the chosen text field linked to the select in order to update options via AJAX
	// Used for taxonomy-based fields where preload is disabled
	$('.fm-wrapper').on( 'keyup', '.fm-item .chzn-choices input,.fm-item .chzn-search input', function( e ) {
		// Do not execute this function for arrow key presses
		if( e.keyCode >= 37 && e.keyCode <= 40 ) return;

		// Get the corresponding Fieldmanager select field to access data attributes needed for the AJAX call
		$fm_select_field = $(this).parents('.chzn-container').siblings('select');
		$fm_text_field = $(this);

		if( $fm_select_field.data("taxonomy") != "" && $fm_select_field.data("taxonomyPreload") == false ) {
			fm_typeahead_term = $(this).val();
			$.post( ajaxurl, { action: 'fm_search_terms', search_term: $fm_text_field.val(), taxonomy: $fm_select_field.data("taxonomy"), fm_search_terms_nonce: fm_select.nonce }, function ( result ) {

				// Clear any non-selected terms before proceeding
				fm_text_field_val = $fm_text_field.val();
				fm_select_clear_terms( $fm_select_field, "", false );
				$fm_select_field.trigger("liszt:updated");
				fm_reset_chosen( $fm_text_field, fm_text_field_val );

				if( result != "" ) {
					$resultObj = $( result );

					// If there are optgroups present, use special processing
					$resultObj.filter("optgroup").each( function( index, element ) {
						// See if the optgroup already exists
						var optgroup_selector = "optgroup[label='" + $(this).attr("label") + "']";
						if( $fm_select_field.find(optgroup_selector).length > 0 ) {
							// The optgroup exists. Append these options to the existing optgroup.
							fm_append_options( $fm_select_field.find(optgroup_selector), $(this).children("option") );
						} else {
							// The optgroup does not exist. Append the whole group.
							$fm_select_field.append( $fm_select_field, $(this) );
						}
					} );

					// Append any options not in an optgroup
					fm_append_options( $fm_select_field, $resultObj.filter("option") );
				}

				// Inform chosen this field has been updated to populate these options in the typeahead dropdown
				$fm_select_field.trigger("liszt:updated");

				// Restore the search term since chosen automatically clears it on the update trigger above
				fm_reset_chosen( $fm_text_field, fm_text_field_val );
			} );
		}
	} );

	// Clear the non-selected options when entering or exiting the typeahead text field
	$('.fm-wrapper').on( 'click', '.fm-item .chzn-choices input', function( e ) {
		var $this_select_field = $(this).parents('.chzn-container').siblings('select');
		if( $this_select_field.data("taxonomy") != "" && $this_select_field.data("taxonomyPreload") == false ) fm_select_clear_terms( $this_select_field, $(this).parents('.chzn-choices'), true );
	} );

} );

} )( jQuery );