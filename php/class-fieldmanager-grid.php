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
	 * @var callable
	 * Sort a grid before rendering (takes entire grid as a parameter)
	 */
	public $grid_sort = Null;

	/**
	 * @var string[]
	 * Options to pass to grid manager
	 */
	public $js_options = array();

	/**
	 * Constructor which adds several scrips and CSS
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->attributes = array(
			'size' => '50',
		);
		parent::__construct( $label, $options );
		$this->sanitize = function( $row, $col, $values ) {
			foreach ( $values as $k => $val ) {
				$values[$k] = sanitize_text_field( $val );
			}
			return $values;
		};

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
		$grid_activate_id = 'grid-activate-' . uniqid();
		if ( !empty( $value ) && is_callable( $this->grid_sort ) ) {
			$value = call_user_func( $this->grid_sort, $value );
		}
		$out = sprintf(
			'<div class="grid-toggle-wrapper">
				<div class="fm-grid" id="%2$s" data-fm-grid-name="%1$s"></div>
				<input name="%1$s" class="fm-element" type="hidden" value="%3$s" />
				<p><a href="#" class="grid-activate" id="%6$s" data-with-grid-title="%5$s">%4$s</a></p>
			</div>',
			$this->get_form_name(),
			'hot-grid-id-' . uniqid(), // handsontable must have an ID, but we don't care what it is.
			htmlspecialchars( json_encode( $value ) ),
			__( 'Show Data Grid' ),
			__( 'Hide Data Grid' ),
			$grid_activate_id
		);
		$out .= sprintf("
			<script type=\"text/javascript\">
				jQuery( document ).ready( function() {
					jQuery( '#%s' ).one( 'click', function( e ) {
						e.preventDefault();
						var grid = jQuery( this ).parents( '.grid-toggle-wrapper' ).find( '.fm-grid' )[0];
						jQuery( grid ).fm_grid( %s );
					} );
				} );
			</script>",
			$grid_activate_id,
			json_encode( $this->js_options )
		);
		return $out;
	}

	/**
	 * Override presave, using the sanitize function per cell
	 * @param string $value
	 * @return array sanitized row/col matrix
	 */
	public function presave( $value, $current_value = array() ) {
		$rows = json_decode( stripslashes( $value ), TRUE );
		if ( !is_array( $rows ) ) return array();
		foreach ( $rows as $i => $cells ) {
			foreach ( $cells as $k => $cell ) {
				$cell = call_user_func( $this->sanitize, $i, $k, $cell );
			}
		}
		return $rows;
	}

}