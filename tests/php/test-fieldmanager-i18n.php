<?php

/**
 * Tests the Fieldmanager translations
 *
 * @group i18n
 */
class Test_Fieldmanager_Translations extends WP_UnitTestCase {

	protected $post;

	public function setUp() {

		parent::setUp();
		Fieldmanager_Field::$debug = true;

		// insert a post
		$this->post = $this->factory->post->create_and_get( array( 'post_title' => rand_str(), 'post_date' => '2016-07-20 00:00:00' ) );
	}

	public function test_basic_render_it_IT() {

		$current_locale = get_locale();
		$this->assertTrue( $this->_load_textdomain_locale( 'it_IT' ) );

		$args = array(
			'name' => 'test_media',
			'preview_size' => rand_str(),
		);
		$fm = new Fieldmanager_Media( $args );

		ob_start();
		$fm->add_meta_box( 'Test Media', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp(
			sprintf(
				'#<input type="button" class="[^"]*fm-media-button[^>]+value="%s" data-choose="%s" data-update="%s" data-preview-size="%s" data-mime-type="all" */>#',
				'Allega un file',
				'Scegli un allegato',
				'Seleziona allegato',
				$args['preview_size']
			),
			$html
		);

		// Restore previous translations (if any)
		unload_textdomain( 'fieldmanager' );
		$this->_load_textdomain_locale( $current_locale );
	}

	/**
	 * Helper which load localized translation file
	 *
	 * @param string $locale
	 *
	 * @return empty
	 */
	private function _load_textdomain_locale ( $locale = WPLANG ) {
		return load_textdomain( 'fieldmanager',  dirname( __FILE__ ) . '/../../languages/fieldmanager-' . $locale . '.mo' );
	}

}
