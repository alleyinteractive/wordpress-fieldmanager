<input
	class="fm-element"
	type="<?php echo esc_attr( $this->input_type ) ?>"
	name="<?php echo esc_attr( $this->get_form_name() ); ?>"
	id="<?php echo esc_attr( $this->get_element_id() ); ?>"
	value="<?php echo esc_attr( $value ); ?>"
	<?php echo $this->get_element_attributes(); ?>
/>