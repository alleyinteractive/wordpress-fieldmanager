<?php
/**
 * @package Fieldmanager_Context
 */
 
/**
 * Use fieldmanager on the public-facing theme.
 * @package Fieldmanager_Datasource
 */
class Fieldmanager_Context_Form extends Fieldmanager_Context {

	/**
	 * @var Fieldmanager_Context_Page[]
	 * Array of contexts for rendering forms
	 */
	public static $forms = array();

	/**
	 * @var boolean
	 * Was the form saved?
	 */
	public $did_save = False;

	/**
	 * Create page context handler.
	 * @param string unique form ID
	 * @param Fieldmanager_Field $fm
	 */
	public function __construct( $uniqid, $fm ) {
		$this->fm = $fm;
		self::$forms[$uniqid] = $this;
		$this->uniqid = $uniqid;

		// since this should be set up in init, check for submit now
		if ( !empty( $_POST ) && ! empty( $_POST['fm-page-action'] ) && esc_html( $_POST['fm-page-action'] ) == $uniqid ) {
			$this->save_page_form();
		}
	}

	/**
	 * Action to save the form
	 * @return void
	 */
	public function save_page_form() {
		if( !wp_verify_nonce( $_POST['fieldmanager-' . $this->fm->name . '-nonce'], 'fieldmanager-save-' . $this->fm->name ) ) {
			$this->fm->_unauthorized_access( 'Nonce validation failed' );
		}
		$this->fm->data_id = $user_id;
		$value = isset( $_POST[ $this->fm->name ] ) ? $_POST[ $this->fm->name ] : "";
		if ( empty( $this->fm->data_type ) ) $this->fm->data_type = 'page';
		if ( empty( $this->fm->data_id ) ) $this->fm->data_id = $this->uniqid;
		$current = apply_filters( 'fm_' . $this->uniqid . '_load', array(), $this->fm );
		$data = apply_filters( 'fm_' . $this->uniqid . '_presave', $value, $this->fm );
		$data = $this->fm->presave_all( $data, $current );
		$data = apply_filters( 'fm_presave_data', $data, $this->fm );
		do_action( 'fm_' . $this->uniqid . '_save', $data, $current, $this->fm );
		$this->did_save = True;
	}

	/**
	 * Output HTML for the form
	 * @return void
	 */
	public function render_page_form() {
		$current = apply_filters( 'fm_' . $this->uniqid . '_load', array(), $this->fm );
		echo '<form method="POST" id="' . esc_attr( $this->uniqid ) . '">';
		echo '<div class="fm-page-form-wrapper">';
		printf( '<input type="hidden" name="fm-page-action" value="%s" />', sanitize_title( $this->uniqid ) );
		wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce' );
		echo $this->fm->element_markup( $current );
		echo '</div>';
		printf( '<input type="submit" name="fm-submit" class="button-primary" value="%s" />', esc_attr( $this->fm->submit_button_label ) ?: __( 'Save Options' ) );
		echo '</form>';
		echo '</div>';
		
		// Check if any validation is required
		$fm_validation = Fieldmanager_Util_Validation( $this->uniqid, 'page' );
		$fm_validation->add_field( $this->fm );
	}

	/**
	 * Get a form by ID, used for rendering
	 * @param string $uniqid
	 * @return Fieldmanager_Context_Page
	 */
	public static function get_form( $uniqid ) {
		return self::$forms[$uniqid];
	}

}

/**
 * Template tag to output a form by unique ID
 * @param string $uniqid
 * @return void
 */
function fm_the_page_form( $uniqid ) {
	Fieldmanager_Context_Page::get_form( $uniqid )->render_page_form();
}

/**
 * Check to see if the form saved (useful for error messages)
 * @param string $uniqid
 * @return void
 */
function fm_page_form_did_save( $uniqid ) {
	return Fieldmanager_Context_Page::get_form( $uniqid )->did_save;
}
