( function( $ ) {
	if (wp.domReady) {
		wp.domReady(function() {
			$(document).trigger('fm_added_element');

			// Multiselects listen to fm-wrapper for the event.
			$('.fm-wrapper').trigger('fm_added_element');
		});
	}
} ) ( jQuery );
