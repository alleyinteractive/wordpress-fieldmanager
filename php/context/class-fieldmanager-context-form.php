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
	 * @var Fieldmanager_Context_Form[]
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
		$this->fm->build_tree(); // Fieldmanager_Group normally does this for itself when it renders, but we may be using groups without actually rendering them.
		if ( !empty( self::$forms[$uniqid] ) ) throw new FM_Developer_Exception( $uniqid . ' has already been used' );
		self::$forms[$uniqid] = $this;
		$this->uniqid = $uniqid;

		// since this should be set up in init, check for submit now
		if ( !empty( $_POST ) && ! empty( $_POST['fm-form-context'] ) && esc_html( $_POST['fm-form-context'] ) == $uniqid ) {
			$this->save_page_form();
		}
	}

	/**
	 * Render a form element
	 */
	public function element() {
		$path = func_get_args();
		$el = $this->fm;
		foreach ( $path as $part ) {
			if ( $el->children[ $part ] ) {
				$el = $el->children[ $part ];
			} else {
				throw new FM_Developer_Exception( 'You referenced an element which does not exist' );
			}
		}
		echo $el->element_markup( array() );
	}

	/**
	 * Action to save the form
	 * @return void
	 */
	public function save_page_form() {
		if( !wp_verify_nonce( $_POST['fieldmanager-' . $this->fm->name . '-nonce'], 'fieldmanager-save-' . $this->fm->name ) ) {
			$this->fm->_unauthorized_access( 'Nonce validation failed' );
		}

		$submission = isset( $_POST[ $this->fm->name ] ) ? $_POST[ $this->fm->name ] : "";

		if ( empty( $this->fm->data_type ) ) $this->fm->data_type = 'page';
		if ( empty( $this->fm->data_id ) ) $this->fm->data_id = $this->uniqid;

		$current = apply_filters( 'fm_' . $this->uniqid . '_load', array(), $this->fm );
		$submission = apply_filters( 'fm_' . $this->uniqid . '_presave', $submission, $this->fm );
		$submission = $this->fm->presave_all( $submission, $current );
		$submission = apply_filters( 'fm_form_presave_data', $submission, $this->fm );
		do_action( 'fm_form_' . $this->uniqid . '_save', $submission, $current, $this->fm );
		$this->did_save = True;
	}

	/**
	 * Output HTML for the form
	 * @return void
	 */
	public function render_page_form() {
		echo '<div class="fm-page-form-wrapper">';
		$this->form_start();
		echo $this->fm->element_markup( $this->values );
		printf( '<input type="submit" name="fm-submit" class="button-primary" value="%s" />', esc_attr( $this->fm->submit_button_label ) ?: __( 'Save Options' ) );
		echo '</form>';
		echo '</div>';
		echo '</div>';
	}

	/**
	 * Output the opening form tag.
	 * @param mixed $attributes to override defaults, eg method => GET.
	 */
	public function form_start( $params = array() ) {
		$this->values = apply_filters( 'fm_' . $this->uniqid . '_values', array(), $this->fm );
		$defaults = array(
			'role' => 'form',
			'method' => 'POST',
			'action' => '',
			'id' => $this->uniqid,	
		);
		$params = array_merge( $defaults, $params );
		$args = array();
		foreach ( $params as $k => $v ) {
			$args[] = sprintf( '%s="%s"', $k, $v );
		}
		printf( '<form %s>', implode( ' ', $args ) );
		$this->form_meta();
	}

	/**
	 * Output the closing form tag, for symetry.
	 */
	public function form_end() {
		echo '</form>';
	}

	/**
	 * Output necessary opening form tags, check for validation
	 */
	public function form_meta() {
		printf( '<input type="hidden" name="fm-form-context" value="%s" />', sanitize_title( $this->uniqid ) );
		wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce' );
		// Check if any validation is required
		$fm_validation = Fieldmanager_Util_Validation( $this->uniqid, 'form' );
		$fm_validation->add_field( $this->fm );
	}

	/**
	 * Get the associated validator of a form
	 * @return Fieldmanager_Util_Validation
	 */
	public function get_validator() {
		return Fieldmanager_Util_Validation::instance( $this->uniqid, 'form' );
	}

	/**
	 * Get a form by ID, used for rendering
	 * @param string $uniqid
	 * @return Fieldmanager_Context_Form
	 */
	public static function get_form( $uniqid ) {
		return self::$forms[$uniqid];
	}

}

/**
 * Check to see if the form saved (useful for error messages)
 * @param string $uniqid
 * @return void
 */
function fm_page_form_did_save( $uniqid ) {
	return Fieldmanager_Context_Form::get_form( $uniqid )->did_save;
}
