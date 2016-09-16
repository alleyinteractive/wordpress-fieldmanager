<?php

/**
 * Use fieldmanager to create settings in the media modal attachment details sidebar.
 *
 * @package Fieldmanager_Context
 */
class Fieldmanager_Context_Media extends Fieldmanager_Context {
	/**
	 * Base field.
	 *
	 * @var Fieldmanager_Field
	 */
	public $fm = null;

	/**
	 * Title of the attachment field.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Description of the attachment field.
	 *
	 * @var string
	 */
	public $description = '';

	/**
	 * Add a context to a fieldmanager.
	 *
	 * @param string             $title       The attachment field title.
	 * @param string             $description The attachment field description.
	 * @param Fieldmanager_Field $fm          The Fieldmanager object.
	 */
	public function __construct( $title, $description, $fm = null ) {
		$this->fm = $fm;
		$this->title = $title;
		$this->description = $description;

		add_filter( 'attachment_fields_to_edit', array( $this, 'add_settings' ), 10, 2 );
		// add_action( 'edit_attachment', array( $this, 'save_settings' ) );
	}

	/**
	 * Add the attachment settings.
	 *
	 * @param array   $form_fields The current array of fields.
	 * @param WP_Post $post        The current post object.
	 */
	public function add_settings( $form_fields, $post ) {
		// Get the current value.
		$field_value = $this->get_data( $post->ID, 'fm_attachment_field_' . $this->fm->name, true );

		// Add the field to the form fields array.
		$form_fields[ 'fm_attachment_field_' . $this->fm->name ] = array(
			'value' => $field_value ? $field_value : '',
			'label' => $this->title,
			'helps' => $this->description,
		);

		return $form_fields;
	}

	/**
	 * Get post meta.
	 *
	 * @see get_post_meta().
	 */
	protected function get_data( $post_id, $meta_key, $single = false ) {
		return get_post_meta( $post_id, $meta_key, $single );
	}

	/**
	 * Add post meta.
	 *
	 * @see add_post_meta().
	 */
	protected function add_data( $post_id, $meta_key, $meta_value, $unique = false ) {
		return add_post_meta( $post_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update post meta.
	 *
	 * @see update_post_meta().
	 */
	protected function update_data( $post_id, $meta_key, $meta_value, $data_prev_value = '' ) {
		return update_post_meta( $post_id, $meta_key, $meta_value, $data_prev_value );
	}

	/**
	 * Delete post meta.
	 *
	 * @see delete_post_meta().
	 */
	protected function delete_data( $post_id, $meta_key, $meta_value = '' ) {
		return delete_post_meta( $post_id, $meta_key, $meta_value );
	}
}
