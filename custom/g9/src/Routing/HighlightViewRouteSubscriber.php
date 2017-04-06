<?php

namespace Drupal\g9\Routing;

use Drupal\Core\Routing\RouteSubscriberBase;
use Symfony\Component\Routing\RouteCollection;

/**
 * Class RouteSubscriber.
 *
 * @package Drupal\g9\Routing
 * Listens to the dynamic route events.
 */
class HighlightViewRouteSubscriber extends RouteSubscriberBase {

  /**
   * {@inheritdoc}
   */
  protected function alterRoutes(RouteCollection $collection) {
    // The "highlight" view route we want to force to use administration theme.
    $admin_routes = ['view.highlight.page_2'];
    foreach ($collection->all() as $name => $route) {
      if (in_array($name, $admin_routes)) {
        // For the highlight view route, set the _admin_route to TRUE.
        $route->setOption('_admin_route', TRUE);
      }
    }
  }

}
