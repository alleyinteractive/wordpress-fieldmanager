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
	 * Arguments to construct Customizer objects.
	 *
	 * @var array $args {
	 *     @type array|bool $section_args Arguments for constructing a {@see WP_Customize_Section},
	 *           or false to not create one for this context.
	 *     @type array $setting_args Arguments for constructing a {@see Fieldmanager_Customize_Setting}.
	 *     @type array $control_args Arguments for constructing a {@see Fieldmanager_Customize_Control}.
	 * }
	 */
	protected $args;

	/**
	 * Constructor.
	 *
	 * @param array              $args Customizer object arguments. @see Fieldmanager_Context_Customizer::args.
	 * @param Fieldmanager_Field $fm   Field object to add to the Customizer.
	 */
	public function __construct( $args, $fm ) {
		$this->args = wp_parse_args( $args, array(
			'section_args' => false,
			'setting_args' => array(),
			'control_args' => array(),
		) );

		$this->fm = $fm;

		add_action( 'customize_register', array( $this, 'customize_register' ), 100 );
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
	 * Exposes Fieldmanager_Context::render_field() for the control to call.
	 *
	 * @param array $args Unused.
	 */
	public function render_field( $args = array() ) {
		return parent::render_field( $args );
	}

	/**
	 * Filter the validity of a Customize setting value.
	 *
	 * Amend the `$validity` object via its `WP_Error::add()` method.
	 *
	 * @see WP_Customize_Setting::validate().
	 *
	 * @param WP_Error             $validity Filtered from `true` to `WP_Error` when invalid.
	 * @param mixed                $value    Value of the setting.
	 * @param WP_Customize_Setting $setting  WP_Customize_Setting instance.
	 */
	public function validate_callback( $validity, $value, $setting ) {
		$value = $this->parse_field_query_string( $value );

		// Start assuming calls to wp_die() signal Fieldmanager validation errors.
		$this->start_handling_wp_die();

		try {
			$this->prepare_data( $setting->value(), $value );
		} catch ( Exception $e ) {
			if ( ! is_wp_error( $validity ) ) {
				$validity = new WP_Error();
			}

			/*
			 * Handle all exceptions Fieldmanager might generate, but use the
			 * message from only validation exceptions, which are more
			 * user-friendly. For others, use the generic message from
			 * WP_Customize_Setting::validate().
			 */
			$message = ( $e instanceof FM_Validation_Exception ) ? $e->getMessage() : __( 'Invalid value.', 'fieldmanager' );

			// @see https://core.trac.wordpress.org/ticket/37890 for the use of array( $value ).
			$validity->add( 'fieldmanager', $message, array( $value ) );
		}

		// Resume normal wp_die() handling.
		$this->stop_handling_wp_die();

		return $validity;
	}

	/**
	 * Filter a Customize setting value in un-slashed form.
	 *
	 * @param  mixed                $value   Setting value.
	 * @param  WP_Customize_Setting $setting WP_Customize_Setting instance.
	 * @return mixed                         The sanitized setting value.
	 */
	public function sanitize_callback( $value, $setting ) {
		$value = $this->parse_field_query_string( $value );

		// Run the validation routine in case we need to reject the value.
		$validity = $this->validate_callback( true, $value, $setting );

		if ( is_wp_error( $validity ) ) {
			/*
			 * The 'customize_save_validation_before' action was added with the
			 * Customizer's validation framework. If it fires, assume it's safe
			 * to return a WP_Error to indicate invalid values. Returning null
			 * is a backwards-compatible way to reject a value from
			 * WP_Customize_Setting::sanitize(). See
			 * https://core.trac.wordpress.org/ticket/34893.
			 */
			return ( did_action( 'customize_save_validation_before' ) ) ? $validity : null;
		}

		// Return the value after Fieldmanager takes a shot at it.
		return stripslashes_deep( $this->prepare_data( $setting->value(), $value ) );
	}

	/**
	 * Filter the callback for killing WordPress execution.
	 *
	 * Fieldmanager calls wp_die() to signal some errors, but messages passed to
	 * wp_die() are not automatically displayed in the Customizer. This filter
	 * should return a callback that throws the message passed to wp_die() as an
	 * exception, which the default validation callback in this context can
	 * catch and convert to a WP_Error.
	 *
	 * @return callable Callback function name.
	 */
	public function on_filter_wp_die_handler() {
		/*
		 * Side effect: We don't want execution to stop, so remove all other
		 * filters because they presumably assume the opposite. See, e.g.,
		 * WP_Customize_Manager::remove_preview_signature().
		 */
		remove_all_filters( current_filter() );

		// Return the new callback.
		return array( $this, 'wp_die_handler' );
	}

	/**
	 * Handle wp_die() by throwing an exception instead of killing execution.
	 *
	 * @throws FM_Validation_Exception With the message passed to wp_die().
	 *
	 * @param string|WP_Error $message Error message or WP_Error object.
	 * @param string          $title   Optional. Error title.
	 * @param string|array    $args    Optional. Arguments to control behavior.
	 */
	public function wp_die_handler( $message, $title, $args ) {
		if ( is_wp_error( $message ) ) {
			$message = $message->get_error_message();
		}

		/*
		 * Modify $message in two ways that follow from our assumption that
		 * Fieldmanager generated this wp_die(): Remove the blank lines and
		 * "back button" message, and unescape HTML.
		 */
		throw new FM_Validation_Exception( preg_replace( '#\n\n.*?$#', '', htmlspecialchars_decode( $message, ENT_QUOTES ) ) );
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
	 * @param  WP_Customize_Manager $manager WP_Customize_Manager instance.
	 * @return WP_Customize_Section|void     Section object, where supported, if created.
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
	 * of the group values from the Customizer, rather than individual settings
	 * for its children, so sanitization and validation routines can access the
	 * full group data.
	 *
	 * @param  WP_Customize_Manager $manager WP_Customize_Manager instance.
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
	 * @param  WP_Customize_Manager $manager WP_Customize_Manager instance.
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

	/**
	 * Decode form element values for this field from a URL-encoded string.
	 *
	 * @param  mixed $value Value to parse.
	 * @return mixed
	 */
	protected function parse_field_query_string( $value ) {
		if ( is_string( $value ) && 0 === strpos( $value, $this->fm->name ) ) {
			// Parse the query-string version of our values into an array.
			parse_str( $value, $value );
		}

		if ( is_array( $value ) && array_key_exists( $this->fm->name, $value ) ) {
			// If the option name is the top-level array key, get just the value.
			$value = $value[ $this->fm->name ];
		}

		return $value;
	}

	/**
	 * Add filters that convert calls to wp_die() into exceptions.
	 *
	 * @return bool Whether the filters were added.
	 */
	protected function start_handling_wp_die() {
		return (
			add_filter( 'wp_die_ajax_handler', array( $this, 'on_filter_wp_die_handler' ), 0 )
			&& add_filter( 'wp_die_handler', array( $this, 'on_filter_wp_die_handler' ), 0 )
		);
	}

	/**
	 * Remove filters that convert calls to wp_die() into exceptions.
	 *
	 * @return bool Whether the filters were removed.
	 */
	protected function stop_handling_wp_die() {
		return (
			remove_filter( 'wp_die_ajax_handler', array( $this, 'on_filter_wp_die_handler' ), 0 )
			&& remove_filter( 'wp_die_handler', array( $this, 'on_filter_wp_die_handler' ), 0 )
		);
	}
}
