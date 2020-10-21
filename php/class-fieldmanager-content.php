<?php
/**
 * Class file for Fieldmanager_Content
 *
 * @package Fieldmanager
 */

/**
 * Static markdown field.
 */
abstract class Fieldmanager_Content extends Fieldmanager_Field {

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
		return $this->render_content( (string) $this->content );
	}

	/**
	 * Render content.
	 *
	 * @param string $content Content to render.
	 * @return string Rendered content.
	 */
	abstract protected function render_content( string $content ): string;
}
