<?php
/**
 * Tests Fieldmanager script-loading.
 *
 * @group scripts
 */
class Test_Fieldmanager_Script_Loading extends Fieldmanager_Assets_Unit_Test_Case {

	public function set_up() {
		parent::set_up();

		// Instantiate field classes that register scripts.
		new Fieldmanager_Autocomplete( 'Test', array( 'datasource' => new Fieldmanager_Datasource_Post() ) );
		new Fieldmanager_Datepicker( 'Test' );
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
			'fieldmanager_script' => array(
				'fieldmanager_script',
				array( 'fm_loader', 'jquery', 'jquery-ui-sortable' ),
			),
			'fm_autocomplete_js'  => array(
				'fm_autocomplete_js',
				array( 'fm_loader', 'fieldmanager_script', 'jquery-ui-autocomplete' ),
			),
			'fm_datepicker'       => array(
				'fm_datepicker',
				array( 'fm_loader', 'fieldmanager_script', 'jquery-ui-datepicker' ),
			),
			'fm_group_tabs_js'    => array(
				'fm_group_tabs_js',
				array( 'fm_loader', 'jquery', 'jquery-hoverintent' ),
			),
			'fm_media'            => array(
				'fm_media',
				array( 'jquery' ),
			),
			'fm_richtext'         => array(
				'fm_richtext',
				array( 'fm_loader', 'jquery', 'fieldmanager_script', 'utils' ),
			),
			'fm_select_js'        => array(
				'fm_select_js',
				array( 'fm_loader' ),
			),
			'fm_colorpicker'      => array(
				'fm_colorpicker',
				array( 'fm_loader', 'jquery', 'wp-color-picker' ),
			),
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
