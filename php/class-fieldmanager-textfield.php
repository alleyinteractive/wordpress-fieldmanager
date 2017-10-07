<?php
/**
 * Class file for Fieldmanager_TextField
 *
 * @package Fieldmanager
 */

/**
 * Single-line text field.
 */
class Fieldmanager_TextField extends Fieldmanager_Field {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'text';

	/**
	 * Override constructor to set default size.
	 *
	 * @param string $label   The form label.
	 * @param array  $options The form options.
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->attributes = array(
			'size' => '50',
		);
		parent::__construct( $label, $options );
	}

}
