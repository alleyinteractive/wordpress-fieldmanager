<?php

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
	 * @var boolean
	 * Override inline_label
	 */
	public $inline_label = True;

	/**
	 * @var boolean
	 * Override label_after_element
	 */
	public $label_after_element = True;

	/**
	 * Form element implementation for checkboxes
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = NULL ) {
		return sprintf(
			'<input type="hidden" name="%1$s" value="%6$s" /><input class="fm-element" type="checkbox" name="%1$s" value="%2$s" %3$s %4$s id="%5$s" onclick="jQuery(this).val(this.checked ? 1 : 0)"/>',
			esc_attr( $this->get_form_name() ),
			esc_attr( (string) $this->checked_value ),
			$this->get_element_attributes(),
			( $value == $this->checked_value ) ? 'checked="checked"' : "",
			esc_attr( $this->get_element_id() ),
			$this->unchecked_value
		);
	}

	/**
	 * Override presave function to swap in unchecked_value if needed
	 * @param mixed $value
	 * @return mixed proper value
	 */
	public function presave( $value = NULL, $current_value = array() ) {
		if ( $value == $this->checked_value || $value === $this->unchecked_value ) {
			return $value;
		} elseif ( empty( $value ) ) {
			return $this->unchecked_value;
		}
		else {
			$this->_unauthorized_access( __( 'Saved a checkbox with a value that was not one of the options', 'fieldmanager' ) );
		}
	}
}
