<?php

/**
 * Define a groups of fields.
 *
 * Groups shouldn't just be thought of as a top-level collection of fields (like
 * a meta box). Groups can be infinitely nested, they can be used to create
 * tabbed interfaces, and so on. Groups submit data as nested arrays.
 *
 * @package Fieldmanager_Field
 */
class Fieldmanager_Group extends Fieldmanager_Field {

	/**
	 * @var Fieldmanager_Field[]
	 * Children elements of this group. Not much point in creating an empty group.
	 */
	public $children = array();

	/**
	 * @var string
	 * Override field class
	 */
	public $field_class = 'group';

	/**
	 * @var string
	 * Override label element
	 */
	public $label_element = 'h4';

	/**
	 * @var boolean
	 * If true, this group can be collapsed by clicking its header.
	 */
	public $collapsible = FALSE;

	/**
	 * @var boolean
	 * If true, this group is collapsed by default.
	 */
	public $collapsed = FALSE;

	/**
	 * Use tabbed groups. Currently supports "horizontal" or "vertical". Default
	 * is false, which means that the group will not be tabbed.
	 *
	 * @var boolean|string
	 */
	public $tabbed = false;

	/**
	 * @var int
	 * How many tabs, maximum?
	 */
	public $tab_limit = 0;

	/**
	 * Persist the active tab on the group between sessions
	 *
	 * @var boolean
	 */
	public $persist_active_tab = true;

	/**
	 * @var array
	 * Label macro is a more convenient shortcut to label_format and label_token. The first element
	 * of the two-element array is the title with a placeholder (%s), and the second element is
	 * simply the name of the child element to pull from, e.g.:
	 *
	 * array( 'Section: %s', 'section_title' )
	 */
	public $label_macro = Null;

	/**
	 * @var string
	 * If specified, $label_format combined with $label_token will override $label, but only if
	 * $(label).find(label_token).val() is not null.
	 */
	public $label_format = Null;

	/**
	 * @var string
	 * CSS selector to an element to get the token for the label format
	 */
	public $label_token = Null;

	/**
	 * @var callable|null
	 * Function that tells whether the group is empty or not. Gets an array of form values.
	 */
	public $group_is_empty = Null;

	/**
	 * Should the group name be included in the meta key prefix for separate
	 * fields? Default is true.
	 *
	 * If false, Fieldmanager will not check for collisions among the meta keys
	 * created for this group's fields and other registered fields.
	 *
	 * @var boolean
	 */
	public $add_to_prefix = true;

	/**
	 * @var boolean
	 * Iterator value for how many children we have rendered.
	 */
	protected $child_count = 0;

	/**
	 * Flag that this field has some descendant with $serialize_data => false.
	 *
	 * This field is set based on its descendants, but you can deliberately set
	 * it yourself if your situation is one where this cannot be determined
	 * automatically (for instance, where descendants are added after the group
	 * has been constructed).
	 *
	 * @var boolean
	 */
	public $has_unserialized_descendants = false;

	/**
	 * Constructor; add CSS if we're looking at a tabbed view
	 */
	public function __construct( $label = '', $options = array() ) {

		parent::__construct( $label, $options );

		// Repeatable groups cannot used unserialized data
		$is_repeatable = ( 1 != $this->limit );
		if ( ! $this->serialize_data && $is_repeatable ) {
			throw new FM_Developer_Exception( esc_html__( 'You cannot use `"serialize_data" => false` with repeating groups', 'fieldmanager' ) );
		}

		// If this is collapsed, collapsibility is implied
		if ( $this->collapsed ) {
			$this->collapsible = True;
		}

		// Convenient naming of child elements via their keys
		foreach ( $this->children as $name => $element ) {
			// if the array key is not an int, and the name attr is set, and they don't match, we got a problem.
			if ( $element->name && !is_int( $name ) && $element->name != $name ) {
				throw new FM_Developer_Exception( esc_html__( 'Group child name conflict: ', 'fieldmanager' ) . $name . ' / ' . $element->name );
			} elseif ( ! $element->name ) {
				$element->name = $name;
			}

			// Catch errors when using serialize_data => false and index => true
			if ( ! $this->serialize_data && $element->index ) {
				throw new FM_Developer_Exception( esc_html__( 'You cannot use `serialize_data => false` with `index => true`', 'fieldmanager' ) );
			}

			// A post can only have one parent, so if this saves to post_parent and
			// it's repeatable, we're doing it wrong.
			if ( $element->datasource && ! empty( $element->datasource->save_to_post_parent ) && $this->is_repeatable() ) {
				_doing_it_wrong( 'Fieldmanager_Datasource_Post::$save_to_post_parent', __( 'A post can only have one parent, therefore you cannot store to post_parent in repeatable fields.', 'fieldmanager' ), '1.0.0' );
				$element->datasource->save_to_post_parent = false;
				$element->datasource->only_save_to_post_parent = false;
			}

			// Flag this group as having unserialized descendants to check invalid use of repeatables
			if ( ! $this->has_unserialized_descendants && ( ! $element->serialize_data || ( $element->is_group() && $element->has_unserialized_descendants ) ) ) {
				$this->has_unserialized_descendants = true;
			}

			// Form a child-parent bond
			$element->parent = $this;
		}

		// Check for invalid usage of repeatables and serialize_data
		if ( $is_repeatable && $this->has_unserialized_descendants ) {
			throw new FM_Developer_Exception( esc_html__( 'You cannot use `serialize_data => false` with repeating groups', 'fieldmanager' ) );
		}

		// Add the tab JS and CSS if it is needed
		if ( $this->tabbed ) {
			fm_add_script( 'jquery-hoverintent', 'js/jquery.hoverIntent.js', array( 'jquery' ), '1.8.1' );
			fm_add_script( 'fm_group_tabs_js', 'js/fieldmanager-group-tabs.js', array( 'jquery', 'jquery-hoverintent' ), '1.0.4' );
			fm_add_style( 'fm_group_tabs_css', 'css/fieldmanager-group-tabs.css', array(), '1.0.5' );
		}
	}

	/**
	 * Render the element, iterating over children and calling their form_element() functions.
	 * @param mixed $value
	 */
	public function form_element( $value = NULL ) {
		$out = '';
		$tab_group = '';
		$tab_group_submenu = '';

		// We do not need the wrapper class for extra padding if no label is set for the group
		if ( isset( $this->label ) && !empty( $this->label ) ) {
			$out .= '<div class="fm-group-inner">';
		}

		// If the display output for this group is set to tabs, build the tab group for navigation
		if ( $this->tabbed ) {
			$tab_group = sprintf( '<ul class="fm-tab-bar wp-tab-bar %s" id="%s-tabs">',
				$this->persist_active_tab ? 'fm-persist-active-tab' : '',
				esc_attr( $this->get_element_id() ) );
		}

		// Produce HTML for each of the children
		foreach ( $this->children as $element ) {

			$element->parent = $this;

			// If the display output for this group is set to tabs, add a tab for this child
			if ( $this->tabbed ) {

				// Set default classes to display the first tab content and hide others
				$tab_classes = array( 'fm-tab' );
			    $tab_classes[] = ( $this->child_count == 0 ) ? "wp-tab-active" : "hide-if-no-js";

				// Generate output for the tab. Depends on whether or not there is a tab limit in place.
				if ( $this->tab_limit == 0 || $this->child_count < $this->tab_limit ) {
					$tab_group .=  sprintf( '<li class="%s"><a href="#%s-tab">%s</a></li>',
						esc_attr( implode( " ", $tab_classes ) ),
						esc_attr( $element->get_element_id() ),
						$element->escape( 'label' )
					 );
				} else if ( $this->tab_limit != 0 && $this->child_count >= $this->tab_limit ) {
					$submenu_item_classes = array( 'fm-submenu-item' );
					$submenu_item_link_class = "";

					// Create the More tab when first hitting the tab limit
					if ( $this->child_count == $this->tab_limit ) {
						// Create the tab
						$tab_group_submenu .=  sprintf( '<li class="fm-tab fm-has-submenu"><a href="#%s-tab">%s</a>',
							esc_attr( $element->get_element_id() ),
							esc_html__( 'More...', 'fieldmanager' )
						 );

						 // Start the submenu
						 $tab_group_submenu .= sprintf(
						 	'<div class="fm-submenu" id="%s-submenu"><div class="fm-submenu-wrap fm-submenu-wrap"><ul>',
						 	esc_attr( $this->get_element_id() )
						 );

						 // Make sure the first submenu item is designated
						 $submenu_item_classes[] = 'fm-first-item';
						 $submenu_item_link_class = 'class="fm-first-item"';
					}

					// Add this element to the More menu
					$tab_group_submenu .=  sprintf( '<li class="%s"><a href="#%s-tab" %s>%s</a></li>',
						esc_attr( implode( ' ', $submenu_item_classes ) ),
						esc_attr( $element->get_element_id() ),
						$submenu_item_link_class,
						$element->escape( 'label' )
					);
				}

				// Ensure the child is aware it is tab content
				$element->is_tab = TRUE;
			}

			// Get markup for the child element
			$child_value = isset( $value[ $element->name ] ) ? $value[ $element->name ] : null;

			// propagate editor state down the chain
			if ( $this->data_type ) $element->data_type = $this->data_type;
			if ( $this->data_id ) $element->data_id = $this->data_id;

			$out .= $element->element_markup( $child_value );

			$this->child_count++;

		}

		// We do not need the wrapper class for extra padding if no label is set for the group
		if ( isset( $this->label ) && !empty( $this->label ) ) $out .= '</div>';

		// If the display output for this group is set to tabs, build the tab group for navigation
		if ( $this->tab_limit != 0 && $this->child_count >= $this->tab_limit ) $tab_group_submenu .= '</ul></div></div></li>';
		if ( $this->tabbed ) $tab_group .= $tab_group_submenu . '</ul>';


		// Return the complete HTML
		return $tab_group . $out;
	}

	/**
	 * Add a child element to this group.
	 * @param Fieldmanager_Field $child
	 * @return void
	 */
	public function add_child( Fieldmanager_Field $child ) {
		$child->parent = $this;
		$this->children[ $child->name ] = $child;

		// Catch errors when using serialize_data => false and index-> true
		if ( ! $this->serialize_data && $child->index ) {
			throw new FM_Developer_Exception( esc_html__( 'You cannot use `serialize_data => false` with `index => true`', 'fieldmanager' ) );
		}
	}

	/**
	 * Presave override for groups which dispatches to child presave_all methods.
	 * @input mixed[] values
	 * @return mixed[] values
	 */
	public function presave( $values, $current_values = array() ) {
		// @SECURITY@ First, make sure all the values we're given are legal.
		if( isset( $values ) && !empty( $values ) ) {
			foreach ( array_keys( $values ) as $key ) {
				if ( !isset( $this->children[$key] ) ) {
					// If we're here, it means that the input, generally $_POST, contains a value that doesn't belong,
					// and thus one which we cannot sanitize and must not save. This might be an attack.
					$this->_unauthorized_access( sprintf( __( 'Found "%1$s" in data but not in children', 'fieldmanager' ), $key ) );
				}
			}
		}

		// Then, dispatch them for sanitization to the children.
		$skip_save_all = true;
		foreach ( $this->children as $k => $element ) {
			$element->data_id = $this->data_id;
			$element->data_type = $this->data_type;
			if ( ! isset( $values[ $element->name ] ) ) {
				$values[ $element->name ] = NULL;
			}

			if ( $element->skip_save ) {
				unset( $values[ $element->name ] );
				continue;
			}

			$child_value = empty( $values[ $element->name ] ) ? Null : $values[ $element->name ];
			$current_child_value = ! isset( $current_values[ $element->name ] ) ? array() : $current_values[ $element->name ];
			$values[ $element->name ] = $element->presave_all( $values[ $element->name ], $current_child_value );
			if ( ! $this->save_empty && $this->limit != 1 ) {
				if ( is_array( $values[ $element->name ] ) && empty( $values[ $element->name ] ) ) unset( $values[ $element->name ] );
				elseif ( empty( $values[ $element->name ] ) ) unset( $values[ $element->name ] );
			}

			if ( ! empty( $element->datasource->only_save_to_taxonomy ) || ! empty( $element->datasource->only_save_to_post_parent ) ) {
				unset( $values[ $element->name ] );
				continue;
			}

			$skip_save_all = false;
		}

		if ( $skip_save_all ) {
			$this->skip_save = true;
		}

		if ( is_callable( $this->group_is_empty ) ) {
			if ( call_user_func( $this->group_is_empty, $values ) ) {
				$values = array();
			}
		}

		return $values;
	}

	/**
	 * Get an HTML label for this element.
	 * @param array $classes extra CSS classes.
	 * @return string HTML label.
	 */
	public function get_element_label( $classes = array() ) {
		$classes[] = 'fm-label';
		$classes[] = 'fm-label-' . $this->name;

		$wrapper_classes = array( 'fm-group-label-wrapper' );

		if ( $this->sortable ) {
			$wrapper_classes[] = 'fmjs-drag';
			$wrapper_classes[] = 'fmjs-drag-header';
		}

		$collapse_handle = '';
		if ( $this->collapsible ) {
			$wrapper_classes[] = 'fmjs-collapsible-handle';
			$collapse_handle = $this->get_collapse_handle();
		}

		$extra_attrs = '';
		if ( $this->label_macro ) {
			$this->label_format = $this->label_macro[0];
			$this->label_token = sprintf( '.fm-%s .fm-element:input', $this->label_macro[1] );
		}

		if ( $this->label_format && $this->label_token ) {
			$extra_attrs = sprintf(
				'data-label-format="%1$s" data-label-token="%2$s"',
				esc_attr( $this->label_format ),
				esc_attr( $this->label_token )
			);
			$classes[] = 'fm-label-with-macro';
		}

		$remove = '';
		if ( $this->one_label_per_item && ( $this->limit == 0 || ( $this->limit > 1 && $this->limit > $this->minimum_count ) ) ) {
			$remove = $this->get_remove_handle();
		}

		return sprintf(
			'<div class="%1$s"><%2$s class="%3$s"%4$s>%5$s</%2$s>%6$s%7$s</div>',
			esc_attr( implode( ' ', $wrapper_classes ) ),
			$this->label_element,
			esc_attr( implode( ' ', $classes ) ),
			$extra_attrs,
			$this->escape( 'label' ),
			$collapse_handle,
			$remove // get_remove_handle() is sanitized html
		);
	}

	/**
	 * Groups have their own drag and remove tools in the label.
	 * @param string $html
	 * @return string
	 */
	public function wrap_with_multi_tools( $html, $classes = array() ) {
		if ( empty( $this->label ) || ! $this->one_label_per_item ) {
			return parent::wrap_with_multi_tools( $html, $classes );
		}
		return $html;
	}

	/**
	 * Maybe add the collapsible class for groups
	 * @return array
	 */
	public function get_extra_element_classes() {
		$classes = array();
		if ( $this->collapsible ) {
			$classes[] = 'fm-collapsible';
		}
		if ( $this->collapsed ) {
			$classes[] = 'fm-collapsed';
		}
		return $classes;
	}

	/**
	 * Helper function to get the list of default meta boxes to remove.
	 * For Fieldmanager_Group, iterate over all children to see if they have meta boxes to remove.
	 * If $remove_default_meta_boxes is true for this group, set all children to also remove any default meta boxes if applicable.
	 * @param $meta_boxes_to_remove the array of meta boxes to remove
	 * @return array list of meta boxes to remove
	 */
	protected function add_meta_boxes_to_remove( &$meta_boxes_to_remove ) {
		foreach( $this->children as $child ) {
			// If remove default meta boxes was true for the group, set it for all children
			if ( $this->remove_default_meta_boxes ) {
				$child->remove_default_meta_boxes = true;
			}

			$child->add_meta_boxes_to_remove( $meta_boxes_to_remove );
		}
	}

}
