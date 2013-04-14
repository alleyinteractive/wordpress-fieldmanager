<?php
/**
 * @package Fieldmanager
 */

/**
 * Text field. A good basic implementation guide, too.
 * @package Fieldmanager
 */
class Fieldmanager_TextField extends Fieldmanager_Field {

	/**
	 * @var string
	 * Override field_class
	 */
	public $field_class = 'text';

	/**
	 * Override constructor to set default size.
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->attributes = array(
			'size' => '50',
		);
		parent::__construct( $label, $options );
	}

	/**
	 * Render a text field.
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = '' ) {
		return sprintf(
			'<input class="fm-element" type="text" name="%s" id="%s" value="%s" %s />',
			$this->get_form_name(),
			$this->get_element_id(),
			htmlspecialchars( $value ),
			$this->get_element_attributes()
		);
	}
}