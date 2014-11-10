<?php

/**
 * Tests the Fieldmanager Datasource Term
 *
 * @group datasource
 * @group term
 */
class Test_Fieldmanager_Datasource_Term extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->post = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		) );

		$this->term = $this->factory->tag->create_and_get( array( 'name' => rand_str() ) );
	}

	/**
	 * Save the data.
	 *
	 * @param Fieldmanager_Field $field
	 * @param WP_Post $post
	 * @param mixed $values
	 */
	public function save_values( $field, $post, $values ) {
		$field->add_meta_box( $field->name, $post->post_type )->save_to_post_meta( $post->ID, $values );
	}

	/**
	 * Test behavior when using the term datasource.
	 */
	public function test_datasource_term_save() {
		$terms = new Fieldmanager_Autocomplete( array(
			'name' => 'test_terms',
			'datasource' => new Fieldmanager_Datasource_Term( array(
				'taxonomy' => $this->term->taxonomy,
			) ),
		) );
		$this->save_values( $terms, $this->post, $this->term->term_id );

		$saved_value = get_post_meta( $this->post->ID, 'test_terms', true );
		$this->assertEquals( $this->term->term_id, $saved_value );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids' ) );
		$this->assertSame( array( $this->term->term_id ), $post_terms );
	}

	/**
	 * Test behavior when only saving to taxonomy.
	 */
	public function test_datasource_term_save_only_tax() {
		$terms = new Fieldmanager_Autocomplete( array(
			'name' => 'test_terms',
			'datasource' => new Fieldmanager_Datasource_Term( array(
				'taxonomy' => $this->term->taxonomy,
				'only_save_to_taxonomy' => true,
			) ),
		) );
		$this->save_values( $terms, $this->post, $this->term->term_id );

		$saved_value = get_post_meta( $this->post->ID, 'test_terms', true );
		$this->assertSame( array(), $saved_value );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids' ) );
		$this->assertSame( array( $this->term->term_id ), $post_terms );
	}

	/**
	 * Test behavior when saving multiple values.
	 */
	public function test_datasource_term_save_multi() {
		$terms = new Fieldmanager_Autocomplete( array(
			'name' => 'test_terms',
			'limit' => 0,
			'datasource' => new Fieldmanager_Datasource_Term( array(
				'taxonomy' => $this->term->taxonomy,
			) ),
		) );

		$term = $this->factory->tag->create_and_get( array( 'name' => rand_str() ) );

		$this->save_values( $terms, $this->post, array( $this->term->term_id, $term->term_id ) );

		$saved_value = get_post_meta( $this->post->ID, 'test_terms', true );
		$this->assertSame( array( $this->term->term_id, $term->term_id ), $saved_value );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids' ) );
		$this->assertCount( 2, $post_terms );
		$this->assertContains( $this->term->term_id, $post_terms );
		$this->assertContains( $term->term_id, $post_terms );
	}

	/**
	 * Test behavior when saving multiple values only to taxonomy.
	 */
	public function test_datasource_term_save_multi_only_tax() {
		$terms = new Fieldmanager_Autocomplete( array(
			'name' => 'test_terms',
			'limit' => 0,
			'datasource' => new Fieldmanager_Datasource_Term( array(
				'taxonomy' => $this->term->taxonomy,
				'only_save_to_taxonomy' => true,
			) ),
		) );

		$term = $this->factory->tag->create_and_get( array( 'name' => rand_str() ) );

		$this->save_values( $terms, $this->post, array( $this->term->term_id, $term->term_id ) );

		$saved_value = get_post_meta( $this->post->ID, 'test_terms', true );
		$this->assertSame( array(), $saved_value );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids' ) );
		$this->assertCount( 2, $post_terms );
		$this->assertContains( $this->term->term_id, $post_terms );
		$this->assertContains( $term->term_id, $post_terms );
	}

	/**
	 * Test behavior when saving a new term (when exact match is not required).
	 */
	public function test_datasource_term_save_multi_only_tax_inexact() {
		$terms = new Fieldmanager_Autocomplete( array(
			'name' => 'test_terms',
			'limit' => 0,
			'exact_match' => false,
			'datasource' => new Fieldmanager_Datasource_Term( array(
				'taxonomy' => $this->term->taxonomy,
				'only_save_to_taxonomy' => true,
			) ),
		) );

		$new_term = rand_str();

		$this->save_values( $terms, $this->post, array( $new_term ) );

		$saved_value = get_post_meta( $this->post->ID, 'test_terms', true );
		$this->assertSame( array(), $saved_value );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'names' ) );
		$this->assertCount( 1, $post_terms );
		$this->assertContains( $new_term, $post_terms );

		// Numeric terms should be prefixed with an '=' from the JS handling.
		$numeric_term = rand();

		$this->save_values( $terms, $this->post, array( "={$numeric_term}" ) );

		$saved_value = get_post_meta( $this->post->ID, 'test_terms', true );
		$this->assertSame( array(), $saved_value );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'names' ) );
		$this->assertCount( 1, $post_terms );
		$this->assertContains( $numeric_term, $post_terms );

		$numeric_term = $this->term->term_id;

		$this->save_values( $terms, $this->post, array( "={$numeric_term}" ) );

		$saved_value = get_post_meta( $this->post->ID, 'test_terms', true );
		$this->assertSame( array(), $saved_value );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'names' ) );
		$this->assertCount( 1, $post_terms );
		$this->assertContains( $numeric_term, $post_terms );
	}

	/**
	 * Test behavior when saving an empty value.
	 */
	public function test_datasource_term_save_empty() {
		$terms = new Fieldmanager_Autocomplete( array(
			'name' => 'test_terms',
			'datasource' => new Fieldmanager_Datasource_Term( array(
				'taxonomy' => $this->term->taxonomy,
			) ),
		) );

		$this->save_values( $terms, $this->post, '' );

		$saved_value = get_post_meta( $this->post->ID, 'test_terms', true );
		$this->assertSame( '', $saved_value );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'names' ) );
		$this->assertCount( 0, $post_terms );
	}
}
