<?php
/**
 * @package Fieldmanager_Context
 */
 
/**
 * Use fieldmanager on the public-facing form
 * @package Fieldmanager_Datasource
 */
class Fieldmanager_Context_Page extends Fieldmanager_Context {

	public static $forms = array();

	private $uniqid;

	public $load_callback = Null;

	public $save_callback = Null;

	public function __construct( $uniqid, $fm ) {
		$this->fm = $fm;

		$this->load_callback = function() { return array(); };
		$this->save_callback = function() { return array(); };
		
		self::$forms[$uniqid] = $this;
		$this->uniqid = $uniqid;
		// since this only ever gets set up in init, check for submit now
		if ( !empty( $_POST ) && !empty( $_POST['fieldmanager_page'] ) && esc_html( $_POST['fm-page-action'] ) == $uniqid ) {
			$this->save_page_form();
		}
	}

	public function save_page_form() {

	}

	public static function get_form( $uniqid ) {
		return self::$forms[$uniqid];
	}

	public function render_page_form() {
		$values = call_user_func( $this->load_callback );
		echo '<form method="POST">';
		echo '<div class="fm-page-form-wrapper">';
		printf( '<input type="hidden" name="fm-page-action" value="%s" />', sanitize_title( $this->uniqid ) );
		wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce' );
		echo $this->fm->element_markup( $values );
		echo '</div>';
		printf( '<input type="submit" name="fm-submit" class="button-primary" value="%s" />', $this->fm->submit_button_label ?: __( 'Save Options' ) );
		echo '</form>';
		echo '</div>';
	}

}

function fm_the_page_form( $uniqid ) {
	Fieldmanager_Context_Page::get_form( $uniqid )->render_page_form();
}