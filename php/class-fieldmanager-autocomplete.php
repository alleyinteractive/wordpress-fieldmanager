<?php
/**
 * Class file for Fieldmanager_Autocomplete
 *
 * @package Fieldmanager
 */

/**
 * Text field that responds to user input with autocomplete suggestions
 * (optionally via an ajax request).
 *
 * This must include a {@link Fieldmanager_Datasource}, which the autocomplete
 * uses to search against.
 */
class Fieldmanager_Autocomplete extends Fieldmanager_Field {

	/**
	 * Require an exact match; e.g. prevent the user from entering free text
	 *
	 * @var bool
	 */
	public $exact_match = true;

	/**
	 * Show the edit link.
	 *
	 * @var bool
	 */
	public $show_edit_link = false;

	/**
	 * Key for reciprocal relationship; if defined will add an entry to postmeta on the mirrored post.
	 *
	 * @var string
	 */
	public $reciprocal = null;

	/**
	 * What function to call to match posts. Initialized to Null here because it will be
	 * written in __construct to an internal function that calls get_posts, so only
	 * overwrite it if you do /not/ want to use get_posts.
	 *
	 * The function signature should be query_callback( $match, $args );
	 *
	 * @var callable
	 */
	public $query_callback = null;

	/**
	 * Javascript trigger to handle adding custom args
	 *
	 * @var string
	 */
	public $custom_args_js_event = null;

	/**
	 * Override save_empty for this element type
	 *
	 * @var bool
	 */
	public $save_empty = false;

	/**
	 * Add libraries for autocomplete.
	 *
	 * @throws FM_Developer_Exception Must use a datasource.
	 *
	 * @param string $label   The label.
	 * @param array  $options The options for the field.
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->attributes = array(
			'size' => '50',
		);
		parent::__construct( $label, $options );

		fm_add_script(
			'fm_autocomplete_js',
			'js/fieldmanager-autocomplete.js',
			array( 'fieldmanager_script', 'jquery-ui-autocomplete' ),
			'1.0.6',
			false,
			'fm_search',
			array(
				'nonce' => wp_create_nonce( 'fm_search_nonce' ),
			)
		);

		if ( empty( $this->datasource ) ) {
			$message = esc_html__( 'You must supply a datasource for the autocomplete field', 'fieldmanager' );
			if ( Fieldmanager_Field::$debug ) {
				throw new FM_Developer_Exception( $message );
			} else {
				wp_die( esc_html( $message ), esc_html__( 'No Datasource', 'fieldmanager' ) );
			}
		}
		$this->datasource->allow_optgroups = false;
	}

	/**
	 * Alter values before rendering.
	 *
	 * @param array $values The values to load.
	 */
	public function preload_alter_values( $values ) {
		if ( $this->datasource ) {
			 return $this->datasource->preload_alter_values( $this, $values );
		}
		return $values;
	}

	/**
	 * Render form element.
	 *
	 * @param mixed $value The current value.
	 * @return string HTML.
	 */
	public function form_element( $value = null ) {

		if ( $this->exact_match ) {
			$this->attributes['data-exact-match'] = true;
		}

		if ( $this->datasource->use_ajax ) {
			$this->attributes['data-action']     = $this->datasource->get_ajax_action( $this->name );
			list ( $context, $subcontext )       = fm_get_context();
			$this->attributes['data-context']    = $context;
			$this->attributes['data-subcontext'] = $subcontext;
		} else {
			$this->attributes['data-options'] = htmlspecialchars( wp_json_encode( $this->datasource->get_items() ) );
		}

		$display_value = $this->datasource->get_value( $value );
		if ( '' === $display_value && ! $this->exact_match && ! isset( $this->datasource->options[ $value ] ) ) {
			$display_value = $value;
		}

		$element = sprintf(
			'<input class="fm-autocomplete fm-element fm-incrementable" type="text" id="%s" value="%s"%s %s />',
			esc_attr( $this->get_element_id() ),
			esc_attr( $display_value ),
			( ! empty( $this->custom_args_js_event ) ) ? ' data-custom-args-js-event="' . esc_attr( $this->custom_args_js_event ) . '"' : '',
			$this->get_element_attributes()
		);

		$element .= sprintf(
			'<input class="fm-autocomplete-hidden fm-element" type="hidden" name="%s" value="%s" />',
			esc_attr( $this->get_form_name() ),
			esc_attr( $value )
		);

		if ( isset( $this->show_view_link ) && $this->show_view_link ) {
			$element .= $this->datasource->get_view_link( $value );
		}

		if ( isset( $this->show_edit_link ) && $this->show_edit_link ) {
			$element .= $this->datasource->get_edit_link( $value );
		}

		return $element;
	}

	/**
	 * Trigger datasource's presave_alter() event to allow it to handle reciprocal
	 * values.
	 *
	 * @param array $values         New post values.
	 * @param array $current_values Existing post values.
	 */
	public function presave_alter_values( $values, $current_values = array() ) {
		// return if there is no data id.
		if ( empty( $this->data_id ) ) {
			return $values;
		}

		if ( ! empty( $this->datasource->only_save_to_taxonomy ) ) {
			$this->skip_save = true;
		} elseif ( ! empty( $this->datasource->only_save_to_post_parent ) ) {
			$this->skip_save = true;
		}

		return $this->datasource->presave_alter_values( $this, $values, $current_values );
	}

	/**
	 * Delegate sanitization and validation to the datasource's presave() method.
	 *
	 * @param  array $value         The new values.
	 * @param  array $current_value The current values.
	 * @return array The new values.
	 */
	public function presave( $value, $current_value = array() ) {
		return $this->datasource->presave( $this, $value, $current_value );
	}

	/**
	 * Helper function to get the list of default meta boxes to remove.
	 * If $remove_default_meta_boxes is true and the datasource is Fieldmanager_Datasource_Term,
	 * this will return a list of all default meta boxes for the specified taxonomies.
	 * We only need to return id and context since the page will be handled by the
	 * list of post types provided to add_meta_box. Otherwise, this will just
	 * return an empty array.
	 *
	 * @param array $meta_boxes_to_remove List of meta boxes to remove.
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
