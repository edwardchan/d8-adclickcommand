<?php

namespace Drupal\g9\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Entity\EntityTypeManager;
use Drupal\taxonomy\Entity\Vocabulary;

/**
 * Vertical Configuration form class.
 */
class VerticalConfigForm extends ConfigFormBase implements ContainerInjectionInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManager
   */
  protected $entityTypeManager;

  /**
   * VerticalConfigForm constructor.
   *
   * @param \Drupal\Core\Entity\EntityTypeManager $entity_type_manager
   *   The entity type manager.
   */
  public function __construct(EntityTypeManager $entity_type_manager) {
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vertical_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form = parent::buildForm($form, $form_state);
    $config = $this->config('g9.vertical.settings');

    $options = [];
    // Get all the vocabularies.
    foreach (Vocabulary::loadMultiple() as $type) {
      $options[$type->id()] = $type->label();
    }

    $form['vertical_mapping'] = [
      '#type' => 'fieldset',
      '#title' => $this->t('Corresponding "Vertical" vocabularies for the "Brands"'),
      '#tree' => TRUE,
    ];

    // The vertical mapping saved in configuration.
    $vertical_mapping = $config->get('vertical_mapping');

    // Get all the brand terms from storage.
    $brand_terms = $this->entityTypeManager->getStorage('taxonomy_term')->loadTree('brand');
    // For each brand term, create a select dropdown containing vocabulary
    // options that can be chosen as the corresponding "Vertical" vocabulary.
    foreach ($brand_terms as $brand) {
      $form['vertical_mapping']['vertical_vocabulary_' . $brand->tid] = [
        '#type' => 'select',
        '#title' => $this->t('":brand_name"', [':brand_name' => $brand->name]),
        '#options' => $options,
        '#default_value' => isset($vertical_mapping['vertical_vocabulary_' . $brand->tid]) ? $vertical_mapping['vertical_vocabulary_' . $brand->tid] : '',
        '#empty_option' => $this->t('-- Select --'),
        '#attributes' => [
          'class' => ['vertical_mapping_options'],
        ],
      ];
    }

    // A toggle checkbox that can be used to filter the options to only those
    // that contain "vertical" in the vocabulary name.
    $form['filter_by_vertical_string'] = [
      '#type' => 'checkbox',
      '#title' => $this->t("Filter by 'vertical' string"),
      '#description' => $this->t('Toggle to filter the options to those that have "vertical" in the name.'),
      '#default_value' => 1,
    ];

    // Attach our custom JS that will perform the option filtering.
    $form['#attached']['library'][] = 'g9/vertical-mapping';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('g9.vertical.settings');
    $config->set('vertical_mapping', $form_state->getValue('vertical_mapping'));

    // Save the configuration.
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Return the configuration names.
   */
  protected function getEditableConfigNames() {
    return [
      'g9.vertical.settings',
    ];
  }

}
