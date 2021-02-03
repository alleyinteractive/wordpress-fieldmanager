<?php
/**
 * Class file for Fieldmanager_Context_Page
 *
 * @package Fieldmanager
 */

/**
 * Use Fieldmanager on the public-facing theme.
 *
 * @deprecated 1.2.0
 */
class Fieldmanager_Context_Page extends Fieldmanager_Context {

	/**
	 * Array of contexts for rendering forms.
	 *
	 * @var array Fieldmanager_Context_Page objects.
	 */
	public static $forms = array();

	/**
	 * Was the form saved?
	 *
	 * @var bool
	 */
	public $did_save = false;

	/**
	 * Create page context handler.
	 *
	 * @param string             $uniqid Unique form ID.
	 * @param Fieldmanager_Field $fm     The field instance.
	 */
	public function __construct( $uniqid, $fm ) {
		_deprecated_function( __METHOD__, '1.2.0' );

		$this->fm               = $fm;
		self::$forms[ $uniqid ] = $this;
		$this->uniqid           = $uniqid;

		// since this should be set up in init, check for submit now.
		if ( ! empty( $_POST ) && ! empty( $_POST['fm-page-action'] ) && esc_html( $_POST['fm-page-action'] ) == $uniqid ) { // WPCS: input var okay. CSRF ok. sanitization ok.
			$this->save_page_form();
		}
	}

	/**
	 * Action to save the form.
	 */
	public function save_page_form() {
		_deprecated_function( __METHOD__, '1.2.0' );

		if (
			isset( $_POST[ 'fieldmanager-' . $this->fm->name . '-nonce' ] ) // WPCS: input var okay.
			&& ! wp_verify_nonce( $_POST[ 'fieldmanager-' . $this->fm->name . '-nonce' ], 'fieldmanager-save-' . $this->fm->name )  // WPCS: input var okay. sanitization ok.
		) {
			$this->fm->_unauthorized_access( __( 'Nonce validation failed', 'fieldmanager' ) );
		}
		$this->fm->data_id = $user_id;
		$value             = isset( $_POST[ $this->fm->name ] ) ? $_POST[ $this->fm->name ] : ''; // WPCS: input var okay. sanitization ok.
		if ( empty( $this->fm->data_type ) ) {
			$this->fm->data_type = 'page';
		}
		if ( empty( $this->fm->data_id ) ) {
			$this->fm->data_id = $this->uniqid;
		}
		$current = apply_filters( 'fm_' . $this->uniqid . '_load', array(), $this->fm );
		$data    = apply_filters( 'fm_' . $this->uniqid . '_presave', $value, $this->fm );
		$data    = $this->fm->presave_all( $data, $current );
		$data    = apply_filters( 'fm_presave_data', $data, $this->fm );
		do_action( 'fm_' . $this->uniqid . '_save', $data, $current, $this->fm );
		$this->did_save = true;
	}

	/**
	 * Output HTML for the form.
	 */
	public function render_page_form() {
		_deprecated_function( __METHOD__, '1.2.0' );

		$current = apply_filters( 'fm_' . $this->uniqid . '_load', array(), $this->fm );
		echo '<form method="POST" id="' . esc_attr( $this->uniqid ) . '">';
		echo '<div class="fm-page-form-wrapper">';
		printf( '<input type="hidden" name="fm-page-action" value="%s" />', sanitize_title( $this->uniqid ) ); // WPCS: XSS ok.
		wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce' );
		echo $this->fm->element_markup( $current ); // WPCS: XSS ok.
		echo '</div>';
		printf( '<input type="submit" name="fm-submit" class="button-primary" value="%s" />', esc_attr( $this->fm->submit_button_label ) ?: esc_attr__( 'Save Options', 'fieldmanager' ) );
		echo '</form>';
		echo '</div>';

		// Check if any validation is required.
		$fm_validation = fieldmanager_util_validation( $this->uniqid, 'page' );
		$fm_validation->add_field( $this->fm );
	}

	/**
	 * Get a form by ID, used for rendering.
	 *
	 * @param  string $uniqid Unique form ID.
	 * @return Fieldmanager_Context_Page The page form.
	 */
	public static function get_form( $uniqid ) {
		_deprecated_function( __METHOD__, '1.2.0' );

		return self::$forms[ $uniqid ];
	}

}

/**
 * Template tag to output a form by unique ID.
 *
 * @param string $uniqid Unique form ID.
 */
function fm_the_page_form( $uniqid ) {
	_deprecated_function( __FUNCTION__, '1.2.0' );

	Fieldmanager_Context_Page::get_form( $uniqid )->render_page_form();
}

/**
 * Check to see if the form saved (useful for error messages).
 *
 * @param string $uniqid Unique form ID.
 */
function fm_page_form_did_save( $uniqid ) {
	_deprecated_function( __FUNCTION__, '1.2.0' );

	return Fieldmanager_Context_Page::get_form( $uniqid )->did_save;
}
