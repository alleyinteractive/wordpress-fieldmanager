<?php
/**
 * Class file for Fieldmanager_Checkbox
 *
 * @package Fieldmanager
 */

/**
 * Single boolean checkbox.
 */
class Fieldmanager_Checkbox extends Fieldmanager_Field {

	/**
	 * Value when checked.
	 *
	 * @var mixed
	 */
	public $checked_value = true;

	/**
	 * Value when unchecked.
	 *
	 * @var mixed
	 */
	public $unchecked_value = false;

	/**
	 * Override save_empty default for this element type.
	 *
	 * @var bool
	 */
	public $save_empty = true;

	/**
	 * Override inline_label.
	 *
	 * @var bool
	 */
	public $inline_label = true;

	/**
	 * Override label_after_element.
	 *
	 * @var bool
	 */
	public $label_after_element = true;

	/**
	 * Form element implementation for checkboxes.
	 *
	 * @param  mixed $value The current value.
	 * @return string HTML.
	 */
	public function form_element( $value = null ) {
		return sprintf(
			'
			<input class="fm-checkbox-hidden fm-element" type="hidden" name="%1$s" value="%6$s" />
			<input class="fm-element" type="checkbox" name="%1$s" value="%2$s" %3$s %4$s id="%5$s" />
			',
			esc_attr( $this->get_form_name() ),
			esc_attr( (string) $this->checked_value ),
			$this->get_element_attributes(),
			( $value == $this->checked_value ) ? 'checked="checked"' : '',
			esc_attr( $this->get_element_id() ),
			$this->unchecked_value
		);
	}

	/**
	 * Override presave function to swap in unchecked_value if needed.
	 *
	 * @param  mixed $value         The new value.
	 * @param  mixed $current_value The curent value.
	 * @return mixed Proper value.
	 */
	public function presave( $value = null, $current_value = array() ) {
		if ( $value == $this->checked_value || $value === $this->unchecked_value ) {
			return $value;
		} elseif ( empty( $value ) ) {
			return $this->unchecked_value;
		} else {
			$this->_unauthorized_access( __( 'Saved a checkbox with a value that was not one of the options', 'fieldmanager' ) );
		}
	}
}
