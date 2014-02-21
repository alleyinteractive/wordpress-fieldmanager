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
	 * @var boolean
	 * If true, throw exceptions for illegal behavior
	 */
	public static $debug = FM_DEBUG;

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
	 * @var boolean
	 * If true, the label and the element will display on the same line. Some elements may not support this.
	 */
	public $inline_label = False;

	/**
	 * @var boolean
	 * If true, the label will be displayed after the element.
	 */
	public $label_after_element = False;

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
	 * @var string|array
	 * jQuery validation rule(s) used to validate this field, entered as a string or associative array.
	 * These rules will be automatically converted to the appropriate Javascript format.
	 * For more information see http://jqueryvalidation.org/documentation/
	 */
	public $validation_rules;

	/**
	 * @var string|array
	 * jQuery validation messages used by the rule(s) defined for this field, entered as a string or associative array.
	 * These rules will be automatically converted to the appropriate Javascript format.
	 * Any messages without a corresponding rule will be ignored.
	 * For more information see http://jqueryvalidation.org/documentation/
	 */
	public $validation_messages;

	/**
	 * @var boolean
	 * Makes the field required on WordPress context forms that already have built-in validation.
	 * This is necessary only for the fields used with the term add context.
	 */
	public $required = false;

	/**
	 * @var string|null
	 * Data type this element is used in, generally set internally
	 */
	public $data_type = NULL;

	/**
	 * @var int|null
	 * ID for $this->data_type, eg $post->ID, generally set internally
	 */
	public $data_id = Null;

	/**
	 * @var boolean
	 * If true, save empty elements to DB (if $this->limit != 1; single elements are always saved)
	 */
	public $save_empty = False;

	/**
	 * @var boolean
	 * Do not save this field (useful for fields which handle saving their own data)
	 */
	public $skip_save = False;

	/**
	 * @var string
	 * Save this field additionally to an index
	 */
	public $index = False;

	/**
	 * @var Fieldmanager_Datasource
	 * Optionally generate field from datasource. Used by Fieldmanager_Autocomplete and Fieldmanager_Options.
	 */
	public $datasource = Null;

	/**
	 * @var array[]
	 * Field name and value on which to display element. Sample:
	 * $element->display_if = array(
	 *	'src' => 'display-if-src-element',
	 *	'value' => 'display-if-src-value'
	 * );
	 */
	public $display_if = array();

	/**
	 * @var boolean
	 * If true, remove any default meta boxes that are overridden by Fieldmanager fields
	 */
	public $remove_default_meta_boxes = False;

	/**
	 * @var string Template
	 * The path to the field template
	 */
	public $template = Null;

	/**
	 * @var array
	 * If $remove_default_meta_boxes is true, this array will be populated with the list of default meta boxes to remove
	 */
	public $meta_boxes_to_remove = array();

	/**
	 * @var mixed Default value
	 * The default value for the field, if unset
	 */
	public $default_value = null;

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
	 * Have we added this field as a meta box yet?
	 */
	private $meta_box_actions_added = False;

	/**
	 * @var boolean
	 * Whether or not this field is present on the attachment edit screen
	 */
	public $is_attachment = false;

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
	public function form_element( $value ) {
		if ( !$this->template ) {
			$tpl_slug = strtolower( str_replace( 'Fieldmanager_', '', get_class( $this ) ));
			$this->template = fieldmanager_get_template( $tpl_slug );
		}
		ob_start();
		include $this->template;
		return ob_get_clean();
	}

	/**
	 * Superclass constructor, just populates options and sanity-checks common elements.
	 * It might also die, but only helpfully, to catch errors in development.
	 * @param string $label title of form field
	 * @param array $options with keys matching vars of the field in use.
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->set_options( $label, $options );
		add_filter( 'fm_submenu_presave_data', 'stripslashes_deep' );
	}

	/**
	 * Build options into properties and throw errors if developers add an unsupported opt.
	 * @param string $label title of form field
	 * @param array $options with keys matching vars of the field in use.
	 * @throws FM_Developer_Exception if an option is set but not defined in this class or the child class.
	 * @throws FM_Developer_Exception if an option is set but not public.
	 */
	public function set_options( $label, $options ) {
		if ( is_array( $label ) ) $options = $label;
		else $options['label'] = $label;

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
	}

	/**
	 * Generates all markup needed for all form elements in this field.
	 * Could be called directly by a plugin or theme.
	 * @param array $values the current values of this element, in a tree structure if the element has children.
	 * @return string HTML for all form elements.
	 */
	public function element_markup( $values = array() ) {
		$values = $this->preload_alter_values( $values );
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
		$fm_wrapper_attrs = array();
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

		// Checks to see if element has display_if data values, and inserts the data attributes if it does
		if ( isset( $this->display_if ) && !empty( $this->display_if ) ) {
			$classes[] = 'display-if';
			$fm_wrapper_attrs['data-display-src'] = $this->display_if['src'];
			$fm_wrapper_attrs['data-display-value'] = $this->display_if['value'];
		}
		$fm_wrapper_attr_string = '';
		foreach ( $fm_wrapper_attrs as $attr => $val ) {
			$fm_wrapper_attr_string .= sprintf( '%s="%s" ', $attr, htmlentities( $val ) );
		}
		$out .= sprintf( '<div class="%s" data-fm-array-position="%d" %s>',
			implode( ' ', $classes ),
			$html_array_position,
			$fm_wrapper_attr_string
		);

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

		// Check if the required attribute is set. If so, add the class.
		if ( $this->required ) {
			$classes[] = 'form-required';
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

		$label = $this->get_element_label( );
		$render_label_after = False;
		// Hide the label if it is empty or if this is a tab since it would duplicate the title from the tab label
		if ( !empty( $this->label ) && !$this->is_tab && $this->one_label_per_item ) {
			if ( $this->limit == 0 && $this->one_label_per_item ) {
				$out .= $this->wrap_with_multi_tools( $label, array( 'fmjs-removable-label' ) );
			} elseif ( !$this->label_after_element ) {
				$out .= $label;
			} else {
				$render_label_after = True;
			}
		}

		if ( Null === $value && Null !== $this->default_value )
			$value = $this->default_value;

		$form_element = $this->form_element( $value );

		if ( $this->limit == 0 && !$this->one_label_per_item ) {
			$out .= $this->wrap_with_multi_tools( $form_element );
		} else {
			$out .= $form_element;
		}

		if ( $render_label_after ) $out .= $label;

		if ( isset( $this->description ) && !empty( $this->description ) ) {
			$out .= sprintf( '<div class="fm-item-description">%s</div>', $this->description );
		}

		$out .= '</div>';
		return $out;
	}

	/**
	 * Alter values before rendering
	 * @param array $values
	 */
	public function preload_alter_values( $values ) {
		return $values;
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
		foreach ( $tree as $level => $branch ) {
			if ( 0 == $level ) {
				$name .= $branch->name;
			} else {
				$name .= '[' . $branch->name . ']';
			}
			if ( $branch->limit != 1 ) {
				$name .= '[' . $branch->get_seq() . ']';
			}
		}
		return $name . $multiple;
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
	 * Relying on the element's ID for anything isn't a great idea since it can be rewritten in JS.
	 * @return string ID for use in a form element.
	 */
	public function get_element_id() {
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
	 * Presaves all elements in what could be a set of them, dispatches to $this->presave()
	 * @input mixed[] $values
	 * @return mixed[] sanitized values
	 */
	public function presave_all( $values, $current_values ) {

		if ( $this->limit == 1 && empty( $this->multiple ) ) {
			$values = $this->presave_alter_values( array( $values ), array( $current_values ) );
			if ( ! empty( $values ) )
				$value = $this->presave( $values[0], $current_values );
			else
				$value = $values;
			if ( !empty( $this->index ) ) $this->save_index( array( $value ), array( $current_values ) );
			return $value;
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

		if ( empty( $values ) ) $values = array();

		if ( isset( $values['proto'] ) ) {
			unset( $values['proto'] );
		}

		# Condense the array to account for middle items removed
		$values = array_values( $values );

		$values = $this->presave_alter_values( $values, $current_values );

		foreach ( $values as $i => $value ) {
			$values[ $i ] = $this->presave( $value, empty( $current_values[ $i ] ) ? array() : $current_values[ $i ] );
			if ( !$this->save_empty && empty( $values[ $i ] ) )
				unset( $values[ $i ] );
		}
		if ( !empty( $this->index ) )
			$this->save_index( $values, $current_values );

		return $values;
	}

	/**
	 * Optionally save fields to a separate postmeta index for easy lookup with WP_Query
	 * Handles internal arrays (e.g. for fieldmanager-options).
	 * Is called multiple times for multi-fields (e.g. limit => 0)
	 * @param array $values
	 * @return void
	 * @todo make this a context method
	 */
	protected function save_index( $values, $current_values ) {
		if ( $this->data_type != 'post' || empty( $this->data_id ) ) return;
		// Must delete current values specifically, then add new ones, to support a scenario where the
		// same field in repeating groups with limit = 1 is going to create more than one entry here, and
		// if we called update_post_meta() we would overwrite the index with each new group.
		foreach ( $current_values as $old_value ) {
			if ( !is_array( $old_value ) ) $old_value = array( $old_value );
			foreach ( $old_value as $value ) {
				if ( empty( $value ) ) $value = 0; // false or null should be saved as 0 to prevent duplicates
				delete_post_meta( $this->data_id, $this->index, $value );
			}
		}
		// add new values
		foreach ( $values as $new_value ) {
			if ( !is_array( $new_value ) ) $new_value = array( $new_value );
			foreach ( $new_value as $value ) {
				if ( empty( $value ) ) $value = 0; // false or null should be saved as 0 to prevent duplicates
				add_post_meta( $this->data_id, $this->index, $value );
			}
		}
	}

	/**
	 * Hook to alter or respond to all the values of a particular element
	 * @param array $values
	 * @return array
	 */
	protected function presave_alter_values( $values, $current_values = array() ) {
		return $values;
	}

	/**
	 * Presave function, which handles sanitization and validation
	 * @param mixed $value If a single field expects to manage an array, it must override presave()
	 * @return sanitized values.
	 */
	public function presave( $value, $current_value = array() ) {
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
		$classes[] = 'fm-label-' . $this->name;
		if ( $this->inline_label ) {
			$this->label_element = 'span';
			$classes[] = 'fm-label-inline';
		}
		if ( $this->label_after_element ) {
			$classes[] = 'fm-label-after';
		}
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
		$classes = array( 'fm-add-another', 'fm-' . $this->name . '-add-another', 'button-secondary' );
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
	 * Add a form on user pages
	 * @param string $title
	 */
	public function add_user_form( $title = '' ) {
		$this->require_base();
		return new Fieldmanager_Context_User( $title, $this );
	}

	/**
	 * Add a form on a frontend page
	 * @see Fieldmanager_Context_Form
	 * @param string $uniqid a unique identifier for this form
	 */
	public function add_page_form( $uniqid ) {
		$this->require_base();
		return new Fieldmanager_Context_Page( $uniqid, $this );
	}

	/**
	 * Add a form on a term add/edit page
	 * @see Fieldmanager_Context_Term
	 * @param string $title
	 * @param string|array $taxonomies The taxonomies on which to display this form
	 * @param boolean $show_on_add Whether or not to show the fields on the add term form
	 * @param boolean $show_on_edit Whether or not to show the fields on the edit term form
	 * @param int $parent Only show this field on child terms of this parent term ID
	 */
	public function add_term_form( $title, $taxonomies, $show_on_add = true, $show_on_edit = true, $parent = '' ) {
		$this->require_base();
		return new Fieldmanager_Context_Term( $title, $taxonomies, $show_on_add, $show_on_edit, $parent, $this );
	}

	/**
	 * Add this field as a metabox to a post type
	 * @see Fieldmanager_Context_Post
	 * @param string $title
	 * @param string|string[] $post_type
	 * @param string $context
	 * @param string $priority
	 */
	public function add_meta_box( $title, $post_types, $context = 'normal', $priority = 'default' ) {
		$this->require_base();
		// Check if any default meta boxes need to be removed for this field
		$this->add_meta_boxes_to_remove( $this->meta_boxes_to_remove );
		return new Fieldmanager_Context_Post( $title, $post_types, $context, $priority, $this );
	}

	/**
	 * Add this field to a post type's quick edit box.
	 * @see Fieldmanager_Context_Quickedit
	 * @param string $title
	 * @param string|string[] $post_type
	 * @param string $column_title
	 * @param callable $column_display_callback
	 */
	public function add_quickedit_box( $title, $post_types, $column_display_callback, $column_title = '' ) {
		$this->require_base();
		return new Fieldmanager_Context_QuickEdit( $title, $post_types, $column_display_callback, $column_title, $this );
	}

	/**
	 * Add this group to an options page
	 * @param string $title
	 */
	public function add_submenu_page( $parent_slug, $page_title, $menu_title = Null, $capability = 'manage_options', $menu_slug = Null ) {
		$this->require_base();
		return new Fieldmanager_Context_Submenu( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $this );
	}

	/**
	 * Activate this group in an already-added submenu page
	 * @param string $title
	 */
	public function activate_submenu_page() {
		$this->require_base();
		$submenus = _fieldmanager_registry( 'submenus' );
		$s = $submenus[ $this->name ];
		$active_submenu = new Fieldmanager_Context_Submenu( $s[0], $s[1], $s[2], $s[3], $s[4], $this, True );
		_fieldmanager_registry( 'active_submenu', $active_submenu );
	}

	private function require_base() {
		if ( !empty( $this->parent ) ) {
			throw new FM_Developer_Exception( __( 'You cannot use this method on a subgroup' ) );
		}
	}

	/**
	 * Die violently. If self::$debug is true, throw an exception.
	 * @param string $debug_message
	 * @return void e.g. return _you_ into a void.
	 */
	public function _unauthorized_access( $debug_message = '' ) {
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
	 * Die violently. If self::$debug is true, throw an exception.
	 * @param string $debug_message
	 * @return void e.g. return _you_ into a void.
	 */
	public function _invalid_definition( $debug_message = '' ) {
		if ( self::$debug ) {
			throw new FM_Exception( $debug_message );
		}
		else {
			wp_die( __( 'Sorry, you\'ve created an invalid field definition. Please check your code and try again.', 'fieldmanager' ) );
		}
	}

	/**
	 * In a multiple element set, return the index of the current element we're rendering.
	 * @return int
	 */
	protected function get_seq() {
		return $this->has_proto() ? 'proto' : $this->seq;
	}

	/**
	 * Are we in the middle of generating a prototype element for repeatable fields?
	 * @return boolean
	 */
	protected function has_proto() {
		if ( $this->is_proto ) return True;
		if ( $this->parent ) return $this->parent->has_proto();
		return False;
	}

	/**
	 * Helper function to add to the list of meta boxes to remove. This will be defined in child classes that require this functionality.
	 * @param array current list of meta boxes to remove
	 * @return void
	 */
	protected function add_meta_boxes_to_remove( &$meta_boxes_to_remove ) {}
}