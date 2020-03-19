<?php
/**
 * Plugin Name:     Enforce Post Requirements
 * Plugin URI:      321webmarketing.com
 * Description:     Adds ability to customize post requirements prior to publishing
 * Author:          321 Web Marketing
 * Author URI:      321webmarketing.com
 * Text Domain:     enforce-post-requirements
 * Domain Path:     /languages
 * Version:         1.3.0
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

$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(
	'https://github.com/321webmarketing/enforce-post-requirements/',
	__FILE__,
	'enforce-post-requirements',
	6
);



/**
 * Main class for plugin
 */
class tto_enforce_post_requirements {
    /**
     * @string version version number for the plugin
     */
    const version = '1.3s.0';

    /**
     * allows plugin to call wordpress core function to check for compatibility with other plugins
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
        if ($post->post_type == 'post' && $post->post_status == 'publish') {

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
			if ( in_array( 'administrator', $post_author_roles, true ) ) {
				$post_author_is_admin = true;
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
