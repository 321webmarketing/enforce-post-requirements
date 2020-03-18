<?php
/**
 * Class EnforcePostRequirementsTest
 *
 * @package Enforce_Post_Requirements
 */

/**
 * Sample test case.
 */
class EnforcePostRequirementsTest extends WP_UnitTestCase {
	private $test_user_id;

	public function setUp() {
		parent::setUp();

		$this->test_user_id = $this->factory->user->create(array(
			'role' => 'administrator',
			) );
		wp_set_current_user($this->test_user_id);
		//set_admin_role( true );
		//do_action('admin_init');
	}

	public function test_database_setup() {
		if(get_option( 'enforce_post_requirements_version' ) ) {
			$option_exists = true;
		}
		
		$this->assertTrue( $option_exists );
	}
	public function test_plugin_post() {
		
		$post_id = $this->factory->post->create( array(
												'post_author' => $this->test_user_id,
												'post_status' => 'draft', ) );
		
		
		try {
			wp_update_post(array( 'ID' => $post_id, 'post_status' => 'publish' ));
		} catch(WPDieException $exception) {

		}
		
		$this->assertTrue(is_a($exception, 'WPDieException'));
		$this->assertFalse( get_post_status( $post_id ) === 'publish');
	}
}
