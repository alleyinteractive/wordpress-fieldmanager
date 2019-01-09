<?php
/**
 * Class file for Fieldmanager_Datasource_User
 *
 * @package Fieldmanager
 */

/**
 * Datasource to populate autocomplete and option fields with WordPress Users.
 */
class Fieldmanager_Datasource_User extends Fieldmanager_Datasource {

	/**
	 * Supply a function which returns a list of users; takes one argument,
	 * a possible fragment.
	 *
	 * @var callable
	 */
	public $query_callback = null;

	/**
	 * Arguments to get_users(), which uses WP's defaults.
	 *
	 * @see http://codex.wordpress.org/Template_Tags/get_users
	 *
	 * @var array
	 */
	public $query_args = array();

	/**
	 * Allow Ajax. If set to false, Autocomplete will pre-load get_items() with no fragment,
	 * so False could cause performance problems.
	 *
	 * @var bool
	 */
	public $use_ajax = true;

	/**
	 * Display property. Defaults to display_name, but can also be 'user_login', 'user_email',
	 * or 'user_nicename'.
	 *
	 * @var string
	 */
	public $display_property = 'display_name';

	/**
	 * Allowed display properties for validation.
	 *
	 * @var array
	 */
	protected $allowed_display_properties = array( 'display_name', 'user_login', 'user_email', 'user_nicename' );

	/**
	 * Store property. Defaults to ID, but can also be 'user_login', 'user_email',
	 * or 'user_nicename'.
	 *
	 * @var string
	 */
	public $store_property = 'ID';

	/**
	 * Allowed store properties for validation.
	 *
	 * @var array
	 */
	protected $allowed_store_properties = array( 'ID', 'user_login', 'user_email', 'user_nicename' );

	/**
	 * Capability required to refer to a user via this datasource.
	 *
	 * @see http://codex.wordpress.org/Roles_and_Capabilities
	 *
	 * @var string
	 */
	public $capability = 'list_users';

	/**
	 * If not empty, set this object's ID as a value on the user. This is used to
	 * establish two-way relationships.
	 *
	 * @var string
	 */
	public $reciprocal = null;

	/**
	 * Constructor. Used for validation.
	 *
	 * @throws FM_Developer_Exception Cannot use reciprocal relationships.
	 *
	 * @param array $options The datasource option.
	 */
	public function __construct( $options = array() ) {
		parent::__construct( $options );

		// Validate improper usage of store property.
		if ( ! in_array( $this->store_property, $this->allowed_store_properties ) ) {
			throw new FM_Developer_Exception(
				sprintf(
					/* translators: 1: stored property, 2: allowed store properties */
					__( 'Store property %1$s is invalid. Must be one of %2$s.', 'fieldmanager' ),
					$this->store_property,
					implode( ', ', $this->allowed_store_properties )
				)
			);
		}

		if ( ! empty( $this->reciprocal ) && 'ID' != $this->store_property ) {
			throw new FM_Developer_Exception( __( 'You cannot use reciprocal relationships with FM_Datasource_User if store_property is not set to ID.', 'fieldmanager' ) );
		}

		// Validate improper usage of display property.
		if ( ! in_array( $this->display_property, $this->allowed_display_properties ) ) {
			throw new FM_Developer_Exception(
				sprintf(
					/* translators: 1: display property, 2: allowed display properties */
					__( 'Display property %1$s is invalid. Must be one of %2$s.', 'fieldmanager' ),
					$this->display_property,
					implode( ', ', $this->allowed_display_properties )
				)
			);
		}
	}

	/**
	 * Get a user by the specified field.
	 *
	 * @param int $value Post ID.
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

		// Sanitize the value.
		$value = $this->sanitize_value( $value );

		$user = get_user_by( $field, $value );
		return $user ? $user->{$this->display_property} : '';
	}

	/**
	 * Get users which match this datasource, optionally filtered by
	 * a search fragment, e.g. for Autocomplete.
	 *
	 * @param string $fragment The search string.
	 * @return array post_id => post_title for display or Ajax.
	 */
	public function get_items( $fragment = null ) {
		if ( is_callable( $this->query_callback ) ) {
			return call_user_func( $this->query_callback, $fragment );
		}

		$default_args = array();
		$user_args    = array_merge( $default_args, $this->query_args );
		$ret          = array();

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
	 *
	 * @return string Ajax action.
	 */
	public function get_ajax_action() {
		if ( ! empty( $this->ajax_action ) ) {
			return $this->ajax_action;
		}
		$unique_key  = wp_json_encode( $this->query_args );
		$unique_key .= $this->display_property;
		$unique_key .= (string) $this->query_callback;
		return 'fm_datasource_post' . crc32( $unique_key );
	}

	/**
	 * Delete reciprocal user metadata prior to saving (presave will re-add).
	 * Reciprocal relationships are not possible if we are not storing by ID.
	 *
	 * @param Fieldmanager_Field $field          The current field.
	 * @param array              $values         The new values.
	 * @param array              $current_values The existing values.
	 * @return string Sanitized values.
	 */
	public function presave_alter_values( Fieldmanager_Field $field, $values, $current_values ) {
		if ( 'post' != $field->data_type || ! $this->reciprocal || 'ID' != $this->store_property ) {
			return $values;
		}

		if ( ! empty( $current_values ) ) {
			foreach ( $current_values as $user_id ) {
				call_user_func(
					/**
					 * Filters function used to delete user meta. This improves compatibility with
					 * WordPress.com.
					 *
					 * @see delete_user_meta() for more details about each param.
					 *
					 * @param string $function_name The function to call to get user
					 *                              data. Default is 'delete_user_meta'.
					 * @param int $user_id User ID.
					 * @param string $meta_key Meta key to retrieve.
					 * @param mixed $meta_value Only delete if the current value matches.
					 */
					apply_filters( 'fm_user_context_delete_data', 'delete_user_meta' ),
					$user_id,
					$this->reciprocal,
					$field->data_id
				);
			}
		}

		return $values;
	}

	/**
	 * Handle reciprocal usermeta.
	 * Reciprocal relationships are not possible if we are not storing by ID.
	 *
	 * @param  Fieldmanager_Field $field         The current field.
	 * @param  array              $value         The new value.
	 * @param  array              $current_value The existing value.
	 * @return string Sanitized value.
	 */
	public function presave( Fieldmanager_Field $field, $value, $current_value ) {
		if ( empty( $value ) ) {
			return;
		}

		$return_single = false;
		if ( ! is_array( $value ) ) {
			$return_single = true;
			$value         = array( $value );
		}

		foreach ( $value as $i => $v ) {
			$value[ $i ] = $this->sanitize_value( $v );
			if ( ! current_user_can( $this->capability, $v ) ) {
				/* translators: %s: user id */
				wp_die( esc_html( sprintf( __( 'Tried to refer to user "%s" which current user cannot edit.', 'fieldmanager' ), $v ) ) );
			}
			if ( $this->reciprocal && 'ID' == $this->store_property ) {
				call_user_func(
					/**
					 * Filters function used to add user meta. This improves compatibility with
					 * WordPress.com.
					 *
					 * @see add_user_meta() for more details about each param.
					 *
					 * @param string $function_name The function to call to get user data. Default
					 *                              is 'add_user_meta'.
					 * @param int    $user_id       User ID.
					 * @param string $meta_key      Meta key to add.
					 * @param mixed  $meta_value    The meta value to store.
					 * @param bool   $unique        If true, only add if key is unique. Default is
					 *                              false.
					 */
					apply_filters( 'fm_user_context_add_data', 'add_user_meta' ),
					$v,
					$this->reciprocal,
					$field->data_id,
					false
				);
			}
		}

		return $return_single ? $value[0] : $value;
	}

	/**
	 * Get view link for a user.
	 *
	 * @param int $value The current value.
	 * @return string HTML string.
	 */
	public function get_view_link( $value ) {
		return '';
	}

	/**
	 * Get edit link for a user.
	 *
	 * @param int $value The current value.
	 * @return string HTML string.
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
	 *
	 * @param  mixed $value The current value.
	 * @return mixed $value The sanitized value.
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
