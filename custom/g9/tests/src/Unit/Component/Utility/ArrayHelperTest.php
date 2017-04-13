<?php

namespace Drupal\Tests\g9\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\g9\Component\Utility\ArrayHelper;

/**
 * @coversDefaultClass \Drupal\g9\Component\Utility\ArrayHelper
 * @group custom
 * @group g9
 */
class ArrayHelperTest extends UnitTestCase {

  /**
   * @covers ArrayHelper::get
   * @dataProvider providerGet
   */
  public function testGet($array, $key, $default, $expected) {
    $this->assertEquals($expected, ArrayHelper::get($array, $key, $default));
  }

  /**
   * Data provider for testGet().
   *
   * @see testGet()
   *
   * @return array
   *   An array containing an array, a key name, a default value, and the
   *   expected value of that array key value.
   */
  public function providerGet() {
    $array = [
      'foo' => 'moo',
      'bar' => 'car',
    ];
    $cases = [
      [$array, 'bar', 'foobar', 'car'],
      [$array, 'foo', 'foobar', 'moo'],
      [$array, 'test', 'foobar', 'foobar'],
    ];
    return $cases;
  }

  /**
   * @covers ArrayHelper::fieldSort
   * @dataProvider providerFieldSort
   */
  public function testFieldSort($array, $field, $callback, $expected) {
    $this->assertArrayEquals($expected, ArrayHelper::fieldSort($array, $field, $callback));
  }

  /**
   * Data provider for testFieldSort().
   *
   * @see testFieldSort()
   *
   * @return array
   *   An array containing an array, a field name, an optional callback name,
   *   and the expected value.
   */
  public function providerFieldSort() {
    $cases = [
      [
        [
          ['name' => 'foo', 'weight' => 2],
          ['name' => 'bar', 'weight' => 0],
        ],
        'weight',
        NULL,
        [
          ['name' => 'bar', 'weight' => 0],
          ['name' => 'foo', 'weight' => 2],
        ],
      ],
    ];
    return $cases;
  }

  /**
   * @covers ArrayHelper::fieldFind
   * @dataProvider providerFieldFind
   */
  public function testFieldFind($array, $field, $value, $return_field, $expected) {

  }

  /**
   * Data provider for testFieldFind().
   *
   * @see testFieldFind()
   *
   * @returns array
   *   An array containing an array, a field name, a search value, a return
   *   field, and the expected value.
   */
  public function providerFieldFind() {
    $cases = [];
    return $cases;
  }

  /**
   * @covers ArrayHelper::fieldSearch
   * @dataProvider providerFieldSearch
   */
  public function testFieldSearch($array, $field, $value, $return_field, $expected) {
    $this->assertArrayEquals($expected, ArrayHelper::fieldSearch($array, $field, $value, $return_field));
  }

  /**
   * Data provider for testFieldSearch().
   *
   * @see testFieldSearch()
   *
   * @return array
   *   An array containing an array, a field name, a search value, an optional
   *   return field, and the expected value.
   */
  public function providerFieldSearch() {
    $cases = [];
    return $cases;
  }

  /**
   * @covers ArrayHelper::prune
   * @dataProvider providerPrune
   */
  public function testPrune($array, $field, $expected) {
    $this->assertArrayEquals($expected, ArrayHelper::prune($array, $field));
  }

  /**
   * Data provider for testPrune().
   *
   * @see testPrune()
   *
   * @return array
   *   An array containing an array, a field name, and the expected value.
   */
  public function providerPrune() {
    $cases = [];
    return $cases;
  }

  /**
   * @covers ArrayHelper::flatten
   * @dataProvider providerFlatten
   */
  public function testFlatten($array, $expected) {
    $this->assertArrayEquals($expected, ArrayHelper::flatten($array));
  }

  /**
   * Data provider for testFlatten().
   *
   * @see testFlatten()
   *
   * @return array
   *   An array containing an array and an expected value.
   */
  public function providerFlatten() {
    $cases = [];
    return $cases;
  }

  /**
   * @covers ArrayHelper::tableSort
   * @dataProvider providerTableSort
   */
  public function testTableSort($array, $header, $expected) {
    $this->assertArrayEquals($expected, ArrayHelper::tableSort($array, $header));
  }

  /**
   * Data provider for testTableSort().
   *
   * @see testTableSort()
   *
   * @return array
   *   An array containing an array, a header array, and the expected value.
   */
  public function providerTableSort() {
    $cases = [];
    return $cases;
  }

}
