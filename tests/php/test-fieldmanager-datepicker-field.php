<?php

/**
 * Tests the Fieldmanager Datepicker Field
 *
 * @group field
 * @group datepicker
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
				'test_datetime_field' => new Fieldmanager_Datepicker( array( 'use_time' => true ) ),
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

		// Date set, time set, but no minutes
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'      => '13 Mar 2014',
					'hour'      => '2',
					'minute'    => '',
					'ampm'      => 'am',
					),
				),
			);
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEquals( strtotime( '13 Mar 2014 2:00am' ), $saved_data['test_datetime_field'] );

		// Date and time set
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

	public function test_local_time() {
		update_option( 'timezone_string', 'America/New_York' );

		$gmt_base = new Fieldmanager_Datepicker( array( 'name' => 'test_gmt_time', 'use_time' => true ) );
		$local_base = new Fieldmanager_Datepicker( array( 'name' => 'test_local_time', 'use_time' => true, 'store_local_time' => true ) );

		$test_data = array(
			'date'   => '13 Mar 2014',
			'hour'   => '2',
			'minute' => '37',
			'ampm'   => 'am',
		);

		$gmt_base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data );
		$gmt_stamp = get_post_meta( $this->post_id, 'test_gmt_time', true );
		$this->assertEquals( strtotime( '2014-03-13 02:37:00' ), intval( $gmt_stamp ) );

		$local_base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data );
		$local_stamp = get_post_meta( $this->post_id, 'test_local_time', true );
		$this->assertEquals( get_gmt_from_date( '2014-03-13 02:37:00', 'U' ), intval( $local_stamp ) );
		$this->assertEquals( strtotime( '2014-03-13 02:37:00 America/New_York' ), intval( $local_stamp ) );

		$this->assertEquals( $gmt_stamp - $local_stamp, -4 * HOUR_IN_SECONDS );
	}
}
