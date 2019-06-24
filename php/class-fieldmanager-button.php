<?php
/**
 * Class file for Fieldmanager_Button
 *
 * @package Fieldmanager
 */

/**
 * Button Field
 */
class Fieldmanager_Button extends Fieldmanager_Options {
	/**
	 * Button content string.
	 *
	 * @var string
	 */
	public $button_content = '';

	/**
	 * Setup Button Template and Type.
	 *
	 * @param string $label   Field label.
	 * @param array  $options The form options.
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->input_type     = 'button';
		$this->template       = fieldmanager_get_template( 'button' );
		parent::__construct( $label, $options );
	}
}
