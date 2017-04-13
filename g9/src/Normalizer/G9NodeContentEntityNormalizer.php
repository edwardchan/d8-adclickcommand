<?php

namespace Drupal\g9\Normalizer;

use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\node\NodeInterface;

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
class G9NodeContentEntityNormalizer extends G9ContentEntityNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\node\NodeInterface';

  /**
   * {@inheritdoc}
   */
  public function normalize($entity, $format = NULL, array $context = []) {
    if ($entity instanceof NodeInterface) {
      // Get the context.
      $context += $this->getContext();

      // Get the common attributes from G9ContentEntityNormalizer.
      $attributes = parent::normalize($entity, $format, $context);

      // Convert the 'created' timestamp to ISO8601 format.
      $created_timestamp = $entity->getCreatedTime();
      $created_date = DrupalDateTime::createFromTimestamp($created_timestamp);
      $attributes['created'] = $created_date->format('c');

      // Published dates.
      $published_timestamp = $entity->published_at->value;
      $attributes['published_date'] = (int) $published_timestamp;
      $published_date = DrupalDateTime::createFromTimestamp($published_timestamp);
      $attributes['published'] = $published_date->format('c');
      $attributes['published_date_rss'] = $published_date->format('D, d M Y H:i:s e');
      $attributes['published_date_string'] = $published_date->format('m.d.y');

      // The sort date.
      $attributes['sort_date_dti'] = $published_date->format('c');

      // Sort the display output alphabetically.
      ksort($attributes);
    }

    return $attributes;
  }

}
