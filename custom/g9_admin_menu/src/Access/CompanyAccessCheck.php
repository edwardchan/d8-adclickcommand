<?php

namespace Drupal\g9_admin_menu\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\user\Entity\User;
use Drupal\taxonomy\Entity\Term;
use Drupal\Core\Routing\RouteMatchInterface;

/**
 * Determines access to routes based on a user's company.
 *
 * You can specify the '_companies' key on route requirements. If you specify
 * a company, only users whom belong to that company will have access.
 * If no companies are specified, all users will have access.
 *
 * Example of using this access check on routes:
 * @code
 * mymodule.configuration:
 *   path: '/admin/config/services/mymodule'
 *   defaults:
 *     _form: 'Drupal\mymodule\Form\ConfigForm'
 *     _title: 'My Module Form'
 *   requirements:
 *     _permission: 'access administration pages'
 *      _company_access_check: 'TRUE'
 *    options:
 *     _companies:
 *       - Thrillist Editorial
 * @endcode
 */
class CompanyAccessCheck implements AccessInterface {

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   Run access checks for the logged in user.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   Run access checks for the logged in user.
   * @param \Symfony\Component\Routing\Route $route
   *   Run access checks for the logged in user.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns a true or false if access is allowed or denied.
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match, Route $route) {
    // User 1 and all 'administrator' users should have access to everything.
    if ($account->id() == 1 || in_array('administrator', $account->getRoles())) {
      return AccessResult::allowed();
    }

    // An array storing all the companies that are allowed to have access.
    $allowed_companies = [];

    // If it is a node route, get the companies tagged in the node.
    if ($node = $route_match->getParameter('node')) {
      if ($node->hasField('field_brand') && $node->get('field_brand')->getValue()) {
        // Get all the 'company' term references from 'field_brand'.
        $node_companies = $node->get('field_brand')->referencedEntities();
        // Append each company to the array. Users for these companies are
        // allowed access.
        foreach ($node_companies as $company) {
          $allowed_companies[] = $company->getName();
        }
      }
    }
    // Otherwise, get the companies from the '_companies' key in the routes.
    else {
      $allowed_companies = $route->getOption('_companies');
    }

    // If there are no companies specified, everyone should be allowed.
    if (empty($allowed_companies)) {
      return AccessResult::allowed();
    }

    // Get the user id and load the user object to access the custom fields.
    $user_id = $account->id();
    $user = User::load($user_id);

    // If there is no company associated with the user and companies are
    // specified, deny access.
    if (empty($user->get('field_brand')->referencedEntities())) {
      return AccessResult::forbidden();
    }

    // Get the company field value and retrieve the target id of the term.
    $company_field = $user->get('field_brand')->getValue();
    $company_id = $user->get('field_brand')
      ->first()
      ->get('entity')
      ->getTarget()
      ->getValue()
      ->id();
    // Load the term and get the term's name to be used to check access.
    $company_term = Term::load($company_id);
    $company_name = $company_term->getName();

    // If the user's company is in the list of allowed companies, grant access.
    if (in_array($company_name, $allowed_companies)) {
      return AccessResult::allowed();
    }

    // Otherwise, deny access.
    return AccessResult::forbidden();
  }

}
