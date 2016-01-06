<?php

/**
 * Tests the Fieldmanager Datasource User
 *
 * @group datasource
 * @group user
 */
class Test_Fieldmanager_Datasource_User extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->author = $this->factory->user->create( array( 'role' => 'author', 'user_login' => 'author', 'user_email' => 'test@test.com', 'display_name' => 'Lorem Ipsum' ) );
		$this->editor = $this->factory->user->create( array( 'role' => 'editor', 'user_login' => 'editor' ) );
		$this->administrator = $this->factory->user->create( array( 'role' => 'administrator', 'user_login' => 'administrator' ) );

		wp_set_current_user( $this->administrator );

		$this->post = $this->factory->post->create_and_get( array(
			'post_status' => 'draft',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
			'post_author' => $this->author,
		) );
	}

	/**
	 * Set up the request environment values and save the data.
	 *
	 * @param Fieldmanager_Field $field
	 * @param WP_Post $post
	 * @param mixed $values
	 */
	public function save_values( $field, $post, $values ) {
		$_POST = array(
			'post_ID' => $post->ID,
			'action' => 'editpost',
			'post_type' => $post->post_type,
			"fieldmanager-{$field->name}-nonce" => wp_create_nonce( "fieldmanager-save-{$field->name}" ),
			$field->name => $values,
		);

		$field->add_meta_box( $field->name, $post->post_type )->save_to_post_meta( $post->ID, $values );
	}

	/**
	 * Test that when configured to do so, child posts will store a reciprocal post ID in user meta.
	 */
	public function test_reciprocal_meta() {
		$reciprocal = new Fieldmanager_Autocomplete( array(
			'name' => 'test_reciprocal',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'reciprocal' => 'reciprocal_post',
			) ),
		) );

		$this->assertEquals( null, get_user_meta( $this->author, 'reciprocal_post', true ) );

		$this->save_values( $reciprocal, $this->post, $this->author );

		$this->assertEquals( $this->author, get_post_meta( $this->post->ID, 'test_reciprocal', true ) );
		$this->assertEquals( $this->post->ID, get_user_meta( $this->author, 'reciprocal_post', true ) );
	}

	/**
	 * Test that various store properties work
	 */
	public function test_store_properties() {
		$user = get_userdata( $this->author );

		$store_id = new Fieldmanager_Autocomplete( array(
			'name' => 'test_store_id',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'store_property' => 'ID',
			) ),
		) );

		$this->save_values( $store_id, $this->post, $user->ID );
		$this->assertEquals( $user->ID, get_post_meta( $this->post->ID, 'test_store_id', true ) );

		$store_user_login = new Fieldmanager_Autocomplete( array(
			'name' => 'test_store_user_login',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'store_property' => 'user_login',
			) ),
		) );

		$this->save_values( $store_user_login, $this->post, $user->user_login );
		$this->assertEquals( $user->user_login, get_post_meta( $this->post->ID, 'test_store_user_login', true ) );

		$store_user_email = new Fieldmanager_Autocomplete( array(
			'name' => 'test_store_user_email',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'store_property' => 'user_email',
			) ),
		) );

		$this->save_values( $store_user_email, $this->post, $user->user_email );
		$this->assertEquals( $user->user_email, get_post_meta( $this->post->ID, 'test_store_user_email', true ) );

		$store_user_nicename = new Fieldmanager_Autocomplete( array(
			'name' => 'test_store_user_nicename',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'store_property' => 'user_nicename',
			) ),
		) );

		$this->save_values( $store_user_nicename, $this->post, $user->user_nicename );
		$this->assertEquals( $user->user_nicename, get_post_meta( $this->post->ID, 'test_store_user_nicename', true ) );
	}

	/**
	 * Test creating a field with an invalid store property.
	 * @expectedException FM_Developer_Exception
	 */
	public function test_save_invalid_store_property() {
		$test_invalid = new Fieldmanager_Autocomplete( array(
			'name' => 'test_invalid',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'store_property' => 'invalid',
			) ),
		) );

		$this->save_values( $test_invalid, $this->post, $this->author );
	}

	/**
	 * Test creating a field with an invalid display property.
	 * @expectedException FM_Developer_Exception
	 */
	public function test_save_invalid_display_property() {
		$test_invalid = new Fieldmanager_Autocomplete( array(
			'name' => 'test_invalid',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'display_property' => 'invalid',
			) ),
		) );

		$this->save_values( $test_invalid, $this->post, $this->author );
	}

	/**
	 * Test that we fail when trying to use reciprocals with something other than ID as a store property.
	 * @expectedException FM_Developer_Exception
	 */
	public function test_save_invalid_reciprocal() {
		$test_invalid = new Fieldmanager_Autocomplete( array(
			'name' => 'test_invalid',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'store_property' => 'user_login',
				'reciprocal' => 'some_field',
			) ),
		) );

		$this->save_values( $test_invalid, $this->post, $this->author );
	}

	/**
	 * Test that this fails when a user doesn't have permission to list users.
	 * @expectedException WPDieException
	 */
	public function test_save_permissions() {
		wp_set_current_user( $this->author );

		$test_invalid = new Fieldmanager_Autocomplete( array(
			'name' => 'test_invalid',
			'datasource' => new Fieldmanager_Datasource_User(),
		) );

		$this->save_values( $test_invalid, $this->post, $this->editor );

		wp_set_current_user( $this->author );

		$this->save_values( $test_invalid, $this->post, $this->editor );
	}

	/**
	 * Test that display property returns the correct value in all reasonable cases.
	 */
	public function test_display_properties() {
		$user = get_userdata( $this->author );

		$test_display_name = new Fieldmanager_Autocomplete( array(
			'name' => 'test_display_name',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'display_property' => 'display_name',
			) ),
		) );

		$test_users_display_name = $test_display_name->datasource->get_items( $user->user_login );
		$this->assertEquals( $user->display_name, $test_users_display_name[ $user->ID ] );

		$test_user_login = new Fieldmanager_Autocomplete( array(
			'name' => 'test_user_login',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'display_property' => 'user_login',
			) ),
		) );

		$test_users_user_login = $test_user_login->datasource->get_items( $user->user_login );
		$this->assertEquals( $user->user_login, $test_users_user_login[ $user->ID ] );

		$test_user_email = new Fieldmanager_Autocomplete( array(
			'name' => 'test_user_email',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'display_property' => 'user_email',
			) ),
		) );

		$test_users_user_email = $test_user_email->datasource->get_items( $user->user_login );
		$this->assertEquals( $user->user_email, $test_users_user_email[ $user->ID ] );

		$test_user_nicename = new Fieldmanager_Autocomplete( array(
			'name' => 'test_user_nicename',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'display_property' => 'user_nicename',
			) ),
		) );

		$test_users_user_nicename = $test_user_nicename->datasource->get_items( $user->user_login );
		$this->assertEquals( $user->user_nicename, $test_users_user_nicename[ $user->ID ] );
	}

	/**
	 * Test that store property returns the correct display value in all reasonable cases.
	 */
	public function test_store_property_display() {
		$user = get_userdata( $this->author );

		$test_id = new Fieldmanager_Autocomplete( array(
			'name' => 'test_display_name',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'store_property' => 'ID',
			) ),
		) );

		$test_users_id = $test_id->datasource->get_items( $user->user_login );
		$this->assertEquals( $user->display_name, $test_users_id[ $user->ID ] );
		$this->assertEquals( $user->display_name, $test_id->datasource->get_value( $user->ID ) );

		$test_user_login = new Fieldmanager_Autocomplete( array(
			'name' => 'test_user_login',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'store_property' => 'user_login',
			) ),
		) );

		$test_users_user_login = $test_user_login->datasource->get_items( $user->user_login );
		$this->assertEquals( $user->display_name, $test_users_user_login[ $user->user_login ] );
		$this->assertEquals( $user->display_name, $test_user_login->datasource->get_value( $user->user_login ) );

		$test_user_email = new Fieldmanager_Autocomplete( array(
			'name' => 'test_user_email',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'store_property' => 'user_email',
			) ),
		) );

		$test_users_user_email = $test_user_email->datasource->get_items( $user->user_login );
		$this->assertEquals( $user->display_name, $test_users_user_email[ $user->user_email ] );
		$this->assertEquals( $user->display_name, $test_user_email->datasource->get_value( $user->user_email ) );

		$test_user_nicename = new Fieldmanager_Autocomplete( array(
			'name' => 'test_user_nicename',
			'datasource' => new Fieldmanager_Datasource_User( array(
				'store_property' => 'user_nicename',
			) ),
		) );

		$test_users_user_nicename = $test_user_nicename->datasource->get_items( $user->user_login );
		$this->assertEquals( $user->display_name, $test_users_user_nicename[ $user->user_nicename ] );
		$this->assertEquals( $user->display_name, $test_user_nicename->datasource->get_value( $user->user_nicename ) );
	}

	public function test_search() {
		$user = get_userdata( $this->author );
		$display_name = 'Lorem Ipsum';

		$fm = new Fieldmanager_Autocomplete( array(
			'name' => 'test_search',
			'datasource' => new Fieldmanager_Datasource_User
		) );

		$test_users_id = $fm->datasource->get_items( $display_name );
		$this->assertEquals( $display_name, $test_users_id[ $user->ID ] );
		$this->assertEquals( $display_name, $fm->datasource->get_value( $user->ID ) );

		$test_users_id = $fm->datasource->get_items( 'rem' );
		$this->assertEquals( $display_name, $test_users_id[ $user->ID ] );

		$test_users_id = $fm->datasource->get_items( 'test@test.com' );
		$this->assertEquals( $display_name, $test_users_id[ $user->ID ] );

		$test_users_id = $fm->datasource->get_items( 'author' );
		$this->assertEquals( $display_name, $test_users_id[ $user->ID ] );

		$test_users_id = $fm->datasource->get_items( $user->ID );
		$this->assertEquals( $display_name, $test_users_id[ $user->ID ] );
	}
}
