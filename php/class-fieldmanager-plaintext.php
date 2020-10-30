<?php
/**
 * Class file for Fieldmanager_Plaintext
 *
 * @package Fieldmanager
 */

/**
 * Content field which renders escaped plaintext.
 */
class Fieldmanager_Plaintext extends Fieldmanager_Content {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'plaintext';

	/**
	 * Render content.
	 *
	 * @param mixed $value Unused value.
	 * @return string Rendered content.
	 */
	public function render_content( $content = '' ) {
		return esc_html( $content );
	}
}
