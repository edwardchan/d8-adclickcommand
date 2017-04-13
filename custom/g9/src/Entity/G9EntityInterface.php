<?php

namespace Drupal\g9\Entity;


use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for storing and retrieving data.
 */
interface G9EntityInterface {

  /**
   * Read records for an array of entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Array of entities on which commenting is enabled, keyed by id.
   * @param string $entity_type
   *   The entity type of the passed entities.
   * @param bool $accurate
   *   (optional) Indicates if results must be completely up to date. If set to
   *   FALSE, a replica database will used if available. Defaults to TRUE.
   *
   * @return object[]
   *   An object.
   */
  public function read(array $entities, $entity_type, $accurate = TRUE);

  /**
   * Delete records for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which comment statistics should be deleted.
   */
  public function delete(EntityInterface $entity);

  /**
   * Update or insert records.
   */
  public function update();

  /**
   * Insert an empty record for the given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The created entity for which a statistics record is to be initialized.
   * @param array $fields
   *   Array of comment field definitions for the given entity.
   */
  public function create(FieldableEntityInterface $entity, array $fields);

}
