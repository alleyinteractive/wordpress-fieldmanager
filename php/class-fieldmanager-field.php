<?php
/**
 * @package Fieldmanager
 */

/**
 * Base class containing core functionality for Fieldmanager
 */
abstract class Fieldmanager_Field {

	public $limit = 1;
	public $starting_count = 1;
	public $extra_elements = 1;
	public $add_more_label = '';
	public $name = '';
	public $label = '';
	public $description = '';
	public $attributes = array();
	public $field_class = 'element';
	public $one_label_per_item = True;
	public $sortable = False;
	public $label_element = 'div';
	public $tree = array();

	public $sanitize = 'sanitize_text_field';
	public $validate = array();

	public $data_type = NULL;
	public $data_id = NULL;

	protected $seq = 0;
	protected $parent = NULL;
	protected $is_valid = FALSE;
	protected $is_tab = FALSE;

	/**
	 * Generate HTML for the form element itself. Generally should be just one tag, no wrappers.
	 * @param mixed $value the value of the element.
	 * @param array $tree the array path to the element.
	 * @return string HTML for the element.
	 */
	public abstract function form_element( $value );

	/**
	 * Superclass constructor, just populates options.
	 * @param array $options with keys matching vars of the field in use.
	 */
	public function __construct( $options = array() ) {
		foreach ( $options as $k => $v ) {
			$this->$k = $v;
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
			$max = max( $this->starting_count, count( $values ) + $this->extra_elements );
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

		$level = count( $this->get_form_tree() ) - 1;
		$out .= sprintf( '<div class="%s" data-fm-level="%d">', implode( ' ', $classes ), $level );
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
		
		// Drop the fm-group class to hide inner box display if no label is set
		if ( !( $this->field_class == 'group' && ( !isset( $this->label ) || empty( $this->label ) ) ) ) {
			$classes[] = 'fm-' . $this->field_class;
		}
		
		if ( $this->get_seq() == 0 && $this->limit == 0 ) {
			// Generate a prototype element for DOM magic on the frontend.
			if ( $is_proto ) {
				$classes[] = 'fmjs-proto';
			} else {
				$out .= $this->single_element_markup( Null, True );
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
	 * Recursively build form tree
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
		return 'fm-edit-' . $this->name . '-' . $this->get_seq();
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
		if ( !is_array( $values ) ) { // default of get_post_meta is empty array.
			$values = json_decode( $values, TRUE );
		}
		echo $this->element_markup( $values );
	}

	public function validate_all( $value ) {
		// iterate over validators, check nonce field.
	}

	public function presave( $value ) {
		return call_user_func( $this->sanitize, $value );
	}

	public function presave_all( $values ) {
		if ( $this->limit = 1 ) {
			return $this->presave( $values );
		}
		foreach ( $values as $i => $value ) {
			$values[$i] = $this->presave( $value );
		}
		return $values;
	}

	public function save_to_post_meta( $post_id, $data ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		$this->data_id = $post_id;
		$this->data_type = 'post';
		$data = $this->presave_all( $data );
		update_post_meta( $post_id, $this->name, json_encode( $data ) );
	}

	/**
	 * Generates an HTML attribute string based on the value of $this->attributes.
	 * @see Fieldmanager_Field::$attributes
	 * @return string attributes ready to insert into an HTML tag.
	 */
	public function get_element_attributes() {
		$attr_str = array();
		foreach ( $this->attributes as $attr => $val ) {
			$attr_str[] = sprintf( '%s="%s"', $attr, $val );
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

	public function get_sort_handle() {
		return '<div class="fmjs-drag fmjs-drag-icon">Move</div>';
	}

	public function get_remove_handle() {
		return '<a href="#" class="fmjs-remove">Remove</a>';
	}

	public function get_collapse_handle() {
		return '<div class="handlediv" title="Click to toggle"><br /></div>';
	}

	public function get_extra_element_classes() {
		return array();
	}

	protected function get_multiple_count( $values ) {
		if ( $this->limit == 0 ) {
			return max( $this->starting_count, count( $values ) + $this->extra_elements );
		}
		else {
			return $this->limit;
		}
	}

	private function get_seq() {
		return $this->seq;
	}
}