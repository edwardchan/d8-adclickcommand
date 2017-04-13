<?php

namespace Drupal\g9\Normalizer;

use Drupal\serialization\Normalizer\NormalizerBase;

/**
 * Converts typed data objects to arrays.
 */
class G9TypedDataNormalizer extends NormalizerBase {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\Core\TypedData\TypedDataInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($object, $format = NULL, array $context = []) {
    $value = $object->getValue();
    if (is_array($value)) {
      $value = array_column($value, 'value');
      if (isset($value[0])) {
        $value = $value[0];
      }
    }
    return $value;
  }

}
