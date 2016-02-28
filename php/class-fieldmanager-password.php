<?php

/**
 * Text field which hides the user input.
 *
 * This field submits plain text.
 *
 * @package Fieldmanager_Field
 */
class Fieldmanager_Password extends Fieldmanager_Field {

	/**
	 * @var string
	 * Override field_class
	 */
	public $field_class = 'password';

	/**
	 * Override the input type
	 * @var string
	 */
	public $input_type = 'password';

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

}