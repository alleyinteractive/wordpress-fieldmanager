<?php

/**
 * Tests the Fieldmanager Term Meta
 *
 * @group util
 * @group term
 * @group fm_term_meta
 */
class Test_Fieldmanager_Term_Meta extends WP_UnitTestCase {
	public $current_user;

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->current_user = get_current_user_id();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$this->term = $this->factory->category->create_and_get( array( 'name' => rand_str() ) );
	}

	public function tearDown() {
		if ( get_current_user_id() != $this->current_user ) {
			wp_delete_user( get_current_user_id() );
		}
		wp_set_current_user( $this->current_user );
	}

	/**
	 * Set up the request environment values and save the data.
	 *
	 * @param Fieldmanager_Field $field
	 * @param WP_Post $post
	 * @param mixed $values
	 */
	public function save_values( $field, $term, $values ) {
		$field->add_term_form( $field->name, $term->taxonomy )->save_to_term_meta( $term->term_id, $term->taxonomy, $values );
	}

	/**
	 * Test behavior when using the term meta fields.
	 *
	 * Fieldmanager_Field::add_term_form is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::get_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::update_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::delete_term_meta is deprecated as of 1.0.0-beta.3
	 */
	public function test_save_term_meta() {
		$term_option = new Fieldmanager_Textfield( array(
			'name'  => 'term_option',
		) );

		// check normal save and fetch behavior
		$text = rand_str();
		$this->save_values( $term_option, $this->term, $text );

		$data = fm_get_term_meta( $this->term->term_id, $this->term->taxonomy, 'term_option', true );
		$this->assertEquals( $text, $data );

		$data = fm_get_term_meta( $this->term->term_id, $this->term->taxonomy, 'term_option', false );
		$this->assertEquals( array( $text ), $data );

		// check update and fetch
		$text_updated = rand_str();
		$this->save_values( $term_option, $this->term, $text_updated );

		$data = fm_get_term_meta( $this->term->term_id, $this->term->taxonomy, 'term_option', true );
		$this->assertEquals( $text_updated, $data );

		$this->assertInternalType( 'int', Fieldmanager_Util_Term_Meta()->get_term_meta_post_id( $this->term->term_id, $this->term->taxonomy ) );

		$cache_key = Fieldmanager_Util_Term_Meta()->get_term_meta_post_id_cache_key( $this->term->term_id, $this->term->taxonomy );

		$this->assertNotEquals( false, wp_cache_get( $cache_key ) );

		fm_delete_term_meta( $this->term->term_id, $this->term->taxonomy, 'term_option' );

		// post id not cached right after removal of only meta value, which results in deletion of the post
		$this->assertEquals( false, wp_cache_get( $cache_key ) );

		// checking that the post id is reported as false when it doesn't exist now
		$this->assertEquals( false, Fieldmanager_Util_Term_Meta()->get_term_meta_post_id( $this->term->term_id, $this->term->taxonomy ) );

		// checking that the post id is cached now to return false since it doesn't exist
		$this->assertNotEquals( false, wp_cache_get( $cache_key ) );
	}

	/**
	 * Fieldmanager_Field::add_term_form is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Context_Term::delete_term_fields is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::get_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::update_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::delete_term_meta is deprecated as of 1.0.0-beta.3
	 */
	public function test_garbage_collection() {
		$term_option = new Fieldmanager_Textfield( array(
			'name'  => 'term_option',
		) );

		// check normal save and fetch behavior
		$text = rand_str();
		$this->save_values( $term_option, $this->term, $text );

		$data = fm_get_term_meta( $this->term->term_id, $this->term->taxonomy, 'term_option', true );
		$this->assertEquals( $text, $data );

		// Verify the FM term post exists
		$post = get_page_by_path( "fm-term-meta-{$this->term->term_id}-category", OBJECT, 'fm-term-meta' );
		$this->assertTrue( ! empty( $post->ID ) );
		$this->assertEquals( 'fm-term-meta', $post->post_type );
		$post_id = $post->ID;
		$this->assertEquals( $text, get_post_meta( $post_id, 'term_option', true ) );

		// Delete the term
		wp_delete_term( $this->term->term_id, 'category' );

		// The post and meta should be deleted
		$post = get_page_by_path( "fm-term-meta-{$this->term->term_id}-category", OBJECT, 'fm-term-meta' );
		$this->assertEmpty( $post );
		$this->assertEquals( '', get_post_meta( $post_id, 'term_option', true ) );
	}

	/**
	 * @group term_splitting
	 * Fieldmanager_Util_Term_Meta::get_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::add_term_meta is deprecated as of 1.0.0-beta.3
	 */
	public function test_term_splitting() {
		// Ensure that term splitting exists
		if ( ! function_exists( 'wp_get_split_terms' ) ) {
			return;
		}

		global $wpdb;

		// Add our first term. This is the one that will split off.
		$t1 = wp_insert_term( 'Joined Term', 'category' );

		// Add term meta to the term
		$value = rand_str();
		$term_id_1 = $t1['term_id'];
		fm_add_term_meta( $term_id_1, 'category', 'split_test', $value );

		// Add a second term to a custom taxonomy
		register_taxonomy( 'fm_test_tax', 'post' );
		$t2 = wp_insert_term( 'Second Joined Term', 'fm_test_tax' );

		// Manually modify the second term to setup the term splitting
		// condition. Shared terms don't naturally occur any longer.
		$wpdb->update(
			$wpdb->term_taxonomy,
			array( 'term_id' => $term_id_1 ),
			array( 'term_taxonomy_id' => $t2['term_taxonomy_id'] ),
			array( '%d' ),
			array( '%d' )
		);

		// Verify that we can retrieve the term meta
		$this->assertEquals( $value, fm_get_term_meta( $term_id_1, 'category', 'split_test', true ) );

		// Update the term to cause it to split
		$new_t1 = wp_update_term( $term_id_1, 'category', array(
			'name' => 'Split Term',
		) );

		// Verify that the term updated and split
		$this->assertTrue( isset( $new_t1['term_id'] ) );
		$this->assertNotEquals( $new_t1['term_id'], $term_id_1 );

		// Verify that the term meta works at the new term id
		$this->assertEquals( $value, fm_get_term_meta( $new_t1['term_id'], 'category', 'split_test', true ) );

		// Verify that we CANNOT access the term meta at the old term id
		$this->assertEquals( '', fm_get_term_meta( $term_id_1, 'category', 'split_test', true ) );
	}
}
