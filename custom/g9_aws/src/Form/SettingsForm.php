<?php

namespace Drupal\g9_aws\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\NodeType;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class AwsSettingsForm.
 *
 * @package Drupal\g9_aws\Form
 */
class SettingsForm extends ConfigFormBase {

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Constructs a new AwsSettingsForm instance.
   */
  public function __construct(ConfigFactoryInterface $config_factory) {
    $this->configFactory = $config_factory;
    parent::__construct($config_factory);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'g9_aws_settings';
  }

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return ['g9_aws.settings'];
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('g9_aws.settings');

    $form['sns_topic_arn'] = [
      '#type' => 'textfield',
      '#title' => 'AWS SNS Topic',
      '#description' => 'This should be the ARN of the SNS Topic',
      '#default_value' => $config->get('sns_topic_arn', ''),
      '#max_length' => 255,
    ];

    $form['aws_accepted_types'] = [
      '#type' => 'checkboxes',
      '#title' => 'Accepted AWS SNS Types',
      '#default_value' => $config->get('aws_accepted_types', []),
      '#options' => $this->getNodeTypes(),
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * A helper function to return all node types.
   *
   * @return array
   *   The array of node types.
   */
  public function getNodeTypes() {
    $all_content_types = NodeType::loadMultiple();

    /** @var \Drupal\node\Entity\NodeType $content_type */
    $labels = [];
    foreach ($all_content_types as $machine_name => $content_type) {
      $labels[$machine_name] = $content_type->label();
    }
    return $labels;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $config = $this->config('g9_aws.settings');
    $config->set('sns_topic_arn', $form_state->getValue('sns_topic_arn'))
      ->save();
    $config->set('aws_accepted_types', $form_state->getValue('aws_accepted_types'))
      ->save();

    $config->save();
    parent::submitForm($form, $form_state);
  }

}
