<?php

namespace Drupal\g9_foursquare;

/**
 * The interface for making queries to the Foursquare API.
 */
interface FoursquareQueryInterface {

  /**
   * Returns the endpoint.
   *
   * @return string
   *   The endpoint.
   */
  public function getEndpoint();

  /**
   * Sets the endpoint.
   *
   * @return string
   *   The endpoint string without the base url.
   */
  public function endpointPath();

  /**
   * Execute the query.
   *
   * @return bool
   *   Whether or not the query was executed successfully.
   */
  public function execute();

}
