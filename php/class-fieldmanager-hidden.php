<?php
/**
 * Class file for Fieldmanager_Hidden
 *
 * @package Fieldmanager
 */

/**
 * Hidden input field.
 */
class Fieldmanager_Hidden extends Fieldmanager_Field {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'hidden';

	/**
	 * Hidden form element.
	 *
	 * @param mixed $value The current value.
	 * @return string HTML string.
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
