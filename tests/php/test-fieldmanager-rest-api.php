<?php
/**
 * Tests Fieldmanager REST API functionality
 */
class Test_Fieldmanager_REST_API extends Fieldmanager_REST_API_Controller {

	function setup() {
		// Add actions for post context.
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );
		add_action( 'fm_post_posts', array( $this, '_fm_post_test_fields' ) );

		// Add actions for term context.
		add_action( 'fm_term_category', array( $this, '_fm_term_test_fields' ) );
		add_action( 'fm_term_categories', array( $this, '_fm_term_test_fields' ) );

		// Add actions for user context.
		add_action( 'fm_user', array( $this, '_fm_user_test_fields' ) );
		add_action( 'fm_users', array( $this, '_fm_user_test_fields' ) );

		$this->test_field = rand_str();

		parent::setup();
	}

	function tearDown() {
		parent::tearDown();

		wp_set_current_user( 0 );
	}

	function test_fieldmanager_rest_api_post() {
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

	function test_fieldmanager_rest_api_term() {
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

	function test_fieldmanager_rest_api_user() {
		// Create the post.
		$user_id = $this->factory->user->create();

		// Add data.
		$test_data = rand_str();
		update_user_meta( $user_id, $this->test_field, $test_data );

		$admin_id = $this->factory->user->create( array(
			'role' => 'administrator',
		) );
		wp_set_current_user( $admin_id );

		// Process the REST API call.
		$request = new WP_REST_Request( 'GET', '/wp/v2/users/' . $user_id );
		$response = $this->server->dispatch( $request );
		$data = $response->get_data();

		$this->assertEquals( $data[ $this->test_field ], $test_data );
	}

	function _fm_post_test_fields() {
		$fm_text = new Fieldmanager_TextField( [
			'name' => $this->test_field,
			'show_in_rest' => true,
		] );
		$fm_text->add_meta_box( __( 'Test Fields', 'fieldmanager' ), array( 'post' ), 'side' );
	}

	function _fm_term_test_fields() {
		$fm_text = new Fieldmanager_TextField( [
			'name' => $this->test_field,
			'show_in_rest' => true,
		] );
		$fm_text->add_term_form( __( 'Test Fields', 'fieldmanager' ), array( 'category' ) );
	}

	function _fm_user_test_fields() {
		$fm_text = new Fieldmanager_TextField( [
			'name' => $this->test_field,
			'show_in_rest' => true,
		] );
		$fm_text->add_user_form( __( 'Test Fields', 'fieldmanager' ) );
	}
}
