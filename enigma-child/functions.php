<?php
/**
 * Базовые подключения стилей/скриптов
 */
add_action( 'wp_enqueue_scripts', 'enigma_child_enqueue_styles' );
function enigma_child_enqueue_styles() {
    wp_enqueue_style(
        'enigma-parent-style',
        get_template_directory_uri() . '/style.css'
    );

    wp_enqueue_style(
        'enigma-child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array( 'enigma-parent-style' ),
        wp_get_theme()->get( 'Version' )
    );
}

/**
 * Сюда постепенно переносите свои функции
 *     – следите, чтобы названия не дублировали функции из родителя.
 */
