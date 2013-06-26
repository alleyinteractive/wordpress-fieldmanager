<?php
/**
 * @package Fieldmanager_Util
 */

class Fieldmanager_Util_Validation  {
	
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
		
		// Add the jQuery validation script
		// http://jqueryvalidation.org/
		fm_add_script( 'fm_validation_js', 'js/validation/jquery.validate.min.js', array(), false, true, "", array(), "", $admin );
	}

	/**
	 * Check if a field has validation enabled and if so add it
	 *
	 * @access public
	 * @param Fieldmanager_Field $fm
	 */
	public function add_field( $fm ) {
		// Check if this field has validation enabled. If not, return.
		if ( empty( $fm->validation_rules ) )
			return;
			
		// Determine if the rules are a string or an array and ensure they are valid.
		// Also aggregate any messages that were set for the rules, ignoring any messages that don't match a rule.
		$messages = "";
		if ( ! is_array( $fm->validation_rules ) ) {
			// If a string, the only acceptable value is "required".
			if ( ! is_string( $fm->validation_rules ) || $fm->validation_rules != 'required' )
				$this->fm->_invalid_definition( 'The validation rule ' . $fm->validation_rules . ' does not exist.' );
			
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
					$this->fm->_invalid_definition( 'The validation rule ' . $validation_key . ' does not exist.' );
				} else {
					// This rule is valid so check for any messages
					if ( isset( $fm->validation_messages[$validation_key] ) )
						$messages[$validation_key] = $fm->validation_messages[$validation_key];
				}
			}
		}
			
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
		// Iterate through the fields and output the required Javascript
		foreach ( $this->fields as $field ) {
			// Get the field name
			$name = $this->quote_field_name( $field );
			
			// Convert boolean values to string
			
			// Add rule string to an array (separate function?), then implode with commas and newlines, maybe add tabs for pretty display in source
			
			// Add message to an array (separate function/same as above?), then implode with commas and newlines, maybe add tabs for pretty display in source
		}
		
		// Create final rule string
		
		// Create final message string
		
		// Add any other options (check http://jqueryvalidation.org/validate)
		
		// Add to final validate method with form ID, wrap in script tags and output
		
	}
	
	/**
	 * Determine if the field name needs to be quoted for Javascript output
	 *
	 * @access private
	 * @param string $field
	 * @return string The field name with quotes added if necessary
	 */
	private function quote_field_name( $field ) {
		if ( ctype_alnum( $field ) )
			return $field;
		else
			return '"' . $field . '"';
	}
}