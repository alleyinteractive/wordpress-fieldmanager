<?php

/**
 * Tests the Fieldmanager Datasource Term when Taxonomy is set to an array
 *
 * @group field
 * @group autocomplete
 */
class Test_Fieldmanager_Datasource_Term_Multiple_Taxonomy extends WP_UnitTestCase {

	public $tag_term;

	public $category_term;

	public $datasource;

	/**
	 * Set up our testing environment
	 */
	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->post = $this->factory->post->create_and_get( array( 'post_title' => rand_str(), 'post_date' => '2016-10-01 00:00:00' ) );

		// Create a Test Post Tag
		$this->tag_term  = $this->factory->tag->create_and_get( array( 'name' => 'test tag' ) );
		// Create a Test Category
		$this->category_term = $this->factory->category->create_and_get( array( 'name' => 'test category' ) );

		// A Term Datasource that queries both Post Tag and Category Taxonomies
		$this->datasource = new Fieldmanager_Datasource_Term( array( 'taxonomy' => array( 'category', 'post_tag' ), 'taxonomy_save_to_terms' => false ) );
	}

	/**
	 * Test whether our datasource works
	 */
	public function test_multiple_taxonomies_with_ajax() {
		$items = $this->datasource->get_items_for_ajax( 'test' );
		$this->assertSame( 2, count( $items ) );
	}
}
