# SimpleSEF

![SMF 2.1](https://img.shields.io/badge/SMF-2.1-ed6033.svg?style=flat)
![Hooks only: Yes](https://img.shields.io/badge/Hooks%20only-YES-blue)
![PHP](https://img.shields.io/badge/PHP-^7.4-blue.svg?style=flat)

- **Original authors:** [Matt Zuba](https://bitbucket.org/mattzuba/), [Suki](https://github.com/MissAllSunday)
- **Contributor:** [Bugo](https://dragomano.ru/reviews/simplesef)
- **License:** [MPL 1.1 license](https://www.mozilla.org/en-US/MPL/1.1/)
- **Tested on:** PHP 8.0.30 / MariaDB 10.6.11
- **Languages:** English, Russian

## Описание

Мод преобразует стандартные URL-адреса форума в ЧПУ-варианты.

### Примеры:

```
yourboard.com/index.php => yourboard.com/
yourboard.com/index.php?board=1.0 => yourboard.com/general-discussion/
yourboard.com/index.php?topic=1.0 => yourboard.com/general-discussion/welcome-smf-1.0
yourboard.com/index.php?action=profile;u=1 => yourboard.com/profile/user-public-name-1
```

### Изменения в этом форке

- Добавлена русская локализация.
- Адаптация для SMF 2.1.x.
- Исправлены некоторые ошибки оригинального мода.
- Удалены ненужные настройки и функции (кому нужны — ставьте [оригинальный мод](https://github.com/MissAllSunday/SimpleSEF)):
  - Создавать простые URL (типа `/forum/board-1/`)
  - Окончание URL в темах и сообщениях (типа `.html`)
  - Слова, удаляемые из адресов (уже есть в Behat Transliterator)
  - Символы, удаляемые из адресов (уже есть в Behat Transliterator)
  - URL-адреса в нижнем регистре (теперь нижний регистр используется по умолчанию)
  - Разрешить использование псевдонимов, игнорирование некоторых областей и некоторые другие параметры (теперь расширенные настройки отображаются всегда)
  - Режим отладки
  - Тестирование производительности
- Интегрирован [Behat Transliterator](https://github.com/Behat/Transliterator) v1.5.0.
- Добавлена возможность искать настройки мода через быстрый поиск в админке.
