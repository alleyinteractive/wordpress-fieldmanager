<?php

/**
 * Tests the Fieldmanager RichTextArea Field
 */
class Test_Fieldmanager_RichTextArea_Field extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = TRUE;

		$this->post = array(
			'post_status' => 'publish',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		);

		// insert a post
		$this->post_id = wp_insert_post( $this->post );
		$this->post = get_post( $this->post_id );
	}

	public function test_basic_render() {
		$fm = new Fieldmanager_RichTextArea( array( 'name' => 'test_richtextarea' ) );
		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		$result = preg_match( '/<textarea class="fm-element fm-richtext" name="test_richtextarea"[^>]+data-mce-options="([^"]+)"/', $html, $matches );
		$this->assertEquals( 1, $result );
		$json = html_entity_decode( $matches[1] );
		$options = json_decode( $json, true );
		$this->assertNotEmpty( $options );
		// Verify a random option
		$this->assertEquals( 'en', $options['language'] );
	}

	public function test_add_code_plugin() {
		$fm = new Fieldmanager_RichTextArea( array( 'name' => 'test_richtextarea' ) );
		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertEquals( 0, preg_match( '/external_plugins[^"]+tinymce.code.js/', $html ) );

		$fm = new Fieldmanager_RichTextArea( array( 'name' => 'test_richtextarea', 'add_code_plugin' => true ) );
		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/external_plugins[^"]+tinymce.code.js/', $html );
	}

	public function test_custom_buttons() {
		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'buttons_1' => array( 'bold', 'italic', 'bullist', 'numlist', 'link', 'unlink' ),
			'buttons_2' => array(),
		) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		$result = preg_match( '/<textarea[^>]+data-mce-options="([^"]+)"/', $html, $matches );
		$json = html_entity_decode( $matches[1] );
		$options = json_decode( $json, true );

		$this->assertEquals( 'bold,italic,bullist,numlist,link,unlink', $options['toolbar1'] );
		$this->assertEmpty( $options['toolbar2'] );
		$this->assertEmpty( $options['toolbar3'] );
		$this->assertEmpty( $options['toolbar4'] );
	}

	public function test_basic_save() {
		$test_data = "<h1>Lorem Ipsum</h1>\n<p>Dolor sit <a href='#'>amet</a></p>";
		$fm = new Fieldmanager_RichTextArea( array( 'name' => 'test_richtextarea' ) );

		$fm->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data );
		$saved_data = get_post_meta( $this->post_id, 'test_richtextarea', true );
		$this->assertEquals( $test_data, trim( $saved_data ) );
	}
}