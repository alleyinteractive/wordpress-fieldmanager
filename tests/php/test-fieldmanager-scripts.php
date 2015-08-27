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
		new Fieldmanager_Datepicker( 'Test' );
		new Fieldmanager_DraggablePost( 'Test' );
		new Fieldmanager_Grid( 'Test' );
		new Fieldmanager_Group( 'Test', array( 'tabbed' => 'horizontal' ) );
		new Fieldmanager_Media( 'Test' );
		new Fieldmanager_Select( 'Test' );
		new Fieldmanager_RichTextArea( 'Test' );

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
	 * Provide data for test_script_depencencies.
	 *
	 * @return array {
	 *     @type string $handle Script handle.
	 *     @type bool $deps Whether $handle depends on 'fieldmanager_script'.
	 * }
	 */
	public function script_handles() {
		return array(
			array( 'fm_autocomplete_js', true ),
			array( 'fm_datepicker', true ),
			array( 'fm_draggablepost_js', false ),
			array( 'fm_group_tabs_js', false ),
			array( 'fm_media', false ),
			array( 'fm_richtext', true ),
			array( 'fm_select_js', false ),
			array( 'grid', false ),
		);
	}

	/**
	 * @dataProvider script_handles
	 */
	function test_script_registration( $handle ) {
		$scripts = wp_scripts();
		$this->assertInstanceOf( '_WP_Dependency', $scripts->query( $handle ) );

	}

	/**
	 * @dataProvider script_handles
	 */
	function test_script_depencencies( $handle, $deps ) {
		$scripts = wp_scripts();

		if ( $deps ) {
			$this->assertContains( 'fieldmanager_script', $scripts->query( $handle )->deps );
		} else {
			$this->assertNotContains( 'fieldmanager_script', $scripts->query( $handle )->deps );
		}
	}

}
