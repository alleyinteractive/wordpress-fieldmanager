<?php

class Fieldmanager_Group extends Fieldmanager_Field {

	public $children = array();
	public $field_class = 'group';
	public $label_element = 'h4';
	public $collapsible = False;

	public function __construct( $options = array() ) {
		$this->validators = array( array( $this, 'validate_children' ) );
		parent::__construct($options);
	}

	public function form_element( $value = NULL ) {
		$out = "";
		// We do not need the wrapper class for extra padding if no label is set for the group
		if ( isset( $this->label ) && !empty( $this->label ) ) $out .= '<div class="fm-group-inner">';
		foreach ( $this->children as $element ) {
			$element->parent = $this;
			$child_value = empty( $value[ $element->name ] ) ? Null : $value[ $element->name ];
			$out .= $element->element_markup( $child_value );
		}
		if ( isset( $this->label ) && !empty( $this->label ) ) $out .= '</div>';
		return $out;
	}

	/**
	 * Validate children of a group. This function is called by Fieldmanager_Field::validate(),
	 * and is registered as a validator in Fieldmanager_Group::__construct().
	 * By using the validator system as with any other field, we can allow special kinds of
	 * group validations (e.g. make sure all the values add up to 100) without having to subclass
	 * Fieldmanager_Group.
	 * @param $value input value (an array)
	 * @param Fieldmanager_Field $field it's actually $this.
	 */
	public function validate_children( &$value, &$field ) {
		
	}

	public function presave( $values ) {
		foreach ( $this->children as $k => $element ) {
			$element->data_id = $this->data_id;
			$element->data_type = $this->data_type;
			$child_value = empty( $values[ $element->name ] ) ? Null : $values[ $element->name ];
			$values[ $element->name ] = $element->presave_all( $values[ $element->name ] );
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