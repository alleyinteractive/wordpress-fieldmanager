<?php
/**
 * Class file for Fieldmanager_Content
 *
 * @package Fieldmanager
 */

/**
 * Static content field.
 */
class Fieldmanager_Content extends Fieldmanager_Field {

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
	 * Type of content being rendered. plaintext, html, markdown supported.
	 *
	 * @var string
	 */
	public $content_type = 'plaintext';

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
		switch ( $this->content_type ) {
			case 'plaintext':
			default:
				return esc_html( $this->content );

			case 'html':
				return wp_kses_post( $this->content );

			case 'markdown':
				return ( new Fieldmanager_Parsedown() )->text( $this->content );
		}
	}
}
