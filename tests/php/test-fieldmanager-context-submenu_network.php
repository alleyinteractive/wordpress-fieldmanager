<?php
/**
 * @group multisite
 * @group submenu
 */
class Test_Fieldmanager_Context_NetworkSubmenu extends Test_Fieldmanager_Context_Submenu {
	public function test_hook_registration() {
		$context = new Fieldmanager_Context_NetworkSubmenu( null, null, null, null, null, $this->get_fields( 'hook_registration' ) );
		$this->assert_unhooked( $context );
	}

	protected function get_context( $name ) {
		$submenus = _fieldmanager_registry( 'submenus' );
		$s = $submenus[ $name ];
		return new Fieldmanager_Context_NetworkSubmenu( $s[0], $s[1], $s[2], $s[3], $s[4], $this->get_fields( $name ), true );
	}

	protected function unhook_registration( $context ) {
		return remove_action( 'network_admin_menu', array( $context, 'register_submenu_page' ) );
	}

	protected function _get_option( $name ) {
		return get_site_option( $name );
	}

	protected function _delete_option( $name ) {
		return delete_site_option( $name );
	}

	protected function _get_admin_url( $path ) {
		return network_admin_url( $path );
	}
}
