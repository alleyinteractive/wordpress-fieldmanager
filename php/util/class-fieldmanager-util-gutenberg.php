<?php
/**
 * Class file for Fieldmanager_Util_Gutenberg
 *
 * @package Fieldmanager
 */

/**
 * Gutenberg helpers.
 */
class Fieldmanager_Util_Gutenberg {
	public function __construct() {
		add_filter( 'fm_enqueue_scripts', [ $this, 'add_gutenberg_js_deps' ], 99 );

		// Gutenberg sidebar polyfill.
		fm_add_script( 'fieldmanager-gutenberg-polyfill', 'js/fieldmanager-gutenberg-support.js' );
	}

	/**
	 * Shim to ensure proper load order with Fieldmanager Deps within the context of Gutenberg.
	 *
	 * @param array $scripts
	 * @return array
	 */
	public function add_gutenberg_js_deps( $scripts ) {
		$screen = get_current_screen();

		// If we are working within the context of the block editor, we should ensure required deps are loaded.
		if ( $screen->is_block_editor ) {
			foreach ( $scripts as $index => $script ) {
				$scripts[ $index ]['deps'][] = 'wp-edit-post';
			}
		}

		return $scripts;
	}
}

new Fieldmanager_Util_Gutenberg();
