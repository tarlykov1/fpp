<?php
/**
 * Custom shortcodes runtime.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function fpp_enqueue_custom_shortcodes_css() {
	$relative = '/assets/css/custom-shortcodes.css';
	$path = get_stylesheet_directory() . $relative;
	wp_enqueue_style( 'fpp-custom-shortcodes', get_stylesheet_directory_uri() . $relative, array(), file_exists( $path ) ? (string) filemtime( $path ) : null );
}
add_action( 'wp_enqueue_scripts', 'fpp_enqueue_custom_shortcodes_css', 21 );

function fpp_register_custom_shortcodes_runtime() {
	$items = get_posts( array( 'post_type' => 'fpp_shortcode', 'post_status' => 'publish', 'numberposts' => -1 ) );
	foreach ( $items as $item ) {
		if ( '1' !== get_post_meta( $item->ID, '_fpp_enable_shortcode', true ) ) { continue; }
		$slug = get_post_meta( $item->ID, '_fpp_slug', true );
		if ( ! $slug || 0 !== strpos( $slug, 'fpp_' ) || shortcode_exists( $slug ) ) { continue; }
		add_shortcode( $slug, function( $atts, $content = null ) use ( $item, $slug ) {
			if ( ! fpp_custom_shortcode_is_visible( $item->ID ) ) {
				return fpp_custom_shortcode_hidden_output( $item->ID, $slug );
			}
			$defaults = json_decode( (string) get_post_meta( $item->ID, '_fpp_default_atts', true ), true );
			if ( ! is_array( $defaults ) ) { $defaults = array(); }
			$defaults['class'] = trim( (string) ( $defaults['class'] ?? '' ) . ' ' . (string) get_post_meta( $item->ID, '_fpp_default_css_class', true ) );
			$atts = shortcode_atts( $defaults, (array) $atts, $slug );
			$render = fpp_custom_shortcode_render_template( (string) get_post_meta( $item->ID, '_fpp_template', true ), $atts, $content );
			return wp_kses_post( $render );
		} );
	}
}
add_action( 'init', 'fpp_register_custom_shortcodes_runtime', 30 );

function fpp_custom_shortcode_render_template( $template, $atts, $content ) {
	$replacements = array(
		'{{content}}' => wp_kses_post( do_shortcode( (string) $content ) ),
		'{{title}}'   => esc_html( (string) ( $atts['title'] ?? '' ) ),
		'{{text}}'    => esc_html( (string) ( $atts['text'] ?? '' ) ),
		'{{url}}'     => esc_url( (string) ( $atts['url'] ?? '#' ) ),
		'{{label}}'   => esc_html( (string) ( $atts['label'] ?? '' ) ),
		'{{class}}'   => esc_attr( (string) ( $atts['class'] ?? '' ) ),
		'{{image}}'   => esc_url( (string) ( $atts['image'] ?? '' ) ),
		'{{date}}'    => esc_html( (string) ( $atts['date'] ?? '' ) ),
	);
	$template = str_replace( array_keys( $replacements ), array_values( $replacements ), (string) $template );
	return $template;
}

function fpp_custom_shortcode_is_visible( $post_id ) {
	$visibility = get_post_meta( $post_id, '_fpp_visibility', true );
	if ( 'disabled' === $visibility ) { return false; }
	if ( 'logged_in' === $visibility && ! is_user_logged_in() ) { return false; }
	if ( 'logged_out' === $visibility && is_user_logged_in() ) { return false; }
	if ( 'roles' === $visibility && is_user_logged_in() ) {
		$user = wp_get_current_user();
		$roles = (array) get_post_meta( $post_id, '_fpp_roles', true );
		if ( empty( array_intersect( $roles, (array) $user->roles ) ) ) { return false; }
	}
	if ( 'pages' === $visibility && is_page() ) {
		$ids = array_map( 'absint', array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $post_id, '_fpp_pages', true ) ) ) ) );
		if ( $ids && ! in_array( get_queried_object_id(), $ids, true ) ) { return false; }
	}
	if ( 'posts' === $visibility && is_single() ) {
		$ids = array_map( 'absint', array_filter( array_map( 'trim', explode( ',', (string) get_post_meta( $post_id, '_fpp_posts', true ) ) ) ) );
		if ( $ids && ! in_array( get_queried_object_id(), $ids, true ) ) { return false; }
	}
	if ( 'date_range' === $visibility ) {
		$now = current_time( 'Y-m-d' );
		$start = get_post_meta( $post_id, '_fpp_date_start', true );
		$end = get_post_meta( $post_id, '_fpp_date_end', true );
		if ( $start && $now < $start ) { return false; }
		if ( $end && $now > $end ) { return false; }
	}
	return true;
}

function fpp_custom_shortcode_hidden_output( $post_id, $slug ) {
	$mode = get_post_meta( $post_id, '_fpp_hidden_behavior', true );
	if ( 'comment' === $mode || ( 'placeholder' === $mode && current_user_can( 'manage_options' ) ) ) {
		return '<!-- FPP shortcode ' . esc_html( $slug ) . ' hidden by visibility rules -->';
	}
	return '';
}
