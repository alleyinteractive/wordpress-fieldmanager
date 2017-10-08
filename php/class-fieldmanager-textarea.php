<?php
/**
 * Class file for Fieldmanager_TextArea
 *
 * @package Fieldmanager
 */

/**
 * Multi-line text field.
 */
class Fieldmanager_TextArea extends Fieldmanager_Field {

	/**
	 * Override $field_class.
	 *
	 * @var string
	 */
	public $field_class = 'text';

	/**
	 * Construct default attributes; 50x10 textarea.
	 *
	 * @param string $label   Field label.
	 * @param array  $options Associative array of class property values. @see Fieldmanager_Field::__construct().
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->attributes = array(
			'cols' => '50',
			'rows' => '10',
		);

		// Sanitize the textarea to preserve newlines. Could be overriden.
		$this->sanitize = 'fm_sanitize_textarea';

		parent::__construct( $label, $options );
	}

	/**
	 * Form element.
	 *
	 * @param mixed $value Field value.
	 * @return string HTML.
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
