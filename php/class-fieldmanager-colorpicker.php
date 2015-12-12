<?php

/**
 * Colorpicker
 *
 * @package Fieldmanager
 */
class Fieldmanager_Colorpicker extends Fieldmanager_Field {

	/**
	 * Sanitize values as a hex color
	 *
	 * @var string
	 */
	public $sanitize = 'sanitize_hex_color';

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'colorpicker';

	/**
	 * Static variable so we only load static assets once.
	 *
	 * @var string
	 */
	public static $has_registered_statics = false;

	/**
	 * Override constructor to set default size.
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {
		if ( ! self::$has_registered_statics ) {
			add_action( 'admin_enqueue_scripts', function() {
				wp_enqueue_style( 'wp-color-picker' );
			} );
			fm_add_script( 'fm_colorpicker', 'js/fieldmanager-colorpicker.js', array( 'jquery', 'wp-color-picker' ), '1.0', true );
			self::$has_registered_statics = true;
		}

		parent::__construct( $label, $options );
	}

	/**
	 * Form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = '' ) {
		return sprintf(
			'<input class="fm-element fm-colorpicker-popup" name="%1$s" id="%2$s" value="%3$s" %4$s />',
			esc_attr( $this->get_form_name() ),
			esc_attr( $this->get_element_id() ),
			esc_attr( $value ),
			$this->get_element_attributes()
		);
	}
}