<?php
/**
 * @package Fieldmanager
 */

/**
 * Hidden field
 * @package Fieldmanager
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
			$this->get_form_name(),
			$this->get_element_id(),
			htmlspecialchars( $value ),
			$this->get_element_attributes()
		);
	}

}