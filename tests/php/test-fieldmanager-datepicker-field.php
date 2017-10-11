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

	public function data_date_field() {
		return array(
			array( 'date', '13 Mar 2014' ),
			array( 'date_altfield', '2014-03-13' ),
		);
	}

	/**
	 * Test behavior when using the time support for datepicker
	 *
	 * @dataProvider data_date_field
	 * @group 1111
	 *
	 * @param string $date_field The key in the array of submitted data with the date.
	 * @param string $date       The submitted date.
	 */
	public function test_time_feature( $date_field, $date ) {

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
					$date_field => '',
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
					$date_field => $date,
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
					$date_field => '',
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
					$date_field => $date,
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
					$date_field => $date,
					'hour'      => '2',
					'minute'    => '37',
					'ampm'      => 'am',
					),
				),
			);
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEquals( strtotime( '13 Mar 2014 2:37am' ), $saved_data['test_datetime_field'] );

		// Empty value; shouldn't be cast to an integer.
		$test_data = array(
			'test_datetime_group' => array(
				'test_datetime_field' => array(
					'date'      => '',
					'hour'      => '',
					'minute'    => '',
					'ampm'      => '',
					),
				),
			);
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
		$saved_data = get_post_meta( $this->post_id, 'test_datetime_group', true );
		$this->assertEquals( '', $saved_data['test_datetime_field'] );
	}

	/**
	 * @dataProvider data_date_field
	 *
	 * @param string $date_field The key in the array of submitted data with the date.
	 * @param string $date       The submitted date.
	 */
	public function test_local_time( $date_field, $date ) {
		update_option( 'timezone_string', 'America/New_York' );

		$gmt_base = new Fieldmanager_Datepicker( array( 'name' => 'test_gmt_time', 'use_time' => true ) );
		$local_base = new Fieldmanager_Datepicker( array( 'name' => 'test_local_time', 'use_time' => true, 'store_local_time' => true ) );

		$test_data = array(
			$date_field => $date,
			'hour'      => '2',
			'minute'    => '37',
			'ampm'      => 'am',
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


	public function data_presave_altfield_value() {
		return array(
			array( '', 0 ),
			array( 0, 0 ),
			array( '2000-01-01', strtotime( '2000-01-01' ) ),
		);
	}

	/**
	 * Test presave() for the expected Unix time for a given 'date_altfield' value.
	 *
	 * @dataProvider data_presave_altfield_value
	 *
	 * @param string $value    Date as YYYY-MM-DD.
	 * @param int    $expected Expected Unix time from Fieldmanager_Datepicker::presave() given the $value.
	 */
	public function test_presave_date_for_altfield( $value, $expected ) {
		$fm = new Fieldmanager_Datepicker();

		$this->assertSame(
			$expected,
			$fm->presave( array( 'date_altfield' => $value ) )
		);
	}

	public function data_render_altfield_for_js_opts() {
		return array(
			array( array(), true),
			array( array( 'dateFormat' => 'abc' ), true ),
			array( array( 'altField' => 'abc' ), false ),
			array( array( 'altFormat' => 'abc' ), false ),
			array( array( 'altField' => 'abc', 'altFormat' => 'def' ), false ),
		);
	}

	/**
	 * Test that FM's altField input renders when no user-supplied altField or altFormat options exist.
	 *
	 * @dataProvider data_render_altfield_for_js_opts
	 *
	 * @param array $js_opts      Fieldmanager_Datepicker::js_opts property.
	 * @param bool  $should_match Whether an altfield input should render given the $js_opts.
	 */
	public function test_render_altfield_for_js_opts( $js_opts, $should_match ) {
		$fm = new Fieldmanager_Datepicker( array( 'js_opts' => $js_opts ) );

		call_user_func(
			array( $this, ( $should_match ) ? 'assertRegExp' : 'assertNotRegExp' ),
			'/<input[^>]+?class="[^"]+?fm-datepicker-altfield/',
			$fm->form_element( '' )
		);
	}

	public function data_altfield_input_value() {
		return array(
			array( '', '' ),
			array( 0, '' ),
			array( strtotime( '2007-05-04' ), '2007-05-04' ),
		);
	}

	/**
	 * Test that the altField input includes the expected value attribute for a given field value.
	 *
	 * @dataProvider data_altfield_input_value
	 *
	 * @param int    $value    Field value.
	 * @param string $expected Expected input attribute value.
	 */
	public function test_altfield_input_value( $value, $expected ) {
		$fm = new Fieldmanager_Datepicker();

		$this->assertRegExp(
			sprintf( '/<input[^>]+?class="[^"]+?fm-datepicker-altfield[^>]+value="%s"/', preg_quote( $expected ) ),
			$fm->form_element( $value )
		);
	}
}
