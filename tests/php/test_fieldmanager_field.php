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
			'test_textfield' => new Fieldmanager_Textfield( array(
				'name' => 'test_textfield',
				'validate' => array(
					function( $value ) {
						return strlen( $value ) > 2;
					}
				),
				'index' => '_test_index',
			) ),
			'test_htmlfield' => new Fieldmanager_Textarea( array(
				'name' => 'test_textfield',
				'sanitize' => 'wp_kses_post',
			) ),
			'test_numfield' => new Fieldmanager_Textfield( array(
				'name' => 'test_numfield',
				'validate' => array( 'is_numeric' ),
			) ),
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
					'extext' => new Fieldmanager_Textfield( array( 
						'limit' => 0,
						'name' => 'extext',
						'one_label_per_item' => False,
						'sortable' => True,
					) ),
				),
			) ),
		);
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
		$base->add_meta_box( 'test meta box', $post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
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
		$base->add_meta_box( 'test meta box', $post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
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
		$base->add_meta_box( 'test meta box', $post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
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
		$base->add_meta_box( 'test meta box', $post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
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
		$base->add_meta_box( 'test meta box', $post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
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
		$base->add_meta_box( 'test meta box', $post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
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
		$base->add_meta_box( 'test meta box', $post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
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
		$base->add_meta_box( 'test meta box', $post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
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

		$base->add_meta_box( 'test meta box', $post )->save_to_post_meta( $this->post_id, $test_data['base_group'] );
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
		$base->add_meta_box( 'test meta box', $post )->render_meta_box( $this->post, array() );
		$str = ob_get_clean();
		// we can't really care about the structure of the HTML, but we can make sure that all fields are here
		$this->assertContains( 'name="fieldmanager-base_group-nonce"', $str );
		$this->assertContains( 'name="base_group[test_textfield]"', $str );
		$this->assertContains( 'name="base_group[test_numfield]"', $str );
		$this->assertContains( 'name="base_group[test_extended][0][extext][proto]"', $str );
	}

}