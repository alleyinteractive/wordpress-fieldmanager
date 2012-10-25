<?php

function fm_required( &$value, &$field ) {
	if ( empty( $value ) ) {
		$field->set_error( sprintf( __( '%1$s is a required field' ), $field->label ) );
	}
}

function fm_validate_number( &$value, &$field ) {
	if ( !is_numeric( $value ) ) {
		$field->set_error( sprintf( __( '%1$s is a required field' ), $field->label ) );	
	}
}