<?php

/**
 * A Javascript date-picker which submits dates as unix timestamps.
 *
 * @package Fieldmanager_Field
 */
class Fieldmanager_Datepicker extends Fieldmanager_Field {

	/**
	 * @var boolean
	 * Collect time info or just date info? Defaults to just date info.
	 */
	public $use_time = False;

	/**
	 * @var boolean
	 * If true, and $use_time == true, and $date_element = 'dropdown', will render an 'AM' and 'PM' dropdown
	 */
	public $use_am_pm = True;

	/**
	 * @var string
	 * PHP date format, only used for rendering already-saved dates. Use js_opts['dateFormat'] for the
	 * date shown when a user selects an option. This option renders to '21 Apr 2013', and is fairly
	 * friendly to international users.
	 */
	public $date_format = 'j M Y';

	/**
	 * @var boolean
	 * By default in WordPress, strtotime() assumes GMT. If $store_local_time is true, FM will use the
	 * site's timezone setting when generating the timestamp. Note that `date()` will return GMT times
	 * for the stamp no matter what, so if you store the local time, `date( 'H:i', $time )` will return
	 * the offset time. Use this option if the exact timestamp is important, e.g. to schedule a wp-cron
	 * event.
	 */
	public $store_local_time = false;

	/**
	 * @var array
	 * Options to pass to the jQueryUI Datepicker. If you change dateFormat, be sure that it returns
	 * a valid unix timestamp. Also, it's best to change js_opts['dateFormat'] and date_format together
	 * for a consistent user experience.
	 *
	 * Default:
	 * <code>
	 * array(
	 *   'showButtonPanel' => True,
	 *   'showOtherMonths' => True,
	 *   'selectOtherMonths' => True,
	 *   'dateFormat' => 'd M yy',
	 * );
	 * </code>
	 * @see http://api.jqueryui.com/datepicker/
	 */
	public $js_opts = array();

	/**
	 * Construct default attributes and enqueue javascript
	 * @param array $options
	 */
	public function __construct( $label = '', $options = array() ) {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		fm_add_style( 'fm-jquery-ui', 'css/jquery-ui/jquery-ui-1.10.2.custom.min.css' );
		fm_add_script( 'fm_datepicker', 'js/fieldmanager-datepicker.js', array( 'fieldmanager_script' ) );
		parent::__construct( $label, $options );

		if ( empty( $this->js_opts ) ) {
			$this->js_opts = array(
				'showButtonPanel' => True,
				'showOtherMonths' => True,
				'selectOtherMonths' => True,
				'dateFormat' => 'd M yy',
			);
		}
	}

	/**
	 * Generate HTML for the form element itself. Generally should be just one tag, no wrappers.
	 *
	 * @param mixed string[]|string the value of the element.
	 * @return string HTML for the element.
	 */
	public function form_element( $value ) {
		$value = absint( $value );
		$old_value = $value;
		// If we're storing the local time, in order to make the form work as expected, we have
		// to alter the timestamp. This isn't ideal, but there currently isn't a good way around
		// it in WordPress.
		if ( $this->store_local_time ) {
			$value += get_option( 'gmt_offset' ) * HOUR_IN_SECONDS;
		}
		ob_start();
		include fieldmanager_get_template( 'datepicker' );

		// Reset the timestamp
		$value = $old_value;
		return ob_get_clean();
	}

	/**
	 * Convert date to timestamp
	 * @param $value
	 * @param $current_value
	 * @return int unix timestamp
	 */
	public function presave( $value, $current_value = array() ) {
		$time_to_parse = sanitize_text_field( $value['date'] );
		if ( isset( $value['hour'] ) && is_numeric( $value['hour'] ) && $this->use_time ) {
			$hour = intval( $value['hour'] );
			$minute = ( isset( $value['minute'] ) && is_numeric( $value['minute'] ) ) ? intval( $value['minute'] ) : 0;
			if ( $hour == 0 && $this->use_am_pm ) $hour = 12;
			$time_to_parse .= ' ' . $hour;
			$time_to_parse .= ':' . str_pad( $minute, 2, '0', STR_PAD_LEFT );
			$time_to_parse .= ' ' . sanitize_text_field( $value['ampm'] );
		}
		if ( $this->store_local_time ) {
			return get_gmt_from_date( $time_to_parse, 'U' );
		} else {
			return intval( strtotime( $time_to_parse ) );
		}
	}

	/**
	 * Get hour for rendering in field
	 * @param int $value unix timestamp
	 * @return string value of hour
	 */
	public function get_hour( $value ) {
		return !empty( $value ) ? date( $this->use_am_pm ? 'g' : 'G', $value ) : '';
	}

	/**
	 * Get minute for rendering in field
	 * @param int $value unix timestamp
	 * @return string value of hour
	 */
	public function get_minute( $value ) {
		return !empty( $value ) ? date( 'i', $value ) : '';
	}

	/**
	 * Get am or pm for rendering in field
	 * @param int $value unix timestamp
	 * @return string 'am', 'pm', or ''
	 */
	public function get_am_pm( $value ) {
		return ! empty( $value ) ? date( 'a', $value ) : '';
	}

}
