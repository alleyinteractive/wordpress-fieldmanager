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
		$is_gutenberg_editor = false;

		 // Do we have access to current screen?
		if ( did_action( 'current_screen' ) ) {
			$current_screen = get_current_screen();
			$is_gutenberg_editor = $current_screen instanceof WP_Screen ? $current_screen->is_block_editor : false;
		}

		// Fallback if we don't have access to `current_screen`.
		if ( ! $is_gutenberg_editor ) {
			// Go into globals for post ID on new auto-draft posts.
			$post_id = get_the_ID() ? get_the_ID() : $GLOBALS['post_ID'];
			$is_gutenberg_editor = use_block_editor_for_post( $post_id );
		}

		// If we are working within the context of the block editor, we should ensure required deps are loaded.
		if ( $is_gutenberg_editor ) {
			foreach ( $scripts as $index => $script ) {
				$scripts[ $index ]['deps'][] = 'wp-edit-post';
			}
		}

		return $scripts;
	}
}

new Fieldmanager_Util_Gutenberg();
