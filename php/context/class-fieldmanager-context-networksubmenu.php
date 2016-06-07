<?php
/**
 * Create a page in the WordPress Network Admin.
 *
 * @package Fieldmanager_Context
 */
class Fieldmanager_Context_NetworkSubmenu extends Fieldmanager_Context_Submenu {
	/**
	 * Capability required to access the submenu
	 *
	 * @var string
	 */
	public $capability = 'manage_network_options';

	/**
	 * Initialize the context.
	 */
	public function __construct( $parent_slug, $page_title, $menu_title = null, $capability = null, $menu_slug = null, $fm = null, $already_registered = false ) {
		parent::__construct( $parent_slug, $page_title, $menu_title, $capability, $menu_slug, $fm, $already_registered );
	}

	/**
	 * Add action to register the submenu page.
	 */
	protected function hook_registration() {
		add_action( 'network_admin_menu', array( $this, 'register_submenu_page' ) );
	}

	/**
	 * Get the admin page URL.
	 *
	 * @return string
	 */
	public function url() {
		if ( $this->parent_slug && ! isset( $GLOBALS['_parent_pages'][ $this->parent_slug ] ) ) {
			return network_admin_url( add_query_arg( 'page', $this->menu_slug, $this->parent_slug ) );
		} else {
			return network_admin_url( 'admin.php?page=' . $this->menu_slug );
		}
	}

	/**
	 * Get site option.
	 *
	 * @see get_site_option().
	 */
	protected function get_data( $data_id, $option_name, $single = false ) {
		return get_site_option( $option_name, null );
	}

	/**
	 * Add site option.
	 *
	 * @see add_site_option().
	 */
	protected function add_data( $data_id, $option_name, $option_value, $unique = false ) {
		return add_site_option( $option_name, $option_value );
	}

	/**
	 * Update site option.
	 *
	 * @see update_site_option().
	 */
	protected function update_data( $data_id, $option_name, $option_value, $option_prev_value = '' ) {
		return update_site_option( $option_name, $option_value );
	}

	/**
	 * Delete site option.
	 *
	 * @see delete_site_option().
	 */
	protected function delete_data( $data_id, $option_name, $option_value = '' ) {
		return delete_site_option( $option_name );
	}
}
