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
	 * @param string|array $label   The field label. A provided string sets $options['label'], while an array sets $options, overriding any existing data in $options.
	 * @param array        $options The field options.
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->sanitize = 'esc_url_raw';
		$this->template = fieldmanager_get_template( 'textfield' );
		parent::__construct( $label, $options );
	}

}
