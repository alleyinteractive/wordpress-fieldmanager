<?php

class Fieldmanager_Checkbox extends Fieldmanager_Field {

	public $field_class = 'checkbox';

	public function form_element( $value = NULL ) {
		return sprintf(
			'<input class="fm-element" type="checkbox" name="%s" %s %s/>',
			$this->name,
			$this->get_element_attributes(),
			( $value == 'o' ) ? "checked" : ""
		);
	}

	public function validate( $value ) {

	}

	public function sanitize( $value ) {

	}

}