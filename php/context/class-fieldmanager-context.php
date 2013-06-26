<?php
/**
 * @package Fieldmanager_Context
 */
 
/**
 * Base class for context
 * @package Fieldmanager_Context
 */
abstract class Fieldmanager_Context {
	
	/**
	 * @var Fieldmanager_Field
	 * The base field associated with this context
	 */
	public $fm = Null;
	
	/**
	 * @var string
	 * Unique ID of the form. Used for forms that are not built into WordPress.
	 */
	public $uniqid;
	
	/**
	 * @var string[]
	 * Array of IDs used for form validation, if enabled
	 */
	public $validate_form_ids;

}