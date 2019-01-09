<?php
/**
 * Tests Fieldmanager script and style enqueueing.
 *
 * @group assets
 */
class Test_Fieldmanager_Util_Assets extends Fieldmanager_Assets_Unit_Test_Case {

	/**
	 * First, test some basic assumptions about the script queue and how we're
	 * interacting with it.
	 */
	public function test_base_queue() {
		$this->assertSame( 0, did_action( 'admin_enqueue_scripts' ) );
		$this->assertSame( 0, did_action( 'wp_enqueue_scripts' ) );
		$this->assertFalse( wp_scripts()->query( 'fieldmanager_script' ) );
		$this->assertFalse( wp_scripts()->query( 'fieldmanager_script', 'enqueued' ) );
		$this->assertFalse( wp_scripts()->query( 'fieldmanager_script', 'to_do' ) );
		$this->assertFalse( wp_scripts()->query( 'fieldmanager_script', 'done' ) );

		$field = new Fieldmanager_TextField();
		do_action( 'admin_enqueue_scripts' );
		$this->assertNotFalse( wp_scripts()->query( 'fieldmanager_script' ) );
		$this->assertTrue( wp_scripts()->query( 'fieldmanager_script', 'enqueued' ) );
		$this->assertFalse( wp_scripts()->query( 'fieldmanager_script', 'to_do' ) );
		$this->assertFalse( wp_scripts()->query( 'fieldmanager_script', 'done' ) );
	}

	/**
	 * Creating a field before admin_enqueue_scripts runs should "pre-enqueue"
	 * it, which is to say that FM will store the attributes and enqueue it
	 * later
	 */
	public function test_delayed_enqueue() {
		$this->assertFalse( wp_scripts()->query( 'fieldmanager_script' ) );
		$field = new Fieldmanager_TextField();
		$this->assertFalse( wp_scripts()->query( 'fieldmanager_script' ) );
		do_action( 'admin_enqueue_scripts' );
		$this->assertNotFalse( wp_scripts()->query( 'fieldmanager_script' ) );
	}

	/**
	 * Creating a field after admin_enqueue_scripts runs should enqueue it
	 * instantly.
	 */
	public function test_late_enqueue() {
		do_action( 'admin_enqueue_scripts' );
		$this->assertFalse( wp_scripts()->query( 'fieldmanager_script' ) );
		$field = new Fieldmanager_TextField();
		$this->assertNotFalse( wp_scripts()->query( 'fieldmanager_script' ) );
	}
}
