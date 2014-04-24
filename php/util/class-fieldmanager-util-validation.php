<?php
/**
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
				$fm->_invalid_definition( 'The validation rule ' . $fm->validation_rules . ' does not exist.' );
			
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
					$fm->_invalid_definition( 'The validation rule ' . $validation_key . ' does not exist.' );
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
		// Iterate through the fields and output the required Javascript
		$rules = array();
		$messages = array();
		foreach ( $this->fields as $field ) {
			// Add the rule string to an array
			$rule = $this->value_to_js( $field, $this->rules );
			if ( ! empty( $rule ) ) {
				$rules[] = $rule;
			
				// Add the message to an array, if it exists
				$message = $this->value_to_js( $field, $this->messages );
				if ( ! empty( $message ) )
					$messages[] = $message;
			}
		}
		
		// Create final rule string
		if ( ! empty( $rules ) ) {
			$rules_js = $this->array_to_js( $rules, "rules" );
			$messages_js = $this->array_to_js( $messages, "messages" );	
			
			// Add a comma and newline if messages is not empty
			if ( ! empty( $messages_js ) ) {
				$rules_js .= ",\n";
			}
			
			// Fields that should always be ignored
			$ignore[] = ".fm-autocomplete";
			$ignore[] = "input[type='button']";
			$ignore[] = ":hidden";
			
			// Certain fields need to be ignored depending on the context
			switch ( $this->context ) {
				case "post":
					$ignore[] = "#active_post_lock";
					break;
			}
			
			// Add JS for fields to ignore
			$ignore_js = implode( ", ", $ignore );
			
			// Add the Fieldmanager validation script and CSS
			// This is not done via the normal enqueue process since there is no way to know at that point if any fields will require validation
			// Doing this here avoids loading JS/CSS for validation if not in use
			echo "<link rel='stylesheet' id='fm-validation-css' href='" . fieldmanager_get_baseurl() . "css/fieldmanager-validation.css' />\n";
			echo "<script type='text/javascript' src='" . fieldmanager_get_baseurl() . "js/validation/fieldmanager-validation.js?ver=0.3'></script>\n";
			
			// Add the jQuery validation script
			echo "<script type='text/javascript' src='" . fieldmanager_get_baseurl() . "js/validation/jquery.validate.min.js'></script>\n";
					
			// Add the ignore, rules and messages to final validate method with form ID, wrap in script tags and output
			echo sprintf(
				"\t<script type='text/javascript'>\n\t\t( function( $ ) {\n\t\t$( document ).ready( function () {\n\t\t\tvar validator = $( '#%s' ).validate( {\n\t\t\t\tinvalidHandler: function( event, validator ) { fm_validation.invalidHandler( event, validator ); },\n\t\t\t\tsubmitHandler: function( form ) { fm_validation.submitHandler( form ); },\n\t\t\t\terrorClass: \"fm-js-error\",\n\t\t\t\tignore: \"%s\",\n%s%s\n\t\t\t} );\n\t\t} );\n\t\t} )( jQuery );\n\t</script>\n",
				esc_js( $this->form_id ),
				$ignore_js,
				$rules_js,
				$messages_js
			);
		}	
	}
	
	/**
	 * Converts a single rule or message value into Javascript
	 *
	 * @access private
	 * @param string $field
	 * @param string $data
	 * @return string The Javascript output or an empty string if no data was provided
	 */
	private function value_to_js( $field, $data ) {
		// Check the array for the corresponding value. If it doesn't exist, return an empty string.
		if ( empty( $data[$field] ) )
			return "";
			
		// Format the field name
		$name = $this->quote_field_name( $field );
		
		// Iterate over the values convert them into a single string
		$values = array();
		foreach ( $data[$field] as $k => $v ) {
			$values[] = sprintf(
				"\t\t\t\t\t\t%s: %s",
				esc_js( $k ),
				$this->format_value( $v )
			);
		}
		
		// Convert the array to a string
		$value = sprintf(
			"{\n%s\n\t\t\t\t\t}",
			implode( ",\n", $values )
		);
		
		// Combine the name and value and return it
		return sprintf(
			"\t\t\t\t\t%s: %s",
			$name,
			$value
		);
	}
	
	/**
	 * Converts an array of values into Javascript 
	 *
	 * @access private
	 * @param array $data
	 * @param string $label
	 * @return string The Javascript output or an empty string if no data was provided
	 */
	private function array_to_js( $data, $label ) {
		return sprintf(
			"\t\t\t\t%s: {\n%s\n\t\t\t\t}",
			esc_js( $label ),
			implode( ",\n", $data )
		);
	}
	
	/**
	 * Converts a PHP value to the required format for Javascript
	 *
	 * @access private
	 * @param string $value
	 * @return string The formatted value
	 */
	private function format_value( $value ) {
		// Determine the data type and return the value formatted appropriately
		if ( is_bool( $value ) ) {
			// Convert the value to a string
			return ( $value ) ? "true" : "false";
		} else if ( is_numeric( $value ) ) {
			// Return as-is
			return $value;
		} else {
			// For any other type (should only be a string) escape for JS output
			return '"' . esc_js( $value ) . '"';
		}
	}
		
	/**
	 * Determine if the field name needs to be quoted for Javascript output
	 *
	 * @access private
	 * @param string $field
	 * @return string The field name with quotes added if necessary
	 */
	private function quote_field_name( $field ) {
		// Check if the field name is alphanumeric (underscores and dashes are allowed)
		if ( ctype_alnum( str_replace( array( '_', '-'), '', $field ) ) )
			return $field;
		else
			return '"' . esc_js( $field ) . '"';
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
