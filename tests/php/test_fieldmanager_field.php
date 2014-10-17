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
	 * Test that using an option not listed in the base or extended class will throw an exception.
	 * @expectedException FM_Developer_Exception
	 */
	public function test_invalid_option() {
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'fake' => 'field',
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
		$this->assertEquals( 6, substr_count( $html, '<a href="#" class="fmjs-remove" title="Remove">Remove</a>' ) );
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
		$this->assertEquals( 6, substr_count( $html, '<a href="#" class="fmjs-remove" title="Remove">Remove</a>' ) );
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
		$this->assertEquals( 6, substr_count( $html, '<a href="#" class="fmjs-remove" title="Remove">Remove</a>' ) );
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
		$this->assertEquals( 6, substr_count( $html, '<a href="#" class="fmjs-remove" title="Remove">Remove</a>' ) );
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
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3 ), $test_data_too_many );
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
}
