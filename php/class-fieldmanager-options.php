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
	 * @deprecated use Fieldmanager_Datsource_Term
	 * Helper for taxonomy-based option sets; taxonomy name
	 */
	public $taxonomy = null;

	/**
	 * @var array
	 * @deprecated use Fieldmanager_Datsource_Term
	 * Helper for taxonomy-based option sets; arguments to find terms
	 */
	public $taxonomy_args = array();

	/**
	 * @var boolean
	 * @deprecated use Fieldmanager_Datsource_Term
	 * Sort taxonomy hierarchically and indent child categories with dashes?
	 */
	public $taxonomy_hierarchical = false;

	/**
	 * @var int
	 * @deprecated use Fieldmanager_Datsource_Term
	 * How far to descend into taxonomy hierarchy (0 for no limit)
	 */
	public $taxonomy_hierarchical_depth = 0;

	/**
	 * @var boolean
	 * @deprecated use Fieldmanager_Datsource_Term
	 * Pass $append = true to wp_set_object_terms?
	 */
	public $append_taxonomy = False;
	
	/**
	 * @var string
	 * @deprecated use Fieldmanager_Datsource_Term
	 * Helper for taxonomy-based option sets; whether or not to preload all terms
	 */
	public $taxonomy_preload = True;

	/**
	 * @var string
	 * @deprecated use Fieldmanager_Datsource_Term
	 * If true, additionally save taxonomy terms to WP's terms tables.
	 */
	public $taxonomy_save_to_terms = True;

	/**
	 * @var Fieldmanager_Datasource
	 * Optionally generate field from datasource
	 */
	public $datasource = Null;

	/**
	 * @var boolean
	 * Allow multiple selections?
	 */
	public $multiple = False;

	/**
	 * @var boolean
	 * Ensure that get_taxonomy_data() only runs once.
	 */
	private $has_built_data = False;
	
	/**
	 * Add CSS, configure taxonomy, construct parent
	 * @param string $label
	 * @param mixed $options
	 */
	public function __construct( $label= '', $options = array() ) {
		parent::__construct( $label, $options );

		if ( !empty( $this->options ) ) {
			$this->add_options( $this->options );
		}
		
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
	}

	/**
	 * Add options
	 * @param array $options
	 * @return void
	 */
	public function add_options( $options ) {
		$values = array_values( $options );
		if ( is_array( $values[0] ) ) {
			foreach ( $options as $group => $data ) {
				foreach ( $data as $value => $label ) {
					$this->add_option_data( $value, $label, $group, $group );
				}
			}
		} else {
			$keys = array_keys( $options );
			$use_name_as_value = ( array_keys( $keys ) === $keys );
			foreach ( $options as $k => $v ) {
				$this->add_option_data( $v, ( $use_name_as_value ? $v : $k ) );
			}
		}
	}
	
	/**
	 * Generate form elements.
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_data_elements( $value ) {
	
		// If the taxonomy parameter is set, populate the data from the given taxonomy if valid
		// Also, if the taxonomy data is not preloaded, this must be run each time to load selected terms
		if ( !$this->has_built_data || !$this->taxonomy_preload ) {
			if ( $this->datasource ) {
				$this->add_options( $this->datasource->get_items() );
			}
			if ( $this->taxonomy != null ) $this->get_taxonomy_data( $value );
		
			// Add the first element to the data array. This is useful for database-based data sets that require a first element.
			if ( !empty( $this->first_element ) ) array_unshift( $this->data, $this->first_element );
			$this->has_built_data = True;
		}
		
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
			if( $this->grouped || $this->datasource->grouped ) $form_data_elements_html .= $this->form_data_end_group();
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
	 * Presave function, which handles sanitization and validation
	 * @param mixed $value If a single field expects to manage an array, it must override presave()
	 * @return sanitized values. 
	 */
	public function presave( $value, $current_value = array() ) {
		if ( !empty( $this->datasource ) ) {
			return $this->datasource->presave( $this, $value, $current_value );
		}
		foreach ( $this->validate as $func ) {
			if ( !call_user_func( $func, $value ) ) {
				$this->_failed_validation( sprintf(
					__( 'Input "%1$s" is not valid for field "%2$s" ' ),
					(string) $value,
					$this->label
				) );
			}
		}
		return call_user_func( $this->sanitize, $value );
	}

	/**
	 * Alter values before rendering
	 * @param array $values
	 */
	public function preload_alter_values( $values ) {
		if ( $this->datasource ) return $this->datasource->preload_alter_values( $this, $values );
		return $values;
	}

	/**
	 * Presave hook to set taxonomy data, maybe
	 * @param int[] $values
	 * @param int[] $current_values
	 * @return int[] $values
	 */
	public function presave_alter_values( $values, $current_values ) {
		if ( !empty( $this->datasource ) ) {
			return $this->datasource->presave_alter_values( $this, $values, $current_values );
		}
		// If this is a taxonomy-based field, must also save the value(s) as an object term
		if ( $this->taxonomy_save_to_terms && isset( $this->taxonomy ) && !empty( $values ) ) {
			// Sanitize the value(s)
			if ( !is_array( $values ) ) {
				$values = array( $values );
			}
			$tax_values = array();
			foreach ( $values as &$value ) {
				$value = call_user_func( $this->sanitize, $value );
				if ( !empty( $value ) ) {
					if( is_numeric( $value ) )
						$tax_values[] = $value;
					else if( is_array( $value ) )
						$tax_values = $value;
				}
			}
			$this->save_taxonomy( $tax_values );
		}
		return $values;
	}

	/**
	 * Save taxonomy data
	 * @param mixed[] $tax_values
	 * @return void
	 */
	public function save_taxonomy( $tax_values ) {
	
		$tax_values = array_map( 'intval', $tax_values );
		$tax_values = array_unique( $tax_values );
		
		// Also assign the taxonomy to an array if it is not one since there may be grouped fields
		$taxonomies = $this->taxonomy;
		if ( !is_array( $this->taxonomy ) ) $taxonomies = array( $this->taxonomy );
	
		// Store the each term for this post. Handle grouped fields differently since multiple taxonomies are present.
		if ( is_array( $this->taxonomy ) ) {
			// Build the taxonomy insert data
			$taxonomy_insert_data = $this->get_taxonomy_insert_data( $tax_values );
			foreach ( $taxonomy_insert_data as $taxonomy => $terms ) {
				wp_set_object_terms( $this->data_id, $terms, $taxonomy, $this->append_taxonomy );
			}
		} else {
			wp_set_object_terms( $this->data_id, $tax_values, $this->taxonomy, $this->append_taxonomy );
		}
	}
	
	/**
	 * Get taxonomy data per $this->taxonomy_args
	 * @param $value The value(s) currently set for this field
	 * @return array[] data entries for options
	 */
	public function get_taxonomy_data( $value ) {

		// If taxonomy_hierarchical is set, assemble recursive term list, then bail out.
		if ( $this->taxonomy_hierarchical ) {
			$tax_args = $this->taxonomy_args;
			$tax_args['parent'] = 0;
			$parent_terms = get_terms( $this->taxonomy, $tax_args );
			$this->build_hierarchical_term_data( $parent_terms, $this->taxonomy_args, 0 );
			return;
		}
	
		// Query for all terms for the defined taxonomies
		// If preload is set to false ONLY load the terms selected previously
		if( $this->taxonomy_preload == false ) {
			// In case this is used with a repeating field, clear any previously loaded taxonomy data
			$this->data = array();
		
			if( empty( $value ) && !is_array( $this->taxonomy ) )
				// Nothing has been selected and we don't have to pre-populate optgroups, so just return
				return;
			
			if( !empty( $value ) ) {
				if( !is_array( $value ) ) 
					// Make sure we have an array
					$value = array( $value );
				
				// Make sure all the values are integers
				$value = array_map( 'intval', $value );
			
				// Finally, make sure we are only including these terms
				$this->taxonomy_args['include'] = $value;
			}
		}
		$terms = get_terms( $this->taxonomy, $this->taxonomy_args );
		
		// If the taxonomy list was an array and group display is set, ensure all terms are grouped by taxonomy
		// Use the order of the taxonomy array list for sorting the groups to make this controllable for developers
		// Order of the terms within the groups is already controllable via $taxonomy_args
		// Skip this entirely if there is only one taxonomy even if group display is set as it would be unnecessary
		if ( is_array( $this->taxonomy ) && $this->grouped ) {
			
			// Group the data
			$term_groups = array();
			foreach ( $this->taxonomy as $tax ) {
				$term_groups[$tax] = array();
			}
			foreach ( $terms as $term ) {
				$term_groups[$term->taxonomy][] = $term;
			}
									
			// Sort the groups by the provided taxonomy order and replace the original $terms data
			$terms = array();
			foreach ( $this->taxonomy as $tax ) {
				if ( !empty( $term_groups[$tax] ) ) {
					$terms = array_merge( $terms, $term_groups[$tax] );
				} else if ( empty( $term_groups[$tax] ) && $this->taxonomy_preload == false ) {
					// Add a default blank group so that the blank optgroup is still present for inserting terms from typeahead search
					$taxonomy_data = get_taxonomy( $tax );
					$this->add_option_data( "", "", $taxonomy_data->label, $taxonomy_data->name );
				}
			}
			
		}
		
		// Put the taxonomy data into the proper data structure to be used for display
		foreach ( $terms as $term ) {
			// Store the label for the taxonomy as the group since it will be used for display
			$taxonomy_data = get_taxonomy( $term->taxonomy );
			$this->add_option_data( $term->name, $term->term_id, $taxonomy_data->label, $taxonomy_data->name );
		}
	}

	/**
	 * Helper to support recursive building of a hierarchical taxonomy list.
	 * @param array $parent_terms
	 * @param array $tax_args as used in top-level get_terms() call.
	 * @param int $depth current recursive depth level.
	 * @return array of terms or false if no children found.
	 */
	protected function build_hierarchical_term_data( $parent_terms, $tax_args, $depth ) {
		
		// Walk through each term passed, add it (at current depth) to the data stack.
		foreach ( $parent_terms as $term ) {
			$taxonomy_data = get_taxonomy( $term->taxonomy );
			$prefix = '';
			
			// Prefix term based on depth. For $depth = 0, prefix will remain empty.
			for ( $i = 0; $i < $depth; $i++ ) {
				$prefix .= '--';
			}
			
			$this->add_option_data( $prefix . ' ' . $term->name, $term->term_id, $taxonomy_data->label, $taxonomy_data->name );
			
			// Find child terms of this. If any, recurse on this function.
			$tax_args['parent'] = $term->term_id;
			$child_terms = get_terms( $this->taxonomy, $tax_args );
			if ( $this->taxonomy_hierarchical_depth == 0 || $depth + 1 < $this->taxonomy_hierarchical_depth ) {
				if ( !empty( $child_terms ) ) {
					$this->build_hierarchical_term_data( $child_terms, $this->taxonomy_args, $depth + 1 );
				}
			}
		}
	}
	
	/**
	 * Add taxonomy ID to data values for insert
	 * @param array $values
	 * @return array $values with taxonomy IDs for saving.
	 */
	protected function get_taxonomy_insert_data( $values ) {
		global $wpdb;
		
		// If the option field data was grouped and is taxonomy-based, we need to find the taxonomy for each value in order to store it
		$taxonomy_insert_data = array();
		foreach ( $values as $value ) {
			$taxonomy = $wpdb->get_var( $wpdb->prepare( 
				"select taxonomy from wp_term_taxonomy where term_id=%d;", 
				$value
			) );
			if ( isset( $taxonomy ) ) $taxonomy_insert_data[$taxonomy][] = intval( $value );
		}
		
		return $taxonomy_insert_data;
	}
	
	/**
	 * Add option data to the data attribute of this object
	 * @param string $name
	 * @param mixed $value
	 * @param string $group
	 * @param string|int $group_id
	 * @return void
	 */
	protected function add_option_data( $name, $value, $group=null, $group_id=null ) {
		$data = array( 
			'name' => $name,
			'value' => $value
		);
		if( isset( $group ) ) $data['group'] = $group;
		if( isset( $group_id ) ) $data['group_id'] = $group_id;
		
		$this->data[] = $data;
	}

}

require_once( dirname( __FILE__ ) . '/class-fieldmanager-select.php' );

require_once( dirname( __FILE__ ) . '/class-fieldmanager-radios.php' );

require_once( dirname( __FILE__ ) . '/class-fieldmanager-checkboxes.php' );
