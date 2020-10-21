<?php
/**
 * Class file for Fieldmanager_Markdown
 *
 * @package Fieldmanager
 */

use Fieldmanager\Libraries\Parsedown;

/**
 * Static markdown field.
 */
class Fieldmanager_Markdown extends Fieldmanager_Content {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'markdown';

	/**
	 * Render content as html from parsed markdown.
	 *
	 * @param mixed $value Unused value.
	 * @return string Rendered content.
	 */
	public function render_content( string $content ): string {
		return ( new Parsedown\Parsedown() )->text( $content );
	}
}
