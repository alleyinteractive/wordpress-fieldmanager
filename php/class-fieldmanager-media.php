<?php

/**
 * A field to select an attachment via the WordPress Media Manager.
 *
 * This field submits the selected attachment as an attachment ID (post ID).
 *
 * @package Fieldmanager_Field
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
	public $button_label;

	/**
	 * @var string
	 * Button label in the media modal popup
	 */
	public $modal_button_label;

	/**
	 * @var string
	 * Title of the media modal popup
	 */
	public $modal_title;

	/**
	 * @var string
	 * Class to attach to thumbnail media display
	 */
	public $thumbnail_class = 'thumbnail';

	/**
	 * @var string
	 * Which size a preview image should be.
	 * Should be a string (e.g. "thumbnail", "large", or some size created with add_image_size)
	 * You can use an array here
	 */
	public $preview_size = 'thumbnail';

	/**
	 * @var string
	 * What mime types are available to choose from.
	 * Valid options are "all" or a partial or full mimetype (e.g. "image" or
	 * "application/pdf").
	 */
	public $mime_type = 'all';

	/**
	 * @var boolean
	 * Static variable so we only load media JS once
	 */
	public static $has_registered_media = false;

	/**
	 * Construct default attributes
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->button_label       = __( 'Attach a File', 'fieldmanager' );
		$this->modal_button_label = __( 'Select Attachment', 'fieldmanager' );
		$this->modal_title        = __( 'Choose an Attachment', 'fieldmanager' );

		add_action( 'admin_print_scripts', array( $this, 'admin_print_scripts' ) );
		if ( ! self::$has_registered_media ) {
			fm_add_script( 'fm_media', 'js/media/fieldmanager-media.js', array( 'jquery' ), '1.0.4' );
			self::$has_registered_media = true;
		}
		parent::__construct( $label, $options );
	}

	/**
	 * Hook into admin_print_scripts action to enqueue the media for the current
	 * post
	 */
	public function admin_print_scripts() {
		$post = get_post();
		$args = array();
		if ( isset( $post ) && $post->ID ) {
			$args['post'] = $post->ID;
		}
		wp_enqueue_media( $args ); // generally on post pages this will not have an impact.
	}

	/**
	 * Presave; ensure that the value is an absolute integer
	 */
	public function presave( $value, $current_value = array() ) {
		if ( $value == 0 || !is_numeric( $value ) ) {
			return NULL;
		}
		return absint( $value );
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
				$preview = esc_html__( 'Uploaded image:', 'fieldmanager' ) . '<br />';
				$preview .= '<a href="#">' . wp_get_attachment_image( $value, $this->preview_size, false, array( 'class' => $this->thumbnail_class ) ) . '</a>';
			} else {
				$preview = esc_html__( 'Uploaded file:', 'fieldmanager' ) . '&nbsp;';
				$preview .= wp_get_attachment_link( $value, $this->preview_size, True, True, $attachment->post_title );
			}
			$preview .= sprintf( '<br /><a href="#" class="fm-media-remove fm-delete">%s</a>', esc_html__( 'remove', 'fieldmanager' ) );
			$preview = apply_filters( 'fieldmanager_media_preview', $preview, $value, $attachment );
		} else {
			$preview = '';
		}
		return sprintf(
			'<input type="button" class="fm-media-button button-secondary fm-incrementable" id="%1$s" value="%3$s" data-choose="%7$s" data-update="%8$s" data-preview-size="%6$s" data-mime-type="%9$s" %10$s />
			<input type="hidden" name="%2$s" value="%4$s" class="fm-element fm-media-id" />
			<div class="media-wrapper">%5$s</div>',
			esc_attr( $this->get_element_id() ),
			esc_attr( $this->get_form_name() ),
			esc_attr( $this->button_label ),
			esc_attr( $value ),
			$preview,
			esc_attr( $this->preview_size ),
			esc_attr( $this->modal_title ),
			esc_attr( $this->modal_button_label ),
			esc_attr( $this->mime_type ),
			$this->get_element_attributes()
		);
	}

}