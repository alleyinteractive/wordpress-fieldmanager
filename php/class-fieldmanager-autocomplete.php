<?php

/**
 * @package Fieldmanager
 */

/**
 * Post auto-complete field
 * @package Fieldmanager
 */
class Fieldmanager_Autocomplete extends Fieldmanager_Field {

	/**
	 * @var boolean
	 * Require an exact match; e.g. prevent the user from entering free text
	 */
	public $exact_match = True;

	/**
	 * @var boolean
	 */
	public $show_edit_link = False;

	/**
	 * @var string
	 * Key for reciprocal relationship; if defined will add an entry to postmeta on the mirrored post.
	 */
	public $reciprocal = Null;

	/**
	 * @var callable
	 * What function to call to match posts. Initialized to Null here because it will be
	 * written in __construct to an internal function that calls get_posts, so only
	 * overwrite it if you do /not/ want to use get_posts.
	 *
	 * The function signature should be query_callback( $match, $args );
	 */
	public $query_callback = Null;

	/**
	 * @var boolean
	 * Override save_empty for this element type
	 */
	public $save_empty = False;

	/**
	 * Add libraries for autocomplete
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {
		$this->attributes = array(
			'size' => '50',
		);
		parent::__construct( $label, $options );

		// Enqueue required scripts in the proper context
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		fm_add_script( 'fm_autocomplete_js', 'js/fieldmanager-autocomplete.js', array(), '1.0.2', false, 'fm_search', array( 'nonce' => wp_create_nonce( 'fm_search_nonce' ) ) );

		if ( empty( $this->datasource ) ) {
			$message = __( 'You must supply a datasource for the autocomplete field' );
			if ( Fieldmanager_Field::$debug ) {
				throw new FM_Developer_Exception( $message );
			} else {
				wp_die( $message, __( 'No Datasource' ) );
			}
		}
		$this->datasource->allow_optgroups = False;
	}

	/**
	 * Handle enqueuing built-in scripts required for autocomplete
	 * @return void
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'jquery-ui-autocomplete' );
	}

	/**
	 * Alter values before rendering
	 * @param array $values
	 */
	public function preload_alter_values( $values ) {
		if ( $this->datasource ) return $this->datasource->preload_alter_values( $this, $values );
		return $values;
	}

	/**
	 * Render form element
	 * @param mixed $value
	 * @return string HTML
	 */
	public function form_element( $value = Null ) {

		if ( $this->exact_match ) {
			$this->attributes['data-exact-match'] = True;
		}

		if ( $this->datasource->use_ajax ) {
			$this->attributes['data-action'] = $this->datasource->get_ajax_action( $this->name );
			list ( $context, $subcontext ) = fm_get_context();
			$this->attributes['data-context'] = $context;
			$this->attributes['data-subcontext'] = $subcontext;
		} else {
			$this->attributes['data-options'] = htmlspecialchars( json_encode( $this->datasource->get_items() ) );
		}

		$element = sprintf(
			'<input class="fm-autocomplete fm-element fm-incrementable" type="text" id="%s" value="%s" %s />',
			$this->get_element_id(),
			$this->datasource->get_value( $value ),
			$this->get_element_attributes()
		);

		$element .= sprintf(
			'<input class="fm-autocomplete-hidden fm-element" type="hidden" name="%s" value="%s" />',
			$this->get_form_name(),
			$value
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
	 * Trigger datasource's presave_alter() event to allow it to handle reciprocal values
	 * @param array $values new post values
	 * @param array $current_values existing post values
	 */
	public function presave_alter_values( $values, $current_values = array() ) {
		// return if there are no saved values, if this isn't a post, or if the reciprocal relationship isn't set.
		if ( empty( $this->data_id ) || $this->data_type !== 'post' ) return $values;
		return $this->datasource->presave_alter_values( $this, $values, $current_values );
	}

	/**
	 * Delegate sanitization and validation to the datasource's presave() method.
	 * @param array $value
	 * @return array $value
	 */
	public function presave( $value, $current_value = array() ) {
		return $this->datasource->presave( $this, $value, $current_value );
	}

	/**
	 * Helper function to get the list of default meta boxes to remove.
	 * If $remove_default_meta_boxes is true and the datasource is Fieldmanager_Datasource_Term,
	 * this will return a list of all default meta boxes for the specified taxonomies.
	 * We only need to return id and context since the page will be handled by the list of post types provided to add_meta_box.
	 * Otherwise, this will just return an empty array.
	 * @param array current list of meta boxes to remove
	 * @return array list of meta boxes to remove
	 */
	protected function add_meta_boxes_to_remove( &$meta_boxes_to_remove ) {
		if ( $this->remove_default_meta_boxes && get_class( $this->datasource ) == 'Fieldmanager_Datasource_Term' ) {
			// Iterate over the list and build the list of meta boxes
			$meta_boxes = array();
			foreach( $this->datasource->get_taxonomies() as $taxonomy ) {
				// The ID differs if this is a hierarchical taxonomy or not. Get the taxonomy object.
				$taxonomy_obj = get_taxonomy( $taxonomy );
				if ( false !== $taxonomy_obj ) {
					if ( $taxonomy_obj->hierarchical )
						$id = $taxonomy . "div";
					else
						$id = 'tagsdiv-' . $taxonomy;

					$meta_boxes[$id] = array(
						'id' => $id,
						'context' => 'side'
					);
				}
			}

			// Merge in the new meta boxes to remove
			$meta_boxes_to_remove = array_merge( $meta_boxes_to_remove, $meta_boxes );
		}
	}
}
