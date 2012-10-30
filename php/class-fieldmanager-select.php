<?php

class Fieldmanager_Select extends Fieldmanager_Options {

	public $field_class = 'select';
	
	public function __construct( $options = array() ) {
		$this->attributes = array(
			'size' => '1'
		);
		parent::__construct($options);
	}

	public function form_element( $value = array() ) {
		
		// If this is a multiple select, need to handle differently
		$do_multiple = "";
		if ( array_key_exists( 'multiple', $this->attributes ) ) $do_multiple = "[]";
		
		return sprintf(
			'<select class="fm-element" name="%s" id="%s" %s />%s</select>',
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

	public function validate( $value ) {

	}

	public function sanitize( $value ) {

	}

}