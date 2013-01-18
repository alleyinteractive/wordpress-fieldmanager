<?php
class Fieldmanager_MediaAttachment extends Fieldmanager_Field {

	/**
	 * @var string
	 * Override field_class
	 */
	public $field_class = 'media';

	/**
	 * Override constructor to set default size.
	 * @param array $options
	 */
	public function __construct( $options = array() ) {
		$this->attributes = array(
			'width' => '200'
		);
		
		//New 3.5 Media Uploader Enqueue
		wp_enqueue_media();

		//js and css for dealing with new uploader
		fm_add_script( 'fm_media_js', 'js/fieldmanager-media.js', array(), false, false, 'fm_media', array( 'nonce' => wp_create_nonce( 'fm_media_nonce' ) ) );

		//Action for adding attachment to post
		add_action( 'fm-post-save-field', array($this, 'fm_media_save'), 10, 3 );

		parent::__construct($options);
	}


	/**
	 * Output HTML for the media attachment meta-box.
	 * @param string url for media item
	 * @return string html
	 */
	function form_element( $value = '' ) {
		if ( isset($value['url']) ){
			$url = $value['url'];
		} else {
			$url = $value;
		}
		return sprintf('
			<div class="fm-media-uploader">
				URL: <input class="fm-element" type="text" name="%s" id="%s" value="%s" /><br>
				<img id="%s_thumb" src="%s" %s /><br>
				<a href="#" class="fm-media-add-link" id="%s_button" />Insert New '. $this->label .'</a><br>
			</div>',
			$this->get_form_name(),
			$this->get_element_id(),
			htmlspecialchars( $url ),
			$this->get_element_id(),
			htmlspecialchars( $url ),
			$this->get_element_attributes(),			
			$this->get_element_id()
		);
	}

	/**
	 * fm_media_save
	 * Hook for saving media items as post attachments
	 * @param int post id
	 * @param object field manager group object
 	 * @param array media urls
	 * @return array of attached ids 
	 */
	public function fm_media_save( $post_id, $fm_obj, $data ) {
		
		$attach_ids = array();

		if ( ('group' == $fm_obj->field_class ) ) {
			foreach ( $fm_obj->children as $fm_child ) {
				if ( 'media' == $fm_child->field_class ) {
					$current = array( 'post_parent' => $post_id, 'post_type' => 'attachment' );
					$current_attachments = get_children( $current );
						

					foreach ( $data as $fm_name => $image_data ) {

						if( !isset( $image_data['url'] ) && preg_match( '/^http/', $image_data ) ) {
							$url = $image_data;
							$image_data = array( 
								'url' => $url,
								'attachment_id' => ''
							);
						}
	
						if ( isset( $image_data['url'] ) ){
							//Since we need absolute paths for attachments, manipulate the url
							$filename = ABSPATH.str_replace(get_site_url().'/','',$image_data['url']);
							$wp_filetype = wp_check_filetype( basename( $filename ), null );
					  		$wp_upload_dir = wp_upload_dir();
					  		$attachment = array(
					     		'guid' => $image_data['url'], 
					     		'post_mime_type' => $wp_filetype['type'],
					     		'post_title' => $fm_name,
					     		'post_content' => '',
					     		'post_status' => 'inherit'
					  		);

					  		foreach ( $current_attachments as $attachment_id => $attachment ) {
								if ( $attachment->post_title == $fm_name ) {
									$attach_id = $attachment_id;
								}
							}

							if ( isset( $attach_id ) ){
								update_attached_file( $attach_id, $filename );
								//Typically we don't touch the guid, but attachments use it to locate a file in the uploader and it is necessary.
								global $wpdb;
								$wpdb->query( $wpdb->prepare("UPDATE $wpdb->posts SET guid = %s WHERE ID = %d", $image_data['url'],$attach_id) );
							} else {
								$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
							}

							require_once( ABSPATH . 'wp-admin/includes/image.php' );
					  		$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
					  			
					  		wp_update_attachment_metadata( $attach_id, $attach_data );

					  		$new_data = array( 
					  			'url' => $image_data['url'],
					  			'attachment_id' => $attach_id
					  		);
					  		$data[$fm_name] = $new_data;
							
					  		update_post_meta( $post_id, $fm_obj->name, str_replace( "\\'", "'", json_encode( $data ) ) );

	  						$attach_ids[]= $attach_id;	
  						}
					}
				}
			}
		}
		return $attach_ids;
	}	
}