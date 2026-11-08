#!/bin/bash
#ddev-generated
set -e -o pipefail

project_type="$DDEV_PROJECT_TYPE"
if [[ "$project_type" != *"drupal"* ]]; then
  project_type="$DDEV_PIMP_MY_SHELL_PROJECT_TYPE"
fi

if [[ "$project_type" == *"drupal"* ]]; then
  if [[ "$project_type" == "drupal6" ]] || [[ "$project_type" == "drupal7" ]]; then
    gum log --level warn "$project_type is not supported by the default post-import-db script provided by the hanoii/ddev-drupal add-on."
    gum log --level warn "Please provide your own .ddev/pimp-my-shell/hooks/post-import-db.d script for what you need to do after 'db:pull'."
    exit 0
  fi

  if ! drush > /dev/null 2>&1; then
    gum log --level error "drush not found, if this is a fresh init/clone, maybe run 'composer install'"
    exit 1
  fi

  drush -y deploy

  /var/www/html/.ddev/drupal/scripts/drush-uli.sh
else
  gum log --level debug "Not drupal"
fi
