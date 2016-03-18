<?php
class Fieldmanager_Customizer_UnitTestCase extends WP_UnitTestCase {
	protected $manager;

	function setUp() {
		parent::setUp();
		require_once ABSPATH . WPINC . '/class-wp-customize-manager.php';
		$this->manager = new WP_Customize_Manager();
	}

	function register() {
		do_action( 'customize_register', $this->manager );
	}
}
