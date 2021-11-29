/**
 * A wrapper for DOM ready handlers in the global wp object and jQuery,
 * with a shim fallback that mimics the behavior of wp.domReady.
 * Ensures that metaboxes have loaded before initializing functionality.
 * @param {function} callback - The callback function to execute when the DOM is ready.
 */
 function fmLoadModule( callback ) {
	// For the Gutenberg editor only, load FM module after metabox initialization.
	if (wp.data && wp.blocks && wp.element) {
		var metaboxesInitializedUnsubscribe = wp.data.subscribe(() => {
			if (wp.data.select( 'core/edit-post' ).areMetaBoxesInitialized()) {
				/**
				 * `areMetaBoxesInitialized` is called immediately before the
				 * `MetaBoxesArea` component is rendered, which is where the metabox
				 * HTML is moved from the hidden div and into the main form element.
				 *
				 * This means we need to delay the callback a bit to allow this
				 * component to render such that the jQuery `:visible` selector
				 * can work as expected.
				 */
				setTimeout(() => {
					callback();
				}, 100);

				metaboxesInitializedUnsubscribe();
			}
		});
	} else {
		if ( 'object' === typeof wp && 'function' === typeof wp.domReady ) {
			wp.domReady( callback );
		} else if ( jQuery ) {
			jQuery( document ).ready( callback );
		} else {
			// Shim wp.domReady.
			if (
				document.readyState === 'complete' || // DOMContentLoaded + Images/Styles/etc loaded, so we call directly.
				document.readyState === 'interactive' // DOMContentLoaded fires at this point, so we call directly.
			) {
				callback();
				return;
			}

			// DOMContentLoaded has not fired yet, delay callback until then.
			document.addEventListener( 'DOMContentLoaded', callback );
		}
	}
}
