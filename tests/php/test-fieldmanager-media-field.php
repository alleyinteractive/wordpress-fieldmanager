<?php

/**
 * Tests the Fieldmanager Media Field
 *
 * @group field
 * @group media
 */
class Test_Fieldmanager_Media_Field extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = TRUE;

		$this->post_id = $this->factory->post->create( array( 'post_title' => rand_str(), 'post_date' => '2009-07-01 00:00:00' ) );
		$this->post = get_post( $this->post_id );
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
}
