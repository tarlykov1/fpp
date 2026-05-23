<?php
/**
 * Enigma Child theme functions.
 */

add_action( 'wp_enqueue_scripts', 'enigma_child_enqueue_styles' );

function enigma_child_enqueue_styles() {
	wp_enqueue_style(
		'enigma-parent-style',
		get_template_directory_uri() . '/style.css',
		array(),
		wp_get_theme( 'enigma' )->get( 'Version' )
	);

	wp_enqueue_style(
		'enigma-child-style',
		get_stylesheet_uri(),
		array( 'enigma-parent-style' ),
		wp_get_theme()->get( 'Version' )
	);
}
