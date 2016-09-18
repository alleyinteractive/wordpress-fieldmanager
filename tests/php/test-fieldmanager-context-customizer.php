<?php
/**
 * @group customize
 */
class Test_Fieldmanager_Context_Customizer extends Fieldmanager_Customizer_UnitTestCase {
	protected $field;
	protected $old_debug;

	function setUp() {
		parent::setUp();
		$this->field = new Fieldmanager_TextField( array( 'name' => 'foo' ) );
		$this->old_debug = Fieldmanager_Field::$debug;
	}

	function tearDown() {
		Fieldmanager_Field::$debug = $this->old_debug;
	}

	/**
	 * Data provider for running a test with field debugging on and off.
	 */
	function data_field_debug() {
		return array(
			array( true ),
			array( false ),
		);
	}

	// Test that no section is created if no section args are passed.
	function test_no_section() {
		new Fieldmanager_Context_Customizer( array(), $this->field );
		$this->assertEmpty( $this->manager->get_section( $this->field->name ) );
	}

	// Test that a section is created even with empty constructor args.
	function test_bare_section() {
		new Fieldmanager_Context_Customizer( array( 'section_args' => array() ), $this->field );
		$this->register();
		$this->assertInstanceOf( 'WP_Customize_Section', $this->manager->get_section( $this->field->name ) );
	}

	// Test that a section is created with a title string.
	function test_section_title() {
		$title = rand_str();

		new Fieldmanager_Context_Customizer( array( 'section_args' => array( 'title' => $title ) ), $this->field );
		$this->register();
		$this->assertSame( $title, $this->manager->get_section( $this->field->name )->title );
	}

	// Test that a section is created with constructor args.
	function test_section_args() {
		$title = rand_str();
		$priority = rand( 0, 100 );

		new Fieldmanager_Context_Customizer( array(
			'section_args' => array(
				'title' => $title,
				'priority' => $priority,
			),
		), $this->field );

		$this->register();

		$actual = $this->manager->get_section( $this->field->name );
		$this->assertSame( $title, $actual->title );
		$this->assertSame( $priority, $actual->priority );
	}

	// Test that a setting is created even without constructor args.
	function test_bare_setting() {
		new Fieldmanager_Context_Customizer( 'Foo', $this->field );
		$this->register();
		$this->assertInstanceOf( 'Fieldmanager_Customize_Setting', $this->manager->get_setting( $this->field->name ) );
	}

	// Test that a setting is created with constructor args.
	function test_setting_args() {
		$capability = 'edit_thing';
		$default = rand_str();

		new Fieldmanager_Context_Customizer( array(
			'setting_args' => array(
				'capability' => $capability,
				'default' => $default
			),
		), $this->field );

		$this->register();

		$actual = $this->manager->get_setting( $this->field->name );
		$this->assertSame( $capability, $actual->capability );
		$this->assertSame( $default, $actual->default );
	}

	// Test that a control is created even without constructor args.
	function test_bare_control() {
		new Fieldmanager_Context_Customizer( 'Foo', $this->field );

		$this->register();

		$actual = $this->manager->get_control( $this->field->name );
		$this->assertInstanceOf( 'Fieldmanager_Customize_Control', $actual );
		$this->assertSame( $this->field->name, $actual->section );
		$this->assertInstanceOf( 'Fieldmanager_Customize_Setting', $actual->settings['default'] );
	}

	// Test that a control is created with constructor args.
	function test_control_args() {
		$label = rand_str();
		$section = rand_str();

		new Fieldmanager_Context_Customizer( array(
			'control_args' => array(
				'label' => $label,
				'section' => $section,
			),
		), $this->field );

		$this->register();

		$actual = $this->manager->get_control( $this->field->name );
		$this->assertSame( $section, $actual->section );
		$this->assertSame( $label, $actual->label );
	}

	// Test that multiple objects are created with constructor args.
	function test_multiple_args() {
		$title = rand_str();
		$theme_supports = rand_str();
		$sanitize_callback = 'absint';
		$type = 'theme_mod';
		$input_attrs = array( rand_str(), rand_str() );

		new Fieldmanager_Context_Customizer( array(
			'section_args' => array(
				'title' => $title,
				'theme_supports' => $theme_supports,
			),
			'setting_args' => array(
				'sanitize_callback' => $sanitize_callback,
				'type' => $type,
			),
			'control_args' => array(
				'input_attrs' => $input_attrs,
			),
		), $this->field );

		$this->register();

		$section = $this->manager->get_section( $this->field->name );
		$this->assertSame( $title, $section->title );
		$this->assertSame( $theme_supports, $section->theme_supports );

		$setting = $this->manager->get_setting( $this->field->name );
		$this->assertSame( $sanitize_callback, $setting->sanitize_callback );
		$this->assertSame( $type, $setting->type );

		$control = $this->manager->get_control( $this->field->name );
		$this->assertSame( $input_attrs, $control->input_attrs );
	}

	// Make sure validating passes a valid value.
	function test_validate_valid_value() {
		$this->field->validate = array( 'is_numeric' );

		$context = new Fieldmanager_Context_Customizer( 'Foo', $this->field );
		$this->register();

		$validity = rand_str();
		$this->assertSame( $validity, $context->validate_callback( $validity, rand( 1, 100 ), $this->manager->get_setting( $this->field->name ) ) );
	}

	// Make sure validating fails an invalid value.
	function test_validate_invalid_value() {
		$this->field->validate = array( 'is_numeric' );

		$context = new Fieldmanager_Context_Customizer( 'Foo', $this->field );
		$this->register();

		$this->assertWPError( $context->validate_callback( rand_str(), rand_str(), $this->manager->get_setting( $this->field->name ) ) );
	}

	/**
	 * Test that a textfield is sanitized the same way when the value is passed
     * as a bare string and a query string.
	 *
	 * @expectedException FM_Exception
	 */
	function test_sanitize_string() {
		$value = rand_str();

		$context = new Fieldmanager_Context_Customizer( 'Foo', $this->field );
		$this->register();
		$setting = $this->manager->get_setting( $this->field->name );

		$this->assertSame( $value, $context->sanitize_callback( $value, $setting ) );
		$this->assertSame( $value, $context->sanitize_callback( "{$this->field->name}={$value}", $setting ) );
		$context->sanitize_callback( array( 'Not', 'a', 'string' ), $setting );
	}

	/**
	 * Test that a group is sanitized the same way when the value is passed as
	 * an array or a query string.
	 */
	function test_sanitize_group() {
		$fm = new Fieldmanager_Group( array(
			'name'           => 'option_fields',
			'limit'          => 0,
			'children'       => array(
				'repeatable_group' => new Fieldmanager_Group( array(
					'limit'          => 0,
					'children'       => array(
						'text'         => new Fieldmanager_Textfield( 'Text Field' ),
						'autocomplete' => new Fieldmanager_Autocomplete( 'Autocomplete', array( 'datasource' => new Fieldmanager_Datasource_Post() ) ),
						'local_data'   => new Fieldmanager_Autocomplete( 'Autocomplete without ajax', array( 'datasource' => new Fieldmanager_Datasource( array( 'options' => array() ) ) ) ),
						'textarea'     => new Fieldmanager_TextArea( 'TextArea' ),
						'media'        => new Fieldmanager_Media( 'Media File' ),
						'checkbox'     => new Fieldmanager_Checkbox( 'Checkbox' ),
						'radios'       => new Fieldmanager_Radios( 'Radio Buttons', array( 'options' => array( 'One', 'Two', 'Three' ) ) ),
						'select'       => new Fieldmanager_Select( 'Select Dropdown', array( 'options' => array( 'One', 'Two', 'Three' ) ) ),
						'richtextarea' => new Fieldmanager_RichTextArea( 'Rich Text Area' )
					)
				) )
			)
		) );

		$in_as_json = array(
			0 => array(
				'repeatable_group' => array(
					0 => array(
						'text' => 'abcd',
						'autocomplete' => '26',
						'local_data' => '',
						'textarea' => '',
						'media' => '',
						'select' => '',
						'richtextarea' => '',
					),
					1 => array(
						'text' => '',
						'autocomplete' => '',
						'local_data' => '',
						'textarea' => '123456',
						'media' => '30',
						'select' => '',
						'richtextarea' => '',
					),
					'proto' => array(
						'text' => '',
						'autocomplete' => '',
						'local_data' => '',
						'textarea' => '',
						'media' => '',
						'select' => '',
						'richtextarea' => '',
					),
				),
			),
			1 => array(
				'repeatable_group' => array(
					0 => array(
						'text' => '',
						'autocomplete' => '',
						'local_data' => '',
						'textarea' => '',
						'media' => '',
						'checkbox' => '1',
						'radios' => 'Two',
						'select' => 'Three',
						'richtextarea' => '<strong>Proin mi arcu, porttitor vel tellus vel, lobortis suscipit risus.</strong> Quisque consectetur eu arcu in commodo.',
					),
					'proto' => array(
						'text' => '',
						'autocomplete' => '',
						'local_data' => '',
						'textarea' => '',
						'media' => '',
						'select' => '',
						'richtextarea' => '',
					),
				),
			),
			'proto' => array(
				'repeatable_group' => array(
					0 => array(
						'text' => '',
						'autocomplete' => '',
						'local_data' => '',
						'local_data' => '',
						'textarea' => '',
						'media' => '',
						'checkbox' => '1',
						'select' => '',
						'richtextarea' => '',
					),
					'proto' => array(
						'text' => '',
						'autocomplete' => '',
						'local_data' => '',
						'local_data' => '',
						'textarea' => '',
						'media' => '',
						'select' => '',
						'richtextarea' => '',
					),
				)
			),
		);

		$in_as_serialized = "option_fields%5Bproto%5D%5Brepeatable_group%5D%5Bproto%5D%5Btext%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5Bproto%5D%5Bautocomplete%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5Bproto%5D%5Blocal_data%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5Bproto%5D%5Btextarea%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5Bproto%5D%5Bmedia%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5Bproto%5D%5Bselect%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5Bproto%5D%5Brichtextarea%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5B0%5D%5Btext%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5B0%5D%5Bautocomplete%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5B0%5D%5Blocal_data%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5B0%5D%5Btextarea%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5B0%5D%5Bmedia%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5B0%5D%5Bcheckbox%5D=1&option_fields%5Bproto%5D%5Brepeatable_group%5D%5B0%5D%5Bradios%5D=One&option_fields%5Bproto%5D%5Brepeatable_group%5D%5B0%5D%5Bselect%5D=&option_fields%5Bproto%5D%5Brepeatable_group%5D%5B0%5D%5Brichtextarea%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5Bproto%5D%5Btext%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5Bproto%5D%5Bautocomplete%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5Bproto%5D%5Blocal_data%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5Bproto%5D%5Btextarea%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5Bproto%5D%5Bmedia%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5Bproto%5D%5Bselect%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5Bproto%5D%5Brichtextarea%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5B0%5D%5Btext%5D=abcd&option_fields%5B0%5D%5Brepeatable_group%5D%5B0%5D%5Bautocomplete%5D=26&option_fields%5B0%5D%5Brepeatable_group%5D%5B0%5D%5Blocal_data%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5B0%5D%5Btextarea%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5B0%5D%5Bmedia%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5B0%5D%5Bselect%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5B0%5D%5Brichtextarea%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5B1%5D%5Btext%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5B1%5D%5Bautocomplete%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5B1%5D%5Blocal_data%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5B1%5D%5Btextarea%5D=123456&option_fields%5B0%5D%5Brepeatable_group%5D%5B1%5D%5Bmedia%5D=30&option_fields%5B0%5D%5Brepeatable_group%5D%5B1%5D%5Bselect%5D=&option_fields%5B0%5D%5Brepeatable_group%5D%5B1%5D%5Brichtextarea%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5Bproto%5D%5Btext%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5Bproto%5D%5Bautocomplete%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5Bproto%5D%5Blocal_data%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5Bproto%5D%5Btextarea%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5Bproto%5D%5Bmedia%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5Bproto%5D%5Bselect%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5Bproto%5D%5Brichtextarea%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5B0%5D%5Btext%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5B0%5D%5Bautocomplete%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5B0%5D%5Blocal_data%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5B0%5D%5Btextarea%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5B0%5D%5Bmedia%5D=&option_fields%5B1%5D%5Brepeatable_group%5D%5B0%5D%5Bcheckbox%5D=1&option_fields%5B1%5D%5Brepeatable_group%5D%5B0%5D%5Bradios%5D=Two&option_fields%5B1%5D%5Brepeatable_group%5D%5B0%5D%5Bselect%5D=Three&option_fields%5B1%5D%5Brepeatable_group%5D%5B0%5D%5Brichtextarea%5D=%3Cstrong%3EProin+mi+arcu%2C+porttitor+vel+tellus+vel%2C+lobortis+suscipit+risus.%3C%2Fstrong%3E+Quisque+consectetur+eu+arcu+in+commodo.";

		$expected = array(
			array(
				'repeatable_group' => array(
					array(
						'text' => 'abcd',
						'autocomplete' => 26,
					),
					array(
						'textarea' => '123456',
						'media' => 30,
					),
				),
			),
			array(
				'repeatable_group' => array(
					array(
						'checkbox' => '1',
						'radios' => 'Two',
						'select' => 'Three',
						'richtextarea' => '<p><strong>Proin mi arcu, porttitor vel tellus vel, lobortis suscipit risus.</strong> Quisque consectetur eu arcu in commodo.</p>
',
					),
				),
			),
		);

		$context = new Fieldmanager_Context_Customizer( 'Foo', $fm );
		$this->register();
		$setting = $this->manager->get_setting( $fm->name );

		$this->assertSame( $expected, $context->sanitize_callback( $in_as_json, $setting ) );
		$this->assertSame( $expected, $context->sanitize_callback( $in_as_serialized, $setting ) );
	}

	// Make sure sanitizing strips slashes.
	function test_sanitize_stripslashes() {
		$context = new Fieldmanager_Context_Customizer( 'Foo', $this->field );
		$this->register();
		$this->assertSame( 'Foo "bar" baz', $context->sanitize_callback( 'Foo \"bar\" baz', $this->manager->get_setting( $this->field->name ) ) );
	}

	/**
	 * Make sure sanitizing returns a WP_Error on an invalid value.
	 *
	 * @dataProvider data_field_debug
	 */
	function test_sanitize_invalid_value( $debug ) {
		Fieldmanager_Field::$debug = $debug;
		$this->field->validate = array( 'is_numeric' );

		$context = new Fieldmanager_Context_Customizer( 'Foo', $this->field );
		$this->register();
		do_action( 'customize_save_validation_before' );

		$this->assertWPError( $context->sanitize_callback( rand_str(), $this->manager->get_setting( $this->field->name ) ) );
	}

	/**
	 * Make sure sanitizing returns null on an invalid value where we don't have Customizer validation.
	 *
	 * @dataProvider data_field_debug
	 */
	function test_sanitize_invalid_value_backcompat( $debug ) {
		Fieldmanager_Field::$debug = $debug;
		$this->field->validate = array( 'is_numeric' );

		$context = new Fieldmanager_Context_Customizer( 'Foo', $this->field );
		$this->register();

		$this->assertNull( $context->sanitize_callback( rand_str(), $this->manager->get_setting( $this->field->name ) ) );
	}

	// Make sure the context's rendering method calls the field rendering method.
	function test_render_field() {
		$field = $this->getMockBuilder( 'Fieldmanager_Textfield' )->disableOriginalConstructor()->getMock();
		$context = new Fieldmanager_Context_Customizer( 'Foo', $field );

		$field->expects( $this->once() )->method( 'element_markup' );
		$context->render_field( array( 'echo' => false ) );
	}
}
