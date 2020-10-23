<?php
/**
 * Class file for Fieldmanager_Markup
 *
 * @package Fieldmanager
 */

/**
 * Static markup field.
 */
class Fieldmanager_Markup extends Fieldmanager_Content {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'markup';

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
