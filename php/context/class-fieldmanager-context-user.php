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
	 * Group Title
	 * @var string
	 */
	public $title;

	/**
	 * Callbacks for manipulating data.
	 * @var array
	 */
	public $data_callbacks = array(
		'get'    => "get_user_meta",
		'add'    => "add_user_meta",
		'update' => "update_user_meta",
		'delete' => "delete_user_meta",
	);

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
	 * Render the form on the user profile page
	 * @return void
	 */
	public function render_user_form( $user ) {
		$this->fm->data_id = $user->ID;
		$this->fm->data_type = 'user';

		if ( !empty( $this->title ) ) {
			echo '<h3>' . esc_html( $this->title ) . '</h3>';
		}
		echo '<div class="fm-user-form-wrapper">';
		$this->render_field();
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
	 * @param  int $user_id
	 */
	public function save_to_user_meta( $user_id, $data = null ) {
		$this->fm->data_id = $user_id;
		$this->fm->data_type = 'user';

		$this->save( $data );
	}
}