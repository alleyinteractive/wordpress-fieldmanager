<?php
/**
 * Class file for Fieldmanager_Context_Customizer.
 *
 * @package Fieldmanager_Context
 */

/**
 * Add Fieldmanager fields to the Customizer.
 */
class Fieldmanager_Context_Customizer extends Fieldmanager_Context {
	/**
	 * The value of the setting before any changes are submitted via the Customizer.
	 *
	 * @see Fieldmanager_Field::presave_all().
	 *
	 * @var mixed
	 */
	protected $current_value;

	/**
	 * Constructor.
	 *
	 * @param string|array $args {
	 *     The title to use with the {@see WP_Customize_Section}, or arrays of
	 *     arguments to construct the Customizer section and setting.
	 *
	 *     @type array $section_args Arguments for constructing a WP_Customize_Section.
	 *     @type array $setting_args Arguments for constructing a {@see WP_Customize_Setting}.
	 * }
	 * @param Fieldmanager_Field $fm Field object to add to the Customizer.
	 */
	public function __construct( $args, $fm ) {
		if ( is_string( $args ) ) {
			$args = array( 'section_args' => array( 'title' => $args ) );
		}

		$this->args = wp_parse_args( $args, array(
			'section_args' => array(),
			'setting_args' => array(),
		) );

		$this->fm = $fm;

		add_action( 'customize_register', array( $this, 'customize_register' ) );
	}

	/**
	 * Fires once WordPress has loaded in the Customizer.
	 *
	 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
	 */
	public function customize_register( $manager ) {
		$this->register( $manager );
		/*
		 * Get the current setting value for Fieldmanager_Field::presave_all()
		 * before the setting's preview method is called.
		 *
		 * WP_Customize_Setting::preview() adds filters to get_option() and
		 * get_theme_mod() that eventually call the setting's sanitize() method.
		 * Attempting to call WP_Customize_Setting::value() inside of
		 * Fieldmanager_Context_Customizer::sanitize_callback() creates an
		 * infinite loop.
		 */
		$this->init_current_value( $manager );
	}

	/**
	 * Exposes Fieldmanager_Context::render_field() for the control to call.
	 *
	 * @param array $args Unused.
	 */
	public function render_field( $args = array() ) {
		parent::render_field( array(
			'data' => $this->current_value,
		) );
	}

	/**
	 * Filter a Customize setting value in un-slashed form.
	 *
	 * @param mixed $value Setting value.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return mixed The sanitized setting value.
	 */
	public function sanitize_callback( $value, $setting ) {
		if ( is_string( $value ) && 0 === strpos( $value, $this->fm->name ) ) {
			// Parse the query-string version of our values into an array.
			parse_str( $value, $value );
		}

		if ( is_array( $value ) && isset( $value[ $this->fm->name ] ) ) {
			// If the option name is the top-level array key, get just the value.
			$value = $value[ $this->fm->name ];
		}

		// Return the value after Fieldmanager takes a shot at it.
		return stripslashes_deep( $this->prepare_data( $this->current_value, $value ) );
	}

	/**
	 * Create a Customizer section, setting, and control for this field.
	 *
	 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
	 */
	protected function register( $manager ) {
		$manager->add_section( $this->fm->name, wp_parse_args(
			$this->args['section_args'],
			array()
		) );

		// Set Fieldmanager defaults after parsing the user args, then register the setting.
		$setting_args = wp_parse_args(
			$this->args['setting_args'],
			array(
				// Use the capability passed to Fieldmanager_Field::add_customizer_section().
				'capability' => $manager->get_section( $this->fm->name )->capability,
				'type'       => 'option',
			)
		);
		$setting_args['default']           = $this->fm->default_value;
		$setting_args['sanitize_callback'] = array( $this, 'sanitize_callback' );
		$setting_args['section']           = $this->fm->name;
		$manager->add_setting( $this->fm->name, $setting_args );

		$manager->add_control( new Fieldmanager_Customize_Control( $manager, $this->fm->name, array(
			'section' => $this->fm->name,
			'context' => $this,
		) ) );
	}

	/**
	 * Initialize $current_value for the type of Customizer setting.
	 *
	 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
	 */
	protected function init_current_value( $manager ) {
		$this->current_value = $manager->get_setting( $this->fm->name )->value();
	}
}
