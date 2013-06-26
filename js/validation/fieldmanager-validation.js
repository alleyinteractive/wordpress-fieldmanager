( function( $ ) {

fm.validation = {
	
	invalidHandler: function( event, validator ) {
		console.log( "invalid" );
		// Certain WordPress forms require additional cleanup to work smoothly with jQuery validation
		var form = $( validator.currentForm ).attr( 'id' );
		switch ( form ) {
			case "post":
				$( "#submitpost .spinner" ).hide();
				$( "#submitpost #publish" ).removeClass( 'button-primary-disabled' );
				break;
		}
	},
	submitHandler: function( form_element ) {
		console.log( "success" );
		form_element.submit();
	}
}

} ) ( jQuery );