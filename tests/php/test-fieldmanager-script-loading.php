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

		// Some fields will only register a script once, so hack around that.
		Fieldmanager_Media::$has_registered_media = false;
		Fieldmanager_Colorpicker::$has_registered_statics = false;

		// Instantiate field classes that register scripts.
		new Fieldmanager_Autocomplete( 'Test', array( 'datasource' => new Fieldmanager_Datasource_Post ) );
		new Fieldmanager_Datepicker( 'Test' );
		new Fieldmanager_DraggablePost( 'Test' );
		new Fieldmanager_Grid( 'Test' );
		new Fieldmanager_Group( 'Test', array( 'tabbed' => 'horizontal' ) );
		new Fieldmanager_Media( 'Test' );
		new Fieldmanager_Select( 'Test' );
		new Fieldmanager_RichTextArea( 'Test' );
		new Fieldmanager_Colorpicker( 'Test' );

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
	 * Provide test data.
	 *
	 * @return array {
	 *     @type string $handle Script handle.
	 *     @type array $deps Other scripts that $handle should depend on.
	 * }
	 */
	public function script_data() {
		return array(
			array( 'fieldmanager_script', array( 'jquery' ) ),
			array( 'fm_autocomplete_js', array( 'fieldmanager_script' ) ),
			array( 'fm_datepicker', array( 'fieldmanager_script' ) ),
			array( 'fm_draggablepost_js', array() ),
			array( 'fm_group_tabs_js', array( 'jquery', 'jquery-hoverintent' ) ),
			array( 'fm_media', array( 'jquery' ) ),
			array( 'fm_richtext', array( 'jquery', 'fieldmanager_script' ) ),
			array( 'fm_select_js', array() ),
			array( 'grid', array() ),
			array( 'fm_colorpicker', array( 'jquery', 'wp-color-picker' ) ),
		);
	}

	/**
	 * @dataProvider script_data
	 */
	function test_script_is_registered( $handle ) {
		global $wp_scripts;
		$this->assertInstanceOf( '_WP_Dependency', $wp_scripts->query( $handle ) );

	}

	/**
	 * @dataProvider script_data
	 */
	function test_script_dependencies( $handle, $deps ) {
		global $wp_scripts;
		$this->assertEquals( $deps, $wp_scripts->query( $handle )->deps );
	}

}
