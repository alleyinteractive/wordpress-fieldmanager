<?php

/**
 * Tests the Fieldmanager Term Meta
 *
 * @group util
 * @group term
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
}
