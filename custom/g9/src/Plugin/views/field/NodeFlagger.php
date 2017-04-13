<?php

namespace Drupal\g9\Plugin\views\field;

use Drupal\Core\Entity\EntityTypeManager;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Plugin\views\field\FieldPluginBase;
use Drupal\views\ResultRow;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Field handler to check a flag status in a node context.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_flagger")
 */
class NodeFlagger extends FieldPluginBase implements ContainerFactoryPluginInterface {

  protected $entityTypeManager;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, EntityTypeManager $entityTypeManager) {
    $this->entityTypeManager = $entityTypeManager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $configuration, $plugin_id, $plugin_definition,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Leave empty to avoid a query on this field.
  }

  /**
   * {@inheritdoc}
   */
  public function adminLabel($short = FALSE) {
    return $this->t('Node Flagger');
  }

  /**
   * Define the available options.
   *
   * @return array
   *   An array of options.
   */
  protected function defineOptions() {
    $options = parent::defineOptions();
    $options['node_flag'] = ['default' => ''];

    return $options;
  }

  /**
   * Provide the options form.
   */
  public function buildOptionsForm(&$form, FormStateInterface $form_state) {
    $terms = $this->entityTypeManager
      ->getStorage('taxonomy_term')
      ->loadTree('flags');

    $options = [];
    foreach ($terms as $term) {
      $options[$term->name] = $term->name;
    }

    $form['node_flag'] = [
      '#title' => $this->t('Which flag term?'),
      '#type' => 'select',
      '#default_value' => isset($this->options['node_flag']) ? $this->options['node_flag'] : '',
      '#options' => $options,
    ];

    parent::buildOptionsForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function render(ResultRow $values) {
    $node = $values->_entity;
    $return = '';

    if (is_object($node) && $node->bundle()) {
      $targets = $node->get('field_flags')->referencedEntities();

      $matches = [];
      foreach ($targets as $target) {
        $matches[] = $target->name->value;
      }

      $return = [
        '#markup' => in_array($this->options['node_flag'], $matches) ? 'Yes' : 'No',
      ];
    }

    return $return;
  }

}
