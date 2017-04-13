<?php

namespace Drupal\g9_admin_menu;

use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\DependencyInjection\ServiceProviderBase;

/**
 * ServiceModifier implementation.
 *
 * @package Drupal\g9_admin_menu
 */
class G9AdminMenuServiceProvider extends ServiceProviderBase {

  /**
   * {@inheritdoc}
   */
  public function alter(ContainerBuilder $container) {
    // Use our custom tree manipulator to check the menu tree items for access
    // based on the company a user belongs to and their user roles.
    $container->getDefinition('menu.default_tree_manipulators')
      ->setClass(AdminMenuLinkTreeManipulator::class);
  }

}
