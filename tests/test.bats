setup() {
  set -eu -o pipefail
  export DIR="$( cd "$( dirname "$BATS_TEST_FILENAME" )" >/dev/null 2>&1 && pwd )/.."
  export TESTDIR=~/tmp/test-ddev-drupal
  export PROJNAME=test-ddev-drupal
  export DDEV_NON_INTERACTIVE=true
  ddev delete -Oy ${PROJNAME} >/dev/null 2>&1 || true
  rm -rf ${TESTDIR}
  mkdir -p ${TESTDIR}
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
  [ "${TESTDIR}" != "" ] && rm -rf ${TESTDIR}
}

@test "install from directory" {
  set -eu -o pipefail
  cd ${TESTDIR}
  echo "# ddev add-on get ${DIR} with project ${PROJNAME} in ${TESTDIR} ($(pwd))" >&3
  ddev add-on get ${DIR}
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
}
