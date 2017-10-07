<?php
/**
 * Framework for user-facing data validation.
 *
 * @package Fieldmanager_Util
 */

class Fieldmanager_Util_Validation {

	/**
	 * Instance of this class
	 *
	 * @var Fieldmanager_Util_Validation
	 * @access private
	 */
	private static $instance;

	/**
	 * @var array
	 * @access private
	 * Array of Fieldmanager fields that require validation
	 */
	private $fields = array();

	/**
	 * @var array
	 * @access private
	 * Rules for each of the fields to be validated
	 */
	private $rules = array();

	/**
	 * @var array
	 * @access private
	 * Messages to override the default and display when a field is invalid
	 */
	private $messages = array();

	/**
	 * @var string
	 * @access private
	 * The form ID that requires validation
	 */
	private $form_id;

	/**
	 * @var string
	 * @access private
	 * The context this form appears in
	 */
	private $context;

	/**
	 * @var array
	 * @access private
	 * The allowed validation rules
	 */
	private $valid_rules = array( 'required', 'remote', 'minlength', 'maxlength', 'rangelength', 'min', 'max', 'range', 'email', 'url', 'date', 'dateISO', 'number', 'digits', 'creditcard', 'equalTo' );

	/**
	 * Singleton helper
	 *
	 * @param array $form_ids
	 * @param string $context
	 * @return object The singleton instance
	 */
	public static function instance( $form_id, $context ) {
		// The current form ID and context are used to generate a global variable name to store the instance.
		// This is necessary so that it persists until all Fieldmanager fields with validation for the current form and context are added.
		$global_id = $form_id . '_' . $context;

		// Check if this global is set.
		// If yes, return it.
		// If not, initialize the singleton instance, set it to the global and return it.
		if ( isset( $GLOBALS[$global_id] ) ) {
			return $GLOBALS[$global_id];
		} else {
			self::$instance = new Fieldmanager_Util_Validation;
			self::$instance->setup( $form_id, $context );
			$GLOBALS[$global_id] = self::$instance;
			return self::$instance;
		}
	}

	/**
	 * Add scripts, initialize variables and add action hooks
	 *
	 * @access private
	 * @param string $form_id
	 * @param string $context
	 * @return Fieldmanager_Util_Validation
	 */
	private function setup( $form_id, $context ) {
		// Set class variables
		$this->form_id = $form_id;
		$this->context = $context;

		// Add the appropriate action hook to finalize and output validation JS
		// Also determine where the jQuery validation script needs to be added
		if ( $context == 'page' ) {
			// Currently only the page context outputs to the frontend
			$action = 'wp_footer';
			$admin = false;
		} else {
			// All other contexts are used only on the admin side
			$action = 'admin_footer';
			$admin = true;
		}

		// Hook the action
		add_action( $action, array( &$this, 'add_validation' ) );
	}

	/**
	 * Check if a field has validation enabled and if so add it
	 *
	 * @access public
	 * @param Fieldmanager_Field $fm
	 */
	public function add_field( &$fm ) {
		// If this field is a Fieldmanager_Group, iterate over the children
		if ( get_class( $fm ) == "Fieldmanager_Group" ) {
			foreach ( $fm->children as $child ) {
				$this->add_field( $child );
			}
		}

		// Check if this field has validation enabled. If not, return.
		if ( empty( $fm->validation_rules ) )
			return;

		// Determine if the rules are a string or an array and ensure they are valid.
		// Also aggregate any messages that were set for the rules, ignoring any messages that don't match a rule.
		$messages = "";
		if ( ! is_array( $fm->validation_rules ) ) {
			// If a string, the only acceptable value is "required".
			if ( ! is_string( $fm->validation_rules ) || $fm->validation_rules != 'required' )
				$fm->_invalid_definition( sprintf( __( 'The validation rule "%s" does not exist.', 'wordpress-fieldmanager' ), $fm->validation_rules ) );

			// Convert the value to an array since we standardize the Javascript output on this format
			$fm->validation_rules = array( 'required' => true );

			// In this instance, messages must either be a string or empty. If valid and defined, store this.
			if ( ! empty( $fm->validation_messages ) && is_string( $fm->validation_messages ) )
				$messages['required'] = $fm->validation_messages;

		} else {
			// Verify each rule defined in the array is valid and also check for any messages that were defined for each.
			foreach ( $fm->validation_rules as $validation_key => $validation_rule ) {
				if ( ! in_array( $validation_key, $this->valid_rules ) ) {
					// This is not a rule available in jQuery validation
					$fm->_invalid_definition( sprintf( __( 'The validation rule "%s" does not exist.', 'wordpress-fieldmanager' ), $validation_key ) );
				} else {
					// This rule is valid so check for any messages
					if ( isset( $fm->validation_messages[$validation_key] ) )
						$messages[$validation_key] = $fm->validation_messages[$validation_key];
				}
			}
		}

		// If this is the term context and the field is required, modify the original element to have the required property.
		// This is necessary because it is the only way validation is supported on the term add form.
		// Other validation methods won't work and will just fail gracefully.
		if ( $this->context == 'term' && isset( $fm->validation_rules['required'] ) && $fm->validation_rules['required'] )
			$fm->required = true;

		// If we have reached this point, there were no errors so store the field and the corresponding rules and messages
		$name = $fm->get_form_name();
		$this->fields[] = $name;
		$this->rules[$name] = $fm->validation_rules;
		$this->messages[$name] = $messages;
	}

	/**
	 * Output the Javascript required for validation, if any fields require it
	 *
	 * @access public
	 */
	public function add_validation() {
		if ( ! empty( $this->rules ) ) {
			// Fields that should always be ignored
			$ignore = array();
			$ignore[] = ".fm-autocomplete";
			$ignore[] = "input[type='button']";
			$ignore[] = ":hidden";

			// Certain fields need to be ignored depending on the context
			switch ( $this->context ) {
				case "post":
					$ignore[] = "#active_post_lock";
					break;
			}

			// Fields that contain hidden inputs and still need to be verified, ie: image fields
			$force = array();
			$force[] = '.fm-media-id';

			// Add the Fieldmanager validation script and CSS
			// This is not done via the normal enqueue process since there is no way to know at that point if any fields will require validation
			// Doing this here avoids loading JS/CSS for validation if not in use
			echo "<link rel='stylesheet' id='fm-validation-css' href='" . fieldmanager_get_baseurl() . "css/fieldmanager-validation.css' />\n";

			// Add the jQuery validation script
			wp_enqueue_script( 'jquery-validate', fieldmanager_get_baseurl() . 'js/validation/jquery.validate.min.js', array( 'jquery' ), '1.11.1', true );
			wp_enqueue_script( 'fm-validation', fieldmanager_get_baseurl() . 'js/validation/fieldmanager-validation.js', array( 'jquery-validate' ), FM_VERSION, true );


			$validation_data = apply_filters( 'fm_validation_options', array(
				'form_id' => $this->form_id,
				'ignore' => $ignore,
				'force' => $force,
				'options' => array(
					'errorClass' => 'fm-js-error',
					'rules' => array_filter( $this->rules ),
					'messages' => array_filter( $this->messages ),
				)
			) );

			wp_localize_script( 'fm-validation', 'FM_VALIDATION_OPTIONS', $validation_data );
		}
	}
}

/**
 * Singleton helper for Fieldmanager_Util_Validation
 *
 * @param string $form_id
 * @param string $context
 * @return object
 */
function Fieldmanager_Util_Validation( $form_id, $context ) {
	return Fieldmanager_Util_Validation::instance( $form_id, $context );
}
