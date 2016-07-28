<?php

abstract class Fieldmanager_Form {

	abstract public function save();

	abstract public function get_fields();

	abstract public function validate();

	protected function load_data() {
		// meant to be overridden, but not mandatory.
	}

	public function render() {

	}

	public function get_errors() {

	}

}