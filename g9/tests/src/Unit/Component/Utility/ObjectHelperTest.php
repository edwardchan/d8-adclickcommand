<?php

namespace Drupal\Tests\g9\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\g9\Component\Utility\ObjectHelper;

/**
 * @coversDefaultClass \Drupal\g9\Component\Utility\ObjectHelper
 * @group custom
 * @group g9
 */
class ObjectHelperTest extends UnitTestCase {

  /**
   * @covers ObjectHelper::get
   * @dataProvider providerGet
   */
  public function testGet($object, $key, $expected, $default) {
    $this->assertEquals($expected, ObjectHelper::get($object, $key, $default));
  }

  /**
   * Data provider for testGet().
   *
   * @see testGet()
   *
   * @return array
   *   An array containing a test object, a key, a default, and the expected
   *   value.
   */
  public function providerGet() {
    $object = (object) [
      'foo' => 'moo',
      'bar' => 'car',
    ];
    $cases = [
      [$object, 'foo', 'moo', 'foobar'],
      [$object, 'bar', 'car', 'foobar'],
      [$object, 'test', 'foobar', 'foobar'],
    ];
    return $cases;
  }

}
