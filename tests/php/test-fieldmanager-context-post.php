<?php

/**
 * Tests the Post context
 *
 * @group context
 * @group post
 */
class Test_Fieldmanager_Context_Post extends WP_UnitTestCase {
	/**
	 * The post ID of the test post.
	 *
	 * @var int
	 */
	private int $post_id = 0;

	/**
	 * The post object of the test post.
	 *
	 * @var WP_Post
	 */
	private WP_Post $post;

	public function set_up() {
		parent::set_up();
		Fieldmanager_Field::$debug = true;

		// Create a post and capture it.
		$this->post = $this->factory->post->create_and_get(
			[
				'post_status'  => 'publish',
				'post_content' => rand_str(),
				'post_title'   => rand_str(),
			]
		);

		// Store the post ID.
		$this->post_id = $this->post->ID;
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
		$base = $this->_get_elements();
		ob_start();
		$base->add_meta_box( 'test meta box', 'post' )->render_meta_box( $this->post, array() );
		$str = ob_get_clean();
		// we can't really care about the structure of the HTML, but we can make sure that all fields are here
		$this->assertMatchesRegularExpression( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $str );
		$this->assertMatchesRegularExpression( '/<input[^>]+type="text"[^>]+name="base_group\[test_basic\]"/', $str );
		$this->assertMatchesRegularExpression( '/<input[^>]+type="text"[^>]+name="base_group\[test_textfield\]"/', $str );
		$this->assertMatchesRegularExpression( '/<textarea[^>]+name="base_group\[test_htmlfield\]"/', $str );
		$this->assertStringContainsString('name="base_group[test_extended][0][extext][proto]"', $str );
		$this->assertStringContainsString('name="base_group[test_extended][0][extext][0]"', $str );
	}

	public function test_context_save() {
		$base      = $this->_get_elements();
		$test_data = $this->_get_valid_test_data();

		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data['base_group'] );

		$saved_value = get_post_meta( $this->post_id, 'base_group', true );
		$saved_index = get_post_meta( $this->post_id, '_test_index', true );

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
