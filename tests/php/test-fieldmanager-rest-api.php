<?php
/**
 * Test out REST API functionality.
 *
 * @package Tests / REST API
 */

/**
 * Tests Fieldmanager REST API functionality
 */
class Test_Fieldmanager_REST_API extends Fieldmanager_REST_API_Controller {

	/**
	 * Peform initial setup.
	 */
	function setup() {
		$this->test_field = rand_str();
		$this->new_test_data = rand_str();
		$this->admin_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
		wp_set_current_user( $this->admin_id );

		parent::setup();
	}

	/**
	 * Peform the tear down.
	 */
	function tearDown() {
		parent::tearDown();

		wp_set_current_user( 0 );
	}

	/**
	 * Test the post context.
	 */
	function test_fieldmanager_rest_api_post() {
		// Add actions for post context.
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );

		// Create the post.
		$post_id = $this->factory->post->create();

		// Add data.
		$test_data = rand_str();
		update_post_meta( $post_id, $this->test_field, $test_data );

		// Process the REST API call.
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( $data[ $this->test_field ], $test_data );
	}

	/**
	 * Test the post context with a field that should not be in the response.
	 */
	function test_fieldmanager_no_rest_api_post() {
		// Add actions for post context.
		add_action( 'fm_post_posts', array( $this, '_fm_no_post_test_fields' ) );
		add_action( 'fm_post_posts', array( $this, '_fm_no_post_test_fields' ) );

		// Create the post.
		$post_id = $this->factory->post->create();

		// Add data.
		$test_data = rand_str();
		update_post_meta( $post_id, $this->test_field, $test_data );

		// Process the REST API call.
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertArrayNotHasKey( $this->test_field, $data );
	}

	/**
	 * Test the post context with a filter
	 */
	function test_fieldmanager_rest_api_post_filter() {
		// Add actions for post context.
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );
		add_action( 'fm_rest_get', array( $this, '_fm_post_test_fields_filter' ), 10, 5 );

		// Create the post.
		$post_id = $this->factory->post->create();

		// Add data.
		$test_data = rand_str();
		update_post_meta( $post_id, $this->test_field, $test_data );

		// Process the REST API call.
		$request = new WP_REST_Request( 'GET', '/wp/v2/posts/' . $post_id );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( $data[ $this->test_field ], $this->new_test_data );
	}

	/**
	 * Test the term context.
	 */
	function test_fieldmanager_rest_api_term() {
		// Add actions for term context.
		add_action( 'fm_term_category', array( $this, '_fm_term_test_fields' ) );
		add_action( 'fm_term_categories', array( $this, '_fm_term_test_fields' ) );

		// Create the post.
		$term_id = $this->factory->category->create();

		// Add data.
		$test_data = rand_str();
		update_term_meta( $term_id, $this->test_field, $test_data );

		// Process the REST API call.
		$request = new WP_REST_Request( 'GET', '/wp/v2/categories/' . $term_id );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( $data[ $this->test_field ], $test_data );
	}

	/**
	 * Test the user context.
	 */
	function test_fieldmanager_rest_api_user() {
		// Add actions for user context.
		add_action( 'fm_user', array( $this, '_fm_user_test_fields' ) );
		add_action( 'fm_users', array( $this, '_fm_user_test_fields' ) );

		// Create the post.
		$user_id = $this->factory->user->create();

		// Add data.
		$test_data = rand_str();
		update_metadata( 'user', $user_id, $this->test_field, $test_data );

		// Process the REST API call.
		$request = new WP_REST_Request( 'GET', '/wp/v2/users/' . $user_id );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( $data[ $this->test_field ], $test_data );
	}

	/**
	 * Add post fields.
	 */
	function _fm_post_test_fields() {
		$fm_post = new Fieldmanager_TextField( [
			'name' => $this->test_field,
			'show_in_rest' => true,
		] );
		$fm_post->add_meta_box( __( 'Test Fields', 'fieldmanager' ), array( 'post' ), 'side' );
	}

	/**
	 * Add field without adding it to the REST repsonse.
	 */
	function _fm_no_post_test_fields() {
		$fm_post = new Fieldmanager_TextField( [
			'name' => $this->test_field,
			'show_in_rest' => false,
		] );
		$fm_post->add_meta_box( __( 'NO Test Fields', 'fieldmanager' ), array( 'post' ), 'side' );
	}

	/**
	 * Filter the data returned.
	 *
	 * @param  mixed           $data        The current data returned from the REST API.
	 * @param  array           $object      The REST API object.
	 * @param  string          $field_name  The REST API field name.
	 * @param  WP_REST_Request $request     The full request object from the REST API.
	 * @param  string          $object_type The REST API object type.
	 * @return mixed                        The data published to the REST API.
	 */
	function _fm_post_test_fields_filter( $data, $object, $field_name, $request, $object_type ) {
		if ( $this->test_field === $field_name ) {
			return $this->new_test_data;
		}

		return $data;
	}

	/**
	 * Add term fields.
	 */
	function _fm_term_test_fields() {
		$fm_term = new Fieldmanager_TextField( [
			'name' => $this->test_field,
			'show_in_rest' => true,
		] );
		$fm_term->add_term_meta_box( __( 'Test Fields', 'fieldmanager' ), array( 'category' ) );
	}

	/**
	 * Add user fields.
	 */
	function _fm_user_test_fields() {
		$fm_user = new Fieldmanager_TextField( [
			'name' => $this->test_field,
			'show_in_rest' => true,
		] );
		$fm_user->add_user_form( __( 'Test Fields', 'fieldmanager' ) );
	}
}