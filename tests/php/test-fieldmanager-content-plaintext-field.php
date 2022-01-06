<?php
/**
 * Tests the Fieldmanager Plaintext Field.
 *
 * @group field
 * @group content
 */
class Test_Fieldmanager_Content_Plaintext_Field extends WP_UnitTestCase {

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
	 * Test the Fieldmanager_Content_Plaintext field.
	 */
	public function test_plaintext() {
		$plaintext = new Fieldmanager_Content_Plaintext(
			[
				'name'    => 'plaintext_content',
				'content' => $this->plaintext,
			]
		);

		$html = new Fieldmanager_Content_Plaintext(
			[
				'name'    => 'html_content',
				'content' => $this->html,
			]
		);

		$markdown = new Fieldmanager_Content_Plaintext(
			[
				'name'    => 'markdown_content',
				'content' => $this->markdown,
			]
		);

		$this->assertEquals( $plaintext->form_element(), wpautop( esc_html( $this->plaintext ) ), 'Failed escaping plaintext to plaintext.' );
		$this->assertEquals( $html->form_element(), wpautop( esc_html( $this->html ) ), 'Failed escaping html to plaintext.' );
		$this->assertEquals( $markdown->form_element(), wpautop( esc_html( $this->markdown ) ), 'Failed escaping markdown to plaintext.' );
	}
}
