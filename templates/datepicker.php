<?php
/**
 * Default template for Fieldmanager_Datepicker
 *
 * @package Fieldmanager
 */

?>

<input
	class="fm-element fm-datepicker-popup"
	type="text"
	data-datepicker-opts="<?php echo esc_attr( wp_json_encode( $this->js_opts ) ); ?>"
	name="<?php echo esc_attr( $this->get_form_name( '[date]' ) ); ?>"
	id="<?php echo esc_attr( $this->get_element_id() ); ?>"
	<?php if ( ! empty( $value ) ) : ?>
		value="<?php echo esc_attr( gmdate( $this->date_format, intval( $value ) ) ); ?>"
		<?php
	endif;
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- baseline
	echo $this->get_element_attributes(); // Escaped internally.
	?>
/>

<?php if ( $this->use_time ) : ?>
	<span class="fm-datepicker-time-wrapper">
		@
		<input class="fm-element fm-datepicker-time" type="text" value="<?php echo esc_attr( $this->get_hour( $value ) ); ?>" name="<?php echo esc_attr( $this->get_form_name( '[hour]' ) ); ?>" />
		:
		<input class="fm-element fm-datepicker-time" type="text" value="<?php echo esc_attr( $this->get_minute( $value ) ); ?>" name="<?php echo esc_attr( $this->get_form_name( '[minute]' ) ); ?>" />
		<?php if ( $this->use_am_pm ) : ?>
			<select class="fm-element" name="<?php echo esc_attr( $this->get_form_name( '[ampm]' ) ); ?>">
				<option value="am"<?php selected( $this->get_am_pm( $value ), 'am' ); ?>>A.M.</option>
				<option value="pm"<?php selected( $this->get_am_pm( $value ), 'pm' ); ?>>P.M.</option>
			</select>
		<?php endif; ?>
	</span>
<?php endif; ?>
