<?php
/**
 * GeneratePress Child functions.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/includes/fruitful-shortcodes-compat.php';

/**
 * Marker to check that child theme functions.php is loaded.
 */
add_action( 'wp_footer', function () {
	echo "\n<!-- FPP_CHILD_THEME_FUNCTIONS_LOADED -->\n";
}, 9999 );

/**
 * Replace GeneratePress footer copyright completely.
 * This keeps the site name and removes "Built with GeneratePress".
 */
add_filter( 'generate_copyright', function () {
	return sprintf(
		'&copy; %1$s %2$s',
		esc_html( date_i18n( 'Y' ) ),
		esc_html( get_bloginfo( 'name' ) )
	);
}, 999 );

/**
 * Remove author and comments link from GeneratePress entry meta.
 */
add_filter( 'generate_header_entry_meta_items', function ( $items ) {
	$items = array_diff( $items, array( 'author', 'comments-link' ) );
	return array_values( $items );
}, 999 );

add_filter( 'generate_footer_entry_meta_items', function ( $items ) {
	$items = array_diff( $items, array( 'author', 'comments-link' ) );
	return array_values( $items );
}, 999 );

/**
 * Replace GeneratePress excerpt "Read more" link.
 */
add_filter( 'generate_excerpt_more_output', function () {
	return sprintf(
		' ... <a title="%1$s" class="read-more" href="%2$s" aria-label="%3$s">%4$s</a>',
		esc_attr( the_title_attribute( array( 'echo' => false ) ) ),
		esc_url( get_permalink( get_the_ID() ) ),
		esc_attr( sprintf( 'Читать далее: %s', get_the_title( get_the_ID() ) ) ),
		esc_html( 'Читать далее...' )
	);
}, 999 );

/**
 * Fallback for default WordPress excerpt ending.
 */
add_filter( 'excerpt_more', function () {
	return ' ...';
}, 999 );

/**
 * Translate GeneratePress archive pagination.
 */
add_filter( 'generate_previous_link_text', function () {
	return '← Назад';
}, 999 );

add_filter( 'generate_next_link_text', function () {
	return 'Далее →';
}, 999 );

/**
 * Extra fallback for translated strings if theme outputs them directly.
 */
add_filter( 'gettext', function ( $translated, $text, $domain ) {
	$replacements = array(
		'Next'            => 'Далее',
		'Next →'          => 'Далее →',
		'Next &rarr;'     => 'Далее →',
		'Previous'        => 'Назад',
		'← Previous'      => '← Назад',
		'&larr; Previous' => '← Назад',
		'Read more'       => 'Читать далее...',
		'Leave a comment' => '',
		'Leave a Comment' => '',
	);

	if ( isset( $replacements[ $text ] ) ) {
		return $replacements[ $text ];
	}

	return $translated;
}, 999, 3 );
