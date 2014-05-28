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
		$(window).off( 'beforeunload.edit-post' );
		form_element.submit();
	}
}

} ) ( jQuery );
