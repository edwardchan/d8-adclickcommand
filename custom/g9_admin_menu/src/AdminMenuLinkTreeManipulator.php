<?php

namespace Drupal\g9_admin_menu;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Menu\DefaultMenuLinkTreeManipulators;
use Drupal\Core\Menu\MenuLinkInterface;
use Drupal\menu_link_content\Plugin\Menu\MenuLinkContent;
use Drupal\user\Entity\User;

/**
 * Constructs a custom menu link tree manipulator to check user access.
 *
 * @package Drupal\g9_admin_menu
 */
class AdminMenuLinkTreeManipulator extends DefaultMenuLinkTreeManipulators {

  /**
   * {@inheritdoc}
   */
  protected function menuLinkCheckAccess(MenuLinkInterface $instance) {
    $result = parent::menuLinkCheckAccess($instance);
    // Get the current user's id.
    $user_id = $this->account->id();
    // Super admin user 1 should have all access.
    if ($user_id == 1) {
      return $result;
    }

    if ($instance instanceof MenuLinkContent) {
      $function = function () {
        return $this->getEntity();
      };
      $function = \Closure::bind($function, $instance, get_class($instance));
      /** @var \Drupal\menu_link_content\Entity\MenuLinkContent $entity */
      $entity = $function();
      // Get the allowed companies values on the menu entity.
      $allowed_companies = $entity->g9_admin_menu__brand_allow->getValue();
      $allowed_companies = array_column($allowed_companies, 'target_id');

      // Get the allowed roles values on the menu entity.
      $allowed_roles = $entity->g9_admin_menu__show_role->getValue();
      $allowed_roles = array_column($allowed_roles, 'target_id');

      // Load the current user object to access our extra fields.
      $user = User::load($user_id);
      if (!empty($allowed_companies) && empty($user->get('field_brand')->getValue())) {
        return AccessResult::forbidden();
      }
      // Get the user's company id.
      if ($user->get('field_brand')->getValue()) {
        $company_ids = array_column($user->get('field_brand')->getValue(), 'target_id');

        // If the user's company is not an allowed company, access is forbidden.
        // If no companies are checked, access is allowed for all companies.
        if (!empty($allowed_companies) && count(array_intersect($company_ids, $allowed_companies)) == 0) {
          return AccessResult::forbidden();
        }
      }

      // If no roles are checked, all roles will have access. For the checked
      // roles, get the current user's roles and see if they belong to any
      // of the allowed roles. If not, access is forbidden.
      if (!empty($allowed_roles)) {
        $user_roles = $this->account->getRoles();
        if (count(array_intersect($allowed_roles, $user_roles)) == 0) {
          return AccessResult::forbidden();
        }
      }
    }

    // If we can to this point, access is allowed.
    return $result;
  }

}
