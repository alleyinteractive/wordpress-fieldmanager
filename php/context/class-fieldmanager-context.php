<?php
/**
 * @package Fieldmanager_Context
 */

/**
 * Base class for context
 * @package Fieldmanager_Context
 */
abstract class Fieldmanager_Context {

	/**
	 * @var Fieldmanager_Field
	 * The base field associated with this context
	 */
	public $fm = Null;

	/**
	 * @var string
	 * Unique ID of the form. Used for forms that are not built into WordPress.
	 */
	public $uniqid;

	/**
	 * Store the meta keys this field saves to, to catch naming conflicts.
	 * @var array
	 */
	public $save_keys = array();

	/**
	 * Check if the nonce is valid. Returns false if the nonce is missing and
	 * throws an exception if it's invalid. If all goes well, returns true.
	 *
	 * @return boolean
	 */
	protected function is_valid_nonce() {
		if ( empty( $_POST['fieldmanager-' . $this->fm->name . '-nonce'] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( $_POST['fieldmanager-' . $this->fm->name . '-nonce'], 'fieldmanager-save-' . $this->fm->name ) ) {
			$this->fm->_unauthorized_access( __( 'Nonce validation failed', 'fieldmanager' ) );
		}

		return true;
	}


	/**
	 * Prepare the data for saving.
	 *
	 * @param  mixed $old_value Optional. The previous value.
	 * @param  mixed $new_value Optional. The new value for the field.
	 * @param  object $fm Optional. The Fieldmanager_Field to prepare.
	 * @return mixed The filtered and sanitized value, safe to save.
	 */
	protected function prepare_data( $old_value = null, $new_value = null, $fm = null ) {
		if ( null === $fm ) {
			$fm = $this->fm;
		}
		if ( null === $new_value ) {
			$new_value = isset( $_POST[ $this->fm->name ] ) ? $_POST[ $this->fm->name ] : '';
		}
		$new_value = apply_filters( "fm_context_before_presave_data", $new_value, $old_value, $this, $fm );
		$data = $fm->presave_all( $new_value, $old_value );
		return apply_filters( "fm_context_after_presave_data", $data, $old_value, $this, $fm );
	}


	/**
	 * Render the field.
	 *
	 * @param array $args {
	 *     Optional. Arguments to adjust the rendering behavior.
	 *
	 *     @type mixed $data The existing data to display with the field. If
	 *                       absent, data will be loaded using
	 *                       Fieldmanager_Context::_load().
	 *     @type boolean $echo Output if true, return if false. Default is true.
	 * }
	 * @return string if $args['echo'] == false.
	 */
	protected function render_field( $args = array() ) {
		$data = array_key_exists( 'data', $args ) ? $args['data'] : null;
		$echo = isset( $args['echo'] ) ? $args['echo'] : true;

		$nonce = wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce', true, false );
		$field = $this->fm->element_markup( $data );
		if ( $echo ) {
			echo $nonce . $field;
		} else {
			return $nonce . $field;
		}
	}

	/**
	 * Register a field for use with the REST API.
	 *
	 * @param  string|array $object_type Required. The object type in the REST API where this field will be available.
	 */
	public function register_rest_field( $object_type ) {
		// Ensure the REST API is active and the field wants to be shown in REST
		if ( function_exists( 'register_rest_field' ) && true === $this->fm->show_in_rest ) {
			register_rest_field(
				$object_type,
				$this->fm->name,
				array(
					'get_callback'    => array( $this, 'rest_get_callback' ),
					'update_callback' => array( $this, 'rest_update_callback' ),
					'schema'          => apply_filters( 'fm_rest_api_schema', $this->fm->get_schema(), $this ),
				)
			);
		}
	}

	/**
	 * Handles getting field data for the REST API.
	 * Needs to be implemented by each context.
	 *
	 * @param  array $object The REST API object.
	 * @param  string $field_name The REST API field name.
	 * @param  WP_REST_Request $request The full request object from the REST API.
	 * @param  string $object_type The REST API object type
	 */
	public function rest_get_callback( $object, $field_name, $request, $object_type ) {}

	/**
	 * Handles updating field data from the REST API.
	 * Needs to be implemented by each context.
	 *
	 * @param  mixed $value The value to be updated for the field from the request.
	 * @param  object $object The REST API object.
	 * @param  string $field_name The REST API field name.
	 * @param  WP_REST_Request $request The full request object from the REST API.
	 * @param  string $object_type The REST API object type
	 */
	public function rest_update_callback( $value, $object, $field_name, $request, $object_type ) {}

}
