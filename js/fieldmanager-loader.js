/**
 * A wrapper for DOM ready handlers in the global wp object and jQuery,
 * with a shim fallback that mimics the behavior of wp.domReady.
 * Ensures that metaboxes have loaded before initializing functionality.
 * @param {function} callback - The callback function to execute when the DOM is ready.
 */
function fmLoadModule( callback ) {
	// Use a Gutenberg subscription to listen for metaboxes being ready.
	if ( wp && wp.data && wp.data.subscribe ) {
		var unsubscribe = wp.data.subscribe(
			function () {
				// Attempt to get an instance of core/edit-post.
				var editPost = wp.data.select( 'core/edit-post' );
				if ( ! editPost ) {
					return;
				}

				// Check for metaboxes in normal and side locations.
				if ( editPost.isMetaBoxLocationVisible( 'normal' ) ) {
					if ( document.querySelector( '.edit-post-meta-boxes-area.is-normal' ) ) {
						unsubscribe();
						callback();
					}
				} else if ( editPost.isMetaBoxLocationVisible( 'side' ) ) {
					if ( document.querySelector( '.edit-post-meta-boxes-area.is-side' ) ) {
						unsubscribe();
						callback();
					}
				}
			}
		);
	} else if ( jQuery ) {
		jQuery( document ).ready( callback );
	} else {
		// Shim wp.domReady.
		if (
			document.readyState === 'complete' || // DOMContentLoaded + Images/Styles/etc loaded, so we call directly.
			document.readyState === 'interactive' // DOMContentLoaded fires at this point, so we call directly.
		) {
			callback();
		}

		// DOMContentLoaded has not fired yet, delay callback until then.
		document.addEventListener( 'DOMContentLoaded', callback );
	}
}
