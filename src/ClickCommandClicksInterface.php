<?php

namespace Drupal\adclickcommand;

use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface for storing and retrieving comment statistics.
 */
interface AdClickCommandInterface {

  /**
   * Read records for an array of entities.
   *
   * @param \Drupal\Core\Entity\EntityInterface[] $entities
   *   Array of entities on which commenting is enabled, keyed by id
   * @param string $entity_type
   *   The entity type of the passed entities.
   * @param bool $accurate
   *   (optional) Indicates if results must be completely up to date. If set to
   *   FALSE, a replica database will used if available. Defaults to TRUE.
   *
   * @return object[]
   *
   */
  public function read($entities, $entity_type, $accurate = TRUE);

  /**
   * Delete records for an entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity for which comment statistics should be deleted.
   */
  public function delete(EntityInterface $entity);

  /**
   * Update or insert records after a comment is added.
   *
   * @param \Drupal\comment\CommentInterface $comment
   *   The comment added or updated.
   */
  public function update(AdClickCommand $adClickCommand);

  /**
   * Find the number of unique_clicks.
   **
   * @param int
   *   The ccid.
   *
   * @return int
   *   The maximum number of comments for and entity of the given type.
   *
   */
   // public function getClickCount($ccid);

  /**
   * Insert an empty record for the given entity.
   *
   * @param \Drupal\Core\Entity\FieldableEntityInterface $entity
   *   The created entity for which a statistics record is to be initialized.
   * @param array $fields
   *   Array of comment field definitions for the given entity.
   */
  public function create(FieldableEntityInterface $entity, $fields);

}
