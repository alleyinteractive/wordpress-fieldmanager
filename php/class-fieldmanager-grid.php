<?php
/**
 * @package Fieldmanager
 */

/**
 * Fieldmanager plugin for Handsontable Grid view, packaged with main
 * Fieldmanager implemenation to demonstrate advanced custom functionality, and
 * a field which overrides presave.
 * @package Fieldmanager
 */
class Fieldmanager_Grid extends Fieldmanager_Field {

	/**
	 * @var string
	 * Override field clas
	 */
	public $field_class = 'grid';

	/**
	 * Constructor which adds several scrips and CSS
	 * @param array $options
	 */
	public function __construct( $options = array() ) {
		$this->js_options = array();
		$this->sanitize = function( $row, $col, $values ) {
			foreach ( $values as $k => $val ) {
				$values[$k] = sanitize_text_field( $val );
			}
			return $values;
		};
		$this->attributes = array(
			'size' => '50',
		);

		parent::__construct( $options );

		$this->limit = 1; // not a good idea to add multiple grids.

		fm_add_script( 'handsontable', 'js/grid/jquery.handsontable.js' );
		fm_add_script( 'contextmenu', 'js/grid/lib/jQuery-contextMenu/jquery.contextMenu.js' );
		fm_add_script( 'ui_position', 'js/grid/lib/jQuery-contextMenu/jquery.ui.position.js' );
		fm_add_script( 'grid', 'js/grid.js' );
		fm_add_style( 'context_menu_css', 'js/grid/lib/jQuery-contextMenu/jquery.contextMenu.css' );
		fm_add_style( 'handsontable_css', 'js/grid/jquery.handsontable.css' );
	}

	/**
	 * Render HTML for Grid element
	 * @param array $value
	 * @return string
	 */
	public function form_element( $value = '' ) {
		$out = sprintf(
			'<div class="fm-grid" data-fm-grid-name="%1$s" id="%2$s"></div>
			<input name="%1$s" id="%2$s-input" type="hidden" value="%3$s" />',
			$this->get_form_name(),
			$this->get_element_id(),
			htmlspecialchars( json_encode( $value ) )
		);
		$out .= sprintf("
			<script type=\"text/javascript\">
				jQuery( document ).ready( function() {
					jQuery( '#%s' ).fm_grid( %s );
				} );
			</script>",
			$this->get_element_id(),
			json_encode( $this->js_options )
		);
		return $out;
	}

	/**
	 * Override presave, using the sanitize function per cell
	 * @param string $value
	 * @return array sanitized row/col matrix
	 */
	public function presave( $value ) {
		$rows = json_decode( stripslashes( $value ), TRUE );
		foreach ( $rows as $i => $cells ) {
			foreach ( $cells as $k => $cell ) {
				$cell = call_user_func( $this->sanitize, $i, $k, $cell );
			}
		}
		return $rows;
	}

}