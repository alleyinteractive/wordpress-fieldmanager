<?php
/**
 * @package Fieldmanager
 */

/**
 * Base class containing core functionality for Fieldmanager
 * 
 * Security features of this class for verifying access and saving data:
 * 1. In Fieldmanager_Field::save_fields_for_post(), verify that we are using an assigned post type
 *    This is the starting point for $_POST requests to post edit pages.
 * 2. In Fieldmanager_Field::save_fields_for_post(), verify that the user can save the post type
 * 3. In Fieldmanager_Field::save_fields_for_post(), perform nonce validation
 * 4. In Fieldmanager_Field::save_to_post_meta(), call Fieldmanager_Field::presave_all()
 *    This is the starting point for save routines that use Fieldmanager as an API
 * 5. In Fieldmanager_Field::presave_all(), verify that the number of elements is less than $limit,
 *    if $limit is not 1 or infinite (0), and verify that all multi-element arrays are numeric only.
 * 6. In Fieldmanager_Field::presave_all(), call Fieldmanager_Field::presave()
 * 5. In Fieldmanager_Field::presave(), call all assigned validators; if one returns false, die.
 * 6a. For groups (including the root group) in Fieldmanager_Group::presave(), verify that all 
 *     keys in the submission match the form names of valid children.
 * 6b. For all other fields, in Fieldmanager_Field::presave(), sanitize the field to save to the DB.
 *     The default sanitizer is sanitize_text_field().
 * 
 * @package Fieldmanager
 */
abstract class Fieldmanager_Field {

	/**
	 * @var debug
	 * If true, throw exceptions for illegal behavior
	 */
	public static $debug = True;

	/**
	 * @var int
	 * How many of these fields to display, 0 for no limit
	 */
	public $limit = 1;

	/**
	 * @var int
	 * How many of these fields to display initially, if $limit > 1
	 */
	public $starting_count = 1;

	/**
	 * @var int
	 * How many extra elements to display if there is already form data and $limit > 1
	 */
	public $extra_elements = 1;

	/**
	 * @var string
	 * Text for add more button
	 */
	public $add_more_label = '';

	/**
	 * @var string
	 * The name of the form element, As in 'foo' in <input name="foo" />
	 */ 
	public $name = '';

	/**
	 * @var string
	 * Label to use for form element
	 */
	public $label = '';

	/**
	 * @var string
	 * Description for the form element
	 */
	public $description = '';

	/**
	 * @var string[]
	 * Extra HTML attributes to apply to the form element
	 */
	public $attributes = array();

	/**
	 * @var string
	 * CSS class for form element
	 */
	public $field_class = 'element';

	/**
	 * @var boolean
	 * Repeat the label for each element if $limit > 1
	 */
	public $one_label_per_item = TRUE;

	/**
	 * @var boolean
	 * Allow draggable sorting if $limit > 1
	 */
	public $sortable = FALSE;

	/**
	 * @var string
	 * HTML element to use for label
	 */
	public $label_element = 'div';

	/**
	 * @var callback
	 * Function to use to sanitize input
	 */
	public $sanitize = 'sanitize_text_field';

	/**
	 * @var callback[]
	 * Functions to use to validate input
	 */
	public $validate = array();

	/**
	 * @var string|null
	 * Data type this element is used in, generally set internally
	 */
	public $data_type = NULL;

	/**
	 * @var int|null
	 * ID for $this->data_type, eg $post->ID, generally set internally
	 */
	public $data_id = NULL;

	/**
	 * @var boolean
	 * If true, save empty elements to DB (if $this->limit != 1; single elements are always saved)
	 */
	public $save_empty = FALSE;

	/**
	 * @var array[]
	 * Array of content type assignments for these fields. Sample:
	 * $element->content_types = array(
	 *     array(
	 *        'meta_box_name' => 'quiz_question',
	 *        'meta_box_title' => 'Quiz Question',
	 *        'content_type' => 'quiz'
	 *     )
	 * );
	 */
	public $content_types = array();

	/**
	 * @var int
	 * If $this->limit > 1, which element in sequence are we currently rendering?
	 */
	protected $seq = 0;

	/**
	 * @var boolean
	 * If $is_proto is true, we're rendering the prototype element for a field that can have infinite instances.
	 */
	protected $is_proto = False;

	/**
	 * @var Fieldmanager_Field
	 * Parent element, if applicable. Would be a Fieldmanager_Group unless third-party plugins support this.
	 */
	protected $parent = Null;

	/**
	 * @todo Add extra wrapper info rather than this specific.
	 * @var boolean
	 * Render this element in a tab?
	 */
	protected $is_tab = False;

	/**
	 * @var int Global Sequence
	 * The global sequence of elements
	 */
	private static $global_seq = 0;

	/**
	 * @param mixed string[]|string the value of the element.
	 * @return string HTML for the element.
	 * Generate HTML for the form element itself. Generally should be just one tag, no wrappers.
	 */
	public abstract function form_element( $value );

	/**
	 * Superclass constructor, just populates options and sanity-checks common elements.
	 * It might also die, but only helpfully, to catch errors in development.
	 * @param array $options with keys matching vars of the field in use.
	 * @throws FM_Developer_Exception if an option is set but not defined in this class or the child class.
	 * @throws FM_Developer_Exception if an option is set but not public.
	 */
	public function __construct( $options = array() ) {
		foreach ( $options as $k => $v ) {
			try {
				$reflection = new ReflectionProperty( $this, $k ); // Would throw a ReflectionException if item doesn't exist (developer error)
				if ( $reflection->isPublic() ) $this->$k = $v;
				else throw new FM_Developer_Exception; // If the property isn't public, don't set it (rare)
			} catch ( Exception $e ) {
				$message = sprintf(
					__( 'You attempted to set a property <em>%1$s</em> that is nonexistant or invalid for an instance of <em>%2$s</em> named <em>%3$s</em>.' ),
					$k, __CLASS__, !empty( $options['name'] ) ? $options['name'] : 'NULL'
				);
				$title = __( 'Nonexistant or invalid option' );
				if ( !self::$debug ) {
					wp_die( $message, $title );
				} else {
					throw new FM_Developer_Exception( $message );
				}
			}
		}
		if ( !empty( $this->content_types ) ) {
			add_action( 'admin_init', array( $this, 'add_meta_boxes' ) );
			add_action( 'save_post', array( $this, 'save_fields_for_post' ) );
		}
	}

	/**
	 * Generates all markup needed for all form elements in this field.
	 * Could be called directly by a plugin or theme.
	 * @param array $values the current values of this element, in a tree structure if the element has children.
	 * @return string HTML for all form elements.
	 */
	public function element_markup( $values = array() ) {
		if ( $this->limit == 0 ) {
			if ( count( $values ) + $this->extra_elements <= $this->starting_count ) {
				$max = $this->starting_count;
			}
			else {
				$max = count( $values ) + $this->extra_elements;
			}
		}
		else {
			$max = $this->limit;
		}

		$classes = array( 'fm-wrapper', 'fm-' . $this->name . '-wrapper' );
		if ( $this->sortable ) {
			$classes[] = 'fmjs-sortable';
		}
		$classes = array_merge( $classes, $this->get_extra_element_classes() );

		$out = '';
		
		// If this element is part of tabbed output, there needs to be a wrapper to contain the tab content
		if ( $this->is_tab ) { 
			$tab_display_style = ( $this->parent->child_count > 0 ) ? ' style="display: none"' : '';
			$out .= '<div id="' . $this->get_element_id() . '-tab" class="wp-tabs-panel"' . $tab_display_style . '>';
		}

		// For lists of items where $one_label_per_item = False, the label should go outside the wrapper.
		if ( !empty( $this->label ) && !$this->one_label_per_item ) {
			$out .= $this->get_element_label( array( 'fm-label-for-list' ) );
		}

		// Find the array position of the "counter" (e.g. in element[0], [0] is the counter, thus the position is 1)
		$html_array_position = 0; // default is no counter; i.e. if $this->limit = 0
		if ( $this->limit != 1 ) {
			$html_array_position = 1; // base situation is formname[0], so the counter is in position 1.
			if ( $this->parent ) {
				$parent = $this->parent;
				while ( $parent ) {
					$html_array_position++; // one more for having a parent (e.g. parent[this][0])
					if ( $parent->limit != 1 ) { // and another for the parent having multiple (e.g. parent[0][this][0])
						$html_array_position++;
					}
					$parent = $parent->parent; // parent's parent; root element has null parent which breaks while loop. 
				}
			}
		}
		$out .= sprintf( '<div class="%s" data-fm-array-position="%d">', implode( ' ', $classes ), $html_array_position );
		
		// After starting the field, apply a filter to allow other plugins to append functionality
		$out = apply_filters( 'fm_element_markup_start', $out, $this );
		
		for ( $i = 0; $i < $max; $i++ ) {
			$this->seq = $i;
			if ( $this->limit == 1 ) {
				$value = $values;
			} else {
				$value = isset( $values[ $i ] ) ? $values[ $i ] : Null;
			}
			$out .= $this->single_element_markup( $value );
		}
		if ( $this->limit == 0 ) {
			$out .= $this->add_another();
		}
		
		// Before closing the field, apply a filter to allow other plugins to append functionality
		$out = apply_filters( 'fm_element_markup_end', $out, $this );
		
		$out .= '</div>';
		
		// Close the tab wrapper if one exists
		if ( $this->is_tab ) $out .= '</div>';
		
		return $out;
	}

	/**
	 * Generate wrappers and labels for one form element.
	 * Is called by element_markup(), calls form_element().
	 * @see Fieldmanager_Field::element_markup()
	 * @see Fieldmanager_Field::form_element()
	 * @param mixed $value the current value of this element.
	 * @param boolean $is_proto true to generate a prototype element for Javascript.
	 * @return string HTML for a single form element.
	 */
	public function single_element_markup( $value = Null, $is_proto = False ) {
		$out = '';
		$classes = array( 'fm-item', 'fm-' . $this->name );

		self::$global_seq++;
		
		// Drop the fm-group class to hide inner box display if no label is set
		if ( !( $this->field_class == 'group' && ( !isset( $this->label ) || empty( $this->label ) ) ) ) {
			$classes[] = 'fm-' . $this->field_class;
		}
		
		if ( $this->get_seq() == 0 && $this->limit == 0 ) {
			// Generate a prototype element for DOM magic on the frontend.
			if ( $is_proto ) {
				$classes[] = 'fmjs-proto';
			} else {
				$this->is_proto = True;
				$out .= $this->single_element_markup( Null, True );
				$this->is_proto = False;
			}
		}

		$out .= sprintf( '<div class="%s">', implode( ' ', $classes ) );

		// Hide the label if it is empty or if this is a tab since it would duplicate the title from the tab label
		if ( !empty( $this->label ) && !$this->is_tab && $this->one_label_per_item ) {
			$label = $this->get_element_label( );
			if ( $this->limit == 0 && $this->one_label_per_item ) {
				$out .= $this->wrap_with_multi_tools( $label, array( 'fmjs-removable-label' ) );
			} else {
				$out .= $label;
			}
		}

		$form_element = $this->form_element( $value );

		if ( $this->limit == 0 && !$this->one_label_per_item ) {
			$out .= $this->wrap_with_multi_tools( $form_element );
		} else {
			$out .= $form_element;
		}
		
		if ( isset( $this->description ) && !empty( $this->description ) ) {
			$out .= sprintf( '<div class="fm-item-description">%s</div>', $this->description );
		}

		$out .= '</div>';
		return $out;
	}

	/**
	 * Wrap a chunk of HTML with "remove" and "move" buttons if applicable.
	 * @param string $html HTML to wrap.
	 * @return string wrapped HTML.
	 */
	public function wrap_with_multi_tools( $html, $classes = array() ) {
		$classes[] = 'fmjs-removable';
		$out = sprintf( '<div class="%s">', implode( ' ', $classes ) );
		if ( $this->sortable ) {
			$out .= $this->get_sort_handle();
		}
		$out .= '<div class="fmjs-removable-element">';
		$out .= $html;
		$out .= '</div>';
		$out .= $this->get_remove_handle();
		$out .= '</div>';
		return $out;
	}

	/**
	 * Get HTML form name (e.g. questions[answer]).
	 * @return string form name
	 */
	public function get_form_name( $multiple = "" ) {
		$tree = $this->get_form_tree();
		$name = '';
		for ( $i = 0; $i < count( $tree ); $i++ ) {
			if ( $i == 0 ) {
				$name .= $tree[$i]->name;
			}
			else {
				$name .= '[' . $tree[$i]->name . ']';
			}
			if ( $tree[$i]->limit != 1 ) {
				$name .= '[' . $tree[$i]->get_seq() . ']';
			}
			if ( $i == count( $tree ) );
		}
		$name .= $multiple;
		
		return $name;
	}

	/**
	 * Recursively build path to this element (e.g. array(grandparent, parent, this) )
	 * @return array of parents
	 */
	public function get_form_tree() {
		$tree = array();
		if ( $this->parent ) {
			$tree = $this->parent->get_form_tree();
		}
		$tree[] = $this;
		return $tree;
	}

	/**
	 * Get the ID for the form element itself, uses $this->seq (e.g. which position is this element in).
	 * @return string ID for use in a form element.
	 */
	public function get_element_id( ) {
		$el = $this;
		$id_slugs = array();
		while ( $el ) {
			$slug = $el->is_proto ? 'proto' : $el->seq;
			array_unshift( $id_slugs, $el->name . '-' . $slug );
			$el = $el->parent;
		}
		return 'fm-' . implode( '-', $id_slugs );
	}

	/**
	 * admin_init callback to add meta boxes to content types
	 * Registers render_meta_box()
	 * @return void
	 */
	public function add_meta_boxes() {
		foreach ( $this->content_types as $type ) {
			add_meta_box(
				$type['meta_box_name'],
				$type['meta_box_title'],
				array( $this, 'render_meta_box' ),
				$type['content_type']
			);
		}
	}

	/**
	 * Helper to attach element_markup() to add_meta_box(). Prints markup for post editor.
	 * @see http://codex.wordpress.org/Function_Reference/add_meta_box
	 * @param $post the post object.
	 * @param $form_struct the structure of the form itself (not very useful).
	 * @return void.
	 */
	public function render_meta_box( $post, $form_struct ) {
		$key = $form_struct['callback'][0]->name;
		$values = get_post_meta( $post->ID, $key, TRUE );
		// if ( $this->name == 'tabs' ) { print_r($values); exit; }
		wp_nonce_field( 'fieldmanager-save-' . $this->name, 'fieldmanager-' . $this->name . '-nonce' );
		echo $this->element_markup( $values );
	}

	/**
	 * Takes $_POST data and saves it to, calling save_to_post_meta() once validation is passed
	 * When using Fieldmanager as an API, do not call this function directly, call save_to_post_meta()
	 * @param int $post_id
	 * @return void
	 */
	public function save_fields_for_post( $post_id ) {
		// Make sure this field is attached to the post type being saved.
		$use_this_post_type = False;
		foreach ( $this->content_types as $type ) {
			if ( $type['content_type'] == $_POST['post_type'] ) {
				$use_this_post_type = True;
				break;
			}
		}
		if ( !$use_this_post_type ) return;

		// Make sure the current user can save this post
		if( $_POST['post_type'] == 'post' ) {
			if( !current_user_can( 'edit_post', $post_id ) ) {
				$this->_unauthorized_access( 'User cannot edit this post' );
				return;
			}
		}

		// Make sure that our nonce field arrived intact
		if( !wp_verify_nonce( $_POST['fieldmanager-' . $this->name . '-nonce'], 'fieldmanager-save-' . $this->name ) ) {
			$this->_unauthorized_access( 'Nonce validation failed' );
		}

		$this->save_to_post_meta( $post_id, $_POST[ $this->name ] );
	}

	/**
	 * Helper to save an array of data to post meta
	 * @param int $post_id
	 * @param array $data
	 * @return void
	 */
	public function save_to_post_meta( $post_id, $data ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		$this->data_id = $post_id;
		$this->data_type = 'post';
		$data = $this->presave_all( $data );
		update_post_meta( $post_id, $this->name, $data );
	}

	/**
	 * Presaves all elements in what could be a set of them, dispatches to $this->presave()
	 * @input mixed[] $values
	 * @return mixed[] sanitized values
	 */
	public function presave_all( $values ) {
		if ( $this->limit == 1 ) {
			return $this->presave( $values );
		}
		
		// If $this->limit != 1, and $values is not an array, that'd just be wrong, and possibly an attack, so...
		if ( $this->limit != 1 && !is_array( $values ) ) {
			$this->_unauthorized_access( '$values should be an array because $limit is ' . $this->limit );
		}

		// If $this->limit is not 0 or 1, and $values has more than $limit, that could also be an attack...
		if ( $this->limit > 1 && count( $values ) > $this->limit ) {
			$this->_unauthorized_access(
				sprintf( 'submitted %1$d values against a limit of %2$d', count( $values ), $this->limit )
			);
		}

		if ( isset( $values['proto'] ) ) {
			unset( $values['proto'] );
		}
		foreach ( $values as $i => $value ) {
			if ( !is_numeric( $i ) ) {
				// If $this->limit != 1 and $values contains something other than a numeric key...
				$this->_unauthorized_access( '$values should be a number-indexed array, but found key ' . $i );
			}
			$values[$i] = $this->presave( $value );
			if ( !$this->save_empty && empty( $values[$i] ) ) unset( $values[$i] );
		}
		return $values;
	}

	/**
	 * Presave function, which handles sanitization and validation
	 * @param mixed $value If a single field expects to manage an array, it must override presave()
	 * @return sanitized values. 
	 */
	public function presave( $value ) {
		// It's possible that some elements (Grid is one) would be arrays at
		// this point, but those elements must override this function. Let's
		// make sure we're dealing with one value here.
		if ( is_array( $value ) ) {
			$this->_unauthorized_access( 'presave() in the base class should not get arrays, but did.' );
		}
		foreach ( $this->validate as $func ) {
			if ( !call_user_func( $func, $value ) ) {
				$this->_failed_validation( sprintf(
					__( 'Input "%1$s" is not valid for field "%2$s" ' ),
					(string) $value,
					$this->label
				) );
			}
		}
		return call_user_func( $this->sanitize, $value );
	}

	/**
	 * Generates an HTML attribute string based on the value of $this->attributes.
	 * @see Fieldmanager_Field::$attributes
	 * @return string attributes ready to insert into an HTML tag.
	 */
	public function get_element_attributes() {
		$attr_str = array();
		foreach ( $this->attributes as $attr => $val ) {
			$attr_str[] = sprintf( '%s="%s"', $attr, str_replace( '"', '\"', $val ) );
		}
		return implode( ' ', $attr_str );
	}

	/**
	 * Get an HTML label for this element.
	 * @param array $classes extra CSS classes.
	 * @return string HTML label.
	 */
	public function get_element_label( $classes = array() ) {
		$classes[] = 'fm-label';
		$classes[] = 'fm-labeladd-' . $this->name;
		return sprintf(
			'<%s class="%s"><label for="%s">%s</label></%s>',
			$this->label_element,
			implode( ' ', $classes ),
			$this->get_element_id( $this->get_seq() ),
			$this->label,
			$this->label_element
		);
	}

	/**
	 * Generates HTML for the "Add Another" button.
	 * @return string button HTML.
	 */
	public function add_another() {
		$classes = array( 'fm-add-another', 'fm-' . $this->name . '-add-another' );
		$out = '<div class="fm-add-another-wrapper">';
		$out .= sprintf(
			'<input type="button" class="%s" value="%s" name="%s" data-related-element="%s" />',
			implode( ' ', $classes ),
			$this->add_more_label,
			'fm_add_another_' . $this->name,
			$this->name
		);
		$out .= '</div>';
		return $out;
	}

	/**
	 * Return HTML for the sort handle (multi-tools); a separate function to override
	 * @return string
	 */
	public function get_sort_handle() {
		return '<div class="fmjs-drag fmjs-drag-icon">Move</div>';
	}

	/**
	 * Return HTML for the remove handle (multi-tools); a separate function to override
	 * @return string
	 */
	public function get_remove_handle() {
		return '<a href="#" class="fmjs-remove" title="Remove">Remove</a>';
	}

	/**
	 * Return HTML for the collapse handle (multi-tools); a separate function to override
	 * @return string
	 */
	public function get_collapse_handle() {
		return '<div class="handlediv" title="Click to toggle"><br /></div>';
	}

	/**
	 * Return extra element classes; overriden by some fields.
	 * @return array
	 */
	public function get_extra_element_classes() {
		return array();
	}

	/**
	 * How many elements should we render?
	 * @return string
	 */
	protected function get_multiple_count( $values ) {
		if ( $this->limit == 0 ) {
			return max( $this->starting_count, count( $values ) + $this->extra_elements );
		}
		else {
			return $this->limit;
		}
	}

	/**
	 * Die violently. If self::$debug is true, throw an exception.
	 * @param string $debug_message
	 * @return void e.g. return _you_ into a void.
	 */
	protected function _unauthorized_access( $debug_message = '' ) {
		if ( self::$debug ) {
			throw new FM_Exception( $debug_message );
		}
		else {
			wp_die( __( 'Sorry, you\'re not supposed to do that...', 'fieldmanager' ) );
		}
	}

	/**
	 * Fail validation. If self::$debug is true, throw an exception.
	 * @param string $error_message
	 * @return void
	 */
	protected function _failed_validation( $debug_message = '' ) {
		if ( self::$debug ) {
			throw new FM_Validation_Exception( $debug_message );
		}
		else {
			wp_die(
				$debug_message . "\n\n" .
				__( 'You may be able to use your browser\'s back button to resolve this error. ', 'fieldmanager' )
			);
		}
	}

	/**
	 * In a multiple element set, return the index of the current element we're rendering.
	 * @return int
	 */
	protected function get_seq() {
		return $this->has_proto() ? 'proto' : $this->seq;
	}

	protected function has_proto() {
		if ( $this->is_proto ) return True;
		if ( $this->parent ) return $this->parent->has_proto();
		return False;
	}
}