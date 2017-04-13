<?php

namespace Drupal\g9\Component\Utility;

/**
 * Class Cache.
 *
 * @package Drupal\g9\Component\Utility
 */
class Cache {

  /**
   * Gets the current Drupal cachebuster string.
   *
   * @return string
   *   A one-character string that changes when the site cache is flushed, to
   *   force browsers to reload altered CSS or JS.
   *
   * @see _drupal_flush_css_js()
   */
  public static function getCacheBuster() {
    return substr(\Drupal::state()->get('system.css_js_query_string', '0'), 0, 1);
  }

}
