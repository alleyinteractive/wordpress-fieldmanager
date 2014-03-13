<?php
/**
 * @package Fieldmanager
 */

/**
 * A Javascript date-picker which stores dates as unix timestamps.
 * @package Fieldmanager
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
	public function __construct( $label, $options = array() ) {
		wp_enqueue_script( 'jquery-ui-datepicker' );
		fm_add_style( 'fm-jquery-ui', 'css/jquery-ui/jquery-ui-1.10.2.custom.min.css' );
		fm_add_script( 'fm_datepicker', 'js/fieldmanager-datepicker.js' );
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
	 * Convert date to timestamp
	 * @param $value
	 * @param $current_value
	 * @return int unix timestamp
	 */
	public function presave( $value, $current_value = array() ) {
		$time_to_parse = sanitize_text_field( $value['date'] );
		if ( is_numeric( $value['hour'] ) && is_numeric( $value['minute'] ) && $this->use_time ) {
			$hour = intval( $value['hour'] );
			if ( $hour == 0 && $this->use_am_pm ) $hour = 12;
			$time_to_parse .= ' ' . $hour;
			$time_to_parse .= ':' . str_pad( intval( $value['minute'] ), 2, '0', STR_PAD_LEFT );
			$time_to_parse .= ' ' . sanitize_text_field( $value['ampm'] );
		}
		return intval( strtotime( $time_to_parse ) );
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
	 * Get hour for rendering in field
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