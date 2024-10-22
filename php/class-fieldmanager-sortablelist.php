<?php
/**
 * Class file for Fieldmanager_SortableList
 *
 * @package Fieldmanager
 */

/**
 * Sortable list field.
 *
 * Allows users to reorder a list of predefined items via drag and drop.
 */
class Fieldmanager_SortableList extends Fieldmanager_Field {

	/**
	 * The items to be sorted. An associative array of key => label.
	 *
	 * @var array
	 */
	public $items = array();

	/**
	 * Add scripts and styles and other setup tasks.
	 *
	 * @param string $label   The label.
	 * @param array  $options The field options.
	 */
	public function __construct( $label = '', $options = array() ) {
		parent::__construct( $label, $options );

		// Enqueue necessary scripts and styles.
		// Enqueue jQuery UI sortable.
		wp_enqueue_script( 'jquery-ui-sortable' );

		// Enqueue custom script to handle the sortable list.
		fm_add_script( 'fm_sortablelist_js', 'js/fieldmanager-sortablelist.js', array( 'jquery', 'jquery-ui-sortable' ), FM_VERSION, true );
		fm_add_style( 'fm_sortablelist_css', 'css/fieldmanager-sortablelist.css', array(), FM_VERSION );
	}

	/**
	 * Render the form element.
	 *
	 * @param mixed $value The current value.
	 * @return string The HTML output.
	 */
	public function form_element( $value ) {
		// If no value is set, use the default order of items.
		if ( empty( $value ) || ! is_array( $value ) ) {
			$value = array_keys( $this->items );
		}
	
		// Build the list of items to display
		$items_to_display = array();
	
		// First, add items from $value that are still in $this->items
		foreach ( $value as $item_key ) {
			if ( isset( $this->items[ $item_key ] ) ) {
				$items_to_display[] = $item_key;
			}
		}
	
		// Then, add any items from $this->items that are not already in $value
		foreach ( $this->items as $item_key => $item_label ) {
			if ( ! in_array( $item_key, $items_to_display ) ) {
				$items_to_display[] = $item_key;
			}
		}
	
		// Build the list based on the current order.
		$output = '<ul class="fm-sortable-list">';
		foreach ( $items_to_display as $item_key ) {
			$output .= sprintf(
				'<li class="fm-sortable-item" data-key="%s">%s</li>',
				esc_attr( $item_key ),
				esc_html( $this->items[ $item_key ] )
			);
		}
		$output .= '</ul>';
	
		// Hidden input to store the order.
		$output .= sprintf(
			'<input type="hidden" name="%s" id="%s" value="%s" />',
			esc_attr( $this->get_form_name() ),
			esc_attr( $this->get_element_id() ),
			esc_attr( implode( ',', $items_to_display ) )
		);
		return $output;
	}	

	/**
	 * Prepare the data before saving.
	 *
	 * @param mixed $value The value submitted from the form.
	 * @param mixed $current_values The current value.
	 * @return mixed The value to save.
	 */
	public function presave( $value, $current_values = array() ) {
		// Save the order as an array of keys.
		$order = explode( ',', $value );
		// Validate the keys to ensure they exist in the items array.
		$order = array_filter( $order, function( $key ) {
			return array_key_exists( $key, $this->items );
		} );
		return $order;
	}
}
