<?php
/**
 * Default template for Fieldmanager_Radios
 *
 * @package Fieldmanager
 */

$fm_cb_id = $this->get_element_id() . '-' . esc_attr( sanitize_text_field( $data_row['value'] ) );
?>

<div class="fm-option">
	<input
		class="fm-element"
		type="radio"
		value="<?php echo esc_attr( $data_row['value'] ); ?>"
		name="<?php echo esc_attr( $this->get_form_name() ); ?>"
		id="<?php echo esc_attr( $fm_cb_id ); ?>"
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- baseline
		echo $this->get_element_attributes(); // Escaped internally.
		?>
		<?php
		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- baseline
		echo $this->option_selected( $data_row['value'], $value, 'checked' ); // Escaped internally.
		?>
	/>
	<label for="<?php echo esc_attr( $fm_cb_id ); ?>" class="fm-option-label">
		<?php echo esc_html( $data_row['name'] ); ?>
	</label>
</div>
