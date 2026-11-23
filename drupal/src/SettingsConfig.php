<?php
// #ddev-generated

namespace Ddev\Drupal;

/**
 * Resolves the options that drive settings.ddev-drupal.php.
 *
 * Every option has a value from a preset, which project defaults set in
 * $ddev_drupal_defaults and then a per-site settings.ddev-drupal-config.php
 * file can override key by key.
 *
 * Preset precedence, lowest first:
 * - The default preset (dev).
 * - 'preset' in $ddev_drupal_defaults.
 * - Legacy DDEV_DRUPAL_PRODUCTION / DDEV_DRUPAL_CACHE_PRODUCTION env vars.
 * - DDEV_DRUPAL_PRESET env var.
 * - Legacy $ddev_drupal_production / $ddev_drupal_cache_production variables.
 * - sites/<site>/settings.ddev-drupal-config.php.
 *
 * Other options, lowest first: the preset, $ddev_drupal_defaults, its
 * 'presets' entry for the resolved preset, settings.ddev-drupal-config.php.
 */
final class SettingsConfig {

  const FILENAME = 'settings.ddev-drupal-config.php';

  const LEGACY_FILE = '/var/www/html/.ddev/drupal/assets/settings.local.php';

  const DEFAULT_PRESET = 'dev';

  /**
   * Option types: 'bool', 'machine_name' or a list of allowed values.
   */
  const SCHEMA = [
    'preprocess' => 'bool',
    'cache' => ['none', 'db_null', 'normal'],
    'twig_debug' => 'bool',
    'cacheability_headers' => 'bool',
    'config_split' => ['enable', 'disable', 'ignore'],
    'config_split_id' => 'machine_name',
    'stage_file_proxy' => 'bool',
  ];

  const PRESETS = [
    'dev' => [
      'preprocess' => FALSE,
      'cache' => 'none',
      'twig_debug' => TRUE,
      'cacheability_headers' => FALSE,
      'config_split' => 'enable',
      'config_split_id' => 'development',
      'stage_file_proxy' => TRUE,
    ],
    'cache' => [
      'preprocess' => FALSE,
      'cache' => 'normal',
      'twig_debug' => FALSE,
      'cacheability_headers' => FALSE,
      'config_split' => 'enable',
      'config_split_id' => 'development',
      'stage_file_proxy' => TRUE,
    ],
    'prod' => [
      'preprocess' => TRUE,
      'cache' => 'normal',
      'twig_debug' => FALSE,
      'cacheability_headers' => FALSE,
      'config_split' => 'disable',
      'config_split_id' => 'development',
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
   * @param array $defaults
   *   Project defaults, the $ddev_drupal_defaults variable.
   *
   * @return array
   *   Option => value, including 'preset'.
   */
  public static function resolve(string $site_dir, ?bool $legacy_production = NULL, ?bool $legacy_cache_production = NULL, array $defaults = []): array {
    $preset_defaults = self::validatePresetDefaults($defaults['presets'] ?? []);
    $defaults = self::validateAll(array_diff_key($defaults, ['presets' => TRUE]), '$ddev_drupal_defaults');
    $preset = $defaults['preset'] ?? self::DEFAULT_PRESET;

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

    $options = array_diff_key(array_replace($defaults, $preset_defaults[$preset] ?? [], $overrides), ['preset' => TRUE]);
    return ['preset' => $preset] + array_replace(self::PRESETS[$preset], $options);
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
    return self::validateAll($data, $file);
  }

  /**
   * Validates a set of options, after mapping deprecated values.
   */
  public static function validateAll(array $data, string $origin): array {
    $data = self::upgradeLegacy($data);
    foreach ($data as $key => $value) {
      $data[$key] = self::validate($key, $value, $origin);
    }
    return $data;
  }

  /**
   * Validates $ddev_drupal_defaults['presets']: preset => options.
   */
  private static function validatePresetDefaults($presets): array {
    $origin = "\$ddev_drupal_defaults['presets']";
    if (!is_array($presets)) {
      throw new \InvalidArgumentException("$origin must be an array of preset => options.");
    }
    foreach ($presets as $preset => $options) {
      self::validate('preset', $preset, "$origin key");
      if (!is_array($options) || isset($options['preset'])) {
        throw new \InvalidArgumentException($origin . "['$preset'] must be an array of options, without 'preset'.");
      }
      $presets[$preset] = self::validateAll($options, $origin . "['$preset']");
    }
    return $presets;
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

    // 'machine_name'.
    if (is_string($value) && preg_match('/^[a-z0-9_]+$/', $value)) {
      return $value;
    }
    throw new \InvalidArgumentException("$origin: '$key' must be a machine name.");
  }

  /**
   * Maps deprecated option values to the current ones.
   *
   * 'config_split' used to hold the split id to enable, or FALSE to leave
   * every split alone.
   */
  private static function upgradeLegacy(array $data): array {
    if (!array_key_exists('config_split', $data) || in_array($data['config_split'], self::SCHEMA['config_split'], TRUE)) {
      return $data;
    }
    if ($data['config_split'] === FALSE) {
      $data['config_split'] = 'ignore';
    }
    elseif (is_string($data['config_split'])) {
      $data += ['config_split_id' => $data['config_split']];
      $data['config_split'] = 'enable';
    }
    return $data;
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
