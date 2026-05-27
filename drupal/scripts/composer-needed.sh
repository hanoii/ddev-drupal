#!/bin/bash
#ddev-generated
set -e -o pipefail

composer_root="${1:-}"
config_dir="${2:-config}"

composer_root="${composer_root%/}"
if [ "$composer_root" = "." ]; then
  composer_root=""
fi

if [ -n "$composer_root" ]; then
  composer_file="$composer_root/composer.json"
  config_path="$composer_root/$config_dir"
else
  composer_file="composer.json"
  config_path="$config_dir"
fi

if [ ! -f "$composer_file" ]; then
  echo "Missing composer file: $composer_file" >&2
  exit 1
fi

if [ ! -d "$config_path" ]; then
  echo "Missing config directory: $config_path" >&2
  exit 1
fi

active_require_modules=()
active_dev_modules=()
unused_require_modules=()
unused_dev_modules=()

while IFS=: read -r dependency_type module; do
  if grep -rFqlw --include="*.yml" -- "$module" "$config_path" 2>/dev/null; then
    if [ "$dependency_type" = "require-dev" ]; then
      active_dev_modules+=("$module")
    else
      active_require_modules+=("$module")
    fi
  else
    if [ "$dependency_type" = "require-dev" ]; then
      unused_dev_modules+=("$module")
    else
      unused_require_modules+=("$module")
    fi
  fi
done < <(
  php -r '
$composer_file = $argv[1];
$composer = json_decode(file_get_contents($composer_file), TRUE);
if (!is_array($composer)) {
  fwrite(STDERR, "Unable to parse composer JSON: $composer_file" . PHP_EOL);
  exit(1);
}
foreach (["require", "require-dev"] as $section) {
  foreach (array_keys($composer[$section] ?? []) as $package) {
    if (str_starts_with($package, "drupal/")) {
      $module = substr($package, strlen("drupal/"));
      if (!str_starts_with($module, "core")) {
        echo $section, ":", $module, PHP_EOL;
      }
    }
  }
}
' "$composer_file"
)

print_group() {
  local title="$1"
  local icon="$2"
  shift 2

  echo "$title"
  if [ "$#" -eq 0 ]; then
    echo "None"
    return
  fi

  for module in "$@"; do
    echo "$icon $module"
  done
}

print_group "Being used (require):" "✅" "${active_require_modules[@]}"
echo
print_group "Being used (require-dev):" "✅" "${active_dev_modules[@]}"
echo
print_group "Not being used (require):" "❌" "${unused_require_modules[@]}"
echo
print_group "Not being used (require-dev):" "❌" "${unused_dev_modules[@]}"

if [ "${#unused_require_modules[@]}" -gt 0 ] || [ "${#unused_dev_modules[@]}" -gt 0 ]; then
  echo
  echo "⚠️ Review carefully before running. Config grep can miss runtime-only dependencies."
  if [ "${#unused_require_modules[@]}" -gt 0 ]; then
    composer_remove_packages=()
    for module in "${unused_require_modules[@]}"; do
      composer_remove_packages+=("drupal/$module")
    done
    echo "🧹 composer remove ${composer_remove_packages[*]}"
  fi
  if [ "${#unused_dev_modules[@]}" -gt 0 ]; then
    composer_remove_dev_packages=()
    for module in "${unused_dev_modules[@]}"; do
      composer_remove_dev_packages+=("drupal/$module")
    done
    echo "🧹 composer remove --dev ${composer_remove_dev_packages[*]}"
  fi
fi
