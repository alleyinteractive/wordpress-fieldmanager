<?php

/**
 * Tests the Quickedit context
 *
 * @group context
 * @group quickedit
 */
class Test_Fieldmanager_Context_Quickedit extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

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
				'test_basic' => 'lorem ipsum<script>alert(/hacked!/);</script>',
				'test_textfield' => 'alley interactive',
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

	private function _get_context( $fm ) {
		$context = $fm->add_quickedit_box( 'test meta box', 'post', array( $this, '_quickedit_column' ), 'Custom Column' );

		// The QuickEdit context absolutely requires we be in the edit.php
		// context, so we have to kind of fake it.
		$context->post_types = array( 'post' );
		$context->title = 'test meta box';
		$context->column_title = 'Custom Column';
		$context->column_display_callback = array( $this, '_quickedit_column' );
		$context->fm = $fm;

		return $context;
	}

	/**
	 * Get a set of elements
	 * @return Fieldmanager_Group
	 */
	private function _get_elements() {
		return new Fieldmanager_Group( array(
			'name' => 'base_group',
			'children' => array(
				'test_basic' => new Fieldmanager_TextField(),
				'test_textfield' => new Fieldmanager_TextField( array(
					'index' => '_test_index',
				) ),
				'test_htmlfield' => new Fieldmanager_Textarea( array(
					'sanitize' => 'wp_kses_post',
				) ),
				'test_extended' => new Fieldmanager_Group( array(
					'limit' => 4,
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
			),
		) );
	}

	private function _get_html_for( $field, $test_data = null ) {
		ob_start();
		$context = $this->_get_context( $field );
		if ( $test_data ) {
			$context->save_to_post_meta( $this->post_id, $test_data );
		}
		$get = $_GET;
		$_GET = array(
			'action'      => 'fm_quickedit_render',
			'post_id'     => $this->post_id,
			'column_name' => $field->name,
		);
		$context->render_ajax_form();
		$_GET = $get;
		return ob_get_clean();
	}

	public function test_context_render() {
		$base = $this->_get_elements();
		$context = $this->_get_context( $base );

		ob_start();
		$context->add_quickedit_box( 'base_group', 'post' );
		$str = ob_get_clean();

		// we can't really care about the structure of the HTML, but we can make sure that all fields are here
		$this->assertContains( 'fm-quickedit', $str );
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $str );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="base_group\[test_basic\]"/', $str );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="base_group\[test_textfield\]"/', $str );
		$this->assertRegExp( '/<textarea[^>]+name="base_group\[test_htmlfield\]"/', $str );
		$this->assertContains( 'name="base_group[test_extended][0][extext][proto]"', $str );
		$this->assertContains( 'name="base_group[test_extended][0][extext][0]"', $str );
	}

	public function test_title() {
		$context = $this->_get_context( $this->_get_elements() );
		$context->title = rand_str();

		ob_start();
		$context->add_quickedit_box( 'base_group', 'post' );
		$str = ob_get_clean();

		$this->assertRegExp( "/<h4[^>]*>{$context->title}<\/h4>/", $str );

		$context->title = false;
		ob_start();
		$context->add_quickedit_box( 'base_group', 'post' );
		$str = ob_get_clean();

		$this->assertNotRegExp( '/<\/h4>/', $str );
	}

	public function test_context_save() {
		$base = $this->_get_elements();
		$test_data = $this->_get_valid_test_data();

		$context = $this->_get_context( $base );
		$context->save_to_post_meta( $this->post_id, $test_data['base_group'] );

		$saved_value = get_post_meta( $this->post_id, 'base_group', true );
		$saved_index = get_post_meta( $this->post_id, '_test_index', TRUE );

		$this->assertEquals( $saved_value['test_basic'], 'lorem ipsum' );
		$this->assertEquals( $saved_index, $saved_value['test_textfield'] );
		$this->assertEquals( $saved_value['test_textfield'], 'alley interactive' );
		$this->assertEquals( $saved_value['test_htmlfield'], '<b>Hello</b> world' );
		$this->assertEquals( count( $saved_value['test_extended'] ), 4 );
		$this->assertEquals( count( $saved_value['test_extended'][0]['extext'] ), 1 );
		$this->assertEquals( count( $saved_value['test_extended'][1]['extext'] ), 3 );
		$this->assertEquals( count( $saved_value['test_extended'][2]['extext'] ), 1 );
		$this->assertEquals( count( $saved_value['test_extended'][3]['extext'] ), 1 );
		$this->assertEquals( $saved_value['test_extended'][1]['extext'], array( 'second1', 'second2', 'second3' ) );
		$this->assertEquals( $saved_value['test_extended'][3]['extext'][0], 'fourth' );
	}

	public function _quickedit_column( $post_id, $data ) {
		return ! empty( $data['text'] ) ? $data['text'] : 'not set';
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_single_field() {
		$base = new Fieldmanager_TextField( array(
			'name'           => 'base_field',
			'limit'          => 0,
			'serialize_data' => false,
		) );
		$html = $this->_get_html_for( $base );
		$this->assertContains( 'name="base_field[0]"', $html );
		$this->assertNotContains( 'name="base_field[3]"', $html );

		$data = array( rand_str(), rand_str(), rand_str() );
		$html = $this->_get_html_for( $base, $data );
		$this->assertEquals( $data, get_post_meta( $this->post_id, 'base_field' ) );
		$this->assertContains( 'name="base_field[3]"', $html );
		$this->assertContains( 'value="' . $data[0] . '"', $html );
		$this->assertContains( 'value="' . $data[1] . '"', $html );
		$this->assertContains( 'value="' . $data[2] . '"', $html );
		$this->assertNotContains( 'name="base_field[4]"', $html );
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

		// Test as 1, 2, 3
		$data = array( $item_1, $item_2, $item_3 );
		$html = $this->_get_html_for( $base, $data );
		$this->assertEquals( $data, get_post_meta( $this->post_id, 'base_field' ) );
		$this->assertRegExp( '/<input[^>]+name="base_field\[0\][^>]+value="' . $item_1 . '"/', $html );
		$this->assertRegExp( '/<input[^>]+name="base_field\[1\][^>]+value="' . $item_2 . '"/', $html );
		$this->assertRegExp( '/<input[^>]+name="base_field\[2\][^>]+value="' . $item_3 . '"/', $html );

		// Reorder and test as 3, 1, 2
		$data = array( $item_3, $item_1, $item_2 );
		$html = $this->_get_html_for( $base, $data );
		$this->assertEquals( $data, get_post_meta( $this->post_id, 'base_field' ) );
		$this->assertRegExp( '/<input[^>]+name="base_field\[0\][^>]+value="' . $item_3 . '"/', $html );
		$this->assertRegExp( '/<input[^>]+name="base_field\[1\][^>]+value="' . $item_1 . '"/', $html );
		$this->assertRegExp( '/<input[^>]+name="base_field\[2\][^>]+value="' . $item_2 . '"/', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_tabbed() {
		$base = new Fieldmanager_Group( array(
			'name'           => 'base_group',
			'tabbed'         => true,
			'serialize_data' => false,
			'add_to_prefix'  => false,
			'children'       => array(
				'tab-1' => new Fieldmanager_Group( array(
					'label'          => 'Tab One',
					'serialize_data' => false,
					'add_to_prefix'  => false,
					'children'       => array(
						'test_text' => new Fieldmanager_TextField( 'Text Field' ),
					)
				) ),
				'tab-2' => new Fieldmanager_Group( array(
					'label'          => 'Tab Two',
					'serialize_data' => false,
					'add_to_prefix'  => false,
					'children'       => array(
						'test_textarea' => new Fieldmanager_TextArea( 'TextArea' ),
					)
				) ),
			)
		) );
		$data = array(
			'tab-1' => array(
				'test_text' => rand_str()
			),
			'tab-2' => array(
				'test_textarea' => rand_str()
			),
		);

		$html = $this->_get_html_for( $base, $data );
		$this->assertEquals( $data['tab-1']['test_text'], get_post_meta( $this->post_id, 'test_text', true ) );
		$this->assertEquals( $data['tab-2']['test_textarea'], get_post_meta( $this->post_id, 'test_textarea', true ) );
		$this->assertContains( 'name="base_group[tab-1][test_text]"', $html );
		$this->assertContains( 'value="' . $data['tab-1']['test_text'] . '"', $html );
		$this->assertContains( 'name="base_group[tab-2][test_textarea]"', $html );
		$this->assertContains( '>' . $data['tab-2']['test_textarea'] . '</textarea>', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_mixed_depth() {
		$base = new Fieldmanager_Group( array(
			'name'           => 'base_group',
			'serialize_data' => false,
			'children'       => array(
				'test_text' => new Fieldmanager_TextField,
				'test_group' => new Fieldmanager_Group( array(
					'serialize_data' => false,
					'children'       => array(
						'deep_text' => new Fieldmanager_TextArea,
					)
				) ),
			)
		) );

		$data = array(
			'test_text' => rand_str(),
			'test_group' => array(
				'deep_text' => rand_str()
			),
		);

		$html = $this->_get_html_for( $base, $data );
		$this->assertEquals( $data['test_text'], get_post_meta( $this->post_id, 'base_group_test_text', true ) );
		$this->assertEquals( $data['test_group']['deep_text'], get_post_meta( $this->post_id, 'base_group_test_group_deep_text', true ) );
		$this->assertContains( 'name="base_group[test_text]"', $html );
		$this->assertContains( 'value="' . $data['test_text'] . '"', $html );
		$this->assertContains( 'name="base_group[test_group][deep_text]"', $html );
		$this->assertContains( '>' . $data['test_group']['deep_text'] . '</textarea>', $html );
	}
}