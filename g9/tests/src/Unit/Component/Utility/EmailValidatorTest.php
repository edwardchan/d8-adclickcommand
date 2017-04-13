<?php

namespace Drupal\Tests\g9\Unit;

use Drupal\Tests\UnitTestCase;
use Drupal\g9\Component\Utility\EmailValidator;

/**
 * @coversDefaultClass \Drupal\g9\Component\Utility\EmailValidator
 *
 * @group custom
 * @group g9
 */
class EmailValidatorTest extends UnitTestCase {

  /**
   * @covers EmailValidator::isLooselyValid
   * @dataProvider providerIsLooselyValid
   */
  public function testIsLooselyValid($value, $expected) {
    $this->assertEquals($expected, EmailValidator::isLooselyValid($value));
  }

  /**
   * Data provider for testIsLooselyValid().
   *
   * @see testIsLooselyValid()
   *
   * @return array
   *   An array containing a test string and whether or not it's loosely valid.
   */
  public function providerIsLooselyValid() {
    $cases = [
      ['mpriscella@groupninemedia.com', TRUE],
      ['127.0.0.1', FALSE],
      ['test@192.168.0.1', TRUE],
    ];
    return $cases;
  }

  /**
   * @covers EmailValidator::isValid
   * @dataProvider providerIsValid
   */
  public function testIsValid($value, $expected) {
    $this->assertEquals($expected, EmailValidator::isValid($value));
  }

  /**
   * Data provider for testIsValid().
   *
   * @see testIsValid()
   *
   * @return array
   *   An array containing a test string and whether or not it's valid.
   */
  public function providerIsValid() {
    $cases = [
      ['mpriscella@thrillist.com', TRUE],
      ['plange+spam@groupninemedia.com', TRUE],
      ['jacinto.thrillist.com', FALSE],
      ['k.richards@childrens.columbia.edu', TRUE],
      ['lmon@test@thrillist.com', FALSE],
    ];
    return $cases;
  }

}
