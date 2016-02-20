<?php

/**
 * Tests the Fieldmanager Datasource Term
 *
 * @group datasource
 * @group term
 */
class Test_Fieldmanager_Datasource_Term extends WP_UnitTestCase {
	public $post;

	public $term;

	public $term_2;

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->post = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		) );

		$this->term   = $this->factory->tag->create_and_get( array( 'name' => rand_str() ) );
		$this->term_2 = $this->factory->tag->create_and_get( array( 'name' => rand_str() ) );
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
		$this->assertSame( '', $saved_value );

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
		$this->assertSame( '', $saved_value );

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
		$this->assertSame( '', $saved_value );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'names' ) );
		$this->assertCount( 1, $post_terms );
		$this->assertContains( $new_term, $post_terms );

		// Numeric terms should be prefixed with an '=' from the JS handling.
		$numeric_term = rand();

		$this->save_values( $terms, $this->post, array( "={$numeric_term}" ) );

		$saved_value = get_post_meta( $this->post->ID, 'test_terms', true );
		$this->assertSame( '', $saved_value );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'names' ) );
		$this->assertCount( 1, $post_terms );
		$this->assertContains( $numeric_term, $post_terms );

		$numeric_term = $this->term->term_id;

		$this->save_values( $terms, $this->post, array( "={$numeric_term}" ) );

		$saved_value = get_post_meta( $this->post->ID, 'test_terms', true );
		$this->assertSame( '', $saved_value );

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

	/**
	 * Test behavior when set to save only to taxonomy and not append.
	 * Ensure that all terms are removed in this case.
	 * This has a specific use case for Fieldmanager_Checkboxes so testing there.
	 * However, this applies to all field types that can create this scenario.
	 */
	public function test_datasource_term_save_to_taxonomy_empty() {
		$term_taxonomy = new Fieldmanager_Checkboxes( array(
			'name' => 'test_terms',
			'limit' => 0,
			'datasource' => new Fieldmanager_Datasource_Term( array(
				'taxonomy' => $this->term->taxonomy,
				'only_save_to_taxonomy' => true,
				'append_taxonomy' => false,
			) ),
		) );

		$term = $this->factory->tag->create_and_get( array( 'name' => rand_str() ) );
		$terms = array( $this->term->term_id, $term->term_id );

		$this->save_values( $term_taxonomy, $this->post, $terms );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids' ) );
		$this->assertSame( sort( $terms ), sort( $post_terms ) );

		$terms = array();
		$this->save_values( $term_taxonomy, $this->post, $terms );

		$post_terms = wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids' ) );
		$this->assertSame( $terms, $post_terms );

	}

	/**
	 * Test behavior when only saving to taxonomy within a single repeating
	 * field set to not use serialized meta.
	 *
	 * @group serialize_data
	 */
	public function test_datasource_term_save_only_tax_with_unseriaized_data() {
		$this->assertCount( 0, wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'names' ) ) );

		$args = array(
			'name'           => 'base_autocomplete',
			'serialize_data' => false,
			'sortable'       => true,
			'limit'          => 0,
			'datasource'     => new Fieldmanager_Datasource_Term( array(
				'taxonomy'              => $this->term->taxonomy,
				'only_save_to_taxonomy' => true,
			) ),
		);
		$base = new Fieldmanager_Autocomplete( $args );
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, array( $this->term->term_id, $this->term_2->term_id ) );
		$this->assertSame( '', get_post_meta( $this->post->ID, 'base_autocomplete', true ) );
		$this->assertSame(
			array( $this->term->term_id, $this->term_2->term_id ),
			wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids', 'orderby' => 'term_order', 'order' => 'ASC' ) )
		);

		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, array( $this->term->term_id ) );
		$this->assertSame( '', get_post_meta( $this->post->ID, 'base_autocomplete', true ) );
		$this->assertSame(
			array( $this->term->term_id ),
			wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids' ) )
		);

		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, array( $this->term_2->term_id , $this->term->term_id ) );
		$this->assertSame( '', get_post_meta( $this->post->ID, 'base_autocomplete', true ) );
		$this->assertSame(
			array( $this->term_2->term_id, $this->term->term_id ),
			wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids', 'orderby' => 'term_order', 'order' => 'ASC' ) )
		);
	}

	/**
	 * Test behavior when only saving to taxonomy within a group set to not use
	 * serialized meta.
	 *
	 * @group serialize_data
	 */
	public function test_group_datasource_term_save_only_tax_with_unseriaized_data() {
		$this->assertCount( 0, wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'names' ) ) );

		$args = array(
			'name'           => 'base_group',
			'serialize_data' => false,
			'children'       => array(
				'test_basic' => new Fieldmanager_TextField(),
				'test_datasource' => new Fieldmanager_Autocomplete( array(
					'datasource' => new Fieldmanager_Datasource_Term( array(
						'taxonomy' => $this->term->taxonomy,
						'only_save_to_taxonomy' => true,
					) ),
				) ),
			)
		);
		$data = array(
			'test_basic'     => rand_str(),
			'test_datasource' => $this->term->term_id,
		);
		$base = new Fieldmanager_Group( $args );
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, $data );
		$this->assertSame( $data['test_basic'], get_post_meta( $this->post->ID, 'base_group_test_basic', true ) );
		$this->assertSame( '', get_post_meta( $this->post->ID, 'base_group_test_datasource', true ) );
		$this->assertSame(
			array( $this->term->term_id ),
			wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids' ) )
		);

		wp_set_object_terms( $this->post->ID, null, 'post_tag' );

		$base = new Fieldmanager_Group( array_merge( $args, array( 'add_to_prefix' => false ) ) );
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, $data );
		$this->assertSame( $data['test_basic'], get_post_meta( $this->post->ID, 'test_basic', true ) );
		$this->assertSame( '', get_post_meta( $this->post->ID, 'test_datasource', true ) );
		$this->assertSame(
			array( $this->term->term_id ),
			wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids' ) )
		);
	}

	/**
	 * When FM stores the term datasource as a term relationship, the taxonomy
	 * needs to be sortable. This tests that taxonomies are made sortable.
	 */
	public function test_taxonomy_made_sortable() {
		global $wp_taxonomies;
		$taxonomy = 'test-fm-unsortable-taxonomy';

		// Create a custom taxonomy
		register_taxonomy( $taxonomy, 'post' );

		// Verify that our custom taxonomy is not sortable
		$this->assertTrue( empty( $wp_taxonomies[ $taxonomy ]->sort ) );

		new Fieldmanager_Autocomplete( array(
			'name' => 'test_terms',
			'datasource' => new Fieldmanager_Datasource_Term( array(
				'taxonomy' => array( 'category', 'post_tag', $taxonomy ),
				'taxonomy_save_to_terms' => true,
			) ),
		) );

		// Verify that the above datasource made our taxonomy sortable. Also,
		// ensure that tags and categories are now sortable (even though they
		// may have already been due to code run elsewhere).
		$this->assertTrue( $wp_taxonomies[ $taxonomy ]->sort );
		$this->assertTrue( $wp_taxonomies['category']->sort );
		$this->assertTrue( $wp_taxonomies['post_tag']->sort );
	}

	public function test_append_true_after_first_save() {
		$fm = new Fieldmanager_Group( array(
			'name'     => 'author_data',
			'children' => array(
				'school' => new Fieldmanager_Group(array(
					'label' => 'School',
					'add_more_label' => 'Add new',
					'limit' => 0,
					'children' => array(
						'school_city' => new Fieldmanager_Autocomplete( array(
							'label' => 'School city',
							'datasource' => new Fieldmanager_Datasource_Term( array(
								'taxonomy' => 'post_tag',
								'taxonomy_save_to_terms' => true
							))
						)),
					)
				))
			)
		) );
		$data = array(
			'school' => array(
				array( 'school_city' => $this->term->term_id ),
				array( 'school_city' => $this->term_2->term_id ),
			),
		);
		$fm->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post->ID, $data );
		$this->assertEqualSets(
			array( $this->term->term_id, $this->term_2->term_id ),
			wp_get_post_terms( $this->post->ID, $this->term->taxonomy, array( 'fields' => 'ids' ) )
		);
	}


	/**
	 * Test saving term relationships to users.
	 */
	public function test_saving_taxonomies_to_users() {
		// Create a new taxonomy and add a term to it
		register_taxonomy( 'user-tax', 'user' );
		$term = wp_insert_term( 'test-term', 'user-tax' );

		// Create a user to which we'll save this data
		$user_id = wp_create_user( rand_str(), rand_str(), 'admin@local.dev' );
		$user = get_user_by( 'id', $user_id );

		// Create the field and save the data
		$field = new Fieldmanager_Autocomplete( array(
			'name' => 'test_terms',
			'datasource' => new Fieldmanager_Datasource_Term( array(
				'taxonomy' => 'user-tax',
				'only_save_to_taxonomy' => true,
			) ),
		) );
		$field->add_user_form( 'test' )->save_to_user_meta( $user_id, array( 'test_terms' => $term['term_id'] ) );

		$this->assertSame( '', get_user_meta( $user_id, 'test_terms', true ) );
		$this->assertSame( array( $term['term_id'] ), wp_get_object_terms( $user_id, 'user-tax', array( 'fields' => 'ids' ) ) );
	}
}
