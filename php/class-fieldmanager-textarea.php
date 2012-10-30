<?php
class Fieldmanager_TextArea extends Fieldmanager_Field {

	public $field_class = 'text';

	public function __construct( $options = array() ) {
		$this->attributes = array(
			'cols' => '50',
			'rows' => '10'
		);
		parent::__construct($options);
	}

	public function form_element( $value = '' ) {
		return sprintf(
			'<textarea class="fm-element" name="%s" id="%s" %s />%s</textarea>',
			$this->get_form_name(),
			$this->get_element_id(),
			$this->get_element_attributes(),
			htmlspecialchars( $value )
		);
	}

	public function validate( $value ) {

	}

	public function sanitize( $value ) {

	}

}