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
	protected function _save( $data, $callbacks = null ) {
		if ( ! $callbacks ) {
			$callbacks = array(
				'get'    => "get_{$this->fm->data_type}_meta",
				'add'    => "add_{$this->fm->data_type}_meta",
				'update' => "update_{$this->fm->data_type}_meta",
				'delete' => "delete_{$this->fm->data_type}_meta",
			);
		}

		$current = call_user_func( $callbacks['get'], $this->fm->data_id, $this->fm->name, true );

		$data = $this->fm->presave_all( $data, $current );
		$data = apply_filters( 'fm_' . $this->fm->data_type . '_presave_data', $data, $this->fm );

		if ( ! $this->fm->skip_save ) {
			call_user_func( $callbacks['update'], $this->fm->data_id, $this->fm->name, $data );
		}
	}
}