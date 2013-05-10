<?php
/**
 * @package Fieldmanager_Context
 */
 
/**
 * Use fieldmanager on the public-facing theme.
 * @package Fieldmanager_Datasource
 */
class Fieldmanager_Context_Page extends Fieldmanager_Context {

	public static $forms = array();

	private $uniqid;

	public $did_save = False;

	/**
	 * Create page context handler.
	 */
	public function __construct( $uniqid, $fm ) {
		$this->fm = $fm;

		self::$forms[$uniqid] = $this;
		$this->uniqid = $uniqid;
		// since this should be set up in init, check for submit now
		if ( !empty( $_POST ) && !empty( $_POST['fm-page-action'] ) && esc_html( $_POST['fm-page-action'] ) == $uniqid ) {
			$this->save_page_form();
		}
	}

	public function save_page_form() {
		if( !wp_verify_nonce( $_POST['fieldmanager-' . $this->fm->name . '-nonce'], 'fieldmanager-save-' . $this->fm->name ) ) {
			$this->fm->_unauthorized_access( 'Nonce validation failed' );
		}
		$this->fm->data_id = $user_id;
		if ( empty( $this->fm->data_type ) ) $this->fm->data_type = 'page';
		if ( empty( $this->fm->data_id ) ) $this->fm->data_id = $this->uniqid;
		$current = apply_filters( 'fm_' . $this->uniqid . '_load', array(), $this->fm );
		$data = apply_filters( 'fm_' . $this->uniqid . '_presave', $_POST[ $this->fm->name ], $this->fm );
		$data = $this->fm->presave_all( $data, $current );
		$data = apply_filters( 'fm_presave_data', $data, $this->fm );
		do_action( 'fm_' . $this->uniqid . '_save', $data, $current, $this->fm );
		$this->did_save = True;
	}

	public static function get_form( $uniqid ) {
		return self::$forms[$uniqid];
	}

	public function render_page_form() {
		$current = apply_filters( 'fm_' . $this->uniqid . '_load', array(), $this->fm );
		echo '<form method="POST">';
		echo '<div class="fm-page-form-wrapper">';
		printf( '<input type="hidden" name="fm-page-action" value="%s" />', sanitize_title( $this->uniqid ) );
		wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce' );
		echo $this->fm->element_markup( $current );
		echo '</div>';
		printf( '<input type="submit" name="fm-submit" class="button-primary" value="%s" />', $this->fm->submit_button_label ?: __( 'Save Options' ) );
		echo '</form>';
		echo '</div>';
	}

}

function fm_the_page_form( $uniqid ) {
	Fieldmanager_Context_Page::get_form( $uniqid )->render_page_form();
}

function fm_page_form_did_save( $uniqid ) {
	return Fieldmanager_Context_Page::get_form( $uniqid )->did_save;
}