<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function fpp_enqueue_custom_shortcodes_css() {
	$relative = '/assets/css/custom-shortcodes.css';
	$path = get_stylesheet_directory() . $relative;
	wp_enqueue_style( 'fpp-custom-shortcodes', get_stylesheet_directory_uri() . $relative, array(), file_exists( $path ) ? (string) filemtime( $path ) : null );
}
add_action( 'wp_enqueue_scripts', 'fpp_enqueue_custom_shortcodes_css', 21 );

function fpp_register_custom_shortcodes_runtime() {
	$items = get_posts( array( 'post_type' => 'fpp_shortcode', 'post_status' => 'publish', 'numberposts' => -1 ) );
	foreach ( $items as $item ) {
		if ( '1' !== get_post_meta( $item->ID, '_fpp_shortcode_enabled', true ) ) { continue; }
		$slug = get_post_meta( $item->ID, '_fpp_shortcode_slug', true );
		if ( ! $slug || shortcode_exists( $slug ) ) { continue; }
		add_shortcode( $slug, function( $atts, $content = null ) use ( $item, $slug ) {
			if ( ! fpp_custom_shortcode_visible( $item->ID ) ) { return ''; }
			$defaults = json_decode( (string) get_post_meta( $item->ID, '_fpp_shortcode_defaults', true ), true );
			$atts = shortcode_atts( is_array( $defaults ) ? $defaults : array(), (array) $atts, $slug );
			$template = (string) get_post_meta( $item->ID, '_fpp_shortcode_template', true );
			$rendered = str_replace(
				array( '{{content}}', '{{title}}', '{{text}}', '{{url}}', '{{label}}', '{{class}}' ),
				array( wp_kses_post( do_shortcode( (string) $content ) ), esc_html( (string) ( $atts['title'] ?? '' ) ), esc_html( (string) ( $atts['text'] ?? '' ) ), esc_url( (string) ( $atts['url'] ?? '#' ) ), esc_html( (string) ( $atts['label'] ?? '' ) ), esc_attr( (string) ( $atts['class'] ?? '' ) ) ),
				$template
			);
			return wp_kses_post( $rendered );
		} );
	}
}
add_action( 'init', 'fpp_register_custom_shortcodes_runtime', 30 );

function fpp_custom_shortcode_visible( $post_id ) {
	$visibility = get_post_meta( $post_id, '_fpp_shortcode_visibility', true );
	if ( 'disabled' === $visibility ) { return false; }
	if ( 'logged_in' === $visibility && ! is_user_logged_in() ) { return false; }
	if ( 'logged_out' === $visibility && is_user_logged_in() ) { return false; }
	return true;
}
