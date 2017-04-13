<?php

namespace Drupal\g9\Component\Utility;

/**
 * Class ObjectHelper.
 *
 * @package Drupal\g9\Component\Utility
 */
class ObjectHelper {

  /**
   * Retrieve a possibly unset property value without generating a notice.
   *
   * @param object $object
   *   An object.
   * @param string $key
   *   The name of the property to get.
   * @param mixed $default
   *   The default return value.
   *
   * @return mixed
   *   The value of $obj->$key, or $default if $key is not set.
   */
  public static function get($object, $key, $default = NULL) {
    return (is_object($object) && property_exists($object, $key)) ? $object->$key : $default;
  }

}
