<?php $cb_id = $this->get_element_id() . '-' . esc_attr( sanitize_text_field( $data_row['value'] ) ); ?>
<div class="fm-option">
	<input
		class="fm-element"
		type="checkbox"
		value="<?php echo esc_attr( $data_row['value'] ); ?>"
		name="<?php echo $this->get_form_name( '[]' ); ?>"
		id="<?php echo $cb_id; ?>"
		<?php echo $this->get_element_attributes(); ?>
		<?php echo $this->option_selected( $data_row['value'], $value, "checked" ); ?>
	/>
	<label for="<?php echo $cb_id; ?>" class="fm-option-label">
		<?php echo esc_html( $data_row['name'] ); ?>
	</label>
</div>