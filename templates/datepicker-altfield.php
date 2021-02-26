<?php
/**
 * Default template for the 'altField' Datepicker input.
 *
 * @see Fieldmanager_Datepicker::js_opts, Fieldmanager_Datepicker::presave().
 *
 * @package Fieldmanager
 */
?>

<input
	class="fm-element fm-datepicker-altfield"
	type="hidden"
	name="<?php echo esc_attr( $this->get_form_name( '[date_altfield]' ) ); ?>"
	value="<?php echo esc_attr( empty( $value ) ? '' : date( 'Y-m-d', intval( $value ) ) ); ?>"
/>
