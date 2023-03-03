<?php

/**
 * Tests the Fieldmanager Datepicker Field
 *
 * @group field
 * @group datepicker
 */
class Test_Fieldmanager_Datepicker_Field extends WP_UnitTestCase {

	public function set_up() {
		parent::set_up();
		Fieldmanager_Field::$debug = true;

		// insert a post
		$this->post_id = self::factory()->post->create();
	}

	/**
	 * Test before 1970s date.
	 */
	public function test_before_1970s_date() {

		$date_picker = new Fieldmanager_Datepicker( array(
			'date_format' => 'm/d/Y',
		) );

		$base = new Fieldmanager_Group( array(
			'name'     => 'test_date_group',
			'children' => array(
				'test_date_field' => $date_picker,
			),
		) );

		// Date before 1970s
		$test_date = '04/23/1940';
		$test_data = array(
			'test_date_group' => array(
				'test_date_field' => array(
					'date' => $test_date,
				),
			),
		);

		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data['test_date_group'] );

		$saved_data    = get_post_meta( $this->post_id, 'test_date_group', true );
		$input_element = $date_picker->form_element( $saved_data['test_date_field'] );

		$this->assertMatchesRegularExpression( sprintf( '#[^*]value="%s"[^*]#', $test_date ), $input_element );
	}

	/**
	 * Test behavior when using the time support for datepicker
	 *
	 * @group 1111
	 */
	public function test_time_feature() {

		$base = new Fieldmanager_Group(
			array(
				'name'     => 'test_datetime_group',
				'children' => array(
					'test_datetime_field' => new Fieldmanager_Datepicker( array( 'use_time' => true ) ),
				),
			)
		);

		// No time fields set
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'   => '',
					'hour'   => '',
					'minute' => '',
					'ampm'   => 'am',
				),
			),
		);
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEmpty( $saved_data['test_datetime_field'] );

		// Date set, but no time
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'   => '13 Mar 2014',
					'hour'   => '',
					'minute' => '',
					'ampm'   => 'am',
				),
			),
		);
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEquals( strtotime( '13 Mar 2014' ), $saved_data['test_datetime_field'] );

		// Time set, but no date
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'   => '',
					'hour'   => '2',
					'minute' => '37',
					'ampm'   => 'am',
				),
			),
		);
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEquals( strtotime( '2:37 am' ), $saved_data['test_datetime_field'] );

		// Date set, time set, but no minutes
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'   => '13 Mar 2014',
					'hour'   => '2',
					'minute' => '',
					'ampm'   => 'am',
				),
			),
		);
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEquals( strtotime( '13 Mar 2014 2:00am' ), $saved_data['test_datetime_field'] );

		// Date and time set
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'   => '13 Mar 2014',
					'hour'   => '2',
					'minute' => '37',
					'ampm'   => 'am',
				),
			),
		);
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEquals( strtotime( '13 Mar 2014 2:37am' ), $saved_data['test_datetime_field'] );

		// Empty value; shouldn't be cast to an integer.
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'   => '',
					'hour'   => '',
					'minute' => '',
					'ampm'   => '',
				),
			),
		);
		$base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEquals( '', $saved_data['test_datetime_field'] );
	}

	public function test_local_time() {
		update_option( 'timezone_string', 'America/New_York' );

		$gmt_base   = new Fieldmanager_Datepicker(
			array(
				'name'     => 'test_gmt_time',
				'use_time' => true,
			)
		);
		$local_base = new Fieldmanager_Datepicker(
			array(
				'name'             => 'test_local_time',
				'use_time'         => true,
				'store_local_time' => true,
			)
		);

		$test_data = array(
			'date'   => '13 Mar 2014',
			'hour'   => '2',
			'minute' => '37',
			'ampm'   => 'am',
		);

		$gmt_base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data );
		$gmt_stamp = get_post_meta( $this->post_id, 'test_gmt_time', true );
		$this->assertEquals( strtotime( '2014-03-13 02:37:00' ), intval( $gmt_stamp ) );

		$local_base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data );
		$local_stamp = get_post_meta( $this->post_id, 'test_local_time', true );
		$this->assertEquals( get_gmt_from_date( '2014-03-13 02:37:00', 'U' ), intval( $local_stamp ) );
		$this->assertEquals( strtotime( '2014-03-13 02:37:00 America/New_York' ), intval( $local_stamp ) );

		$this->assertEquals( $gmt_stamp - $local_stamp, -4 * HOUR_IN_SECONDS );
	}

	public function test_local_time_after_time_change() {
		update_option( 'timezone_string', 'America/New_York' );

		$local_base = new Fieldmanager_Datepicker(
			array(
				'name'             => 'test_local_time',
				'use_time'         => true,
				'store_local_time' => true,
			)
		);

		// Determine when the next time shift is.
		$tz          = new DateTimeZone( 'America/New_York' );
		$transitions = $tz->getTransitions( time(), strtotime( '+1 year' ) );
		// Add a day to the time change timestamp for the test timestamp.
		$test_timestamp = $transitions[1]['ts'] + DAY_IN_SECONDS;

		// Create a DateTime object for noon of the day after the change, in ET.
		$test_time = new \DateTime( "@{$test_timestamp}" );
		$test_time->setTimezone( $tz );
		$test_time->setTime( 12, 0 );

		$test_data = array(
			'date'   => $test_time->format('j M Y' ),
			'hour'   => '12',
			'minute' => '00',
			'ampm'   => 'pm',
		);

		// Save the data and ensure that it converts properly.
		$local_base->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data );
		$local_stamp = get_post_meta( $this->post_id, 'test_local_time', true );
		$this->assertSame( $test_time->getTimestamp(), (int) $local_stamp );

		// Load the markup and ensure that the time converted back properly.
		$markup = $local_base->form_element( $local_stamp );
		$this->assertContains(
			'<input class="fm-element fm-datepicker-time" type="text" value="12" name="test_local_time[hour]" />',
			$markup
		);
	}
}
