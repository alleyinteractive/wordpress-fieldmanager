<?php

class Fieldmanager_Group extends Fieldmanager_Field {

	public $children = array();
	public $field_class = 'group';
	public $label_element = 'h4';
	public $collapsible = FALSE;
	public $tabbed = FALSE;
	protected $child_count = 0;

	public function form_element( $value = NULL ) {
		$out = "";
		$tab_group = "";
		
		// We do not need the wrapper class for extra padding if no label is set for the group
		if ( isset( $this->label ) && !empty( $this->label ) ) $out .= '<div class="fm-group-inner">';
		
		// If the display output for this group is set to tabs, build the tab group for navigation
		if ( $this->tabbed ) $tab_group = '<ul class="fm-tab-bar wp-tab-bar" id="' . $this->get_element_id() . '-tabs">';
		
		// Produce HTML for each of the children
		foreach ( $this->children as $element ) {
		
			// If the display output for this group is set to tabs, add a tab for this child
			if ( $this->tabbed ) { 
				
				// Set default classes to display the first tab content and hide others
				$tab_class = ( $this->child_count == 0 ) ? "wp-tab-active" : "hide-if-no-js";
			
				// Generate output for the tab
				$tab_group .= '<li class="' . $tab_class . '"><a href="#' . $element->get_element_id() . '-tab">' . $element->label . '</a></li>';
				
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
		if ( $this->tabbed ) $tab_group .= '</ul>';
		
		// Return the complete HTML
		return $tab_group . $out;
	}

	public function presave( $values ) {
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