<?php

/**
 * Tests the Fieldmanager Media Field
 *
 * @group field
 * @group media
 */
class Test_Fieldmanager_Media_Field extends WP_UnitTestCase {

	protected $post;

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = TRUE;

		// insert a post
		$this->post = $this->factory->post->create_and_get( array( 'post_title' => rand_str(), 'post_date' => '2009-07-01 00:00:00' ) );
	}

	public function test_basic_render() {
		$args = array(
			'name' => 'test_media',
			'button_label' => rand_str(),
			'modal_button_label' => rand_str(),
			'modal_title' => rand_str(),
			'preview_size' => rand_str(),
		);
		$fm = new Fieldmanager_Media( $args );

		ob_start();
		$fm->add_meta_box( 'Test Media', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp(
			sprintf(
				'#<input type="button" class="[^"]*fm-media-button[^>]+value="%s" data-choose="%s" data-update="%s" data-preview-size="%s" data-mime-type="all" */>#',
				$args['button_label'],
				$args['modal_title'],
				$args['modal_button_label'],
				$args['preview_size']
			),
			$html
		);
		$this->assertRegExp( '#<input type="hidden" name="test_media" value="" class="fm-element fm-media-id" />#', $html );
		$this->assertRegExp( '#<div class="media-wrapper"></div>#', $html );
	}

	public function test_basic_save() {
		$test_data = 3335444;
		$fm = new Fieldmanager_Media( array( 'name' => 'test_media' ) );

		$fm->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, $test_data );
		$saved_data = get_post_meta( $this->post->ID, 'test_media', true );
		$this->assertEquals( $test_data, $saved_data );
	}

	public function test_mime_type() {
		$args = array(
			'name'      => 'test_media',
			'mime_type' => 'image',
		);

		$fm = new Fieldmanager_Media( $args );
		ob_start();
		$fm->add_meta_box( 'Test Media', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/<input[^>]+type=[\'"]button[\'"][^>]+data-mime-type=[\'"]image[\'"]/', $html );
	}

	public function test_attributes() {
		$args = array(
			'name'      => 'test_media',
			'attributes' => array(
				'data-test' => rand_str(),
			),
		);

		$fm = new Fieldmanager_Media( $args );
		ob_start();
		$fm->add_meta_box( 'Test Media', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/<input[^>]+type=[\'"]button[\'"][^>]+data-test=[\'"]' . $args['attributes']['data-test'] . '[\'"]/', $html );
	}
}
