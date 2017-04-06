<?php

namespace Drupal\g9\Component\Utility;

/**
 * Class ArrayHelper.
 *
 * @package Drupal\g9\Component\Utility
 */
class ArrayHelper {

  /**
   * Retrieves an unknown array value without generating a notice.
   *
   * @param array $array
   *   An array.
   * @param mixed $key
   *   A string or integer key to check for.
   * @param mixed $default
   *   If the key does not exist, return this value.
   *
   * @return mixed
   *   The array value.
   */
  public static function get(array $array, $key, $default = NULL) {
    return (is_array($array) && array_key_exists($key, $array)) ? $array[$key] : $default;
  }

  /**
   * Sorts an array of arrays/ objects based on the value of a specified field.
   *
   * Example:
   * $a = [
   *   ['name' => 'foo', 'weight' => 2],
   *   ['name' => 'bar', 'weight' => 1],
   * ];
   * $sorted_by_weight = array_field_sort($a, 'weight');
   *
   * @param array $array
   *   An array.
   * @param string $field
   *   A field name.
   * @param callable $callback
   *   Name of the function to use for sorting.
   *
   * @return array
   *   A sorted copy of the array.
   */
  public static function fieldSort(array $array, $field, callable $callback = NULL) {
    $fields = [];
    foreach ($array as $key => $value) {
      $fields[$key] = is_object($value) ? $value->$field : $value[$field];
    }

    if ($callback) {
      uasort($fields, $callback);
    }
    else {
      asort($fields);
    }

    $result = [];
    foreach ($fields as $key => $field) {
      $result[$key] = $array[$key];
    }
    return $result;
  }

  /**
   * Retrieves an element from an array of arrays/ objects based off a field.
   *
   * @param array $array
   *   An array of arrays.
   * @param string $field
   *   A key in the sub-arrays.
   * @param mixed $value
   *   The value to find.
   * @param string $return_field
   *   If not null return only this field. Otherwise return the whole sub-array.
   *
   * @return mixed
   *   The requested array or array element.
   */
  public static function fieldFind(array $array, $field, $value, $return_field = NULL) {
    if (!is_array($array)) {
      return FALSE;
    }

    foreach ($array as $key => $item) {
      $item_field = is_object($item) ? $item->field : $item[$field];
      if ($item_field == $value) {
        return ($return_field ? (is_object($item) ? $item->$return_field : $item[$return_field]) : $item);
      }
    }
  }

  /**
   * Retrieve all elements in an array of arrays/objects.
   *
   * @param array $array
   *   An array of arrays or objects.
   * @param string $field
   *   A key in the sub-arrays.
   * @param mixed $value
   *   The value to search for in.
   * @param string $return_field
   *   If not null, return only this field of the array. Otherwise all.
   *
   * @return array
   *   A subset of the array.
   */
  public static function fieldSearch(array $array, $field, $value, $return_field = NULL) {
    if (!is_array($array)) {
      return FALSE;
    }

    $found = [];
    foreach ($array as $key => $item) {
      $item_field = is_object($item) ? $item->$field : $item[$field];
      if ($item_field == $value) {
        $found[$key] = ($return_field ? (is_object($item) ? $item->$return_field : $item[$return_field]) : $item);
      }
    }
    return $found;
  }

  /**
   * Reduce each element of an array of arrays to the value of a subkey.
   *
   * @param array $array
   *   An array.
   * @param string $field
   *   The name of the field.
   *
   * @return array
   *   An array with the same keys as the source array.
   */
  public static function prune(array $array, $field) {
    $pruned = [];
    foreach ($array as $key => $value) {
      if (is_array($value)) {
        $pruned[$key] = $value[$field];
      }
      elseif (is_object($value)) {
        $pruned[$key] = $value->$field;
      }
    }
    return $pruned;
  }

  /**
   * Reduce a two dimensional array to a single dimensional array.
   *
   * This is likely the version you want when you want to flatten a nested
   * array.
   *
   * Via https://stackoverflow.com/questions/1319903/how-to-flatten-a-multidimensional-array#1320156
   *
   * @param array $array
   *   Nested array.
   *
   * @return array
   *   Flattened array.
   */
  public static function flatten(array $array) {
    $return = [];
    array_walk_recursive($array, function ($a) use (&$return) {
      $return[] = $a;
    });
    return $return;
  }

  /**
   * Apply Drupal's global tablesort parameters to an array of records.
   *
   * Drupal provides tablesort_sql() to add a sort clause to SQL queries. This
   * function should be used to sort data records that are acquired from
   * another source or must be processed before sorting.
   *
   * @param array $array
   *   An array of associative arrays representing records to sort.
   * @param array $header
   *   An array of header fields as used by theme_table().
   *
   * @return array
   *   The array sorted according to the tablesort query parameters.
   */
  public static function tableSort(array $array, array $header) {
    $tablesort = tablesort_init($header);
    if ($tablesort['sql']) {
      $sorted = ArrayHelper::fieldSort($array, $tablesort['sql']);
      if (strtolower($tablesort['sort']) === 'desc') {
        $sorted = array_reverse($sorted);
      }
      return $sorted;
    }
    return $array;
  }

}
