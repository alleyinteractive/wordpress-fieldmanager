<?php
/**
 * Class file for Fieldmanager_HTML
 *
 * @package Fieldmanager
 */

/**
 * Content field which renders HTML using wp_kses_post().
 */
class Fieldmanager_HTML extends Fieldmanager_Content {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'html';

	/**
	 * Render content using `wp_kses_post()`.
	 *
	 * @param mixed $value Unused value.
	 * @return string Rendered content.
	 */
	public function render_content( $content = '' ) {
		return wp_kses_post( $content );
	}
}
