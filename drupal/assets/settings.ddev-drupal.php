<?php

/**
 * #ddev-generated
 * Development settings managed by hanoii/ddev-drupal.
 *
 * Documentation for most of these can be found on default.settings.php and
 * example.settings.local.php.
 *
 * What this file toggles is driven by options resolved by
 * \Ddev\Drupal\SettingsConfig: a preset (dev, cache or prod) plus per-site
 * overrides in sites/<site>/settings.ddev-drupal-config.php. See
 * example.settings.ddev-drupal-config.php next to this file.
 *
 * The resolved options are left in $ddev_drupal for any settings file
 * included after this one.
 */

// Load DDEV Drupal add-on autoloader.
require_once '/var/www/html/.ddev/drupal/autoload.php';

// Deprecated: .ddev/drupal/assets/settings.local.php could set
// $ddev_drupal_production / $ddev_drupal_cache_production. Still honored, but
// settings.ddev-drupal-config.php replaces it.
if (is_readable(\Ddev\Drupal\SettingsConfig::LEGACY_FILE)) {
  require \Ddev\Drupal\SettingsConfig::LEGACY_FILE;
}

$ddev_drupal = \Ddev\Drupal\SettingsConfig::resolve(
  $app_root . '/' . $site_path,
  isset($ddev_drupal_production) ? (bool) $ddev_drupal_production : NULL,
  isset($ddev_drupal_cache_production) ? (bool) $ddev_drupal_cache_production : NULL
);

// Kept for project settings that still check them.
$ddev_drupal_production = $ddev_drupal['preset'] === 'prod';
$ddev_drupal_cache_production = $ddev_drupal['cache'] === 'normal';

/**
 * Always on, whatever the options.
 */

// Assertions. zend.assertions itself can only be set in php.ini.
if (PHP_VERSION_ID < 80300) {
  assert_options(ASSERT_ACTIVE, TRUE);
  assert_options(ASSERT_EXCEPTION, TRUE);
}

// Allow every url.
$settings['trusted_host_patterns'] = [
  '^.*$',
];

$settings['skip_permissions_hardening'] = TRUE;

// In general this is not used as most devs rely on drush cr and other handy
// ways of rebuilding cache. If needed you can still add it to your own
// settings.
// $settings['rebuild_access'] = TRUE;
$settings['extension_discovery_scan_tests'] = TRUE;

// Cache backends shipped by this add-on (cache.backend.null and
// cache.backend.database.null), available to any bin.
$settings['container_yamls'][] = '/var/www/html/.ddev/drupal/assets/services/backends.services.yml';

$config['system.logging']['error_level'] = 'verbose';

// Local hash_salt not to use the same as remote ones.
$settings['hash_salt'] = 'development';

// Allowing insecure derivatives as hash_salt locally is different and content
// in wysiwyg get (like from the insert module) from production would not work
// otherwise.
$config['image.settings']['allow_insecure_derivatives'] = TRUE;

// Set temporary directory within the local filesystem.
// https://www.drupal.org/node/3039255
if (version_compare(\Drupal::VERSION, '8.8.0', '<')) {
  $config['system.file']['path']['temporary'] = '/tmp';
}
else {
  $settings['file_temp_path'] = '/tmp';
}

// Easily accessible local private file system.
// Any public and private path set before this file is kept in
// $ddev_drupal_settings_original, for settings files included after it.
// While this default will mostly work, complex projects or multisite setups
// will need to set their own paths after this file.
if (!empty($settings['file_public_path'])) {
  $ddev_drupal_settings_original['file_public_path'] = $settings['file_public_path'];
}
if (!empty($settings['file_private_path'])) {
  $ddev_drupal_settings_original['file_private_path'] = $settings['file_private_path'];
}
$settings['file_public_path'] = 'sites/default/files';
$settings['file_private_path'] = 'sites/default/files/private';

// This needs to be set by the project, in a settings file of its own.
// $config['stage_file_proxy.settings']['origin'] = 'https://www.example.com';

/**
 * Driven by options.
 */

if (!$ddev_drupal['preprocess']) {
  $config['system.performance']['css']['preprocess'] = FALSE;
  $config['system.performance']['js']['preprocess'] = FALSE;
}

if ($ddev_drupal['cache'] !== 'normal') {
  // 'none' discards everything, 'db_null' stores entries in the database but
  // never serves them, so you can inspect what would have been cached.
  $ddev_drupal_cache_backend = $ddev_drupal['cache'] === 'db_null' ? 'cache.backend.database.null' : 'cache.backend.null';
  $settings['cache']['bins']['render'] = $ddev_drupal_cache_backend;
  $settings['cache']['bins']['dynamic_page_cache'] = $ddev_drupal_cache_backend;
  $settings['cache']['bins']['page'] = $ddev_drupal_cache_backend;
  unset($ddev_drupal_cache_backend);
}

if ($ddev_drupal['twig_debug']) {
  // A service provider rather than a services YAML file, so that only these
  // keys of twig.config change. Drupal has read this global since 8.0, and
  // its installer registers its own provider the same way.
  $GLOBALS['conf']['container_service_providers']['DdevDrupalTwigDebug'] = \Ddev\Drupal\TwigDebugServiceProvider::class;
  // The cached container is keyed on container_yamls only, see this file.
  $settings['container_yamls'][] = '/var/www/html/.ddev/drupal/assets/services/twig-debug.services.yml';
}

if ($ddev_drupal['cacheability_headers']) {
  $settings['container_yamls'][] = '/var/www/html/.ddev/drupal/assets/services/cacheability-headers.services.yml';
}

if ($ddev_drupal['config_split']) {
  $config['config_split.config_split.' . $ddev_drupal['config_split']]['status'] = TRUE;
}

if ($ddev_drupal['stage_file_proxy']) {
  $config['stage_file_proxy.settings']['hotlink'] = TRUE;
  $config['stage_file_proxy.settings']['use_imagecache_root'] = FALSE;
}
