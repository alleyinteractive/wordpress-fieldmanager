<?php
/**
 * @package Fieldmanager
 */

/**
 * Textarea field
 * @package Fieldmanager
 */
class Fieldmanager_Media extends Fieldmanager_Field {

	/**
	 * @var string
	 * Override field_class
	 */
	public $field_class = 'media';

	/**
	 * @var string
	 * Button Label
	 */
	public $button_label = 'Attach a file';

	/**
	 * @var string
	 * Static variable so we only load media JS once
	 */
	public static $has_registered_media = False;

	/**
	 * Construct default attributes; 50x10 textarea
	 * @param array $options
	 */
	public function __construct( $options = array() ) {
		if ( !self::$has_registered_media ) {
			wp_enqueue_script( 'media-upload' );
			wp_enqueue_script( 'thickbox' );
			wp_enqueue_style( 'thickbox' );
			fm_add_script( 'fm_media', 'js/media/fieldmanager-media.js' );
			self::$has_registered_media = True;
		}
		parent::__construct( $options );
	}

	/**
	 * Form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = array() ) {
		return sprintf(
			'<input type="button" class="fm-media-button" id="%1$s" value="%3$s" />
			<input type="hidden" name="%2$s[src]" value="%4$s" class="fm-media-src" />
			<input type="hidden" name="%2$s[src]" value="%5$s" class="fm-media-id" />
			<div class="media-wrapper"></div>',
			$this->get_element_id(),
			$this->get_form_name(),
			$this->button_label,
			isset( $value['src'] ) ? $value['src'] : '',
			isset( $value['id'] ) ? $value['id'] : ''
		);
	}

}