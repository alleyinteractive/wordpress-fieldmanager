<?php
/**
 * @package Fieldmanager_Context
 */
 
/**
 * Use fieldmanager on the public-facing theme.
 * @package Fieldmanager_Context
 */
class Fieldmanager_Context_Form extends Fieldmanager_Context {

	/**
	 * @var Fieldmanager_Context_Form[]
	 * Array of contexts for rendering forms
	 */
	public static $forms = array();

	/**
	 * @var array[]
	 * Array of error tuples
	 */
	public $errors = array();

	/**
	 * @var array[]
	 * Array of message tuples
	 */
	public $messages = array();

	/**
	 * @var array
	 * Form values to render
	 */
	public $values = array();

	/**
	 * @var string
	 * Form wrapper element
	 */
	public $wrapper_start = '<div class="fm-page-form-wrapper">';

	/**
	 * @var string
	 * Closing tag for form wrapper element
	 */
	public $wrapper_end = '</div>';

	/**
	 * Create page context handler.
	 * @param string unique form ID
	 * @param Fieldmanager_Field $fm
	 */
	public function __construct( $uniqid, $fm ) {
		$this->fm = $fm;
		$this->fm->build_tree(); // Fieldmanager_Group normally does this for itself when it renders, but we may be using groups without actually rendering them.
		$this->uniqid = $uniqid;

		// since this should be set up in init, check for submit now
		if ( !empty( $_POST ) && ! empty( $_POST['fm-form-context'] ) && esc_html( $_POST['fm-form-context'] ) == $uniqid ) {
			$this->save_page_form();
		}
	}

	/**
	 * Generate a form element's markup
	 * See element() for arguments.
	 */
	public function element_html() {
		$el = $this->fm;
		$path = func_get_args();
		foreach ( $path as $part ) {
			if ( !empty( $el->children[ $part ] ) ) {
				$el = $el->children[ $part ];
			} else {
				throw new FM_Developer_Exception( 'You referenced an element which does not exist' );
			}
		}
		$values = ( ! empty( $this->values[$el->name] ) ) ? $this->values[$el->name] : array();
		return $el->element_markup( $values );
	}

	/**
	 * Render a form element's markup
	 * @param path to the element; if it's in the root group, just the element name.
	 */
	public function element() {
		echo call_user_func_array( array( $this, 'element_html' ), func_get_args() );
	}

	/**
	 * Action to save the form
	 * @return void
	 */
	public function save_page_form() {
		if( !wp_verify_nonce( $_POST['fieldmanager-' . $this->fm->name . '-nonce'], 'fieldmanager-save-' . $this->fm->name ) ) {
			return $this->fm->_unauthorized_access( 'Nonce validation failed' );
		}

		$this->values = isset( $_POST[ $this->fm->name ] ) ? $_POST[ $this->fm->name ] : '';

		if ( empty( $this->fm->data_type ) ) $this->fm->data_type = 'page';
		if ( empty( $this->fm->data_id ) ) $this->fm->data_id = $this->uniqid;

		$current = apply_filters( 'fm_form_' . $this->uniqid . '_load', array(), $this );
		$this->values = apply_filters( 'fm_form_' . $this->uniqid . '_presave', $this->values, $this );

		$this->values = $this->fm->presave_all( $this->values, $current );
		$this->values = apply_filters( 'fm_form_presave_data', $this->values, $this );
		do_action( 'fm_form_' . $this->uniqid . '_save', $this->values, $current, $this );
	}

	/**
	 * Build HTML for the form
	 * @return void
	 */
	public function page_form_html() {
		$buffer = $this->wrapper_start;
		$buffer .= $this->get_the_errors();
		$buffer .= $this->get_the_messages();
		$buffer .= $this->get_form_start();
		$buffer .= $this->fm->element_markup( $this->values );
		$buffer .= sprintf( '<input type="submit" name="fm-submit" class="button-primary" value="%s" />', esc_attr( $this->fm->submit_button_label ) ?: __( 'Save Options' ) );
		$buffer .= $this->get_form_end();
		$buffer .= $this->wrapper_end;
		return $buffer;
	}

	/**
	 * Output HTML for the form
	 */
	public function render_page_form() {
		echo $this->page_form_html();
	}

	/**
	 * Output the opening form tag.
	 * @param mixed $attributes to override defaults, eg method => GET.
	 */
	public function get_form_start( $params = array() ) {
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
			$args[] = sprintf( '%s="%s"', $k, esc_attr( $v ) );
		}
		$buffer = sprintf( '<form %s>', implode( ' ', $args ) );
		$buffer .= $this->get_form_meta();
		return $buffer;
	}

	/** 
	 * Output the opening form tag
	 * @param mixed $attributes to override defaults, eg method => GET.
	 */
	public function form_start( $params = array() ) {
		echo $this->get_form_start( $params );
	}

	/**
	 * Build the closing form tag, for symmetry.
	 */
	public function get_form_end() {
		return '</form>';
	}

	/**
	 * Output closing form tag
	 */
	public function form_end() {
		echo $this->get_form_end();
	}

	/**
	 * Build necessary opening form tags, check for validation and include if required
	 */
	public function get_form_meta() {
		$buffer = sprintf( '<input type="hidden" name="fm-form-context" value="%s" />', sanitize_title( $this->uniqid ) );
		$buffer .= wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce', true, false );
		// Check if any validation is required
		$fm_validation = Fieldmanager_Util_Validation( $this->uniqid, 'form' );
		$fm_validation->add_field( $this->fm );
		return $buffer;
	}

	/**
	 * Output opening form tags
	 */
	public function form_meta() {
		echo $this->get_form_meta();
	}

	/**
	 * Trigger a form error.
	 * @param string $el name of element
	 * @param string $message error message
	 */
	public function error( $el, $message ) {
		$this->errors[] = array( $el, $message );
	}

	/**
	 * Add a form message
	 * @param string $el name of element
	 * @param string $message message to render
	 */
	public function message( $el, $message ) {
		$this->messages[] = array( $el, $message );
	}

	/**
	 * Build form errors HTML
	 */
	public function get_the_errors() {
		return $this->get_the_messages( 'fm-errors', $this->errors );
	}

	/**
	 * Output form errors
	 */
	public function the_errors() {
		echo $this->get_the_errors();
	}

	/**
	 * Build messages or errors
	 * @param string $class usually 'fm-errors' or 'fm-messages' but coule be user-custom
	 * @param string[] $messages list of message tuples, like [element_name, message]
	 */
	public function get_the_messages( $class = 'fm-messages', $messages = null ) {
		$buffer = '';
		if ( !$messages ) $messages = $this->messages;
		if ( empty( $messages ) ) return $buffer;
		$buffer .= '<div class="' . $class . '">';
		foreach ( $messages as $message ) {
			// $error is always set in code, may contain HTML.
			$buffer .= '<p>' . $message[1] . '</p>';
		}
		$buffer .= '</div>';
		return $buffer;
	}

	/**
	 * Output messages HTML
	 */
	public function the_messages( $class = 'fm-messages', $messages = null ) {
		echo $this->get_the_messages( $class, $messages );
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