<?php

/**
 * A collection of multiple radio button fields.
 *
 * This class extends {@link Fieldmanager_Options}, which allows you to define
 * options (values) via an array or via a dynamic
 * {@link Fieldmanager_Datasource}, like {@link Fieldmanager_Datasource_Post},
 * {@link Fieldmanager_Datasource_Term}, or {@link Fieldmanager_Datasource_User}.
 *
 * @package Fieldmanager_Field
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