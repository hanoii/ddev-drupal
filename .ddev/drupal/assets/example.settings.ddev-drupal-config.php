<?php

/**
 * #ddev-generated
 * Per-site options for hanoii/ddev-drupal's settings.ddev-drupal.php.
 *
 * Copy to sites/<site>/settings.ddev-drupal-config.php, next to settings.php,
 * and uncomment what you need. Any option left commented out takes its value
 * from the preset, so changing only 'preset' moves every other option with it.
 *
 * It holds personal settings, so add it to your .gitignore, for example:
 * /web/sites/<site>/settings.ddev-drupal-config.php
 *
 * Changes apply on the next request. Values shown are those of the dev preset.
 */

return [
  // Base values for every option below: 'dev', 'cache' or 'prod'.
  // 'preset' => 'dev',

  // CSS/JS aggregation.
  // 'preprocess' => FALSE,

  // Backend of the render, dynamic_page_cache and page bins:
  // - 'none': nothing is cached.
  // - 'db_null': entries are stored in the database but never served, to
  //   inspect what would have been cached.
  // - 'normal': caches work as in production.
  // 'cache' => 'none',

  // Twig debug, auto reload and no Twig cache.
  // 'twig_debug' => TRUE,

  // X-Drupal-Cache-Tags / X-Drupal-Cache-Contexts debug headers.
  // 'cacheability_headers' => FALSE,

  // Config split id forced enabled, or FALSE.
  // 'config_split' => 'development',

  // Stage File Proxy hotlinking.
  // 'stage_file_proxy' => TRUE,
];
