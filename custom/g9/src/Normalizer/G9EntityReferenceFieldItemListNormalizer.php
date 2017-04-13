<?php

namespace Drupal\g9\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Converts entity reference field list objects to arrays.
 *
 * This custom normalizer supports all entity reference field items.
 */
class G9EntityReferenceFieldItemListNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Field\EntityReferenceFieldItemListInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $index = 0;
    $attributes = [];
    // Iterate through each field item, normalize it, and append to the array.
    foreach ($object as $fieldItem) {
      $attributes[$index] = $this->serializer->normalize($fieldItem, $format, $context);
      $index++;
    }

    // If this is a top-level field output, do not output the array indices.
    if (isset($context['top_level']) && $context['top_level']) {
      // Filter out any elements that may be empty or false.
      $attributes = array_filter($attributes);
      // Get the values of the array without the indices.
      $attributes = array_values($attributes);
      // De-dupe array elements.
      $attributes = array_unique($attributes, SORT_REGULAR);

      return $attributes;
    }

    // Sort the array output alphabetically.
    ksort($attributes);

    return (object) $attributes;
  }

}
