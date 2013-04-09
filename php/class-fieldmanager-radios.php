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

	/**
	 * Individual radio element
	 * @see Fieldmanager_Options::form_data_elements()
	 * @param mixed $data_row
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_data_element( $data_row, $value = array() ) {
		
		return sprintf(
			'<div class="fm-option"><input class="fm-element" type="radio" value="%s" name="%s" %s %s/><div class="fm-option-label">%s</div></div>',
			$data_row['value'],
			$this->get_form_name(),
			$this->get_element_attributes(),
			$this->option_selected( $data_row['value'], $value, "checked" ),
			htmlspecialchars( $data_row['name'] )
		);						
	
	}

}