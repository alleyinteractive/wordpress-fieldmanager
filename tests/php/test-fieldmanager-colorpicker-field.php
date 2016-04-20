<?php

/**
 * Tests the Fieldmanager Colorpicker Field
 *
 * @group field
 * @group colorpicker
 */
class Test_Fieldmanager_Colorpicker_Field extends WP_UnitTestCase {

	protected $post;

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		// insert a post
		$this->post = $this->factory->post->create_and_get( array( 'post_title' => rand_str(), 'post_date' => '2009-07-01 00:00:00' ) );
	}

	public function test_basic_render() {
		$fm = new Fieldmanager_Colorpicker( array( 'name' => 'test_colorpicker' ) );

		ob_start();
		$fm->add_meta_box( 'Test Colorpicker', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '#<input class="[^"]*fm-colorpicker-popup[^>]+name="test_colorpicker"#', $html );
	}

	public function test_default_color() {
		$fm_1 = new Fieldmanager_Colorpicker( 'test-1', array( 'name' => 'test_colorpicker', 'default_color' => '#ff0000' ) );
		ob_start();
		$fm_1->add_meta_box( 'Test Colorpicker', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/data-default-color="#ff0000"/', $html );

		$fm_2 = new Fieldmanager_Colorpicker( 'test-1', array( 'name' => 'test_colorpicker', 'default_value' => '#ff0000' ) );
		ob_start();
		$fm_2->add_meta_box( 'Test Colorpicker', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/data-default-color="#ff0000"/', $html );

		$fm_3 = new Fieldmanager_Colorpicker( 'test-1', array( 'name' => 'test_colorpicker', 'default_color' => '', 'default_value' => '#ff0000' ) );
		ob_start();
		$fm_3->add_meta_box( 'Test Colorpicker', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/data-default-color/', $html );

		$fm_4 = new Fieldmanager_Colorpicker( 'test-1', array( 'name' => 'test_colorpicker' ) );
		ob_start();
		$fm_4->add_meta_box( 'Test Colorpicker', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/data-default-color/', $html );
	}

	public function test_basic_save() {
		$test_data = '#bada55';
		$fm = new Fieldmanager_Colorpicker( array( 'name' => 'test_colorpicker' ) );

		$fm->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, $test_data );
		$this->assertEquals( $test_data, get_post_meta( $this->post->ID, 'test_colorpicker', true ) );
	}

	public function test_sanitization() {
		$test_data = 'invalid';
		$fm = new Fieldmanager_Colorpicker( array( 'name' => 'test_colorpicker' ) );

		$fm->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, $test_data );
		$this->assertEquals( '', get_post_meta( $this->post->ID, 'test_colorpicker', true ) );
	}
}
