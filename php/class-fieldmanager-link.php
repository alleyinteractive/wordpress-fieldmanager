<?php
/**
 * Class file for Fieldmanager_Link
 *
 * @package Fieldmanager
 */

/**
 * Link (url) field
 */
class Fieldmanager_Link extends Fieldmanager_Textfield {

	/**
	 * Construct default attributes, set link sanitizer.
	 *
	 * @param string $label   The form label.
	 * @param array  $options The form options.
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->sanitize = 'esc_url_raw';
		$this->template = fieldmanager_get_template( 'textfield' );
		parent::__construct( $label, $options );
	}

}
