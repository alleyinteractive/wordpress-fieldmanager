<?php
/**
 * Class file for Fieldmanager_Media
 *
 * @package Fieldmanager
 */

/**
 * A field to select an attachment via the WordPress Media Manager.
 *
 * This field submits the selected attachment as an attachment ID (post ID).
 */
class Fieldmanager_Media extends Fieldmanager_Field {

	/**
	 * Override field_class.
	 *
	 * @var string
	 */
	public $field_class = 'media';

	/**
	 * Button Label.
	 *
	 * @var string
	 */
	public $button_label;

	/**
	 * Button label in the media modal popup.
	 *
	 * @var string
	 */
	public $modal_button_label;

	/**
	 * Title of the media modal popup.
	 *
	 * @var string
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
	 * Class to attach to thumbnail media display.
	 *
	 * @var string
	 */
	public $thumbnail_class = 'thumbnail';

	/**
	 * Which size a preview image should be. e.g. "thumbnail", "large", or some
	 * size created with add_image_size.
	 *
	 * @var string
	 */
	public $preview_size = 'thumbnail';

	/**
	 * What mime types are available to choose from.
	 * Valid options are "all" or a partial or full mimetype (e.g. "image" or
	 * "application/pdf").
	 *
	 * @var string
	 */
	public $mime_type = 'all';

	/**
	 * Static variable so we only load media JS once.
	 *
	 * @var bool
	 */
	public static $has_registered_media = false;

	/**
	 * Construct default attributes.
	 *
	 * @param string $label   The form label.
	 * @param array  $options The form options.
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->button_label         = __( 'Attach a File', 'fieldmanager' );
		$this->modal_button_label   = __( 'Select Attachment', 'fieldmanager' );
		$this->modal_title          = __( 'Choose an Attachment', 'fieldmanager' );
		$this->selected_image_label = __( 'Uploaded image:', 'fieldmanager' );
		$this->selected_file_label  = __( 'Uploaded file:', 'fieldmanager' );
		$this->remove_media_label   = __( 'remove', 'fieldmanager' );

		if ( ! self::$has_registered_media ) {
			fm_add_script( 'fm_media', 'js/media/fieldmanager-media.js', array( 'jquery' ), FM_VERSION, true );
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
	 * Presave; ensure that the value is an absolute integer.
	 *
	 * @param  int   $value         The new value.
	 * @param  array $current_value The current value.
	 * @return int The sanitized value.
	 */
	public function presave( $value, $current_value = array() ) {
		if ( 0 == $value || ! is_numeric( $value ) ) {
			return null;
		}
		return absint( $value );
	}

	/**
	 * Form element.
	 *
	 * @param mixed $value The current value.
	 * @return string HTML string.
	 */
	public function form_element( $value = array() ) {
		if ( is_numeric( $value ) && $value > 0 ) {
			$attachment = get_post( $value );
			if ( strpos( $attachment->post_mime_type, 'image/' ) === 0 ) {
				$preview  = esc_html( $this->selected_image_label ) . '<br />';
				$preview .= '<a href="#">' . wp_get_attachment_image(
					$value,
					$this->preview_size,
					false,
					array(
						'class' => $this->thumbnail_class,
					)
				) . '</a>';
			} else {
				$preview  = esc_html( $this->selected_file_label ) . '&nbsp;';
				$preview .= wp_get_attachment_link( $value, $this->preview_size, true, true, $attachment->post_title );
			}
			$preview .= sprintf( '<br /><a href="#" class="fm-media-remove fm-delete">%s</a>', esc_html( $this->remove_media_label ) );
			$preview  = apply_filters( 'fieldmanager_media_preview', $preview, $value, $attachment );
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
