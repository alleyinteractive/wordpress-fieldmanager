<?php
/**
 * @package Fieldmanager
 */

/**
 * Base class for handling option fields, like checkboxes or radios
 * @package Fieldmanager
 */
abstract class Fieldmanager_Options extends Fieldmanager_Field {

	/**
	 * @var array
	 * Full option data, allows grouping
	 */
	public $data = array();

	/**
	 * @var array
	 * Shortcut to data which allows a simple associative array
	 */
	public $options = array();

	/**
	 * @var boolean
	 * Is the data grouped?
	 * E.g. should we use <optgroup>
	 */
	public $grouped = false;

	/**
	 * @var array
	 * Always prepend this element to the $data
	 */
	public $first_element = array();

	/**
	 * @var string
	 * Helper for taxonomy-based option sets; taxonomy name
	 */
	public $taxonomy = null;

	/**
	 * @var array
	 * Helper for taxonomy-based option sets; arguments to find terms
	 */
	public $taxonomy_args = array();

	/**
	 * @var boolean
	 * Allow multiple selections?
	 */
	public $multiple = false;
	
	/**
	 * Add CSS, configure taxonomy, construct parent
	 * @param mixed $options
	 */
	public function __construct( $options = array() ) {
		if ( !empty( $options['options'] ) ) {
			$keys = array_keys( $options['options'] );
			$use_name_as_value = ( array_keys( $keys ) === $keys );
			foreach ( $options['options'] as $k => $v ) {
				$this->data[] = array(
					'name' => $v,
					'value' => $use_name_as_value ? $v : $k,
				);
			}
		}

		parent::__construct($options);
		
		// Add the options CSS
		fm_add_style( 'fm_options_css', 'css/fieldmanager-options.css' );
		
		// Sanitization
		$this->sanitize = function( $value ) {
		
			if ( isset( $value ) && is_array( $value ) && !empty( $value ) ) {
				return array_map( 'sanitize_text_field', $value );
			} else {
				return sanitize_text_field( $value );
			}
		};
		
		// If the taxonomy parameter is set, populate the data from the given taxonomy if valid
		if ( $this->taxonomy != null ) $this->get_taxonomy_data();
	
	}
	
	/**
	 * Generate form elements.
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_data_elements( $value ) {
	
		// Add the first element to the data array. This is useful for database-based data sets that require a first element.
		if ( !empty( $this->first_element ) ) array_unshift( $this->data, $this->first_element );
		
		// If the value is not in an array, put it in one since sometimes there will be multiple selects
		if ( !is_array( $value ) && isset( $value ) ) {
			$value = array( $value );
		}
	
		// Output the data for the form. Child classes will handle the output format appropriate for them.
		$form_data_elements_html = '';

		if ( !empty( $this->data ) ) {
		
			$current_group = '';

			foreach( $this->data as $data_element ) {
			
				// If grouped display is desired, check where to add the start and end points
				// Note we are assuming the data has come pre-sorted into groups
				if( $this->grouped && ( $current_group != $data_element['group'] ) ) {
					
					// Append the end for the previous group unless this is the first group 
					if ( $current_group != '' ) $form_data_elements_html .= $this->form_data_end_group();
					
					// Append the start of the group
					$form_data_elements_html .= $this->form_data_start_group( $data_element['group'] );
					
					// Set the current group
					$current_group = $data_element['group'];
				}
				
				// Get the current element
				$form_data_elements_html .= $this->form_data_element( $data_element, $value );
			}
			
			// If this was grouped display, close the final group
			if( $this->grouped ) $form_data_elements_html .= $this->form_data_end_group();
		}
		
		return $form_data_elements_html;
	
	}
	
	/**
	 * A single element for a single bit of data, e.g. '<option>'
	 * @param mixed $value
	 */
	public abstract function form_data_element( $value );

	/**
	 * Helper for output functions to toggle a selected options
	 * @param string $current_option this option
	 * @param array $options all valid options
	 * @param string $attribute
	 * @return string $attribute on match, empty on failure.
	 */
	public function option_selected( $current_option, $options, $attribute ) {
		if ( ( $options != null && !empty( $options ) ) && in_array( $current_option, $options ) ) return $attribute;
		else return '';
	}
	
	/**
	 * Override presave to handle taxonomy
	 * @param mixed $value
	 * @return void
	 */
	public function presave( $value ) {
	
		// Sanitize the value(s)
		$value = call_user_func( $this->sanitize, $value );
		
		// If this is a taxonomy-based field, must also save the value(s) as an object term
		if ( isset( $this->taxonomy ) && isset( $value ) ) {
			
			// If the value is not an array, make it one, cast the values to integers and ensure uniqueness		
			if ( !is_array( $value ) ) $tax_values = array( $value ); 
			else $tax_values = $value;
						
			$tax_values = array_map('intval', $tax_values);
    		$tax_values = array_unique( $tax_values );
    		
    		// Also assign the taxonomy to an array if it is not one since there may be grouped fields
    		$taxonomies = $this->taxonomy;
    		if ( !is_array( $this->taxonomy ) ) $taxonomies = array( $this->taxonomy );
		
			// Store the each term for this post. Handle grouped fields differently since multiple taxonomies are present.
			if ( is_array( $this->taxonomy ) ) {
				// Build the taxonomy insert data
				$taxonomy_insert_data = $this->get_taxonomy_insert_data( $tax_values );
				foreach ( $taxonomy_insert_data as $taxonomy => $terms ) {
					wp_set_object_terms( $this->data_id, $terms, $taxonomy, false );
				}
			} else {
				wp_set_object_terms( $this->data_id, $tax_values, $this->taxonomy, false );
			}
					
		}
		
		// Return the sanitized value
		return $value;
		
	}
	
	/**
	 * Get taxonomy data per $this->taxonomy_args
	 * @return array[] data entries for options
	 */
	public function get_taxonomy_data() {
	
		// Query for all terms for the defined taxonomies
		$terms = get_terms ( $this->taxonomy, $this->taxonomy_args );
		
		// If the taxonomy list was an array and group display is set, ensure all terms are grouped by taxonomy
		// Use the order of the taxonomy array list for sorting the groups to make this controllable for developers
		// Order of the terms within the groups is already controllable via $taxonomy_args
		// Skip this entirely if there is only one taxonomy even if group display is set as it would be unnecessary
		if ( is_array( $this->taxonomy ) && $this->grouped ) {
			
			// Group the data
			$term_groups = array();
			foreach ( $terms as $term ) {
				$term_groups[$term->taxonomy][] = $term;
			}
						
			// Sort the groups by the provided taxonomy order and replace the original $terms data
			$terms = array();
			foreach ( $this->taxonomy as $tax ) {
				if ( array_key_exists( $tax, $term_groups ) && is_array( $term_groups[$tax] ) ) {
					$terms = array_merge( $terms, $term_groups[$tax] );
				}
			}
			
		}
		
		// Put the taxonomy data into the proper data structure to be used for display
		foreach ( $terms as $term ) {
			// Store the label for the taxonomy as the group since it will be used for display
			$taxonomy_data = get_taxonomy( $term->taxonomy );
		
			$this->data[] = array( 
				'name' => $term->name,
				'value' => $term->term_id,
				'group' => $taxonomy_data->label,
				'group_id' => $taxonomy_data->name
			);
		}
	 
	}
	
	/**
	 * Add taxonomy ID to data values for insert
	 * @param array $values
	 * @return array $values with taxonomy IDs for saving.
	 */
	protected function get_taxonomy_insert_data( $values ) {
	
		// If the option field data was grouped and is taxonomy-based, we need to find the taxonomy for each value in order to store it
		$taxonomy_insert_data = array();
		foreach ( $this->data as $element ) {
			if ( in_array( $element['value'], $values ) ) $taxonomy_insert_data[$element['group_id']][] = intval( $element['value'] );
		}
		
		return $taxonomy_insert_data;
	}

}

require_once( dirname( __FILE__ ) . '/class-fieldmanager-select.php' );

require_once( dirname( __FILE__ ) . '/class-fieldmanager-radios.php' );

require_once( dirname( __FILE__ ) . '/class-fieldmanager-checkboxes.php' );
