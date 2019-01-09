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
		$this->post = $this->factory->post->create_and_get(
			array(
				'post_title' => rand_str(),
				'post_date'  => '2009-07-01 00:00:00',
			)
		);
	}

	public function test_basic_render() {
		$fm = new Fieldmanager_Colorpicker( array( 'name' => 'test_colorpicker' ) );

		ob_start();
		$fm->add_meta_box( 'Test Colorpicker', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '#<input class="[^"]*fm-colorpicker-popup[^>]+name="test_colorpicker"#', $html );
	}

	public function test_basic_save() {
		$test_data = '#bada55';
		$fm        = new Fieldmanager_Colorpicker( array( 'name' => 'test_colorpicker' ) );

		$fm->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, $test_data );
		$this->assertEquals( $test_data, get_post_meta( $this->post->ID, 'test_colorpicker', true ) );
	}

	public function test_sanitization() {
		$test_data = 'invalid';
		$fm        = new Fieldmanager_Colorpicker( array( 'name' => 'test_colorpicker' ) );

		$fm->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, $test_data );
		$this->assertEquals( '', get_post_meta( $this->post->ID, 'test_colorpicker', true ) );
	}

	public function default_color_iterations() {
		return array(
			array( array( 'default_color' => '#ff0000' ), '/data-default-color="#ff0000" value=""/' ),
			array( array( 'default_value' => '#abc' ), '/data-default-color="#abc" value="#abc"/' ),
			array(
				array(
					'default_color' => '',
					'default_value' => '#001122',
				),
				'/data-default-color="" value="#001122"/',
			),
			array( array(), '/data-default-color="" value=""/' ),
		);
	}

	/**
	 * @dataProvider default_color_iterations
	 * @param  array  $args Fieldmanager_Colorpicker args
	 * @param  string $regex Test regex
	 */
	public function test_default_color( $args, $regex ) {
		$args['name'] = rand_str();
		$fm           = new Fieldmanager_Colorpicker( $args );
		ob_start();
		$fm->add_meta_box( 'Test Colorpicker', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( $regex, $html );
	}
}
