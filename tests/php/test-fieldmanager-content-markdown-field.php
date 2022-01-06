<?php
/**
 * Tests the Fieldmanager Content Markdown Field.
 *
 * @group field
 * @group content
 */

use Fieldmanager\Libraries\Parsedown;

class Test_Fieldmanager_Content_Markdown_Field extends WP_UnitTestCase {

	/**
	 * Plaintext content to test with.
	 *
	 * @var string
	 */
	protected $plaintext = '';

	/**
	 * HTML content to test with.
	 *
	 * @var string
	 */
	protected $html = '';

	/**
	 * Markdown content to test with.
	 *
	 * @var string
	 */
	protected $markdown = '';

	/**
	 * Setup test.
	 */
	public function set_up() {
		parent::set_up();
		Fieldmanager_Field::$debug = true;

		$this->plaintext = file_get_contents( __DIR__ . '/data/plaintext.txt' );
		$this->html      = file_get_contents( __DIR__ . '/data/html.txt' );
		$this->markdown  = file_get_contents( __DIR__ . '/data/markdown.txt' );
	}

	/**
	 * Test the Fieldmanager_Content_Markdown field.
	 */
	public function test_markdown() {
		$plaintext = new Fieldmanager_Content_Markdown(
			[
				'name'    => 'plaintext_content',
				'content' => $this->plaintext,
			]
		);

		$html = new Fieldmanager_Content_Markdown(
			[
				'name'    => 'html_content',
				'content' => $this->html,
			]
		);

		$markdown = new Fieldmanager_Content_Markdown(
			[
				'name'    => 'markdown_content',
				'content' => $this->markdown,
			]
		);

		$this->assertEquals(
			$plaintext->form_element(),
			( new Parsedown\Parsedown() )->text( $this->plaintext ),
			'Failed escaping plaintext to markdown.'
		);

		$this->assertEquals(
			$html->form_element(),
			( new Parsedown\Parsedown() )->text( $this->html ),
			'Failed escaping html to markdown.'
		);

		$this->assertEquals(
			$markdown->form_element(),
			( new Parsedown\Parsedown() )->text( $this->markdown ),
			'Failed escaping markdown to markdown.'
		);

	}
}
