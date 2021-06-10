<?php
/**
 * Plugin Name:     Enforce Post Requirements
 * Plugin URI:      321webmarketing.com
 * Description:     Adds ability to customize post requirements prior to publishing
 * Author:          321 Web Marketing
 * Author URI:      321webmarketing.com
 * Text Domain:     enforce-post-requirements
 * Domain Path:     /languages
 * Version:         1.7.1
 *
 * @package         Enforce_Post_Requirements
 */
// Your code starts here.
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {

	die;

}

require 'admin-menu.php';
require 'plugin-update-checker-master/plugin-update-checker.php';
require 'tto_custom_scripts.php';
require 'tto-prevent-spam.php';

$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/321webmarketing/enforce-post-requirements/',
	__FILE__,
	'enforce-post-requirements',
	6
);

$myUpdateChecker->setBranch('master');

/**
 * Main class for plugin
 */
class tto_enforce_post_requirements {
    /**
     * @string version version number for the plugin
     */
    const version = '1.7.1';

    /**
     * this allows plugin to call wordpress core function to check for compatibility with other plugins
     */
    static function is_plugin_active( $plugin ) {
        return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
    }
    /**
     * writes default settings to database on initial install
     */
    static function load_default_settings() {
        if( ! get_option( 'enforce_post_requirements_version' ) && ! tto_enforce_post_requirements::is_plugin_active('wordpress-seo/wp-seo.php') ) {
            update_option('enforce_post_requirements_settings', array(
                'enforce_post_requirements_hero_image' => 1,
                'enforce_post_requirements_author' => 1,
                'enforce_post_requirements_category' => 1,
                'enforce_post_requirements_meta_description' => 0,
            ));
        } elseif( ! get_option( 'enforce_post_requirements_version' ) ) {
            update_option('enforce_post_requirements_settings', array(
                'enforce_post_requirements_hero_image' => 1,
                'enforce_post_requirements_author' => 1,
                'enforce_post_requirements_category' => 1,
                'enforce_post_requirements_meta_description' => 1,
            ));
        }
    }

    /**
     * @hook save_post
	 * runs on save_post hook and prevents a post from publishing if requirements are not met
	 * displays error page if publishing prevented with reasons why
     */
    static function prevent_post_publishing($post_id) {
        $post = get_post( $post_id );
        if ($post->post_type == 'post' && ( $post->post_status == 'publish' || $post->post_status == 'future' ) )  {

            $prevent_post_publish = false;
            $error_message = '<h1>Post not published. Please complete the following items:</h1><ul>';

            $meta_description = true;
            if ( is_plugin_active('wordpress-seo/wp-seo.php') ) {
                $meta_description = get_post_meta( $post_id, '_yoast_wpseo_metadesc', true );
                $yoast_seo_score = get_post_meta($post_id, '_yoast_wpseo_linkdex', true);
                $yoast_content_score = get_post_meta($post_id, '_yoast_wpseo_content_score', true);
            }

            $current_user = wp_get_current_user();
            $user_role = $current_user->roles[0];
            $current_user_id = $current_user->ID;
            $post_categories = get_the_category( $post_id );
			$post_author_id = (int)$post->post_author;//post_author is stored as a numeric string
			$post_author = get_userdata( $post_author_id );
			$post_author_roles = $post_author->roles;
			$post_author_name = get_userdata($post_author_id)->user_login;
			if ( in_array( 'administrator', $post_author_roles, true ) ) {
				$post_author_is_admin = true;
			} else if (strpos($post_author_name, 'tto_poster') !== false ) {
				$post_author_is_admin = true;
			} else {
				$post_author_is_admin = false;
			}

            $options = get_option( 'enforce_post_requirements_settings' );

            if( ! has_post_thumbnail($post_id) && $options['enforce_post_requirements_hero_image'] ) {
                $error_message .= '<li>Add featured image <strong>(required)</strong></li>';
                $prevent_post_publish = true;
            }
            if ( tto_enforce_post_requirements::is_plugin_active('wordpress-seo/wp-seo.php') ) {
                if( ! $meta_description && $options['enforce_post_requirements_meta_description'] ) {
                    $error_message .= '<li>Add meta description <strong>(required)</strong></li>';
                    $prevent_post_publish = true;
                }
            }

            if( $post_author_is_admin  && $options['enforce_post_requirements_author'] ) {
                $error_message .= '<li>Change blog author/username to non-admin author/username <strong>(required)</strong></li>';
                $prevent_post_publish = true;
            }
            if( $options['enforce_post_requirements_category'] ) {
                foreach ($post_categories as $category) {
                    if( $category->slug === 'uncategorized' ) {
                        $error_message .= '<li>Add one or more categories <strong>(required)</strong></li>';
                        $prevent_post_publish = true;
                    }
                }
            }

            if($prevent_post_publish) {
                $post->post_status = 'draft';
                wp_update_post($post);
                $error_message .= "</ul><p><a href=" . admin_url("post.php?post=$post_id&action=edit") . ">Go back and edit the post</a></p>";
                wp_die($error_message, 'Post elements missing');
            }
        }
    }
}


add_action('save_post', array('tto_enforce_post_requirements', 'prevent_post_publishing'), -1);

/**
 * @hook wp_loaded
 * initial install function only runs if plugin has not been installed before, or the version
 * number in the database is older than the current version
 */
function tto_prevent_post_publishing_activation() {
    tto_enforce_post_requirements::load_default_settings();
    update_option('enforce_post_requirements_version', array(
        'version_number' => tto_enforce_post_requirements::version,
    ));
}
if ( ! get_option( 'enforce_post_requirements_version' ) || get_option( 'enforce_post_requirements_version' ) < tto_enforce_post_requirements::version ) {
    add_action( 'wp_loaded', 'tto_prevent_post_publishing_activation' );
}


class tto_no_author_publish {
	public static $debug_log = '';

	static function nap_setup_function() {
		$user = wp_get_current_user();
		if ( tto_no_author_publish::is_tto_poster( $user ) ) {
			add_filter( 'user_can_richedit' , '__return_false', 50 );
			add_filter( 'gettext', array('tto_no_author_publish', 'modify_publish_button'), 10, 2 );
			//the save_draft_and_notify function will be called after the publish button is clicked
			add_action( 'save_post', array('tto_no_author_publish', 'save_draft_and_notify'), 2 );
			add_action( 'wp_loaded', function() use ($user) {
				tto_no_author_publish::give_tto_poster_access_to_change_author($user);
			});

			add_action('shutdown', array('tto_no_author_publish', 'export_nap_error_log'));
			// add_action( 'init', function() use ($user) {
			// 	tto_no_author_publish::remove_tto_poster_access_to_change_author($user);
			// });
		}
	}

	static function add_output($string) {
		tto_no_author_publish::$debug_log .= $string . ' ';
	}

	static function is_tto_poster( $user ) {
		$current_role = tto_no_author_publish::nap_get_current_user_roles($user);
		$user_login = $user->user_login;
		if ( ($current_role == 'author') && strpos($user_login, 'tto_poster') !== false ) {
			return true;
		} else {
			return false;
		}
	}

	static function give_tto_poster_access_to_change_author($user) {
		global $pagenow;
		if (in_array( $pagenow, array( 'post.php', 'post-new.php' ) )) {
			if ( ! user_can($user, 'edit_others_posts' )) {
				$user->add_cap('edit_others_posts', true);
				tto_no_author_publish::add_output('capability added');
			} else {
				tto_no_author_publish::add_output('capability not added');
			}

		} else {
			if ( user_can($user, 'edit_others_posts' )) {
				$user->remove_cap('edit_others_posts');
				tto_no_author_publish::add_output('capability removed');
			} else {
				tto_no_author_publish::add_output('capability not removed');
			}
		}
	}

	/**
	 * Get the user's roles
	 * @since 1.0.0
	 */
	static function nap_get_current_user_roles($user) {
		if( is_user_logged_in() ) {

			$roles = ( array ) $user->roles;
			//return $roles; // This returns an array
			// Use this to return a single value
			return $roles[0];
		} else {
			return false;
		}
	}


	static function modify_publish_button( $translation, $text ) {

		if ( 'post' == get_post_type() && ($text == 'Publish' || $text == 'Update' || $text == 'Schedule') ) {

			return 'Submit Draft';

		} else {

			return $translation;
		}

		tto_no_author_publish::add_output('modifying publish button');
	}
	static function save_draft_and_notify( $post_id ) {
		$post = get_post( $post_id );
		$new_status = $post->post_status;
		if ( $new_status === 'publish' || $new_status === 'future' ) {
			$args = array (
				'response' => 300,
				'back_link' => true,
				'exit' => true,
			);
			$post->post_status = 'draft';
			wp_update_post($post);
			wp_die(tto_no_author_publish::nap_draft_submission_notification(), 'Draft Submitted', $args);
		}
	}

	static function nap_draft_submission_notification() {
		return sprintf(
			__( 'Thank you, your draft has been submitted.' )
		);
	}

	static function export_nap_error_log() {
		//error_log(tto_no_author_publish::$debug_log);
	}
}

add_action('init', array('tto_no_author_publish', 'nap_setup_function') );



