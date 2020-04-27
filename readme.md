# SimpleSEF
![SMF 2.1](https://img.shields.io/badge/SMF-2.1-ed6033.svg?style=flat)
![PHP](https://img.shields.io/badge/PHP-^7.2-blue.svg?style=flat)

* **Original authors:** [Mattzuba](https://bitbucket.org/mattzuba/simplesef), [Suki](https://github.com/MissAllSunday/SimpleSEF)
* **Author:** Bugo [dragomano.ru](https://dragomano.ru/translations/simplesef)
* **License:** [MPL 1.1 license](https://www.mozilla.org/en-US/MPL/1.1/)
* **Compatible with:** SMF 2.1.x / PHP 7.2+
* **Tested on:** PHP 7.3.17 / MariaDB 10.4.12
* **Hooks only:** Yes
* **Languages:** English, Russian

## Description
This mod creates content filled URLs for your forum.

### Examples:

```
yourboard.com/index.php =>> yourboard.com/
yourboard.com/index.php?board=1.0 =>> yourboard.com/general-discussion/
yourboard.com/index.php?topic=1.0 =>> yourboard.com/general-discussion/welcome-smf-1.0
yourboard.com/index.php?action=profile;u=1 =>> yourboard.com/profile/user-public-name-1
```

### Features:
* Makes no core code changes to SMF **AT ALL**.
* Works with Apache (mod_rewrite required) or IIS7 (Microsoft URL Rewrite module required + web.config files in your directory).
* Custom action handling for other mods.
* Action ignoring- Prevent urls with certain actions from being rewritten.
* Action aliasing- change 'register' to 'signup' for example.
* Very low overhead to each page load- Average database query count per page load- 2 (with caching enabled, 3 without).
* Smart- when you add mods with new actions to your board, SimpleSEF readily recognizes the new ones and accounts for them without any interaction from you.
* Specify the 'space' character in the URL (ie: general_discussion, general-discussion, general.discussion, etc).
* UTF-8 compatible, changes non-ASCII characters to their closes US-ASCII equivilant.

#### Post-Install Notes:
Please ensure your .htaccess or web.config file contains the proper information for this mod to work.  Visit the admin panel and click on the [Help] link at the end of the bolded text in the page description for more information.

### Изменения в этом форке
* Добавлена русская локализация.
* Адаптация для SMF 2.1.x.
* Исправлены некоторые ошибки оригинального мода.
* Удалены ненужные настройки (удаление лишних символов, перевод в нижний регистр, окончания URL-адресов и т. п.) и функции.
* Интегрирован [Behat Transliterator](https://github.com/Behat/Transliterator).