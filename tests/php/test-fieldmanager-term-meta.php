<?php

/**
 * Tests the Fieldmanager Term Meta
 */
class Test_Fieldmanager_Term_Meta extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = true;

		$this->term = $this->factory->category->create_and_get( array( 'name' => rand_str() ) );
	}

	/**
	 * Set up the request environment values and save the data.
	 *
	 * @param Fieldmanager_Field $field
	 * @param WP_Post $post
	 * @param mixed $values
	 */
	public function save_values( $field, $term, $values ) {
		$field->add_term_form( $field->name, $term->taxonomy )->save_to_term_meta( $term->term_id, $term->taxonomy, $values );
	}

	/**
	 * Test behavior when using the term meta fields.
	 */
	public function test_save_term_meta() {
		$term_option = new Fieldmanager_Textfield( array(
			'name'  => 'term_option',
		) );

		$text = rand_str();
		$this->save_values( $term_option, $this->term, $text );

		$data = fm_get_term_meta( $this->term->term_id, $this->term->taxonomy, 'term_option', true );
		$this->assertEquals( $text, $data );

		$data = fm_get_term_meta( $this->term->term_id, $this->term->taxonomy, 'term_option', false );
		$this->assertEquals( array( $text ), $data );
	}
}
