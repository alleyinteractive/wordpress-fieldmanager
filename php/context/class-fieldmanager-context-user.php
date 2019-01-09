<?php
/**
 * Class file for Fieldmanager_Context_User
 *
 * @package Fieldmanager
 */

/**
 * Use fieldmanager on the user profile screen and save data primarily to user
 * meta.
 */
class Fieldmanager_Context_User extends Fieldmanager_Context_Storable {

	/**
	 * Group Title.
	 *
	 * @var string
	 */
	public $title;

	/**
	 * Add fieldmanager to user form.
	 *
	 * @param string             $title The form title.
	 * @param Fieldmanager_Field $fm    The base field.
	 */
	public function __construct( $title = '', $fm = null ) {
		$this->title = $title;
		$this->fm    = $fm;
		add_action( 'show_user_profile', array( $this, 'render_user_form' ) );
		add_action( 'edit_user_profile', array( $this, 'render_user_form' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_form' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_form' ) );
		add_filter( 'fm_context_after_presave_data', array( $this, 'legacy_presave_filter' ) );
	}

	/**
	 * Maintain legacy support for custom filter.
	 *
	 * @deprecated
	 *
	 * @param  mixed $data Data being saved.
	 * @return mixed
	 */
	public function legacy_presave_filter( $data ) {
		return apply_filters( 'fm_user_presave_data', $data, $this->fm );
	}

	/**
	 * Render the form on the user profile page.
	 *
	 * @param WP_User $user The user.
	 */
	public function render_user_form( $user ) {
		$this->fm->data_id   = $user->ID;
		$this->fm->data_type = 'user';

		if ( ! empty( $this->title ) ) {
			echo '<h3>' . esc_html( $this->title ) . '</h3>';
		}
		echo '<div class="fm-user-form-wrapper">';
		$this->render_field();
		echo '</div>';

		// Check if any validation is required.
		$fm_validation = fieldmanager_util_validation( 'your-profile', 'user' );
		$fm_validation->add_field( $this->fm );
	}

	/**
	 * Save user form.
	 *
	 * @param int $user_id The user ID.
	 */
	public function save_user_form( $user_id ) {
		if ( ! $this->is_valid_nonce() ) {
			return;
		}

		if ( ! current_user_can( 'edit_user', $user_id ) ) {
			$this->fm->_unauthorized_access( __( 'Current user cannot edit this user', 'fieldmanager' ) );
			return;
		}

		$this->save_to_user_meta( $user_id );
	}

	/**
	 * Save data to user meta.
	 *
	 * @param int   $user_id The user ID.
	 * @param array $data    The user data.
	 */
	public function save_to_user_meta( $user_id, $data = null ) {
		$this->fm->data_id   = $user_id;
		$this->fm->data_type = 'user';

		$this->save( $data );
	}

	/**
	 * Get user meta.
	 *
	 * @see get_user_meta().
	 *
	 * @param int    $user_id  The User ID.
	 * @param string $meta_key The meta key for the user meta data.
	 * @param bool   $single   Only return a single value or all values.
	 */
	protected function get_data( $user_id, $meta_key, $single = false ) {
		return call_user_func(
			/**
			 * Filters function used to get user meta. This improves compatibility with
			 * WordPress.com.
			 *
			 * @see get_user_meta() for more details about each param.
			 *
			 * @param string $function_name The function to call to get user
			 *                              data. Default is 'get_user_meta'.
			 * @param int $user_id User ID.
			 * @param string $meta_key Meta key to retrieve.
			 * @param bool    $single Get single value (true) or array of values
			 *                        (false). Default is false.
			 */
			apply_filters( 'fm_user_context_get_data', 'get_user_meta' ),
			$user_id,
			$meta_key,
			$single
		);
	}

	/**
	 * Add user meta.
	 *
	 * @see add_user_meta().
	 *
	 * @param int    $user_id    The User ID.
	 * @param string $meta_key   The meta key for the user meta data.
	 * @param mixed  $meta_value The meta data.
	 * @param bool   $unique     Only save it to a single option.
	 */
	protected function add_data( $user_id, $meta_key, $meta_value, $unique = false ) {
		return call_user_func(
			/**
			 * Filters function used to add user meta. This improves compatibility with
			 * WordPress.com.
			 *
			 * @see add_user_meta() for more details about each param.
			 *
			 * @param string $function_name The function to call to get user
			 *                              data. Default is 'add_user_meta'.
			 * @param int $user_id User ID.
			 * @param string $meta_key Meta key to add.
			 * @param mixed $meta_value The meta value to store.
			 * @param bool    $unique If true, only add if key is unique.
			 *                        Default is false.
			 */
			apply_filters( 'fm_user_context_add_data', 'add_user_meta' ),
			$user_id,
			$meta_key,
			$meta_value,
			$unique
		);
	}

	/**
	 * Update user meta.
	 *
	 * @see update_user_meta().
	 *
	 * @param int    $user_id         The User ID.
	 * @param string $meta_key        The meta key for the user meta data.
	 * @param mixed  $meta_value      The meta data.
	 * @param mixed  $data_prev_value The previous meta data.
	 */
	protected function update_data( $user_id, $meta_key, $meta_value, $data_prev_value = '' ) {
		$meta_value = $this->sanitize_scalar_value( $meta_value );
		return call_user_func(
			/**
			 * Filters function used to update user meta. This improves compatibility with
			 * WordPress.com.
			 *
			 * @see update_user_meta() for more details about each param.
			 *
			 * @param string $function_name The function to call to get user
			 *                              data. Default is 'update_user_meta'.
			 * @param int $user_id User ID.
			 * @param string $meta_key Meta key to update.
			 * @param mixed $meta_value The meta value to store.
			 * @param mixed $data_prev_value Only update if the previous value
			 *                               matches $data_prev_value.
			 */
			apply_filters( 'fm_user_context_update_data', 'update_user_meta' ),
			$user_id,
			$meta_key,
			$meta_value,
			$data_prev_value
		);
	}

	/**
	 * Delete user meta.
	 *
	 * @see delete_user_meta().
	 *
	 * @param int    $user_id         The User ID.
	 * @param string $meta_key        The meta key for the user meta data.
	 * @param mixed  $meta_value      The meta data.
	 */
	protected function delete_data( $user_id, $meta_key, $meta_value = '' ) {
		return call_user_func(
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
			$meta_key,
			$meta_value
		);
	}
}
