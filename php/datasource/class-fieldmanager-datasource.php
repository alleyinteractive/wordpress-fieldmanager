<?php
/**
 * @package Fieldmanager_Datasource
 */

/**
 * Base data source; can provide static options for Option fields or
 * Autocomplete fields.
 * @package Fieldmanager_Datasource
 */
class Fieldmanager_Datasource {

	/**
	 * @var array
	 */
	public $options = array();

	/**
	 * @var boolean
	 */
	public $options_callback = null;

	/**
	 * @var boolean
	 */
	public $use_ajax = false;

	/**
	 * @var boolean
	 */
	public $allow_optgroups = true;

	/**
	 * @var string
	 */
	public $ajax_action = '';

	/**
	 * @var int
	 * Counter to create uniquely named AJAX actions.
	 */
	public static $counter = 0;

	/**
	 * @var boolean
	 * If true, group elements
	 */
	public $grouped = false;

	/**
	 * Constructor
	 */
	public function __construct( $options = array() ) {

		foreach ( $options as $k => $v ) {
			try {
				$reflection = new ReflectionProperty( $this, $k ); // Would throw a ReflectionException if item doesn't exist (developer error)
				if ( $reflection->isPublic() ) {
					$this->$k = $v;
				} else {
					throw new FM_Developer_Exception; // If the property isn't public, don't set it (rare)
				}
			} catch ( Exception $e ) {
				$message = sprintf(
					__( 'You attempted to set a property "%1$s" that is nonexistant or invalid for an instance of "%2$s" named "%3$s".', 'fieldmanager' ),
					$k, __CLASS__, ! empty( $options['name'] ) ? $options['name'] : 'NULL'
				);
				$title = esc_html__( 'Nonexistant or invalid option' );
				if ( ! Fieldmanager_Field::$debug ) {
					wp_die( esc_html( $message ), $title );
				} else {
					throw new FM_Developer_Exception( esc_html( $message ) );
				}
			}
		}

		if ( get_class( $this ) == __CLASS__ && empty( $options ) ) {
			$message = esc_html__( 'Invalid options for Datasource; must use the options parameter to supply an array.', 'fieldmanager' );
			if ( Fieldmanager_Field::$debug ) {
				throw new FM_Developer_Exception( $message );
			} else {
				wp_die( $message, esc_html__( 'Invalid Datasource Options', 'fieldmanager' ) );
			}
		}

		if ( ! empty( $this->options ) ) {
			$keys = array_keys( $this->options );
			if ( ( array_keys( $keys ) === $keys ) ) {
				foreach ( $this->options as $k => $v ) {
					$this->options[ $v ] = $v;
					unset( $this->options[ $k ] );
				}
			}
		}

		if ( $this->use_ajax ) {
			add_action( 'wp_ajax_' . $this->get_ajax_action(), array( $this, 'autocomplete_search' ) );
		}
	}

	/**
	 * Get the value of an item; most clearly used by Post and Term, which
	 * take database IDs and return user-friendly titles.
	 * @param int $id
	 * @return string value
	 */
	public function get_value( $id ) {
		return isset( $this->options[ $id ] ) ? $this->options[ $id ] : '';
	}

	/**
	 * Get available options, optionally filtering by a fragment (e.g. for Autocomplete)
	 * @param string $fragment optional fragment to filter by
	 * @return array, key => value of available options
	 */
	public function get_items( $fragment = null ) {
		if ( ! $fragment ) {
			return $this->options;
		}
		$ret = array();
		foreach ( $this->options as $k => $v ) {
			if ( false !== strpos( $v, $fragment ) ) {
				$ret[ $k ] = $v;
			}
		}
		return $ret;
	}

	/**
	 * Get an action to register by hashing (non cryptographically for speed)
	 * the options that make this datasource unique.
	 * @return string ajax action
	 */
	public function get_ajax_action() {
		if ( ! empty( $this->ajax_action ) ) {
			return $this->ajax_action;
		}
		return 'fm_datasource_' . crc32( 'base' . json_encode( $this->options ) . $this->options_callback );
	}

	/**
	 * Format items for use in AJAX.
	 *
	 * @param string|null $fragment to search
	 */
	public function get_items_for_ajax( $fragment = null ) {
		$items = $this->get_items( $fragment );
		$return = array();

		foreach ( $items as $id => $label ) {
			$return[] = array( 'label' => $label, 'value' => $id );
		}

		return $return;
	}

	/**
	 * AJAX callback to find posts
	 * @return void, causes process to exit.
	 */
	public function autocomplete_search() {
		// Check the nonce before we do anything
		check_ajax_referer( 'fm_search_nonce', 'fm_search_nonce' );
		if ( isset( $_POST['fm_autocomplete_search'] ) ) {
			$items = $this->get_items_for_ajax( sanitize_text_field( $_POST['fm_autocomplete_search'] ) );
		}
		// See if any results were returned and return them as an array
		if ( ! empty( $items ) ) {
			wp_send_json( $items );
		} else {
			wp_send_json( 0 );
		}
	}

	/**
	 * Trigger to handle actions needed before saving data
	 * @param Fieldmanager_Field $field
	 * @param string|int $value
	 * @param string|int|null $current_value
	 * @return string cleaned value
	 */
	public function presave_alter_values( Fieldmanager_Field $field, $values, $current_values ) {
		// nothing here, but some child classes need this method.
		return $values;
	}

	/**
	 * Modify values before rendering editor
	 * @param Fieldmanager_Field $field
	 * @param array $values
	 * @return array $values loaded up, if applicable.
	 */
	public function preload_alter_values( Fieldmanager_Field $field, $values ) {
		return $values;
	}

	/**
	 * Datasource handles sanitization and validation
	 * @param Fieldmanager_Field $field
	 * @param string|int $value
	 * @param string|int|null $current_value
	 * @return string cleaned value
	 */
	public function presave( Fieldmanager_Field $field, $value, $current_value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}
		return sanitize_text_field( $value );
	}

	/**
	 * Get view link, not used here but meant for override
	 * @param int|string $value
	 * @return string
	 */
	public function get_view_link( $value ) {
		return '';
	}

	/**
	 * Get edit link, not used here but meant for override
	 * @param int|string $value
	 * @return string
	 */
	public function get_edit_link( $value ) {
		return '';
	}

}
