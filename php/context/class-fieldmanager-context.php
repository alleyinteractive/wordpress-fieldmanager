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
	 * @var array {
	 *     Callback functions for data storage manipulation.
	 *
	 *     @type string $get Callback to get data.
	 *     @type string $add Callback to add data.
	 *     @type string $update Callback to update data.
	 *     @type string $delete Callback to delete data.
	 * }
	 */
	public $data_callbacks;

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
	protected function _render_field( $args = array() ) {
		$data = array_key_exists( 'data', $args ) ? $args['data'] : $this->_load();
		$echo = array_key_exists( 'echo', $args ) ? $args['echo'] : true;

		$nonce = wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce', true, false );
		$field = $this->fm->element_markup( $data );
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
	 */
	protected function _save( $data = null ) {
		if ( $this->fm->serialize_data ) {
			$this->_save_field( $this->fm, $data );
		} else {
			$this->_save_walk_children( $this->fm, $data );
		}
	}

	protected function _save_field( $field, $data ) {
		$current = call_user_func( $this->data_callbacks['get'], $field->data_id, $field->get_element_key(), $field->serialize_data );
		$data = $this->_prepare_data( $current, $data );
		if ( ! $field->skip_save ) {
			if ( $field->serialize_data ) {
				call_user_func( $this->data_callbacks['update'], $field->data_id, $field->get_element_key(), $data );
			} else {
				call_user_func( $this->data_callbacks['delete'], $field->data_id, $field->get_element_key() );
				foreach ( $data as $value ) {
					call_user_func( $this->data_callbacks['add'], $field->data_id, $field->get_element_key(), $value );
				}
			}
		}
	}

	protected function _save_walk_children( $field, $data ) {
		if ( $field->serialize_data || 'group' != $field->field_class ) {
			$this->_save_field( $field, $data );
		} else {
			foreach ( $field->children as $child ) {
				if ( isset( $data[ $child->name ] ) ) {
					$this->_save_walk_children( $child, $data[ $child->name ] );
				}
			}
		}
	}


	/**
	 * Handle loading data for any context.
	 */
	protected function _load() {
		if ( $this->fm->serialize_data ) {
			return $this->_load_field( $this->fm );
		} else {
			return $this->_load_walk_children( $this->fm );
		}
	}

	protected function _load_field( $field ) {
		$data = call_user_func( $this->data_callbacks['get'], $field->data_id, $field->get_element_key() );
		if ( $field->serialize_data ) {
			return empty( $data ) ? null : reset( $data );
		} else {
			return $data;
		}
	}

	protected function _load_walk_children( $field ) {
		if ( $field->serialize_data || 'group' != $field->field_class ) {
			return $this->_load_field( $field );
		} else {
			$return = array();
			foreach ( $field->children as $child ) {
				$return[ $child->name ] = $this->_load_walk_children( $child );
			}
			return $return;
		}
	}

}