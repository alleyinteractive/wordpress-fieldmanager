<?php
/**
 * Class file for Fieldmanager_Content_HTML
 *
 * @package Fieldmanager
 */

/**
 * Content field which renders HTML using wp_kses_post().
 */
class Fieldmanager_Content_HTML extends Fieldmanager_Content {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'html';

	// phpcs:disable Squiz.Commenting.FunctionComment.ParamNameNoMatch -- baseline
	// phpcs:ignore Squiz.Commenting.FunctionComment.MissingParamTag -- baseline
	/**
	 * Render content using `wp_kses_post()`.
	 *
	 * @param mixed $value Unused value.
	 * @return string Rendered content.
	 */
	// phpcs:enable Squiz.Commenting.FunctionComment.ParamNameNoMatch -- baseline
	// phpcs:ignore Squiz.Commenting.FunctionComment.Missing -- baseline
	public function render_content( $content = '' ) {
		return wp_kses_post( $content );
	}
}
