<?php
/**
 * Fruitful shortcodes settings page.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function fpp_fruitful_admin_menu() {
	add_options_page( 'Fruitful Shortcodes', 'Fruitful Shortcodes', 'manage_options', 'fpp-fruitful-shortcodes', 'fpp_fruitful_admin_page' );
}
add_action( 'admin_menu', 'fpp_fruitful_admin_menu' );

function fpp_fruitful_admin_styles( $hook ) {
	if ( 'settings_page_fpp-fruitful-shortcodes' !== $hook && 'post.php' !== $hook && 'post-new.php' !== $hook ) { return; }
	$rel = '/assets/css/fruitful-shortcodes-admin.css';
	$path = get_stylesheet_directory() . $rel;
	wp_enqueue_style( 'fpp-fruitful-admin', get_stylesheet_directory_uri() . $rel, array(), file_exists( $path ) ? (string) filemtime( $path ) : null );
}
add_action( 'admin_enqueue_scripts', 'fpp_fruitful_admin_styles' );

function fpp_fruitful_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'status';
	$tabs = array('status'=>'Статус','compat'=>'Настройки совместимости','guide'=>'Инструкция Fruitful Shortcodes','scan'=>'Поиск старых шорткодов','custom'=>'Пользовательские шорткоды');
	echo '<div class="wrap"><h1>Fruitful Shortcodes</h1><h2 class="nav-tab-wrapper">';
	foreach ( $tabs as $key => $label ) {
		echo '<a class="nav-tab ' . ( $tab === $key ? 'nav-tab-active' : '' ) . '" href="' . esc_url( admin_url( 'options-general.php?page=fpp-fruitful-shortcodes&tab=' . $key ) ) . '">' . esc_html( $label ) . '</a>';
	}
	echo '</h2>';
	if ( 'custom' === $tab ) { fpp_render_custom_shortcodes_tab(); }
	else { echo '<p>Раздел: ' . esc_html( $tabs[ $tab ] ?? 'Статус' ) . '</p>'; }
	echo '</div>';
}

function fpp_render_custom_shortcodes_tab() {
	fpp_handle_custom_shortcode_import_export();
	echo '<p><a class="button button-primary" href="' . esc_url( admin_url( 'post-new.php?post_type=fpp_shortcode' ) ) . '">Создать шорткод</a></p>';
	fpp_render_custom_shortcode_diagnostics();
	$items = get_posts( array( 'post_type' => 'fpp_shortcode', 'post_status' => array( 'publish','draft' ), 'numberposts' => -1 ) );
	echo '<table class="widefat striped fpp-custom-shortcodes-table"><thead><tr><th>Название</th><th>Шорткод</th><th>Статус</th><th>Тип</th><th>Видимость</th><th>Пример</th><th></th></tr></thead><tbody>';
	foreach ( $items as $item ) {
		$slug = get_post_meta( $item->ID, '_fpp_slug', true );
		$status = '1' === get_post_meta( $item->ID, '_fpp_enable_shortcode', true ) ? 'включён' : 'выключен';
		$type = get_post_meta( $item->ID, '_fpp_output_type', true );
		$vis = get_post_meta( $item->ID, '_fpp_visibility', true );
		echo '<tr><td>' . esc_html( $item->post_title ) . '</td><td><code>[' . esc_html( $slug ) . ']</code></td><td><span class="fpp-status fpp-status-' . esc_attr( 'включён' === $status ? 'on' : 'off' ) . '">' . esc_html( $status ) . '</span></td><td>' . esc_html( $type ) . '</td><td>' . esc_html( $vis ) . '</td><td><code>' . esc_html( fpp_custom_shortcode_usage_example( $item->ID ) ) . '</code></td><td><a href="' . esc_url( get_edit_post_link( $item->ID ) ) . '">Редактировать</a></td></tr>';
	}
	echo '</tbody></table>';
	echo '<h3>Экспорт / импорт</h3>';
	echo '<form method="post"><input type="hidden" name="fpp_export_shortcodes" value="1">';
	wp_nonce_field( 'fpp_export_shortcodes_action', 'fpp_export_shortcodes_nonce' );
	submit_button( 'Экспорт JSON', 'secondary', 'submit', false );
	echo '</form>';
	echo '<form method="post" enctype="multipart/form-data" style="margin-top:10px;">';
	wp_nonce_field( 'fpp_import_shortcodes_action', 'fpp_import_shortcodes_nonce' );
	echo '<input type="file" name="fpp_import_file" accept="application/json" required> ';
	submit_button( 'Импорт JSON', 'secondary', 'fpp_import_shortcodes', false );
	echo '</form>';
}

function fpp_render_custom_shortcode_diagnostics() {
	$warnings = array();
	if ( ! is_child_theme() ) { $warnings[] = 'Дочерняя тема не активна.'; }
	$parent = wp_get_theme( get_template() );
	if ( 'GeneratePress' !== $parent->get( 'Name' ) ) { $warnings[] = 'Родительская тема не GeneratePress.'; }
	if ( is_plugin_active( 'fruitful-shortcodes/fruitful-shortcodes.php' ) ) { $warnings[] = 'Старый плагин Fruitful Shortcodes активен.'; }
	if ( ! function_exists( 'fpp_register_custom_shortcodes_runtime' ) ) { $warnings[] = 'Файл runtime не подключён.'; }
	$items = get_posts( array( 'post_type' => 'fpp_shortcode', 'numberposts' => -1 ) );
	foreach ( $items as $item ) {
		$slug = get_post_meta( $item->ID, '_fpp_slug', true );
		if ( 0 !== strpos( $slug, 'fpp_' ) ) { $warnings[] = 'Slug ' . $slug . ' без префикса fpp_.'; }
		if ( shortcode_exists( $slug ) && '1' === get_post_meta( $item->ID, '_fpp_enable_shortcode', true ) ) { $warnings[] = 'Slug ' . $slug . ' уже зарегистрирован.'; }
	}
	foreach ( $warnings as $w ) { echo '<div class="notice notice-warning"><p>' . esc_html( $w ) . '</p></div>'; }
}

function fpp_handle_custom_shortcode_import_export() {
	if ( isset( $_POST['fpp_export_shortcodes'] ) && isset( $_POST['fpp_export_shortcodes_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fpp_export_shortcodes_nonce'] ) ), 'fpp_export_shortcodes_action' ) ) {
		$items = get_posts( array( 'post_type' => 'fpp_shortcode', 'numberposts' => -1 ) );
		$data = array();
		foreach ( $items as $item ) {
			$data[] = array( 'title' => $item->post_title, 'slug' => get_post_meta( $item->ID, '_fpp_slug', true ), 'meta' => get_post_meta( $item->ID ) );
		}
		header( 'Content-Type: application/json; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=fpp-custom-shortcodes.json' );
		echo wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT );
		exit;
	}
	if ( isset( $_POST['fpp_import_shortcodes'] ) && isset( $_POST['fpp_import_shortcodes_nonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['fpp_import_shortcodes_nonce'] ) ), 'fpp_import_shortcodes_action' ) && ! empty( $_FILES['fpp_import_file']['tmp_name'] ) ) {
		$content = file_get_contents( $_FILES['fpp_import_file']['tmp_name'] );
		$data = json_decode( (string) $content, true );
		if ( ! is_array( $data ) ) { echo '<div class="notice notice-error"><p>Некорректный JSON.</p></div>'; return; }
		$imported = 0; $skipped = 0;
		foreach ( $data as $row ) {
			$slug = fpp_sanitize_shortcode_slug( $row['slug'] ?? '' );
			if ( empty( $slug ) || get_posts( array( 'post_type' => 'fpp_shortcode', 'meta_key' => '_fpp_slug', 'meta_value' => $slug, 'numberposts' => 1 ) ) ) { $skipped++; continue; }
			$post_id = wp_insert_post( array( 'post_type' => 'fpp_shortcode', 'post_status' => 'publish', 'post_title' => sanitize_text_field( $row['title'] ?? $slug ) ) );
			if ( $post_id ) { update_post_meta( $post_id, '_fpp_slug', $slug ); $imported++; }
		}
		echo '<div class="notice notice-success"><p>Импорт завершён. Импортировано: ' . esc_html( (string) $imported ) . ', пропущено: ' . esc_html( (string) $skipped ) . '.</p></div>';
	}
}
