<?php

namespace Drupal\g9\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;

/**
 * Displays the author term id for 'author' taxonomy terms.
 */
class G9AuthorReferenceNormalizer extends G9TaxonomyReferenceFieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = EntityReferenceItem::class;

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // If we aren't dealing with an object or the format is not supported return
    // now.
    if (!is_object($data) || !$this->checkFormat($format)) {
      return FALSE;
    }

    // This custom normalizer should be picked up only for image pool fields.
    if ($data instanceof EntityReferenceItem) {
      $field_definition = $data->getFieldDefinition();
      $target_type = $field_definition->getFieldStorageDefinition()->getSetting('target_type');
      $handler = $field_definition->getSetting('handler_settings');
      if ($target_type == 'taxonomy_term' && !empty($handler['target_bundles'])) {
        foreach ($handler['target_bundles'] as $bundle) {
          if ($bundle == 'author') {
            return TRUE;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = parent::normalize($field_item, $format, $context);
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if ($entity = $field_item->get('entity')->getValue()) {
      $entity_id = $entity->id();
      // Get the entity type id.
      $entity_type_id = $entity->getEntityTypeId();
      // Only return if it is the top level.
      if (isset($context['top_level']) && $context['top_level']) {
        // Set the return as the term id.
        $values = (int) $entity_id;
      }
      // Otherwise, output all the general information about the term entity.
      else {
        // From the term storage, load the parents by the term id.
        $term_storage = $this->entityTypeManager->getStorage('taxonomy_term');
        $parents = array_keys($term_storage->loadParents($entity_id));
        // If there is only 1 parent, just get that value. Otherwise, keep
        // it as an array of parent tids.
        if (count($parents) == 1) {
          $parents = reset($parents);
        }
        // Set the term values.
        $values += [
          'tid' => (int) $entity_id,
          'name' => $entity->getName(),
          'parent_tid' => $parents,
        ];

        // Sort the values.
        ksort($values);
      }
    }

    return $values;
  }

}
