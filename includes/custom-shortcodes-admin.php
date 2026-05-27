<?php
/**
 * Custom shortcodes admin UI + CPT.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function fpp_custom_shortcode_output_types() {
	return array(
		'static_html'   => 'Статический HTML-блок',
		'text_block'    => 'Текстовый блок',
		'button'        => 'Кнопка',
		'card'          => 'Карточка',
		'notice'        => 'Уведомление',
		'container'     => 'Контейнер',
		'links_list'    => 'Список ссылок',
		'raw_safe_html' => 'Безопасный HTML',
	);
}

function fpp_custom_shortcode_visibility_types() {
	return array(
		'always'     => 'Всегда',
		'logged_in'  => 'Только авторизованным',
		'logged_out' => 'Только гостям',
		'roles'      => 'Только выбранным ролям',
		'pages'      => 'Только на выбранных страницах',
		'posts'      => 'Только в выбранных записях',
		'date_range' => 'В заданный период',
		'disabled'   => 'Скрыт полностью',
	);
}

function fpp_register_shortcodes_cpt() {
	register_post_type(
		'fpp_shortcode',
		array(
			'labels'          => array(
				'name'          => 'Шорткоды',
				'singular_name' => 'Шорткод',
				'add_new_item'  => 'Добавить шорткод',
				'new_item'      => 'Новый шорткод',
				'edit_item'     => 'Редактировать шорткод',
			),
			'public'          => false,
			'show_ui'         => true,
			'show_in_menu'    => false,
			'supports'        => array( 'title' ),
			'capability_type' => 'post',
			'map_meta_cap'    => true,
			'capabilities'    => array(
				'create_posts' => 'manage_options',
				'edit_post'    => 'manage_options',
				'read_post'    => 'manage_options',
				'delete_post'  => 'manage_options',
				'edit_posts'   => 'manage_options',
				'edit_others_posts' => 'manage_options',
				'publish_posts' => 'manage_options',
				'read_private_posts' => 'manage_options',
			),
		)
	);
}
add_action( 'init', 'fpp_register_shortcodes_cpt' );

function fpp_custom_shortcodes_reserved_slugs() {
	global $shortcode_tags;
	$built_in = is_array( $shortcode_tags ) ? array_keys( $shortcode_tags ) : array();
	$compat   = array( 'fruitful_dbox', 'fruitful_btn', 'fruitful_alert', 'fruitful_tabs', 'fruitful_tab' );
	return array_unique( array_merge( $built_in, $compat ) );
}

function fpp_sanitize_shortcode_slug( $slug ) {
	$slug = strtolower( (string) $slug );
	$slug = preg_replace( '/[^a-z0-9_]/', '', $slug );
	if ( 0 !== strpos( $slug, 'fpp_' ) ) {
		$slug = 'fpp_' . ltrim( $slug, '_' );
	}
	return $slug;
}

function fpp_add_custom_shortcode_metaboxes() {
	add_meta_box( 'fpp_custom_shortcode_meta', 'Настройки шорткода', 'fpp_render_custom_shortcode_metabox', 'fpp_shortcode', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'fpp_add_custom_shortcode_metaboxes' );

function fpp_render_custom_shortcode_metabox( $post ) {
	wp_nonce_field( 'fpp_save_custom_shortcode', 'fpp_custom_shortcode_nonce' );
	$meta = array(
		'slug' => get_post_meta( $post->ID, '_fpp_slug', true ),
		'enable_shortcode' => get_post_meta( $post->ID, '_fpp_enable_shortcode', true ),
		'output_type' => get_post_meta( $post->ID, '_fpp_output_type', true ),
		'description' => get_post_meta( $post->ID, '_fpp_description', true ),
		'template' => get_post_meta( $post->ID, '_fpp_template', true ),
		'default_atts' => get_post_meta( $post->ID, '_fpp_default_atts', true ),
		'supports_content' => get_post_meta( $post->ID, '_fpp_supports_content', true ),
		'default_css_class' => get_post_meta( $post->ID, '_fpp_default_css_class', true ),
		'visibility' => get_post_meta( $post->ID, '_fpp_visibility', true ),
		'roles' => (array) get_post_meta( $post->ID, '_fpp_roles', true ),
		'pages' => get_post_meta( $post->ID, '_fpp_pages', true ),
		'posts' => get_post_meta( $post->ID, '_fpp_posts', true ),
		'date_start' => get_post_meta( $post->ID, '_fpp_date_start', true ),
		'date_end' => get_post_meta( $post->ID, '_fpp_date_end', true ),
		'hidden_behavior' => get_post_meta( $post->ID, '_fpp_hidden_behavior', true ),
	);
	if ( '' === $meta['output_type'] ) { $meta['output_type'] = 'static_html'; }
	if ( '' === $meta['visibility'] ) { $meta['visibility'] = 'always'; }
	if ( '' === $meta['hidden_behavior'] ) { $meta['hidden_behavior'] = 'nothing'; }
	if ( '' === trim( (string) $meta['default_atts'] ) ) { $meta['default_atts'] = "{\n  \"title\": \"\",\n  \"url\": \"#\",\n  \"label\": \"Подробнее\",\n  \"class\": \"\"\n}"; }
	echo '<p><strong>Безопасность:</strong> PHP-код не выполняется. JavaScript и опасные теги фильтруются.</p>';
	echo '<p><label>Slug: <input type="text" name="fpp_slug" value="' . esc_attr( $meta['slug'] ) . '" class="regular-text"></label></p>';
	echo '<p><label><input type="checkbox" name="fpp_enable_shortcode" value="1" ' . checked( '1', $meta['enable_shortcode'], false ) . '> Включён</label></p>';
	echo '<p><label>Тип вывода: <select name="fpp_output_type">';
	foreach ( fpp_custom_shortcode_output_types() as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $meta['output_type'], $k, false ) . '>' . esc_html( $v ) . '</option>';
	}
	echo '</select></label></p>';
	echo '<p><label>Описание<br><textarea name="fpp_description" rows="3" class="large-text">' . esc_textarea( $meta['description'] ) . '</textarea></label></p>';
	echo '<p><label>Шаблон вывода<br><textarea name="fpp_template" rows="8" class="large-text code">' . esc_textarea( $meta['template'] ) . '</textarea></label></p>';
	echo '<p>Плейсхолдеры: {{content}}, {{title}}, {{text}}, {{url}}, {{label}}, {{class}}, {{image}}, {{date}}</p>';
	echo '<p><label>Атрибуты по умолчанию (JSON)<br><textarea name="fpp_default_atts" rows="6" class="large-text code">' . esc_textarea( $meta['default_atts'] ) . '</textarea></label></p>';
	echo '<p><label><input type="checkbox" name="fpp_supports_content" value="1" ' . checked( '1', $meta['supports_content'], false ) . '> Поддерживает вложенный контент</label></p>';
	echo '<p><label>CSS-класс по умолчанию <input type="text" name="fpp_default_css_class" value="' . esc_attr( $meta['default_css_class'] ) . '" class="regular-text"></label></p>';
	echo '<p><label>Видимость: <select name="fpp_visibility">';
	foreach ( fpp_custom_shortcode_visibility_types() as $k => $v ) {
		echo '<option value="' . esc_attr( $k ) . '" ' . selected( $meta['visibility'], $k, false ) . '>' . esc_html( $v ) . '</option>';
	}
	echo '</select></label></p>';
	$roles = array( 'administrator', 'editor', 'author', 'contributor', 'subscriber' );
	echo '<p>Роли:<br>';
	foreach ( $roles as $role ) {
		echo '<label><input type="checkbox" name="fpp_roles[]" value="' . esc_attr( $role ) . '" ' . checked( in_array( $role, $meta['roles'], true ), true, false ) . '> ' . esc_html( $role ) . '</label> ';
	}
	echo '</p>';
	echo '<p><label>ID страниц (через запятую): <input type="text" name="fpp_pages" value="' . esc_attr( $meta['pages'] ) . '" class="regular-text"></label></p>';
	echo '<p><label>ID записей (через запятую): <input type="text" name="fpp_posts" value="' . esc_attr( $meta['posts'] ) . '" class="regular-text"></label></p>';
	echo '<p><label>Дата начала <input type="date" name="fpp_date_start" value="' . esc_attr( $meta['date_start'] ) . '"></label> <label>Дата конца <input type="date" name="fpp_date_end" value="' . esc_attr( $meta['date_end'] ) . '"></label></p>';
	echo '<p><label>Если скрыто: <select name="fpp_hidden_behavior"><option value="nothing" ' . selected( $meta['hidden_behavior'], 'nothing', false ) . '>nothing</option><option value="comment" ' . selected( $meta['hidden_behavior'], 'comment', false ) . '>comment</option><option value="placeholder" ' . selected( $meta['hidden_behavior'], 'placeholder', false ) . '>placeholder</option></select></label></p>';
	$sample = fpp_custom_shortcode_usage_example( $post->ID );
	echo '<div class="fpp-usage-example"><strong>Пример использования:</strong><code>' . esc_html( $sample ) . '</code></div>';
}

function fpp_custom_shortcode_usage_example( $post_id ) {
	$slug = get_post_meta( $post_id, '_fpp_slug', true );
	$slug = $slug ? $slug : 'fpp_shortcode_name';
	$supports_content = '1' === get_post_meta( $post_id, '_fpp_supports_content', true );
	$base = '[' . $slug . ' title="Заголовок" url="/page/" label="Подробнее"]';
	return $supports_content ? $base . 'Текст[/' . $slug . ']' : $base;
}

function fpp_save_custom_shortcode_meta( $post_id ) {
	if ( ! isset( $_POST['fpp_custom_shortcode_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fpp_custom_shortcode_nonce'] ) ), 'fpp_save_custom_shortcode' ) ) { return; }
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) { return; }
	$slug = isset( $_POST['fpp_slug'] ) ? fpp_sanitize_shortcode_slug( wp_unslash( $_POST['fpp_slug'] ) ) : '';
	if ( in_array( $slug, fpp_custom_shortcodes_reserved_slugs(), true ) ) {
		add_filter( 'redirect_post_location', function ( $location ) { return add_query_arg( 'fpp_slug_error', 1, $location ); } );
		return;
	}
	update_post_meta( $post_id, '_fpp_slug', $slug );
	update_post_meta( $post_id, '_fpp_enable_shortcode', isset( $_POST['fpp_enable_shortcode'] ) ? '1' : '0' );
	update_post_meta( $post_id, '_fpp_output_type', sanitize_key( wp_unslash( $_POST['fpp_output_type'] ?? 'static_html' ) ) );
	update_post_meta( $post_id, '_fpp_description', sanitize_textarea_field( wp_unslash( $_POST['fpp_description'] ?? '' ) ) );
	update_post_meta( $post_id, '_fpp_template', wp_kses_post( wp_unslash( $_POST['fpp_template'] ?? '' ) ) );
	update_post_meta( $post_id, '_fpp_default_atts', wp_kses_post( wp_unslash( $_POST['fpp_default_atts'] ?? '' ) ) );
	update_post_meta( $post_id, '_fpp_supports_content', isset( $_POST['fpp_supports_content'] ) ? '1' : '0' );
	update_post_meta( $post_id, '_fpp_default_css_class', sanitize_html_class( wp_unslash( $_POST['fpp_default_css_class'] ?? '' ) ) );
	update_post_meta( $post_id, '_fpp_visibility', sanitize_key( wp_unslash( $_POST['fpp_visibility'] ?? 'always' ) ) );
	$roles = isset( $_POST['fpp_roles'] ) ? array_map( 'sanitize_key', (array) wp_unslash( $_POST['fpp_roles'] ) ) : array();
	update_post_meta( $post_id, '_fpp_roles', $roles );
	update_post_meta( $post_id, '_fpp_pages', sanitize_text_field( wp_unslash( $_POST['fpp_pages'] ?? '' ) ) );
	update_post_meta( $post_id, '_fpp_posts', sanitize_text_field( wp_unslash( $_POST['fpp_posts'] ?? '' ) ) );
	update_post_meta( $post_id, '_fpp_date_start', sanitize_text_field( wp_unslash( $_POST['fpp_date_start'] ?? '' ) ) );
	update_post_meta( $post_id, '_fpp_date_end', sanitize_text_field( wp_unslash( $_POST['fpp_date_end'] ?? '' ) ) );
	update_post_meta( $post_id, '_fpp_hidden_behavior', sanitize_key( wp_unslash( $_POST['fpp_hidden_behavior'] ?? 'nothing' ) ) );
}
add_action( 'save_post_fpp_shortcode', 'fpp_save_custom_shortcode_meta' );
