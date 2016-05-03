<?php

/**
 * Base class for contexts.
 *
 * Contexts dictate where fields appear, how they load data, and where they
 * save data.
 *
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

}
