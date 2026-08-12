<?php

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Fieldmanager Datepicker Field
 */
#[Group( 'field' )]
#[Group( 'datepicker' )]
class FieldmanagerDatepickerFieldTest extends WP_UnitTestCase {
	/**
	 * The post object.
	 *
	 * @var WP_Post
	 */
	private WP_Post $post;

	/**
	 * The post ID.
	 *
	 * @var int
	 */
	private int $post_id;

	public function set_up() {
		parent::set_up();
		Fieldmanager_Field::$debug = true;

		// Create a post and capture it.
		$this->post = $this->factory->post->create_and_get(
			[
				'post_status'  => 'publish',
				'post_content' => rand_str(),
				'post_title'   => rand_str(),
			]
		);

		// Store the post ID.
		$this->post_id = $this->post->ID;
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

		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_date_group'] );

		$saved_data    = get_post_meta( $this->post_id, 'test_date_group', true );
		$input_element = $date_picker->form_element( $saved_data['test_date_field'] );

		$this->assertMatchesRegularExpression( sprintf( '#[^*]value="%s"[^*]#', $test_date ), $input_element );
	}

	/**
	 * Test behavior when using the time support for datepicker
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
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
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
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
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
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
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
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
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
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
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
		$base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data['test_datetime_group'] );
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

		$gmt_base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data );
		$gmt_stamp = get_post_meta( $this->post_id, 'test_gmt_time', true );
		$this->assertEquals( strtotime( '2014-03-13 02:37:00' ), intval( $gmt_stamp ) );

		$local_base->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $test_data );
		$local_stamp = get_post_meta( $this->post_id, 'test_local_time', true );
		$this->assertEquals( get_gmt_from_date( '2014-03-13 02:37:00', 'U' ), intval( $local_stamp ) );
		$this->assertEquals( strtotime( '2014-03-13 02:37:00 America/New_York' ), intval( $local_stamp ) );

		$this->assertEquals( $gmt_stamp - $local_stamp, -4 * HOUR_IN_SECONDS );
	}

	/**
	 * A store_local_time value must survive a re-save across a DST boundary without drifting.
	 *
	 * Rendering the stored GMT timestamp back to local time (form_element()) must use a
	 * DST-aware offset; otherwise it disagrees with presave()'s get_gmt_from_date() and the
	 * value shifts by an hour on every save. Regression test for #759.
	 */
	#[Group( 'dst' )]
	public function test_local_time_across_dst_change() {
		update_option( 'timezone_string', 'America/New_York' );

		$field = new Fieldmanager_Datepicker(
			array(
				'name'             => 'test_local_time',
				'use_time'         => true,
				'store_local_time' => true,
			)
		);

		// Noon on the day after the next DST transition, in the site timezone.
		$tz          = new DateTimeZone( 'America/New_York' );
		$transitions = $tz->getTransitions( time(), strtotime( '+1 year' ) );
		$local       = new DateTime( "@{$transitions[1]['ts']}" );
		$local->setTimezone( $tz );
		$local->modify( '+1 day' );
		$local->setTime( 12, 0 );

		$submitted = array(
			'date'   => $local->format( 'j M Y' ),
			'hour'   => '12',
			'minute' => '00',
			'ampm'   => 'pm',
		);

		// Saving stores the correct instant.
		$field->add_meta_box( 'test meta box', $this->post )->save_to_post_meta( $this->post_id, $submitted );
		$stored = (int) get_post_meta( $this->post_id, 'test_local_time', true );
		$this->assertSame( $local->getTimestamp(), $stored );

		// Rendering the stored value shows the same local hour it was entered with,
		// so a no-change re-save will not drift.
		$markup = $field->form_element( $stored );
		$this->assertStringContainsString( 'name="test_local_time[hour]"', $markup );
		$this->assertStringContainsString( 'value="12"', $markup );
	}
}
