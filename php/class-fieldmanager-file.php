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

	private $file_buffer = array();

	/**
	 * Override constructor to set default size.
	 * @param string $label
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {

		$this->save_function = array( $this, 'save_attachment' );

		parent::__construct( $label, $options );
	}

	public function presave_all( $values, $current_values ) {
		$ancestors = array();
		foreach ( $this->get_form_tree() as &$p ) {
			$ancestors[] = $p->name;
		}

		$values = array();
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
					 $i = -1; // use negative numbers to avoid possible ID conflict
					 foreach ( $property as $i => $val ) {
					 	$this->file_buffer[ $i ][ $key ] = $val;
					 }
				} else {
					$this->file_buffer[ $key ] = $val;
				}
			}
		}
		$values = array_keys( $this->file_buffer );
		parent::presave_all( $values, $current_values );
	}

	public function presave( $value, $current_value = null ) {
		$fstruct = $this->file_buffer[ $value ];

		if ( empty( $fstruct['tmp_name'] ) ) {
			if ( is_array( $value ) && !empty( $value['saved'] ) ) {
				return intval( $value['saved'] );
			}
			return false; // no upload, stop processing
		}

		if ( !in_array( $fstruct['type'], $this->valid_types ) ) {
			$this->_failed_validation( 'This file is of an invalid type' );
		}
		return call_user_func_array( $this->save_function, array( $this->name, $fstruct ) );
	}

	public function get_form_saved_name() {
		return $this->get_form_name() . '[saved]';
	}

	/**
	 * Save the uploaded file to the media library.
	 */
	public function save_attachment( $fieldname, $file_struct ) {
		$filename = sanitize_text_field( $file_struct['name'] );
		$file = wp_upload_bits(
			$filename,
			null, // deprecated
			file_get_contents( $file_struct['tmp_name'] )
		);

		$filetype = wp_check_filetype( $file['file'], null );

		// double check filetype
		if ( !in_array( $filetype['type'], $this->valid_types ) ) {
			unlink( $file['file'] );
			$this->_failed_validation( 'This file is of an invalid type' );
		}

		$attachment = array(
			'guid'           => $file['url'], 
			'post_mime_type' => $filetype['type'],
			'post_title'     => $filename,
			'post_content'   => '',
			'post_status'    => 'inherit'
		);

		// Insert the attachment.
		$attach_id = wp_insert_attachment( $attachment, $file['file'] );

		// Make sure that this file is included, as wp_generate_attachment_metadata() depends on it.
		require_once( ABSPATH . 'wp-admin/includes/image.php' );

		// Generate the metadata for the attachment, and update the database record.
		$attach_data = wp_generate_attachment_metadata( $attach_id, $file['file'] );
		wp_update_attachment_metadata( $attach_id, $attach_data );
		return $attach_id;
	}

}