<?php

/**
 * Tests the Post context
 */
class Test_Fieldmanager_Context_Term extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = TRUE;

		$this->taxonomy = 'category';
		$term = wp_insert_term( 'test', $this->taxonomy );
		$this->term_id = $term['term_id'];
		$this->tt_id = $term['term_taxonomy_id'];

		// reload as proper object
		$this->term = get_term( $this->term_id, $this->taxonomy );
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
					// 'index' => '_test_index',
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
							// 'index' => '_extext_index',
						) ),
					),
				) ),
			),
		) );
	}

	public function test_context_render_add_form() {
		$base = $this->_get_elements();
		ob_start();
		$base->add_term_form( 'test meta box', $this->taxonomy )->add_term_fields( $this->taxonomy );
		$str = ob_get_clean();
		// we can't really care about the structure of the HTML, but we can make sure that all fields are here
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $str );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="base_group\[test_basic\]"/', $str );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="base_group\[test_textfield\]"/', $str );
		$this->assertRegExp( '/<textarea[^>]+name="base_group\[test_htmlfield\]"/', $str );
		$this->assertContains( 'name="base_group[test_extended][0][extext][proto]"', $str );
		$this->assertContains( 'name="base_group[test_extended][0][extext][0]"', $str );
	}

	public function test_context_render_edit_form() {
		$base = $this->_get_elements();
		ob_start();
		$base->add_term_form( 'test meta box', $this->taxonomy )->edit_term_fields( $this->term, $this->taxonomy );
		$str = ob_get_clean();
		// we can't really care about the structure of the HTML, but we can make sure that all fields are here
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $str );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="base_group\[test_basic\]"/', $str );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="base_group\[test_textfield\]"/', $str );
		$this->assertRegExp( '/<textarea[^>]+name="base_group\[test_htmlfield\]"/', $str );
		$this->assertContains( 'name="base_group[test_extended][0][extext][proto]"', $str );
		$this->assertContains( 'name="base_group[test_extended][0][extext][0]"', $str );
	}

	public function test_context_save() {
		$base = $this->_get_elements();
		$test_data = $this->_get_valid_test_data();

		$base->add_term_form( 'test meta box', $this->taxonomy )->save_to_term_meta( $this->term_id, $this->taxonomy, $test_data['base_group'] );

		$saved_value = fm_get_term_meta( $this->term_id, $this->taxonomy, 'base_group', true );
		$saved_index = fm_get_term_meta( $this->term_id, $this->taxonomy, '_test_index', true );

		$this->assertEquals( $saved_value['test_basic'], 'lorem ipsum' );
		// $this->assertEquals( $saved_index, $saved_value['test_textfield'] );
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

	public function test_programmatic_save_terms() {
		$current_user = get_current_user_id();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$base = $this->_get_elements();
		$base->add_term_form( 'test meta box', $this->taxonomy );

		$term = wp_insert_term( 'test-2', $this->taxonomy );
		$this->assertTrue( $term['term_id'] > 0 );
		$this->assertTrue( $term['term_taxonomy_id'] > 0 );

		wp_update_term( $term['term_id'], $this->taxonomy, array( 'name' => 'Alley' ) );
		$updated_term = get_term( $term['term_id'], $this->taxonomy );
		$this->assertEquals( 'Alley', $updated_term->name );

		wp_set_current_user( $current_user );
	}
}