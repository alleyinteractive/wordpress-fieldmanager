<?php
/**
 * Class file for Fieldmanager_Options
 *
 * @package Fieldmanager
 */

/**
 * Abstract base class for handling option fields, like select elements,
 * checkboxes or radios.
 */
abstract class Fieldmanager_Options extends Fieldmanager_Field {

	/**
	 * Full option data, allows grouping.
	 *
	 * @var array
	 */
	public $data = array();

	/**
	 * Shortcut to data which allows a simple associative array.
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Is the data grouped?
	 * E.g. should we use <optgroup>
	 *
	 * @var bool
	 */
	public $grouped = false;

	/**
	 * Always prepend this element to the $data.
	 *
	 * @var array
	 */
	public $first_element = array();

	/**
	 * Allow multiple selections?
	 *
	 * @var bool
	 */
	public $multiple = false;

	/**
	 * Path to an options template to load.
	 *
	 * @var string
	 */
	public $options_template = '';

	/**
	 * Ensure that the datasource only runs once.
	 *
	 * @var bool
	 */
	private $has_built_data = false;

	/**
	 * Add CSS, construct parent.
	 *
	 * @param string $label   The form label.
	 * @param mixed  $options The form options.
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->sanitize = array( $this, 'sanitize' );
		parent::__construct( $label, $options );

		if ( ! empty( $this->options ) ) {
			$this->add_options( $this->options );
		}

		// Add the options CSS.
		fm_add_style( 'fm_options_css', 'css/fieldmanager-options.css', array(), FM_VERSION );
	}

	/**
	 * Sanitize function that can handle arrays as well as string values.
	 *
	 * @param  array|string $value The current value.
	 * @return array|string Sanitized $value.
	 */
	public function sanitize( $value ) {
		if ( isset( $value ) && is_array( $value ) && ! empty( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		} else {
			return sanitize_text_field( $value );
		}
	}

	/**
	 * Add options.
	 *
	 * @param array $options The options to show.
	 */
	public function add_options( $options ) {
		$values = array_values( $options );
		if ( isset( $values[0] ) && is_array( $values[0] ) ) {
			foreach ( $options as $group => $data ) {
				foreach ( $data as $value => $label ) {
					$this->add_option_data( $label, $value, $group, $group );
				}
			}
		} else {
			$keys              = array_keys( $options );
			$use_name_as_value = ( array_keys( $keys ) === $keys );
			foreach ( $options as $k => $v ) {
				$this->add_option_data( $v, ( $use_name_as_value ? $v : $k ) );
			}
		}
	}

	/**
	 * Generate form elements.
	 *
	 * @param mixed $value The current value.
	 * @return string HTML string.
	 */
	public function form_data_elements( $value ) {

		if ( ! $this->has_built_data ) {
			if ( $this->datasource ) {
				$this->add_options( $this->datasource->get_items() );
			}

			/**
			 * Add the first element to the data array. This is useful for
			 * database-based data sets that require a first element.
			 */
			if ( ! empty( $this->first_element ) ) {
				array_unshift( $this->data, $this->first_element );
			}
			$this->has_built_data = true;
		}

		// If the value is not in an array, put it in one since sometimes there will be multiple selects.
		if ( ! is_array( $value ) && isset( $value ) ) {
			$value = array( $value );
		}

		// Output the data for the form. Child classes will handle the output format appropriate for them.
		$form_data_elements_html = '';

		if ( ! empty( $this->data ) ) {

			$current_group = '';

			foreach ( $this->data as $data_element ) {

				// If grouped display is desired, check where to add the start and end points
				// Note we are assuming the data has come pre-sorted into groups.
				if ( $this->grouped && ( $current_group != $data_element['group'] ) ) {

					// Append the end for the previous group unless this is the first group.
					if ( '' != $current_group ) {
						$form_data_elements_html .= $this->form_data_end_group();
					}

					// Append the start of the group.
					$form_data_elements_html .= $this->form_data_start_group( $data_element['group'] );

					// Set the current group.
					$current_group = $data_element['group'];
				}

				// Get the current element.
				$form_data_elements_html .= $this->form_data_element( $data_element, $value );
			}

			// If this was grouped display, close the final group.
			if ( $this->grouped || ( isset( $this->datasource ) && $this->datasource->grouped ) ) {
				$form_data_elements_html .= $this->form_data_end_group();
			}
		}

		return $form_data_elements_html;

	}

	/**
	 * A single element for a single bit of data, e.g. '<option>'.
	 *
	 * @param  mixed $data_row The data row.
	 * @param  mixed $value    The current value.
	 * @return string HTML.
	 */
	public function form_data_element( $data_row, $value ) {
		if ( ! $this->options_template ) {
			$tpl_slug               = 'options-' . strtolower( str_replace( 'Fieldmanager_', '', get_class( $this ) ) );
			$this->options_template = fieldmanager_get_template( $tpl_slug );
		}
		ob_start();
		include $this->options_template;
		return ob_get_clean();
	}

	/**
	 * Helper for output functions to toggle a selected options.
	 *
	 * @param  string $current_option This option.
	 * @param  array  $options all    Valid options.
	 * @param  string $attribute      The option attribute.
	 * @return string $attribute On match, empty On failure.
	 */
	public function option_selected( $current_option, $options, $attribute ) {
		if ( ( null != $options && ! empty( $options ) ) && in_array( $current_option, $options ) ) {
			return $attribute;
		}

		return '';
	}

	/**
	 * Override presave_all to handle special cases associated with multiple options fields.
	 *
	 * @param  mixed $values         The new values.
	 * @param  mixed $current_values The current values.
	 * @return mixed Sanitized values.
	 */
	public function presave_all( $values, $current_values ) {
		// Multiple select and radio fields with no values chosen are left out of
		// the post request altogether, requiring special case handling.
		if ( 1 !== $this->limit && '' === $values ) {
			$values = null;
		}

		return parent::presave_all( $values, $current_values );
	}

	/**
	 * Presave function, which handles sanitization and validation.
	 *
	 * @param  mixed $value         If a single field expects to manage an array,
	 *                              it must override presave().
	 * @param  mixed $current_value The current value.
	 * @return mixed Sanitized values.
	 */
	public function presave( $value, $current_value = array() ) {
		if ( ! empty( $this->datasource ) ) {
			return $this->datasource->presave( $this, $value, $current_value );
		}
		foreach ( $this->validate as $func ) {
			if ( ! call_user_func( $func, $value ) ) {
				$this->_failed_validation(
					sprintf(
						/* translators: 1: Invalid value, 2: field label */
						__( 'Input "%1$s" is not valid for field "%2$s" ', 'fieldmanager' ),
						(string) $value,
						$this->label
					)
				);
			}
		}
		return call_user_func( $this->sanitize, $value );
	}

	/**
	 * Alter values before rendering.
	 *
	 * @param array $values The current values.
	 */
	public function preload_alter_values( $values ) {
		if ( $this->datasource ) {
			return $this->datasource->preload_alter_values( $this, $values );
		}
		return $values;
	}

	/**
	 * Presave hook to set taxonomy data, maybe.
	 *
	 * @param  array $values         The new values.
	 * @param  array $current_values The current values.
	 * @return array $values Sanitized values.
	 */
	public function presave_alter_values( $values, $current_values = array() ) {
		if ( ! empty( $this->datasource ) ) {
			if ( ! empty( $this->datasource->only_save_to_taxonomy ) ) {
				$this->skip_save = true;
			} elseif ( ! empty( $this->datasource->only_save_to_post_parent ) ) {
				$this->skip_save = true;
			}
			return $this->datasource->presave_alter_values( $this, $values, $current_values );
		}
		return $values;
	}


	/**
	 * Add option data to the data attribute of this object.
	 *
	 * @param string     $name      Option name.
	 * @param mixed      $value     Option value.
	 * @param string     $group     Option group.
	 * @param string|int $group_id  Option group ID.
	 */
	protected function add_option_data( $name, $value, $group = null, $group_id = null ) {
		$data = array(
			'name'  => $name,
			'value' => $value,
		);
		if ( isset( $group ) ) {
			$data['group'] = $group;
		}
		if ( isset( $group_id ) ) {
			$data['group_id'] = $group_id;
		}

		$this->data[] = $data;
	}

	/**
	 * Helper function to get the list of default meta boxes to remove.
	 * If $remove_default_meta_boxes is true and the datasource is Fieldmanager_Datasource_Term,
	 * this will return a list of all default meta boxes for the specified taxonomies.
	 * We only need to return id and context since the page will be handled by
	 * the list of post types provided to add_meta_box. Otherwise, this will just
	 * return an empty array.
	 *
	 * @param array $meta_boxes_to_remove Current list of meta boxes to remove.
	 */
	protected function add_meta_boxes_to_remove( &$meta_boxes_to_remove ) {
		if ( $this->remove_default_meta_boxes && $this->datasource instanceof \Fieldmanager_Datasource_Term ) {
			// Iterate over the list and build the list of meta boxes.
			$meta_boxes = array();
			foreach ( $this->datasource->get_taxonomies() as $taxonomy ) {
				// The ID differs if this is a hierarchical taxonomy or not. Get the taxonomy object.
				$taxonomy_obj = get_taxonomy( $taxonomy );
				if ( false !== $taxonomy_obj ) {
					if ( $taxonomy_obj->hierarchical ) {
						$id = $taxonomy . 'div';
					} else {
						$id = 'tagsdiv-' . $taxonomy;
					}

					$meta_boxes[ $id ] = array(
						'id'      => $id,
						'context' => 'side',
					);
				}
			}

			// Merge in the new meta boxes to remove.
			$meta_boxes_to_remove = array_merge( $meta_boxes_to_remove, $meta_boxes );
		}
	}
}
