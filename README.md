# SimpleSEF

![SMF 2.1](https://img.shields.io/badge/SMF-2.1-ed6033.svg?style=flat)
![License](https://img.shields.io/github/license/dragomano/simplesef)
![Hooks only: Yes](https://img.shields.io/badge/Hooks%20only-YES-blue)
![PHP](https://img.shields.io/badge/PHP-^8.0-blue.svg?style=flat)
[![Crowdin](https://badges.crowdin.net/simplesef/localized.svg)](https://crowdin.com/project/simplesef)

- **Original authors:** [Matt Zuba](https://bitbucket.org/mattzuba/), [Suki](https://github.com/MissAllSunday)
- **Contributor:** [Bugo](https://dragomano.ru/reviews/simplesef)
- **Tested on:** PHP 8.0.30 / MariaDB 10.6.11
- **Languages:** English, Russian

## Description

This mod creates content filled URLs for your forum.

### Examples:

```
yourboard.com/index.php => yourboard.com/
yourboard.com/index.php?board=1.0 => yourboard.com/general-discussion/
yourboard.com/index.php?topic=1.0 => yourboard.com/general-discussion/welcome-smf-1.0
yourboard.com/index.php?action=profile;u=1 => yourboard.com/profile/user-public-name-1
```

### Changes in this fork

- Adapted for SMF 2.1.x.
- Updated license (MPL 1.1 => 2.0).
- Changed the minimum PHP version.
- Added Russian localization.
- Fixed some bugs in the original mod.
- Removed unnecessary settings and functions (if you need them, install [original mod](https://github.com/MissAllSunday/SimpleSEF)):
  - Create simple URLs (like `/forum/board-1/`)
  - URL endings in topics and messages (like `.html`)
  - Words removed from addresses (already available in Behat Transliterator)
  - Characters removed from addresses (already available in Behat Transliterator)
  - URLs in lower case (lower case is now the default)
  - Allow the use of aliases, ignore some areas and some other options (advanced settings are now always shown)
  - Debug mode
  - Performance testing
- Integrated [Behat Transliterator](https://github.com/Behat/Transliterator) v1.5.0.
- Added the ability to search for mod settings through a quick search in the admin panel.
