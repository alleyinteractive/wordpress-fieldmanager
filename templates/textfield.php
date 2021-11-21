<?php
/**
 * Default template for Fieldmanager_TextField
 *
 * @package Fieldmanager
 */

?>
<input
	class="fm-element"
	type="<?php echo esc_attr( $this->input_type ); ?>"
	name="<?php echo esc_attr( $this->get_form_name() ); ?>"
	id="<?php echo esc_attr( $this->get_element_id() ); ?>"
	value="<?php echo esc_attr( $value ); ?>"
	<?php
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- baseline
	echo $this->get_element_attributes(); // Escaped internally.
	?>
/>
