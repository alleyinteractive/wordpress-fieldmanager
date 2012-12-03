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
	 * Should we support type-ahead? e.g. use chosen.js or not
	 */
	public $type_ahead = False;
	
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
		
		return sprintf(
			'<select class="' . implode( " ", $select_classes ) . '" name="%s" id="%s" %s data-value=\'%s\' %s />%s</select>',
			$this->get_form_name( $do_multiple ),
			$this->get_element_id(),
			$this->get_element_attributes(),
			json_encode( $value ), // For applications where options may be dynamically provided. This way we can still provide the previously stored value to a Javascript.
			( $this->taxonomy != null ) ? "data-taxonomy='" . json_encode($this->taxonomy) . "'" : "",
			$this->form_data_elements( $value )
		);
	}
	
	/**
	 * Single data element (<option>)
	 * @param array $data_row
	 * @param array $value
	 * @return string HTML
	 */
	public function form_data_element( $data_row, $value = array() ) {
		
		return sprintf(
			'<option value="%s" %s />%s</option>',
			$data_row['value'],
			$this->option_selected( $data_row['value'], $value, "selected" ),
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
	 * Init chosen.js
	 * @return string HTML
	 */
	public function chosen_init( ) {
		echo '<script type="text/javascript"> $("#' . $this->get_element_id() . '").chosen({allow_single_deselect:true})</script>';
	}
}