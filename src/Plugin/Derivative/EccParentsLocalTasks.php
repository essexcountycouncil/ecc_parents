<?php

namespace Drupal\ecc_parents\Plugin\Derivative;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\views\ViewExecutable;
use Drupal\views\Views;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generates local tasks.
 */
class EccParentsLocalTasks extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static();
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition): array {
    $this->derivatives = [];

    $view = Views::getView('parents');
    if ($view instanceof ViewExecutable) {
      $this->derivatives['ecc_parents.parents'] = $base_plugin_definition;
      $this->derivatives['ecc_parents.parents']['route_name'] = 'view.parents.page_1';
      $this->derivatives['ecc_parents.parents']['base_route'] = 'system.admin_content';
      $this->derivatives['ecc_parents.parents']['title'] = $this->t('Parents');
    }
    return $this->derivatives;
  }

}
