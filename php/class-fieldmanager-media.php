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
			//Use WP 3.5 Media Uploader Enqueue
			wp_enqueue_media();
			fm_add_script( 'fm_media', 'js/fieldmanager-media.js' );
			self::$has_registered_media = True;
		}
		parent::__construct( $options );
	}

	/**
	 * Presave; convert a URL to an attachment ID.
	 */
	public function presave( $value ) {
		global $wpdb;
		if ( !empty( $value ) && !is_numeric( $value ) ) {
			$attachment = $wpdb->get_row(
				$wpdb->prepare(
					"SELECT ID FROM $wpdb->posts WHERE guid = %s AND post_type = 'attachment'",
					$value
				)
			);
			if ( !empty( $attachment->ID ) ) return $attachment->ID;
			else return NULL;
		}
		return $value;
	}

	/**
	 * Form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = array() ) {
		return sprintf('
			<div class="fm-media-uploader">
				<input class="fm-media-button" type="button" id="%1$s_button" value="%3$s" />
				<input type="hidden" name="%2$s" value="%4$s" id="%1$s" class="fm-media-id" />
				<div class="media-wrapper" id="%1$s_thumb">%5$s</div>
			</div>',
			$this->get_element_id(),
			$this->get_form_name(),
			$this->button_label,
			$value,
			is_numeric( $value ) && $value > 0 ? wp_get_attachment_link( $value, 'full' ) : ''
		);
	}

}