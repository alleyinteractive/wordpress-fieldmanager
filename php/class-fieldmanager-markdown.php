<?php
/**
 * Class file for Fieldmanager_Markdown
 *
 * @package Fieldmanager
 */

/**
 * Static markdown field.
 */
class Fieldmanager_Markdown extends Fieldmanager_Field {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'content';

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
		return ( new Fieldmanager_Parsedown() )->text( $this->content );
	}
}
