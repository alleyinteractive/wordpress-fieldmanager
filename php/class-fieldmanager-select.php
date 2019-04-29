<?php
/**
 * Class file for Fieldmanager_Select
 *
 * @package Fieldmanager
 */

/**
 * Select dropdown or multi-select field.
 *
 * This class extends {@link Fieldmanager_Options}, which allows you to define
 * options (values) via an array or via a dynamic
 * {@link Fieldmanager_Datasource}, like {@link Fieldmanager_Datasource_Post},
 * {@link Fieldmanager_Datasource_Term}, or {@link Fieldmanager_Datasource_User}.
 */
class Fieldmanager_Select extends Fieldmanager_Options {

	/**
	 * Override $field_class.
	 *
	 * @var string
	 */
	public $field_class = 'select';

	/**
	 * Should we support type-ahead? i.e. use chosen.js or not.
	 *
	 * @var bool
	 */
	public $type_ahead = false;

	/**
	 * Send an empty element first.
	 *
	 * @var bool
	 */
	public $first_empty = false;

	/**
	 * Tell FM to save multiple values.
	 *
	 * @var bool
	 */
	public $multiple = false;

	/**
	 * Override constructor to add chosen.js maybe.
	 *
	 * @param string $label   The form label.
	 * @param array  $options The form options.
	 */
	public function __construct( $label = '', $options = array() ) {

		$this->attributes = array(
			'size' => '1',
		);

		// Add the Fieldmanager Select Javascript library.
		fm_add_script(
			'fm_select_js',
			'js/fieldmanager-select.js',
			array(),
			'1.0.2',
			false,
			'fm_select',
			array(
				'nonce' => wp_create_nonce( 'fm_search_terms_nonce' ),
			)
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

		// Add the chosen library for type-ahead capabilities.
		if ( $this->type_ahead ) {
			fm_add_script( 'fm_chosen', 'js/chosen/chosen.jquery.min.js', array( 'jquery' ), '1.8.2' );
			fm_add_style( 'fm_chosen_css', 'js/chosen/chosen.min.css', array(), '1.8.2' );
		}

	}

	/**
	 * Form element.
	 *
	 * @param array $value The current value.
	 * @return string HTML string.
	 */
	public function form_element( $value = array() ) {

		$select_classes = array( 'fm-element' );

		// If this is a multiple select, need to handle differently.
		$do_multiple = '';
		if ( $this->multiple ) {
			$do_multiple = '[]';
		}

		// Handle type-ahead based fields using the chosen library.
		if ( $this->type_ahead ) {
			$select_classes[] = 'chosen-select';
			if ( ! isset( $GLOBALS['fm_chosen_initialized'] ) ) {
				add_action( 'admin_footer', array( $this, 'chosen_init' ) );
				$GLOBALS['fm_chosen_initialized'] = true;
			}

			if ( $this->grouped ) {
				$select_classes[] = 'fm-options-grouped';
			} else {
				$select_classes[] = 'fm-options';
			}
		}

		$opts = '';
		if ( $this->is_repeatable() || $this->first_empty ) {
			$opts .= '<option value="">&nbsp;</option>';
		}
		$opts .= $this->form_data_elements( $value );

		return sprintf(
			'<select class="%s" name="%s" id="%s" %s>%s</select>',
			esc_attr( implode( ' ', $select_classes ) ),
			esc_attr( $this->get_form_name( $do_multiple ) ),
			esc_attr( $this->get_element_id() ),
			$this->get_element_attributes(),
			$opts
		);
	}

	/**
	 * Single data element (<option>).
	 *
	 * @param  array $data_row The data row.
	 * @param  array $value    The option value.
	 * @return string HTML string.
	 */
	public function form_data_element( $data_row, $value = array() ) {

		// For taxonomy-based selects, only return selected options if taxonomy preload is disabled.
		// Additional terms will be provided by Ajax for typeahead to avoid overpopulating the select for large taxonomies.
		$option_selected = $this->option_selected( $data_row['value'], $value, 'selected' );

		return sprintf(
			'<option value="%s" %s>%s</option>',
			esc_attr( $data_row['value'] ),
			$option_selected,
			esc_html( $data_row['name'] )
		);

	}

	/**
	 * Start an <optgroup>.
	 *
	 * @param string $label Optgroup label.
	 * @return string HTML string.
	 */
	public function form_data_start_group( $label ) {
		return sprintf(
			'<optgroup label="%s">',
			esc_attr( $label )
		);
	}

	/**
	 * End an <optgroup>.
	 *
	 * @return string HTML string.
	 */
	public function form_data_end_group() {
		return '</optgroup>';
	}

	/**
	 * Init chosen.js.
	 */
	public function chosen_init() {
		?>
		<script type="text/javascript">
		jQuery(function($){
			var chosenOpts = {
				allow_single_deselect: true,
				disable_search_threshold: -1,
				width: '350px'
			}
			$('.fm-wrapper').on("fm_added_element fm_collapsible_toggle fm_activate_tab",".fm-item",function(){
				$(".chosen-select:visible",this).chosen(chosenOpts)
			});
			$(".chosen-select:visible").chosen(chosenOpts);
		});
		</script>
		<?php
	}
}
