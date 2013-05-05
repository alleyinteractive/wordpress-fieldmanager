<?php
/**
 * @package Fieldmanager
 */

/**
 * Checkboxes multi-select field
 * @package Fieldmanager
 */
class Fieldmanager_Checkboxes extends Fieldmanager_Options {

	/**
	 * @var string
	 * Override field_class
	 */
	public $field_class = 'checkboxes';

	public $multiple = True;
	
	/**
	 * Render form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = array() ) {
		
		return sprintf(
			'<div class="fm-checkbox-group" id="%s">%s</div>',
			$this->get_element_id(),
			$this->form_data_elements( $value )
		);
	}

}