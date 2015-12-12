<?php

/**
 * Colorpicker
 *
 * @package Fieldmanager
 */
class Fieldmanager_Colorpicker extends Fieldmanager_Field {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'colorpicker';

	/**
	 * Static variable so we only load static assets once.
	 *
	 * @var boolean
	 */
	public static $has_registered_statics = false;

	/**
	 * Build the colorpicker object and enqueue assets.
	 *
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

		$this->sanitize = array( $this, 'sanitize_hex_color' );

		parent::__construct( $label, $options );
	}

	/**
	 * Form element.
	 *
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

	/**
	 * Sanitizes a hex color.
	 *
	 * Returns either '', a 3 or 6 digit hex color (with #), or nothing.
	 * For sanitizing values without a #, see sanitize_hex_color_no_hash().
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