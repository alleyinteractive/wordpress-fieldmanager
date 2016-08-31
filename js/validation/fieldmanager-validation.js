/*globals FM_VALIDATION_OPTIONS*/
(function( $ ) {
	'use strict';

	var args = $.extend( {}, FM_VALIDATION_OPTIONS.options, {

		ignore: function( index, formElement ) {

			var $element = $( formElement );

			// Field wrapper is visible, not behind a display_if
			// And the field input exists in force option
			if (
				$element.closest( '.fm-item' ).is( ':visible' ) &&
				$element.is( FM_VALIDATION_OPTIONS.force.join( ', ' ) )
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

				case 'post':

					$( '.spinner', '#submitpost' ).hide();
					$( '#publish', '#submitpost' ).removeClass( 'button-primary-disabled' );
					$( window ).off( 'beforeunload.edit-post' );

					break;
			}
		},
		submitHandler: function( formElement ) {

			$( window ).off( 'beforeunload.edit-post' );
			formElement.submit();
		}
	} );

	if ( 'undefined' !== typeof FM_VALIDATION_OPTIONS.form_id ) {
		$( 'form#' + FM_VALIDATION_OPTIONS.form_id ).validate( args );
	}

})( jQuery );
