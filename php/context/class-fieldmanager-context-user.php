<?php
/**
 * @package Fieldmanager_Context
 */

/**
 * Use fieldmanager on user management forms
 * @package Fieldmanager_Datasource
 */
class Fieldmanager_Context_User extends Fieldmanager_Context {

	/**
	 * @var string
	 * Group Title
	 */
	public $title;

	/**
	 * Add fieldmanager to user form
	 * @param string $title
	 * @param Fieldmanager_Field $fm
	 */
	public function __construct( $title = '', $fm = null ) {
		$this->title = $title;
		$this->fm = $fm;
		add_action( 'show_user_profile', array( $this, 'render_user_form' ) );
		add_action( 'edit_user_profile', array( $this, 'render_user_form' ) );
		add_action( 'personal_options_update', array( $this, 'save_user_form' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_form' ) );
	}

	/**
	 * Render the form on the user profile page
	 * @return void
	 */
	public function render_user_form( $user ) {
		$values = get_user_meta( $user->ID, $this->fm->name );
		$values = empty( $values ) ? null : $values[0];
		if ( !empty( $this->title ) ) echo '<h3>' . $this->title . '</h3>';
		echo '<div class="fm-user-form-wrapper">';
		wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce' );
		echo $this->fm->element_markup( $values );
		echo '</div>';

		// Check if any validation is required
		$fm_validation = Fieldmanager_Util_Validation( 'your-profile', 'user' );
		$fm_validation->add_field( $this->fm );
	}

	/**
	 * Save user form.
	 *
	 * @param int $user_id
	 */
	public function save_user_form( $user_id ) {
		if ( ! empty( $_POST ) && ! empty( $_POST['fieldmanager-' . $this->fm->name . '-nonce'] ) && current_user_can( 'edit_user', $user_id ) ) {
			// Make sure that our nonce field arrived intact
			if ( ! wp_verify_nonce( $_POST['fieldmanager-' . $this->fm->name . '-nonce'], 'fieldmanager-save-' . $this->fm->name ) ) {
				$this->fm->_unauthorized_access( __( 'Nonce validation failed', 'fieldmanager' ) );
			}

			$this->save_to_user_meta( $user_id, ( isset( $_POST[ $this->fm->name ] ) ? $_POST[ $this->fm->name ] : "" ) );
		}
	}

	/**
	 * Save data to user meta.
	 *
	 * @param  int $user_id
	 */
	public function save_to_user_meta( $user_id, $value ) {
		$this->fm->data_id = $user_id;
		$this->fm->data_type = 'user';
		$current = get_user_meta( $user_id, $this->fm->name, true );
		$data = $this->fm->presave_all( $value, $current );
		$data = apply_filters( 'fm_user_presave_data', $data, $this->fm );
		update_user_meta( $user_id, $this->fm->name, $data );
	}
}