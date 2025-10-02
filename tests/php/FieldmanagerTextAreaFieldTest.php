<?php
/**
 * Test_Fieldmanager_TextArea_Field
 *
 * @package Fieldmanager
 */

use PHPUnit\Framework\Attributes\Group;

/**
 * Tests the Fieldmanager TextArea Field
 */
#[Group( 'field' )]
#[Group( 'textarea' )]
class FieldmanagerTextAreaFieldTest extends WP_UnitTestCase {

	/**
	 * The post ID of the test post.
	 *
	 * @var int
	 */
	private int $post_id = 0;

	/**
	 * The post object of the test post.
	 *
	 * @var WP_Post
	 */
	private WP_Post $post;

	/**
	 * Deprecation Error count.
	 *
	 * @var int
	 */
	private int $errors = 0;

	/**
	 * Sets up the requirements for the test.
	 */
	public function set_up() {
		parent::set_up();
		Fieldmanager_Field::$debug = true;

		$this->post_id = $this->factory->post->create(
			[
				'post_title' => rand_str(),
				'post_date'  => '2024-02-29 00:00:00',
			]
		);
		$this->post    = get_post( $this->post_id );

		set_error_handler( // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_set_error_handler
			/**
			 * Convert deprecations to exceptions. This was removed from PHPUnit in
			 * PHPUnit 10, so doing so manually here for future compatibility.
			 *
			 * @see https://github.com/sebastianbergmann/phpunit/issues/5062
			 */
			function () {
				$this->errors++;
				return true;
			},
			E_DEPRECATED | E_USER_DEPRECATED
		);
	}

	/**
	 * Restore test state.
	 */
	public function tear_down() {
		$this->errors = 0;
		// Restore default error handler.
		restore_error_handler();

		parent::tear_down();
	}

	/**
	 * Test to confirm that not defining a default value for a field
	 * does not cause that field to throw a deprecation warning when
	 * a textarea field is used.
	 *
	 * @see https://github.com/alleyinteractive/wordpress-fieldmanager/issues/863
	 */
	public function test_default_value_does_not_throw_deprecation() {
		ob_start();

		$fm = new Fieldmanager_Textarea(
			[
				'name'        => 'example-textarea',
				'description' => 'Description Text',
			]
		);
		$fm->add_meta_box( 'Test TextArea', 'post' )
			->render_meta_box( $this->post, array() );

		ob_get_clean();

		$this->assertSame(0, $this->errors);
	}
}
