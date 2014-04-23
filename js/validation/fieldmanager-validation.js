( function( $ ) {

fm_validation = {
	
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
		// The below method will skip running validation again and avoid an infinite loop.
		// It will also work around the fact that many WordPress built in forms have submit buttons called 'submit'
		// which removes the ability to call the Javascript .submit() method for the form.
		$(window).off( 'beforeunload.edit-post' );
		HTMLFormElement.prototype.submit.call( $( form_element )[0] );
	}
}

} ) ( jQuery );