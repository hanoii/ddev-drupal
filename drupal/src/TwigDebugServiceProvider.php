<?php
// #ddev-generated

namespace Ddev\Drupal;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceModifierInterface;

/**
 * Turns on Twig debug, auto reload and no Twig cache.
 *
 * Registered by settings.ddev-drupal.php when the 'twig_debug' option is on.
 * Unlike a services YAML file, which replaces the whole twig.config parameter,
 * this only changes these three keys and keeps the rest of what core (or the
 * site's services.yml) defines there, such as use_yield and
 * allowed_file_extensions.
 */
class TwigDebugServiceProvider implements ServiceModifierInterface {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    $twig_config = $container->hasParameter('twig.config') ? $container->getParameter('twig.config') : [];
    $container->setParameter('twig.config', array_replace($twig_config, [
      'debug' => TRUE,
      'auto_reload' => TRUE,
      'cache' => FALSE,
    ]));
  }

}
