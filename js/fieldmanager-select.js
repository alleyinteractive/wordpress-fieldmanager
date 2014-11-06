( function( $ ) {

fm_select_clear_terms = function( $fm_field, $fm_options, debug ) {

	// Iterate through all the options and remove those that still aren't selected
	$fm_field.find( 'option:not(:selected)' ).remove();

	// Inform chosen the list has been updated
	$fm_field.trigger("liszt:updated");
}

$( document ).ready( function() {

	// Clear the non-selected options when entering or exiting the typeahead text field
	$('.fm-wrapper').on( 'click', '.fm-item .chzn-choices input', function( e ) {
		var $this_select_field = $(this).parents('.chzn-container').siblings('select');
		if( $this_select_field.data("taxonomy") != "" && $this_select_field.data("taxonomyPreload") == false ) fm_select_clear_terms( $this_select_field, $(this).parents('.chzn-choices'), true );
	} );

} );

} )( jQuery );
