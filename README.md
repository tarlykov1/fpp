# GeneratePress Child

This repository contains a child theme for GeneratePress.

## Notes

- The parent theme **GeneratePress** must be installed separately in WordPress.
- This repository contains only child-theme files.
- Old Enigma-related files were removed from this repository.
- Add custom styles in `style.css`.
- Add custom PHP logic in `functions.php`.

## Fruitful Shortcodes compatibility

- Устаревший плагин `fruitful-shortcodes` не используется и не устанавливается.
- В дочернюю тему добавлен слой совместимости, который заново регистрирует старые шорткоды Fruitful.
- Реализованные шорткоды: `fruitful_dbox`, `fruitful_sep`, `fruitful_alert`, `fruitful_btn`, `fruitful_pbar`, `fruitful_bar`, `fruitful_tabs`, `fruitful_tab`, `fruitful_tab_link`, `fruitful_ibox_row`, `fruitful_ibox`, `fruitful_recent_posts`, `fruitful_recent_posts_slider`.
- PHP-логика совместимости: `includes/fruitful-shortcodes-compat.php`.
- CSS-стили совместимости: `assets/css/fruitful-shortcodes-compat.css`.
