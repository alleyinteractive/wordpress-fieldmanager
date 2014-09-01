<?php

/**
 * Tests Fieldmanager_Field, which handles validation and
 * throws most core exceptions
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
	 * Helper to test limit, extra_elements, starting_count combinations.
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

		// Ensure customized button label
		$field->add_more_label = $button;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $button, $html );
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

		// Ensure customized button label
		$field->add_more_label = $button;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label, $html );
		$this->assertContains( $button, $html );
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

		// Ensure customized button label
		$field->add_more_label = $button;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $button, $html );
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

		// Ensure customized button label
		$field->add_more_label = $button;
		$html = $this->_get_html_for( $field );
		$this->assertContains( $label, $html );
		$this->assertContains( $button, $html );
	}

	/**
	 * Test extra elements, limits, and starting count
	 */
	public function test_extra_elements() {
		$test_data_single = array(
			'test_element' => array(
				array( 'text' => 'a' ),
			)
		);
		$test_data_multiple = array(
			'test_element' => array(
				array( 'text' => 'a' ),
				array( 'text' => 'b' ),
				array( 'text' => 'c' ),
			)
		);


		// Test no limit
		///////////////////////////////////////////////////

		// Defaults for extra elements and starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][1][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][2][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][3][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][4][text]"', $str );

		// 0 extra elements, default starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 0 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 0 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 0 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		// default extra elements, 0 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'starting_count' => 0 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][0][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'starting_count' => 0 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][1][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][2][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'starting_count' => 0 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][3][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][4][text]"', $str );

		// 0 extra elements, 0 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 0, 'starting_count' => 0 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][0][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 0, 'starting_count' => 0 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 0, 'starting_count' => 0 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		// Default for extra elements, 5 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'starting_count' => 5 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][4][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][5][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'starting_count' => 5 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][1][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][2][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'starting_count' => 5 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][3][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][4][text]"', $str );

		// 0 extra elements, 5 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 0, 'starting_count' => 5 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][4][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][5][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 0, 'starting_count' => 5 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 0, 'starting_count' => 5 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		// 5 extra elements, 5 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 5, 'starting_count' => 5 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][4][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][5][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 5, 'starting_count' => 5 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][5][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][6][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 5, 'starting_count' => 5 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][7][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][8][text]"', $str );

		// 5 extra elements, default starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 5 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 5 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][5][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][6][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 5 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][7][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][8][text]"', $str );

		// 5 extra elements, 0 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 5, 'starting_count' => 0 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][0][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 5, 'starting_count' => 0 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][5][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][6][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 0, 'extra_elements' => 5, 'starting_count' => 0 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][7][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][8][text]"', $str );


		// Test limit of 3
		///////////////////////////////////////////////////

		// Defaults for extra elements and starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][1][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][2][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		// 0 extra elements, default starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 0 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 0 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 0 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		// default extra elements, 0 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'starting_count' => 0 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][0][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'starting_count' => 0 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][1][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][2][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'starting_count' => 0 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		// 0 extra elements, 0 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 0, 'starting_count' => 0 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][0][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 0, 'starting_count' => 0 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 0, 'starting_count' => 0 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		// Default for extra elements, 5 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'starting_count' => 5 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'starting_count' => 5 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][1][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][2][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'starting_count' => 5 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		// 0 extra elements, 5 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 0, 'starting_count' => 5 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 0, 'starting_count' => 5 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 0, 'starting_count' => 5 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		// 5 extra elements, 5 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 5, 'starting_count' => 5 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 5, 'starting_count' => 5 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 5, 'starting_count' => 5 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		// 5 extra elements, default starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 5 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][1][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 5 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 5 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		// 5 extra elements, 0 starting count
		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 5, 'starting_count' => 0 ) );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][0][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 5, 'starting_count' => 0 ), $test_data_single );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

		$str = $this->_get_html_for_extra_element_args( array( 'limit' => 3, 'extra_elements' => 5, 'starting_count' => 0 ), $test_data_multiple );
		$this->assertContains( 'name="base_group[test_element][proto][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][0][text]"', $str );
		$this->assertContains( 'name="base_group[test_element][2][text]"', $str );
		$this->assertNotContains( 'name="base_group[test_element][3][text]"', $str );

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

}
