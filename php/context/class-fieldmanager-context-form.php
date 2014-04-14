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

	public $errors = array();

	public $messages = array();

	public $values = array();

	/**
	 * Create page context handler.
	 * @param string unique form ID
	 * @param Fieldmanager_Field $fm
	 */
	private function __construct( $uniqid, $fm ) {
		$this->fm = $fm;
		$this->fm->build_tree(); // Fieldmanager_Group normally does this for itself when it renders, but we may be using groups without actually rendering them.
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
		$values = ( ! empty( $this->values[$el->name] ) ) ? $this->values[$el->name] : array();
		echo $el->element_markup( $values );
	}

	/**
	 * Action to save the form
	 * @return void
	 */
	public function save_page_form() {
		if( !wp_verify_nonce( $_POST['fieldmanager-' . $this->fm->name . '-nonce'], 'fieldmanager-save-' . $this->fm->name ) ) {
			$this->fm->_unauthorized_access( 'Nonce validation failed' );
		}

		$this->values = isset( $_POST[ $this->fm->name ] ) ? $_POST[ $this->fm->name ] : "";

		if ( empty( $this->fm->data_type ) ) $this->fm->data_type = 'page';
		if ( empty( $this->fm->data_id ) ) $this->fm->data_id = $this->uniqid;

		$current = apply_filters( 'fm_form_' . $this->uniqid . '_load', array(), $this );
		$this->values = apply_filters( 'fm_form_' . $this->uniqid . '_presave', $this->values, $this );

		$this->values = $this->fm->presave_all( $this->values, $current );
		$this->values = apply_filters( 'fm_form_presave_data', $this->values, $this );
		do_action( 'fm_form_' . $this->uniqid . '_save', $this->values, $current, $this );
	}

	/**
	 * Output HTML for the form
	 * @return void
	 */
	public function render_page_form() {
		echo '<div class="fm-page-form-wrapper">';
		$this->the_errors();
		$this->the_messages();
		$this->form_start();
		echo $this->fm->element_markup( $this->values );
		printf( '<input type="submit" name="fm-submit" class="button-primary" value="%s" />', esc_attr( $this->fm->submit_button_label ) ?: __( 'Save Options' ) );
		echo '</form>';
		echo '</div>';
	}

	/**
	 * Output the opening form tag.
	 * @param mixed $attributes to override defaults, eg method => GET.
	 */
	public function form_start( $params = array() ) {
		$this->values = apply_filters( 'fm_form_' . $this->uniqid . '_values', $this->values, $this );
		$defaults = array(
			'role' => 'form',
			'method' => 'POST',
			'action' => '',
			'id' => $this->uniqid,
			'enctype' => 'multipart/form-data',
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

	public function error( $el, $message ) {
		$this->errors[] = array( $el, $message );
	}

	public function message( $el, $message ) {
		$this->messages[] = array( $el, $message );
	}

	public function the_errors() {
		$this->the_messages( 'fm-errors', $this->errors );
	}

	public function the_messages( $class = 'fm-messages', $messages = null ) {
		if ( !$messages ) $messages = $this->messages;
		if ( empty( $messages ) ) return;
		echo '<div class="' . $class . '">';
		foreach ( $messages as $message ) {
			// $error is always set in code, may contain HTML.
			echo '<p>' . $message[1] . '</p>';
		}
		echo '</div>';
	}

	/**
	 * Does the form have errors? Useful for breaking out of a submission routine.
	 */
	public function has_errors() {
		return count( $this->errors ) > 0;
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
	public static function get_form( $uniqid, $fm = null ) {
		if ( empty( self::$forms[$uniqid] ) ) {
			if ( !empty( $fm ) ) {
				self::$forms[$uniqid] = new Fieldmanager_Context_Form( $uniqid, $fm );
			} else {
				return null;
			}
		}
		return self::$forms[$uniqid];
	}

}