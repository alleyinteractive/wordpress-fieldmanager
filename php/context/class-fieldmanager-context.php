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
	 * Check if the nonce is valid. Returns false if the nonce is missing and
	 * throws an exception if it's invalid. If all goes well, returns true.
	 *
	 * @return boolean
	 */
	protected function _is_valid_nonce() {
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
	 * @return mixed The filtered and sanitized value, safe to save.
	 */
	protected function _prepare_data( $old_value = null, $new_value = null ) {
		if ( null === $new_value ) {
			$new_value = isset( $_POST[ $this->fm->name ] ) ? $_POST[ $this->fm->name ] : "";
		}
		$new_value = apply_filters( "fm_context_before_presave_data", $new_value, $old_value, $this );
		$data = $this->fm->presave_all( $new_value, $old_value );
		return apply_filters( "fm_context_after_presave_data", $data, $old_value, $this );
	}


	/**
	 * Render the field.
	 *
	 * @param  mixed $values Optional. The existing data to display with the
	 *                       field.
	 * @param  boolean $echo Optional. Output the data or return it. Defaults
	 *                       to true (to output the data).
	 * @return string if $echo = false.
	 */
	protected function _render_field( $values = null, $echo = true ) {
		$nonce = wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce', true, false );
		$field = $this->fm->element_markup( $values );
		if ( $echo ) {
			echo $nonce . $field;
		} else {
			return $nonce . $field;
		}
	}


	/**
	 * Handle saving data for any context.
	 *
	 * @param mixed $data Data to save. Should be raw, e.g. POST data.
	 * @param array $callbacks {
	 *     Callback functions for data storage manipulation.
	 *
	 *     @type string $get Callback to get data.
	 *     @type string $add Callback to add data.
	 *     @type string $update Callback to update data.
	 *     @type string $delete Callback to delete data.
	 * }
	 */
	protected function _save( $data = null, $callbacks = null ) {
		if ( ! $callbacks ) {
			$callbacks = array(
				'get'    => "get_{$this->fm->data_type}_meta",
				'add'    => "add_{$this->fm->data_type}_meta",
				'update' => "update_{$this->fm->data_type}_meta",
				'delete' => "delete_{$this->fm->data_type}_meta",
			);
		}

		$current = call_user_func( $callbacks['get'], $this->fm->data_id, $this->fm->name, true );

		// if $data is null, this will get it from $_POST
		$data = $this->_prepare_data( $current, $data );

		if ( ! $this->fm->skip_save ) {
			call_user_func( $callbacks['update'], $this->fm->data_id, $this->fm->name, $data );
		}
	}
}