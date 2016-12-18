<?php
/**
 * Default template for Fieldmanager_Datepicker.
 *
 * @package Fieldmanager
 */
?>

<input
	class="fm-element fm-datepicker-popup"
	type="text"
	data-datepicker-opts="<?php echo esc_attr( json_encode( $this->js_opts ) ); ?>"
	name="<?php echo esc_attr( $this->get_form_name( '[date]' ) ); ?>"
	id="<?php echo esc_attr( $this->get_element_id() ); ?>"
	<?php if ( ! empty( $value ) ) : ?>
		value="<?php echo esc_attr( date_i18n( $this->date_format, intval( $value ), true ) ); ?>"
	<?php endif; ?>
	<?php echo $this->get_element_attributes(); // Escaped interally. xss ok. ?>
/>

<?php // Render only if no user-supplied options already exist. ?>
<?php if ( empty( $this->js_opts['altField'] ) && empty( $this->js_opts['altFormat'] ) ) : ?>
	<?php include fieldmanager_get_template( 'datepicker-altfield' ); ?>
<?php endif; ?>

<?php if ( $this->use_time ) : ?>
	<span class="fm-datepicker-time-wrapper">
		@
		<input class="fm-element fm-datepicker-time" type="text" value="<?php echo esc_attr( $this->get_hour( $value ) ); ?>" name="<?php echo esc_attr( $this->get_form_name( '[hour]' ) ); ?>" />
		:
		<input class="fm-element fm-datepicker-time" type="text" value="<?php echo esc_attr( $this->get_minute( $value ) ); ?>" name="<?php echo esc_attr( $this->get_form_name( '[minute]' ) ); ?>" />
		<?php if ( $this->use_am_pm ) : ?>
			<select class="fm-element" name="<?php echo esc_attr( $this->get_form_name( '[ampm]' ) ); ?>">
				<option value="am"<?php selected( $this->get_am_pm( $value ), 'am' ) ?>><?php echo esc_html( $wp_locale->get_meridiem( 'AM' ) ) ?></option>
				<option value="pm"<?php selected( $this->get_am_pm( $value ), 'pm' ) ?>><?php echo esc_html( $wp_locale->get_meridiem( 'PM' ) ) ?></option>
			</select>
		<?php endif; ?>
	</span>
<?php endif; ?>
