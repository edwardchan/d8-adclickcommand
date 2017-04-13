<?php

namespace Drupal\g9\Normalizer;

use Drupal\Core\Field\EntityReferenceFieldItemList;
use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Converts entity reference field list objects to arrays.
 *
 * This custom normalizer supports all entity reference field items.
 */
class G9ImagePoolReferenceItemListNormalizer extends NormalizerBase {

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

    // This custom normalizer should be picked up only for image pool fields.
    if ($data instanceof EntityReferenceFieldItemList) {
      return (bool) ($data->getFieldDefinition()->getType() == 'image_pool');
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $attributes = [];
    $index = 0;
    // Iterate through each field item, normalize it, and append to the array.
    foreach ($object as $fieldItem) {
      $attributes[$index] = $this->serializer->normalize($fieldItem, $format, $context);
      $index++;
    }

    // Sort the array output alphabetically.
    ksort($attributes);

    return (object) $attributes;
  }

}
