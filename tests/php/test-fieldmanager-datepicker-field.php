<?php

/**
 * Tests the Fieldmanager Datepicker Field
 */
class Test_Fieldmanager_Datepicker_Field extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = TRUE;

		$this->post = array(
			'post_status' => 'publish',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		);

		// insert a post
		$this->post_id = wp_insert_post( $this->post );
	}

	/**
	 * Test behavior when using the time support for datepicker
	 * 
	 * @group 1111
	 */
	public function test_time_feature() {

		$base = new Fieldmanager_Group( array(
			'name'       => 'test_datetime_group',
			'children'   => array(
				'test_datetime_field' => new Fieldmanager_Datepicker( false, array( 'use_time' => true ) ),
				),
			) );

		// No time fields set
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'      => '',
					'hour'      => '',
					'minute'    => '',
					'ampm'      => 'am',
					),
				),
			);
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEmpty( $saved_data['test_datetime_field'] );

		// Date set, but no time
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'      => '13 Mar 2014',
					'hour'      => '',
					'minute'    => '',
					'ampm'      => 'am',
					),
				),
			);
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEquals( strtotime( '13 Mar 2014' ), $saved_data['test_datetime_field'] );

		// Time set, but no date
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'      => '',
					'hour'      => '2',
					'minute'    => '37',
					'ampm'      => 'am',
					),
				),
			);
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEquals( strtotime( '2:37 am' ), $saved_data['test_datetime_field'] );


		// Date and time set
		// Time set, but no date
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'      => '13 Mar 2014',
					'hour'      => '2',
					'minute'    => '37',
					'ampm'      => 'am',
					),
				),
			);
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEquals( strtotime( '13 Mar 2014 2:37am' ), $saved_data['test_datetime_field'] );

	}

}