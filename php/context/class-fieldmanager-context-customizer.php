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
	 * @var array $args {
	 *     Arguments to construct Customizer objects.
	 *
	 *     @type array|bool $section_args Arguments for constructing a {@see WP_Customize_Section},
	 *           or false to not create one for this context.
	 *     @type array $setting_args Arguments for constructing a {@see Fieldmanager_Customize_Setting}.
	 *     @type array $control_args Arguments for constructing a {@see Fieldmanager_Customize_Control}.
	 * }
	 */
	protected $args;

	/**
	 * Whether to support the Customizer's setting validation model.
	 *
	 * By default, the context will automatically set this to true when it
	 * detects that the Customizer's validation model is available.
	 *
	 * @var bool
	 */
	public $use_customize_validiation = false;

	/**
	 * Constructor.
	 *
	 * @param array $args Customizer object arguments. @see Fieldmanager_Context_Customizer::args.
	 * @param Fieldmanager_Field $fm Field object to add to the Customizer.
	 */
	public function __construct( $args, $fm ) {
		$this->args = wp_parse_args( $args, array(
			'section_args' => false,
			'setting_args' => array(),
			'control_args' => array(),
		) );

		$this->fm = $fm;

		add_action( 'customize_register', array( $this, 'customize_register' ), 100 );
		add_action( 'customize_save_validation_before', array( $this, 'customize_save_validation_before' ) );
	}

	/**
	 * Fires once WordPress has loaded in the Customizer.
	 *
	 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
	 */
	public function customize_register( $manager ) {
		$this->register( $manager );
	}

	/**
	 * Fires before save validation happens.
	 *
	 * @param WP_Customize_Manager $this WP_Customize_Manager instance.
	 */
	public function customize_save_validation_before( $manager ) {
		// This hook facilitates "just-in-time" support for validation.
		$this->use_customize_validiation = true;
	}

	/**
	 * Exposes Fieldmanager_Context::render_field() for the control to call.
	 *
	 * @param array $args Unused.
	 */
	public function render_field( $args = array() ) {
		return parent::render_field( $args );
	}

	/**
	 * Filter a Customize setting value in un-slashed form.
	 *
	 * @param mixed $value Setting value.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return mixed The sanitized setting value; WP_Error on invalidity.
	 */
	public function sanitize_callback( $value, $setting ) {
		if ( is_string( $value ) && 0 === strpos( $value, $this->fm->name ) ) {
			// Parse the query-string version of our values into an array.
			parse_str( $value, $value );
		}

		if ( is_array( $value ) && array_key_exists( $this->fm->name, $value ) ) {
			// If the option name is the top-level array key, get just the value.
			$value = $value[ $this->fm->name ];
		}

		$validity = $this->validate_callback( new WP_Error(), $value, $setting );

		if ( $validity->get_error_message() ) {
			if ( $this->use_customize_validiation ) {
				return $validity;
			}

			/*
			 * Returning null is a backwards-compatible way to reject a value
			 * from WP_Customize_Setting::sanitize(). See
			 * https://core.trac.wordpress.org/ticket/34893.
			 */
			return null;
		}

		// Return the value after Fieldmanager takes a shot at it.
		return stripslashes_deep( $this->prepare_data( $setting->value(), $value ) );
	}

	/**
	 * Filter the validity of a Customize setting value.
	 *
	 * @param WP_Error $validity Object with error messages, if any.
	 * @param mixed $value Setting value.
	 * @param WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return WP_Error Object with an added message from Fieldmanager, if any.
	 */
	public function validate_callback( $validity, $value, $setting ) {
		try {
			$this->prepare_data( $setting->value(), $value );
		} catch ( FM_Validation_Exception $e ) {
			if ( ! is_wp_error( $validity ) ) {
				$validity = new WP_Error();
			}

			$validity->add( 'fieldmanager', $e->getMessage() );
		}

		return $validity;
	}

	/**
	 * Create a Customizer section, setting, and control for this field.
	 *
	 * @param WP_Customize_Manager $manager WP_Customize_Manager instance.
	 */
	protected function register( $manager ) {
		$this->register_section( $manager );
		$this->register_setting( $manager );
		$this->register_control( $manager );
	}

	/**
	 * Add a Customizer section for this field.
	 *
	 * @param WP_Customize_Manager $manager
	 * @return WP_Customize_Section|void Section object, where supported, if created.
	 */
	protected function register_section( $manager ) {
		if ( false === $this->args['section_args'] ) {
			return;
		}

		return $manager->add_section( $this->fm->name, $this->args['section_args'] );
	}

	/**
	 * Add a Customizer setting for this field.
	 *
	 * By default, Fieldmanager registers one setting for a group and sends all
	 * of the group data from the Customizer, rather than individual settings
	 * for its children, so sanitization and validation routines can access the
	 * full group data.
	 *
	 * @param WP_Customize_Manager $manager
	 * @return Fieldmanager_Customize_Setting Setting object, where supported.
	 */
	protected function register_setting( $manager ) {
		return $manager->add_setting(
			new Fieldmanager_Customize_Setting( $manager, $this->fm->name, wp_parse_args(
				$this->args['setting_args'],
				array(
					'context' => $this,
				)
			) )
		);
	}

	/**
	 * Add a Customizer control for this field.
	 *
	 * @param WP_Customize_Manager $manager
	 * @return Fieldmanager_Customize_Control Control object, where supported.
	 */
	protected function register_control( $manager ) {
		return $manager->add_control(
			new Fieldmanager_Customize_Control( $manager, $this->fm->name, wp_parse_args(
				$this->args['control_args'],
				array(
					'section' => $this->fm->name,
					'context' => $this,
				)
			) )
		);
	}
}
