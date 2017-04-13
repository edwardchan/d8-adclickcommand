<?php

namespace Drupal\g9\Normalizer;

use Drupal\node\Entity\Node;

/**
 * Normalizes/denormalizes Drupal content entities into an array structure.
 */
class G9ImagePoolContentEntityNormalizer extends G9NodeContentEntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var array
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\Entity\ContentEntityInterface';

  /**
   * {@inheritdoc}
   */
  public function supportsNormalization($data, $format = NULL) {
    // If we aren't dealing with an object or the format is not supported return
    // now.
    if (!is_object($data) || !$this->checkFormat($format)) {
      return FALSE;
    }

    if ($data instanceof Node) {
      return TRUE;
    }
    return FALSE;
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    $attributes = parent::normalize($entity, $format, $context);

    // Iterate through each field on the node entity and search for the
    // image_pool fields to get the images attached on these fields.
    foreach ($entity as $name => $field) {
      // Get the field definition to check if it is of type 'image_pool'.
      $field_definition = $field->getFieldDefinition();
      if ($field_definition->getType() == 'image_pool') {
        // For each referenced media entity on the 'image_pool' field, create
        // an 'images' array and append to that indexed by the image size.
        $values = $this->serializer->normalize($field, $format, $context);
        $index = 0;
        foreach ($values as $value) {
          if (isset($value['image_size'])) {
            $key = $value['image_size'];
            if (!isset($attributes['images'][$key])) {
              // The sub-array should be an object to preserve the numeric keys.
              $attributes['images'][$key] = (object) [];
            }
            // Append the value to the sub-array keyed by the type.
            $index = count((array) $attributes['images'][$key]);
            $attributes['images'][$key]->{$index} = $value;
            $index++;
          }
          // Otherwise, if ther is no image size, just append it to the array.
          else {
            $attributes['images'][] = $value;
          }
        }
        $attributes = array_filter($attributes);
        // Sort the array alphabetically.
        ksort($attributes);
      }
    }

    return (object) $attributes;
  }

}
