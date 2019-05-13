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

	/**
	 * Add libraries for gutenberg support.
	 */
	public function __construct() {
		add_filter( 'fm_enqueue_scripts', array( $this, 'add_gutenberg_js_deps' ), 99 );

		// Gutenberg sidebar polyfill.
		fm_add_script( 'fieldmanager-gutenberg-polyfill', 'js/fieldmanager-gutenberg-support.js' );
	}

	/**
	 * Shim for fields loaded inside of Gutenberg editor.
	 *
	 * Adds JS dependency to all fm scripts to ensure proper load order.
	 *
	 * @param array $scripts array of scripts.
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
			$post_id = false !== get_the_ID() ? get_the_ID() : $GLOBALS['post_ID'];
			$is_gutenberg_editor = use_block_editor_for_post( $post_id );
		}

		if ( $is_gutenberg_editor ) {
			foreach ( $scripts as $index => $script ) {
				$scripts[ $index ]['deps'][] = 'wp-edit-post';
			}
		}

		return $scripts;
	}
}

new Fieldmanager_Util_Gutenberg();
