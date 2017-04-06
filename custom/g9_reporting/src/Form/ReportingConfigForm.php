<?php

namespace Drupal\g9_reporting\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Entity\EntityFieldManager;

/**
 * Reporting Configuration form class.
 */
class ReportingConfigForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManager
   */
  protected $entityFieldManager;

  /**
   * ReportingConfigForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Entity\EntityFieldManager $entity_field_manager
   *   The entity field manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager, EntityFieldManager $entity_field_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager'),
      $container->get('entity_field.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'reporting_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('g9_reporting.settings');

    $options = [];
    // Get all the content types from the entity type manager storage.
    $content_types = $this->entityTypeManager
      ->getStorage('node_type')
      ->loadMultiple();
    foreach ($content_types as $type) {
      // Get the field definitions of the node type.
      $field_definitions = $this->entityFieldManager->getFieldDefinitions('node', $type->id());
      // Only show the content types that have a 'field_reporting'.
      if (isset($field_definitions['field_reporting'])) {
        $options[$type->id()] = $type->label();
      }
    }

    $form['enabled'] = [
      '#type' => 'checkboxes',
      '#title' => $this->t('Enabled content types'),
      '#options' => $options,
      '#default_value' => $config->get('enabled') ? $config->get('enabled') : '',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('g9_reporting.settings');
    $config->set('enabled', $form_state->getValue('enabled'));
    // Save the configuration.
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return the configuration names.
   */
  protected function getEditableConfigNames() {
    return [
      'g9_reporting.settings',
    ];
  }

}
