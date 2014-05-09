<?php

/**
 * Use fieldmanager to add edit-in-place fields to items in a zoninator zone
 * @package Fieldmanager_Context
 */
class Fieldmanager_Context_Zoninator extends Fieldmanager_Context {

	/**
	 * @var string[]
	 * What post types to render the fields for
	 */
	public $post_types = array();

	/**
	 * @var string
	 * Optional label to display above edit form
	 */
	public $label = '';

	/**
	 * @var Fieldmanager_Group
	 * Base field
	 */
	public $fm = '';

	/**
	 * Add a context to a fieldmanager object
	 * @param string|string[] $post_types
	 * @param Fieldmanager_Field $fm
	 */
	public function __construct( $label, $post_types, $fm = Null ) {
		global $zoninator;

		// If zoninator is not enabled, just do nothing.
		if ( !function_exists( 'z_get_zoninator' ) || !is_object( $zoninator ) ) {
			return;
		}

		// Populate the list of post types to which we will add this group in zoninator's admin
		if ( !is_array( $post_types ) ) $post_types = array( $post_types );

		$this->label = $label;
		$this->post_types = $post_types;
		$this->fm = $fm;

		add_filter( 'zoninator_zone_post_columns', array( $this, 'modify_zone_post_info_callback' ) );
		add_action( 'wp_ajax_fm_zoninator_post_form_process', array( $this, 'process_ajax_submit' ), 10, 2 );
		fm_add_script( 'zoninator-js', 'js/fieldmanager-zoninator.js' );
		add_action( 'admin_enqueue_scripts', array( $this, 'localize_scripts' ) );
	}

	function localize_scripts() {
		$localization = array(
			'updating' => __( 'Updating...' ),
			'updated' => __( 'Post updated successfully.' ),
		);
		wp_localize_script( 'zoninator-js', 'fm_zoninator_localization', $localization ); 
	}

	/**
	 * Intercepts the function that is called to output zone post info
	 *
	 * @param array $columns
	 * @return array
	 */
	public function modify_zone_post_info_callback( $columns ) {
		if ( array_key_exists( 'info', $columns ) ) {
			$columns['info'] = array( $this, 'zone_post_info' );
		}

		// Register ourselves as needing to be called in the event that are not the last context object to respond to this filter.
		foreach ( $this->post_types as $post_type ) {
			$key = 'fm_zoninator_funcs_' . $post_type;
			$funcs = _fieldmanager_registry( $key );
			if ( !$funcs ) {
				$funcs = array();
			}
			$funcs[] = array( $this, 'zone_post_info' );
			_fieldmanager_registry( $key, $funcs );
		}

		return $columns;
	}

	/**
	 * Outputs the form additions along with the base elements from zoninator's internals.
	 *
	 * @param object $post
	 * @param object $zone
	 * @param boolean $master
	 * @return void
	 */
	public function zone_post_info( $post, $zone, $master = true ) {
		$zoninator = z_get_zoninator();

		if ( !in_array( $post->post_type, $this->post_types ) ) {
			return $zoninator->admin_page_zone_post_col_info( $post, $zone );
		}

		if ( $master ) {
			$zoninator->admin_page_zone_post_col_info( $post, $zone );
		}

		// Since zoninator only supports setting one callback function in the zoninator_zone_post_columns filter, we need to call 
		// the zone_post_info method of any other FM group that registered this context for this post type.
		// We do this before rendering our own markup since if we're being called here, we were the last FM group that responded to the filter.
		$funcs = _fieldmanager_registry( 'fm_zoninator_funcs_' . $post->post_type );
		if ( !empty( $funcs ) && $master ) {
			foreach ( $funcs as $func ) {
				if ( is_array( $func ) && $func[0]->fm->name == $this->fm->name ) {
					continue;
				}
				call_user_func( $func, $post, $zone, false );
			}
		}
		// Clear the registry for the next post.
		_fieldmanager_registry( 'fm_zoninator_funcs_' . $post->post_type, array() );

		$build .= '<form method="POST" class="fm-zoninator-post-form" id="fm-zone-post-' . esc_attr( $this->fm->name ) . '"><div class="fm-zone-post-form-wrapper">';
		$build .= sprintf( '<input type="hidden" name="fm-zone-post-id" value="%s" />', esc_attr( $post->ID ) );
		if ( $this->label ) {
			$build .= '<p>' . esc_html( $this->label ) . '</p>';
		}
		$build .= $this->render_post_form_elements( $post );
		$build .= sprintf( '</div><input type="submit" name="fm-submit" class="button-primary" value="%s" /><div class="fm-zone-post-form-message"></div></form>', esc_attr( $this->fm->submit_button_label ) ?: __( 'Update' ) );

		echo $build;
	}

	/**
	 * Renders FM elements.
	 * 
	 * @param object $post
	 * @return string
	 */
	public function render_post_form_elements( $post ) {
		$values = get_post_meta( $post->ID, $this->fm->name, true );
		$values = empty( $values ) ? Null : $values;
		$this->fm->data_type = 'zone_post';
		$this->fm->data_id = $post->ID;
		$nonce = wp_nonce_field( 'fieldmanager-save-' . $this->fm->name, 'fieldmanager-' . $this->fm->name . '-nonce', true, false );
		$elements = $nonce . $this->fm->element_markup( $values );
		
		// Check if any validation is required
		$fm_validation = Fieldmanager_Util_Validation( 'zone_post', 'zone_post' );
		$fm_validation->add_field( $this->fm );

		return $elements;
	}

	/**
	 * Receives AJAX post data, sanitizes, passes off for processing
	 * 
	 * @return void
	 */
	public function process_ajax_submit() {
		$data = null;
		parse_str( $_POST['data'], $data );

		if ( !array_key_exists( $this->fm->name, $data ) || !array_key_exists( 'fm-zone-post-id', $data ) ) {
			return;
		}

		if ( !wp_verify_nonce( $data['fieldmanager-' . $this->fm->name . '-nonce'], 'fieldmanager-save-' . $this->fm->name ) ) {
			return;
		}

		if ( is_array( $data[$this->fm->name] ) ) {
			$value = $data[$this->fm->name];
			array_walk_recursive( $value, 'sanitize_text_field' );
		} else {
			$value = sanitize_text_field( $data[$this->fm->name] ); 
		}

		$this->save_fields_for_zone_post( intval($data['fm-zone-post-id']), $value );

		echo "0"; 
		die();
	}

	/**
	 * Saves data to postmeta
	 *
	 * @param int $post_id
	 * @param mixed $value
	 */
	public function save_fields_for_zone_post( $post_id, $value ) {
		$post_type = get_post_type( $post_id );

		if ( $post_type == 'revision' ) return; // prevents saving the same post twice; FM does not yet use revisions.
		if ( !in_array( $post_type, $this->post_types ) ) return; // one more check against post type mismatch

		if( !current_user_can( 'edit_post', $post_id ) ) {
			$this->fm->_unauthorized_access( 'User cannot edit this post' );
			return;
		}

		$this->save_to_post_meta( $post_id, $value );

	}

	/**
	 * Helper to save an array of data to post meta
	 * @param int $post_id
	 * @param array $data
	 * @return void
	 */
	public function save_to_post_meta( $post_id, $data ) {
		$this->fm->data_id = $post_id;
		$this->fm->data_type = 'post';
		$current = get_post_meta( $this->fm->data_id, $this->fm->name, True );
		$data = $this->fm->presave_all( $data, $current );
		if ( !$this->fm->skip_save ) update_post_meta( $post_id, $this->fm->name, $data );
	}
}