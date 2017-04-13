<?php

namespace Drupal\g9_foursquare;

use FoursquareApi as FoursquareApiLib;

/**
 * The Foursquare API class.
 *
 * This class contains methods for accessing the API.
 */
class FoursquareApi extends FoursquareApiLib {

  /**
   * {@inheritdoc}
   */
  public function getPublic($endpoint, $params = FALSE) {
    return parent::GetPublic($endpoint, $params);
  }

  /**
   * {@inheritdoc}
   */
  public function getPrivate($endpoint, $params = FALSE, $post = FALSE) {
    return parent::GetPrivate($endpoint, $params, $post);
  }

  /**
   * {@inheritdoc}
   */
  public function getMulti($requests = FALSE, $post = FALSE) {
    return parent::GetMulti($requests, $post);
  }

  /**
   * {@inheritdoc}
   */
  public function geoLocate($address) {
    return parent::GeoLocate($address);
  }

  /**
   * {@inheritdoc}
   */
  public function setAccessToken($token) {
    parent::SetAccessToken($token);
  }

  /**
   * {@inheritdoc}
   */
  private function request($url, $params = FALSE, $type = HTTP_GET) {
    return parent::Request($url, $params, $type);
  }

}
