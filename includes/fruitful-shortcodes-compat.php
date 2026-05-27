<?php
/**
 * Fruitful Shortcodes compatibility layer.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function fpp_fruitful_enqueue_assets() {
	if ( is_admin() ) {
		return;
	}

	$relative = '/assets/css/fruitful-shortcodes-compat.css';
	$path     = get_stylesheet_directory() . $relative;
	$version  = file_exists( $path ) ? (string) filemtime( $path ) : null;

	wp_enqueue_style(
		'fpp-fruitful-shortcodes-compat',
		get_stylesheet_directory_uri() . $relative,
		array(),
		$version
	);
}
add_action( 'wp_enqueue_scripts', 'fpp_fruitful_enqueue_assets', 20 );

function fpp_fruitful_footer_marker() {
	echo "\n<!-- FPP_FRUITFUL_SHORTCODES_COMPAT_LOADED -->\n";
}
add_action( 'wp_footer', 'fpp_fruitful_footer_marker', 9999 );

function fpp_fruitful_parse_positive_int( $value, $default = 0 ) {
	$int = absint( $value );
	return $int > 0 ? $int : absint( $default );
}

function fpp_shortcode_fruitful_dbox( $atts, $content = null ) {
	$inner = do_shortcode( (string) $content );
	return '<div class="fpp-fruitful-dbox">' . wp_kses_post( $inner ) . '</div>';
}

function fpp_shortcode_fruitful_sep( $atts ) {
	$atts = shortcode_atts(
		array(
			'id'     => '',
			'height' => '',
			'style'  => '',
		),
		$atts,
		'fruitful_sep'
	);

	$id_attr  = '';
	$height   = fpp_fruitful_parse_positive_int( $atts['height'] );
	$style    = $height ? ' style="height:' . esc_attr( $height ) . 'px"' : '';

	if ( '' !== trim( (string) $atts['id'] ) ) {
		$id_attr = ' id="' . esc_attr( sanitize_html_class( (string) $atts['id'] ) ) . '"';
	}

	return '<div class="fpp-fruitful-sep"' . $id_attr . $style . ' aria-hidden="true"></div>';
}

function fpp_shortcode_fruitful_alert( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'type' => 'alert-info',
		),
		$atts,
		'fruitful_alert'
	);

	$type = strtolower( sanitize_html_class( (string) $atts['type'] ) );
	$type = preg_replace( '/^alert-/', '', $type );
	if ( ! in_array( $type, array( 'success', 'info', 'warning', 'danger' ), true ) ) {
		$type = 'info';
	}

	$inner = do_shortcode( (string) $content );
	return '<div class="fpp-fruitful-alert fpp-fruitful-alert-' . esc_attr( $type ) . '">' . wp_kses_post( $inner ) . '</div>';
}

function fpp_shortcode_fruitful_btn( $atts, $content = null ) {
	$atts = shortcode_atts(
		array(
			'link'          => '#',
			'target'        => '',
			'color'         => 'default',
			'size'          => 'md',
			'icon'          => '',
			'icon_position' => 'left',
		),
		$atts,
		'fruitful_btn'
	);

	$link          = '' !== trim( (string) $atts['link'] ) ? esc_url( $atts['link'] ) : '#';
	$target        = '_blank' === $atts['target'] ? '_blank' : '';
	$rel           = '_blank' === $target ? ' rel="noopener noreferrer"' : '';
	$target_attr   = $target ? ' target="_blank"' : '';
	$color         = sanitize_html_class( (string) $atts['color'] );
	$size          = sanitize_html_class( (string) $atts['size'] );
	$icon          = sanitize_html_class( (string) $atts['icon'] );
	$icon_position = 'right' === strtolower( (string) $atts['icon_position'] ) ? 'right' : 'left';
	$inner         = wp_kses_post( do_shortcode( (string) $content ) );

	$icon_html = '';
	if ( '' !== $icon ) {
		$icon_html = '<span class="fpp-fruitful-btn-icon fpp-fruitful-btn-icon-' . esc_attr( $icon ) . '" aria-hidden="true"></span>';
	}

	if ( $icon_html && 'left' === $icon_position ) {
		$inner = $icon_html . '<span class="fpp-fruitful-btn-label">' . $inner . '</span>';
	} elseif ( $icon_html ) {
		$inner = '<span class="fpp-fruitful-btn-label">' . $inner . '</span>' . $icon_html;
	}

	return sprintf(
		'<a class="fpp-fruitful-btn fpp-fruitful-btn-color-%1$s fpp-fruitful-btn-size-%2$s" href="%3$s"%4$s%5$s>%6$s</a>',
		esc_attr( $color ),
		esc_attr( $size ),
		esc_url( $link ),
		$target_attr,
		$rel,
		$inner
	);
}

function fpp_shortcode_fruitful_pbar( $atts, $content = null ) {
	$atts = shortcode_atts( array( 'title' => '' ), $atts, 'fruitful_pbar' );
	$title = '' !== trim( (string) $atts['title'] ) ? '<div class="fpp-fruitful-pbar-title">' . esc_html( $atts['title'] ) . '</div>' : '';
	$inner = do_shortcode( (string) $content );
	return '<div class="fpp-fruitful-pbar">' . $title . '<div class="fpp-fruitful-pbar-bars">' . wp_kses_post( $inner ) . '</div></div>';
}

function fpp_shortcode_fruitful_bar( $atts ) {
	$atts = shortcode_atts(
		array(
			'title'   => '',
			'percent' => '0',
			'color'   => '',
		),
		$atts,
		'fruitful_bar'
	);
	$percent = min( 100, fpp_fruitful_parse_positive_int( $atts['percent'] ) );
	$title   = '' !== trim( (string) $atts['title'] ) ? '<span class="fpp-fruitful-bar-title">' . esc_html( $atts['title'] ) . '</span>' : '';
	$color   = '' !== trim( (string) $atts['color'] ) ? ' fpp-fruitful-bar-color-' . sanitize_html_class( (string) $atts['color'] ) : '';

	return '<div class="fpp-fruitful-bar' . esc_attr( $color ) . '">' . $title . '<div class="fpp-fruitful-bar-track"><span class="fpp-fruitful-bar-fill" style="width:' . esc_attr( (string) $percent ) . '%"></span></div><span class="fpp-fruitful-bar-percent">' . esc_html( (string) $percent ) . '%</span></div>';
}

function fpp_shortcode_fruitful_tabs( $atts, $content = null ) {
	$inner = do_shortcode( (string) $content );
	return '<div class="fpp-fruitful-tabs">' . wp_kses_post( $inner ) . '</div>';
}

function fpp_shortcode_fruitful_tab( $atts, $content = null ) {
	$atts  = shortcode_atts( array( 'title' => '' ), $atts, 'fruitful_tab' );
	$title = '' !== trim( (string) $atts['title'] ) ? $atts['title'] : __( 'Tab', 'generatepress-child' );
	$inner = do_shortcode( (string) $content );
	return '<section class="fpp-fruitful-tab"><h3 class="fpp-fruitful-tab-title">' . esc_html( $title ) . '</h3><div class="fpp-fruitful-tab-content">' . wp_kses_post( $inner ) . '</div></section>';
}

function fpp_shortcode_fruitful_tab_link( $atts, $content = null ) {
	$inner = do_shortcode( (string) $content );
	return '<div class="fpp-fruitful-tab-link">' . wp_kses_post( $inner ) . '</div>';
}

function fpp_shortcode_fruitful_ibox_row( $atts, $content = null ) {
	$inner = do_shortcode( (string) $content );
	return '<div class="fpp-fruitful-ibox-row">' . wp_kses_post( $inner ) . '</div>';
}

function fpp_shortcode_fruitful_ibox( $atts, $content = null ) {
	$atts = shortcode_atts( array( 'title' => '' ), $atts, 'fruitful_ibox' );
	$title = '' !== trim( (string) $atts['title'] ) ? '<h4 class="fpp-fruitful-ibox-title">' . esc_html( $atts['title'] ) . '</h4>' : '';
	$inner = do_shortcode( (string) $content );
	return '<article class="fpp-fruitful-ibox">' . $title . '<div class="fpp-fruitful-ibox-content">' . wp_kses_post( $inner ) . '</div></article>';
}

function fpp_fruitful_render_recent_posts( $atts, $tag ) {
	$atts = shortcode_atts(
		array(
			'posts' => 4,
			'cat'   => '',
		),
		$atts,
		$tag
	);

	$query_args = array(
		'post_type'      => 'post',
		'post_status'    => 'publish',
		'posts_per_page' => fpp_fruitful_parse_positive_int( $atts['posts'], 4 ),
	);

	$cat = sanitize_title( (string) $atts['cat'] );
	if ( '' !== $cat ) {
		$query_args['category_name'] = $cat;
	}

	$query = new WP_Query( $query_args );
	$cards = '';

	if ( $query->have_posts() ) {
		while ( $query->have_posts() ) {
			$query->the_post();
			$thumb = has_post_thumbnail() ? get_the_post_thumbnail( get_the_ID(), 'medium', array( 'class' => 'fpp-fruitful-post-thumb' ) ) : '';
			$cards .= '<article class="fpp-fruitful-post-card">';
			$cards .= '<a class="fpp-fruitful-post-link" href="' . esc_url( get_permalink() ) . '">' . wp_kses_post( $thumb ) . '<h3 class="fpp-fruitful-post-title">' . esc_html( get_the_title() ) . '</h3></a>';
			$cards .= '<time class="fpp-fruitful-post-date" datetime="' . esc_attr( get_the_date( DATE_W3C ) ) . '">' . esc_html( get_the_date() ) . '</time>';
			$cards .= '<div class="fpp-fruitful-post-excerpt">' . esc_html( get_the_excerpt() ) . '</div>';
			$cards .= '<a class="fpp-fruitful-post-readmore" href="' . esc_url( get_permalink() ) . '">' . esc_html__( 'Читать далее', 'generatepress-child' ) . '</a>';
			$cards .= '</article>';
		}
	} else {
		$cards = '<p class="fpp-fruitful-empty">' . esc_html__( 'Записей не найдено.', 'generatepress-child' ) . '</p>';
	}

	wp_reset_postdata();

	$wrapper_class = 'fruitful_recent_posts_slider' === $tag ? 'fpp-fruitful-posts fpp-fruitful-posts-slider-fallback' : 'fpp-fruitful-posts';
	return '<div class="' . esc_attr( $wrapper_class ) . '">' . $cards . '</div>';
}

function fpp_register_fruitful_shortcodes() {
	$shortcodes = array(
		'fruitful_dbox'               => 'fpp_shortcode_fruitful_dbox',
		'fruitful_sep'                => 'fpp_shortcode_fruitful_sep',
		'fruitful_alert'              => 'fpp_shortcode_fruitful_alert',
		'fruitful_btn'                => 'fpp_shortcode_fruitful_btn',
		'fruitful_pbar'               => 'fpp_shortcode_fruitful_pbar',
		'fruitful_bar'                => 'fpp_shortcode_fruitful_bar',
		'fruitful_tabs'               => 'fpp_shortcode_fruitful_tabs',
		'fruitful_tab'                => 'fpp_shortcode_fruitful_tab',
		'fruitful_tab_link'           => 'fpp_shortcode_fruitful_tab_link',
		'fruitful_ibox_row'           => 'fpp_shortcode_fruitful_ibox_row',
		'fruitful_ibox'               => 'fpp_shortcode_fruitful_ibox',
		'fruitful_recent_posts'       => 'fpp_fruitful_render_recent_posts',
		'fruitful_recent_posts_slider'=> 'fpp_fruitful_render_recent_posts',
	);

	foreach ( $shortcodes as $tag => $callback ) {
		add_shortcode( $tag, $callback );
	}
}
add_action( 'init', 'fpp_register_fruitful_shortcodes', 20 );
