<?php
/**
 * @package Fieldmanager
 */

/**
 * Text field. A good basic implementation guide, too.
 * @package Fieldmanager
 */
class Fieldmanager_File extends Fieldmanager_Field {

	/**
	 * @var string
	 * Override field_class
	 */
	public $field_class = 'file';

	/**
	 * @var string[]
	 * List of valid mime types for this upload
	 */
	public $valid_types = array(
		'image/gif',
		'image/jpeg',
		'image/png',
	);

	/**
	 * @var callable
	 * How to save the file. Defaults to Fieldmanager_File->save_attachment()
	 * but you could override this to process a file or do something completely novel.
	 */
	public $save_function = null;

	/**
	 * Override constructor to set default size.
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {

		$this->save_function = array( $this, 'save_attachment' );

		parent::__construct( $label, $options );
	}

	/**
	 * Presave, validates upload, moves file into place
	 * @param mixed[] $values
	 * @param mixed[] $current_values
	 * @return mixed[] sanitized values
	 */
	public function presave_all( $values, $current_values ) {
		$ancestors = array();
		foreach ( $this->get_form_tree() as $p ) {
			$ancestors[] = $p->name;
		}

		$base_name = array_shift( $ancestors );
		if ( ! empty( $_FILES[ $base_name ]['name'] ) ) {
			$file_keys = array( 'name', 'type', 'tmp_name', 'error', 'size' );
			foreach ( $file_keys as $key ) {
				$property = $_FILES[ $base_name ][ $key ];
				foreach ( $ancestors as $a ) {
					if ( ! empty( $property[ $a ] ) ) {
						$property = $property[ $a ];
					}
				}
				if ( is_array( $property ) ) {
					 unset( $property['proto'] );
					 foreach ( $property as $i => $val ) {
					 	$values[ $i ][ $key ] = $val;
					 }
				} else {
					$values[ $key ] = $property;
				}
			}
		}
		if ( 1 !== $this->limit ) {
			ksort( $values );
		}
		return parent::presave_all( $values, $current_values );
	}

	/**
	 * Override presave to move file into place
	 * @param mixed $value If a single field expects to manage an array, it must override presave()
	 * @return sanitized values.
	 */
	public function presave( $value, $current_value = null ) {

		if ( empty( $value['tmp_name'] ) ) {
			if ( is_array( $value ) && !empty( $value['saved'] ) ) {
				return intval( $value['saved'] );
			}
			return false; // no upload, stop processing
		}

		if ( !in_array( $value['type'], $this->valid_types ) ) {
			$this->_failed_validation( 'This file is of an invalid type' );
		}
		return call_user_func_array( $this->save_function, array( $this->name, $value ) );
	}

	/**
	 * Return name for hidden form element
	 */
	public function get_form_saved_name() {
		return $this->get_form_name() . '[saved]';
	}

	/**
	 * Save the uploaded file to the media library.
	 * @param string $filename name of the file on disk
	 * @param mixed[] $file_struct from $_FILES array
	 * @return int attachment ID
	 */
	public function save_attachment( $fieldname, $file_struct ) {
		global $post;

		require_once ABSPATH . 'wp-admin/includes/admin.php';

		$post_id = 0;
		if ( ! is_object( $post ) && ! empty( $post->ID ) ) {
			$post_id = $post->ID;
		}

		$filename = sanitize_text_field( $file_struct['name'] );
		$attach_id = media_handle_sideload( $file_struct, $post_id );

		return $attach_id;
	}

}