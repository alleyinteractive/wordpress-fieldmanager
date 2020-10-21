<?php
/**
 * Class file for Fieldmanager_Markup
 *
 * @package Fieldmanager
 */

/**
 * Static markup field.
 */
class Fieldmanager_Markup extends Fieldmanager_Field {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'markup';

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
		return wp_kses_post( $this->content );
	}
}
