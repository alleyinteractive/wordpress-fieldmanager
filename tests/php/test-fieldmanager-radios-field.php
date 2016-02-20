<?php

/**
 * Tests the Fieldmanager Radio Field
 *
 * @group field
 * @group radio
 */
class Test_Fieldmanager_Radios_Field extends WP_UnitTestCase {
	public $post_id;
	public $post;
	public $custom_datasource;

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->post_id = $this->factory->post->create( array( 'post_title' => rand_str(), 'post_date' => '2009-07-01 00:00:00' ) );
		$this->post = get_post( $this->post_id );

		$this->custom_datasource = new Fieldmanager_Datasource( array(
			'options' => array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' )
		) );
	}

	/**
	 * Helper which returns the post meta box HTML for a given field;
	 *
	 * @param  object $field     Some Fieldmanager_Field object.
	 * @param  array  $test_data Data to save (and use when rendering)
	 * @return string            Rendered HTML
	 */
	private function _get_html_for( $field, $test_data = null ) {
		ob_start();
		$context = $field->add_meta_box( 'test meta box', $this->post );
		if ( $test_data ) {
			$context->save_to_post_meta( $this->post_id, $test_data );
		}
		$context->render_meta_box( $this->post );
		return ob_get_clean();
	}

	public function test_repeatable_save() {
		$fm = new Fieldmanager_Radios( array(
			'name' => 'base_field',
			'limit' => 0,
			'options' => array( 'one', 'two', 'three' ),
		) );

		$fm->add_meta_box( 'base_field', $this->post->post_type )->save_to_post_meta( $this->post->ID, array( 'two' ) );
		$saved_value = get_post_meta( $this->post->ID, 'base_field', true );
		$this->assertSame( array( 'two' ), $saved_value );

		$fm->add_meta_box( 'base_field', $this->post->post_type )->save_to_post_meta( $this->post->ID, array( 'two', 'three' ) );
		$saved_value = get_post_meta( $this->post->ID, 'base_field', true );
		$this->assertSame( array( 'two', 'three' ), $saved_value );

		$fm->add_meta_box( 'base_field', $this->post->post_type )->save_to_post_meta( $this->post->ID, '' );
		$saved_value = get_post_meta( $this->post->ID, 'base_field', true );
		$this->assertEquals( null, $saved_value );
	}


}
