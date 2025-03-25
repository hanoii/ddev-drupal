<!-- [![tests](https://github.com/hanoii/ddev-drupal/actions/workflows/tests.yml/badge.svg)](https://github.com/hanoii/ddev-drupal/actions/workflows/tests.yml) -->

![project is maintained](https://img.shields.io/maintenance/yes/2024.svg)

# ddev-drupal

<!-- toc -->

- [What is ddev-drupal?](#what-is-ddev-drupal)
- [Features](#features)
  * [settings.dev.php](#settingsdevphp)
  * [.ddev/drupal/assets/settings.local.php](#ddevdrupalassetssettingslocalphp)
- [Options](#options)
  * [Caches enabled](#caches-enabled)
  * [Close to production](#close-to-production)

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
$is_ddev_project = !empty($_ENV['IS_DDEV_PROJECT']) && $_ENV['IS_DDEV_PROJECT'] == "true";
if ($is_ddev_project) {
  $ddev_dev_settings = '/var/www/html/.ddev/drupal/assets/settings.dev.php';
  if (is_readable($ddev_dev_settings)) {
    require $ddev_dev_settings;
  }

  // Additionally, look for a project-wide settings.dev.php site settings directory.
  $project_dev_settings = $app_root . '/' . $site_path . '/settings.dev.php';
  if (is_readable($project_dev_settings)) {
    require $project_dev_settings;
  }
}
```

### .ddev/drupal/assets/settings.local.php

The settings.dev.php file of this add-on also includes, if present, a
`.ddev/drupal/assets/settings.local.php` before everything else.

This files allows to configure things that will affect some defaults of this
add-on.

See [options](#options) below.

This file is _gitignored_.

## Options

By default, this add-on is configured to put drupal in a very dev oriented mode:

- No preprocessing
- No cache whatsoever
- Includes a dev.services.yml file that disables twig caching and add some
  debugging
- Assumes a `development` named config split that should be enabled.

### Caches enabled

**The caches are disabled by default.**

In case you prefer not to have this add-on disable caches and handle it
different from how this add-on does it, you can configure either with an
`DDEV_DRUPAL_CACHE_PRODUCTION` [enviroment variable][ddev-envvars] set to `true`
or adding a
[`.ddev/drupal/assets/settings.local.php`](#ddevdrupalassetssettingslocalphp)
file with:

```php
<?php

$ddev_drupal_cache_production = TRUE;
```

<!-- prettier-ignore -->
> [!WARNING]
> The boolean value of `$ddev_drupal_cache_production` will override whatever is
> in the environment variable, allowing you to alter the functionality
> temporarily.

### Close to production

Another option is to configure this add-on to keep more settings closer to
production (but still allow other defaults that this add-on consider improtant
to be set on dev boxes regardless) by either setting `DDEV_DRUPAL_PRODUCTION`
[enviroment variable][ddev-envvars] to `true` or adding a
[`.ddev/drupal/assets/settings.local.php`](#ddevdrupalassetssettingslocalphp)
file with:

[ddev-envvars]:
  https://ddev.readthedocs.io/en/stable/users/extend/customization-extendibility/#environment-variables-for-containers-and-services

```php
<?php

$ddev_drupal_production = TRUE;
```

<!-- prettier-ignore -->
> [!WARNING]
> The boolean value of `$ddev_drupal_production` will override whatever is in
> the environment variable, allowing you to alter the functionality temporarily.

**Contributed and maintained by [@hanoii](https://github.com/hanoii)**
