<?php
class Fieldmanager_Hidden extends Fieldmanager_Field {

	public $field_class = 'hidden';

	public function __construct( $options = array() ) {
		parent::__construct($options);
	}

	public function form_element( $value = '' ) {
		return sprintf(
			'<input class="fm-element" type="hidden" name="%s" id="%s" value="%s" %s />',
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