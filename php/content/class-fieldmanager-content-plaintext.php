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

	// phpcs:disable Squiz.Commenting.FunctionComment.ParamNameNoMatch -- baseline
	// phpcs:ignore Squiz.Commenting.FunctionComment.MissingParamTag -- baseline
	/**
	 * Render content.
	 *
	 * @param mixed $value Unused value.
	 * @return string Rendered content.
	 */
	// phpcs:enable Squiz.Commenting.FunctionComment.ParamNameNoMatch -- baseline
	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing -- baseline
	public function render_content( $content = '' ) {
		return wpautop( esc_html( $content ) );
	}
}
