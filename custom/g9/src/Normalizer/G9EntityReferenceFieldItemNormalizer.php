<?php

namespace Drupal\g9\Normalizer;

use Drupal\Core\Field\Plugin\Field\FieldType\EntityReferenceItem;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer;

/**
 * Adds values to the display for entity reference items.
 */
class G9EntityReferenceFieldItemNormalizer extends EntityReferenceFieldItemNormalizer {

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = EntityReferenceItem::class;

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = parent::normalize($field_item, $format, $context);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if ($entity = $field_item->get('entity')->getValue()) {
      $entity_type_id = $entity->getEntityTypeId();

      $values['target_type'] = $entity_type_id;
      // Add the target entity UUID to the normalized output values.
      $values['target_uuid'] = $entity->uuid();
      $values['name'] = $entity->label();

      // Add a 'url' value if there is a reference and a canonical URL. Hard
      // code 'canonical' here as config entities override the default $rel
      // parameter value to 'edit-form.
      if ($url = $entity->url('canonical')) {
        $values['url'] = $url;
      }
    }

    return $values;
  }

}
