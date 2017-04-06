<?php

namespace Drupal\g9\EntityResolver;

use Drupal\Core\Entity\EntityManagerInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Drupal\serialization\EntityResolver\EntityResolverInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Resolves entities from data that contains an entity UUID.
 */
class TaxonomyMetaResolver implements EntityResolverInterface {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * Constructs a TaxonomyMetaResolver object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactory $config_factory) {
    $this->entityManager = $entity_manager;
    $this->config = $config_factory->get("g9.taxonomy_term.mapping");
  }

  /**
   * {@inheritdoc}
   */
  public function resolve(NormalizerInterface $normalizer, $data, $entity_type) {
    // Get the label overrides defined in the config.
    $label_overrides = $this->config->get('label_overrides');
    // Get the uuid of the entity to be used to load it.
    if ($uuid = $normalizer->getUuid($data)) {
      if ($entity = $this->entityManager->loadEntityByUuid($entity_type, $uuid)) {
        foreach ($entity->getFields() as $name => $field) {
          // The label should default to field name - unless it is overridden.
          $label = $name;
          // If a label override exists for the field name, use it.
          if (isset($label_overrides[$name])) {
            $label = $label_overrides[$name];
          }
          $attributes[$label] = $entity->{$name}->value;
        }
      }

      // Sort the elements alphabetically.
      ksort($attributes);

      return $attributes;
    }

    return NULL;
  }

}
