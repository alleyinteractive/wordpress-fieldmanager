<?php

class Fieldmanager_Checkboxes extends Fieldmanager_Options {

	public $field_class = 'checkboxes';
	
	public function __construct( $options = array() ) {
		parent::__construct($options);
	}

	public function form_element( $value = array() ) {
		
		return sprintf(
			'<div class="fm-checkbox-group" id="%s"></div>',
			$this->get_element_id(),
			$this->form_data_elements( $value )
		);
	}
	
	public function form_data_element( $data_row, $value = array() ) {
	
		return sprintf(
			'<div class="fm-option"><input class="fm-element" type="checkbox" value="%s" name="%s" %s %s/><div class="fm-option-label">%s</div></div>',
			$data_row['value'],
			$this->get_form_name(),
			$this->get_element_attributes(),
			$this->option_selected( $data_row['value'], $value, "checked" ),
			htmlspecialchars( $data_row['name'] )
		);						
	
	}
	
	public function form_data_start_group( $label ) {
	
	}
	
	public function form_data_end_group() {
	
	}

	public function validate( $value ) {

	}

	public function sanitize( $value ) {

	}

}