<?php
/**
 * Class file for Fieldmanager_Context_Post
 *
 * @package Fieldmanager
 */

/**
 * Use fieldmanager to create meta boxes on the new/edit post screen and save
 * data primarily to post meta.
 */
class Fieldmanager_Context_Post extends Fieldmanager_Context_Storable {

	/**
	 * Title of meta box.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * What post types to render this meta box.
	 *
	 * @var array
	 */
	public $post_types = array();

	/**
	 * Context (normal, advanced, or side).
	 *
	 * @var string
	 */
	public $context = 'normal';

	/**
	 * Priority (high, core, default, or low).
	 *
	 * @var priority
	 */
	public $priority = 'default';

	/**
	 * Base field.
	 *
	 * @var Fieldmanager_Group
	 */
	public $fm = null;

	/**
	 * Doing internal update.
	 *
	 * @var bool
	 */
	private static $doing_internal_update = false;

	/**
	 * Add a context to a fieldmanager.
	 *
	 * @param string             $title      Metabox title.
	 * @param mixed              $post_types Metabox post types.
	 * @param string             $context    Metabox context (normal, advanced, or side).
	 * @param string             $priority   Metabox priority (high, core, default, or low).
	 * @param Fieldmanager_Field $fm         Current field.
	 */
	public function __construct( $title, $post_types, $context = 'normal', $priority = 'default', $fm = null ) {

		// Populate the list of post types for which to add this meta box with the given settings.
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}

		$this->post_types = $post_types;
		$this->title      = $title;
		$this->context    = $context;
		$this->priority   = $priority;
		$this->fm         = $fm;

		add_action( 'admin_init', array( $this, 'meta_box_render_callback' ) );
		// If this meta box is on an attachment page, add the appropriate filter hook to save the data.
		if ( isset( $this->fm->is_attachment ) && $this->fm->is_attachment ) {
			add_filter( 'attachment_fields_to_save', array( $this, 'save_fields_for_attachment' ), 10, 2 );
		}
		add_action( 'save_post', array( $this, 'delegate_save_post' ) );
		// Check if any meta boxes need to be removed.
		if ( $this->fm && ! empty( $this->fm->meta_boxes_to_remove ) ) {
			add_action( 'add_meta_boxes', array( $this, 'remove_meta_boxes' ), 100 );
		}
	}

	/**
	 * Registers render_meta_box() via admin_init callback to add meta boxes to
	 * content types.
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
	 *
	 * @see http://codex.wordpress.org/Function_Reference/add_meta_box
	 *
	 * @param WP_Post $post        The post object.
	 * @param null    $form_struct The structure of the form itself. Unused.
	 */
	public function render_meta_box( $post, $form_struct = null ) {
		$this->fm->data_type = 'post';
		$this->fm->data_id   = $post->ID;

		$this->render_field();

		// Check if any validation is required.
		$fm_validation = fieldmanager_util_validation( 'post', 'post' );
		$fm_validation->add_field( $this->fm );
	}

	/**
	 * Helper to remove all built-in meta boxes for all specified taxonomies on a post type.
	 */
	public function remove_meta_boxes() {
		foreach ( $this->post_types as $type ) {
			foreach ( $this->fm->meta_boxes_to_remove as $meta_box ) {
				remove_meta_box( $meta_box['id'], $type, $meta_box['context'] );
			}
		}
	}

	/**
	 * Handles saving Fieldmanager data when the custom meta boxes are used on an attachment.
	 * Calls save_fields_for_post with the post ID.
	 *
	 * @param  array $post       The post fields.
	 * @param  array $attachment The attachment fields.
	 * @return array The post fields.
	 */
	public function save_fields_for_attachment( $post, $attachment ) {
		// Use save_fields_for_post to handle saving any Fieldmanager meta data.
		$this->save_fields_for_post( $post['ID'] );

		// Return the post data for the attachment unmodified.
		return $post;
	}

	/**
	 * Action handler to delegate to appropriate methods when a post is saved.
	 *
	 * @param int $post_id The post ID.
	 */
	public function delegate_save_post( $post_id ) {
		if ( self::$doing_internal_update ) {
			return;
		}
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$this->save_fields_for_cron( $post_id );
		} else {
			$this->save_fields_for_post( $post_id );
		}
	}

	/**
	 * Takes $_POST data and saves it to, calling save_to_post_meta() once validation is passed.
	 * When using Fieldmanager as an API, do not call this function directly, call save_to_post_meta().
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_fields_for_post( $post_id ) {
		// Make sure this field is attached to the post type being saved.
		if (
			empty( $_POST['post_ID'] ) // WPCS: input var okay. CSRF ok.
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| ( isset( $_POST['action'] ) && 'editpost' != $_POST['action'] ) // WPCS: input var okay. CSRF ok.
		) {
			return;
		}

		// Make sure this hook fired on the post being saved, not a side-effect post for which the $_POST context is invalid.
		if ( absint( $_POST['post_ID'] ) !== $post_id ) { // WPCS: input var okay. CSRF ok.
			return;
		}

		// Prevent saving the same post twice; FM does not yet use revisions.
		if ( get_post_type( $post_id ) == 'revision' ) {
			return;
		}

		// Make sure this post type is intended for handling by this FM context.
		if ( ! in_array( get_post_type( $post_id ), $this->post_types ) ) {
			return;
		}

		// Do not handle quickedit in this context.
		if ( 'inline-save' == $_POST['action'] ) { // WPCS: input var okay. CSRF ok.
			return;
		}

		// Verify nonce is present and valid. If present but not valid, this
		// throws an exception, but if it's absent we can assume our data is
		// not present.
		if ( ! $this->is_valid_nonce() ) {
			return;
		}

		// Make sure the current user is authorized to save this post.
		if ( isset( $_POST['post_type'] ) && 'post' == $_POST['post_type'] ) { // WPCS: input var okay. CSRF ok.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				$this->fm->_unauthorized_access( __( 'User cannot edit this post', 'fieldmanager' ) );
				return;
			}
		}

		$this->save_to_post_meta( $post_id );
	}

	/**
	 * Process fields during a cron request, without saving data since the data
	 * isn't changing.
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_fields_for_cron( $post_id ) {
		if ( ! in_array( get_post_type( $post_id ), $this->post_types ) ) {
			return;
		}
		/**
		 * Don't save values since we aren't provided with any; just trigger
		 * presave so that subclass handlers can process as necessary.
		 */
		$this->fm->skip_save = true;
		$current             = get_post_meta( $post_id, $this->fm->name, true );
		$this->save_to_post_meta( $post_id, $current );
	}

	/**
	 * Helper to save an array of data to post meta.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $data    The data to save.
	 */
	public function save_to_post_meta( $post_id, $data = null ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		$this->fm->data_id   = $post_id;
		$this->fm->data_type = 'post';

		$this->save( $data );
	}

	/**
	 * Helper for fieldmanager internals to save a post without worrying about
	 * infinite loops.
	 *
	 * @param  array $args The post args to save.
	 * @return mixed The result of the post update function.
	 */
	public static function safe_update_post( $args ) {
		self::$doing_internal_update = true;
		$ret                         = wp_update_post( $args );
		self::$doing_internal_update = false;
		return $ret;
	}

	/**
	 * Get post meta.
	 *
	 * @see get_post_meta().
	 *
	 * @param int    $post_id  Post ID.
	 * @param string $meta_key Optional. The meta key to retrieve. By default, returns
	 *                         data for all keys. Default empty.
	 * @param bool   $single   Optional. Whether to return a single value. Default false.
	 */
	protected function get_data( $post_id, $meta_key, $single = false ) {
		return get_post_meta( $post_id, $meta_key, $single );
	}

	/**
	 * Add post meta.
	 *
	 * @see add_post_meta().
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Metadata value. Must be serializable if non-scalar.
	 * @param bool   $unique     Optional. Whether the same key should not be added.
	 *                           Default false.
	 */
	protected function add_data( $post_id, $meta_key, $meta_value, $unique = false ) {
		return add_post_meta( $post_id, $meta_key, $meta_value, $unique );
	}

	/**
	 * Update post meta.
	 *
	 * @see update_post_meta().
	 *
	 * @param int    $post_id         Post ID.
	 * @param string $meta_key        Metadata key.
	 * @param mixed  $meta_value      Metadata value. Must be serializable if non-scalar.
	 * @param mixed  $data_prev_value Optional. Previous value to check before removing.
	 *                                Default empty.
	 */
	protected function update_data( $post_id, $meta_key, $meta_value, $data_prev_value = '' ) {
		$meta_value = $this->sanitize_scalar_value( $meta_value );
		return update_post_meta( $post_id, $meta_key, $meta_value, $data_prev_value );
	}

	/**
	 * Delete post meta.
	 *
	 * @see delete_post_meta().
	 *
	 * @param int    $post_id    Post ID.
	 * @param string $meta_key   Metadata name.
	 * @param mixed  $meta_value Optional. Metadata value. Must be serializable if
	 *                           non-scalar. Default empty.
	 */
	protected function delete_data( $post_id, $meta_key, $meta_value = '' ) {
		return delete_post_meta( $post_id, $meta_key, $meta_value );
	}

}
