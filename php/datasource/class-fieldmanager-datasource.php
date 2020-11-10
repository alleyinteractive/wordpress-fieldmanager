<?php
/**
 * Class file for Fieldmanager_Datasource
 *
 * @package Fieldmanager
 */

/**
 * Datasource base class to populate autocomplete and option fields.
 *
 * This class can be used as an arbitrary data source for static options, or it
 * can be extended to provide custom data sources.
 */
class Fieldmanager_Datasource {

	/**
	 * The datasource options.
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Options callback.
	 *
	 * @var string
	 */
	public $options_callback = null;

	/**
	 * Whether or not to use Ajax.
	 *
	 * @var bool
	 */
	public $use_ajax = false;

	/**
	 * Allow option groups.
	 *
	 * @var bool
	 */
	public $allow_optgroups = true;

	/**
	 * The Ajax action.
	 *
	 * @var string
	 */
	public $ajax_action = '';

	/**
	 * Counter to create uniquely named Ajax actions.
	 *
	 * @var int
	 */
	public static $counter = 0;

	/**
	 * If true, group elements.
	 *
	 * @var bool
	 */
	public $grouped = false;

	/**
	 * Constructor.
	 *
	 * @throws FM_Developer_Exception If the property isn't public, don't set it (rare).
	 *
	 * @param array $options The datasource options.
	 */
	public function __construct( $options = array() ) {

		foreach ( $options as $k => $v ) {
			try {
				// Would throw a ReflectionException if item doesn't exist (developer error).
				$reflection = new ReflectionProperty( $this, $k );
				if ( $reflection->isPublic() ) {
					$this->$k = $v;
				} else {
					// If the property isn't public, don't set it (rare).
					throw new FM_Developer_Exception();
				}
			} catch ( Exception $e ) {
				$message = sprintf(
					/* translators: 1: property name, 2: current class name, 3: option name */
					__( 'You attempted to set a property "%1$s" that is nonexistant or invalid for an instance of "%2$s" named "%3$s".', 'fieldmanager' ),
					$k,
					get_class(),
					! empty( $options['name'] ) ? $options['name'] : 'NULL'
				);
				if ( ! Fieldmanager_Field::$debug ) {
					wp_die( esc_html( $message ), esc_html__( 'Nonexistant or invalid option', 'fieldmanager' ) );
				} else {
					throw new FM_Developer_Exception( esc_html( $message ) );
				}
			}
		}

		if ( get_class( $this ) === __CLASS__ && empty( $options ) ) {
			$message = __( 'Invalid options for Datasource; must use the options parameter to supply an array.', 'fieldmanager' );
			if ( Fieldmanager_Field::$debug ) {
				throw new FM_Developer_Exception( $message );
			} else {
				wp_die( esc_html( $message ), esc_html__( 'Invalid Datasource Options', 'fieldmanager' ) );
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
	 *
	 * @param int $id The ID of the object.
	 * @return string The value.
	 */
	public function get_value( $id ) {
		return isset( $this->options[ $id ] ) ? $this->options[ $id ] : '';
	}

	/**
	 * Get available options, optionally filtering by a fragment (e.g. for Autocomplete).
	 *
	 * @param string $fragment Optional fragment to filter by.
	 * @return array The key => value of available options.
	 */
	public function get_items( $fragment = null ) {
		if ( ! $fragment ) {
			return $this->options;
		}
		$ret = array();
		foreach ( $this->options as $k => $v ) {
			if ( strpos( $v, $fragment ) !== false ) {
				$ret[ $k ] = $v;
			}
		}
		return $ret;
	}

	/**
	 * Get an action to register by hashing (non cryptographically for speed)
	 * the options that make this datasource unique.
	 *
	 * @return string Ajax action.
	 */
	public function get_ajax_action() {
		if ( ! empty( $this->ajax_action ) ) {
			return $this->ajax_action;
		}
		return 'fm_datasource_' . crc32( 'base' . wp_json_encode( $this->options ) . $this->options_callback );
	}

	/**
	 * Format items for use in Ajax.
	 *
	 * @param string|null $fragment Search string.
	 */
	public function get_items_for_ajax( $fragment = null ) {
		$items  = $this->get_items( $fragment );
		$return = array();

		foreach ( $items as $id => $label ) {
			$return[] = array(
				'label' => $label,
				'value' => $id,
			);
		}

		return $return;
	}

	/**
	 * Ajax callback to find posts.
	 */
	public function autocomplete_search() {
		// Check the nonce before we do anything.
		check_ajax_referer( 'fm_search_nonce', 'fm_search_nonce' );
		if ( isset( $_POST['fm_autocomplete_search'] ) ) { // WPCS: input var okay.
			$items = $this->get_items_for_ajax( sanitize_text_field( wp_unslash( $_POST['fm_autocomplete_search'] ) ) ); // WPCS: input var okay.
		}

		// See if any results were returned and return them as an array.
		if ( ! empty( $items ) ) {
			wp_send_json( $items );
		} else {
			wp_send_json( 0 );
		}
	}

	/**
	 * Trigger to handle actions needed before saving data.
	 *
	 * @param  Fieldmanager_Field $field          Base field.
	 * @param  mixed              $values         New value.
	 * @param  mixed              $current_values Current value.
	 * @return string $values Cleaned value.
	 */
	public function presave_alter_values( Fieldmanager_Field $field, $values, $current_values ) {
		// nothing here, but some child classes need this method.
		return $values;
	}

	/**
	 * Modify values before rendering editor.
	 *
	 * @param  Fieldmanager_Field $field  Base field.
	 * @param  mixed              $values New values.
	 * @return array $values Loaded up, if applicable.
	 */
	public function preload_alter_values( Fieldmanager_Field $field, $values ) {
		return $values;
	}

	/**
	 * Datasource handles sanitization and validation.
	 *
	 * @param  Fieldmanager_Field $field         Base field.
	 * @param  mixed              $value         New value.
	 * @param  mixed              $current_value Current value.
	 * @return string $values Cleaned value.
	 */
	public function presave( Fieldmanager_Field $field, $value, $current_value ) {
		if ( is_array( $value ) ) {
			return array_map( 'sanitize_text_field', $value );
		}
		return sanitize_text_field( $value );
	}

	/**
	 * Get view link, not used here but meant for override.
	 *
	 * @param mixed $value The current value.
	 * @return string HTML string.
	 */
	public function get_view_link( $value ) {
		return '';
	}

	/**
	 * Get edit link, not used here but meant for override.
	 *
	 * @param int|string $value The current value.
	 * @return string HTML string.
	 */
	public function get_edit_link( $value ) {
		return '';
	}

}
