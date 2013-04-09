<?php

/**
 * Fieldmanager Datasource
 * Connects autocomplete and option fields to various dynamic data sources
 */
class Fieldmanager_Datasource {

	/**
	 * @var boolean
	 */
	public $options = array();

	/**
	 * @var boolean
	 */
	public $options_callback = Null;

	/**
	 * @var boolean
	 */
	public $use_ajax = False;

	/**
	 * @var boolean
	 */
	public $allow_optgroups = True;

	/**
	 * @var int
	 * Local instance of self::$counter
	 */
	private $ajax_idx = 0;

	/**
	 * @var int
	 * Counter to create uniquely named AJAX actions.
	 */
	private static $counter = 0;

	public function __construct( $options ) {

		foreach ( $options as $k => $v ) {
			try {
				$reflection = new ReflectionProperty( $this, $k ); // Would throw a ReflectionException if item doesn't exist (developer error)
				if ( $reflection->isPublic() ) $this->$k = $v;
				else throw new FM_Developer_Exception; // If the property isn't public, don't set it (rare)
			} catch ( Exception $e ) {
				$message = sprintf(
					__( 'You attempted to set a property <em>%1$s</em> that is nonexistant or invalid for an instance of <em>%2$s</em> named <em>%3$s</em>.' ),
					$k, __CLASS__, !empty( $options['name'] ) ? $options['name'] : 'NULL'
				);
				$title = __( 'Nonexistant or invalid option' );
				if ( !Fieldmanager_Field::$debug ) {
					wp_die( $message, $title );
				} else {
					throw new FM_Developer_Exception( $message );
				}
			}
			$this->ajax_idx = Fieldmanager_Datasource::$counter++;
		}

		if ( get_class( $this ) == __CLASS__ && empty( $options ) ) {
			$message = __( 'Invalid options for Datasource; must use the options parameter to supply an array.' );
			if ( Fieldmanager_Field::$debug ) {
				throw new FM_Developer_Exception( $message );
			} else {
				wp_die( $message, __( 'Invalid Datasource Options' ) );
			}
		}

		if ( !empty( $this->options ) ) {
			$keys = array_keys( $this->options );
			$use_name_as_value = ( array_keys( $keys ) === $keys );
			foreach ( $this->options as $k => $v ) {
				$this->options[$v] = $v;
				unset( $this->options[$k] );
			}
		}

		if ( $this->use_ajax ) {
			add_action( 'wp_ajax_' . $this->get_ajax_action(), array( $this, 'autocomplete_search' ) );
		}
	}

	public function get_value( $id ) {
		return $this->options[$id] ?: '';
	}

	public function get_items( $fragment = Null ) {
		if ( !$fragment ) {
			return $this->options;
		}
		$ret = array();
		foreach ( $this->options as $k => $v ) {
			if ( strpos( $v, $fragment ) !== False ) $ret[$k] = $v;
		}
		return $ret;
	}

	public function get_ajax_action() {
		return 'fm_datasource_' . $this->ajax_idx;
	}

	/**
	 * AJAX callback to find posts
	 */
	public function autocomplete_search() {
		// Check the nonce before we do anything
		check_ajax_referer( 'fm_search_nonce', 'fm_search_nonce' );
		$items = $this->get_items( sanitize_text_field( $_POST['fm_autocomplete_search'] ) );

		// See if any results were returned and return them as an array
		if ( !empty( $items ) ) {
			echo json_encode( $items ); exit;
		} else {
			echo "0";
		}

		die();
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