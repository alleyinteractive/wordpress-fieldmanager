<?php

/**
 * Tests the Fieldmanager RichTextArea Field
 *
 * @group field
 * @group richtextarea
 */
class Test_Fieldmanager_RichTextArea_Field extends WP_UnitTestCase {

	public function setUp() {
		parent::setUp();
		add_filter( 'user_can_richedit', '__return_true' );
		Fieldmanager_Field::$debug = TRUE;

		$this->post = array(
			'post_status' => 'publish',
			'post_content' => rand_str(),
			'post_title' => rand_str(),
		);

		// insert a post
		$this->post_id = wp_insert_post( $this->post );
		$this->post = get_post( $this->post_id );
	}

	public function tearDown() {
		remove_filter( 'user_can_richedit', '__return_true' );
		parent::tearDown();
	}

	/**
	 * Copied from core because `set_user_setting()` doesn't appear to work in
	 * phpunit. The core function checks if `headers_sent()`, which seems to
	 * return true when testing. Therefore, this is a clone with that removed.
	 *
	 * Add or update user interface setting.
	 *
	 * Both $name and $value can contain only ASCII letters, numbers and underscores.
	 * This function has to be used before any output has started as it calls setcookie().
	 *
	 * @param string $name The name of the setting.
	 * @param string $value The value for the setting.
	 * @return bool true if set successfully/false if not.
	 */
	protected function _set_user_setting( $name, $value ) {
		$all_user_settings = get_all_user_settings();
		$all_user_settings[ $name ] = $value;

		return wp_set_all_user_settings( $all_user_settings );
	}

	/**
	 * Mark a test as skipped because of the current WP Version. Note that
	 * skipping should happen in place of assertion, but any test parameters
	 * should still happen (to ensure that a feature doesn't cause an error in
	 * an old version of WordPress).
	 *
	 * @param  float $min_version The required version, e.g. 3.9.
	 */
	protected function _skip_tests_because_version( $min_version ) {
		global $wp_version;
		$this->markTestSkipped( "Test requires WordPress {$min_version} or greater, but we're currently testing against {$wp_version}" );
	}


	public function test_basic_render() {
		$fm = new Fieldmanager_RichTextArea( array( 'name' => 'test_richtextarea' ) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/<textarea class="fm-element fm-richtext wp-editor-area"[^>]+name="test_richtextarea"/', $html );
		$this->assertNotContains( 'fm-richtext-remember-editor', $html );

		ob_start();
		_WP_Editors::editor_js();
		$js = ob_get_clean();
		$this->assertContains( $fm->get_element_id(), $js );
		$this->assertContains( 'wp_skip_init:true', $js );
	}

	public function test_repeatable_render() {
		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'limit' => 0,
		) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();
		$this->assertRegExp( '/<textarea class="fm-element fm-richtext wp-editor-area"[^>]+name="test_richtextarea\[0\]"/', $html );
		$this->assertRegExp( '/<textarea data-proto-id="fm-test_richtextarea-proto" class="fm-element fm-richtext wp-editor-area"[^>]+name="test_richtextarea\[proto\]"/', $html );

		ob_start();
		_WP_Editors::editor_js();
		$js = ob_get_clean();

		$this->assertContains( $fm->get_element_id(), $js );
		$this->assertContains( 'fm-test_richtextarea-proto', $js );
		$this->assertContains( 'wp_skip_init:true', $js );
	}

	public function test_default_value() {
		$value = "<h1>Lorem Ipsum</h1>\n<p>Dolor sit <a href='#'>amet</a></p>";
		$fm = new Fieldmanager_RichTextArea( array( 'name' => 'test_richtextarea', 'default_value' => $value ) );
		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		if ( _fm_phpunit_is_wp_at_least( 4.3 ) ) {
			// Core always adds this since 4.3. We'll do this too to match that
			// functionality. It will be removed if this is at least 4.5.
			add_filter( 'the_editor_content', 'format_for_editor', 10, 2 );
		}

		$this->assertRegExp( '/<textarea[^>]+>' . preg_quote( apply_filters( 'the_editor_content', $value, 'tinymce' ), '/' ) . "<\/textarea>/", $html );

		// WordPress 4.5 fixed an issue with multiple editors and this filter.
		// _WP_Editors::editor() now removes it after use, which is what we'll
		// do here to match that functionality.
		if ( _fm_phpunit_is_wp_at_least( 4.5 ) ) {
			remove_filter( 'the_editor_content', 'format_for_editor' );
		}
	}

	public function test_custom_buttons() {
		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'buttons_1' => array( 'bold', 'italic' ),
			'buttons_2' => array( 'bullist', 'fieldmanager' ),
			'buttons_3' => array( 'numlist' ),
			'buttons_4' => array( 'link', 'unlink' ),
		) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		ob_start();
		_WP_Editors::editor_js();
		$js = ob_get_clean();

		if ( strpos( $js, 'theme_advanced_buttons2' ) ) {
			// WP 3.8 uses an older version of tinymce, with different names for the toolbars
			$toolbar = 'theme_advanced_buttons';
		} else {
			$toolbar = 'toolbar';
		}
		$this->assertContains( $toolbar . '1:"bold,italic"', $js );
		$this->assertContains( $toolbar . '2:"bullist,fieldmanager"', $js );
		$this->assertContains( $toolbar . '3:"numlist"', $js );
		$this->assertContains( $toolbar . '4:"link,unlink"', $js );
	}

	public function test_teeny_custom_buttons() {
		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'buttons_1' => array( 'bold', 'italic' ),
			'buttons_2' => array( 'bullist', 'fieldmanager' ),
			'buttons_3' => array( 'numlist' ),
			'buttons_4' => array( 'link', 'unlink' ),
			'editor_settings' => array( 'teeny' => true ),
		) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		ob_start();
		_WP_Editors::editor_js();
		$js = ob_get_clean();

		if ( strpos( $js, 'theme_advanced_buttons2' ) ) {
			// WP 3.8 uses an older version of tinymce, with different names for the toolbars
			$toolbar = 'theme_advanced_buttons';
		} else {
			$toolbar = 'toolbar';
		}
		$this->assertContains( $toolbar . '1:"bold,italic"', $js );
		$this->assertContains( $toolbar . '2:""', $js );
		$this->assertContains( $toolbar . '3:""', $js );
		$this->assertContains( $toolbar . '4:""', $js );
	}

	public function test_default_editor_mode() {
		$fm = new Fieldmanager_RichTextArea( array( 'name' => 'test_richtextarea' ) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		if ( _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->assertContains( 'tmce-active', $html );
			$this->assertNotContains( 'html-active', $html );
		} else {
			$this->_skip_tests_because_version( 3.9 );
		}
	}

	public function test_tinymce_editor_mode() {
		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'editor_settings' => array(
				'default_editor' => 'tinymce',
			)
		) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		if ( _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->assertContains( 'tmce-active', $html );
			$this->assertNotContains( 'html-active', $html );
		} else {
			$this->_skip_tests_because_version( 3.9 );
		}
	}

	public function test_html_editor_mode() {
		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'editor_settings' => array(
				'default_editor' => 'html',
			)
		) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		if ( _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->assertContains( 'html-active', $html );
			$this->assertNotContains( 'tmce-active', $html );
		} else {
			$this->_skip_tests_because_version( 3.9 );
		}
	}

	public function test_cookie_editor_mode() {
		$current_user = get_current_user_id();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'editor_settings' => array(
				'default_editor' => 'cookie',
			)
		) );
		$setting_key = str_replace( '-', '_', $fm->get_element_id() );
		$setting_key = 'editor_' . preg_replace( '/[^a-z0-9_]/i', '', $setting_key );

		// Test that the default is tinymce
		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		if ( _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->assertContains( 'fm-richtext-remember-editor', $html );
			$this->assertContains( 'tmce-active', $html );
			$this->assertNotContains( 'html-active', $html );
		}


		// Test that it becomes html when we set the user setting to 'html'
		$this->_set_user_setting( $setting_key, 'html' );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		if ( _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->assertContains( 'fm-richtext-remember-editor', $html );
			$this->assertContains( 'html-active', $html );
			$this->assertNotContains( 'tmce-active', $html );
		}


		// Test that it becomes tinymce again when we set the user setting to 'tinymce'
		$this->_set_user_setting( $setting_key, 'tinymce' );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		if ( _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->assertContains( 'fm-richtext-remember-editor', $html );
			$this->assertContains( 'tmce-active', $html );
			$this->assertNotContains( 'html-active', $html );
		}

		wp_set_current_user( $current_user );

		if ( ! _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->_skip_tests_because_version( 3.9 );
		}
	}

	public function test_cookie_editor_mode_with_repeatables() {
		$current_user = get_current_user_id();
		wp_set_current_user( $this->factory->user->create( array( 'role' => 'administrator' ) ) );

		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'limit' => 0,
			'editor_settings' => array(
				'default_editor' => 'cookie',
			)
		) );
		$setting_key = str_replace( '-', '_', $fm->get_element_id() );
		$setting_key = 'editor_' . preg_replace( '/[^a-z0-9_]/i', '', $setting_key );

		// Test that the default is tinymce
		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		if ( _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->assertContains( 'fm-richtext-remember-editor', $html );
			$this->assertContains( 'tmce-active', $html );
			$this->assertNotContains( 'html-active', $html );
		}

		// Test that it becomes html when we set the user setting to 'html'
		$this->_set_user_setting( $setting_key, 'html' );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html = ob_get_clean();

		if ( _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->assertContains( 'fm-richtext-remember-editor', $html );
			// The one field should contain html-active
			$this->assertContains( 'html-active', $html );
			// The proto should still contain tmce-active
			$this->assertContains( 'tmce-active', $html );
		}

		wp_set_current_user( $current_user );

		if ( ! _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->_skip_tests_because_version( 3.9 );
		}
	}

	public function test_editor_mode_conflicts() {
		$args = array(
			'name' => 'test_richtextarea_1',
			'default_value' => '<p>some <strong>html</strong> content</p>',
			'editor_settings' => array(
				'default_editor' => 'html',
			)
		);

		$fm = new Fieldmanager_RichTextArea( $args );
		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html_1 = ob_get_clean();

		$args['name'] = 'test_richtextarea_2';
		$args['editor_settings']['default_editor'] = 'tinymce';
		$fm = new Fieldmanager_RichTextArea( $args );
		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html_2 = ob_get_clean();

		$args['name'] = 'test_richtextarea_3';
		$args['editor_settings']['default_editor'] = 'html';
		$fm = new Fieldmanager_RichTextArea( $args );
		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		$html_3 = ob_get_clean();

		if ( _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->assertContains( 'html-active', $html_1 );
			$this->assertNotContains( 'tmce-active', $html_1 );

			$this->assertContains( 'tmce-active', $html_2 );
			$this->assertNotContains( 'html-active', $html_2 );

			$this->assertContains( 'html-active', $html_3 );
			$this->assertNotContains( 'tmce-active', $html_3 );
		} else {
			$this->_skip_tests_because_version( 3.9 );
		}

		if ( _fm_phpunit_is_wp_at_least( 4.3 ) ) {
			$this->assertContains( format_for_editor( $args['default_value'], 'html' ), $html_1 );
			$this->assertContains( format_for_editor( $args['default_value'], 'tinymce' ), $html_2 );
			$this->assertContains( format_for_editor( $args['default_value'], 'html' ), $html_3 );
		} elseif ( _fm_phpunit_is_wp_at_least( 3.9 ) ) {
			$this->assertContains( wp_htmledit_pre( $args['default_value'] ), $html_1 );
			$this->assertContains( wp_richedit_pre( $args['default_value'] ), $html_2 );
			$this->assertContains( wp_htmledit_pre( $args['default_value'] ), $html_3 );
		} else {
			$this->_skip_tests_because_version( 3.9 );
		}
	}

	public function test_css_override() {
		$css_url = 'http://example.org/' . rand_str() . '.css';
		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'stylesheet' => $css_url,
		) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		ob_end_clean();

		ob_start();
		_WP_Editors::editor_js();
		$js = ob_get_clean();

		$this->assertContains( $css_url, $js );
	}

	public function test_teeny_css_override() {
		$css_url = 'http://example.org/' . rand_str() . '.css';
		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'stylesheet' => $css_url,
			'editor_settings' => array( 'teeny' => true ),
		) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		ob_end_clean();

		ob_start();
		_WP_Editors::editor_js();
		$js = ob_get_clean();

		$this->assertContains( $css_url, $js );
	}

	public function test_tinymce_overrides() {
		$data = array(
			'fm_test' => rand_str(),
			rand( 100, 10000 ) => rand_str(),
			strval( rand( 100, 10000 ) ) => rand( 100, 10000 ),
		);
		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'editor_settings' => array(
				'tinymce' => $data,
			),
		) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		ob_end_clean();

		ob_start();
		_WP_Editors::editor_js();
		$js = ob_get_clean();

		$i = 0;
		$format = '%s:"%s"';
		foreach ( $data as $key => $value ) {
			if ( is_numeric( $key ) ) {
				$key = $i++;
			}
			$this->assertContains( sprintf( $format, $key, $value ), $js );
		}
	}

	/**
	 * This feature is deprecated, but we still try to support it.
	 */
	public function test_init_options() {
		$value = rand_str();
		$value_2 = rand_str();
		$css_url = 'http://example.org/' . rand_str() . '.css';
		$css_url_2 = 'http://example.org/' . rand_str() . '.css';

		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'init_options' => array(
				'fm_test' => $value,
				'content_css' => $css_url,
			),
		) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		ob_end_clean();

		ob_start();
		_WP_Editors::editor_js();
		$js = ob_get_clean();

		$this->assertContains( 'fm_test:"' . $value . '"', $js );
		$this->assertContains( $css_url, $js );

		// Now test that `editor_settings` and `stylesheet` take precedence
		$fm = new Fieldmanager_RichTextArea( array(
			'name' => 'test_richtextarea',
			'init_options' => array(
				'fm_test' => $value,
				'content_css' => $css_url,
			),
			'stylesheet' => $css_url_2,
			'editor_settings' => array(
				'tinymce' => array( 'fm_test' => $value_2 )
			)
		) );

		ob_start();
		$fm->add_meta_box( 'Test RichTextArea', 'post' )->render_meta_box( $this->post, array() );
		ob_end_clean();

		ob_start();
		_WP_Editors::editor_js();
		$js = ob_get_clean();

		$this->assertNotContains( 'fm_test:"' . $value . '"', $js );
		$this->assertNotContains( $css_url, $js );
		$this->assertContains( 'fm_test:"' . $value_2 . '"', $js );
		$this->assertContains( $css_url_2, $js );
	}

	public function test_basic_save() {
		$test_data = "<h1>Lorem Ipsum</h1>\n<p>Dolor sit <a href='#'>amet</a></p>";
		$fm = new Fieldmanager_RichTextArea( array( 'name' => 'test_richtextarea' ) );

		$fm->add_meta_box( 'test meta box', 'post' )->save_to_post_meta( $this->post_id, $test_data );
		$saved_data = get_post_meta( $this->post_id, 'test_richtextarea', true );
		$this->assertEquals( $test_data, trim( $saved_data ) );
	}

	/**
	 * To attain code coverage 100%. Why not?
	 */
	public function test_customize_buttons_filter() {
		$fm = new Fieldmanager_RichTextArea( array( 'name' => 'test_richtextarea' ) );
		$value = rand_str();
		$this->assertEquals( $value, $fm->customize_buttons( $value ) );
	}
}