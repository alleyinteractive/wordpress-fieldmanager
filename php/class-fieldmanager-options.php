<?php

abstract class Fieldmanager_Options extends Fieldmanager_Field {

	public $data = array();
	public $first_element = array();
	public $taxonomy = null;
	public $taxonomy_args = array();
	public $multiple = false;
	
	public function __construct( $options = array() ) {
	
		parent::__construct($options);
		
		// Sanitization
		$this->sanitize = function( $value ) {
		
			if ( isset( $value ) && is_array( $value ) && !empty( $value ) ) {
				return array_map( 'sanitize_text_field', $value );
			} else {
				return sanitize_text_field( $value );
			}
		};
	
		// If the taxonomy parameter is set, populate the data from the given taxonomy if valid
		if ( $this->taxonomy != null && taxonomy_exists( $this->taxonomy ) ) {
		
			$terms = get_terms ( $this->taxonomy, $this->taxonomy_args );
			foreach ( $terms as $term ) {
				$this->data[] = array( 
					'name' => $term->name,
					'value' => $term->term_id 
				);
			}
		
		}
	
	}
	
	public function form_data_elements( $value ) {
	
		// Add the first element to the data array. This is useful for database-based data sets that require a first element.
		if ( !empty( $this->first_element ) ) array_unshift( $this->data, $this->first_element );
		
		// If the value is not in an array, put it in one since sometimes there will be multiple selects
		if ( !is_array( $value ) && isset( $value ) ) {
			$value = array( $value );
		}
	
		// Output the data for the form. Child classes will handle the output format appropriate for them.
		$form_data_elements_html = "";

		if ( !empty( $this->data ) ) {
			foreach( $this->data as $data_element ) {
				$form_data_elements_html .= $this->form_data_element( $data_element, $value );
			}
		}
		
		return $form_data_elements_html;
	
	}
	
	public abstract function form_data_element( $value );

	public function option_selected( $current_option, $options, $attribute ) {

		if ( ( $options != null && !empty( $options ) ) && in_array( $current_option, $options ) ) return $attribute;
		else return "";
		
	}
	
	public function presave( $value ) {
	
		// Sanitize the value(s)
		$value = call_user_func( $this->sanitize, $value );
		
		// If this is a taxonomy-based field, must also save the value(s) as an object term
		if ( isset( $this->taxonomy ) && isset( $value ) && taxonomy_exists( $this->taxonomy ) ) {
			
			// If the value is not an array, make it one, cast the values to integers and ensure uniqueness		
			if ( !is_array( $value ) ) $tax_values = array( $value ); 
			else $tax_values = $value;
						
			$tax_values = array_map('intval', $tax_values);
    		$tax_values = array_unique( $tax_values );
		
			// Store the terms for this post and taxonomy
			wp_set_object_terms( $this->data_id, $tax_values, $this->taxonomy, false );
					
		}
		
		// Return the sanitized value
		return $value;
		
	}
	
	public function validate( $value ) {

	}

}

require_once( dirname( __FILE__ ) . '/class-fieldmanager-select.php' );

require_once( dirname( __FILE__ ) . '/class-fieldmanager-radios.php' );

require_once( dirname( __FILE__ ) . '/class-fieldmanager-checkboxes.php' );
