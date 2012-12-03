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
	
	/**
	 * Render form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = array() ) {
		
		return sprintf(
			'<div class="fm-checkbox-group" id="%s"></div>',
			$this->get_element_id(),
			$this->form_data_elements( $value )
		);
	}
	
	/**
	 * Individual checkbox element
	 * @see Fieldmanager_Options::form_data_elements()
	 * @param mixed $data_row
	 * @param mixed $value
	 * @return string HTML
	 */
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

}