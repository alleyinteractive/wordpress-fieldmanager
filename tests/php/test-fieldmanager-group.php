<?php

/**
 * Tests the Fieldmanager Datasource Post
 *
 * @group field
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
						'repeat' => new Fieldmanager_Textfield( array(
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
		$field = new Fieldmanager_Group( array(
			'name' => 'add_another_box_position',
			'limit' => 10,
			'add_more_label' => 'Add Another',
			'add_more_position' => 'top',
			'children' => array(
				'a' => new Fieldmanager_Textfield(),
			),
		) );

		$group_data = array(
			array( 'a' => rand_str() ),
			array( 'a' => rand_str() ),
		);

		$context = $field->add_meta_box( 'add_another_box_position', $this->post );

		$context->save_to_post_meta( $this->post->ID, $group_data );

		ob_start();
		$context->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		$this->assertContains( "value=\"Add Another\"", $html );
		$this->assertContains( "data-add-more-position=\"top\"", $html );

	}
}
