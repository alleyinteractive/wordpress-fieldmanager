<?php

/**
 * Tests the Fieldmanager Datasource Post
 *
 * @group datasource
 * @group post
 */
class Test_Fieldmanager_Datasource_Post extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->author = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'author' ) );
		$this->editor = $this->factory->user->create( array( 'role' => 'editor', 'user_login' => 'editor' ) );

		wp_set_current_user( $this->editor );

		$this->parent_post = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
			'post_author' => $this->author,
		) );

		$this->child_post_a = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
			'post_author' => $this->author,
		) );

		$this->child_post_b = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
			'post_author' => $this->editor,
		) );
	}

	/**
	 * Set up the request environment values and save the data.
	 *
	 * @param Fieldmanager_Field $field
	 * @param WP_Post $post
	 * @param mixed $values
	 */
	public function save_values( $field, $post, $values ) {
		$_POST = array(
			'post_ID' => $post->ID,
			'action' => 'editpost',
			'post_type' => $post->post_type,
			"fieldmanager-{$field->name}-nonce" => wp_create_nonce( "fieldmanager-save-{$field->name}" ),
			$field->name => $values,
		);

		$field->add_meta_box( $field->name, $post->post_type )->save_to_post_meta( $post->ID, $values );
	}

	/**
	 * Test behavior when using the post datasource.
	 */
	public function test_save_child_posts() {
		// Author is permitted to use child_post_b because this configuration makes no changes to children.
		wp_set_current_user( $this->author );

		$children = new Fieldmanager_Autocomplete( array(
			'name' => 'test_children',
			'limit' => 0,
			'datasource' => new Fieldmanager_Datasource_Post( array(
				'query_args' => array(
					'post_type' => 'post'
				),
			) ),
		) );
		$this->save_values( $children, $this->parent_post, array( $this->child_post_a->ID, $this->child_post_b->ID ) );
		$saved_value = get_post_meta( $this->parent_post->ID, 'test_children', true );

		$this->assertEquals( 2, count( $saved_value ) );
		$this->assertEquals( $this->child_post_a->ID, $saved_value[0] );
		$this->assertEquals( $this->child_post_b->ID, $saved_value[1] );

		wp_publish_post( $this->parent_post->ID );

		$this->assertEquals( 'publish', get_post_status( $this->parent_post->ID ) );
		$this->assertEquals( 'draft', get_post_status( $this->child_post_a->ID ) );
		$this->assertEquals( 'draft', get_post_status( $this->child_post_b->ID ) );

		// reload post from db
		$this->child_post_a = get_post( $this->child_post_a->ID );
		$this->child_post_b = get_post( $this->child_post_b->ID );

		$this->assertEquals( null, $this->child_post_a->post_name );
		$this->assertEquals( null, $this->child_post_b->post_name );
	}

	/**
	 * Test that when configured to do so, child posts will store a reciprocal post id meta.
	 */
	public function test_reciprocal_meta() {
		$children = new Fieldmanager_Autocomplete( array(
			'name' => 'test_children',
			'limit' => 0,
			'datasource' => new Fieldmanager_Datasource_Post( array(
				'query_args' => array(
					'post_type' => 'post'
				),
				'reciprocal' => 'parent_post',
			) ),
		) );

		$this->assertEquals( null, get_post_meta( $this->child_post_a->ID, 'parent_post', true ) );
		$this->assertEquals( null, get_post_meta( $this->child_post_b->ID, 'parent_post', true ) );

		$this->save_values( $children, $this->parent_post, array( $this->child_post_a->ID ) );

		$this->assertEquals( $this->parent_post->ID, get_post_meta( $this->child_post_a->ID, 'parent_post', true ) );
		$this->assertEquals( null, get_post_meta( $this->child_post_b->ID, 'parent_post', true ) );

		$this->save_values( $children, $this->parent_post, array( $this->child_post_b->ID ) );

		$this->assertEquals( null, get_post_meta( $this->child_post_a->ID, 'parent_post', true ) );
		$this->assertEquals( $this->parent_post->ID, get_post_meta( $this->child_post_b->ID, 'parent_post', true ) );

		$this->save_values( $children, $this->parent_post, array( $this->child_post_a->ID, $this->child_post_b->ID ) );

		$this->assertEquals( $this->parent_post->ID, get_post_meta( $this->child_post_a->ID, 'parent_post', true ) );
		$this->assertEquals( $this->parent_post->ID, get_post_meta( $this->child_post_b->ID, 'parent_post', true ) );

		$this->save_values( $children, $this->parent_post, array() );

		$this->assertEquals( null, get_post_meta( $this->child_post_a->ID, 'parent_post', true ) );
		$this->assertEquals( null, get_post_meta( $this->child_post_b->ID, 'parent_post', true ) );
	}

	/**
	 * Test that when configured to do so, child posts will publish when the parent is published
	 */
	public function test_publish_children_with_parent() {
		$children = new Fieldmanager_Autocomplete( array(
			'name' => 'test_children',
			'limit' => 0,
			'datasource' => new Fieldmanager_Datasource_Post( array(
				'query_args' => array(
					'post_type' => 'post'
				),
				'publish_with_parent' => true,
			) ),
		) );
		$this->save_values( $children, $this->parent_post, array( $this->child_post_a->ID, $this->child_post_b->ID ) );

		$this->assertEquals( 'draft', get_post_status( $this->parent_post->ID ) );
		$this->assertEquals( 'draft', get_post_status( $this->child_post_a->ID ) );
		$this->assertEquals( 'draft', get_post_status( $this->child_post_b->ID ) );

		wp_publish_post( $this->parent_post->ID );

		$this->assertEquals( 'publish', get_post_status( $this->parent_post->ID ) );
		$this->assertEquals( 'publish', get_post_status( $this->child_post_a->ID ) );
		$this->assertEquals( 'publish', get_post_status( $this->child_post_b->ID ) );

		// reload post from db
		$this->child_post_a = get_post( $this->child_post_a->ID );
		$this->child_post_b = get_post( $this->child_post_b->ID );

		$this->assertEquals( sanitize_title( $this->child_post_a->post_title ), $this->child_post_a->post_name );
		$this->assertEquals( sanitize_title( $this->child_post_b->post_title ), $this->child_post_b->post_name );
	}

	/**
	 * Test multiple-select field with datasource
	 */
	public function test_save_multiple_select_with_datasource() {
		$test_data = array( $this->child_post_a->ID, $this->child_post_b->ID );

		$children = new Fieldmanager_Select( array(
			'name' => 'multi_select_test_1',
			'multiple' => false,
			'datasource' => new Fieldmanager_Datasource_Post( array(
				'query_args' => array(
					'post_type' => 'post'
				),
			) ),
		) );
		$this->save_values( $children, $this->parent_post, $test_data );
		$saved_value = get_post_meta( $this->parent_post->ID, 'multi_select_test_1', true );
		$this->assertNotEquals( $test_data, $saved_value );

		$children = new Fieldmanager_Select( array(
			'name' => 'test_save_multiple_select_with_datasource2',
			'multiple' => true,
			'datasource' => new Fieldmanager_Datasource_Post( array(
				'query_args' => array(
					'post_type' => 'post'
				),
			) ),
		) );
		$this->save_values( $children, $this->parent_post, $test_data );
		$saved_value = get_post_meta( $this->parent_post->ID, 'test_save_multiple_select_with_datasource2', true );
		$this->assertEquals( $test_data, $saved_value );

		$children = new Fieldmanager_Select( array(
			'name' => 'test_save_multiple_select_with_datasource3',
			'attributes' => array( 'multiple' => 'multiple' ),
			'datasource' => new Fieldmanager_Datasource_Post( array(
				'query_args' => array(
					'post_type' => 'post'
				),
			) ),
		) );
		$this->save_values( $children, $this->parent_post, $test_data );
		$saved_value = get_post_meta( $this->parent_post->ID, 'test_save_multiple_select_with_datasource3', true );
		$this->assertEquals( $test_data, $saved_value );
	}

	/**
	 * Test that a user lacking permission can not publish a child post.
	 *
	 * @expectedException WPDieException
	 */
	public function test_alter_child_invalid_publish() {
		$test_data = array( $this->child_post_a->ID, $this->child_post_b->ID );

		$children = new Fieldmanager_Autocomplete( array(
			'name' => 'test_children',
			'limit' => 0,
			'datasource' => new Fieldmanager_Datasource_Post( array(
				'query_args' => array(
					'post_type' => 'post'
				),
				'publish_with_parent' => true,
			) ),
		) );

		wp_set_current_user( $this->author );

		$this->save_values( $children, $this->parent_post, $test_data );
	}

	/**
	 * Test save_to_post_parent logic
	 */
	public function test_post_parent() {
		$test_data = $this->child_post_a->ID;
		$children = new Fieldmanager_Autocomplete( array(
			'name' => 'test_parent',
			'datasource' => new Fieldmanager_Datasource_Post( array(
				'query_args' => array(
					'post_type' => 'post'
				),
				'save_to_post_parent' => true,
			) ),
		) );
		$this->save_values( $children, $this->parent_post, $test_data );
		$parent = get_post( $this->parent_post->ID );
		$this->assertEquals( $parent->post_parent, $this->child_post_a->ID );
		$this->assertEquals( get_post_meta( $this->parent_post->ID, 'test_parent', true ), $this->child_post_a->ID );
	}

	/**
	 * Test save_to_post_parent logic
	 */
	public function test_post_parent_nested() {
		$test_data = array( 'parent' => $this->child_post_a->ID );
		$children = new Fieldmanager_Group( array(
			'name' => 'test_parent',
			'children' => array(
				'parent' => new Fieldmanager_Autocomplete( 'Post Parent', array(
					'datasource' => new Fieldmanager_Datasource_Post( array(
						'query_args' => array(
							'post_type' => 'post'
						),
						'save_to_post_parent' => true,
						'only_save_to_post_parent' => true,
					) ),
				) ),
			),
		) );
		$this->save_values( $children, $this->parent_post, $test_data );
		$parent = get_post( $this->parent_post->ID );
		$this->assertEquals( $parent->post_parent, $this->child_post_a->ID );
	}

	/**
	 * Test save_to_post_parent_only logic
	 */
	public function test_post_parent_only() {
		$test_data = $this->child_post_a->ID;
		$children = new Fieldmanager_Autocomplete( array(
			'name' => 'test_parent',
			'datasource' => new Fieldmanager_Datasource_Post( array(
				'query_args' => array(
					'post_type' => 'post'
				),
				'save_to_post_parent' => true,
				'only_save_to_post_parent' => true,
			) ),
		) );
		$this->save_values( $children, $this->parent_post, $test_data );
		$parent = get_post( $this->parent_post->ID );
		$this->assertEquals( $parent->post_parent, $this->child_post_a->ID );
		$this->assertEmpty( get_post_meta( $this->parent_post->ID, 'test_parent', true ) );
	}

	/**
	 * Test that a user lacking permission can not add meta to a child post.
	 *
	 * @expectedException WPDieException
	 */
	public function test_alter_child_invalid_reciprocal() {
		$test_data = array( $this->child_post_a->ID, $this->child_post_b->ID );

		$children = new Fieldmanager_Autocomplete( array(
			'name' => 'test_children',
			'limit' => 0,
			'datasource' => new Fieldmanager_Datasource_Post( array(
				'query_args' => array(
					'post_type' => 'post'
				),
				'reciprocal' => 'parent_post',
			) ),
		) );

		wp_set_current_user( $this->author );

		$this->save_values( $children, $this->parent_post, $test_data );
	}
}
