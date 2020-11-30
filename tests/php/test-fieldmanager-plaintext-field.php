<?php
/**
 * Tests the Fieldmanager Plaintext Field.
 *
 * @group field
 * @group content
 */
class Test_Fieldmanager_Plaintext_Field extends WP_UnitTestCase {

	/**
	 * Plaintext content to test with.
	 *
	 * @var string
	 */
	protected $plaintext = '';

	/**
	 * Markup content to test with.
	 *
	 * @var string
	 */
	protected $markup = '';

	/**
	 * Markdown content to test with.
	 *
	 * @var string
	 */
	protected $markdown = '';

	/**
	 * Setup test.
	 */
	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->plaintext = file_get_contents( __DIR__ . '/data/plaintext.txt' );
		$this->markup    = file_get_contents( __DIR__ . '/data/markup.txt' );
		$this->markdown  = file_get_contents( __DIR__ . '/data/markdown.txt' );
	}

	/**
	 * Test the Fieldmanager_Plaintext field.
	 */
	public function test_plaintext() {
		$plaintext = new Fieldmanager_Plaintext(
			[
				'name'    => 'plaintext_content',
				'content' => $this->plaintext,
			]
		);

		$markup = new Fieldmanager_Plaintext(
			[
				'name'    => 'markup_content',
				'content' => $this->markup,
			]
		);

		$markdown = new Fieldmanager_Plaintext(
			[
				'name'    => 'markdown_content',
				'content' => $this->markdown,
			]
		);

		$this->assertEquals( $plaintext->form_element(), wpautop( esc_html( $this->plaintext ) ), 'Failed escaping plaintext to plaintext.' );
		$this->assertEquals( $markup->form_element(), wpautop( esc_html( $this->markup ) ), 'Failed escaping html to plaintext.' );
		$this->assertEquals( $markdown->form_element(), wpautop( esc_html( $this->markdown ) ), 'Failed escaping markdown to plaintext.' );
	}
}
