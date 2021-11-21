<?php
/**
 * Class file for Fieldmanager_Content_Markdown
 *
 * @package Fieldmanager
 */

use Fieldmanager\Libraries\Parsedown;

/**
 * Content field which renders Markdown using the Parsedown library.
 */
class Fieldmanager_Content_Markdown extends Fieldmanager_Content {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'markdown';

	/**
	 * Render content as HTML from parsed Markdown.
	 *
	 * @param string $content Content.
	 * @return string Rendered content.
	 */
	public function render_content( $content ) {
		return wp_kses_post( Parsedown\Parsedown::instance()->text( $content ) );
	}
}
