<?php

/**
 * Abstract base class containing core functionality for Fieldmanager fields.
 *
 * Fields are UI elements that allow a person to interact with data.
 *
 * @package Fieldmanager_Field
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
	 * DEPREATED: How many of these fields to display initially, if $limit != 1.
	 * @deprecated This argument will have no impact. It only remains to avoid
	 *             throwing exceptions in code that used it previously.
	 * @var int
	 */
	public $starting_count = 1;

	/**
	 * How many of these fields to display at a minimum, if $limit != 1. If
	 * $limit == $minimum_count, the "add another" button and the remove tool
	 * will be hidden.
	 * @var int
	 */
	public $minimum_count = 0;

	/**
	 * @var int
	 * How many empty fields to display if $limit != 1 when the total fields in
	 * the loaded data + $extra_elements > $minimum_count.
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
	 * @var boolean
	 * If true, the description will be displayed after the element.
	 */
	public $description_after_element = true;

	/**
	 * @var string|boolean[]
	 * Extra HTML attributes to apply to the form element. Use boolean true to apply a standalone attribute, e.g. 'required' => true
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
	 * Save this field additionally to an index
	 * @var boolean
	 */
	public $index = False;

	/**
	 * Save the fields to their own keys (only works in some contexts). Default
	 * is true.
	 * @var boolean
	 */
	public $serialize_data = true;

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
	* @var string
	* Where the new item should to added ( top/bottom ) of the stack. Used by Add Another button
	* "top|bottom"
	*/
	public $add_more_position = "bottom";

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
	 * @var callable|null
	 * Function that parses an index value and returns an optionally modified value.
	 */
	public $index_filter = null;

	/**
	 * Input type, mainly to support HTML5 input types.
	 * @var string
	 */
	public $input_type = 'text';

	/**
	 * Custom escaping for labels, descriptions, etc. Associative array of
	 * $field => $callable arguments, for example:
	 *
	 *     'escape' => array( 'label' => 'wp_kses_post' )
	 *
	 * @var array
	 */
	public $escape = array();

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

		// A post can only have one parent, so if this saves to post_parent and
		// it's repeatable, we're doing it wrong.
		if ( $this->datasource && ! empty( $this->datasource->save_to_post_parent ) && $this->is_repeatable() ) {
			_doing_it_wrong( 'Fieldmanager_Datasource_Post::$save_to_post_parent', __( 'A post can only have one parent, therefore you cannot store to post_parent in repeatable fields.', 'fieldmanager' ), '1.0.0' );
			$this->datasource->save_to_post_parent = false;
			$this->datasource->only_save_to_post_parent = false;
		}
	}

	/**
	 * Build options into properties and throw errors if developers add an unsupported opt.
	 * @param string $label title of form field
	 * @param array $options with keys matching vars of the field in use.
	 * @throws FM_Developer_Exception if an option is set but not defined in this class or the child class.
	 * @throws FM_Developer_Exception if an option is set but not public.
	 */
	public function set_options( $label, $options ) {
		if ( is_array( $label ) ) {
			$options = $label;
		} else {
			$options['label'] = $label;
		}

		// Get all the public properties for this object
		$properties = call_user_func( 'get_object_vars', $this );

		foreach ( $options as $key => $value ) {
			if ( array_key_exists( $key, $properties ) ) {
				$this->$key = $value;
			} elseif ( self::$debug ) {
				$message = sprintf(
					__( 'You attempted to set a property "%1$s" that is nonexistant or invalid for an instance of "%2$s" named "%3$s".', 'fieldmanager' ),
					$key, get_class( $this ), !empty( $options['name'] ) ? $options['name'] : 'NULL'
				);
				throw new FM_Developer_Exception( esc_html( $message ) );
			}
		}

		// If this is a single field with a limit of 1, serialize_data has no impact
		if ( ! $this->serialize_data && ! $this->is_group() && 1 == $this->limit ) {
			$this->serialize_data = true;
		}

		// Cannot use serialize_data => false with index => true
		if ( ! $this->serialize_data && $this->index ) {
			throw new FM_Developer_Exception( esc_html__( 'You cannot use `"serialize_data" => false` with `"index" => true`', 'fieldmanager' ) );
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
		if ( $this->limit != 1 ) {
			$max = max( $this->minimum_count, count( $values ) + $this->extra_elements );

			// Ensure that we don't display more fields than we can save
			if ( $this->limit > 1 && $max > $this->limit ) {
				$max = $this->limit;
			}
		} else {
			$max = 1;
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
			$out .= sprintf(
				'<div id="%s-tab" class="wp-tabs-panel"%s>',
				esc_attr( $this->get_element_id() ),
				( $this->parent->child_count > 0 ) ? ' style="display: none"' : ''
			);
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
			$fm_wrapper_attr_string .= sprintf( '%s="%s" ', sanitize_key( $attr ), esc_attr( $val ) );
		}
		$out .= sprintf( '<div class="%s" data-fm-array-position="%d" %s>',
			esc_attr( implode( ' ', $classes ) ),
			absint( $html_array_position ),
			$fm_wrapper_attr_string
		);

		// After starting the field, apply a filter to allow other plugins to append functionality
		$out = apply_filters( 'fm_element_markup_start', $out, $this, $values );
		if ( ( 0 == $this->limit || ( $this->limit > 1 && $this->limit > $this->minimum_count ) ) && "top" == $this->add_more_position ) {
			$out .= $this->add_another();
		}

		if ( 1 != $this->limit ) {
			$out .= $this->single_element_markup( null, true );
		}
		for ( $i = 0; $i < $max; $i++ ) {
			$this->seq = $i;
			if ( $this->limit == 1 ) {
				$value = $values;
			} else {
				$value = isset( $values[ $i ] ) ? $values[ $i ] : Null;
			}
			$out .= $this->single_element_markup( $value );
		}
		if ( ( 0 == $this->limit || ( $this->limit > 1 && $this->limit > $this->minimum_count ) ) && "bottom" == $this->add_more_position ) {
			$out .= $this->add_another();
		}

		// Before closing the field, apply a filter to allow other plugins to append functionality
		$out = apply_filters( 'fm_element_markup_end', $out, $this, $values );

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
		if ( $is_proto ) {
			$this->is_proto = true;
		}
		$out = '';
		$classes = array( 'fm-item', 'fm-' . $this->name );

		self::$global_seq++;

		// Drop the fm-group class to hide inner box display if no label is set
		if ( !( $this->is_group() && ( !isset( $this->label ) || empty( $this->label ) ) ) ) {
			$classes[] = 'fm-' . $this->field_class;
		}

		// Check if the required attribute is set. If so, add the class.
		if ( $this->required ) {
			$classes[] = 'form-required';
		}

		if ( $is_proto ) {
			$classes[] = 'fmjs-proto';
		}

		if ( $this->is_group() && 'vertical' === $this->tabbed ) {
			$classes[] = 'fm-tabbed-vertical';
		}

		$classes = apply_filters( 'fm_element_classes', $classes, $this->name, $this );

		$out .= sprintf( '<div class="%s">', esc_attr( implode( ' ', $classes ) ) );

		$label = $this->get_element_label( );
		$render_label_after = False;
		// Hide the label if it is empty or if this is a tab since it would duplicate the title from the tab label
		if ( !empty( $this->label ) && !$this->is_tab && $this->one_label_per_item ) {
			if ( $this->limit != 1 ) {
				$out .= $this->wrap_with_multi_tools( $label, array( 'fmjs-removable-label' ) );
			} elseif ( !$this->label_after_element ) {
				$out .= $label;
			} else {
				$render_label_after = True;
			}
		}

		if ( isset( $this->description ) && !empty( $this->description ) && ! $this->description_after_element ) {
			$out .= sprintf( '<div class="fm-item-description">%s</div>', $this->escape( 'description' ) );
		}

		if ( Null === $value && Null !== $this->default_value )
			$value = $this->default_value;

		$form_element = $this->form_element( $value );

		if ( $this->limit != 1 && ( ! $this->one_label_per_item || empty( $this->label ) ) ) {
			$out .= $this->wrap_with_multi_tools( $form_element );
		} else {
			$out .= $form_element;
		}

		if ( $render_label_after ) $out .= $label;

		if ( isset( $this->description ) && !empty( $this->description ) && $this->description_after_element ) {
			$out .= sprintf( '<div class="fm-item-description">%s</div>', $this->escape( 'description' ) );
		}

		$out .= '</div>';

		if ( $is_proto ) {
			$this->is_proto = false;
		}
		return $out;
	}

	/**
	 * Alter values before rendering
	 * @param array $values
	 */
	public function preload_alter_values( $values ) {
		return apply_filters( 'fm_preload_alter_values', $values, $this );
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

		if ( $this->limit == 0 || $this->limit > $this->minimum_count ) {
			$out .= $this->get_remove_handle();
		}

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
	 * Get the storage key for the form element.
	 *
	 * @return string
	 */
	public function get_element_key() {
		$el = $this;
		$key = $el->name;
		while ( $el = $el->parent ) {
			if ( $el->add_to_prefix ) {
				$key = "{$el->name}_{$key}";
			}
		}
		return $key;
	}

	/**
	 * Is this element repeatable or does it have a repeatable ancestor?
	 *
	 * @return boolean True if yes, false if no.
	 */
	public function is_repeatable() {
		if ( 1 != $this->limit ) {
			return true;
		} elseif ( $this->parent ) {
			return $this->parent->is_repeatable();
		}
		return false;
	}

	/**
	 * Is the current field a group?
	 *
	 * @return boolean True if yes, false if no.
	 */
	public function is_group() {
		return $this instanceof Fieldmanager_Group;
	}

	/**
	 * Presaves all elements in what could be a set of them, dispatches to $this->presave()
	 * @input mixed[] $values
	 * @return mixed[] sanitized values
	 */
	public function presave_all( $values, $current_values ) {
		if ( $this->limit == 1 && empty( $this->multiple ) ) {
			$values = $this->presave_alter_values( array( $values ), array( $current_values ) );
			if ( ! empty( $values ) ) {
				$value = $this->presave( $values[0], $current_values );
			} else {
				$value = $values;
			}
			if ( !empty( $this->index ) ) {
				$this->save_index( array( $value ), array( $current_values ) );
			}
			return $value;
		}

		// If $this->limit != 1, and $values is not an array, that'd just be wrong, and possibly an attack, so...
		if ( $this->limit != 1 && !is_array( $values ) ) {

			// EXCEPT maybe this is a request to remove indices
			if ( ! empty( $this->index ) && null === $values && ! empty( $current_values ) && is_array( $current_values ) ) {
				$this->save_index( null, $current_values );
				return;
			}

			// OR doing cron, where we should just do nothing if there are no values to process.
			// OR we've now accumulated some cases where a null value instead of an empty array is an acceptable case to
			// just bail out instead of throwing an error. If it WAS an attack, bailing should prevent damage.
			if ( null === $values || ( defined( 'DOING_CRON' ) && DOING_CRON && empty( $values ) ) ) {
				return;
			}

			$this->_unauthorized_access( sprintf( __( '$values should be an array because $limit is %d', 'fieldmanager' ), $this->limit ) );
		}

		if ( empty( $values ) ) {
			$values = array();
		}

		// Remove the proto
		if ( isset( $values['proto'] ) ) {
			unset( $values['proto'] );
		}

		// If $this->limit is not 0 or 1, and $values has more than $limit, that could also be an attack...
		if ( $this->limit > 1 && count( $values ) > $this->limit ) {
			$this->_unauthorized_access(
				sprintf( __( 'submitted %1$d values against a limit of %2$d', 'fieldmanager' ), count( $values ), $this->limit )
			);
		}

		// Check for non-numeric keys
		$keys = array_keys( $values );
		foreach ( $keys as $key ) {
			if ( ! is_numeric( $key ) ) {
				throw new FM_Exception( esc_html__( 'Use of a non-numeric key suggests that something is wrong with this group.', 'fieldmanager' ) );
			}
		}

		// Condense the array to account for middle items removed
		$values = array_values( $values );

		$values = $this->presave_alter_values( $values, $current_values );

		// If this update results in fewer children, trigger presave on empty children to make up the difference.
		if ( ! empty( $current_values ) && is_array( $current_values ) ) {
			foreach ( array_diff( array_keys( $current_values ), array_keys( $values ) ) as $i ) {
				$values[ $i ] = null;
			}
		}

		foreach ( $values as $i => $value ) {
			$values[ $i ] = $this->presave( $value, empty( $current_values[ $i ] ) ? array() : $current_values[ $i ] );
		}

		if ( ! $this->save_empty ) {
			// reindex the array after removing empty values
			$values = array_values( array_filter( $values ) );
		}

		if ( ! empty( $this->index ) ) {
			$this->save_index( $values, $current_values );
		}

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
		if ( ! empty( $current_values ) && is_array( $current_values ) ) {
			foreach ( $current_values as $old_value ) {
				if ( !is_array( $old_value ) ) $old_value = array( $old_value );
				foreach ( $old_value as $value ) {
					$value = $this->process_index_value( $value );
					if ( empty( $value ) ) $value = 0; // false or null should be saved as 0 to prevent duplicates
					delete_post_meta( $this->data_id, $this->index, $value );
				}
			}
		}
		// add new values
		if ( ! empty( $values ) && is_array( $values ) ) {
			foreach ( $values as $new_value ) {
				if ( !is_array( $new_value ) ) $new_value = array( $new_value );
				foreach ( $new_value as $value ) {
					$value = $this->process_index_value( $value );
					if ( isset( $value ) ) {
						if ( empty( $value ) ) $value = 0; // false or null should be saved as 0 to prevent duplicates
						add_post_meta( $this->data_id, $this->index, $value );
					}
				}
			}
		}
	}

	/**
	 * Hook to alter handling of an individual index value, which may make sense to change per field type.
	 * @param mixed $value
	 * @return mixed
	 */
	protected function process_index_value( $value ) {
		if ( is_callable( $this->index_filter ) ) {
			$value = call_user_func( $this->index_filter, $value );
		}

		return apply_filters( 'fm_process_index_value', $value, $this );
	}

	/**
	 * Hook to alter or respond to all the values of a particular element
	 * @param array $values
	 * @return array
	 */
	protected function presave_alter_values( $values, $current_values = array() ) {
		return apply_filters( 'fm_presave_alter_values', $values, $this, $current_values );
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
			$this->_unauthorized_access( __( 'presave() in the base class should not get arrays, but did.', 'fieldmanager' ) );
		}
		foreach ( $this->validate as $func ) {
			if ( !call_user_func( $func, $value ) ) {
				$this->_failed_validation( sprintf(
					__( 'Input "%1$s" is not valid for field "%2$s" ', 'fieldmanager' ),
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
			if ( $val === true ){
				$attr_str[] = sanitize_key( $attr );
			} else{
				$attr_str[] = sprintf( '%s="%s"', sanitize_key( $attr ), esc_attr( $val ) );
			}
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
			sanitize_key( $this->label_element ),
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $this->get_element_id( $this->get_seq() ) ),
			$this->escape( 'label' ),
			sanitize_key( $this->label_element )
		);
	}

	/**
	 * Generates HTML for the "Add Another" button.
	 * @return string button HTML.
	 */
	public function add_another() {
		$classes = array( 'fm-add-another', 'fm-' . $this->name . '-add-another', 'button-secondary' );
		if ( empty( $this->add_more_label ) ) {
			$this->add_more_label = $this->is_group() ? __( 'Add group', 'fieldmanager' ) : __( 'Add field', 'fieldmanager' );
		}

		$out = '<div class="fm-add-another-wrapper">';
		$out .= sprintf(
			'<input type="button" class="%s" value="%s" name="%s" data-related-element="%s" data-add-more-position="%s" data-limit="%d" />',
			esc_attr( implode( ' ', $classes ) ),
			esc_attr( $this->add_more_label ),
			esc_attr( 'fm_add_another_' . $this->name ),
			esc_attr( $this->name ),
			esc_attr( $this->add_more_position ),
			intval( $this->limit )
		);
		$out .= '</div>';
		return $out;
	}

	/**
	 * Return HTML for the sort handle (multi-tools); a separate function to override
	 * @return string
	 */
	public function get_sort_handle() {
		return sprintf( '<div class="fmjs-drag fmjs-drag-icon"><span class="screen-reader-text">%s</span></div>', esc_html__( 'Move', 'fieldmanager' ) );
	}

	/**
	 * Return HTML for the remove handle (multi-tools); a separate function to override
	 * @return string
	 */
	public function get_remove_handle() {
		return sprintf( '<a href="#" class="fmjs-remove" title="%1$s"><span class="screen-reader-text">%1$s</span></a>', esc_attr__( 'Remove', 'fieldmanager' ) );
	}

	/**
	 * Return HTML for the collapse handle (multi-tools); a separate function to override
	 * @return string
	 */
	public function get_collapse_handle() {
		return '<span class="toggle-indicator" aria-hidden="true"></span>';
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
	 *
	 * @deprecated 1.0.0-beta.3 Replaced by {@see Fieldmanager_Field::add_term_meta_box()}.
	 *
	 * @see Fieldmanager_Context_Term
	 *
	 * @param string $title
	 * @param string|array $taxonomies The taxonomies on which to display this form
	 * @param boolean $show_on_add Whether or not to show the fields on the add term form
	 * @param boolean $show_on_edit Whether or not to show the fields on the edit term form
	 * @param int $parent Only show this field on child terms of this parent term ID
	 */
	public function add_term_form( $title, $taxonomies, $show_on_add = true, $show_on_edit = true, $parent = '' ) {
		$this->require_base();
		return new Fieldmanager_Context_Term( array(
			'title'        => $title,
			'taxonomies'   => $taxonomies,
			'show_on_add'  => $show_on_add,
			'show_on_edit' => $show_on_edit,
			'parent'       => $parent,
			// Use the deprecated FM Term Meta instead of core's term meta
			'use_fm_meta'  => true,
			'field'        => $this,
		) );
	}

	/**
	 * Add fields to the term add/edit page
	 *
	 * @see Fieldmanager_Context_Term
	 *
	 * @param string $title
	 * @param string|array $taxonomies The taxonomies on which to display this form
	 * @param boolean $show_on_add Whether or not to show the fields on the add term form
	 * @param boolean $show_on_edit Whether or not to show the fields on the edit term form
	 * @param int $parent Only show this field on child terms of this parent term ID
	 */
	public function add_term_meta_box( $title, $taxonomies, $show_on_add = true, $show_on_edit = true, $parent = '' ) {
		// Bail if term meta table is not installed.
		if ( get_option( 'db_version' ) < 34370 ) {
			_doing_it_wrong( __METHOD__, esc_html__( 'This method requires WordPress 4.4 or above', 'fieldmanager' ), 'Fieldmanager-1.0.0-beta.3' );
			return false;
		}

		$this->require_base();
		return new Fieldmanager_Context_Term( array(
			'title'        => $title,
			'taxonomies'   => $taxonomies,
			'show_on_add'  => $show_on_add,
			'show_on_edit' => $show_on_edit,
			'parent'       => $parent,
			'use_fm_meta'  => false,
			'field'        => $this,
		) );
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
		if ( in_array( 'attachment', (array) $post_types ) ) {
			$this->is_attachment = true;
		}
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
			throw new FM_Developer_Exception( esc_html__( 'You cannot use this method on a subgroup', 'fieldmanager' ) );
		}
	}

	/**
	 * Die violently. If self::$debug is true, throw an exception.
	 * @param string $debug_message
	 * @return void e.g. return _you_ into a void.
	 */
	public function _unauthorized_access( $debug_message = '' ) {
		if ( self::$debug ) {
			throw new FM_Exception( esc_html( $debug_message ) );
		}
		else {
			wp_die( esc_html__( "Sorry, you're not supposed to do that...", 'fieldmanager' ) );
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
			wp_die( esc_html(
				$debug_message . "\n\n" .
				__( "You may be able to use your browser's back button to resolve this error.", 'fieldmanager' )
			) );
		}
	}

	/**
	 * Die violently. If self::$debug is true, throw an exception.
	 * @param string $debug_message
	 * @return void e.g. return _you_ into a void.
	 */
	public function _invalid_definition( $debug_message = '' ) {
		if ( self::$debug ) {
			throw new FM_Exception( esc_html( $debug_message ) );
		} else {
			wp_die( esc_html__( "Sorry, you've created an invalid field definition. Please check your code and try again.", 'fieldmanager' ) );
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

	/**
	 * Escape a field based on the function in the escape argument.
	 *
	 * @param  string $field   The field to escape.
	 * @param  string $default The default function to use to escape the field.
	 *                         Optional. Defaults to `esc_html()`
	 * @return string          The escaped field.
	 */
	public function escape( $field, $default = 'esc_html' ) {
		if ( isset( $this->escape[ $field ] ) && is_callable( $this->escape[ $field ] ) ) {
			return call_user_func( $this->escape[ $field ], $this->$field );
		} else {
			return call_user_func( $default, $this->$field );
		}
	}
}
