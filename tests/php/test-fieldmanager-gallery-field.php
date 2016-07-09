<?php

/**
 * Tests the Fieldmanager Gallery
 *
 * @group field
 * @group gallery
 */
class Test_Fieldmanager_Gallery_Field extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->post = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		) );

		// Add attachments needed for rendering
		$this->media_id = array();
		foreach ( range(1, 3) as $i ) {
			$this->media_id[] = self::factory()->attachment->create_object( 'img' . $i . '.jpg', $this->post->ID, array( 'post_mime_type' => 'image/jpeg', 'post_type' => 'attachment' ) );
		}
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
	 * Test render
	 */
	public function test_render () {

		$gallery = new Fieldmanager_Gallery( false, array(
			'name' => 'test_gallery',
			'collection'          => false,
			'button_label'        => 'Select images',
			'modal_button_label'  => 'Select images',
			'modal_title'         => 'Choose image',
			'empty_gallery_label' => 'Empty gallery'
		) );

		$html = $this->render( $gallery, $this->post );
		$this->assertContains( 'name="test_gallery"', $html );
		$this->assertContains( 'data-collection="0"', $html );
		$this->assertContains( 'value="Select images"', $html );
	}

	/**
	 * Test render
	 */
	public function test_render_default_value () {

		$gallery = new Fieldmanager_Gallery( false, array(
			'name' => 'test_gallery',
			'collection'          => false,
			'button_label'        => 'Select images',
			'modal_button_label'  => 'Select images',
			'modal_title'         => 'Choose image',
			'empty_gallery_label' => 'Empty gallery',
			'default_value' => $this->media_id[0]
		) );
		$html = $this->render( $gallery, $this->post );
		$this->assertContains( 'name="test_gallery"', $html );
		$this->assertContains( 'data-collection="0"', $html );
		$this->assertContains( 'value="Select images"', $html );
		$this->assertRegExp( '/input\s+type="hidden"\s+name="test_gallery"\s+value="' . $this->media_id[0] . '"/', $html );
	}

	/**
	 * Test render (empty) collection
	 */
	public function test_render_collection () {

		$gallery = new Fieldmanager_Gallery( false, array(
			'name' => 'test_gallery',
			'collection'          => true,
			'button_label'        => 'Select images',
			'modal_button_label'  => 'Select images',
			'modal_title'         => 'Choose image',
			'empty_gallery_label' => 'Empty gallery'
		) );

		$html = $this->render( $gallery, $this->post );
		$this->assertContains( 'name="test_gallery"', $html );
		$this->assertContains( 'data-collection="1"', $html );
		$this->assertContains( 'value="Empty gallery"', $html );
		$this->assertContains( 'button-disabled', $html );
	}

	/**
	 * Test render collection
	 */
	public function test_render_collection_default_values () {

		$gallery = new Fieldmanager_Gallery( false, array(
			'name' => 'test_gallery',
			'collection'          => true,
			'button_label'        => 'Select images',
			'modal_button_label'  => 'Select images',
			'modal_title'         => 'Choose image',
			'empty_gallery_label' => 'Empty gallery',
			'default_value' => $this->media_id
		) );

		$html = $this->render( $gallery, $this->post );
		$this->assertContains( 'name="test_gallery"', $html );
		$this->assertContains( 'data-collection="1"', $html );
		$this->assertContains( 'value="Empty gallery"', $html );
		$this->assertNotContains( 'button-disabled', $html );
		$this->assertRegExp( '#src="http://example.org/wp-content/uploads/img(1|2).jpg"#', $html );
	}

	/**
	 * Test save collection
	 */
	public function test_save_collection () {

		$gallery = new Fieldmanager_Gallery( false, array(
			'name' => 'test_gallery',
			'collection'          => true,
			'button_label'        => 'Select images',
			'modal_button_label'  => 'Select images',
			'modal_title'         => 'Choose image',
			'empty_gallery_label' => 'Empty gallery'
		) );

		$html = $this->render( $gallery, $this->post );
		$this->assertContains( 'button-disabled', $html );
		$this->save_values( $gallery, $this->post, implode( ',', $this->media_id ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_gallery', true );
		$this->assertEquals( $this->media_id, $saved_value );

		// Check rendering of non empty gallery
		$html = $this->render( $gallery, $this->post );
		$this->assertRegExp( '/input\s+type="hidden"\s+name="test_gallery"\s+value="' . implode( ',', $this->media_id ) . '"/', $html );
		$this->assertRegExp( '#src="http://example.org/wp-content/uploads/img(1|2).jpg"#', $html );
		$this->assertNotContains( 'button-disabled', $html );
	}

	/**
	 * Test save group collection
	 */
	public function test_save_collection_group () {
		$gallery = new Fieldmanager_Gallery( false, array(
			'name' => 'test_gallery',
			'collection'          => true,
			'button_label'        => 'Select images',
			'modal_button_label'  => 'Select images',
			'modal_title'         => 'Choose image',
			'empty_gallery_label' => 'Empty gallery'
		) );

		$group = new Fieldmanager_Group( array(
			'name' => 'test_gallery_group',
			'limit' => 0,
			'add_to_prefix'  => false,
			'label' => 'New Gallery',
			'label_macro' => array( 'Gallery: %s', 'title' ),
			'add_more_label' => 'Add another gallery',
			'sortable' => true,
			'children' => array(
				'test_gallery' => $gallery
			),
		) );

		$html = $this->render( $group, $this->post );
		$this->save_values( $group, $this->post, array( array( 'test_gallery' => implode( ',', $this->media_id ) ) ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_gallery_group', true );
		$this->assertSame( $this->media_id, $saved_value[0]['test_gallery'] );
	}
}

