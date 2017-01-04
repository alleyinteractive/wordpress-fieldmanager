<?php
/**
 * The Media context.
 *
 * @package PHP / Context
 */

/**
 * Use fieldmanager to create settings in the media modal attachment details sidebar.
 *
 * @package Fieldmanager_Context
 */
class Fieldmanager_Context_Media extends Fieldmanager_Context_Storable {
	/**
	 * Base field.
	 *
	 * @var Fieldmanager_Field
	 */
	public $fm = null;

	/**
	 * Add a context to a fieldmanager.
	 *
	 * @param Fieldmanager_Field $fm The Fieldmanager object.
	 */
	public function __construct( $fm = null ) {
		$this->fm = $fm;

		add_filter( 'attachment_fields_to_edit', array( $this, 'add_settings' ), 10, 2 );
		add_action( 'edit_attachment', array( $this, 'save_settings' ) );
	}

	/**
	 * Add the attachment settings.
	 *
	 * @param array   $form_fields The current array of fields.
	 * @param WP_Post $post        The current post object.
	 */
	public function add_settings( $form_fields, $post ) {
		// Get the current value.
		$this->fm->data_id = $post->ID;
		$this->fm->data_type = 'post';
		$field_value = $this->load();

		// Add the field to the form fields array.
		$form_fields[ 'fm_attachment_field_' . $this->fm->name ] = array(
			'input' => 'html',
			'html'  => $this->render_field( array( 'echo' => false ) ),
			'label' => $this->fm->label,
			'helps' => $this->fm->description,
		);

		return $form_fields;
	}

	/**
	 * Save the attachment settings.
	 *
	 * @param  int $post_id The attachment ID.
	 */
	public function save_settings( $post_id ) {
		if ( ! empty( $_REQUEST[ $this->fm->name ] ) ) {
			$this->fm->data_id = $post_id;
			$this->fm->data_type = 'post';

			$this->save( $_REQUEST[ $this->fm->name ] );
		}
	}

	/**
	 * Get post meta.
	 *
	 * @see get_post_meta().
	 *
	 * @param  int     $post_id  The post ID.
	 * @param  string  $meta_key The meta key.
	 * @param  boolean $single   Just return one value.
	 * @return mixed             The value.
	 */
	protected function get_data( $post_id, $meta_key, $single = false ) {
		return get_post_meta( $post_id, $meta_key, $single );
	}

	/**
	 * Add post meta.
	 *
	 * @see add_post_meta().
	 *
	 * @param  int    $post_id    The post ID.
	 * @param  string $meta_key   The meta key.
	 * @param  mixed  $meta_value The meta value.
	 * @param  bool   $unique     Only have one value.
	 * @return bool
	 */
	protected function add_data( $post_id, $meta_key, $meta_value, $unique = false ) {
		return add_post_meta( $post_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update post meta.
	 *
	 * @see update_post_meta().
	 *
	 * @param  int    $post_id         The post ID.
	 * @param  string $meta_key        The meta key.
	 * @param  mixed  $meta_value      The meta value.
	 * @param  mixed  $data_prev_value The previous meta value.
	 * @return bool
	 */
	protected function update_data( $post_id, $meta_key, $meta_value, $data_prev_value = '' ) {
		return update_post_meta( $post_id, $meta_key, $meta_value, $data_prev_value );
	}

	/**
	 * Delete post meta.
	 *
	 * @see delete_post_meta().
	 *
	 * @param  int    $post_id    The post ID.
	 * @param  string $meta_key   The meta key.
	 * @param  mixed  $meta_value The meta value.
	 * @return bool
	 */
	protected function delete_data( $post_id, $meta_key, $meta_value = '' ) {
		return delete_post_meta( $post_id, $meta_key, $meta_value );
	}
}
