<?php
/**
 * Class file for Fieldmanager_Grid
 *
 * @package Fieldmanager
 */

/**
 * Data grid (spreadsheet) field.
 *
 * This field uses {@link https://github.com/handsontable/handsontable/
 * Handsontable} to provide a grid interface.
 */
class Fieldmanager_Grid extends Fieldmanager_Field {

	/**
	 * Override field class.
	 *
	 * @var string
	 */
	public $field_class = 'grid';

	/**
	 * Sort a grid before rendering (takes entire grid as a parameter).
	 *
	 * @var callable
	 */
	public $grid_sort = null;

	/**
	 * Options to pass to grid manager.
	 *
	 * @var array
	 */
	public $js_options = array();

	/**
	 * Constructor which adds several scrips and CSS.
	 *
	 * @param string $label   The form label.
	 * @param array  $options The form options.
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->attributes = array(
			'size' => '50',
		);
		parent::__construct( $label, $options );
		$this->sanitize = function( $row, $col, $values ) {
			foreach ( $values as $k => $val ) {
				$values[ $k ] = sanitize_text_field( $val );
			}
			return $values;
		};

		fm_add_script( 'handsontable', 'js/grid/jquery.handsontable.js' );
		fm_add_script( 'contextmenu', 'js/grid/lib/jQuery-contextMenu/jquery.contextMenu.js' );
		fm_add_script( 'ui_position', 'js/grid/lib/jQuery-contextMenu/jquery.ui.position.js' );
		fm_add_script( 'grid', 'js/grid.js', array( 'fm_loader', 'handsontable', 'contextmenu', 'ui_position' ), FM_VERSION, true );
		fm_add_style( 'context_menu_css', 'js/grid/lib/jQuery-contextMenu/jquery.contextMenu.css' );
		fm_add_style( 'handsontable_css', 'js/grid/jquery.handsontable.css' );
	}

	/**
	 * Render HTML for Grid element.
	 *
	 * @param array $value The current value.
	 * @return string The HTML string.
	 */
	public function form_element( $value = '' ) {
		$grid_activate_id = 'grid-activate-' . uniqid( true );
		if ( ! empty( $value ) && is_callable( $this->grid_sort ) ) {
			$value = call_user_func( $this->grid_sort, $value );
		}
		$out = sprintf(
			'<div class="grid-toggle-wrapper">
				<div class="fm-grid" id="%2$s" data-fm-grid-name="%1$s" data-fm-grid-opts="%3$s"></div>
				<input name="%1$s" class="fm-element" type="hidden" value="%4$s" />
				<p><a href="#" class="grid-activate" id="%7$s" data-with-grid-title="%6$s">%5$s</a></p>
			</div>',
			esc_attr( $this->get_form_name() ),
			esc_attr( 'hot-grid-id-' . uniqid( true ) ), // handsontable must have an ID, but we don't care what it is.
			esc_attr( wp_json_encode( $this->js_options ) ),
			esc_attr( wp_json_encode( $value ) ),
			esc_attr__( 'Show Data Grid', 'fieldmanager' ),
			esc_attr__( 'Hide Data Grid', 'fieldmanager' ),
			esc_attr( $grid_activate_id )
		);
		return $out;
	}

	/**
	 * Override presave, using the sanitize function per cell.
	 *
	 * @param  array $value         The new value.
	 * @param  array $current_value The current values.
	 * @return array Sanitized row/col matrix.
	 */
	public function presave( $value, $current_value = array() ) {
		$rows = json_decode( stripslashes( $value ), true );
		if ( ! is_array( $rows ) ) {
			return array();
		}

		foreach ( $rows as $i => $cells ) {
			foreach ( $cells as $k => $cell ) {
				$cell = call_user_func( $this->sanitize, $i, $k, $cell );
			}
		}
		return $rows;
	}

}
