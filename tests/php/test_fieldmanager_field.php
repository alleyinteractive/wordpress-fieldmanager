<?php

/**
 * Tests Fieldmanager_Field, which handles validation and
 * throws most core exceptions
 *
 * @group field
 */
class Fieldmanager_Field_Test extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = TRUE;

		$this->post = array(
			'post_status' => 'publish',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		);

		// insert a post
		$this->post_id = wp_insert_post( $this->post );
		// reload as proper object
		$this->post = get_post( $this->post_id );
	}

	public function tearDown() {
		$meta = get_post_meta( $this->post_id );
		foreach ( $meta as $key => $value ) {
			delete_post_meta( $this->post_id, $key );
		}
	}

	/**
	 * Get valid test data.
	 * Several tests transform this data to somehow be invalid.
	 * @return array valid test data
	 */
	private function _get_valid_test_data() {
		return array(
			'base_group' => array(
				'test_textfield' => 'alley interactive',
				'test_checkbox2' => 'yes',
				'test_numfield' => 1234,
				'test_htmlfield' => '<b>Hello</b> world',
				'test_extended' => array(
					array(
						'extext' => array( 'first' ),
					),
					array(
						'extext' => array( 'second1', 'second2', 'second3' ),
					),
					array(
						'extext' => array( 'third' ),
					),
					array(
						'extext' => array( 'fourth' ),
					),
				),
			),
		);
	}

	/**
	 * Get some simple test data for a single entry of a repeatable field
	 * @return array
	 */
	private function _get_simple_test_data_single() {
		return array(
			'test_element' => array(
				array( 'text' => 'a' ),
			)
		);
	}

	/**
	 * Get some simple test data for three entries of a repeatable field
	 * @return array
	 */
	private function _get_simple_test_data_multiple() {
		return array(
			'test_element' => array(
				array( 'text' => 'a' ),
				array( 'text' => 'b' ),
				array( 'text' => 'c' ),
			)
		);
	}

	/**
	 * Get a set of elements
	 * @return Fieldmanager_Field[]
	 */
	private function _get_elements() {
		return array(
			'test_textfield' => new Fieldmanager_TextField( array(
				'name' => 'test_textfield',
				'validate' => array(
					function( $value ) {
						return strlen( $value ) > 2;
					}
				),
				'index' => '_test_index',
			) ),
			'test_htmlfield' => new Fieldmanager_Textarea( array(
				'name' => 'test_htmlfield',
				'sanitize' => 'wp_kses_post',
			) ),
			'test_numfield' => new Fieldmanager_TextField( array(
				'name' => 'test_numfield',
				'input_type' => 'number',
				'validate' => array( 'is_numeric' ),
			) ),
			'test_pwfield' => new Fieldmanager_Password,
			'test_checkbox' => new Fieldmanager_Checkbox( array(
				'name' => 'test_checkbox',
			) ),
			'test_checkbox2' => new Fieldmanager_Checkbox( array(
				'name' => 'test_checkbox2',
				'checked_value' => 'yes',
				'unchecked_value' => 'no',
			) ),
			'test_extended' => new Fieldmanager_Group( array(
				'limit' => 4,
				'name' => 'test_extended',
				'children' => array(
					'extext' => new Fieldmanager_TextField( array(
						'limit' => 0,
						'name' => 'extext',
						'one_label_per_item' => False,
						'sortable' => True,
						'index' => '_extext_index',
					) ),
				),
			) ),
		);
	}

	/**
	 * Helper to test limit, extra_elements, minimum_count combinations.
	 *
	 * @param  array $args      Fieldmanager_Group args.
	 * @param  array $test_data Optional. If present, data will be saved to post meta.
	 * @return string Rendered meta box.
	 */
	private function _get_html_for_extra_element_args( $args, $test_data = null ) {
		delete_post_meta( $this->post_id, 'base_group' );
		$args = wp_parse_args( $args, array(
			'children' => array( 'text' => new Fieldmanager_TextField( false ) )
		) );
		$field = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => array(
				'test_element' => new Fieldmanager_Group( $args )
			)
		) );

		return $this->_get_html_for( $field, $test_data );
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
		$context->render_meta_box( $this->post, array() );
		return ob_get_clean();
	}

	/**
	 * Test that basic save functions work properly
	 */
	public function test_save_fields() {
		$elements = $this->_get_elements();
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => $elements
		) );
		$test_str = rand_str();
		$test_data = $this->_get_valid_test_data();
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
		$saved_value = get_post_meta( $this->post_id, 'base_group', TRUE );

		$saved_index = get_post_meta( $this->post_id, '_test_index', TRUE );
		$this->assertEquals( $saved_index, $saved_value['test_textfield'] );

		$this->assertEquals( $saved_value['test_textfield'], 'alley interactive' );
		$this->assertEquals( $saved_value['test_checkbox'], 0 );
		$this->assertEquals( $saved_value['test_checkbox2'], 'yes' );
		$this->assertEquals( count( $saved_value['test_extended'] ), 4 );
		$this->assertEquals( count( $saved_value['test_extended'][0]['extext'] ), 1 );
		$this->assertEquals( count( $saved_value['test_extended'][1]['extext'] ), 3 );
		$this->assertEquals( count( $saved_value['test_extended'][2]['extext'] ), 1 );
		$this->assertEquals( count( $saved_value['test_extended'][3]['extext'] ), 1 );
		$this->assertEquals( $saved_value['test_extended'][1]['extext'], array( 'second1', 'second2', 'second3' ) );
		$this->assertEquals( $saved_value['test_extended'][3]['extext'][0], 'fourth' );
		$this->assertEquals( count( $saved_value ), count( $elements ) );
	}

	/**
	 * Test that index functions work properly
	 */
	public function test_save_indices() {
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => $this->_get_elements(),
		) );
		$test_data = $this->_get_valid_test_data();
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
		$saved_value = get_post_meta( $this->post_id, 'base_group', TRUE );

		$saved_index = get_post_meta( $this->post_id, '_test_index', TRUE );
		$this->assertEquals( $saved_value['test_textfield'], $saved_index );

		$repeat_indices = get_post_meta( $this->post_id, '_extext_index', FALSE );
		$this->assertEquals( array( 'first', 'second1', 'second2', 'second3', 'third', 'fourth' ), $repeat_indices );

		// Test updating the data.
		$test_data['base_group']['test_textfield'] = rand_str();
		$test_data['base_group']['test_extended'] = array( array( 'extext' => array( rand_str() ) ) );
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );

		$saved_value = get_post_meta( $this->post_id, 'base_group', TRUE );
		$this->assertEquals( $test_data['base_group']['test_textfield'], $saved_value['test_textfield'] );

		// Test single field index.
		$saved_index = get_post_meta( $this->post_id, '_test_index', TRUE );
		$this->assertEquals( $saved_value['test_textfield'], $saved_index );

		$this->assertEquals( $test_data['base_group']['test_extended'][0]['extext'], $saved_value['test_extended'][0]['extext'] );

		// Test repeated field index.
		$repeat_indices = get_post_meta( $this->post_id, '_extext_index', FALSE );
		$this->assertEquals( $saved_value['test_extended'][0]['extext'], $repeat_indices );

		// Test empty repeated field index.
		$test_data['base_group']['test_extended'] = array( array( 'extext' => array() ) );
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );

		$saved_value = get_post_meta( $this->post_id, 'base_group', TRUE );
		$this->assertEmpty( $saved_value['test_extended'] );

		$repeat_indices = get_post_meta( $this->post_id, '_extext_index', FALSE );
		$this->assertEmpty( $repeat_indices );

		// Test filtered field index.
		$replace_me = rand_str();
		$dont_replace_me = rand_str();
		$filter_value = rand_str();
		$base->children['test_extended']->children['extext']->index_filter = function( $value ) use ( $replace_me, $filter_value ) {
			return $value == $replace_me ? $filter_value : $value;
		};

		$test_data['base_group']['test_extended'] = array( array( 'extext' => array( $replace_me, $dont_replace_me ) ) );
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );

		$repeat_indices = get_post_meta( $this->post_id, '_extext_index', FALSE );
		$this->assertEquals( array( $filter_value, $dont_replace_me ), $repeat_indices );
	}

	/**
	 * Test that a closure validator works properly
	 * Specifically verifies that callables of various types work.
	 * @expectedException FM_Validation_Exception
	 */
	public function test_invalid_closure() {
		$elements = $this->_get_elements();
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => $elements
		) );
		$test_data = $this->_get_valid_test_data();
		$test_data['base_group']['test_textfield'] = 'a'; // Violate test_textfield's validator which checks strlen > 2
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
	}

	/**
	 * Test that using is_numeric as a validator works properly
	 * Verifies that the framework hasn't been refactored to something that expects a result other than true/false
	 * @expectedException FM_Validation_Exception
	 */
	public function test_invalid_php_callback() {
		$elements = $this->_get_elements();
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => $elements
		) );
		$test_data = $this->_get_valid_test_data();
		$test_data['base_group']['test_numfield'] = rand_str(); // Violate test_numfield's is_numeric validater
		$test_data = array( 'base_group' => array( 'test_textfield' => rand_str(), 'test_numfield' => rand_str(), 'test_checkbox2' => 'yes' ) );
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
	}

	/**
	 * Test that using an option not listed in the base or extended class will
	 * fail silently when debug mode is disabled.
	 */
	public function test_invalid_option() {
		Fieldmanager_Field::$debug = false;
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'fake' => 'field',
			'meta_box_actions_added' => 'foobar',
		) );
		$this->assertFalse( isset( $base->fake ) );
		Fieldmanager_Field::$debug = true;
	}

	/**
	 * Test that using an option not listed in the base or extended class will
	 * throw an exception when debug mode is enabled.
	 *
	 * @expectedException FM_Developer_Exception
	 */
	public function test_invalid_option_debug() {
		Fieldmanager_Field::$debug = true;
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'fake' => 'field',
			'meta_box_actions_added' => 'foobar',
		) );
	}

	/**
	 * Test that submitting a form with an undefined key will throw an exception.
	 * @expectedException FM_Exception
	 */
	public function test_invalid_key() {
		$elements = $this->_get_elements();
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => $elements
		) );
		$test_data = $this->_get_valid_test_data();
		$test_data['base_group']['hax'] = 'all ur base'; // this bit of data is not in the group.
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
	}

	/**
	 * Test that submitting a multi-dimensional array with $limit = 1 will throw an exception.
	 * @expectedException FM_Exception
	 */
	public function test_unexpected_array() {
		$elements = $this->_get_elements();
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => $elements
		) );
		$test_data = $this->_get_valid_test_data();
		$test_data['base_group']['test_textfield'] = array( 'alley interactive' ); // should not be multi-dimensional.
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
	}

	/**
	 * Test that submitting a single value with $limit != 1 will throw an exception.
	 * @expectedException FM_Exception
	 */
	public function test_unexpected_not_array() {
		$elements = $this->_get_elements();
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => $elements
		) );
		$test_data = $this->_get_valid_test_data();
		$test_data['base_group']['test_extended'][0]['extext'] = 'first'; // should be multi-dimensional.
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
	}

	/**
	 * Test that submitting more values than the limit will throw an exception.
	 * @expectedException FM_Exception
	 */
	public function test_unexpected_too_many_elements() {
		$elements = $this->_get_elements();
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => $elements
		) );
		$test_data = $this->_get_valid_test_data();
		$test_data['base_group']['test_extended'][] = array( 'extext' => array( 'fifth' ) ); // Limit is 4.
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
	}

	/**
	 * Test that submitting a non-numeric key with multiple elements will throw an exception.
	 * @expectedException FM_Exception
	 */
	public function test_unexpected_non_numeric_key() {
		$elements = $this->_get_elements();
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => $elements
		) );
		$test_data = $this->_get_valid_test_data();
		unset( $test_data['base_group']['test_extended'][3] ); // keep the limit legal.
		$test_data['base_group']['test_extended']['f0urth'] = array( 'extext' => array( 'fifth' ) ); // non-numeric keys aren't allowed.
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
	}

	/**
	 * Test that the default sanitizer will strip HTML, and that wp_kses_post will allow it through.
	 */
	public function test_sanitize() {
		$elements = $this->_get_elements();
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => $elements
		) );
		$test_data = $this->_get_valid_test_data();

		// some unwanted HTML which should be stripped by the default sanitizer.
		$test_data['base_group']['test_textfield'] = '<a href="#">alley interactive</a>';
		$test_data['base_group']['test_htmlfield'] = '<a href="#">alley interactive</a>';

		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
		$saved_value = get_post_meta( $this->post_id, 'base_group', TRUE );
		$this->assertEquals( $saved_value['test_textfield'], 'alley interactive' );
		$this->assertEquals( $saved_value['test_htmlfield'], '<a href="#">alley interactive</a>' );
	}

	/**
	 * Test the form output
	 */
	public function test_form_output() {
		$elements = $this->_get_elements();
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => $elements
		) );
		$test_data = $this->_get_valid_test_data();
		ob_start();
		$base->add_meta_box( 'test meta box', $this->post )->render_meta_box( $this->post, array() );
		$str = ob_get_clean();
		// we can't really care about the structure of the HTML, but we can make sure that all fields are here
		$this->assertContains( 'name="fieldmanager-base_group-nonce"', $str );
		$this->assertContains( 'name="base_group[test_textfield]"', $str );
		$this->assertContains( 'name="base_group[test_numfield]"', $str );
		$this->assertContains( 'name="base_group[test_pwfield]"', $str );
		$this->assertContains( 'name="base_group[test_extended][0][extext][proto]"', $str );
		$this->assertContains( 'type="text"', $str );
		$this->assertContains( 'type="number"', $str );
		$this->assertContains( 'type="password"', $str );
	}

	public function test_multi_tools_in_group_without_label() {
		$label = rand_str();
		$button = rand_str();

		$field = new Fieldmanager_Group( array(
			'name' => 'multi_tools',
			'children' => array( 'text' => new Fieldmanager_TextField ),
		) );

		// Ensure that, by default, no multitools are present
		$html = $this->_get_html_for( $field );
		$this->assertNotContains( 'fmjs-drag', $html );
		$this->assertNotContains( 'fmjs-remove', $html );
		$this->assertNotContains( 'fm-collapsible', $html );
		$this->assertNotContains( 'fm-add-another', $html );

		// Ensure limit != 1 tools are present
		$field->limit = 0;
		$html = $this->_get_html_for( $field );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertNotContains( 'fmjs-drag', $html );
		$this->assertNotContains( 'fm-collapsible', $html );

		// Ensure sortable tools are present
		$field->sortable = true;
		$html = $this->_get_html_for( $field );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertContains( 'fmjs-drag', $html );
		$this->assertNotContains( 'fm-collapsible', $html );

		// Ensure collapsible tools are present, even though this doesn't
		// work (there's nothing to collapse to)
		$field->collapsible = true;
		$html = $this->_get_html_for( $field );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertContains( 'fmjs-drag', $html );
		$this->assertContains( 'fm-collapsible', $html );

		// Ensure everything still works without one_label_per_item
		$field->one_label_per_item = false;
		$html = $this->_get_html_for( $field );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertContains( 'fmjs-drag', $html );
		$this->assertContains( 'fm-collapsible', $html );

		// Ensure customized button label
		$field->add_more_label = $button;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $button, $html );

		// Ensure we have 6 (5 + proto) of all of our tools, when we have a
		// minimum count of 5
		$field->minimum_count = 5;
		$html = $this->_get_html_for( $field );
		$this->assertEquals( 6, substr_count( $html, '<a href="#" class="fmjs-remove" title="Remove"><span class="screen-reader-text">Remove</span></a>' ) );
		$this->assertEquals( 6, substr_count( $html, 'fmjs-drag-icon' ) );
	}

	public function test_multi_tools_in_group_with_label() {
		$label = rand_str();
		$button = rand_str();

		$field = new Fieldmanager_Group( array(
			'name' => 'multi_tools',
			'label' => $label,
			'children' => array( 'text' => new Fieldmanager_TextField ),
		) );

		// Ensure that, by default, no multitools are present
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label, $html );
		$this->assertNotContains( 'fmjs-drag', $html );
		$this->assertNotContains( 'fmjs-remove', $html );
		$this->assertNotContains( 'fm-collapsible', $html );
		$this->assertNotContains( 'fm-add-another', $html );

		// Ensure limit != 1 tools are present
		$field->limit = 0;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label, $html );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertNotContains( 'fmjs-drag', $html );
		$this->assertNotContains( 'fm-collapsible', $html );

		// Ensure sortable tools are present
		$field->sortable = true;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label, $html );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertContains( 'fmjs-drag', $html );
		$this->assertNotContains( 'fm-collapsible', $html );

		// Ensure collapsible tools are present
		$field->collapsible = true;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label, $html );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertContains( 'fmjs-drag', $html );
		$this->assertContains( 'fm-collapsible', $html );

		// Ensure everything still works without one_label_per_item
		$field->one_label_per_item = false;
		$html = $this->_get_html_for( $field );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertContains( 'fmjs-drag', $html );
		$this->assertContains( 'fm-collapsible', $html );

		// Ensure customized button label
		$field->add_more_label = $button;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label, $html );
		$this->assertContains( $button, $html );

		// Ensure we have 6 (5 + proto) of all of our tools, when we have a
		// minimum count of 5
		$field->minimum_count = 5;
		$html = $this->_get_html_for( $field );
		$this->assertEquals( 6, substr_count( $html, '<a href="#" class="fmjs-remove" title="Remove"><span class="screen-reader-text">Remove</span></a>' ) );
		$this->assertEquals( 6, substr_count( $html, 'fmjs-drag-icon' ) );
	}

	public function test_multi_tools_in_field_without_label() {
		$label = rand_str();
		$button = rand_str();

		$field = new Fieldmanager_TextField( array(
			'name' => 'multi_tools',
		) );

		// Ensure that, by default, no multitools are present
		$html = $this->_get_html_for( $field );
		$this->assertNotContains( 'fmjs-drag', $html );
		$this->assertNotContains( 'fmjs-remove', $html );
		$this->assertNotContains( 'fm-collapsible', $html );

		// Ensure limit != 1 tools are present
		$field->limit = 0;
		$html = $this->_get_html_for( $field );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertNotContains( 'fmjs-drag', $html );

		// Ensure sortable tools are present
		$field->sortable = true;
		$html = $this->_get_html_for( $field );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertContains( 'fmjs-drag', $html );

		// Ensure everything still works without one_label_per_item
		$field->one_label_per_item = false;
		$html = $this->_get_html_for( $field );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertContains( 'fmjs-drag', $html );

		// Ensure customized button label
		$field->add_more_label = $button;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $button, $html );

		// Ensure we have 6 (5 + proto) of all of our tools, when we have a
		// minimum count of 5
		$field->minimum_count = 5;
		$html = $this->_get_html_for( $field );
		$this->assertEquals( 6, substr_count( $html, '<a href="#" class="fmjs-remove" title="Remove"><span class="screen-reader-text">Remove</span></a>' ) );
		$this->assertEquals( 6, substr_count( $html, 'fmjs-drag-icon' ) );
	}

	public function test_multi_tools_in_field_with_label() {
		$label = rand_str();
		$button = rand_str();

		$field = new Fieldmanager_TextField( array(
			'name' => 'multi_tools',
			'label' => $label,
		) );

		// Ensure that, by default, no multitools are present
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label, $html );
		$this->assertNotContains( 'fmjs-drag', $html );
		$this->assertNotContains( 'fmjs-remove', $html );
		$this->assertNotContains( 'fm-collapsible', $html );

		// Ensure limit != 1 tools are present
		$field->limit = 0;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label, $html );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertNotContains( 'fmjs-drag', $html );

		// Ensure sortable tools are present
		$field->sortable = true;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label, $html );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertContains( 'fmjs-drag', $html );

		// Ensure customized button label
		$field->add_more_label = $button;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label, $html );
		$this->assertContains( $button, $html );

		// Ensure everything still works without one_label_per_item
		$field->one_label_per_item = false;
		$html = $this->_get_html_for( $field );
		$this->assertContains( 'fmjs-remove', $html );
		$this->assertContains( 'fm-add-another', $html );
		$this->assertContains( 'fmjs-drag', $html );

		// Ensure we have 6 (5 + proto) of all of our tools, when we have a
		// minimum count of 5
		$field->minimum_count = 5;
		$html = $this->_get_html_for( $field );
		$this->assertEquals( 6, substr_count( $html, '<a href="#" class="fmjs-remove" title="Remove"><span class="screen-reader-text">Remove</span></a>' ) );
		$this->assertEquals( 6, substr_count( $html, 'fmjs-drag-icon' ) );
	}


	/**
	 * Thoroughly test the interaction of limits, extra elements, minimum
	 * counts, and submitting data. This may appear unnecessarily complex, but
	 * it's worth it to verify the interaction at each turn.
	 *
	 * The test considers the interaction of:
	 *     no limit, limit of 3 fields
	 *     (0, default, 2, 3, 5) extra_elements
	 *     (default, 1, 2, 3, 5) minimum_count
	 *     (0, 1, 3) submitted entries
	 *
	 * Since this is a test, the nested loops shouldn't be that big of a
	 * concern.
	 */
	public function test_extra_elements_and_minimum_counts() {
		foreach ( array( 0, 3 ) as $limit ) {
			$args = array( 'limit' => $limit );
			foreach ( array( 0, 1, 2, 3, 5 ) as $extra_elements ) {
				unset( $args['extra_elements'] );
				if ( 1 != $extra_elements ) {
					$args['extra_elements'] = $extra_elements;
				}
				foreach ( array( 0, 1, 2, 3, 5) as $minimum_count ) {
					unset( $args['minimum_count'] );
					if ( $minimum_count > 0 ) {
						$args['minimum_count'] = $minimum_count;
					}
					foreach ( array( 0, 1, 3 ) as $data ) {
						if ( 1 == $data ) {
							$test_data = $this->_get_simple_test_data_single();
						} elseif ( 3 == $data ) {
							$test_data = $this->_get_simple_test_data_multiple();
						} else {
							$test_data = null;
						}

						$test_conditions = json_encode( array_merge( $args, array( 'data' => $data ) ) );
						$str = $this->_get_html_for_extra_element_args( $args, $test_data );

						// There should always be a prototype
						$this->assertContains( 'name="base_group[test_element][proto][text]"', $str, "Attempted to assert that the prototype is present when: {$test_conditions}" );

						if ( 0 === $extra_elements && 0 === $minimum_count && 0 === $data ) {
							// We should have no fields beyond the prototype
							$this->assertNotContains( 'name="base_group[test_element][0][text]"', $str, "Attempted to assert that field 0 is NOT present when: {$test_conditions}" );
						} else {
							// At the very least, we have 1 field
							$this->assertContains( 'name="base_group[test_element][0][text]"', $str, "Attempted to assert that field 0 is present when: {$test_conditions}" );

							$ceiling = max( $minimum_count, $data + $extra_elements );
							if ( 3 == $limit ) {
								$ceiling = min( $ceiling, $limit );
							}

							if ( $ceiling > 1 ) {
								// Ensure that the absolute ceiling is present
								$this->assertContains( 'name="base_group[test_element][' . ( $ceiling - 1 ) . '][text]"', $str, "Attempted to assert that field " . ( $ceiling - 1 ) . " is present when: {$test_conditions}" );
							}

							// Ensure that the field after the ceiling is absent
							$this->assertNotContains( 'name="base_group[test_element][' . $ceiling . '][text]"', $str, "Attempted to assert that field {$ceiling} is NOT present when: {$test_conditions}" );
						}

						if ( 3 == $limit && $minimum_count >= 3 ) {
							// Ensure that the multi-field tools were removed
							$this->assertNotContains( 'fmjs-remove', $str, "Attempted to assert that the remove button is NOT present when: {$test_conditions}" );
							$this->assertNotContains( 'fm-add-another', $str, "Attempted to assert that the add another button is NOT present when: {$test_conditions}" );
						} else {
							// Ensure that the multi-field tools are present
							$this->assertContains( 'fmjs-remove', $str, "Attempted to assert that the remove button is present when: {$test_conditions}" );
							$this->assertContains( 'fm-add-another', $str, "Attempted to assert that the add another button is present when: {$test_conditions}" );
						}
					}
				}
			}
		}
	}


	/**
	 * @expectedException FM_Exception
     * @expectedExceptionMessage submitted 5 values against a limit of 3
	 */
	public function test_limit_exceeded_exceptions() {
		$test_data_too_many = array(
			'test_element' => array(
				array( 'text' => 'a' ),
				array( 'text' => 'b' ),
				array( 'text' => 'c' ),
				array( 'text' => 'd' ),
				array( 'text' => 'e' ),
			)
		);

		$field = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => array(
				'test_element' => new Fieldmanager_Group( array(
					'limit' => 3,
					'children' => array( 'text' => new Fieldmanager_TextField( false ) ),
				) )
			)
		) );
		$context = $field->add_meta_box( 'test meta box', $this->post );
		$context->save_to_post_meta( $this->post_id, $test_data_too_many );
	}

	public function test_attributes(){
		$fm = new Fieldmanager_Textfield( array(
			'name' => 'test_attributes',
			'attributes' => array(
				'required' => true,
				'data-foo' => 'bar',
				'data-UPPER' => 'lower'
			)
		) );
		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		$this->assertRegExp( '/\srequired[\/\s]/', $html );
		$this->assertRegExp( '/\sdata-foo="bar"[\/\s]/', $html );
		$this->assertRegExp( '/\sdata-upper="lower"[\/\s]/', $html );
	}

	public function test_label_escaping() {
		$id = rand_str();
		$label_raw = rand_str();
		$label_html = "<strong id='{$id}'>{$label_raw}</strong>";
		$args = array(
			'name' => 'label_escape_testing',
			'label' => $label_html,
		);

		// Ensure that, by default, the label is present without the HTML
		$field = new Fieldmanager_TextField( $args );
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label_raw, $html );
		$this->assertNotContains( $label_html, $html );

		// Ensure that the label has HTML when we change the escaping
		$args['escape'] = array( 'label' => 'wp_kses_post' );
		$field = new Fieldmanager_TextField( $args );
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label_html, $html );
	}

	public function test_description_escaping() {
		$id = rand_str();
		$description_raw = rand_str();
		$description_html = "<strong id='{$id}'>{$description_raw}</strong>";
		$args = array(
			'name' => 'description_escape_testing',
			'description' => $description_html,
		);

		// Ensure that, by default, the description is present without the HTML
		$field = new Fieldmanager_TextField( $args );
		$html = $this->_get_html_for( $field );
		$this->assertContains( $description_raw, $html );
		$this->assertNotContains( $description_html, $html );

		// Ensure that the description has HTML when we change the escaping
		$args['escape'] = array( 'description' => 'wp_kses_post' );
		$field = new Fieldmanager_TextField( $args );
		$html = $this->_get_html_for( $field );
		$this->assertContains( $description_html, $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_single_field_render() {
		$args = array(
			'name'  => 'base_field',
			'limit' => 0,
		);

		$base = new Fieldmanager_TextField( $args );
		$html = $this->_get_html_for( $base );
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_field-nonce"/', $html );
		$this->assertContains( 'name="base_field[proto]"', $html );
		$this->assertContains( 'name="base_field[0]"', $html );
		$this->assertNotContains( 'name="base_field[1]"', $html );

		// Using serialize_data => false shouldn't change anything
		$base = new Fieldmanager_TextField( array_merge( $args, array( 'serialize_data' => false ) ) );
		$html = $this->_get_html_for( $base );
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_field-nonce"/', $html );
		$this->assertContains( 'name="base_field[proto]"', $html );
		$this->assertContains( 'name="base_field[0]"', $html );
		$this->assertNotContains( 'name="base_field[1]"', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_single_field_render_with_data() {
		$args = array(
			'name'  => 'base_field',
			'limit' => 0,
		);
		$data = array( rand_str(), rand_str(), rand_str() );

		update_post_meta( $this->post_id, 'base_field', $data );
		$base = new Fieldmanager_TextField( $args );
		$html = $this->_get_html_for( $base );
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_field-nonce"/', $html );
		$this->assertContains( 'name="base_field[proto]"', $html );
		$this->assertContains( 'name="base_field[3]"', $html );
		$this->assertNotContains( 'name="base_field[4]"', $html );

		// Using serialize_data => false requires a different data storage
		delete_post_meta( $this->post_id, 'base_field' );
		foreach ( $data as $meta_value ) {
			add_post_meta( $this->post_id, 'base_field', $meta_value );
		}
		$this->assertEquals( $data, get_post_meta( $this->post_id, 'base_field' ) );
		$base = new Fieldmanager_TextField( array_merge( $args, array( 'serialize_data' => false ) ) );
		$html = $this->_get_html_for( $base );
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_field-nonce"/', $html );
		$this->assertContains( 'name="base_field[proto]"', $html );
		$this->assertContains( 'name="base_field[3]"', $html );
		$this->assertNotContains( 'name="base_field[4]"', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_single_field_save() {
		$base = new Fieldmanager_TextField( array(
			'name'           => 'base_field',
			'limit'          => 0,
			'serialize_data' => false,
		) );
		$data = array( rand_str(), rand_str(), rand_str() );
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );

		$this->assertEquals( $data, get_post_meta( $this->post_id, 'base_field' ) );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_single_field_sorting() {
		$item_1 = rand_str();
		$item_2 = rand_str();
		$item_3 = rand_str();
		$base = new Fieldmanager_TextField( array(
			'name'           => 'base_field',
			'limit'          => 0,
			'serialize_data' => false,
		) );
		$context = $base->add_meta_box( 'test meta box', 'post' );

		// Test as 1, 2, 3
		$data = array( $item_1, $item_2, $item_3 );
		$context->save_to_post_meta( $this->post_id, $data );
		$html = $this->_get_html_for( $base );
		$this->assertEquals( $data, get_post_meta( $this->post_id, 'base_field' ) );
		$this->assertRegExp( '/<input[^>]+name="base_field\[0\][^>]+value="' . $item_1 . '"/', $html );
		$this->assertRegExp( '/<input[^>]+name="base_field\[1\][^>]+value="' . $item_2 . '"/', $html );
		$this->assertRegExp( '/<input[^>]+name="base_field\[2\][^>]+value="' . $item_3 . '"/', $html );

		// Reorder and test as 3, 1, 2
		$data = array( $item_3, $item_1, $item_2 );
		$context->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data, get_post_meta( $this->post_id, 'base_field' ) );
		$html = $this->_get_html_for( $base );
		$this->assertRegExp( '/<input[^>]+name="base_field\[0\][^>]+value="' . $item_3 . '"/', $html );
		$this->assertRegExp( '/<input[^>]+name="base_field\[1\][^>]+value="' . $item_1 . '"/', $html );
		$this->assertRegExp( '/<input[^>]+name="base_field\[2\][^>]+value="' . $item_2 . '"/', $html );

		// Reorder and test as 2, 3, 1
		$data = array( $item_2, $item_3, $item_1 );
		$context->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data, get_post_meta( $this->post_id, 'base_field' ) );
		$html = $this->_get_html_for( $base );
		$this->assertRegExp( '/<input[^>]+name="base_field\[0\][^>]+value="' . $item_2 . '"/', $html );
		$this->assertRegExp( '/<input[^>]+name="base_field\[1\][^>]+value="' . $item_3 . '"/', $html );
		$this->assertRegExp( '/<input[^>]+name="base_field\[2\][^>]+value="' . $item_1 . '"/', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_limit_1_no_impact() {
		$base = new Fieldmanager_TextField( array(
			'name'           => 'base_field',
			'serialize_data' => false,
		) );
		$data = rand_str();
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );

		$this->assertEquals( $data, get_post_meta( $this->post_id, 'base_field', true ) );
	}

	/**
	 * @group serialize_data
	 * @expectedException FM_Developer_Exception
	 */
	public function test_unserialize_data_single_field_index() {
		new Fieldmanager_TextField( array(
			'name'           => 'test',
			'limit'          => 0,
			'serialize_data' => false,
			'index'          => true,
		) );
	}

	public function test_removing_item_from_repeatable() {
		$field = new Fieldmanager_Textfield( array(
			'name' => 'removing_items_testing',
			'sortable' => true,
			'extra_elements' => 0,
			'limit' => 0,
		) );

		$context = $field->add_meta_box( 'removing_items_testing', $this->post );

		$to_remove = rand_str();
		$to_save = array( $to_remove, rand_str(), rand_str() );

		$context->save_to_post_meta( $this->post_id, $to_save );

		$data = get_post_meta( $this->post_id, 'removing_items_testing', true );

		$this->assertEquals( 3, count( $data ) );

		$to_save[0] = '';

		$context->save_to_post_meta( $this->post_id, $to_save );

		$data = get_post_meta( $this->post_id, 'removing_items_testing', true );

		$this->assertEquals( 2, count( $data ) );

		ob_start();
		$context->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		$this->assertNotContains( "value=\"{$to_remove}\"", $html );
		$this->assertContains( "value=\"{$to_save[1]}\"", $html );
		$this->assertContains( "value=\"{$to_save[2]}\"", $html );
	}

	public function test_attachment_detection() {
		$fm_1 = new Fieldmanager_Textfield( array(
			'name' => 'test_attachment_detection',
		) );
		$context_1 = $fm_1->add_meta_box( 'Test Attachment Detection', 'post' );
		$this->assertFalse( $fm_1->is_attachment );

		// Ensure attachment sets $is_attachment
		$fm_2 = new Fieldmanager_Textfield( array(
			'name' => 'test_attachment_detection',
		) );
		$context_2 = $fm_2->add_meta_box( 'Test Attachment Detection', 'attachment' );
		$this->assertEquals( 10, has_filter( 'attachment_fields_to_save', array( $context_2, 'save_fields_for_attachment') ) );
		remove_filter( 'attachment_fields_to_save', array( $context_2, 'save_fields_for_attachment' ) );
		$this->assertTrue( $fm_2->is_attachment );

		// Ensure attachment is read from an array
		$fm_3 = new Fieldmanager_Textfield( array(
			'name' => 'test_attachment_detection',
		) );
		$context_3 = $fm_3->add_meta_box( 'Test Attachment Detection', array( 'post', 'attachment' ) );
		$this->assertEquals( 10, has_filter( 'attachment_fields_to_save', array( $context_3, 'save_fields_for_attachment') ) );
		remove_filter( 'attachment_fields_to_save', array( $context_3, 'save_fields_for_attachment' ) );
		$this->assertTrue( $fm_3->is_attachment );
	}
}
