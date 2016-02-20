<?php

/**
 * Dropdown for options
 * @package Fieldmanager
 */
class Fieldmanager_Select extends Fieldmanager_Options {

	/**
	 * @var string
	 * Override $field_class
	 */
	public $field_class = 'select';

	/**
	 * @var boolean
	 * Should we support type-ahead? i.e. use selec2.js or not
	 */
	public $type_ahead = False;

	/**
	 * @var boolean
	 * Send an empty element first
	 */
	public $first_empty = False;

	/**
	 * @var boolean
	 * Tell FM to save multiple values
	 */
	public $multiple = false;

	/**
	 * Override constructor to add select2.js maybe
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {

		$this->attributes = array(
			'size' => '1'
		);

		parent::__construct( $label, $options );

		// You can make a select field multi-select either by setting the attribute
		// or by setting `'multiple' => true`. If you opt for the latter, the
		// attribute will be set for you.
		if ( array_key_exists( 'multiple', $this->attributes ) ) {
			$this->multiple = true;
		} elseif ( $this->multiple ) {
			$this->attributes['multiple'] = 'multiple';
		}

		// Add the Select2 library for type-ahead capabilities
		if ( $this->type_ahead ) {
			fm_add_script( 'select2', 'js/select2/select2.js', array( 'jquery' ), '3.5.2', false, 'fm_select2', array( 'searchPlaceholder' => esc_html__( 'Search for a term', 'fieldmanager' ), 'nonce' => wp_create_nonce( 'fm_search_nonce' ) ) );
			fm_add_style( 'select2_css', 'js/select2/select2.css' );
		}

	}

	/**
	 * Form element
	 * @param array $value
	 * @return string HTML
	 */
	public function form_element( $value = array() ) {

		$select_classes = array( 'fm-element' );

		// If this is a multiple select, need to handle differently
		$do_multiple = '';
		if ( $this->multiple ) {
			$do_multiple = "[]";
		}

		// Handle type-ahead based fields using the select2 library
		if ( $this->type_ahead ) {
			$select_classes[] = 'select2-select';
			if ( !isset( $GLOBALS['fm_select2_initialized'] ) ) {
				add_action( 'admin_footer', array( $this, 'select2_init' ) );
				$GLOBALS['fm_select2_initialized'] = true;
			}

			if ( ! empty( $this->datasource ) && $this->datasource->use_ajax ) {
				$select_classes[] = 'select2-ajax-datasource';
				$this->attributes['fm-ajax-search-action'] = $this->datasource->get_ajax_action();
			} else {
				$select_classes[] = 'select2-static-datasource';
			}

			if ( $this->grouped ) {
				$select_classes[] = "fm-options-grouped";
			} else {
				$select_classes[] = "fm-options";
			}
		}

		$opts = '';
		if ( $this->is_repeatable() || $this->first_empty ) {
			$opts .= '<option value="">&nbsp;</option>';
		}
		$opts .= $this->form_data_elements( $value );

		return sprintf(
			'<select class="%s" name="%s" id="%s" %s>%s</select>',
			esc_attr( implode( " ", $select_classes ) ),
			esc_attr( $this->get_form_name( $do_multiple ) ),
			esc_attr( $this->get_element_id() ),
			$this->get_element_attributes(),
			$opts
		);
	}

	/**
	 * Single data element (<option>)
	 * @param array $data_row
	 * @param array $value
	 * @return string HTML
	 */
	public function form_data_element( $data_row, $value = array() ) {

		$option_selected = $this->option_selected( $data_row['value'], $value, "selected" );

		return sprintf(
			'<option value="%s" %s>%s</option>',
			esc_attr( $data_row['value'] ),
			$option_selected,
			esc_html( $data_row['name'] )
		);

	}

	/**
	 * Start an <optgroup>
	 * @param string $label
	 * @return string HTML
	 */
	public function form_data_start_group( $label ) {
		return sprintf(
			'<optgroup label="%s">',
			esc_attr( $label )
		);
	}

	/**
	 * End an <optgroup>
	 * @return string HTML
	 */
	public function form_data_end_group() {
		return '</optgroup>';
	}

	/**
	 * Init select2.js
	 * @return string HTML
	 */
	public function select2_init() {
		?>
		<script type="text/javascript">
		jQuery(function($){
			var select2Opts = {};
			select2Opts.allowClear = true;
			/*
			 * Static datasources
			 */
			$('.fm-wrapper').on("fm_added_element fm_collapsible_toggle fm_activate_tab",".fm-item",function(){
				$(".select2-select.select2-static-datasource:visible",this).select2( select2Opts );
			});
			$(".select2-select.select2-static-datasource:visible").select2( select2Opts );

			/*
			 * AJAX datasources
			 */
			select2Opts.placeholder = fm_select2.searchPlaceholder;
			select2Opts.minimumInputLength = 2;
			$('.fm-wrapper').on("fm_added_element fm_collapsible_toggle fm_activate_tab",".fm-item",function(){
				$(".select2-select.select2-ajax-datasource:visible",this).select2( select2Opts );
			});
			var fmSetUpSelect2Ajax = function() {
				select2Opts.ajax = {
					url: ajaxurl,
					data: function( term, page ) {
						return {
							action: $(this).data('fm-ajax-search-action'),
							fm_search_nonce: fm_select2.nonce,
							fm_autocomplete_search: term
						};
					},
					results: function( response, page ) {
						console.log( response );
					}
				};
				$(this).select2( select2Opts );
			};
			$('.fm-wrapper').on("fm_added_element fm_collapsible_toggle fm_activate_tab",".fm-item",function(){
				$(".select2-select.select2-ajax-datasource:visible",this).each(fmSetUpSelect2Ajax);
			});
			$(".select2-select.select2-ajax-datasource:visible").each(fmSetUpSelect2Ajax);
		});
		</script>
		<?php
	}
}
