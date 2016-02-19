<?php

/**
 * Tests the Fieldmanager Checkbox
 *
 * @group field
 */
class Test_Fieldmanager_File extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->post = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		) );
	}

	/**
	 * Set up the request environment values and save the data.
	 *
	 * @param Fieldmanager_Field $field
	 * @param WP_Post $post
	 * @param mixed $values
	 */
	public function save_values( $field, $post, $values ) {
		$field->add_meta_box( $field->name, $post->post_type )->save_to_post_meta( $post->ID, $values );
	}

	/**
	 * Render the HTML for the field
	 *
	 * @param Fieldmanager_Field $field
	 * @param WP_Post $post
	 * @param mixed $values
	 */
	public function render( $field, $post ) {
		ob_start();
		$field->add_meta_box( $field->name, $post->post_type )->render_meta_box( $post );
		return ob_get_clean();
	}

	/**
	 * Test behavior when using a checkbox
	 */
	public function test_save() {
		$file = new Fieldmanager_File( array(
			'name' => 'test_file',
		) );

		$html = $this->render( $file, $this->post );
		$this->assertContains( 'name="test_file"', $html );
		$this->assertContains( 'type="file"', $html );

		$_FILES = array(
            'test_file' => array(
                'name' => 'test.jpg',
                'type' => 'image/jpeg',
                'size' => 37988,
                'tmp_name' => __DIR__ . '/sample/test.jpg',
                'error' => 0
            )
        );

		$this->save_values( $file, $this->post, array() );

		$saved_value = get_post_meta( $this->post->ID, 'test_file', true );
	}

}
