<?php

/**
 * Tests the Fieldmanager Select Field
 *
 * @group field
 * @group select
 */
class Test_Fieldmanager_Select_Field extends WP_UnitTestCase {
	public $post_id;
	public $post;
	public $custom_datasource;

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->post_id = $this->factory->post->create( array( 'post_title' => rand_str(), 'post_date' => '2009-07-01 00:00:00' ) );
		$this->post = get_post( $this->post_id );

		$this->custom_datasource = new Fieldmanager_Datasource( array(
			'options' => array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' )
		) );
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

	public function test_basic_render() {
		$fm = new Fieldmanager_Select( array(
			'name' => 'base_field',
			'options' => array( 'one', 'two', 'three' ),
		) );

		$html = $this->_get_html_for( $fm );
		$this->assertRegExp(
			'#<select[^>]+>'
			. '\s*<option\s*value="one"\s*>one</option>'
			. '\s*<option\s*value="two"\s*>two</option>'
			. '\s*<option\s*value="three"\s*>three</option>'
			. '\s*</select>#si',
			$html
		);

		$this->assertNotContains( '<option value="">&nbsp;</option>', $html );
	}

	public function test_basic_save() {
		$fm = new Fieldmanager_Select( array(
			'name' => 'base_field',
			'options' => array( 'one', 'two', 'three' ),
		) );

		$html = $this->_get_html_for( $fm, array( 'two' ) );
		$this->assertRegExp(
			'#<select[^>]+>'
			. '\s*<option\s*value="one"\s*>one</option>'
			. '\s*<option\s*value="two"\s*selected>two</option>'
			. '\s*<option\s*value="three"\s*>three</option>'
			. '\s*</select>#si',
			$html
		);
	}

	public function test_associative_render() {
		$fm = new Fieldmanager_Select( array(
			'name' => 'base_field',
			'options' => array( 1 => 'one', 2 => 'two', 3 => 'three' ),
		) );

		$html = $this->_get_html_for( $fm );
		$this->assertRegExp(
			'#<select[^>]+>'
			. '\s*<option\s*value="1"\s*>one</option>'
			. '\s*<option\s*value="2"\s*>two</option>'
			. '\s*<option\s*value="3"\s*>three</option>'
			. '\s*</select>#si',
			$html
		);

		$html = $this->_get_html_for( $fm, array( 2 ) );
		$this->assertRegExp(
			'#<select[^>]+>'
			. '\s*<option\s*value="1"\s*>one</option>'
			. '\s*<option\s*value="2"\s*selected>two</option>'
			. '\s*<option\s*value="3"\s*>three</option>'
			. '\s*</select>#si',
			$html
		);
	}

	public function test_multiselect_render() {
		$fm = new Fieldmanager_Select( array(
			'name' => 'base_field',
			'multiple' => true,
			'options' => array( 'one', 'two', 'three' ),
		) );

		$html = $this->_get_html_for( $fm );
		$this->assertRegExp(
			'#<select[^>]+multiple[^>]*>'
			. '\s*<option\s*value="one"\s*>one</option>'
			. '\s*<option\s*value="two"\s*>two</option>'
			. '\s*<option\s*value="three"\s*>three</option>'
			. '\s*</select>#si',
			$html
		);
	}

	public function test_multiselect_save() {
		$fm = new Fieldmanager_Select( array(
			'name' => 'base_field',
			'multiple' => true,
			'options' => array( 'one', 'two', 'three' ),
		) );

		$html = $this->_get_html_for( $fm, array( 'two' ) );
		$this->assertRegExp(
			'#<select[^>]+multiple[^>]*>'
			. '\s*<option\s*value="one"\s*>one</option>'
			. '\s*<option\s*value="two"\s*selected>two</option>'
			. '\s*<option\s*value="three"\s*>three</option>'
			. '\s*</select>#si',
			$html
		);

		$html = $this->_get_html_for( $fm, array( 'two', 'three' ) );
		$this->assertRegExp(
			'#<select[^>]+multiple[^>]*>'
			. '\s*<option\s*value="one"\s*>one</option>'
			. '\s*<option\s*value="two"\s*selected>two</option>'
			. '\s*<option\s*value="three"\s*selected>three</option>'
			. '\s*</select>#si',
			$html
		);
	}

	public function test_repeatable_multiselect_save() {
		$fm = new Fieldmanager_Select( array(
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

	public function test_repeatable_render() {
		$fm = new Fieldmanager_Select( array(
			'name' => 'base_field',
			'limit' => 0,
			'options' => array( 'one', 'two', 'three' ),
		) );

		$html = $this->_get_html_for( $fm );
		$this->assertEquals( 2, preg_match_all( '#<select[^>]+>.*?</select>#si', $html, $matches ) );
		$this->assertEquals( 2, substr_count( $html, '<option value="one" >one</option>' ) );

		// @see https://github.com/alleyinteractive/wordpress-fieldmanager/issues/150
		$this->assertContains( '<option value="">&nbsp;</option>', $html );

		$html = $this->_get_html_for( $fm, array( 'three', 'one' ) );
		$this->assertEquals( 4, preg_match_all( '#<select[^>]+>.*?</select>#si', $html, $matches ) );
		$this->assertEquals( 3, substr_count( $html, '<option value="one" >one</option>' ) );
		$this->assertEquals( 1, substr_count( $html, '<option value="one" selected>one</option>' ) );
		$this->assertEquals( 4, substr_count( $html, '<option value="two" >two</option>' ) );
		$this->assertEquals( 3, substr_count( $html, '<option value="three" >three</option>' ) );
		$this->assertEquals( 1, substr_count( $html, '<option value="three" selected>three</option>' ) );
	}

	public function test_repeatable_child_first_empty() {
		$fm = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'limit' => 0,
			'children' => array(
				'subgroup' => new Fieldmanager_Group( array(
					'children' => array(
						'select' => new Fieldmanager_Select( array(
							'options' => array( 'one', 'two', 'three' ),
						) )
					)
				) ),
			),
		) );

		$html = $this->_get_html_for( $fm );
		$this->assertEquals( 2, preg_match_all(
			'#<select[^>]+>'
			. '\s*<option value="">&nbsp;</option>'
			. '\s*<option\s*value="one"\s*>one</option>'
			. '\s*<option\s*value="two"\s*>two</option>'
			. '\s*<option\s*value="three"\s*>three</option>'
			. '\s*</select>#si',
			$html,
			$matches
		) );
	}

	public function test_first_empty() {
		$fm = new Fieldmanager_Select( array(
			'name' => 'base_field',
			'options' => array( 'one', 'two', 'three' ),
			'first_empty' => true,
		) );

		$html = $this->_get_html_for( $fm );
		$this->assertRegExp(
			'#<select[^>]+>'
			. '\s*<option value="">&nbsp;</option>'
			. '\s*<option value="one"\s*>one</option>'
			. '\s*<option value="two"\s*>two</option>'
			. '\s*<option value="three"\s*>three</option>'
			. '\s*</select>#si',
			$html
		);
	}

	public function test_default_value() {
		$fm = new Fieldmanager_Select( array(
			'name' => 'base_field',
			'options' => array( 'one', 'two', 'three' ),
			'default_value' => 'two',
		) );

		$html = $this->_get_html_for( $fm );
		$this->assertRegExp(
			'#<select[^>]+>'
			. '\s*<option value="one"\s*>one</option>'
			. '\s*<option value="two"\s*selected>two</option>'
			. '\s*<option value="three"\s*>three</option>'
			. '\s*</select>#si',
			$html
		);
	}

	public function test_datasource() {
		$fm = new Fieldmanager_Select( array(
			'name' => 'base_field',
			'datasource' => $this->custom_datasource,
		) );

		$html = $this->_get_html_for( $fm );
		$this->assertRegExp(
			'#<select[^>]+>'
			. '\s*<option\s*value="January"\s*>January</option>'
			. '\s*<option\s*value="February"\s*>February</option>'
			. '(\s*<option\s*value="\w+"\s*>\w+</option>){9}'
			. '\s*<option\s*value="December"\s*>December</option>'
			. '\s*</select>#si',
			$html
		);
	}

	public function test_datasource_default_value() {
		$fm = new Fieldmanager_Select( array(
			'name' => 'base_field',
			'default_value' => 'February',
			'datasource' => $this->custom_datasource,
		) );

		$html = $this->_get_html_for( $fm );
		$this->assertRegExp(
			'#<select[^>]+>'
			. '\s*<option\s*value="January"\s*>January</option>'
			. '\s*<option\s*value="February"\s*selected>February</option>'
			. '(\s*<option\s*value="\w+"\s*>\w+</option>){9}'
			. '\s*<option\s*value="December"\s*>December</option>'
			. '\s*</select>#si',
			$html
		);
	}

	public function test_datasource_save() {
		$fm = new Fieldmanager_Select( array(
			'name' => 'base_field',
			'datasource' => $this->custom_datasource,
		) );

		$html = $this->_get_html_for( $fm, 'February' );
		$this->assertRegExp(
			'#<select[^>]+>'
			. '\s*<option\s*value="January"\s*>January</option>'
			. '\s*<option\s*value="February"\s*selected>February</option>'
			. '(\s*<option\s*value="\w+"\s*>\w+</option>){9}'
			. '\s*<option\s*value="December"\s*>December</option>'
			. '\s*</select>#si',
			$html
		);
	}

}
