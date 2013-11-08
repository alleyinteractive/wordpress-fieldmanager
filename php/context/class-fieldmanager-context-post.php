<?php
/**
 * @package Fieldmanager_Context
 */

/**
 * Use fieldmanager to create meta boxes on
 * @package Fieldmanager_Datasource
 */
class Fieldmanager_Context_Post extends Fieldmanager_Context {

	/**
	 * @var string
	 * Title of meta box
	 */
	public $title = '';

	/**
	 * @var string[]
	 * What post types to render this meta box
	 */
	public $post_types = array();

	/**
	 * @var string
	 * Context (normal, advanced, or side)
	 */
	public $context = 'normal';

	/**
	 * @var priority
	 * Priority (high, core, default, or low)
	 */
	public $priority = 'default';

	/**
	 * @var Fieldmanager_Group
	 * Base field
	 */
	public $fm = '';

	/**
	 * Add a context to a fieldmanager
	 * @param string $title
	 * @param string|string[] $post_types
	 * @param string $context (normal, advanced, or side)
	 * @param string $priority (high, core, default, or low)
	 * @param Fieldmanager_Field $fm
	 */
	public function __construct( $title, $post_types, $context = 'normal', $priority = 'default', $fm = Null ) {

		// Populate the list of post types for which to add this meta box with the given settings
		if ( !is_array( $post_types ) ) $post_types = array( $post_types );

		$this->post_types = $post_types;
		$this->title = $title;
		$this->context = $context;
		$this->priority = $priority;
		$this->fm = $fm;

		add_action( 'admin_init', array( $this, 'meta_box_render_callback' ) );
		// If this meta box is on an attachment page, add the appropriate filter hook to save the data
		if ( isset( $this->fm->is_attachment ) && $this->fm->is_attachment ) {
			add_filter( 'attachment_fields_to_save', array( $this, 'save_fields_for_attachment' ), 10, 2 );
		}
		add_action( 'save_post', array( $this, 'save_fields_for_post' ) );
		// Check if any meta boxes need to be removed
		if ( $this->fm && !empty( $this->fm->meta_boxes_to_remove ) ) {
			add_action( 'admin_init', array( $this, 'remove_meta_boxes' ) );
		}
	}

	/**
	 * admin_init callback to add meta boxes to content types
	 * Registers render_meta_box()
	 * @return void
	 */
	public function meta_box_render_callback() {
		foreach ( $this->post_types as $type ) {
			add_meta_box(
				'fm_meta_box_' . $this->fm->name,
				$this->title,
				array( $this, 'render_meta_box' ),
				$type,
				$this->context,
				$this->priority
			);
		}
	}

	/**
	 * Helper to attach element_markup() to add_meta_box(). Prints markup for post editor.
	 * @see http://codex.wordpress.org/Function_Reference/add_meta_box
	 * @param $post the post object.
	 * @param $form_struct the structure of the form itself (not very useful).
	 * @return void.
	 */
	public function render_meta_box( $post, $form_struct ) {
		$key = $form_struct['callback'][0]->fm->name;
		$values = get_post_meta( $post->ID, $key );
		$values = empty( $values ) ? Null : $values[0];
		$this->fm->data_type = 'post';
		$this->fm->data_id = $post->ID;
		wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce' );
		echo $this->fm->element_markup( $values );
		
		// Check if any validation is required
		$fm_validation = Fieldmanager_Util_Validation( 'post', 'post' );
		$fm_validation->add_field( $this->fm );
	}

	/**
	 * Helper to remove all built-in meta boxes for all specified taxonomies on a post type
	 * @param $post_type the post type
	 * @param $taxonomies the taxonomies for which to remove default meta boxes
	 * @return void.
	 */
	public function remove_meta_boxes() {
		foreach( $this->post_types as $type ) {
			foreach( $this->fm->meta_boxes_to_remove as $meta_box ) {
				remove_meta_box( $meta_box['id'], $type, $meta_box['context'] );
			}
		}
	}

	/**
	 * Handles saving Fieldmanager data when the custom meta boxes are used on an attachment.
	 * Calls save_fields_for_post with the post ID.
	 * @param array $post The post fields
	 * @param array $attachment The attachment fields
	 * @return void
	 */
	public function save_fields_for_attachment( $post, $attachment ) {
		// Use save_fields_for_post to handle saving any Fieldmanager meta data
		$this->save_fields_for_post( $post['ID'] );

		// Return the post data for the attachment unmodified
		return $post;
	}

	/**
	 * Takes $_POST data and saves it to, calling save_to_post_meta() once validation is passed
	 * When using Fieldmanager as an API, do not call this function directly, call save_to_post_meta()
	 * @param int $post_id
	 * @return void
	 */
	public function save_fields_for_post( $post_id ) {
		// Make sure this field is attached to the post type being saved.
		if ( !isset( $_POST['post_type'] ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || $_POST['action'] != 'editpost' )
			return;

		if ( get_post_type( $post_id ) == 'revision' ) return; // prevents saving the same post twice; FM does not yet use revisions.

		$use_this_post_type = False;
		foreach ( $this->post_types as $type ) {
			if ( $type == $_POST['post_type'] ) {
				$use_this_post_type = True;
				break;
			}
		}
		if ( !$use_this_post_type ) return;

		if ( $_POST['action'] == 'inline-save' ) return; // no fieldmanager on quick edit yet

		// Make sure the current user can save this post
		if( $_POST['post_type'] == 'post' ) {
			if( !current_user_can( 'edit_post', $post_id ) ) {
				$this->fm->_unauthorized_access( 'User cannot edit this post' );
				return;
			}
		}

		// Make sure that our nonce field arrived intact
		if( !wp_verify_nonce( $_POST['fieldmanager-' . $this->fm->name . '-nonce'], 'fieldmanager-save-' . $this->fm->name ) ) {
			$this->fm->_unauthorized_access( 'Nonce validation failed' );
		}
		
		$value = isset( $_POST[ $this->fm->name ] ) ? $_POST[ $this->fm->name ] : "";
		$this->save_to_post_meta( $post_id, $value );
	}

	/**
	 * Helper to save an array of data to post meta
	 * @param int $post_id
	 * @param array $data
	 * @return void
	 */
	public function save_to_post_meta( $post_id, $data ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
		$this->fm->data_id = $post_id;
		$this->fm->data_type = 'post';
		$current = get_post_meta( $this->fm->data_id, $this->fm->name, True );
		$data = $this->fm->presave_all( $data, $current );
		if ( !$this->fm->skip_save ) update_post_meta( $post_id, $this->fm->name, $data );
	}

}