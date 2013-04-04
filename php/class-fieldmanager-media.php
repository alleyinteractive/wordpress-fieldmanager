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
	 * Class to attach to thumbnail media display
	 */
	public $thumbnail_class = 'thumbnail';

	/**
	 * @var string
	 * Static variable so we only load media JS once
	 */
	public static $has_registered_media = False;

	/**
	 * Construct default attributes
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label, $options = array() ) {
		add_action( 'admin_print_scripts', function() {
			$post = get_post();	
			$args = array();
			if ( $post->ID ) {
				$args['post'] = $post->ID;
			}
			wp_enqueue_media( $args ); // generally on post pages this will not have an impact.
		} );
		if ( !self::$has_registered_media ) {
			fm_add_script( 'fm_media', 'js/media/fieldmanager-media.js' );
			self::$has_registered_media = True;
		}
		parent::__construct( $label, $options );
	}

	/**
	 * Presave; convert a URL to an attachment ID.
	 */
	public function presave( $value, $current_value = array() ) {
		if ( $value == 0 || !is_numeric( $value ) ) {
			return NULL;
		}
		return $value;
	}

	/**
	 * Form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = array() ) {
		if ( is_numeric( $value ) && $value > 0 ) {
			$attachment = get_post( $value );
			if ( strpos( $attachment->post_mime_type, 'image/' ) === 0 ) {
				$preview = sprintf( '%s<br />', __( 'Uploaded image:' ) );
				$preview .= wp_get_attachment_image( $value, 'thumbnail', false, array( 'class' => $this->thumbnail_class ) );
			} else {
				$preview = sprintf( '%s', __( 'Uploaded file:' ) ) . '&nbsp;';
				$preview .= wp_get_attachment_link( $value, 'thumbnail', True, True, $attachment->post_title );
			}
			$preview .= sprintf( '<br /><a href="#" class="fm-media-remove fm-delete">%s</a>', __( 'remove' ) );
		} else {
			$preview = '';
		}
		return sprintf(
			'<input type="button" class="fm-media-button" id="%1$s" value="%3$s" />
			<input type="hidden" name="%2$s" value="%4$s" class="fm-element fm-media-id" />
			<div class="media-wrapper">%5$s</div>',
			$this->get_element_id(),
			$this->get_form_name(),
			$this->button_label,
			$value,
			$preview
		);
	}

}