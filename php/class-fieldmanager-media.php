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
	 * Label for the preview of the selected image attachment.
	 *
	 * @var string
	 */
	public $selected_image_label;

	/**
	 * Label for the preview of the selected non-image attachment.
	 *
	 * @var string
	 */
	public $selected_file_label;

	/**
	 * Text of the link that deselects the currently selected attachment.
	 *
	 * @var string
	 */
	public $remove_media_label;

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
		$this->button_label         = __( 'Attach a File', 'fieldmanager' );
		$this->modal_button_label   = __( 'Select Attachment', 'fieldmanager' );
		$this->modal_title          = __( 'Choose an Attachment', 'fieldmanager' );
		$this->selected_image_label = __( 'Uploaded image:', 'fieldmanager' );
		$this->selected_file_label  = __( 'Uploaded file:', 'fieldmanager' );
		$this->remove_media_label   = __( 'remove', 'fieldmanager' );

		if ( ! self::$has_registered_media ) {
			fm_add_script( 'fm_media', 'js/media/fieldmanager-media.js', array( 'jquery' ), '1.0.4' );
			if ( did_action( 'admin_print_scripts' ) ) {
				$this->admin_print_scripts();
			} else {
				add_action( 'admin_print_scripts', array( $this, 'admin_print_scripts' ) );
			}
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
		if ( ! empty( $post->ID ) ) {
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
			d($attachment->post_mime_type);
			// open the preview wrapper
			$preview = '<div class="media-file-preview">';
			$file_label = ''; // the uploaded file label - image or file
			// If the preview is an image display the image, otherwise use a media icon
			if ( strpos( $attachment->post_mime_type, 'image/' ) === 0 ) {
				$preview .= '<a href="#">' . wp_get_attachment_image( $value, $this->preview_size, false, array( 'class' => $this->thumbnail_class ) ) . '</a>';
				$file_label = $this->selected_image_label;
			} elseif ( strpos( $attachment->post_mime_type, 'audio/' ) === 0 ) {
				$preview .= '<a href="#"><span class="dashicons dashicons-media-audio"></span></a>';
				$file_label = $this->selected_file_label;
			} elseif ( strpos( $attachment->post_mime_type, 'video/' ) === 0 ) {
				$preview .= '<a href="#"><span class="dashicons dashicons-media-video"></span></a>';
				$file_label = $this->selected_file_label;
			} else {
				// Potentially display other icons for other mime types
				$preview .= '<a href="#"><span class="dashicons dashicons-media-document"></span></a>';
				$file_label = $this->selected_file_label;
			}

			$preview .= sprintf( '<div class="fm-file-detail">%s<h4>%s</h4><span class="fm-file-type">%s</span></div>',
				esc_html( $file_label ),
				wp_get_attachment_link( $value, $this->preview_size, true, true, $attachment->post_title ),
				$attachment->post_mime_type
			);

			$preview .= sprintf( '<a href="#" class="fm-media-edit"><span class="screen-reader-text">%s</span></a>', esc_html__( 'edit', 'fieldmanager' ) );
			$preview .= sprintf( '<a href="#" class="fm-media-remove fm-delete fmjs-remove"><span class="screen-reader-text">%s</span></a>', esc_html( $this->remove_media_label ) );
			$preview .= '</div>';

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
