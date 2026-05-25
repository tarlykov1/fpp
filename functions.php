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

/**
 * Remove the default "Built with GeneratePress" footer credit.
 *
 * @param string $copyright Footer copyright HTML.
 * @return string
 */
function generatepress_child_remove_generatepress_credit( $copyright ) {
	return preg_replace(
		'#\s*(?:•|&middot;|&#183;)\s*Built with\s*<a[^>]*generatepress\.com[^>]*>.*?</a>#iu',
		'',
		$copyright
	);
}
add_filter( 'generate_copyright', 'generatepress_child_remove_generatepress_credit' );

/**
 * Hide post author meta in GeneratePress entries.
 */
add_filter( 'generate_post_author', '__return_false' );

/**
 * Hide post comments link in GeneratePress entries.
 */
add_filter( 'generate_post_comment', '__return_false' );

/**
 * Replace excerpt "read more" text with Russian text.
 *
 * @param string $more Excerpt trailing text.
 * @return string
 */
function generatepress_child_excerpt_more( $more ) {
	return ' … Читать далее...';
}
add_filter( 'excerpt_more', 'generatepress_child_excerpt_more' );

/**
 * Localize common frontend strings (pagination and post meta).
 *
 * @param string $translated Translated text.
 * @param string $text       Original text.
 * @param string $domain     Text domain.
 * @return string
 */
function generatepress_child_localize_frontend_strings( $translated, $text, $domain ) {
	if ( 'generatepress' !== $domain && 'default' !== $domain ) {
		return $translated;
	}

	$map = array(
		'Next →'          => 'Следующая →',
		'← Previous'      => '← Предыдущая',
		'Next'            => 'Следующая',
		'Previous'        => 'Предыдущая',
		'Leave a comment' => '',
		'Read more'       => 'Читать далее...',
	);

	if ( isset( $map[ $text ] ) ) {
		return $map[ $text ];
	}

	if ( preg_match( '/^Leave a Comment(?: on .+)?$/i', $text ) ) {
		return '';
	}

	return $translated;
}
add_filter( 'gettext', 'generatepress_child_localize_frontend_strings', 20, 3 );
