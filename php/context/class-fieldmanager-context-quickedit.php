<?php
/**
 * @package Fieldmanager_Context
 */

/**
 * Use fieldmanager to create meta boxes on
 * @package Fieldmanager_Datasource
 */
class Fieldmanager_Context_QuickEdit extends Fieldmanager_Context {

	/**
	 * @var string
	 * Title of QuickEdit box
	 */
	public $title = '';

	/**
	 * @var callback
	 * Since QuickEdit fields are tied directly to custom posts admin columns, this context will create one and manage it.
	 * This callback will provide the contents of each cell of this column where the post has data set.
	 */
	public $column_not_empty_callback = '';

	/**
	 * @var callback
	 * Since QuickEdit fields are tied directly to custom posts admin columns, this context will create one and manage it.
	 * This callback will provide the contents of each cell of this column where the post does NOT have data set.
	 */
	public $column_empty_callback = '';

	/**
	 * @var string[]
	 * What post types to render this meta box
	 */
	public $post_types = array();

	/**
	 * @var Fieldmanager_Group
	 * Base field
	 */
	public $fm = '';

	/**
	 * Add a context to a fieldmanager
	 * @param string $title
	 * @param string|string[] $post_types
	 * @param callback $column_not_empty_callback
	 * @param callback $column_empty_callback
	 * @param Fieldmanager_Field $fm
	 */
	public function __construct( $title, $post_types, $column_not_empty_callback, $column_empty_callback, $fm = Null ) {
		// Populate the list of post types for which to add this meta box with the given settings
		if ( !is_array( $post_types ) ) $post_types = array( $post_types );

		$this->post_types = $post_types;
		$this->title = $title;
		$this->column_not_empty_callback = $column_not_empty_callback;
		$this->column_empty_callback = $column_empty_callback;
		$this->fm = $fm;

		foreach ( $post_types as $post_type ) {
			add_action( 'manage_' . $post_type . '_posts_columns', array( $this, 'add_custom_columns' ) );
		}

		add_action( 'manage_posts_custom_column', array( $this, 'manage_custom_columns' ), 10, 2 );
		add_action( 'quick_edit_custom_box', array( $this, 'add_quickedit_box' ) );
		add_action( 'save_post', array( $this, 'save_fields_for_quickedit' ) );
	}

	/**
	 * manage_{$post_type}_posts_columns callback, as QuickEdit boxes only work on custom columns.
	 * @param array $columns
	 * @return void
	 */
	function add_custom_columns( $columns ) {
		$columns[$this->fm->name] = $this->title;
		return $columns;
	}

	/**
	 * manage_posts_custom_column callback
	 * @param string $column_name
	 * @param int $post_id
	 * @return void
	 */
	public function manage_custom_columns( $column_name, $post_id ) {
		if ( $column_name != $this->fm->name ) return;
		$data = get_post_meta( $post_id, $this->fm->name, true );
		if ( !empty( $data ) ) {
			$column_text = call_user_func( $this->column_not_empty_callback, $data, $post_id );
		}
		else {
			$column_text = call_user_func( $this->column_empty_callback, $post_id );
		}
		echo $column_text;
	}

	/**
	 * quick_edit_custom_box callback. Renders the QuickEdit box.
	 * Unfortunately, there is no way to access the post_id or existing values using this hook, so we cannot pass
	 * this to Fieldmanager_Field::element_markup here. There is prior art to do this using JS 
	 * (see http://codex.wordpress.org/Plugin_API/Action_Reference/quick_edit_custom_box#Setting_Existing_Values).
	 * Employing this method would require making some assumptions about the data structure of $fm, however, so for now
	 * we're unable to show prior field values.
	 * @param string $column_name
	 * @param string $post_type
	 * @return void
	 */
	public function add_quickedit_box( $column_name, $post_type ) {
		if ( $column_name != $this->fm->name ) return;
		?>
		<fieldset class="inline-edit-col-left">
			<div class="inline-edit-col">
				<?php wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce' ); ?>
				<?php echo $this->fm->element_markup( array() ); ?>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Takes $_POST data and saves it to, calling save_to_post_meta() once validation is passed
	 * When using Fieldmanager as an API, do not call this function directly, call save_to_post_meta()
	 * @param int $post_id
	 * @return void
	 */
	public function save_fields_for_quickedit( $post_id ) {
		// Make sure this field is attached to the post type being saved.
		if ( !isset( $_POST['post_type'] ) || ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || $_POST['action'] != 'inline-save' )
			return;
		$use_this_post_type = False;
		foreach ( $this->post_types as $type ) {
			if ( $type == $_POST['post_type'] ) {
				$use_this_post_type = True;
				break;
			}
		}
		if ( !$use_this_post_type ) return;

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
		$post = get_post( $post_id );
		if ( $post->post_type = 'revision' && $post->post_parent != 0 ) {
			$this->fm->data_id = $post->post_parent;
		}
		$current = get_post_meta( $this->fm->data_id, $this->fm->name, True );
		$data = $this->fm->presave_all( $data, $current );
		if ( !$this->fm->skip_save ) update_post_meta( $post_id, $this->fm->name, $data );
	}

	/**
	 * Default display function for non-empty columns.
	 * @var mixed $data
	 */
	public function default_not_empty( $data ) {
		return "Data set.";
	}

	/**
	 * Default display function for non-empty columns.
	 */
	public function default_empty() {
		return "Data not set.";
	}


}
