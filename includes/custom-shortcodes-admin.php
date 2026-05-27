<?php
/**
 * Custom shortcodes admin UI + CPT.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function fpp_custom_shortcode_output_types() {
	return array(
		'static_html' => 'Статический HTML',
		'button'      => 'Кнопка',
		'notice'      => 'Уведомление',
		'card'        => 'Карточка',
		'container'   => 'Контейнер',
		'links_list'  => 'Список ссылок',
	);
}

function fpp_custom_shortcode_visibility_types() {
	return array(
		'always'     => 'Всегда',
		'logged_in'  => 'Только авторизованным',
		'logged_out' => 'Только гостям',
		'disabled'   => 'Не показывать',
	);
}

function fpp_register_shortcodes_cpt() {
	register_post_type(
		'fpp_shortcode',
		array(
			'labels'       => array(
				'name'          => 'Шорткоды FPP',
				'singular_name' => 'Шорткод FPP',
				'add_new_item'  => 'Создать шорткод',
				'edit_item'     => 'Редактировать шорткод',
			),
			'public'       => false,
			'show_ui'      => true,
			'show_in_menu' => false,
			'supports'     => array( 'title' ),
			'capabilities' => array_fill_keys(
				array(
					'edit_post','read_post','delete_post','edit_posts','edit_others_posts','publish_posts','read_private_posts','delete_posts',
					'delete_private_posts','delete_published_posts','delete_others_posts','edit_private_posts','edit_published_posts','create_posts',
				),
				'manage_options'
			),
			'map_meta_cap' => false,
		)
	);
}
add_action( 'init', 'fpp_register_shortcodes_cpt' );

function fpp_sanitize_shortcode_slug( $slug ) {
	$slug = strtolower( (string) $slug );
	$slug = preg_replace( '/[^a-z0-9_]/', '', $slug );
	if ( 0 === strpos( $slug, 'fruitful_' ) ) {
		$slug = substr( $slug, 9 );
	}
	if ( 0 !== strpos( $slug, 'fpp_' ) ) {
		$slug = 'fpp_' . ltrim( $slug, '_' );
	}
	return $slug;
}

function fpp_shortcode_slug_exists( $slug, $exclude_post_id = 0 ) {
	if ( shortcode_exists( $slug ) ) {
		return true;
	}
	$existing = get_posts(
		array(
			'post_type'   => 'fpp_shortcode',
			'post_status' => array( 'publish', 'draft', 'pending', 'private' ),
			'meta_key'    => '_fpp_shortcode_slug',
			'meta_value'  => $slug,
			'numberposts' => 1,
			'exclude'     => $exclude_post_id ? array( $exclude_post_id ) : array(),
		)
	);
	return ! empty( $existing );
}

function fpp_add_custom_shortcode_metaboxes() {
	add_meta_box( 'fpp_custom_shortcode_meta', 'Параметры шорткода', 'fpp_render_custom_shortcode_metabox', 'fpp_shortcode', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'fpp_add_custom_shortcode_metaboxes' );

function fpp_render_custom_shortcode_metabox( $post ) {
	wp_nonce_field( 'fpp_save_custom_shortcode', 'fpp_custom_shortcode_nonce' );
	$defaults_json = "{\n  \"title\": \"\",\n  \"url\": \"#\",\n  \"label\": \"Подробнее\",\n  \"class\": \"\"\n}";
	$meta          = array(
		'slug'             => get_post_meta( $post->ID, '_fpp_shortcode_slug', true ),
		'enabled'          => get_post_meta( $post->ID, '_fpp_shortcode_enabled', true ),
		'type'             => get_post_meta( $post->ID, '_fpp_shortcode_type', true ),
		'description'      => get_post_meta( $post->ID, '_fpp_shortcode_description', true ),
		'template'         => get_post_meta( $post->ID, '_fpp_shortcode_template', true ),
		'defaults'         => get_post_meta( $post->ID, '_fpp_shortcode_defaults', true ),
		'supports_content' => get_post_meta( $post->ID, '_fpp_shortcode_supports_content', true ),
		'visibility'       => get_post_meta( $post->ID, '_fpp_shortcode_visibility', true ),
	);
	if ( '' === $meta['enabled'] ) { $meta['enabled'] = '1'; }
	if ( '' === $meta['type'] ) { $meta['type'] = 'static_html'; }
	if ( '' === $meta['visibility'] ) { $meta['visibility'] = 'always'; }
	if ( '' === trim( (string) $meta['defaults'] ) ) { $meta['defaults'] = $defaults_json; }
	echo '<p><label>Slug шорткода<br><input type="text" name="fpp_shortcode_slug" class="regular-text" value="' . esc_attr( $meta['slug'] ) . '" placeholder="fpp_button"></label></p>';
	echo '<p><label><input type="checkbox" name="fpp_shortcode_enabled" value="1" ' . checked( '1', $meta['enabled'], false ) . '> Включён</label></p>';
	echo '<p><label>Тип вывода<br><select name="fpp_shortcode_type">';
	foreach ( fpp_custom_shortcode_output_types() as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $meta['type'], $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><label>Описание<br><textarea name="fpp_shortcode_description" rows="3" class="large-text">' . esc_textarea( $meta['description'] ) . '</textarea></label></p>';
	echo '<p><label>HTML-шаблон<br><textarea name="fpp_shortcode_template" rows="8" class="large-text code">' . esc_textarea( $meta['template'] ) . '</textarea></label></p>';
	echo '<p class="description">Плейсхолдеры: {{content}}, {{title}}, {{text}}, {{url}}, {{label}}, {{class}}</p>';
	echo '<p><label>Атрибуты по умолчанию (JSON)<br><textarea name="fpp_shortcode_defaults" rows="8" class="large-text code">' . esc_textarea( $meta['defaults'] ) . '</textarea></label></p>';
	echo '<p><label><input type="checkbox" name="fpp_shortcode_supports_content" value="1" ' . checked( '1', $meta['supports_content'], false ) . '> Поддерживает вложенный контент</label></p>';
	echo '<p><label>Видимость<br><select name="fpp_shortcode_visibility">';
	foreach ( fpp_custom_shortcode_visibility_types() as $key => $label ) {
		echo '<option value="' . esc_attr( $key ) . '" ' . selected( $meta['visibility'], $key, false ) . '>' . esc_html( $label ) . '</option>';
	}
	echo '</select></label></p>';
}

function fpp_save_custom_shortcode_meta( $post_id ) {
	if ( ! isset( $_POST['fpp_custom_shortcode_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fpp_custom_shortcode_nonce'] ) ), 'fpp_save_custom_shortcode' ) ) { return; }
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }

	$slug = isset( $_POST['fpp_shortcode_slug'] ) ? fpp_sanitize_shortcode_slug( wp_unslash( $_POST['fpp_shortcode_slug'] ) ) : '';
	if ( empty( $slug ) || 0 === strpos( $slug, 'fruitful_' ) || fpp_shortcode_slug_exists( $slug, $post_id ) ) {
		add_filter( 'redirect_post_location', static function( $location ) {
			return add_query_arg( 'fpp_slug_error', 1, $location );
		} );
		return;
	}

	$type       = sanitize_key( wp_unslash( $_POST['fpp_shortcode_type'] ?? 'static_html' ) );
	$visibility = sanitize_key( wp_unslash( $_POST['fpp_shortcode_visibility'] ?? 'always' ) );
	$defaults   = wp_unslash( $_POST['fpp_shortcode_defaults'] ?? '' );
	$decoded    = json_decode( (string) $defaults, true );
	if ( ! is_array( $decoded ) ) {
		$defaults = '{"title":"","url":"#","label":"Подробнее","class":""}';
	}

	update_post_meta( $post_id, '_fpp_shortcode_slug', $slug );
	update_post_meta( $post_id, '_fpp_shortcode_enabled', isset( $_POST['fpp_shortcode_enabled'] ) ? '1' : '0' );
	update_post_meta( $post_id, '_fpp_shortcode_type', $type );
	update_post_meta( $post_id, '_fpp_shortcode_description', sanitize_textarea_field( wp_unslash( $_POST['fpp_shortcode_description'] ?? '' ) ) );
	update_post_meta( $post_id, '_fpp_shortcode_template', wp_kses_post( wp_unslash( $_POST['fpp_shortcode_template'] ?? '' ) ) );
	update_post_meta( $post_id, '_fpp_shortcode_defaults', wp_kses_post( (string) $defaults ) );
	update_post_meta( $post_id, '_fpp_shortcode_supports_content', isset( $_POST['fpp_shortcode_supports_content'] ) ? '1' : '0' );
	update_post_meta( $post_id, '_fpp_shortcode_visibility', in_array( $visibility, array_keys( fpp_custom_shortcode_visibility_types() ), true ) ? $visibility : 'always' );
}
add_action( 'save_post_fpp_shortcode', 'fpp_save_custom_shortcode_meta' );
