<?php

/**
 * Tests the Fieldmanager Datasource Post
 */
class Test_Fieldmanager_Datasource_Post extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		// imitate cron; otherwise the context post save routine expects environment specific to form submission
		if ( ! defined( 'DOING_CRON' ) ) {
			define( 'DOING_CRON', true );
		}

		$this->parent_post = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		) );

		$this->child_post_a = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		) );

		$this->child_post_b = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		) );
	}

	/**
	 * Test behavior when using the post datasource
	 */
	public function test_save_child_posts() {
		$children = new Fieldmanager_Autocomplete( array(
			'name' => 'test_children',
			'limit' => 0,
			'datasource' => new Fieldmanager_Datasource_Post( array(
				'query_args' => array(
					'post_type' => 'post'
				),
			) ),
		) );
		$children->add_meta_box( 'test_children', $this->parent_post->post_type )->save_to_post_meta( $this->parent_post->ID, array( $this->child_post_a->ID, $this->child_post_b->ID ) );
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
		$children->add_meta_box( 'test_children', $this->parent_post->post_type )->save_to_post_meta( $this->parent_post->ID, array( $this->child_post_a->ID, $this->child_post_b->ID ) );

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
		$children->add_meta_box( 'multi_select_test_1', $this->parent_post->post_type )->save_to_post_meta( $this->parent_post->ID, $test_data );
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
		$children->add_meta_box( 'test_save_multiple_select_with_datasource2', $this->parent_post->post_type )->save_to_post_meta( $this->parent_post->ID, $test_data );
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
		$children->add_meta_box( 'test_save_multiple_select_with_datasource3', $this->parent_post->post_type )->save_to_post_meta( $this->parent_post->ID, $test_data );
		$saved_value = get_post_meta( $this->parent_post->ID, 'test_save_multiple_select_with_datasource3', true );
		$this->assertEquals( $test_data, $saved_value );
	}
}
