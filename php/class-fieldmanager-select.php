<?php
/**
 * @package Fieldmanager
 */

/**
 * Dropdown for options
 * @package Fieldmanager
 */
class Fieldmanager_Select extends Fieldmanager_Options {

	/**
	 * @var string
	 * Override $field_class
	 */
	public $field_class = 'select';

	/**
	 * @var boolean
	 * Should we support type-ahead? i.e. use chosen.js or not
	 */
	public $type_ahead = False;	
	
	/**
	 * @var boolean
	 * Send an empty element first with a taxonomy select
	 */
	public $first_empty = False;

	/**
	 * @var string
	 * Helper for taxonomy-based option sets; whether or not to preload all terms
	 * Currently only available for Fieldmanager Select due to implementations with chosen.js
	 */
	public $taxonomy_preload = true;	
		
	/**
	 * @var callable
	 * What function to call to match terms. Initialized to Null here because it will be 
	 * written in __construct to an internal function that calls get_terms, so only
	 * overwrite it if you do /not/ want to use get_terms.
	 * 
	 * The function signature should be query_callback( $match, $args );
	 */
	public $query_callback = null;
	
	/**
	 * Override constructor to add chosen.js maybe
	 * @param array $options
	 */
	public function __construct( $options = array() ) {
		$this->attributes = array(
			'size' => '1'
		);
		
		// Add the chosen library for type-ahead capabilities
		if ( $this->type_ahead ) {
			fm_add_script( 'chosen', 'js/chosen/chosen.jquery.js' );
			fm_add_style( 'chosen_css', 'js/chosen/chosen.css' );
		}
		
		// Add the Fieldmanager Select javascript library
		fm_add_script( 'fm_select_js', 'js/fieldmanager-select.js', array(), false, false, 'fm_select', array( 'nonce' => wp_create_nonce( 'fm_search_terms_nonce' ) ) );

		// Add the action hook for typeahead handling via AJAX
		add_action('wp_ajax_fm_search_terms', array( $this, 'search_terms' ) );
		
		if ( empty( $this->query_callback ) ) {
			$this->query_callback = array( $this, 'search_terms_using_get_terms' );
		}
				
		parent::__construct($options);
	}

	/**
	 * Form element
	 * @param array $value
	 * @return string HTML
	 */
	public function form_element( $value = array() ) {
		
		$select_classes = array( 'fm-element' );
		
		// If this is a multiple select, need to handle differently
		$do_multiple = '';
		if ( array_key_exists( 'multiple', $this->attributes ) ) $do_multiple = "[]";
		
		// Handle type-ahead based fields using the chosen library
		if ( $this->type_ahead ) { 
			$select_classes[] = 'chzn-select';
			add_action( 'admin_footer', array( $this, 'chosen_init' ) );
			
			if ( $this->grouped ) { 
				$select_classes[] = "fm-options-grouped";
			} else {
				$select_classes[] = "fm-options";
			}
		}
		
		$opts = '';
		if ( $this->first_empty ) {
			$opts .= '<option value="">&nbsp;</option>';
		}
		$opts .= $this->form_data_elements( $value );

		return sprintf(
			'<select class="' . implode( " ", $select_classes ) . '" name="%s" id="%s" %s data-value=\'%s\' %s %s />%s</select>',
			$this->get_form_name( $do_multiple ),
			$this->get_element_id(),
			$this->get_element_attributes(),
			( $value == null ) ? "" : json_encode( $value ), // For applications where options may be dynamically provided. This way we can still provide the previously stored value to a Javascript.
			( $this->taxonomy != null ) ? "data-taxonomy='" . json_encode($this->taxonomy) . "'" : "",
			( $this->taxonomy != null ) ? "data-taxonomy-preload='" . json_encode($this->taxonomy_preload) . "'" : "",
			$opts
		);
	}
	
	/**
	 * Single data element (<option>)
	 * @param array $data_row
	 * @param array $value
	 * @return string HTML
	 */
	public function form_data_element( $data_row, $value = array() ) {
		
		// For taxonomy-based selects, only return selected options if taxonomy preload is disabled
		// Additional terms will be provided by AJAX for typeahead to avoid overpopulating the select for large taxonomies
		$option_selected = $this->option_selected( $data_row['value'], $value, "selected" );
		if ( $this->taxonomy != null && $this->taxonomy_preload == false && $option_selected != "selected" ) return "";
		
		return sprintf(
			'<option value="%s" %s>%s</option>',
			$data_row['value'],
			$option_selected,
			htmlspecialchars( $data_row['name'] )
		);			
	
	}
	
	/**
	 * Start an <optgroup>
	 * @param string $label
	 * @return string HTML
	 */
	public function form_data_start_group( $label ) {
		return sprintf(
			'<optgroup label="%s">',
			$label
		);
	}
	
	/**
	 * End an <optgroup>
	 * @return string HTML
	 */
	public function form_data_end_group() {
		return '</optgroup>';
	}
			
	/**
	 * Default callback for term queries.
	 * @param array $values
	 * @return array $values with taxonomy IDs for saving.
	 */
	protected function search_terms_using_get_terms( $taxonomy, $search_term ) {
		// Set the args for the term search
		$args = array(
			'orderby' => 'name',
			'hide_empty' => false,
			'name__like' => $search_term
		);
		$terms = get_terms( $taxonomy, $args );
		if( !$terms ) $terms = array();
		return $terms;
	}
	
	/**
	 * AJAX callback to find terms
	 */
	public function search_terms() {
		// Check the nonce before we do anything
		check_ajax_referer( 'fm_search_terms_nonce', 'fm_search_terms_nonce' );
		
		// Assure that we have all the parameters we need
		if( !array_key_exists( 'search_term', $_POST )
			|| empty( $_POST['search_term'] )
			|| !array_key_exists( 'taxonomy', $_POST )
			|| empty( $_POST['taxonomy'] ) ) {
			echo "";
			die();
		}
		
		// Determine if this is multiple taxonomies since that will impact the HTML output
		$taxonomies = $_POST['taxonomy'];
		$do_optgroup = true;
		if( !is_array( $taxonomies ) ) {
			$taxonomies = array( json_decode( stripslashes( $taxonomies ) ) );
			$do_optgroup = false;
		}
		
		// Holds the final output
		$term_output = "";
		
		// Iterate through the taxonomies
		foreach( $taxonomies as $taxonomy ) {
			// Perform the search
			$terms = call_user_func( $this->query_callback, $taxonomy, $_POST['search_term'] );

			// Add a filter hook to allow for modification of the term list before HTML processing
			$terms = apply_filters( 'fm_search_terms', $terms, $_POST['search_term'], $taxonomies );

			$optgroup_label = "";
			
			if( !empty( $terms ) ) {
				// If any terms were returned, add this to the output.
				// Since we are going to add this dynamically to the available options, use the existing HTML output functions
				
				// Determine if there is an optgroup (used for multiple taxonomies)
				if( $do_optgroup ) {
					$taxonomy_data = get_taxonomy( $taxonomy );
					$term_output .= $this->form_data_start_group( $taxonomy_data->label );
				}
				
				// Add each term
				foreach( $terms as $term ) {
					$data_row = array(
						'name' => $term->name,
						'value' => $term->term_id
					);
					$term_output .= $this->form_data_element( $data_row );
				}
				
				// Determine if there is an optgroup to close (used for multiple taxonomies)
				if( $do_optgroup ) {
					$term_output .= $this->form_data_end_group();
				}
			}
			
		}
						
		// Return the HTML to add to the select
		echo $term_output;
		
		die();
	}
	
	/**
	 * Init chosen.js
	 * @return string HTML
	 */
	public function chosen_init( ) {
		echo sprintf(
			'<script type="text/javascript"> $("#%s").chosen({allow_single_deselect:true});</script>',
			$this->get_element_id()
		);
	}
}