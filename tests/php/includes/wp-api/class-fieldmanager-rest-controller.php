<?php

class Fieldmanager_REST_API_Controller extends WP_Test_REST_TestCase {

	protected $server;

	public function setUp() {
		parent::setUp();
		add_filter( 'rest_url', array( $this, 'filter_rest_url_for_leading_slash' ), 10, 2 );
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$this->server = $wp_rest_server = new WP_Test_Spy_REST_Server;
		do_action( 'rest_api_init' );
	}

	public function tearDown() {
		parent::tearDown();
		remove_filter( 'rest_url', array( $this, 'test_rest_url_for_leading_slash' ), 10, 2 );
		/** @var WP_REST_Server $wp_rest_server */
		global $wp_rest_server;
		$wp_rest_server = null;
	}

	public function filter_rest_url_for_leading_slash( $url, $path ) {
		// Make sure path for rest_url has a leading slash for proper resolution.
		$this->assertTrue( 0 === strpos( $path, '/' ) );

		return $url;
	}
}
