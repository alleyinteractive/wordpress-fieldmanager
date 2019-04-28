( function( $ ) {
	if (wp.domReady) {
		wp.domReady(function() {
			console.log('Triggering FM Collapse Toggle Event for Gutenberg.');
			$(document).trigger('fm_added_element');
		});
	}
} ) ( jQuery );
