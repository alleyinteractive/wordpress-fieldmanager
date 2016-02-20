<?php

/**
 * Tests the Post context
 *
 * @group context
 * @group zoninator
 */
class Test_Fieldmanager_Context_Zoninator extends WP_UnitTestCase {

	public $post;

	public $zone;

	public $z;

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->z = new Zoninator();
		$this->z->init();

		$this->post = $this->factory->post->create_and_get( array(
			'post_status' => 'publish',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		) );

		$this->zone = $this->factory->term->create_and_get( array( 
			'name' => rand_str(), 
			'taxonomy' => $this->z->zone_taxonomy 
		) );

		$this->z->add_zone_posts( $this->zone, $this->post );
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


	public function test_context_render() {
		$base = $this->_get_elements();
		$zone_flag = 'zone-post-' . $this->post->ID;
		ob_start();
		$base->add_zoninator_post_form( 'test meta box', 'post' )->zone_post_info( $this->post, array() );
		$str = ob_get_clean();
		// we can't really care about the structure of the HTML, but we can make sure that all fields are here
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $str );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="base_group-' . $zone_flag . '\[test_basic\]"/', $str );
		$this->assertRegExp( '/<input[^>]+type="text"[^>]+name="base_group-' . $zone_flag . '\[test_textfield\]"/', $str );
		$this->assertRegExp( '/<textarea[^>]+name="base_group-' . $zone_flag . '\[test_htmlfield\]"/', $str );
		$this->assertContains( 'name="base_group-' . $zone_flag . '[test_extended][0][extext][proto]"', $str );
		$this->assertContains( 'name="base_group-' . $zone_flag . '[test_extended][0][extext][0]"', $str );
	}

	public function test_context_save() {
		$base = $this->_get_elements();
		$test_data = $this->_get_valid_test_data();

		$base->add_zoninator_post_form( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, $test_data['base_group'] );

		$saved_value = get_post_meta( $this->post->ID, 'base_group', true );
		$saved_index = get_post_meta( $this->post->ID, '_test_index', TRUE );

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

	public function test_programmatic_save_posts() {
		$base = $this->_get_elements();
		$base->add_zoninator_post_form( 'test meta box', 'post' );

		$post_id = wp_insert_post( array( 'post_type' => 'post', 'post_name' => 'test-post', 'post_title' => 'Test Post', 'post_date' => '2012-10-25 12:34:56' ) );
		wp_update_post( array( 'ID' => $post_id, 'post_content' => 'Lorem ipsum dolor sit amet.' ) );
	}
}