<?php

namespace Drupal\g9\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer;
use Drupal\serialization\EntityResolver\UuidReferenceInterface;
use Drupal\Core\Config\ConfigFactory;
use Drupal\serialization\EntityResolver\EntityResolverInterface;
use Drupal\Core\Entity\EntityTypeManager;

/**
 * Adds values to the display for entity reference items.
 *
 * This includes the 'meta' values for each taxonomy term, which prints out
 * all the term fields and values.
 */
class G9TaxonomyReferenceFieldItemNormalizer extends EntityReferenceFieldItemNormalizer implements UuidReferenceInterface {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = EntityReferenceItem::class;

  /**
   * The G9TaxonomyReferenceFieldItemNormalizer constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   * @param \Drupal\serialization\EntityResolver\EntityResolverInterface $entity_resolver
   *   The entity resolver.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity_type_manager.
   */
  public function __construct(ConfigFactory $config_factory, EntityResolverInterface $entity_resolver, EntityTypeManager $entity_type_manager) {
    $this->configFactory = $config_factory;
    $this->entityResolver = $entity_resolver;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = parent::normalize($field_item, $format, $context);
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if ($entity = $field_item->get('entity')->getValue()) {
      if (($entity_type_id = $entity->getEntityTypeId()) && ($entity_type_id == 'taxonomy_term')) {
        $values = [];
        $term_id = $entity->id();
        // From the entity type id, get the configuration.
        $config = $this->getConfiguration($entity_type_id);
        // Get the 'show_fields' value which is a list of taxonomy vocabularies
        // that should show the fields.
        $show_fields = $config->get('show_fields');
        // If the config values exist and the entity's vocabulary id is supposed
        // to show the fields, call the helper method to normalize.
        if ($show_fields && in_array($entity->getVocabularyId(), $show_fields)) {
          // Add the 'uuid' from the context, so it can be used by the resolver.
          $context += [
            'uuid' => $entity->get('uuid')->value,
          ];
          $values = $this->constructMetaValue($field_item, $format, $context);
        }
        // From the term storage, load the parents by the term id.
        $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
        $parents = array_keys($term_storage->loadParents($term_id));
        // If there is only 1 parent, just get that value. Otherwise, keep
        // it as an array of parent tids.
        if (count($parents) == 1) {
          $parents = reset($parents);
        }

        // If it is a top-level field, only output the term name.
        if (isset($context['top_level']) && $context['top_level']) {
          return $entity->getName();
        }
        // Otherwise, output all the general information about the term entity.
        else {
          // Set the term values.
          $values += [
            'tid' => (int) $term_id,
            'name' => $entity->getName(),
            'parent_tid' => $parents,
          ];
        }

        // Sort the elements alphabetically.
        ksort($values);
      }
    }
    return $values;
  }

  /**
   * Generates and returns the field values for the entity.
   *
   * @param object $field_item
   *   The entity reference field item.
   * @param string $format
   *   The format to normalize.
   * @param array $context
   *   The context.
   *
   * @return array|null
   *   The 'meta' values for the entity.
   */
  protected function constructMetaValue($field_item, $format, array $context) {
    // Get the field definition and use it to get the target_type.
    $field_definition = $field_item->getFieldDefinition();
    $target_type = $field_definition->getSetting('target_type');
    // Call the resolver to get the metadata values for the entity.
    $meta = $this->entityResolver->resolve($this, $context, $target_type);
    if ($meta) {
      return ['meta' => $meta];
    }
    return $this->serializer->normalize($field_item, $format, $context);;
  }

  /**
   * Returns the configuration based on the entity type id.
   *
   * @param string $entity_type_id
   *   The entity type id.
   *
   * @return \Drupal\Core\Config\Config|\Drupal\Core\Config\ImmutableConfig
   *   The configuration.
   */
  protected function getConfiguration($entity_type_id) {
    return $this->configFactory->get("g9.$entity_type_id.mapping");
  }

  /**
   * {@inheritdoc}
   */
  public function getUuid($data) {
    if (isset($data['uuid'])) {
      $uuid = $data['uuid'];
      // The value may be a nested array like $uuid[0]['value'].
      if (is_array($uuid) && isset($uuid[0]['value'])) {
        $uuid = $uuid[0]['value'];
      }
      return $uuid;
    }
  }

}
