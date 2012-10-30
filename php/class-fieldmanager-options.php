<?php

class Fieldmanager_Options extends Fieldmanager_Fields {

	public $field_class = 'text';

	public function __construct( $options = array() ) {
		$this->attributes = array(
			'size' => '50',
		);
		parent::__construct($options);
	}

	public function form_element( $value = '' ) {
		return sprintf(
			'<input class="fm-element" type="text" name="%s" id="%s" value="%s" %s />',
			$this->get_form_name(),
			$this->get_element_id(),
			htmlspecialchars( $value ),
			$this->get_element_attributes()
		);
	}

	public function validate( $value ) {

	}

	public function sanitize( $value ) {

	}

}

class Fieldmanager_Radios extends Fieldmanager_Options {

}

class Fieldmanager_Checkboxes extends Fieldmanager_Options {
	
}

class Fieldmanager_Select extends Fieldmanager_Options {

}