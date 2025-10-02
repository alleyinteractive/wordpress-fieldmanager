<?php
use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Term context using the deprecated Fieldmanager Term Meta. This will
 * help ensure backwards compatibility.
 */
#[Group( 'context' )]
#[Group( 'term' )]
#[Group( 'fm_term_meta' )]
class FieldmanagerContextTermFMTermMetaTest extends WP_UnitTestCase {
	/**
	 * Taxonomy name.
	 *
	 * @var string
	 */
	private string $taxonomy;

	/**
	 * Term ID
	 *
	 * @var int
	 */
	private int $term_id;

	/**
	 * Term Taxonomy ID.
	 *
	 * @var int
	 */
	private int $tt_id;

	/**
	 * The term.
	 *
	 * @var WP_Term
	 */
	private WP_Term $term;

	public function set_up() {
		parent::set_up();
		Fieldmanager_Field::$debug = true;

		$this->acting_as( 'administrator' );

		$this->taxonomy = 'category';
		$term           = wp_insert_term( 'test', $this->taxonomy );
		$this->term_id  = $term['term_id'];
		$this->tt_id    = $term['term_taxonomy_id'];

		// reload as proper object
		$this->term = get_term( $this->term_id, $this->taxonomy );
	}

	public function tear_down() {
		parent::tear_down();

		$meta = fm_get_term_meta( $this->term_id, $this->taxonomy );
		foreach ( $meta as $key => $value ) {
			fm_delete_term_meta( $this->term_id, $this->taxonomy, $key );
		}
	}

	/**
	 * Get valid test data.
	 * Several tests transform this data to somehow be invalid.
	 *
	 * @return array valid test data
	 */
	private function _get_valid_test_data() {
		return array(
			'base_group' => array(
				'test_basic'     => 'lorem ipsum<script>alert(/hacked!/);</script>',
				'test_textfield' => 'alley interactive',
				'test_htmlfield' => '<b>Hello</b> world',
				'test_extended'  => array(
					array(
						'extext' => array( 'first' ),
					),
					array(
						'extext' => array( 'second1', 'second2', 'second3' ),
					),
					array(
						'extext' => array( 'third' ),
					),
					array(
						'extext' => array( 'fourth' ),
					),
				),
			),
		);
	}

	/**
	 * Get a set of elements
	 *
	 * @return Fieldmanager_Group
	 */
	private function _get_elements() {
		return new Fieldmanager_Group(
			array(
				'name'     => 'base_group',
				'children' => array(
					'test_basic'     => new Fieldmanager_TextField(),
					'test_textfield' => new Fieldmanager_TextField(
						array(
						// 'index' => '_test_index',
						)
					),
					'test_htmlfield' => new Fieldmanager_Textarea(
						array(
							'sanitize' => 'wp_kses_post',
						)
					),
					'test_extended'  => new Fieldmanager_Group(
						array(
							'limit'    => 4,
							'children' => array(
								'extext' => new Fieldmanager_TextField(
									array(
										'limit'    => 0,
										'name'     => 'extext',
										'one_label_per_item' => false,
										'sortable' => true,
									// 'index' => '_extext_index',
									)
								),
							),
						)
					),
				),
			)
		);
	}

	private function _get_html_for( $field, $test_data = null ) {
		ob_start();
		$context = $field->add_term_form( 'test meta box', $this->taxonomy );
		if ( $test_data ) {
			$context->save_to_term_meta( $this->term_id, $this->taxonomy, $test_data );
			$context->edit_term_fields( $this->term, $this->taxonomy );
		} else {
			$context->add_term_fields( $this->taxonomy );
		}
		return ob_get_clean();
	}

	/**
	 * Fieldmanager_Field::add_term_form is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::get_term_meta is deprecated as of 1.0.0-beta.3
	 */
	public function test_context_render_add_form() {
		$base = $this->_get_elements();
		ob_start();
		$base->add_term_form( 'test meta box', $this->taxonomy )->add_term_fields( $this->taxonomy );
		$str = ob_get_clean();
		// we can't really care about the structure of the HTML, but we can make sure that all fields are here
		$this->assertMatchesRegularExpression( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $str );
		$this->assertMatchesRegularExpression( '/<input[^>]+type="text"[^>]+name="base_group\[test_basic\]"/', $str );
		$this->assertMatchesRegularExpression( '/<input[^>]+type="text"[^>]+name="base_group\[test_textfield\]"/', $str );
		$this->assertMatchesRegularExpression( '/<textarea[^>]+name="base_group\[test_htmlfield\]"/', $str );
		$this->assertStringContainsString('name="base_group[test_extended][0][extext][proto]"', $str );
		$this->assertStringContainsString('name="base_group[test_extended][0][extext][0]"', $str );
	}

	/**
	 * Fieldmanager_Field::add_term_form is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::get_term_meta is deprecated as of 1.0.0-beta.3
	 */
	public function test_context_render_edit_form() {
		$base = $this->_get_elements();
		ob_start();
		$base->add_term_form( 'test meta box', $this->taxonomy )->edit_term_fields( $this->term, $this->taxonomy );
		$str = ob_get_clean();
		// we can't really care about the structure of the HTML, but we can make sure that all fields are here
		$this->assertMatchesRegularExpression( '/<input[^>]+type="hidden"[^>]+name="fieldmanager-base_group-nonce"/', $str );
		$this->assertMatchesRegularExpression( '/<input[^>]+type="text"[^>]+name="base_group\[test_basic\]"/', $str );
		$this->assertMatchesRegularExpression( '/<input[^>]+type="text"[^>]+name="base_group\[test_textfield\]"/', $str );
		$this->assertMatchesRegularExpression( '/<textarea[^>]+name="base_group\[test_htmlfield\]"/', $str );
		$this->assertStringContainsString('name="base_group[test_extended][0][extext][proto]"', $str );
		$this->assertStringContainsString('name="base_group[test_extended][0][extext][0]"', $str );
	}

	/**
	 * Fieldmanager_Field::add_term_form is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::get_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::update_term_meta is deprecated as of 1.0.0-beta.3
	 */
	public function test_context_save() {
		$base      = $this->_get_elements();
		$test_data = $this->_get_valid_test_data();

		$base->add_term_form( 'test meta box', $this->taxonomy )->save_to_term_meta( $this->term_id, $this->taxonomy, $test_data['base_group'] );

		$saved_value = fm_get_term_meta( $this->term_id, $this->taxonomy, 'base_group', true );
		$saved_index = fm_get_term_meta( $this->term_id, $this->taxonomy, '_test_index', true );

		$this->assertEquals( $saved_value['test_basic'], 'lorem ipsum' );
		// $this->assertEquals( $saved_index, $saved_value['test_textfield'] );
		$this->assertEquals( $saved_value['test_textfield'], 'alley interactive' );
		$this->assertEquals( $saved_value['test_htmlfield'], '<b>Hello</b> world' );
		$this->assertEquals( count( $saved_value['test_extended'] ), 4 );
		$this->assertEquals( count( $saved_value['test_extended'][0]['extext'] ), 1 );
		$this->assertEquals( count( $saved_value['test_extended'][1]['extext'] ), 3 );
		$this->assertEquals( count( $saved_value['test_extended'][2]['extext'] ), 1 );
		$this->assertEquals( count( $saved_value['test_extended'][3]['extext'] ), 1 );
		$this->assertEquals( $saved_value['test_extended'][1]['extext'], array( 'second1', 'second2', 'second3' ) );
		$this->assertEquals( $saved_value['test_extended'][3]['extext'][0], 'fourth' );
	}

	/**
	 * Fieldmanager_Field::add_term_form is deprecated as of 1.0.0-beta.3
	 */
	public function test_programmatic_save_terms() {
		$base = $this->_get_elements();
		$base->add_term_form( 'test meta box', $this->taxonomy );

		$term = wp_insert_term( 'test-2', $this->taxonomy );
		$this->assertTrue( $term['term_id'] > 0 );
		$this->assertTrue( $term['term_taxonomy_id'] > 0 );

		wp_update_term( $term['term_id'], $this->taxonomy, array( 'name' => 'Alley' ) );
		$updated_term = get_term( $term['term_id'], $this->taxonomy );
		$this->assertEquals( 'Alley', $updated_term->name );
	}

	/**
	 * Fieldmanager_Field::add_term_form is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::get_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::delete_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::add_term_meta is deprecated as of 1.0.0-beta.3
	 */
	#[Group( 'serialize_data' )]
	public function test_unserialize_data_single_field() {
		$base = new Fieldmanager_TextField(
			array(
				'name'           => 'base_field',
				'limit'          => 0,
				'serialize_data' => false,
			)
		);
		$html = $this->_get_html_for( $base );
		$this->assertStringContainsString('name="base_field[0]"', $html );
		$this->assertStringNotContainsString( 'name="base_field[3]"', $html );

		$data = array( rand_str(), rand_str(), rand_str() );
		$html = $this->_get_html_for( $base, $data );
		$this->assertEquals( $data, fm_get_term_meta( $this->term_id, $this->taxonomy, 'base_field' ) );
		$this->assertStringContainsString('name="base_field[3]"', $html );
		$this->assertStringContainsString('value="' . $data[0] . '"', $html );
		$this->assertStringContainsString('value="' . $data[1] . '"', $html );
		$this->assertStringContainsString('value="' . $data[2] . '"', $html );
		$this->assertStringNotContainsString( 'name="base_field[4]"', $html );
	}

	/**
	 * Fieldmanager_Field::add_term_form is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::get_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::delete_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::add_term_meta is deprecated as of 1.0.0-beta.3
	 */
	#[Group( 'serialize_data' )]
	public function test_unserialize_data_single_field_sorting() {
		$item_1 = rand_str();
		$item_2 = rand_str();
		$item_3 = rand_str();
		$base   = new Fieldmanager_TextField(
			array(
				'name'           => 'base_field',
				'limit'          => 0,
				'serialize_data' => false,
			)
		);

		// Test as 1, 2, 3
		$data = array( $item_1, $item_2, $item_3 );
		$html = $this->_get_html_for( $base, $data );
		$this->assertEquals( $data, fm_get_term_meta( $this->term_id, $this->taxonomy, 'base_field' ) );
		$this->assertMatchesRegularExpression( '/<input[^>]+name="base_field\[0\][^>]+value="' . $item_1 . '"/', $html );
		$this->assertMatchesRegularExpression( '/<input[^>]+name="base_field\[1\][^>]+value="' . $item_2 . '"/', $html );
		$this->assertMatchesRegularExpression( '/<input[^>]+name="base_field\[2\][^>]+value="' . $item_3 . '"/', $html );

		// Reorder and test as 3, 1, 2
		$data = array( $item_3, $item_1, $item_2 );
		$html = $this->_get_html_for( $base, $data );
		$this->assertEquals( $data, fm_get_term_meta( $this->term_id, $this->taxonomy, 'base_field' ) );
		$this->assertMatchesRegularExpression( '/<input[^>]+name="base_field\[0\][^>]+value="' . $item_3 . '"/', $html );
		$this->assertMatchesRegularExpression( '/<input[^>]+name="base_field\[1\][^>]+value="' . $item_1 . '"/', $html );
		$this->assertMatchesRegularExpression( '/<input[^>]+name="base_field\[2\][^>]+value="' . $item_2 . '"/', $html );
	}

	/**
	 * Fieldmanager_Field::add_term_form is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::get_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::update_term_meta is deprecated as of 1.0.0-beta.3
	 */
	#[Group( 'serialize_data' )]
	public function test_unserialize_data_tabbed() {
		$base = new Fieldmanager_Group(
			array(
				'name'           => 'base_group',
				'tabbed'         => true,
				'serialize_data' => false,
				'add_to_prefix'  => false,
				'children'       => array(
					'tab-1' => new Fieldmanager_Group(
						array(
							'label'          => 'Tab One',
							'serialize_data' => false,
							'add_to_prefix'  => false,
							'children'       => array(
								'test_text' => new Fieldmanager_TextField( 'Text Field' ),
							),
						)
					),
					'tab-2' => new Fieldmanager_Group(
						array(
							'label'          => 'Tab Two',
							'serialize_data' => false,
							'add_to_prefix'  => false,
							'children'       => array(
								'test_textarea' => new Fieldmanager_TextArea( 'TextArea' ),
							),
						)
					),
				),
			)
		);
		$data = array(
			'tab-1' => array(
				'test_text' => rand_str(),
			),
			'tab-2' => array(
				'test_textarea' => rand_str(),
			),
		);

		$html = $this->_get_html_for( $base, $data );
		$this->assertEquals( $data['tab-1']['test_text'], fm_get_term_meta( $this->term_id, $this->taxonomy, 'test_text', true ) );
		$this->assertEquals( $data['tab-2']['test_textarea'], fm_get_term_meta( $this->term_id, $this->taxonomy, 'test_textarea', true ) );
		$this->assertStringContainsString('name="base_group[tab-1][test_text]"', $html );
		$this->assertStringContainsString('value="' . $data['tab-1']['test_text'] . '"', $html );
		$this->assertStringContainsString('name="base_group[tab-2][test_textarea]"', $html );
		$this->assertStringContainsString('>' . $data['tab-2']['test_textarea'] . '</textarea>', $html );
	}

	/**
	 * Fieldmanager_Field::add_term_form is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::get_term_meta is deprecated as of 1.0.0-beta.3
	 * Fieldmanager_Util_Term_Meta::update_term_meta is deprecated as of 1.0.0-beta.3
	 */
	#[Group( 'serialize_data' )]
	public function test_unserialize_data_mixed_depth() {
		$base = new Fieldmanager_Group(
			array(
				'name'           => 'base_group',
				'serialize_data' => false,
				'children'       => array(
					'test_text'  => new Fieldmanager_TextField(),
					'test_group' => new Fieldmanager_Group(
						array(
							'serialize_data' => false,
							'children'       => array(
								'deep_text' => new Fieldmanager_TextArea(),
							),
						)
					),
				),
			)
		);

		$data = array(
			'test_text'  => rand_str(),
			'test_group' => array(
				'deep_text' => rand_str(),
			),
		);

		$html = $this->_get_html_for( $base, $data );
		$this->assertEquals( $data['test_text'], fm_get_term_meta( $this->term_id, $this->taxonomy, 'base_group_test_text', true ) );
		$this->assertEquals( $data['test_group']['deep_text'], fm_get_term_meta( $this->term_id, $this->taxonomy, 'base_group_test_group_deep_text', true ) );
		$this->assertStringContainsString('name="base_group[test_text]"', $html );
		$this->assertStringContainsString('value="' . $data['test_text'] . '"', $html );
		$this->assertStringContainsString('name="base_group[test_group][deep_text]"', $html );
		$this->assertStringContainsString('>' . $data['test_group']['deep_text'] . '</textarea>', $html );
	}
}
