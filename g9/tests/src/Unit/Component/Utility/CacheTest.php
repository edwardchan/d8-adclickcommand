<?php

namespace Drupal\Tests\g9\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\g9\Component\Utility\Cache;
use Drupal\Core\State\State;
use Drupal\Core\KeyValueStore\KeyValueMemoryFactory;

/**
 * @coversDefaultClass \Drupal\g9\Component\Utility\Cache
 *
 * @group custom
 * @group g9
 */
class CacheTest extends UnitTestCase {

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $container = new ContainerBuilder();
    $state = new State(new KeyValueMemoryFactory());
    $container->set('state', $state);
    \Drupal::setContainer($container);
  }

  /**
   * @covers Cache::getCacheBuster
   * @dataProvider providerGetCacheBuster
   */
  public function testGetCacheBuster($value, $expected) {
    \Drupal::state()->set('system.css_js_query_string', base_convert($value, 10, 36));
    $this->assertEquals($expected, Cache::getCacheBuster());
  }

  /**
   * Data provider for testGetCacheBuster().
   *
   * @see testGetCacheBuster()
   *
   * @return array
   *   An array containing a timestamp and it's cachebuster version.
   */
  public function providerGetCacheBuster() {
    $cases = [
      [1487885328, 'o'],
      [647654400, 'a'],
    ];
    return $cases;
  }

}
