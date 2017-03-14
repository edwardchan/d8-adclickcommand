<?php

namespace Drupal\adclickcommand\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Entity\Query\QueryFactory;

/**
 * Returns responses for PuSH module routes.
 */
class AdClickCommandController extends ControllerBase implements ContainerInjectionInterface {

  /**
   * The key value expirable factory.
   *
   * @var \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface
   */
  protected $keyValueExpireFactory;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $entityQuery;

  /**
   * Constructs a AdClickCommandController object.
   *
   * @param \Drupal\Core\KeyValueStore\KeyValueExpirableFactoryInterface $key_value_expire_factory
   *   The key value expirable factory.
   */
  public function __construct(KeyValueExpirableFactoryInterface $key_value_expire_factory, EntityTypeManagerInterface $entity_type_manager, QueryFactory $entity_query) {
    $this->keyValueExpireFactory = $key_value_expire_factory;
    $this->entityTypeManager = $entity_type_manager;
    $this->entityQuery = $entity_query;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('keyvalue.expirable'),
      $container->get('entity_type.manager'),
      $container->get('entity.query')
    );
  }

  /**
   *
   */
  public static function commandList() {
    return $this->entityQuery('click_command_clicks')
      ->condition('ccid', $id)
      ->execute()
      ->fetchAll();
  }

}
