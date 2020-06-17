<?php

/**
 * Tests the Menu Item context
 *
 * @group context
 * @group menuitem
 */
class Test_Fieldmanager_Context_Menuitem extends WP_UnitTestCase {
	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->post = self::factory()->post->create_and_get( array(
			'post_type'    => 'nav_menu_item',
			'post_status'  => 'publish',
		) );
	}

	/**
	 * Get valid test data.
	 * Several tests transform this data to somehow be invalid.
	 *
	 * @return array valid test data
	 */
	private function _get_valid_test_data() {
		return array(
			'base_group' => array(
				'test_basic'     => 'lorem ipsum<script>alert(/hacked!/);</script>',
				'test_textfield' => 'alley interactive',
				'test_htmlfield' => '<b>Hello</b> world',
				'test_extended'  => array(
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
	 *
	 * @return Fieldmanager_Group
	 */
	private function _get_elements() {
		return new Fieldmanager_Group(
			array(
				'name'     => 'base_group',
				'children' => array(
					'test_basic'     => new Fieldmanager_TextField(),
					'test_textfield' => new Fieldmanager_TextField(
						array(
							'index' => '_test_index',
						)
					),
					'test_htmlfield' => new Fieldmanager_Textarea(
						array(
							'sanitize' => 'wp_kses_post',
						)
					),
					'test_extended'  => new Fieldmanager_Group(
						array(
							'limit'    => 4,
							'children' => array(
								'extext' => new Fieldmanager_TextField(
									array(
										'limit'    => 0,
										'name'     => 'extext',
										'one_label_per_item' => false,
										'sortable' => true,
										'index'    => '_extext_index',
									)
								),
							),
						)
					),
				),
			)
		);
	}

	public function test_context_render() {
		global $wp_version;

		// Only run these tests for WP versions above 5.4.0.
		if ( version_compare( $wp_version, '5.4.0', '<' ) ) {
			return;
		}

		$base = $this->_get_elements();
		ob_start();
		$base->add_nav_menu_fields()->add_fields( $this->post->ID );
		$str = ob_get_clean();
		// we can't really care about the structure of the HTML, but we can make sure that all fields are here
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group_fm-menu-item-id-' . $this->post->ID . '-nonce"/', $str );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="base_group_fm-menu-item-id-' . $this->post->ID . '\[test_basic\]"/', $str );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="base_group_fm-menu-item-id-' . $this->post->ID . '\[test_textfield\]"/', $str );
		$this->assertRegExp( '/<textarea[^>]+name="base_group_fm-menu-item-id-' . $this->post->ID . '\[test_htmlfield\]"/', $str );
		$this->assertContains( 'name="base_group_fm-menu-item-id-' . $this->post->ID . '[test_extended][0][extext][proto]"', $str );
		$this->assertContains( 'name="base_group_fm-menu-item-id-' . $this->post->ID . '[test_extended][0][extext][0]"', $str );
	}

	public function test_context_save() {
		global $wp_version;

		// Only run these tests for WP versions above 5.4.0.
		if ( version_compare( $wp_version, '5.4.0', '<' ) ) {
			return;
		}

		$base      = $this->_get_elements();
		$test_data = $this->_get_valid_test_data();

		$base->add_nav_menu_fields()->save_to_post_meta( $this->post->ID, $test_data['base_group'] );

		$saved_value = get_post_meta( $this->post->ID, 'base_group', true );
		$saved_index = get_post_meta( $this->post->ID, '_test_index', true );

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
}
