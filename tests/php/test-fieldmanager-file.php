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

	private function make_temp( $filename ) {
		$tmp = tempnam( sys_get_temp_dir(), 'fm-test-' );
		copy( $filename, $tmp );
		clearstatcache();
		return $tmp;
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
	 * Test behavior when using a single file upload field
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
                'size' => 1260,
                'tmp_name' => $this->make_temp( __DIR__ . '/sample/test.jpg' ),
                'error' => 0
            ),
        );

		$this->save_values( $file, $this->post, array() );

		$saved_value = (int) get_post_meta( $this->post->ID, 'test_file', true );

		// ensure that the post was properly saved as an attachment
		$posts = get_posts( array(
			'post_type' => 'attachment',
			'post__in' => array( $saved_value, ),
		) );

		$this->assertSame( $posts[0]->ID, $saved_value );
	}

	/**
	 * Multi save
	 */
	public function test_save_multiple_with_existing() {
		$group = new Fieldmanager_Group( array(
			'name' => 'nested',
			'children' => array(
				'test_file' => new Fieldmanager_File( array(
					'name' => 'test_file',
					'limit' => 0,
				) ),
			),
		) );

		$html = $this->render( $group, $this->post );
		$this->assertContains( 'name="nested[test_file][0]"', $html );
		$this->assertContains( 'type="file"', $html );

		$f = new Fieldmanager_File;
		$attach_id = $f->save_attachment( 'test_file', array(
            'name' => 'test.gif',
            'type' => 'image/gif',
            'size' => 1214,
            'tmp_name' => $this->make_temp( __DIR__ . '/sample/test.gif' ),
            'error' => 0
        ) );

        $this->assertGreaterThan( 0, $attach_id );

		$_FILES = array(
			'nested' => array(
				'name' => array(
					'test_file' => array(
						'proto' => null,
						0 => 'test.jpg',
						2 => 'test2.jpg',
					),
				),
				'type' => array(
					'test_file' => array(
						'proto' => null,
						0 => 'image/jpeg',
						2 => 'image/png',
					),
				),
				'size' => array(
					'test_file' => array(
						'proto' => null,
						0 => 1260,
						2 => 967,
					),
				),
				'tmp_name' => array(
					'test_file' => array(
						'proto' => null,
						0 => $this->make_temp( __DIR__ . '/sample/test.jpg' ),
						2 => $this->make_temp( __DIR__ . '/sample/test.png' ),
					),
				),
				'error' => array(
					'test_file' => array(
						'proto' => null, 
						0 => 0,
						2 => 0,
					),
				),
			),
        );

        $values = array( 'test_file' => array( 1 => array( 'saved' => $attach_id ) ) );

		$this->save_values( $group, $this->post, $values );

		$saved_values = get_post_meta( $this->post->ID, 'nested', true );

		$this->assertSame( $attach_id, (int) $saved_values['test_file'][1] );

		$this->assertCount( 3, $saved_values['test_file'] );

		$saved_attachments = get_posts( array(
			'post_type' => 'attachment',
			'post__in' => $saved_values['test_file'],
		) );

		$this->assertCount( 3, $saved_attachments );
	}

}
