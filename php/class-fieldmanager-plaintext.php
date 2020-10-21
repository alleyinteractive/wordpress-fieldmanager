<?php
/**
 * Class file for Fieldmanager_Plaintext
 *
 * @package Fieldmanager
 */

/**
 * Static plaintext field.
 */
class Fieldmanager_Plaintext extends Fieldmanager_Field {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'plaintext';

	/**
	 * Content to render.
	 *
	 * @var string
	 */
	public $content = '';

	/**
	 * Do not save this field. This class is purely informational.
	 *
	 * @var bool
	 */
	public $skip_save = true;

	/**
	 * Render content.
	 *
	 * @param mixed $value Unused value.
	 * @return string Rendered content.
	 */
	public function form_element( $value = '' ) {
		return esc_html( $this->content );
	}
}
