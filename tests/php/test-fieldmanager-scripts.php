<?php
/**
 * Tests Fieldmanager script-loading.
 *
 * @group scripts
 */
class Test_Fieldmanager_Script_Loading extends WP_UnitTestCase {

	protected $screen, $old_wp_scripts;

	public function setUp() {
		parent::setUp();

		// Spoof is_admin() for fm_add_script().
		$this->screen = get_current_screen();
		set_current_screen( 'dashboard-user' );

		// Re-init scripts. @see Tests_Dependencies_Scripts.
		$this->old_wp_scripts = isset( $GLOBALS['wp_scripts'] ) ? $GLOBALS['wp_scripts'] : null;
		remove_action( 'wp_default_scripts', 'wp_default_scripts' );
		$GLOBALS['wp_scripts'] = new WP_Scripts();
		$GLOBALS['wp_scripts']->default_version = get_bloginfo( 'version' );

		// Instantiate FM classes that register scripts.
		new Fieldmanager_Autocomplete( 'Test', array( 'datasource' => new Fieldmanager_Datasource_Post ) );

		do_action( 'wp_enqueue_scripts' );
		do_action( 'admin_enqueue_scripts' );
	}

	public function tearDown() {
		// Restore current_screen.
		$GLOBALS['current_screen'] = $this->screen;

		// Restore scripts. @see Tests_Dependencies_Scripts.
		$GLOBALS['wp_scripts'] = $this->old_wp_scripts;
		add_action( 'wp_default_scripts', 'wp_default_scripts' );
	}

	/**
	 * [test_script_depencencies description]
	 *
	 * @return [type] [description]
	 */
	function test_script_depencencies() {
		$scripts = wp_scripts();

		$autocomplete = $scripts->query( 'fm_autocomplete_js' );
		$this->assertInstanceOf( '_WP_Dependency', $autocomplete );
		$this->assertContains( 'fieldmanager_script', $autocomplete->deps );
	}

}
