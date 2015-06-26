<?php

/**
 * Tests the Fieldmanager Autocomplete Field
 *
 * @group field
 * @group autocomplete
 */
class Test_Fieldmanager_Autocomplete_Field extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		Fieldmanager_Field::$debug = TRUE;

		$this->post_id = $this->factory->post->create( array( 'post_title' => rand_str(), 'post_date' => '2009-07-01 00:00:00' ) );
		$this->post = get_post( $this->post_id );

		$this->data_posts = array(
			$this->factory->post->create_and_get( array( 'post_title' => 'test ' . rand_str(), 'post_date' => '2009-07-02 00:00:00' ) ),
			$this->factory->post->create_and_get( array( 'post_title' => 'test ' . rand_str(), 'post_date' => '2009-07-03 00:00:00' ) ),
			$this->factory->post->create_and_get( array( 'post_title' => 'test ' . rand_str(), 'post_date' => '2009-07-04 00:00:00' ) ),
		);

		$this->custom_datasource = new Fieldmanager_Datasource( array(
			'options' => array( 'January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December' )
		) );
	}

	public function test_exact_match() {
		$args = array(
			'name'       => 'test_autocomplete',
			'datasource' => $this->custom_datasource,
			'default_value'    => rand_str(),
		);

		$fm = new Fieldmanager_Autocomplete( $args );
		ob_start();
		$fm->add_meta_box( 'Test Autocomplete', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/<input[^>]+type=[\'"]text[\'"][^>]+value=[\'"]{2}/', $html );

		$args['exact_match'] = false;
		$fm = new Fieldmanager_Autocomplete( $args );
		ob_start();
		$fm->add_meta_box( 'Test Autocomplete', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/<input[^>]+type=[\'"]text[\'"][^>]+value=[\'"]' . $args['default_value'] . '[\'"]/', $html );
	}

	public function test_ajax() {
		$datasource = new Fieldmanager_Datasource_Post();

		$fm = new Fieldmanager_Autocomplete( array(
			'name' => 'test_autocomplete',
			'datasource' => $datasource,
		) );

		$items = $datasource->get_items_for_ajax( 'test' );

		$this->assertSame( 3, count( $items ) );

		// we expect them to be returned in reverse chronological order
		// could have created the samples in that order but this seems more explicit...
		$this->assertSame( $this->data_posts[2]->ID, $items[0]['value'] );
		$this->assertSame( $this->data_posts[1]->ID, $items[1]['value'] );
		$this->assertSame( $this->data_posts[0]->ID, $items[2]['value'] );

		$items = $datasource->get_items_for_ajax( $this->data_posts[1]->ID );
		$this->assertSame( $this->data_posts[1]->ID, $items[0]['value'] );

		$items = $datasource->get_items_for_ajax( get_permalink( $this->data_posts[2]->ID ) );
		$this->assertSame( $this->data_posts[2]->ID, $items[0]['value'] );
	}

}
