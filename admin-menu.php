<?php
add_action( 'admin_menu', 'enforce_post_requirements_add_admin_menu' );
add_action( 'admin_init', 'enforce_post_requirements_settings_init' );


function enforce_post_requirements_add_admin_menu(  ) { 

	add_options_page( 'Enforce Post Requirements', ' Post Requirements', 'manage_options', 'enforce_post_requirements', 'enforce_post_requirements_options_page' );

}


function enforce_post_requirements_settings_init(  ) { 

	register_setting( 'pluginPage', 'enforce_post_requirements_settings', array('sanitize_callback' => 'enforce_post_requirements_error') );

	add_settings_section(
		'enforce_post_requirements_pluginPage_section', 
		__( 'Blog Requirements:', 'enforce-post-requirements' ), 
		'enforce_post_requirements_settings_section_callback', 
		'pluginPage'
	);


	add_settings_field( 
		'enforce_post_requirements_hero_image', 
		__( 'Featured image', 'enforce-post-requirements' ), 
		'enforce_post_requirements_hero_image_render', 
		'pluginPage', 
		'enforce_post_requirements_pluginPage_section' 
	);

	add_settings_field(
		'enforce_post_requirements_author', 
		__( 'Non-admin author', 'enforce-post-requirements' ), 
		'enforce_post_requirements_author_render', 
		'pluginPage', 
		'enforce_post_requirements_pluginPage_section' 
	);

	add_settings_field( 
		'enforce_post_requirements_category', 
		__( 'Category (one or more)', 'enforce-post-requirements' ), 
		'enforce_post_requirements_category_render', 
		'pluginPage', 
		'enforce_post_requirements_pluginPage_section' 
	);

	add_settings_field( 
		'enforce_post_requirements_meta_description', 
		__( 'Metadescription (Yoast)', 'enforce-post-requirements' ), 
		'enforce_post_requirements_meta_description_render', 
		'pluginPage', 
		'enforce_post_requirements_pluginPage_section' 
	);

}


function enforce_post_requirements_hero_image_render(  ) { 

	$options = get_option( 'enforce_post_requirements_settings' );
	?>
	<input type='checkbox' name='enforce_post_requirements_settings[enforce_post_requirements_hero_image]' <?php checked( $options['enforce_post_requirements_hero_image'], 1 ); ?> value='1'>
	<?php

}

function enforce_post_requirements_author_render(  ) { 

	$options = get_option( 'enforce_post_requirements_settings' );
	?>
	<input type='checkbox' name='enforce_post_requirements_settings[enforce_post_requirements_author]' <?php checked( $options['enforce_post_requirements_author'], 1 ); ?> value='1'>
	<?php

}

function enforce_post_requirements_category_render(  ) {

	$options = get_option( 'enforce_post_requirements_settings' );
	?>
	<input type='checkbox' name='enforce_post_requirements_settings[enforce_post_requirements_category]' <?php checked( $options['enforce_post_requirements_category'], 1 ); ?> value='1'>
	<?php

}

function enforce_post_requirements_meta_description_render(  ) { 

	$options = get_option( 'enforce_post_requirements_settings' );
	?>
	<input type='checkbox' name='enforce_post_requirements_settings[enforce_post_requirements_meta_description]' <?php checked( $options['enforce_post_requirements_meta_description'], 1 ); ?> value='1'>
	<?php

}

function enforce_post_requirements_error($input) {
	if ( ! is_plugin_active('wordpress-seo/wp-seo.php') && $input['enforce_post_requirements_meta_description'] == 1 ) {
		$input['enforce_post_requirements_meta_description'] = 0;
		add_settings_error(
			'enforce_post_requirements_meta_description',
			'yoast_not_active',
			'Yoast SEO is required to check for metadescriptions',
			'error');
	}
	return $input;
}

function enforce_post_requirements_settings_section_callback(  ) { 

	echo __( '', 'enforce-post-requirements' );

}

function enforce_post_requirements_options_page(  ) { 

		?>
		<h1>Enforce Post Requirements</h1>
		<hr />
		<form action='options.php' method='post'>

			<?php
			settings_fields( 'pluginPage' );
			do_settings_sections( 'pluginPage' );
			submit_button();
			?>

		</form>
		<?php

}
