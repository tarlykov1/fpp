<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function fpp_fruitful_admin_menu() {
	add_options_page( 'Fruitful Shortcodes', 'Fruitful Shortcodes', 'manage_options', 'fpp-fruitful-shortcodes', 'fpp_fruitful_admin_page' );
}
add_action( 'admin_menu', 'fpp_fruitful_admin_menu' );

function fpp_fruitful_admin_styles( $hook ) {
	if ( 'settings_page_fpp-fruitful-shortcodes' !== $hook ) { return; }
	$rel = '/assets/css/fruitful-shortcodes-admin.css';
	$path = get_stylesheet_directory() . $rel;
	wp_enqueue_style( 'fpp-fruitful-admin', get_stylesheet_directory_uri() . $rel, array(), file_exists( $path ) ? (string) filemtime( $path ) : null );
}
add_action( 'admin_enqueue_scripts', 'fpp_fruitful_admin_styles' );

function fpp_fruitful_get_settings() { return wp_parse_args( get_option( 'fpp_fruitful_shortcodes_settings', array() ), array( 'enable_compat' => '1', 'load_css' => '1', 'show_debug_marker' => '0', 'dbox_style' => 'default', 'recent_posts_count' => 4 ) ); }
function fpp_fruitful_save_settings() {
	if ( isset( $_POST['fpp_save_settings'] ) && check_admin_referer( 'fpp_save_settings_action', 'fpp_save_settings_nonce' ) ) {
		$settings = array(
			'enable_compat'     => isset( $_POST['enable_compat'] ) ? '1' : '0',
			'load_css'          => isset( $_POST['load_css'] ) ? '1' : '0',
			'show_debug_marker' => isset( $_POST['show_debug_marker'] ) ? '1' : '0',
			'dbox_style'        => sanitize_key( wp_unslash( $_POST['dbox_style'] ?? 'default' ) ),
			'recent_posts_count'=> absint( $_POST['recent_posts_count'] ?? 4 ),
		);
		update_option( 'fpp_fruitful_shortcodes_settings', $settings );
		echo '<div class="notice notice-success"><p>Настройки сохранены.</p></div>';
	}
}

function fpp_fruitful_admin_page() {
	if ( ! current_user_can( 'manage_options' ) ) { return; }
	fpp_fruitful_save_settings();
	$tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'status';
	$tabs = array('status'=>'Статус','compat'=>'Настройки совместимости','guide'=>'Инструкция Fruitful Shortcodes','scan'=>'Поиск старых шорткодов','custom'=>'Пользовательские шорткоды');
	echo '<div class="wrap"><h1>Fruitful Shortcodes</h1><h2 class="nav-tab-wrapper">';
	foreach ( $tabs as $k => $l ) echo '<a class="nav-tab '.( $tab===$k?'nav-tab-active':'' ).'" href="'.esc_url(admin_url('options-general.php?page=fpp-fruitful-shortcodes&tab='.$k)).'">'.esc_html($l).'</a>';
	echo '</h2>';
	if ( 'custom' === $tab ) fpp_render_custom_shortcodes_tab();
	elseif ( 'guide' === $tab ) fpp_render_guide_tab();
	elseif ( 'scan' === $tab ) fpp_render_scan_tab();
	elseif ( 'compat' === $tab ) fpp_render_compat_tab();
	else fpp_render_status_tab();
	echo '</div>';
}

function fpp_get_custom_shortcode_posts() { return get_posts( array( 'post_type'=>'fpp_shortcode','post_status'=>array('publish','draft'),'numberposts'=>-1 ) ); }
function fpp_render_custom_shortcodes_tab() {
	$items = fpp_get_custom_shortcode_posts();
	echo '<p><a class="button button-primary" href="'.esc_url( admin_url( 'post-new.php?post_type=fpp_shortcode' ) ).'">Создать шорткод</a></p>';
	if ( empty( $items ) ) {
		echo '<div class="fpp-empty"><h3>Пользовательские шорткоды ещё не созданы.</h3><p>Здесь можно создавать собственные шорткоды без редактирования PHP-кода. Например: [fpp_button], [fpp_notice], [fpp_card].</p><pre>[fpp_button url="/biblioteka/" label="Перейти в библиотеку"]</pre><pre>[fpp_notice title="Важно"]Текст уведомления[/fpp_notice]</pre><pre>[fpp_card title="Общественная приёмная"]Описание проекта[/fpp_card]</pre></div>';
		return;
	}
	echo '<table class="widefat striped"><thead><tr><th>Название</th><th>Шорткод</th><th>Статус</th><th>Тип</th><th>Видимость</th><th>Пример</th><th>Действия</th></tr></thead><tbody>';
	foreach ( $items as $item ) {
		$slug = get_post_meta( $item->ID, '_fpp_shortcode_slug', true );
		$enabled = '1' === get_post_meta( $item->ID, '_fpp_shortcode_enabled', true );
		$toggle = wp_nonce_url( admin_url( 'options-general.php?page=fpp-fruitful-shortcodes&tab=custom&toggle=' . $item->ID ), 'fpp_toggle_shortcode_' . $item->ID );
		$delete = get_delete_post_link( $item->ID, '', true );
		echo '<tr><td>'.esc_html($item->post_title).'</td><td><code>['.esc_html($slug).']</code></td><td>'.($enabled?'Включён':'Отключён').'</td><td>'.esc_html(get_post_meta($item->ID,'_fpp_shortcode_type',true)).'</td><td>'.esc_html(get_post_meta($item->ID,'_fpp_shortcode_visibility',true)).'</td><td><code>['.esc_html($slug).' url="/biblioteka/" label="Подробнее"]</code></td><td><a href="'.esc_url(get_edit_post_link($item->ID)).'">Редактировать</a> | <a href="'.esc_url($toggle).'">'.($enabled?'Отключить':'Включить').'</a> | <a href="'.esc_url($delete).'">Удалить</a></td></tr>';
	}
	echo '</tbody></table>';
}
add_action( 'admin_init', function() {
	if ( ! isset( $_GET['page'], $_GET['toggle'] ) || 'fpp-fruitful-shortcodes' !== $_GET['page'] ) return;
	$id = absint( $_GET['toggle'] );
	if ( ! $id || ! wp_verify_nonce( $_GET['_wpnonce'] ?? '', 'fpp_toggle_shortcode_' . $id ) ) return;
	update_post_meta( $id, '_fpp_shortcode_enabled', '1' === get_post_meta( $id, '_fpp_shortcode_enabled', true ) ? '0' : '1' );
	wp_safe_redirect( admin_url( 'options-general.php?page=fpp-fruitful-shortcodes&tab=custom' ) ); exit;
} );

function fpp_render_guide_tab(){ echo '<table class="widefat striped"><thead><tr><th>Шорткод</th><th>Что делает</th><th>Атрибуты</th><th>Пример</th></tr></thead><tbody>';
$rows=[['[fruitful_dbox]','Декоративный блок/кнопка. Используется на страницах разделов.','content','[fruitful_dbox]ОБЩЕСТВЕННАЯ ПРИЕМНАЯ[/fruitful_dbox]'],['[fruitful_btn]','Кнопка-ссылка.','link','[fruitful_btn link="/projects/"]Перейти[/fruitful_btn]'],['[fruitful_alert]','Блок уведомления.','type','[fruitful_alert type="success"]Текст[/fruitful_alert]'],['[fruitful_sep]','Разделитель/отступ.','height','[fruitful_sep height="30"]'],['[fruitful_tabs]','Вкладки.','title','[fruitful_tabs] [fruitful_tab title="Раздел 1"]Текст[/fruitful_tab] [/fruitful_tabs]'],['[fruitful_ibox]','Информационный блок.','title','[fruitful_ibox title="Заголовок"]Текст[/fruitful_ibox]'],['[fruitful_recent_posts]','Последние записи.','posts','[fruitful_recent_posts posts="4"]'],['[fruitful_recent_posts_slider]','Последние записи в виде сетки/слайдера-заглушки.','posts','[fruitful_recent_posts_slider posts="4"]']];
foreach($rows as $r){echo '<tr><td><code>'.esc_html($r[0]).'</code></td><td>'.esc_html($r[1]).'</td><td>'.esc_html($r[2]).'</td><td><code>'.esc_html($r[3]).'</code></td></tr>';} echo '</tbody></table>'; }
function fpp_render_scan_tab(){ global $wpdb; $posts=$wpdb->get_results("SELECT ID, post_type, post_title, post_status, post_content FROM {$wpdb->posts} WHERE post_type IN ('post','page') AND post_status NOT IN ('trash','auto-draft') AND post_content LIKE '%[fruitful_%'");
if(empty($posts)){echo '<p>Старые шорткоды Fruitful не найдены.</p>';return;} echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Тип</th><th>Заголовок</th><th>Статус</th><th>Найденные шорткоды</th><th>Редактировать</th><th>Открыть</th></tr></thead><tbody>';
foreach($posts as $p){preg_match_all('/\[(fruitful_[a-z0-9_]+)/i',$p->post_content,$m);$found=implode(', ',array_unique($m[1]??[]));echo '<tr><td>'.(int)$p->ID.'</td><td>'.esc_html($p->post_type).'</td><td>'.esc_html($p->post_title).'</td><td>'.esc_html($p->post_status).'</td><td><code>'.esc_html($found).'</code></td><td><a href="'.esc_url(get_edit_post_link($p->ID)).'">Редактировать</a></td><td><a target="_blank" href="'.esc_url(get_permalink($p->ID)).'">Открыть</a></td></tr>';}
 echo '</tbody></table>'; }
function fpp_render_status_tab(){ $parent=wp_get_theme(get_template())->get('Name');$custom=count(fpp_get_custom_shortcode_posts()); global $wpdb; $legacy=(int)$wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('post','page') AND post_content LIKE '%[fruitful_%'");
$cards=[['Дочерняя тема активна',is_child_theme()?'да':'нет'],['Родительская тема',$parent],['Совместимость Fruitful включена',fpp_fruitful_get_settings()['enable_compat']==='1'?'да':'нет'],['CSS совместимости подключается',fpp_fruitful_get_settings()['load_css']==='1'?'да':'нет'],['Пользовательские шорткоды активны',function_exists('fpp_register_custom_shortcodes_runtime')?'да':'нет'],['Старый плагин Fruitful Shortcodes активен',is_plugin_active('fruitful-shortcodes/fruitful-shortcodes.php')?'да':'нет'],['Количество страниц со старыми шорткодами',(string)$legacy],['Количество пользовательских шорткодов',(string)$custom]];
 echo '<div class="fpp-cards">'; foreach($cards as $c){echo '<div class="fpp-card"><strong>'.esc_html($c[0]).'</strong><span>'.esc_html($c[1]).'</span></div>';} echo '</div>'; }
function fpp_render_compat_tab(){ $s=fpp_fruitful_get_settings(); echo '<form method="post">'; wp_nonce_field( 'fpp_save_settings_action', 'fpp_save_settings_nonce' );
 echo '<p><label><input type="checkbox" name="enable_compat" value="1" '.checked('1',$s['enable_compat'],false).'> включить поддержку Fruitful</label></p>';
 echo '<p><label><input type="checkbox" name="load_css" value="1" '.checked('1',$s['load_css'],false).'> подключать стили совместимости</label></p>';
 echo '<p><label><input type="checkbox" name="show_debug_marker" value="1" '.checked('1',$s['show_debug_marker'],false).'> показывать HTML-маркер</label></p>';
 echo '<p><label>style fruitful_dbox <select name="dbox_style">'; foreach(['default','button','card','minimal'] as $v){echo '<option value="'.$v.'" '.selected($s['dbox_style'],$v,false).'>'.$v.'</option>';} echo '</select></label></p>';
 echo '<p><label>количество записей по умолчанию <input type="number" min="1" name="recent_posts_count" value="'.esc_attr((string)$s['recent_posts_count']).'"></label></p>';
 submit_button('Сохранить','primary','fpp_save_settings'); echo '</form>'; }
