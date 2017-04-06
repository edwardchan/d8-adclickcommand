<?php

namespace Drupal\g9\Normalizer;

use Drupal\taxonomy\TermInterface;

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
class G9TermContentEntityNormalizer extends G9ContentEntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\taxonomy\TermInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    if ($entity instanceof TermInterface) {
      // Get the context.
      $context += $this->getContext();

      // Get the common attributes from G9ContentEntityNormalizer.
      $attributes = parent::normalize($entity, $format, $context);

      // Sort the display output alphabetically.
      ksort($attributes);
    }

    return $attributes;
  }

  /**
   * {@inheritdoc}
   */
  public function getMappingFile($entity_type = NULL) {
    return "g9.taxonomy_term.mapping";
  }

}
