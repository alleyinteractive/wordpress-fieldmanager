<?php

/**
 * Tests the Fieldmanager Checkboxes Field
 *
 * @group field
 * @group checkboxes
 */
class Test_Fieldmanager_Checkboxes_Field extends WP_UnitTestCase {
	public $post_id;
	public $post;
	public $custom_datasource;

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->post_id = $this->factory->post->create( array( 'post_title' => rand_str(), 'post_date' => '2009-07-01 00:00:00' ) );
		$this->post = get_post( $this->post_id );

		$this->months = array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' );

		$this->custom_datasource = new Fieldmanager_Datasource( array( 'options' => $this->months ) );
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

	private function _get_input_field_regex( $name, $value, $checked = false ) {
		if ( is_array( $value ) ) {
			$v = array_keys( $value );
			$v = $v[0];
			$label = $value[ $v ];
			$value = $v;
		} else {
			$label = $value;
		}

		return sprintf( '#<input\s*class="fm-element"\s*type="checkbox"\s*value="%2$s"\s*name="%1$s\[\]"\s*id="fm-%1$s-\d+-%2$s"'
			. ( $checked ? '\s*checked' : '' ) . '\s*/>'
			. '\s*<label\s*for="fm-%1$s-\d+-%2$s"\s*class="fm-option-label">\s*%3$s\s*</label>#si',
			$name, $value, $label );
	}

	public function test_basic_render() {
		$fm = new Fieldmanager_Checkboxes( array(
			'name' => 'base_field',
			'options' => array( 'one', 'two', 'three' ),
		) );

		$html = $this->_get_html_for( $fm );

		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', 'one' ), $html );
		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', 'two' ), $html );
		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', 'three' ), $html );
	}

	public function test_basic_save() {
		$fm = new Fieldmanager_Checkboxes( array(
			'name' => 'base_field',
			'options' => array( 'one', 'two', 'three' ),
		) );

		$html = $this->_get_html_for( $fm, array( 'two' ) );

		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', 'one' ), $html );
		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', 'two', true ), $html );
		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', 'three' ), $html );
	}

	public function test_associative_render() {
		$fm = new Fieldmanager_Checkboxes( array(
			'name' => 'base_field',
			'options' => array( 1 => 'one', 2 => 'two', 3 => 'three' ),
		) );

		$html = $this->_get_html_for( $fm );

		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', array( 1 => 'one' ) ), $html );
		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', array( 2 => 'two' ) ), $html );
		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', array( 3 => 'three' ) ), $html );

		$html = $this->_get_html_for( $fm, array( 2 ) );

		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', array( 1 => 'one' ) ), $html );
		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', array( 2 => 'two' ), true ), $html );
		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', array( 3 => 'three' ) ), $html );
	}

	public function test_default_value() {
		$fm = new Fieldmanager_Checkboxes( array(
			'name' => 'base_field',
			'options' => array( 'one', 'two', 'three' ),
			'default_value' => 'two',
		) );

		$html = $this->_get_html_for( $fm );

		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', 'one' ), $html );
		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', 'two', true ), $html );
		$this->assertRegExp( $this->_get_input_field_regex( 'base_field', 'three' ), $html );
	}

	public function test_datasource() {
		$fm = new Fieldmanager_Checkboxes( array(
			'name' => 'base_field',
			'datasource' => $this->custom_datasource,
		) );

		$html = $this->_get_html_for( $fm );

		foreach ( $this->months as $month ) {
			$this->assertRegExp( $this->_get_input_field_regex( 'base_field', $month ), $html );
		}
	}

	public function test_datasource_default_value() {
		$fm = new Fieldmanager_Checkboxes( array(
			'name' => 'base_field',
			'default_value' => 'February',
			'datasource' => $this->custom_datasource,
		) );

		$html = $this->_get_html_for( $fm );

		foreach ( $this->months as $month ) {
			$this->assertRegExp( $this->_get_input_field_regex( 'base_field', $month, ( 'February' === $month ) ), $html );
		}
	}

	public function test_datasource_save() {
		$fm = new Fieldmanager_Checkboxes( array(
			'name' => 'base_field',
			'datasource' => $this->custom_datasource,
		) );

		$html = $this->_get_html_for( $fm, array( 'February' ) );

		foreach ( $this->months as $month ) {
			$this->assertRegExp( $this->_get_input_field_regex( 'base_field', $month, ( 'February' === $month ) ), $html );
		}
	}

	public function test_datasource_default_value_all() {
		$fm = new Fieldmanager_Checkboxes( array(
			'name' => 'base_field',
			'default_value' => 'checked',
			'datasource' => $this->custom_datasource,
		) );

		$html = $this->_get_html_for( $fm );

		foreach ( $this->months as $month ) {
			$this->assertRegExp( $this->_get_input_field_regex( 'base_field', $month, true ), $html );
		}
	}

	public function test_repeatable_checkboxes_save() {
		$fm = new Fieldmanager_Checkboxes( array(
			'name' => 'base_field',
			'multiple' => true,
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
