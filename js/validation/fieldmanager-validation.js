/*globals FM_VALIDATION_OPTIONS*/
( function( $ ) {
	'use strict';

	var args = $.extend( {}, FM_VALIDATION_OPTIONS.options, {
		ignore: function( index, form_element ) {
			var $element = $( form_element );
			if (
				$element.closest( '.fm-item' ).is( ':visible' ) // Field wrapper is visible, not behind a display_if
				&&
				$element.is( FM_VALIDATION_OPTIONS.force.join( ', ' ) ) // And the field input exists in force option
			) {
				// Force inclusion of fields added to FM_VALIDATION_OPTIONS.force
				return false;
			} else {
				// Other inputs follow the rules in FM_VALIDATION_OPTIONS.ignore
				return ! $element.is( FM_VALIDATION_OPTIONS.ignore.join( ', ' ) );
			}
		},
		invalidHandler: function( event, validator ) {
			// Certain WordPress forms require additional cleanup to work smoothly with jQuery validation
			var form = $( validator.currentForm ).attr( 'id' );
			switch ( form ) {
				case "post":
					$( "#submitpost .spinner" ).hide();
					$( "#submitpost #publish" ).removeClass( 'button-primary-disabled' );
					$(window).off( 'beforeunload.edit-post' );
					break;
			}
		},
		submitHandler: function( form_element ) {
			$(window).off( 'beforeunload.edit-post' );
			form_element.submit();
		}
	} );

	$( 'form#' + FM_VALIDATION_OPTIONS.form_id ).validate( args );

} )( jQuery );
