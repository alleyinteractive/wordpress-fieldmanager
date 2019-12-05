/**
 * A wrapper for DOM ready handlers in the global wp object and jQuery,
 * with a shim fallback that mimics the behavior of wp.domReady.
 * Ensures that metaboxes have loaded before initializing functionality.
 * @param {function} callback - The callback function to execute when the DOM is ready.
 */
function fmLoadModule( callback ) {
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
