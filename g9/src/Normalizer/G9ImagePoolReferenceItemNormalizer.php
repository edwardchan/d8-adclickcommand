<?php

namespace Drupal\g9\Normalizer;

use Drupal\artemis\ArtemisImageUriTrait;
use Drupal\serialization\Normalizer\EntityReferenceFieldItemNormalizer;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Config\ConfigFactory;

/**
 * Alters the JSON output of 'image_pool' field reference values.
 */
class G9ImagePoolReferenceItemNormalizer extends EntityReferenceFieldItemNormalizer {

  use ArtemisImageUriTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactory
   */
  protected $config;

  /**
   * The interface or class that this Normalizer supports.
   *
   * @var string
   */
  protected $supportedInterfaceOrClass = 'Drupal\artemis\Plugin\Field\FieldType\ImagePool';

  /**
   * Constructs a TaxonomyMetaResolver object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Config\ConfigFactory $config_factory
   *   The config factory.
   */
  public function __construct(EntityManagerInterface $entity_manager, ConfigFactory $config_factory) {
    $this->entityManager = $entity_manager;
    $this->config = $config_factory->get("g9.taxonomy_term.mapping");
  }

  /**
   * {@inheritdoc}
   */
  public function normalize($field_item, $format = NULL, array $context = []) {
    $values = parent::normalize($field_item, $format, $context);

    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    if ($entity = $field_item->get('entity')->getValue()) {
      $artemis_id = (int) $entity->get('field_id')->value;
      $values['image_id'] = $artemis_id;
      $values['clickthrough_url'] = $this->getUri($artemis_id);
      $values['image_size'] = $entity->get('field_type')->value;

      $values['target_type'] = $entity->getEntityTypeId();
      // Add the target entity UUID to the normalized output values.
      $values['target_uuid'] = $entity->uuid();

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
