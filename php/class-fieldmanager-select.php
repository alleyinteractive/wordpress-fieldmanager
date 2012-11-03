<?php

class Fieldmanager_Select extends Fieldmanager_Options {

	public $field_class = 'select';
	public $type_ahead = false;
	
	public function __construct( $options = array() ) {
		$this->attributes = array(
			'size' => '1'
		);
		
		// Add the chosen library for type-ahead capabilities
		fm_add_script( 'chosen', 'js/chosen/chosen.jquery.js' );
		fm_add_style( 'chosen_css', 'js/chosen/chosen.css' );
				
		parent::__construct($options);
	}

	public function form_element( $value = array() ) {
		
		$select_classes = array( 'fm-element' );
		
		// If this is a multiple select, need to handle differently
		$do_multiple = "";
		if ( array_key_exists( 'multiple', $this->attributes ) ) $do_multiple = "[]";
		
		// Handle type-ahead based fields
		if ( $this->type_ahead ) { 
			$select_classes[] = 'chzn-select' . $this->get_element_id();
			add_action( 'admin_footer', array( $this, 'chosen_init' ) );
			
			if ( $this->grouped ) { 
				$select_classes[] = "fm-options-grouped";
			} else {
				$select_classes[] = "fm-options";
			}
		}
		
		return sprintf(
			'<select class="' . implode( " ", $select_classes ) . '" name="%s" id="%s" %s />%s</select>',
			$this->get_form_name( $do_multiple ),
			$this->get_element_id(),
			$this->get_element_attributes(),
			$this->form_data_elements( $value )
		);
	}
	
	public function form_data_element( $data_row, $value = array() ) {
		
		return sprintf(
			'<option value="%s" %s />%s</option>',
			$data_row['value'],
			$this->option_selected( $data_row['value'], $value, "selected" ),
			htmlspecialchars( $data_row['name'] )
		);						
	
	}
	
	public function form_data_start_group( $label ) {
		return sprintf(
			'<optgroup label="%s">',
			$label
		);
	}
	
	public function form_data_end_group() {
		return '</optgroup>';
	}
	
	public function chosen_init( ) {
		echo '<script type="text/javascript"> $(".chzn-select' . $this->get_element_id() . '").chosen()</script>';
	}

	public function validate( $value ) {

	}

	public function sanitize( $value ) {

	}

}