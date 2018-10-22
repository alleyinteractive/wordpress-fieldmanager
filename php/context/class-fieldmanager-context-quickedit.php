<?php
/**
 * Class file for Fieldmanager_Context_QuickEdit
 *
 * @package Fieldmanager
 */

/**
 * Use fieldmanager to add fields to the "quick edit" (post list inline editing)
 * and save data primarily to post meta.
 */
class Fieldmanager_Context_QuickEdit extends Fieldmanager_Context_Storable {

	/**
	 * Title of QuickEdit box; also used for the column title unless $column_title is specified.
	 *
	 * @var string
	 */
	public $title = '';

	/**
	 * Override $title for the column in the list of posts.
	 *
	 * @var string
	 */
	public $column_title = '';

	/**
	 * QuickEdit fields are tied to custom columns in the list of posts. This callback should return a value to
	 * display in a custom column.
	 *
	 * @var callable
	 */
	public $column_display_callback = null;

	/**
	 * What post types to render this Quickedit form.
	 *
	 * @var string
	 */
	public $post_types = array();

	/**
	 * Base field
	 *
	 * @var Fieldmanager_Field
	 */
	public $fm = '';

	/**
	 * Add a context to a fieldmanager.
	 *
	 * @throws FM_Developer_Exception Must have a valid display callback.
	 *
	 * @param string             $title                   Title of the form.
	 * @param mixed              $post_types              Post types to show form on.
	 * @param callable           $column_display_callback Display callback.
	 * @param callable           $column_title            Column title.
	 * @param Fieldmanager_Field $fm                      The base field.
	 */
	public function __construct( $title, $post_types, $column_display_callback, $column_title = '', $fm = null ) {

		if ( ! fm_match_context( 'quickedit' ) ) {
			return; // make sure we only load up our JS if we're in a quickedit form.
		}

		if ( FM_DEBUG && ! is_callable( $column_display_callback ) ) {
			throw new FM_Developer_Exception( esc_html__( 'You must set a valid column display callback.', 'fieldmanager' ) );
		}

		// Populate the list of post types for which to add this meta box with the given settings.
		if ( ! is_array( $post_types ) ) {
			$post_types = array( $post_types );
		}

		$this->post_types = $post_types;
		$this->title = $title;
		$this->column_title = ! empty( $column_title ) ? $column_title : $title;
		$this->column_display_callback = $column_display_callback;
		$this->fm = $fm;

		if ( is_callable( $column_display_callback ) ) {
			foreach ( $post_types as $post_type ) {
				add_action( 'manage_' . $post_type . '_posts_columns', array( $this, 'add_custom_columns' ) );
			}
			add_action( 'manage_posts_custom_column', array( $this, 'manage_custom_columns' ), 10, 2 );
		}

		add_action( 'quick_edit_custom_box', array( $this, 'add_quickedit_box' ), 10, 2 );
		add_action( 'save_post', array( $this, 'save_fields_for_quickedit' ) );
		add_action( 'wp_ajax_fm_quickedit_render', array( $this, 'render_ajax_form' ), 10, 2 );

		$post_type = ! isset( $_GET['post_type'] ) ? 'post' : sanitize_text_field( wp_unslash( $_GET['post_type'] ) ); // WPCS: input var okay.

		if ( in_array( $post_type, $this->post_types ) ) {
			fm_add_script( 'quickedit-js', 'js/fieldmanager-quickedit.js' );
		}
	}

	/**
	 * Callback for manage_{$post_type}_posts_columns, as QuickEdit boxes only
	 * work on custom columns.
	 *
	 * @param  array $columns The custom columns.
	 * @return array $columns The custom columns.
	 */
	public function add_custom_columns( $columns ) {
		$columns[ $this->fm->name ] = $this->column_title;
		return $columns;
	}

	/**
	 * The manage_posts_custom_column callback.
	 *
	 * @param string $column_name Column name.
	 * @param int    $post_id     The post ID.
	 */
	public function manage_custom_columns( $column_name, $post_id ) {
		if ( $column_name != $this->fm->name ) {
			return;
		}
		$data = get_post_meta( $post_id, $this->fm->name, true );
		$column_text = call_user_func( $this->column_display_callback, $post_id, $data );
		echo $column_text; // WPCS: XSS ok.
	}

	/**
	 * The quick_edit_custom_box callback. Renders the QuickEdit box.
	 * Renders with blank values here since QuickEdit boxes cannot access to the WP post_id.
	 * The values will be populated by an ajax-fetched form later (see $this->render_ajax_form() ).
	 *
	 * @param string $column_name The column name.
	 * @param string $post_type   The post type to show the column.
	 * @param array  $values      The current values.
	 */
	public function add_quickedit_box( $column_name, $post_type, $values = array() ) {
		if ( $column_name != $this->fm->name ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-left fm-quickedit" id="fm-quickedit-<?php echo esc_attr( $column_name ); ?>" data-fm-post-type="<?php echo esc_attr( $post_type ); ?>">
			<div class="inline-edit-col">
				<?php if ( ! empty( $this->title ) ) : ?>
					<h4><?php echo esc_html( $this->title ); ?></h4>
				<?php endif ?>

				<?php
				$this->render_field( array(
					'data' => $values,
				) );
				?>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Callback for wp_ajax_fm_quickedit_render.
	 * Renders a form with pre-filled values to replace the one generated by $this->add_quickedit_box().
	 */
	public function render_ajax_form() {
		if ( ! isset( $_GET['action'], $_GET['post_id'], $_GET['column_name'] ) ) { // WPCS: input var okay.
			return;
		}

		if ( 'fm_quickedit_render' != $_GET['action'] ) { // WPCS: input var okay.
			return;
		}

		$column_name = sanitize_text_field( wp_unslash( $_GET['column_name'] ) ); // WPCS: input var okay.
		$post_id = intval( $_GET['post_id'] ); // WPCS: input var okay.

		if ( ! $post_id || $column_name != $this->fm->name ) {
			return;
		}

		$this->fm->data_type = 'post';
		$this->fm->data_id = $post_id;
		$post_type = get_post_type( $post_id );

		$this->add_quickedit_box( $column_name, $post_type, $this->load() );
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			exit;
		}
	}

	/**
	 * Takes $_POST data and saves it to, calling save_to_post_meta() once validation is passed
	 * When using Fieldmanager as an API, do not call this function directly, call save_to_post_meta().
	 *
	 * @param int $post_id The post ID.
	 */
	public function save_fields_for_quickedit( $post_id ) {
		// Make sure this field is attached to the post type being saved.
		if (
			! isset( $_POST['post_type'] ) // WPCS: input var okay. CSRF okay.
			|| ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			|| ( isset( $_POST['action'] ) && 'inline-save' != $_POST['action'] ) // WPCS: input var okay. CSRF okay.
		) {
			return;
		}

		$use_this_post_type = false;
		foreach ( $this->post_types as $type ) {
			if ( $type == $_POST['post_type'] ) { // WPCS: input var okay. CSRF okay.
				$use_this_post_type = true;
				break;
			}
		}
		if ( ! $use_this_post_type ) {
			return;
		}

		// Ensure that the nonce is set and valid.
		if ( ! $this->is_valid_nonce() ) {
			return;
		}

		// Make sure the current user can save this post.
		if ( 'post' == $_POST['post_type'] ) { // WPCS: input var okay. CSRF okay.
			if ( ! current_user_can( 'edit_post', $post_id ) ) {
				$this->fm->_unauthorized_access( __( 'User cannot edit this post', 'fieldmanager' ) );
				return;
			}
		}

		$this->save_to_post_meta( $post_id );
	}

	/**
	 * Helper to save an array of data to post meta.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $data    The post data.
	 */
	public function save_to_post_meta( $post_id, $data = null ) {
		$this->fm->data_id = $post_id;
		$this->fm->data_type = 'post';

		$this->save( $data );
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
