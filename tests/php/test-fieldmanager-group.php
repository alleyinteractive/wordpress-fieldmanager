<?php

/**
 * Tests the Fieldmanager Datasource Post
 *
 * @group field
 * @group group
 */
class Test_Fieldmanager_Group extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->post = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		) );
		$this->post_id = $this->post->ID;
	}

	public function tearDown() {
		$meta = get_post_meta( $this->post_id );
		foreach ( $meta as $key => $value ) {
			delete_post_meta( $this->post_id, $key );
		}
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
	 * Test what happens when setting and changing values in a nested repeatable group.
	 */
	public function test_repeat_subgroups() {
		$base = new Fieldmanager_Group( array(
			'name' => 'base_group',
			'limit' => 0,
			'children' => array(
				'sub' => new Fieldmanager_Group( array(
					'name' => 'sub',
					'limit' => 0,
					'children' => array(
						'repeat' => new Fieldmanager_TextField( array(
							'limit' => 0,
							'name' => 'repeat',
						) ),
					),
				) ),
			),
		) );
		$data = array(
			array( 'sub' => array(
				array( 'repeat' => array( 'a', 'b', 'c' ) ),
				array( 'repeat' => array( '1', '2', '3' ) ),
			) ),
			array( 'sub' => array(
				array( 'repeat' => array( '1', '2', '3', '4' ) ),
			) ),
		);
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post->ID, $data );
		$saved_value = get_post_meta( $this->post->ID, 'base_group', true );

		$this->assertEquals( 2, count( $saved_value ) );
		$this->assertEquals( 2, count( $saved_value[0]['sub'] ) );
		$this->assertEquals( 1, count( $saved_value[1]['sub'] ) );
		$this->assertEquals( $data[0]['sub'][0]['repeat'], $saved_value[0]['sub'][0]['repeat'] );
		$this->assertEquals( $data[0]['sub'][1]['repeat'], $saved_value[0]['sub'][1]['repeat'] );
		$this->assertEquals( $data[1]['sub'][0]['repeat'], $saved_value[1]['sub'][0]['repeat'] );

		$data = array(
			array( 'sub' => array(
				array( 'repeat' => array( '1', '2', '3', '4' ) ),
			) ),
			array( 'sub' => array(
				array( 'repeat' => array( 'a', 'b', 'c' ) ),
				array( 'repeat' => array( '1', '2', '3' ) ),
			) ),
		);
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post->ID, $data );
		$saved_value = get_post_meta( $this->post->ID, 'base_group', true );

		$this->assertEquals( 2, count( $saved_value ) );
		$this->assertEquals( 1, count( $saved_value[0]['sub'] ) );
		$this->assertEquals( 2, count( $saved_value[1]['sub'] ) );
		$this->assertEquals( $data[0]['sub'][0]['repeat'], $saved_value[0]['sub'][0]['repeat'] );
		$this->assertEquals( $data[1]['sub'][0]['repeat'], $saved_value[1]['sub'][0]['repeat'] );
		$this->assertEquals( $data[1]['sub'][1]['repeat'], $saved_value[1]['sub'][1]['repeat'] );
	}

	/*
	 * Test building and saving a nested group
	 */
	public function test_saving_nested_groups() {

		$meta_group = new \Fieldmanager_Group( '', array(
			'name'        => 'distribution',
			'tabbed'      => true,
			) );

		$social_group = new \Fieldmanager_Group( 'Social', array(
			'name'        => 'social',
			) );
		$social_group->add_child( new \Fieldmanager_Group( 'Twitter', array(
			'name'                    => 'twitter',
			'children'                => array(
				'share_text'          => new \Fieldmanager_TextArea( 'Sharing Text', array(
					'description'     => 'What text would you like the user to include in their tweet? (Defaults to title)',
					'attributes'      => array(
						'style'           => 'width:100%',
						)
					) )
				),
			) ) );

		$meta_group->add_child( $social_group );
		$meta_group->add_meta_box( 'Distribution', array( 'post' ) );

		$meta_group->presave( array(
				'social'         => array(
					'twitter'    => array(
						'share_text'      => 'This is my sample share text'
						),
					),
			) );
	}

	public function test_tabbed_group() {
		$base = new Fieldmanager_Group( array(
			'name'           => 'base_group',
			'tabbed'         => true,
			'children'       => array(
				'tab-1' => new Fieldmanager_Group( array(
					'label'          => 'Tab One',
					'children'       => array(
						'test_text' => new Fieldmanager_TextField( 'Text Field' ),
					)
				) ),
				'tab-2' => new Fieldmanager_Group( array(
					'label'          => 'Tab Two',
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
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data, get_post_meta( $this->post_id, 'base_group', true ) );
		$html = $this->_get_html_for( $base );
		$this->assertContains( 'fm-tab-bar', $html );
		$this->assertContains( 'name="base_group[tab-1][test_text]"', $html );
		$this->assertContains( 'value="' . $data['tab-1']['test_text'] . '"', $html );
		$this->assertContains( 'name="base_group[tab-2][test_textarea]"', $html );
		$this->assertContains( '>' . $data['tab-2']['test_textarea'] . '</textarea>', $html );
		$this->assertNotContains( 'fm-tabbed-vertical', $html );
	}

	public function test_vertical_tabbed_group() {
		$base = new Fieldmanager_Group( array(
			'name'     => 'base_group',
			'tabbed'   => 'vertical',
			'children' => array(
				'tab-1' => new Fieldmanager_Group( array(
					'label'    => 'Tab One',
					'children' => array(
						'test_text' => new Fieldmanager_TextField( 'Text Field' ),
					)
				) ),
				'tab-2' => new Fieldmanager_Group( array(
					'label'    => 'Tab Two',
					'children' => array(
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
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data, get_post_meta( $this->post_id, 'base_group', true ) );
		$html = $this->_get_html_for( $base );
		$this->assertContains( 'fm-tab-bar', $html );
		$this->assertContains( 'name="base_group[tab-1][test_text]"', $html );
		$this->assertContains( 'value="' . $data['tab-1']['test_text'] . '"', $html );
		$this->assertContains( 'name="base_group[tab-2][test_textarea]"', $html );
		$this->assertContains( '>' . $data['tab-2']['test_textarea'] . '</textarea>', $html );
		$this->assertContains( 'fm-tabbed-vertical', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_group_render() {
		$args = array(
			'name'           => 'base_group',
			'children'       => array(
				'test_basic' => new Fieldmanager_TextField(),
				'test_htmlfield' => new Fieldmanager_Textarea( array(
					'sanitize' => 'wp_kses_post',
				) ),
			)
		);

		$base = new Fieldmanager_Group( $args );
		$html = $this->_get_html_for( $base );
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $html );
		$this->assertContains( 'name="base_group[test_basic]"', $html );
		$this->assertContains( 'name="base_group[test_htmlfield]"', $html );

		// Using serialize_data => false shouldn't change anything
		$base = new Fieldmanager_Group( array_merge( $args, array( 'serialize_data' => false ) ) );
		$html = $this->_get_html_for( $base );
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $html );
		$this->assertContains( 'name="base_group[test_basic]"', $html );
		$this->assertContains( 'name="base_group[test_htmlfield]"', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_group_render_with_data() {
		$args = array(
			'name'           => 'base_group',
			'children'       => array(
				'test_basic' => new Fieldmanager_TextField(),
				'test_htmlfield' => new Fieldmanager_Textarea( array(
					'sanitize' => 'wp_kses_post',
				) ),
			)
		);
		$data = array(
			'test_basic'     => rand_str(),
			'test_htmlfield' => rand_str()
		);

		update_post_meta( $this->post_id, 'base_group', $data );
		$base = new Fieldmanager_Group( $args );
		$html = $this->_get_html_for( $base );
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $html );
		$this->assertContains( 'name="base_group[test_basic]"', $html );
		$this->assertContains( 'value="' . $data['test_basic'] . '"', $html );
		$this->assertContains( 'name="base_group[test_htmlfield]"', $html );
		$this->assertContains( ">{$data['test_htmlfield']}</textarea>", $html );
		delete_post_meta( $this->post_id, 'base_group' );

		// Using serialize_data => false requires a different data storage
		foreach ( $data as $meta_key => $meta_value ) {
			add_post_meta( $this->post_id, "base_group_{$meta_key}", $meta_value );
		}
		$base = new Fieldmanager_Group( array_merge( $args, array( 'serialize_data' => false ) ) );
		$html = $this->_get_html_for( $base );
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $html );
		$this->assertContains( 'name="base_group[test_basic]"', $html );
		$this->assertContains( 'value="' . $data['test_basic'] . '"', $html );
		$this->assertContains( 'name="base_group[test_htmlfield]"', $html );
		$this->assertContains( ">{$data['test_htmlfield']}</textarea>", $html );
		foreach ( $data as $meta_key => $meta_value ) {
			delete_post_meta( $this->post_id, "base_group_{$meta_key}" );
		}

		// Here, we'll set 'add_to_prefix' => false so the group name is not
		// included in the meta keys.
		foreach ( $data as $meta_key => $meta_value ) {
			add_post_meta( $this->post_id, $meta_key, $meta_value );
		}
		$base = new Fieldmanager_Group( array_merge( $args, array( 'serialize_data' => false, 'add_to_prefix' => false ) ) );
		$html = $this->_get_html_for( $base );
		$this->assertRegExp( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $html );
		$this->assertContains( 'name="base_group[test_basic]"', $html );
		$this->assertContains( 'value="' . $data['test_basic'] . '"', $html );
		$this->assertContains( 'name="base_group[test_htmlfield]"', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_group_save() {
		$args = array(
			'name'           => 'base_group',
			'serialize_data' => false,
			'children'       => array(
				'test_basic' => new Fieldmanager_TextField(),
				'test_htmlfield' => new Fieldmanager_Textarea( array(
					'sanitize' => 'wp_kses_post',
				) ),
			)
		);
		$data = array(
			'test_basic'     => rand_str(),
			'test_htmlfield' => rand_str()
		);
		$base = new Fieldmanager_Group( $args );
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data['test_basic'], get_post_meta( $this->post_id, 'base_group_test_basic', true ) );
		$this->assertEquals( $data['test_htmlfield'], get_post_meta( $this->post_id, 'base_group_test_htmlfield', true ) );

		$base = new Fieldmanager_Group( array_merge( $args, array( 'add_to_prefix' => false ) ) );
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data['test_basic'], get_post_meta( $this->post_id, 'test_basic', true ) );
		$this->assertEquals( $data['test_htmlfield'], get_post_meta( $this->post_id, 'test_htmlfield', true ) );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_deep_group_no_prefix() {
		$base = new Fieldmanager_Group( array(
			'name'           => 'base_group',
			'serialize_data' => false,
			'add_to_prefix' => false,
			'children'       => array(
				'level2' => new Fieldmanager_Group( array(
					'serialize_data' => false,
					'add_to_prefix' => false,
					'children' => array(
						'level3' => new Fieldmanager_Group( array(
							'serialize_data' => false,
							'add_to_prefix' => false,
							'children' => array(
								'level4' => new Fieldmanager_Group( array(
									'serialize_data' => false,
									'add_to_prefix' => false,
									'children' => array(
										'field' => new Fieldmanager_TextField
									)
								) ),
							)
						) ),
					)
				) ),
			)
		) );

		$data = array(
			'level2' => array(
				'level3' => array(
					'level4' => array(
						'field' => rand_str()
					)
				)
			)
		);
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data['level2']['level3']['level4']['field'], get_post_meta( $this->post_id, 'field', true ) );
		$html = $this->_get_html_for( $base );
		$this->assertContains( 'name="base_group[level2][level3][level4][field]"', $html );
		$this->assertContains( 'value="' . $data['level2']['level3']['level4']['field'] . '"', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_deep_group_no_prefix_repeatable_field() {
		$base = new Fieldmanager_Group( array(
			'name'           => 'base_group',
			'serialize_data' => false,
			'add_to_prefix' => false,
			'children'       => array(
				'level2' => new Fieldmanager_Group( array(
					'serialize_data' => false,
					'add_to_prefix' => false,
					'children' => array(
						'level3' => new Fieldmanager_Group( array(
							'serialize_data' => false,
							'add_to_prefix' => false,
							'children' => array(
								'level4' => new Fieldmanager_Group( array(
									'serialize_data' => false,
									'add_to_prefix' => false,
									'children' => array(
										'field_one' => new Fieldmanager_TextField( array(
											'serialize_data' => false,
											'limit' => 0,
										) ),
										'field_two' => new Fieldmanager_TextField( array(
											'serialize_data' => false,
											'limit' => 0,
										) ),
									)
								) ),
							)
						) ),
					)
				) ),
			)
		) );

		$data = array(
			'level2' => array(
				'level3' => array(
					'level4' => array(
						'field_one' => array( rand_str(), rand_str(), rand_str() ),
						'field_two' => array( rand_str(), rand_str(), rand_str() ),
					)
				)
			)
		);
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data['level2']['level3']['level4']['field_one'], get_post_meta( $this->post_id, 'field_one' ) );
		$this->assertEquals( $data['level2']['level3']['level4']['field_two'], get_post_meta( $this->post_id, 'field_two' ) );
		$html = $this->_get_html_for( $base );
		$this->assertContains( 'name="base_group[level2][level3][level4][field_one][0]"', $html );
		$this->assertContains( 'value="' . $data['level2']['level3']['level4']['field_one'][0] . '"', $html );
		$this->assertContains( 'value="' . $data['level2']['level3']['level4']['field_one'][1] . '"', $html );
		$this->assertContains( 'value="' . $data['level2']['level3']['level4']['field_one'][2] . '"', $html );
		$this->assertContains( 'value="' . $data['level2']['level3']['level4']['field_two'][0] . '"', $html );
		$this->assertContains( 'value="' . $data['level2']['level3']['level4']['field_two'][1] . '"', $html );
		$this->assertContains( 'value="' . $data['level2']['level3']['level4']['field_two'][2] . '"', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_mid_group_prefix() {
		$base = new Fieldmanager_Group( array(
			'name'           => 'base_group',
			'serialize_data' => false,
			'children'       => array(
				'level2' => new Fieldmanager_Group( array(
					'serialize_data' => false,
					'children' => array(
						'level3' => new Fieldmanager_Group( array(
							'serialize_data' => false,
							'add_to_prefix' => false,
							'children' => array(
								'level4' => new Fieldmanager_Group( array(
									'serialize_data' => false,
									'children' => array(
										'field' => new Fieldmanager_TextField
									)
								) ),
							)
						) ),
					)
				) ),
			)
		) );

		$data = array(
			'level2' => array(
				'level3' => array(
					'level4' => array(
						'field' => rand_str()
					)
				)
			)
		);
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data['level2']['level3']['level4']['field'], get_post_meta( $this->post_id, 'base_group_level2_level4_field', true ) );
		$html = $this->_get_html_for( $base );
		$this->assertContains( 'name="base_group[level2][level3][level4][field]"', $html );
		$this->assertContains( 'value="' . $data['level2']['level3']['level4']['field'] . '"', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_mid_group_serialize() {
		$base = new Fieldmanager_Group( array(
			'name'           => 'base_group',
			'serialize_data' => false,
			'children'       => array(
				'level2' => new Fieldmanager_Group( array(
					'serialize_data' => false,
					'children' => array(
						'level3' => new Fieldmanager_Group( array(
							'children' => array(
								'level4' => new Fieldmanager_Group( array(
									'children' => array(
										'field' => new Fieldmanager_TextField
									)
								) ),
							)
						) ),
					)
				) ),
			)
		) );

		$data = array(
			'level2' => array(
				'level3' => array(
					'level4' => array(
						'field' => rand_str()
					)
				)
			)
		);
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data['level2']['level3'], get_post_meta( $this->post_id, 'base_group_level2_level3', true ) );
		$html = $this->_get_html_for( $base );
		$this->assertContains( 'name="base_group[level2][level3][level4][field]"', $html );
		$this->assertContains( 'value="' . $data['level2']['level3']['level4']['field'] . '"', $html );
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
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data['tab-1']['test_text'], get_post_meta( $this->post_id, 'test_text', true ) );
		$this->assertEquals( $data['tab-2']['test_textarea'], get_post_meta( $this->post_id, 'test_textarea', true ) );
		$html = $this->_get_html_for( $base );
		$this->assertContains( 'name="base_group[tab-1][test_text]"', $html );
		$this->assertContains( 'value="' . $data['tab-1']['test_text'] . '"', $html );
		$this->assertContains( 'name="base_group[tab-2][test_textarea]"', $html );
		$this->assertContains( '>' . $data['tab-2']['test_textarea'] . '</textarea>', $html );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_mixed() {
		$base = new Fieldmanager_Group( array(
			'name'           => 'base_group',
			'serialize_data' => false,
			'children'       => array(
				'test_text' => new Fieldmanager_TextField,
				'test_group' => new Fieldmanager_Group( array(
					'children'       => array(
						'text' => new Fieldmanager_TextArea,
					)
				) ),
			)
		) );

		$data = array(
			'test_text' => rand_str(),
			'test_group' => array(
				'text' => rand_str()
			),
		);

		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data['test_text'], get_post_meta( $this->post_id, 'base_group_test_text', true ) );
		$this->assertEquals( $data['test_group'], get_post_meta( $this->post_id, 'base_group_test_group', true ) );
		$html = $this->_get_html_for( $base );
		$this->assertContains( 'name="base_group[test_text]"', $html );
		$this->assertContains( 'value="' . $data['test_text'] . '"', $html );
		$this->assertContains( 'name="base_group[test_group][text]"', $html );
		$this->assertContains( '>' . $data['test_group']['text'] . '</textarea>', $html );
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

		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data['test_text'], get_post_meta( $this->post_id, 'base_group_test_text', true ) );
		$this->assertEquals( $data['test_group']['deep_text'], get_post_meta( $this->post_id, 'base_group_test_group_deep_text', true ) );
		$html = $this->_get_html_for( $base );
		$this->assertContains( 'name="base_group[test_text]"', $html );
		$this->assertContains( 'value="' . $data['test_text'] . '"', $html );
		$this->assertContains( 'name="base_group[test_group][deep_text]"', $html );
		$this->assertContains( '>' . $data['test_group']['deep_text'] . '</textarea>', $html );
	}

	/**
	 * @group serialize_data
	 * @expectedException FM_Developer_Exception
	 */
	public function test_unserialize_data_repeatable_group() {
		new Fieldmanager_Group( array(
			'name'           => 'parent',
			'serialize_data' => false,
			'limit'          => 0,
			'children'       => array(
				'child_1' => new Fieldmanager_TextField,
				'child_2' => new Fieldmanager_Textarea,
			),
		) );
	}

	/**
	 * @group serialize_data
	 * @expectedException FM_Developer_Exception
	 */
	public function test_unserialize_data_repeatable_parent() {
		new Fieldmanager_Group( array(
			'name'     => 'grandparent',
			'limit'    => 0,
			'children' => array(
				'parent' => new Fieldmanager_Group( array(
					'serialize_data' => false,
					'children'       => array(
						'child_1' => new Fieldmanager_TextField,
						'child_2' => new Fieldmanager_Textarea,
					),
				) ),
			),
		) );
	}

	/**
	 * @group serialize_data
	 * @expectedException FM_Developer_Exception
	 */
	public function test_unserialize_data_repeatable_distant_ancestor() {
		new Fieldmanager_Group( array(
			'name'     => 'gggp',
			'limit'    => 0,
			'children' => array(
				'ggp' => new Fieldmanager_Group( array(
					'children' => array(
						'grandparent' => new Fieldmanager_Group( array(
							'children' => array(
								'parent' => new Fieldmanager_Group( array(
									'serialize_data' => false,
									'children'       => array(
										'child_1' => new Fieldmanager_TextField,
										'child_2' => new Fieldmanager_Textarea,
									),
								) ),
							),
						) ),
					),
				) ),
			),
		) );
	}

	/**
	 * @group serialize_data
	 * @expectedException FM_Developer_Exception
	 */
	public function test_unserialize_data_indexed_field() {
		new Fieldmanager_Group( array(
			'name'           => 'parent',
			'serialize_data' => false,
			'children'       => array(
				'child_1' => new Fieldmanager_TextField,
				'child_2' => new Fieldmanager_Textarea( array(
					'index' => true,
				) ),
			),
		) );
	}

	/**
	 * @group serialize_data
	 */
	public function test_unserialize_data_no_name_conflict() {
		$base = new Fieldmanager_Group( array(
			'name'           => 'base_group',
			'serialize_data' => false,
			'add_to_prefix'  => false,
			'children'       => array(
				'test_text' => new Fieldmanager_TextField,
				'test_group' => new Fieldmanager_Group( array(
					'serialize_data' => false,
					'children'       => array(
						'test_text' => new Fieldmanager_TextArea,
					)
				) ),
			)
		) );

		$data = array(
			'test_text' => rand_str(),
			'test_group' => array(
				'test_text' => rand_str()
			),
		);

		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
		$this->assertEquals( $data['test_text'], get_post_meta( $this->post_id, 'test_text', true ) );
		$this->assertEquals( $data['test_group']['test_text'], get_post_meta( $this->post_id, 'test_group_test_text', true ) );
	}

	/**
	 * @group serialize_data
	 * @expectedException FM_Developer_Exception
	 */
	public function test_unserialize_data_name_conflict() {
		$base = new Fieldmanager_Group( array(
			'name'           => 'base_group',
			'serialize_data' => false,
			'add_to_prefix'  => false,
			'children'       => array(
				'test_text' => new Fieldmanager_TextField,
				'test_group' => new Fieldmanager_Group( array(
					'serialize_data' => false,
					'add_to_prefix'  => false,
					'children'       => array(
						'test_text' => new Fieldmanager_TextArea,
					)
				) ),
			)
		) );

		$data = array(
			'test_text' => rand_str(),
			'test_group' => array(
				'test_text' => rand_str()
			),
		);

		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $data );
	}

	public function test_removing_item_from_repeatable_group() {
		$field = new Fieldmanager_Group( array(
			'name' => 'removing_items_testing',
			'children' => array(
				'group' => new Fieldmanager_Group( array(
					'limit' => 10,
					'extra_elements' => 0,
					'group_is_empty' => function( $values ) { return empty( $values['a'] ); },
					'children' => array(
						'a' => new Fieldmanager_Textfield(),
						'b' => new Fieldmanager_Textfield(),
					),
				) )
			),
		) );

		$group_data = array(
			array( 'a' => rand_str(), 'b' => rand_str() ),
			array( 'a' => rand_str(), 'b' => rand_str() ),
			array( 'a' => rand_str(), 'b' => rand_str() ),
		);

		$to_save = array( 'group' => $group_data );

		$context = $field->add_meta_box( 'removing_items_testing', $this->post );

		$context->save_to_post_meta( $this->post->ID, $to_save );

		$data = get_post_meta( $this->post->ID, 'removing_items_testing', true );

		$this->assertEquals( 3, count( $data['group'] ) );

		$to_save['group'][0]['a'] = '';

		$context->save_to_post_meta( $this->post->ID, $to_save );

		$data = get_post_meta( $this->post->ID, 'removing_items_testing', true );

		$this->assertEquals( 2, count( $data['group'] ) );

		ob_start();
		$context->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		$this->assertNotContains( "value=\"{$group_data[0]['a']}\"", $html );
		$this->assertNotContains( "value=\"{$group_data[0]['b']}\"", $html );
		$this->assertContains( "value=\"{$group_data[1]['a']}\"", $html );
		$this->assertContains( "value=\"{$group_data[1]['b']}\"", $html );
		$this->assertContains( "value=\"{$group_data[2]['a']}\"", $html );
		$this->assertContains( "value=\"{$group_data[2]['b']}\"", $html );
	}

	public function test_add_another_box_position() {
		$default_field = new Fieldmanager_Group( array(
			'name' => 'default_group',
			'limit' => 10,
			'add_more_label' => 'Add Another',
			'children' => array(
				'a' => new Fieldmanager_Textfield(),
			),
		) );

		$group_data = array(
			array( 'a' => rand_str() ),
			array( 'a' => rand_str() ),
		);

		$default_context = $default_field->add_meta_box( 'default_group', $this->post );

		$default_context->save_to_post_meta( $this->post->ID, $group_data );

		ob_start();
		$default_context->render_meta_box( $this->post, array() );
		$default_html = ob_get_clean();

		$add_another_occurrence = strpos( $default_html, 'value="Add Another' );
		$text_field_occurrence = strpos( $default_html, 'class="fm-wrapper fm-a-wrapper"' );

		$this->assertContains( 'value="Add Another"', $default_html );
		$this->assertContains( 'data-add-more-position="bottom"', $default_html );
		//indicates add another button is at the bottom (default behavior).
		$this->assertTrue( $add_another_occurrence > $text_field_occurrence );



		$field = new Fieldmanager_Group( array(
			'name' => 'add_another_box_position',
			'limit' => 10,
			'add_more_label' => 'Add Another',
			'add_more_position' => 'top',
			'children' => array(
				'a' => new Fieldmanager_Textfield(),
			),
		) );

		$context = $field->add_meta_box( 'add_another_box_position', $this->post );

		$context->save_to_post_meta( $this->post->ID, $group_data );

		ob_start();
		$context->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$add_another_occurrence = strpos( $html, 'value="Add Another' );
		$text_field_occurrence = strpos( $html, 'class="fm-wrapper fm-a-wrapper"' );

		$this->assertContains( 'value="Add Another"', $html );
		$this->assertContains( 'data-add-more-position="top"', $html );
		//indicates add another button is at the top (add_another_position = top).
		$this->assertTrue( $add_another_occurrence < $text_field_occurrence );

	}

	public function test_textfield_zero_input_in_group() {
		$group = new Fieldmanager_Group( array(
			'name' => 'group',
			'children' => array(
				'a' => new Fieldmanager_Textfield(),
			),
		) );

		$group_data = array(
			'a' => 0,
		);

		$context = $group->add_meta_box( 'group', $this->post );
		$context->save_to_post_meta( $this->post->ID, $group_data );
		$this->assertEquals( array( 'a' => 0 ), get_post_meta( $this->post->ID, 'group', true ) );

		ob_start();
		$context->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		$this->assertContains( 'value="0"', $html );

		$group_data = array(
			'a' => '',
		);
		$context->save_to_post_meta( $this->post->ID, $group_data );
		$this->assertEquals( array( 'a' => '' ), get_post_meta( $this->post->ID, 'group', true ) );
	}

	public function test_obey_skip_save_inside_groups() {
		$group = new Fieldmanager_Group( array(
			'name' => 'group',
			'children' => array(
				'a' => new Fieldmanager_Textfield( array(
					'label' => 'a',
					'skip_save' => true,
				) ),
				'b' => new Fieldmanager_Textfield( array(
					'label' => 'b',
					'skip_save' => false,
				) ),
			),
		) );

		$skip = rand_str();
		$save = rand_str();

		$group_data = array(
			'a' => $skip,
			'b' => $save,
		);

		$context = $group->add_meta_box( 'group', $this->post );
		$context->save_to_post_meta( $this->post->ID, $group_data );
		$meta = get_post_meta( $this->post->ID, 'group', true );

		$this->assertTrue( ! isset( $meta['a'] ) );
		$this->assertEquals( $meta['b'], $save );

		ob_start();
		$context->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		$this->assertNotContains( $skip, $html );
		$this->assertContains( $save, $html );
	}
}
