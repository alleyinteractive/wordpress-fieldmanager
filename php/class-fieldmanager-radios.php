<?php
/**
 * @package Fieldmanager
 */

/**
 * Radio button class for options
 * @package Fieldmanager
 */
class Fieldmanager_Radios extends Fieldmanager_Options {

	/**
	 * @var string
	 * Override field class
	 */
	public $field_class = 'radio';

	/**
	 * Form element
	 * @param array $value
	 * @return string HTML
	 */
	public function form_element( $value = array() ) {

		return $this->form_data_elements( $value );

	}

}