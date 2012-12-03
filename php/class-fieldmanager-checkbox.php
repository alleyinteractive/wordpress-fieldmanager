<?php
/**
 * Class file for checkbox field
 * @package Fieldmanager
 */

/**
 * Checkbox field
 * @package Fieldmanager
 */
class Fieldmanager_Checkbox extends Fieldmanager_Field {

	/**
	 * @var mixed
	 * Value when checked
	 */
	public $checked_value = TRUE;

	/**
	 * @var mixed
	 * Value when unchecked
	 */
	public $unchecked_value = FALSE;

	/**
	 * @var boolean
	 * Override save_empty default for this element type
	 */
	public $save_empty = True;

	/**
	 * Form element implementation for checkboxes
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = NULL ) {
		return sprintf(
			'<input class="fm-element" type="checkbox" name="%1$s" value="%2$s" %3$s %4$s/>',
			$this->name,
			htmlentities( (string) $this->checked_value ),
			$this->get_element_attributes(),
			( $value == $this->checked_value ) ? "checked" : ""
		);
	}

	/**
	 * Override presave function to swap in unchecked_value if needed
	 * @param mixed $value
	 * @return mixed proper value
	 */
	public function presave( $value = NULL ) {
		if ( $value == $this->checked_value ) {
			return $value;
		}
		elseif ( empty( $value ) ) {
			return $this->unchecked_value;
		}
		else {
			$this->_unauthorized_access( 'Saved a checkbox with a value that was not one of the options' );
		}
	}
}