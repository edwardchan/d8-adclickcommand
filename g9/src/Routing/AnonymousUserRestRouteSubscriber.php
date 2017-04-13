<?php

namespace Drupal\g9\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Restricts anonymous users from accessing REST routes.
 */
class AnonymousUserRestRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    // Set this to a low priority because we want Drupal access checks to
    // come first. If a user is restricted because of defined user permissions,
    // then our custom check should not be checked. This prevents users without
    // the appropriate permissions (i.e., editing an 'article' node) from
    // accessing the node page. If this was the default priority, this check
    // would possibly come before all the other checks - and would result in
    // access allowed if that the node has the user's company tagged (even
    // though they do not have permission to edit it).
    $events[RoutingEvents::ALTER] = ['onAlterRoutes', -999];
    return $events;
  }

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // All the REST methods which will be used to check the route name for.
    $rest_methods = ['GET', 'POST', 'DELETE', 'PATCH', 'PUT'];
    foreach ($collection as $name => $route) {
      // Break the route name into tokens delimited by a '.'.
      $route_name_tokens = explode('.', $name);
      // Check to see if any part of the route has any of the REST methods in
      // it. If so, add a requirement to the route so that anonymous users
      // cannot access it.
      if (array_intersect($route_name_tokens, $rest_methods)) {
        $route->setRequirement('_anonymous_user_rest_access', 'Drupal\g9\Access\AnonymousUserRestAccess::access');
      }
    }
  }

}
