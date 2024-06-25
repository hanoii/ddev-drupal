<!-- [![tests](https://github.com/hanoii/ddev-drupal/actions/workflows/tests.yml/badge.svg)](https://github.com/hanoii/ddev-drupal/actions/workflows/tests.yml) -->

![project is maintained](https://img.shields.io/maintenance/yes/2024.svg)

# ddev-drupal

<!-- toc -->

- [What is ddev-drupal?](#what-is-ddev-drupal)
- [Features](#features)
  * [settings.dev.php](#settingsdevphp)

<!-- tocstop -->

## What is ddev-drupal?

This is a collection of scripts, assets and utils that I find useful working
with Drupal on ddev.

## Features

### settings.dev.php

A common/general settings.dev.php files that any drupal site can include by
adding it to their main settings.php:

```php
// include for settings managed by hanoii/ddev-drupal.
$ddev_dev_settings = '/var/www/html/.ddev/drupal/assets/settings.dev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($ddev_dev_settings)) {
  require $ddev_dev_settings;
}

// Additionally, or alternatively, you can add your own settings.dev.php to your 
// site settings directory.
$project_dev_settings = $app_root . '/' . $site_path . '/settings.dev.php';
if (getenv('IS_DDEV_PROJECT') == 'true' && is_readable($project_dev_settings)) {
  require $project_dev_settings;
}
```

**Contributed and maintained by [@hanoii](https://github.com/hanoii)**
