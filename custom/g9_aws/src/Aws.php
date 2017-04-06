<?php

namespace Drupal\g9_aws;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\algolia\Algolia;
use Drupal\Core\Config\ConfigFactoryInterface;

/**
 * Class Aws.
 *
 * @package Drupal\g9_aws
 */
class Aws implements ContainerInjectionInterface {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $algoliaConfig;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\Config
   */
  protected $awsConfig;

  /**
   * The configuration factory.
   *
   * @var \Drupal\algolia\Algolia
   */
  protected $algolia;

  /**
   * Constructs a new Aws instance.
   */
  public function __construct(
    ConfigFactoryInterface $config_factory,
    Algolia $algolia) {
    $this->algoliaConfig = $config_factory->get('algolia.settings');
    $this->awsConfig = $config_factory->get('g9_aws.settings');

    $this->algolia = $algolia;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('algolia')
    );
  }

  /**
   * Perform a node operation from an event hook.
   *
   * @param mixed $node
   *   Node object or ID.
   * @param string $op
   *   Operation.
   * @param string $type
   *   Node type.
   */
  public function performNodeOperation($node, $op, $type) {
    $this->algolia->algoliaClient();
    $this->algolia->push($node, $op, $type);
  }

}
