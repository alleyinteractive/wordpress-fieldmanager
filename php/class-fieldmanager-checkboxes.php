<?php

/**
 * A set of multiple checkboxes which submits as an array of the checked values.
 *
 * This class extends {@link Fieldmanager_Options}, which allows you to define
 * options (values) via an array or via a dynamic
 * {@link Fieldmanager_Datasource}, like {@link Fieldmanager_Datasource_Post},
 * {@link Fieldmanager_Datasource_Term}, or {@link Fieldmanager_Datasource_User}.
 *
 * @package Fieldmanager_Field
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
			esc_attr( $this->get_element_id() ),
			$this->form_data_elements( $value )
		);
	}

	/**
	 * Override function to allow all boxes to be checked by default.
	 * @param string $current_option this option
	 * @param array $options all valid options
	 * @param string $attribute
	 * @return string $attribute on match, empty on failure.
	 */
	public function option_selected( $current_option, $options, $attribute ) {
		if ( ( ( $options != null && !empty( $options ) ) && in_array( $current_option, $options ) ) || ( 'checked' == $this->default_value && in_array( $this->default_value, $options ) ) ) {
			return $attribute;
		} else { 
			return '';
		}
	}

}