<?php
/**
 * Class file for Fieldmanager_Content_HTML
 *
 * @package Fieldmanager
 */

/**
 * Content field which renders HTML using wp_kses_post().
 */
class Fieldmanager_Content_HTML extends Fieldmanager_Content {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'html';

	/**
	 * Render content using `wp_kses_post()`.
	 *
	 * @param string $content Content.
	 * @return string Rendered content.
	 */
	public function render_content( $content = '' ) {
		return wp_kses_post( $content );
	}
}
