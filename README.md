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


## Пользовательские шорткоды

Создание: **Настройки → Fruitful Shortcodes → Пользовательские шорткоды**.

- Можно создавать безопасные шорткоды без редактирования PHP.
- Выполнение PHP из админки запрещено: шаблоны рендерятся только как безопасный HTML.
- Поддерживаемые типы: `static_html`, `text_block`, `button`, `card`, `notice`, `container`, `links_list`, `raw_safe_html`.
- Slug обязан начинаться с `fpp_`; значения нормализуются и фильтруются.
- Есть правила видимости: always, logged_in, logged_out, roles, pages, posts, date_range, disabled.
- Доступны плейсхолдеры: `{{content}}`, `{{title}}`, `{{text}}`, `{{url}}`, `{{label}}`, `{{class}}`, `{{image}}`, `{{date}}`.
- Доступен экспорт/импорт JSON пользовательских шорткодов.

Примеры:

`[fpp_notice title="Важно"]Текст уведомления[/fpp_notice]`

`[fpp_button url="/biblioteka/" label="Открыть библиотеку"]`

`[fpp_card title="Общественная приёмная"]Описание проекта[/fpp_card]`
