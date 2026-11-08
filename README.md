<!-- #ddev-generated -->
<!-- [![tests](https://github.com/hanoii/ddev-drupal/actions/workflows/tests.yml/badge.svg)](https://github.com/hanoii/ddev-drupal/actions/workflows/tests.yml) -->

![project is maintained](https://img.shields.io/maintenance/yes/2026.svg)

# ddev-drupal

<!-- toc -->

- [What is ddev-drupal?](#what-is-ddev-drupal)
- [Features](#features)
  * [settings.ddev-drupal.php](#settingsddev-drupalphp)
- [Options](#options)
  * [Presets](#presets)
  * [The config file](#the-config-file)
  * [Environment variable](#environment-variable)
  * [Upgrading from earlier versions](#upgrading-from-earlier-versions)

<!-- tocstop -->

## What is ddev-drupal?

This is a collection of scripts, assets and utils that I find useful working
with Drupal on ddev.

## Features

### settings.ddev-drupal.php

A common/general development settings file that any drupal site can include by
adding it to their main settings.php:

```php
// Include settings managed by hanoii/ddev-drupal.
if (getenv('IS_DDEV_PROJECT') === 'true') {
  $ddev_drupal_settings = '/var/www/html/.ddev/drupal/assets/settings.ddev-drupal.php';
  if (is_readable($ddev_drupal_settings)) {
    require $ddev_drupal_settings;
  }
}
```

Anything included after it can override what it sets, and can read the resolved
[options](#options) from `$ddev_drupal` (for example `$ddev_drupal['preset']`).
The exception is Twig debug, applied while the container is built: use the
`twig_debug` option for it.

## Options

Some settings are always applied:

- Assertions (on PHP < 8.3, otherwise set `zend.assertions` in php.ini), verbose
  error logging, `extension_discovery_scan_tests`.
- Any trusted host, `skip_permissions_hardening`.
- A local `hash_salt` and `allow_insecure_derivatives` for images.
- `/tmp` as temporary path, files in `sites/default/files` and private files in
  `sites/default/files/private`.
- The `cache.backend.null` and `cache.backend.database.null` cache backends.

The rest is driven by options:

| Option                 | Values                      | What it does                                                                                                                   |
| ---------------------- | --------------------------- | ------------------------------------------------------------------------------------------------------------------------------ |
| `preprocess`           | `true`, `false`             | CSS/JS aggregation.                                                                                                            |
| `cache`                | `none`, `db_null`, `normal` | Backend of the `render`, `dynamic_page_cache` and `page` bins. `db_null` stores entries in the database but never serves them. |
| `twig_debug`           | `true`, `false`             | Twig debug, auto reload and no Twig cache.                                                                                     |
| `cacheability_headers` | `true`, `false`             | `X-Drupal-Cache-Tags` / `X-Drupal-Cache-Contexts` debug headers.                                                               |
| `config_split`         | a split id, `false`         | Config split forced enabled.                                                                                                   |
| `stage_file_proxy`     | `true`, `false`             | Stage File Proxy hotlinking.                                                                                                   |

### Presets

Every option gets its value from a preset, `dev` by default, and can then be
overridden one by one in [the config file](#the-config-file).

| Option                 | `dev`         | `cache`       | `prod`   |
| ---------------------- | ------------- | ------------- | -------- |
| `preprocess`           | `false`       | `false`       | `true`   |
| `cache`                | `none`        | `normal`      | `normal` |
| `twig_debug`           | `true`        | `false`       | `false`  |
| `cacheability_headers` | `false`       | `false`       | `false`  |
| `config_split`         | `development` | `development` | `false`  |
| `stage_file_proxy`     | `true`        | `true`        | `false`  |

<!-- prettier-ignore -->
> [!CAUTION]
> `prod` leaves only the always applied settings. Anything else a project
> needs locally is up to the project.

### The config file

Overrides go in `sites/<site>/settings.ddev-drupal-config.php`, next to your
`settings.php`. Copy
[`.ddev/drupal/assets/example.settings.ddev-drupal-config.php`](drupal/assets/example.settings.ddev-drupal-config.php),
which documents every option, commented out, and uncomment what you need. Any
option left out follows the preset:

```php
<?php

// Caches on, but keep twig debug.
return [
  'preset' => 'cache',
  'twig_debug' => TRUE,
];
```

Changes apply on the next request, no restart or cache rebuild needed. With
caches on, pages cached before the change are still served until they expire or
the next `drush cr`. An unknown option or an invalid value stops with an error
naming it.

The file holds personal settings and should not be committed. Add it to your
`.gitignore`, for example:

```gitignore
/web/sites/*/settings.ddev-drupal-config.php
```

### Environment variable

`DDEV_DRUPAL_PRESET` sets the base preset, for instance for a whole team from a
committed `.ddev/config.*.yaml`, or permanently for yourself from
`.ddev/config.local.yaml`. Set it through `web_environment` ([environment
variables][ddev-envvars]); it needs a `ddev restart`. The config file still wins
over it.

### Upgrading from earlier versions

Everything that worked before still works, but is deprecated. `ddev add-on get`
warns about each of these it finds in the project (this add-on needs DDEV
v1.24.8 or later):

- The add-on's `.ddev/drupal/assets/settings.dev.php` is now a one-line shim
  that requires `settings.ddev-drupal.php`. Point your `settings.php` to the new
  name.
- `.ddev/drupal/assets/settings.local.php` setting `$ddev_drupal_production` /
  `$ddev_drupal_cache_production` is still read: production maps to the `prod`
  preset, cache production to `cache`. Move it to a `'preset'` in
  [the config file](#the-config-file) and delete the old file.
- The `DDEV_DRUPAL_PRODUCTION` / `DDEV_DRUPAL_CACHE_PRODUCTION` environment
  variables map to the same presets, now read as real booleans (`false` is
  false). Use `DDEV_DRUPAL_PRESET` instead.
- `dev.services.yml` is no longer loaded. Its services are now always available
  from `services/backends.services.yml`, so `cache.backend.database.null` can be
  used even with caches on.

[ddev-envvars]:
  https://ddev.readthedocs.io/en/stable/users/extend/customization-extendibility/#environment-variables-for-containers-and-services

**Contributed and maintained by [@hanoii](https://github.com/hanoii)**
