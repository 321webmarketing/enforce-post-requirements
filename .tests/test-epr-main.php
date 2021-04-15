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
	private $test_admin_id;
	private $test_company_author_id;
	private $test_tto_poster_id;
	private $default_args = [];

	public function setUp() {
		parent::setUp();

		$this->test_admin_id = $this->factory->user->create(array(
			'role' => 'administrator',
			) );
		$this->test_tto_poster_id = $this->factory->user->create(array(
			'role' => 'author',
			'name' => 'tto_poster.test.poster',
		) );
		$this->test_client_author_id = $this->factory->user->create(array(
			'role' => 'author',
		) );
		wp_set_current_user($this->test_admin_id);
		//set_admin_role( true );
		//do_action('admin_init');
	}


	public function test_database_setup() {
		if(get_option( 'enforce_post_requirements_version' ) ) {
			$option_exists = true;
		}

		$this->assertTrue( $option_exists );
	}
	/*
	* Creates a test post
	* @parameters $args  Array of boolean arguments to create the test post
	*
	* @return the ID of the created post
	*/

	public function set_up_test_post($args) {
		if (empty($args)) {
			$args = array(
				'client_author' => false,
				'featured_image' => false,
				'category_set' => false,
				'meta_desc' => false,
			);
		}

		$test_post_args = array(
			'post_author' => $this->test_admin_id,
			'post_status' => 'draft',
			'featured_image' => '',
			'category_set' => '',
		);

		if ($args['client_author']) {
			$test_post_args['post_author'] = $this->test_client_author_id;
		}

		$post_id = $this->factory->post->create( $test_post_args );


		if ($args['featured_image']) {
			$attachment_id = $this->factory->attachment->create(array(
				'post_parent' => $post_id,
			));
			update_post_meta($post_id, '_thumbnail_id', ''.$attachment_id .'');
		} else {
			update_post_meta($post_id, '_thumbnail_id', '');
		}
		if ($args['category_set']) {
			$term_id = $this->factory->term->create(array(
				'slug' => 'testing_category',
				'taxonomy' => 'category',
			));
			wp_set_post_categories($post_id, $term_id, false);
		}
		if ($args['meta_desc']) {
			update_post_meta($post_id, '_yoast_wpseo_metadesc', 'meta description here.');
		} else {
			update_post_meta($post_id, '_yoast_wpseo_metadesc', '');
		}

		return $post_id;


	}

	public function test_plugin_post_fail_with_admin_author() {
		$args = array(
			'client_author' => false,
			'featured_image' => true,
			'category_set' => true,
			'meta_desc' => true,
		);

		$post_id = $this->set_up_test_post($args);

		try {
			wp_update_post(array( 'ID' => $post_id, 'post_status' => 'publish' ));
		} catch(WPDieException $exception) {

		}

		$this->assertTrue(is_a($exception, 'WPDieException'));
		$this->assertFalse( get_post_status( $post_id ) === 'publish');
	}
	public function test_no_featured_image() {

		$args = array(
			'client_author' => true,
			'featured_image' => false,
			'category_set' => true,
			'meta_desc' => true,
		);

		$post_id = $this->set_up_test_post($args);


		try {
			wp_update_post(array( 'ID' => $post_id, 'post_status' => 'publish' ));
		} catch(WPDieException $exception) {

		}

		$this->assertTrue(is_a($exception, 'WPDieException'));
		$this->assertFalse( get_post_status( $post_id ) === 'publish');
	}
	public function test_no_meta_description() {

		$args = array(
			'client_author' => true,
			'featured_image' => true,
			'category_set' => true,
			'meta_desc' => false,
		);

		$post_id = $this->set_up_test_post($args);

		$meta_description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );

		$this->assertTrue($meta_description === '');
		//$this->assertFalse( get_post_status( $post_id ) === 'publish');
	}
	public function test_with_no_category_set() {

		$args = array(
			'client_author' => true,
			'featured_image' => true,
			'category_set' => false,
			'meta_desc' => true,
		);

		$post_id = $this->set_up_test_post($args);


		try {
			wp_update_post(array( 'ID' => $post_id, 'post_status' => 'publish' ));
		} catch(WPDieException $exception) {

		}

		$this->assertTrue(is_a($exception, 'WPDieException'));
		$this->assertFalse( get_post_status( $post_id ) === 'publish');
	}
	public function test_successful_post() {

		$args = array(
			'client_author' => true,
			'featured_image' => true,
			'category_set' => true,
			'meta_desc' => true,
		);

		$post_id = $this->set_up_test_post($args);

		try {
			wp_update_post(array( 'ID' => $post_id, 'post_status' => 'publish' ));
		} catch(WPDieException $exception) {

		}

		$this->assertFalse(isset($exception));
		$this->assertTrue( get_post_status( $post_id ) === 'publish');
	}
}
