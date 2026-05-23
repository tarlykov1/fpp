<?php
/**
 * Enqueue parent and child theme styles.
 *
 * @package GeneratePress_Child
 */

add_action( 'wp_enqueue_scripts', 'generatepress_child_enqueue_styles' );

/**
 * Enqueue stylesheets for the child theme.
 */
function generatepress_child_enqueue_styles() {
	wp_enqueue_style(
		'generatepress-parent',
		get_template_directory_uri() . '/style.css'
	);

	wp_enqueue_style(
		'generatepress-child',
		get_stylesheet_directory_uri() . '/style.css',
		array( 'generatepress-parent' ),
		wp_get_theme()->get( 'Version' )
	);
}
