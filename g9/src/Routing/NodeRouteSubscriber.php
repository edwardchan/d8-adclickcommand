<?php

namespace Drupal\g9\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;
use Drupal\Core\Routing\RoutingEvents;

/**
 * Listens to the dynamic route events.
 */
class NodeRouteSubscriber extends RouteSubscriberBase {

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
    // If the route being accessed is a node 'view' or node 'edit', set the
    // company access requirement.
    if (($route = $collection->get('entity.node.canonical')) || ($route = $collection->get('entity.node.edit_form'))) {
      $route->setRequirement('_company_access_check', '\Drupal\g9_admin_menu\Access\CompanyAccessCheck::access');
    }
  }

}
