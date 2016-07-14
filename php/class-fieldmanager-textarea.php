<?php

/**
 * Multi-line text field.
 *
 * @package Fieldmanager_Field
 */
class Fieldmanager_TextArea extends Fieldmanager_Field {

	/**
	 * @var string
	 * Override field_class
	 */
	public $field_class = 'text';

	/**
	 * Construct default attributes; 50x10 textarea
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->attributes = array(
			'cols' => '50',
			'rows' => '10'
		);

		// Sanitize the textarea to preserve newlines. Could be overriden.
		$this->sanitize = 'fm_sanitize_textarea';

		parent::__construct( $label, $options );
	}

	/**
	 * Form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = '' ) {
		return sprintf(
			'<textarea class="fm-element" name="%s" id="%s" %s >%s</textarea>',
			esc_attr( $this->get_form_name() ),
			esc_attr( $this->get_element_id() ),
			$this->get_element_attributes(),
			esc_textarea( $value )
		);
	}

}