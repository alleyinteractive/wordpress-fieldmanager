<?php

/**
 * Color picker field which submits a 6-character hex code with the hash mark.
 *
 * Provides an interface to navigate colors and submits the 6-character hex code
 * e.g. `#ffffff`. This field uses the
 * {@link https://make.wordpress.org/core/2012/11/30/new-color-picker-in-wp-3-5/
 * WordPress core color picker introduced in WordPress 3.5}.
 *
 * @package Fieldmanager_Field
 */
class Fieldmanager_Colorpicker extends Fieldmanager_Field {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'colorpicker';

	/**
	 * The default color for the color picker.
	 *
	 * @var string
	 */
	public $default_color = null;

	/**
	 * Build the colorpicker object and enqueue assets.
	 *
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {
		fm_add_script( 'fm_colorpicker', 'js/fieldmanager-colorpicker.js', array( 'jquery', 'wp-color-picker' ), '1.0', true );
		fm_add_style( 'wp-color-picker' );

		$this->sanitize = array( $this, 'sanitize_hex_color' );

		parent::__construct( $label, $options );

		// If we have a default_value and default_color was not explicitly set
		// to be empty, set default_color to default_value.
		if ( ! isset( $this->default_color ) && ! empty( $this->default_value ) ) {
			$this->default_color = $this->default_value;
		}
	}

	/**
	 * Form element.
	 *
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = '' ) {
		return sprintf(
			'<input class="fm-element fm-colorpicker-popup" name="%1$s" id="%2$s" data-default-color="%3$s" value="%4$s" %5$s />',
			esc_attr( $this->get_form_name() ),
			esc_attr( $this->get_element_id() ),
			esc_attr( $this->default_color ),
			esc_attr( $value ),
			$this->get_element_attributes()
		);
	}

	/**
	 * Sanitizes a hex color.
	 *
	 * Returns either '', a 3 or 6 digit hex color (with #), or nothing.
	 *
	 * This was copied from core; sanitize_hex_color() is not available outside
	 * of the customizer. {@see https://core.trac.wordpress.org/ticket/27583}.
	 *
	 * @param string $color
	 * @return string
	 */
	function sanitize_hex_color( $color ) {
		$color = trim( $color );

		if ( '' !== $color && preg_match( '|^#([A-Fa-f0-9]{3}){1,2}$|', $color ) ) {
			return $color;
		}

		return '';
	}
}
