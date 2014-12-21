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
	 * Should we support type-ahead? i.e. use chosen.js or not
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
	 * Override constructor to add chosen.js maybe
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {

		$this->attributes = array(
			'size' => '1'
		);

		// Add the Fieldmanager Select javascript library
		fm_add_script( 'fm_select_js', 'js/fieldmanager-select.js', array(), '1.0.1', false, 'fm_select', array( 'fm_search_nonce' => wp_create_nonce( 'fm_search_nonce' ) ) );

		parent::__construct( $label, $options );

		// You can make a select field multi-select either by setting the attribute
		// or by setting `'multiple' => true`. If you opt for the latter, the
		// attribute will be set for you.
		if ( array_key_exists( 'multiple', $this->attributes ) ) {
			$this->multiple = true;
		} elseif ( $this->multiple ) {
			$this->attributes['multiple'] = 'multiple';
		}

		// Add the chosen library for type-ahead capabilities
		if ( $this->type_ahead ) {
			fm_add_script( 'fm-chosen', 'js/chosen/chosen.jquery.min.js', array( 'jquery' ), '1.3.0' );
			fm_add_style( 'fm-chosen-css', 'js/chosen/chosen.min.css', array(), '1.3.0' );

			if ( $this->datasource && $this->datasource->use_ajax ) {
				$this->attributes['data-fm-ajax-search-action'] = $this->datasource->get_ajax_action();
				list ( $context, $subcontext ) = fm_get_context();
				$this->attributes['data-context'] = $context;
				$this->attributes['data-subcontext'] = $subcontext;
			}
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

		// Handle type-ahead based fields using the chosen library
		if ( $this->type_ahead ) {
			$select_classes[] = 'chosen-select';
			if ( !isset( $GLOBALS['fm_chosen_initialized'] ) ) {
				add_action( 'admin_footer', array( $this, 'chosen_init' ) );
				$GLOBALS['fm_chosen_initialized'] = true;
			}

			if ( $this->grouped ) {
				$select_classes[] = "fm-options-grouped";
			} else {
				$select_classes[] = "fm-options";
			}
		}

		$opts = '';
		if ( $this->first_empty ) {
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

		// For taxonomy-based selects, only return selected options if taxonomy preload is disabled
		// Additional terms will be provided by AJAX for typeahead to avoid overpopulating the select for large taxonomies
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
	 * Init chosen.js
	 * @return string HTML
	 */
	public function chosen_init() {
		?>
		<script type="text/javascript">
		jQuery(function($){
			$('.fm-wrapper').on("fm_added_element fm_collapsible_toggle fm_activate_tab",".fm-item",function(){
				$(".chosen-select:visible",this).chosen({allow_single_deselect:true,disable_search_threshold:-1,width:'350px'})
			});
			$(".chosen-select:visible").chosen({allow_single_deselect:true,disable_search_threshold:-1,width:'350px'});
		});
		</script>
		<?php
	}
}
