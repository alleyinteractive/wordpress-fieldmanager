/**
 * A wrapper for DOM ready handlers in the global wp object and jQuery,
 * with a shim fallback that mimics the behavior of wp.domReady.
 * Ensures that metaboxes have loaded before initializing functionality.
 * @param {function} callback - The callback function to execute when the DOM is ready.
 */
function fmLoadModule( callback ) {
	/**
	 * Wraps the provided callback to add check for the Gutenberg editor. If this
	 * is called within the Gutenberg editor, then the callback function will trigger
	 * after the metaboxes are initialized.
	 */
	const wrappedCallback = () => {
		if ( document.querySelector( '.block-editor-page' ) ) {
			const unsubscribeListener = wp.data.subscribe( () => {
				/**
				 * `areMetaBoxesInitialized` is called immediately before the
				 * `MetaBoxesArea` component is rendered, which is where the metabox
				 * HTML is moved from the hidden div and into the main form element.
				 *
				 * This means we need to checks for the existence of the markup in the
				 * DOM before we run our callbacks and then unsubscribe our listener.
				 *
				 * @link https://github.com/WordPress/gutenberg/blob/019d0a1b1883a5c3e5c9cdecc60bd5e546b60a1b/packages/edit-post/src/components/meta-boxes/index.js#L38-L45
				 * @link https://github.com/WordPress/gutenberg/blob/d39949a3b9dc8e12d5f5d33b9091f14b93b37c8a/packages/edit-post/src/components/meta-boxes/meta-boxes-area/index.js#L34-L36
				 */
				if (
					wp.data.select( 'core/edit-post' ).areMetaBoxesInitialized()
					&& document.querySelector( '.edit-post-meta-boxes-area__container .metabox-location-normal' )
				) {
					callback();
					unsubscribeListener();
				}
			} );
		} else {
			callback();
		}
	};

	if ( 'object' === typeof wp && 'function' === typeof wp.domReady ) {
		wp.domReady( wrappedCallback );
	} else if ( jQuery ) {
		jQuery( document ).ready( wrappedCallback );
	} else {
		// Shim wp.domReady.
		if (
			document.readyState === 'complete' || // DOMContentLoaded + Images/Styles/etc loaded, so we call directly.
			document.readyState === 'interactive' // DOMContentLoaded fires at this point, so we call directly.
		) {
			wrappedCallback();
			return;
		}

		// DOMContentLoaded has not fired yet, delay callback until then.
		document.addEventListener( 'DOMContentLoaded', wrappedCallback );
	}
}
