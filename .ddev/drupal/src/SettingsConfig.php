<?php
// #ddev-generated

namespace Ddev\Drupal;

/**
 * Resolves the options that drive settings.ddev-drupal.php.
 *
 * Every option has a value from a preset, which a per-site
 * settings.ddev-drupal-config.php file can override key by key.
 *
 * Precedence, lowest first:
 * - The default preset (dev).
 * - Legacy DDEV_DRUPAL_PRODUCTION / DDEV_DRUPAL_CACHE_PRODUCTION env vars.
 * - DDEV_DRUPAL_PRESET env var.
 * - Legacy $ddev_drupal_production / $ddev_drupal_cache_production variables.
 * - sites/<site>/settings.ddev-drupal-config.php.
 */
final class SettingsConfig {

  const FILENAME = 'settings.ddev-drupal-config.php';

  const LEGACY_FILE = '/var/www/html/.ddev/drupal/assets/settings.local.php';

  const DEFAULT_PRESET = 'dev';

  /**
   * Option types: 'bool', 'string|false' or a list of allowed values.
   */
  const SCHEMA = [
    'preprocess' => 'bool',
    'cache' => ['none', 'db_null', 'normal'],
    'twig_debug' => 'bool',
    'cacheability_headers' => 'bool',
    'config_split' => 'string|false',
    'stage_file_proxy' => 'bool',
  ];

  const PRESETS = [
    'dev' => [
      'preprocess' => FALSE,
      'cache' => 'none',
      'twig_debug' => TRUE,
      'cacheability_headers' => FALSE,
      'config_split' => 'development',
      'stage_file_proxy' => TRUE,
    ],
    'cache' => [
      'preprocess' => FALSE,
      'cache' => 'normal',
      'twig_debug' => FALSE,
      'cacheability_headers' => FALSE,
      'config_split' => 'development',
      'stage_file_proxy' => TRUE,
    ],
    'prod' => [
      'preprocess' => TRUE,
      'cache' => 'normal',
      'twig_debug' => FALSE,
      'cacheability_headers' => FALSE,
      'config_split' => FALSE,
      'stage_file_proxy' => FALSE,
    ],
  ];

  /**
   * Resolves every option for a site.
   *
   * @param string $site_dir
   *   Absolute path of the site directory, e.g. /var/www/html/web/sites/default.
   * @param bool|null $legacy_production
   *   The legacy $ddev_drupal_production variable, if set.
   * @param bool|null $legacy_cache_production
   *   The legacy $ddev_drupal_cache_production variable, if set.
   *
   * @return array
   *   Option => value, including 'preset'.
   */
  public static function resolve(string $site_dir, ?bool $legacy_production = NULL, ?bool $legacy_cache_production = NULL): array {
    $preset = self::DEFAULT_PRESET;

    $env_production = self::envBool('DDEV_DRUPAL_PRODUCTION');
    $env_cache_production = self::envBool('DDEV_DRUPAL_CACHE_PRODUCTION');
    if ($env_production || $env_cache_production) {
      $preset = self::legacyPreset($env_production, $env_cache_production);
    }

    $env_preset = getenv('DDEV_DRUPAL_PRESET');
    if ($env_preset !== FALSE && $env_preset !== '') {
      $preset = self::validate('preset', $env_preset, 'DDEV_DRUPAL_PRESET');
    }

    // As before, each legacy variable only replaces its own env var.
    if ($legacy_production !== NULL || $legacy_cache_production !== NULL) {
      $preset = self::legacyPreset($legacy_production ?? $env_production, $legacy_cache_production ?? $env_cache_production);
    }

    $overrides = self::readFile(rtrim($site_dir, '/') . '/' . self::FILENAME);
    $preset = $overrides['preset'] ?? $preset;

    return ['preset' => $preset] + array_replace(self::PRESETS[$preset], array_diff_key($overrides, ['preset' => TRUE]));
  }

  /**
   * Reads and validates a config file. A missing file means no overrides.
   */
  public static function readFile(string $file): array {
    if (!is_file($file)) {
      return [];
    }
    $data = (static function () use ($file) {
      return include $file;
    })();
    if (!is_array($data)) {
      throw new \UnexpectedValueException("$file must return an array.");
    }
    foreach ($data as $key => $value) {
      $data[$key] = self::validate($key, $value, $file);
    }
    return $data;
  }

  /**
   * Validates an option value.
   *
   * @throws \InvalidArgumentException
   */
  public static function validate(string $key, $value, string $origin) {
    if ($key === 'preset') {
      $type = array_keys(self::PRESETS);
    }
    elseif (isset(self::SCHEMA[$key])) {
      $type = self::SCHEMA[$key];
    }
    else {
      throw new \InvalidArgumentException("$origin: unknown option '$key'. Known: preset, " . implode(', ', array_keys(self::SCHEMA)) . '.');
    }

    if (is_array($type)) {
      if (in_array($value, $type, TRUE)) {
        return $value;
      }
      throw new \InvalidArgumentException("$origin: '$key' must be one of: " . implode(', ', $type) . '.');
    }

    if ($type === 'bool') {
      if (is_bool($value)) {
        return $value;
      }
      throw new \InvalidArgumentException("$origin: '$key' must be TRUE or FALSE.");
    }

    // 'string|false'.
    if ($value === FALSE || (is_string($value) && preg_match('/^[a-z0-9_]+$/', $value))) {
      return $value;
    }
    throw new \InvalidArgumentException("$origin: '$key' must be a machine name or FALSE.");
  }

  /**
   * Maps the legacy production / cache production flags to a preset.
   */
  private static function legacyPreset(bool $production, bool $cache_production): string {
    return $production ? 'prod' : ($cache_production ? 'cache' : 'dev');
  }

  /**
   * Reads a boolean environment variable; 'false', '0', 'no' are FALSE.
   */
  public static function envBool(string $name): bool {
    $value = getenv($name);
    return $value !== FALSE && filter_var($value, FILTER_VALIDATE_BOOLEAN);
  }

}
