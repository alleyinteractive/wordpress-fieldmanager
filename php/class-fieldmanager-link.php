<?php

/**
 * Link field
 * @package Fieldmanager
 * @todo good test case for JS validation framework
 */
class Fieldmanager_Link extends Fieldmanager_Textfield {

	/**
	 * Construct default attributes, set link sanitizer
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->sanitize = 'esc_url_raw';
		$this->template = fieldmanager_get_template( 'textfield' );
		parent::__construct( $label, $options );
	}

}