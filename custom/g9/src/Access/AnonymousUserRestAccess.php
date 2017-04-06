<?php

namespace Drupal\g9\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\HttpFoundation\Request;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\jwt\Authentication\Provider\JwtAuth;

/**
 * Restricts access for anonymous users to any REST resources.
 */
class AnonymousUserRestAccess implements AccessInterface, ContainerInjectionInterface {

  /**
   * The JWT authentication provider.
   *
   * @var \Drupal\jwt\Authentication\Provider\JwtAuth
   */
  protected $jwtAuth;

  /**
   * AnonymousUserRestAccess constructor.
   *
   * @param \Drupal\jwt\Authentication\Provider\JwtAuth $jwt_auth
   *   The JWT authentication provider.
   */
  public function __construct(JwtAuth $jwt_auth) {
    $this->jwtAuth = $jwt_auth;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('jwt.authentication.jwt')
    );
  }

  /**
   * A custom access check.
   *
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The active account.
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match object.
   * @param \Symfony\Component\Routing\Route $route
   *   The route object.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   *
   * @return \Drupal\Core\Access\AccessResult
   *   Returns a true or false if access is allowed or denied.
   */
  public function access(AccountInterface $account, RouteMatchInterface $route_match, Route $route, Request $request) {
    // User 1 and all 'administrator' users should have access to everything.
    if ($account->id() == 1 || in_array('administrator', $account->getRoles())) {
      return AccessResult::allowed();
    }

    $is_valid = "";
    // Check to see if the JWT in the request is valid. If a JWT is present,
    // it will try to validate it. Otherwise, if there is no JWT, it is invalid.
    if ($request->headers->get('Authorization')) {
      $is_valid = $this->jwtAuth->authenticate($request);
    }

    // If the request is for a REST resource, but the request is invalid,
    // deny access.
    if ($request->get('_rest_resource_config') && $account->isAnonymous() && !$is_valid) {
      return AccessResult::forbidden();
    }

    // If the account does not have the appropriate permission, deny access.
    if (!$account->hasPermission('access rest endpoints')) {
      return AccessResult::forbidden();
    }

    // Otherwise, grant access.
    return AccessResult::allowed();
  }

}
