<?php
/**
 * @group field
 * @group options
 */
class Test_Fieldmanager_Options extends WP_UnitTestCase {
	/**
	 * See #523.
	 */
	public function test_apply_presave_alter_values_filter() {
		$filter = '__return_empty_array';

		add_filter( 'fm_presave_alter_values', $filter );

		$stub = $this->getMockForAbstractClass( 'Fieldmanager_Options' );
		$this->assertSame( call_user_func( $filter ), $stub->presave_alter_values( '123' ) );

		/*
		 * Additional processing occurs when a datasource is present; make sure
		 * the filter is still applied afterwards.
		 */
		$stub->datasource = new Fieldmanager_Datasource_Post;
		$this->assertSame( call_user_func( $filter ), $stub->presave_alter_values( '123' ) );

		remove_filter( 'fm_presave_alter_values', $filter );
	}
}
