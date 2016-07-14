<?php

/**
 * Datasource to populate autocomplete and option fields with WordPress Users.
 *
 * @package Fieldmanager_Datasource
 */
class Fieldmanager_Datasource_User extends Fieldmanager_Datasource {

    /**
     * Supply a function which returns a list of users; takes one argument,
     * a possible fragment
     */
    public $query_callback = Null;

    /**
     * Arguments to get_users(), which uses WP's defaults.
     * @see http://codex.wordpress.org/Template_Tags/get_users
     */
    public $query_args = array();

    /**
     * @var boolean
     * Allow AJAX. If set to false, Autocomplete will pre-load get_items() with no fragment,
     * so False could cause performance problems.
     */
    public $use_ajax = True;

    /**
     * @var string
     * Display property. Defaults to display_name, but can also be 'user_login', 'user_email',
     * or 'user_nicename'
     */
    public $display_property = 'display_name';

    /**
     * @var array
     * Allowed display properties for validation.
     */
    protected $allowed_display_properties = array( 'display_name', 'user_login', 'user_email', 'user_nicename' );

    /**
     * @var string
     * Store property. Defaults to ID, but can also be 'user_login', 'user_email',
     * or 'user_nicename'.
     */
    public $store_property = 'ID';

    /**
     * @var array
     * Allowed store properties for validation.
     */
    protected $allowed_store_properties = array( 'ID', 'user_login', 'user_email', 'user_nicename' );

    /**
     * @var string
     * Capability required to refer to a user via this datasource.
     * @see http://codex.wordpress.org/Roles_and_Capabilities
     */
    public $capability = 'list_users';

    /**
     * @var string|Null
     * If not empty, set this object's ID as a value on the user. This is used to
     * establish two-way relationships.
     */
    public $reciprocal = Null;

    /**
	 * Constructor. Used for validation.
	 */
	public function __construct( $options = array() ) {
		parent::__construct( $options );

		// Validate improper usage of store property
		if ( ! in_array( $this->store_property, $this->allowed_store_properties ) ) {
			throw new FM_Developer_Exception( sprintf(
				__( 'Store property %s is invalid. Must be one of %s.', 'fieldmanager' ),
				$this->store_property,
				implode( ', ', $this->allowed_store_properties )
			) );
		}

		if ( ! empty( $this->reciprocal ) && 'ID' != $this->store_property ) {
			throw new FM_Developer_Exception( __( 'You cannot use reciprocal relationships with FM_Datasource_User if store_property is not set to ID.', 'fieldmanager' ) );
		}

		// Validate improper usage of display property
		if ( ! in_array( $this->display_property, $this->allowed_display_properties ) ) {
			throw new FM_Developer_Exception( sprintf(
				__( 'Display property %s is invalid. Must be one of %s.', 'fieldmanager' ),
				$this->display_property,
				implode( ', ', $this->allowed_display_properties )
			) );
		}
	}

    /**
     * Get a user by the specified field.
     * @param int $value post_id
     * @return int|string
     */
    public function get_value( $value ) {
    	switch ( $this->store_property ) {
			case 'ID':
				$field = 'id';
				break;
			case 'user_nicename':
				$field = 'slug';
				break;
			case 'user_email':
				$field = 'email';
				break;
			case 'user_login':
				$field = 'login';
				break;
    	}

		// Sanitize the value
		$value = $this->sanitize_value( $value );

		$user = get_user_by( $field, $value );
		return $user ? $user->{$this->display_property} : '';
    }

    /**
     * Get users which match this datasource, optionally filtered by
     * a search fragment, e.g. for Autocomplete.
     * @param string $fragment
     * @return array post_id => post_title for display or AJAX
     */
    public function get_items( $fragment = Null ) {
        if ( is_callable( $this->query_callback ) ) {
            return call_user_func( $this->query_callback, $fragment );
        }

        $default_args = array();
        $user_args = array_merge( $default_args, $this->query_args );
        $ret = array();

        if ( $fragment ) {
        	$user_args['search'] = '*' . $fragment . '*';
        }

        $users = get_users( $user_args );
        foreach ( $users as $u ) {
            $ret[ $u->{$this->store_property} ] = $u->{$this->display_property};
        }

        return $ret;
    }

    /**
     * Get an action to register by hashing (non cryptographically for speed)
     * the options that make this datasource unique.
     * @return string ajax action
     */
    public function get_ajax_action() {
        if ( !empty( $this->ajax_action ) ) return $this->ajax_action;
        $unique_key = json_encode( $this->query_args );
        $unique_key .= $this->display_property;
        $unique_key .= (string) $this->query_callback;
        return 'fm_datasource_post' . crc32( $unique_key );
    }

    /**
     * Delete reciprocal user metadata prior to saving (presave will re-add).
     * Reciprocal relationships are not possible if we are not storing by ID.
     * @param array $values new post values
     * @param array $current_values existing post values
     */
    public function presave_alter_values( Fieldmanager_Field $field, $values, $current_values ) {
		if ( $field->data_type != 'post' || ! $this->reciprocal || 'ID' != $this->store_property ) {
			return $values;
		}

		if ( ! empty( $current_values ) ) {
			foreach ( $current_values as $user_id ) {
				delete_user_meta( $user_id, $this->reciprocal, $field->data_id );
			}
		}

        return $values;
    }

    /**
     * Handle reciprocal usermeta.
     * Reciprocal relationships are not possible if we are not storing by ID.
     * @param int $value
     * @return string
     */
    public function presave( Fieldmanager_Field $field, $value, $current_value ) {
        if ( empty( $value ) ) {
        	return;
        }

        $return_single = False;
        if ( !is_array( $value ) ) {
            $return_single = True;
            $value = array( $value );
        }

        foreach ( $value as $i => $v ) {
            $value[$i] = $this->sanitize_value( $v );
            if( ! current_user_can( $this->capability, $v ) ) {
                wp_die( esc_html( sprintf( __( 'Tried to refer to user "%s" which current user cannot edit.', 'fieldmanager' ), $v ) ) );
            }
            if ( $this->reciprocal && 'ID' == $this->store_property ) {
                add_user_meta( $v, $this->reciprocal, $field->data_id );
            }
        }

        return $return_single ? $value[0] : $value;
    }

    /**
     * Get view link for a user.
     * @param int $value
     * @return string
     */
    public function get_view_link( $value ) {
        return '';
    }

    /**
     * Get edit link for a user.
     * @param int $value
     * @return string
     */
    public function get_edit_link( $value ) {
        return sprintf(
            ' <a target="_new" class="fm-autocomplete-edit-link %s" href="%s">%s</a>',
            empty( $value ) ? 'fm-hidden' : '',
            empty( $value ) ? '#' : esc_url( get_edit_user_link( $value ) ),
            esc_html__( 'Edit', 'fieldmanager' )
        );
    }

    /**
     * Sanitize the value based on store_property.
     * @param int|string $value
     * @return int|string
     */
    protected function sanitize_value( $value ) {
    	switch ( $this->store_property ) {
		case 'ID':
			$value = intval( $value );
			break;
		case 'user_email':
			$value = sanitize_email( $value );
			break;
		default:
			$value = sanitize_text_field( $value );
			break;
    	}

    	return $value;
    }
}
