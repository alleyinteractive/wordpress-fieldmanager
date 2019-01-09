<?php
/**
 * Tests Fieldmanager script-loading.
 *
 * @group scripts
 */
class Test_Fieldmanager_Script_Loading extends Fieldmanager_Assets_Unit_Test_Case {

	public function setUp() {
		parent::setUp();

		// Instantiate field classes that register scripts.
		new Fieldmanager_Autocomplete( 'Test', array( 'datasource' => new Fieldmanager_Datasource_Post() ) );
		new Fieldmanager_Datepicker( 'Test' );
		new Fieldmanager_Grid( 'Test' );
		new Fieldmanager_Group( 'Test', array( 'tabbed' => 'horizontal' ) );
		new Fieldmanager_Media( 'Test' );
		new Fieldmanager_Select( 'Test' );
		new Fieldmanager_RichTextArea( 'Test' );
		new Fieldmanager_Colorpicker( 'Test' );

		do_action( 'wp_enqueue_scripts' );
		do_action( 'admin_enqueue_scripts' );
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
			array( 'fieldmanager_script', array( 'jquery', 'jquery-ui-sortable' ) ),
			array( 'fm_autocomplete_js', array( 'fieldmanager_script', 'jquery-ui-autocomplete' ) ),
			array( 'fm_datepicker', array( 'fieldmanager_script', 'jquery-ui-datepicker' ) ),
			array( 'fm_group_tabs_js', array( 'jquery', 'jquery-hoverintent' ) ),
			array( 'fm_media', array( 'jquery' ) ),
			array( 'fm_richtext', array( 'jquery', 'fieldmanager_script', 'utils' ) ),
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
