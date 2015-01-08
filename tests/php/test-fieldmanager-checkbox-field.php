<?php

/**
 * Tests the Fieldmanager Checkbox
 *
 * @group field
 * @group checkbox
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

		$this->assertSame( '1', $saved_value );

		$html = $this->render( $checkbox, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $checkbox, $this->post, false );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( '', $saved_value );

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

		$this->assertSame( '1', $saved_value );

		$html = $this->render( $checkbox, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $checkbox, $this->post, false );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( '', $saved_value );

		$html = $this->render( $checkbox, $this->post );
		$this->assertNotContains( 'checked', $html );
	}

	/**
	 * Test behavior when attempting to use the save_empty parameter
	 */
	public function test_save_no_empties() {
		$checkbox = new Fieldmanager_Checkbox( array(
			'name' => 'test_checkbox',
			'save_empty' => false,
		) );

		$saved_value = metadata_exists( 'post', $this->post->ID, 'test_checkbox' );
		$this->assertSame( false, $saved_value );

		$this->save_values( $checkbox, $this->post, true );
		$saved_value = metadata_exists( 'post', $this->post->ID, 'test_checkbox' );
		$this->assertSame( true, $saved_value );

		/**
		 * This is obviously not what one would expect when setting this option,
		 * but I think because of the way checkbox's presave override works this
		 * is how it works right now (essentially, the option is disregarded).
		 */
		$this->save_values( $checkbox, $this->post, '' );
		$saved_value = metadata_exists( 'post', $this->post->ID, 'test_checkbox' );
		$this->assertSame( true, $saved_value );

		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );
		$this->assertSame( '', $saved_value );
	}

	/**
	 * Test behavior when using a checkbox with custom values
	 */
	public function test_save_custom_values() {
		$checked = rand_str();
		$unchecked = rand_str();
		$checkbox = new Fieldmanager_Checkbox( array(
			'name' => 'test_checkbox',
			'checked_value' => $checked,
			'unchecked_value' => $unchecked,
		) );

		$html = $this->render( $checkbox, $this->post );
		$this->assertContains( 'name="test_checkbox"', $html );
		$this->assertContains( 'value="' . $checked . '"', $html );
		$this->assertNotContains( 'checked', $html );

		$this->save_values( $checkbox, $this->post, $checked );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( $checked, $saved_value );

		$html = $this->render( $checkbox, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $checkbox, $this->post, '' );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( $unchecked, $saved_value );

		$html = $this->render( $checkbox, $this->post );
		$this->assertNotContains( 'checked', $html );
	}

	/**
	 * Test behavior when using a checkbox with custom values that defaults to checked
	 */
	public function test_save_custom_values_default_checked() {
		$checked = rand_str();
		$unchecked = rand_str();
		$checkbox = new Fieldmanager_Checkbox( array(
			'name' => 'test_checkbox',
			'checked_value' => $checked,
			'unchecked_value' => $unchecked,
			'default_value' => $checked,
		) );

		$html = $this->render( $checkbox, $this->post );
		$this->assertContains( 'name="test_checkbox"', $html );
		$this->assertContains( 'value="' . $checked . '"', $html );
		$this->assertContains( 'checked', $html );

		$this->save_values( $checkbox, $this->post, $checked );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( $checked, $saved_value );

		$html = $this->render( $checkbox, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $checkbox, $this->post, '' );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( $unchecked, $saved_value );

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

		$this->assertSame( true, $saved_value['checkbox'] );

		$html = $this->render( $group, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $group, $this->post, array( 'checkbox' => false ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( false, $saved_value['checkbox'] );

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

		$this->assertSame( true, $saved_value['checkbox'] );

		$html = $this->render( $group, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $group, $this->post, array( 'checkbox' => false ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( false, $saved_value['checkbox'] );

		$html = $this->render( $group, $this->post );
		$this->assertNotContains( 'checked', $html );
	}

	/**
	 * Test behavior when using a checkbox with custom values inside a group
	 */
	public function test_save_group_custom_values() {
		$checked = rand_str();
		$unchecked = rand_str();
		$group = new Fieldmanager_Group( array(
			'name' => 'test_checkbox',
			'children' => array(
				'checkbox' => new Fieldmanager_Checkbox( array(
					'checked_value' => $checked,
					'unchecked_value' => $unchecked,
				) ),
			),
		) );

		$html = $this->render( $group, $this->post );
		$this->assertContains( 'value="' . $checked . '"', $html );
		$this->assertNotContains( 'checked', $html );

		$this->save_values( $group, $this->post, array( 'checkbox' => $checked ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( $checked, $saved_value['checkbox'] );

		$html = $this->render( $group, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $group, $this->post, array( 'checkbox' => false ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( $unchecked, $saved_value['checkbox'] );

		$html = $this->render( $group, $this->post );
		$this->assertNotContains( 'checked', $html );
	}

	/**
	 * Test behavior when using a checkbox with custom values inside a group
	 */
	public function test_save_group_custom_values_default_checked() {
		$checked = rand_str();
		$unchecked = rand_str();
		$group = new Fieldmanager_Group( array(
			'name' => 'test_checkbox',
			'children' => array(
				'checkbox' => new Fieldmanager_Checkbox( array(
					'checked_value' => $checked,
					'unchecked_value' => $unchecked,
					'default_value' => $checked,
				) ),
			),
		) );

		$html = $this->render( $group, $this->post );
		$this->assertContains( 'value="' . $checked . '"', $html );
		$this->assertContains( 'checked', $html );

		$this->save_values( $group, $this->post, array( 'checkbox' => $checked ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( $checked, $saved_value['checkbox'] );

		$html = $this->render( $group, $this->post );
		$this->assertContains( 'checked', $html );

		$this->save_values( $group, $this->post, array( 'checkbox' => false ) );
		$saved_value = get_post_meta( $this->post->ID, 'test_checkbox', true );

		$this->assertSame( $unchecked, $saved_value['checkbox'] );

		$html = $this->render( $group, $this->post );
		$this->assertNotContains( 'checked', $html );
	}
}
