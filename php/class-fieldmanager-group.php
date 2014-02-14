<?php
/**
 * @package Fieldmanager
 */

/**
 * Fieldmanager Group; allows associating multiple fields together
 * and required as the base element.
 * @package Fieldmanager
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
	 * @var boolean
	 * If true, render children in tabs.
	 */
	public $tabbed = FALSE;

	/**
	 * @var int
	 * How many tabs, maximum?
	 */
	public $tab_limit = 0;

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
	 * @var boolean
	 * Iterator value for how many children we have rendered.
	 */
	protected $child_count = 0;

	/**
	 * Constructor; add CSS if we're looking at a tabbed view
	 */
	public function __construct( $label = '', $options = array() ) {

		parent::__construct( $label, $options );

		if ( $this->collapsed ) $this->collapsible = True;

		// Convenient naming of child elements via their keys
		foreach ( $this->children as $name => $element ) {
			// if the array key is not an int, and the name attr is set, and they don't match, we got a problem.
			if ( $element->name && !is_int( $name ) && $element->name != $name ) throw new FM_Developer_Exception( __( 'Group child name conflict: ' ) . $name . ' / ' . $element->name );
			else if ( !$element->name ) $element->name = $name;
		}

		// Add the tab JS and CSS if it is needed
		if ( $this->tabbed ) {
			fm_add_script( 'fm_group_tabs_js', 'js/fieldmanager-group-tabs.js', array( 'jquery' ), '1.0.1' );
			fm_add_style( 'fm_group_tabs_css', 'css/fieldmanager-group-tabs.css' );
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
		if ( isset( $this->label ) && !empty( $this->label ) ) $out .= '<div class="fm-group-inner">';

		// If the display output for this group is set to tabs, build the tab group for navigation
		if ( $this->tabbed ) $tab_group = sprintf( '<ul class="fm-tab-bar wp-tab-bar" id="%s-tabs">', $this->get_element_id() );

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
						implode( " ", $tab_classes ),
						$element->get_element_id(),
						$element->label
					 );
				} else if ( $this->tab_limit != 0 && $this->child_count >= $this->tab_limit ) {
					$submenu_item_classes = array( 'fm-submenu-item' );
					$submenu_item_link_class = "";

					// Create the More tab when first hitting the tab limit
					if ( $this->child_count == $this->tab_limit ) {
						// Create the tab
						$tab_group_submenu .=  sprintf( '<li class="fm-tab fm-has-submenu %s"><a href="#%s-tab">%s</a>',
							$tab_class,
							$element->get_element_id(),
							__( 'More...' )
						 );

						 // Start the submenu
						 $tab_group_submenu .= sprintf(
						 	'<div class="fm-submenu" id="%s-submenu"><div class="fm-submenu-wrap fm-submenu-wrap"><ul>',
						 	$this->get_element_id()
						 );

						 // Make sure the first submenu item is designated
						 $submenu_item_classes[] = 'fm-first-item';
						 $submenu_item_link_class = 'class="fm-first-item"';
					}

					// Add this element to the More menu
					$tab_group_submenu .=  sprintf( '<li class="%s"><a href="#%s-tab" %s>%s</a></li>',
						implode( ' ', $submenu_item_classes ),
						$element->get_element_id(),
						$submenu_item_link_class,
						$element->label
					);
				}

				// Ensure the child is aware it is tab content
				$element->is_tab = TRUE;
			}

			// Get markup for the child element
			$child_value = empty( $value[ $element->name ] ) ? Null : $value[ $element->name ];

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
		$this->children[] = $child;
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
					$this->_unauthorized_access( sprintf( 'Found "%1$s" in data but not in children', $key ) );
				}
			}
		}

		// Then, dispatch them for sanitization to the children.
		foreach ( $this->children as $k => $element ) {
			$element->data_id = $this->data_id;
			$element->data_type = $this->data_type;
			if ( empty( $values[$element->name] ) ) {
				$values[ $element->name ] = NULL;
			}
			$child_value = empty( $values[ $element->name ] ) ? Null : $values[ $element->name ];
			$current_child_value = empty( $current_values[$element->name ]) ? array() : $current_values[$element->name];
			$values[ $element->name ] = $element->presave_all( $values[ $element->name ], $current_child_value );
			if ( !$this->save_empty && $this->limit != 1 ) {
				if ( is_array( $values[$element->name] ) && empty( $values[$element->name] ) ) unset( $values[$element->name] );
				elseif ( empty( $values[$element->name] ) ) unset( $values[$element->name] );
			}
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

		$extra_attrs = '';
		if ( $this->label_macro ) {
			$this->label_format = $this->label_macro[0];
			$this->label_token = sprintf( '.fm-%s input.fm-element', $this->label_macro[1] );
		}

		if ( $this->label_format && $this->label_token ) {
			$extra_attrs = sprintf(
				'data-label-format="%1$s" data-label-token="%2$s"',
				htmlentities( $this->label_format ),
				htmlentities( $this->label_token )
			);
			$classes[] = 'fm-label-with-macro';
		}

		return sprintf(
			'<div class="%1$s"><%2$s class="%3$s"%4$s>%5$s</%2$s>%6$s</div>',
			implode( ' ', $wrapper_classes ),
			$this->label_element,
			implode( ' ', $classes ),
			$extra_attrs,
			$this->label,
			$this->limit == 0 ? $this->get_remove_handle() : ''
		);
	}

	/**
	 * Groups have their own drag and remove tools in the label.
	 * @param string $html
	 * @return string
	 */
	public function wrap_with_multi_tools( $html, $classes = array() ) {
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