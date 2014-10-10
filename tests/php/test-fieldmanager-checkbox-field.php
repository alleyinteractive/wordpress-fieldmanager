<?php

/**
 * Tests the Fieldmanager Checkbox
 *
 * @group field
 */
class Test_Fieldmanager_Checkbox_Field extends WP_UnitTestCase {

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
		$_POST = array(
			'post_ID' => $post->ID,
			'action' => 'editpost',
			'post_type' => $post->post_type,
			"fieldmanager-{$field->name}-nonce" => wp_create_nonce( "fieldmanager-save-{$field->name}" ),
			$field->name => $values,
		);

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
		$checkbox = new Fieldmanager_Checkbox( array(
			'name' => 'test_checkbox',
		) );

		$html = $this->render( $checkbox, $this->post );
		$this->assertContains( 'name="test_checkbox"', $html );
		$this->assertNotContains( 'checked', $html );

		$this->save_values( $checkbox, $this->post, true );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertEquals( true, $saved_value );

		$html = $this->render( $checkbox, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $checkbox, $this->post, false );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertEquals( false, $saved_value );

		$html = $this->render( $checkbox, $this->post );
		$this->assertNotContains( 'checked', $html );
	}

	/**
	 * Test behavior when using a checkbox that defaults to true
	 */
	public function test_save_default_true() {
		$checkbox = new Fieldmanager_Checkbox( array(
			'name' => 'test_checkbox',
			'default_value' => true,
		) );

		$html = $this->render( $checkbox, $this->post );
		$this->assertContains( 'name="test_checkbox"', $html );
		$this->assertContains( 'checked', $html );

		$this->save_values( $checkbox, $this->post, true );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertEquals( true, $saved_value );

		$html = $this->render( $checkbox, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $checkbox, $this->post, false );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertEquals( false, $saved_value );

		$html = $this->render( $checkbox, $this->post );
		$this->assertNotContains( 'checked', $html );
	}

	/**
	 * Test behavior when using a checkbox inside a group
	 */
	public function test_save_group() {
		$group = new Fieldmanager_Group( array(
			'name' => 'test_checkbox',
			'children' => array(
				'checkbox' => new Fieldmanager_Checkbox(),
			),
		) );

		$html = $this->render( $group, $this->post );
		$this->assertNotContains( 'checked', $html );

		$this->save_values( $group, $this->post, array( 'checkbox' => true ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertEquals( true, $saved_value['checkbox'] );

		$html = $this->render( $group, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $group, $this->post, array( 'checkbox' => false ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertEquals( false, $saved_value['checkbox'] );

		$html = $this->render( $group, $this->post );
		$this->assertNotContains( 'checked', $html );
	}

	/**
	 * Test behavior when using a checkbox inside a group
	 */
	public function test_save_group_default_true() {
		$group = new Fieldmanager_Group( array(
			'name' => 'test_checkbox',
			'children' => array(
				'checkbox' => new Fieldmanager_Checkbox( array(
					'default_value' => true,
				) ),
			),
		) );

		$html = $this->render( $group, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $group, $this->post, array( 'checkbox' => true ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertEquals( true, $saved_value['checkbox'] );

		$html = $this->render( $group, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $group, $this->post, array( 'checkbox' => false ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertEquals( false, $saved_value['checkbox'] );

		$html = $this->render( $group, $this->post );
		$this->assertNotContains( 'checked', $html );
	}
}
