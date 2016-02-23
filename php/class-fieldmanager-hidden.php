<?php

/**
 * Hidden input field.
 *
 * @package Fieldmanager_Field
 */
class Fieldmanager_Hidden extends Fieldmanager_Field {

	/**
	 * @var string
	 * Override field_class
	 */
	public $field_class = 'hidden';

	/**
	 * Hidden form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = '' ) {
		return sprintf(
			'<input class="fm-element" type="hidden" name="%s" id="%s" value="%s" %s />',
			esc_attr( $this->get_form_name() ),
			esc_attr( $this->get_element_id() ),
			esc_attr( $value ),
			$this->get_element_attributes()
		);
	}

}