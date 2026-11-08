setup() {
  set -eu -o pipefail
  export DIR="$( cd "$( dirname "$BATS_TEST_FILENAME" )" >/dev/null 2>&1 && pwd )/.."
  export TESTDIR=~/tmp/test-ddev-drupal
  export PROJNAME=test-ddev-drupal
  export DDEV_NON_INTERACTIVE=true
  ddev delete -Oy ${PROJNAME} >/dev/null 2>&1 || true
  rm -rf ${TESTDIR}
  mkdir -p ${TESTDIR}

  # Install from a copy of what git would ship (tracked and new files), not
  # from the checkout: it may hold ignored local files, like a legacy
  # drupal/assets/settings.local.php.
  export ADDON_DIR="${TESTDIR}-addon"
  rm -rf ${ADDON_DIR}
  mkdir -p ${ADDON_DIR}
  git -C "${DIR}" ls-files -z --cached --others --exclude-standard | (cd "${DIR}" && xargs -0 tar -cf -) | tar -xf - -C "${ADDON_DIR}"

  cd "${TESTDIR}"

  # One site directory per scenario, written before 'ddev start' so they are
  # in the container from the start (no waiting on Mutagen).
  mkdir -p web/sites/default web/sites/cache web/sites/invalid web/sites/example
  # The shipped example, as copied by a user who only changes the preset.
  sed "s|// 'preset' => 'dev',|'preset' => 'cache',|" ${DIR}/drupal/assets/example.settings.ddev-drupal-config.php > web/sites/example/settings.ddev-drupal-config.php
  cat > web/sites/cache/settings.ddev-drupal-config.php <<'EOF'
<?php
return ['preset' => 'cache', 'twig_debug' => TRUE, 'cache' => 'db_null'];
EOF
  cat > web/sites/invalid/settings.ddev-drupal-config.php <<'EOF'
<?php
return ['cache' => 'maybe'];
EOF
  # Loads settings.ddev-drupal.php for a site with a stub Drupal and prints
  # what it resolved and set.
  cat > probe.php <<'EOF'
<?php
class Drupal { const VERSION = '11.0.0'; }
$app_root = '/var/www/html/web';
$site_path = 'sites/' . $argv[1];
$settings = $config = [];
// Same as a legacy settings.local.php setting it.
if (getenv('PROBE_LEGACY_CACHE_PRODUCTION') !== FALSE) {
  $ddev_drupal_cache_production = (bool) getenv('PROBE_LEGACY_CACHE_PRODUCTION');
}
require '/var/www/html/.ddev/drupal/assets/settings.ddev-drupal.php';
echo json_encode([
  'options' => $ddev_drupal,
  'yamls' => array_map('basename', $settings['container_yamls']),
  'providers' => array_keys($GLOBALS['conf']['container_service_providers'] ?? []),
  'bins' => $settings['cache']['bins'] ?? [],
], JSON_UNESCAPED_SLASHES), "\n";
EOF

  ddev config --project-name=${PROJNAME} --project-type=php --docroot=web
  ddev start -y >/dev/null
}

teardown() {
  set -eu -o pipefail
  cd ${TESTDIR} || ( printf "unable to cd to ${TESTDIR}\n" && exit 1 )
  ddev delete -Oy ${PROJNAME} >/dev/null 2>&1
  [ "${TESTDIR}" != "" ] && rm -rf ${TESTDIR} ${TESTDIR}-addon
}

@test "install from directory" {
  set -eu -o pipefail
  cd ${TESTDIR}
  echo "# ddev add-on get ${ADDON_DIR} with project ${PROJNAME} in ${TESTDIR} ($(pwd))" >&3
  out=$(ddev add-on get ${ADDON_DIR} 2>&1)
  echo "$out"
  echo "$out" | grep -F 'Installed drupal'
  # A clean project has nothing deprecated to report.
  run grep -F 'Deprecated:' <<< "$out"
  [ "$status" -eq 1 ]
  ddev restart >/dev/null

  ddev exec php -l .ddev/drupal/assets/settings.ddev-drupal.php
  ddev exec php -l .ddev/drupal/assets/example.settings.ddev-drupal-config.php

  # No config file: the dev preset.
  out=$(ddev exec php probe.php default)
  echo "$out"
  echo "$out" | grep -F '"preset":"dev"'
  echo "$out" | grep -F '"providers":["DdevDrupalTwigDebug"]'
  echo "$out" | grep -F '"render":"cache.backend.null"'

  # Overrides on top of a preset: caches stored but not served, twig debug on.
  out=$(ddev exec php probe.php cache)
  echo "$out"
  echo "$out" | grep -F '"preset":"cache"'
  echo "$out" | grep -F '"providers":["DdevDrupalTwigDebug"]'
  echo "$out" | grep -F '"render":"cache.backend.database.null"'

  # The example only with the preset changed: every option follows it.
  out=$(ddev exec php probe.php example)
  echo "$out"
  echo "$out" | grep -F '"preset":"cache"'
  echo "$out" | grep -F '"cache":"normal"'
  echo "$out" | grep -F '"bins":[]'

  # Invalid values stop with an error naming them.
  run ddev exec php probe.php invalid
  [ "$status" -ne 0 ]
  echo "$output" | grep -F "'cache' must be one of"

  # Legacy env vars are real booleans: 'false' is false.
  ddev exec env DDEV_DRUPAL_PRODUCTION=false php probe.php default | grep -F '"preset":"dev"'
  ddev exec env DDEV_DRUPAL_PRESET=prod php probe.php default | grep -F '"preset":"prod"'

  # A legacy variable only replaces its own env var.
  ddev exec env DDEV_DRUPAL_PRODUCTION=true PROBE_LEGACY_CACHE_PRODUCTION= php probe.php default | grep -F '"preset":"prod"'

  # Reinstalling over legacy usage warns about each one but still installs.
  cat > web/sites/default/settings.php <<'EOF'
<?php
require '/var/www/html/.ddev/drupal/assets/settings.dev.php';
$settings['container_yamls'][] = '/var/www/html/.ddev/drupal/assets/dev.services.yml';
$ddev_drupal_production = TRUE;
EOF
  touch .ddev/drupal/assets/settings.local.php
  printf 'web_environment:\n  - DDEV_DRUPAL_CACHE_PRODUCTION=true\n' > .ddev/config.legacy.yaml
  out=$(ddev add-on get ${ADDON_DIR} 2>&1)
  echo "$out"
  echo "$out" | grep -F 'Installed drupal'
  echo "$out" | grep -F 'Deprecated: .ddev/drupal/assets/settings.local.php'
  echo "$out" | grep -F 'Deprecated: web/sites/default/settings.php requires .ddev/drupal/assets/settings.dev.php'
  echo "$out" | grep -F 'Deprecated: web/sites/default/settings.php loads .ddev/drupal/assets/dev.services.yml'
  echo "$out" | grep -F 'Deprecated: web/sites/default/settings.php sets $ddev_drupal_production'
  echo "$out" | grep -F 'Deprecated: .ddev/config.legacy.yaml sets DDEV_DRUPAL_PRODUCTION'
}
