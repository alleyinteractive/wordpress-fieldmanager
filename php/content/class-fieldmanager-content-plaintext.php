<?php
/**
 * Class file for Fieldmanager_Content_Plaintext
 *
 * @package Fieldmanager
 */

/**
 * Content field which renders escaped plaintext.
 */
class Fieldmanager_Content_Plaintext extends Fieldmanager_Content {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'plaintext';

	/**
	 * Render content.
	 *
	 * @param string $content Content.
	 * @return string Rendered content.
	 */
	public function render_content( $content = '' ) {
		return wpautop( esc_html( $content ) );
	}
}
