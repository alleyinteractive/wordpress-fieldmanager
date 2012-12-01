<?php

class Fieldmanager_Group extends Fieldmanager_Field {

	public $children = array();
	public $field_class = 'group';
	public $label_element = 'h4';
	public $collapsible = FALSE;
	public $tabbed = FALSE;
	public $tab_limit = 0;
	protected $child_count = 0;

	public function form_element( $value = NULL ) {
		$out = "";
		$tab_group = "";
		$tab_group_submenu = "";
		
		// We do not need the wrapper class for extra padding if no label is set for the group
		if ( isset( $this->label ) && !empty( $this->label ) ) $out .= '<div class="fm-group-inner">';
		
		// If the display output for this group is set to tabs, build the tab group for navigation
		if ( $this->tabbed ) $tab_group = sprintf( '<ul class="fm-tab-bar wp-tab-bar" id="%s-tabs">', $this->get_element_id() );
		
		// Produce HTML for each of the children
		foreach ( $this->children as $element ) {
		
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
						 $submenu_item_classes[] = "fm-first-item";
						 $submenu_item_link_class = 'class="fm-first-item"';
					}
					
					// Add this element to the More menu
					$tab_group_submenu .=  sprintf( '<li class="%s"><a href="#%s-tab" %s>%s</a></li>',
						implode( " ", $submenu_item_classes ),
						$element->get_element_id(),
						$submenu_item_link_class,
						$element->label
					);
				}
								
				// Ensure the child is aware it is tab content
				$element->is_tab = TRUE;
			}
		
			// Get markup for the child element
			$element->parent = $this;
			$child_value = empty( $value[ $element->name ] ) ? Null : $value[ $element->name ];
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
	 * Presave override for groups which dispatches to child presave_all methods.
	 * @input mixed[] values
	 * @return mixed[] values
	 */
	public function presave( $values ) {
		// @SECURITY@ First, make sure all the values we're given are legal.
		foreach ( array_keys( $values ) as $key ) {
			if ( !isset( $this->children[$key] ) ) {
				// If we're here, it means that the input, generally $_POST, contains a value that doesn't belong,
				// and thus one which we cannot sanitize and must not save. This might be an attack, so do what
				// Zoninator does and just die already.
				$this->_unauthorized_access();
			}
		}
		// Then, dispatch them for sanitization to the children.
		foreach ( $this->children as $k => $element ) {
			$element->data_id = $this->data_id;
			$element->data_type = $this->data_type;
			$child_value = empty( $values[ $element->name ] ) ? Null : $values[ $element->name ];
			$values[ $element->name ] = $element->presave_all( $values[ $element->name ] );
			if ( empty( $values[$element->name] ) && !$this->save_empty ) unset( $values[$element->name] );
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
		return sprintf(
			'<div class="%s"><%s class="%s">%s</%s>%s</div>',
			implode( ' ', $wrapper_classes ),
			$this->label_element,
			implode( ' ', $classes ),
			$this->label,
			$this->label_element,
			$this->limit == 0 ? $this->get_remove_handle() : ''
		);
	}

	/**
	 * Groups have their own drag and remove tools in the label.
	 */
	public function wrap_with_multi_tools( $html ) {
		return $html;
	}

	public function get_extra_element_classes() {
		if ( $this->collapsible ) {
			return array( 'fm-collapsible' );
		}
		return array();
	}
}