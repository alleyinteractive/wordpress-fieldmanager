<?php
/**
 * Class file for Fieldmanager_Checkboxes
 *
 * @package Fieldmanager
 */

/**
 * A set of multiple checkboxes which submits as an array of the checked values.
 *
 * This class extends {@see Fieldmanager_Options}, which allows you to define
 * options (values) via an array or via a dynamic
 * {@see Fieldmanager_Datasource}, like {@see Fieldmanager_Datasource_Post},
 * {@see Fieldmanager_Datasource_Term}, or {@see Fieldmanager_Datasource_User}.
 */
class Fieldmanager_Checkboxes extends Fieldmanager_Options {

	/**
	 * Override $field_class.
	 *
	 * @var string
	 */
	public $field_class = 'checkboxes';

	/**
	 * Allow multiple selections.
	 *
	 * @var bool
	 */
	public $multiple = true;

	/**
	 * Render form element.
	 *
	 * @param mixed $value The value of the element.
	 * @return string HTML for the element.
	 */
	public function form_element( $value = array() ) {
		return sprintf(
			'<div class="fm-checkbox-group" id="%s">%s</div>',
			esc_attr( $this->get_element_id() ),
			$this->form_data_elements( $value )
		);
	}

	/**
	 * Override function to allow all boxes to be checked by default.
	 *
	 * @param string $current_option This option.
	 * @param array  $options        All valid options.
	 * @param string $attribute      The attribute to return if $current_option should be selected.
	 * @return string $attribute on match, empty on failure.
	 */
	public function option_selected( $current_option, $options, $attribute ) {
		if ( ( ( null !== $options && ! empty( $options ) ) && in_array( $current_option, $options ) ) || ( 'checked' == $this->default_value && in_array( $this->default_value, $options ) ) ) {
			return $attribute;
		} else {
			return '';
		}
	}

}
