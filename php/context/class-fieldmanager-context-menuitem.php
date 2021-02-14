<?php
/**
 * Class file for Fieldmanager_Context_Menu_Item
 *
 * @package Fieldmanager
 */

/**
 * Use fieldmanager to ad custom fields to a menu item.
 */
class Fieldmanager_Context_MenuItem extends Fieldmanager_Context_Storable {
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
	 * The original form name.
	 *
	 * @var string
	 */
	private $original_form_name = '';

	/**
	 * Add a context to a fieldmanager.
	 *
	 * @throws FM_Developer_Exception Must have a valid display callback.
	 *
	 * @param Fieldmanager_Field $fm The base field.
	 */
	public function __construct( $fm = null ) {
		global $wp_version;

		// Needs WP version 5.4.0 or greater.
		if ( version_compare( $wp_version, '5.4.0', '<' ) ) {
			return;
		}

		$this->fm = $fm;

		$this->fm->data_type = 'post';

		// Save the original form name.
		$this->original_form_name = $this->fm->name;

		add_filter( 'wp_nav_menu_item_custom_fields', array( $this, 'add_fields' ), 10, 5 );
		add_action( 'wp_update_nav_menu_item', array( $this, 'save_fields' ), 10, 3 );
	}

	/**
	 * Get the menu item form name given the menu ID. This allows FM to save meta
	 * data to each menu item within the same POST request.
	 *
	 * @param int $menu_id The menu ID.
	 * @return string The form name.
	 */
	public function get_menu_item_form_name( $menu_id ) {
		return $this->original_form_name . '_fm-menu-item-id-' . absint( $menu_id );
	}

	/**
	 * Parse the form name, assuming it already contains the menu ID, into its
	 * original form name.
	 *
	 * @param string $form_name The form name.
	 * @return mixed False if form name does not exist or an array of menu ID and name.
	 */
	public function parse_form_name( $form_name ) {
		// Not a menu item form name.
		if ( false === strpos( $form_name, '_fm-menu-item-id-', true ) ) {
			return false;
		}

		// Break out the original name from the menu item ID.
		$parts = explode( '_fm-menu-item-id-', $form_name );

		if ( ! empty( $parts[0] ) && ! empty( $parts[1] ) ) {
			return array(
				'name' => $parts[0],
				'id'   => absint( $parts[1] ),
			);
		}

		return false;
	}


	/**
	 * Add fields to the editor of a nav menu item.
	 *
	 * @param int $item_id Menu item ID.
	 */
	public function add_fields( $item_id ) {
		// Set the ID.
		$this->fm->data_id = $item_id;

		// Ensure the ID is part of the name.
		$this->fm->name = $this->get_menu_item_form_name( $item_id );

		// Render the field.
		$this->render_field();
	}

	/**
	 * Save post meta for nav menu items.
	 *
	 * @param int   $menu_id The ID of the menu.
	 * @param int   $menu_item_db_id The ID of the menu item.
	 * @param array $menu_item_args Menu item args.
	 */
	public function save_fields( $menu_id, $menu_item_db_id, $menu_item_args ) {
		// Ensure the ID is part of the name.
		$this->fm->name = $this->get_menu_item_form_name( $menu_item_db_id );

		// Ensure that the nonce is set and valid.
		if ( ! $this->is_valid_nonce() ) {
			return;
		}

		// Make sure the current user can save this post.
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			$this->fm->_unauthorized_access( __( 'User cannot edit this menu item', 'fieldmanager' ) );
			return;
		}

		$this->save_to_post_meta( $menu_item_db_id );
	}

	/**
	 * Helper to save an array of data to post meta.
	 *
	 * @param int   $post_id The post ID.
	 * @param array $data    The post data.
	 */
	public function save_to_post_meta( $post_id, $data = null ) {
		$this->fm->data_id   = $post_id;
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
		$parts = $this->parse_form_name( $meta_key );

		if ( ! empty( $parts['name'] ) && ! empty( $parts['id'] ) ) {
			$post_id  = $parts['id'];
			$meta_key = $parts['name'];
		}

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
		$parts = $this->parse_form_name( $meta_key );

		if ( ! empty( $parts['name'] ) && ! empty( $parts['id'] ) ) {
			$post_id  = $parts['id'];
			$meta_key = $parts['name'];
		}

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
		$parts = $this->parse_form_name( $meta_key );

		if ( ! empty( $parts['name'] ) && ! empty( $parts['id'] ) ) {
			$post_id  = $parts['id'];
			$meta_key = $parts['name'];
		}

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
		$parts = $this->parse_form_name( $meta_key );

		if ( ! empty( $parts['name'] ) && ! empty( $parts['id'] ) ) {
			$post_id  = $parts['id'];
			$meta_key = $parts['name'];
		}

		return delete_post_meta( $post_id, $meta_key, $meta_value );
	}
}
