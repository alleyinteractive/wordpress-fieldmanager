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
	}

}
