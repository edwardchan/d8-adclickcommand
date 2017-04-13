<?php

namespace Drupal\g9\Normalizer;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Converts entity reference field list objects to arrays.
 *
 * This custom normalizer supports all entity reference field items.
 */
class G9RelatedNodesReferenceItemListNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\EntityReferenceFieldItemListInterface';

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // If we aren't dealing with an object or the format is not supported return
    // now.
    if (!is_object($data) || !$this->checkFormat($format)) {
      return FALSE;
    }

    // This normalizer should apply to entity reference fields targeting nodes
    // and contains 'related' in the field name.
    if ($data instanceof EntityReferenceFieldItemList) {
      // Get the target type from the field storage definition.
      $target_type = $data->getFieldDefinition()
        ->getFieldStorageDefinition()
        ->getSetting('target_type');
      // If the target type is node, check if the field has 'field_related' in
      // the field name.
      if ($target_type == 'node') {
        return (bool) (preg_match('/^field_related_content/', $data->getName()) === 1);
      }
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = [];
    $index = 0;
    // Iterate through each referenced entity and append the id to the array.
    foreach ($object as $field_item) {
      if ($entity = $field_item->get('entity')->getValue()) {
        $attributes[$entity->bundle()][] = (int) $entity->id();
      }
    }

    // Sort the array output alphabetically.
    ksort($attributes);

    return (object) $attributes;
  }

}
