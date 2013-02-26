( function( $ ) {

$( document ).on( "click", '.fm-richtext', function() {
	$this = $( this );
	if ( !$this.hasClass( 'fm-tinymce' ) ) {
		var opts = $( this ).data( 'mce-options' );
		if ( opts ) {
			opts['elements'] = $( this ).attr( 'id' );
			tinyMCE.init( opts );
			$this.addClass( 'fm-tinymce' );
		}
	}
} );

} ) ( jQuery );