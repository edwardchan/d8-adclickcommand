<?php

namespace Drupal\g9\Normalizer;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Config\ConfigFactory;
use Drupal\serialization\Normalizer\ContentEntityNormalizer;
use Drupal\Core\Datetime\DrupalDateTime;

/**
 * Converts the Drupal entity object structures to a normalized array.
 *
 * This is the default Normalizer for entities. All formats that have Encoders
 * registered with the Serializer in the DIC will be normalized with this
 * class unless another Normalizer is registered which supersedes it. If a
 * module wants to use format-specific or class-specific normalization, then
 * that module can register a new Normalizer and give it a higher priority than
 * this one.
 */
class G9ContentEntityNormalizer extends ContentEntityNormalizer {

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $configFactory;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\ContentEntityInterface';

  /**
   * The G9ContentEntityNormalizer constructor.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   The active database connection.
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(Connection $connection, EntityTypeManager $entity_type_manager, ConfigFactory $config_factory) {
    $this->connection = $connection;
    $this->entityTypeManager = $entity_type_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $attributes = [];

    // Iterate through each field on the entity to process the JSON output.
    foreach ($entity->getFields() as $field) {
      // If this field should be excluded from display or the current user
      // does not have access to view this field, move onto the next field.
      if ($this->isExcluded($field->getName(), $entity->bundle()) || !$field->access('view', $context['account'])) {
        continue;
      }

      // Call the helper method to determine if there is a field alias we
      // should override the Drupal field name with.
      $field_name = $this->getFieldAlias($field->getName(), $entity->bundle());
      // Append the value to the attributes output array.
      $attributes[$field_name] = $this->serializer->normalize($field, $format, $context);
    }

    // Get all the tagged taxonomy terms on the node.
    $all_tagged_terms = $this->getAllTaggedTerms($entity, $format, $context);
    $attributes['term'] = array_values($all_tagged_terms['all_tagged_terms']);
    $attributes['tid'] = array_keys($all_tagged_terms['all_tagged_terms']);

    // "New Taxonomy" values.
    $attributes['new_taxonomy'] = $all_tagged_terms['new_taxonomy'];

    // Convert the 'changed' timestamp to ISO8601 format.
    $changed_timestamp = $entity->getChangedTime();
    $changed_date = DrupalDateTime::createFromTimestamp($changed_timestamp);
    $attributes['changed'] = $changed_date->format('c');

    // The entity link.
    $attributes['link'] = $entity->toUrl()->toString();
    // The entity aliases.
    $attributes['url'] = $this->getUrlAliases($entity);

    // Custom ID value.
    $attributes['id'] = $entity->getEntityTypeId() . '-' . $entity->id();

    // The entity type.
    $attributes['node_type'] = $entity->bundle();

    $attributes['type'] = $entity->bundle();

    // Get any value overrides specified in the configuration.
    $attributes = array_merge($attributes, $this->getValueOverrides($entity));

    return $attributes;
  }

  /**
   * Returns the context values.
   *
   * @return array
   *   The context array.
   */
  protected function getContext() {
    return [
      'account' => NULL,
      'included_fields' => NULL,
      'top_level' => TRUE,
    ];
  }

  /**
   * Gets the value overrides as specified in the configuration.
   *
   * @param object $entity
   *   The entity to get value overrides for.
   *
   * @return array
   *   The array containing the fields and their overriding values.
   */
  protected function getValueOverrides($entity) {
    $overrides = [];
    // Get the configuration based on the entity bundle.
    $config = $this->getEntityTypeConfig($entity->bundle());
    // If there are values under the 'value_overrides' key, iterate through it.
    if ($values = $config->get('value_overrides')) {
      foreach ($values as $key => $value) {
        // Set the overrides array with the Drupal field name and the value.
        $overrides[$key] = $value;
      }
    }
    return $overrides;
  }

  /**
   * Helper method to get all the tagged terms on a node entity.
   *
   * @param object $entity
   *   The node entity.
   * @param string $format
   *   The format to normalize.
   * @param array $context
   *   The context data.
   *
   * @return array
   *   The array of terms keyed by the term ID.
   */
  protected function getAllTaggedTerms($entity, $format, array $context) {
    // The array to store all the tagged terms on the node.
    $terms = $all_terms = [];
    // Iterate through each field on the node entity to get all the tagged
    // taxonomy terms on the node.
    foreach ($entity->getFields() as $field) {
      // Only look for fields that are entity reference fields.
      if ($field instanceof EntityReferenceFieldItemList) {
        // Get the field settings.
        $field_definition = $field->getFieldDefinition();
        $target_type = $field_definition->getSetting('target_type');
        // Check that the field targets are taxonomy terms.
        if ($target_type == 'taxonomy_term') {
          // For each taxonomy reference field, get the referenced entities
          // and append them to the term array.
          $field_name = $field_definition->getName();
          // Get the referenced entities.
          $referenced_entities = $entity->{$field_name}->referencedEntities();
          foreach ($referenced_entities as $key => $term) {
            // Append the term name and id to the all_terms array.
            $all_terms[$term->id()] = $term->getName();
            // For new sub-arrays, cast an array from a stdClass object. This
            // helps in getting the numeric keys to display on the JSON output.
            if (!isset($terms[$term->getVocabularyId()])) {
              $vid = $term->getVocabularyId();
              $terms[$vid] = new \stdClass();
              $terms[$vid]->{0} = "";
              $terms[$vid] = (array) $terms[$vid];
            }

            // The terms are normalized in the custom taxonomy reference
            // field item normalizer.
            $context['top_level'] = FALSE;
            $terms[$vid] = $this->serializer->normalize($field, $format, $context);
          }
        }
      }
    }

    // Return the array of tagged terms on the node entity.
    return ['new_taxonomy' => $terms, 'all_tagged_terms' => $all_terms];
  }

  /**
   * Retrieves all the url aliases defined for a node entity.
   *
   * @param object $entity
   *   The node entity object.
   *
   * @return array
   *   An array containing all of the url aliases for the entity.
   */
  protected function getUrlAliases($entity) {
    // Get the internal path of the entity to be used to look for url aliases.
    $path = '/' . $entity->toUrl()->getInternalPath();
    $source = $this->connection->escapeLike($path);

    // Using the entity path, look up the aliases in the 'url_alias' table.
    $select = $this->connection->select('url_alias')
      ->fields('url_alias', ['alias'])
      ->condition('source', $source, 'LIKE');

    // Return all the aliases.
    return $select->execute()->fetchCol();
  }

  /**
   * Checks to see if the field should be excluded from display.
   *
   * It checks the configuration files (both entity type-specific and global)
   * to determine if the field has been listed under the 'exclude_fields'
   * key.
   *
   * @param string $field_name
   *   The field name to check if excluded.
   * @param string $entity_type
   *   The entity type that the field belongs to.
   *
   * @return bool
   *   Returns TRUE if the field is to be excluded, FALSE otherwise.
   */
  protected function isExcluded($field_name, $entity_type = NULL) {
    $field_mapping = [];

    // Check the entity type-specific configuration to see if the field is
    // listed under 'exclude_fields'.
    $exclude_fields = $this->getEntityTypeConfig($entity_type)->get('exclude_fields');
    if ($exclude_fields && in_array($field_name, $exclude_fields)) {
      return TRUE;
    }

    $exclude_fields = $this->getEntityTypeConfig()->get('exclude_fields');
    if ($exclude_fields && in_array($field_name, $exclude_fields)) {
      return TRUE;
    }

    return FALSE;
  }

  /**
   * Retrieves and returns the field alias given a field name.
   *
   * @param string $field_name
   *   The field name to look for an alias for.
   * @param string|null $entity_type
   *   The entity type.
   *
   * @return string
   *   The field name.
   */
  protected function getFieldAlias($field_name, $entity_type = NULL) {
    $field_mapping = [];

    // Get the entity type-specific configuration to see if there is an explicit
    // field mapping for this field.
    $config = $this->getEntityTypeConfig($entity_type);
    // If there is a mapping, return the value of that field alias.
    if (($field_mapping = $config->get('label_overrides')) && isset($field_mapping[$field_name])) {
      return $field_mapping[$field_name];
    }

    // Otherwise, check the global config for a mapping.
    $config = $this->getEntityTypeConfig();
    // If there is a mapping on the global configuration, return that value.
    if (($field_mapping = $config->get('label_overrides')) && isset($field_mapping[$field_name])) {
      return $field_mapping[$field_name];
    }

    // Otherwise, return the original field name.
    return $field_name;
  }

  /**
   * Helper method to return the mapping file given the entity type.
   *
   * @param string $entity_type
   *   The entity type.
   *
   * @return string
   *   The mapping file name.
   */
  public function getMappingFile($entity_type) {
    return "g9.$entity_type.mapping";
  }

  /**
   * Retrieves the configuration given an entity type.
   *
   * @param string|null $entity_type
   *   The entity type.
   *
   * @return \Drupal\Core\Config\Config
   *   The configuration.
   */
  protected function getEntityTypeConfig($entity_type = NULL) {
    // If no entity type was provided, return the global configuration.
    if (!$entity_type) {
      return $this->configFactory->get('g9.global.mapping');
    }

    // Get the mapping file name.
    $mapping_file = $this->getMappingFile($entity_type);
    // Return the entity type-specific configuration.
    return $this->configFactory->get($mapping_file);
  }

}
