<?php

/**
 * Use fieldmanager to create settings in the media modal attachment details sidebar.
 *
 * @package Fieldmanager_Context
 */
class Fieldmanager_Context_Media_Modal extends Fieldmanager_Context {
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
	 * Add a context to a fieldmanager.
	 *
	 * @param string             $title The attachment field title.
	 * @param Fieldmanager_Field $fm    The Fieldmanager object.
	 */
	public function __construct( $title, $fm = null ) {
		$this->fm = $fm;
		$this->title = $title;

		add_filter( 'attachment_fields_to_edit', array( $this, 'add_settings' ), 10, 2 );
		add_action( 'edit_attachment', array( $this, 'save_settings' ) );
	}

	/**
	 * Add the attachment settings.
	 *
	 * @param array   $form_fields The current array of fields.
	 * @param WP_Post $post        The current post object.
	 */
	public function my_add_attachment_location_field( $form_fields, $post ) {
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
}
