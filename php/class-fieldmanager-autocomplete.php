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
	 * @var Fieldmanager_Datasource
	 * Data source to use for this Autocomplete
	 */
	public $datasource = Null;

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

		wp_enqueue_script( 'jquery-ui-autocomplete' );
		fm_add_script( 'fm_autocomplete_js', 'js/fieldmanager-autocomplete.js', array(), false, false, 'fm_search', array( 'nonce' => wp_create_nonce( 'fm_search_nonce' ) ) );

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
		} else {
			$this->attributes['data-options'] = htmlspecialchars( json_encode( $this->datasource->options ) );
		}

		$element = sprintf(
			'<input class="fm-autocomplete fm-element" type="text" id="%s" value="%s" %s />',
			$this->get_element_id(),
			$this->datasource->get_value( $value ),
			$this->get_element_attributes()
		);

		$element .= sprintf(
			'<input class="fm-autocomplete-hidden fm-element" type="hidden" name="%s" value="%s" />',
			$this->get_form_name(),
			$value
		);

		if ( $this->show_view_link ) {
			$element .= $this->datasource->get_view_link( $value );
		}

		if ( $this->show_edit_link ) {
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

}