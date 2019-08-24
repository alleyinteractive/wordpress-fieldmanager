<?php
/**
 * Base UnitTestCase for Fieldmanager script and style testing.
 */
class Fieldmanager_Assets_Unit_Test_Case extends WP_UnitTestCase {

	protected $screen, $old_wp_scripts;

	public function setUp() {
		parent::setUp();

		// Spoof is_admin() for fm_add_script().
		$this->screen = get_current_screen();
		set_current_screen( 'dashboard-user' );

		// Re-init scripts. @see Tests_Dependencies_Scripts.
		$this->old_wp_scripts = isset( $GLOBALS['wp_scripts'] ) ? $GLOBALS['wp_scripts'] : null;
		remove_action( 'wp_default_scripts', 'wp_default_scripts' );
		$GLOBALS['wp_scripts']                  = new WP_Scripts();
		$GLOBALS['wp_scripts']->default_version = get_bloginfo( 'version' );

		// Some fields will only register a script once, so hack around that.
		Fieldmanager_Field::$enqueued_base_assets    = false;
		Fieldmanager_Media::$has_registered_media    = false;
		Fieldmanager_Util_Assets::instance()->hooked = false;
	}

	public function tearDown() {
		// Restore current_screen.
		$GLOBALS['current_screen'] = $this->screen;

		// Restore scripts. @see Tests_Dependencies_Scripts.
		$GLOBALS['wp_scripts'] = $this->old_wp_scripts;
		add_action( 'wp_default_scripts', 'wp_default_scripts' );

		parent::tearDown();
	}

}
