<?php
/**
 * Test out REST API functionality.
 *
 * @package Tests / REST API
 */

// We can only run these tests if WordPress 4.7.0 or greater is installed.
if ( ! function_exists( 'register_rest_field' ) ) {
	return;
}

/**
 * Tests Fieldmanager REST API functionality
 */
class Test_Fieldmanager_REST_API extends Fieldmanager_REST_API_Controller {

	/**
	 * Peform initial setup.
	 */
	function setUp() {
		parent::setUp();

		// Name of the test field.
		$this->test_field = rand_str();

		// Test data for the filters.
		$this->new_test_data = rand_str();

		// Create an admin user and set them as the current user.
		$this->admin_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
		wp_set_current_user( $this->admin_id );

		if ( is_multisite() ) {
			$admin = wp_get_current_user();
			update_site_option( 'site_admins', array( $admin->user_login ) );
		}
	}

	/**
	 * Peform the tear down.
	 */
	function tearDown() {
		parent::tearDown();

		wp_set_current_user( 0 );
	}

	/**
	 * Test retrieving data from the post context.
	 */
	function test_fieldmanager_rest_api_post_get() {
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
	 * Test updating from the post context.
	 */
	function test_fieldmanager_rest_api_post_update() {
		// Add actions for post context.
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );

		// Create the post.
		$post_id = $this->factory->post->create();

		// Add data.
		$test_data = rand_str();

		// Process the REST API call.
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts/' . $post_id );
		$request->set_body_params( array(
			$this->test_field => $test_data,
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( $data[ $this->test_field ], $test_data );
	}

	/**
	 * Test the post context with a field that should not be in the response.
	 */
	function test_fieldmanager_no_rest_api_post_get() {
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
	function test_fieldmanager_rest_api_post_get_filter() {
		// Add actions for post context.
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );
		add_action( 'fm_rest_get', array( $this, '_fm_post_get_test_fields_filter' ), 10, 5 );

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
	 * Test updating post data with the update filter.
	 */
	function test_fieldmanager_rest_api_post_update_filter() {
		// Add actions for post context.
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );
		add_action( 'fm_rest_update', array( $this, '_fm_post_update_test_fields_filter' ), 10, 5 );

		// Create the post.
		$post_id = $this->factory->post->create();

		// Add data.
		$test_data = rand_str();

		// Process the REST API call.
		$request = new WP_REST_Request( 'POST', '/wp/v2/posts/' . $post_id );
		$request->set_body_params( array(
			$this->test_field => $test_data,
		) );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( get_post_meta( $post_id, $this->test_field, true ), $this->new_test_data );
	}

	/**
	 * Test retrieving data from the term context.
	 */
	function test_fieldmanager_rest_api_term_get() {
		// Add actions for term context.
		add_action( 'fm_term_category', array( $this, '_fm_term_test_fields' ) );
		add_action( 'fm_term_categories', array( $this, '_fm_term_test_fields' ) );

		// Create the term.
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
	 * Test updating from the term context.
	 */
	function test_fieldmanager_rest_api_term_update() {
		// Add actions for term context.
		add_action( 'fm_term_category', array( $this, '_fm_term_test_fields' ) );
		add_action( 'fm_term_categories', array( $this, '_fm_term_test_fields' ) );

		// Create the term.
		$term_id = $this->factory->category->create();

		// Add data.
		$test_data = rand_str();

		// Process the REST API call.
		$request = new WP_REST_Request( 'POST', '/wp/v2/categories/' . $term_id );
		$request->set_body_params( array(
			$this->test_field => $test_data,
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( $data[ $this->test_field ], $test_data );
	}

	/**
	 * Test retrieving data from the user context.
	 */
	function test_fieldmanager_rest_api_user_get() {
		// Add actions for user context.
		add_action( 'fm_user', array( $this, '_fm_user_test_fields' ) );
		add_action( 'fm_users', array( $this, '_fm_user_test_fields' ) );

		// Create the user.
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
	 * Test updating from the user context.
	 */
	function test_fieldmanager_rest_api_user_update() {
		// Add actions for user context.
		add_action( 'fm_user', array( $this, '_fm_user_test_fields' ) );
		add_action( 'fm_users', array( $this, '_fm_user_test_fields' ) );

		// Create the user.
		$user_id = $this->factory->user->create();

		// Add data.
		$test_data = rand_str();

		// Process the REST API call.
		$request = new WP_REST_Request( 'POST', '/wp/v2/users/' . $user_id );
		$request->set_body_params( array(
			$this->test_field => $test_data,
		) );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( $data[ $this->test_field ], $test_data );
	}

	/**
	 * Add post fields.
	 */
	function _fm_post_test_fields() {
		$fm_post = new Fieldmanager_TextField( array(
			'name' => $this->test_field,
			'show_in_rest' => true,
		) );
		$fm_post->add_meta_box( __( 'Test Fields', 'fieldmanager' ), array( 'post' ), 'side' );
	}

	/**
	 * Add field without adding it to the REST repsonse.
	 */
	function _fm_no_post_test_fields() {
		$fm_post = new Fieldmanager_TextField( array(
			'name' => $this->test_field,
			'show_in_rest' => false,
		) );
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
	function _fm_post_get_test_fields_filter( $data, $object, $field_name, $request, $object_type ) {
		if ( $this->test_field === $field_name ) {
			return $this->new_test_data;
		}

		return $data;
	}

	/**
	 * Filter the data to be updated.
	 *
	 * @param  mixed           $data        The current data returned from the REST API.
	 * @param  array           $object      The REST API object.
	 * @param  string          $field_name  The REST API field name.
	 * @param  WP_REST_Request $request     The full request object from the REST API.
	 * @param  string          $object_type The REST API object type.
	 * @return mixed                        The data published to the REST API.
	 */
	function _fm_post_update_test_fields_filter( $data, $object, $field_name, $request, $object_type ) {
		if ( $this->test_field === $field_name ) {
			return $this->new_test_data;
		}

		return $data;
	}

	/**
	 * Add term fields.
	 */
	function _fm_term_test_fields() {
		$fm_term = new Fieldmanager_TextField( array(
			'name' => $this->test_field,
			'show_in_rest' => true,
		) );
		$fm_term->add_term_meta_box( __( 'Test Fields', 'fieldmanager' ), array( 'category' ) );
	}

	/**
	 * Add user fields.
	 */
	function _fm_user_test_fields() {
		$fm_user = new Fieldmanager_TextField( array(
			'name' => $this->test_field,
			'show_in_rest' => true,
		) );
		$fm_user->add_user_form( __( 'Test Fields', 'fieldmanager' ) );
	}
}
